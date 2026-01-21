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
        <h2 class="mb-4">Pridaj podujatie</h2>
        <!--
        Znovupoužiteľný formulár
        Rovnaký ako pri editácii albumu
        (MVC princíp – DRY)
        -->
        <?php include __DIR__ . '/form.view.php'; ?>
    </div>
</div>