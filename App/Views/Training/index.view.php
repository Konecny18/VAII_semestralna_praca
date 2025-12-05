<?php

/** @var \App\Models\Training[] $trainings */
/** @var Framework\Support\LinkGenerator $link */

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

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3>Rozvrh tréningov</h3>
            <a href="<?= $link->url('training.add') ?>" class="btn btn-success">Pridať tréning</a>
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
                            <th class="text-end">Akcie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainings as $t): ?>
                            <tr>
                                <td><?= $days[$t->getDen()] ?? htmlspecialchars($t->getDen()) ?></td>
                                <td><?= htmlspecialchars(substr((string)$t->getCasZaciatku(), 0, 5)) ?> - <?= htmlspecialchars(substr((string)$t->getCasKonca(), 0, 5)) ?></td>
                                <td><?= htmlspecialchars($t->getPopis()) ?></td>
                                <td class="text-end">
                                    <a href="<?= $link->url('training.edit', ['id' => $t->getId()]) ?>" class="btn btn-sm btn-warning">Upraviť</a>
                                    <a href="<?= $link->url('training.delete', ['id' => $t->getId()]) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Naozaj zmazať tréning?')">Zmazať</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
