<?php
declare(strict_types=1);
$query = $_GET;
$query['section'] = $query['section'] ?? 'citas';
header('Location: ../index.php?' . http_build_query($query), true, 307);
exit;
