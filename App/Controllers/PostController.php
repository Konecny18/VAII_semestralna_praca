<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Post;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\UploadedFile;

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
        $auth = $this->app->getAuthenticator();
        try {
            $albumId = (int)$request->value('albumId');
            if ($albumId > 0) {
                $posts = Post::getAll('`albumId` = ?', [$albumId], 'id DESC');
            } else {
                $posts = Post::getAll(null, [], 'id DESC');
            }

            return $this->html(
                [
                    'posts' => $posts,
                    'albumId' => $albumId,
                    'auth' => $auth
                ]
            );
        } catch (Exception $e) {
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
        $albumId = (int)$request->value('albumId');
        return $this->html(['albumId' => $albumId]);
    }

    /**
     * Zobrazí stránku pre úpravu existujúceho príspevku.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException ak príspevok neexistuje
     */
    public function edit(Request $request): Response
    {
        $this->checkAdmin();
        $id = (int)$request->value('id');
        $post = Post::getOne($id);
        if (is_null($post)) {
            throw new HttpException(404);
        }
        $albumId = (int)$request->value('albumId');
        return $this->html(array_merge(compact('post'), ['albumId' => $albumId]));
    }

    /**
     * Uloží nový alebo upravený príspevok. Pri vytváraní podporuje viacnásobné nahranie obrázkov.
     * Validuje nahrané súbory a vykonáva rollback v prípade chyby.
     *
     * @param Request $request
     * @return Response Presmerovanie po úspechu alebo zobrazenie formulára s chybami
     * @throws HttpException pri závažných chybách (IO/DB)
     */
    public function save(Request $request): Response
    {
        $this->checkAdmin();
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
                    throw new \Exception("Príspevok neexistuje.");
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
                    //nahradi divne znaky preg_replace('/[^A-Za-z0-9._-]/'
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

        } catch (\Throwable $e) {
            // --- ROLLBACK (Záchranná brzda) ---
            // Ak sa niečo pokazilo (napr. DB chyba), zmažem všetky súbory, ktoré som v tomto kroku stihol nahrať
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
        $this->checkAdmin();
        try {
            $id = (int)$request->value('id');
            $post = Post::getOne($id);


            if (is_null($post)) {
                //pre AJAX vrati chybu v JSON formate
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Obrazok nebol nájdený.'], 404);
                }
                throw new HttpException(404);
            }

            //zmazanie subora z disku
            if ($post->getPicture()) {
                // Ak Configuration::UPLOAD_URL je "/uploads/",
                // ltrim odstráni začiatočné lomko, aby vznikla cesta "uploads/meno.jpg"
                $relativeUrl = ltrim(Configuration::UPLOAD_URL, '/');
                $filePath = $relativeUrl . $post->getPicture();
                //$filePath = Configuration::UPLOAD_DIR . $post->getPicture();
//                $filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . Configuration::UPLOAD_URL . str_replace('/', DIRECTORY_SEPARATOR, $post->getPicture());
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $post->delete();

            //Ak je AJAX vratim uspech a ukoncim metodu
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }


        } catch (\Exception $e) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()], 500);
            }
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
        // keep album context when redirecting
        if (!empty($albumId)) {
            return $this->redirect($this->url('post.index', ['albumId' => $albumId]));
        }

        //klasicky redirect pre pripad AJAXu
        return $this->redirect($this->url('post.index'));
    }

    // Updated formErrors to validate multiple uploaded files

    private function formErrors(Request $request, bool $isEdit = false): array
    {
        $errors = [];
        $albumId = (int)$request->value('albumId');

        // Accept files from 'pictures' input (multiple) - normalize from $_FILES here
        $files = $this->normalizeUploadedFiles('pictures');
        if (!is_array($files)) {
            $files = [];
        }

        // Limity
        $maxFileSize = 5242880; // 5 MB

        // --- 1. Validácia albumId ---
        if ($albumId <= 0 || is_null(\App\Models\Album::getOne($albumId))) {
            $errors[] = "Album, ku ktorému sa snažíte príspevok pridať, neexistuje.";
        }

        // --- 3. Validácia Obrázkov ---
        $hasUpload = false;
        foreach ($files as $file) {
            if ($file && $file->getName() !== "") {
                $hasUpload = true;
                // Kontrola MIME typu a Max. veľkosti
                $type = $file->getType();
                if (!in_array($type, ['image/jpeg', 'image/png'])) {
                    $errors[] = "Obrázok musí byť typu JPG alebo PNG! (súbor: " . $file->getName() . ")";
                }
                if ($file->getSize() > $maxFileSize) {
                    $errors[] = "Veľkosť obrázka nesmie presiahnuť 5 MB! (súbor: " . $file->getName() . ")";
                }
            }
        }

        // Pri vytváraní (nie editácii) je aspoň jeden súbor povinný.
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
