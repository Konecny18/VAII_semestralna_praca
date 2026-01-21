<?php
/** @var LinkGenerator $link */
/** @var AppUser|null $user */
/** @var Record[]|null $records */
/** @var array|null $owners */
/** @var IAuthenticator $auth */

use App\Models\Record;
use Framework\Auth\AppUser;
use Framework\Core\IAuthenticator;
use Framework\Support\LinkGenerator;

$owners = $owners ?? [];
// Získame identitu prihláseného používateľa raz na začiatku
$identity = $auth->isLoggedIn() ? $user?->getIdentity() : null;
?>

<div class="row mb-5">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4 sekcia-hlavicka">
            <h3 class="mb-0">Športové záznamy</h3>
            <?php if ($auth->isLoggedIn()): ?>
                <a href="<?php echo $link->url('record.add') ?>" class="btn btn-success shadow-sm">
                    <i class="bi bi-plus-lg"></i> Pridať záznam
                </a>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 vlastna-tabulka">
                    <thead class="table-light">
                    <tr>
                        <th class="col-disciplina">Disciplína</th>
                        <th class="col-vlastnik">Vlastník</th>
                        <th class="col-vykon">Výkon</th>
                        <th class="col-datum">Dátum</th>
                        <th class="col-poznamka">Poznámka</th>
                        <th class="text-end col-akcie">Akcie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="6" class="text-center p-4 text-muted">Žiadne záznamy neboli nájdené.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $rec): ?>
                            <?php
                            // LOGIKA PRE SHOW ACTIONS:
                            $showActions = false;
                            if ($identity) {
                                $role = $identity->getRole();
                                $uid = $identity->getId();
                                // Zobraziť akcie ak je admin/trener ALEBO ak je to jeho vlastný záznam
                                if (in_array($role, ['admin', 'trener'], true) || $uid === $rec->getUserId()) {
                                    $showActions = true;
                                }
                            }
                            ?>
                            <tr id="record-row-<?= $rec->getId() ?>">
                                <td class="fw-bold text-primary disciplina-text">
                                    <?= htmlspecialchars($rec->getNazovDiscipliny(), ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <?php $ownerName = $owners[$rec->getUserId()] ?? ('Užívateľ #' . $rec->getUserId()); ?>
                                <td>
                                    <span class="text-secondary">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars((string)$ownerName, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="vykon-znacka">
                                        <?= htmlspecialchars((string)($rec->getDosiahnutyVykon() ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td class="text-muted">
                                    <small><?= htmlspecialchars((string)($rec->getDatumVykonu() ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>

                                <td>
                                    <small class="text-truncate d-inline-block poznamka-text" style="max-width: 150px;">
                                        <?= htmlspecialchars((string)($rec->getPoznamka() ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </td>

                                <td class="text-end">
                                    <?php if ($showActions): ?>
                                        <div class="d-flex justify-content-end gap-2">
                                            <a class="btn btn-sm btn-warning" href="<?php echo $link->url('record.edit', ['id' => $rec->getId()]) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?= $link->url('record.delete', ['id' => $rec->getId()]) ?>"
                                               class="btn btn-sm btn-danger tlacidlo-vymazat"
                                               data-ajax="true"
                                               data-target-id="record-row-<?= $rec->getId() ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
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