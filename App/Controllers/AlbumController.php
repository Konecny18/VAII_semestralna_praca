<?php

namespace App\Controllers;

use App\Models\Album;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\UploadedFile;
use Framework\Http\Responses\Response;
use Throwable;

/**
 * Class AlbumController
 *
 * Spravuje galérie (albumy): prehliadanie, vytváranie, úprava a mazanie albumov.
 * Rieši nahrávanie obrázkov pre albumy a server-side validáciu vstupov.
 *
 * @package App\Controllers
 * @method checkAdmin()
 */
class AlbumController extends BaseController
{
    /**
     * Zobrazí zoznam albumov.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException pri chybe DB
     */
    public function index(Request $request): Response
    {
        //vytiahne vsetky albumy z DTB, zoradene od najnovsieho
        $auth = $this->app->getAuthenticator();
        try {
            return $this->html(
                [
                    'albums' => Album::getAll(null, [], 'id DESC'),
                    'auth' => $auth
                ]
            );
        } catch (Exception $exception) {
            throw new HttpException(500, "DB chyba: " . $exception->getMessage());
        }
    }

    /**
     * Zobrazí formulár na vytvorenie nového albumu.
     *
     * @return Response
     */
    public function add(): Response
    {
        //kontrola iba admin moze robit CRUD operacie
        $this->checkAdmin();
        return $this->html();
    }

    /**
     * Zobrazí formulár pre úpravu existujúceho albumu.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException ak album neexistuje
     * @throws Exception
     */
    public function edit(Request $request): Response
    {
        $this->checkAdmin();
        //ziskanie id a pretipovanie na int
        //kvoli tomu aby niekdo tam nedal string a nevznikla chyba
        $id = (int)$request->value('id');
        //nacitanie albumu z DTB
        $album = Album::getOne($id);
        if (is_null($album)) {
            throw new HttpException(404);
        }
        //vrati upravny formular s datami albumu
        return $this->html(compact('album'), 'edit');
    }

    /**
     * Spracuje vytvorenie alebo úpravu albumu (vrátane nahrávania obrázka).
     * Validuje vstupy a pri chybe vráti formulár s chybovými hláseniami.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
     */
    public function save(Request $request): Response
    {
        //kontrola iba admin moze robit CRUD operacie
        $this->checkAdmin();
        //kontrola ci bol odoslany z mojho webu
        $this->validateCsrf($request);
        // prepare default values for form re-population
        $formValues = ['text' => '', 'picture' => '', 'id' => null];
        $errors = [];

        if ($request->isPost()) {
            //odstranenie html tagov z textu a trim
            $text = strip_tags(trim((string)($request->post('text') ?? '')));

            //ak mam id tak ide o edit inak pridanie
            $idRaw = $request->post('id') ?? null;
            $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
            $isEdit = !empty($id);

            $formValues['text'] = $text;
            $formValues['id'] = $id;

            // server-side validation (required fields, types, lengths)
            $errors = $this->formErrors($request, $isEdit);

            // handle uploaded file if present and no validation errors so far
            $newFileFullPath = null;
            $oldFilePath = null;
            if (empty($errors)) {
                try {
                    //vytiahne subor z poziadavky
                    $uploaded = $request->file('picture');
                    $existing = null;
                    //ak editujem, nacitam aktualny album
                    if ($isEdit) {
                        $existing = Album::getOne((int)$id);
                    }
                    //bol subor naozaj nahrany a je v poriadky, kontrola
                    if ($uploaded instanceof UploadedFile && $uploaded->isOk() && $uploaded->getName() !== "") {

                        // Určenie cesty: dirname(__DIR__, 2) ma hodí do rootu projektu, potom idem do public/images
                        $imagesDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images';

                        //ak priecinok images neexistuje, vytvorim ho
                        if (!is_dir($imagesDir)) {
                            if (!@mkdir($imagesDir, 0755, true) && !is_dir($imagesDir)) {
                                throw new HttpException(500, 'Nepodarilo sa vytvoriť adresár pre ukladanie obrázkov. Skontrolujte práva k adresáru.');
                            }
                        }

                        //generovanie unikatneho nazvu suboru
                        $menoSuboru = $uploaded->getName();
                        //vycistenie nazvu suboru od nebezpecnych znakov
                        $bezpecneMenoSuboru = preg_replace('/[^A-Za-z0-9._-]/', '_', $menoSuboru);
                        //cas + nahodny kod + meno
                        $finalMenoSuboru = time() . '_' . bin2hex(random_bytes(4)) . '_' . $bezpecneMenoSuboru;
                        //cielova cela cesta
                        $celaCesta = $imagesDir . DIRECTORY_SEPARATOR . $finalMenoSuboru;

                        if ($uploaded->store($celaCesta)) {
                            // cesta pre DTB
                            $picture = 'images/' . $finalMenoSuboru;
                            $formValues['picture'] = $picture;
                            // ak editujem a nahravam novy obrazok musim si zapamatat cestu k staremu, aby som ho potom zmazal
                            if ($isEdit && $existing && $existing->getPicture() != '') {

                                $oldFilePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $existing->getPicture());
                            }
                            // Cesta k novému súboru (keby sme ho museli zmazať pri chybe)
                            $newFileFullPath = $celaCesta;
                        } else {
                            $errors[] = 'Nepodarilo sa uložiť nahraný súbor.';
                        }
                    } else {
                        // Ak používateľ nenahral nič nové a editujeme, ponecháme starý obrázok
                        if ($isEdit) {
                            $existing = $existing ?? Album::getOne((int)$id);
                            if ($existing) {
                                $picture = $existing->getPicture();
                                $formValues['picture'] = $picture;
                            }
                        }
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Chyba pri nahrávaní súboru: ' . $e->getMessage();
                }
            }

            // basic validation for text (double-check in case formErrors wasn't used)
            if ($text === '') {
                $errors[] = 'Názov albumu je povinný.';
            }

            if (empty($errors)) {
                try {
                    // If editing, load the existing album and update its fields; otherwise create new
                    if ($isEdit) {
                        //nacita existujuci objekt
                        $album = Album::getOne((int)$id);
                        if (is_null($album)) {
                            throw new Exception('Album neexistuje.');
                        }
                        //nacita nove meno
                        $album->setText($formValues['text']);
                        //nastavime cestu k obrazku
                        $album->setPicture($formValues['picture']);
                    } else {
                        // Vytvoríme úplne nový objekt Albumu
                        $album = new Album(null, $formValues['text'], $formValues['picture']);
                    }
                    // Model sám vie, či má urobiť INSERT alebo UPDATE
                    $album->save();

                    // Ak zápis do DB prebehol v poriadku a nahradil som starý obrázok novým,
                    // až TERAZ môžem ten starý súbor definitívne zmazať z disku.
                    if ($newFileFullPath !== null && $oldFilePath !== null && file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }

                    // success -> redirect to view
                    return $this->redirect($this->url('album.index'));
                } catch (Throwable $e) {
                    // Ak DB zlyhala, ale ja som už obrázok nahral na disk, musím ho zmazať,
                    // aby na serveri nezostávali súbory, ktoré v DB neexistujú (tzv. siroty).
                    if ($newFileFullPath !== null && file_exists($newFileFullPath)) {
                        @unlink($newFileFullPath);
                    }
                    $errors[] = 'Nepodarilo sa uložiť album: ' . $e->getMessage();
                }
            }
        }



        // Tento blok sa spustí buď pri GET požiadavke (keď len otváram stránku)
        // alebo keď hore vo validácii vznikla nejaká chyba.
        $album = null;
        if (!empty($formValues['id'])) {
            $existingForForm = Album::getOne((int)$formValues['id']);
            if ($existingForForm !== null) {
                // Use DB-loaded model (it will have correct internal state)
                $album = $existingForForm;
                // Ak používateľ odoslal formulár s chybou, prepíšeme dáta v objekte tým, čo napísal,
                // aby to vo formulári zostalo vyplnené (lepšie UX).
                if (isset($formValues['text'])) {
                    $album->setText($formValues['text']);
                }
                if (isset($formValues['picture'])) {
                    $album->setPicture($formValues['picture']);
                }
            }
        }

        // Ak album stále nemám (lebo robím ADD), vytvorím prázdny model pre formulár.
        if ($album === null) {
            // When preparing a fresh model for the form, do not set an id — using a non-null id
            // on a newly constructed model could lead to duplicate PK inserts later.
            $album = new Album(null, $formValues['text'], $formValues['picture']);
        }
        $isEditMode = !empty($formValues['id']);
        $viewName = $isEditMode ? 'edit' : 'add';
        return $this->html(array_merge(compact('errors', 'album')), $viewName);
    }

    /**
     * Odstráni album (vrátane súboru). Ak ide o AJAX požiadavku, vráti JSON odpoveď.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws Exception
     */
    public function delete(Request $request): Response
    {
        //kontrola iba admin moze robit CRUD operacie
        $this->checkAdmin();
        //kontrola ci bol odoslany z mojho webu
        $this->validateCsrf($request);

        try {
            //ziskanie id a pretipovanie na int
            $id = (int)$request->value('id');
            $album = Album::getOne($id);

            //overenie ci album existuje
            //ak nie vratim chybu 404
            if (is_null($album)) {
                //pre AJAX vratim chybu v JSON formate
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Album nebol nájdený.']);
                }
                throw new HttpException(404);
            }

            //zmazanie fyzickeho subora obrazka
            if ($album->getPicture()) {
                //cesta k suboru
                $filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $album->getPicture());
                if ($filePath && file_exists($filePath)) {
                    //vymazanie obrazkov z priecinka images aby nevznikli siroty
                    @unlink($filePath);
                }
            }

            //zmazanie z DTB
            $album->delete();

            //po zmazani AJAX vratim uspesnu odpoved v JSON formate
            //tym padom sa to vymaze bez nutnosti reloadu stranky
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (Exception $e) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'DB Chyba: ' . $e->getMessage()]);
            }
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }

        //klasicke presmerovanie ak data-ajax nieje true
        return $this->redirect($this->url("album.index"));
    }

    /**
     * Server side kontrola chyb vo formulari pre pridanie/úpravu albumu.
     * @param Request $request
     * @param bool $isEdit
     * @return array
     */
    private function formErrors(Request $request, bool $isEdit = false): array
    {
        $errors = [];
        //odstranenie medzie z textu aby niekdo nespravil to, ze ta 5 medzier, ako nazov
        $text = trim((string)$request->value('text') ?? '');
        $file = $request->file('picture');

        // Definovanie limitov na serverovej strane
        // (musia zodpovedať nastaveniam v DB a požiadavkám)
        // zachyti sa tu aby dtb nepadla
        $maxTextLength = 255;
        $maxFileSize = 5242880; // 5 MB

        // --- Validácia Názvu albumu (text) ---
        if ($text === "") {
            $errors[] = "Názov albumu musí byť vyplnený!";
        } elseif (strlen($text) < 3) {
            $errors[] = "Názov albumu musí mať aspoň 3 znakov!";
        } elseif (strlen($text) > $maxTextLength) {
            // Kontrola, aby dĺžka neprekročila limit DB stĺpca VARCHAR(255)
            $errors[] = "Názov albumu nesmie presiahnuť " . $maxTextLength . " znakov!";
        }

        // --- Validácia Súboru obrázka (picture) ---
        //zistuje ci pouzivatel vybral nejaky subor
        $isNewUpload = ($file instanceof UploadedFile) && $file->getName() !== "";

        // Povinnosť súboru pri vytváraní nového albumu
        if (!$isEdit && !$isNewUpload) {
            $errors[] = "Súbor obrázka je povinný pre vytvorenie nového albumu!";
        }

        if ($isNewUpload) {
            // Kontrola MIME typu (skutočný typ súboru)
            //nekontrolujem len koncovku suboru lebo sa da prepisat
            // ale konretny typ suboru
            if (!in_array($file->getType(), ['image/jpeg', 'image/png'])) {
                $errors[] = "Obrázok musí byť typu JPG alebo PNG!";
            }

            // Kontrola maximálnej veľkosti
            if ($file->getSize() > $maxFileSize) {
                $errors[] = "Veľkosť obrázka nesmie presiahnuť 5 MB!";
            }
        }

        return $errors;
    }
}
