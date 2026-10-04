/*
   TINTA VIVA - USUARIOS LOCALES DE DEMOSTRACION

   Ejecutar despues de database/db_preliminar.sql.
   Estas cuentas son solo para desarrollo local y deben eliminarse o
   cambiarse antes de publicar el sistema.

   Contrasena de las cuatro cuentas: PruebaTinta2026!
*/

USE estudio_tatuajes;

START TRANSACTION;

SET @clave_demo = '$2y$10$2k2wzhojF1N332o8e4H2Ye7w5FlbU7I1yoS1hRnDYt90ksD4dEX4S';

INSERT INTO cuentas (
    id_rol,
    usuario,
    correo,
    contrasena_hash,
    estado,
    correo_verificado
)
SELECT
    r.id_rol,
    'admin_demo',
    'admin.demo@tintaviva.local',
    @clave_demo,
    'activo',
    TRUE
FROM roles r
WHERE r.nombre_rol = 'administrador'
  AND NOT EXISTS (
      SELECT 1
      FROM cuentas c
      WHERE c.usuario = 'admin_demo'
         OR c.correo = 'admin.demo@tintaviva.local'
  );

INSERT INTO cuentas (
    id_rol,
    usuario,
    correo,
    contrasena_hash,
    estado,
    correo_verificado
)
SELECT
    r.id_rol,
    'secretaria_demo',
    'secretaria.demo@tintaviva.local',
    @clave_demo,
    'activo',
    TRUE
FROM roles r
WHERE r.nombre_rol = 'secretaria'
  AND NOT EXISTS (
      SELECT 1
      FROM cuentas c
      WHERE c.usuario = 'secretaria_demo'
         OR c.correo = 'secretaria.demo@tintaviva.local'
  );

INSERT INTO cuentas (
    id_rol,
    usuario,
    correo,
    contrasena_hash,
    estado,
    correo_verificado
)
SELECT
    r.id_rol,
    'artista_demo',
    'artista.demo@tintaviva.local',
    @clave_demo,
    'activo',
    TRUE
FROM roles r
WHERE r.nombre_rol = 'artista'
  AND NOT EXISTS (
      SELECT 1
      FROM cuentas c
      WHERE c.usuario = 'artista_demo'
         OR c.correo = 'artista.demo@tintaviva.local'
  );

INSERT INTO cuentas (
    id_rol,
    usuario,
    correo,
    contrasena_hash,
    estado,
    correo_verificado
)
SELECT
    r.id_rol,
    'cliente_demo',
    'cliente.demo@tintaviva.local',
    @clave_demo,
    'activo',
    TRUE
FROM roles r
WHERE r.nombre_rol = 'cliente'
  AND NOT EXISTS (
      SELECT 1
      FROM cuentas c
      WHERE c.usuario = 'cliente_demo'
         OR c.correo = 'cliente.demo@tintaviva.local'
  );

INSERT INTO artistas (
    id_cuenta,
    nombre_artistico,
    nombre,
    apellidos,
    telefono,
    biografia,
    activo
)
SELECT
    c.id_cuenta,
    'Ink Demo',
    'Valeria',
    'Artista',
    '8888-0001',
    'Artista de prueba para el desarrollo local de Tinta Viva.',
    TRUE
FROM cuentas c
WHERE c.usuario = 'artista_demo'
  AND NOT EXISTS (
      SELECT 1
      FROM artistas a
      WHERE a.id_cuenta = c.id_cuenta
  );

INSERT INTO clientes (
    id_cuenta,
    nombre,
    apellidos,
    telefono
)
SELECT
    c.id_cuenta,
    'Carlos',
    'Cliente',
    '8888-0002'
FROM cuentas c
WHERE c.usuario = 'cliente_demo'
  AND NOT EXISTS (
      SELECT 1
      FROM clientes cl
      WHERE cl.id_cuenta = c.id_cuenta
  );

COMMIT;

SELECT
    c.usuario,
    c.correo,
    r.nombre_rol AS rol,
    c.estado
FROM cuentas c
INNER JOIN roles r
    ON r.id_rol = c.id_rol
WHERE c.usuario IN (
    'admin_demo',
    'secretaria_demo',
    'artista_demo',
    'cliente_demo'
)
ORDER BY c.id_cuenta;
