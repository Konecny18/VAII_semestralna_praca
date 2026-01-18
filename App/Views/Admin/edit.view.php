<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var array $userData */

$userData = $userData ?? [];
?>

<?php if (!empty($errors)): ?>
    <div class="row justify-content-center">
        <div class="col-10">
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-10">
        <h1 class="mb-4">Upraviť používateľa: <?= htmlspecialchars($userData['email'] ?? '') ?></h1>

        <?php include __DIR__ . DIRECTORY_SEPARATOR . 'form.view.php'; ?>
    </div>
</div>