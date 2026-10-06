<?php
declare(strict_types=1);

$storeSearch=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,180):'';
$storeCategory=is_string($_GET['categoria']??null)?$_GET['categoria']:'';
$db=conectarBaseDatos();
$storeCategories=$db->query('SELECT id_categoria_producto,nombre FROM categorias_productos WHERE activo=1 ORDER BY nombre')->fetchAll();
if (!in_array($storeCategory,array_map('strval',array_column($storeCategories,'id_categoria_producto')),true)) $storeCategory='';
$conditions=['p.activo=1','c.activo=1'];
$values=[];
if ($storeSearch!=='') { $conditions[]='(p.nombre LIKE ? OR p.descripcion LIKE ?)'; $values[]='%'.$storeSearch.'%'; $values[]='%'.$storeSearch.'%'; }
if ($storeCategory!=='') { $conditions[]='p.id_categoria_producto=?'; $values[]=$storeCategory; }
$from=' FROM productos p JOIN categorias_productos c ON c.id_categoria_producto=p.id_categoria_producto WHERE '.implode(' AND ',$conditions);
$query=$db->prepare('SELECT COUNT(*)'.$from);$query->execute($values);$storeTotal=(int)$query->fetchColumn();
$storePageSize=20;
$storePages=max(1,(int)ceil($storeTotal/$storePageSize));
$storePage=max(1,min($storePages,(int)($_GET['page']??1)));
$query=$db->prepare('SELECT p.id_producto,p.id_categoria_producto,p.nombre,p.descripcion,p.precio,p.stock_actual,c.nombre AS categoria,
    (SELECT i.imagen_url FROM imagenes_productos i WHERE i.id_producto=p.id_producto ORDER BY i.es_portada DESC,i.orden,i.id_imagen LIMIT 1) AS imagen'
    .$from.' ORDER BY p.nombre,p.id_producto LIMIT '.$storePageSize.' OFFSET '.(($storePage-1)*$storePageSize));
$query->execute($values);$storeProducts=$query->fetchAll();
$storePromotions=StorePromotions::available($db,$profileMissing?null:(int)$profileId);
foreach ($storeProducts as &$product) {
    $product['ofertas']=array_values(array_filter($storePromotions,fn($promo)=>StorePromotions::matches($promo,$product)));
}
unset($product);
