<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var Post[] $posts */
/** @var IAuthenticator $auth */

use App\Configuration;
use App\Models\Post;
use Framework\Core\IAuthenticator;

?>

<!--
row – Bootstrap grid riadok (flex kontajner)
mb-4 – margin-bottom, odsadenie spodnej časti
-->
<div class="row mb-4">
    <!--
   col – jeden stĺpec v gride (automatická šírka)
   d-flex – zapne flexbox pre vnútorné prvky
   align-items-center – vertikálne zarovnanie na stred
   justify-content-between – prvky sú rozložené na opačné strany
   -->
    <div class="col d-flex align-items-center justify-content-between">

<!--        zistuje do ktoreho albumu sme vstupili-->
        <?php $currentAlbumId = isset($albumId) ? (int)$albumId : 0; ?>

        <?php if ($auth->isAdmin()): ?>
<!--            ma tag a lebo to sa pouziva ked chcem poslat niekam pouzivatela a button sa pouziva na vykonanie akcie-->
            <a href="<?= $link->url('post.add', ['albumId' => $currentAlbumId]) ?>"
               class="btn btn-success me-2">Pridať príspevok</a>

            <!--
            d-flex – zapne flexbox layout
            align-items-center – vertikálne zarovnanie prvkov na stred
            gap-3 – medzera medzi prvkami (Bootstrap spacing scale)
            -->
            <div class="d-flex align-items-center gap-3">
                <button id="btn-bulk-delete-posts"
                        class="btn btn-danger"
                        data-url="<?= $link->url('post.bulkDelete') ?>"
                        data-csrf="<?= $_SESSION['csrf_token'] ?>">
                    Vymazať vybrané (<span id="selected-count">0</span>)
                </button>

                <!--
                form-check – Bootstrap wrapper pre checkbox/radio
                m-0 – margin zo všetkých strán
                -->
                <div class="form-check mb-0">
                    <!--form-check-input – štýlovaný Bootstrap checkbox-->
                    <input class="form-check-input" type="checkbox" id="select-all-posts">
                    <label class="form-check-label btn btn-secondary" for="select-all-posts">
                        Vybrať všetko
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 justify-content-center" id="posts-container" data-bulk-url="<?= $link->url('post.bulkDelete') ?>" data-csrf="<?= $_SESSION['csrf_token'] ?>">
    <?php foreach ($posts as $post): ?>
        <!--position-relative – referenčný bod pre absolute prvky -->
        <div class="col-auto position-relative" id="post-card-<?= $post->getId() ?>">
            <?php if ($auth->isAdmin()): ?>
                <div class="form-check position-absolute m-2" style="z-index:10;">
                    <input class="form-check-input post-checkbox" type="checkbox" value="<?= $post->getId() ?>" id="select-post-<?= $post->getId() ?>">
                    <label class="visually-hidden" for="select-post-<?= $post->getId() ?>">Vybrať príspevok <?= $post->getId() ?></label>
                </div>
            <?php endif; ?>
            <!--
            border – tenký Bootstrap okraj
            d-flex – flexbox layout
            flex-column – prvky pod sebou (vertikálne)
            h-100 – výška 100 % rodiča
            -->
<!--            pouzivam album aby to vyzeralo tak isto ako v albume-->
            <div class="border album d-flex flex-column h-100">
                <div>
                    <a href="#" class="klikatelny-obrazok"
                       data-bs-toggle="modal"
                       data-bs-target="#imageModal"
                       data-image="<?= htmlspecialchars($link->asset(Configuration::UPLOAD_URL . $post->getPicture()), ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars($link->asset(Configuration::UPLOAD_URL . $post->getPicture()), ENT_QUOTES, 'UTF-8') ?>" class="obrazok-karta" alt="Post image">
                    </a>
                </div>
                <?php if ($auth->isAdmin()): ?>
                    <!--
                    justify-content-end – zarovnanie prvkov doprava
                    gap-2 – medzera medzi tlačidlami
                    mt-2 – margin-top
                    -->
                    <div class="m-2 d-flex gap-2 justify-content-end mt-2">
                        <a href="<?= $link->url('post.edit', ['id' => $post->getId(), 'albumId' => $currentAlbumId]) ?>" class="btn btn-warning">Upraviť</a>
                        <a href="<?= $link->url('post.delete', ['id' => $post->getId()]) ?>"
                           class="btn btn-sm btn-danger tlacidlo-vymazat"
                           data-ajax="true"
                           data-target-id="post-card-<?= $post->getId() ?>"
                           data-message="Naozaj chceš vymazať túto fotku z albumu?">
                            <i class="bi bi-trash"></i> Zmazať
                        </a>

<!--                        zaloha keby ajax zlyha, inak si ajax vytiahne csrf token sam-->
                        <form id="delete-post-<?= $post->getId() ?>"
                              method="post"
                              action="<?= $link->url('post.delete', ['id' => $post->getId()]) ?>"
                              style="display:none;">
                            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!--
modal fade – Bootstrap modal s animáciou
tabindex -1 – modal nie je focusovateľný bežne
-->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <!--
    modal-dialog-centered – vertikálne vycentrovanie modalu
    modal-xl – extra veľký modal
    -->
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <!--
        modal-content – obsah modalu
        bg-transparent – priehľadné pozadie
        border-0 – bez okraja
        -->
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body text-center p-0 position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

                <button type="button" id="prevImg" class="gallery-nav gallery-nav-left">
                    <i class="bi bi-chevron-left"></i>
                </button>

                <img id="imageModalImg" src="" alt="Full image">

                <button type="button" id="nextImg" class="gallery-nav gallery-nav-right">
                    <i class="bi bi-chevron-right"></i>
                </button>

            </div>
        </div>
    </div>
</div>

<!-- Load external JS that manages the post image modal -->
<script src="<?= $link->asset('js/show-move-posts.js') ?>"></script>
<!-- Bulk delete script -->
<script src="<?= $link->asset('js/posts-bulk-delete-ajax.js') ?>"></script>
