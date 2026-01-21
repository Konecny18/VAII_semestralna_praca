<?php
/** @var Framework\Support\LinkGenerator $link */

// Zabezpečenie, že premenná $user vždy existuje
// Controller ju nemusí poslať v každom prípade
if (!isset($user)) {
    $user = null;
}

// Získanie a pretypovanie ID upravovaného používateľa
// Ochrana pred neplatnými vstupmi
$uid = (int)($userData['id'] ?? 0);
?>

<!--
Formulár na úpravu používateľa
method="post" – odoslanie dát na server
action – URL pre spracovanie formulára
card – Bootstrap karta (vizuálny obal)
shadow-sm – jemný tieň
p-4 – vnútorné odsadenie
-->
<form method="post" action="<?= $link->url('admin.update') ?>" class="card shadow-sm p-4">

    <!--
   CSRF token – ochrana proti CSRF útokom
   -->
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
    <!--
    Skryté ID používateľa, ktorý sa upravuje
    -->
    <input type="hidden" name="id" value="<?= $uid ?>">

<!--    spodny margin medzi polami-->
    <div class="mb-3">
        <!--
        form-label – Bootstrap štýl pre label
        fw-bold – tučný text
        -->
        <label for="meno" class="form-label fw-bold">Meno</label>
        <!--
        form-control – Bootstrap štýl pre input
        required – povinné pole
        -->
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
        <!--
        type="email" – HTML5 validácia emailu
        -->
        <input id="email" name="email" type="email" class="form-control"
               value="<?= htmlspecialchars((string)($userData['email'] ?? '')) ?>" required>
    </div>

    <div class="mb-3">
        <label for="role" class="form-label fw-bold">Rola</label>
        <?php
        // Aktuálna rola používateľa
        $currentRole = $userData['rola'] ?? '';
        // Kontrola, či si používateľ upravuje vlastný účet
        $isEditingSelf = ((int)($user?->getIdentity()?->getId() ?? 0) === (int)$uid);
        ?>

        <!--
            Ak si používateľ upravuje vlastný účet,
            zmena roly nie je povolená (bezpečnostné pravidlo)
            -->
        <?php if ($isEditingSelf): ?>
<!--        pokial chce sam sebe menit rolu, nemoze to urobit lebo readonly-->
            <input id="role" type="text" class="form-control bg-light" value="<?= htmlspecialchars($currentRole) ?>" readonly>
            <!--
                Skrytý input zabezpečí, že rola sa odošle nezmenená
                -->
            <input type="hidden" name="role" value="<?= htmlspecialchars($currentRole) ?>">
            <!--
                form-text – pomocný text pod poľom
                text-info – modré informačné zvýraznenie
                -->
            <div class="form-text fw-bold text-info"> Nemôžeš meniť vlastnú rolu.</div>
        <?php else: ?>
            <!--
                form-select – Bootstrap štýlovaný select box
                -->
            <select id="role" name="role" class="form-select">
                <option value="atlet" <?= $currentRole === 'atlet' ? 'selected' : '' ?>>Atlet</option>
                <option value="trener" <?= $currentRole === 'trener' ? 'selected' : '' ?>>Tréner</option>
                <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        <?php endif; ?>
    </div>

    <!--
    d-flex – flexbox rozloženie
    justify-content-between – prvky na opačných stranách
    align-items-center – vertikálne zarovnanie
    mt-4 – horný margin
    -->
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="<?= $link->url('admin.users') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Späť
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg"></i> Uložiť zmeny
        </button>
    </div>
</form>
