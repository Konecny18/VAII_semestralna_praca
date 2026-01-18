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
?>

<?php if ($auth->isLoggedIn()): ?>
    <a href="<?php echo $link->url('record.add') ?>" class="btn btn-success">Pridať záznam</a>
<?php endif; ?>

<?php if (empty($records)): ?>
    <p>Žiadne záznamy.</p>
<?php else: ?>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Disciplína</th>
            <th>Vlastník</th>
            <th>Výkon</th>
            <th>Dátum</th>
            <th>Poznámka</th>
            <th>Akcie</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $rec): ?>
            <tr id="record-row-<?= $rec->getId() ?>">
                <td><?= htmlspecialchars($rec->getNazovDiscipliny(), ENT_QUOTES, 'UTF-8') ?></td>

                <?php $ownerName = $owners[$rec->getUserId()] ?? ('Užívateľ #' . $rec->getUserId()); ?>
                <td><?= htmlspecialchars((string)$ownerName, ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars((string)($rec->getDosiahnutyVykon() ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($rec->getDatumVykonu() ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($rec->getPoznamka() ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
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
                        <a class="btn btn-sm btn-warning" href="<?php echo $link->url('record.edit', ['id' => $rec->getId()]) ?>">Upraviť</a>
                        <a href="<?= $link->url('record.delete', ['id' => $rec->getId()]) ?>"
                           class="btn btn-sm btn-danger delete-btn"
                           data-ajax="true"
                           data-target-id="record-row-<?= $rec->getId() ?>"
                           data-message="Naozaj chceš vymazať tento športový záznam?">
                            <i class="bi bi-trash"></i> Zmazať
                        </a>

                        <form id="delete-record-<?= $rec->getId() ?>"
                              method="post"
                              action="<?= $link->url('record.delete', ['id' => $rec->getId()]) ?>"
                              style="display:none;">
                            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>