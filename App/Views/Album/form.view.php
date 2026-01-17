<?php

/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var Album $album */
/** @var array $errors */
/** @var string $text */
/** @var string $picture */
/** @var int|null $id */

use App\Models\Album;

?>

<form method="post" action="<?= $link->url('album.save') ?>" enctype="multipart/form-data">

    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

    <input type="hidden" name="id" value="<?= @$album?->getId() ?>">

    <label for="picture" class="form-label fw-bold">Súbor obrázka</label>
    <div class="input-group mb-3 has-validation">
        <?php $isEdit = !empty(@$album?->getId()); ?>
        <input type="file" class="form-control " name="picture" id="picture" <?= $isEdit ? '' : 'required' ?> accept="image/png, image/jpeg">
    </div>
    <?php if (@$album?->getPicture() != ""): ?>
        <div class="text-muted mb-3">Pôvodný súbor: <?= htmlspecialchars(substr($album->getPicture(), strrpos($album->getPicture(), '_') + 1)) ?></div>
    <?php endif; ?>
    <label for="text" class="form-label fw-bold">Názov albumu</label>
    <div class="input-group has-validation mb-3 ">
        <textarea class="form-control" aria-label="With textarea" name="text" id="text"
                  required minlength="5" maxlength="255"><?= htmlspecialchars(@$album?->getText() ?? '') ?></textarea>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= $link->url('album.index') ?>" class="btn btn-secondary">Späť</a>
        <button type="submit" class="btn btn-primary">Uložiť</button>
    </div>

</form>
