INSERT INTO categorias_productos (nombre, descripcion, activo)
SELECT datos.nombre, datos.descripcion, 1
FROM (
    SELECT 'Ropa del estudio' AS nombre, 'Camisetas y prendas con diseños del estudio.' AS descripcion
    UNION ALL SELECT 'Accesorios', 'Gorras, bolsos, llaveros y accesorios del estudio.'
    UNION ALL SELECT 'Cuidado del tatuaje', 'Artículos para el cuidado de tatuajes. Seguir las indicaciones del fabricante y del artista.'
    UNION ALL SELECT 'Láminas y arte', 'Ilustraciones, láminas y diseños artísticos impresos.'
    UNION ALL SELECT 'Pegatinas y pines', 'Pegatinas y pines con diseños originales.'
    UNION ALL SELECT 'Kits y regalos', 'Conjuntos de artículos y regalos de la tienda.'
) AS datos
WHERE NOT EXISTS (SELECT 1 FROM categorias_productos existente WHERE existente.nombre = datos.nombre);

INSERT INTO productos (id_categoria_producto, sku, nombre, descripcion, precio, stock_actual, stock_minimo, activo)
SELECT categoria.id_categoria_producto, datos.sku, datos.nombre, datos.descripcion, datos.precio, datos.stock, datos.minimo, 1
FROM (
    SELECT 'Ropa del estudio' AS categoria, 'EST-ROP-001' AS sku, 'Camiseta Blackwork' AS nombre, 'Camiseta negra con ilustración original del estudio. Talla M.' AS descripcion, 12000.00 AS precio, 18 AS stock, 4 AS minimo
    UNION ALL SELECT 'Ropa del estudio', 'EST-ROP-002', 'Camiseta Traditional', 'Camiseta con diseño inspirado en tatuajes tradicionales. Talla M.', 12000.00, 15, 4
    UNION ALL SELECT 'Ropa del estudio', 'EST-ROP-003', 'Camiseta Logo del estudio', 'Camiseta de algodón con el logo del estudio. Talla L.', 11000.00, 20, 5
    UNION ALL SELECT 'Ropa del estudio', 'EST-ROP-004', 'Sudadera del estudio', 'Sudadera negra con diseño del estudio. Talla L.', 25000.00, 8, 2
    UNION ALL SELECT 'Accesorios', 'EST-ACC-001', 'Gorra del estudio', 'Gorra negra ajustable con bordado del estudio.', 10000.00, 12, 3
    UNION ALL SELECT 'Accesorios', 'EST-ACC-002', 'Bolso artístico', 'Bolso de tela reutilizable con ilustración artística.', 9500.00, 10, 3
    UNION ALL SELECT 'Accesorios', 'EST-ACC-003', 'Llavero del estudio', 'Llavero con diseño del estudio.', 2500.00, 30, 6
    UNION ALL SELECT 'Accesorios', 'EST-ACC-004', 'Taza Tattoo', 'Taza de cerámica con ilustración del estudio.', 6500.00, 14, 3
    UNION ALL SELECT 'Cuidado del tatuaje', 'EST-CUI-001', 'Crema para tatuajes', 'Crema de cuidado. Utilizar según las indicaciones del fabricante.', 8500.00, 16, 4
    UNION ALL SELECT 'Cuidado del tatuaje', 'EST-CUI-002', 'Jabón suave sin perfume', 'Jabón suave sin perfume. Consultar su uso con el artista.', 5500.00, 18, 4
    UNION ALL SELECT 'Cuidado del tatuaje', 'EST-CUI-003', 'Loción hidratante sin perfume', 'Loción hidratante sin perfume para cuidado de la piel.', 7500.00, 12, 3
    UNION ALL SELECT 'Cuidado del tatuaje', 'EST-CUI-004', 'Protector solar para piel tatuada', 'Protector solar para piel con tatuajes ya cicatrizados. Seguir las indicaciones del fabricante.', 11000.00, 10, 3
    UNION ALL SELECT 'Láminas y arte', 'EST-ART-001', 'Lámina Blackwork', 'Ilustración Blackwork impresa en formato A4.', 6000.00, 12, 2
    UNION ALL SELECT 'Láminas y arte', 'EST-ART-002', 'Lámina Traditional', 'Ilustración de estilo tradicional impresa en formato A4.', 6000.00, 12, 2
    UNION ALL SELECT 'Láminas y arte', 'EST-ART-003', 'Lámina Floral', 'Ilustración floral original impresa en formato A4.', 6500.00, 10, 2
    UNION ALL SELECT 'Láminas y arte', 'EST-ART-004', 'Postal artística', 'Postal con ilustración original del estudio.', 1500.00, 30, 5
    UNION ALL SELECT 'Pegatinas y pines', 'EST-PEG-001', 'Pack de pegatinas Blackwork', 'Conjunto de cinco pegatinas con diseños Blackwork.', 3000.00, 25, 5
    UNION ALL SELECT 'Pegatinas y pines', 'EST-PEG-002', 'Pack de pegatinas Traditional', 'Conjunto de cinco pegatinas con diseños tradicionales.', 3000.00, 25, 5
    UNION ALL SELECT 'Pegatinas y pines', 'EST-PEG-003', 'Pin del estudio', 'Pin metálico con diseño del estudio.', 3500.00, 20, 4
    UNION ALL SELECT 'Pegatinas y pines', 'EST-PEG-004', 'Pin artístico', 'Pin metálico con ilustración artística.', 3500.00, 20, 4
    UNION ALL SELECT 'Kits y regalos', 'EST-KIT-001', 'Kit de cuidado', 'Conjunto de crema y jabón suave. Seguir las indicaciones de cada producto.', 15000.00, 8, 2
    UNION ALL SELECT 'Kits y regalos', 'EST-KIT-002', 'Kit de accesorios', 'Conjunto de gorra, llavero y pegatinas del estudio.', 14000.00, 6, 2
    UNION ALL SELECT 'Kits y regalos', 'EST-KIT-003', 'Kit de arte', 'Conjunto de dos láminas A4 y una postal artística.', 12000.00, 6, 2
    UNION ALL SELECT 'Kits y regalos', 'EST-KIT-004', 'Pack regalo del estudio', 'Conjunto de taza, pin y pegatinas del estudio.', 11000.00, 8, 2
) AS datos
JOIN categorias_productos categoria ON categoria.nombre = datos.categoria
WHERE NOT EXISTS (SELECT 1 FROM productos existente WHERE existente.sku = datos.sku);
