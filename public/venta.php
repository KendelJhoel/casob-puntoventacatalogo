<?php

declare(strict_types=1);

use App\Repositorios\RepositorioVentas;

require __DIR__ . '/_init.php';
require __DIR__ . '/../views/layout/encabezado.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$venta = $id === false || $id === null ? null : (new RepositorioVentas($pdo))->buscar($id);
if ($venta === null) {
    http_response_code(404);
}
encabezado($venta === null ? 'Ticket no encontrado' : 'Ticket #' . $venta['id']);
?>
<?php if ($venta === null): ?>
    <div class="empty"><h1>Ticket no encontrado</h1><p>La venta solicitada no existe.</p><a class="button" href="ventas.php">Ver ventas</a></div>
<?php else: ?>
    <section class="page-top"><div><p class="eyebrow">Comprobante de venta</p><h1>Ticket #<?= (int) $venta['id'] ?></h1><p>Emitido el <?= e($venta['creada_en']) ?></p></div><a class="button button-ghost" href="ventas.php">Volver a ventas</a></section>
    <article class="panel ticket-panel">
        <h2>Cliente: <?= e($venta['cliente']) ?></h2>
        <div class="table-scroll"><table class="data-table">
            <thead><tr><th scope="col">Ítem</th><th scope="col">Cantidad</th><th scope="col">Precio unitario</th><th scope="col">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($venta['detalles'] as $detalle): ?>
                <tr><td><strong><?= e($detalle['nombre']) ?></strong><small><?= e($detalle['sku']) ?> · <?= e($detalle['detalle']) ?></small></td><td><?= (int) $detalle['cantidad'] ?></td><td>$<?= number_format((float) $detalle['precio_unitario'], 2) ?></td><td>$<?= number_format((float) $detalle['subtotal'], 2) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th scope="row" colspan="3">Total pagado</th><td><strong>$<?= number_format((float) $venta['total'], 2) ?></strong></td></tr></tfoot>
        </table></div>
        <p class="ticket-note">Los nombres, precios y detalles pertenecen a esta venta y se conservan aunque cambie el catálogo.</p>
    </article>
<?php endif; ?>
<?php pie(); ?>
