<?php

/** @var string $contentHTML */
/** @var AppUser|null $user */
/** @var LinkGenerator $link */

// The framework injects a `user` helper (AppUser) and `link` into all views via ViewResponse.
// Do not overwrite `$user` here; if it's not injected, keep it null.
use Framework\Auth\AppUser;
use Framework\Support\LinkGenerator;

if (!isset($user)) {
    $user = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Ensure responsive behavior on mobile devices and DevTools -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= App\Configuration::APP_NAME ?></title>
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $link->asset('images/tat_logo.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $link->asset('images/tat_logo.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $link->asset('images/tat_logo.png') ?>">
    <link rel="manifest" href="<?= $link->asset('images/site.webmanifest') ?>">
    <link rel="shortcut icon" href="<?= $link->asset('images/favicon.ico') ?>">

    <!-- Bootstrap (match index.html) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $link->asset('js/delete-confirmation.js') ?>"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Existing app assets -->
    <link rel="stylesheet" href="<?= $link->asset('css/home.css') ?>?v=3">
    <link rel="stylesheet" href="<?= $link->asset('css/rootStyle.css') ?>?v=3">
    <!-- Add gallery specific CSS (card-img) -->
    <link rel="stylesheet" href="<?= $link->asset('css/galeria.css') ?>?v=1">
    <!-- Contact page specific CSS -->
    <link rel="stylesheet" href="<?= $link->asset('css/contact.css') ?>?v=1">
    <!-- Klub (flip card) styles -->
    <link rel="stylesheet" href="<?= $link->asset('css/klub.css') ?>?v=1">
    <!-- Include events stylesheet -->
    <link rel="stylesheet" href="<?= $link->asset('css/events.css') ?>">
    <!-- Include sponzor stylesheet -->
    <link rel="stylesheet" href="<?= $link->asset('css/sponzor.css') ?>">

    <!-- Include record stylesheet -->
    <link rel="stylesheet" href="<?= $link->asset('css/record.css') ?>?v=1">

</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $link->url('home.index') ?>">
            <img class="logo" src="<?= $link->asset('images/tat_logo.png') ?>" title="<?= App\Configuration::APP_NAME ?> " alt="logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="<?= $link->url('home.index') ?>">Úvod</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $link->url('album.index') ?>">Galéria</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $link->url('home.klub') ?>">Klub TAT</a>
                </li>
                <?php if ($user && method_exists($user, 'isLoggedIn') && $user->isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $link->url('record.index') ?>">Výkony</a>
                </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Informácie
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item " href="<?= $link->url('home.contact')?>">Kontakt</a></li>
                        <li><a class="dropdown-item " href="<?= $link->url('training.index')?>">Rozvrh tréningov</a></li>
                        <li><a class="dropdown-item" href="<?= $link->url('event.index')?>">Podujatia</a></li>
                        <li><a class="dropdown-item" href="<?= $link->url('home.sponzor')?>">Partneri</a></li>
                    </ul>
                </li>
            </ul>

            <?php if ($user && method_exists($user, 'isLoggedIn') && $user->isLoggedIn()) { ?>
                <span class="navbar-text">Logged in user: <b><?= $user->getName() ?></b></span>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $link->url('auth.logout') ?>">Log out</a>
                    </li>
                </ul>
            <?php } else { ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= App\Configuration::LOGIN_URL ?>">Prihlásiť sa</a></li>
                            <li><a class="dropdown-item" href="<?= $link->url('auth.register') ?>">Registrácia</a></li>
                        </ul>
                    </li>
                </ul>
            <?php } ?>

        </div>
    </div>
</nav>


<main class="content-stranky-kontakt">
<!--    ked dam iba container bez fluid tak vsetky stranky budu odsadene od krajov-->
    <div class="container mt-3">
        <div class="web-content">
            <?= $contentHTML ?>
        </div>
    </div>
</main>

<footer class="site-footer bg-dark py-4 mt-auto"> <div class="container">
        <div class="row align-items-center">

            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <p class="mb-0 text-white">© <?= date('Y') ?> T.A.T. Martin. All rights reserved.</p>
            </div>

            <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end">
                <p class="mb-0 me-2 text-white">Sleduj nás na našich sociálnych sieťach:</p>
                <a href="https://www.facebook.com/..." class="me-3 fs-4">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="https://www.instagram.com/tatriatlon/" class="fs-4">
                    <i class="bi bi-instagram"></i>
                </a>
            </div>

        </div>
    </div>
</footer>

</body>
</html>
