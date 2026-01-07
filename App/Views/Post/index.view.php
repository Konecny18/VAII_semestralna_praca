<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var Post[] $posts */
/** @var IAuthenticator $auth */

use App\Configuration;
use App\Models\Post;
use Framework\Core\IAuthenticator;


?>


<div class="row mb-4">
    <div class="col">
        <?php $currentAlbumId = isset($albumId) ? (int)$albumId : 0; ?>
        <?php if ($auth->isAdmin()): ?>
            <a href="<?= $link->url('post.add', ['albumId' => $currentAlbumId]) ?>" class="btn btn-success">Pridať príspevok</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <?php foreach ($posts as $post): ?>
        <div class="col-auto">
<!--            pouzivam album aby to vyzeralo tak isto ako v albume-->
            <div class="border album d-flex flex-column h-100">
                <div>
                    <a href="#" class="klikatelny-obrazok" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="<?= $link->asset(Configuration::UPLOAD_URL . $post->getPicture()) ?>">
                        <img src="<?= $link->asset(Configuration::UPLOAD_URL . $post->getPicture()) ?>" class="obrazok-karta" alt="Post image">
                    </a>
                </div>
                <div class="m-2">
                    <?= $post->getText() ?>
                </div>
                <?php if ($auth->isAdmin()): ?>
                    <div class="m-2 d-flex gap-2 justify-content-end mt-auto">
                        <a href="<?= $link->url('post.edit', ['id' => $post->getId(), 'albumId' => $currentAlbumId]) ?>" class="btn btn-warning">Upraviť</a>
                        <a href="<?= $link->url('post.delete', ['id' => $post->getId(), 'albumId' => $currentAlbumId]) ?>" class="btn btn-danger" onclick="return confirm('Naozaj zmazať príspevok?')">Zmazať</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>


<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body text-center p-0 position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

                <button type="button" id="prevImg" class="btn text-white position-absolute top-50 start-0 translate-middle-y fs-1 border-0 bg-transparent" style="z-index: 11;">
                    <i class="bi bi-chevron-left"></i>
                </button>

                <img id="imageModalImg" src="" alt="Full image">

                <button type="button" id="nextImg" class="btn text-white position-absolute top-50 end-0 translate-middle-y fs-1 border-0 bg-transparent" style="z-index: 11;">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Load external JS that manages the post image modal -->
<script src="<?= $link->asset('js/show-move-posts.js') ?>"></script>
