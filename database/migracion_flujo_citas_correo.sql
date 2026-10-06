/*
   TINTA VIVA - MIGRACIÓN DEL FLUJO COTIZACIÓN -> CITA -> CORREO

   Se puede ejecutar varias veces. Añade una clave de evento a la cola de
   correos para impedir que la confirmación de una misma cita se envíe dos veces.
*/

USE estudio_tatuajes;

SET @existe_clave_evento = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'correos_salida'
      AND column_name = 'clave_evento'
);

SET @sql_clave_evento = IF(
    @existe_clave_evento = 0,
    'ALTER TABLE correos_salida ADD COLUMN clave_evento VARCHAR(190) NULL AFTER id_plantilla',
    'SELECT 1'
);

PREPARE stmt_clave_evento FROM @sql_clave_evento;
EXECUTE stmt_clave_evento;
DEALLOCATE PREPARE stmt_clave_evento;

SET @existe_indice_evento = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'correos_salida'
      AND index_name = 'uq_correos_clave_evento'
);

SET @sql_indice_evento = IF(
    @existe_indice_evento = 0,
    'ALTER TABLE correos_salida ADD UNIQUE KEY uq_correos_clave_evento (clave_evento)',
    'SELECT 1'
);

PREPARE stmt_indice_evento FROM @sql_indice_evento;
EXECUTE stmt_indice_evento;
DEALLOCATE PREPARE stmt_indice_evento;
