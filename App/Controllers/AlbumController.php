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
            $picture = trim((string)($request->post('picture') ?? ''));
            $id = $request->post('id') ?? null;

            $formValues['text'] = $text;
            $formValues['picture'] = $picture;
            $formValues['id'] = $id;

            // handle uploaded file if present
            try {
                $uploaded = $request->file('picture');
                if ($uploaded instanceof UploadedFile && $uploaded->isOk()) {
                    $imagesDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images';
                    if (!is_dir($imagesDir)) {
                        mkdir($imagesDir, 0755, true);
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
                }
            } catch (\Throwable $e) {
                $errors[] = 'Chyba pri nahrávaní súboru: ' . $e->getMessage();
            }

            // basic validation
            if ($text === '') {
                $errors[] = 'Názov albumu je povinný.';
            }

            // if picture is provided as URL validate it
            if ($picture !== '' && !str_starts_with($picture, 'images/') && filter_var($picture, FILTER_VALIDATE_URL) === false) {
                $errors[] = 'URL obrázka nie je platná.';
            }

            if (empty($errors)) {
                try {
                    $album = new Album(null, $text, $picture);
                    $album->save();

                    // success -> redirect to view
                    return $this->redirect('?c=album&a=view&id=' . urlencode((string)$album->getId()));
                } catch (\Throwable $e) {
                    // don't echo or print; pass the message to the view
                    $errors[] = 'Nepodarilo sa uložiť album: ' . $e->getMessage();
                }
            }
        }

        // show form (on GET or validation/save error)
        // pass errors and previous values to view
        $album = new Album($formValues['id'], $formValues['text'], $formValues['picture']); // Vytvorte objekt modelu
        return $this->html(array_merge(compact('errors', 'album')), 'create');
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

        return $this->redirect($this->url("post.index"));
    }

    private function formErrors(Request $request): array
    {
        $errors = [];
        if ($request->file('picture')->getName() == "") {
            $errors[] = "Pole Súbor obrázka musí byť vyplnené!";
        }
        if ($request->value('text') == "") {
            $errors[] = "Pole Text príspevku musí byť vyplnené!";
        }
        if ($request->file('picture')->getName() != "" &&
            !in_array($request->file('picture')->getType(), ['image/jpeg', 'image/png'])) {
            $errors[] = "Obrázok musí byť typu JPG alebo PNG!";
        }
        if ($request->value('text') != "" && strlen($request->value('text') < 5)) {
            $errors[] = "Počet znakov v text príspevku musí byť viac ako 5!";
        }
        return $errors;
    }
}