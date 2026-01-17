<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var Training $training */

use App\Models\Training;

?>

<?php if (!empty($formErrors ?? [])): ?>
    <div class="row justify-content-center">
        <div class="col-8">
            <?php foreach ($formErrors as $error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-8">
        <h2 class="mb-4">Upraviť tréning</h2> <?php include __DIR__ . '/form.view.php'; ?>
    </div>
</div>