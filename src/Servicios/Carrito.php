<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Contratos\Facturable;
use App\Modelos\ProductoFisico;

/**
 * Servicio que gestiona el carrito de compras.
 *
 * Acepta cualquier ítem que implemente Facturable, permitiendo
 * totalizar ítems heterogéneos mediante el método común calcularPrecioFinal().
 * Al emitir el ticket, lo persiste en disco como JSON.
 */
class Carrito
{
    /** @var Facturable[] */
    private array $items = [];

    private string $directorioTickets;

    public function __construct(string $directorioTickets = __DIR__ . '/../../tickets')
    {
        $this->directorioTickets = $directorioTickets;

        // Asegurar que el directorio de tickets exista.
        if (!is_dir($this->directorioTickets)) {
            mkdir($this->directorioTickets, 0755, true);
        }
    }

    /**
     * Agrega un ítem al carrito. Si es un ProductoFisico, reduce su stock
     * en la cantidad indicada (encapsulamiento: la validación ocurre dentro del modelo).
     *
     * @throws \App\Excepciones\StockInsuficienteException si el stock es insuficiente.
     */
    public function agregarItem(Facturable $item, int $cantidad = 1): void
    {
        // El carrito delega la validación de stock al propio modelo.
        if ($item instanceof ProductoFisico) {
            $item->reducirStock($cantidad);
        }

        // Cada unidad se agrega como entrada individual para el ticket.
        for ($i = 0; $i < $cantidad; $i++) {
            $this->items[] = $item;
        }
    }

    /**
     * Totaliza todos los ítems del carrito mediante el método polimórfico
     * calcularPrecioFinal(), que cada ítem implementa de forma diferente.
     */
    public function calcularTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->calcularPrecioFinal();
        }
        return $total;
    }

    /**
     * Genera el ticket en pantalla y lo persiste como archivo JSON en disco.
     *
     * @return string La ruta del archivo JSON generado.
     */
    public function emitirTicket(): string
    {
        $timestamp = date('Ymd_His');
        $nombreArchivo = "ticket_{$timestamp}.json";
        $rutaCompleta = $this->directorioTickets . '/' . $nombreArchivo;

        $detallesItems = [];
        foreach ($this->items as $item) {
            $detallesItems[] = [
                'detalle' => $item->obtenerDetalle(),
                'precio_final' => round($item->calcularPrecioFinal(), 2),
            ];
        }

        $datosTicket = [
            'numero_ticket' => $timestamp,
            'fecha_emision' => date('Y-m-d H:i:s'),
            'items' => $detallesItems,
            'total' => round($this->calcularTotal(), 2),
        ];

        // Persistencia en archivo JSON (manejo de archivos).
        $jsonContenido = json_encode($datosTicket, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($rutaCompleta, $jsonContenido);

        return $rutaCompleta;
    }

    /**
     * Imprime en la terminal un recibo con estilo de ticket físico.
     * Agrupa los ítems por nombre, mostrando cantidad y subtotal por línea.
     *
     * @param string $numeroTicket El identificador del ticket (timestamp).
     */
    public function imprimirTicketTerminal(string $numeroTicket): void
    {
        // Ancho interno del ticket (sin los bordes laterales).
        $ancho = 51;
        $borde = str_repeat('─', $ancho);

        $centrar = fn(string $texto): string =>
            str_pad($texto, $ancho, ' ', STR_PAD_BOTH);

        $linea = fn(string $contenido): string =>
            '│' . str_pad($contenido, $ancho) . '│';

        // ── Agrupar ítems por nombre para mostrar cantidad ─────────────────
        $agrupados = [];
        foreach ($this->items as $item) {
            $nombre = $item->getNombre();
            if (!isset($agrupados[$nombre])) {
                $agrupados[$nombre] = ['item' => $item, 'cantidad' => 0];
            }
            $agrupados[$nombre]['cantidad']++;
        }

        // ── Dibujar el ticket ──────────────────────────────────────────────
        echo PHP_EOL;
        echo "  ┌{$borde}┐" . PHP_EOL;
        echo '  │' . "\033[1;33m" . $centrar('★  PUNTO DE VENTA  ★') . "\033[0m" . '│' . PHP_EOL;
        echo '  │' . $centrar('Caso B — Sistema de Catálogo') . '│' . PHP_EOL;
        echo "  ├{$borde}┤" . PHP_EOL;
        echo '  ' . $linea("  Ticket N°: {$numeroTicket}") . PHP_EOL;
        echo '  ' . $linea("  Fecha:     " . date('Y-m-d H:i:s')) . PHP_EOL;
        echo "  ├{$borde}┤" . PHP_EOL;

        // Cabecera de columnas.
        $cabecera = sprintf('  %-28s %8s %3s %9s', 'PRODUCTO', 'P.UNIT', 'QTY', 'SUBTOTAL');
        echo '  │' . "\033[1m" . str_pad($cabecera, $ancho) . "\033[0m" . '│' . PHP_EOL;
        echo "  ├{$borde}┤" . PHP_EOL;

        // Ítems del ticket.
        foreach ($agrupados as $grupo) {
            $item     = $grupo['item'];
            $cantidad = $grupo['cantidad'];
            $subtotal = $item->calcularPrecioFinal() * $cantidad;

            // Truncar nombre si es demasiado largo.
            $nombre = mb_strlen($item->getNombre()) > 28
                ? mb_substr($item->getNombre(), 0, 25) . '...'
                : $item->getNombre();

            $fila = sprintf(
                '  %-28s %8s x%-2d %8s',
                $nombre,
                '$' . number_format($item->calcularPrecioFinal(), 2),
                $cantidad,
                '$' . number_format($subtotal, 2)
            );

            echo '  ' . $linea($fila) . PHP_EOL;
        }

        echo "  ├{$borde}┤" . PHP_EOL;

        // Total.
        $totalStr = '$' . number_format($this->calcularTotal(), 2);
        $filaTotal = sprintf('  %-38s %10s', "\033[1mTOTAL A PAGAR:\033[0m", "\033[1;32m{$totalStr}\033[0m");
        echo '  │' . $filaTotal . str_repeat(' ', max(0, $ancho - 49)) . '│' . PHP_EOL;

        echo "  ├{$borde}┤" . PHP_EOL;
        echo '  │' . $centrar('¡Gracias por su compra!') . '│' . PHP_EOL;
        echo '  │' . $centrar('Conserve este ticket como comprobante.') . '│' . PHP_EOL;
        echo "  └{$borde}┘" . PHP_EOL . PHP_EOL;
    }

    /** @return Facturable[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
