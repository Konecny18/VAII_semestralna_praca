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
    <!-- Use row-cols utilities so items wrap correctly at breakpoints; add flex-wrap as explicit fallback -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 justify-content-center flex-wrap">
        <?php foreach ($posts as $post): ?>
            <div class="col">
                <div class="border post d-flex flex-column h-100">
                    <div>
                        <img src="<?= $link->asset(Configuration::UPLOAD_URL . $post->getPicture()) ?>" class="card-img" alt="Post image">
                    </div>
                    <div class="m-2">
                        <?= $post->getText() ?>
                    </div>
                    <div class="m-2 d-flex gap-2 justify-content-end mt-auto">
                        <?php // show actions (adjust auth check as needed) ?>
                        <a href="<?= $link->url('post.edit', ['id' => $post->getId(), 'albumId' => $currentAlbumId]) ?>" class="btn btn-warning">Upraviť</a>
                        <a href="<?= $link->url('post.delete', ['id' => $post->getId(), 'albumId' => $currentAlbumId]) ?>" class="btn btn-danger">Zmazať</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>