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

class AlbumController extends BaseController
{
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
        /*
        $albums = Album::getAll(null, [], 'id DESC');
        return $this->html(compact('albums'));*/
    }

    /**
     * add new album
     * GET -> show form
     * POST -> save and redirect to album view
     */

    public function add(Request $request): Response
    {
        return $this->html();
    }

    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');
        $album = Album::getOne($id);
        if (is_null($album)) {
            throw new HttpException(404);
        }
        return $this->html(compact('album'), 'edit');
    }
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


    public function delete(Request $request): Response
    {
        try {
            $id = (int)$request->value('id');
            $album = Album::getOne($id);

            if (is_null($album)) {
                throw new HttpException(404);
            }

            // build full path to public file and delete if exists
            $filePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $album->getPicture());
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }

            $album->delete();

        } catch (\Exception $e) {
            throw new HttpException(500, 'DB Chyba: ' . $e->getMessage());
        }

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