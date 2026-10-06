<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
$db=conectarBaseDatos();
$db->exec('ALTER TABLE tarjetas_regalo MODIFY id_detalle_venta BIGINT UNSIGNED NULL DEFAULT NULL');
$db->exec(file_get_contents(__DIR__.'/../database/client-gift-cards.sql'));
$db->exec("INSERT IGNORE INTO tarjetas_regalo_clientes (id_tarjeta,id_cliente) SELECT t.id_tarjeta,cl.id_cliente FROM tarjetas_regalo t JOIN cuentas c ON c.correo=t.correo_destinatario AND c.estado='activo' JOIN clientes cl ON cl.id_cuenta=c.id_cuenta WHERE t.estado IN ('activa','pendiente')");
echo "Tarjetas sin detalle de venta habilitadas y regalos vinculados a sus destinatarios registrados. Relaciones existentes conservadas.\n";
