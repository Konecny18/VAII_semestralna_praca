<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser|null $user */
/** @var \App\Models\Record[]|null $records */
/** @var array|null $owners */
/** @var IAuthenticator $auth */

use Framework\Core\IAuthenticator;

$owners = $owners ?? [];

//// compute flags
//$isLoggedIn = ($user && method_exists($user, 'isLoggedIn') && $user->isLoggedIn());
//$isAdmin = false;
//if ($isLoggedIn && method_exists($user, 'getIdentity')) {
//    $ident = $user->getIdentity();
//    $isAdmin = ($ident?->getRole() ?? null) === 'admin';
//}

?>

<?php if ($auth->isLoggedIn()): ?>
    <a href="<?php echo $link->url('record.add') ?>" class="btn btn-success">Pridať záznam</a>
<?php endif; ?>

<?php if (empty($records)): ?>
    <p>Žiadne záznamy.</p>
<?php else: ?>
    <!--bootstrap triedy-->
    <table class="table table-striped">
        <thead>
        <tr>
            <?php if ($auth->isAdmin()): ?>
                <th>ID</th>
            <?php endif; ?>
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
            <tr>
                <?php if ($auth->isAdmin()): ?>
                    <td><?= htmlspecialchars((string)$rec->getId(), ENT_QUOTES, 'UTF-8') ?></td>
                <?php endif; ?>

                <td><?= htmlspecialchars($rec->getNazovDiscipliny(), ENT_QUOTES, 'UTF-8') ?></td>

<!--                v poli owners zadam id pouzivatela a nasledne si vytiahne meno pomocou kodu v controlleri za ?? sa spravi iba ak sa nenajde uzivatel-->
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
                        if ($role === 'admin' || $uid === $rec->getUserId()) {
                            $showActions = true;
                        }
                    }
                    ?>

                    <?php if ($showActions): ?>
                        <a class="btn btn-sm btn-warning" href="<?php echo $link->url('record.edit', ['id' => $rec->getId()]) ?>">Upraviť</a>
                        <form method="post" action="<?= $link->url('record.delete', ['id' => $rec->getId()]) ?>"
                              class="record-action-form"
                              id="delete-form-<?= $rec->getId() ?>"> <button type="submit"
                             class="btn btn-sm btn-danger delete-btn"
                             data-message="Naozaj chceš vymazať tento záznam?">
                                <i class="bi bi-trash"></i> Zmazať
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
