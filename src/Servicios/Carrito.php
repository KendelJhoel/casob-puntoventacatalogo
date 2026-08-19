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
        $timestamp     = date('Ymd_His');
        $nombreArchivo = "ticket_{$timestamp}.json";
        $rutaCompleta  = $this->directorioTickets . '/' . $nombreArchivo;

        $detallesItems = [];
        foreach ($this->items as $item) {
            $detallesItems[] = [
                'detalle'      => $item->obtenerDetalle(),
                'precio_final' => round($item->calcularPrecioFinal(), 2),
            ];
        }

        $datosTicket = [
            'numero_ticket' => $timestamp,
            'fecha_emision' => date('Y-m-d H:i:s'),
            'items'         => $detallesItems,
            'total'         => round($this->calcularTotal(), 2),
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
     * Regla de alineación: cada línea del ticket tiene exactamente $ancho=51
     * caracteres VISIBLES entre los bordes │. Los códigos ANSI se aplican
     * SIEMPRE fuera de str_pad() para no distorsionar el padding.
     *
     * Layout de columnas (suma = 51 chars):
     *   '  %-26s %8s x%-2d %9s'
     *    2 + 26 + 1 + 8 + 2 + 2 + 1 + 9 = 51 ✓
     *
     * @param string $numeroTicket El identificador del ticket (timestamp).
     */
    public function imprimirTicketTerminal(string $numeroTicket): void
    {
        $ancho = 51;
        $borde = str_repeat('─', $ancho);

        // Línea completa: margen + borde izq + contenido padded a $ancho + borde der.
        // Usa mb_str_pad para manejar correctamente tildes y caracteres multibyte.
        $linea = fn(string $contenido): string =>
            '  │' . mb_str_pad($contenido, $ancho) . '│';

        // Centra $texto (sin ANSI) en $ancho chars (multibyte-safe).
        $centrar = fn(string $texto): string =>
            mb_str_pad($texto, $ancho, ' ', STR_PAD_BOTH);


        // Agrupar ítems por nombre para mostrar cantidad por línea.
        $agrupados = [];
        foreach ($this->items as $item) {
            $nombre = $item->getNombre();
            if (!isset($agrupados[$nombre])) {
                $agrupados[$nombre] = ['item' => $item, 'cantidad' => 0];
            }
            $agrupados[$nombre]['cantidad']++;
        }

        // Formato de filas — 2+26+1+8+2+2+1+9 = 51 chars exactos.
        $formatoFila     = '  %-26s %8s x%-2d %9s';
        // Formato cabecera — 2+26+1+8+1+3+1+9 = 51 chars exactos.
        $formatoCabecera = '  %-26s %8s %3s %9s';

        // ── Dibujar el ticket ─────────────────────────────────────────────
        echo PHP_EOL;
        echo "  ┌{$borde}┐" . PHP_EOL;

        // Título: ANSI fuera de str_pad para no corromper el ancho.
        echo '  │' . "\033[1;33m" . $centrar('★  PUNTO DE VENTA  ★') . "\033[0m" . '│' . PHP_EOL;
        echo $linea($centrar('Caso B — Sistema de Catálogo')) . PHP_EOL;
        echo "  ├{$borde}┤" . PHP_EOL;
        echo $linea("  Ticket N°: {$numeroTicket}") . PHP_EOL;
        echo $linea("  Fecha:     " . date('Y-m-d H:i:s')) . PHP_EOL;
        echo "  ├{$borde}┤" . PHP_EOL;

        // Cabecera de columnas: bold aplicado fuera del sprintf.
        $cabecera = sprintf($formatoCabecera, 'PRODUCTO', 'P.UNIT', 'QTY', 'SUBTOTAL');
        echo '  │' . "\033[1m" . $cabecera . "\033[0m" . '│' . PHP_EOL;
        echo "  ├{$borde}┤" . PHP_EOL;

        // Filas de ítems — cada columna se pad con mb_str_pad para manejar tildes.
        foreach ($agrupados as $grupo) {
            $item     = $grupo['item'];
            $cantidad = $grupo['cantidad'];
            $subtotal = $item->calcularPrecioFinal() * $cantidad;

            // Truncar nombre a 26 chars visibles.
            $nombre = mb_strlen($item->getNombre()) > 26
                ? mb_substr($item->getNombre(), 0, 23) . '...'
                : $item->getNombre();

            // Construir columnas con mb_str_pad para respetar ancho visual.
            // Total: 2(margen) + 26(nombre) + 1(sep) + 8(precio) + 2(xQ) + 2(qty) + 1(sep) + 9(sub) = 51
            $colNombre   = mb_str_pad($nombre, 26);
            $colPrecio   = str_pad('$' . number_format($item->calcularPrecioFinal(), 2), 8, ' ', STR_PAD_LEFT);
            $colCantidad = str_pad((string)$cantidad, 2);
            $colSubtotal = str_pad('$' . number_format($subtotal, 2), 9, ' ', STR_PAD_LEFT);

            $fila = "  {$colNombre} {$colPrecio} x{$colCantidad} {$colSubtotal}";

            echo $linea($fila) . PHP_EOL;
        }


        echo "  ├{$borde}┤" . PHP_EOL;

        // Fila de total: construir SIN ANSI para que str_pad cuente bien,
        // luego aplicar color a la línea completa.
        $labelTotal = 'TOTAL A PAGAR:';
        $valorTotal = '$' . number_format($this->calcularTotal(), 2);
        $espacios   = $ancho - 2 - strlen($labelTotal) - strlen($valorTotal);
        $rawTotal   = '  ' . $labelTotal . str_repeat(' ', max(1, $espacios)) . $valorTotal;
        echo '  │' . "\033[1;32m" . str_pad($rawTotal, $ancho) . "\033[0m" . '│' . PHP_EOL;

        echo "  ├{$borde}┤" . PHP_EOL;
        echo $linea($centrar('¡Gracias por su compra!')) . PHP_EOL;
        echo $linea($centrar('Conserve este ticket como comprobante.')) . PHP_EOL;
        echo "  └{$borde}┘" . PHP_EOL . PHP_EOL;
    }

    /** @return Facturable[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
