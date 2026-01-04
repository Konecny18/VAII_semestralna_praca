<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser|null $user */
/** @var array $errors */
/** @var \App\Models\Event $event */

// Provide helpers if not present
$link = $link ?? null;
$asset = function(?string $p) use ($link) { if ($link) return $link->asset($p); return '/' . ltrim($p ?? '', '/'); };
$url = function(string $r, array $p = []) use ($link) { if ($link) return $link->url($r, $p); $parts = explode('.', $r); return '?c=' . ($parts[0] ?? 'home') . '&a=' . ($parts[1] ?? 'index') . (empty($p) ? '' : '&' . http_build_query($p)); };

?>

<div class="container">
    <h1>Upraviť podujatie</h1>
    <?php include __DIR__ . DIRECTORY_SEPARATOR . 'form.view.php'; ?>
</div>

