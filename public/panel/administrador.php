<?php
require_once __DIR__.'/../../app/Core/bootstrap.php';
try {
    requireAdmin();
    require __DIR__.'/../../app/Controllers/PanelController.php';
    require __DIR__.'/../../app/Views/panel.php';
} catch (PDOException $ex) {
    error_log($ex->getMessage()); http_response_code(503);
    $connectionError=true; require __DIR__.'/../../app/Views/unavailable.php';
}
