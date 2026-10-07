<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../config/database.php';
require __DIR__.'/../app/Services/BenefitsOverview.php';
require __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();
foreach (['promociones','promociones_reglas_tienda','grupos_clientes','promociones_productos','productos','promociones_categorias','categorias_productos','planes_membresia','membresias_configuracion','membresias_reglas','membresias_reglas_textos','tarjetas_regalo','movimientos_tarjetas_regalo'] as $table) temporaryStoreTable($db,$table);
$db->exec("INSERT INTO promociones (id_promocion,titulo,tipo_descuento,valor_descuento,fecha_inicio,fecha_fin,activo) VALUES
    (1,'Vigente','porcentaje',10,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY),1),
    (2,'Vencida','porcentaje',10,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 2 DAY),DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY),1),
    (3,'Futura','porcentaje',10,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 2 DAY),1),
    (4,'Inactiva','porcentaje',10,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY),0)");
$db->exec("INSERT INTO promociones_reglas_tienda (id_promocion,alcance,publico) VALUES (1,'tienda','todos'),(2,'tienda','todos'),(3,'tienda','todos'),(4,'tienda','todos')");
$db->exec("INSERT INTO planes_membresia (id_plan,nombre,precio,duracion_dias,activo) VALUES (1,'Activo',1000,30,1),(2,'Inactivo',1000,30,0)");
$db->exec("INSERT INTO membresias_configuracion (id_plan,nivel) VALUES (1,'esencial'),(2,'plus')");
$db->exec("INSERT INTO membresias_reglas (id_plan,codigo,valor) VALUES (1,'prioridad',1),(1,'mercancia_porcentaje',15),(2,'retoques',2)");
$card=$db->prepare("INSERT INTO tarjetas_regalo (id_tarjeta,codigo_hash,id_cliente_comprador,nombre_destinatario,correo_destinatario,monto_inicial,fecha_emision,fecha_vencimiento,estado) VALUES (?,UNHEX(SHA2(?,256)),1,'Prueba','prueba@example.com',1000,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 3 DAY),?,?)");
$dates=$db->query("SELECT DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY) AS vencida,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY) AS vigente")->fetch();
foreach ([[1,null,'activa'],[2,$dates['vencida'],'activa'],[3,null,'pendiente'],[4,null,'activa']] as [$id,$date,$state]) $card->execute([$id,'tarjeta-'.$id,$date,$state]);
$db->exec("INSERT INTO movimientos_tarjetas_regalo (id_tarjeta,tipo,monto,referencia) VALUES (1,'carga_inicial',1000,'carga-1'),(2,'carga_inicial',1000,'carga-2'),(3,'carga_inicial',1000,'carga-3')");
$service=new BenefitsOverview($db); $checks=0;
function benefitCheck(bool $ok,string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); ++$checks; }
$products=$service->listing('productos',1);
benefitCheck($products['counts']===['productos'=>1,'membresias'=>2,'tarjetas'=>1],'Cuenta solo promociones vigentes, planes activos y tarjetas utilizables');
benefitCheck($products['rows'][0]['titulo']==='Vigente','Excluye futuras, vencidas e inactivas');
$memberships=$service->listing('membresias',1);
benefitCheck(count($memberships['rows'])===2 && $memberships['rows'][0]['id_plan']==1,'Incluye reglas del plan activo');
$cards=$service->listing('tarjetas',999);
benefitCheck($cards['page']===1 && count($cards['rows'])===1 && $cards['rows'][0]['saldo']==='1000.00','Saldo y paginación sin incluir tarjetas vencidas, pendientes o sin saldo');
benefitCheck($service->listing('desconocido',0)['section']==='productos','Selección desconocida usa productos');
require __DIR__.'/../app/Views/admin-iconos.php';
function e(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
foreach (['productos','membresias','tarjetas'] as $section) {
    $benefitsOverview=$service->listing($section,1);
    ob_start(); require __DIR__.'/../app/Views/beneficios-resumen.php'; $html=ob_get_clean();
    benefitCheck(!str_contains($html,'<form') && substr_count($html,'aria-current="page"')===1,'Vista de consulta y selector activo');
    benefitCheck(str_contains($html,match($section) {'productos'=>'mode=edit&amp;id_promocion=1','membresias'=>'module=planes&amp;plan=1','tarjetas'=>'mode=view&amp;id_tarjeta=1'}),'Enlace al registro original');
}
echo "OK: $checks comprobaciones de beneficios, vigencia, saldo, selección y enlaces. Solo tablas temporales.\n";
