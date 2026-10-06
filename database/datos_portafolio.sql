/*
    TINTA VIVA
    DATOS DE PORTAFOLIO PARA LOS MÓDULOS DE BYRON

    Este script:
    - Conserva los registros existentes.
    - Completa cinco artistas y cinco clientes.
    - No borra información.
    - Puede ejecutarse nuevamente sin duplicar los datos principales.
*/

USE estudio_tatuajes;
SET NAMES utf8mb4;

START TRANSACTION;

/*
    Hash de desarrollo local.
    Nunca usar estas cuentas en producción.
*/
SET @clave_portafolio =
    '$2y$10$2k2wzhojF1N332o8e4H2Ye7w5FlbU7I1yoS1hRnDYt90ksD4dEX4S';


/* ============================================================
   1. COMPLETAR CINCO CUENTAS DE ARTISTA
   ============================================================ */

INSERT INTO cuentas (
    id_rol,
    usuario,
    correo,
    contrasena_hash,
    estado,
    correo_verificado
)
SELECT
    r.id_rol,
    datos.usuario,
    datos.correo,
    @clave_portafolio,
    'activo',
    TRUE
FROM roles r
CROSS JOIN (
    SELECT
        'artista_demo' AS usuario,
        'artista.demo@tintaviva.local' AS correo

    UNION ALL

    SELECT
        'artista_nocturna',
        'nocturna@tintaviva.local'

    UNION ALL

    SELECT
        'artista_memento',
        'memento@tintaviva.local'

    UNION ALL

    SELECT
        'artista_raven',
        'raven@tintaviva.local'

    UNION ALL

    SELECT
        'artista_umbra',
        'umbra@tintaviva.local'
) datos
WHERE r.nombre_rol = 'artista'
  AND NOT EXISTS (
      SELECT 1
      FROM cuentas c
      WHERE c.usuario = datos.usuario
         OR c.correo = datos.correo
  );


/* ============================================================
   2. COMPLETAR CINCO CUENTAS DE CLIENTE
   ============================================================ */

INSERT INTO cuentas (
    id_rol,
    usuario,
    correo,
    contrasena_hash,
    estado,
    correo_verificado
)
SELECT
    r.id_rol,
    datos.usuario,
    datos.correo,
    @clave_portafolio,
    'activo',
    TRUE
FROM roles r
CROSS JOIN (
    SELECT
        'cliente_demo' AS usuario,
        'cliente.demo@tintaviva.local' AS correo

    UNION ALL

    SELECT
        'cliente_luna',
        'luna@tintaviva.local'

    UNION ALL

    SELECT
        'cliente_elias',
        'elias@tintaviva.local'

    UNION ALL

    SELECT
        'cliente_mara',
        'mara@tintaviva.local'

    UNION ALL

    SELECT
        'cliente_damian',
        'damian@tintaviva.local'
) datos
WHERE r.nombre_rol = 'cliente'
  AND NOT EXISTS (
      SELECT 1
      FROM cuentas c
      WHERE c.usuario = datos.usuario
         OR c.correo = datos.correo
  );


/* ============================================================
   3. PERFILES DE ARTISTAS
   ============================================================ */

INSERT INTO artistas (
    id_cuenta,
    nombre_artistico,
    nombre,
    apellidos,
    telefono,
    biografia,
    slug,
    instagram_url,
    sitio_web_url,
    activo
)
SELECT
    c.id_cuenta,
    datos.nombre_artistico,
    datos.nombre,
    datos.apellidos,
    datos.telefono,
    datos.biografia,
    datos.slug,
    datos.instagram_url,
    'https://example.com',
    TRUE
FROM cuentas c
INNER JOIN (
    SELECT
        'artista_demo' AS usuario,
        'Ink Demo' AS nombre_artistico,
        'Valeria' AS nombre,
        'Artista' AS apellidos,
        '8888-0001' AS telefono,
        'Especialista en blackwork, símbolos ocultos y composiciones de alto contraste.' AS biografia,
        'ink-demo' AS slug,
        CAST(NULL AS CHAR(1024)) AS instagram_url

    UNION ALL

    SELECT
        'artista_nocturna',
        'Nocturna',
        'Valeria',
        'Vega',
        '8888-0101',
        'Crea piezas inspiradas en terror victoriano, vampirismo, lunas rojas y arquitectura gótica.',
        'nocturna',
        NULL

    UNION ALL

    SELECT
        'artista_memento',
        'Memento',
        'Dante',
        'Mora',
        '8888-0102',
        'Especialista en cráneos, grabado oscuro, blackwork y obras inspiradas en la mortalidad.',
        'memento',
        NULL

    UNION ALL

    SELECT
        'artista_raven',
        'Raven',
        'Salomé',
        'Castillo',
        '8888-0103',
        'Trabaja línea fina, rosas negras, cuervos y símbolos delicados con una estética sobrenatural.',
        'raven',
        NULL

    UNION ALL

    SELECT
        'artista_umbra',
        'Umbra',
        'Adrián',
        'Solís',
        '8888-0104',
        'Fusiona realismo oscuro, criaturas de pesadilla y contrastes rojos para crear piezas dramáticas.',
        'umbra',
        NULL
) datos
    ON datos.usuario = c.usuario
WHERE NOT EXISTS (
    SELECT 1
    FROM artistas a
    WHERE a.id_cuenta = c.id_cuenta
       OR a.slug = datos.slug
);


/*
    Completar el artista demo existente si todavía no tiene
    información pública.
*/
UPDATE artistas a
INNER JOIN cuentas c
    ON c.id_cuenta = a.id_cuenta
SET
    a.nombre_artistico = COALESCE(
        NULLIF(a.nombre_artistico, ''),
        'Ink Demo'
    ),
    a.biografia = COALESCE(
        NULLIF(a.biografia, ''),
        'Especialista en blackwork, símbolos ocultos y composiciones de alto contraste.'
    ),
    a.slug = COALESCE(
        NULLIF(a.slug, ''),
        'ink-demo'
    ),
    a.instagram_url = NULL,
    a.sitio_web_url = 'https://example.com',
    a.activo = TRUE
WHERE c.usuario = 'artista_demo';


/* ============================================================
   4. PERFILES DE CLIENTES
   ============================================================ */

INSERT INTO clientes (
    id_cuenta,
    nombre,
    apellidos,
    telefono
)
SELECT
    c.id_cuenta,
    datos.nombre,
    datos.apellidos,
    datos.telefono
FROM cuentas c
INNER JOIN (
    SELECT
        'cliente_demo' AS usuario,
        'Carlos' AS nombre,
        'Cliente' AS apellidos,
        '8888-0002' AS telefono

    UNION ALL

    SELECT
        'cliente_luna',
        'Luna',
        'Alvarado',
        '8888-0201'

    UNION ALL

    SELECT
        'cliente_elias',
        'Elías',
        'Vargas',
        '8888-0202'

    UNION ALL

    SELECT
        'cliente_mara',
        'Mara',
        'Jiménez',
        '8888-0203'

    UNION ALL

    SELECT
        'cliente_damian',
        'Damián',
        'Castro',
        '8888-0204'
) datos
    ON datos.usuario = c.usuario
WHERE NOT EXISTS (
    SELECT 1
    FROM clientes cl
    WHERE cl.id_cuenta = c.id_cuenta
);


/* ============================================================
   5. IDENTIFICADORES DE LOS REGISTROS
   ============================================================ */

SET @artista_1 = (
    SELECT a.id_artista
    FROM artistas a
    INNER JOIN cuentas c ON c.id_cuenta = a.id_cuenta
    WHERE c.usuario = 'artista_demo'
    LIMIT 1
);

SET @artista_2 = (
    SELECT a.id_artista
    FROM artistas a
    INNER JOIN cuentas c ON c.id_cuenta = a.id_cuenta
    WHERE c.usuario = 'artista_nocturna'
    LIMIT 1
);

SET @artista_3 = (
    SELECT a.id_artista
    FROM artistas a
    INNER JOIN cuentas c ON c.id_cuenta = a.id_cuenta
    WHERE c.usuario = 'artista_memento'
    LIMIT 1
);

SET @artista_4 = (
    SELECT a.id_artista
    FROM artistas a
    INNER JOIN cuentas c ON c.id_cuenta = a.id_cuenta
    WHERE c.usuario = 'artista_raven'
    LIMIT 1
);

SET @artista_5 = (
    SELECT a.id_artista
    FROM artistas a
    INNER JOIN cuentas c ON c.id_cuenta = a.id_cuenta
    WHERE c.usuario = 'artista_umbra'
    LIMIT 1
);

SET @cliente_1 = (
    SELECT cl.id_cliente
    FROM clientes cl
    INNER JOIN cuentas c ON c.id_cuenta = cl.id_cuenta
    WHERE c.usuario = 'cliente_demo'
    LIMIT 1
);

SET @cliente_2 = (
    SELECT cl.id_cliente
    FROM clientes cl
    INNER JOIN cuentas c ON c.id_cuenta = cl.id_cuenta
    WHERE c.usuario = 'cliente_luna'
    LIMIT 1
);

SET @cliente_3 = (
    SELECT cl.id_cliente
    FROM clientes cl
    INNER JOIN cuentas c ON c.id_cuenta = cl.id_cuenta
    WHERE c.usuario = 'cliente_elias'
    LIMIT 1
);

SET @cliente_4 = (
    SELECT cl.id_cliente
    FROM clientes cl
    INNER JOIN cuentas c ON c.id_cuenta = cl.id_cuenta
    WHERE c.usuario = 'cliente_mara'
    LIMIT 1
);

SET @cliente_5 = (
    SELECT cl.id_cliente
    FROM clientes cl
    INNER JOIN cuentas c ON c.id_cuenta = cl.id_cuenta
    WHERE c.usuario = 'cliente_damian'
    LIMIT 1
);

SET @cat_blackwork = (
    SELECT id_categoria
    FROM categorias_tatuajes
    WHERE nombre = 'Blackwork'
    LIMIT 1
);

SET @cat_realismo = (
    SELECT id_categoria
    FROM categorias_tatuajes
    WHERE nombre = 'Realismo'
    LIMIT 1
);

SET @cat_japanese = (
    SELECT id_categoria
    FROM categorias_tatuajes
    WHERE nombre = 'Japanese'
    LIMIT 1
);

SET @cat_linea = (
    SELECT id_categoria
    FROM categorias_tatuajes
    WHERE nombre = 'Linea fina'
    LIMIT 1
);

SET @cat_neo = (
    SELECT id_categoria
    FROM categorias_tatuajes
    WHERE nombre = 'Neo Traditional'
    LIMIT 1
);


/* ============================================================
   6. ESPECIALIDADES
   ============================================================ */

INSERT IGNORE INTO artistas_especialidades (
    id_artista,
    id_categoria
)
VALUES
    (@artista_1, @cat_blackwork),
    (@artista_1, @cat_japanese),

    (@artista_2, @cat_realismo),
    (@artista_2, @cat_blackwork),

    (@artista_3, @cat_blackwork),
    (@artista_3, @cat_realismo),

    (@artista_4, @cat_linea),
    (@artista_4, @cat_neo),

    (@artista_5, @cat_realismo),
    (@artista_5, @cat_neo);


/* ============================================================
   7. CERTIFICACIONES
   ============================================================ */

INSERT INTO certificaciones_artistas (
    id_artista,
    nombre,
    institucion,
    fecha_emision,
    fecha_vencimiento
)
SELECT
    @artista_1,
    'Bioseguridad aplicada al tatuaje',
    'Instituto Costarricense de Arte Corporal',
    DATE_SUB(CURDATE(), INTERVAL 3 YEAR),
    DATE_ADD(CURDATE(), INTERVAL 2 YEAR)
WHERE NOT EXISTS (
    SELECT 1
    FROM certificaciones_artistas
    WHERE id_artista = @artista_1
      AND nombre = 'Bioseguridad aplicada al tatuaje'
);

INSERT INTO certificaciones_artistas (
    id_artista,
    nombre,
    institucion,
    fecha_emision
)
SELECT
    @artista_2,
    'Realismo y composición anatómica',
    'Academia Latinoamericana de Tattoo',
    DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
WHERE NOT EXISTS (
    SELECT 1
    FROM certificaciones_artistas
    WHERE id_artista = @artista_2
      AND nombre = 'Realismo y composición anatómica'
);

INSERT INTO certificaciones_artistas (
    id_artista,
    nombre,
    institucion,
    fecha_emision
)
SELECT
    @artista_3,
    'Blackwork avanzado',
    'Dark Ink Workshop',
    DATE_SUB(CURDATE(), INTERVAL 4 YEAR)
WHERE NOT EXISTS (
    SELECT 1
    FROM certificaciones_artistas
    WHERE id_artista = @artista_3
      AND nombre = 'Blackwork avanzado'
);

INSERT INTO certificaciones_artistas (
    id_artista,
    nombre,
    institucion,
    fecha_emision
)
SELECT
    @artista_4,
    'Línea fina y precisión',
    'Fine Line Studio',
    DATE_SUB(CURDATE(), INTERVAL 18 MONTH)
WHERE NOT EXISTS (
    SELECT 1
    FROM certificaciones_artistas
    WHERE id_artista = @artista_4
      AND nombre = 'Línea fina y precisión'
);

INSERT INTO certificaciones_artistas (
    id_artista,
    nombre,
    institucion,
    fecha_emision
)
SELECT
    @artista_5,
    'Teoría del color aplicada a la piel',
    'International Tattoo Academy',
    DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
WHERE NOT EXISTS (
    SELECT 1
    FROM certificaciones_artistas
    WHERE id_artista = @artista_5
      AND nombre = 'Teoría del color aplicada a la piel'
);


/* ============================================================
   8. BOCETOS
   Temporalmente utilizan el fondo horror existente.
   Más adelante tendrán imágenes individuales.
   ============================================================ */

INSERT INTO bocetos_tatuajes (
    id_artista,
    id_categoria,
    titulo,
    descripcion,
    slug,
    imagen_url,
    ancho_cm,
    alto_cm,
    precio_estimado,
    es_exclusivo,
    publicado,
    estado
)
VALUES
    (
        @artista_1,
        @cat_blackwork,
        'El vigilante',
        'Cráneo ceremonial rodeado por un círculo de símbolos antiguos.',
        'el-vigilante',
        'assets/images/hero-horror.png',
        18,
        24,
        95000,
        TRUE,
        TRUE,
        'disponible'
    ),
    (
        @artista_2,
        @cat_realismo,
        'Catedral sangrienta',
        'Ventana gótica iluminada por una luna roja.',
        'catedral-sangrienta',
        'assets/images/hero-horror.png',
        22,
        30,
        145000,
        TRUE,
        TRUE,
        'disponible'
    ),
    (
        @artista_3,
        @cat_blackwork,
        'Memento mori',
        'Composición de cráneo, reloj antiguo y rosas negras.',
        'memento-mori',
        'assets/images/hero-horror.png',
        20,
        28,
        125000,
        TRUE,
        TRUE,
        'reservado'
    ),
    (
        @artista_4,
        @cat_linea,
        'Rosa funeraria',
        'Rosa de línea fina inspirada en ilustraciones victorianas.',
        'rosa-funeraria',
        'assets/images/hero-horror.png',
        12,
        17,
        70000,
        TRUE,
        TRUE,
        'disponible'
    ),
    (
        @artista_5,
        @cat_neo,
        'El cuervo de medianoche',
        'Cuervo oscuro con detalles en rojo y dorado.',
        'cuervo-medianoche',
        'assets/images/hero-horror.png',
        19,
        26,
        135000,
        TRUE,
        TRUE,
        'realizado'
    )
ON DUPLICATE KEY UPDATE
    titulo = VALUES(titulo),
    descripcion = VALUES(descripcion),
    precio_estimado = VALUES(precio_estimado),
    publicado = VALUES(publicado);

SET @boceto_1 = (
    SELECT id_boceto FROM bocetos_tatuajes
    WHERE slug = 'el-vigilante' LIMIT 1
);

SET @boceto_2 = (
    SELECT id_boceto FROM bocetos_tatuajes
    WHERE slug = 'catedral-sangrienta' LIMIT 1
);

SET @boceto_3 = (
    SELECT id_boceto FROM bocetos_tatuajes
    WHERE slug = 'memento-mori' LIMIT 1
);

SET @boceto_4 = (
    SELECT id_boceto FROM bocetos_tatuajes
    WHERE slug = 'rosa-funeraria' LIMIT 1
);

SET @boceto_5 = (
    SELECT id_boceto FROM bocetos_tatuajes
    WHERE slug = 'cuervo-medianoche' LIMIT 1
);


/* ============================================================
   9. COTIZACIONES
   ============================================================ */

INSERT INTO cotizaciones (
    id_cliente,
    id_artista,
    id_categoria,
    id_boceto,
    nombre_contacto,
    correo_contacto,
    telefono_contacto,
    metodo_contacto_preferido,
    descripcion_idea,
    zona_cuerpo,
    ancho_cm,
    alto_cm,
    tamano_descripcion,
    a_color,
    fecha_preferida,
    precio_cotizado,
    anticipo_requerido,
    duracion_estimada_minutos,
    sesiones_estimadas,
    estado,
    fecha_vencimiento,
    observaciones
)
SELECT
    @cliente_1,
    @artista_1,
    @cat_blackwork,
    @boceto_1,
    'Carlos Cliente',
    'cliente.demo@tintaviva.local',
    '8888-0002',
    'whatsapp',
    'Cráneo ceremonial con símbolos alrededor.',
    'Antebrazo',
    18,
    24,
    'Mediano',
    FALSE,
    DATE_ADD(CURDATE(), INTERVAL 20 DAY),
    95000,
    30000,
    180,
    1,
    'solicitada',
    DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 DAY),
    'Solicitud recibida desde la página pública.'
WHERE NOT EXISTS (
    SELECT 1
    FROM cotizaciones
    WHERE correo_contacto = 'cliente.demo@tintaviva.local'
      AND descripcion_idea = 'Cráneo ceremonial con símbolos alrededor.'
);

INSERT INTO cotizaciones (
    id_cliente,
    id_artista,
    id_categoria,
    id_boceto,
    nombre_contacto,
    correo_contacto,
    telefono_contacto,
    metodo_contacto_preferido,
    descripcion_idea,
    zona_cuerpo,
    tamano_descripcion,
    a_color,
    fecha_preferida,
    estado,
    observaciones
)
SELECT
    @cliente_2,
    @artista_2,
    @cat_realismo,
    @boceto_2,
    'Luna Alvarado',
    'luna@tintaviva.local',
    '8888-0201',
    'correo',
    'Catedral con luna roja y niebla.',
    'Espalda',
    'Grande',
    TRUE,
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    'en_revision',
    'Pendiente de definir medidas finales.'
WHERE NOT EXISTS (
    SELECT 1
    FROM cotizaciones
    WHERE correo_contacto = 'luna@tintaviva.local'
      AND descripcion_idea = 'Catedral con luna roja y niebla.'
);

INSERT INTO cotizaciones (
    id_cliente,
    id_artista,
    id_categoria,
    id_boceto,
    nombre_contacto,
    correo_contacto,
    telefono_contacto,
    metodo_contacto_preferido,
    descripcion_idea,
    zona_cuerpo,
    ancho_cm,
    alto_cm,
    tamano_descripcion,
    a_color,
    fecha_preferida,
    precio_cotizado,
    anticipo_requerido,
    duracion_estimada_minutos,
    sesiones_estimadas,
    estado,
    fecha_vencimiento
)
SELECT
    @cliente_3,
    @artista_3,
    @cat_blackwork,
    @boceto_3,
    'Elías Vargas',
    'elias@tintaviva.local',
    '8888-0202',
    'telefono',
    'Memento mori con reloj y rosas.',
    'Pecho',
    20,
    28,
    'Grande',
    FALSE,
    DATE_ADD(CURDATE(), INTERVAL 25 DAY),
    125000,
    40000,
    240,
    2,
    'enviada',
    DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 DAY)
WHERE NOT EXISTS (
    SELECT 1
    FROM cotizaciones
    WHERE correo_contacto = 'elias@tintaviva.local'
      AND descripcion_idea = 'Memento mori con reloj y rosas.'
);

INSERT INTO cotizaciones (
    id_cliente,
    id_artista,
    id_categoria,
    id_boceto,
    nombre_contacto,
    correo_contacto,
    telefono_contacto,
    metodo_contacto_preferido,
    descripcion_idea,
    zona_cuerpo,
    ancho_cm,
    alto_cm,
    tamano_descripcion,
    a_color,
    fecha_preferida,
    precio_cotizado,
    anticipo_requerido,
    duracion_estimada_minutos,
    sesiones_estimadas,
    estado,
    fecha_vencimiento
)
SELECT
    @cliente_4,
    @artista_4,
    @cat_linea,
    @boceto_4,
    'Mara Jiménez',
    'mara@tintaviva.local',
    '8888-0203',
    'whatsapp',
    'Rosa funeraria delicada.',
    'Costillas',
    12,
    17,
    'Pequeño',
    FALSE,
    DATE_ADD(CURDATE(), INTERVAL 18 DAY),
    70000,
    20000,
    120,
    1,
    'aceptada',
    DATE_ADD(UTC_TIMESTAMP(), INTERVAL 20 DAY)
WHERE NOT EXISTS (
    SELECT 1
    FROM cotizaciones
    WHERE correo_contacto = 'mara@tintaviva.local'
      AND descripcion_idea = 'Rosa funeraria delicada.'
);

INSERT INTO cotizaciones (
    id_cliente,
    id_artista,
    id_categoria,
    id_boceto,
    nombre_contacto,
    correo_contacto,
    telefono_contacto,
    metodo_contacto_preferido,
    descripcion_idea,
    zona_cuerpo,
    tamano_descripcion,
    a_color,
    fecha_preferida,
    estado,
    observaciones
)
SELECT
    @cliente_5,
    @artista_5,
    @cat_neo,
    @boceto_5,
    'Damián Castro',
    'damian@tintaviva.local',
    '8888-0204',
    'correo',
    'Cuervo con detalles de luna y rosas.',
    'Hombro',
    'Mediano',
    TRUE,
    DATE_ADD(CURDATE(), INTERVAL 22 DAY),
    'rechazada',
    'Se solicitó modificar completamente el concepto.'
WHERE NOT EXISTS (
    SELECT 1
    FROM cotizaciones
    WHERE correo_contacto = 'damian@tintaviva.local'
      AND descripcion_idea = 'Cuervo con detalles de luna y rosas.'
);

SET @cotizacion_4 = (
    SELECT id_cotizacion
    FROM cotizaciones
    WHERE correo_contacto = 'mara@tintaviva.local'
      AND descripcion_idea = 'Rosa funeraria delicada.'
    LIMIT 1
);


/* ============================================================
   10. REFERENCIAS DE COTIZACIÓN
   ============================================================ */

INSERT INTO referencias_cotizacion (
    id_cotizacion,
    imagen_url,
    nombre_archivo_original,
    tipo_mime,
    descripcion,
    texto_alternativo,
    orden
)
SELECT
    c.id_cotizacion,
    'assets/images/hero-horror.png',
    'referencia-horror.png',
    'image/png',
    'Referencia visual de estilo oscuro.',
    'Referencia oscura para la cotización',
    1
FROM cotizaciones c
WHERE c.correo_contacto IN (
    'cliente.demo@tintaviva.local',
    'luna@tintaviva.local',
    'elias@tintaviva.local',
    'mara@tintaviva.local',
    'damian@tintaviva.local'
)
AND NOT EXISTS (
    SELECT 1
    FROM referencias_cotizacion rc
    WHERE rc.id_cotizacion = c.id_cotizacion
      AND rc.imagen_url = 'assets/images/hero-horror.png'
);


/* ============================================================
   11. TATUAJES PUBLICADOS
   ============================================================ */

INSERT INTO tatuajes_realizados (
    id_cliente,
    id_artista,
    id_boceto,
    id_cotizacion,
    id_categoria,
    titulo,
    descripcion,
    slug,
    fecha_realizacion,
    autorizacion_publicacion,
    publicado
)
VALUES
    (
        @cliente_1,
        @artista_1,
        @boceto_1,
        NULL,
        @cat_blackwork,
        'El guardián de la cripta',
        'Blackwork de cráneo y símbolos ceremoniales.',
        'guardian-de-la-cripta',
        DATE_SUB(CURDATE(), INTERVAL 8 MONTH),
        TRUE,
        TRUE
    ),
    (
        @cliente_2,
        @artista_2,
        @boceto_2,
        NULL,
        @cat_realismo,
        'La catedral roja',
        'Escena gótica con luna roja y niebla.',
        'la-catedral-roja',
        DATE_SUB(CURDATE(), INTERVAL 7 MONTH),
        TRUE,
        TRUE
    ),
    (
        @cliente_3,
        @artista_3,
        @boceto_3,
        NULL,
        @cat_blackwork,
        'Recuerda que morirás',
        'Memento mori con reloj antiguo.',
        'recuerda-que-moriras',
        DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
        TRUE,
        TRUE
    ),
    (
        @cliente_4,
        @artista_4,
        @boceto_4,
        @cotizacion_4,
        @cat_linea,
        'Rosa de medianoche',
        'Rosa funeraria realizada en línea fina.',
        'rosa-de-medianoche',
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
        TRUE,
        TRUE
    ),
    (
        @cliente_5,
        @artista_5,
        @boceto_5,
        NULL,
        @cat_neo,
        'El último cuervo',
        'Cuervo con detalles rojos de estilo neo traditional.',
        'el-ultimo-cuervo',
        DATE_SUB(CURDATE(), INTERVAL 4 MONTH),
        TRUE,
        TRUE
    )
ON DUPLICATE KEY UPDATE
    titulo = VALUES(titulo),
    descripcion = VALUES(descripcion),
    publicado = TRUE,
    autorizacion_publicacion = TRUE;

SET @tatuaje_1 = (
    SELECT id_tatuaje_realizado FROM tatuajes_realizados
    WHERE slug = 'guardian-de-la-cripta' LIMIT 1
);

SET @tatuaje_2 = (
    SELECT id_tatuaje_realizado FROM tatuajes_realizados
    WHERE slug = 'la-catedral-roja' LIMIT 1
);

SET @tatuaje_3 = (
    SELECT id_tatuaje_realizado FROM tatuajes_realizados
    WHERE slug = 'recuerda-que-moriras' LIMIT 1
);

SET @tatuaje_4 = (
    SELECT id_tatuaje_realizado FROM tatuajes_realizados
    WHERE slug = 'rosa-de-medianoche' LIMIT 1
);

SET @tatuaje_5 = (
    SELECT id_tatuaje_realizado FROM tatuajes_realizados
    WHERE slug = 'el-ultimo-cuervo' LIMIT 1
);


/* ============================================================
   12. IMÁGENES PARA LA GALERÍA
   ============================================================ */

INSERT INTO imagenes_tatuajes (
    id_tatuaje_realizado,
    imagen_url,
    descripcion,
    texto_alternativo,
    orden,
    es_portada
)
SELECT
    datos.id_tatuaje,
    'assets/images/hero-horror.png',
    datos.descripcion,
    datos.alternativo,
    1,
    TRUE
FROM (
    SELECT
        @tatuaje_1 AS id_tatuaje,
        'Fotografía de El guardián de la cripta' AS descripcion,
        'Tatuaje blackwork de cráneo' AS alternativo

    UNION ALL

    SELECT
        @tatuaje_2,
        'Fotografía de La catedral roja',
        'Tatuaje realista de una catedral gótica'

    UNION ALL

    SELECT
        @tatuaje_3,
        'Fotografía de Recuerda que morirás',
        'Tatuaje memento mori con reloj'

    UNION ALL

    SELECT
        @tatuaje_4,
        'Fotografía de Rosa de medianoche',
        'Tatuaje de rosa funeraria en línea fina'

    UNION ALL

    SELECT
        @tatuaje_5,
        'Fotografía de El último cuervo',
        'Tatuaje de cuervo con detalles rojos'
) datos
WHERE NOT EXISTS (
    SELECT 1
    FROM imagenes_tatuajes it
    WHERE it.id_tatuaje_realizado = datos.id_tatuaje
      AND it.es_portada = TRUE
);


/* ============================================================
   13. HORARIOS DE LOS ARTISTAS
   ============================================================ */

INSERT IGNORE INTO horarios_artistas (
    id_artista,
    dia_semana,
    hora_inicio,
    hora_fin,
    activo
)
VALUES
    (@artista_1, 1, '09:00:00', '18:00:00', TRUE),
    (@artista_2, 2, '09:00:00', '18:00:00', TRUE),
    (@artista_3, 3, '09:00:00', '18:00:00', TRUE),
    (@artista_4, 4, '09:00:00', '18:00:00', TRUE),
    (@artista_5, 5, '09:00:00', '18:00:00', TRUE);


/*
    Fechas dinámicas:
    - Semana anterior para citas finalizadas.
    - Próxima semana para citas futuras.
*/
SET @lunes_anterior = DATE_SUB(
    CURDATE(),
    INTERVAL (WEEKDAY(CURDATE()) + 7) DAY
);

SET @lunes_siguiente = DATE_ADD(
    CURDATE(),
    INTERVAL (7 - WEEKDAY(CURDATE())) DAY
);


/* ============================================================
   14. BLOQUEOS DE AGENDA
   ============================================================ */

INSERT INTO bloqueos_agenda (
    id_artista,
    fecha_hora_inicio,
    fecha_hora_fin,
    motivo,
    activo
)
SELECT
    datos.id_artista,
    datos.inicio,
    datos.fin,
    datos.motivo,
    TRUE
FROM (
    SELECT
        @artista_1 AS id_artista,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 7 DAY),
            '15:00:00'
        ) AS inicio,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 7 DAY),
            '17:00:00'
        ) AS fin,
        'Preparación de equipo' AS motivo

    UNION ALL

    SELECT
        @artista_2,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 8 DAY),
            '15:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 8 DAY),
            '17:00:00'
        ),
        'Sesión fotográfica'

    UNION ALL

    SELECT
        @artista_3,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 9 DAY),
            '15:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 9 DAY),
            '17:00:00'
        ),
        'Diseño de bocetos'

    UNION ALL

    SELECT
        @artista_4,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 10 DAY),
            '15:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 10 DAY),
            '17:00:00'
        ),
        'Capacitación'

    UNION ALL

    SELECT
        @artista_5,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 11 DAY),
            '15:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 11 DAY),
            '17:00:00'
        ),
        'Mantenimiento del estudio'
) datos
WHERE NOT EXISTS (
    SELECT 1
    FROM bloqueos_agenda b
    WHERE b.id_artista = datos.id_artista
      AND b.fecha_hora_inicio = datos.inicio
      AND b.fecha_hora_fin = datos.fin
);


/* ============================================================
   15. CITAS FINALIZADAS Y PRÓXIMAS
   ============================================================ */

INSERT INTO citas (
    id_cliente,
    id_artista,
    id_cotizacion,
    id_tatuaje_realizado,
    fecha_hora_inicio,
    fecha_hora_fin,
    numero_sesion,
    origen,
    estado,
    observaciones
)
SELECT
    datos.id_cliente,
    datos.id_artista,
    datos.id_cotizacion,
    datos.id_tatuaje,
    datos.inicio,
    datos.fin,
    1,
    datos.origen,
    datos.estado,
    datos.observaciones
FROM (
    /* Cinco citas finalizadas */

    SELECT
        @cliente_1 AS id_cliente,
        @artista_1 AS id_artista,
        NULL AS id_cotizacion,
        @tatuaje_1 AS id_tatuaje,
        TIMESTAMP(@lunes_anterior, '10:00:00') AS inicio,
        TIMESTAMP(@lunes_anterior, '12:00:00') AS fin,
        'administracion' AS origen,
        'finalizada' AS estado,
        'Sesión finalizada de demostración.' AS observaciones

    UNION ALL

    SELECT
        @cliente_2,
        @artista_2,
        NULL,
        @tatuaje_2,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 1 DAY),
            '10:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 1 DAY),
            '12:00:00'
        ),
        'web',
        'finalizada',
        'Sesión finalizada de demostración.'

    UNION ALL

    SELECT
        @cliente_3,
        @artista_3,
        NULL,
        @tatuaje_3,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 2 DAY),
            '10:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 2 DAY),
            '12:00:00'
        ),
        'telefono',
        'finalizada',
        'Sesión finalizada de demostración.'

    UNION ALL

    SELECT
        @cliente_4,
        @artista_4,
        @cotizacion_4,
        @tatuaje_4,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 3 DAY),
            '10:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 3 DAY),
            '12:00:00'
        ),
        'web',
        'finalizada',
        'Sesión finalizada de demostración.'

    UNION ALL

    SELECT
        @cliente_5,
        @artista_5,
        NULL,
        @tatuaje_5,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 4 DAY),
            '10:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 4 DAY),
            '12:00:00'
        ),
        'administracion',
        'finalizada',
        'Sesión finalizada de demostración.'

    /* Cinco citas futuras */

    UNION ALL

    SELECT
        @cliente_1,
        @artista_1,
        NULL,
        NULL,
        TIMESTAMP(@lunes_siguiente, '11:00:00'),
        TIMESTAMP(@lunes_siguiente, '13:00:00'),
        'web',
        'confirmada',
        'Próxima sesión de blackwork.'

    UNION ALL

    SELECT
        @cliente_2,
        @artista_2,
        NULL,
        NULL,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 1 DAY),
            '11:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 1 DAY),
            '13:00:00'
        ),
        'web',
        'confirmada',
        'Próxima sesión de realismo.'

    UNION ALL

    SELECT
        @cliente_3,
        @artista_3,
        NULL,
        NULL,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 2 DAY),
            '11:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 2 DAY),
            '13:00:00'
        ),
        'telefono',
        'pendiente',
        'Cita pendiente de confirmación.'

    UNION ALL

    SELECT
        @cliente_4,
        @artista_4,
        @cotizacion_4,
        NULL,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 3 DAY),
            '11:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 3 DAY),
            '13:00:00'
        ),
        'web',
        'confirmada',
        'Seguimiento de línea fina.'

    UNION ALL

    SELECT
        @cliente_5,
        @artista_5,
        NULL,
        NULL,
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 4 DAY),
            '11:00:00'
        ),
        TIMESTAMP(
            DATE_ADD(@lunes_siguiente, INTERVAL 4 DAY),
            '13:00:00'
        ),
        'administracion',
        'pendiente',
        'Pendiente de confirmación administrativa.'
) datos
WHERE NOT EXISTS (
    SELECT 1
    FROM citas c
    WHERE c.id_artista = datos.id_artista
      AND c.fecha_hora_inicio = datos.inicio
);


/* ============================================================
   16. CALIFICACIONES
   ============================================================ */

INSERT IGNORE INTO calificaciones_artistas (
    id_cita,
    puntuacion,
    comentario,
    estado_publicacion
)
SELECT
    c.id_cita,
    datos.puntuacion,
    datos.comentario,
    'publicado'
FROM (
    SELECT
        @artista_1 AS id_artista,
        TIMESTAMP(@lunes_anterior, '10:00:00') AS inicio,
        5 AS puntuacion,
        'El diseño quedó incluso mejor de lo que imaginaba.' AS comentario

    UNION ALL

    SELECT
        @artista_2,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 1 DAY),
            '10:00:00'
        ),
        5,
        'La atmósfera, el detalle y el resultado fueron increíbles.'

    UNION ALL

    SELECT
        @artista_3,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 2 DAY),
            '10:00:00'
        ),
        4,
        'Excelente trabajo de líneas y sombras.'

    UNION ALL

    SELECT
        @artista_4,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 3 DAY),
            '10:00:00'
        ),
        5,
        'Trabajo delicado, limpio y muy profesional.'

    UNION ALL

    SELECT
        @artista_5,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 4 DAY),
            '10:00:00'
        ),
        5,
        'El color rojo quedó intenso y perfectamente integrado.'
) datos
INNER JOIN citas c
    ON c.id_artista = datos.id_artista
   AND c.fecha_hora_inicio = datos.inicio;


/* ============================================================
   17. CUIDADOS DEL TATUAJE
   ============================================================ */

INSERT INTO cuidados_tatuaje (
    titulo,
    slug,
    categoria,
    contenido,
    imagen_url,
    texto_alternativo,
    orden,
    activo
)
VALUES
    (
        'Las primeras horas',
        'primeras-horas',
        'Primer día',
        'Mantén la protección colocada por el artista durante el tiempo indicado. Lávate las manos antes de tocar la zona.',
        NULL,
        NULL,
        1,
        TRUE
    ),
    (
        'Limpieza de la piel',
        'limpieza-de-la-piel',
        'Higiene',
        'Lava suavemente el tatuaje con agua tibia y jabón neutro. No frotes ni utilices esponjas.',
        NULL,
        NULL,
        2,
        TRUE
    ),
    (
        'Hidratación',
        'hidratacion-del-tatuaje',
        'Cicatrización',
        'Aplica una capa delgada del producto recomendado. El exceso de crema puede dificultar la cicatrización.',
        NULL,
        NULL,
        3,
        TRUE
    ),
    (
        'Evita el sol y el agua',
        'evitar-sol-y-agua',
        'Protección',
        'No expongas el tatuaje al sol, piscinas, mar o baños prolongados durante la cicatrización.',
        NULL,
        NULL,
        4,
        TRUE
    ),
    (
        'Señales de alerta',
        'senales-de-alerta',
        'Salud',
        'Consulta a un profesional si observas dolor creciente, fiebre, secreción inusual o inflamación intensa.',
        NULL,
        NULL,
        5,
        TRUE
    )
ON DUPLICATE KEY UPDATE
    titulo = VALUES(titulo),
    categoria = VALUES(categoria),
    contenido = VALUES(contenido),
    orden = VALUES(orden),
    activo = TRUE;


/* ============================================================
   18. PREGUNTAS FRECUENTES
   ============================================================ */

INSERT INTO preguntas_frecuentes (
    categoria,
    pregunta,
    respuesta,
    orden,
    activo
)
SELECT
    datos.categoria,
    datos.pregunta,
    datos.respuesta,
    datos.orden,
    TRUE
FROM (
    SELECT
        'Cotizaciones' AS categoria,
        '¿Cómo puedo solicitar una cotización?' AS pregunta,
        'Completa el formulario con tu idea, zona del cuerpo, tamaño aproximado, estilo y referencias visuales.' AS respuesta,
        1 AS orden

    UNION ALL

    SELECT
        'Preparación',
        '¿Debo comer antes de una sesión?',
        'Sí. Descansa bien, come antes de asistir y mantente hidratado.',
        2

    UNION ALL

    SELECT
        'Dolor',
        '¿Cuánto duele hacerse un tatuaje?',
        'La intensidad depende de la zona, el tamaño y la sensibilidad de cada persona.',
        3

    UNION ALL

    SELECT
        'Agenda',
        '¿Puedo elegir al artista?',
        'Sí. Puedes consultar los perfiles, especialidades y trabajos de cada artista antes de solicitar una cita.',
        4

    UNION ALL

    SELECT
        'Cuidados',
        '¿Cuánto tarda en cicatrizar?',
        'La parte superficial suele tardar entre dos y cuatro semanas, aunque la recuperación completa puede tardar más.',
        5
) datos
WHERE NOT EXISTS (
    SELECT 1
    FROM preguntas_frecuentes pf
    WHERE pf.pregunta = datos.pregunta
);


/* ============================================================
   19. TESTIMONIOS
   ============================================================ */

INSERT INTO testimonios (
    id_cliente,
    id_cita,
    titulo,
    contenido,
    nombre_publico,
    estado_publicacion,
    destacado
)
SELECT
    c.id_cliente,
    c.id_cita,
    datos.titulo,
    datos.contenido,
    datos.nombre_publico,
    'publicado',
    datos.destacado
FROM (
    SELECT
        @artista_1 AS id_artista,
        TIMESTAMP(@lunes_anterior, '10:00:00') AS inicio,
        'Una experiencia inolvidable' AS titulo,
        'Desde el diseño hasta el último detalle, todo el proceso fue profesional y creativo.' AS contenido,
        'Carlos C.' AS nombre_publico,
        TRUE AS destacado

    UNION ALL

    SELECT
        @artista_2,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 1 DAY),
            '10:00:00'
        ),
        'Mi idea cobró vida',
        'La artista entendió perfectamente la atmósfera oscura que quería.',
        'Luna A.',
        TRUE

    UNION ALL

    SELECT
        @artista_3,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 2 DAY),
            '10:00:00'
        ),
        'Detalle impresionante',
        'El sombreado y las líneas superaron mis expectativas.',
        'Elías V.',
        FALSE

    UNION ALL

    SELECT
        @artista_4,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 3 DAY),
            '10:00:00'
        ),
        'Delicado y perfecto',
        'Me sentí acompañada durante todo el proceso y el resultado fue hermoso.',
        'Mara J.',
        TRUE

    UNION ALL

    SELECT
        @artista_5,
        TIMESTAMP(
            DATE_ADD(@lunes_anterior, INTERVAL 4 DAY),
            '10:00:00'
        ),
        'Exactamente lo que buscaba',
        'Una pieza oscura, artística y con una personalidad increíble.',
        'Damián C.',
        FALSE
) datos
INNER JOIN citas c
    ON c.id_artista = datos.id_artista
   AND c.fecha_hora_inicio = datos.inicio
WHERE NOT EXISTS (
    SELECT 1
    FROM testimonios t
    WHERE t.id_cita = c.id_cita
);


/* ============================================================
   20. CONFIGURACIÓN ADICIONAL DEL CHATBOT
   Las tres configuraciones iniciales ya existen.
   ============================================================ */

INSERT IGNORE INTO configuraciones_chatbot (
    clave,
    valor,
    tipo_dato,
    descripcion,
    activo
)
VALUES
    (
        'tono_respuesta',
        'oscuro, creativo, respetuoso y claro',
        'texto',
        'Personalidad utilizada por el asistente',
        TRUE
    ),
    (
        'mensaje_sin_respuesta',
        'No encontré una respuesta segura. Puedo comunicarte con el estudio.',
        'texto',
        'Respuesta utilizada cuando no existe información suficiente',
        TRUE
    );


/* ============================================================
   21. FUENTES DEL CHATBOT
   ============================================================ */

SET @pregunta_1 = (
    SELECT id_pregunta
    FROM preguntas_frecuentes
    WHERE pregunta = '¿Cómo puedo solicitar una cotización?'
    LIMIT 1
);

SET @pregunta_2 = (
    SELECT id_pregunta
    FROM preguntas_frecuentes
    WHERE pregunta = '¿Cuánto duele hacerse un tatuaje?'
    LIMIT 1
);

SET @cuidado_1 = (
    SELECT id_cuidado
    FROM cuidados_tatuaje
    WHERE slug = 'primeras-horas'
    LIMIT 1
);

SET @cuidado_2 = (
    SELECT id_cuidado
    FROM cuidados_tatuaje
    WHERE slug = 'limpieza-de-la-piel'
    LIMIT 1
);

INSERT INTO fuentes_conocimiento_chatbot (
    tipo,
    id_pregunta,
    id_cuidado,
    titulo,
    contenido,
    activo
)
SELECT
    'pregunta_frecuente',
    @pregunta_1,
    NULL,
    'Solicitud de cotizaciones',
    NULL,
    TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM fuentes_conocimiento_chatbot
    WHERE tipo = 'pregunta_frecuente'
      AND id_pregunta = @pregunta_1
);

INSERT INTO fuentes_conocimiento_chatbot (
    tipo,
    id_pregunta,
    id_cuidado,
    titulo,
    contenido,
    activo
)
SELECT
    'pregunta_frecuente',
    @pregunta_2,
    NULL,
    'Dolor durante el tatuaje',
    NULL,
    TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM fuentes_conocimiento_chatbot
    WHERE tipo = 'pregunta_frecuente'
      AND id_pregunta = @pregunta_2
);

INSERT INTO fuentes_conocimiento_chatbot (
    tipo,
    id_pregunta,
    id_cuidado,
    titulo,
    contenido,
    activo
)
SELECT
    'cuidado_tatuaje',
    NULL,
    @cuidado_1,
    'Primeras horas',
    NULL,
    TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM fuentes_conocimiento_chatbot
    WHERE tipo = 'cuidado_tatuaje'
      AND id_cuidado = @cuidado_1
);

INSERT INTO fuentes_conocimiento_chatbot (
    tipo,
    id_pregunta,
    id_cuidado,
    titulo,
    contenido,
    activo
)
SELECT
    'cuidado_tatuaje',
    NULL,
    @cuidado_2,
    'Limpieza del tatuaje',
    NULL,
    TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM fuentes_conocimiento_chatbot
    WHERE tipo = 'cuidado_tatuaje'
      AND id_cuidado = @cuidado_2
);

INSERT INTO fuentes_conocimiento_chatbot (
    tipo,
    id_pregunta,
    id_cuidado,
    titulo,
    contenido,
    activo
)
SELECT
    'contenido_personalizado',
    NULL,
    NULL,
    'Información del estudio',
    'Tinta Viva trabaja con cita previa. Las cotizaciones dependen del tamaño, la zona, el estilo, el color y la cantidad de sesiones.',
    TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM fuentes_conocimiento_chatbot
    WHERE tipo = 'contenido_personalizado'
      AND titulo = 'Información del estudio'
);


/* ============================================================
   22. CONVERSACIONES DEL CHATBOT
   ============================================================ */

INSERT INTO conversaciones_chat (
    id_cliente,
    identificador_visitante,
    canal,
    estado
)
SELECT
    datos.id_cliente,
    datos.identificador,
    'web',
    datos.estado
FROM (
    SELECT
        @cliente_1 AS id_cliente,
        'portfolio-chat-001' AS identificador,
        'cerrada' AS estado

    UNION ALL

    SELECT
        @cliente_2,
        'portfolio-chat-002',
        'cerrada'

    UNION ALL

    SELECT
        @cliente_3,
        'portfolio-chat-003',
        'activa'

    UNION ALL

    SELECT
        @cliente_4,
        'portfolio-chat-004',
        'cerrada'

    UNION ALL

    SELECT
        @cliente_5,
        'portfolio-chat-005',
        'escalada'
) datos
WHERE NOT EXISTS (
    SELECT 1
    FROM conversaciones_chat cc
    WHERE cc.identificador_visitante = datos.identificador
);


/* ============================================================
   23. MENSAJES DEL CHATBOT
   ============================================================ */

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido
)
SELECT
    cc.id_conversacion,
    'usuario',
    '¿Cómo solicito una cotización?'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-001'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'usuario'
        AND m.contenido = '¿Cómo solicito una cotización?'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido,
    proveedor,
    modelo
)
SELECT
    cc.id_conversacion,
    'asistente',
    'Puedes completar el formulario indicando idea, zona, tamaño, estilo y referencias.',
    'demo-local',
    'respuesta-predefinida'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-001'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'asistente'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido
)
SELECT
    cc.id_conversacion,
    'usuario',
    '¿Qué debo hacer durante las primeras horas?'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-002'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'usuario'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido,
    proveedor,
    modelo
)
SELECT
    cc.id_conversacion,
    'asistente',
    'Mantén la protección indicada y lávate las manos antes de tocar el tatuaje.',
    'demo-local',
    'respuesta-predefinida'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-002'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'asistente'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido
)
SELECT
    cc.id_conversacion,
    'usuario',
    '¿Puedo escoger al artista?'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-003'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'usuario'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido,
    proveedor,
    modelo
)
SELECT
    cc.id_conversacion,
    'asistente',
    'Sí. Puedes revisar perfiles, especialidades y trabajos antes de elegir.',
    'demo-local',
    'respuesta-predefinida'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-003'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'asistente'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido
)
SELECT
    cc.id_conversacion,
    'usuario',
    '¿Cuánto tarda en cicatrizar?'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-004'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'usuario'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido,
    proveedor,
    modelo
)
SELECT
    cc.id_conversacion,
    'asistente',
    'La superficie suele tardar entre dos y cuatro semanas.',
    'demo-local',
    'respuesta-predefinida'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-004'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'asistente'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido
)
SELECT
    cc.id_conversacion,
    'usuario',
    'Necesito hablar con alguien del estudio.'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-005'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'usuario'
  );

INSERT INTO mensajes_chat (
    id_conversacion,
    rol,
    contenido,
    proveedor,
    modelo
)
SELECT
    cc.id_conversacion,
    'asistente',
    'He marcado la conversación para que una persona del estudio pueda continuar.',
    'demo-local',
    'respuesta-predefinida'
FROM conversaciones_chat cc
WHERE cc.identificador_visitante = 'portfolio-chat-005'
  AND NOT EXISTS (
      SELECT 1 FROM mensajes_chat m
      WHERE m.id_conversacion = cc.id_conversacion
        AND m.rol = 'asistente'
  );


/* ============================================================
   24. RETROALIMENTACIÓN DEL CHATBOT
   ============================================================ */

INSERT INTO retroalimentacion_chatbot (
    id_mensaje,
    id_cliente,
    fue_util,
    comentario
)
SELECT
    m.id_mensaje,
    cc.id_cliente,
    datos.fue_util,
    datos.comentario
FROM (
    SELECT
        'portfolio-chat-001' AS identificador,
        TRUE AS fue_util,
        'Respuesta clara.' AS comentario

    UNION ALL

    SELECT
        'portfolio-chat-002',
        TRUE,
        'Me ayudó con el cuidado inicial.'

    UNION ALL

    SELECT
        'portfolio-chat-003',
        TRUE,
        'Encontré la información que necesitaba.'

    UNION ALL

    SELECT
        'portfolio-chat-004',
        TRUE,
        'Explicación sencilla.'

    UNION ALL

    SELECT
        'portfolio-chat-005',
        FALSE,
        'Necesitaba atención humana.'
) datos
INNER JOIN conversaciones_chat cc
    ON cc.identificador_visitante = datos.identificador
INNER JOIN mensajes_chat m
    ON m.id_conversacion = cc.id_conversacion
   AND m.rol = 'asistente'
WHERE NOT EXISTS (
    SELECT 1
    FROM retroalimentacion_chatbot r
    WHERE r.id_mensaje = m.id_mensaje
);


/* ============================================================
   FINALIZAR
   ============================================================ */

COMMIT;


/* ============================================================
   COMPROBACIÓN
   ============================================================ */

SELECT 'artistas' AS tabla, COUNT(*) AS registros
FROM artistas

UNION ALL

SELECT 'clientes', COUNT(*)
FROM clientes

UNION ALL

SELECT 'especialidades', COUNT(*)
FROM artistas_especialidades

UNION ALL

SELECT 'certificaciones', COUNT(*)
FROM certificaciones_artistas

UNION ALL

SELECT 'bocetos', COUNT(*)
FROM bocetos_tatuajes

UNION ALL

SELECT 'cotizaciones', COUNT(*)
FROM cotizaciones

UNION ALL

SELECT 'tatuajes_publicados', COUNT(*)
FROM tatuajes_realizados
WHERE publicado = TRUE

UNION ALL

SELECT 'imagenes_tatuajes', COUNT(*)
FROM imagenes_tatuajes

UNION ALL

SELECT 'horarios', COUNT(*)
FROM horarios_artistas

UNION ALL

SELECT 'bloqueos', COUNT(*)
FROM bloqueos_agenda

UNION ALL

SELECT 'citas', COUNT(*)
FROM citas

UNION ALL

SELECT 'calificaciones', COUNT(*)
FROM calificaciones_artistas

UNION ALL

SELECT 'cuidados', COUNT(*)
FROM cuidados_tatuaje

UNION ALL

SELECT 'preguntas', COUNT(*)
FROM preguntas_frecuentes

UNION ALL

SELECT 'testimonios', COUNT(*)
FROM testimonios

UNION ALL

SELECT 'fuentes_chatbot', COUNT(*)
FROM fuentes_conocimiento_chatbot

UNION ALL

SELECT 'conversaciones_chat', COUNT(*)
FROM conversaciones_chat

UNION ALL

SELECT 'mensajes_chat', COUNT(*)
FROM mensajes_chat

UNION ALL

SELECT 'retroalimentacion_chatbot', COUNT(*)
FROM retroalimentacion_chatbot;


/* ============================================================
   17. FOTOGRAFÍAS DE LOS ARTISTAS
   Los archivos físicos están en:
   public/assets/images/artistas/
   ============================================================ */

UPDATE artistas
SET foto_url = CASE slug
    WHEN 'ink-demo'
        THEN 'assets/images/artistas/ink-demo.png'
    WHEN 'nocturna'
        THEN 'assets/images/artistas/nocturna.png'
    WHEN 'memento'
        THEN 'assets/images/artistas/memento.png'
    WHEN 'raven'
        THEN 'assets/images/artistas/raven.png'
    WHEN 'umbra'
        THEN 'assets/images/artistas/umbra.png'
    ELSE foto_url
END
WHERE slug IN (
    'ink-demo',
    'nocturna',
    'memento',
    'raven',
    'umbra'
);

/*
    Los perfiles son ficticios. No se enlazan cuentas reales de
    redes sociales; se utiliza únicamente el dominio reservado
    example.com como muestra de un portafolio externo.
*/
UPDATE artistas
SET
    instagram_url = NULL,
    sitio_web_url = 'https://example.com'
WHERE slug IN (
    'ink-demo',
    'nocturna',
    'memento',
    'raven',
    'umbra'
);

SELECT
    id_artista,
    nombre_artistico,
    slug,
    foto_url,
    instagram_url,
    sitio_web_url
FROM artistas
WHERE slug IN (
    'ink-demo',
    'nocturna',
    'memento',
    'raven',
    'umbra'
)
ORDER BY id_artista;
