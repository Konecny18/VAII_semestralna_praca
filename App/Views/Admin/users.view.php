<?php
/** @var array $users */
/** @var Framework\Support\LinkGenerator $link */
/** @var AppUser|null $user */
/** @var string|null $error */

use Framework\Auth\AppUser;

$users = $users ?? [];
$currentUserId = $user?->getIdentity()?->getId();
?>

<div class="row">
    <div class="col-12">
        <h3 class="mb-4">Správa používateľov</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Meno</th>
                        <th>Priezvisko</th>
                        <th>Email</th>
                        <th>Rola</th>
                        <th class="text-end">Akcie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="text-center p-4">V databáze nie sú žiadni používatelia.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $row): ?>
                            <?php
                            $uid = (int)($row['id'] ?? 0);
                            $meno = $row['meno'] ?? '';
                            $priezvisko = $row['priezvisko'] ?? '';
                            $email = $row['email'] ?? '';
                            $rola = $row['rola'] ?? 'atlet';
                            $isSelf = ($currentUserId !== null && $uid !== 0 && (int)$currentUserId === $uid);
                            ?>
                            <tr id="user-row-<?= $uid ?>">
                                <td><?= htmlspecialchars((string)$meno) ?></td>
                                <td><?= htmlspecialchars((string)$priezvisko) ?></td>
                                <td><?= htmlspecialchars((string)$email) ?></td>
                                <td>
                                    <span class="badge <?= $rola === 'admin' ? 'bg-danger' : ($rola === 'trener' ? 'bg-warning text-dark' : 'bg-primary') ?>">
                                        <?= htmlspecialchars((string)$rola) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-warning" href="<?= $link->url('admin.edit', ['id' => $uid]) ?>">
                                            <i class="bi bi-pencil"></i> Upraviť
                                        </a>

                                        <?php if (!$isSelf): ?>
                                            <a href="<?= $link->url('admin.delete', ['user_id' => $uid]) ?>"
                                               class="btn btn-sm btn-danger delete-btn"
                                               data-ajax="true"
                                               data-target-id="user-row-<?= $uid ?>"
                                               data-message="Naozaj zmazať tento účet a všetky jeho výkony?">
                                                <i class="bi bi-trash"></i> Vymazať
                                            </a>

                                            <form id="delete-user-form-<?= $uid ?>"
                                                  method="post"
                                                  action="<?= $link->url('admin.delete') ?>"
                                                  style="display:none;">
                                                <input type="hidden" name="user_id" value="<?= $uid ?>">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
                                            </form>
                                        <?php else: ?>
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