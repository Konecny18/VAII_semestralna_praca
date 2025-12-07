<?php

// ...existing code...
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var \App\Models\Record $record */
/** @var array $errors */

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
            <form method="post" action="<?= $link->url('record.save') ?>">

                <input type="hidden" name="id" value="<?= @$record?->getId() ?>">

                <label for="nazov_discipliny" class="form-label fw-bold">Názov disciplíny</label>
                <div class="input-group has-validation mb-3">
                    <input type="text" class="form-control" name="nazov_discipliny" id="nazov_discipliny" required minlength="2"
                           value="<?= htmlspecialchars(@$record?->getNazovDiscipliny() ?? '') ?>">
                </div>

                <label for="dosiahnuty_vykon" class="form-label fw-bold">Dosiahnutý výkon</label>
                <div class="input-group has-validation mb-3">
                    <input type="text" class="form-control" name="dosiahnuty_vykon" id="dosiahnuty_vykon"
                           value="<?= htmlspecialchars(@$record?->getDosiahnutyVykon() ?? '') ?>">
                </div>

                <label for="datum_vykonu" class="form-label fw-bold">Dátum výkonu</label>
                <div class="input-group has-validation mb-3">
                    <input type="date" class="form-control" name="datum_vykonu" id="datum_vykonu"
                           value="<?= @($record?->getDatumVykonu() ? date('Y-m-d', strtotime($record->getDatumVykonu())) : '') ?>">
                </div>

                <label for="poznamka" class="form-label fw-bold">Poznámka</label>
                <div class="input-group has-validation mb-3">
                    <textarea class="form-control" name="poznamka" id="poznamka" rows="4">
                        <?= htmlspecialchars(@$record?->getPoznamka() ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Uložiť</button>
            </form>
        </div>
    </div>
</div>

