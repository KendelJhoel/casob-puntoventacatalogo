<?php

declare(strict_types=1);

namespace App\Modelos;

/**
 * Producto digital (software, ebook, música, etc.).
 *
 * - Sin stock físico: se puede vender un número ilimitado de veces.
 * - Incluye un enlace de descarga único.
 * - El precio final aplica un descuento del 10% (incentivo digital).
 */
class ProductoDigital extends ItemVendible
{
    // Porcentaje de descuento aplicado a los productos digitales.
    private const DESCUENTO = 0.10;

    public function __construct(
        string $id,
        string $nombre,
        float $precioBase,
        private readonly string $enlaceDescarga
    ) {
        parent::__construct($id, $nombre, $precioBase);
    }

    public function getEnlaceDescarga(): string
    {
        return $this->enlaceDescarga;
    }

    /**
     * Precio final = precio base - descuento del 10%.
     * Polimorfismo: descuento como incentivo para compras digitales.
     */
    public function calcularPrecioFinal(): float
    {
        return $this->precioBase * (1 - self::DESCUENTO);
    }

    public function obtenerDetalle(): string
    {
        return sprintf(
            "[Digital] %s (SKU: %s) — Base: $%.2f | -10%% descuento → Final: $%.2f | Descarga: %s",
            $this->nombre,
            $this->id,
            $this->precioBase,
            $this->calcularPrecioFinal(),
            $this->enlaceDescarga
        );
    }
}
