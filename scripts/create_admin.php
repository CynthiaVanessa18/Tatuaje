<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
$username=$argv[1]??''; $email=$argv[2]??''; $password=getenv('ADMIN_PASSWORD')?:'';
if (!$username || strlen($username)>80 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12) {
    fwrite(STDERR,"Define ADMIN_PASSWORD (mínimo 12 caracteres) y ejecuta php scripts/create_admin.php usuario correo\n"); exit(1);
}
try {
    $db=conectarBaseDatos();
    $q=$db->prepare("INSERT INTO cuentas (id_rol,usuario,correo,contrasena_hash,estado) SELECT id_rol,?,?,?,'activo' FROM roles WHERE nombre_rol='administrador' AND activo=1");
    $q->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
    if ($q->rowCount()!==1) throw new RuntimeException('Importa primero el esquema y sus roles iniciales.');
    echo "Administrador creado.\n";
} catch (Throwable $e) { fwrite(STDERR,"No se pudo crear el administrador. Comprueba conexión, roles y que usuario/correo no existan.\n"); exit(1); }
