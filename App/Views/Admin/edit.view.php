<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var array $userData */

$userData = $userData ?? [];
$errors = $errors ?? [];

// Spojíme meno a priezvisko pre nadpis, ak existujú
$fullDisplayName = trim(($userData['meno'] ?? '') . ' ' . ($userData['priezvisko'] ?? ''));
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h1 class="mb-4">
            Upraviť používateľa:
            <small class="text-muted"><?= htmlspecialchars($fullDisplayName ?: ($userData['email'] ?? '')) ?></small>
        </h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php include __DIR__ . DIRECTORY_SEPARATOR . 'form.view.php'; ?>
    </div>
</div>