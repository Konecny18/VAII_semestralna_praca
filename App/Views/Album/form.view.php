<?php

/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var \App\Models\Album $album */
/** @var array $errors */
/** @var string $text */
/** @var string $picture */
/** @var int|null $id */
?>

<?php if (!empty($errors ?? [])): ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger" role="alert">
            <?= $error ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-10 d-flex gap-4 flex-column">
            <form method="post" action="<?= $link->url('album.add') ?>" enctype="multipart/form-data">

                <input type="hidden" name="id" value="<?= @$album?->getId() ?>">

                <label for="picture" class="form-label fw-bold">Súbor obrázka</label>
                <div class="input-group mb-3 has-validation">
                    <input type="file" class="form-control " name="picture" id="picture">
                </div>
                <?php if (@$album?->getPicture() != ""): ?>
                    <div class="text-muted mb-3">Pôvodný súbor: <?= substr($album->getPicture(), strpos($album->getPicture(), '-') + 1) ?></div>
                <?php endif; ?>
                <label for="text" class="form-label fw-bold">Názov albumu</label>
                <div class="input-group has-validation mb-3 ">
                    <textarea class="form-control" aria-label="With textarea" name="text" id="text"><?= @$album?->getText() ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Uložiť</button>
            </form>
        </div>
    </div>
</div>