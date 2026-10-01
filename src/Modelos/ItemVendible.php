<?php

declare(strict_types=1);

namespace App\Modelos;

use App\Contratos\Facturable;

/**
 * [ABSTRACCION] Clase base abstracta para todos los ítems que pueden venderse.
 * Garantiza un contrato común (Facturable) y encapsula los atributos
 * esenciales de cualquier ítem del catálogo.
 */
abstract class ItemVendible implements Facturable
{
    /**
     * [ENCAPSULAMIENTO] Las propiedades son protected/readonly para que
     * el estado interno no pueda modificarse desde fuera de la jerarquía
     * de clases. Los invariantes (SKU, nombre, precio) se validan aquí
     * y no pueden quedar en estado inválido.
     */
    public function __construct(
        protected readonly string $id,
        protected string $nombre,
        protected float $precioBase,
        protected readonly ?int $databaseId = null,
        protected readonly ?string $imagen = null
    ) {
        if (!preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $this->id)) {
            throw new \InvalidArgumentException('El SKU no es válido.');
        }
        if (trim($this->nombre) === '' || mb_strlen($this->nombre) > 150) {
            throw new \InvalidArgumentException('El nombre debe tener entre 1 y 150 caracteres.');
        }
        if (!is_finite($this->precioBase) || $this->precioBase <= 0
            || $this->precioBase > 99999999.99 || round($this->precioBase, 2) !== $this->precioBase) {
            throw new \InvalidArgumentException(
                "El precio base de '{$this->nombre}' debe ser positivo y tener máximo dos decimales."
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

    public function getDatabaseId(): ?int
    {
        return $this->databaseId;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function reservar(int $cantidad): void
    {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }
    }

    public function getInfoCatalogo(): string
    {
        return '';
    }

    public function requiereCantidad(): bool
    {
        return false;
    }

    /** @return array{stock:?int,enlace_descarga:?string,fecha_agenda:?string} */
    abstract public function getCamposPropios(): array;

    abstract public function getTipo(): string;

    abstract public function getEtiquetaTipo(): string;

    /**
     * Cada subclase define su propio cálculo de precio final (polimorfismo).
     */
    abstract public function calcularPrecioFinal(): float;

    /**
     * Cada subclase provee su propio detalle para el ticket (polimorfismo).
     */
    abstract public function obtenerDetalle(): string;
}
