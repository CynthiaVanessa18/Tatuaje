<?php
declare(strict_types=1);
require_once __DIR__.'/StorePromotions.php';

final class MembershipPlans
{
    public const RULES = [
        'prioridad'=>['Prioridad en horarios de tatuaje','prioridad','Sin costo adicional; sujeto a disponibilidad.'],
        'retoques'=>['Retoques gratuitos','sesiones','Para tatuajes realizados en el local, previa valoración.'],
        'mercancia_porcentaje'=>['Descuento en mercancías','porcentaje','Porcentaje de descuento en artículos de la tienda.'],
        'mercancia_monto'=>['Rebaja en mercancías','monto','Monto de descuento por compra en colones.'],
        'hidratacion'=>['Sesiones de hidratación de piel','sesiones','Cuidado de piel, previa valoración en el estudio.'],
        'color'=>['Repaso de color','sesiones','Solo tatuajes antiguos realizados en el local, previa valoración.'],
        'brillo'=>['Brillo para tatuajes antiguos','sesiones','Solo tatuajes realizados en el local, previa valoración.'],
        'kit_regalo'=>['Kit de cuidado de regalo','kits','Cantidad de kits de cuidado incluidos.'],
        'kit_descuento'=>['Descuento en kit de cuidado','porcentaje','Porcentaje de rebaja en kits de cuidado.'],
    ];
    public function __construct(private PDO $db) {}

    public function plans(bool $activeOnly=false): array
    {
        $rows=$this->db->query('SELECT p.*,c.nivel,c.cuota_mensual,c.cuota_anual,c.modalidad FROM membresias_configuracion c JOIN planes_membresia p ON p.id_plan=c.id_plan'.($activeOnly?' WHERE p.activo=1':'')." ORDER BY FIELD(c.nivel,'esencial','plus','premium')")->fetchAll();
        $query=$this->db->prepare('SELECT codigo,valor FROM membresias_reglas WHERE id_plan=?');
        foreach ($rows as &$row) { $query->execute([$row['id_plan']]); $row['reglas']=array_column($query->fetchAll(),'valor','codigo'); }
        return $rows;
    }

    public static function describe(string $code, string $value): string
    {
        [$name,$unit]=self::RULES[$code];
        return $name.($unit==='prioridad'?'':': '.($unit==='monto'?'₡'.number_format((float)$value,2,',','.') : rtrim(rtrim($value,'0'),'.')).match($unit) {'porcentaje'=>' %','sesiones'=>' sesión(es) por período contratado','kits'=>' kit(s) por período contratado',default=>''});
    }

    public function save(array $data): void
    {
        $id=filter_var($data['id_plan']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $name=$data['nombre']??null; $description=$data['descripcion']??'';
        if (!$id || !is_string($name) || trim($name)==='' || mb_strlen($name)>100 || !is_string($description) || mb_strlen($description)>5000) throw new DomainException('Revisa el nombre y la descripción del plan.');
        $mode=$data['modalidad']??''; $active=$data['activo']??'';
        if (!in_array($mode,['mensual','anual','ambas'],true) || !in_array($active,['0','1'],true)) throw new DomainException('Modalidad o estado inválido.');
        $monthly=StorePromotions::cents(is_string($data['cuota_mensual']??null)?$data['cuota_mensual']:'');
        $yearly=StorePromotions::cents(is_string($data['cuota_anual']??null)?$data['cuota_anual']:'');
        if ($active==='1' && (($mode!=='anual' && !$monthly) || ($mode!=='mensual' && !$yearly))) throw new DomainException('Configura una cuota mayor que cero para cada modalidad habilitada.');
        $selected=$data['reglas']??[]; $values=$data['valores']??[];
        if (!is_array($selected) || !is_array($values)) throw new DomainException('Selección de beneficios inválida.');
        $rules=[];
        foreach ($selected as $code) {
            if (!is_string($code) || !isset(self::RULES[$code])) throw new DomainException('Selecciona únicamente reglas precargadas.');
            $unit=self::RULES[$code][1];
            $value=$unit==='prioridad'?100:StorePromotions::cents(is_string($values[$code]??null)?$values[$code]:'');
            if (!$value || ($unit==='porcentaje' && $value>10000) || (in_array($unit,['kits','sesiones'],true) && $value%100!==0)) throw new DomainException('Revisa el valor de '.self::RULES[$code][0].': cantidades enteras positivas y porcentajes hasta 100.');
            $rules[$code]=StorePromotions::money($value);
        }
        if (!$rules) throw new DomainException('Selecciona al menos un beneficio del plan.');
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT id_plan FROM membresias_configuracion WHERE id_plan=? FOR UPDATE'); $q->execute([$id]);
            if (!$q->fetchColumn()) throw new DomainException('Este plan no pertenece a los tres tipos configurables.');
            $q=$this->db->prepare('UPDATE planes_membresia SET nombre=?,descripcion=?,activo=?,precio=?,duracion_dias=? WHERE id_plan=?');
            $q->execute([trim($name),trim($description),$active,StorePromotions::money($mode==='anual'?$yearly:$monthly),$mode==='anual'?365:30,$id]);
            $this->db->prepare('UPDATE membresias_configuracion SET cuota_mensual=?,cuota_anual=?,modalidad=? WHERE id_plan=?')->execute([StorePromotions::money($monthly),StorePromotions::money($yearly),$mode,$id]);
            $this->db->prepare('DELETE FROM membresias_reglas WHERE id_plan=?')->execute([$id]);
            $q=$this->db->prepare('INSERT INTO membresias_reglas (id_plan,codigo,valor) VALUES (?,?,?)');
            foreach ($rules as $code=>$value) $q->execute([$id,$code,$value]);
            $this->db->commit();
        } catch (Throwable $ex) { $this->db->rollBack(); throw $ex; }
    }
}
