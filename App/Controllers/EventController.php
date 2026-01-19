<?php

namespace App\Controllers;

use App\Models\Event;
use DateTime;
use Exception;
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
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        // 1. ZÍSKANIE AUTENTIFIKÁTORA
        // Autentifikátor je objekt, ktorý sa stará o informácie o prihlásenom používateľovi.
        // Budem ho potrebovať v šablóne (view), aby som vedel zobraziť napr. tlačidlá pre Admina.
        $auth = $this->app->getAuthenticator();

        try {
            // 2. NAČÍTANIE PODUJATÍ (EVENTS) Z DATABÁZY
            // 'datum_podujatia ASC' - zoradíme podujatia od najbližšieho (vzostupne)
            $events = Event::getAll(null, [], 'datum_podujatia ASC');

            // 3. ODOSLANIE DÁT DO ŠABLÓNY (VIEW)
            // 'events' - zoznam všetkých akcií z DB
            // 'auth' - objekt na kontrolu prihlásenia (isAdmin(), isLogged() atď.)
            return $this->html([
                'events' => $events,
                'auth' => $auth
            ]);
        } catch (Exception $e) {
            // 4. OŠETRENIE CHÝB
            // Ak zlyhá databáza alebo model, vyhodíme chybu 500 (Server Error)
            // Je to lepšie ako zobraziť prázdnu bielu stránku.
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }
    }

    /**
     * Zobrazí formulár na vytvorenie nového podujatia (len admin).
     *
     * @return Response
     * @throws HttpException
     */
    public function add(): Response
    {
        // IBA PRE ADMINA
        $this->checkAdmin();
        // 2. PRÍPRAVA NOVÉHO OBJEKTU
        // Do šablóny (view) posielame úplne novú, prázdnu inštanciu modelu Event.
        // Robíme to preto, aby formulár v HTML vedel, aké polia má očakávať,
        // a aby sme mohli použiť rovnakú šablónu pre pridávanie aj pre editáciu.
        return $this->html(['event' => new Event()]);
    }

    /**
     * Zobrazí formulár na úpravu existujúceho podujatia (len admin).
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
     */
    public function edit(Request $request): Response
    {
        // IBA PRE ADMINA
        $this->checkAdmin();

        // 2. ZÍSKANIE DÁT Z DATABÁZY
        // $request->value('id') vytiahne ID z URL adresy (napr. event/edit?id=5)
        // (int) zabezpečí, že hodnotu pretypujeme na celé číslo (ochrana pred nečakaným textom)
        $event = Event::getOne((int)$request->value('id'));

        // 3. KONTROLA EXISTENCIE
        // Ak by niekto v URL zadal ID, ktoré neexistuje, $event bude null.
        // V tom prípade používateľa presmerujeme späť na zoznam, aby aplikácia nespadla na chybe.
        if (!$event) {
            return $this->redirect($this->url('event.index'));
        }

        // 4. ZOBRAZENIE FORMULÁRA
        // compact('event') vytvorí pole ['event' => $event]
        // 'edit' hovorí, že chceme použiť šablónu pre editáciu
        return $this->html(compact('event'), 'edit');
    }

    /**
     * Spracuje uloženie nového alebo upraveného podujatia. Spracováva nahraté súbory a validuje vstup.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
     */
    public function save(Request $request): Response
    {
        // IBA PRE ADMINA
        $this->checkAdmin();

        // 2. ROZHODNUTIE: ADD vs EDIT
        // Ak v POST dátach existuje ID, vieme, že upravujeme existujúci záznam.
        $id = $request->post('id');
        $isEdit = !empty($id);

        // Ak editujeme, vytiahneme pôvodné dáta z DB. Ak pridávame, vytvoríme prázdny objekt.
        $event = $isEdit ? Event::getOne((int)$id) : new Event();

        if ($request->isPost()) {
            /* --- 3. BEZPEČNOSŤ (CSRF) --- */
            // Overíme, či formulár prišiel z nášho webu a nie od útočníka.
            $this->validateCsrf($request);
            /* --- 4. VALIDÁCIA --- */
            // Voláme vlastnú metódu formErrors, ktorá vráti pole textových chýb.
            $errors = $this->formErrors($request, $isEdit);

            if (empty($errors)) {
                $data = $request->post();

                /* --- 5. DISKOVÉ OPERÁCIE (UPLOADY) --- */
                // uploadFile je pomocná metóda. Ak používateľ nahral nový súbor,
                // vráti novú cestu. Ak nie, vráti cestu k pôvodnému súboru ($event->getPlagat()).
                $plagatPath = $this->uploadFile($request, 'plagat', $event->getPlagat());
                $docPath = $this->uploadFile($request, 'dokument_propozicie', $event->getDokumentPropozicie());

                /* --- 6. SANITIZÁCIA A PLNENIE OBJEKTU --- */
                // strip_tags() chráni databázu pred nežiaducim HTML kódom (XSS ochrana).
                $event->setNazov(strip_tags($data['nazov'] ?? ''));
                $event->setPopis(strip_tags($data['popis'] ?? ''));
                $event->setLinkPrihlasovanie(strip_tags($data['link_prihlasovanie'] ?? ''));
                $event->setDatumPodujatia($data['datum_podujatia']);
                $event->setPlagat($plagatPath);
                $event->setDokumentPropozicie($docPath);

                /* --- 7. PERZISTENCIA (ZÁPIS) --- */
                // Model Event sám rozhodne, či vykoná SQL INSERT alebo UPDATE
                $event->save();

                // Po úspešnom uložení presmerujem na zoznam podujatí.
                return $this->redirect($this->url('event.index'));
            }
        }

        // 8. NÁVRAT PRI CHYBE
        // Ak sa niečo nepodarilo (nevyplnené polia atď.), vrátime používateľa do formulára
        // a pribalíme pole chýb, aby vedel, čo má opraviť.
        return $this->html([
            'errors' => $errors ?? [],
            'event' => $event],
            $isEdit ? 'edit' : 'add');
    }

    /**
     * Odstráni podujatie vrátane súborov (plagát, propozície). Len pre admin.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
     */
    public function delete(Request $request): Response
    {
        // IBA PRE ADMINA
        $this->checkAdmin();
        //Ochrana proti CSRF pre mazanie
        $this->validateCsrf($request);

        try {
            // Získame ID z požiadavky a pretypujeme ho na int kvôli bezpečnosti
            $id = (int)$request->value('id');
            $event = Event::getOne($id);

            /* --- 2. KONTROLA EXISTENCIE --- */
            if (!$event) {
                // Ak ide o AJAX (mazanie bez reloadu), vrátime chybu v JSON formáte
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Podujatie neexistuje.']);
                }
                // Ak ide o klasickú požiadavku, hodíme chybu 404 (Nenájdené)
                throw new HttpException(404);
            }

            /* --- 3. MAZANIE FYZICKÝCH SÚBOROV --- */
            // Predtým než zmažem záznam z DB, musím odstrániť súbory z disku.
            // Volám pomocnú metódu deleteFile pre plagát aj propozície.
            $this->deleteFile($event->getPlagat());
            $this->deleteFile($event->getDokumentPropozicie());

            /* --- 4. ODSTRÁNENIE Z DATABÁZY --- */
            // Spustí SQL DELETE dopyt v databáze
            $event->delete();

            // Ak to bol AJAX (kliknutie na tlačidlo zmazať v zozname), vrátime úspech
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (Exception $e) {
            /* --- 5. OŠETRENIE CHÝB --- */
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()]);
            }
            throw new HttpException(500, $e->getMessage());
        }

        // Ak to nebol AJAX, presmerujeme používateľa späť na zoznam podujatí
        return $this->redirect($this->url('event.index'));
    }


    private function uploadFile(Request $request, string $inputName, ?string $oldFile): string
    {
        // Získam súbor z požiadavky podľa názvu inputu (napr. 'plagat')
        $file = $request->file($inputName);
        // Skontrolujeme, či súbor existuje, či je v poriadku (isOk) a či má meno
        if ($file && $file->isOk() && $file->getName() !== '') {
            /* --- 1. BEZPEČNÝ NÁZOV --- */

            // 1. Získame pôvodné meno
            $originalName = $file->getName();

            // 2. Očistím ho: nahradím všetko, čo nie je písmeno, číslo, bodka alebo pomlčka, podčiarkovníkom
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

            // 3. Poskladáme finálnu cestu s časovou pečiatkou
            $path = 'uploads/' . time() . '_' . $safeName;
            /* --- 3. FYZICKÉ ULOŽENIE --- */
            // dirname(__DIR__, 2) nás dostane do koreňového priečinka projektu
            // Súbor ukladáme do zložky public/, aby bol prístupný cez URL
            if ($file->store(dirname(__DIR__, 2) . '/public/' . $path)) {
                /* --- 4. ČISTENIE DISKU --- */
                // Ak sa nahrávanie podarilo, zavoláme deleteFile pre starý súbor.
                // Toto zabraňuje hromadeniu zbytočných súborov (tzv. "sirot") na serveri.
                $this->deleteFile($oldFile);
                // Vrátime novú cestu, ktorá sa zapíše do databázy
                return $path;
            }
        }
        // Ak nebol nahratý nový súbor, vrátime pôvodnú cestu (ponecháme starý súbor)
        return $oldFile ?? '';
    }

    private function deleteFile(?string $path): void
    {
        // 1. KONTROLA EXISTENCIE CESTY
        // Ak je cesta (path) null alebo prázdna, funkcia nerobí nič.
        if ($path) {
            // 2. REKONŠTRUKCIA CELEJ CESTY NA DISKU
            // V databáze máme uložené napr. 'uploads/foto.jpg'.
            // Aby sme to mohli zmazať, musíme k tomu pridať absolútnu cestu k priečinku 'public'.
            // dirname(__DIR__, 2) nás vráti do rootu projektu.
            $fullPath = dirname(__DIR__, 2) . '/public/' . $path;

            // 3. FYZICKÉ MAZANIE
            // file_exists: najskôr overíme, či ten súbor na disku naozaj je, aby sme sa vyhli chybám.
            // @unlink: zavoláme funkciu na zmazanie súboru.
            // Znak '@' pred funkciou potláča prípadné varovania (napr. ak súbor existuje, ale nemáme práva na zmazanie).
            if (file_exists($fullPath)) @unlink($fullPath);
        }
    }

    private function formErrors(Request $request, bool $isEdit): array
    {
        $errors = [];
        $data = $request->post();

        // 1. ZÁKLADNÁ KONTROLA POVINNÝCH POLÍ
        if (empty($data['nazov'])) $errors[] = 'Názov je povinný.';
        if (empty($data['datum_podujatia'])) $errors[] = 'Dátum je povinný.';

        // 2. LOGICKÁ KONTROLA DÁTUMU
        if (!empty($data['datum_podujatia'])) {
            // Skonvertujeme textový dátum na objekt DateTime pre lepšiu manipuláciu
            $eventDate = DateTime::createFromFormat('Y-m-d', $data['datum_podujatia']);
            $today = new DateTime('today');
            if (!$eventDate) {
                $errors[] = 'Dátum podujatia je neplatný.';
            } elseif ($eventDate <= $today) {
                // Zabezpečíme, aby admin nevytváral podujatia, ktoré už skončili
                $errors[] = 'Dátum podujatia musí byť neskôr ako dnešný deň.';
            }
        }

        /* --- 3. VALIDÁCIA PLAGÁTU (Obrázok) --- */
        $plagatFile = $request->file('plagat');

        // Ak ide o NOVÉ podujatie ($isEdit je false), plagát musí byť nahraný
        if (!$isEdit && (!$plagatFile || !$plagatFile->isOk())) {
            $errors[] = 'Plagát je povinný pri vytváraní nového podujatia.';
        }

        if ($plagatFile && $plagatFile->isOk() && $plagatFile->getName() !== '') {
            // Kontrola MIME typu: nepýtame sa len na príponu .jpg, ale na skutočný obsah súboru
            $allowedImgTypes = ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png'];
            if (!in_array(strtolower($plagatFile->getType()), $allowedImgTypes)) {
                $errors[] = 'Plagát musí byť vo formáte JPG alebo PNG.';
            }
            // Limit veľkosti: 2 MB (2 * 1024 * 1024 bajtov)
            if ($plagatFile->getSize() > 2 * 1024 * 1024) {
                $errors[] = 'Plagát nesmie byť väčší ako 2 MB.';
            }
        }

        /* --- 4. VALIDÁCIA DOKUMENTU (PDF) --- */
        $docFile = $request->file('dokument_propozicie');
        if ($docFile && $docFile->isOk() && $docFile->getName() !== '') {
            if (strtolower($docFile->getType()) !== 'application/pdf') {
                $errors[] = 'Dokument musí byť vo formáte PDF.';
            }
            // Tiež limit 2 MB kvôli stabilite servera
            if ($docFile->getSize() > 2 * 1024 * 1024) {
                $errors[] = 'Dokument nesmie byť väčší ako 2 MB.';
            }
        }

        return $errors;
    }
}
