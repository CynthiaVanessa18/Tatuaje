<?php
declare(strict_types=1);
require_once __DIR__.'/StorePromotions.php';

final class StorePromotionAdmin
{
    public function __construct(private PDO $db) {}

    private static function text(array $data, string $name, int $max): string
    {
        $value = $data[$name] ?? '';
        if (!is_string($value) || mb_strlen($value)>$max) throw new DomainException('Revisa el campo '.label($name).'.');
        return trim($value);
    }

    private static function date(string $value): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/D',$value)) throw new DomainException('Indica fechas y horas válidas.');
        if (strlen($value)===16) $value .= ':00';
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s',$value,new DateTimeZone('America/Costa_Rica'));
        if (!$date || $date->format('Y-m-d\TH:i:s')!==$value || (int)$date->format('Y')<1000) throw new DomainException('Fecha inválida.');
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function targets(mixed $values, string $table, string $field): array
    {
        if (!is_array($values) || count($values)>5000) throw new DomainException('Selección de artículos o categorías inválida.');
        $ids = [];
        foreach ($values as $value) {
            $id = filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if (!$id) throw new DomainException('Selección inválida.');
            $ids[$id] = $id;
        }
        $ids = array_values($ids);
        if (!$ids) throw new DomainException('Selecciona al menos un artículo o categoría.');
        $marks = implode(',',array_fill(0,count($ids),'?'));
        $q = $this->db->prepare("SELECT COUNT(*) FROM `$table` WHERE `$field` IN ($marks)");
        $q->execute($ids);
        if ((int)$q->fetchColumn()!==count($ids)) throw new DomainException('Uno de los artículos o categorías ya no existe.');
        return $ids;
    }

    public function save(array $data, ?int $id=null): int
    {
        $title = self::text($data,'titulo',180);
        if ($title==='') throw new DomainException('Indica el nombre de la promoción.');
        $description = self::text($data,'descripcion',5000);
        $type = self::text($data,'tipo_descuento',20);
        if (!in_array($type,['porcentaje','monto'],true)) throw new DomainException('Selecciona porcentaje o monto fijo.');
        $value = StorePromotions::cents(self::text($data,'valor_descuento',20));
        if (!$value || ($type==='porcentaje' && $value>10000)) throw new DomainException('El porcentaje debe ser mayor que 0 y hasta 100; el monto debe ser mayor que 0.');
        $minimumRule = self::text($data,'regla_minimo',20);
        if (!in_array($minimumRule,['siempre','minimo'],true)) throw new DomainException('Selecciona la regla de compra mínima.');
        $minimum = $minimumRule==='minimo' ? StorePromotions::cents(self::text($data,'minimo_compra',20)) : 0;
        if ($minimumRule==='minimo' && !$minimum) throw new DomainException('Indica una compra mínima mayor que cero.');
        $start = self::date(self::text($data,'fecha_inicio',30));
        $end = self::date(self::text($data,'fecha_fin',30));
        if ($end<=$start) throw new DomainException('El vencimiento debe ser posterior al inicio.');
        $scope = self::text($data,'alcance',20);
        if (!in_array($scope,['tienda','productos','categorias'],true)) throw new DomainException('Selecciona dónde aplica la promoción.');
        $audience = self::text($data,'publico',20);
        if (!in_array($audience,['todos','grupo'],true)) throw new DomainException('Selecciona los clientes que pueden usarla.');
        $active = self::text($data,'activo',1);
        if (!in_array($active,['0','1'],true)) throw new DomainException('Selecciona el estado de la promoción.');
        $this->db->beginTransaction();
        try {
            if ($id) {
                $q = $this->db->prepare('SELECT p.id_promocion FROM promociones p JOIN promociones_reglas_tienda r ON r.id_promocion=p.id_promocion WHERE p.id_promocion=? FOR UPDATE');
                $q->execute([$id]);
                if (!$q->fetchColumn()) throw new DomainException('La promoción de tienda no existe.');
            }
            $products = $scope==='productos' ? $this->targets($data['productos'] ?? [],'productos','id_producto') : [];
            $categories = $scope==='categorias' ? $this->targets($data['categorias'] ?? [],'categorias_productos','id_categoria_producto') : [];
            $group = null;
            if ($audience==='grupo') {
                $group = filter_var($data['id_grupo'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                $q = $this->db->prepare('SELECT id_grupo FROM grupos_clientes WHERE id_grupo=? AND activo=1');
                $q->execute([$group ?: 0]);
                if (!$group || !$q->fetchColumn()) throw new DomainException('Selecciona un grupo de clientes activo.');
            }
            $values = [$title,$description ?: null,$type,StorePromotions::money($value),$start,$end,StorePromotions::money($minimum),(int)$active];
            if ($id) {
                $this->db->prepare('UPDATE promociones SET titulo=?,descripcion=?,tipo_descuento=?,valor_descuento=?,fecha_inicio=?,fecha_fin=?,minimo_compra=?,activo=? WHERE id_promocion=?')
                    ->execute([...$values,$id]);
            } else {
                $this->db->prepare("INSERT INTO promociones (titulo,descripcion,tipo_descuento,valor_descuento,fecha_inicio,fecha_fin,minimo_compra,activo,aplica_a) VALUES (?,?,?,?,?,?,?,?,'productos')")
                    ->execute($values);
                $id = (int)$this->db->lastInsertId();
            }
            $this->db->prepare('INSERT INTO promociones_reglas_tienda (id_promocion,alcance,publico,id_grupo) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE alcance=VALUES(alcance),publico=VALUES(publico),id_grupo=VALUES(id_grupo)')
                ->execute([$id,$scope,$audience,$group]);
            foreach (['productos'=>'id_producto','categorias'=>'id_categoria_producto'] as $target=>$field) {
                $this->db->prepare("DELETE FROM promociones_$target WHERE id_promocion=?")->execute([$id]);
                $q = $this->db->prepare("INSERT INTO promociones_$target (id_promocion,$field) VALUES (?,?)");
                foreach ($target==='productos' ? $products : $categories as $targetId) $q->execute([$id,$targetId]);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $ex;
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $q = $this->db->prepare('UPDATE promociones p JOIN promociones_reglas_tienda r ON r.id_promocion=p.id_promocion SET p.activo=? WHERE p.id_promocion=?');
        $q->execute([(int)$active,$id]);
        if (!$q->rowCount()) $this->find($id);
    }

    public function find(int $id): array
    {
        $q = $this->db->prepare('SELECT p.*,r.alcance,r.publico,r.id_grupo FROM promociones p JOIN promociones_reglas_tienda r ON r.id_promocion=p.id_promocion WHERE p.id_promocion=?');
        $q->execute([$id]); $record = $q->fetch();
        if (!$record) throw new DomainException('La promoción de tienda no existe.');
        foreach (['productos'=>'id_producto','categorias'=>'id_categoria_producto'] as $target=>$field) {
            $q = $this->db->prepare("SELECT $field FROM promociones_$target WHERE id_promocion=?");
            $q->execute([$id]); $record[$target] = array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
        }
        foreach (['fecha_inicio','fecha_fin'] as $field) {
            $record[$field] = (new DateTimeImmutable($record[$field],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('Y-m-d\TH:i');
        }
        $record['regla_minimo'] = StorePromotions::cents($record['minimo_compra']) ? 'minimo' : 'siempre';
        return $record;
    }

    public function listing(string $search, string $status, int $page): array
    {
        $where = ' WHERE 1=1'; $params = [];
        if ($search!=='') { $where .= ' AND (p.titulo LIKE ? OR p.descripcion LIKE ?)'; $params = ['%'.$search.'%','%'.$search.'%']; }
        if (in_array($status,['0','1'],true)) { $where .= ' AND p.activo=?'; $params[] = (int)$status; }
        $from = ' FROM promociones p JOIN promociones_reglas_tienda r ON r.id_promocion=p.id_promocion LEFT JOIN grupos_clientes g ON g.id_grupo=r.id_grupo'.$where;
        $q = $this->db->prepare('SELECT COUNT(*)'.$from); $q->execute($params); $total = (int)$q->fetchColumn();
        $page = max(1,min($page,max(1,(int)ceil($total/20))));
        $q = $this->db->prepare('SELECT p.*,r.alcance,r.publico,g.nombre AS grupo'.$from.' ORDER BY p.id_promocion DESC LIMIT 20 OFFSET '.(($page-1)*20));
        $q->execute($params);
        return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page];
    }
}
