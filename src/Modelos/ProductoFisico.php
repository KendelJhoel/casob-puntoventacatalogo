<?php

declare(strict_types=1);

namespace App\Modelos;

use App\Excepciones\StockInsuficienteException;

/**
 * [HERENCIA] Producto con existencia física en bodega.
 * 
 * - El stock está encapsulado: solo se puede reducir a través de `reducirStock()`,
 *   que lanza una excepción si no hay unidades disponibles.
 * - El precio final incluye un impuesto de envío del 15%.
 */
class ProductoFisico extends ItemVendible
{
    // Tasa de impuesto de envío aplicada a los productos físicos.
    private const TASA_IMPUESTO_ENVIO = 0.15;

    public function __construct(
        string $id,
        string $nombre,
        float $precioBase,
        private int $stock,
        ?int $databaseId = null,
        ?string $imagen = null
    ) {
        parent::__construct($id, $nombre, $precioBase, $databaseId, $imagen);

        if ($this->stock < 0) {
            throw new \InvalidArgumentException(
                "El stock inicial de '{$nombre}' no puede ser negativo."
            );
        }
    }

    /**
     * Consulta el stock actual sin permitir su modificación directa.
     */
    public function getStock(): int
    {
        return $this->stock;
    }

    public function reservar(int $cantidad): void
    {
        $this->reducirStock($cantidad);
    }

    public function getInfoCatalogo(): string
    {
        return " (stock: {$this->stock})";
    }

    public function requiereCantidad(): bool
    {
        return true;
    }

    public function getCamposPropios(): array
    {
        return ['stock' => $this->stock, 'enlace_descarga' => null, 'fecha_agenda' => null];
    }

    public function getTipo(): string
    {
        return 'fisico';
    }

    public function getEtiquetaTipo(): string
    {
        return 'Productos Físicos';
    }

    /**
     * Único punto de acceso para reducir el stock.
     * Valida que haya suficientes unidades antes de proceder.
     *
     * @throws StockInsuficienteException si el stock es insuficiente.
     */
    public function reducirStock(int $cantidad): void
    {
        parent::reservar($cantidad);
        if ($cantidad > $this->stock) {
            throw new StockInsuficienteException($this->nombre, $this->stock);
        }
        $this->stock -= $cantidad;
    }

    /**
     * [POLIMORFISMO] Precio final = precio base + impuesto de envío (15%).
     * Polimorfismo: cada tipo de ítem calcula el precio de forma diferente.
     */
    public function calcularPrecioFinal(): float
    {
        return $this->precioBase * (1 + self::TASA_IMPUESTO_ENVIO);
    }

    public function obtenerDetalle(): string
    {
        return sprintf(
            "[Físico] %s (SKU: %s) — Base: $%.2f | +15%% envío → Final: $%.2f",
            $this->nombre,
            $this->id,
            $this->precioBase,
            $this->calcularPrecioFinal()
        );
    }
}
