<?php
/** @var Framework\Support\LinkGenerator $link */

// Ensure $user is defined (controller may or may not pass it)
if (!isset($user)) {
    $user = null;
}

// Zabezpečíme, aby sme pracovali s čistými dátami
$uid = (int)($userData['id'] ?? 0);
?>

<form method="post" action="<?= $link->url('admin.update') ?>" class="card shadow-sm p-4">
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
    <input type="hidden" name="id" value="<?= $uid ?>">

    <div class="mb-3">
        <label for="meno" class="form-label fw-bold">Meno</label>
        <input id="meno" name="meno" class="form-control"
               value="<?= htmlspecialchars((string)($userData['meno'] ?? '')) ?>" required>
    </div>

    <div class="mb-3">
        <label for="priezvisko" class="form-label fw-bold">Priezvisko</label>
        <input id="priezvisko" name="priezvisko" class="form-control"
               value="<?= htmlspecialchars((string)($userData['priezvisko'] ?? '')) ?>" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-bold">Email</label>
        <input id="email" name="email" type="email" class="form-control"
               value="<?= htmlspecialchars((string)($userData['email'] ?? '')) ?>" required>
    </div>

    <div class="mb-3">
        <label for="role" class="form-label fw-bold">Rola</label>
        <?php
        $currentRole = $userData['rola'] ?? '';
        $isEditingSelf = ((int)($user?->getIdentity()?->getId() ?? 0) === (int)$uid);
        ?>

        <?php if ($isEditingSelf): ?>
<!--        pokial chce sam sebe menit rolu, nemoze to urobit lebo readonly-->
            <input id="role" type="text" class="form-control bg-light" value="<?= htmlspecialchars($currentRole) ?>" readonly>
            <input type="hidden" name="role" value="<?= htmlspecialchars($currentRole) ?>">
            <div class="form-text fw-bold text-info"> Nemôžeš meniť vlastnú rolu.</div>
        <?php else: ?>
            <select id="role" name="role" class="form-select">
                <option value="atlet" <?= $currentRole === 'atlet' ? 'selected' : '' ?>>Atlet</option>
                <option value="trener" <?= $currentRole === 'trener' ? 'selected' : '' ?>>Tréner</option>
                <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="<?= $link->url('admin.users') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Späť
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg"></i> Uložiť zmeny
        </button>
    </div>
</form>
