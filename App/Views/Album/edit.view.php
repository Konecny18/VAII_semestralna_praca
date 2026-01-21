<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $errors */
?>

<?php if (!empty($errors)): ?>
    <!--
        row – Bootstrap grid riadok
        justify-content-center – vycentrovanie obsahu
        -->
    <div class="row justify-content-center">
        <!--
        col-6 – stĺpec so šírkou 6/12
        (formulár nie je príliš široký)
        -->
        <div class="col-6">
            <?php foreach ($errors as $error): ?>
                <!--
                    alert – Bootstrap komponent pre hlásenia
                    alert-danger – červený štýl (chyba)
                    role="alert" – zlepšenie prístupnosti
                    -->
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!--
row – Bootstrap grid riadok
justify-content-center – horizontálne vycentrovanie obsahu
-->
<div class="row justify-content-center">
    <!--
    col-6 – stredná šírka stĺpca
    d-flex – flexbox rozloženie
    flex-column – prvky pod sebou
    gap-4 – medzera medzi prvkami
    -->
    <div class="col-6 d-flex gap-4 flex-column">
        <h1>Úprava albumu</h1>
        <?php require 'form.view.php' ?>
    </div>
</div>