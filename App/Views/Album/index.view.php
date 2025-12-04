<?php

/** @var Album[] $albums */
/** @var Framework\Support\LinkGenerator $link */
/** @var array $formErrors */
/** @var IAuthenticator $auth */

use App\Models\Album;
use Framework\Core\IAuthenticator;

?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3>Albumy</h3>
            <a href="<?= $link->url('album.add') ?>" class="btn btn-success">Vytvor album</a>
        </div>
    </div>

    <!-- Use row-cols utilities so albums wrap responsively -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 justify-content-center">

        <?php if (empty($albums)): ?>
            <div class="col-12 text-center my-4">Žiadne albumy.</div>
        <?php else: ?>
            <?php foreach ($albums as $album): ?>
                <div class="col">
                    <div class="border album d-flex flex-column position-relative h-100">
                        <div>
                            <?php $picture = $album->getPicture(); ?>
                            <?php if ($picture !== ''): ?>
                                <!-- Use .card-img for consistent gallery sizing -->
                                <img src="<?= $link->asset($picture) ?>" class="card-img" alt="Album image">
                            <?php else: ?>
                                <img src="<?= $link->asset('images/tat_logo.png') ?>" class="card-img" alt="Album placeholder">
                            <?php endif; ?>
                        </div>
                        <div class="m-2">
                            <strong><?= $album->getText() ?></strong>
                        </div>
                        <div class="m-2 d-flex gap-2 justify-content-end mt-auto">
                            <a href="<?= $link->url('post.index', ['albumId' => $album->getId()]) ?>" class="btn btn-primary">Zobraziť</a>

                            <span class="flex-grow-1"></span>
                            <?php
                            // Safely determine whether current user is an admin.
                            $user = null;
                            try {
                                $user = $auth->getUser();
                            } catch (Throwable $e) {
                                // ignore - treat as not logged in
                                $user = null;
                            }

                            $showAdminButtons = false;
                            if ($user) {
                                // Prefer explicit isAdmin() method if present
                                if (method_exists($user, 'isAdmin')) {
                                    $showAdminButtons = (bool)$user->isAdmin();
                                }
                                // Fallback to role-based check
                                elseif (method_exists($user, 'getRole')) {
                                    $showAdminButtons = (string)$user->getRole() === 'admin';
                                }
                                // Fallback to name-based check (rare)
                                elseif (method_exists($user, 'getName')) {
                                    $showAdminButtons = (string)$user->getName() === 'admin';
                                }
                            }
                            ?>

<!--                            --><?php //if ($showAdminButtons): ?>
                                <a href="<?= $link->url('album.edit', ['id' => $album->getId()]) ?>" class="btn btn-warning">Upraviť</a>
                                <a href="<?= $link->url('album.delete', ['id' => $album->getId()]) ?>" class="btn btn-danger" onclick="return confirm('Naozaj zmazať album?')">Zmazať</a>
<!--                            --><?php //endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
