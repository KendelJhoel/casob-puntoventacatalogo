<?php

declare(strict_types=1);

namespace App\Modelos;

use InvalidArgumentException;

/** Único lugar que decide qué subclase representa una fila o un formulario. [FABRICA] */
final class ItemFactory
{
    /** @param array<string,mixed> $datos */
    public static function crear(array $datos): ItemVendible
    {
        $sku = (string) ($datos['sku'] ?? '');
        $nombre = (string) ($datos['nombre'] ?? '');
        $precio = (float) ($datos['precio_base'] ?? 0);
        $id = isset($datos['id']) ? (int) $datos['id'] : null;
        $imagen = $datos['imagen'] ?? null;

        $fecha = str_replace('T', ' ', (string) ($datos['fecha_agenda'] ?? ''));
        if (strlen($fecha) === 16) {
            $fecha .= ':00';
        }

        return match ($datos['tipo'] ?? null) {
            'fisico' => new ProductoFisico($sku, $nombre, $precio, (int) ($datos['stock'] ?? 0), $id, $imagen),
            'digital' => new ProductoDigital($sku, $nombre, $precio, (string) ($datos['enlace_descarga'] ?? ''), $id, $imagen),
            'servicio' => new Servicio($sku, $nombre, $precio, $fecha, $id, $imagen),
            default => throw new InvalidArgumentException('Tipo de ítem desconocido.'),
        };
    }

    /** @return array{campo:string,etiqueta:string,tipo:string,restricciones:string} */
    public static function campoPropio(string $tipo): array
    {
        return match ($tipo) {
            'fisico' => ['campo' => 'stock', 'etiqueta' => 'Existencias', 'tipo' => 'number', 'restricciones' => 'min="0" step="1"'],
            'digital' => ['campo' => 'enlace_descarga', 'etiqueta' => 'Enlace de descarga', 'tipo' => 'url', 'restricciones' => 'maxlength="2048"'],
            'servicio' => ['campo' => 'fecha_agenda', 'etiqueta' => 'Fecha y hora', 'tipo' => 'datetime-local', 'restricciones' => ''],
            default => throw new InvalidArgumentException('Tipo de ítem desconocido.'),
        };
    }
}
