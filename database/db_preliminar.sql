/*
   TINTA VIVA - ESQUEMA COMPLETO PARA XAMPP
   Compatible y validado con MySQL 8.3 y MariaDB 10.4.32.

   Uso recomendado:
   1. Importar este archivo desde phpMyAdmin en una instalación nueva.
   2. No guardar claves SMTP, contraseñas ni claves de proveedores de IA aquí.
   3. Guardar secretos en un archivo .env excluido mediante .gitignore.
   4. Todas las fechas de la aplicación deben tratarse con la zona
      America/Costa_Rica o convertirse a UTC desde PHP.

   El script no elimina bases ni tablas existentes.
*/

/* ============================================================
   BASE DE DATOS
   ============================================================ */

CREATE DATABASE IF NOT EXISTS estudio_tatuajes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE estudio_tatuajes;

SET NAMES utf8mb4;




/* ============================================================
   1. ROLES
   ============================================================ */

CREATE TABLE IF NOT EXISTS roles (
    id_rol BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(40) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   2. CUENTAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS cuentas (
    id_cuenta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_rol BIGINT UNSIGNED NOT NULL,

    usuario VARCHAR(80) NOT NULL UNIQUE,
    correo VARCHAR(254) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,

    estado ENUM(
        'inactivo',
        'activo',
        'bloqueado'
    ) NOT NULL DEFAULT 'inactivo',

    correo_verificado BOOLEAN NOT NULL DEFAULT FALSE,
    ultimo_acceso DATETIME,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_rol)
        REFERENCES roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   3. CLIENTES
   ============================================================ */

CREATE TABLE IF NOT EXISTS clientes (
    id_cliente BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED NOT NULL UNIQUE,

    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    telefono VARCHAR(25),

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   4. ARTISTAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS artistas (
    id_artista BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED NOT NULL UNIQUE,

    nombre_artistico VARCHAR(120),
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    telefono VARCHAR(25),

    biografia TEXT,
    foto_url VARCHAR(1024),
    slug VARCHAR(160) UNIQUE,
    instagram_url VARCHAR(1024),
    sitio_web_url VARCHAR(1024),

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta),

    INDEX idx_artistas_publicos (
        activo,
        nombre_artistico
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   5. CATEGORÍAS DE TATUAJES
   ============================================================ */

CREATE TABLE IF NOT EXISTS categorias_tatuajes (
    id_categoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,

    /* Una imagen representativa */
    imagen_url VARCHAR(1024),

    activo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   6. ESPECIALIDADES DE ARTISTAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS artistas_especialidades (
    id_artista BIGINT UNSIGNED NOT NULL,
    id_categoria BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (id_artista, id_categoria),

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    FOREIGN KEY (id_categoria)
        REFERENCES categorias_tatuajes(id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   7. CERTIFICACIONES DE ARTISTAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS certificaciones_artistas (
    id_certificacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_artista BIGINT UNSIGNED NOT NULL,

    nombre VARCHAR(180) NOT NULL,
    institucion VARCHAR(180) NOT NULL,

    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE,

    documento_url VARCHAR(1024),

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    CHECK (
        fecha_vencimiento IS NULL
        OR fecha_vencimiento >= fecha_emision
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   8. BOCETOS DE TATUAJES
   ============================================================ */

CREATE TABLE IF NOT EXISTS bocetos_tatuajes (
    id_boceto BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_artista BIGINT UNSIGNED NOT NULL,
    id_categoria BIGINT UNSIGNED NOT NULL,

    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT,
    slug VARCHAR(220) UNIQUE,

    imagen_url VARCHAR(1024) NOT NULL,

    ancho_cm DECIMAL(6,2) NOT NULL,
    alto_cm DECIMAL(6,2) NOT NULL,

    precio_estimado DECIMAL(12,2),

    es_exclusivo BOOLEAN NOT NULL DEFAULT TRUE,
    publicado BOOLEAN NOT NULL DEFAULT TRUE,

    estado ENUM(
        'disponible',
        'reservado',
        'realizado'
    ) NOT NULL DEFAULT 'disponible',

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    FOREIGN KEY (id_categoria)
        REFERENCES categorias_tatuajes(id_categoria),

    CHECK (ancho_cm > 0),
    CHECK (alto_cm > 0),

    CHECK (
        precio_estimado IS NULL
        OR precio_estimado >= 0
    ),

    INDEX idx_bocetos_galeria (
        publicado,
        estado,
        id_categoria,
        id_artista
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   9. COTIZACIONES
   ============================================================ */

CREATE TABLE IF NOT EXISTS cotizaciones (
    id_cotizacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    /* Puede ser NULL para permitir solicitudes de visitantes. */
    id_cliente BIGINT UNSIGNED,
    id_artista BIGINT UNSIGNED,
    id_categoria BIGINT UNSIGNED NOT NULL,
    id_boceto BIGINT UNSIGNED,

    /* Se guarda una copia de los datos de contacto aunque exista una cuenta. */
    nombre_contacto VARCHAR(180) NOT NULL,
    correo_contacto VARCHAR(254) NOT NULL,
    telefono_contacto VARCHAR(25),
    metodo_contacto_preferido ENUM(
        'correo',
        'telefono',
        'whatsapp'
    ) NOT NULL DEFAULT 'correo',

    descripcion_idea TEXT NOT NULL,
    zona_cuerpo VARCHAR(100) NOT NULL,

    /* El cliente puede no conocer todavía las medidas exactas. */
    ancho_cm DECIMAL(6,2),
    alto_cm DECIMAL(6,2),
    tamano_descripcion VARCHAR(120),

    a_color BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_preferida DATE,

    moneda CHAR(3) NOT NULL DEFAULT 'CRC',

    precio_cotizado DECIMAL(12,2),

    anticipo_requerido DECIMAL(12,2)
        NOT NULL DEFAULT 0,

    duracion_estimada_minutos INT UNSIGNED,
    sesiones_estimadas INT UNSIGNED,

    estado ENUM(
        'solicitada',
        'en_revision',
        'enviada',
        'aceptada',
        'rechazada',
        'vencida',
        'cancelada'
    ) NOT NULL DEFAULT 'solicitada',

    fecha_solicitud DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    fecha_vencimiento DATETIME,

    observaciones TEXT,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    FOREIGN KEY (id_categoria)
        REFERENCES categorias_tatuajes(id_categoria),

    FOREIGN KEY (id_boceto)
        REFERENCES bocetos_tatuajes(id_boceto),

    CHECK (
        ancho_cm IS NULL
        OR ancho_cm > 0
    ),

    CHECK (
        alto_cm IS NULL
        OR alto_cm > 0
    ),

    CHECK (
        (ancho_cm IS NULL AND alto_cm IS NULL)
        OR
        (ancho_cm IS NOT NULL AND alto_cm IS NOT NULL)
    ),

    CHECK (anticipo_requerido >= 0),

    CHECK (
        precio_cotizado IS NULL
        OR precio_cotizado >= anticipo_requerido
    ),

    CHECK (
        duracion_estimada_minutos IS NULL
        OR duracion_estimada_minutos > 0
    ),

    CHECK (
        sesiones_estimadas IS NULL
        OR sesiones_estimadas > 0
    ),

    CHECK (
        fecha_vencimiento IS NULL
        OR fecha_vencimiento > fecha_solicitud
    ),

    INDEX idx_cotizaciones_cliente (
        id_cliente,
        estado,
        fecha_solicitud
    ),

    INDEX idx_cotizaciones_artista (
        id_artista,
        estado,
        fecha_solicitud
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   10. REFERENCIAS DE COTIZACIÓN
   Varias imágenes por cotización
   ============================================================ */

CREATE TABLE IF NOT EXISTS referencias_cotizacion (
    id_referencia BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cotizacion BIGINT UNSIGNED NOT NULL,

    imagen_url VARCHAR(1024) NOT NULL,
    nombre_archivo_original VARCHAR(255),
    tipo_mime VARCHAR(100),
    descripcion VARCHAR(255),
    texto_alternativo VARCHAR(255),
    orden INT UNSIGNED NOT NULL DEFAULT 0,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cotizacion)
        REFERENCES cotizaciones(id_cotizacion)
        ON DELETE CASCADE,

    INDEX idx_referencias_cotizacion (
        id_cotizacion,
        orden
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   11. TATUAJES REALIZADOS
   ============================================================ */

CREATE TABLE IF NOT EXISTS tatuajes_realizados (
    id_tatuaje_realizado BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cliente BIGINT UNSIGNED NOT NULL,
    id_artista BIGINT UNSIGNED NOT NULL,

    id_boceto BIGINT UNSIGNED,
    id_cotizacion BIGINT UNSIGNED,
    id_categoria BIGINT UNSIGNED NOT NULL,

    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT,
    slug VARCHAR(220) UNIQUE,

    fecha_realizacion DATE NOT NULL,

    autorizacion_publicacion BOOLEAN NOT NULL DEFAULT FALSE,
    publicado BOOLEAN NOT NULL DEFAULT FALSE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    FOREIGN KEY (id_boceto)
        REFERENCES bocetos_tatuajes(id_boceto),

    FOREIGN KEY (id_cotizacion)
        REFERENCES cotizaciones(id_cotizacion),

    FOREIGN KEY (id_categoria)
        REFERENCES categorias_tatuajes(id_categoria),

    CHECK (
        publicado = FALSE
        OR autorizacion_publicacion = TRUE
    ),

    INDEX idx_tatuajes_galeria (
        publicado,
        id_categoria,
        id_artista,
        fecha_realizacion
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   12. IMÁGENES DE TATUAJES REALIZADOS
   Varias imágenes por tatuaje
   ============================================================ */

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


/* ============================================================
   13. HORARIOS DE ARTISTAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS horarios_artistas (
    id_horario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_artista BIGINT UNSIGNED NOT NULL,

    dia_semana TINYINT UNSIGNED NOT NULL
        COMMENT '1=lunes, 7=domingo',

    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    CHECK (dia_semana BETWEEN 1 AND 7),
    CHECK (hora_fin > hora_inicio),

    UNIQUE KEY uq_horario_artista (
        id_artista,
        dia_semana,
        hora_inicio,
        hora_fin
    ),

    INDEX idx_horarios_disponibles (
        id_artista,
        dia_semana,
        activo
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   14. BLOQUEOS DE AGENDA
   ============================================================ */

CREATE TABLE IF NOT EXISTS bloqueos_agenda (
    id_bloqueo BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_artista BIGINT UNSIGNED NOT NULL,

    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin DATETIME NOT NULL,

    motivo VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    CHECK (fecha_hora_fin > fecha_hora_inicio),

    INDEX idx_bloqueos_artista (
        id_artista,
        activo,
        fecha_hora_inicio,
        fecha_hora_fin
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   15. CITAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS citas (
    id_cita BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cliente BIGINT UNSIGNED NOT NULL,
    id_artista BIGINT UNSIGNED NOT NULL,

    id_cotizacion BIGINT UNSIGNED,
    id_tatuaje_realizado BIGINT UNSIGNED,

    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin DATETIME NOT NULL,

    numero_sesion INT UNSIGNED NOT NULL DEFAULT 1,

    origen ENUM(
        'web',
        'administracion',
        'telefono'
    ) NOT NULL DEFAULT 'web',

    estado ENUM(
        'pendiente',
        'confirmada',
        'en_proceso',
        'finalizada',
        'cancelada',
        'no_asistio'
    ) NOT NULL DEFAULT 'pendiente',

    observaciones TEXT,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    FOREIGN KEY (id_artista)
        REFERENCES artistas(id_artista),

    FOREIGN KEY (id_cotizacion)
        REFERENCES cotizaciones(id_cotizacion),

    FOREIGN KEY (id_tatuaje_realizado)
        REFERENCES tatuajes_realizados(id_tatuaje_realizado),

    CHECK (fecha_hora_fin > fecha_hora_inicio),
    CHECK (DATE(fecha_hora_inicio) = DATE(fecha_hora_fin)),
    CHECK (numero_sesion > 0),

    INDEX idx_citas_artista_agenda (
        id_artista,
        fecha_hora_inicio,
        fecha_hora_fin,
        estado
    ),

    INDEX idx_citas_cliente (
        id_cliente,
        fecha_hora_inicio,
        estado
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   16. CALIFICACIONES
   ============================================================ */

CREATE TABLE IF NOT EXISTS calificaciones_artistas (
    id_calificacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cita BIGINT UNSIGNED NOT NULL UNIQUE,

    puntuacion TINYINT UNSIGNED NOT NULL,
    comentario TEXT,

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    estado_publicacion ENUM(
        'pendiente',
        'publicado',
        'rechazado'
    ) NOT NULL DEFAULT 'pendiente',

    FOREIGN KEY (id_cita)
        REFERENCES citas(id_cita),

    CHECK (puntuacion BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   17. CUIDADOS DEL TATUAJE
   ============================================================ */

CREATE TABLE IF NOT EXISTS cuidados_tatuaje (
    id_cuidado BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    titulo VARCHAR(180) NOT NULL,
    slug VARCHAR(220) UNIQUE,
    categoria VARCHAR(100),
    contenido TEXT NOT NULL,

    imagen_url VARCHAR(1024),
    texto_alternativo VARCHAR(255),

    orden INT UNSIGNED NOT NULL DEFAULT 0,

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cuidados_publicos (
        activo,
        orden
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   18. CATEGORÍAS DE PRODUCTOS
   ============================================================ */

CREATE TABLE IF NOT EXISTS categorias_productos (
    id_categoria_producto BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,

    activo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   19. PRODUCTOS
   ============================================================ */

CREATE TABLE IF NOT EXISTS productos (
    id_producto BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_categoria_producto BIGINT UNSIGNED NOT NULL,

    sku VARCHAR(80) NOT NULL UNIQUE,

    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT,

    precio DECIMAL(12,2) NOT NULL,

    stock_actual INT UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED NOT NULL DEFAULT 0,

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    FOREIGN KEY (id_categoria_producto)
        REFERENCES categorias_productos(id_categoria_producto),

    CHECK (precio >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   20. IMÁGENES DE PRODUCTOS
   Varias imágenes por producto
   ============================================================ */

CREATE TABLE IF NOT EXISTS imagenes_productos (
    id_imagen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_producto BIGINT UNSIGNED NOT NULL,

    imagen_url VARCHAR(1024) NOT NULL,

    orden INT UNSIGNED NOT NULL DEFAULT 0,
    es_portada BOOLEAN NOT NULL DEFAULT FALSE,

    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   21. PROMOCIONES
   ============================================================ */

CREATE TABLE IF NOT EXISTS promociones (
    id_promocion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT,

    imagen_url VARCHAR(1024),

    tipo_descuento ENUM(
        'porcentaje',
        'monto'
    ) NOT NULL,

    valor_descuento DECIMAL(12,2) NOT NULL,

    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,

    codigo VARCHAR(80) UNIQUE,

    minimo_compra DECIMAL(12,2)
        NOT NULL DEFAULT 0,

    aplica_a ENUM(
        'productos',
        'tatuajes',
        'ambos'
    ) NOT NULL DEFAULT 'ambos',

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    CHECK (fecha_fin > fecha_inicio),
    CHECK (valor_descuento > 0),
    CHECK (minimo_compra >= 0),

    CHECK (
        tipo_descuento <> 'porcentaje'
        OR valor_descuento <= 100
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   22. PLANES DE MEMBRESÍA
   ============================================================ */

CREATE TABLE IF NOT EXISTS planes_membresia (
    id_plan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,

    precio DECIMAL(12,2) NOT NULL,

    duracion_dias INT UNSIGNED NOT NULL,

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    CHECK (precio >= 0),
    CHECK (duracion_dias > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   23. BENEFICIOS
   ============================================================ */

CREATE TABLE IF NOT EXISTS beneficios (
    id_beneficio BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT,

    tipo ENUM(
        'descuento_porcentaje',
        'descuento_monto',
        'servicio',
        'prioridad',
        'otro'
    ) NOT NULL,

    valor DECIMAL(12,2),

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    CHECK (
        valor IS NULL
        OR valor >= 0
    ),

    CHECK (
        tipo <> 'descuento_porcentaje'
        OR (
            valor IS NOT NULL
            AND valor <= 100
        )
    ),

    CHECK (
        tipo <> 'descuento_monto'
        OR valor IS NOT NULL
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   24. PLANES Y BENEFICIOS
   ============================================================ */

CREATE TABLE IF NOT EXISTS planes_beneficios (
    id_plan BIGINT UNSIGNED NOT NULL,
    id_beneficio BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (id_plan, id_beneficio),

    FOREIGN KEY (id_plan)
        REFERENCES planes_membresia(id_plan),

    FOREIGN KEY (id_beneficio)
        REFERENCES beneficios(id_beneficio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   25. VENTAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS ventas (
    id_venta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cliente BIGINT UNSIGNED NOT NULL,

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    estado ENUM(
        'pendiente',
        'confirmada',
        'completada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente',

    moneda CHAR(3) NOT NULL DEFAULT 'CRC',

    subtotal DECIMAL(12,2)
        NOT NULL DEFAULT 0,

    descuento_total DECIMAL(12,2)
        NOT NULL DEFAULT 0,

    impuesto_total DECIMAL(12,2)
        NOT NULL DEFAULT 0,

    total DECIMAL(12,2)
        GENERATED ALWAYS AS (
            subtotal
            - descuento_total
            + impuesto_total
        ) STORED,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    CHECK (
        subtotal >= 0
        AND descuento_total >= 0
        AND descuento_total <= subtotal
        AND impuesto_total >= 0
    ),

    INDEX (fecha, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   26. DETALLE DE VENTAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS detalle_ventas (
    id_detalle_venta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_venta BIGINT UNSIGNED NOT NULL,

    tipo_item ENUM(
        'producto',
        'tatuaje',
        'membresia',
        'tarjeta_regalo'
    ) NOT NULL,

    id_promocion BIGINT UNSIGNED,

    id_producto BIGINT UNSIGNED,
    id_cotizacion BIGINT UNSIGNED,
    id_plan BIGINT UNSIGNED,

    descripcion VARCHAR(255) NOT NULL,

    cantidad INT UNSIGNED NOT NULL DEFAULT 1,

    precio_unitario DECIMAL(12,2) NOT NULL,

    descuento DECIMAL(12,2)
        NOT NULL DEFAULT 0,

    impuesto DECIMAL(12,2)
        NOT NULL DEFAULT 0,

    total_linea DECIMAL(16,2)
        GENERATED ALWAYS AS (
            cantidad * precio_unitario
            - descuento
            + impuesto
        ) STORED,

    FOREIGN KEY (id_venta)
        REFERENCES ventas(id_venta),

    FOREIGN KEY (id_promocion)
        REFERENCES promociones(id_promocion),

    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto),

    FOREIGN KEY (id_cotizacion)
        REFERENCES cotizaciones(id_cotizacion),

    FOREIGN KEY (id_plan)
        REFERENCES planes_membresia(id_plan),

    CHECK (
        cantidad > 0
        AND precio_unitario >= 0
        AND descuento >= 0
        AND impuesto >= 0
        AND descuento <= cantidad * precio_unitario
    ),

    CHECK (
        tipo_item = 'producto'
        OR cantidad = 1
    ),

    CHECK (
        (
            tipo_item = 'producto'
            AND id_producto IS NOT NULL
            AND id_cotizacion IS NULL
            AND id_plan IS NULL
        )

        OR

        (
            tipo_item = 'tatuaje'
            AND id_producto IS NULL
            AND id_cotizacion IS NOT NULL
            AND id_plan IS NULL
        )

        OR

        (
            tipo_item = 'membresia'
            AND id_producto IS NULL
            AND id_cotizacion IS NULL
            AND id_plan IS NOT NULL
        )

        OR

        (
            tipo_item = 'tarjeta_regalo'
            AND id_producto IS NULL
            AND id_cotizacion IS NULL
            AND id_plan IS NULL
        )
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   27. PAGOS
   ============================================================ */

CREATE TABLE IF NOT EXISTS pagos (
    id_pago BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_venta BIGINT UNSIGNED NOT NULL,

    metodo ENUM(
        'efectivo',
        'transferencia',
        'pasarela',
        'tarjeta_regalo'
    ) NOT NULL,

    tipo_operacion ENUM(
        'cobro',
        'reembolso'
    ) NOT NULL DEFAULT 'cobro',

    id_pago_original BIGINT UNSIGNED,

    monto DECIMAL(12,2) NOT NULL,

    moneda CHAR(3) NOT NULL DEFAULT 'CRC',

    estado ENUM(
        'pendiente',
        'aprobado',
        'rechazado',
        'cancelado'
    ) NOT NULL DEFAULT 'pendiente',

    proveedor VARCHAR(80),
    referencia_externa VARCHAR(190),

    clave_idempotencia VARCHAR(128)
        NOT NULL UNIQUE,

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_venta)
        REFERENCES ventas(id_venta),

    FOREIGN KEY (id_pago_original)
        REFERENCES pagos(id_pago),

    UNIQUE (
        proveedor,
        referencia_externa
    ),

    CHECK (monto > 0),

    CHECK (
        (
            tipo_operacion = 'cobro'
            AND id_pago_original IS NULL
        )

        OR

        (
            tipo_operacion = 'reembolso'
            AND id_pago_original IS NOT NULL
        )
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   28. MEMBRESÍAS DE CLIENTES
   ============================================================ */

CREATE TABLE IF NOT EXISTS membresias_clientes (
    id_membresia BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cliente BIGINT UNSIGNED NOT NULL,
    id_plan BIGINT UNSIGNED NOT NULL,

    id_detalle_venta BIGINT UNSIGNED NOT NULL UNIQUE,

    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,

    estado ENUM(
        'pendiente',
        'activa',
        'vencida',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente',

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    FOREIGN KEY (id_plan)
        REFERENCES planes_membresia(id_plan),

    FOREIGN KEY (id_detalle_venta)
        REFERENCES detalle_ventas(id_detalle_venta),

    CHECK (fecha_fin > fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   29. TARJETAS DE REGALO
   ============================================================ */

CREATE TABLE IF NOT EXISTS tarjetas_regalo (
    id_tarjeta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    codigo_hash BINARY(32) NOT NULL UNIQUE,

    id_cliente_comprador BIGINT UNSIGNED NOT NULL,

    id_detalle_venta BIGINT UNSIGNED NULL DEFAULT NULL UNIQUE,

    nombre_destinatario VARCHAR(180) NOT NULL,
    correo_destinatario VARCHAR(254) NOT NULL,

    mensaje TEXT,

    monto_inicial DECIMAL(12,2) NOT NULL,

    moneda CHAR(3) NOT NULL DEFAULT 'CRC',

    fecha_emision DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    fecha_vencimiento DATETIME,

    estado ENUM(
        'pendiente',
        'activa',
        'agotada',
        'vencida',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente',

    FOREIGN KEY (id_cliente_comprador)
        REFERENCES clientes(id_cliente),

    FOREIGN KEY (id_detalle_venta)
        REFERENCES detalle_ventas(id_detalle_venta),

    CHECK (monto_inicial > 0),

    CHECK (
        fecha_vencimiento IS NULL
        OR fecha_vencimiento > fecha_emision
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   30. MOVIMIENTOS DE TARJETAS DE REGALO
   ============================================================ */

CREATE TABLE IF NOT EXISTS movimientos_tarjetas_regalo (
    id_movimiento BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_tarjeta BIGINT UNSIGNED NOT NULL,

    id_venta BIGINT UNSIGNED,
    id_pago BIGINT UNSIGNED UNIQUE,

    tipo ENUM(
        'carga_inicial',
        'canje',
        'devolucion',
        'anulacion'
    ) NOT NULL,

    monto DECIMAL(12,2) NOT NULL,

    fecha DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    referencia VARCHAR(128)
        NOT NULL UNIQUE,

    FOREIGN KEY (id_tarjeta)
        REFERENCES tarjetas_regalo(id_tarjeta),

    FOREIGN KEY (id_venta)
        REFERENCES ventas(id_venta),

    FOREIGN KEY (id_pago)
        REFERENCES pagos(id_pago),

    CHECK (monto > 0),

    CHECK (
        tipo <> 'canje'
        OR (
            id_venta IS NOT NULL
            AND id_pago IS NOT NULL
        )
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   31. PREGUNTAS FRECUENTES
   ============================================================ */

CREATE TABLE IF NOT EXISTS preguntas_frecuentes (
    id_pregunta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    categoria VARCHAR(100),
    pregunta VARCHAR(500) NOT NULL,
    respuesta TEXT NOT NULL,

    orden INT UNSIGNED NOT NULL DEFAULT 0,

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_preguntas_publicas (
        activo,
        categoria,
        orden
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   32. TESTIMONIOS
   ============================================================ */

CREATE TABLE IF NOT EXISTS testimonios (
    id_testimonio BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cliente BIGINT UNSIGNED NOT NULL,
    id_cita BIGINT UNSIGNED,

    titulo VARCHAR(180),
    contenido TEXT NOT NULL,
    nombre_publico VARCHAR(180),

    fecha DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    estado_publicacion ENUM(
        'pendiente',
        'publicado',
        'rechazado'
    ) NOT NULL DEFAULT 'pendiente',

    destacado BOOLEAN NOT NULL DEFAULT FALSE,

    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    FOREIGN KEY (id_cita)
        REFERENCES citas(id_cita),

    UNIQUE KEY uq_testimonio_cita (id_cita),

    INDEX idx_testimonios_publicos (
        estado_publicacion,
        destacado,
        fecha
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   33. CONFIGURACIONES DEL SISTEMA
   ============================================================ */

CREATE TABLE IF NOT EXISTS configuraciones_sistema (
    id_configuracion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    clave VARCHAR(100) NOT NULL UNIQUE,

    valor TEXT NOT NULL,

    tipo_dato ENUM(
        'texto',
        'numero',
        'booleano',
        'json'
    ) NOT NULL DEFAULT 'texto',

    descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   34. PLANTILLAS DE CORREO
   ============================================================ */

CREATE TABLE IF NOT EXISTS plantillas_correo (
    id_plantilla BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    codigo VARCHAR(100) NOT NULL UNIQUE,

    nombre VARCHAR(180) NOT NULL,

    asunto VARCHAR(255) NOT NULL,

    contenido TEXT NOT NULL,

    activo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



/* ============================================================
   SEGURIDAD Y RECUPERACIÓN DE CUENTAS
   Los tokens reales nunca se guardan: únicamente su SHA-256.
   ============================================================ */

CREATE TABLE IF NOT EXISTS tokens_verificacion_correo (
    id_token BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED NOT NULL,

    token_hash BINARY(32) NOT NULL UNIQUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    fecha_uso DATETIME,

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta)
        ON DELETE CASCADE,

    CHECK (fecha_expiracion > fecha_creacion),

    INDEX idx_token_verificacion_cuenta (
        id_cuenta,
        fecha_expiracion,
        fecha_uso
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS tokens_recuperacion_contrasena (
    id_token BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED NOT NULL,

    token_hash BINARY(32) NOT NULL UNIQUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    fecha_uso DATETIME,

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta)
        ON DELETE CASCADE,

    CHECK (fecha_expiracion > fecha_creacion),

    INDEX idx_token_recuperacion_cuenta (
        id_cuenta,
        fecha_expiracion,
        fecha_uso
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS sesiones_cuentas (
    id_sesion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED NOT NULL,

    selector CHAR(32) NOT NULL UNIQUE,
    verificador_hash BINARY(32) NOT NULL,

    direccion_ip VARBINARY(16),
    agente_usuario VARCHAR(500),

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_actividad DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,

    revocada BOOLEAN NOT NULL DEFAULT FALSE,

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta)
        ON DELETE CASCADE,

    CHECK (fecha_expiracion > fecha_creacion),

    INDEX idx_sesiones_cuenta (
        id_cuenta,
        revocada,
        fecha_expiracion
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS factores_dos_pasos (
    id_factor BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED NOT NULL,

    tipo ENUM(
        'totp',
        'correo'
    ) NOT NULL DEFAULT 'totp',

    /* Debe cifrarse desde PHP con una clave guardada fuera de Git. */
    secreto_cifrado VARBINARY(512),

    habilitado BOOLEAN NOT NULL DEFAULT FALSE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta)
        ON DELETE CASCADE,

    UNIQUE KEY uq_factor_cuenta_tipo (
        id_cuenta,
        tipo
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS codigos_recuperacion_dos_pasos (
    id_codigo BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_factor BIGINT UNSIGNED NOT NULL,

    codigo_hash BINARY(32) NOT NULL UNIQUE,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_uso DATETIME,

    FOREIGN KEY (id_factor)
        REFERENCES factores_dos_pasos(id_factor)
        ON DELETE CASCADE,

    INDEX idx_codigos_factor (
        id_factor,
        fecha_uso
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS auditoria_sistema (
    id_evento BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED,

    evento VARCHAR(120) NOT NULL,
    entidad VARCHAR(100),
    id_entidad VARCHAR(100),

    detalle JSON,
    direccion_ip VARBINARY(16),
    agente_usuario VARCHAR(500),

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta),

    INDEX idx_auditoria_cuenta_fecha (
        id_cuenta,
        fecha
    ),

    INDEX idx_auditoria_entidad (
        entidad,
        id_entidad,
        fecha
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   TRAZABILIDAD DE COTIZACIONES Y CITAS
   ============================================================ */

CREATE TABLE IF NOT EXISTS historial_cotizaciones (
    id_historial BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion BIGINT UNSIGNED NOT NULL,
    id_cuenta_responsable BIGINT UNSIGNED,

    estado_anterior ENUM(
        'solicitada',
        'en_revision',
        'enviada',
        'aceptada',
        'rechazada',
        'vencida',
        'cancelada'
    ),

    estado_nuevo ENUM(
        'solicitada',
        'en_revision',
        'enviada',
        'aceptada',
        'rechazada',
        'vencida',
        'cancelada'
    ) NOT NULL,

    precio_cotizado DECIMAL(12,2),
    observacion TEXT,

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cotizacion)
        REFERENCES cotizaciones(id_cotizacion)
        ON DELETE CASCADE,

    FOREIGN KEY (id_cuenta_responsable)
        REFERENCES cuentas(id_cuenta),

    CHECK (
        precio_cotizado IS NULL
        OR precio_cotizado >= 0
    ),

    INDEX idx_historial_cotizacion (
        id_cotizacion,
        fecha
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS historial_citas (
    id_historial BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cita BIGINT UNSIGNED NOT NULL,
    id_cuenta_responsable BIGINT UNSIGNED,

    estado_anterior ENUM(
        'pendiente',
        'confirmada',
        'en_proceso',
        'finalizada',
        'cancelada',
        'no_asistio'
    ),

    estado_nuevo ENUM(
        'pendiente',
        'confirmada',
        'en_proceso',
        'finalizada',
        'cancelada',
        'no_asistio'
    ) NOT NULL,

    observacion TEXT,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cita)
        REFERENCES citas(id_cita)
        ON DELETE CASCADE,

    FOREIGN KEY (id_cuenta_responsable)
        REFERENCES cuentas(id_cuenta),

    INDEX idx_historial_cita (
        id_cita,
        fecha
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   CORREOS SALIENTES
   La contraseña SMTP debe vivir en un archivo .env ignorado por Git.
   ============================================================ */

CREATE TABLE IF NOT EXISTS correos_salida (
    id_correo BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cuenta BIGINT UNSIGNED,
    id_plantilla BIGINT UNSIGNED,
    clave_evento VARCHAR(190),

    destinatario VARCHAR(254) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    contenido MEDIUMTEXT NOT NULL,
    datos_plantilla JSON,

    estado ENUM(
        'pendiente',
        'enviando',
        'enviado',
        'fallido',
        'cancelado'
    ) NOT NULL DEFAULT 'pendiente',

    intentos INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_error TEXT,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_programada DATETIME,
    fecha_envio DATETIME,

    FOREIGN KEY (id_cuenta)
        REFERENCES cuentas(id_cuenta),

    FOREIGN KEY (id_plantilla)
        REFERENCES plantillas_correo(id_plantilla),

    UNIQUE KEY uq_correos_clave_evento (clave_evento),

    INDEX idx_correos_pendientes (
        estado,
        fecha_programada,
        intentos
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   CHATBOT
   Las claves del proveedor de IA no se almacenan en la base de datos.
   Deben mantenerse en un archivo .env fuera del repositorio.
   ============================================================ */

CREATE TABLE IF NOT EXISTS configuraciones_chatbot (
    id_configuracion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,

    tipo_dato ENUM(
        'texto',
        'numero',
        'booleano',
        'json'
    ) NOT NULL DEFAULT 'texto',

    descripcion VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,

    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS fuentes_conocimiento_chatbot (
    id_fuente BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    tipo ENUM(
        'pregunta_frecuente',
        'cuidado_tatuaje',
        'contenido_personalizado'
    ) NOT NULL,

    id_pregunta BIGINT UNSIGNED,
    id_cuidado BIGINT UNSIGNED,

    titulo VARCHAR(255),
    contenido MEDIUMTEXT,

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_pregunta)
        REFERENCES preguntas_frecuentes(id_pregunta),

    FOREIGN KEY (id_cuidado)
        REFERENCES cuidados_tatuaje(id_cuidado),

    CHECK (
        (
            tipo = 'pregunta_frecuente'
            AND id_pregunta IS NOT NULL
            AND id_cuidado IS NULL
            AND contenido IS NULL
        )
        OR
        (
            tipo = 'cuidado_tatuaje'
            AND id_pregunta IS NULL
            AND id_cuidado IS NOT NULL
            AND contenido IS NULL
        )
        OR
        (
            tipo = 'contenido_personalizado'
            AND id_pregunta IS NULL
            AND id_cuidado IS NULL
            AND contenido IS NOT NULL
        )
    ),

    INDEX idx_fuentes_chatbot (
        activo,
        tipo
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS conversaciones_chat (
    id_conversacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_cliente BIGINT UNSIGNED,

    /* Permite conversar sin crear una cuenta. */
    identificador_visitante VARCHAR(64),

    canal ENUM(
        'web',
        'administracion'
    ) NOT NULL DEFAULT 'web',

    estado ENUM(
        'activa',
        'cerrada',
        'escalada'
    ) NOT NULL DEFAULT 'activa',

    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actividad DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    CHECK (
        id_cliente IS NOT NULL
        OR identificador_visitante IS NOT NULL
    ),

    INDEX idx_conversaciones_cliente (
        id_cliente,
        fecha_ultima_actividad
    ),

    INDEX idx_conversaciones_estado (
        estado,
        fecha_ultima_actividad
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS mensajes_chat (
    id_mensaje BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_conversacion BIGINT UNSIGNED NOT NULL,

    rol ENUM(
        'usuario',
        'asistente',
        'sistema'
    ) NOT NULL,

    contenido MEDIUMTEXT NOT NULL,

    proveedor VARCHAR(80),
    modelo VARCHAR(120),

    tokens_entrada INT UNSIGNED,
    tokens_salida INT UNSIGNED,
    duracion_milisegundos INT UNSIGNED,

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_conversacion)
        REFERENCES conversaciones_chat(id_conversacion)
        ON DELETE CASCADE,

    INDEX idx_mensajes_conversacion (
        id_conversacion,
        fecha
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS retroalimentacion_chatbot (
    id_retroalimentacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_mensaje BIGINT UNSIGNED NOT NULL UNIQUE,
    id_cliente BIGINT UNSIGNED,

    fue_util BOOLEAN NOT NULL,
    comentario VARCHAR(500),

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_mensaje)
        REFERENCES mensajes_chat(id_mensaje)
        ON DELETE CASCADE,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   VALIDACIÓN DE AGENDA
   Evita citas fuera de horario, durante bloqueos o superpuestas.
   ============================================================ */

DROP TRIGGER IF EXISTS trg_citas_validar_insert;
DROP TRIGGER IF EXISTS trg_citas_validar_update;

DELIMITER $

CREATE TRIGGER trg_citas_validar_insert
BEFORE INSERT ON citas
FOR EACH ROW
BEGIN
    IF DATE(NEW.fecha_hora_inicio) <> DATE(NEW.fecha_hora_fin) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La cita debe iniciar y terminar el mismo día';
    END IF;

    IF NEW.estado NOT IN ('cancelada', 'no_asistio') THEN
        IF NOT EXISTS (
            SELECT 1
            FROM horarios_artistas h
            WHERE h.id_artista = NEW.id_artista
              AND h.activo = TRUE
              AND h.dia_semana = WEEKDAY(NEW.fecha_hora_inicio) + 1
              AND TIME(NEW.fecha_hora_inicio) >= h.hora_inicio
              AND TIME(NEW.fecha_hora_fin) <= h.hora_fin
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La cita está fuera del horario activo del artista';
        END IF;

        IF EXISTS (
            SELECT 1
            FROM bloqueos_agenda b
            WHERE b.id_artista = NEW.id_artista
              AND b.activo = TRUE
              AND NEW.fecha_hora_inicio < b.fecha_hora_fin
              AND NEW.fecha_hora_fin > b.fecha_hora_inicio
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La cita coincide con un bloqueo de agenda';
        END IF;

        IF EXISTS (
            SELECT 1
            FROM citas c
            WHERE c.id_artista = NEW.id_artista
              AND c.estado NOT IN ('cancelada', 'no_asistio')
              AND NEW.fecha_hora_inicio < c.fecha_hora_fin
              AND NEW.fecha_hora_fin > c.fecha_hora_inicio
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'El artista ya tiene una cita en ese intervalo';
        END IF;
    END IF;
END$


CREATE TRIGGER trg_citas_validar_update
BEFORE UPDATE ON citas
FOR EACH ROW
BEGIN
    IF DATE(NEW.fecha_hora_inicio) <> DATE(NEW.fecha_hora_fin) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La cita debe iniciar y terminar el mismo día';
    END IF;

    IF NEW.estado NOT IN ('cancelada', 'no_asistio') THEN
        IF NOT EXISTS (
            SELECT 1
            FROM horarios_artistas h
            WHERE h.id_artista = NEW.id_artista
              AND h.activo = TRUE
              AND h.dia_semana = WEEKDAY(NEW.fecha_hora_inicio) + 1
              AND TIME(NEW.fecha_hora_inicio) >= h.hora_inicio
              AND TIME(NEW.fecha_hora_fin) <= h.hora_fin
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La cita está fuera del horario activo del artista';
        END IF;

        IF EXISTS (
            SELECT 1
            FROM bloqueos_agenda b
            WHERE b.id_artista = NEW.id_artista
              AND b.activo = TRUE
              AND NEW.fecha_hora_inicio < b.fecha_hora_fin
              AND NEW.fecha_hora_fin > b.fecha_hora_inicio
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La cita coincide con un bloqueo de agenda';
        END IF;

        IF EXISTS (
            SELECT 1
            FROM citas c
            WHERE c.id_artista = NEW.id_artista
              AND c.id_cita <> NEW.id_cita
              AND c.estado NOT IN ('cancelada', 'no_asistio')
              AND NEW.fecha_hora_inicio < c.fecha_hora_fin
              AND NEW.fecha_hora_fin > c.fecha_hora_inicio
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'El artista ya tiene una cita en ese intervalo';
        END IF;
    END IF;
END$

DELIMITER ;


/* ============================================================
   VISTAS
   ============================================================ */


/* ------------------------------------------------------------
   SALDO DE TARJETAS DE REGALO
   ------------------------------------------------------------ */

CREATE OR REPLACE VIEW vista_saldo_tarjetas AS
SELECT
    t.id_tarjeta,
    t.estado,
    t.moneda,

    COALESCE(
        SUM(
            CASE
                WHEN m.tipo IN (
                    'carga_inicial',
                    'devolucion'
                )
                THEN m.monto

                ELSE -m.monto
            END
        ),
        0
    ) AS saldo_actual

FROM tarjetas_regalo t

LEFT JOIN movimientos_tarjetas_regalo m
    ON m.id_tarjeta = t.id_tarjeta

GROUP BY
    t.id_tarjeta,
    t.estado,
    t.moneda;


/* ------------------------------------------------------------
   SALDO DE VENTAS
   ------------------------------------------------------------ */

CREATE OR REPLACE VIEW vista_saldo_ventas AS
SELECT
    v.id_venta,
    v.id_cliente,
    v.moneda,
    v.total,

    COALESCE(
        SUM(
            CASE
                WHEN p.estado = 'aprobado'
                THEN
                    CASE
                        WHEN p.tipo_operacion = 'cobro'
                        THEN p.monto
                        ELSE -p.monto
                    END

                ELSE 0
            END
        ),
        0
    ) AS pagado_neto,

    v.total -

    COALESCE(
        SUM(
            CASE
                WHEN p.estado = 'aprobado'
                THEN
                    CASE
                        WHEN p.tipo_operacion = 'cobro'
                        THEN p.monto
                        ELSE -p.monto
                    END

                ELSE 0
            END
        ),
        0
    ) AS saldo_pendiente

FROM ventas v

LEFT JOIN pagos p
    ON p.id_venta = v.id_venta

GROUP BY
    v.id_venta,
    v.id_cliente,
    v.moneda,
    v.total;


/* ------------------------------------------------------------
   CALIFICACIÓN DE ARTISTAS
   ------------------------------------------------------------ */

CREATE OR REPLACE VIEW vista_calificaciones_artistas AS
SELECT
    a.id_artista,
    a.nombre_artistico,

    COUNT(ca.id_calificacion)
        AS cantidad_calificaciones,

    ROUND(
        AVG(ca.puntuacion),
        2
    ) AS promedio

FROM artistas a

LEFT JOIN citas c
    ON c.id_artista = a.id_artista

LEFT JOIN calificaciones_artistas ca
    ON ca.id_cita = c.id_cita
    AND ca.estado_publicacion = 'publicado'

GROUP BY
    a.id_artista,
    a.nombre_artistico;


/* ------------------------------------------------------------
   CITAS POR ESTADO
   ------------------------------------------------------------ */

CREATE OR REPLACE VIEW vista_citas_por_estado AS

SELECT
    estado,
    COUNT(*) AS cantidad

FROM citas

GROUP BY estado;


/* ------------------------------------------------------------
   INGRESOS MENSUALES
   ------------------------------------------------------------ */

CREATE OR REPLACE VIEW vista_ingresos_mensuales AS

SELECT
    DATE_FORMAT(fecha, '%Y-%m') AS periodo,
    moneda,

    SUM(
        CASE
            WHEN tipo_operacion = 'cobro'
            THEN monto

            ELSE -monto
        END
    ) AS ingreso_neto

FROM pagos

WHERE estado = 'aprobado'

GROUP BY
    DATE_FORMAT(fecha, '%Y-%m'),
    moneda;


/* ============================================================
   DATOS INICIALES
   ============================================================ */


/* ------------------------------------------------------------
   ROLES
   ------------------------------------------------------------ */

INSERT IGNORE INTO roles (
    nombre_rol,
    descripcion
)
VALUES
    (
        'administrador',
        'Administracion del sistema'
    ),
    (
        'secretaria',
        'Atencion y gestion de citas'
    ),
    (
        'artista',
        'Gestion de trabajos y agenda propia'
    ),
    (
        'cliente',
        'Solicitudes y compras propias'
    );


/* ------------------------------------------------------------
   CATEGORÍAS DE TATUAJES
   ------------------------------------------------------------ */

INSERT IGNORE INTO categorias_tatuajes (
    nombre
)
VALUES
    ('Blackwork'),
    ('Realismo'),
    ('Japanese'),
    ('Lettering'),
    ('Trash Polka'),
    ('Neo Traditional'),
    ('Linea fina');


/* ------------------------------------------------------------
   CONFIGURACIONES
   ------------------------------------------------------------ */

INSERT IGNORE INTO configuraciones_sistema (
    clave,
    valor,
    tipo_dato,
    descripcion
)
VALUES
    (
        'nombre_estudio',
        'Estudio Tattoo',
        'texto',
        'Nombre comercial provisional'
    ),
    (
        'moneda',
        'CRC',
        'texto',
        'Moneda unica del catalogo'
    ),
    (
        'zona_horaria',
        'America/Costa_Rica',
        'texto',
        'Zona local para mostrar citas y horarios'
    );

/* ------------------------------------------------------------
   CONFIGURACIÓN INICIAL DEL CHATBOT
   ------------------------------------------------------------ */

INSERT IGNORE INTO configuraciones_chatbot (
    clave,
    valor,
    tipo_dato,
    descripcion
)
VALUES
    (
        'mensaje_bienvenida',
        'Hola, puedo ayudarte con estilos, cuidados, artistas, cotizaciones y citas.',
        'texto',
        'Mensaje inicial mostrado al visitante'
    ),
    (
        'guardar_historial',
        'true',
        'booleano',
        'Indica si las conversaciones se conservan'
    ),
    (
        'maximo_mensajes_contexto',
        '20',
        'numero',
        'Cantidad máxima de mensajes previos enviados al modelo'
    );



/* Promociones automáticas de tienda y grupos de clientes */
-- Ampliación aditiva: no modifica promociones ni ventas existentes.
CREATE TABLE IF NOT EXISTS grupos_clientes (
    id_grupo BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN NOT NULL DEFAULT TRUE
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


-- Pedidos cubiertos completamente por una oferta: no crean un pago ficticio.
CREATE TABLE IF NOT EXISTS pedidos_sin_cobro (
    id_venta BIGINT UNSIGNED PRIMARY KEY,
    clave_idempotencia VARCHAR(128) NOT NULL UNIQUE,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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

/* Vinculación de tarjetas recibidas a cuentas de clientes. */
CREATE TABLE IF NOT EXISTS tarjetas_regalo_clientes (
 id_tarjeta BIGINT UNSIGNED PRIMARY KEY,
 id_cliente BIGINT UNSIGNED NOT NULL,
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (id_tarjeta) REFERENCES tarjetas_regalo(id_tarjeta),
 FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
