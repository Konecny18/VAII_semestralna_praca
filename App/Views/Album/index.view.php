<?php
/** @var Album[] $albums */
/** @var Framework\Support\LinkGenerator $link */
/** @var IAuthenticator $auth */

use App\Models\Album;
use Framework\Core\IAuthenticator;
?>

<!--
row – Bootstrap grid riadok
mb-3 – spodný margin pre oddelenie hlavičky od obsahu
-->
<div class="row mb-3">
    <!--
    col-12 – stĺpec na celú šírku
    d-flex – flexbox rozloženie
    justify-content-between – prvky na opačných stranách
    align-items-center – vertikálne zarovnanie
    -->
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h3>Albumy</h3>
        <?php if ($auth->isAdmin()): ?>
            <!--
                Tlačidlo na vytvorenie albumu
                Viditeľné iba pre administrátora
                btn-success – zelené tlačidlo (pozitívna akcia)
                -->
            <a href="<?= $link->url('album.add') ?>" class="btn btn-success">Vytvor album</a>
        <?php endif; ?>
    </div>
</div>

<!--
row – Bootstrap grid riadok pre zoznam albumov
g-4 – medzera medzi stĺpcami/riadkami
justify-content-center – vycentrovanie albumov
-->
<div class="row g-4 justify-content-center">
    <?php if (empty($albums)): ?>
        <div class="col-12 text-center my-4">Žiadne albumy.</div>
    <?php else: ?>
        <?php foreach ($albums as $album): ?>
            <!--
               col-auto – šírka podľa obsahu
               ID slúži pre AJAX odstránenie albumu z DOM
               -->
            <div class="col-auto" id="album-card-<?= $album->getId() ?>">
                <!--
               border – Bootstrap okraj
               album – vlastná CSS trieda pre dizajn
               d-flex – flexbox layout
               flex-column – prvky pod sebou
               position-relative – referenčný bod pre absolute prvky
               h-100 – výška 100 % rodiča
               -->
                <div class="border album d-flex flex-column position-relative h-100">
                    <div>

                        <?php
                        // Získanie obrázka albumu
                        $picture = $album->getPicture();
                        ?>

                        <?php if ($picture !== ''): ?>
                            <!--
                               Zobrazenie obrázka albumu, ak existuje
                               -->
                            <img src="<?= $link->asset($picture) ?>" class="obrazok-karta" alt="Album image">
                        <?php else: ?>
                            <!--
                                Placeholder obrázok, ak album nemá vlastnú fotku
                                -->
                            <img src="<?= $link->asset('images/tat_logo.png') ?>" class="obrazok-karta" alt="Album placeholder">
                        <?php endif; ?>
                    </div>
                    <div class="m-2">
                        <!--
                            htmlspecialchars – ochrana proti XSS útokom
                            Aj keby niekto obišiel validáciu v controlleri,
                            HTML/JS kód sa nevykoná
                            -->
                        <strong><?= htmlspecialchars($album->getText(), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>

                    <!--
                    m-2 – margin
                    d-flex – flexbox rozloženie tlačidiel
                    gap-2 – medzera medzi tlačidlami
                    justify-content-end – zarovnanie doprava
                    mt-auto – posunutie tlačidiel na spodok karty
                    -->
                    <div class="m-2 d-flex gap-2 justify-content-end mt-auto">
                        <!--
                        Tlačidlo na zobrazenie príspevkov v albume
                        btn-primary – hlavná akcia
                        -->
                        <a href="<?= $link->url('post.index', ['albumId' => $album->getId()]) ?>" class="btn btn-primary">Zobraziť</a>

                        <?php if ($auth->isAdmin()): ?>
                            <a href="<?= $link->url('album.edit', ['id' => $album->getId()]) ?>" class="btn btn-warning">Upraviť</a>

                            <!--
                                Tlačidlo na vymazanie albumu
                                data-ajax – vymazanie pomocou AJAXu
                                data-target-id – karta, ktorá sa odstráni z DOM
                                -->
                            <a href="<?= $link->url('album.delete', ['id' => $album->getId()]) ?>"
                               class="btn btn-danger tlacidlo-vymazat"
                               data-ajax="true"
                               data-target-id="album-card-<?= $album->getId() ?>"
                               data-message="Odstrániť album a všetky jeho fotky?">
                                Zmazať
                            </a>

                            <!--
                                Skrytý formulár ako záloha,
                                ak AJAX vymazanie zlyhá
                                Obsahuje CSRF token
                                -->
                            <form id="delete-album-<?= $album->getId() ?>"
                                  method="post"
                                  action="<?= $link->url('album.delete', ['id' => $album->getId()]) ?>"
                                  style="display:none;">
                                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
