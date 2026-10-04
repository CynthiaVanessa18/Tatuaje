<?php
declare(strict_types=1);

final class CrudService
{
    public function __construct(private CrudRepository $repo) {}
    public static function validate(array $columns, callable $editable, array $input): array {
        $data=[];
        foreach ($columns as $name=>$c) {
            if (!$editable($c)) continue;
            $v=$input[$name] ?? '';
            if (!is_scalar($v)) throw new DomainException('Valor inválido: '.label($name));
            $v=trim((string)$v); $type=$c['Type'];
            if ($v==='') {
                if ($c['Null']==='YES') { $data[$name]=null; continue; }
                throw new DomainException('Completa el campo '.label($name).'.');
            }
            if (str_starts_with($type,'enum(')) {
                preg_match_all("/'([^']*)'/",$type,$matches);
                if (!in_array($v,$matches[1],true)) throw new DomainException('Opción inválida: '.label($name));
            } elseif (preg_match('/^(tinyint|int|bigint)/',$type)) {
                if (!preg_match('/^\d+$/',$v) || (float)$v>PHP_INT_MAX || ($type==='tinyint(1)' && !in_array($v,['0','1'],true))) throw new DomainException('Número entero inválido: '.label($name));
            } elseif (preg_match('/^decimal\((\d+),(\d+)\)/',$type,$m)) {
                if (!preg_match('/^\d{1,'.((int)$m[1]-(int)$m[2]).'}(\.\d{1,'.$m[2].'})?$/',$v)) throw new DomainException('Monto inválido: '.label($name));
            } elseif (str_starts_with($type,'datetime') || $type==='date') {
                $v=str_replace('T',' ',$v); if (strlen($v)===16) $v.=':00';
                $format=$type==='date'?'Y-m-d':'Y-m-d H:i:s'; $d=DateTimeImmutable::createFromFormat('!'.$format,$v);
                if (!$d || $d->format($format)!==$v) throw new DomainException('Fecha inválida: '.label($name));
            }
            if (preg_match('/^(?:var)?char\((\d+)\)/',$type,$m) && mb_strlen($v)>(int)$m[1]) throw new DomainException('Texto demasiado largo: '.label($name));
            if (str_contains($name,'correo') && !filter_var($v,FILTER_VALIDATE_EMAIL)) throw new DomainException('Correo inválido.');
            $localImage = $name==='imagen_url' && preg_match('~^uploads/productos/[a-f0-9]{32}\.(jpg|png|webp)$~D',$v);
            if (str_ends_with($name,'_url') && !$localImage && (!filter_var($v,FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($v,PHP_URL_SCHEME) ?: ''),['http','https'],true))) throw new DomainException('La imagen requiere una URL http o https válida.');
            $data[$name]=$v;
        }
        return $data;
    }
    private function row(string $table, string $pk, $id): array {
        $q=$this->repo->db->prepare("SELECT * FROM `$table` WHERE `$pk`=? FOR UPDATE"); $q->execute([$id]);
        return $q->fetch() ?: throw new DomainException('No existe el registro relacionado en '.label($table).'.');
    }
    private function check(bool $ok,string $message): void { if (!$ok) throw new DomainException($message); }
    private function salePending($id): array {
        $v=$this->row('ventas','id_venta',$id);
        $this->check($v['estado']==='pendiente','Solo se pueden modificar detalles de ventas pendientes.'); return $v;
    }
    public function execute(string $action, array $input, ?array $key): string {
        $this->check(empty($this->repo->module['readonly']),'Este módulo es de consulta para preservar el historial.');
        $db=$this->repo->db; $table=$this->repo->module['table']; $db->beginTransaction();
        try {
            $old=$key?$this->repo->find($key,true):null;
            $d=$action==='delete'?[]:self::validate($this->repo->columns(),[$this->repo,'editable'],$input);
            $message='Registro guardado correctamente.';
            if ($old && in_array($table,['membresias_clientes','tarjetas_regalo'],true)) {
                if ($action==='delete') throw new DomainException('Este registro conserva historial. Utiliza el estado cancelada.');
                $immutable=$table==='tarjetas_regalo'?['id_cliente_comprador','id_detalle_venta','monto_inicial','moneda']:['id_cliente','id_plan','id_detalle_venta'];
                foreach ($immutable as $f) $this->check($f==='monto_inicial' ? self::cents($d[$f])===self::cents($old[$f]) : (string)$d[$f]===(string)$old[$f],'No se puede cambiar '.label($f).' después de emitir el registro.');
            }
            if ($table==='detalle_ventas') {
                if ($old) $this->salePending($old['id_venta']);
                if ($action!=='delete') {
                    $this->salePending($d['id_venta']);
                    $types=['producto'=>'id_producto','tatuaje'=>'id_cotizacion','membresia'=>'id_plan','tarjeta_regalo'=>null];
                    foreach (['id_producto','id_cotizacion','id_plan'] as $f) $this->check(($d[$f]!==null)===($types[$d['tipo_item']]===$f),'La referencia debe corresponder al tipo de artículo.');
                    $this->check((int)$d['cantidad']>0 && ($d['tipo_item']==='producto' || (int)$d['cantidad']===1),'Cantidad inválida.');
                    if ($d['tipo_item']==='producto') { $p=$this->row('productos','id_producto',$d['id_producto']); $this->check((bool)$p['activo'],'El artículo está inactivo.'); $d['precio_unitario']=$p['precio']; }
                    if ($d['tipo_item']==='membresia') { $p=$this->row('planes_membresia','id_plan',$d['id_plan']); $this->check((bool)$p['activo'],'El plan está inactivo.'); $d['precio_unitario']=$p['precio']; }
                    $base=self::cents($d['precio_unitario'])*(int)$d['cantidad'];
                    if ($d['id_promocion']) {
                        $p=$this->row('promociones','id_promocion',$d['id_promocion']);
                        $this->check((bool)$p['activo'] && $p['fecha_inicio']<=gmdate('Y-m-d H:i:s') && $p['fecha_fin']>=gmdate('Y-m-d H:i:s'),'Promoción fuera de vigencia.');
                        $this->check(in_array($d['tipo_item'],['producto','tatuaje'],true) && ($p['aplica_a']==='ambos' || $p['aplica_a']===($d['tipo_item']==='producto'?'productos':'tatuajes')),'La promoción no aplica a este artículo.');
                        $this->check($base>=self::cents($p['minimo_compra']),'El importe de la línea no alcanza el mínimo de la promoción.');
                        $discount=$p['tipo_descuento']==='porcentaje'?(int)round($base*self::cents($p['valor_descuento'])/10000):self::cents($p['valor_descuento']);
                        $d['descuento']=self::money(min($base,$discount));
                    }
                    $this->check(self::cents($d['descuento'])<=$base,'El descuento supera el importe de la línea.');
                }
            }
            if ($table==='ventas') {
                if ($old) {
                    $this->check($old['estado']==='pendiente','Las ventas confirmadas conservan su historial y no se editan desde el CRUD.');
                    $q=$db->prepare('SELECT COUNT(*) FROM pagos WHERE id_venta=?'); $q->execute([$old['id_venta']]);
                    $this->check(!(int)$q->fetchColumn(),'La venta tiene pagos asociados y no puede modificarse.');
                    if ($action!=='delete') $this->check($d['id_cliente']==$old['id_cliente'] && $d['moneda']===$old['moneda'],'El cliente y la moneda de una venta existente no pueden cambiarse.');
                }
                if ($action!=='delete' && $d['estado']!=='pendiente') {
                    $this->check($old!==null && in_array($d['estado'],['confirmada','cancelada'],true),'Crea la venta pendiente, agrega sus detalles y luego confírmala.');
                    if ($d['estado']==='confirmada') {
                        $q=$db->prepare('SELECT * FROM detalle_ventas WHERE id_venta=? ORDER BY id_producto FOR UPDATE'); $q->execute([$old['id_venta']]); $lines=$q->fetchAll();
                        $this->check(count($lines)>0,'Agrega al menos un detalle antes de confirmar.');
                        foreach ($lines as $line) if ($line['tipo_item']==='producto') {
                            $q=$db->prepare('UPDATE productos SET stock_actual=stock_actual-? WHERE id_producto=? AND activo=1 AND stock_actual>=?');
                            $q->execute([$line['cantidad'],$line['id_producto'],$line['cantidad']]); $this->check($q->rowCount()===1,'Inventario insuficiente para '.$line['descripcion'].'.');
                        }
                    }
                }
            }
            if ($action!=='delete') {
                if (isset($d['fecha_inicio'],$d['fecha_fin'])) $this->check($d['fecha_fin']>$d['fecha_inicio'],'La fecha final debe ser posterior a la inicial.');
                if ($table==='promociones') $this->check(self::cents($d['valor_descuento'])>0 && ($d['tipo_descuento']!=='porcentaje' || self::cents($d['valor_descuento'])<=10000),'Descuento inválido: el porcentaje debe ser mayor que 0 y hasta 100.');
                if ($table==='planes_membresia') $this->check((int)$d['duracion_dias']>0,'La duración debe ser mayor que cero.');
                if ($table==='beneficios' && in_array($d['tipo'],['descuento_monto','descuento_porcentaje'],true)) $this->check($d['valor']!==null && ($d['tipo']!=='descuento_porcentaje' || self::cents($d['valor'])<=10000),'Indica el valor del descuento (porcentaje máximo 100).');
                if ($table==='calificaciones_artistas') {
                    $c=$this->row('citas','id_cita',$d['id_cita']);
                    $this->check($c['estado']==='finalizada','Solo se pueden calificar citas finalizadas.');
                    $this->check((int)$d['puntuacion']>=1 && (int)$d['puntuacion']<=5,'La puntuación debe estar entre 1 y 5.');
                }
                if (in_array($table,['membresias_clientes','tarjetas_regalo'],true)) {
                    $line=$this->row('detalle_ventas','id_detalle_venta',$d['id_detalle_venta']); $sale=$this->row('ventas','id_venta',$line['id_venta']);
                    $isCard=$table==='tarjetas_regalo';
                    $this->check($line['tipo_item']===($isCard?'tarjeta_regalo':'membresia'),'El detalle de venta no corresponde a este tipo de registro.');
                    $this->check($sale['id_cliente']==$d[$isCard?'id_cliente_comprador':'id_cliente'],'El cliente no coincide con la venta.');
                    $this->check(in_array($sale['estado'],['confirmada','completada'],true),'La venta debe estar confirmada.');
                    if (!$isCard) $this->check($line['id_plan']==$d['id_plan'],'El plan no coincide con el detalle vendido.');
                    if ($isCard) {
                        $this->check(self::cents($d['monto_inicial'])>0 && self::cents($d['monto_inicial'])===self::cents($line['precio_unitario']) && $d['moneda']===$sale['moneda'],'El importe y la moneda deben coincidir con el detalle vendido.');
                        $this->check(!$d['fecha_vencimiento'] || $d['fecha_vencimiento']>($old['fecha_emision']??gmdate('Y-m-d H:i:s')),'El vencimiento debe ser posterior a la emisión.');
                        $this->check(in_array($d['estado'],['pendiente','activa','cancelada'],true),'Agotada y vencida son estados reservados para el proceso de canje/vencimiento.');
                        if ($old && $old['estado']==='cancelada') $this->check($d['estado']==='cancelada','Una tarjeta cancelada no puede reactivarse.');
                        if (!$old) { $code=strtoupper(bin2hex(random_bytes(16))); $d['codigo_hash']=hash('sha256',$code,true); $message='Tarjeta creada. Guarda el código: '.$code.'. Se muestra una sola vez.'; }
                    }
                }
            }
            if ($action==='delete') { $this->repo->delete($key); $message='Registro eliminado correctamente.'; }
            else $this->repo->save($d,$key);
            if ($table==='productos' && $action!=='delete' && !empty($input['_product_image'])) {
                $image=$input['_product_image'];
                $this->check(is_string($image) && (bool)preg_match('~^uploads/productos/[a-f0-9]{32}\.(jpg|png|webp)$~D',$image),'Imagen inválida.');
                $productId=$key['id_producto'] ?? $db->lastInsertId();
                $db->prepare('UPDATE imagenes_productos SET es_portada=0 WHERE id_producto=?')->execute([$productId]);
                $db->prepare('INSERT INTO imagenes_productos (id_producto,imagen_url,orden,es_portada) VALUES (?,?,0,1)')->execute([$productId,$image]);
            }
            if ($table==='tarjetas_regalo' && !$old) {
                $id=$db->lastInsertId();
                $db->prepare("INSERT INTO movimientos_tarjetas_regalo (id_tarjeta,tipo,monto,referencia) VALUES (?,'carga_inicial',?,?)")->execute([$id,$d['monto_inicial'],'emision-'.bin2hex(random_bytes(16))]);
            }
            if ($table==='detalle_ventas') {
                foreach (array_unique(array_filter([$old['id_venta']??null,$d['id_venta']??null])) as $id) {
                    $db->prepare('UPDATE ventas SET subtotal=(SELECT COALESCE(SUM(cantidad*precio_unitario),0) FROM detalle_ventas WHERE id_venta=?), descuento_total=(SELECT COALESCE(SUM(descuento),0) FROM detalle_ventas WHERE id_venta=?), impuesto_total=(SELECT COALESCE(SUM(impuesto),0) FROM detalle_ventas WHERE id_venta=?) WHERE id_venta=?')->execute([$id,$id,$id,$id]);
                }
            }
            $db->commit(); return $message;
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    }
    public static function cents($v): int { $parts=explode('.',(string)$v); return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0'); }
    public static function money(int $v): string { return intdiv($v,100).'.'.str_pad((string)($v%100),2,'0',STR_PAD_LEFT); }
}
