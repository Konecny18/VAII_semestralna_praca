<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Album;
use App\Models\Post;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\UploadedFile;
use Throwable;

/**
 * Class PostController
 *
 * Spravuje príspevky (posty / fotografie) v rámci albumov. Umožňuje zobraziť zoznam príspevkov, pridávať nové
 * (vrátane hromadného nahrávania obrázkov), upravovať existujúce a mazať príspevky. Operácie na úpravu sú obmedzené
 * podľa autorizácie (zvyčajne len admin alebo vlastník podľa implementácie vo view/auth).
 *
 * @package App\Controllers
 */
class PostController extends BaseController
{

    /**
     * Zobrazí zoznam príspevkov v rámci voliteľného albumu.
     *
     * @param Request $request
     * @return Response HTML s listom príspevkov
     * @throws HttpException pri chybe DB
     */
    public function index(Request $request): Response
    {
        // 1. ZÍSKANIE AUTENTIFIKÁTORA
        // Potrebujeme ho vo View, aby sme vedeli, či zobraziť tlačidlá na pridanie/mazanie príspevkov.
        $auth = $this->app->getAuthenticator();
        try {
            /* --- 2. FILTROVANIE PODĽA ALBUMU --- */
            // Získame albumId z URL (napr. posts?albumId=5)
            $albumId = (int)$request->value('albumId');

            // Ak je albumId zadané (väčšie ako 0), vytiahneme len príspevky z daného albumu.
            if ($albumId > 0) {
                // Používame podmienku WHERE `albumId` = ?
                // Parametre posielame v poli [$albumId], aby sme predišli SQL injection.
                $posts = Post::getAll('`albumId` = ?', [$albumId], 'id DESC');
            } else {
                // Ak nie je albumId, nepustíme ho ďalej
                throw new HttpException(404, "Album nebol špecifikovaný.");
            }

            /* --- 3. ODOSLANIE DÁT DO VIEW --- */
            return $this->html(
                [
                    // Zoznam príspevkov (všetky alebo filtrované)
                    'posts' => $posts,
                    // ID aktuálneho albumu (aby sme vedeli, kam pridať nový príspevok)
                    'albumId' => $albumId,
                    // Informácie o prihlásenom používateľovi
                    'auth' => $auth
                ]
            );
        } catch (Exception $e) {
            // V prípade chyby s databázou vyhodíme systémovú chybu 500
            throw new HttpException(500, "DB Chyba: " . $e->getMessage());
        }
    }

    /**
     * Zobrazí formulár pre pridanie nového príspevku (s možnosťou nahrania jedného alebo viacerých obrázkov).
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function add(Request $request): Response
    {
        //iba admin moze robit CRUD
        $this->checkAdmin();
        // 2. IDENTIFIKÁCIA CIEĽOVÉHO ALBUMU
        // Z požiadavky (Request) vytiahneme ID albumu, do ktorého chceme pridávať.
        // Pretypujeme ho na (int), aby sme zaistili bezpečnosť a správny dátový typ.
        $albumId = (int)$request->value('albumId');

        // 3. ZOBRAZENIE FORMULÁRA
        // Metóda html() vykreslí šablónu
        // Posielame do nej pole s 'albumId', aby formulár vedel,
        // ku ktorému albumu má tento nový príspevok priradiť.
        return $this->html(['albumId' => $albumId]);
    }

    /**
     * Zobrazí stránku pre úpravu existujúceho príspevku.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException ak príspevok neexistuje
     * @throws Exception
     */
    public function edit(Request $request): Response
    {
        //iba admin moze robit CRUD
        $this->checkAdmin();

        // 2. NAČÍTANIE PRÍSPEVKU
        // Získame ID príspevku z URL a pokúsime sa ho nájsť v databáze.
        $id = (int)$request->value('id');
        $post = Post::getOne($id);

        // 3. OŠETRENIE NEEXISTUJÚCEHO ZÁZNAMU
        // Ak príspevok s daným ID neexistuje, vrátime chybu 404 (Nenájdené).
        // Je to dôležité, aby aplikácia nepokračovala s prázdnymi dátami.
        if (is_null($post)) {
            throw new HttpException(404);
        }

        // 4. KONTEXT ALBUMU
        // Získame albumId, aby sme sa po úprave vedeli vrátiť do správneho albumu.
        $albumId = (int)$request->value('albumId');

        // 5. ODOSLANIE DÁT DO ŠABLÓNY
        // Používame array_merge, aby sme do view poslali objekt '$post' aj premennú '$albumId'.
        // Šablóna tak bude mať predvyplnené pôvodné dáta príspevku.
        return $this->html(array_merge(compact('post'), ['albumId' => $albumId]));
    }

    /**
     * Uloží nový alebo upravený príspevok. Pri vytváraní podporuje viacnásobné nahranie obrázkov.
     * Validuje nahrané súbory a vykonáva rollback v prípade chyby.
     *
     * @param Request $request
     * @return Response Presmerovanie po úspechu alebo zobrazenie formulára s chybami
     * @throws HttpException pri závažných chybách (IO/DB)
     * @throws Exception
     */
    public function save(Request $request): Response
    {
        //iba admin moze robit CRUD
        $this->checkAdmin();

        // CSRF ochrana - akcia sa vykoná len ak sedí token
        $this->validateCsrf($request);

        // --- 1. Inicializácia ---
        // Získam ID príspevku. Ak chýba, viem, že vytváram nový (ADD), ak existuje, upravujem (EDIT).
        $idRaw = $request->post('id') ?? null;
        $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
        $isEdit = !empty($id);

        // Identifikujem album, do ktorého príspevky patria
        $albumId = (int)$request->value('albumId');

        // 2. Validácia
        // Skontrolujem, či sú súbory v poriadku (typ, veľkosť) a či je vybraný album
        $formErrors = $this->formErrors($request, $isEdit);

        if (count($formErrors) > 0) {
            // Ak sa našli chyby, vrátim používateľa späť na formulár a zobrazím mu ich
            // Pri chybe vrátime používateľa do formulára s vyplnenými dátami (UX)
            $post = ($isEdit) ? Post::getOne($id) : new Post();
            if ($post) {

                $post->setAlbumId($albumId);
            }

            return $this->html(
                compact('post', 'formErrors'), ($isEdit) ? 'edit' : 'add'
            );
        }

        // --- 3. Spracovanie Dát a Súborov ---
        try {
            // Tu použijem pomocnú metódu, ktorá uprace $_FILES do pekného poľa objektov
            $files = $this->normalizeUploadedFiles('pictures');

            // Ensure we have an array
            if (!is_array($files)) {
                $files = [];
            }

            // ukladam si cesty k súborom, ktoré som už počas tohto behu uložili na disk.
            // Slúži to na "Rollback" – ak program spadne neskôr, tieto súbory zmažem, aby nezostal bordel.
            $createdFiles = []; // keep track of created files to rollback on error

            // --- REŽIM EDITÁCIE (Úprava existujúceho príspevku) ---
            if ($isEdit) {
                $post = Post::getOne($id);
                if (is_null($post)) {
                    throw new Exception("Príspevok neexistuje.");
                }

                $oldPicturePath = $post->getPicture(); // Odložíme si názov starého obrázka
                $post->setAlbumId($albumId);

                // Pri editácii ma zaujíma len prvý vybraný súbor (vymieňam 1 za 1)
                $newFile = $files[0] ?? null;
                if ($newFile && $newFile->getName() !== "") {
                    // Skontrolujem/vytvorím priečinok pre nahrávanie
                    if (!is_dir(Configuration::UPLOAD_DIR)) {
                        if (!@mkdir(Configuration::UPLOAD_DIR, 0777, true) && !is_dir(Configuration::UPLOAD_DIR)) {
                            throw new HttpException(500, 'Nepodarilo sa vytvoriť adresár pre nahrávanie súborov.');
                        }
                    }

                    // Vygenerujem unikátne meno, kvoli tomu keby nahravam obrazok z takym istym menom znova tak by sa mi prepisal
                    // robim tam nahodny retazec kvoli tomu skupinovemu nahravaniu bin2hex(random_bytes(4))
                    // nahradi divne znaky preg_replace('/[^A-Za-z0-9._-]/'
                    $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $newFile->getName());
                    $targetPath = Configuration::UPLOAD_DIR . $uniqueName;

                    // Fyzicky uložím súbor na disk
                    if (!$newFile->store($targetPath)) {
                        throw new HttpException(500, 'Nepodarilo sa uložiť nahraný súbor.');
                    }

                    $createdFiles[] = $targetPath;  //Zapisem si ze som ho vytvoril
                    $post->setPicture($uniqueName);  //Priradim nove meno do databazoveho modelu
                }

                // Save post
                $post->save();

                // Ak sa všetko podarilo a nahral som nový obrázok, ten starý môžem zo servera zmazať
                if (!empty($oldPicturePath)) {
                    $oldPath = Configuration::UPLOAD_DIR . $oldPicturePath;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                return $this->redirect($this->url('post.index', ['albumId' => $post->getAlbumId()]));
            }

            // --- REŽIM PRIDÁVANIA (Hromadné nahrávanie) ---
            if (!is_dir(Configuration::UPLOAD_DIR)) {
                if (!@mkdir(Configuration::UPLOAD_DIR, 0777, true) && !is_dir(Configuration::UPLOAD_DIR)) {
                    throw new HttpException(500, 'Nepodarilo sa vytvoriť adresár pre nahrávanie súborov.');
                }
            }

            // Prechádzam všetky nahrané obrázky jeden po druhom
            foreach ($files as $file) {
                if ($file && $file->getName() !== "") {
                    // Vygenerujem unikátne meno, kvoli tomu keby nahravam obrazok z takym istym menom znova tak by sa mi prepisal,
                    // robim tam nahodny retazec kvoli tomu skupinovemu nahravaniu
                    $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getName());
                    $targetPath = Configuration::UPLOAD_DIR . $uniqueName;

                    // Uložíme na disk
                    if (!$file->store($targetPath)) {
                        throw new HttpException(500, 'Nepodarilo sa uložiť nahraný súbor.');
                    }

                    $createdFiles[] = $targetPath; // Pridáme do zoznamu pre prípadný rollback

                    // Pre KAŽDÝ obrázok vytvoríme úplne nový riadok v tabuľke príspevkov
                    $post = new Post();
                    $post->setAlbumId($albumId);
                    $post->setPicture($uniqueName);
                    $post->save();
                }
            }

            // Po úspešnom nahraní všetkých fotiek presmerujem späť do albumu
            return $this->redirect($this->url('post.index', ['albumId' => $albumId]));

        } catch (Throwable $e) {
            /* --- ROLLBACK MECHANIZMUS --- */
            // Ak počas cyklu padne DB (napr. pri 5. fotke z desiatich),
            // zmažeme tých 4, ktoré sme už stihli nahrať na disk.
            foreach ($createdFiles as $p) {
                if (file_exists($p)) {
                    @unlink($p);
                }
            }
            // Vrátenie chyby
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
    }

    /**
     * Odstráni príspevok a jeho obrázok zo servera a DB. Pri AJAX požiadavke vráti JSON.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
     */
    public function delete(Request $request): Response
    {
        //iba admin moze robit CRUD
        $this->checkAdmin();

        // CSRF ochrana pre mazanie
        $this->validateCsrf($request);

        try {
            $id = (int)$request->value('id');
            $post = Post::getOne($id);

            // 2. KONTROLA EXISTENCIE
            if (is_null($post)) {
                // Ak ide o AJAX (asynchrónna požiadavka z JS), vrátime chybu v JSON formáte
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Obrazok nebol nájdený.']);
                }
                throw new HttpException(404);
            }

            //zmazanie subora z disku
            if ($post->getPicture()) {
                // Ak Configuration::UPLOAD_URL je "/uploads/",
                // ltrim odstráni začiatočné lomitko, aby vznikla cesta "uploads/meno.jpg"
                $relativeUrl = ltrim(Configuration::UPLOAD_URL, '/');
                $filePath = $relativeUrl . $post->getPicture();

                if (file_exists($filePath)) {
                    // Zmaže súbor z disku
                    @unlink($filePath);
                }
            }
            // 4. ZMAZANIE Z DATABÁZY
            $post->delete();

            // 5. ODPOVEĎ PRE AJAX
            // Ak sa maže cez JS (bez reloadu), frontend očakáva JSON odpoveď { "success": true }
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }


        } catch (Exception $e) {
            // Ošetrenie chýb pri mazaní (napr. chyba DB)
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()]);
            }
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }

        // 6. REDIRECT (Pre klasické tlačidlo bez AJAXu)
        // Pokúsime sa vrátiť do pôvodného albumu, aby admin zostal v kontexte galérie
        if (!empty($albumId)) {
            return $this->redirect($this->url('post.index', ['albumId' => $albumId]));
        }

        //klasicky redirect pre pripad AJAXu
        return $this->redirect($this->url('post.index'));
    }

    /**
     * Hromadné mazanie príspevkov (bulk delete) cez AJAX.
     * Očakáva JSON telo: { "ids": [1,2,3] }
     *
     * @param Request $request
     * @return Response JSON s výsledkom operácie
     * @throws HttpException
     */
    /**
     * Hromadné mazanie príspevkov cez AJAX.
     * @throws HttpException
     */
    public function bulkDelete(Request $request): Response
    {
        $this->checkAdmin();
        // CSRF ochrana
        $this->validateCsrf($request);

        try {
            // Získame dáta z JSON požiadavky alebo z POST poľa
            $ids = [];

            // 1) Pokúsime sa bezpečne načítať JSON telo (niektoré servery môžu mať CONTENT_TYPE s charset)
            try {
                $body = $request->json();
            } catch (\Throwable $e) {
                $body = null;
            }

            // Ak je $body objekt (stdClass), prevedieme ho na asociatívne pole
            if ($body !== null && !is_array($body)) {
                if (is_object($body)) {
                    $body = json_decode(json_encode($body), true);
                }
            }

            if (is_array($body) && isset($body['ids']) && is_array($body['ids'])) {
                $ids = $body['ids'];
            } else {
                // 2) Fallback: načítame POST (napr. klasický form submit alebo AJAX s form-encoded)
                $post = $request->post();
                if (isset($post['ids']) && is_array($post['ids'])) {
                    $ids = $post['ids'];
                } elseif (isset($post['ids']) && is_string($post['ids'])) {
                    // ak prišiel csv string, rozparsujeme ho
                    $ids = array_filter(array_map('trim', explode(',', $post['ids'])));
                }
            }

            // Normalizácia a očistenie ID (len kladné celé čísla)
            $cleanIds = [];
            foreach ($ids as $rid) {
                $id = (int)$rid;
                if ($id > 0) $cleanIds[] = $id;
            }

            if (empty($cleanIds)) {
                return $this->json(['success' => false, 'message' => 'Žiadne ID neboli zaslané.']);
            }

            $deletedCount = 0;
            $errors = [];
            foreach ($cleanIds as $id) {
                try {
                    $post = Post::getOne($id);
                    if (!$post) {
                        $errors[] = "Post s ID $id neexistuje.";
                        continue;
                    }

                    // zmazanie suboru z disku
                    if ($post->getPicture()) {
                        $relativeUrl = ltrim(Configuration::UPLOAD_URL, '/');
                        $filePath = $relativeUrl . $post->getPicture();
                        if (file_exists($filePath)) @unlink($filePath);
                    }

                    $post->delete();
                    $deletedCount++;
                } catch (\Throwable $e) {
                    $errors[] = "Chyba pri mazaní ID $id: " . $e->getMessage();
                }
            }

            return $this->json([
                'success' => true,
                'deleted' => $deletedCount,
                'errors' => $errors,
                'message' => "Úspešne zmazaných $deletedCount príspevkov."
            ]);

        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @throws Exception
     */
    private function formErrors(Request $request, bool $isEdit = false): array
    {
        $errors = [];
        $albumId = (int)$request->value('albumId');

        // 1. ZÍSKANIE A NORMALIZÁCIA SÚBOROV
        // Keďže používame 'pictures[]' v HTML, $_FILES má komplikovanú štruktúru.
        // normalizeUploadedFiles nám ich premení na pole objektov, ktoré vieme prejsť cyklom.
        $files = $this->normalizeUploadedFiles('pictures');
        if (!is_array($files)) {
            $files = [];
        }

        // Nastavenie limitu na 5 MB
        $maxFileSize = 5242880;

        /* --- 2. VALIDÁCIA ALBUMU (Cudzieho kľúča) --- */
        // Kontrolujeme, či albumId nie je nula a či daný album v databáze naozaj existuje.
        // Zabraňuje to vzniku "sirototvorných" príspevkov, ktoré by nepatrili nikam.
        if ($albumId <= 0 || is_null(Album::getOne($albumId))) {
            $errors[] = "Album, ku ktorému sa snažíte príspevok pridať, neexistuje.";
        }

        /* --- 3. VALIDÁCIA CELÉHO POĽA OBRÁZKOV --- */
        $hasUpload = false;
        foreach ($files as $file) {
            // Kontrolujeme len reálne nahrané súbory
            if ($file && $file->getName() !== "") {
                $hasUpload = true;
                // Kontrola MIME typu a Max. veľkosti
                $type = $file->getType();
                if (!in_array($type, ['image/jpeg', 'image/png'])) {
                    // Do chyby pridáme aj meno súboru, aby admin vedel, ktorý obrázok má opraviť
                    $errors[] = "Obrázok musí byť typu JPG alebo PNG! (súbor: " . $file->getName() . ")";
                }
                // Kontrola veľkosti
                if ($file->getSize() > $maxFileSize) {
                    $errors[] = "Veľkosť obrázka nesmie presiahnuť 5 MB! (súbor: " . $file->getName() . ")";
                }
            }
        }

        /* --- 4. KONTROLA POVINNOSTI --- */
        // Ak ide o nový príspevok (nie editáciu), admin MUSÍ vybrať aspoň jednu fotku.
        if (!$isEdit && !$hasUpload) {
            $errors[] = "Súbor obrázka je povinný pre vytvorenie príspevku!";
        }

        return $errors;
    }

    /**
     * Táto metóda "upratuje" PHP globálnu premennú $_FILES.
     * Premieňa chaotickú štruktúru polí na jednotné pole objektov UploadedFile.
     */
    private function normalizeUploadedFiles(string $key): array
    {
        $result = [];

        //Zisti ci niekdo poslal nejaky subor ak nie vrati prazdny zoznam
        if (!isset($_FILES[$key])) {
            return [];
        }
        $entry = $_FILES[$key];

       //zistuje ci to je pole mien, ak ano tak viem ze pouzivatel poslal viac suorov naraz (cez multiple v HTML)
        if (is_array($entry['name'])) {
            // Prechádzam indexy nahraných súborov
            foreach ($entry['name'] as $i => $name) {

                // Ak používateľ priložil input, ale nevybral súbor, PHP vygeneruje chybu UPLOAD_ERR_NO_FILE
                // Tento záznam preskočím, aby som nevytváral prázdne objekty
                if ($entry['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                // "Rekonštruujem" dáta pre jeden konkrétny súbor na základe indexu $i
                $fileData = [
                    'name' => $entry['name'][$i], //menu suboru
                    'type' => $entry['type'][$i] ?? '',  //typ suboru, ak by tam nahodou nebol tak da '' aby program nepadol
                    'tmp_name' => $entry['tmp_name'][$i] ?? '',  //cesta ku docasnemu miestu na serveri
                    'error' => $entry['error'][$i] ?? UPLOAD_ERR_NO_FILE,  //ulozi info ci sa sobur nahral v poriadku alebo bol prilis velky
                    'size' => $entry['size'][$i] ?? 0, //ulozi velkost suboru v bajtoch
                ];
                // Vytvorím inštanciu triedy UploadedFile (váš frameworkový objekt) a pridám do výsledku
                $result[] = new UploadedFile($fileData);
            }
        } else {
            // ak pouzivatel vybral len jeden subor tak ho posle v normalnom formate
            //kod ho len vezme a obali ho do objektu UploadedFile aby bol na konci rovnaky vysledok
            if ($entry['error'] !== UPLOAD_ERR_NO_FILE) {
                // Celé $entry už má správny formát pre jeden súbor, stačí ho poslať do objektu
                $result[] = new UploadedFile($entry);
            }
        }

        // Vrátim pole objektov, s ktorými sa už v Controlleri ľahko pracuje cez foreach
        return $result;
    }
}
