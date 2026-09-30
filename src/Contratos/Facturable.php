<?php

declare(strict_types=1);

namespace App\Contratos;

/**
 * [INTERFAZ] Contrato que deben cumplir todos los ítems vendibles.
 * Define el método polimórfico de precio final y el detalle para el ticket.
 */
interface Facturable
{
    /** Comprueba la cantidad y reserva existencias si corresponde. */
    public function reservar(int $cantidad): void;

    /**
     * Calcula el precio final del ítem, aplicando impuestos,
     * descuentos o recargos según el tipo de ítem.
     */
    public function calcularPrecioFinal(): float;

    /**
     * Retorna una representación legible del ítem para mostrarse en el ticket.
     */
    public function obtenerDetalle(): string;
}
