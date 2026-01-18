<?php
/** @var Framework\Support\LinkGenerator $link */
/** @var array $userData */
/** @var string|null $action */

$uid = $userData['id'] ?? null;
$rola = $userData['rola'] ?? 'atlet';
?>

<form method="post" action="<?= $link->url('admin.update') ?>" class="card shadow-sm p-4">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($userData['id'] ?? '')) ?>">

    <div class="mb-3">
        <label for="meno" class="form-label fw-bold">Meno</label>
        <input id="meno" name="meno" class="form-control" value="<?= htmlspecialchars($userData['meno'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
        <label for="priezvisko" class="form-label fw-bold">Priezvisko</label>
        <input id="priezvisko" name="priezvisko" class="form-control" value="<?= htmlspecialchars($userData['priezvisko'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-bold">Email</label>
        <input id="email" name="email" type="email" class="form-control" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
        <label for="role" class="form-label fw-bold">Rola</label>
        <select id="role" name="role" class="form-select">
            <option value="atlet" <?= ($userData['rola'] ?? '') === 'atlet' ? 'selected' : '' ?>>Atlet</option>
            <option value="trener" <?= ($userData['rola'] ?? '') === 'trener' ? 'selected' : '' ?>>Tréner</option>
            <option value="admin" <?= ($userData['rola'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?= $link->url('admin.users') ?>" class="btn btn-secondary">Späť</a>
        <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
    </div>
</form>