<div align="center">

# ✦ TINTA VIVA

### Arte que deja huella · Gestión de un estudio de tatuajes

**Catálogo · Membresías · Beneficios · Regalos · Experiencia del cliente**

`PHP 8.2` · `MySQL / MariaDB` · `PDO` · `HTML / CSS / JavaScript`

[Explorar módulos](#modulos-de-persona-2) · [Vista del cliente](#experiencia-del-cliente) · [Administración](#panel-de-administracion) · [Instalación](#ejecutar-el-proyecto)

</div>

---

Tinta Viva reúne la experiencia del cliente y la administración del estudio en una aplicación web. El cliente explora artículos, consulta sus beneficios y utiliza sus tarjetas de regalo; el administrador organiza el catálogo, los planes y los registros del negocio.

## Vista rápida del sistema

Tres pantallas destacadas para conocer el resultado visual del proyecto. **Haz clic en cada imagen para verla en tamaño completo.**

<table>
  <tr>
    <th align="center">Tienda del estudio</th>
    <th align="center">Membresía y beneficios</th>
    <th align="center">Calificaciones de artistas</th>
  </tr>
  <tr>
    <td valign="top" width="33%"><a href="docs/capturas/cliente/tienda.png"><img src="docs/capturas/cliente/tienda.png" alt="Tienda con catálogo ilustrado, promociones, filtros y tarjetas de regalo" width="300"></a></td>
    <td valign="top" width="33%"><a href="docs/capturas/cliente/membresia-beneficios.png"><img src="docs/capturas/cliente/membresia-beneficios.png" alt="Área del cliente con membresía Premium activa y beneficios del plan" width="300"></a></td>
    <td valign="top" width="33%"><a href="docs/capturas/administrador/calificaciones-artistas.png"><img src="docs/capturas/administrador/calificaciones-artistas.png" alt="Panel administrativo con indicadores, retratos y valoraciones de artistas" width="300"></a></td>
  </tr>
  <tr>
    <td>Catálogo de productos, búsqueda por categoría y ofertas en una misma vista.</td>
    <td>Estado del plan y beneficios organizados en tarjetas para el cliente.</td>
    <td>Resumen de reseñas, promedios y acceso al detalle de cada artista.</td>
  </tr>
</table>

[Ver todas las pantallas del sistema →](docs/GALERIA.md)

Este README presenta los **módulos de Persona 2** con las capturas proporcionadas. La identidad visual combina negro, vino y dorado, con una navegación diferenciada para cliente y administrador.

> **Referencia de las pantallas:** las capturas muestran distintos momentos del proyecto y datos de ejemplo. La rama `Cynthia` integra los avances de `main`, incluidas las calificaciones por artista y la renovación de membresías. Los importes, estados y cantidades corresponden al momento de cada captura.

<a id="contenido"></a>
## Contenido

1. [Módulos de Persona 2](#modulos-de-persona-2)
2. [Experiencia del cliente](#experiencia-del-cliente)
3. [Panel de administración](#panel-de-administracion)
4. [Flujos principales](#flujos-principales)
5. [Ejecutar el proyecto](#ejecutar-el-proyecto)
6. [Alcance y verificación](#alcance-y-verificacion)
7. [Documentación y galería](#documentacion-y-galeria)

<a id="modulos-de-persona-2"></a>
## Módulos de Persona 2

| # | Módulo | Experiencia del cliente | Administración |
| :--: | --- | --- | --- |
| 3 | 🏷️ Categorías y filtros | Buscar y filtrar artículos por categoría | Organizar categorías, productos y especialidades |
| 7 | ⭐ Calificaciones de artistas | Consultar la valoración pública de los artistas | Consultar promedios y gestionar la publicación de reseñas |
| 8 | 🎁 Ofertas y promociones | Consultar ofertas y descuentos aplicables a la tienda | Configurar vigencia, descuento y artículos o categorías elegibles |
| 9 | 💎 Membresías | Consultar planes, modalidad y membresía contratada | Configurar planes y gestionar membresías de clientes |
| 10 | 🎯 Beneficios | Revisar condiciones y beneficios incluidos en su plan | Seleccionar y configurar beneficios por membresía |
| 11 | 🎫 Tarjetas de regalo | Vincular un código, consultar saldo y usarlo en compras | Emitir tarjetas y consultar saldos y movimientos |
| 12 | 🛍️ Venta de artículos | Explorar el catálogo y realizar pedidos desde el carrito | Gestionar productos, imágenes, inventario y ventas |

Las cuentas de usuario sirven como apoyo para identificar al cliente y controlar el acceso a cada área.

<a id="experiencia-del-cliente"></a>
## Experiencia del cliente

### 🛍️ Tienda, categorías y promociones

El catálogo reúne imágenes de productos, precios, filtros y ofertas disponibles. La misma pantalla permite acceder al carrito y seleccionar una tarjeta de regalo con saldo.

**Recorrido:** elegir categoría → buscar artículo → añadir al carrito → revisar descuentos y saldo → confirmar el pedido.

![Tienda del cliente con categorías, promociones, productos y tarjetas disponibles](docs/capturas/cliente/tienda.png)

<details>
<summary><strong>Qué puede consultar el cliente</strong></summary>

- Artículos de la categoría seleccionada y sus precios.
- Ofertas disponibles y fechas de vigencia.
- Tarjetas de regalo disponibles para usar.
- Acceso al carrito para revisar cantidades y el total antes de comprar.

</details>

### 💎 Membresía y beneficios

La vista de membresía presenta el plan contratado, su período y los beneficios incluidos. Cada beneficio muestra su cantidad, porcentaje o condición para que el cliente pueda entender qué tiene disponible.

![Membresía del cliente con estado, período y beneficios del plan](docs/capturas/cliente/membresia-beneficios.png)

<details>
<summary><strong>Ver pantalla de confirmación de membresía</strong></summary>

La captura reúne el resumen del plan, la modalidad, el importe y el resultado de la tarjeta de demostración. También muestra la opción de renovación de membresía; los pagos bancarios continúan siendo de demostración.

![Confirmación de membresía y resumen de beneficios](docs/capturas/cliente/confirmar-membresia.png)

</details>

### 🎫 Mis tarjetas de regalo

El cliente puede añadir el código recibido y consultar el saldo, la emisión, el vencimiento y el estado de cada tarjeta. El botón **Usar en la tienda** conecta el regalo con la compra.

![Tarjetas del cliente con código, saldo, vencimiento y acceso a la tienda](docs/capturas/cliente/tarjetas-regalo.png)

[Volver al contenido ↑](#contenido)

<a id="panel-de-administracion"></a>
## Panel de administración

### ⭐ Calificaciones y detalle del artista

Las capturas de referencia muestran una vista por artista con retrato, estrellas, promedio y cantidad de reseñas. Al abrir el detalle, se consultan indicadores de publicación y la distribución de puntuaciones.

La rama `Cynthia` incluye esta vista de tarjetas por artista, sus indicadores y el detalle de reseñas.

![Referencia visual de las calificaciones organizadas por artista](docs/capturas/administrador/calificaciones-artistas.png)

<details>
<summary><strong>Ver detalle e indicadores del artista</strong></summary>

El detalle mostrado contiene promedio publicado, total de calificaciones, pendientes de revisión y distribución de una a cinco estrellas. La ventana tiene desplazamiento interno: la captura no muestra toda la sección de comentarios.

![Detalle del artista con promedio y distribución de estrellas](docs/capturas/administrador/calificaciones-detalle.png)

</details>

### 💎 Configuración de membresías y beneficios

El administrador selecciona un plan, ajusta nombre, estado, cuotas mensuales o anuales y los beneficios incluidos. Las cantidades y condiciones se organizan por plan.

<details>
<summary><strong>Ver planes y edición de beneficios</strong></summary>

![Configuración de planes de membresía y beneficios](docs/capturas/administrador/planes-membresia.png)

</details>

### 🎫 Emisión, saldos y movimientos de tarjetas

La administración reúne el listado de tarjetas, el destinatario, el importe inicial y el estado. Los saldos y movimientos permiten consultar el uso registrado del regalo.

<details>
<summary><strong>Ver tarjetas de regalo</strong></summary>

![Administración de tarjetas de regalo](docs/capturas/administrador/tarjetas-regalo.png)

</details>

<details>
<summary><strong>Ver saldos disponibles</strong></summary>

![Consulta administrativa de saldos de tarjetas](docs/capturas/administrador/saldos-tarjetas.png)

</details>

<details>
<summary><strong>Ver historial de movimientos</strong></summary>

![Historial de movimientos de tarjetas de regalo](docs/capturas/administrador/movimientos-tarjetas.png)

</details>

### 🛍️ Catálogo, categorías e inventario

Los artículos se organizan por categorías y se administran junto con sus imágenes, precios y existencias. Los filtros ayudan a encontrar productos para consultar o editar sus datos.

<details>
<summary><strong>Ver productos e inventario</strong></summary>

![Administración de productos e inventario](docs/capturas/administrador/productos.png)

</details>

<details>
<summary><strong>Ver categorías de artículos</strong></summary>

![Categorías de productos de la tienda](docs/capturas/administrador/categorias-productos.png)

</details>

<details>
<summary><strong>Ver productos filtrados por categoría</strong></summary>

![Productos filtrados por categoría en el panel administrativo](docs/capturas/administrador/productos-filtrados.png)

</details>

### 🧾 Ventas de la tienda

El listado permite consultar las ventas y sus estados. Los detalles de venta relacionan los artículos y cantidades con el pedido correspondiente.

<details>
<summary><strong>Ver administración de ventas</strong></summary>

![Ventas de la tienda en el panel administrativo](docs/capturas/administrador/ventas.png)

</details>

### 👤 Cuentas de usuarios

El directorio de cuentas organiza usuarios por rol y estado. La pantalla muestra indicadores y filtros para localizar una cuenta y consultar o editar sus datos.

<details>
<summary><strong>Ver directorio de cuentas</strong></summary>

![Gestión de cuentas con indicadores, búsqueda y filtros](docs/capturas/administrador/cuentas.png)

</details>

[Volver al contenido ↑](#contenido)

<a id="flujos-principales"></a>
## Flujos principales

| Proceso | Recorrido |
| --- | --- |
| Compra de artículos | Catálogo → carrito → revisión de descuentos y saldo → confirmación del pedido |
| Membresía | Consultar planes → elegir modalidad → confirmar compra → consultar estado y beneficios |
| Tarjeta de regalo | Emisión administrativa → entrega del código → vinculación al cliente → uso del saldo en tienda |
| Promoción | Configuración administrativa → comprobación de vigencia y condiciones → aplicación en la compra |
| Cotización y cita | Solicitud → propuesta del estudio → aceptación del cliente → programación de cita pendiente → confirmación |

**Una cotización aceptada no genera automáticamente una cita.** El administrador debe programar la sesión y confirmar el horario. Una cotización puede relacionarse con varias citas cuando el trabajo requiere varias sesiones.

<a id="ejecutar-el-proyecto"></a>
## Ejecutar el proyecto

**Requisitos:** PHP 8.2 con PDO MySQL y una base MySQL o MariaDB compatible. Las dependencias PHP están en `vendor/`; no se requiere Node para iniciar la aplicación.

1. Iniciar MySQL e importar [`database/db_preliminar.sql`](database/db_preliminar.sql) para una instalación nueva.
2. Revisar la conexión en [`config/database.php`](config/database.php) y configurar `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` cuando corresponda.
3. Iniciar la aplicación desde la raíz:

   ```powershell
   php -S 127.0.0.1:8000 -t public
   ```

4. Abrir [Tinta Viva en el navegador](http://127.0.0.1:8000).

Con XAMPP y este repositorio dentro de `htdocs`, también puedes abrir [la aplicación con Apache](http://localhost/TatuajesRepositorio/Tatuaje/public/).

Para crear un administrador, configurar SMTP o actualizar una base existente, consultar la [guía técnica](docs/GUIA-TECNICA.md). Las instrucciones y comandos de esa guía se ejecutan desde la raíz del repositorio.

<a id="alcance-y-verificacion"></a>
## Alcance y verificación

| Aspecto | Alcance |
| --- | --- |
| Pagos con tarjeta bancaria | Demostración; no realizan cobros bancarios reales |
| Efectivo y transferencia | Requieren verificación del estudio |
| Correos | Requieren configuración SMTP válida en el servidor |
| Regalos y membresías | Se validan según su estado, período, saldo y configuración |
| Capturas | Referencia visual; importes, fechas y cantidades reflejan el momento de la captura |
| Datos iniciales | Las capturas no sustituyen una importación de datos ni crean usuarios o ventas |

El acceso se controla por rol. La aplicación utiliza consultas preparadas, validación en servidor, protección CSRF y escape HTML.

Las pruebas disponibles se documentan en la [guía técnica](docs/GUIA-TECNICA.md#verificación). Este cambio de documentación no representa una nueva ejecución de todas las pruebas del sistema.

<a id="documentacion-y-galeria"></a>
## Documentación y galería

| Recurso | Contenido |
| --- | --- |
| [Guía técnica](docs/GUIA-TECNICA.md) | Documentación anterior conservada: instalación, estructura, migraciones y pruebas |
| [Galería completa](docs/GALERIA.md) | Las 18 capturas proporcionadas, organizadas por rol |
| [Esquema de base de datos](database/db_preliminar.sql) | Tablas, relaciones y reglas del sistema |
| [Módulos administrativos](config/modules.php) | Catálogo de módulos de administración |

---

<div align="center">

**TINTA VIVA** · Arte, identidad y gestión en un mismo lugar.

</div>
