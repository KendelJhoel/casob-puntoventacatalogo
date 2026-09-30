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
        private string $fechaAgenda,
        ?int $databaseId = null,
        ?string $imagen = null
    ) {
        parent::__construct($id, $nombre, $precioBase, $databaseId, $imagen);
        if (!\App\Servicios\Validador::fechaValida($this->fechaAgenda)) {
            throw new \InvalidArgumentException('La fecha del servicio no es válida.');
        }
    }

    public function getCamposPropios(): array
    {
        return ['stock' => null, 'enlace_descarga' => null, 'fecha_agenda' => $this->fechaAgenda];
    }

    public function getTipo(): string
    {
        return 'servicio';
    }

    public function getEtiquetaTipo(): string
    {
        return 'Servicios';
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
        return round($this->precioBase + self::TARIFA_AGENDAMIENTO, 2);
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
