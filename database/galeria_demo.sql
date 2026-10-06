/* ============================================================
   TINTA VIVA · COLECCIÓN DEMO PARA LA GALERÍA
   Requiere db_preliminar.sql y datos_portafolio.sql.
   Es idempotente: puede ejecutarse más de una vez.
   ============================================================ */

USE estudio_tatuajes;

SET @gal_artist_ink = (SELECT id_artista FROM artistas WHERE slug = 'ink-demo' LIMIT 1);
SET @gal_artist_nocturna = (SELECT id_artista FROM artistas WHERE slug = 'nocturna' LIMIT 1);
SET @gal_artist_memento = (SELECT id_artista FROM artistas WHERE slug = 'memento' LIMIT 1);
SET @gal_artist_raven = (SELECT id_artista FROM artistas WHERE slug = 'raven' LIMIT 1);
SET @gal_artist_umbra = (SELECT id_artista FROM artistas WHERE slug = 'umbra' LIMIT 1);

SET @gal_client_1 = (SELECT id_cliente FROM clientes ORDER BY id_cliente LIMIT 1 OFFSET 0);
SET @gal_client_2 = (SELECT id_cliente FROM clientes ORDER BY id_cliente LIMIT 1 OFFSET 1);
SET @gal_client_3 = (SELECT id_cliente FROM clientes ORDER BY id_cliente LIMIT 1 OFFSET 2);
SET @gal_client_4 = (SELECT id_cliente FROM clientes ORDER BY id_cliente LIMIT 1 OFFSET 3);
SET @gal_client_5 = (SELECT id_cliente FROM clientes ORDER BY id_cliente LIMIT 1 OFFSET 4);

SET @gal_blackwork = (SELECT id_categoria FROM categorias_tatuajes WHERE nombre = 'Blackwork' LIMIT 1);
SET @gal_realismo = (SELECT id_categoria FROM categorias_tatuajes WHERE nombre = 'Realismo' LIMIT 1);
SET @gal_japanese = (SELECT id_categoria FROM categorias_tatuajes WHERE nombre = 'Japanese' LIMIT 1);
SET @gal_linea = (SELECT id_categoria FROM categorias_tatuajes WHERE nombre = 'Linea fina' LIMIT 1);
SET @gal_neo = (SELECT id_categoria FROM categorias_tatuajes WHERE nombre = 'Neo Traditional' LIMIT 1);

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
    (@gal_client_1, @gal_artist_ink, NULL, NULL, @gal_blackwork,
     'El guardián de la cripta',
     'Una catedral imposible protege el cráneo de quien juró no olvidar.',
     'guardian-de-la-cripta', DATE_SUB(CURDATE(), INTERVAL 210 DAY), TRUE, TRUE),

    (@gal_client_2, @gal_artist_nocturna, NULL, NULL, @gal_realismo,
     'La catedral roja',
     'Arquitectura gótica, niebla y una luna carmesí en realismo oscuro.',
     'la-catedral-roja', DATE_SUB(CURDATE(), INTERVAL 194 DAY), TRUE, TRUE),

    (@gal_client_3, @gal_artist_memento, NULL, NULL, @gal_blackwork,
     'Recuerda que morirás',
     'Reloj antiguo, lirios marchitos y un recordatorio grabado para siempre.',
     'recuerda-que-moriras', DATE_SUB(CURDATE(), INTERVAL 178 DAY), TRUE, TRUE),

    (@gal_client_4, @gal_artist_raven, NULL, NULL, @gal_linea,
     'Rosa de medianoche',
     'Una rosa funeraria suspendida sobre la luna en delicada línea fina.',
     'rosa-de-medianoche', DATE_SUB(CURDATE(), INTERVAL 162 DAY), TRUE, TRUE),

    (@gal_client_5, @gal_artist_umbra, NULL, NULL, @gal_neo,
     'El último cuervo',
     'Cuervo neo tradicional y peonía carmesí: mensajero entre dos mundos.',
     'el-ultimo-cuervo', DATE_SUB(CURDATE(), INTERVAL 146 DAY), TRUE, TRUE),

    (@gal_client_1, @gal_artist_nocturna, NULL, NULL, @gal_blackwork,
     'Vuelo nocturno',
     'Un murciélago cruza la luna creciente entre estrellas ornamentales.',
     'vuelo-nocturno', DATE_SUB(CURDATE(), INTERVAL 130 DAY), TRUE, TRUE),

    (@gal_client_2, @gal_artist_raven, NULL, NULL, @gal_linea,
     'La polilla del oráculo',
     'Polilla de la muerte, flores nocturnas y geometría ritual.',
     'polilla-del-oraculo', DATE_SUB(CURDATE(), INTERVAL 114 DAY), TRUE, TRUE),

    (@gal_client_3, @gal_artist_umbra, NULL, NULL, @gal_realismo,
     'El ángel caído',
     'Una figura de piedra descansa bajo el arco de una catedral olvidada.',
     'angel-caido', DATE_SUB(CURDATE(), INTERVAL 98 DAY), TRUE, TRUE),

    (@gal_client_4, @gal_artist_ink, NULL, NULL, @gal_japanese,
     'Juramento de espinas',
     'Serpiente, daga ceremonial y hojas rojas en una composición vertical.',
     'juramento-de-espinas', DATE_SUB(CURDATE(), INTERVAL 82 DAY), TRUE, TRUE),

    (@gal_client_5, @gal_artist_umbra, NULL, NULL, @gal_neo,
     'Corazón de vitrales',
     'Un relicario encendido bajo rosas negras y fragmentos de luz antigua.',
     'corazon-de-vitrales', DATE_SUB(CURDATE(), INTERVAL 68 DAY), TRUE, TRUE),

    (@gal_client_1, @gal_artist_memento, NULL, NULL, @gal_blackwork,
     'El médico de la niebla',
     'Un viajero enmascarado recorre la ciudad con una lámpara roja.',
     'medico-de-la-niebla', DATE_SUB(CURDATE(), INTERVAL 54 DAY), TRUE, TRUE),

    (@gal_client_2, @gal_artist_raven, NULL, NULL, @gal_linea,
     'El relicario secreto',
     'Corazón victoriano, llave antigua y encaje tejido como una telaraña.',
     'relicario-secreto', DATE_SUB(CURDATE(), INTERVAL 42 DAY), TRUE, TRUE),

    (@gal_client_3, @gal_artist_nocturna, NULL, NULL, @gal_linea,
     'La invocación',
     'Una mano ceremonial sostiene la última vela encendida de la noche.',
     'la-invocacion', DATE_SUB(CURDATE(), INTERVAL 30 DAY), TRUE, TRUE),

    (@gal_client_4, @gal_artist_memento, NULL, NULL, @gal_realismo,
     'Máscara de orquídeas',
     'Porcelana agrietada y flores oscuras para una identidad que renace.',
     'mascara-de-orquideas', DATE_SUB(CURDATE(), INTERVAL 18 DAY), TRUE, TRUE),

    (@gal_client_5, @gal_artist_ink, NULL, NULL, @gal_neo,
     'El familiar del cementerio',
     'Un gato negro vigila las puertas del camposanto bajo la luna de octubre.',
     'familiar-del-cementerio', DATE_SUB(CURDATE(), INTERVAL 7 DAY), TRUE, TRUE)
ON DUPLICATE KEY UPDATE
    id_cliente = VALUES(id_cliente),
    id_artista = VALUES(id_artista),
    id_categoria = VALUES(id_categoria),
    titulo = VALUES(titulo),
    descripcion = VALUES(descripcion),
    fecha_realizacion = VALUES(fecha_realizacion),
    autorizacion_publicacion = TRUE,
    publicado = TRUE;

/* Actualizar portadas ya existentes con las fotografías definitivas. */
UPDATE imagenes_tatuajes image
INNER JOIN tatuajes_realizados tattoo
    ON tattoo.id_tatuaje_realizado = image.id_tatuaje_realizado
INNER JOIN (
    SELECT 'guardian-de-la-cripta' AS slug, 'assets/images/galeria/01-guardian-cripta.webp' AS ruta,
           'Blackwork de cráneo bajo una catedral gótica' AS alternativo
    UNION ALL SELECT 'la-catedral-roja', 'assets/images/galeria/02-catedral-roja.webp', 'Tatuaje realista de una catedral bajo una luna roja'
    UNION ALL SELECT 'recuerda-que-moriras', 'assets/images/galeria/03-memento-reloj.webp', 'Tatuaje memento mori con cráneo, reloj y lirios'
    UNION ALL SELECT 'rosa-de-medianoche', 'assets/images/galeria/04-rosa-lunar.webp', 'Tatuaje de rosa y luna creciente en línea fina'
    UNION ALL SELECT 'el-ultimo-cuervo', 'assets/images/galeria/05-cuervo-carmesi.webp', 'Tatuaje de cuervo y peonía carmesí'
    UNION ALL SELECT 'vuelo-nocturno', 'assets/images/galeria/06-vuelo-nocturno.webp', 'Tatuaje de murciélago frente a una luna creciente'
    UNION ALL SELECT 'polilla-del-oraculo', 'assets/images/galeria/07-polilla-oraculo.webp', 'Tatuaje de polilla de la muerte y flores nocturnas'
    UNION ALL SELECT 'angel-caido', 'assets/images/galeria/08-angel-caido.webp', 'Tatuaje realista de un ángel de piedra'
    UNION ALL SELECT 'juramento-de-espinas', 'assets/images/galeria/09-serpiente-daga.webp', 'Tatuaje de serpiente alrededor de una daga ceremonial'
    UNION ALL SELECT 'corazon-de-vitrales', 'assets/images/galeria/10-corazon-vitrales.webp', 'Tatuaje de corazón gótico inspirado en vitrales'
    UNION ALL SELECT 'medico-de-la-niebla', 'assets/images/galeria/11-medico-peste.webp', 'Tatuaje de médico de la peste en una ciudad gótica'
    UNION ALL SELECT 'relicario-secreto', 'assets/images/galeria/12-relicario-secreto.webp', 'Tatuaje de relicario victoriano con llave y telarañas'
    UNION ALL SELECT 'la-invocacion', 'assets/images/galeria/13-invocacion.webp', 'Tatuaje de una mano sosteniendo una vela negra'
    UNION ALL SELECT 'mascara-de-orquideas', 'assets/images/galeria/14-mascara-orquideas.webp', 'Tatuaje de máscara de porcelana y orquídeas oscuras'
    UNION ALL SELECT 'familiar-del-cementerio', 'assets/images/galeria/15-familiar-cementerio.webp', 'Tatuaje de gato negro frente a un cementerio'
) assets ON assets.slug = tattoo.slug
SET
    image.imagen_url = assets.ruta,
    image.descripcion = CONCAT('Fotografía de ', tattoo.titulo),
    image.texto_alternativo = assets.alternativo,
    image.orden = 0
WHERE image.es_portada = TRUE;

/* Crear la portada cuando la obra todavía no tiene ninguna. */
INSERT INTO imagenes_tatuajes (
    id_tatuaje_realizado,
    imagen_url,
    descripcion,
    texto_alternativo,
    orden,
    es_portada
)
SELECT
    tattoo.id_tatuaje_realizado,
    assets.ruta,
    CONCAT('Fotografía de ', tattoo.titulo),
    assets.alternativo,
    0,
    TRUE
FROM tatuajes_realizados tattoo
INNER JOIN (
    SELECT 'guardian-de-la-cripta' AS slug, 'assets/images/galeria/01-guardian-cripta.webp' AS ruta,
           'Blackwork de cráneo bajo una catedral gótica' AS alternativo
    UNION ALL SELECT 'la-catedral-roja', 'assets/images/galeria/02-catedral-roja.webp', 'Tatuaje realista de una catedral bajo una luna roja'
    UNION ALL SELECT 'recuerda-que-moriras', 'assets/images/galeria/03-memento-reloj.webp', 'Tatuaje memento mori con cráneo, reloj y lirios'
    UNION ALL SELECT 'rosa-de-medianoche', 'assets/images/galeria/04-rosa-lunar.webp', 'Tatuaje de rosa y luna creciente en línea fina'
    UNION ALL SELECT 'el-ultimo-cuervo', 'assets/images/galeria/05-cuervo-carmesi.webp', 'Tatuaje de cuervo y peonía carmesí'
    UNION ALL SELECT 'vuelo-nocturno', 'assets/images/galeria/06-vuelo-nocturno.webp', 'Tatuaje de murciélago frente a una luna creciente'
    UNION ALL SELECT 'polilla-del-oraculo', 'assets/images/galeria/07-polilla-oraculo.webp', 'Tatuaje de polilla de la muerte y flores nocturnas'
    UNION ALL SELECT 'angel-caido', 'assets/images/galeria/08-angel-caido.webp', 'Tatuaje realista de un ángel de piedra'
    UNION ALL SELECT 'juramento-de-espinas', 'assets/images/galeria/09-serpiente-daga.webp', 'Tatuaje de serpiente alrededor de una daga ceremonial'
    UNION ALL SELECT 'corazon-de-vitrales', 'assets/images/galeria/10-corazon-vitrales.webp', 'Tatuaje de corazón gótico inspirado en vitrales'
    UNION ALL SELECT 'medico-de-la-niebla', 'assets/images/galeria/11-medico-peste.webp', 'Tatuaje de médico de la peste en una ciudad gótica'
    UNION ALL SELECT 'relicario-secreto', 'assets/images/galeria/12-relicario-secreto.webp', 'Tatuaje de relicario victoriano con llave y telarañas'
    UNION ALL SELECT 'la-invocacion', 'assets/images/galeria/13-invocacion.webp', 'Tatuaje de una mano sosteniendo una vela negra'
    UNION ALL SELECT 'mascara-de-orquideas', 'assets/images/galeria/14-mascara-orquideas.webp', 'Tatuaje de máscara de porcelana y orquídeas oscuras'
    UNION ALL SELECT 'familiar-del-cementerio', 'assets/images/galeria/15-familiar-cementerio.webp', 'Tatuaje de gato negro frente a un cementerio'
) assets ON assets.slug = tattoo.slug
WHERE NOT EXISTS (
    SELECT 1
    FROM imagenes_tatuajes existing_image
    WHERE existing_image.id_tatuaje_realizado = tattoo.id_tatuaje_realizado
      AND existing_image.es_portada = TRUE
);

SELECT
    COUNT(DISTINCT tattoo.id_tatuaje_realizado) AS obras_publicadas,
    COUNT(image.id_imagen) AS imagenes_disponibles
FROM tatuajes_realizados tattoo
INNER JOIN imagenes_tatuajes image
    ON image.id_tatuaje_realizado = tattoo.id_tatuaje_realizado
WHERE tattoo.publicado = TRUE
  AND tattoo.autorizacion_publicacion = TRUE;

