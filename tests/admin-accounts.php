<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/AdminAccounts.php';
require __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();
foreach (['roles','cuentas','clientes'] as $table) temporaryStoreTable($db,$table);
$db->exec("INSERT INTO roles (id_rol,nombre_rol,activo) VALUES (1,'administrador',1),(2,'cliente',1),(3,'artista',1)");
$hash=password_hash('PruebaSegura123!',PASSWORD_DEFAULT);
$db->prepare("INSERT INTO cuentas (id_cuenta,id_rol,usuario,correo,contrasena_hash,estado) VALUES (1,1,'admin','admin@example.com',?,'activo'),(2,3,'artista','artista@example.com',?,'activo')")->execute([$hash,$hash]);
$service=new AdminAccounts($db,1);$checks=0;
function accountCheck(bool $ok,string $message): void { global $checks; if (!$ok) throw new RuntimeException($message);++$checks; }
function accountReject(callable $action,string $message): void { try { $action(); } catch (DomainException $e) { accountCheck(true,$message);return; } throw new RuntimeException($message); }
$data=['usuario'=>'cliente','correo'=>'cliente@example.com','rol'=>'cliente','estado'=>'activo','nombre'=>'Ana','apellidos'=>'Prueba','telefono'=>'12345678','clave'=>'PruebaSegura123!','confirmar_clave'=>'PruebaSegura123!'];
$id=$service->save($data);
accountCheck($service->find($id)['nombre']==='Ana','Crea perfil de cliente');
accountCheck(password_verify($data['clave'],$db->query('SELECT contrasena_hash FROM cuentas WHERE id_cuenta='.$id)->fetchColumn()),'Protege contraseña');
$service->save(array_replace($data,['usuario'=>'editado','clave'=>'','confirmar_clave'=>'','estado'=>'bloqueado']),$id);
accountCheck($service->find($id)['estado']==='bloqueado','Bloquea cuenta');
accountCheck(password_verify($data['clave'],$db->query('SELECT contrasena_hash FROM cuentas WHERE id_cuenta='.$id)->fetchColumn()),'Edición vacía conserva contraseña');
$other=$service->save(array_replace($data,['usuario'=>'otroadmin','correo'=>'otro@example.com','rol'=>'administrador','nombre'=>'','apellidos'=>'']));
accountCheck($service->find($other)['rol']==='administrador','Crea administrador');
accountCheck($service->listing('','cliente','bloqueado',1)['total']===1,'Filtros');
accountCheck($service->listing('editado','','',1)['total']===1,'Búsqueda');
accountCheck($service->listing('','','',1)['total']===3,'Lista solo dos roles');
$summary=$service->summary();
accountCheck((int)$summary['total']===3 && (int)$summary['activos']===2 && (int)$summary['bloqueados']===1,'Resumen cuenta estados sin incluir artistas');
accountCheck((int)$summary['clientes']===1 && (int)$summary['administradores']===2 && (int)$summary['inactivos']===0,'Resumen cuenta roles y estados vacíos');
accountReject(fn()=>(new AdminAccounts($db,$id))->summary(),'Cliente no consulta dashboard administrativo');
accountCheck(!array_key_exists('contrasena_hash',$service->find($id)),'No expone hash');
accountReject(fn()=>$service->find(2),'No administra otros roles');
accountReject(fn()=>$service->save(array_replace($data,['rol'=>'artista'])),'Rechaza rol no permitido');
accountReject(fn()=>$service->save(array_replace($data,['usuario'=>'nuevo','correo'=>'cliente@example.com'])),'Rechaza duplicados');
accountReject(fn()=>$service->save(array_replace($data,['clave'=>'corta'])),'Rechaza contraseña débil');
accountReject(fn()=>$service->save(array_replace($data,['correo'=>'invalido'])),'Rechaza correo inválido');
accountReject(fn()=>$service->save(array_replace($data,['nombre'=>''])),'Exige nombre cliente');
accountReject(fn()=>$service->save(array_replace($data,['usuario'=>'admin','correo'=>'admin@example.com','rol'=>'cliente']),1),'No se quita su propio acceso');
accountReject(fn()=>(new AdminAccounts($db,$id))->save($data),'Cliente no administra cuentas');
$oldHash=$db->query('SELECT contrasena_hash FROM cuentas WHERE id_cuenta='.$id)->fetchColumn();
$service->save(array_replace($data,['usuario'=>'editado','clave'=>'NuevaClaveSegura456!','confirmar_clave'=>'NuevaClaveSegura456!']),$id);
accountCheck(password_verify('NuevaClaveSegura456!',$db->query('SELECT contrasena_hash FROM cuentas WHERE id_cuenta='.$id)->fetchColumn()),'Actualiza contraseña');
$service->save(array_replace($data,['usuario'=>'otroadmin','correo'=>'otro@example.com','rol'=>'cliente','clave'=>'','confirmar_clave'=>'']),$other);
accountCheck($service->find($other)['rol']==='cliente','Cambio a cliente crea perfil');
accountCheck((int)$db->query('SELECT COUNT(*) FROM cuentas')->fetchColumn()===4 && !$db->inTransaction(),'Errores no dejan cuentas parciales');
echo "OK: $checks comprobaciones de creación, edición, roles, perfiles, estados, contraseñas y acceso. Solo tablas temporales.\n";
