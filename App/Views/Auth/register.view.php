<?php
/** @var array $errors */
/** @var array $old */
/** @var LinkGenerator $link */
/** @var View $view */

use Framework\Support\LinkGenerator;
use Framework\Support\View;

$view->setLayout('auth');
?>

<div class="row align-items-center min-vh-100 py-5">
    <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto">
        <div class="card prihlasenie-karta shadow-lg">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <div class="prihlasenie-icona-obalovac mb-3">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Registrácia</h3>
                    <p class="text-muted small">Staňte sa členom T.A.T. Martin</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4 text-start">
                        <ul class="mb-0 small">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form class="form-signin" method="post" action="<?= $link->url('register') ?>">

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

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                            <input name="email" type="email" id="email" class="form-control border-start-0 bg-light" placeholder="Email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                        </div>
                        <div id="email-feedback" class="small mt-1 px-2"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Heslo</label>
                        <div class="input-group prihlasenie-input-skupina">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                            <input name="password" type="password" id="password" class="form-control border-start-0 bg-light" placeholder="Heslo" required>
                            <button class="btn btn-outline-light border-start-0 text-muted toggle-password" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted px-1" style="font-size: 0.75rem;">Min. 8 znakov (A, a, 1, @).</small>
                    </div>

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

                    <button class="btn btn-primary tlacidlo-prihlasenie w-100 py-3 fw-bold shadow-sm" type="submit">
                        Vytvoriť účet <i class="bi bi-check-circle ms-2"></i>
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-muted small mb-0">Už máte účet?</p>
                    <a href="<?= $link->url('login') ?>" class="text-primary fw-bold text-decoration-none">Prihlásiť sa teraz</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    window.__CHECK_EMAIL_URL__ = '<?= $link->url('auth.checkEmail') ?>';
</script>
<script src="<?= $link->asset('js/register-validate-ajax.js') ?>"></script>
<script src="<?= $link->asset('js/show-password.js') ?>"></script>