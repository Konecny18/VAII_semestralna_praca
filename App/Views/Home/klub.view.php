<?php

/** @var LinkGenerator $link */

use Framework\Support\LinkGenerator;

?>


<div class="container my-5">

    <!-- Sekcia: Info o klube -->
    <div class="row mb-5">
        <div class="col">
            <h2>O našom klube</h2>
            <p>
                T.A.T. Martin je klub zameraný na rozvoj atletiky a plávania. Naši členovia sú profesionáli aj amatéri, ktorí sa učia nové techniky a zlepšujú kondíciu. Klub organizuje tréningy, súťaže a priateľské stretnutia pre všetky vekové kategórie.
            </p>
        </div>
    </div>

    <!-- Sekcia: Tréneri -->
    <div class="row">
        <div class="col-12">
            <h2>Naši tréneri</h2>
        </div>


<!--         Tréner 1 -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="flip-card h-100">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h5 class="card-title">Hlavný tréner</h5>
                        <img src="<?= $link->asset('images/daniel.png') ?>" class="card-img-top" alt="Daniel Konečný">
                        <h5 class="card-title">Daniel Konečný</h5>
                    </div>
                    <div class="flip-card-back">
                        <div class="card-body text-center d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Daniel Konečný</h5>
                            <p class="card-text">Hlavný tréner atletiky s viac než 10-ročnou praxou, špecialista na techniku behu a kondičné tréningy.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tréner 2 -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="flip-card h-100">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h5 class="card-title">Plavecký tréner</h5>
                        <img src="<?= $link->asset('images/gustav.png') ?>" class="card-img-top" alt="Gustav Konečný">
                        <h5 class="card-title">Gustav Konečný</h5>
                    </div>
                    <div class="flip-card-back">
                        <div class="card-body text-center d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Gustav Konečný</h5>
                            <p class="card-text">Tréner plávania, odborník na techniku plávania a kondičný rozvoj športovcov.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tréner 3 -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="flip-card h-100">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h5 class="card-title">Pomocný tréner</h5>
                        <img src="<?= $link->asset('images/damianTrener.jpg') ?>" class="card-img-top" alt="Damián Konečný">
                        <h5 class="card-title">Damián Konečný</h5>
                    </div>
                    <div class="flip-card-back">
                        <div class="card-body text-center d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Damián Konečný</h5>
                            <p class="card-text">Tréner zameraný na atletiku a kondičné tréningy mládeže, skúsenosti s rôznymi disciplínami.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tréner 4 -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="flip-card h-100">
                <div class="flip-card-inner">
                    <div class="flip-card-front">

                        <h5 class="card-title">Pomocný tréner</h5>
                        <img src="<?= $link->asset('images/kristofTrener.jpg') ?>" class="card-img-top" alt="Krištof Konečný">
                        <h5 class="card-title">Krištof Konečný</h5>
                    </div>
                    <div class="flip-card-back">
                        <div class="card-body text-center d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Krištof Konečný</h5>
                            <p class="card-text">Tréner s dôrazom na atletiku a plávanie, špecialista na techniku a motiváciu športovcov.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

