<?php

/** @var Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var \App\Models\Event $event */

use App\Models\Event;

$event = $event ?? new Event();
$errors = $errors ?? [];

?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-10">
            <form method="post" action="<?= $link->url('event.save') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>

                <input type="hidden" name="id" value="<?= htmlspecialchars($event->getId() ?? '') ?>">

                <div class="mb-3">
                    <label for="nazov" class="form-label fw-bold">Názov podujatia</label>
                    <input id="nazov" name="nazov" class="form-control" required value="<?= htmlspecialchars($event->getNazov() ?? '') ?>">
                    <div class="invalid-feedback">Názov podujatia je povinný.</div>
                </div>

                <div class="mb-3">
                    <label for="datum_podujatia" class="form-label fw-bold">Dátum podujatia</label>
                    <input id="datum_podujatia" name="datum_podujatia" type="date" class="form-control" required value="<?= htmlspecialchars($event->getDatumPodujatia() ?? '') ?>">
                    <div class="invalid-feedback">Datum podujatia je povinný.</div>
                </div>

                <div class="mb-3">
                    <label for="popis" class="form-label fw-bold">Popis</label>
                    <textarea id="popis" name="popis" rows="6" class="form-control" required><?= htmlspecialchars($event->getPopis() ?? '') ?></textarea>
                    <div class="invalid-feedback">Popis podujatia je povinný.</div>
                </div>

                <div class="mb-3">
                    <label for="link_prihlasovanie" class="form-label fw-bold">Link na prihlásenie</label>
                    <input id="link_prihlasovanie" name="link_prihlasovanie" class="form-control" value="<?= htmlspecialchars($event->getLinkPrihlasovanie() ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="plagat" class="form-label fw-bold">Plagát (JPG/PNG, max 2 MB)</label>
                    <input id="plagat" name="plagat" type="file" accept="image/jpeg,image/png" class="form-control">
                    <div class="invalid-feedback">Plagát je povinný (max 2 MB).</div>
                    <?php if ($event->getPlagat()): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($link->asset($event->getPlagat()), ENT_QUOTES, 'UTF-8') ?>" alt="plagat" style="max-width:220px;" class="img-thumbnail">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="dokument_propozicie" class="form-label fw-bold">Dokument propozície (PDF, max 2 MB)</label>
                    <input id="dokument_propozicie" name="dokument_propozicie" type="file" accept="application/pdf" class="form-control">
                    <div class="invalid-feedback">Propozície musia mať veľkosť max 2 MB.</div>
                    <?php if ($event->getDokumentPropozicie()): ?>
                        <div class="mt-2">
                            <a href="<?= htmlspecialchars($link->asset($event->getDokumentPropozicie()), ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-primary">Zobraziť existujúci dokument</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?= $link->url('event.index') ?>" class="btn btn-secondary">Späť</a>
                    <button type="submit" class="btn btn-primary">Uložiť podujatie</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="<?= $link->asset('js/event-form.js') ?>"></script>
