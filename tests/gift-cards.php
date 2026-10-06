<?php
declare(strict_types=1);
require_once __DIR__.'/../app/Core/bootstrap.php';
require_once __DIR__.'/../app/Models/CrudRepository.php';
require_once __DIR__.'/../app/Services/CrudService.php';
require_once __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();
foreach (['tarjetas_regalo','movimientos_tarjetas_regalo','tarjetas_regalo_clientes'] as $table) temporaryStoreTable($db,$table);
$modules=require __DIR__.'/../config/modules.php';
$repo=new CrudRepository($db,$modules['tarjetas']); $service=new CrudService($repo);
if (isset($repo->columns()['id_detalle_venta'])) throw new RuntimeException('El campo de venta sigue visible.');
$input=['id_cliente_comprador'=>'1','nombre_destinatario'=>'Persona de prueba','correo_destinatario'=>'prueba@example.com','mensaje'=>'Regalo','monto_inicial'=>'5000','moneda'=>'CRC','fecha_vencimiento'=>'','estado'=>'pendiente'];
$message=$service->execute('create',$input,null);
$card=$db->query('SELECT * FROM tarjetas_regalo')->fetch();
if (!preg_match('/código: ([A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4})/u',$message,$match) || hash('sha256',str_replace('-','',$match[1]),true)!==$card['codigo_hash']) throw new RuntimeException('Código corto o hash incorrecto.');
if ($card['id_detalle_venta']!==null || $card['monto_inicial']!=='5000.00' || strlen($card['codigo_hash'])!==32) throw new RuntimeException('Tarjeta independiente incorrecta.');
if ($db->query('SELECT monto FROM movimientos_tarjetas_regalo')->fetchColumn()!=='5000.00') throw new RuntimeException('Falta carga inicial.');
$key=['id_tarjeta'=>$card['id_tarjeta']];
$input['estado']='activa'; $service->execute('update',$input,$key);
if ($repo->find($key)['estado']!=='activa') throw new RuntimeException('No se pudo activar.');
$db->exec('UPDATE tarjetas_regalo SET id_detalle_venta=123');
$input['mensaje']='Actualizado'; $service->execute('update',$input,$key);
if ((int)$repo->find($key)['id_detalle_venta']!==123) throw new RuntimeException('Se perdió la relación anterior.');
foreach ([['monto_inicial'=>'0'],['monto_inicial'=>'6000'],['estado'=>'agotada'],['fecha_vencimiento'=>'2000-01-01T00:00']] as $bad) {
    try { $service->execute('update',array_replace($input,$bad),$key); throw new RuntimeException('Valor inválido aceptado.'); } catch (DomainException $ex) {}
}
try { $service->execute('delete',[],$key); throw new RuntimeException('Historial eliminado.'); } catch (DomainException $ex) {}
echo "OK: creación sin venta, carga inicial, activación, relación anterior y validaciones. Solo tablas temporales.\n";
