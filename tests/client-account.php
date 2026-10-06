<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/ClientAccount.php';
require __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();foreach (['roles','cuentas','clientes'] as $table) temporaryStoreTable($db,$table);
$db->exec("INSERT INTO roles (id_rol,nombre_rol,activo) VALUES (1,'cliente',1),(2,'administrador',1)");
$hash=password_hash('ClaveActual123!',PASSWORD_DEFAULT);
$db->prepare("INSERT INTO cuentas (id_cuenta,id_rol,usuario,correo,contrasena_hash,estado) VALUES (1,1,'ana','ana@example.com',?,'activo'),(2,1,'luis','luis@example.com',?,'activo'),(3,2,'admin','admin@example.com',?,'activo')")->execute([$hash,$hash,$hash]);
$db->exec("INSERT INTO clientes (id_cuenta,nombre,apellidos) VALUES (1,'Ana','Prueba'),(2,'Luis','Prueba')");
$service=new ClientAccount($db,1);$checks=0;
function selfCheck(bool $ok,string $message): void { global $checks;if (!$ok) throw new RuntimeException($message);++$checks; }
function selfReject(callable $action,string $message): void { try { $action(); } catch (DomainException $ex) { selfCheck(true,$message);return; } throw new RuntimeException($message); }
$data=['usuario'=>'ana','correo'=>'ana@example.com','nombre'=>'Ana editada','apellidos'=>'Prueba','telefono'=>'12345678'];
$service->save($data);
selfCheck($service->load()['nombre']==='Ana editada','Actualiza datos personales');
selfCheck(!array_key_exists('contrasena_hash',$service->load()),'No expone contraseña');
selfReject(fn()=>$service->save(array_replace($data,['correo'=>'nueva@example.com'])),'Cambio de correo exige contraseña actual');
selfReject(fn()=>$service->save(array_replace($data,['usuario'=>'otra'])),'Cambio de usuario exige contraseña actual');
selfReject(fn()=>$service->save(array_replace($data,['nueva_clave'=>'ClaveNueva456!','confirmar_clave'=>'ClaveNueva456!','clave_actual'=>'incorrecta'])),'Rechaza contraseña actual incorrecta');
selfReject(fn()=>$service->save(array_replace($data,['nueva_clave'=>'ClaveNueva456!','confirmar_clave'=>'otra','clave_actual'=>'ClaveActual123!'])),'Exige confirmación igual');
selfReject(fn()=>$service->save(array_replace($data,['nueva_clave'=>'corta','confirmar_clave'=>'corta','clave_actual'=>'ClaveActual123!'])),'Exige contraseña larga');
$service->save(array_replace($data,['nueva_clave'=>'ClaveNueva456!','confirmar_clave'=>'ClaveNueva456!','clave_actual'=>'ClaveActual123!','id_cuenta'=>2,'id_rol'=>2]));
selfCheck(password_verify('ClaveNueva456!',$db->query('SELECT contrasena_hash FROM cuentas WHERE id_cuenta=1')->fetchColumn()),'Cambia contraseña propia');
selfCheck(password_verify('ClaveActual123!',$db->query('SELECT contrasena_hash FROM cuentas WHERE id_cuenta=2')->fetchColumn()),'No cambia contraseña de otra cuenta');
selfCheck((int)$db->query('SELECT id_rol FROM cuentas WHERE id_cuenta=1')->fetchColumn()===1,'No cambia su rol');
selfReject(fn()=>$service->save(array_replace($data,['usuario'=>'luis','clave_actual'=>'ClaveNueva456!'])),'Rechaza usuario duplicado');
selfCheck($service->load()['usuario']==='ana','Duplicado revierte cambios');
$service->save(array_replace($data,['usuario'=>'ana_nueva','correo'=>'ana.nueva@example.com','clave_actual'=>'ClaveNueva456!']));
selfCheck($service->load()['usuario']==='ana_nueva' && $service->load()['correo']==='ana.nueva@example.com','Actualiza usuario y correo con contraseña');
selfReject(fn()=>(new ClientAccount($db,3))->load(),'Ruta de cliente no edita administrador');
$db->exec("UPDATE cuentas SET estado='bloqueado' WHERE id_cuenta=1");
selfReject(fn()=>$service->save(array_replace($data,['nombre'=>'Bloqueada'])),'Cuenta bloqueada no puede editar');
selfCheck(!$db->inTransaction(),'No quedan transacciones abiertas');
echo "OK: $checks comprobaciones de datos, contraseña actual, cambio de clave, duplicados y privacidad. Solo tablas temporales.\n";
