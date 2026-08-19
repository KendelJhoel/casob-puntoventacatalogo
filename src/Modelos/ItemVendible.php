<?php

declare(strict_types=1);

namespace App\Modelos;

use App\Contratos\Facturable;

/**
 * Clase base abstracta para todos los ítems que pueden venderse.
 * Garantiza un contrato común (Facturable) y encapsula los atributos
 * esenciales de cualquier ítem del catálogo.
 */
abstract class ItemVendible implements Facturable
{
    /**
     * El ID/SKU del ítem es inmutable tras su creación (readonly).
     * Esto protege la identidad del producto en el sistema.
     */
    public function __construct(
        protected readonly string $id,
        protected string $nombre,
        protected float $precioBase
    ) {
        if ($this->precioBase < 0) {
            throw new \InvalidArgumentException(
                "El precio base de '{$this->nombre}' no puede ser negativo."
            );
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getPrecioBase(): float
    {
        return $this->precioBase;
    }

    /**
     * Cada subclase define su propio cálculo de precio final (polimorfismo).
     */
    abstract public function calcularPrecioFinal(): float;

    /**
     * Cada subclase provee su propio detalle para el ticket (polimorfismo).
     */
    abstract public function obtenerDetalle(): string;
}
