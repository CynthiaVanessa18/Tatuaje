<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';

$db = conectarBaseDatos();
$db->exec(file_get_contents(__DIR__ . '/../database/imagenes_tatuajes.sql'));
echo "Tabla de imágenes de tatuajes disponible. Datos existentes conservados.\n";
