CREATE TABLE IF NOT EXISTS membresias_configuracion (
 id_plan BIGINT UNSIGNED PRIMARY KEY,
 nivel ENUM('esencial','plus','premium') NOT NULL UNIQUE,
 cuota_mensual DECIMAL(12,2) NOT NULL DEFAULT 0,
 cuota_anual DECIMAL(12,2) NOT NULL DEFAULT 0,
 modalidad ENUM('mensual','anual','ambas') NOT NULL DEFAULT 'ambas',
 FOREIGN KEY (id_plan) REFERENCES planes_membresia(id_plan),
 CHECK (cuota_mensual >= 0 AND cuota_anual >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS membresias_reglas (
 id_plan BIGINT UNSIGNED NOT NULL,
 codigo VARCHAR(40) NOT NULL,
 valor DECIMAL(12,2) NOT NULL DEFAULT 1,
 PRIMARY KEY (id_plan,codigo),
 FOREIGN KEY (id_plan) REFERENCES membresias_configuracion(id_plan),
 CHECK (valor > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
