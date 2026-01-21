<?php
/** @var LinkGenerator $link */
/** @var View $view */

use Framework\Support\LinkGenerator;
use Framework\Support\View;

$view->setLayout('auth');
?>

<!--
Hlavný wrapper pre odhlásenie
-->
<div class="odhlasenie-obal">
    <!--
    col-sm-10 col-md-8 col-lg-5 – responzívny stĺpec
    menší na desktop, širší na mobile
    -->
    <div class="col-sm-10 col-md-8 col-lg-5">
        <!--
        alert – Bootstrap komponent pre oznámenie
        alert-info – modrá farba pre informácie
        shadow-lg – veľký tieň
        p-5 – padding
        text-center – centrovanie textu
        border-0 – bez okraja
        odhlasenie-karta – vlastná CSS trieda
        role="alert" – prístupnosť
        -->
        <div class="alert alert-info shadow-lg p-5 text-center border-0 odhlasenie-karta" role="alert">

            <!--
            Ikona v kruhu
            d-inline-flex – inline flexbox
            align-items-center – vertikálne centrovanie
            justify-content-center – horizontálne centrovanie
            bg-light – svetlé pozadie
            rounded-circle – kruh
            mb-4 – margin bottom
            ikona-kruh – vlastná CSS trieda pre veľkosť kruhu
            -->
            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-4 ikona-kruh">
                <i class="bi bi-check-lg text-success"></i>
            </div>

            <h3 class="alert-heading fw-bold text-dark">Dovidenia nabudúce!</h3>
            <p class="text-muted fs-5">Boli ste úspešne odhlásený.</p>

            <!--
           Horizontálna čiara na oddelenie sekcií
           vlastná CSS trieda oddelovac-ciara
           -->
            <hr class="oddelovac-ciara">

            <!--
            Bootstrap row pre tlačidlá
            g-3 – medzera medzi stĺpcami
            -->
            <div class="row g-3">
                <!--
               Stĺpec pre tlačidlo Prihlásiť sa
               col-12 – full width na mobile
               col-sm-6 – polovičná šírka na desktop
               -->
                <div class="col-12 col-sm-6">
                    <a href="<?= App\Configuration::LOGIN_URL ?>" class="btn btn-primary w-100 py-2 shadow-sm">
                        <i class="bi bi-person-fill me-1"></i> Prihlásiť sa
                    </a>
                </div>
                <!--
                Stĺpec pre tlačidlo Domov
                col-12 – full width na mobile
                col-sm-6 – polovičná šírka na desktop
                btn-outline-dark – obrysové tlačidlo tmavej farby
                w-100 – šírka 100 %
                py-2 – vertikálny padding
                -->
                <div class="col-12 col-sm-6">
                    <a href="<?= $link->url("home.index") ?>" class="btn btn-outline-dark w-100 py-2">
                        <i class="bi bi-house-door me-1"></i> Domov
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
