<?php

namespace App\Controllers;

use App\Models\Post;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

class PostController extends BaseController
{

    public function index(Request $request): Response
    {
        $albumId = $request->value('albumId');
        if ($albumId !== null) {
            $posts = Post::getAll('albumId = ?', [(int)$albumId], 'created_at DESC');
        } else {
            $posts = Post::getAll(null, [], 'created_at DESC');
        }

        return $this->html(compact('posts'));
    }
}