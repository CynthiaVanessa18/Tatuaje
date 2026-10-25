<?php
declare(strict_types=1);

$formFaq = $editingFaq ?? [
    'id_pregunta' => '',
    'categoria' => '',
    'pregunta' => '',
    'respuesta' => '',
    'orden' => 0,
    'activo' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($faqError ?? null) !== null && ($_POST['accion'] ?? 'guardar') === 'guardar') {
    $formFaq = array_merge($formFaq, $_POST);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050304">
    <title>Preguntas frecuentes · Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/admin-preguntas.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/admin-preguntas.css')) ?>">
    <link rel="stylesheet" href="../assets/css/admin-navegacion.css">
    <?php responsiveAssets(); ?>
</head>
<body class="admin-artists admin-faq">
<?php require __DIR__.'/../admin-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading">
        <div><p class="admin-eyebrow">CONOCIMIENTO DEL ESTUDIO</p><h1>Preguntas frecuentes</h1><p>Administra las respuestas que orientan a los clientes antes de solicitar una cotización o asistir a una sesión.</p></div>
        <a class="admin-button admin-button--ghost" href="../preguntas/" target="_blank" rel="noopener">Ver página pública ↗</a>
    </header>

    <?php if ($faqNotice !== null): ?><div class="admin-faq-notice" role="status"><span>✓</span><?= e($faqNotice) ?></div><?php endif ?>
    <?php if ($faqError !== null): ?><div class="admin-faq-notice admin-faq-notice--error" role="alert"><span>!</span><?= e($faqError) ?></div><?php endif ?>

    <section class="admin-faq-overview" aria-label="Resumen de preguntas frecuentes">
        <div><p>ARCHIVO DE RESPUESTAS</p><strong><?= e($faqSummary['total']) ?></strong><span><?= e($faqSummary['activas']) ?> públicas · <?= e($faqSummary['ocultas']) ?> ocultas · <?= e($faqSummary['categorias']) ?> categorías</span></div>
        <i aria-hidden="true">?</i>
    </section>

    <div class="admin-faq-layout">
        <section class="admin-faq-editor" id="editor-pregunta">
            <header><p><?= $editingFaq === null ? 'NUEVA ENTRADA' : 'EDITANDO #' . e($editingFaq['id_pregunta']) ?></p><h2><?= $editingFaq === null ? 'Crear respuesta' : 'Modificar respuesta' ?></h2></header>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id_pregunta" value="<?= e($formFaq['id_pregunta'] ?? '') ?>">
                <label><span>Categoría *</span><input name="categoria" maxlength="100" required value="<?= e($formFaq['categoria'] ?? '') ?>" placeholder="Ej. Preparación"></label>
                <label><span>Pregunta *</span><textarea name="pregunta" rows="3" maxlength="500" required placeholder="¿Qué necesita saber el cliente?"><?= e($formFaq['pregunta'] ?? '') ?></textarea></label>
                <label><span>Respuesta *</span><textarea name="respuesta" rows="7" required placeholder="Escribe una respuesta clara y completa."><?= e($formFaq['respuesta'] ?? '') ?></textarea></label>
                <div class="admin-faq-editor__row">
                    <label><span>Orden *</span><input type="number" name="orden" min="0" max="9999" required value="<?= e($formFaq['orden'] ?? 0) ?>"></label>
                    <label class="admin-faq-switch"><input type="checkbox" name="activo" value="1" <?= !empty($formFaq['activo']) ? 'checked' : '' ?>><i></i><span>Visible en el sitio</span></label>
                </div>
                <div class="admin-faq-editor__actions"><button type="submit"><?= $editingFaq === null ? 'Crear pregunta' : 'Guardar cambios' ?></button><?php if ($editingFaq !== null): ?><a href="preguntas.php">Cancelar edición</a><?php endif ?></div>
            </form>
        </section>

        <section class="admin-faq-archive">
            <header><div><p>ARCHIVO ACTUAL</p><h2>Respuestas publicadas</h2></div><b><?= e((string) count($adminFaqs)) ?></b></header>
            <form class="admin-faq-filters" method="get">
                <label><span>Buscar</span><input type="search" name="buscar" value="<?= e($searchValue) ?>" placeholder="Pregunta o respuesta"></label>
                <label><span>Categoría</span><select name="categoria"><option value="">Todas</option><?php foreach ($adminFaqCategories as $category): ?><option value="<?= e($category['categoria']) ?>" <?= $categoryValue === $category['categoria'] ? 'selected' : '' ?>><?= e($category['categoria']) ?> (<?= e($category['total']) ?>)</option><?php endforeach ?></select></label>
                <button type="submit">Filtrar</button>
                <?php if ($searchValue !== '' || $categoryValue !== ''): ?><a href="preguntas.php">Limpiar</a><?php endif ?>
            </form>

            <?php if ($adminFaqs === []): ?>
                <div class="admin-faq-empty"><span>?</span><p>No hay preguntas que coincidan con el filtro.</p></div>
            <?php else: ?>
                <div class="admin-faq-list">
                    <?php foreach ($adminFaqs as $faq): ?>
                        <article class="admin-faq-card<?= !empty($faq['activo']) ? '' : ' is-hidden' ?>">
                            <div class="admin-faq-card__number"><?= e(str_pad((string) $faq['orden'], 2, '0', STR_PAD_LEFT)) ?></div>
                            <div class="admin-faq-card__content"><p><?= e($faq['categoria'] ?: 'General') ?> · #<?= e($faq['id_pregunta']) ?></p><h3><?= e($faq['pregunta']) ?></h3><div><?= nl2br(e($faq['respuesta'])) ?></div></div>
                            <div class="admin-faq-card__actions">
                                <span class="admin-faq-status"><?= !empty($faq['activo']) ? 'PÚBLICA' : 'OCULTA' ?></span>
                                <a href="preguntas.php?editar=<?= e($faq['id_pregunta']) ?>#editor-pregunta">Editar</a>
                                <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_pregunta" value="<?= e($faq['id_pregunta']) ?>"><input type="hidden" name="activo" value="<?= !empty($faq['activo']) ? '0' : '1' ?>"><button type="submit"><?= !empty($faq['activo']) ? 'Ocultar' : 'Publicar' ?></button></form>
                            </div>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>
    </div>
</main>
</body>
</html>
