<?php
/** @var Training[] $trainings */
/** @var Framework\Support\LinkGenerator $link */
/** @var AppUser|null $user */
/** @var IAuthenticator $auth */

use App\Models\Training;
use Framework\Auth\AppUser;
use Framework\Core\IAuthenticator;

$trainings = $trainings ?? [];
$days = [
        'Pon' => 'Pondelok',
        'Uto' => 'Utorok',
        'Str' => 'Streda',
        'Stv' => 'Štvrtok',
        'Pia' => 'Piatok',
        'Sob' => 'Sobota',
        'Ned' => 'Nedeľa'
];
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4 sekcia-hlavicka">
            <h3 class="mb-0">Rozvrh tréningov</h3>
            <?php if ($auth->isAdmin()): ?>
                <a href="<?= $link->url('training.add') ?>" class="btn btn-success shadow-sm">
                    <i class="bi bi-plus-lg"></i> Pridať tréning
                </a>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 vlastna-tabulka">
                    <thead class="table-light">
                    <tr>
                        <th class="col-den">Deň</th>
                        <th class="col-cas">Čas</th>
                        <th class="col-popis">Popis</th>
                        <?php if ($auth->isAdmin()): ?>
                            <th class="text-center col-skryt">Skryť</th>
                            <th class="text-end col-akcie">Akcie</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($trainings)): ?>
                        <tr>
                            <td colspan="<?= $auth->isAdmin() ? 5 : 3 ?>" class="text-center p-4 text-muted">
                                Aktuálne nie sú naplánované žiadne tréningy.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trainings as $t): ?>
                            <?php // safety: ensure non-admin users don't see inactive trainings even if controller returned them ?>
                            <?php if (!$auth->isAdmin() && !$t->getActive()) continue; ?>
                            <tr id="training-row-<?= $t->getId() ?>">
                                <td>
                                    <span class="den-znacka">
                                        <i class="bi bi-calendar-event me-2"></i><?= $days[$t->getDen()] ?? htmlspecialchars($t->getDen()) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="cis-znacka">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= htmlspecialchars(substr((string)$t->getCasZaciatku(), 0, 5)) ?> - <?= htmlspecialchars(substr((string)$t->getCasKonca(), 0, 5)) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="td-popis"><?= htmlspecialchars($t->getPopis()) ?></span>
                                </td>
                                <?php if ($auth->isAdmin()): ?>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <div class="form-check form-switch mb-0 ps-0">
                                                <input class="form-check-input training-active-toggle m-0"
                                                       type="checkbox"
                                                       role="switch"
                                                       id="training-active-<?= $t->getId() ?>"
                                                       data-id="<?= $t->getId() ?>"
                                                       data-url="<?= $link->url('training.toggleActive') ?>"
                                                       data-csrf="<?= $_SESSION['csrf_token'] ?>"
                                                        <?= $t->getActive() ? 'checked' : '' ?>
                                                       style="cursor: pointer; float: none;"> <label class="visually-hidden" for="training-active-<?= $t->getId() ?>">
                                                    Toggle viditeľnosti tréningu <?= $t->getId() ?>
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 align-items-center">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?= $link->url('training.edit', ['id' => $t->getId()]) ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?= $link->url('training.delete', ['id' => $t->getId()]) ?>"
                                                   class="btn btn-sm btn-danger delete-btn"
                                                   data-ajax="true"
                                                   data-target-id="training-row-<?= $t->getId() ?>">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<script src="<?= $link->asset('js/training-active-toggle.js') ?>"></script>
