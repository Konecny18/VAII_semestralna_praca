<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var array $userData */

// Inicializácia premenných – ochrana proti undefined chybám
$userData = $userData ?? [];
$errors = $errors ?? [];

// Spojíme meno a priezvisko pre nadpis, ak existujú
$fullDisplayName = trim(($userData['meno'] ?? '') . ' ' . ($userData['priezvisko'] ?? ''));
?>

<!--
row – Bootstrap grid riadok
justify-content-center – vycentrovanie obsahu horizontálne
-->
<div class="row justify-content-center">
    <!--
    col-md-8 – šírka stĺpca na stredných obrazovkách
    col-lg-6 – užší stĺpec na veľkých obrazovkách
    (zlepšenie čitateľnosti formulára)
    -->
    <div class="col-md-8 col-lg-6">
        <h1 class="mb-4">
            Upraviť používateľa:
            <!--
            small – menší text
            text-muted – tlmená (sivá) farba
            Zobrazí sa meno alebo email používateľa
            -->
            <small class="text-muted"><?= htmlspecialchars($fullDisplayName ?: ($userData['email'] ?? '')) ?></small>
        </h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0">
                    <!--
                    Výpis validačných chýb z backendu
                    -->
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <!--
        Vloženie formulára ako samostatnej view (MVC princíp)
        Umožňuje znovupoužitie formulára pre create/edit
        -->

        <?php include __DIR__ . DIRECTORY_SEPARATOR . 'form.view.php'; ?>
    </div>
</div>