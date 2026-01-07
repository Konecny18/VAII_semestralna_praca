<?php
/** @var Album[] $albums */
/** @var Framework\Support\LinkGenerator $link */
/** @var IAuthenticator $auth */

use App\Models\Album;
use Framework\Core\IAuthenticator;
?>


<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h3>Albumy</h3>
        <?php if ($auth->isAdmin()): ?>
            <a href="<?= $link->url('album.add') ?>" class="btn btn-success">Vytvor album</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <?php if (empty($albums)): ?>
        <div class="col-12 text-center my-4">Žiadne albumy.</div>
    <?php else: ?>
        <?php foreach ($albums as $album): ?>
            <div class="col-auto">
                <div class="border album d-flex flex-column position-relative h-100">
                    <div>
                        <?php $picture = $album->getPicture(); ?>
                        <?php if ($picture !== ''): ?>
                            <img src="<?= $link->asset($picture) ?>" class="obrazok-karta" alt="Album image">
                        <?php else: ?>
                            <img src="<?= $link->asset('images/tat_logo.png') ?>" class="obrazok-karta" alt="Album placeholder">
                        <?php endif; ?>
                    </div>
                    <div class="m-2">
                        <strong><?= $album->getText() ?></strong>
                    </div>
                    <div class="m-2 d-flex gap-2 justify-content-end mt-auto">
                        <a href="<?= $link->url('post.index', ['albumId' => $album->getId()]) ?>" class="btn btn-primary">Zobraziť</a>

                        <?php if ($auth->isAdmin()): ?>
                            <a href="<?= $link->url('album.edit', ['id' => $album->getId()]) ?>" class="btn btn-warning">Upraviť</a>
                            <a href="<?= $link->url('album.delete', ['id' => $album->getId()]) ?>" class="btn btn-danger" onclick="return confirm('Naozaj zmazať album?')">Zmazať</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
