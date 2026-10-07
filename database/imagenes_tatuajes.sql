-- Agrega la tabla de imágenes sin modificar registros existentes.
CREATE TABLE IF NOT EXISTS imagenes_tatuajes (
    id_imagen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_tatuaje_realizado BIGINT UNSIGNED NOT NULL,

    imagen_url VARCHAR(1024) NOT NULL,
    descripcion VARCHAR(255),
    texto_alternativo VARCHAR(255),

    orden INT UNSIGNED NOT NULL DEFAULT 0,
    es_portada BOOLEAN NOT NULL DEFAULT FALSE,

    /*
       Esta columna generada permite una sola portada por tatuaje.
       Los NULL no chocan en un índice UNIQUE.
    */
    portada_unica BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN es_portada = TRUE
                THEN id_tatuaje_realizado
                ELSE NULL
            END
        ) VIRTUAL,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_tatuaje_realizado)
        REFERENCES tatuajes_realizados(id_tatuaje_realizado)
        ON DELETE CASCADE,

    UNIQUE KEY uq_portada_por_tatuaje (portada_unica),

    INDEX idx_imagenes_tatuaje (
        id_tatuaje_realizado,
        orden
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
