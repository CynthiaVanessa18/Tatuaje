<?php
declare(strict_types=1);
require_once __DIR__.'/../Models/CrudRepository.php';
require_once __DIR__.'/../Services/CrudService.php';

$modules=require __DIR__.'/../../config/modules.php';
$moduleId=is_string($_GET['module']??null)?$_GET['module']:'categorias';
if (!isset($modules[$moduleId])) { http_response_code(404); exit('Módulo no encontrado.'); }
$module=$modules[$moduleId];
$repo=new CrudRepository(conectarBaseDatos(),$module);
$columns=$repo->columns(); $refs=$repo->references(); $error=null; $record=null;
$notice=$_SESSION['notice']??null; unset($_SESSION['notice']);
$mode=is_string($_GET['mode']??null)?$_GET['mode']:'list';
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verifyCsrf();
        $action=$_POST['action']??'';
        if (!in_array($action,['create','update','delete'],true)) throw new DomainException('Acción inválida.');
        $key=$action==='create'?null:$repo->key(is_array($_POST['key']??null)?$_POST['key']:[]);
        $_SESSION['notice']=(new CrudService($repo))->execute($action,$_POST,$key);
        header('Location: index.php?module='.urlencode($moduleId)); exit;
    }
    if (in_array($mode,['edit','view'],true)) $record=$repo->find($repo->key($_GET));
} catch (DomainException $ex) { $error=$ex->getMessage(); }
catch (PDOException $ex) {
    error_log($ex->getMessage());
    $error=($ex->errorInfo[1]??0)===1062?'Ya existe un registro con esos datos únicos.':(($ex->errorInfo[1]??0)===1451?'No se puede eliminar: otros registros dependen de este. Puedes desactivarlo si tiene estado activo.':'No se pudo guardar. Revisa los valores y los registros relacionados.');
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
