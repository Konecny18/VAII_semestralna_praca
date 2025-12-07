<?php

/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var \App\Models\Training|null $training */

$training = $training ?? null;
$isEdit = !empty($training?->getId());
$denValue = $training?->getDen() ?? '';
$casStart = $training?->getCasZaciatku() ?? '';
$casEnd = $training?->getCasKonca() ?? '';
$popis = $training?->getPopis() ?? '';

// ensure time inputs are in HH:MM format for <input type="time">
$casStartInput = $casStart !== null && $casStart !== '' ? substr($casStart, 0, 5) : '';
$casEndInput = $casEnd !== null && $casEnd !== '' ? substr($casEnd, 0, 5) : '';

$days = [
    'Pon' => 'Pondelok',
    'Uto' => 'Utorok',
    'Str' => 'Streda',
    'Stv' => 'Štvrtok',
    'Pia' => 'Piatok',
    'Sob' => 'Sobota',
    'Ned' => 'Nedeľa'
];
?>

<?php if (!empty($formErrors ?? [])): ?>
    <?php foreach ($formErrors as $error): ?>
        <div class="alert alert-danger" role="alert"><?= $error ?></div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-8">
<!--            <form  method="post" action="--><?php //= $link->url('training.save') ?><!--">-->
            <form method="post" action="<?= $link->url('training.save') ?>" data-ajax-form>

                <div data-ajax-feedback class="alert d-none mt-2"></div>

<!--                <input type="hidden" name="id" value="--><?php //= $isEdit ? $training?->getId() : '' ?><!--">-->
                <input type="hidden" name="id" value="<?= $isEdit ? $training?->getId() : '' ?>">

                <div class="mb-3">
                    <label for="den" class="form-label fw-bold">Deň</label>
                    <select id="den" name="den" class="form-select" required>
                        <option value="" disabled selected>-- Vyber deň --</option>
                        <?php foreach ($days as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $k === $denValue ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cas_zaciatku" class="form-label fw-bold">Čas začiatku</label>
                        <input type="time" id="cas_zaciatku" name="cas_zaciatku" class="form-control" value="<?= htmlspecialchars($casStartInput) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cas_konca" class="form-label fw-bold">Čas konca</label>
                        <input type="time" id="cas_konca" name="cas_konca" class="form-control" value="<?= htmlspecialchars($casEndInput) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="popis" class="form-label fw-bold">Popis tréningu</label>
                    <textarea id="popis" name="popis" class="form-control" rows="3" required maxlength="100"><?= htmlspecialchars($popis) ?></textarea>
                </div>

                <div class="d-flex gap-2">
<!--                    <button type="submit" class="btn btn-primary">Uložiť</button>-->
                    <button type="submit" class="btn btn-primary">Uložiť</button>
                    <a href="<?= $link->url('training.index') ?>" class="btn btn-secondary">Zrušiť</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="<?= $link->asset('js/form-ajax.js') ?>" defer></script>
