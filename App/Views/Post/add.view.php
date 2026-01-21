<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
?>

<?php if (!empty($formErrors)): ?>
    <div class="row justify-content-center">
        <div class="col-6">
            <?php foreach ($formErrors as $error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-6 d-flex gap-4 flex-column">
        <h1>Pridanie príspevku</h1>

        <?php require 'form.view.php' ?>
    </div>
</div>