<?php
/** @var array $users */
/** @var Framework\Support\LinkGenerator $link */
/** @var AppUser|null $user */
/** @var string|null $error */

use Framework\Auth\AppUser;

// Inicializácia: Ak $users neexistuje, nastavím prázdne pole, aby foreach nezlyhal
$users = $users ?? [];
// Získanie ID aktuálne prihláseného používateľa pre neskoršiu kontrolu (zamedzenie zmazania seba samého)
$currentUserId = $user?->getIdentity()?->getId();
?>

<!--row – Bootstrap grid riadok (flex kontajner)-->
<div class="row">
    <div class="col-12">
<!--        spodny okraj-->
        <h3 class="mb-4">Správa používateľov</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div>
        <?php endif; ?>

        <!--
       card – Bootstrap karta (vizuálny obal obsahu)
       shadow-sm – jemný tieň
       -->
        <div class="card shadow-sm">
            <!--
            table-responsive – zabezpečí scroll tabuľky na menších obrazovkách
            -->
            <div class="table-responsive">
                <!--
               table – základná Bootstrap tabuľka
               table-hover – zvýraznenie riadku pri prejdení myšou
               align-middle – vertikálne zarovnanie obsahu buniek
               mb-0 – odstránenie spodného marginu tabuľky
               -->
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Meno</th>
                            <th>Priezvisko</th>
                            <th>Email</th>
                            <th>Rola</th>
                            <!--
                               text-end – zarovnanie textu doprava
                               -->
                            <th class="text-end">Akcie</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center p-4">V databáze nie sú žiadni používatelia.
                            </td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($users as $row): ?>
                            <?php
                            // Príprava dát z aktuálneho riadku
                            $uid = (int)($row['id'] ?? 0);
                            $meno = $row['meno'] ?? '';
                            $priezvisko = $row['priezvisko'] ?? '';
                            $email = $row['email'] ?? '';
                            $rola = $row['rola'] ?? 'atlet';

                            // Logická kontrola: Ak je ID riadku rovnaké ako ID prihláseného, ide o mňa
                            $isSelf = ($currentUserId !== null && $uid !== 0 && (int)$currentUserId === $uid);
                            ?>

                            <!--
                            Jeden riadok používateľa
                            ID sa používa pri AJAX vymazaní
                            -->
                            <tr id="user-row-<?= $uid ?>">
                                <td><?= htmlspecialchars((string)$meno) ?></td>
                                <td><?= htmlspecialchars((string)$priezvisko) ?></td>
                                <td><?= htmlspecialchars((string)$email) ?></td>

                                <td>
                                    <!--
                                    badge – Bootstrap štítok
                                    bg-danger – admin
                                    bg-warning – tréner
                                    bg-primary – bežný používateľ
                                    -->
                                    <span class="badge <?= $rola === 'admin' ? 'bg-danger' : ($rola === 'trener' ? 'bg-warning text-dark' : 'bg-primary') ?>">
                                        <?= htmlspecialchars((string)$rola) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <!--
                                    d-flex – flexbox rozloženie
                                    justify-content-end – zarovnanie doprava
                                    gap-2 – medzera medzi tlačidlami
                                    -->
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-warning" href="<?= $link->url('admin.edit', ['id' => $uid]) ?>">
                                            <i class="bi bi-pencil"></i> Upraviť
                                        </a>

                                        <?php if (!$isSelf): ?>
                                            <!--
                                               btn-danger – deštruktívna akcia
                                               data-ajax – vymazanie pomocou AJAXu
                                               data-target-id – ID riadku, ktorý sa odstráni z DOM
                                               -->
                                            <a href="<?= $link->url('admin.delete', ['user_id' => $uid]) ?>"
                                               class="btn btn-sm btn-danger tlacidlo-vymazat"
                                               data-ajax="true"
                                               data-target-id="user-row-<?= $uid ?>"
                                               data-message="Naozaj zmazať tento účet a všetky jeho výkony?">
                                                <i class="bi bi-trash"></i> Vymazať
                                            </a>

                                            <!--
                                            Skrytý formulár ako záloha,
                                            ak AJAX vymazanie zlyhá
                                            Obsahuje CSRF token
                                            -->
                                            <form id="delete-user-form-<?= $uid ?>"
                                                  method="post"
                                                  action="<?= $link->url('admin.delete') ?>"
                                                  style="display:none;">
                                                <input type="hidden" name="user_id" value="<?= $uid ?>">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
                                            </form>
                                        <?php else: ?>
                                            <!--
                                               disabled – zakázané tlačidlo
                                               Ochrana pred vymazaním vlastného účtu
                                               -->
                                            <button class="btn btn-sm btn-outline-secondary disabled" title="Nemôžete zmazať sami seba">
                                                <i class="bi bi-trash"></i> Vymazať
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>