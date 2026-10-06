<?php
declare(strict_types=1);
require_once __DIR__.'/../Services/StorePromotionAdmin.php';
$promotionAdmin = new StorePromotionAdmin(conectarBaseDatos());
$promotionForm = [
    'titulo'=>'', 'descripcion'=>'', 'tipo_descuento'=>'porcentaje', 'valor_descuento'=>'',
    'alcance'=>'tienda', 'publico'=>'todos', 'id_grupo'=>'', 'productos'=>[], 'categorias'=>[],
    'regla_minimo'=>'siempre', 'minimo_compra'=>'0.00', 'activo'=>'1',
    'fecha_inicio'=>(new DateTimeImmutable('now',new DateTimeZone('America/Costa_Rica')))->format('Y-m-d\TH:i'),
    'fecha_fin'=>'',
];
$promotionId = null;
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verifyCsrf();
        $action = $_POST['action'] ?? '';
        if (!in_array($action,['create','update','activate','deactivate'],true)) throw new DomainException('Acción inválida.');
        if ($action!=='create') {
            $promotionId = filter_var($_POST['id_promocion'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if (!$promotionId) throw new DomainException('Promoción inválida.');
        }
        if (in_array($action,['activate','deactivate'],true)) {
            $promotionAdmin->setActive($promotionId,$action==='activate');
            $_SESSION['notice'] = $action==='activate' ? 'Promoción activada. Se aplicará durante su vigencia.' : 'Promoción desactivada.';
        } else {
            $promotionAdmin->save($_POST,$promotionId);
            $_SESSION['notice'] = 'Promoción guardada. El cliente verá la oferta cuando cumpla sus condiciones y esté vigente.';
        }
        header('Location: administrador.php?module=promociones_tienda',true,303); exit;
    }
    if (in_array($mode,['edit','view'],true)) {
        $promotionId = filter_var($_GET['id_promocion'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$promotionId) throw new DomainException('Promoción inválida.');
        $promotionForm = $promotionAdmin->find($promotionId);
    }
} catch (DomainException $ex) { $error = $ex->getMessage(); }
catch (PDOException $ex) { error_log($ex->getMessage()); $error = 'No se pudo guardar la promoción. Revisa los datos e intenta nuevamente.'; }
if ($_SERVER['REQUEST_METHOD']==='POST' && $error && in_array($_POST['action'] ?? '',['create','update'],true)) {
    $mode = ($_POST['action'] ?? '')==='create' ? 'create' : 'edit';
    foreach ($promotionForm as $field=>$default) {
        $value = $_POST[$field] ?? $default;
        if (in_array($field,['productos','categorias'],true)) $promotionForm[$field] = is_array($value) ? array_values(array_filter($value,'is_scalar')) : [];
        elseif (is_scalar($value)) $promotionForm[$field] = (string)$value;
    }
}
$promotionStatus = is_string($_GET['activo'] ?? null) ? $_GET['activo'] : '';
$list = $promotionAdmin->listing($search,$promotionStatus,max(1,(int)($_GET['page'] ?? 1)));
$promotionProducts = conectarBaseDatos()->query('SELECT id_producto,nombre,sku,activo FROM productos ORDER BY nombre,id_producto')->fetchAll();
$promotionCategories = conectarBaseDatos()->query('SELECT id_categoria_producto,nombre,activo FROM categorias_productos ORDER BY nombre')->fetchAll();
$promotionGroups = conectarBaseDatos()->query('SELECT id_grupo,nombre,activo FROM grupos_clientes ORDER BY nombre')->fetchAll();
