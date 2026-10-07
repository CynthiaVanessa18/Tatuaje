<section class="card">
    <div class="dashboard-section-heading"><span class="dashboard-stat-icon"><?= adminIcon('tarjetas') ?></span><div><p class="eyebrow">CONSULTA DE SALDOS</p><h2>Busca una persona</h2><p>Escribe parte del nombre del destinatario o comprador y selecciona una coincidencia.</p></div></div>
    <form action="administrador.php" method="get" class="balance-search-form" data-balance-search>
        <input type="hidden" name="module" value="saldos_tarjetas">
        <input type="hidden" name="selected_card" value="<?= e($selectedCard??'') ?>" data-balance-selection>
        <div class="balance-search-bar">
            <div class="balance-autocomplete"><label for="balance-name">Nombre del destinatario o comprador</label><div class="balance-input-wrap"><input type="search" id="balance-name" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre" maxlength="180" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="balance-matches" aria-expanded="false" data-balance-name><div id="balance-matches" class="balance-matches" role="listbox" aria-label="Coincidencias por nombre" hidden></div></div><small data-balance-status role="status" aria-live="polite"><?= $selectedCard?'Tarjeta seleccionada. Cambia el nombre para buscar otra.':'' ?></small></div>
            <button type="submit"><?= adminIcon('buscar') ?> Buscar</button><a href="administrador.php?module=saldos_tarjetas">Limpiar</a>
        </div>
        <fieldset class="balance-states"><legend>Estado</legend><div class="balance-state-options">
        <?php foreach ([''=>'Todos']+GiftCardBalances::STATES as $state=>$caption): ?><label class="balance-state-option"><input type="radio" name="estado" value="<?= e($state) ?>" <?= $balanceState===$state?'checked':'' ?>><span><?= e($caption) ?></span></label><?php endforeach ?>
        </div></fieldset>
    </form>
    <p><?= e($list['total']) ?> <?= $list['total']===1?'coincidencia':'coincidencias' ?></p>
    <div class="table-scroll"><table data-server-paginated><thead><tr><th>Destinatario</th><th>Estado</th><th>Saldo disponible</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($list['rows'] as $card): ?><tr><td class="list-value"><strong><?= e($card['nombre_destinatario']) ?></strong><small class="balance-card-number">Tarjeta #<?= e($card['id_tarjeta']) ?></small></td><td><?= e(GiftCardBalances::STATES[$card['estado']]??$card['estado']) ?></td><td><?= e($card['moneda']) ?> <?= e(number_format((float)$card['saldo_actual'],2,',','.')) ?></td><td><a href="administrador.php?module=tarjetas&amp;mode=view&amp;id_tarjeta=<?= e($card['id_tarjeta']) ?>">Ver tarjeta →</a></td></tr><?php endforeach ?>
        <?php if (!$list['rows']): ?><tr><td colspan="4">No hay coincidencias con ese nombre y estado.</td></tr><?php endif ?>
    </tbody></table></div>
    <?php renderPagination($list['total'],$list['page'],['module'=>'saldos_tarjetas','q'=>$search,'estado'=>$balanceState,'selected_card'=>$selectedCard]); ?>
</section>
