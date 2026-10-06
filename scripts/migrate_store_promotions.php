<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
$db = conectarBaseDatos();
foreach (['clientes','categorias_productos','productos','promociones','ventas'] as $table) {
    $db->query("SELECT 1 FROM `$table` LIMIT 1");
}
$db->exec(file_get_contents(__DIR__.'/../database/promociones_tienda.sql'));
echo "Relaciones de promociones de tienda instaladas. Datos existentes conservados.\n";
