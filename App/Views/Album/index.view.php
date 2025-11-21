<?php

/** @var \App\Models\Album[] $albums */
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var \Framework\Core\IAuthenticator $auth */
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3>Albumy</h3>
            <a href="<?= $link->url('album.add') ?>" class="btn btn-success">Vytvor album</a>
        </div>
    </div>

    <div class="row justify-content-center">

        <?php if (empty($albums)): ?>
            <div class="col-12 text-center my-4">Žiadne albumy.</div>
        <?php else: ?>
            <?php foreach ($albums as $album): ?>
                <div class="col-3 d-flex gap-4 flex-column">
                    <div class="border album d-flex flex-column position-relative">
                        <div>
                            <?php $picture = (string)$album->getPicture(); ?>
                            <?php if ($picture !== ''): ?>
                                <img src="<?= $link->asset($picture) ?>" class="img-fluid" alt="Album image">
                            <?php else: ?>
                                <img src="<?= $link->asset('images/tat_logo.png') ?>" class="img-fluid" alt="Album placeholder">
                            <?php endif; ?>
                        </div>
                        <div class="m-2">
                            <strong><?= (string)$album->getText() ?></strong>
                        </div>
                        <div class="m-2">
                            <a href="<?= $link->url('album.view', ['id' => $album->getId()]) ?>" class="btn btn-primary">Zobraziť</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
