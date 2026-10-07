<?php
declare(strict_types=1);

$editing = $mode === 'edit' && $form !== null;
$creating = $mode === 'create' && $form !== null;
$formImage = $form !== null
    ? adminGalleryImageUrl($form['imagen_url'] ?? null)
    : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080506">
    <title>Gestión de galería · Tinta Viva</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/admin-galeria.css">
    <link rel="stylesheet" href="../assets/css/admin-navegacion.css">
<?php responsiveAssets(); ?>
</head>

<body class="admin-artists admin-gallery">
<?php require __DIR__.'/../admin-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading">
        <div>
            <p class="admin-eyebrow">ARCHIVO DE OBRAS</p>
            <h1>Galería fotográfica</h1>
            <p>Publica nuevas piezas y conserva ocultas las obras que todavía no deben aparecer frente al cliente.</p>
        </div>

        <?php if (!$creating): ?>
            <a class="admin-button admin-button--primary" href="galeria.php?mode=create">+ Añadir obra</a>
        <?php endif ?>
    </header>

    <?php if ($notice !== null): ?>
        <div class="admin-notice" role="status"><span aria-hidden="true">✓</span><?= e($notice) ?></div>
    <?php endif ?>

    <?php if ($error !== null): ?>
        <div class="admin-notice admin-notice--error" role="alert"><span aria-hidden="true">!</span><?= e($error) ?></div>
    <?php endif ?>

    <?php if ($creating || $editing): ?>
        <section class="artist-editor gallery-editor">
            <div class="artist-editor__heading">
                <div>
                    <p class="admin-eyebrow"><?= $creating ? 'NUEVA OBRA' : 'EDITAR OBRA' ?></p>
                    <h2><?= $creating ? 'Abrir un nuevo relicario' : e($form['titulo'] ?? 'Obra') ?></h2>
                </div>
                <a href="galeria.php">Cerrar editor ×</a>
            </div>

            <form method="post" enctype="multipart/form-data" class="artist-form gallery-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="action" value="save">

                <?php if ($editing): ?>
                    <input type="hidden" name="id_tatuaje_realizado" value="<?= e($form['id_tatuaje_realizado']) ?>">
                <?php endif ?>

                <div class="artist-form__visual gallery-form__visual">
                    <div class="artist-form__portrait gallery-form__portrait">
                        <?php if ($formImage !== null): ?>
                            <img src="<?= e($formImage) ?>" alt="Fotografía actual de <?= e($form['titulo'] ?? 'la obra') ?>">
                        <?php else: ?>
                            <span aria-hidden="true">†</span>
                        <?php endif ?>
                    </div>

                    <label class="upload-field">
                        <span><?= $editing ? 'Sustituir fotografía' : 'Fotografía de la obra *' ?></span>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" <?= $creating ? 'required' : '' ?>>
                        <small>JPG, PNG o WEBP · máximo 8 MB · mínimo 600 × 600 px.</small>
                    </label>

                    <label class="active-switch">
                        <input type="checkbox" name="autorizacion_publicacion" value="1" <?= !empty($form['autorizacion_publicacion']) ? 'checked' : '' ?>>
                        <span></span>
                        Cliente autorizó la publicación
                    </label>

                    <label class="active-switch">
                        <input type="checkbox" name="publicado" value="1" <?= !empty($form['publicado']) ? 'checked' : '' ?>>
                        <span></span>
                        Visible en la galería pública
                    </label>
                </div>

                <div>
                    <div class="field-grid">
                        <label>
                            <span>Cliente *</span>
                            <select name="id_cliente" required>
                                <option value="">Selecciona un cliente</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= e($client['id_cliente']) ?>" <?= (string) ($form['id_cliente'] ?? '') === (string) $client['id_cliente'] ? 'selected' : '' ?>><?= e($client['nombre_publico']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </label>

                        <label>
                            <span>Artista *</span>
                            <select name="id_artista" required>
                                <option value="">Selecciona un artista</option>
                                <?php foreach ($artists as $galleryArtist): ?>
                                    <option value="<?= e($galleryArtist['id_artista']) ?>" <?= (string) ($form['id_artista'] ?? '') === (string) $galleryArtist['id_artista'] ? 'selected' : '' ?>><?= e($galleryArtist['nombre_publico']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </label>

                        <label>
                            <span>Estilo *</span>
                            <select name="id_categoria" required>
                                <option value="">Selecciona un estilo</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e($category['id_categoria']) ?>" <?= (string) ($form['id_categoria'] ?? '') === (string) $category['id_categoria'] ? 'selected' : '' ?>><?= e($category['nombre']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </label>

                        <label>
                            <span>Fecha de realización *</span>
                            <input type="date" name="fecha_realizacion" max="<?= e(date('Y-m-d')) ?>" value="<?= e($form['fecha_realizacion'] ?? '') ?>" required>
                        </label>

                        <label>
                            <span>Título *</span>
                            <input name="titulo" maxlength="180" value="<?= e($form['titulo'] ?? '') ?>" required>
                        </label>

                        <label>
                            <span>Identificador público</span>
                            <input name="slug" maxlength="220" value="<?= e($form['slug'] ?? '') ?>" placeholder="Se genera desde el título">
                        </label>

                        <label class="field-grid__wide">
                            <span>Historia de la obra</span>
                            <textarea name="descripcion" rows="5" maxlength="5000" placeholder="Concepto, símbolos y técnica utilizada..."><?= e($form['descripcion'] ?? '') ?></textarea>
                        </label>

                        <label>
                            <span>Descripción de la fotografía</span>
                            <input name="imagen_descripcion" maxlength="255" value="<?= e($form['imagen_descripcion'] ?? '') ?>">
                        </label>

                        <label>
                            <span>Texto alternativo</span>
                            <input name="texto_alternativo" maxlength="255" value="<?= e($form['texto_alternativo'] ?? '') ?>" placeholder="Describe lo visible para accesibilidad">
                        </label>
                    </div>

                    <div class="artist-form__actions">
                        <button class="admin-button admin-button--primary" type="submit">Guardar obra</button>
                        <a href="galeria.php">Cancelar</a>
                    </div>
                </div>
            </form>
        </section>
    <?php endif ?>

    <section class="artist-management gallery-list">
        <form class="artist-search" method="get">
            <label>
                <span>Buscar obra</span>
                <input name="buscar" value="<?= e($search) ?>" placeholder="Título, artista, estilo o slug...">
            </label>

            <label>
                <span>Visibilidad</span>
                <select name="estado">
                    <option value="">Todas</option>
                    <option value="publicado" <?= $status === 'publicado' ? 'selected' : '' ?>>Publicadas</option>
                    <option value="oculto" <?= $status === 'oculto' ? 'selected' : '' ?>>Ocultas</option>
                    <option value="sin_autorizacion" <?= $status === 'sin_autorizacion' ? 'selected' : '' ?>>Sin autorización</option>
                </select>
            </label>

            <button class="admin-button admin-button--primary" type="submit">Filtrar</button>
            <?php if ($search !== '' || $status !== ''): ?><a href="galeria.php">Limpiar</a><?php endif ?>
        </form>

        <div class="artist-management__summary">
            <p><strong><?= e(count($works)) ?></strong> <?= count($works) === 1 ? 'obra encontrada' : 'obras encontradas' ?></p>
            <p>Ocultar conserva artista, cliente, historial y fotografías.</p>
        </div>

        <?php if ($works === []): ?>
            <div class="admin-empty"><span aria-hidden="true">†</span><h2>El archivo está vacío</h2><p>Añade una obra o modifica los filtros.</p></div>
        <?php else: ?>
            <div class="gallery-admin-grid">
                <?php foreach ($works as $position => $work): ?>
                    <?php $imageUrl = adminGalleryImageUrl($work['imagen_url'] ?? null); ?>
                    <article class="gallery-admin-card">
                        <div class="gallery-admin-card__image">
                            <?php if ($imageUrl !== null): ?>
                                <img src="<?= e($imageUrl) ?>" alt="<?= e($work['texto_alternativo'] ?: $work['titulo']) ?>" loading="lazy">
                            <?php else: ?>
                                <span aria-hidden="true">†</span>
                            <?php endif ?>
                            <b><?= str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT) ?></b>
                            <em class="<?= !empty($work['publicado']) ? 'is-visible' : '' ?>"><?= !empty($work['publicado']) ? 'Pública' : 'Oculta' ?></em>
                        </div>

                        <div class="gallery-admin-card__copy">
                            <p><?= e($work['categoria']) ?> · <?= e(date('Y', strtotime((string) $work['fecha_realizacion']))) ?></p>
                            <h2><?= e($work['titulo']) ?></h2>
                            <a href="../artistas/ver.php?artista=<?= rawurlencode((string) ($work['artista_slug'] ?: $work['id_artista'])) ?>" target="_blank" rel="noopener" class="gallery-admin-card__artist"><?= e($work['artista']) ?> ↗</a>

                            <dl>
                                <div><dt>Fotos</dt><dd><?= e($work['total_imagenes']) ?></dd></div>
                                <div><dt>Permiso</dt><dd><?= !empty($work['autorizacion_publicacion']) ? 'Sí' : 'No' ?></dd></div>
                            </dl>

                            <div class="gallery-admin-card__actions">
                                <a href="galeria.php?mode=edit&amp;id=<?= e($work['id_tatuaje_realizado']) ?>">Editar</a>

                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id_tatuaje_realizado" value="<?= e($work['id_tatuaje_realizado']) ?>">
                                    <input type="hidden" name="publicado" value="<?= !empty($work['publicado']) ? '0' : '1' ?>">
                                    <button type="submit"><?= !empty($work['publicado']) ? 'Ocultar' : 'Publicar' ?></button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</main>
</body>
</html>
