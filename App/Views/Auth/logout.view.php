<?php
/** @var LinkGenerator $link */
/** @var View $view */

use Framework\Support\LinkGenerator;
use Framework\Support\View;

$view->setLayout('auth');
?>



<div class="odhlasenie-obal">
    <div class="col-sm-10 col-md-8 col-lg-5">
        <div class="alert alert-info shadow-lg p-5 text-center border-0 odhlasenie-karta" role="alert">

            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-4 ikona-kruh">
                <i class="bi bi-check-lg text-success"></i>
            </div>

            <h3 class="alert-heading fw-bold text-dark">Dovidenia nabudúce!</h3>
            <p class="text-muted fs-5">Boli ste úspešne odhlásený.</p>

            <hr class="oddelovac-ciara">

            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <a href="<?= App\Configuration::LOGIN_URL ?>" class="btn btn-primary w-100 py-2 shadow-sm">
                        <i class="bi bi-person-fill me-1"></i> Prihlásiť sa
                    </a>
                </div>
                <div class="col-12 col-sm-6">
                    <a href="<?= $link->url("home.index") ?>" class="btn btn-outline-dark w-100 py-2">
                        <i class="bi bi-house-door me-1"></i> Domov
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
