<?php
declare(strict_types=1);
require_once __DIR__.'/MembershipPlans.php';

final class BenefitsOverview
{
    private const PRODUCT_FROM = " FROM promociones p
        JOIN promociones_reglas_tienda r ON r.id_promocion=p.id_promocion
        LEFT JOIN grupos_clientes g ON g.id_grupo=r.id_grupo
        WHERE p.activo=1 AND p.aplica_a IN ('productos','ambos')
        AND p.fecha_inicio<=UTC_TIMESTAMP() AND p.fecha_fin>UTC_TIMESTAMP()
        AND (p.codigo IS NULL OR p.codigo='')
        AND (r.publico='todos' OR g.activo=1)";
    private const CARD_FROM = " FROM tarjetas_regalo t
        JOIN (SELECT id_tarjeta, SUM(CASE WHEN tipo IN ('carga_inicial','devolucion') THEN monto ELSE -monto END) AS saldo
            FROM movimientos_tarjetas_regalo GROUP BY id_tarjeta) s ON s.id_tarjeta=t.id_tarjeta
        WHERE t.estado='activa' AND t.moneda='CRC' AND s.saldo>0
        AND (t.fecha_vencimiento IS NULL OR t.fecha_vencimiento>UTC_TIMESTAMP())";

    public function __construct(private PDO $db) {}

    public function listing(string $section, int $page): array
    {
        $plans=(new MembershipPlans($this->db))->plans(true);
        $memberships=[];
        foreach ($plans as $plan) {
            foreach ($plan['reglas'] as $code=>$value) {
                if (!isset(MembershipPlans::RULES[$code])) continue;
                $memberships[]=['id_plan'=>$plan['id_plan'],'nombre'=>$plan['nombre'],
                    'beneficio'=>MembershipPlans::describe($code,(string)$value,$plan['textos'][$code]['nombre']??null),
                    'descripcion'=>$plan['textos'][$code]['descripcion']??MembershipPlans::RULES[$code][2]];
            }
        }
        $counts=[
            'productos'=>(int)$this->db->query('SELECT COUNT(*)'.self::PRODUCT_FROM)->fetchColumn(),
            'membresias'=>count($memberships),
            'tarjetas'=>(int)$this->db->query('SELECT COUNT(*)'.self::CARD_FROM)->fetchColumn(),
        ];
        $section=array_key_exists($section,$counts)?$section:'productos';
        $page=max(1,min($page,max(1,(int)ceil($counts[$section]/20))));
        $limit=' LIMIT 20 OFFSET '.(($page-1)*20);
        $rows=match($section) {
            'productos'=>$this->db->query("SELECT p.id_promocion,p.titulo,p.descripcion,p.tipo_descuento,p.valor_descuento,p.minimo_compra,p.fecha_fin,
                r.alcance,r.publico,g.nombre AS grupo,
                (SELECT GROUP_CONCAT(pr.nombre ORDER BY pr.nombre SEPARATOR ', ') FROM promociones_productos pp JOIN productos pr ON pr.id_producto=pp.id_producto WHERE pp.id_promocion=p.id_promocion) AS productos,
                (SELECT GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ') FROM promociones_categorias pc JOIN categorias_productos c ON c.id_categoria_producto=pc.id_categoria_producto WHERE pc.id_promocion=p.id_promocion) AS categorias"
                .self::PRODUCT_FROM.' ORDER BY p.fecha_fin,p.id_promocion'.$limit)->fetchAll(),
            'membresias'=>array_slice($memberships,($page-1)*20,20),
            'tarjetas'=>$this->db->query('SELECT t.id_tarjeta,t.nombre_destinatario,t.moneda,t.fecha_vencimiento,s.saldo'.self::CARD_FROM.' ORDER BY t.id_tarjeta DESC'.$limit)->fetchAll(),
        };
        return compact('section','counts','page','rows')+['total'=>$counts[$section]];
    }
}
