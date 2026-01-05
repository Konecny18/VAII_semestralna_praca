<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser|null $user */
/** @var \App\Models\Event[]|null $events */

// Pomocná premenná pre kontrolu admina
$isAdmin = $user && $user->isLoggedIn() && ($user->getIdentity()?->getRole() === 'admin');

$link = $link ?? null;
$asset = function(?string $path) use ($link) {
    if (empty($path)) return '';
    if ($link) return $link->asset($path);
    return '/' . ltrim($path, '/');
};
$url = function(string $route, array $params = []) use ($link) {
    if ($link) return $link->url($route, $params);
    $parts = explode('.', $route);
    $c = $parts[0] ?? 'home';
    $a = $parts[1] ?? 'index';
    $qs = '?c=' . $c . '&a=' . $a;
    if (!empty($params)) {
        $qs .= '&' . http_build_query($params);
    }
    return $qs;
};
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="display-5 fw-bold text-primary">Kalendár podujatí</h1>

        <?php if ($isAdmin): ?>
            <a href="<?= $link->url('event.add') ?>" class="btn btn-success shadow-sm">
                <i class="bi bi-plus-lg"></i> Nové podujatie
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($events)): ?>
        <div class="text-center p-5 bg-light rounded-3">
            <p class="lead text-muted">Momentálne neplánujeme žiadne akcie. Sledujte nás neskôr!</p>
        </div>
    <?php else: ?>
        <div class="list-group border-0">
            <?php foreach ($events as $event): ?>
                <div class="list-group-item p-0 mb-3 rounded-3 border shadow-sm bg-white">
                    <div class="row g-0 align-items-center">

                        <div class="col d-flex align-items-center p-4 event-toggle"
                             data-bs-toggle="modal"
                             data-bs-target="#eventModal<?= $event->getId() ?>"
                             style="cursor: pointer;">

                            <div class="text-center border-end pe-4 d-none d-md-block event-date">
                                <span class="d-block h4 mb-0 fw-bold text-dark"><?= date('d', strtotime($event->getDatumPodujatia())) ?></span>
                                <span class="d-block text-uppercase text-muted small"><?= date('M', strtotime($event->getDatumPodujatia())) ?></span>
                                <span class="badge bg-primary mt-2"><?= date('Y', strtotime($event->getDatumPodujatia())) ?></span>
                            </div>

                            <div class="ms-3">
                                <img src="<?= htmlspecialchars($asset($event->getPlagat() ?: 'images/tat_logo.png'), ENT_QUOTES, 'UTF-8') ?>"
                                     class="rounded shadow-sm event-thumb" alt="event" style="width: 60px; height: 60px; object-fit: cover;">
                            </div>

                            <div class="ms-3">
                                <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($event->getNazov()) ?></h5>
                                <small class="text-primary fw-semibold"><i class="bi bi-eye me-1"></i>Zobraziť detaily</small>
                            </div>
                        </div>

                        <div class="col-auto p-4 border-start bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($isAdmin): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-white btn-sm rounded-circle shadow-sm border event-dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                                onclick="event.stopPropagation();">
                                            <i class="bi bi-three-dots-vertical text-dark"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 event-dropdown-menu" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();">
                                            <li>
                                                <a class="dropdown-item py-2" href="<?= htmlspecialchars($url('event.edit', ['id' => $event->getId()])) ?>" onclick="event.stopPropagation();">
                                                    <i class="bi bi-pencil me-2 text-warning"></i> Upraviť
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item py-2 text-danger" onclick="event.stopPropagation(); if (confirm('Naozaj zmazať podujatie?')) { document.getElementById('delete-event-<?= $event->getId() ?>').submit(); }">
                                                    <i class="bi bi-trash me-2"></i> Zmazať
                                                </button>
                                                <form id="delete-event-<?= $event->getId() ?>" method="post" action="<?= htmlspecialchars($url('event.delete', ['id' => $event->getId()])) ?>" style="display:none;"></form>
                                            </li>
                                        </ul>
                                    </div>
                                <?php else: ?>
<!--                                toto je klikatelna sipka ked niesom prihlaseny ako admin-->
                                    <div style="cursor: pointer;"
                                         data-bs-toggle="modal"
                                         data-bs-target="#eventModal<?= $event->getId() ?>">
                                        <i class="bi bi-chevron-right text-muted h5 mb-0"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="eventModal<?= $event->getId() ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title fw-bold"><?= htmlspecialchars($event->getNazov()) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="container-fluid p-0">
                                    <div class="row g-0">
                                        <div class="col-lg-6">
                                            <img src="<?= htmlspecialchars($asset($event->getPlagat() ?: 'images/tat_logo.png'), ENT_QUOTES, 'UTF-8') ?>"
                                                 class="img-fluid h-100 w-100 event-modal-img" alt="plagat" style="object-fit: cover; min-height: 300px;">
                                        </div>
                                        <div class="col-lg-6 p-4">
                                            <div class="mb-4">
                                                <span class="badge bg-primary text-white mb-2">Podrobnosti preteku</span>
                                                <h3 class="fw-bold"><?= htmlspecialchars($event->getNazov()) ?></h3>
                                                <p class="text-muted"><i class="bi bi-calendar3 me-2"></i><?= date('d. m. Y', strtotime($event->getDatumPodujatia())) ?></p>
                                            </div>

                                            <div class="event-description mb-4 text-secondary">
                                                <?= nl2br(htmlspecialchars($event->getPopis())) ?>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <?php if ($event->getLinkPrihlasovanie()): ?>
                                                    <a href="<?= htmlspecialchars($event->getLinkPrihlasovanie()) ?>" target="_blank" class="btn btn-primary btn-lg shadow-sm">
                                                        <i class="bi bi-check2-circle me-2"></i>Registrovať sa
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($event->getDokumentPropozicie()): ?>
                                                    <a href="<?= htmlspecialchars($asset($event->getDokumentPropozicie()), ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-danger">
                                                        <i class="bi bi-file-earmark-pdf me-2"></i>Propozície (PDF)
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Zavrieť</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>