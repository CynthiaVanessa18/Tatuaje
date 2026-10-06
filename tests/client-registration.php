<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/ClientRegistration.php';
$db=conectarBaseDatos();
require __DIR__.'/temporary-store-promotions.php';
foreach (['roles','cuentas','clientes'] as $table) temporaryStoreTable($db,$table);
$db->exec("INSERT INTO roles (id_rol,nombre_rol,activo) VALUES (1,'cliente',1),(2,'administrador',1)");
$data=['usuario'=>'prueba','correo'=>'prueba@example.com','nombre'=>'Ana','apellidos'=>'Prueba','telefono'=>'','clave'=>'ClavePrueba123!','confirmar_clave'=>'ClavePrueba123!','id_rol'=>2];
function registrationCheck(bool $ok): void { if (!$ok) throw new RuntimeException('Falló comprobación de registro'); }
$id=ClientRegistration::register($db,$data);
$account=$db->query('SELECT * FROM cuentas WHERE id_cuenta='.$id)->fetch();
registrationCheck((int)$account['id_rol']===1 && $account['estado']==='activo' && password_verify($data['clave'],$account['contrasena_hash']));
registrationCheck((int)$db->query('SELECT COUNT(*) FROM clientes WHERE id_cuenta='.$id)->fetchColumn()===1);
foreach ([$data,array_replace($data,['usuario'=>'otro']),array_replace($data,['confirmar_clave'=>'distinta']),array_replace($data,['correo'=>'invalido']),array_replace($data,['clave'=>'corta'])] as $invalid) {
    try { ClientRegistration::register($db,$invalid); throw new RuntimeException('Aceptó registro inválido'); }
    catch (DomainException $e) {}
    registrationCheck(!$db->inTransaction());
}
registrationCheck((int)$db->query('SELECT COUNT(*) FROM cuentas')->fetchColumn()===1);
registrationCheck((int)$db->query('SELECT COUNT(*) FROM clientes')->fetchColumn()===1);
$db->exec('UPDATE roles SET activo=0 WHERE id_rol=1');
try { ClientRegistration::register($db,array_replace($data,['usuario'=>'nuevo','correo'=>'nuevo@example.com'])); throw new RuntimeException('Aceptó rol inactivo'); } catch (DomainException $e) {}
echo "OK: registro de cliente, contraseña protegida, perfil, duplicados, validación y rol fijo. Solo tablas temporales.\n";
