<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var array $errors */

$allErrors = array_merge($errors ?? [], $formErrors ?? []);
?>

<?php if (!empty($allErrors)): ?>
    <div class="row justify-content-center">
        <div class="col-6">
            <?php foreach ($allErrors as $error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-6 d-flex gap-4 flex-column">
        <h1>Úprava záznamu</h1>
        <?php require 'form.view.php' ?>
    </div>
</div>