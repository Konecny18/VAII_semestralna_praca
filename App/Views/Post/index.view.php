<?php

/** @var \App\Models\Post[] $posts */
/** @var Framework\Support\LinkGenerator $link */
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <?php if (empty($posts)): ?>
            <div class="col-12 text-center my-4">Žiadne príspevky v albume.</div>
        <?php else: ?>
            <?php foreach ($posts as $post):  ?>
                <div class="col-3 d-flex gap-4 flex-column">
                    <div class="border post d-flex flex-column">
                        <div>
                            <img src="<?= htmlspecialchars($post->getPicture()) ?>" alt="Album image <?= htmlspecialchars((string)$post->getId()) ?>" class="img-fluid">
                        </div>
                        <div class="m-2">
                            <?= nl2br(htmlspecialchars($post->getText())) ?>
                        </div>
                        <div class="m-2 d-flex gap-2 justify-content-end">
                            <a href="#" class="btn btn-primary">Upraviť</a>
                            <a href="#"  class="btn btn-danger">Zmazať</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>