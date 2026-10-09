<?php
declare(strict_types=1);
final class GiftCardBalances
{
    public const STATES=['pendiente'=>'Pendiente','activa'=>'Activa','agotada'=>'Agotada','vencida'=>'Vencida','cancelada'=>'Cancelada'];
    private const EFFECTIVE_STATE="CASE WHEN t.estado IN ('activa','pendiente') AND t.fecha_vencimiento<=UTC_TIMESTAMP() THEN 'vencida' WHEN t.estado='activa' AND COALESCE(m.saldo,0)<=0 THEN 'agotada' ELSE t.estado END";
    private const FROM=" FROM tarjetas_regalo t LEFT JOIN clientes c ON c.id_cliente=t.id_cliente_comprador
        LEFT JOIN (SELECT id_tarjeta,SUM(CASE WHEN tipo IN ('carga_inicial','devolucion') THEN monto ELSE -monto END) AS saldo
            FROM movimientos_tarjetas_regalo GROUP BY id_tarjeta) m ON m.id_tarjeta=t.id_tarjeta";
    public function __construct(private PDO $db) {}
    public function recipients(string $search,string $state,int $page): array
    {
        [$where,$args]=$this->conditions($search,$state,null,true);
        $group="SELECT MAX(SHA2(LOWER(TRIM(t.correo_destinatario)),256)) AS destinatario,
            MAX(t.nombre_destinatario) AS nombre_destinatario,t.moneda,COUNT(*) AS tarjetas,
            SUM(CASE WHEN t.estado='activa' AND (t.fecha_vencimiento IS NULL OR t.fecha_vencimiento>UTC_TIMESTAMP()) THEN GREATEST(COALESCE(m.saldo,0),0) ELSE 0 END) AS saldo_actual"
            .self::FROM.$where.' GROUP BY LOWER(TRIM(t.correo_destinatario)),t.moneda';
        $q=$this->db->prepare('SELECT COUNT(*) FROM ('.$group.') recipients');$q->execute($args);$total=(int)$q->fetchColumn();
        $page=max(1,min($page,max(1,(int)ceil($total/20))));
        $q=$this->db->prepare($group.' ORDER BY nombre_destinatario,destinatario,t.moneda LIMIT 20 OFFSET '.(($page-1)*20));$q->execute($args);
        return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page];
    }
    public function recipientCards(string $recipient,int $page,string $state='',string $currency=''): array
    {
        $where=' WHERE SHA2(LOWER(TRIM(t.correo_destinatario)),256)=?';$args=[$recipient];
        if (isset(self::STATES[$state])) {$where.=' AND '.self::EFFECTIVE_STATE.'=?';$args[]=$state;}
        if ($currency!=='') {$where.=' AND t.moneda=?';$args[]=$currency;}
        $q=$this->db->prepare('SELECT COUNT(*)'.self::FROM.$where);$q->execute($args);$total=(int)$q->fetchColumn();
        $page=max(1,min($page,max(1,(int)ceil($total/20))));
        $q=$this->db->prepare("SELECT t.id_tarjeta,t.nombre_destinatario,".self::EFFECTIVE_STATE." AS estado,t.moneda,COALESCE(m.saldo,0) AS saldo_actual,
            CASE WHEN t.estado='activa' AND (t.fecha_vencimiento IS NULL OR t.fecha_vencimiento>UTC_TIMESTAMP()) THEN GREATEST(COALESCE(m.saldo,0),0) ELSE 0 END AS disponible"
            .self::FROM.$where.' ORDER BY t.id_tarjeta DESC LIMIT 20 OFFSET '.(($page-1)*20));$q->execute($args);
        return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page];
    }
    private function conditions(string $search,string $state,?int $card,bool $effective=false): array
    {
        $where=[];$args=[];
        if ($search!=='') {
            $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],$search).'%';
            // Busca coincidencias literales del nombre, sin interpretar comodines ingresados.
            $where[]="(t.nombre_destinatario LIKE ? ESCAPE '!' OR CONCAT_WS(' ',c.nombre,c.apellidos) LIKE ? ESCAPE '!')";
            $args=[$term,$term];
        }
        if (isset(self::STATES[$state])) { $where[]=($effective?self::EFFECTIVE_STATE:'t.estado').'=?';$args[]=$state; }
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
