<?php

/** @var \App\Models\Album $album */
/** @var \App\Models\Post[] $posts */
/** @var Framework\Support\LinkGenerator $link */
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center gap-3">
            <div style="max-width:120px;">
                <img src="<?= htmlspecialchars($album->getPicture()) ?>" alt="Album <?= htmlspecialchars((string)$album->getId()) ?>" class="img-fluid rounded">
            </div>
            <div>
                <h2><?= nl2br(htmlspecialchars($album->getText())) ?></h2>
                <a href="<?= htmlspecialchars('?c=album&a=index') ?>" class="btn btn-link">Späť na albumy</a>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if (empty($posts)): ?>
            <div class="col-12 text-center my-4">Žiadne príspevky v tomto albume.</div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="col-3 d-flex gap-4 flex-column">
                    <div class="border post d-flex flex-column">
                        <div>
                            <img src="<?= htmlspecialchars($post->getPicture()) ?>" alt="Post <?= htmlspecialchars((string)$post->getId()) ?>" class="img-fluid">
                        </div>
                        <div class="m-2">
                            <?= nl2br(htmlspecialchars($post->getText())) ?>
                        </div>
                        <div class="m-2 d-flex gap-2 justify-content-end">
                            <a href="#" class="btn btn-primary">Upraviť</a>
                            <a href="#" class="btn btn-danger">Zmazať</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

