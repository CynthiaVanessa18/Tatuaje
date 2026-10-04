# Pagos de la tienda

El esquema existente de `db_preliminar.sql` ya incluye las tablas y columnas necesarias. No se requiere una migración ni datos iniciales de pagos. `insert_tienda.sql` sigue conteniendo solamente INSERT de categorías y productos.

- La tarjeta de demostración se registra en `pagos.metodo = 'pasarela'`, con `proveedor = 'demo_tienda'` y una referencia de prueba única.
- Prueba aprobada: pago aprobado, venta completada y descuento de inventario.
- Prueba rechazada: pago rechazado, venta cancelada, sin descontar inventario. El carrito se conserva para reintentar.
- Efectivo y transferencia: pago pendiente y venta confirmada, con inventario reservado para retirar en el estudio. No se marcan como pagados automáticamente.
- La clave de idempotencia evita duplicar pedidos en un reintento.
- No se solicitan ni almacenan números de tarjetas, fechas de vencimiento o códigos de seguridad. Las tarjetas son opciones de simulación.

No ejecutar INSERT ficticios en ventas, detalles o pagos: esos registros se crean al confirmar una compra. Los cobros reales requieren otra integración y las ventas de demostración deben mantenerse fuera de la contabilidad real.
