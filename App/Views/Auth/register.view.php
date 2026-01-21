<?php
/** @var array $errors */
/** @var array $old */
/** @var LinkGenerator $link */
/** @var View $view */

use Framework\Support\LinkGenerator;
use Framework\Support\View;

// Nastavenie layoutu pre auth stránky (prihlásenie / registrácia)
$view->setLayout('auth');
?>

<!--
Bootstrap row
align-items-center – vertikálne centrovanie obsahu
min-vh-100 – výška 100% viewportu
py-5 – padding top/bottom
-->
<div class="row align-items-center min-vh-100 py-5">
    <!--
    Responzívny stĺpec
    col-sm-10 col-md-8 col-lg-6 col-xl-5 – postupne menšia šírka na väčších obrazovkách
    mx-auto – horizontálne centrovanie
    -->
    <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto"><!--
        card – Bootstrap karta
        prihlasenie-karta – vlastná CSS pre štýl formulára
        shadow-lg – väčší tieň pre vizuálny efekt
        -->
        <div class="card prihlasenie-karta shadow-lg">
            <!--
            card-body – obsah karty
            p-4 p-md-5 – padding pre malé a väčšie obrazovky
            -->
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <!--
                    Ikona formulára
                    prihlasenie-icona-obalovac – vlastná trieda
                    mb-3 – spodný margin
                    -->
                    <div class="prihlasenie-icona-obalovac mb-3">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Registrácia</h3>
                    <p class="text-muted small">Staňte sa členom T.A.T. Martin</p>
                </div>

                <!--
                Zobrazenie chýb, ak existujú
                alert-danger – červený alert
                border-0 – bez okraja
                shadow-sm – jemný tieň
                text-start – zarovnanie textu doľava
                -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4 text-start">
                        <ul class="mb-0 small">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!--
                Formulár registrácie
                form-signin – vlastná CSS trieda
                method="post" – odoslanie dát
                action – URL spracovania registrácie
                -->
                <form class="form-signin" method="post" action="<?= $link->url('register') ?>">

                    <!--
                    Meno a Priezvisko v jednom row
                    col-md-6 – stĺpce po polovici šírky na desktop
                    mb-3 – spodný margin
                    -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="meno" class="form-label fw-semibold">Meno</label>
                            <div class="input-group prihlasenie-input-skupina">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input name="meno" type="text" id="meno" class="form-control border-start-0 bg-light" placeholder="Meno" required value="<?= htmlspecialchars($old['meno'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="priezvisko" class="form-label fw-semibold">Priezvisko</label>
                            <div class="input-group prihlasenie-input-skupina">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input name="priezvisko" type="text" id="priezvisko" class="form-control border-start-0 bg-light" placeholder="Priezvisko" required value="<?= htmlspecialchars($old['priezvisko'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                            <input name="email" type="email" id="email" class="form-control border-start-0 bg-light" placeholder="Email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                        </div>
                        <div id="email-feedback" class="small mt-1 px-2"></div>
                    </div>

                    <!-- Heslo -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Heslo</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                            <input name="password" type="password" id="password" class="form-control border-start-0 bg-light" placeholder="Heslo" required>
                            <!-- Toggle password viditeľnosti -->
                            <button class="btn btn-outline-light border-start-0 text-muted toggle-password" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted px-1" style="font-size: 0.75rem;">Min. 8 znakov (A, a, 1, @).</small>
                    </div>

                    <!-- Potvrdenie hesla -->
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label fw-semibold">Potvrdenie hesla</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-check text-muted"></i></span>
                            <input name="password_confirm" type="password" id="password_confirm" class="form-control border-start-0 bg-light" placeholder="Zopakujte heslo" required>
                            <button class="btn btn-outline-light border-start-0 text-muted toggle-password" type="button" data-target="password_confirm">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button class="btn btn-primary tlacidlo-prihlasenie w-100 py-3 fw-bold shadow-sm" type="submit">
                        Vytvoriť účet <i class="bi bi-check-circle ms-2"></i>
                    </button>
                </form>

                <!-- Link na login, ak už má účet -->
                <div class="mt-4 text-center">
                    <p class="text-muted small mb-0">Už máte účet?</p>
                    <a href="<?= $link->url('login') ?>" class="text-primary fw-bold text-decoration-none">Prihlásiť sa teraz</a>
                </div>

            </div>
        </div>
    </div>
</div>

<!--
JS pre AJAX kontrolu emailu a prepínanie viditeľnosti hesla
window.__CHECK_EMAIL_URL__ – URL pre AJAX validáciu emailu
register-validate-ajax.js – kontrola unikátnosti emailu
show-password.js – prepína viditeľnosť hesla
-->
<script>
    window.__CHECK_EMAIL_URL__ = '<?= $link->url('auth.checkEmail') ?>';
</script>
<script src="<?= $link->asset('js/register-validate-ajax.js') ?>"></script>
<script src="<?= $link->asset('js/show-password.js') ?>"></script>