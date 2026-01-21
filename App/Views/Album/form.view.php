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

<!--
Formulár na vytvorenie alebo úpravu albumu
method="post" – odoslanie dát na server
enctype="multipart/form-data" – povinné pre upload súborov
-->
<form method="post" action="<?= $link->url('album.save') ?>" enctype="multipart/form-data">

    <!--
    CSRF token – ochrana proti CSRF útokom
    -->
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!--
    Skryté ID albumu
    Ak existuje, ide o editáciu, inak o vytvorenie nového albumu
    -->
    <input type="hidden" name="id" value="<?= @$album?->getId() ?>">

    <!--
    form-label – Bootstrap štýl pre label
    fw-bold – tučný text
    -->
    <label for="picture" class="form-label fw-bold">Súbor obrázka</label>

    <!--
    input-group – Bootstrap skupina inputov
    mb-3 – spodný margin
    has-validation – pripravené pre validačné hlásenia
    -->
    <div class="input-group mb-3 has-validation">
        <?php
        // Zistenie, či ide o editáciu existujúceho albumu
        $isEdit = !empty(@$album?->getId()); ?>

        <!--
        type="file" – upload súboru
        form-control – Bootstrap štýl
        required – povinné len pri vytváraní albumu
        accept – povolené typy obrázkov
        -->
        <input type="file" class="form-control " name="picture" id="picture" <?= $isEdit ? '' : 'required' ?> accept="image/png, image/jpeg">
    </div>
    <?php if (@$album?->getPicture() != ""): ?>

        <!--
            Zobrazenie názvu pôvodného súboru pri editácii albumu
            htmlspecialchars – ochrana proti XSS
            -->
        <div class="text-muted mb-3">Pôvodný súbor: <?= htmlspecialchars(substr($album->getPicture(), strrpos($album->getPicture(), '_') + 1)) ?></div>
    <?php endif; ?>

    <label for="text" class="form-label fw-bold">Názov albumu</label>

    <!--
    input-group – skupina formulárových prvkov
    has-validation – priestor pre validáciu
    mb-3 – spodný margin
    -->
    <div class="input-group has-validation mb-3 ">

        <!--
        textarea – viacriadkový textový vstup
        required – povinné pole
        minlength / maxlength – obmedzenie dĺžky textu
        -->
        <textarea class="form-control" aria-label="With textarea" name="text" id="text"
                  required minlength="3" maxlength="255"><?= htmlspecialchars(@$album?->getText() ?? '') ?></textarea>
    </div>
    <!--
    d-flex – flexbox rozloženie
    justify-content-between – tlačidlá na opačných stranách
    align-items-center – vertikálne zarovnanie
    mb-3 – spodný margin
    -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= $link->url('album.index') ?>" class="btn btn-secondary">Späť</a>
        <button type="submit" class="btn btn-primary">Uložiť</button>
    </div>

</form>
