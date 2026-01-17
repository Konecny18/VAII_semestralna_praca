<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
?>

<?php if (!empty($formErrors ?? [])): ?>
    <div class="row justify-content-center">
        <div class="col-8">
            <?php foreach ($formErrors as $error): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-8">
        <h2 class="mb-4">Pridať tréning</h2>

        <?php include __DIR__ . '/form.view.php'; ?>
    </div>
</div>