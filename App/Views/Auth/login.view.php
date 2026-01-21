<?php

/** @var string|null $message */
/** @var LinkGenerator $link */
/** @var View $view */

use Framework\Support\LinkGenerator;
use Framework\Support\View;

// Nastavenie layoutu pre prihlasovaciu stránku (auth layout)
$view->setLayout('auth');
?>

<!--
row – Bootstrap grid riadok
-->
<div class="row">
    <!--
    col-sm-9 col-md-7 col-lg-5 – responzívny stĺpec
    mx-auto – horizontálne centrovanie
    -->
    <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
        <!--
        card – Bootstrap karta
        prihlasenie-karta – vlastná CSS pre štýl login karty
        shadow-lg – väčší tieň pre vizuálny efekt
        -->
        <div class="card prihlasenie-karta shadow-lg">
            <!--
            card-body – obsah karty
            p-4 p-md-5 – padding pre menšie a väčšie obrazovky
            -->
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <!--
                    Ikona používateľa
                    mb-3 – spodný margin
                    -->
                    <div class="prihlasenie-icona-obalovac mb-3">
                        <i class="bi bi-person-lock"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Prihlásenie</h3>
                    <p class="text-muted small">Vitajte späť v T.A.T. Martin</p>
                </div>

                <?php if (!empty($message)): ?>
                    <!--
                        Zobrazenie chybového hlásenia pri neúspešnom login-e
                        alert-danger – červený alert
                        border-0 – bez okraja
                        shadow-sm – jemný tieň
                        text-center – centrovanie textu
                        p-3 mb-4 – padding a margin
                        -->
                    <div class="alert alert-danger border-0 shadow-sm text-center p-3 mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!--
                Formulár prihlasovania
                form-signin – vlastná CSS trieda
                method="post" – odoslanie dát na server
                action – URL spracovania loginu
                -->
                <form class="form-signin" method="post" action="<?= $link->url("login") ?>">

                    <!-- Email input -->
                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold">Emailová adresa</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <!--
                            ikona emailu v inpute
                            input-group-text – Bootstrap wrapper pre ikonku
                            bg-light – svetlé pozadie
                            border-end-0 – odstráni pravý okraj
                            -->
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>

                            <!--
                            email input
                            border-start-0 – odstráni ľavý okraj (spojuje sa s ikonou)
                            bg-light – svetlé pozadie
                            placeholder – ukážkový text
                            required – povinné pole
                            autofocus – kurzor hneď v poli
                            -->
                            <input name="email" type="email" id="email"
                                   class="form-control border-start-0 bg-light"
                                   placeholder="meno@email.sk" required autofocus>
                        </div>
                    </div>

                    <!-- Password input -->
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Heslo</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <!-- ikona zámku -->
                            <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-shield-lock text-muted"></i>
                            </span>

                            <input name="password" type="password" id="password"
                                   class="form-control border-start-0 bg-light"
                                   placeholder="Vaše heslo" required>

                            <!--
                           toggle-password button
                           btn-outline-light – svetle tlačidlo
                           border-start-0 – bez ľavého okraja
                           text-muted – sivá ikona
                           data-target="password" – JS vie, ktorý input prepnúť
                           -->
                            <button class="btn btn-outline-light border-start-0 text-muted toggle-password"
                                    type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit button -->
                    <button class="btn btn-primary tlacidlo-prihlasenie w-100 py-3 fw-bold shadow-sm mt-4" type="submit" name="submit">
                        Prihlásiť sa <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>

                <!-- Link na registráciu -->
                <div class="mt-3 text-center">
                    <p class="text-muted small mb-0">Nemáte ešte účet?</p>
                    <a href="<?= $link->url('register') ?>" class="text-primary fw-bold text-decoration-none">
                        Vytvoriť bezplatnú registráciu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!--
JS na prepínanie viditeľnosti hesla
-->
<script src="<?= $link->asset('js/show-password.js') ?>"></script>