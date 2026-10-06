-- Ejecutar una vez sobre la base de datos del estudio existente.
CREATE TABLE IF NOT EXISTS perfiles_cuentas (
    id_cuenta BIGINT UNSIGNED PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL DEFAULT '',
    telefono VARCHAR(30) NOT NULL DEFAULT '',
    biografia VARCHAR(1000) NOT NULL DEFAULT '',
    foto_url VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (id_cuenta) REFERENCES cuentas(id_cuenta) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
