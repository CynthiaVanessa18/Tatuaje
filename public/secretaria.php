<?php
require_once __DIR__ . '/../app/bootstrap.php';
$requiredRole = 'secretaria';
try {
    require __DIR__ . '/../app/Controllers/RoleController.php';
    require __DIR__ . '/../app/Views/role-home.php';
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(503);
    require __DIR__ . '/../app/Views/unavailable.php';
}
