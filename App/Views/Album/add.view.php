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
        col-6 – stĺpec so šírkou 6/12
        -->
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
        <!--
        Nadpis stránky – vytvorenie nového albumu
        -->
        <h1>Pridanie albumu</h1>
        <!--
        Znovupoužiteľný formulár
        Rovnaký ako pri editácii albumu
        (MVC princíp – DRY)
        -->
        <?php require 'form.view.php' ?>
    </div>
</div>