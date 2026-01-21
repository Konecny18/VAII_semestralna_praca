<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $errors */
?>

<?php if (!empty($errors)): ?>
    <!--
            Zobrazenie validačných chýb
            row – Bootstrap grid riadok
            justify-content-center – centrovanie obsahu
            -->
    <div class="row justify-content-center">
        <!--
        col-10 – stĺpec so šírkou 6/12
        -->
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
        <h1 class="mb-4">Upraviť podujatie</h1>
        <!--
        Znovupoužiteľný formulár
        Rovnaký ako pri editácii albumu
        (MVC princíp – DRY)
        -->
        <?php include __DIR__ . DIRECTORY_SEPARATOR . 'form.view.php'; ?>
    </div>
</div>
