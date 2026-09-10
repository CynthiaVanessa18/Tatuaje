<?php
declare(strict_types=1);

final class CrudRepository
{
    public function __construct(public PDO $db, public array $module) {}
    public function columns(): array {
        $result = [];
        foreach ($this->db->query('SHOW FULL COLUMNS FROM `' . $this->module['table'] . '`') as $c) {
            if (!in_array($c['Field'], $this->module['hidden'] ?? [], true)) $result[$c['Field']] = $c;
        }
        return $result;
    }
    public function editable(array $c): bool {
        return !str_contains($c['Extra'], 'auto_increment') && !str_contains($c['Extra'], 'GENERATED')
            && !in_array($c['Field'], $this->module['computed'] ?? [], true)
            && !in_array($c['Field'], ['fecha','fecha_emision'], true);
    }
    public function references(): array {
        $q=$this->db->prepare('SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND REFERENCED_TABLE_NAME IS NOT NULL');
        $q->execute([$this->module['table']]); $out=[];
        foreach ($q as $r) {
            $table=$r['REFERENCED_TABLE_NAME']; $pk=$r['REFERENCED_COLUMN_NAME'];
            $names=['artistas'=>"CONCAT(nombre, ' ', apellidos)", 'clientes'=>"CONCAT(nombre, ' ', apellidos)", 'citas'=>"CONCAT('Cita ', id_cita, ' · ', fecha_hora_inicio, ' · ', estado)", 'ventas'=>"CONCAT('Venta ', id_venta, ' · ', estado)", 'detalle_ventas'=>"CONCAT('Detalle ', id_detalle_venta, ' · ', tipo_item, ' · ', descripcion)", 'pagos'=>"CONCAT('Pago ', id_pago)", 'promociones'=>'titulo', 'cotizaciones'=>"CONCAT('Cotización ', id_cotizacion)", 'tarjetas_regalo'=>'nombre_destinatario'];
            $display=$names[$table] ?? 'nombre';
            $out[$r['COLUMN_NAME']]=$this->db->query("SELECT `$pk` AS id, $display AS nombre FROM `$table` ORDER BY `$pk` DESC")->fetchAll();
        }
        return $out;
    }
    public function key(array $input): array {
        $key=[];
        foreach ($this->module['pk'] as $field) {
            $v=$input[$field] ?? null;
            if (!is_scalar($v) || !preg_match('/^[1-9][0-9]{0,18}$/', (string)$v)) throw new DomainException('Identificador inválido.');
            $key[$field]=(string)$v;
        }
        return $key;
    }
    private function where(array $key): string { return implode(' AND ', array_map(fn($k)=>"`$k`=?", array_keys($key))); }
    public function find(array $key, bool $lock=false): array {
        $q=$this->db->prepare('SELECT * FROM `'.$this->module['table'].'` WHERE '.$this->where($key).($lock?' FOR UPDATE':''));
        $q->execute(array_values($key));
        return $q->fetch() ?: throw new DomainException('El registro ya no existe.');
    }
    public function listing(string $search, array $filters, int $page): array {
        $where=[]; $params=[]; $columns=$this->columns();
        if ($search !== '') {
            $text=array_filter($columns, fn($c)=>preg_match('/char|text/', $c['Type']));
            if ($text) { $where[]='('.implode(' OR ',array_map(fn($k)=>"`$k` LIKE ?",array_keys($text))).')'; foreach ($text as $_) $params[]='%'.$search.'%'; }
        }
        foreach ($filters as $k=>$v) if (isset($columns[$k]) && is_string($v) && $v!=='') { $where[]="`$k`=?"; $params[]=$v; }
        $sql=' FROM `'.$this->module['table'].'`'.($where?' WHERE '.implode(' AND ',$where):'');
        $q=$this->db->prepare('SELECT COUNT(*)'.$sql); $q->execute($params); $total=(int)$q->fetchColumn();
        $page=max(1,min($page,max(1,(int)ceil($total/20))));
        $q=$this->db->prepare('SELECT '.implode(',',array_map(fn($k)=>"`$k`",array_keys($columns))).$sql.' ORDER BY `'.$this->module['pk'][0].'` DESC LIMIT 20 OFFSET '.(($page-1)*20));
        $q->execute($params); return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page];
    }
    public function save(array $data, ?array $key): void {
        if ($key) {
            $sql='UPDATE `'.$this->module['table'].'` SET '.implode(',',array_map(fn($k)=>"`$k`=?",array_keys($data))).' WHERE '.$this->where($key);
            $values=array_merge(array_values($data),array_values($key));
        } else {
            $sql='INSERT INTO `'.$this->module['table'].'` (`'.implode('`,`',array_keys($data)).'`) VALUES ('.implode(',',array_fill(0,count($data),'?')).')'; $values=array_values($data);
        }
        $this->db->prepare($sql)->execute($values);
    }
    public function delete(array $key): void { $this->db->prepare('DELETE FROM `'.$this->module['table'].'` WHERE '.$this->where($key))->execute(array_values($key)); }
}
