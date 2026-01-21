<?php

/** @var string $contentHTML */
/** @var IAuthenticator $auth */
/** @var LinkGenerator $link */

use Framework\Core\IAuthenticator;
use Framework\Support\LinkGenerator;

?>

<!DOCTYPE html>
<html lang="sk">
<head>

    <meta charset="UTF-8">

    <!-- Responzívne zobrazenie pre mobil a desktop -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= App\Configuration::APP_NAME ?></title>

    <!-- Favicons: rovnaké ako v hlavnom layout pre konzistenciu -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $link->asset('images/tat_logo.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $link->asset('images/tat_logo.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $link->asset('images/tat_logo.png') ?>">
    <link rel="manifest" href="<?= $link->asset('images/site.webmanifest') ?>">
    <link rel="shortcut icon" href="<?= $link->asset('images/favicon.ico') ?>">

    <!-- Bootstrap CSS + JS bundle pre komponenty a grid systém -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>

    <!-- Bootstrap ikony -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Štýly špecifické pre login / logout -->
    <link rel="stylesheet" href="<?= $link->asset('css/logout.css') ?>?v=1">
    <link rel="stylesheet" href="<?= $link->asset('css/login.css') ?>?v=1">

</head>
<body>

<!-- Hlavný kontajner rozťahujúci sa na celú šírku (container-fluid) -->
<div class="container-fluid position-relative mt-0">
    <!-- Button fixed to the top-left corner -->
    <div class="auth-back-btn">
        <a href="<?= $link->url('home.index') ?>" class="btn btn-secondary" role="button" aria-label="Hlavná stránka">
            <i class="bi bi-house" aria-hidden="true"></i>
            <span class="visually-hidden">Hlavná stránka</span>
        </a>
    </div>

    <!-- Hlavný obsah stránky (formulár login/logout) -->
    <div class="web-content auth-content">
        <?= $contentHTML ?>
    </div>

</div>
</body>
</html>
