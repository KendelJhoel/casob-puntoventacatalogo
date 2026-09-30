-- Caso B: una tabla para la jerarquía de ítems y dos para las ventas.
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS casob_puntoventa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE casob_puntoventa;

DROP TABLE IF EXISTS venta_detalles;
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS items;

CREATE TABLE items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(40) NOT NULL UNIQUE,
    tipo ENUM('fisico', 'digital', 'servicio') NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    precio_base DECIMAL(10,2) NOT NULL,
    stock INT NULL,
    enlace_descarga VARCHAR(2048) NULL,
    fecha_agenda DATETIME NULL,
    imagen VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_items_precio CHECK (precio_base > 0),
    CONSTRAINT chk_items_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
    CONSTRAINT chk_items_sku CHECK (CHAR_LENGTH(TRIM(sku)) > 0),
    CONSTRAINT chk_items_tipo CHECK (
        (tipo = 'fisico' AND stock IS NOT NULL AND stock >= 0 AND enlace_descarga IS NULL AND fecha_agenda IS NULL)
        OR (tipo = 'digital' AND stock IS NULL AND enlace_descarga IS NOT NULL AND enlace_descarga <> '' AND fecha_agenda IS NULL)
        OR (tipo = 'servicio' AND stock IS NULL AND enlace_descarga IS NULL AND fecha_agenda IS NOT NULL)
    )
);

CREATE TABLE ventas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente VARCHAR(150) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_ventas_total CHECK (total > 0),
    CONSTRAINT chk_ventas_cliente CHECK (CHAR_LENGTH(TRIM(cliente)) > 0)
);

CREATE TABLE venta_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venta_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NULL,
    sku VARCHAR(40) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    detalle TEXT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_detalles_venta FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_detalles_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    CONSTRAINT chk_detalles_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_detalles_precio CHECK (precio_unitario > 0),
    CONSTRAINT chk_detalles_subtotal CHECK (subtotal > 0)
);

CREATE INDEX idx_items_tipo ON items(tipo);
CREATE INDEX idx_ventas_fecha ON ventas(creada_en);
