<?php
// El listado resume el registro; la vista de detalle conserva sus demás campos.
return [
    'categorias'=>['nombre','activo'],
    'especialidades'=>['id_artista','id_categoria'],
    'resumen_calificaciones'=>['nombre_artistico','promedio','cantidad_calificaciones'],
    'promociones'=>['titulo','tipo_descuento','valor_descuento','activo'],
    'planes'=>['nombre','precio','activo'],
    'membresias'=>['id_cliente','id_plan','estado','fecha_fin'],
    'beneficios'=>['nombre','tipo','activo'],
    'planes_beneficios'=>['id_plan','id_beneficio'],
    'tarjetas'=>['nombre_destinatario','monto_inicial','estado'],
    'movimientos'=>['id_tarjeta','tipo','monto','fecha'],
    'categorias_productos'=>['nombre','activo'],
    'productos'=>['nombre','precio','stock_actual','activo'],
    'imagenes'=>['id_producto','orden','es_portada'],
    'ventas'=>['id_cliente','fecha','total','estado'],
    'detalle'=>['id_venta','descripcion','cantidad','total_linea'],
    'grupos_clientes'=>['nombre','activo'],
    'clientes_grupos'=>['id_cliente','id_grupo'],
];
