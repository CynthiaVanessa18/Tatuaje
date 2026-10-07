<?php
declare(strict_types=1);
final class GiftCardBalances
{
    public const STATES=['pendiente'=>'Pendiente','activa'=>'Activa','agotada'=>'Agotada','vencida'=>'Vencida','cancelada'=>'Cancelada'];
    private const FROM=" FROM tarjetas_regalo t LEFT JOIN clientes c ON c.id_cliente=t.id_cliente_comprador
        LEFT JOIN (SELECT id_tarjeta,SUM(CASE WHEN tipo IN ('carga_inicial','devolucion') THEN monto ELSE -monto END) AS saldo
            FROM movimientos_tarjetas_regalo GROUP BY id_tarjeta) m ON m.id_tarjeta=t.id_tarjeta";
    public function __construct(private PDO $db) {}
    private function conditions(string $search,string $state,?int $card): array
    {
        $where=[];$args=[];
        if ($search!=='') {
            $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],$search).'%';
            // Busca coincidencias literales del nombre, sin interpretar comodines ingresados.
            $where[]="(t.nombre_destinatario LIKE ? ESCAPE '!' OR CONCAT_WS(' ',c.nombre,c.apellidos) LIKE ? ESCAPE '!')";
            $args=[$term,$term];
        }
        if (isset(self::STATES[$state])) { $where[]='t.estado=?';$args[]=$state; }
        if ($card!==null) { $where[]='t.id_tarjeta=?';$args[]=$card; }
        return [($where?' WHERE '.implode(' AND ',$where):''),$args];
    }
    public function listing(string $search,string $state,?int $card,int $page): array
    {
        [$where,$args]=$this->conditions($search,$state,$card);
        $q=$this->db->prepare('SELECT COUNT(*)'.self::FROM.$where);$q->execute($args);$total=(int)$q->fetchColumn();
        $page=max(1,min($page,max(1,(int)ceil($total/20))));
        $q=$this->db->prepare("SELECT t.id_tarjeta,t.nombre_destinatario,CONCAT_WS(' ',c.nombre,c.apellidos) AS comprador,t.estado,t.moneda,COALESCE(m.saldo,0) AS saldo_actual".self::FROM.$where.' ORDER BY t.id_tarjeta DESC LIMIT 20 OFFSET '.(($page-1)*20));
        $q->execute($args);return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page];
    }
    public function suggestions(string $search,string $state): array
    {
        if ($search==='') return [];
        [$where,$args]=$this->conditions($search,$state,null);
        $q=$this->db->prepare("SELECT t.id_tarjeta,t.nombre_destinatario,CONCAT_WS(' ',c.nombre,c.apellidos) AS comprador,t.estado,t.moneda,COALESCE(m.saldo,0) AS saldo_actual".self::FROM.$where.' ORDER BY t.nombre_destinatario,t.id_tarjeta DESC LIMIT 10');
        $q->execute($args);return $q->fetchAll();
    }
}
