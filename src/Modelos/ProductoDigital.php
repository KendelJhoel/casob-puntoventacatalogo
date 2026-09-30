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
        private readonly string $enlaceDescarga,
        ?int $databaseId = null,
        ?string $imagen = null
    ) {
        parent::__construct($id, $nombre, $precioBase, $databaseId, $imagen);
        if (strlen($this->enlaceDescarga) > 2048 || !filter_var($this->enlaceDescarga, FILTER_VALIDATE_URL)
            || !in_array(parse_url($this->enlaceDescarga, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('El enlace de descarga debe ser una URL http o https.');
        }
    }

    public function getCamposPropios(): array
    {
        return ['stock' => null, 'enlace_descarga' => $this->enlaceDescarga, 'fecha_agenda' => null];
    }

    public function getTipo(): string
    {
        return 'digital';
    }

    public function getEtiquetaTipo(): string
    {
        return 'Productos Digitales';
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
        return round($this->precioBase * (1 - self::DESCUENTO), 2);
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
