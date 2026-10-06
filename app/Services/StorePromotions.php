<?php
declare(strict_types=1);

final class StorePromotions
{
    public static function cents(string $value): int
    {
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $value)) throw new DomainException('Monto inválido.');
        $parts = explode('.', $value);
        return (int)$parts[0]*100 + (int)str_pad($parts[1] ?? '', 2, '0');
    }

    public static function money(int $cents): string
    {
        return intdiv($cents,100).'.'.str_pad((string)($cents%100),2,'0',STR_PAD_LEFT);
    }

    // División de un producto de enteros sin desbordar ni usar punto flotante.
    private static function fraction(int $a, int $b, int $divisor): array
    {
        $whole = intdiv($a,$divisor); $rest = $a%$divisor;
        $result = 0; $remainder = 0;
        while ($b > 0) {
            if ($b%2) {
                $result += $whole; $remainder += $rest;
                $result += intdiv($remainder,$divisor); $remainder %= $divisor;
            }
            $b = intdiv($b,2);
            if (!$b) break;
            $whole = $whole*2 + intdiv($rest*2,$divisor);
            $rest = ($rest*2)%$divisor;
        }
        return [$result,$remainder];
    }

    public static function available(PDO $db, ?int $client, bool $lock=false): array
    {
        $groups = [];
        if ($client) {
            $q = $db->prepare('SELECT cg.id_grupo FROM clientes_grupos cg JOIN grupos_clientes g ON g.id_grupo=cg.id_grupo WHERE cg.id_cliente=? AND g.activo=1 ORDER BY cg.id_grupo'.($lock?' FOR UPDATE':''));
            $q->execute([$client]); $groups = array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
        }
        $q = $db->query("SELECT p.*,r.alcance,r.publico,r.id_grupo FROM promociones p
            JOIN promociones_reglas_tienda r ON r.id_promocion=p.id_promocion
            WHERE p.activo=1 AND p.aplica_a IN ('productos','ambos')
            AND p.fecha_inicio<=UTC_TIMESTAMP() AND p.fecha_fin>UTC_TIMESTAMP()
            AND (p.codigo IS NULL OR p.codigo='') ORDER BY p.id_promocion".($lock?' FOR UPDATE':''));
        $promotions = [];
        foreach ($q->fetchAll() as $promo) {
            if ($promo['publico']==='grupo' && !in_array((int)$promo['id_grupo'],$groups,true)) continue;
            $promo['productos'] = []; $promo['categorias'] = [];
            $promotions[(int)$promo['id_promocion']] = $promo;
        }
        if (!$promotions) return [];
        $ids = array_keys($promotions); $marks = implode(',',array_fill(0,count($ids),'?'));
        foreach (['productos'=>'id_producto','categorias'=>'id_categoria_producto'] as $target=>$field) {
            $q = $db->prepare("SELECT id_promocion,$field FROM promociones_$target WHERE id_promocion IN ($marks) ORDER BY id_promocion,$field".($lock?' FOR UPDATE':''));
            $q->execute($ids);
            foreach ($q->fetchAll() as $row) $promotions[(int)$row['id_promocion']][$target][] = (int)$row[$field];
        }
        return array_values($promotions);
    }

    public static function matches(array $promo, array $item): bool
    {
        return match ($promo['alcance']) {
            'tienda' => true,
            'productos' => in_array((int)$item['id_producto'],$promo['productos'],true),
            'categorias' => in_array((int)$item['id_categoria_producto'],$promo['categorias'],true),
            default => false,
        };
    }

    public static function quote(array $items, array $promotions): array
    {
        $subtotal = 0;
        foreach ($items as &$item) {
            $item['subtotal_centavos'] = self::cents((string)$item['precio'])*(int)$item['cantidad'];
            $item['descuento_centavos'] = 0;
            $item['centavos'] = $item['subtotal_centavos'];
            $item['id_promocion'] = null;
            $subtotal += $item['subtotal_centavos'];
        }
        unset($item);
        $applied = []; $pending = [];
        foreach ($promotions as $promo) {
            $matching = []; $base = 0;
            foreach ($items as $index=>$item) {
                if (self::matches($promo,$item)) { $matching[] = $index; $base += $item['subtotal_centavos']; }
            }
            if (!$base) continue;
            $minimum = self::cents((string)$promo['minimo_compra']);
            if ($subtotal < $minimum) {
                $pending[] = ['promotion'=>$promo,'remaining'=>$minimum-$subtotal];
                continue;
            }
            $value = self::cents((string)$promo['valor_descuento']);
            if ($promo['tipo_descuento']==='porcentaje') {
                [$discount,$remainder] = self::fraction($base,$value,10000);
                if ($remainder >= 5000) ++$discount;
            } else $discount = $value;
            $remainingBase = 0;
            foreach ($matching as $index) $remainingBase += $items[$index]['centavos'];
            $discount = min($remainingBase,$discount);
            if (!$discount) continue;
            $allocated = 0;
            $portions = [];
            foreach ($matching as $index) {
                [$portion] = self::fraction($discount,$items[$index]['centavos'],$remainingBase);
                $portions[$index] = $portion; $allocated += $portion;
            }
            $remaining = $discount-$allocated;
            foreach ($matching as $index) {
                if ($remaining && $portions[$index] < $items[$index]['centavos']) {
                    ++$portions[$index]; --$remaining;
                }
                if (!$portions[$index]) continue;
                // Una línea con varias promociones no se atribuye a una sola.
                $items[$index]['id_promocion'] = $items[$index]['descuento_centavos']===0 ? (int)$promo['id_promocion'] : null;
                $items[$index]['descuento_centavos'] += $portions[$index];
                $items[$index]['centavos'] -= $portions[$index];
            }
            $applied[] = $promo;
        }
        $discount = array_sum(array_column($items,'descuento_centavos'));
        $summary = count($applied)>1 ? ['titulo'=>'Descuentos acumulados'] : ($applied[0] ?? null);
        return ['items'=>$items,'subtotal'=>$subtotal,'discount'=>$discount,'total'=>$subtotal-$discount,'promotion'=>$summary,'promotions'=>$applied,'pending_promotions'=>$pending];
    }

    public static function benefit(array $promo): string
    {
        return $promo['tipo_descuento']==='porcentaje'
            ? rtrim(rtrim(number_format((float)$promo['valor_descuento'],2,'.',''),'0'),'.').'% de descuento'
            : '₡'.number_format((float)$promo['valor_descuento'],2,',','.').' de descuento por compra';
    }
}
