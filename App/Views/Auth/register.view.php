<?php

/** @var array $errors */
/** @var array $old */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container">
    <div class="row">
        <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
            <div class="card card-signin my-5">
                <div class="card-body">
                    <h5 class="card-title text-center">Registrácia</h5>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form class="form-signin" method="post" action="<?= $link->url('register') ?>">
                        <div class="form-label-group mb-3">
                            <label for="meno" class="form-label">Meno</label>
                            <input name="meno" type="text" id="meno" class="form-control" placeholder="Meno"
                                   required value="<?= htmlspecialchars($old['meno'] ?? '') ?>">
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="priezvisko" class="form-label">Priezvisko</label>
                            <input name="priezvisko" type="text" id="priezvisko" class="form-control" placeholder="Priezvisko"
                                   required value="<?= htmlspecialchars($old['priezvisko'] ?? '') ?>">
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input name="email" type="email" id="email" class="form-control" placeholder="Email"
                                   required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="password" class="form-label">Heslo</label>
                            <input name="password" type="password" id="password" class="form-control"
                                   placeholder="Heslo" required>
                        </div>

                        <div class="form-label-group mb-3">
                            <label for="password_confirm" class="form-label">Potvrďte heslo</label>
                            <input name="password_confirm" type="password" id="password_confirm" class="form-control"
                                   placeholder="Potvrďte heslo" required>
                        </div>

                        <div class="text-center">
                            <button class="btn btn-primary" type="submit">Registrovať</button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="<?= $link->url('login') ?>">Máte účet? Prihlásiť sa</a>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>
