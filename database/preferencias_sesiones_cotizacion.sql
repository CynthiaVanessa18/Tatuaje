USE estudio_tatuajes;

CREATE TABLE IF NOT EXISTS preferencias_sesiones_cotizacion (
    id_preferencia BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion BIGINT UNSIGNED NOT NULL,
    numero_sesion INT UNSIGNED NOT NULL,
    fecha_preferida DATE NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE,
    UNIQUE KEY uq_preferencia_sesion (id_cotizacion, numero_sesion),
    INDEX idx_preferencia_fecha (fecha_preferida),
    CHECK (numero_sesion > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
