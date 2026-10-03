# Tinta Viva · Estudio de tatuajes

Aplicación web en PHP 8.2 y PDO para la gestión y la experiencia pública de un estudio de tatuajes. El esquema principal está en `database/db_preliminar.sql`. No requiere Node y las dependencias PHP necesarias están incluidas en `vendor/`.

## Instalación

1. Inicia MySQL 8.0.16+ o MariaDB 10.4+. XAMPP es opcional; también puedes usar PHP y MySQL instalados de forma independiente.
2. Importa `database/db_preliminar.sql` en phpMyAdmin o MySQL Workbench para una instalación nueva. Crea la base **estudio_tatuajes** con sus tablas, relaciones, vistas, disparadores y datos iniciales. La copia usa `utf8mb4_unicode_ci` y es compatible con MySQL 8.3 y MariaDB/XAMPP 10.4.
3. `config/database.php` conecta por defecto a `127.0.0.1:3306`, base `estudio_tatuajes` y usuario `root`. Configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` como variables de entorno del servidor cuando tus datos sean distintos. No se carga `.env` automáticamente.
   Para habilitar la recuperación por correo, configura también `SMTP_USER` y `SMTP_PASSWORD`; nunca guardes esas credenciales en el repositorio.
4. Para pruebas locales, importa `database/usuarios_demo.sql`. También puedes crear únicamente un administrador desde PowerShell:

   ```powershell
   $env:ADMIN_PASSWORD = 'elige-una-clave-larga'
   php scripts/create_admin.php administrador administrador@ejemplo.com
   Remove-Item Env:ADMIN_PASSWORD
   ```

5. Ejecuta `php -S 127.0.0.1:8000 -t public` desde la raíz y abre http://127.0.0.1:8000. Con Apache usa `public/` como DocumentRoot. En htdocs puedes entrar a `Tatuaje/public/`.

El esquema no incluye clientes, artistas ni citas de prueba. Esos registros pertenecen a otros integrantes y deben existir para seleccionarlos en calificaciones, ventas y membresías.

## Carpetas

| Carpeta | Responsabilidad |
| --- | --- |
| `config/` | Conexión y módulos autorizados |
| `app/Controllers/` | Solicitudes, filtros, mensajes |
| `app/Models/` | Consultas preparadas y metadatos del esquema |
| `app/Services/` | Validaciones y transacciones |
| `app/Views/` | Plantillas del panel |
| `public/` | Entradas HTTP: index, login, logout |
| `public/assets/css/` | Estilos |
| `public/assets/js/` | Confirmaciones y prevención de doble envío |
| `database/` | Esquema importable |
| `scripts/` | Alta de administrador por consola |
| `tests/` | Pruebas con datos ficticios |

Las rutas anteriores de `view/` y `controller/` son puentes al panel nuevo. Se conservan las imágenes originales. Los formularios se construyen con las columnas reales de una lista cerrada de tablas para evitar duplicar enumeraciones y campos.

## Alcance

| Requisito | Módulos |
| --- | --- |
| 3 · Categorías / filtros | Categorías, especialidades y filtros por estado, actividad y relaciones |
| 7 · Calificaciones | Citas finalizadas, puntuación 1–5, una calificación por cita y moderación |
| 8 · Ofertas | Promociones por vigencia, porcentaje/monto, mínimo y ámbito |
| 9 · Membresía | Planes y membresías vinculadas al detalle vendido |
| 10 · Beneficios | Beneficios y asignación de varios beneficios por plan |
| 11 · Tarjetas | Emisión, consulta, edición permitida, cancelación e historial |
| 12 · Artículos | Categorías, inventario, imágenes, ventas y detalles |

Los módulos editables incluyen crear, listar con paginación, consultar detalle, editar y eliminar por POST. Las claves foráneas impiden eliminar registros utilizados; se puede desactivar cuando exista el campo activo.

### Ventas

Crea una venta **pendiente**, agrega detalles y cámbiala a **confirmada** para descontar inventario. Si falta stock se revierte toda la confirmación. Los precios de productos y planes se toman del catálogo al guardar el detalle; el descuento de una promoción se calcula por línea y su mínimo se evalúa por línea. Sin promoción se admite descuento administrativo. El impuesto se registra como monto. Los totales se calculan desde los detalles.

La venta confirmada queda bloqueada contra edición/eliminación y nuevos detalles, evitando doble descuento de stock. Solo se permite cancelar desde el CRUD una venta pendiente sin pagos. Cobros, reembolsos, pasarelas, cancelación posterior a la confirmación y comprobantes fiscales están fuera de este panel.

### Membresías y tarjetas

Crea primero una venta confirmada con un detalle del tipo correspondiente. Después selecciona ese detalle al registrar la membresía o tarjeta; se verifica cliente y plan o importe/moneda. La activación es administrativa: verifica el cobro antes de activarla. Las fechas se introducen y almacenan en UTC. En membresías registra el período contratado según la duración del plan.

Cada tarjeta genera un código mostrado **una sola vez** y almacena únicamente su hash SHA-256. Su carga inicial se registra en la misma transacción. El historial de movimientos es de consulta y el saldo está disponible en `vista_saldo_tarjetas`. No incluye canje, devolución de saldo ni envío de correos. Los estados agotada y vencida se reservan para esa futura integración.

Las membresías y tarjetas emitidas se cancelan mediante estado para conservar historial; no se eliminan ni se cambian sus referencias de compra. Una tarjeta cancelada no puede reactivarse.

## Verificación

Ingreso con contraseña cifrada por hash, comprobación del rol administrador en cada solicitud, CSRF, escape HTML, consultas preparadas y validación en servidor. Los errores SQL se registran en el servidor; el navegador recibe mensajes comprensibles.

Sintaxis PHP: `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`.

Integración: importa una copia del esquema sustituyendo el nombre de base por `tattoo_crud_test`, configura `DB_NAME=tattoo_crud_test` y ejecuta `php tests/integration.php`. La prueba exige una base de prueba vacía de datos operativos y conserva los datos ficticios para inspección. Nunca la ejecutes contra la base del estudio.

Prueba HTTP: con el servidor apuntando a esa base desechable, crea un administrador de prueba, define `TEST_ADMIN_USER` y `TEST_ADMIN_PASSWORD` y ejecuta `tests/http.ps1 -BaseUrl http://127.0.0.1:8018`. Comprueba ingreso, todos los listados y formularios, protección CSRF, alta/edición/eliminación, escape HTML y cierre de sesión.

## Inicio de sesión por rol

El mismo `public/login.php` admite cuentas activas de los cuatro roles. No se selecciona el rol en el formulario: se obtiene de la cuenta y se verifica nuevamente al abrir cada página.

| Rol | Destino | Acceso actual |
| --- | --- | --- |
| administrador | `public/index.php` | CRUD de Persona 2 |
| secretaria | `public/secretaria.php` | Consulta de próximas citas del estudio |
| artista | `public/artista.php` | Consulta de sus propias próximas citas |
| cliente | `public/cliente.php` | Consulta de sus propias próximas citas |

Las páginas de artista y cliente necesitan un registro en `artistas` o `clientes` vinculado mediante `id_cuenta`. Si todavía no existe, la sesión funciona y se muestra un aviso para completar el perfil. Estas páginas no implementan edición de agenda ni otros CRUD de los demás integrantes.

Las rutas se centralizan en `app/auth.php`, función `roleRoutes()`. Cada entrada usa `requireRole()`; el panel administrativo conserva `requireAdmin()`. Un acceso a una página de otro rol redirige al inicio del usuario. Las cuentas bloqueadas/inactivas y los roles desactivados pierden acceso incluso con una sesión abierta. `tests/roles-http.ps1` comprueba las redirecciones con cuentas ficticias en un servidor de pruebas.
