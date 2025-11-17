<?php

namespace App\Controllers;

use App\Models\Album;
use App\Models\Post;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class AlbumController extends BaseController
{
    public function index(Request $request): Response
    {
        $albums = Album::getAll(null, [], 'id DESC');
        return $this->html(compact('albums'));
    }

    /**
     * view a single album and its posts
     * expects ?id={albumId}
     */
    public function view(Request $request): Response
    {
        $id = $request->value('id');
        if ($id === null) {
            // no id -> redirect to index
            return $this->redirect('?c=album&a=index');
        }

        $album = Album::getOne((int)$id);
        if ($album === null) {
            // album not found -> show index with message (or redirect)
            return $this->redirect('?c=album&a=index');
        }

        // load posts that belong to this album
        $posts = Post::getAll('albumId = ?', [(int)$id], 'created_at DESC');

        return $this->html(compact('album', 'posts'));
    }

    /**
     * create new album
     * GET -> show form
     * POST -> save and redirect to album view
     */
    public function create(Request $request): Response
    {
        if ($request->isPost()) {
            $text = $request->post('text') ?? '';
            $picture = $request->post('picture') ?? '';

            $album = new Album(null, $text, $picture);
            $album->save();

            return $this->redirect('?c=album&a=view&id=' . urlencode((string)$album->getId()));
        }

        return $this->html([], 'create');
    }
}