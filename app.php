<?php

declare(strict_types=1);

/**
 * app.php — Interfaz interactiva de terminal para el punto de venta.
 *
 * Permite al usuario navegar el catálogo, agregar ítems al carrito,
 * ver el resumen y emitir el ticket en formato JSON.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Modelos\ProductoFisico;
use App\Modelos\ProductoDigital;
use App\Modelos\Servicio;
use App\Servicios\Carrito;
use App\Excepciones\StockInsuficienteException;

// ─── Helpers de I/O ────────────────────────────────────────────────────────

function leer(string $prompt): string
{
    echo $prompt;
    return trim((string) fgets(STDIN));
}

function limpiarPantalla(): void
{
    // Funciona en macOS/Linux.
    echo "\033[2J\033[H";
}

function titulo(string $texto): void
{
    $borde = str_repeat('═', 63);
    echo PHP_EOL . "╔{$borde}╗" . PHP_EOL;
    $padding = str_pad($texto, 61);
    echo "║  {$padding}║" . PHP_EOL;
    echo "╚{$borde}╝" . PHP_EOL . PHP_EOL;
}

function seccion(string $texto): void
{
    echo PHP_EOL . "  \033[1;33m► {$texto}\033[0m" . PHP_EOL;
    echo "  " . str_repeat('─', 60) . PHP_EOL;
}

function exito(string $texto): void
{
    echo "  \033[1;32m✔ {$texto}\033[0m" . PHP_EOL;
}

function error(string $texto): void
{
    echo "  \033[1;31m✖ {$texto}\033[0m" . PHP_EOL;
}

function info(string $texto): void
{
    echo "  \033[0;36m  {$texto}\033[0m" . PHP_EOL;
}

// ─── Catálogo de productos disponibles ────────────────────────────────────

$catalogo = [
    1 => new ProductoFisico('SKU-001', 'Auriculares Bluetooth',         50.00, 5),
    2 => new ProductoFisico('SKU-002', 'Teclado Mecánico RGB',         120.00, 3),
    3 => new ProductoFisico('SKU-003', 'Mouse Ergonómico',              35.00, 8),
    4 => new ProductoDigital('DIG-001', 'Ebook: Clean Code en PHP',     20.00, 'https://downloads.tienda.com/clean-code.pdf'),
    5 => new ProductoDigital('DIG-002', 'Curso PHP Avanzado (acceso)', 150.00, 'https://downloads.tienda.com/curso-php.zip'),
    6 => new Servicio('SRV-001', 'Consultoría Técnica (1h)',            80.00, '2026-09-01 10:00'),
    7 => new Servicio('SRV-002', 'Instalación y Configuración',         60.00, '2026-09-03 14:00'),
];

// ─── Estado global ─────────────────────────────────────────────────────────

$carrito = new Carrito();

// ─── Funciones de UI ───────────────────────────────────────────────────────

function mostrarCatalogo(array $catalogo): void
{
    seccion("CATÁLOGO DE PRODUCTOS");

    $tipoActual = '';
    foreach ($catalogo as $num => $item) {
        $tipo = match(true) {
            $item instanceof ProductoFisico  => 'Productos Físicos',
            $item instanceof ProductoDigital => 'Productos Digitales',
            $item instanceof Servicio        => 'Servicios',
        };

        if ($tipo !== $tipoActual) {
            echo PHP_EOL . "  \033[1;34m  [{$tipo}]\033[0m" . PHP_EOL;
            $tipoActual = $tipo;
        }

        $stockInfo = $item instanceof ProductoFisico
            ? " (stock: {$item->getStock()})"
            : '';

        echo sprintf(
            "  \033[1m  [%d]\033[0m %-35s \033[0;32m$%.2f final\033[0m%s" . PHP_EOL,
            $num,
            $item->getNombre(),
            $item->calcularPrecioFinal(),
            $stockInfo
        );
    }
}

function mostrarCarrito(Carrito $carrito): void
{
    $items = $carrito->getItems();
    seccion("CARRITO ACTUAL");

    if (empty($items)) {
        info("El carrito está vacío.");
        return;
    }

    // Agrupar ítems por ID para mostrar cantidades.
    $agrupados = [];
    foreach ($items as $item) {
        $id = method_exists($item, 'getId') ? $item->getId() : spl_object_id($item);
        if (!isset($agrupados[$id])) {
            $agrupados[$id] = ['item' => $item, 'cantidad' => 0];
        }
        $agrupados[$id]['cantidad']++;
    }

    $i = 1;
    foreach ($agrupados as $grupo) {
        $subtotal = $grupo['item']->calcularPrecioFinal() * $grupo['cantidad'];
        echo sprintf(
            "  %d. %-30s x%d  → $%.2f" . PHP_EOL,
            $i++,
            $grupo['item']->getNombre(),
            $grupo['cantidad'],
            $subtotal
        );
    }

    echo PHP_EOL;
    echo sprintf("  \033[1m  TOTAL: $%.2f\033[0m" . PHP_EOL, $carrito->calcularTotal());
}

function mostrarMenu(): void
{
    seccion("OPCIONES");
    info("[A] Agregar ítem al carrito");
    info("[V] Ver carrito");
    info("[C] Cobrar y emitir ticket");
    info("[L] Limpiar carrito");
    info("[S] Salir");
}

// ─── Bucle principal de la aplicación ────────────────────────────────────

limpiarPantalla();
titulo("SISTEMA DE PUNTO DE VENTA — Caso B");

while (true) {
    mostrarCatalogo($catalogo);
    mostrarCarrito($carrito);
    mostrarMenu();

    $opcion = strtoupper(leer(PHP_EOL . "  Selecciona una opción: "));

    switch ($opcion) {
        // ── Agregar ítem ──────────────────────────────────────────────────
        case 'A':
            $numStr = leer("  Número de producto: ");

            if (!is_numeric($numStr) || !isset($catalogo[(int)$numStr])) {
                error("Número de producto inválido.");
                sleep(1);
                break;
            }

            $num      = (int) $numStr;
            $item     = $catalogo[$num];
            $cantidad = 1;

            if ($item instanceof ProductoFisico) {
                $cantStr = leer("  Cantidad (stock disponible: {$item->getStock()}): ");
                $cantidad = is_numeric($cantStr) && (int)$cantStr > 0 ? (int)$cantStr : 1;
            }

            try {
                $carrito->agregarItem($item, $cantidad);
                exito("'{$item->getNombre()}' x{$cantidad} agregado al carrito.");
            } catch (StockInsuficienteException $e) {
                error($e->getMessage());
            }

            sleep(1);
            limpiarPantalla();
            titulo("SISTEMA DE PUNTO DE VENTA — Caso B");
            break;

        // ── Ver carrito (solo pausa) ───────────────────────────────────────
        case 'V':
            leer(PHP_EOL . "  Presiona Enter para continuar...");
            limpiarPantalla();
            titulo("SISTEMA DE PUNTO DE VENTA — Caso B");
            break;

        // ── Cobrar y emitir ticket ─────────────────────────────────────────
        case 'C':
            if (empty($carrito->getItems())) {
                error("El carrito está vacío. Agrega productos primero.");
                sleep(1);
                break;
            }

            // Persiste el ticket en JSON y obtiene la ruta.
            $rutaTicket   = $carrito->emitirTicket();
            $numeroTicket = pathinfo(basename($rutaTicket), PATHINFO_FILENAME);
            $numeroTicket = str_replace('ticket_', '', $numeroTicket);

            limpiarPantalla();
            titulo("TICKET EMITIDO");

            // Imprime el recibo estilizado en pantalla.
            $carrito->imprimirTicketTerminal($numeroTicket);

            exito("Ticket JSON guardado en: {$rutaTicket}");
            echo PHP_EOL;

            // Reiniciar carrito para una nueva venta.
            $carrito = new Carrito();
            leer("  Presiona Enter para nueva venta...");
            limpiarPantalla();
            titulo("SISTEMA DE PUNTO DE VENTA — Caso B");
            break;


        // ── Limpiar carrito ───────────────────────────────────────────────
        case 'L':
            $carrito = new Carrito();
            // Restaurar stock del catálogo (re-instanciar solo físicos afectados).
            $catalogo[1] = new ProductoFisico('SKU-001', 'Auriculares Bluetooth',   50.00, 5);
            $catalogo[2] = new ProductoFisico('SKU-002', 'Teclado Mecánico RGB',    120.00, 3);
            $catalogo[3] = new ProductoFisico('SKU-003', 'Mouse Ergonómico',         35.00, 8);
            exito("Carrito limpiado. Stock restaurado.");
            sleep(1);
            limpiarPantalla();
            titulo("SISTEMA DE PUNTO DE VENTA — Caso B");
            break;

        // ── Salir ─────────────────────────────────────────────────────────
        case 'S':
            echo PHP_EOL . "  ¡Hasta luego! 👋" . PHP_EOL . PHP_EOL;
            exit(0);

        default:
            error("Opción no válida. Usa A, V, C, L o S.");
            sleep(1);
            limpiarPantalla();
            titulo("SISTEMA DE PUNTO DE VENTA — Caso B");
    }
}
