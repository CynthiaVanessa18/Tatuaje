<?php
declare(strict_types=1);
require_once __DIR__.'/../Models/CrudRepository.php';
require_once __DIR__.'/../Services/CrudService.php';
require_once __DIR__.'/../Services/ProductImageUpload.php';

$modules=require __DIR__.'/../../config/modules.php';
$moduleId=is_string($_GET['module']??null)?$_GET['module']:'categorias';
if (!isset($modules[$moduleId])) { http_response_code(404); exit('Módulo no encontrado.'); }
$module=$modules[$moduleId];
$repo=new CrudRepository(conectarBaseDatos(),$module);
$columns=$repo->columns(); $refs=$repo->references(); $error=null; $record=null;
$notice=$_SESSION['notice']??null; unset($_SESSION['notice']);
$mode=is_string($_GET['mode']??null)?$_GET['mode']:'list';
$uploadedImage=null;
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verifyCsrf();
        $action=$_POST['action']??'';
        if (!in_array($action,['create','update','delete'],true)) throw new DomainException('Acción inválida.');
        $key=$action==='create'?null:$repo->key(is_array($_POST['key']??null)?$_POST['key']:[]);
        if ($moduleId==='imagenes' && $action!=='delete') {
            $existing=$key ? $repo->find($key) : null;
            $uploadedImage=ProductImageUpload::save(is_array($_FILES['imagen_archivo']??null)?$_FILES['imagen_archivo']:null);
            $_POST['imagen_url']=$uploadedImage ?? ($existing['imagen_url'] ?? '');
            if ($_POST['imagen_url']==='') throw new DomainException('Selecciona una imagen desde tus archivos.');
        }
        if ($moduleId==='productos' && $action!=='delete') {
            $uploadedImage=ProductImageUpload::save(is_array($_FILES['imagen_archivo']??null)?$_FILES['imagen_archivo']:null);
            $_POST['_product_image']=$uploadedImage;
        }
        $_SESSION['notice']=(new CrudService($repo))->execute($action,$_POST,$key);
        header('Location: administrador.php?module='.urlencode($moduleId)); exit;
    }
    if (in_array($mode,['edit','view'],true)) $record=$repo->find($repo->key($_GET));
} catch (DomainException $ex) { $error=$ex->getMessage(); }
catch (PDOException $ex) {
    error_log($ex->getMessage());
    $error=($ex->errorInfo[1]??0)===1062?'Ya existe un registro con esos datos únicos.':(($ex->errorInfo[1]??0)===1451?'No se puede eliminar: otros registros dependen de este. Puedes desactivarlo si tiene estado activo.':'No se pudo guardar. Revisa los valores y los registros relacionados.');
}
if ($error && $uploadedImage!==null) {
    ProductImageUpload::discard($uploadedImage);
    unset($_POST['imagen_url']);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $error && ($_POST['action']??'')!=='delete') {
    $mode=($_POST['action']??'')==='create'?'create':'edit'; $record=$_POST;
}
$search=is_string($_GET['q']??null)?trim($_GET['q']):'';
$filterable=array_filter($columns,fn($c)=>isset($refs[$c['Field']]) || str_starts_with($c['Type'],'enum(') || $c['Field']==='activo');
$filters=array_filter(array_intersect_key(is_array($_GET['filter']??null)?$_GET['filter']:[],$filterable),'is_string');
$list=$repo->listing($search,$filters,max(1,(int)($_GET['page']??1)));
function choices(array $c, array $refs): ?array {
    if (isset($refs[$c['Field']])) return array_column($refs[$c['Field']],'nombre','id');
    if ($c['Type']==='tinyint(1)') return ['0'=>'No','1'=>'Sí'];
    if (str_starts_with($c['Type'],'enum(')) { preg_match_all("/'([^']*)'/",$c['Type'],$m); return array_combine($m[1],array_map('label',$m[1])); }
    return null;
}
function displayValue(string $name, array $column, array $refs, mixed $value): string {
    if ($value===null || $value==='') return '—';
    $options=choices($column,$refs);
    if (str_starts_with($name,'id_')) return (string)($options[$value] ?? 'No disponible');
    return (string)($options[$value] ?? $value);
}

$tiendaIds=['productos','ventas','categorias_productos','imagenes','detalle'];
$esTienda=in_array($moduleId,$tiendaIds,true);
$moduloSeleccionado=$moduleId;
$seccionesTienda=[];
$resumenTienda=[];
$portadas=[];
if ($esTienda) {
    foreach ($tiendaIds as $id) {
        if ($id===$moduloSeleccionado) {
            $seccionesTienda[]=compact('moduleId','module','repo','columns','refs','mode','record','search','filterable','filters','list');
            continue;
        }
        $sectionRepo=new CrudRepository(conectarBaseDatos(),$modules[$id]);
        $campos=$sectionRepo->columns();
        $referencias=$sectionRepo->references();
        $seccionesTienda[]=[
            'moduleId'=>$id,'module'=>$modules[$id],'repo'=>$sectionRepo,
            'columns'=>$campos,'refs'=>$referencias,'mode'=>'list','record'=>null,
            'search'=>'','filters'=>[],
            'filterable'=>array_filter($campos,fn($c)=>isset($referencias[$c['Field']]) || str_starts_with($c['Type'],'enum(') || $c['Field']==='activo'),
            'list'=>$sectionRepo->listing('',[],1),
        ];
    }
    $db=conectarBaseDatos();
    $resumenTienda['productos']=(int)$db->query('SELECT COUNT(*) FROM productos WHERE activo=1')->fetchColumn();
    $resumenTienda['pendientes']=(int)$db->query("SELECT COUNT(*) FROM ventas WHERE estado='pendiente'")->fetchColumn();
    $resumenTienda['stock']=(int)$db->query('SELECT COUNT(*) FROM productos WHERE activo=1 AND stock_actual<=stock_minimo')->fetchColumn();
    $resumenTienda['ventas']=$db->query("SELECT moneda,SUM(total) AS importe FROM ventas WHERE estado IN ('confirmada','completada') AND fecha>=DATE_FORMAT(UTC_TIMESTAMP(),'%Y-%m-01') AND fecha<DATE_FORMAT(UTC_TIMESTAMP()+INTERVAL 1 MONTH,'%Y-%m-01') GROUP BY moneda")->fetchAll();
    foreach ($db->query('SELECT id_producto,imagen_url FROM imagenes_productos ORDER BY es_portada DESC,orden,id_imagen') as $imagen) {
        $url=trim($imagen['imagen_url']);
        if (($url!=='' && !preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i',$url)) || preg_match('~^https?://~i',$url)) {
            $portadas[$imagen['id_producto']]??=$url;
        }
    }
}
