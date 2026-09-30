<?php

declare(strict_types=1);

namespace App\Servicios;

use DateTimeImmutable;

/** Validación del servidor, independiente de los atributos HTML5. [VALIDACION] */
final class Validador
{
    /** @param array<string,mixed> $datos @return array<string,string> */
    public static function item(array $datos): array
    {
        $errores = [];
        $sku = self::texto($datos, 'sku');
        $nombre = self::texto($datos, 'nombre');
        $precio = self::texto($datos, 'precio_base');
        $tipo = self::texto($datos, 'tipo');

        if (!preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $sku)) {
            $errores['sku'] = 'Usa un SKU de 1 a 40 letras, números, guiones o guiones bajos.';
        }
        if ($nombre === '' || mb_strlen($nombre) > 150) {
            $errores['nombre'] = 'El nombre debe tener entre 1 y 150 caracteres.';
        }
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $precio) || (float) $precio <= 0) {
            $errores['precio_base'] = 'El precio debe ser mayor que cero, con máximo dos decimales.';
        }
        if (!in_array($tipo, ['fisico', 'digital', 'servicio'], true)) {
            $errores['tipo'] = 'Selecciona un tipo válido.';
            return $errores;
        }

        if ($tipo === 'fisico') {
            $stock = filter_var($datos['stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($stock === false || $stock === null) {
                $errores['stock'] = 'Las existencias deben ser un entero no negativo.';
            }
        } elseif ($tipo === 'digital') {
            $url = self::texto($datos, 'enlace_descarga');
            if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)
                || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                $errores['enlace_descarga'] = 'Ingresa una URL http o https válida.';
            }
        } elseif (!self::fechaValida(self::texto($datos, 'fecha_agenda'))) {
            $errores['fecha_agenda'] = 'Ingresa una fecha y hora válidas.';
        }

        return $errores;
    }

    public static function fechaValida(string $fecha): bool
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $formato) {
            $valor = DateTimeImmutable::createFromFormat('!' . $formato, $fecha);
            if ($valor !== false && $valor->format($formato) === $fecha) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string,mixed> $datos */
    private static function texto(array $datos, string $campo): string
    {
        $valor = $datos[$campo] ?? '';
        return is_scalar($valor) ? trim((string) $valor) : '';
    }
}
