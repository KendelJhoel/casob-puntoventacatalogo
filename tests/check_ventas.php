<?php

declare(strict_types=1);

if (getenv('CASOB_TEST_DB') !== '1') {
    exit("Configura CASOB_TEST_DB=1 y usa una base de prueba; este script crea una venta.\n");
}

require __DIR__ . '/../vendor/autoload.php';

use App\Excepciones\StockInsuficienteException;
use App\Infraestructura\Conexion;
use App\Repositorios\RepositorioVentas;

$pdo = Conexion::crear(require __DIR__ . '/../config/config.php');
$ventas = new RepositorioVentas($pdo);
$filas = $pdo->query("SELECT id, sku, stock FROM items WHERE sku IN ('SKU-001', 'SKU-002', 'DIG-001', 'SRV-001')")
    ->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
$porSku = [];
foreach ($filas as $id => $fila) {
    $porSku[$fila['sku']] = ['id' => (int) $id, 'stock' => $fila['stock']];
}
foreach (['SKU-001', 'SKU-002', 'DIG-001', 'SRV-001'] as $sku) {
    if (!isset($porSku[$sku])) {
        throw new RuntimeException("Falta la semilla $sku.");
    }
}

$fisico = $porSku['SKU-001']['id'];
$otroFisico = $porSku['SKU-002']['id'];
$digital = $porSku['DIG-001']['id'];
$servicio = $porSku['SRV-001']['id'];
$stockAntes = (int) $porSku['SKU-001']['stock'];
$ventaId = null;
try {
    $ventaId = $ventas->registrar('Cliente de prueba', [$fisico => 2, $digital => 1, $servicio => 1]);
    $cabecera = $pdo->query("SELECT cliente, total FROM ventas WHERE id = $ventaId")->fetch();
    $detalles = $pdo->query("SELECT sku, nombre, cantidad, precio_unitario, subtotal FROM venta_detalles WHERE venta_id = $ventaId ORDER BY id")->fetchAll();
    if ($cabecera['cliente'] !== 'Cliente de prueba' || (float) $cabecera['total'] !== 218.00 || count($detalles) !== 3
        || $detalles[0]['sku'] !== 'SKU-001' || (float) $detalles[0]['subtotal'] !== 115.00
        || (float) $detalles[1]['precio_unitario'] !== 18.00 || (float) $detalles[2]['precio_unitario'] !== 85.00) {
        throw new RuntimeException('La venta o el cálculo polimórfico no coinciden con las semillas.');
    }
    $stockActual = (int) $pdo->query("SELECT stock FROM items WHERE id = $fisico")->fetchColumn();
    if ($stockActual !== $stockAntes - 2) {
        throw new RuntimeException('No se descontó el stock físico.');
    }

    $ventasAntes = (int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
    try {
        $ventas->registrar('Otra prueba', [$fisico => 1, $otroFisico => 999]);
        throw new RuntimeException('Se permitió vender por encima del stock.');
    } catch (StockInsuficienteException) {
        if ((int) $pdo->query("SELECT stock FROM items WHERE id = $fisico")->fetchColumn() !== $stockActual
            || (int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn() !== $ventasAntes) {
            throw new RuntimeException('La venta fallida modificó existencias o creó un ticket.');
        }
    }
    try {
        $ventas->registrar('Otra prueba', [$fisico => 0]);
        throw new RuntimeException('Se aceptó cantidad cero.');
    } catch (InvalidArgumentException) {
    }
    echo "Venta de tres tipos, precios, stock, rollback y validación: OK.\n";
} finally {
    if ($ventaId !== null) {
        $pdo->prepare('DELETE FROM ventas WHERE id = ?')->execute([$ventaId]);
        $pdo->prepare('UPDATE items SET stock = ? WHERE id = ?')->execute([$stockAntes, $fisico]);
    }
}
