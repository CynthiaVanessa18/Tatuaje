<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../config/database.php';
conectarBaseDatos()->exec(file_get_contents(__DIR__.'/../database/membresias_textos.sql'));
echo "Edición de textos de membresías disponible. Datos existentes conservados.\n";
