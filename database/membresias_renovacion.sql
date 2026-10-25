CREATE TABLE IF NOT EXISTS membresias_renovacion (
 id_membresia BIGINT UNSIGNED PRIMARY KEY,
 habilitada BOOLEAN NOT NULL DEFAULT FALSE,
 modalidad VARCHAR(10) NOT NULL,
 importe_centavos BIGINT NOT NULL,
 resultado_demo VARCHAR(10) NOT NULL DEFAULT 'aprobado',
 terminos_version VARCHAR(30) NOT NULL,
 fecha_aceptacion DATETIME NOT NULL,
 ultimo_resultado VARCHAR(30) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
