<?php

/** @var \App\Models\Training[] $trainings */
/** @var Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser|null $user */
/** @var IAuthenticator $auth */

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


<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h3>Rozvrh tréningov</h3>
        <?php if ($auth->isAdmin()): ?>
            <a href="<?php echo $link->url('training.add') ?>" class="btn btn-success">Pridať tréning</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($trainings)): ?>
    <div class="row">
        <div class="col-12 text-center my-4">Žiadne tréningy.</div>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Deň</th>
                    <th>Čas</th>
                    <th>Popis</th>
                    <?php if ($auth->isAdmin()): ?>
                        <th class="text-end">Akcie</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($trainings as $t): ?>
                    <tr>
                        <td><?php echo $days[$t->getDen()] ?? htmlspecialchars($t->getDen()) ?></td>
                        <td><?php echo htmlspecialchars(substr((string)$t->getCasZaciatku(), 0, 5)) ?> - <?php echo htmlspecialchars(substr((string)$t->getCasKonca(), 0, 5)) ?></td>
                        <td><?php echo htmlspecialchars($t->getPopis()) ?></td>
                        <?php if ($auth->isAdmin()): ?>
                            <td class="text-end">
                                <a href="<?php echo $link->url('training.edit', ['id' => $t->getId()]) ?>" class="btn btn-sm btn-warning">Upraviť</a>
                                <a href="<?= $link->url('training.delete', ['id' => $t->getId()]) ?>"
                                   class="btn btn-sm btn-danger delete-btn"
                                   data-message="Naozaj chceš zmazať tento tréning?">
                                    <i class="bi bi-trash"></i> Zmazať
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

