-- Ampliación aditiva: no modifica promociones ni ventas existentes.
CREATE TABLE IF NOT EXISTS grupos_clientes (
    id_grupo BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos cubiertos completamente por una oferta: no crean un pago ficticio.
CREATE TABLE IF NOT EXISTS pedidos_sin_cobro (
    id_venta BIGINT UNSIGNED PRIMARY KEY,
    clave_idempotencia VARCHAR(128) NOT NULL UNIQUE,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clientes_grupos (
    id_grupo BIGINT UNSIGNED NOT NULL,
    id_cliente BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_grupo, id_cliente),
    FOREIGN KEY (id_grupo) REFERENCES grupos_clientes(id_grupo) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promociones_reglas_tienda (
    id_promocion BIGINT UNSIGNED PRIMARY KEY,
    alcance ENUM('tienda','productos','categorias') NOT NULL,
    publico ENUM('todos','grupo') NOT NULL DEFAULT 'todos',
    id_grupo BIGINT UNSIGNED,
    FOREIGN KEY (id_promocion) REFERENCES promociones(id_promocion) ON DELETE CASCADE,
    FOREIGN KEY (id_grupo) REFERENCES grupos_clientes(id_grupo),
    CHECK ((publico='todos' AND id_grupo IS NULL) OR (publico='grupo' AND id_grupo IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promociones_productos (
    id_promocion BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_promocion,id_producto),
    FOREIGN KEY (id_promocion) REFERENCES promociones_reglas_tienda(id_promocion) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promociones_categorias (
    id_promocion BIGINT UNSIGNED NOT NULL,
    id_categoria_producto BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_promocion,id_categoria_producto),
    FOREIGN KEY (id_promocion) REFERENCES promociones_reglas_tienda(id_promocion) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria_producto) REFERENCES categorias_productos(id_categoria_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
