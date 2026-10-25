<section class="card">
    <div class="dashboard-section-heading"><span class="dashboard-stat-icon"><?= adminIcon('tarjetas') ?></span><div><p class="eyebrow">CONSULTA DE SALDOS</p><h2>Busca una persona</h2><p>Escribe parte del nombre del destinatario o comprador y selecciona una coincidencia.</p></div></div>
    <form action="administrador.php" method="get" class="balance-search-form" data-balance-search>
        <input type="hidden" name="module" value="saldos_tarjetas">
        <input type="hidden" name="destinatario" value="<?= e($selectedRecipient??'') ?>" data-balance-selection>
        <?php if ($selectedRecipient && $balanceCurrency): ?><input type="hidden" name="moneda" value="<?= e($balanceCurrency) ?>" data-balance-currency><?php endif ?>
        <div class="balance-search-bar">
            <div class="balance-autocomplete"><label for="balance-name">Nombre del destinatario o comprador</label><div class="balance-input-wrap"><input type="search" id="balance-name" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre" maxlength="180" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="balance-matches" aria-expanded="false" data-balance-name><div id="balance-matches" class="balance-matches" role="listbox" aria-label="Coincidencias por nombre" hidden></div></div><small data-balance-status role="status" aria-live="polite"><?= $selectedRecipient?'Destinatario seleccionado. Cambia el nombre para buscar otro.':'' ?></small></div>
            <button type="submit"><?= adminIcon('buscar') ?> Buscar</button><a href="administrador.php?module=saldos_tarjetas">Limpiar</a>
        </div>
        <fieldset class="balance-states"><legend>Estado</legend><div class="balance-state-options">
        <?php foreach ([''=>'Todos']+GiftCardBalances::STATES as $state=>$caption): ?><label class="balance-state-option"><input type="radio" name="estado" value="<?= e($state) ?>" <?= $balanceState===$state?'checked':'' ?>><span><?= e($caption) ?></span></label><?php endforeach ?>
        </div></fieldset>
    </form>
    <p><?= e($list['total']) ?> <?= $list['total']===1?'coincidencia':'coincidencias' ?></p>
    <?php if ($selectedRecipient): ?><p><a href="administrador.php?<?= e(http_build_query(['module'=>'saldos_tarjetas','q'=>$search,'estado'=>$balanceState])) ?>">← Volver al saldo por cliente</a></p><h2>Detalle de tarjetas</h2><?php else: ?><h2>Saldo total por destinatario</h2><p>El total disponible suma únicamente tarjetas activas, con saldo y sin vencer. Cada moneda se muestra por separado.</p><?php endif ?>
    <div class="table-scroll"><table data-server-paginated><thead><tr><th>Destinatario</th><th><?= $selectedRecipient?'Estado':'Tarjetas' ?></th><th>Saldo disponible</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($list['rows'] as $card): ?><tr><td class="list-value"><strong><?= e($card['nombre_destinatario']) ?></strong><?php if ($selectedRecipient): ?><small class="balance-card-number">Tarjeta #<?= e($card['id_tarjeta']) ?></small><?php endif ?></td><td><?= $selectedRecipient?e(GiftCardBalances::STATES[$card['estado']]??$card['estado']):e($card['tarjetas']) ?></td><td><?= e($card['moneda']) ?> <?= e(number_format((float)($selectedRecipient?$card['disponible']:$card['saldo_actual']),2,',','.')) ?></td><td><?php if ($selectedRecipient): ?><a href="administrador.php?module=tarjetas&amp;mode=view&amp;id_tarjeta=<?= e($card['id_tarjeta']) ?>">Ver tarjeta →</a><?php else: ?><a href="administrador.php?<?= e(http_build_query(['module'=>'saldos_tarjetas','destinatario'=>$card['destinatario'],'q'=>$search,'estado'=>$balanceState,'moneda'=>$card['moneda']])) ?>">Ver detalle de tarjetas →</a><?php endif ?></td></tr><?php endforeach ?>
        <?php if (!$list['rows']): ?><tr><td colspan="4">No hay coincidencias con ese nombre y estado.</td></tr><?php endif ?>
    </tbody></table></div>
    <?php renderPagination($list['total'],$list['page'],['module'=>'saldos_tarjetas','q'=>$search,'estado'=>$balanceState,'destinatario'=>$selectedRecipient,'moneda'=>$balanceCurrency]); ?>
</section>
