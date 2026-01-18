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
$currentUserId = $user?->getIdentity()?->getId();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Športové záznamy</h3>
            <?php if ($auth->isLoggedIn()): ?>
                <a href="<?php echo $link->url('record.add') ?>" class="btn btn-success shadow-sm">
                    <i class="bi bi-plus-lg"></i> Pridať záznam
                </a>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Disciplína</th>
                        <th>Vlastník</th>
                        <th>Výkon</th>
                        <th>Dátum</th>
                        <th>Poznámka</th>
                        <th class="text-end">Akcie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="6" class="text-center p-4 text-muted">Žiadne záznamy neboli nájdené.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $rec): ?>
                            <tr id="record-row-<?= $rec->getId() ?>">
                                <td class="fw-bold text-primary">
                                    <?= htmlspecialchars($rec->getNazovDiscipliny(), ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <?php $ownerName = $owners[$rec->getUserId()] ?? ('Užívateľ #' . $rec->getUserId()); ?>
                                <td>
                                    <span class="text-secondary">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars((string)$ownerName, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars((string)($rec->getDosiahnutyVykon() ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td class="text-muted">
                                    <small><?= htmlspecialchars((string)($rec->getDatumVykonu() ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>

                                <td>
                                    <small class="text-truncate d-inline-block" style="max-width: 150px;">
                                        <?= htmlspecialchars((string)($rec->getPoznamka() ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </td>

                                <td class="text-end">
                                    <?php
                                    $showActions = false;
                                    if ($auth->isLoggedIn() && method_exists($user, 'getIdentity')) {
                                        $ident = $user->getIdentity();
                                        $role = $ident?->getRole() ?? null;
                                        $uid = $ident?->getId() ?? null;
                                        if (in_array($role, ['admin', 'trener'], true) || $uid === $rec->getUserId()) {
                                            $showActions = true;
                                        }
                                    }
                                    ?>

                                    <?php if ($showActions): ?>
                                        <div class="d-flex justify-content-end gap-2">
                                            <a class="btn btn-sm btn-warning" href="<?php echo $link->url('record.edit', ['id' => $rec->getId()]) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?= $link->url('record.delete', ['id' => $rec->getId()]) ?>"
                                               class="btn btn-sm btn-danger delete-btn"
                                               data-ajax="true"
                                               data-target-id="record-row-<?= $rec->getId() ?>"
                                               data-message="Naozaj chceš vymazať tento športový záznam?">
                                                <i class="bi bi-trash"></i>
                                            </a>

                                            <form id="delete-record-<?= $rec->getId() ?>"
                                                  method="post"
                                                  action="<?= $link->url('record.delete', ['id' => $rec->getId()]) ?>"
                                                  style="display:none;">
                                                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            </form>
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