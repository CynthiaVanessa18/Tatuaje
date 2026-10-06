<?php
declare(strict_types=1);
function temporaryStorePromotions(PDO $db): void
{
    foreach (['promociones','grupos_clientes','clientes_grupos','promociones_reglas_tienda','promociones_productos','promociones_categorias','pedidos_sin_cobro'] as $table) {
        temporaryStoreTable($db,$table);
    }
}
function temporaryStoreTable(PDO $db, string $table): void
{
    if (!preg_match('/^[a-z_]+$/D',$table)) throw new RuntimeException('Tabla de prueba inválida.');
    $definition=$db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    $definition=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition);
    $lines=array_filter(explode("\n",$definition),fn($line)=>!str_contains($line,'FOREIGN KEY'));
    $definition=preg_replace('/,\s*\n\)/',"\n)",implode("\n",$lines));
    $definition=preg_replace('/ AUTO_INCREMENT=\d+/','',$definition);
    $db->exec($definition);
}
