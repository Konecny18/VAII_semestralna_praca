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
        <div class="card card-signin my-5 shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-center">Prihlásenie</h5>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger text-center p-2 small">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form class="form-signin" method="post" action="<?= $link->url("login") ?>">
                    <div class="form-label-group mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input name="email" type="email" id="email" class="form-control" placeholder="Email"
                               required autofocus>
                    </div>

                    <div class="form-label-group mb-3">
                        <label for="password" class="form-label">Heslo</label>
                        <div class="input-group">
                            <input name="password" type="password" id="password" class="form-control"
                                   placeholder="Heslo" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="text-center">
                        <button class="btn btn-primary w-100" type="submit" name="submit">Log in</button>
                    </div>
                </form>
                <div class="mt-4 text-center">
                    <a href="<?= $link->url('register') ?>" class="text-decoration-none">Nemáte účet? Zaregistrujte sa</a>
                </div>

            </div>
        </div>
    </div>
</div>


<script src="<?= $link->asset('js/show-password.js') ?>"></script>