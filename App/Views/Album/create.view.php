<?php

/** @var Framework\Support\LinkGenerator $link */
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <h3>Vytvoriť nový album</h3>
            <form method="post" action="<?= htmlspecialchars('?c=album&a=create') ?>">
                <div class="mb-3">
                    <label for="text" class="form-label">Názov / popis</label>
                    <input type="text" id="text" name="text" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="picture" class="form-label">URL obrázka (thumbnail)</label>
                    <input type="url" id="picture" name="picture" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Vytvoriť album</button>
                <a href="<?= htmlspecialchars('?c=album&a=index') ?>" class="btn btn-link">Zrušiť</a>
            </form>
        </div>
    </div>
</div>

