-- Textos personalizables por plan, sin alterar el cálculo de las reglas.
CREATE TABLE IF NOT EXISTS membresias_reglas_textos (
    id_plan BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(1000) NOT NULL,
    PRIMARY KEY (id_plan, codigo),
    FOREIGN KEY (id_plan) REFERENCES membresias_configuracion(id_plan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
