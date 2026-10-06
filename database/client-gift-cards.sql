CREATE TABLE IF NOT EXISTS tarjetas_regalo_clientes (
 id_tarjeta BIGINT UNSIGNED PRIMARY KEY,
 id_cliente BIGINT UNSIGNED NOT NULL,
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (id_tarjeta) REFERENCES tarjetas_regalo(id_tarjeta),
 FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
