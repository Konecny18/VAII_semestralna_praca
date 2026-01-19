<?php
/** @var LinkGenerator $link */
use Framework\Support\LinkGenerator;
?>

<!-- HERO SEKCIa -->
<section class="hlavna-sekcia text-white text-center">

<!--        <img src="--><?php //= $link->asset('images/tat_logo.png') ?><!--" class="pozadie" alt="logo">-->
        <h1 class="display-4 fw-bold">T.A.T. Martin</h1>
        <p class="lead mt-3">
            Všeobecná športová príprava pre všetkých, zameranie na triatlon
        </p>
        <a href="<?= $link->url('home.contact') ?>" class="btn btn-outline-light btn-lg mt-4">
            Pridaj sa k nám
        </a>

</section>

<!-- O NÁS -->
<section class="my-5">
    <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
            <h2>O našom klube</h2>
            <p>
                T.A.T. Martin je klub zameraný na rozvoj atletiky a plávania.
                Naši členovia sú profesionáli aj amatéri, ktorí sa učia nové techniky
                a zlepšujú kondíciu.
            </p>
            <ul class="list-unstyled mt-3">
                <li>🏃 Atletické tréningy</li>
                <li>🏊 Plavecké tréningy</li>
                <li>👶 Mládež aj dospelí</li>
            </ul>
        </div>

        <div class="col-md-6 text-center">
            <img src="<?= $link->asset('images/tat_logo.png') ?>"
                 class="img-fluid rounded shadow"
                 alt="Tréning klubu T.A.T. Martin">
        </div>
    </div>
</section>

<!-- TRÉNERI -->
<section class="bg-light py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Náš trénerský tím</h2>
        <p class="text-muted lead">
            Skúsení tréneri s individuálnym prístupom ku každému športovcovi
        </p>
    </div>

    <div class="row g-4 justify-content-center">

        <!-- Tréner 1 -->
        <div class="col-12 col-sm-6 col-md-6 col-lg-4 mb-4 d-flex">
            <div class="otocenie-karty h-100 w-100">
                <div class="otocenie-karty-vnutro">
                    <div class="otacacia-karta-predok">
                        <h5 class="card-title">Hlavný tréner</h5>
                        <img src="<?= $link->asset('images/trenery/daniel.png') ?>"
                             class="card-img-top"
                             alt="Daniel Konečný">
                    </div>
                    <div class="otacacia-karta-zadok">
                        <div class="card-body d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Daniel Konečný</h5>
                            <p class="card-text">
                                Hlavný tréner atletiky s viac než 10-ročnou praxou,
                                špecialista na techniku behu a kondičné tréningy.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tréner 2 -->
        <div class="col-12 col-sm-6 col-md-6 col-lg-4 mb-4 d-flex">
            <div class="otocenie-karty h-100 w-100">
                <div class="otocenie-karty-vnutro">
                    <div class="otacacia-karta-predok">
                        <h5 class="card-title">Plavecký tréner</h5>
                        <img src="<?= $link->asset('images/trenery/gustav.png') ?>"
                             class="card-img-top"
                             alt="Gustav Konečný">
                    </div>
                    <div class="otacacia-karta-zadok">
                        <div class="card-body d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Gustav Konečný</h5>
                            <p class="card-text">
                                Tréner plávania, odborník na techniku plávania
                                a kondičný rozvoj športovcov.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tréner 3 -->
        <div class="col-12 col-sm-6 col-md-6 col-lg-4 mb-4 d-flex">
            <div class="otocenie-karty h-100 w-100">
                <div class="otocenie-karty-vnutro">
                    <div class="otacacia-karta-predok">
                        <h5 class="card-title">Pomocný tréner</h5>
                        <img src="<?= $link->asset('images/trenery/damianTrener.jpg') ?>"
                             class="card-img-top"
                             alt="Damián Konečný">
                    </div>
                    <div class="otacacia-karta-zadok">
                        <div class="card-body d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Damián Konečný</h5>
                            <p class="card-text">
                                Tréner zameraný na atletiku a kondičné tréningy mládeže,
                                skúsenosti s rôznymi disciplínami.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tréner 4 -->
        <div class="col-12 col-sm-6 col-md-6 col-lg-4 mb-4 d-flex">
            <div class="otocenie-karty h-100 w-100">
                <div class="otocenie-karty-vnutro">
                    <div class="otacacia-karta-predok">
                        <h5 class="card-title">Pomocný tréner</h5>
                        <img src="<?= $link->asset('images/trenery/kristofTrener.jpg') ?>"
                             class="card-img-top"
                             alt="Krištof Konečný">
                    </div>
                    <div class="otacacia-karta-zadok">
                        <div class="card-body d-flex flex-column justify-content-center h-100">
                            <h5 class="card-title">Krištof Konečný</h5>
                            <p class="card-text">
                                Tréner s dôrazom na atletiku a plávanie,
                                špecialista na techniku a motiváciu športovcov.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</section>

