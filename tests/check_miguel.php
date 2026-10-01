<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Excepciones\StockInsuficienteException;
use App\Infraestructura\Conexion;
use App\Modelos\ItemFactory;
use App\Modelos\ProductoDigital;
use App\Modelos\ProductoFisico;
use App\Repositorios\RepositorioItems;
use App\Servicios\Validador;

function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$base = ['sku' => 'SKU-999', 'nombre' => 'Prueba', 'precio_base' => '10.50', 'tipo' => 'fisico', 'stock' => '2'];
comprobar(Validador::item($base) === [], 'El físico válido fue rechazado.');
comprobar(isset(Validador::item([...$base, 'stock' => '-1'])['stock']), 'Stock negativo aceptado.');
comprobar(isset(Validador::item([...$base, 'stock' => null])['stock']), 'Stock ausente aceptado.');
comprobar(isset(Validador::item([...$base, 'stock' => '2147483648'])['stock']), 'Stock fuera del rango SQL aceptado.');
comprobar(isset(Validador::item([...$base, 'sku' => 'SKU CON ESPACIOS!'])['sku']), 'SKU inválido aceptado.');
comprobar(isset(Validador::item([...$base, 'tipo' => 'servicio', 'fecha_agenda' => '2026-02-30T10:00'])['fecha_agenda']), 'Fecha inexistente aceptada por el formulario.');
comprobar(isset(Validador::item([...$base, 'tipo' => 'digital', 'enlace_descarga' => []])['enlace_descarga']), 'URL no escalar aceptada.');
comprobar(isset(Validador::item([...$base, 'precio_base' => '0'])['precio_base']), 'Precio cero aceptado.');
comprobar(isset(Validador::item([...$base, 'nombre' => []])['nombre']), 'Dato no escalar aceptado.');
comprobar(isset(Validador::item([...$base, 'tipo' => 'digital', 'enlace_descarga' => 'javascript:alert(1)'])['enlace_descarga']), 'URL peligrosa aceptada.');
comprobar(!Validador::fechaValida('2026-02-30 10:00'), 'Fecha inexistente aceptada.');

$fisico = ItemFactory::crear($base);
comprobar($fisico instanceof ProductoFisico && $fisico->calcularPrecioFinal() === 12.08, 'La fábrica o el precio físico falló.');
$fisico->reservar(1);
comprobar($fisico->getStock() === 1, 'No bajó el stock.');
try {
    $fisico->reservar(2);
    throw new RuntimeException('Se permitió sobreventa.');
} catch (StockInsuficienteException) {
    comprobar($fisico->getStock() === 1, 'La sobreventa alteró el stock.');
}
$servicio = ItemFactory::crear(['sku' => 'SRV-999', 'nombre' => 'Agenda', 'precio_base' => '30.00', 'tipo' => 'servicio', 'fecha_agenda' => '2026-10-05T09:00']);
comprobar($servicio->getCamposPropios()['fecha_agenda'] === '2026-10-05 09:00:00', 'La fecha del formulario no se normalizó.');

$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    echo "Modelo y validación: OK. Copia config.example.php a config.php para probar MySQL.\n";
    exit(0);
}

$pdo = Conexion::crear(require $configPath);
$repo = new RepositorioItems($pdo);
comprobar(count($repo->listar()) >= 9, 'Faltan los datos iniciales.');

$pdo->beginTransaction();
try {
    $id = $repo->crear(new ProductoDigital('DIG-999', 'Archivo de prueba', 10.00, 'https://ejemplo.com/archivo.zip'));
    $guardado = $repo->buscar($id);
    comprobar($guardado instanceof ProductoDigital && $guardado->getId() === 'DIG-999', 'No se recuperó el digital.');

    $editado = new ProductoDigital('DIG-999', 'Archivo actualizado', 12.00, 'https://ejemplo.com/nuevo.zip', $id);
    comprobar($repo->actualizar($editado), 'No se actualizó el digital.');
    comprobar($repo->actualizar($editado), 'Guardar sin cambios se confundió con un ítem inexistente.');
    comprobar($repo->buscar($id)?->getNombre() === 'Archivo actualizado', 'La edición no persistió.');

    $pdo->prepare('INSERT INTO ventas (cliente, total) VALUES (?, ?)')->execute(['Cliente de prueba', '10.80']);
    $ventaId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO venta_detalles (venta_id, item_id, sku, nombre, detalle, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$ventaId, $id, 'DIG-999', 'Archivo actualizado', 'Detalle histórico', 1, '10.80', '10.80']);
    comprobar($repo->eliminar($id) && $repo->buscar($id) === null, 'No se eliminó el ítem.');
    $detalle = $pdo->query('SELECT item_id, nombre FROM venta_detalles ORDER BY id DESC LIMIT 1')->fetch();
    comprobar($detalle['item_id'] === null && $detalle['nombre'] === 'Archivo actualizado', 'El borrado dañó el ticket histórico.');
    echo "Modelo, validación, semillas, CRUD e historial: OK.\n";
} finally {
    $pdo->rollBack();
}
