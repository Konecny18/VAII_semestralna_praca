<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $errors */
?>

<?php if (!empty($errors)): ?>
    <div class="row justify-content-center">
        <div class="col-6">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-6 d-flex gap-4 flex-column">
        <h1>Úprava albumu</h1>
        <?php require 'form.view.php' ?>
    </div>
</div>