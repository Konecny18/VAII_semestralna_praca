<?php

namespace App\Controllers;

use App\Models\Event;
use App\Configuration;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class EventController
 *
 * Spravuje CRUD operácie pre podujatia (events). Zahŕňa nahrávanie plagátu a PDF propozícií,
 * validáciu dátumu (musia byť v budúcnosti) a obmedzenie prístupu na administrátorov pre úpravy.
 *
 * @package App\Controllers
 */
class EventController extends BaseController
{
    /**
     * Zobrazí zoznam všetkých podujatí (zoradené podľa dátumu udalosti).
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $events = Event::getAll(null, [], 'datum_podujatia DESC');
        return $this->html(compact('events'));
    }

    /**
     * Zobrazí formulár na vytvorenie nového podujatia (len admin).
     *
     * @return Response
     */
    public function add(): Response
    {
        $this->checkAdmin();
        return $this->html(['event' => new Event()]);
    }

    /**
     * Zobrazí formulár na úpravu existujúceho podujatia (len admin).
     *
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request): Response
    {
        $this->checkAdmin();
        $event = Event::getOne((int)$request->value('id'));
        if (!$event) {
            return $this->redirect($this->url('event.index'));
        }
        return $this->html(compact('event'), 'edit');
    }

    /**
     * Spracuje uloženie nového alebo upraveného podujatia. Spracováva nahraté súbory a validuje vstup.
     *
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        $this->checkAdmin();

        $id = $request->post('id');
        $isEdit = !empty($id);
        $event = $isEdit ? Event::getOne((int)$id) : new Event();

        if ($request->isPost()) {
            // Zavoláme samostatnú validačnú metódu
            $errors = $this->formErrors($request, $isEdit);

            if (empty($errors)) {
                $data = $request->post();

                // 1. Spracovanie súborov (iba ak sú nahraté nové)
                $plagatPath = $this->uploadFile($request, 'plagat', $event->getPlagat());
                $docPath = $this->uploadFile($request, 'dokument_propozicie', $event->getDokumentPropozicie());

                // 2. Nastavenie hodnôt (Sanitizácia textov)
                $event->setNazov(strip_tags($data['nazov'] ?? ''));
                $event->setPopis(strip_tags($data['popis'] ?? ''));
                $event->setLinkPrihlasovanie(strip_tags($data['link_prihlasovanie'] ?? ''));
                $event->setDatumPodujatia($data['datum_podujatia']);
                $event->setPlagat($plagatPath);
                $event->setDokumentPropozicie($docPath);

                // 3. Uloženie
                $event->save();
                return $this->redirect($this->url('event.index'));
            }
        }

        // Ak sú chyby, vrátime sa späť do formulára
        return $this->html(['errors' => $errors ?? [], 'event' => $event], $isEdit ? 'edit' : 'add');
    }

    /**
     * Odstráni podujatie vrátane súborov (plagát, propozície). Len pre admin.
     *
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $this->checkAdmin();

        try {
            $id = (int)$request->value('id');
            $event = Event::getOne($id);

            if (!$event) {
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Podujatie neexistuje.'], 404);
                }
                throw new HttpException(404);
            }

            // Najskôr upraceme súbory z disku
            $this->deleteFile($event->getPlagat());
            $this->deleteFile($event->getDokumentPropozicie());

            // Potom zmažeme záznam z DB
            $event->delete();

            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (\Exception $e) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()], 500);
            }
            throw new HttpException(500, $e->getMessage());
        }

        return $this->redirect($this->url('event.index'));
    }

    /**
     * Pomocná metóda na kontrolu Admina
     */
    private function checkAdmin(): void
    {
        if (!$this->user->isLoggedIn()) {
            // Ak nie je prihlásený, presmeruj na login (vyžaduje Configuration::LOGIN_URL)
            header('Location: ' . Configuration::LOGIN_URL);
            exit;
        }

        $identity = $this->user->getIdentity();
        if (($identity?->getRole() ?? null) !== 'admin') {
            throw new HttpException(403, 'Nemáte oprávnenie na túto akciu.');
        }
    }

    private function uploadFile(Request $request, string $inputName, ?string $oldFile): string
    {
        $file = $request->file($inputName);
        if ($file && $file->isOk() && $file->getName() !== '') {
            $path = 'uploads/' . time() . '_' . $file->getName();
            if ($file->store(dirname(__DIR__, 2) . '/public/' . $path)) {
                $this->deleteFile($oldFile);
                return $path;
            }
        }
        return $oldFile ?? '';
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            $fullPath = dirname(__DIR__, 2) . '/public/' . $path;
            if (file_exists($fullPath)) @unlink($fullPath);
        }
    }

    private function formErrors(Request $request, bool $isEdit): array
    {
        $errors = [];
        $data = $request->post();

        if (empty($data['nazov'])) $errors[] = 'Názov je povinný.';
        if (empty($data['datum_podujatia'])) $errors[] = 'Dátum je povinný.';

        if (!empty($data['datum_podujatia'])) {
            $eventDate = \DateTime::createFromFormat('Y-m-d', $data['datum_podujatia']);
            $today = new \DateTime('today');
            if (!$eventDate) {
                $errors[] = 'Dátum podujatia je neplatný.';
            } elseif ($eventDate <= $today) {
                $errors[] = 'Dátum podujatia musí byť neskôr ako dnešný deň.';
            }
        }

        // Validácia plagátu
        $plagatFile = $request->file('plagat');
        if (!$isEdit && (!$plagatFile || !$plagatFile->isOk())) {
            $errors[] = 'Plagát je povinný pri vytváraní nového podujatia.';
        }

        if ($plagatFile && $plagatFile->isOk() && $plagatFile->getName() !== '') {
            $allowedImgTypes = ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];
            if (!in_array(strtolower($plagatFile->getType()), $allowedImgTypes)) {
                $errors[] = 'Plagát musí byť vo formáte JPG alebo PNG.';
            }
            if ($plagatFile->getSize() > 2 * 1024 * 1024) {
                $errors[] = 'Plagát nesmie byť väčší ako 2 MB.';
            }
        }

        // Validácia PDF dokumentu
        $docFile = $request->file('dokument_propozicie');
        if ($docFile && $docFile->isOk() && $docFile->getName() !== '') {
            if (strtolower($docFile->getType()) !== 'application/pdf') {
                $errors[] = 'Dokument musí byť vo formáte PDF.';
            }
            if ($docFile->getSize() > 2 * 1024 * 1024) {
                $errors[] = 'Dokument nesmie byť väčší ako 2 MB.';
            }
        }

        return $errors;
    }
}
