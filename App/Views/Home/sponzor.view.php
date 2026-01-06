<?php
/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="container my-5 sponzor-page">
    <div class="text-center mb-5 page-header">
        <h1 class="display-4 fw-bold text-uppercase">Naši Partneri</h1>
        <div class="header-line bg-primary mx-auto"></div>
        <p class="lead mt-3 text-secondary">Vďaka týmto organizáciám môžeme rásť a dosahovať lepšie výsledky.</p>
    </div>

    <div class="mb-5">
        <h3 class="text-center mb-4 text-muted">Generálni partneri</h3>
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 border-0 text-center p-4">
                    <div class="logo-wrapper-large d-flex align-items-center justify-content-center mb-3">
                        <img src="<?= $link->asset('images/viagrande_logo.jpg') ?>" class="img-fluid" alt="Generálny sponzor">
                    </div>
                    <div class="card-body">
                        <h4 class="card-title fw-bold">Via Grande</h4>
                        <p class="card-text text-muted">Dlhoročný partner, ktorý stojí pri nás od vzniku nášho klubu.</p>
                        <a href="https://www.viagrande.sk/" class="btn btn-primary px-4">Web sponzora</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h3 class="text-center mb-4 text-muted">Partneri</h3>
        <div class="row g-4">
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 text-center p-3">
                    <div class="logo-wrapper d-flex align-items-center justify-content-center">
                        <img src="<?= $link->asset('images/fatraski_logo.jpg') ?>" class="img-fluid" alt="Logo 2">
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold">Firma ABC</h5>
                        <p class="small text-muted">Podpora športového vybavenia a dresov.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 text-center p-3">
                    <div class="logo-wrapper d-flex align-items-center justify-content-center">
                        <img src="<?= $link->asset('images/ecco_logo.png') ?>" class="img-fluid" alt="Logo 3">
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold">Mesto / Obec</h5>
                        <p class="small text-muted">Podpora z grantového systému pre šport.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 text-center p-3">
                    <div class="logo-wrapper d-flex align-items-center justify-content-center">
                        <img src="<?= $link->asset('images/aluprint_logo.jpg') ?>" class="img-fluid" alt="Logo 4">
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold">Lokálna Pekáreň</h5>
                        <p class="small text-muted">Zabezpečenie občerstvenia na našich akciách.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-primary bg-light cta-card">
        <div class="card-body text-center py-5">
            <h2 class="fw-bold">Chcete sa k nim pridať?</h2>
            <p class="lead mb-4">Budujeme silnú komunitu a radi privítame nových partnerov do našej rodiny.</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="mailto:vas@email.sk" class="btn btn-primary btn-lg px-5 shadow">Napíšte nám</a>
                <a href="<?= $link->url('home.index') ?>" class="btn btn-outline-secondary btn-lg px-5">Viac o nás</a>
            </div>
        </div>
    </div>
</div>