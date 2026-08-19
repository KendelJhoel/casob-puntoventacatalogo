<?php

declare(strict_types=1);

namespace App\Modelos;

/**
 * Servicio agendable ofrecido por el comercio (consultoría, tutoría, etc.).
 *
 * - Sin stock: se agenda por fecha y hora.
 * - El precio final incluye una tarifa plana de agendamiento.
 */
class Servicio extends ItemVendible
{
    // Tarifa fija de agendamiento en la misma moneda que el precio base.
    private const TARIFA_AGENDAMIENTO = 5.00;

    public function __construct(
        string $id,
        string $nombre,
        float $precioBase,
        private string $fechaAgenda
    ) {
        parent::__construct($id, $nombre, $precioBase);
    }

    public function getFechaAgenda(): string
    {
        return $this->fechaAgenda;
    }

    /**
     * Precio final = precio base + tarifa fija de agendamiento ($5.00).
     * Polimorfismo: recargo por la gestión de agenda del servicio.
     */
    public function calcularPrecioFinal(): float
    {
        return $this->precioBase + self::TARIFA_AGENDAMIENTO;
    }

    public function obtenerDetalle(): string
    {
        return sprintf(
            "[Servicio] %s (ID: %s) — Base: $%.2f | +$%.2f agenda → Final: $%.2f | Fecha: %s",
            $this->nombre,
            $this->id,
            $this->precioBase,
            self::TARIFA_AGENDAMIENTO,
            $this->calcularPrecioFinal(),
            $this->fechaAgenda
        );
    }
}
