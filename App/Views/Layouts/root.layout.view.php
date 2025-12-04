<?php

/** @var string $contentHTML */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var \Framework\Support\LinkGenerator $link */

// Safely obtain current user (fallback to null) to avoid undefined variable in views
$user = null;
if (isset($auth)) {
    try {
        $user = $auth->getUser();
    } catch (\Throwable $e) {
        $user = null;
    }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Existing app assets -->
    <link rel="stylesheet" href="<?= $link->asset('css/home.css') ?>?v=3">
    <link rel="stylesheet" href="<?= $link->asset('css/styl.css') ?>?v=3">
    <!-- Add gallery specific CSS (card-img) -->
    <link rel="stylesheet" href="<?= $link->asset('css/galeria.css') ?>?v=1">
    <!-- Contact page specific CSS -->
    <link rel="stylesheet" href="<?= $link->asset('css/contact.css') ?>?v=1">
    <script src="<?= $link->asset('js/script.js') ?>"></script>
</head>
<body>

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
                    <a class="nav-link" href="#">Klub TAT</a>
                </li>



                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Informácie
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item " href="<?= $link->url('home.contact')?>">Kontakt</a></li>
                        <li><a class="dropdown-item" href="#">Podujatia</a></li>
                        <li><a class="dropdown-item" href="#">Partneri</a></li>
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
<div class="container-fluid mt-3">
    <div class="web-content">
        <?= $contentHTML ?>
    </div>
</div>
</body>
</html>
