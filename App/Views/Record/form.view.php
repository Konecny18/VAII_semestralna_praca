<?php

// ...existing code...
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var Record $record */
/** @var array $errors */

// Zlúčime errors a formErrors, ak by náhodou controller poslal obe premenné
use App\Models\Record;

$allErrors = array_merge($errors ?? [], $formErrors ?? []);

?>

<form method="post" action="<?= $link->url('record.save') ?>">

    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

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
        <textarea class="form-control" name="poznamka" id="poznamka" rows="4"><?= htmlspecialchars(@$record?->getPoznamka() ?? '') ?></textarea>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= $link->url('record.index') ?>" class="btn btn-secondary">Späť</a>
        <button type="submit" class="btn btn-primary">Uložiť</button>
    </div>
</form>


