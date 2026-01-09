<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Album;
use App\Models\Post;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\UploadedFile;
use Framework\Http\Responses\Response;

/**
 * Class AlbumController
 *
 * Spravuje galérie (albumy): prehliadanie, vytváranie, úprava a mazanie albumov.
 * Rieši nahrávanie obrázkov pre albumy a server-side validáciu vstupov.
 *
 * @package App\Controllers
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
        $auth = $this->app->getAuthenticator();
        try {
            return $this->html(
                [
                    'albums' => Album::getAll(null, [], 'id DESC'),
                    'auth' => $auth
                ]
            );
        } catch (\Exception $exception) {
            throw new HttpException(500, "DB chyba: " . $exception->getMessage());
        }
    }

    /**
     * Zobrazí formulár na vytvorenie nového albumu.
     *
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        return $this->html();
    }

    /**
     * Zobrazí formulár pre úpravu existujúceho albumu.
     *
     * @param Request $request
     * @return Response
     * @throws HttpException ak album neexistuje
     */
    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');
        $album = Album::getOne($id);
        if (is_null($album)) {
            throw new HttpException(404);
        }
        return $this->html(compact('album'), 'edit');
    }

    /**
     * Spracuje vytvorenie alebo úpravu albumu (vrátane nahrávania obrázka).
     * Validuje vstupy a pri chybe vráti formulár s chybovými hláseniami.
     *
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        // prepare default values for form re-population
        $formValues = ['text' => '', 'picture' => '', 'id' => null];
        $errors = [];

        if ($request->isPost()) {
            // sanitize inputs
            //$text = trim((string)($request->post('text') ?? ''));
            // Zlepšená sanitizácia: Odstránenie HTML tagov z textu (prevencia XSS)
            $text = strip_tags(trim((string)($request->post('text') ?? '')));
            // normalize id: treat empty string as null, otherwise cast to int
            $idRaw = $request->post('id') ?? null;
            $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
            $isEdit = !empty($id);

            $formValues['text'] = $text;
            $formValues['id'] = $id;

            // server-side validation (required fields, types, lengths)
            $errors = $this->formErrors($request, $isEdit);

            // handle uploaded file if present and no validation errors so far
            $picture = '';
            $newFileFullPath = null;
            $oldFilePath = null;
            if (empty($errors)) {
                try {
                    $uploaded = $request->file('picture');
                    // if editing, load existing album so we can keep or remove its picture
                    $existing = null;
                    if ($isEdit) {
                        $existing = Album::getOne((int)$id);
                    }
                    if ($uploaded instanceof UploadedFile && $uploaded->isOk() && $uploaded->getName() !== "") {
                        // build absolute path to public/images (two levels up from App/Controllers -> project root)
                        $imagesDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images';
                        if (!is_dir($imagesDir)) {
                            // attempt to create directory and throw a clear error if it fails (permission issues etc.)
                            if (!@mkdir($imagesDir, 0755, true) && !is_dir($imagesDir)) {
                                throw new HttpException(500, 'Nepodarilo sa vytvoriť adresár pre ukladanie obrázkov. Skontrolujte práva k adresáru.');
                            }
                        }
                        // sanitize original name
                        $orig = $uploaded->getName();
                        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
                        $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
                        $destFull = $imagesDir . DIRECTORY_SEPARATOR . $filename;
                        if ($uploaded->store($destFull)) {
                            // store relative path for DB and views
                            $picture = 'images/' . $filename;
                            $formValues['picture'] = $picture;
                            // record old and new file paths; deletion of old file will happen after DB save
                            if ($isEdit && $existing && $existing->getPicture() != '') {
                                //$oldFilePath = Configuration::UPLOAD_DIR . str_replace('images/', '', $existing->getPicture());
                                $oldFilePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $existing->getPicture());
                            }
                            $newFileFullPath = $destFull;
                        } else {
                            $errors[] = 'Nepodarilo sa uložiť nahraný súbor.';
                        }
                    } else {
                        // no new uploaded file — if editing, keep existing picture
                        if ($isEdit) {
                            $existing = $existing ?? Album::getOne((int)$id);
                            if ($existing) {
                                $picture = $existing->getPicture();
                                $formValues['picture'] = $picture;
                            }
                        }
                    }
                } catch (\Throwable $e) {
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
                        $album = Album::getOne((int)$id);
                        if (is_null($album)) {
                            throw new \Exception('Album neexistuje.');
                        }
                        $album->setText($formValues['text']);
                        $album->setPicture($formValues['picture']);
                    } else {
                        $album = new Album(null, $formValues['text'], $formValues['picture']);
                    }
                    $album->save();
                    // if save succeeded and we had a previous image, remove old file now
                    if ($newFileFullPath !== null && $oldFilePath !== null && file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }

                    // success -> redirect to view
                    return $this->redirect($this->url('album.index'));
                } catch (\Throwable $e) {
                    // don't echo or print; pass the message to the view
                    // if save failed and we uploaded a new file, remove the new file to avoid orphan files
                    if ($newFileFullPath !== null && file_exists($newFileFullPath)) {
                        @unlink($newFileFullPath);
                    }
                    $errors[] = 'Nepodarilo sa uložiť album: ' . $e->getMessage();
                }
            }
        }



        // show form (on GET or validation/save error)
        // pass errors and previous values to view
        // If we are editing, and an existing album was loaded earlier, use it so the form shows current data.
        $album = null;
        if (!empty($formValues['id'])) {
            $existingForForm = Album::getOne((int)$formValues['id']);
            if ($existingForForm !== null) {
                // Use DB-loaded model (it will have correct internal state)
                $album = $existingForForm;
                // But if there were form values (text/picture) from a failed submit, prefer those for display
                if (isset($formValues['text'])) {
                    $album->setText($formValues['text']);
                }
                if (isset($formValues['picture'])) {
                    $album->setPicture($formValues['picture']);
                }
            }
        }
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
     * @throws \Exception
     */
    public function delete(Request $request): Response
    {
        try {
            $id = (int)$request->value('id');
            $album = Album::getOne($id);

            if (is_null($album)) {
                //pre AJAX vratim chybu v JSON formate
                if ($request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Album nebol nájdený.'], 404);
                }
                throw new HttpException(404);
            }

            //zmazanie fyzickeho subora obrazka
            if ($album->getPicture()) {
                $cesta = str_replace('images/', '', $album->getPicture());
                $filePath = 'images' . DIRECTORY_SEPARATOR . $cesta;
                //$filePath = Configuration::UPLOAD_DIR . str_replace('images/', '', $album->getPicture());
                //$filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $album->getPicture());
                if ($filePath && file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            //zmazanie z DTB
            $album->delete();

            //ZMENA PRE AJAX
            if ($request->isAjax()) {
                return $this->json(['success' => true]);
            }

        } catch (\Exception $e) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'DB Chyba: ' . $e->getMessage()], 500);
            }
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }

        //klasicke presmerovanie ak data-ajax nieje true
        return $this->redirect($this->url("album.index"));
    }

    private function formErrors(Request $request, bool $isEdit = false): array
    {
        $errors = [];
        $text = trim((string)$request->value('text') ?? '');
        $file = $request->file('picture');

        // Definovanie limitov na serverovej strane
        $maxTextLength = 255;
        $maxFileSize = 5242880; // 5 MB

        // --- Validácia Názvu albumu (text) ---
        if ($text === "") {
            $errors[] = "Názov albumu musí byť vyplnený!";
        } elseif (strlen($text) < 5) {
            $errors[] = "Názov albumu musí mať aspoň 5 znakov!";
        } elseif (strlen($text) > $maxTextLength) {
            // Kontrola, aby dĺžka neprekročila limit DB stĺpca VARCHAR(255)
            $errors[] = "Názov albumu nesmie presiahnuť " . $maxTextLength . " znakov!";
        }
        // POZNÁMKA: Kontrola unikátnosti tu chýba, ak je názov albumu jedinečný (odporúčam pridať).

        // --- Validácia Súboru obrázka (picture) ---
        $isNewUpload = ($file instanceof UploadedFile) && $file->getName() !== "";

        // Povinnosť súboru pri vytváraní nového albumu
        if (!$isEdit && !$isNewUpload) {
            $errors[] = "Súbor obrázka je povinný pre vytvorenie nového albumu!";
        }

        if ($isNewUpload) {
            // Kontrola MIME typu (skutočný typ súboru)
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
