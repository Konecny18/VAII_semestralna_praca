<?php

/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var Post $post */

use App\Models\Post;

// ak existuje post napr pri edit tak sa pouzije jej hodnota
//ak neexistuje tak bude null
$post = $post ?? null;
$isEdit = !empty(@$post?->getId());

?>

<form method="post" action="<?= $link->url('post.save') ?>" enctype="multipart/form-data">

    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

    <input type="hidden" name="id" value="<?= @$post?->getId() ?>">

    <input type="hidden" name="albumId" value="<?= isset($albumId) ? (int)$albumId : (@$post?->getAlbumId() ?? '') ?>">

    <label for="picture" class="form-label fw-bold">Súbor obrázka</label>
    <div class="input-group mb-3 has-validation">
        <input type="file" class="form-control " name="pictures[]" id="picture" accept="image/png, image/jpeg"
        <?= $isEdit ? '' : 'required' ?>
        multiple>
    </div>
    <?php if (@$post?->getPicture() != ""): ?>
<!--                odreze vsetky znaky pred _ tak aby videl iba nazov suboru bez unikatneho id-->
        <div class="text-muted mb-3">Pôvodný súbor: <?= htmlspecialchars(substr($post->getPicture(), strrpos($post->getPicture(), '_') + 1)) ?></div>

    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= $link->url('post.index') ?>" class="btn btn-secondary">Späť</a>
        <button type="submit" class="btn btn-primary">Uložiť</button>
    </div>
</form>

