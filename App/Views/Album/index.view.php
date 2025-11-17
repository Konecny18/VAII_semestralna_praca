<?php

/** @var \App\Models\Album[] $albums */
/** @var Framework\Support\LinkGenerator $link */
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3>Albumy</h3>
            <a href="<?= htmlspecialchars('?c=album&a=create') ?>" class="btn btn-success">Nové album</a>
        </div>
    </div>

    <div class="row justify-content-center">
        <?php if (empty($albums)): ?>
            <div class="col-12 text-center my-4">Žiadne albumy.</div>
        <?php else: ?>
            <?php foreach ($albums as $album): ?>
                <div class="col-3 d-flex gap-4 flex-column">
                    <div class="border album d-flex flex-column position-relative">
                        <!-- Small create button on each album card (button with onclick navigation) -->
                        <button type="button" class="btn btn-sm btn-outline-primary position-absolute" title="Vytvoriť nové album" aria-label="Vytvoriť nové album" style="top:8px; right:8px; z-index:5;" onclick="window.location.href='?c=album&a=create'">Nové album</button>

                        <a href="<?= htmlspecialchars('?c=album&a=view&id=' . urlencode((string)$album->getId())) ?>" class="stretched-link" style="text-decoration:none;color:inherit;">
                            <div>
                                <img src="<?= htmlspecialchars($album->getPicture()) ?>" alt="Album <?= htmlspecialchars((string)$album->getId()) ?>" class="img-fluid">
                            </div>
                            <div class="m-2">
                                <strong><?= nl2br(htmlspecialchars($album->getText())) ?></strong>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
