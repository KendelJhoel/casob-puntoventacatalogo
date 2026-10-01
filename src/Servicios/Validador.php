<?php

declare(strict_types=1);

namespace App\Servicios;

use DateTimeImmutable;
use App\Modelos\ItemFactory;

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

        $errores += ItemFactory::validarCamposPropios($tipo, $datos);

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
