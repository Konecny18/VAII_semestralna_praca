<?php
/** @var LinkGenerator $link */
use Framework\Support\LinkGenerator;
?>

<div class="container-fluid px-0 obal-carousel">
    <div id="carouselHome" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselHome" data-bs-slide-to="0" class="active" aria-current="true"></button>
            <button type="button" data-bs-target="#carouselHome" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#carouselHome" data-bs-slide-to="2"></button>
        </div>

        <div class="carousel-inner">
            <div class="carousel-item active" data-bs-interval="5000">
                <div class="overlay-cez-carousel"></div>
                <img src="<?= $link->asset('images/team.jpg') ?>" class="d-block w-100 fotka-carousel" alt="Tím">
                <div class="carousel-caption text-carousel">
                    <h1 class="display-5 fw-bold text-uppercase">Spoločne za víťazstvom</h1>
<!--                    lead mierne zvacsi pismo a odlahci jeho hrubku, fs (font size) velkost pisma-->
                    <p class="lead fs-4 mb-4">Sme viac než len klub. Sme rodina, ktorá drží spolu.</p>
                    <a href="<?= $link->url('home.klub') ?>" class="btn btn-primary btn-lg px-5 shadow-lg">Spoznajte nás</a>
                </div>
            </div>

            <div class="carousel-item" data-bs-interval="5000">
                <div class="overlay-cez-carousel"></div>
                <img src="<?= $link->asset('images/galavecer.jpg') ?>" class="d-block w-100 fotka-carousel" alt="Logo">
                <div class="carousel-caption text-carousel">
                    <h1 class="display-5 fw-bold text-uppercase">Naša Tradícia</h1>
                    <p class="lead fs-4 mb-4">Budujeme meno klubu už od roku 2000.</p>
                </div>
            </div>

            <div class="carousel-item" data-bs-interval="5000">
                <div class="overlay-cez-carousel"></div>
                <img src="<?= $link->asset('images/vsetci.jpg') ?>" class="d-block w-100 fotka-carousel" alt="Všetci">
                <div class="carousel-caption text-carousel">
                    <h1 class="display-5 fw-bold text-uppercase">Pridaj sa k nám</h1>
                    <p class="lead fs-4 mb-4">Hľadáme nové talenty. Tvoja cesta začína tu.</p>
                    <a href="<?= $link->url('home.contact') ?>" class="btn btn-light btn-lg px-5 shadow-lg">Kontaktuj nás</a>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#carouselHome" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Predchádzajúci</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselHome" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Nasledujúci</span>
        </button>
    </div>
</div>


<div class="container moje-karty">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card shadow-lg p-4 karta-info">
                <h2 class="fw-bold text-primary">60+</h2>
                <p class="text-muted mb-0">Aktívnych hráčov</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-lg p-4 karta-info">
                <h2 class="fw-bold text-primary">2000</h2>
                <p class="text-muted mb-0">Rok založenia</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-lg p-4 karta-info">
                <h2 class="fw-bold text-primary">4</h2>
                <p class="text-muted mb-0">Profi tréneri</p>
            </div>
        </div>
    </div>
</div>