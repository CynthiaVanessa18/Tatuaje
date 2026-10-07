<?php
declare(strict_types=1);
require_once __DIR__.'/../app/Core/bootstrap.php';
require_once __DIR__.'/../app/Services/MembershipPlans.php';
require_once __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();
foreach (['planes_membresia','membresias_configuracion','membresias_reglas','membresias_reglas_textos'] as $table) temporaryStoreTable($db,$table);
$db->exec("INSERT INTO planes_membresia (id_plan,nombre,precio,duracion_dias,activo) VALUES (1,'Prueba',0,30,0)");
$db->exec("INSERT INTO membresias_configuracion (id_plan,nivel) VALUES (1,'esencial')");
$service=new MembershipPlans($db); $checks=0;
function membershipCheck(bool $ok): void { global $checks; if (!$ok) throw new RuntimeException('Falló una comprobación de membresías.'); $checks++; }
$data=['id_plan'=>'1','nombre'=>'Plan probado','descripcion'=>'Descripción','modalidad'=>'ambas','activo'=>'1','cuota_mensual'=>'1000.50','cuota_anual'=>'10000.25','reglas'=>['prioridad','retoques','mercancia_porcentaje','kit_regalo'],'valores'=>['retoques'=>'2','mercancia_porcentaje'=>'15','kit_regalo'=>'1']];
$service->save($data); $plan=$service->plans(true)[0];
membershipCheck($plan['cuota_mensual']==='1000.50' && $plan['cuota_anual']==='10000.25');
membershipCheck(count($plan['reglas'])===4 && $plan['precio']==='1000.50' && $plan['duracion_dias']==30);
$data['textos']=['retoques'=>['nombre'=>'Retoque de cortesía','descripcion'=>'Una revisión previa antes del retoque.']];
$service->save($data); $plan=$service->plans()[0];
membershipCheck($plan['textos']['retoques']['nombre']==='Retoque de cortesía' && $plan['textos']['retoques']['descripcion']==='Una revisión previa antes del retoque.');
membershipCheck(MembershipPlans::describe('retoques','2.00',$plan['textos']['retoques']['nombre'])==='Retoque de cortesía: 2 sesión(es) por período contratado');
$db->exec("INSERT INTO planes_membresia (id_plan,nombre,precio,duracion_dias,activo) VALUES (2,'Otro plan',1000,30,0)");
$db->exec("INSERT INTO membresias_configuracion (id_plan,nivel) VALUES (2,'plus')");
membershipCheck($service->texts(2)===[]);
foreach ([['retoques'=>['nombre'=>'','descripcion'=>'Prueba']],['retoques'=>['nombre'=>str_repeat('a',121),'descripcion'=>'Prueba']],['retoques'=>['nombre'=>'Prueba','descripcion'=>str_repeat('a',1001)]],['inventada'=>['nombre'=>'Prueba','descripcion'=>'Prueba']]] as $badTexts) {
    try { $service->save(array_replace($data,['textos'=>$badTexts])); throw new RuntimeException('Texto inválido aceptado.'); } catch (DomainException $ex) { $checks++; }
}
unset($data['textos']);
$service->save($data);
membershipCheck($service->texts(1)['retoques']['nombre']==='Retoque de cortesía');
$data['modalidad']='anual'; $data['reglas']=['color','brillo']; $data['valores']=['color'=>'1','brillo'=>'2']; $service->save($data); $plan=$service->plans()[0];
membershipCheck($plan['precio']==='10000.25' && $plan['duracion_dias']==365 && count($plan['reglas'])===2);
foreach ([['reglas'=>['inventada']],['cuota_anual'=>'0'],['cuota_anual'=>'-1'],['reglas'=>['retoques'],'valores'=>['retoques'=>'1.5']],['reglas'=>['mercancia_porcentaje'],'valores'=>['mercancia_porcentaje'=>'101']],['reglas'=>[]],['id_plan'=>'500']] as $bad) {
    try { $service->save(array_replace($data,$bad)); throw new RuntimeException('Entrada inválida aceptada.'); } catch (DomainException $ex) { $checks++; }
}
membershipCheck(count($service->plans()[0]['reglas'])===2);
$data['activo']='0'; $service->save($data); membershipCheck(!$service->plans(true));
echo "OK: $checks comprobaciones de cuotas, modalidades, beneficios, validación y conservación. Solo tablas temporales.\n";
