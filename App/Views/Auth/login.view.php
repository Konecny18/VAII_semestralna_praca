<?php

/** @var string|null $message */
/** @var LinkGenerator $link */
/** @var View $view */

use Framework\Support\LinkGenerator;
use Framework\Support\View;

$view->setLayout('auth');
?>


<div class="row">
    <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
        <div class="card prihlasenie-karta shadow-lg">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <div class="prihlasenie-icona-obalovac mb-3">
                        <i class="bi bi-person-lock"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Prihlásenie</h3>
                    <p class="text-muted small">Vitajte späť v T.A.T. Martin</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger border-0 shadow-sm text-center p-3 mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form class="form-signin" method="post" action="<?= $link->url("login") ?>">

                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold">Emailová adresa</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>

                            <input name="email" type="email" id="email"
                                   class="form-control border-start-0 bg-light"
                                   placeholder="meno@email.sk" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Heslo</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-shield-lock text-muted"></i>
                            </span>

                            <input name="password" type="password" id="password"
                                   class="form-control border-start-0 bg-light"
                                   placeholder="Vaše heslo" required>

                            <button class="btn btn-outline-light border-start-0 text-muted toggle-password"
                                    type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-primary tlacidlo-prihlasenie w-100 py-3 fw-bold shadow-sm mt-4" type="submit" name="submit">
                        Prihlásiť sa <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>

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


<script src="<?= $link->asset('js/show-password.js') ?>"></script>