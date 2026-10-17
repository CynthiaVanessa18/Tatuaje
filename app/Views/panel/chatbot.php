<?php
declare(strict_types=1);
$sourceForm = $editingChatbotSource && $editingChatbotSource['tipo'] === 'contenido_personalizado'
    ? $editingChatbotSource
    : ['id_fuente' => '', 'titulo' => '', 'contenido' => '', 'activo' => 1];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $chatbotError !== null && ($_POST['accion'] ?? '') === 'guardar_fuente') {
    $sourceForm = array_merge($sourceForm, $_POST);
}
$usefulPercentage = (int) ($chatbotSummary['opiniones'] ?? 0) > 0
    ? (int) round(((int) $chatbotSummary['utiles'] / (int) $chatbotSummary['opiniones']) * 100)
    : 0;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050304">
    <title>Oráculo · Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/admin-chatbot.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/admin-chatbot.css')) ?>">
    <link rel="stylesheet" href="../assets/css/admin-navegacion.css">
    <?php responsiveAssets(); ?>
</head>
<body class="admin-artists admin-chatbot">
<?php require __DIR__.'/../admin-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading admin-chatbot__heading">
        <div><p class="admin-eyebrow">INTELIGENCIA DEL ARCHIVO</p><h1>El Oráculo de Tinta</h1><p>Controla lo que responde el asistente, revisa conversaciones y atiende las consultas que piden intervención humana.</p></div>
        <a class="admin-button admin-button--ghost" href="../index.php" target="_blank" rel="noopener">Probar en el sitio ↗</a>
    </header>

    <?php if ($chatbotNotice !== null): ?><div class="admin-chatbot__notice" role="status">✦ <?= e($chatbotNotice) ?></div><?php endif ?>
    <?php if ($chatbotError !== null): ?><div class="admin-chatbot__notice is-error" role="alert">! <?= e($chatbotError) ?></div><?php endif ?>

    <section class="admin-chatbot__summary" aria-label="Resumen del chatbot">
        <article><small>I</small><strong><?= e($chatbotSummary['conversaciones'] ?? 0) ?></strong><span>Conversaciones</span></article>
        <article><small>II</small><strong><?= e($chatbotSummary['escaladas'] ?? 0) ?></strong><span>Esperan al estudio</span></article>
        <article><small>III</small><strong><?= e($chatbotSummary['fuentes_activas'] ?? 0) ?></strong><span>Fuentes activas</span></article>
        <article><small>IV</small><strong><?= e($usefulPercentage) ?>%</strong><span>Respuestas útiles</span></article>
    </section>

    <div class="admin-chatbot__grid">
        <section class="admin-chatbot__conversations">
            <header><div><p>VOCES DEL UMBRAL</p><h2>Conversaciones</h2></div><b><?= e(count($chatbotConversations)) ?></b></header>
            <form class="admin-chatbot__filters" method="get">
                <input type="search" name="buscar" value="<?= e($chatbotSearch) ?>" placeholder="Cliente, correo o número">
                <select name="estado"><option value="">Todos los estados</option><?php foreach (['escalada'=>'Escaladas','activa'=>'Activas','cerrada'=>'Cerradas'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $chatbotStateFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select>
                <button type="submit">Filtrar</button>
            </form>
            <div class="admin-chatbot__conversation-list">
                <?php if ($chatbotConversations === []): ?><p class="admin-chatbot__empty">No hay conversaciones con este filtro.</p><?php endif ?>
                <?php foreach ($chatbotConversations as $conversation): ?>
                    <a href="chatbot.php?conversacion=<?= e($conversation['id_conversacion']) ?>" class="<?= $selectedConversation && (int)$selectedConversation['id_conversacion'] === (int)$conversation['id_conversacion'] ? 'is-current' : '' ?>">
                        <span class="admin-chatbot__state is-<?= e($conversation['estado']) ?>"></span>
                        <div><strong><?= e($conversation['persona']) ?></strong><small>#<?= e($conversation['id_conversacion']) ?> · <?= e($conversation['total_mensajes']) ?> mensajes</small><p><?= e($conversation['ultimo_mensaje'] ?: 'Sin mensajes') ?></p></div>
                        <time><?= e((new DateTimeImmutable($conversation['fecha_ultima_actividad']))->format('d/m H:i')) ?></time>
                    </a>
                <?php endforeach ?>
            </div>
        </section>

        <section class="admin-chatbot__detail">
            <?php if ($selectedConversation === null): ?>
                <div class="admin-chatbot__detail-empty"><span>◉</span><h2>Selecciona una conversación</h2><p>Las consultas escaladas aparecen primero para que el estudio pueda atenderlas.</p></div>
            <?php else: ?>
                <header><div><p>CONVERSACIÓN #<?= e($selectedConversation['id_conversacion']) ?></p><h2><?= e($selectedConversation['persona']) ?></h2><span><?= e($selectedConversation['correo'] ?: 'Visitante sin cuenta') ?></span></div><b class="is-<?= e($selectedConversation['estado']) ?>"><?= e(strtoupper($selectedConversation['estado'])) ?></b></header>
                <div class="admin-chatbot__messages">
                    <?php foreach ($selectedMessages as $message): ?><article class="is-<?= e($message['rol']) ?>"><small><?= $message['rol'] === 'usuario' ? 'PERSONA' : ($message['proveedor'] === 'administracion' ? 'ESTUDIO' : 'ORÁCULO') ?></small><p><?= nl2br(e($message['contenido'])) ?></p><time><?= e((new DateTimeImmutable($message['fecha']))->format('d/m/Y H:i')) ?></time></article><?php endforeach ?>
                </div>
                <form class="admin-chatbot__reply" method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="responder"><input type="hidden" name="id_conversacion" value="<?= e($selectedConversation['id_conversacion']) ?>">
                    <label><span>Respuesta humana</span><textarea name="respuesta" maxlength="1500" rows="3" required placeholder="Escribe una respuesta que aparecerá en el chat…"></textarea></label><button type="submit">Responder como estudio</button>
                </form>
                <div class="admin-chatbot__state-actions">
                    <?php foreach (['activa'=>'Reabrir','escalada'=>'Marcar para atención','cerrada'=>'Cerrar'] as $state=>$label): if ($selectedConversation['estado'] === $state) continue; ?>
                        <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="estado_conversacion"><input type="hidden" name="id_conversacion" value="<?= e($selectedConversation['id_conversacion']) ?>"><input type="hidden" name="estado" value="<?= e($state) ?>"><button type="submit"><?= e($label) ?></button></form>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>
    </div>

    <div class="admin-chatbot__knowledge">
        <section class="admin-chatbot__voice">
            <header><p>VOZ DEL ORÁCULO</p><h2>Configuración</h2></header>
            <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="configuracion">
                <label><span>Bienvenida</span><textarea name="mensaje_bienvenida" rows="3" required><?= e($chatbotConfig['mensaje_bienvenida'] ?? '') ?></textarea></label>
                <label><span>Cuando no conoce la respuesta</span><textarea name="mensaje_sin_respuesta" rows="3" required><?= e($chatbotConfig['mensaje_sin_respuesta'] ?? '') ?></textarea></label>
                <label><span>Tono</span><input name="tono_respuesta" required value="<?= e($chatbotConfig['tono_respuesta'] ?? '') ?>"></label>
                <button type="submit">Guardar voz</button>
            </form>
        </section>

        <section class="admin-chatbot__source-editor" id="fuente-editor">
            <header><p><?= $sourceForm['id_fuente'] ? 'EDITAR CONOCIMIENTO' : 'NUEVO CONOCIMIENTO' ?></p><h2><?= $sourceForm['id_fuente'] ? 'Modificar respuesta' : 'Enseñar al oráculo' ?></h2></header>
            <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="guardar_fuente"><input type="hidden" name="id_fuente" value="<?= e($sourceForm['id_fuente']) ?>">
                <label><span>Tema o pregunta</span><input name="titulo" maxlength="255" required value="<?= e($sourceForm['titulo']) ?>" placeholder="Ej. Métodos de pago"></label>
                <label><span>Respuesta segura</span><textarea name="contenido" rows="7" required><?= e($sourceForm['contenido']) ?></textarea></label>
                <label class="admin-chatbot__check"><input type="checkbox" name="activo" value="1" <?= !empty($sourceForm['activo']) ? 'checked' : '' ?>><span>Usar en las respuestas</span></label>
                <div><button type="submit"><?= $sourceForm['id_fuente'] ? 'Guardar cambio' : 'Agregar conocimiento' ?></button><?php if ($sourceForm['id_fuente']): ?><a href="chatbot.php#fuente-editor">Cancelar</a><?php endif ?></div>
            </form>
        </section>

        <section class="admin-chatbot__sources">
            <header><p>FUENTES VINCULADAS</p><h2>Archivo de conocimiento</h2></header>
            <div><?php foreach ($chatbotSources as $source): ?><article class="<?= $source['activo'] ? '' : 'is-off' ?>"><small><?= e(str_replace('_', ' ', strtoupper($source['tipo']))) ?></small><h3><?= e($source['titulo_resuelto']) ?></h3><p><?= e($source['contenido_resuelto']) ?></p><footer><?php if ($source['tipo'] === 'contenido_personalizado'): ?><a href="chatbot.php?editar_fuente=<?= e($source['id_fuente']) ?>#fuente-editor">Editar</a><?php else: ?><span>Se edita en su módulo original</span><?php endif ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="estado_fuente"><input type="hidden" name="id_fuente" value="<?= e($source['id_fuente']) ?>"><input type="hidden" name="activo" value="<?= $source['activo'] ? '0' : '1' ?>"><button type="submit"><?= $source['activo'] ? 'Desactivar' : 'Activar' ?></button></form></footer></article><?php endforeach ?></div>
        </section>
    </div>
</main>
</body>
</html>
