<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Excepciones\StockInsuficienteException;
use App\Modelos\ItemFactory;
use InvalidArgumentException;
use PDO;
use Throwable;

/** Las ventas, sus líneas y el stock se confirman juntos. [COMPOSICION] [SEGURIDAD] */
final class RepositorioVentas
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<int|string,int|string> $cantidades Íd de base => cantidad */
    public function registrar(string $cliente, array $cantidades): int
    {
        $cliente = trim($cliente);
        if ($cliente === '' || mb_strlen($cliente) > 150) {
            throw new InvalidArgumentException('Escribe el nombre del cliente (máximo 150 caracteres).');
        }

        $lineas = [];
        foreach ($cantidades as $id => $cantidad) {
            if (!filter_var((string) $id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                || !(is_int($cantidad) || is_string($cantidad))
                || !preg_match('/^[1-9][0-9]*$/D', (string) $cantidad)
                || (float) $cantidad > 1000000) {
                throw new InvalidArgumentException('Cada ítem debe tener una cantidad entera entre 1 y 1000000.');
            }
            $lineas[(int) $id] = (int) $cantidad;
        }
        if ($lineas === []) {
            throw new InvalidArgumentException('Selecciona al menos un ítem para cobrar.');
        }

        $this->pdo->beginTransaction();
        try {
            $buscar = $this->pdo->prepare('SELECT * FROM items WHERE id = ? FOR UPDATE');
            $descontar = $this->pdo->prepare('UPDATE items SET stock = stock - ? WHERE id = ? AND stock >= ?');
            $detalles = [];
            $totalCentavos = 0;
            foreach ($lineas as $id => $cantidad) {
                $buscar->execute([$id]);
                $fila = $buscar->fetch();
                $buscar->closeCursor();
                if ($fila === false) {
                    throw new InvalidArgumentException('Un ítem seleccionado ya no está disponible.');
                }

                $item = ItemFactory::crear($fila);
                $item->reservar($cantidad); // [POLIMORFISMO] El físico comprueba existencias.
                if ($item->requiereCantidad()) {
                    $descontar->execute([$cantidad, $id, $cantidad]);
                    if ($descontar->rowCount() !== 1) {
                        throw new StockInsuficienteException($item->getNombre(), (int) $fila['stock']);
                    }
                }

                $precioCentavos = (int) round($item->calcularPrecioFinal() * 100);
                if ($precioCentavos > 9999999999) {
                    throw new InvalidArgumentException('El precio de un ítem supera el límite permitido.');
                }
                $subtotalCentavos = $precioCentavos * $cantidad;
                $totalCentavos += $subtotalCentavos;
                if ($totalCentavos > 999999999999) {
                    throw new InvalidArgumentException('El total de la venta supera el límite permitido.');
                }
                $detalles[] = [$id, $item->getId(), $item->getNombre(), $item->obtenerDetalle(),
                    $cantidad, self::dinero($precioCentavos), self::dinero($subtotalCentavos)];
            }

            $venta = $this->pdo->prepare('INSERT INTO ventas (cliente, total) VALUES (?, ?)');
            $venta->execute([$cliente, self::dinero($totalCentavos)]);
            $ventaId = (int) $this->pdo->lastInsertId();
            $guardarDetalle = $this->pdo->prepare(
                'INSERT INTO venta_detalles (venta_id, item_id, sku, nombre, detalle, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($detalles as $detalle) {
                $guardarDetalle->execute([$ventaId, ...$detalle]);
            }
            $this->pdo->commit();
            return $ventaId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(): array
    {
        $consulta = $this->pdo->prepare('SELECT id, cliente, total, creada_en FROM ventas ORDER BY id DESC');
        $consulta->execute();
        return $consulta->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function buscar(int $id): ?array
    {
        $venta = $this->pdo->prepare('SELECT id, cliente, total, creada_en FROM ventas WHERE id = ?');
        $venta->execute([$id]);
        $cabecera = $venta->fetch();
        if ($cabecera === false) {
            return null;
        }

        $detalles = $this->pdo->prepare(
            'SELECT sku, nombre, detalle, cantidad, precio_unitario, subtotal
             FROM venta_detalles WHERE venta_id = ? ORDER BY id'
        );
        $detalles->execute([$id]);
        $cabecera['detalles'] = $detalles->fetchAll();
        return $cabecera;
    }

    /** @return array{fecha:string,cantidad:int,total:string,productos:array<int,array<string,mixed>>} */
    public function reporteHoy(): array
    {
        $resumen = $this->pdo->prepare(
            'SELECT CURRENT_DATE() AS fecha, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
             FROM ventas WHERE creada_en >= CURRENT_DATE() AND creada_en < CURRENT_DATE() + INTERVAL 1 DAY'
        );
        $resumen->execute();
        $datos = $resumen->fetch();
        $productos = $this->pdo->prepare(
            'SELECT d.sku, d.nombre, SUM(d.cantidad) AS unidades, SUM(d.subtotal) AS importe
             FROM venta_detalles d JOIN ventas v ON v.id = d.venta_id
             WHERE v.creada_en >= CURRENT_DATE() AND v.creada_en < CURRENT_DATE() + INTERVAL 1 DAY
             GROUP BY d.sku, d.nombre ORDER BY importe DESC, d.sku'
        );
        $productos->execute();
        $datos['productos'] = $productos->fetchAll();
        return $datos;
    }

    private static function dinero(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
