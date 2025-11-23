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
            return $this->html(
                [
                    'posts' => Post::getAll()
                ]
            );
        } catch (Exception $e) {
            throw new HttpException(500, "DB Chyba: " . $e->getMessage());
        }
    }


    public function add(Request $request): Response
    {
        return $this->html();
    }

    public function edit(Request $request): Response
    {
        $id = (int)$request->value('id');
        $post = Post::getOne($id);
        if (is_null($post)) {
            throw new HttpException(404);
        }
        return $this->html(compact('post'));
    }

    public function save(Request $request): Response
    {
        $id = (int)$request->value('id');
        $oldFileName = "";

        if($id > 0) {
            $post = Post::getOne($id);
            $oldFileName = $post->getPicture();
        } else {
            $post = new Post();
        }

        $post->setAlbumId((int)$request->value('albumId'));
        $post->setText((string)$request->value('text'));
        $post->setPicture((string)$request->value('picture'));

        $formErrors = $this->formErrors($request);
        if (count($formErrors) > 0) {
            return $this->html(
                compact('post', 'formErrors'), ($id > 0) ? 'edit' : 'add',
            );
        } else {
            if (!is_dir(Configuration::UPLOAD_DIR)) {
                if (!@mkdir(Configuration::UPLOAD_DIR, 0777, true) && !@isdir(Configuration::UPLOAD_DIR)) {
                    throw new HttpException(500, 'Nepodarilo sa vytvoriť adresár ore nahrávanie súborov.',);
                }
            }
        }

        if ($oldFileName != "") {
            $oldPath = Configuration::UPLOAD_DIR . $oldFileName;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $newFile = $request->file('picture');
        $uniqueName = time() . '-' . $newFile->getName();
        $targetPath = Configuration::UPLOAD_DIR . $uniqueName;

        if(!$newFile->store($targetPath)) {
            throw new HttpException(500, 'Nepodarilo sa uložiť súbor.');
        }

        $post->setPicture($uniqueName);

        try {
            $post->save();
        } catch (\Exception $e) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
        return $this->redirect($this->url('post.index'));

    }

    public function delete(Request $request): Response
    {
        try {
            $id = (int)$request->value('id');
            $post = Post::getOne($id);

            if (is_null($post)) {
                throw new HttpException(404);
            }

            @unlink(Configuration::UPLOAD_DIR . $post->getPicture());
            $post->delete();

        } catch (\Exception $e) {
            throw new HttpException(500, 'DB chyba: ' . $e->getMessage());
        }
        return $this->redirect($this->url('post.index'));
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