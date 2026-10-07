<?php
declare(strict_types=1);

function adminArtistAsset(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $value = trim(str_replace('\\', '/', $value));
    $scheme = parse_url($value, PHP_URL_SCHEME);

    if ($scheme !== null) {
        if (
            !in_array(strtolower($scheme), ['http', 'https'], true)
            || filter_var($value, FILTER_VALIDATE_URL) === false
        ) {
            return null;
        }

        return $value;
    }

    if (str_starts_with($value, '/')) {
        return $value;
    }

    return '../' . ltrim($value, '/');
}

function adminArtistName(array $artist): string
{
    $artisticName = trim((string) ($artist['nombre_artistico'] ?? ''));

    return $artisticName !== ''
        ? $artisticName
        : trim(
            (string) ($artist['nombre'] ?? '')
            . ' '
            . (string) ($artist['apellidos'] ?? '')
        );
}

$editing = $mode === 'edit' && $form !== null;
$creating = $mode === 'create' && $form !== null;
$formImage = $form !== null
    ? adminArtistAsset($form['foto_url'] ?? null)
    : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080506">

    <title>Gestión de artistas · Tinta Viva</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/admin-navegacion.css">
<?php responsiveAssets(); ?>
</head>

<body class="admin-artists">
<?php require __DIR__.'/../admin-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading">
        <div>
            <p class="admin-eyebrow">ARCHIVO DE MAESTROS</p>
            <h1>Perfiles de artistas</h1>
            <p>
                Administra la información pública sin alterar citas,
                trabajos ni historial de cada artista.
            </p>
        </div>

        <?php if (!$creating): ?>
            <a class="admin-button admin-button--primary" href="artistas.php?mode=create">
                + Crear perfil
            </a>
        <?php endif ?>
    </header>

    <?php if ($notice !== null): ?>
        <div class="admin-notice" role="status">
            <span aria-hidden="true">✓</span>
            <?= e($notice) ?>
        </div>
    <?php endif ?>

    <?php if ($error !== null): ?>
        <div class="admin-notice admin-notice--error" role="alert">
            <span aria-hidden="true">!</span>
            <?= e($error) ?>
        </div>
    <?php endif ?>

    <?php if ($creating || $editing): ?>
        <section class="artist-editor">
            <div class="artist-editor__heading">
                <div>
                    <p class="admin-eyebrow">
                        <?= $creating ? 'NUEVO PERFIL' : 'EDITAR PERFIL' ?>
                    </p>

                    <h2>
                        <?= $creating
                            ? 'Invocar un nuevo artista'
                            : e(adminArtistName($form)) ?>
                    </h2>
                </div>

                <a href="artistas.php">Cerrar editor ×</a>
            </div>

            <?php if ($creating && $availableAccounts === []): ?>
                <div class="admin-notice admin-notice--warning">
                    No hay cuentas activas con rol de artista disponibles.
                    Primero crea la cuenta correspondiente.
                </div>
            <?php endif ?>

            <form
                method="post"
                enctype="multipart/form-data"
                class="artist-form"
            >
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="action" value="save">

                <?php if ($editing): ?>
                    <input
                        type="hidden"
                        name="id_artista"
                        value="<?= e($form['id_artista']) ?>"
                    >
                <?php endif ?>

                <div class="artist-form__visual">
                    <div class="artist-form__portrait">
                        <?php if ($formImage !== null): ?>
                            <img
                                src="<?= e($formImage) ?>"
                                alt="Fotografía actual del artista"
                            >
                        <?php else: ?>
                            <span aria-hidden="true">†</span>
                        <?php endif ?>
                    </div>

                    <label class="upload-field">
                        <span>Fotografía del artista</span>
                        <input
                            type="file"
                            name="foto"
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <small>
                            JPG, PNG o WEBP · máximo 5 MB · mínimo 300 × 300 px.
                        </small>
                    </label>

                    <label class="active-switch">
                        <input
                            type="checkbox"
                            name="activo"
                            value="1"
                            <?= !empty($form['activo']) ? 'checked' : '' ?>
                        >
                        <span></span>
                        Perfil visible para clientes
                    </label>
                </div>

                <div class="artist-form__fields">
                    <div class="field-grid">
                        <label>
                            <span>Cuenta de acceso *</span>
                            <select
                                name="id_cuenta"
                                required
                                <?= $availableAccounts === [] ? 'disabled' : '' ?>
                            >
                                <option value="">Selecciona una cuenta…</option>

                                <?php foreach ($availableAccounts as $artistAccount): ?>
                                    <option
                                        value="<?= e($artistAccount['id_cuenta']) ?>"
                                        <?= (string) ($form['id_cuenta'] ?? '')
                                            === (string) $artistAccount['id_cuenta']
                                                ? 'selected'
                                                : '' ?>
                                    >
                                        <?= e($artistAccount['usuario']) ?>
                                        · <?= e($artistAccount['correo']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </label>

                        <label>
                            <span>Nombre artístico</span>
                            <input
                                type="text"
                                name="nombre_artistico"
                                maxlength="120"
                                value="<?= e($form['nombre_artistico'] ?? '') ?>"
                                placeholder="Ej. Nocturna"
                            >
                        </label>

                        <label>
                            <span>Nombre *</span>
                            <input
                                type="text"
                                name="nombre"
                                maxlength="100"
                                required
                                value="<?= e($form['nombre'] ?? '') ?>"
                            >
                        </label>

                        <label>
                            <span>Apellidos *</span>
                            <input
                                type="text"
                                name="apellidos"
                                maxlength="150"
                                required
                                value="<?= e($form['apellidos'] ?? '') ?>"
                            >
                        </label>

                        <label>
                            <span>Teléfono</span>
                            <input
                                type="tel"
                                name="telefono"
                                maxlength="25"
                                value="<?= e($form['telefono'] ?? '') ?>"
                            >
                        </label>

                        <label>
                            <span>Identificador público</span>
                            <input
                                type="text"
                                name="slug"
                                maxlength="160"
                                value="<?= e($form['slug'] ?? '') ?>"
                                placeholder="Se genera automáticamente"
                            >
                        </label>

                        <label class="field-grid__wide">
                            <span>Portafolio externo</span>
                            <input
                                type="url"
                                name="sitio_web_url"
                                maxlength="1024"
                                value="<?= e($form['sitio_web_url'] ?? '') ?>"
                                placeholder="https://example.com"
                            >
                            <small>
                                Usa example.com mientras los perfiles sean ficticios.
                            </small>
                        </label>

                        <label class="field-grid__wide">
                            <span>Biografía</span>
                            <textarea
                                name="biografia"
                                rows="6"
                                maxlength="5000"
                                placeholder="Describe su estilo, inspiración y trayectoria…"
                            ><?= e($form['biografia'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <fieldset class="specialty-picker">
                        <legend>Especialidades públicas</legend>

                        <div>
                            <?php foreach ($categories as $category): ?>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="especialidades[]"
                                        value="<?= e($category['id_categoria']) ?>"
                                        <?= in_array(
                                            (int) $category['id_categoria'],
                                            $selectedCategoryIds,
                                            true
                                        ) ? 'checked' : '' ?>
                                    >

                                    <span>
                                        <strong><?= e($category['nombre']) ?></strong>
                                        <small>
                                            <?= e(
                                                $category['descripcion']
                                                ?: 'Especialidad disponible'
                                            ) ?>
                                        </small>
                                    </span>
                                </label>
                            <?php endforeach ?>
                        </div>
                    </fieldset>

                    <div class="artist-form__actions">
                        <button
                            class="admin-button admin-button--primary"
                            type="submit"
                            <?= $creating && $availableAccounts === []
                                ? 'disabled'
                                : '' ?>
                        >
                            <?= $creating ? 'Crear perfil' : 'Guardar cambios' ?>
                        </button>

                        <a class="admin-button admin-button--ghost" href="artistas.php">
                            Cancelar
                        </a>

                        <?php if ($editing && !empty($form['activo'])): ?>
                            <a
                                class="editor-public-link"
                                href="../artistas/ver.php?artista=<?= e(urlencode(
                                    (string) ($form['slug'] ?: $form['id_artista'])
                                )) ?>"
                                target="_blank"
                                rel="noopener"
                            >
                                Ver perfil público ↗
                            </a>
                        <?php endif ?>
                    </div>
                </div>
            </form>
        </section>
    <?php endif ?>

    <section class="artist-management">
        <form method="get" class="artist-search">
            <label>
                <span>Buscar artista</span>
                <input
                    type="search"
                    name="buscar"
                    maxlength="100"
                    value="<?= e($search) ?>"
                    placeholder="Nombre, cuenta, correo o slug…"
                >
            </label>

            <label>
                <span>Visibilidad</span>
                <select name="estado">
                    <option value="">Todos</option>
                    <option value="activo" <?= $status === 'activo' ? 'selected' : '' ?>>
                        Visibles
                    </option>
                    <option value="inactivo" <?= $status === 'inactivo' ? 'selected' : '' ?>>
                        Ocultos
                    </option>
                </select>
            </label>

            <button class="admin-button admin-button--primary" type="submit">
                Filtrar
            </button>

            <?php if ($search !== '' || $status !== ''): ?>
                <a href="artistas.php">Limpiar</a>
            <?php endif ?>
        </form>

        <div class="artist-management__summary">
            <p>
                <strong><?= e(count($artists)) ?></strong>
                perfiles encontrados
            </p>

            <p>
                Ocultar conserva citas, obras, reseñas y certificaciones.
            </p>
        </div>

        <?php if ($artists === []): ?>
            <div class="admin-empty">
                <span aria-hidden="true">✦</span>
                <h2>No hay artistas para mostrar</h2>
                <p>Crea un perfil nuevo o cambia los filtros.</p>
            </div>
        <?php else: ?>
            <div class="admin-artist-grid">
                <?php foreach ($artists as $artist): ?>
                    <?php $artistImage = adminArtistAsset($artist['foto_url'] ?? null); ?>

                    <article class="admin-artist-card">
                        <div class="admin-artist-card__image">
                            <?php if ($artistImage !== null): ?>
                                <img
                                    src="<?= e($artistImage) ?>"
                                    alt="Retrato de <?= e(adminArtistName($artist)) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <span aria-hidden="true">†</span>
                            <?php endif ?>

                            <span class="status-badge <?= !empty($artist['activo'])
                                ? 'status-badge--active'
                                : 'status-badge--inactive' ?>">
                                <?= !empty($artist['activo']) ? 'Visible' : 'Oculto' ?>
                            </span>
                        </div>

                        <div class="admin-artist-card__body">
                            <p class="admin-artist-card__real-name">
                                <?= e(trim($artist['nombre'] . ' ' . $artist['apellidos'])) ?>
                            </p>

                            <h2><?= e(adminArtistName($artist)) ?></h2>

                            <p class="admin-artist-card__account">
                                <?= e($artist['usuario']) ?> · <?= e($artist['correo']) ?>
                            </p>

                            <p class="admin-artist-card__specialties">
                                <?= e($artist['especialidades'] ?: 'Sin especialidades') ?>
                            </p>

                            <div class="admin-artist-card__metrics">
                                <p>
                                    <strong><?= e($artist['total_trabajos']) ?></strong>
                                    <span>Obras</span>
                                </p>

                                <p>
                                    <strong>
                                        <?= (int) $artist['cantidad_calificaciones'] > 0
                                            ? '★ ' . e(number_format(
                                                (float) $artist['promedio'],
                                                1,
                                                ',',
                                                '.'
                                            ))
                                            : '—' ?>
                                    </strong>
                                    <span>Calificación</span>
                                </p>
                            </div>

                            <div class="admin-artist-card__actions">
                                <a
                                    class="admin-button admin-button--ghost"
                                    href="artistas.php?mode=edit&id=<?= e($artist['id_artista']) ?>"
                                >
                                    Editar
                                </a>

                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input
                                        type="hidden"
                                        name="id_artista"
                                        value="<?= e($artist['id_artista']) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="activo"
                                        value="<?= !empty($artist['activo']) ? '0' : '1' ?>"
                                    >

                                    <button
                                        class="admin-button <?= !empty($artist['activo'])
                                            ? 'admin-button--danger'
                                            : 'admin-button--success' ?>"
                                        type="submit"
                                    >
                                        <?= !empty($artist['activo'])
                                            ? 'Ocultar'
                                            : 'Publicar' ?>
                                    </button>
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
