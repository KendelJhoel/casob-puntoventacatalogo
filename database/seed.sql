SET NAMES utf8mb4;
USE casob_puntoventa;

INSERT INTO items (sku, tipo, nombre, precio_base, stock, enlace_descarga, fecha_agenda) VALUES
('SKU-001', 'fisico', 'Auriculares Bluetooth', 50.00, 5, NULL, NULL),
('SKU-002', 'fisico', 'Teclado Mecánico RGB', 120.00, 3, NULL, NULL),
('SKU-003', 'fisico', 'Mouse Ergonómico', 35.00, 8, NULL, NULL),
('DIG-001', 'digital', 'Ebook: Clean Code en PHP', 20.00, NULL, 'https://downloads.tienda.com/clean-code.pdf', NULL),
('DIG-002', 'digital', 'Curso PHP Avanzado', 150.00, NULL, 'https://downloads.tienda.com/curso-php.zip', NULL),
('DIG-003', 'digital', 'Plantilla de inventario', 15.00, NULL, 'https://downloads.tienda.com/plantilla.zip', NULL),
('SRV-001', 'servicio', 'Consultoría Técnica (1h)', 80.00, NULL, NULL, '2026-10-02 10:00:00'),
('SRV-002', 'servicio', 'Instalación y Configuración', 60.00, NULL, NULL, '2026-10-03 14:00:00'),
('SRV-003', 'servicio', 'Capacitación en PHP', 95.00, NULL, NULL, '2026-10-05 09:00:00');
