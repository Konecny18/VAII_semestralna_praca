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
        try {
            return $this->html(
                [
                    'albums' => Album::getAll(null, [], 'id DESC')
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
        $id = (int)$request->value('key');
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
            $text = trim((string)($request->post('text') ?? ''));
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
            if (empty($errors)) {
                try {
                    $uploaded = $request->file('picture');
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
                        } else {
                            $errors[] = 'Nepodarilo sa uložiť nahraný súbor.';
                        }
                    } else {
                        // no new uploaded file — if editing, keep existing picture
                        if ($isEdit) {
                            $existing = Album::getOne((int)$id);
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
                    // ensure id passed to Album is either null or int
                    $albumIdForModel = $formValues['id'] === '' ? null : $formValues['id'];
                    $album = new Album($albumIdForModel, $formValues['text'], $formValues['picture']);
                    $album->save();

                    // success -> redirect to view
                    //return $this->redirect($this->url('album.view') . urlencode((string)$album->getId()));
                    return $this->redirect($this->url('album.index'));
                } catch (\Throwable $e) {
                    // don't echo or print; pass the message to the view
                    $errors[] = 'Nepodarilo sa uložiť album: ' . $e->getMessage();
                }
            }
        }

        // show form (on GET or validation/save error)
        // pass errors and previous values to view
        $album = new Album($formValues['id'] ?? null, $formValues['text'], $formValues['picture']);
        return $this->html(array_merge(compact('errors', 'album')), 'add');
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

        $file = $request->file('picture');
        $text = trim((string)$request->value('text') ?? '');

        // picture required when creating new album
        if (!$isEdit) {
            if (!($file instanceof UploadedFile) || $file->getName() == "") {
                $errors[] = "Pole Súbor obrázka musí byť vyplnené!";
            }
        }

        if ($text == "") {
            $errors[] = "Pole Názov albumu musí byť vyplnené!";
        }

        if ($file instanceof UploadedFile && $file->getName() != "") {
            if (!in_array($file->getType(), ['image/jpeg', 'image/png'])) {
                $errors[] = "Obrázok musí byť typu JPG alebo PNG!";
            }
        }

        if ($text != "" && strlen($text) < 5) {
            $errors[] = "Počet znakov v názve albumu musí byť viac ako 5!";
        }
        return $errors;
    }
}