<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';
$account = currentAccount();
require __DIR__ . '/../../app/Controllers/PublicArtistsController.php';
require __DIR__ . '/../../app/Views/artistas/index.php';
