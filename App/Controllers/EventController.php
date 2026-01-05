<?php

namespace App\Controllers;

use App\Models\Event;
use App\Configuration;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class EventController extends BaseController
{
    public function index(Request $request): Response
    {
        $events = Event::getAll(null, [], 'datum_podujatia DESC');
        return $this->html(compact('events'));
    }

    public function add(): Response
    {
        // Iba admin môže pristupovať k formuláru na pridanie
        $this->checkAdmin();
        return $this->html(['event' => new Event()]);
    }

    public function edit(Request $request): Response
    {
        // Iba admin môže upravovať
        $this->checkAdmin();

        $event = Event::getOne((int)$request->value('id'));
        if (!$event) {
            return $this->redirect($this->url('event.index'));
        }

        return $this->html(compact('event'), 'edit');
    }

    public function save(Request $request): Response
    {
        // Iba admin môže ukladať dáta
        $this->checkAdmin();

        $id = $request->post('id');
        $event = $id ? Event::getOne((int)$id) : new Event();
        $errors = [];

        if ($request->isPost()) {
            $data = $request->post();

            // 1. Validácia textov
            if (empty($data['nazov'])) $errors[] = 'Názov je povinný.';
            if (empty($data['datum_podujatia'])) $errors[] = 'Dátum je povinný.';

            // Validácia dátumu (nesmie byť v minulosti)
            if (!empty($data['datum_podujatia'])) {
                $eventDate = \DateTime::createFromFormat('Y-m-d', $data['datum_podujatia']);
                $today = new \DateTime('today');
                if (!$eventDate) {
                    $errors[] = 'Dátum podujatia je neplatný.';
                } else {
                    if ($eventDate <= $today) {
                        $errors[] = 'Dátum podujatia musí byť neskôr ako dnešný deň.';
                    }
                }
            }

            // 2. Validácia súborov (Plagát a Dokument)
            $plagatFile = $request->file('plagat');
            if (!$id && (!$plagatFile || !$plagatFile->isOk())) {
                $errors[] = 'Plagát je povinný pri vytváraní nového podujatia.';
            }

            // Kontrola plagátu (formát a veľkosť 2MB)
            if ($plagatFile && $plagatFile->isOk() && $plagatFile->getName() !== '') {
                $allowedImgTypes = ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];
                if (!in_array(strtolower($plagatFile->getType()), $allowedImgTypes) && !preg_match('/\.(jpe?g|png)$/i', $plagatFile->getName())) {
                    $errors[] = 'Plagát musí byť vo formáte JPG alebo PNG.';
                }
                if ($plagatFile->getSize() > 2 * 1024 * 1024) {
                    $errors[] = 'Plagát nesmie byť väčší ako 2 MB.';
                }
            }

            // Kontrola dokumentu (PDF a veľkosť 2MB)
            $docFile = $request->file('dokument_propozicie');
            if ($docFile && $docFile->isOk() && $docFile->getName() !== '') {
                if (strtolower($docFile->getType()) !== 'application/pdf' && !preg_match('/\.pdf$/i', $docFile->getName())) {
                    $errors[] = 'Dokument musí byť vo formáte PDF.';
                }
                if ($docSize = $docFile->getSize() > 2 * 1024 * 1024) {
                    $errors[] = 'Dokument nesmie byť väčší ako 2 MB.';
                }
            }

            if (empty($errors)) {
                // 3. Nahranie súborov
                $plagatPath = $this->uploadFile($request, 'plagat', $event->getPlagat());
                $docPath = $this->uploadFile($request, 'dokument_propozicie', $event->getDokumentPropozicie());

                // 4. Uloženie modelu
                $event->setNazov(strip_tags($data['nazov']));
                $event->setPopis(strip_tags($data['popis']));
                $event->setLinkPrihlasovanie($data['link_prihlasovanie']);
                $event->setDatumPodujatia($data['datum_podujatia']);
                $event->setPlagat($plagatPath);
                $event->setDokumentPropozicie($docPath);

                $event->save();
                return $this->redirect($this->url('event.index'));
            }
        }

        return $this->html(compact('errors', 'event'), $id ? 'edit' : 'add');
    }

    public function delete(Request $request): Response
    {
        // Iba admin môže mazať
        $this->checkAdmin();

        $event = Event::getOne((int)$request->value('id'));
        if ($event) {
            $this->deleteFile($event->getPlagat());
            $this->deleteFile($event->getDokumentPropozicie());
            $event->delete();
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
}