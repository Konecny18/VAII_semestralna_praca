<?php

/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var \App\Models\Post[] $posts */
/** @var \Framework\Core\IAuthenticator $auth */

use App\Configuration;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <?php $currentAlbumId = isset($albumId) ? (int)$albumId : 0; ?>
            <a href="<?= $link->url('post.add', ['albumId' => $currentAlbumId]) ?>" class="btn btn-success">Pridať príspevok</a>
        </div>
    </div>
    <div class="row justify-content-center">
        <?php foreach ($posts as $post): ?>
            <div class="col-3 d-flex gap-4 flex-column">
                <div class="border post d-flex flex-column">
                    <div>
                        <img src="<?= $link->asset(Configuration::UPLOAD_URL . $post->getPicture()) ?>" class="img-fluid" alt="Post image">
                    </div>
                    <div class="m-2">
                        <?= $post->getText() ?>
                    </div>
                    <div class="m-2 d-flex gap-2 justify-content-end">
                        <span class="flex-grow-1"></span>
<!--                        --><?php //if ($auth->getUser()->getName() === $post->getAuthor()): ?>
                            <a href="<?= $link->url('post.edit', ['id' => $post->getId(), 'albumId' => $currentAlbumId]) ?>" class="btn btn-warning">Upraviť</a>
                            <a href="<?= $link->url('post.delete', ['id' => $post->getId(), 'albumId' => $currentAlbumId]) ?>" class="btn btn-danger">Zmazať</a>
<!--                        --><?php //endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>