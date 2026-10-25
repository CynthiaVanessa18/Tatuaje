<?php
declare(strict_types=1);

$formCare = $editingCare ?? [
    'id_cuidado' => '',
    'titulo' => '',
    'slug' => '',
    'categoria' => '',
    'contenido' => '',
    'imagen_url' => '',
    'texto_alternativo' => '',
    'orden' => 0,
    'activo' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($careError ?? null) !== null && ($_POST['accion'] ?? 'guardar') === 'guardar') {
    $formCare = array_merge($formCare, $_POST);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050304">
    <title>Cuidados · Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/admin-cuidados.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/admin-cuidados.css')) ?>">
    <link rel="stylesheet" href="../assets/css/admin-navegacion.css">
    <?php responsiveAssets(); ?>
</head>
<body class="admin-artists admin-care">
<?php require __DIR__.'/../admin-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading">
        <div><p class="admin-eyebrow">RITUAL DE CICATRIZACIÓN</p><h1>Cuidados del tatuaje</h1><p>Organiza las instrucciones que acompañan al cliente después de cada sesión y mantén visible solamente la información vigente.</p></div>
        <a class="admin-button admin-button--ghost" href="../cuidados/" target="_blank" rel="noopener">Ver guía pública ↗</a>
    </header>

    <?php if ($careNotice !== null): ?><div class="admin-care-notice" role="status"><span>✓</span><?= e($careNotice) ?></div><?php endif ?>
    <?php if ($careError !== null): ?><div class="admin-care-notice admin-care-notice--error" role="alert"><span>!</span><?= e($careError) ?></div><?php endif ?>

    <section class="admin-care-overview"><div><p>GUÍA ACTUAL</p><strong><?= e($careSummary['total']) ?></strong><span><?= e($careSummary['activos']) ?> instrucciones públicas · <?= e($careSummary['ocultos']) ?> ocultas</span></div><i aria-hidden="true">†</i></section>

    <div class="admin-care-layout">
        <section class="admin-care-editor" id="editor-cuidado">
            <header><p><?= $editingCare === null ? 'NUEVA INSTRUCCIÓN' : 'EDITANDO #' . e($editingCare['id_cuidado']) ?></p><h2><?= $editingCare === null ? 'Añadir cuidado' : 'Modificar cuidado' ?></h2></header>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id_cuidado" value="<?= e($formCare['id_cuidado'] ?? '') ?>">
                <label><span>Título *</span><input name="titulo" maxlength="180" required value="<?= e($formCare['titulo'] ?? '') ?>" placeholder="Ej. Las primeras horas"></label>
                <div class="admin-care-editor__row"><label><span>Categoría *</span><input name="categoria" maxlength="100" required value="<?= e($formCare['categoria'] ?? '') ?>" placeholder="Ej. Primer día"></label><label><span>Orden *</span><input type="number" name="orden" min="0" max="9999" required value="<?= e($formCare['orden'] ?? 0) ?>"></label></div>
                <label><span>Identificador</span><input name="slug" maxlength="220" value="<?= e($formCare['slug'] ?? '') ?>" placeholder="Se genera automáticamente"></label>
                <label><span>Instrucción *</span><textarea name="contenido" rows="6" required placeholder="Explica el cuidado de manera clara."><?= e($formCare['contenido'] ?? '') ?></textarea></label>
                <label><span>Ruta de imagen opcional</span><input name="imagen_url" maxlength="1024" value="<?= e($formCare['imagen_url'] ?? '') ?>" placeholder="assets/images/... o https://..."></label>
                <label><span>Texto alternativo</span><input name="texto_alternativo" maxlength="255" value="<?= e($formCare['texto_alternativo'] ?? '') ?>" placeholder="Describe la imagen"></label>
                <label class="admin-care-switch"><input type="checkbox" name="activo" value="1" <?= !empty($formCare['activo']) ? 'checked' : '' ?>><i></i><span>Visible en la guía pública</span></label>
                <div class="admin-care-editor__actions"><button type="submit"><?= $editingCare === null ? 'Crear instrucción' : 'Guardar cambios' ?></button><?php if ($editingCare !== null): ?><a href="cuidados.php">Cancelar edición</a><?php endif ?></div>
            </form>
        </section>

        <section class="admin-care-archive">
            <header><div><p>CÓDICE ACTUAL</p><h2>Etapas de cuidado</h2></div><b><?= e((string) count($adminCareInstructions)) ?></b></header>
            <form class="admin-care-filters" method="get"><label><span>Buscar</span><input type="search" name="buscar" value="<?= e($careSearch) ?>" placeholder="Título, categoría o contenido"></label><label><span>Estado</span><select name="estado"><option value="">Todos</option><option value="activo" <?= $careStatus === 'activo' ? 'selected' : '' ?>>Públicos</option><option value="oculto" <?= $careStatus === 'oculto' ? 'selected' : '' ?>>Ocultos</option></select></label><button type="submit">Filtrar</button><?php if ($careSearch !== '' || $careStatus !== ''): ?><a href="cuidados.php">Limpiar</a><?php endif ?></form>

            <?php if ($adminCareInstructions === []): ?>
                <div class="admin-care-empty"><span>†</span><p>No hay instrucciones que coincidan con el filtro.</p></div>
            <?php else: ?>
                <div class="admin-care-list">
                    <?php foreach ($adminCareInstructions as $care): ?>
                        <article class="admin-care-card<?= !empty($care['activo']) ? '' : ' is-hidden' ?>">
                            <div class="admin-care-card__number"><?= e(str_pad((string) $care['orden'], 2, '0', STR_PAD_LEFT)) ?></div>
                            <div class="admin-care-card__content"><p><?= e($care['categoria'] ?: 'General') ?> · #<?= e($care['id_cuidado']) ?></p><h3><?= e($care['titulo']) ?></h3><div><?= nl2br(e($care['contenido'])) ?></div></div>
                            <div class="admin-care-card__actions"><span><?= !empty($care['activo']) ? 'PÚBLICO' : 'OCULTO' ?></span><a href="cuidados.php?editar=<?= e($care['id_cuidado']) ?>#editor-cuidado">Editar</a><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_cuidado" value="<?= e($care['id_cuidado']) ?>"><input type="hidden" name="activo" value="<?= !empty($care['activo']) ? '0' : '1' ?>"><button type="submit"><?= !empty($care['activo']) ? 'Ocultar' : 'Publicar' ?></button></form></div>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>
    </div>
</main>
</body>
</html>
