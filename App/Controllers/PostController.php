<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\Post;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class PostController extends BaseController
{

    public function index(Request $request): Response
    {
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
                    'albumId' => $albumId
                ]
            );
        } catch (Exception $e) {
            throw new HttpException(500, "DB Chyba: " . $e->getMessage());
        }
    }


    public function add(Request $request): Response
    {
        $albumId = (int)$request->value('albumId');
        return $this->html(['albumId' => $albumId]);
    }

    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');
        $post = Post::getOne($id);
        if (is_null($post)) {
            throw new HttpException(404);
        }
        $albumId = (int)$request->value('albumId');
        return $this->html(array_merge(compact('post'), ['albumId' => $albumId]));
    }

    public function save(Request $request): Response
    {
        // --- 1. Inicializácia a Sanitizácia ---
        $idRaw = $request->post('id') ?? null;
        $id = ($idRaw === '' || $idRaw === null) ? null : (int)$idRaw;
        $isEdit = !empty($id);

        // Zásadná sanitizácia textu (odstránenie HTML/JS tagov) pred validáciou a uložením
        $text = strip_tags(trim((string)$request->value('text') ?? ''));
        $albumId = (int)$request->value('albumId');

        // 2. Validácia
        $formErrors = $this->formErrors($request, $isEdit);

        if (count($formErrors) > 0) {
            // Ak validácia zlyhala:
            // Pripravíme Model pre re-populáciu formulára s chybami a pôvodnými dátami.
            $post = ($isEdit) ? Post::getOne($id) : new Post();
            $post->setText($text); // Vrátime sanitizovaný text
            $post->setAlbumId($albumId);

            return $this->html(
                compact('post', 'formErrors'), ($isEdit) ? 'edit' : 'add'
            );
        }

        // --- 3. Spracovanie Dát a Súboru (Iba ak je validácia úspešná) ---
        try {
            // Získanie existujúceho alebo vytvorenie nového modelu
            if ($isEdit) {
                $post = Post::getOne($id);
                if (is_null($post)) {
                    throw new \Exception("Príspevok neexistuje.");
                }
            } else {
                $post = new Post();
            }

            $oldPicturePath = $post->getPicture(); // Uložíme si starú cestu pre prípad mazania
            $post->setText($text); // Nastavíme sanitizovaný text
            $post->setAlbumId($albumId);

            $newFile = $request->file('picture');
            $newFileFullPath = null; // Pre rollback v prípade DB chyby

            // Spracovanie uploadu, len ak je nahraný nový súbor
            if ($newFile && $newFile->getName() != "") {
                // Kontrola/vytvorenie adresára
                if (!is_dir(Configuration::UPLOAD_DIR)) {
                    if (!@mkdir(Configuration::UPLOAD_DIR, 0777, true) && !is_dir(Configuration::UPLOAD_DIR)) {
                        throw new HttpException(500, 'Nepodarilo sa vytvoriť adresár pre nahrávanie súborov.');
                    }
                }

                // Unikátne a bezpečné meno súboru
                $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $newFile->getName());
                $targetPath = Configuration::UPLOAD_DIR . $uniqueName;

                // Uloženie súboru
                if(!$newFile->store($targetPath)) {
                    throw new HttpException(500, 'Nepodarilo sa uložiť nahraný súbor.');
                }
                $post->setPicture($uniqueName); // Aktualizujeme cestu v modeli
                $newFileFullPath = $targetPath; // Uložíme cestu pre rollback
            }

            // --- 4. Uloženie do Databázy ---
            $post->save();

            // 5. Odstránenie starého súboru AŽ PO úspešnom uložení nového
            if ($newFileFullPath !== null && $oldPicturePath != "") {
                $oldPath = Configuration::UPLOAD_DIR . $oldPicturePath;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Úspech -> presmerovanie
            return $this->redirect($this->url('post.index', ['albumId' => $post->getAlbumId()]));

        } catch (\Throwable $e) {
            // Rollback: Ak nastala DB chyba, odstránime práve nahraný súbor
            if ($newFileFullPath !== null && file_exists($newFileFullPath)) {
                @unlink($newFileFullPath);
            }
            // Vrátenie chyby
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
    }

//    public function save(Request $request): Response
//    {
//        $id = (int)$request->value('id');
//        $oldFileName = "";
//
//        if($id > 0) {
//            $post = Post::getOne($id);
//            $oldFileName = $post->getPicture();
//        } else {
//            $post = new Post();
//        }
//
//        $post->setAlbumId((int)$request->value('albumId'));
//        $post->setText((string)$request->value('text'));
//        $post->setPicture((string)$request->value('picture'));
//
//        $formErrors = $this->formErrors($request);
//        if (count($formErrors) > 0) {
//            return $this->html(
//                compact('post', 'formErrors'), ($id > 0) ? 'edit' : 'add',
//            );
//        } else {
//            if (!is_dir(Configuration::UPLOAD_DIR)) {
//                // try to create uploads directory and provide a clear error on failure (permissions etc.)
//                if (!@mkdir(Configuration::UPLOAD_DIR, 0777, true) && !is_dir(Configuration::UPLOAD_DIR)) {
//                    throw new HttpException(500, 'Nepodarilo sa vytvoriť adresár pre nahrávanie súborov. Skontrolujte práva k adresáru.');
//                }
//            }
//        }
//
//        if ($oldFileName != "") {
//            $oldPath = Configuration::UPLOAD_DIR . $oldFileName;
//            if (is_file($oldPath)) {
//                @unlink($oldPath);
//            }
//        }
//
//        $newFile = $request->file('picture');
//        $uniqueName = time() . '-' . $newFile->getName();
//        $targetPath = Configuration::UPLOAD_DIR . $uniqueName;
//
//        if(!$newFile->store($targetPath)) {
//            throw new HttpException(500, 'Nepodarilo sa uložiť súbor.');
//        }
//
//        $post->setPicture($uniqueName);
//
//        try {
//            $post->save();
//        } catch (\Exception $e) {
//            if (is_file($targetPath)) {
//                @unlink($targetPath);
//            }
//            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
//        }
//        // redirect back to the posts index for the same album
//        return $this->redirect($this->url('post.index', ['albumId' => $post->getAlbumId()]));
//
//    }

    public function delete(Request $request): Response
    {
        try {
            $id = (int)$request->value('id');
            $post = Post::getOne($id);

            if (is_null($post)) {
                throw new HttpException(404);
            }

            $albumId = $post->getAlbumId();
            @unlink(Configuration::UPLOAD_DIR . $post->getPicture());
            $post->delete();

        } catch (\Exception $e) {
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
        // keep album context when redirecting
        if (!empty($albumId)) {
            return $this->redirect($this->url('post.index', ['albumId' => $albumId]));
        }
        return $this->redirect($this->url('post.index'));
    }

    // Pôvodná metóda formErrors by sa mala nahradiť touto:

    private function formErrors(Request $request, bool $isEdit = false): array
    {
        $errors = [];
        $text = trim((string)$request->value('text') ?? '');
        $albumId = (int)$request->value('albumId');
        $file = $request->file('picture');

        // Limity
        $maxTextLength = 255;
        $maxFileSize = 5242880; // 5 MB

        // --- 1. Validácia albumId ---
        if ($albumId <= 0 || is_null(\App\Models\Album::getOne($albumId))) {
            $errors[] = "Album, ku ktorému sa snažíte príspevok pridať, neexistuje.";
        }

        // --- 2. Validácia Textu ---
        if ($text === "") {
            $errors[] = "Text príspevku musí byť vyplnený!";
        } elseif (strlen($text) < 5) {
            $errors[] = "Text príspevku musí mať aspoň 5 znakov!";
        } elseif (strlen($text) > $maxTextLength) {
            $errors[] = "Text príspevku nesmie presiahnuť " . $maxTextLength . " znakov!";
        }

        // --- 3. Validácia Obrázka ---
        $isNewUpload = $file && $file->getName() !== "";

        // Pri vytváraní (nie editácii) je súbor povinný.
        if (!$isEdit && !$isNewUpload) {
            $errors[] = "Súbor obrázka je povinný pre vytvorenie príspevku!";
        }

        if ($isNewUpload) {
            // Kontrola MIME typu a Max. veľkosti
            if (!in_array($file->getType(), ['image/jpeg', 'image/png'])) {
                $errors[] = "Obrázok musí byť typu JPG alebo PNG!";
            }
            if ($file->getSize() > $maxFileSize) {
                $errors[] = "Veľkosť obrázka nesmie presiahnuť 5 MB!";
            }
        }

        return $errors;
    }



//    private function formErrors(Request $request): array
//    {
//        $errors = [];
//        if ($request->file('picture')->getName() == "") {
//            $errors[] = "Pole Súbor obrázka musí byť vyplnené!";
//        }
//        if ($request->value('text') == "") {
//            $errors[] = "Pole Text príspevku musí byť vyplnené!";
//        }
//        if ($request->file('picture')->getName() != "" &&
//            !in_array($request->file('picture')->getType(), ['image/jpeg', 'image/png'])) {
//            $errors[] = "Obrázok musí byť typu JPG alebo PNG!";
//        }
//        if ($request->value('text') != "" && strlen($request->value('text') < 5)) {
//            $errors[] = "Počet znakov v text príspevku musí byť viac ako 5!";
//        }
//        return $errors;
//    }
}