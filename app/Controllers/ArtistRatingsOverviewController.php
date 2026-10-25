<?php
declare(strict_types=1);

$ratingsDb=conectarBaseDatos();
$ratingsSummary=$ratingsDb->query("SELECT (SELECT COUNT(*) FROM artistas) AS artistas, COUNT(*) AS resenas, AVG(puntuacion) AS promedio FROM calificaciones_artistas WHERE estado_publicacion='publicado'")->fetch();
$ratingsSort=is_string($_GET['orden']??null)?$_GET['orden']:'mejor';
$ratingsOrder=match($ratingsSort) {
    'nombre'=>'nombre_publico ASC, a.id_artista ASC',
    'resenas'=>'r.cantidad_calificaciones DESC, a.id_artista ASC',
    default=>'r.promedio DESC, r.cantidad_calificaciones DESC, a.id_artista ASC',
};
$ratingsWhere=[];$ratingsParams=[];
if ($search!=='') {
    $ratingsWhere[]="COALESCE(NULLIF(a.nombre_artistico,''),CONCAT_WS(' ',a.nombre,a.apellidos)) LIKE ?";
    $ratingsParams[]='%'.$search.'%';
}
if (($filters['id_artista']??'')!=='') {$ratingsWhere[]='a.id_artista=?';$ratingsParams[]=$filters['id_artista'];}
if ($record && $mode==='view') {$ratingsWhere[]='a.id_artista=?';$ratingsParams[]=$record['id_artista'];}
$ratingsFrom=' FROM artistas a JOIN vista_calificaciones_artistas r ON r.id_artista=a.id_artista';
$ratingsWhereSql=$ratingsWhere?' WHERE '.implode(' AND ',$ratingsWhere):'';
$ratingsCount=$ratingsDb->prepare('SELECT COUNT(*)'.$ratingsFrom.$ratingsWhereSql);
$ratingsCount->execute($ratingsParams);
$ratingsTotal=(int)$ratingsCount->fetchColumn();
$ratingsPage=max(1,min(max(1,(int)($_GET['page']??1)),max(1,(int)ceil($ratingsTotal/20))));
$ratingsQuery=$ratingsDb->prepare("SELECT a.id_artista,a.slug,a.foto_url,COALESCE(NULLIF(a.nombre_artistico,''),CONCAT_WS(' ',a.nombre,a.apellidos)) AS nombre_publico,r.promedio,r.cantidad_calificaciones".$ratingsFrom.$ratingsWhereSql.' ORDER BY '.$ratingsOrder.' LIMIT 20 OFFSET '.(($ratingsPage-1)*20));
$ratingsQuery->execute($ratingsParams);
$list=['rows'=>$ratingsQuery->fetchAll(),'total'=>$ratingsTotal,'page'=>$ratingsPage];
$artistReviews=[];
$selectedArtist=null;
$selectedArtistId=filter_var($_GET['artista']??($record['id_artista']??null),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if ($selectedArtistId) {
    $selectedQuery=$ratingsDb->prepare("SELECT a.id_artista,COALESCE(NULLIF(a.nombre_artistico,''),CONCAT_WS(' ',a.nombre,a.apellidos)) AS nombre_publico FROM artistas a WHERE a.id_artista=?");
    $selectedQuery->execute([$selectedArtistId]);$selectedArtist=$selectedQuery->fetch()?:null;
}
if ($selectedArtist) {
    $statsQuery=$ratingsDb->prepare("SELECT COUNT(*) AS total,SUM(ca.estado_publicacion='pendiente') AS pendientes,SUM(ca.estado_publicacion='rechazado') AS ocultas,SUM(ca.estado_publicacion='publicado') AS publicadas,AVG(CASE WHEN ca.estado_publicacion='publicado' THEN ca.puntuacion END) AS promedio FROM calificaciones_artistas ca JOIN citas c ON c.id_cita=ca.id_cita WHERE c.id_artista=?");
    $statsQuery->execute([$selectedArtistId]);$artistStats=$statsQuery->fetch();
    $distributionQuery=$ratingsDb->prepare("SELECT ca.puntuacion,COUNT(*) AS cantidad FROM calificaciones_artistas ca JOIN citas c ON c.id_cita=ca.id_cita WHERE c.id_artista=? AND ca.estado_publicacion='publicado' GROUP BY ca.puntuacion");
    $distributionQuery->execute([$selectedArtistId]);$artistDistribution=array_column($distributionQuery->fetchAll(),'cantidad','puntuacion');
    $reviewState=is_string($_GET['estado_resena']??null)?$_GET['estado_resena']:'';
    if (!in_array($reviewState,['pendiente','publicado','rechazado'],true)) $reviewState='';
    $reviewWhere=' WHERE c.id_artista=?';$reviewParams=[$selectedArtistId];
    if ($reviewState!=='') {$reviewWhere.=' AND ca.estado_publicacion=?';$reviewParams[]=$reviewState;}
    $reviewCountQuery=$ratingsDb->prepare('SELECT COUNT(*) FROM calificaciones_artistas ca JOIN citas c ON c.id_cita=ca.id_cita'.$reviewWhere);
    $reviewCountQuery->execute($reviewParams);$reviewTotal=(int)$reviewCountQuery->fetchColumn();
    $reviewPage=max(1,min(max(1,(int)($_GET['resenas_page']??1)),max(1,(int)ceil($reviewTotal/20))));
    $reviewsQuery=$ratingsDb->prepare("SELECT ca.id_calificacion,ca.puntuacion,ca.comentario,ca.fecha,ca.estado_publicacion,CONCAT_WS(' ',cl.nombre,cl.apellidos) AS cliente FROM calificaciones_artistas ca JOIN citas c ON c.id_cita=ca.id_cita LEFT JOIN clientes cl ON cl.id_cliente=c.id_cliente".$reviewWhere.' ORDER BY ca.fecha DESC,ca.id_calificacion DESC LIMIT 20 OFFSET '.(($reviewPage-1)*20));
    $reviewsQuery->execute($reviewParams);
    $artistReviews=$reviewsQuery->fetchAll();
}
