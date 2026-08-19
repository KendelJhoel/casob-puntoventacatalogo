<?php

declare(strict_types=1);

/**
 * main.php — Script de demostración del sistema de punto de venta.
 *
 * Demuestra:
 *   1. Creación de ítems heterogéneos (físico, digital, servicio).
 *   2. Polimorfismo en el cálculo de precios.
 *   3. Encapsulamiento de stock y manejo de excepciones.
 *   4. Totalización mediante método común del Carrito.
 *   5. Generación de ticket en formato JSON.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Modelos\ProductoFisico;
use App\Modelos\ProductoDigital;
use App\Modelos\Servicio;
use App\Servicios\Carrito;
use App\Excepciones\StockInsuficienteException;

// ─── Separadores visuales ──────────────────────────────────────────────────
$linea     = str_repeat('─', 65);
$lineaDoble = str_repeat('═', 65);

echo PHP_EOL;
echo "╔" . str_repeat("═", 63) . "╗" . PHP_EOL;
echo "║   SISTEMA DE PUNTO DE VENTA — Caso B                         ║" . PHP_EOL;
echo "╚" . str_repeat("═", 63) . "╝" . PHP_EOL . PHP_EOL;

// ─── 1. Catálogo de productos ──────────────────────────────────────────────
echo "► CATÁLOGO DE PRODUCTOS" . PHP_EOL;
echo $linea . PHP_EOL;

// Producto físico con 3 unidades en stock (ID readonly).
$auriculares = new ProductoFisico('SKU-001', 'Auriculares Bluetooth', 50.00, 3);

// Producto digital con enlace de descarga.
$ebook = new ProductoDigital('DIG-042', 'Ebook: Clean Code en PHP', 20.00, 'https://downloads.tienda.com/ebook-clean-code.pdf');

// Servicio agendable.
$consultoria = new Servicio('SRV-007', 'Consultoría Técnica (1h)', 80.00, '2026-08-25 10:00');

$catalogo = [$auriculares, $ebook, $consultoria];

foreach ($catalogo as $item) {
    echo $item->obtenerDetalle() . PHP_EOL;
}

echo PHP_EOL;

// ─── 2. Carrito: compra exitosa ────────────────────────────────────────────
echo "► COMPRA N° 1 — Venta normal" . PHP_EOL;
echo $linea . PHP_EOL;

$carrito1 = new Carrito();
$carrito1->agregarItem($auriculares, 2); // Reduce stock de 3 → 1
$carrito1->agregarItem($ebook);
$carrito1->agregarItem($consultoria);

echo "  Ítems en carrito: " . count($carrito1->getItems()) . PHP_EOL;
echo sprintf("  Total (polimórfico): $%.2f", $carrito1->calcularTotal()) . PHP_EOL;

$rutaTicket1 = $carrito1->emitirTicket();
echo "  ✔ Ticket guardado en: " . realpath($rutaTicket1) . PHP_EOL;

echo PHP_EOL;

// ─── 3. Encapsulamiento de stock: excepción al exceder el stock ────────────
echo "► COMPRA N° 2 — Excepción por stock insuficiente" . PHP_EOL;
echo $linea . PHP_EOL;
echo "  Stock restante de '{$auriculares->getNombre()}': {$auriculares->getStock()} unidad(es)." . PHP_EOL;
echo "  Intentando comprar 5 unidades..." . PHP_EOL;

try {
    $carrito2 = new Carrito();
    $carrito2->agregarItem($auriculares, 5); // Stock es 1, debe fallar
    echo "  ERROR: No se lanzó la excepción esperada." . PHP_EOL;
} catch (StockInsuficienteException $e) {
    echo "  ✔ Excepción capturada correctamente: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// ─── 4. SKU/ID readonly: no puede modificarse ─────────────────────────────
echo "► DEMOSTRACIÓN: Propiedad readonly (ID/SKU)" . PHP_EOL;
echo $linea . PHP_EOL;
echo "  ID de auriculares: {$auriculares->getId()} (solo lectura, no modificable)." . PHP_EOL;

try {
    // Intentar modificar una propiedad readonly lanzará un Error fatal de PHP.
    // Usamos Reflection para demostrarlo de forma segura sin detener el script.
    $reflection = new ReflectionProperty($auriculares, 'id');
    $reflection->setValue($auriculares, 'NUEVO-ID');
    echo "  ERROR: Se permitió modificar la propiedad readonly." . PHP_EOL;
} catch (Error $e) {
    echo "  ✔ Propiedad readonly protegida: No se puede modificar (Error de PHP)." . PHP_EOL;
}

echo PHP_EOL;
echo "╔" . str_repeat("═", 63) . "╗" . PHP_EOL;
echo "║   SISTEMA FINALIZADO EXITOSAMENTE                             ║" . PHP_EOL;
echo "╚" . str_repeat("═", 63) . "╝" . PHP_EOL . PHP_EOL;
