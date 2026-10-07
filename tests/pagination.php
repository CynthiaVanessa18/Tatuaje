<?php
declare(strict_types=1);
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/RatingModeration.php';
require __DIR__.'/../app/Models/CrudRepository.php';
require __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();
foreach (['auditoria_sistema','cuentas','categorias_tatuajes'] as $table) temporaryStoreTable($db,$table);
$insert=$db->prepare("INSERT INTO auditoria_sistema (evento,entidad,id_entidad,detalle) VALUES ('moderacion_calificacion','calificaciones_artistas',?,?)");
for ($i=1;$i<=45;$i++) $insert->execute(['7',json_encode(['motivo'=>'Motivo '.$i])]);
$insert->execute(['8','{}']);
$checks=0;
function paginationCheck(bool $ok,string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); ++$checks; }
$moderator=new RatingModeration($db);
$first=$moderator->historyListing(7,1);$second=$moderator->historyListing(7,2);$last=$moderator->historyListing(7,999);
paginationCheck($first['total']===45 && count($first['rows'])===20 && count($second['rows'])===20 && count($last['rows'])===5 && $last['page']===3,'Historial completo en tres páginas sin incluir otra calificación');
paginationCheck($first['rows'][0]['detalle']!==$second['rows'][0]['detalle'],'Páginas diferentes');
paginationCheck(count($moderator->history(7))===20,'Compatibilidad del historial');
for ($i=1;$i<=45;$i++) $db->prepare('INSERT INTO categorias_tatuajes (nombre,activo) VALUES (?,1)')->execute(['Categoría '.$i]);
$modules=require __DIR__.'/../config/modules.php';$repo=new CrudRepository($db,$modules['categorias']);
$result=$repo->listing('',[],999);
paginationCheck($result['total']===45 && $result['page']===3 && count($result['rows'])===5,'Paginación de módulos');
ob_start(); renderPagination(45,2,['module'=>'categorias','q'=>'tinta & arte','filter'=>['activo'=>'1']]); $html=ob_get_clean();
paginationCheck(substr_count($html,'aria-current="page"')===1 && str_contains($html,'q=tinta+%26+arte') && str_contains($html,'filter%5Bactivo%5D=1'),'Conserva búsqueda y filtros');
ob_start(); renderPagination(45,2,['module'=>'calificaciones','mode'=>'view','id_calificacion'=>7],'history_page');$html=ob_get_clean();
paginationCheck(str_contains($html,'history_page=3') && str_contains($html,'id_calificacion=7'),'Página independiente para historial');
echo "OK: $checks comprobaciones de paginación, historial completo y filtros. Solo tablas temporales.\n";
