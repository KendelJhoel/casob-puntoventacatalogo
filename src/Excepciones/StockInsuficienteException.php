<?php

declare(strict_types=1);

namespace App\Excepciones;

use RuntimeException;

/**
 * Excepción lanzada cuando se intenta vender un ProductoFisico
 * sin stock disponible. Encapsula la regla de negocio de inventario.
 */
class StockInsuficienteException extends RuntimeException
{
    public function __construct(string $nombreProducto, int $stockDisponible)
    {
        $mensaje = sprintf(
            "Stock insuficiente para '%s'. Stock disponible: %d unidad(es).",
            $nombreProducto,
            $stockDisponible
        );
        parent::__construct($mensaje);
    }
}
