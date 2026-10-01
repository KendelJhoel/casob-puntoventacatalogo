<?php

declare(strict_types=1);

use App\Repositorios\RepositorioVentas;

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$reporte = (new RepositorioVentas($pdo))->reporteHoy();
$catalogo = [];
foreach ($repo->listar() as $item) {
    $tipo = $item->getEtiquetaTipo(); // [POLIMORFISMO] Cada clase aporta etiqueta y precio final.
    $catalogo[$tipo]['cantidad'] = ($catalogo[$tipo]['cantidad'] ?? 0) + 1;
    $catalogo[$tipo]['valor'] = ($catalogo[$tipo]['valor'] ?? 0) + $item->calcularPrecioFinal();
}
encabezado('Reporte del día');
?>
<section class="page-top"><div><p class="eyebrow">Resumen del negocio</p><h1>Reporte del día</h1><p>Ventas registradas el <?= e($reporte['fecha']) ?>, hora de El Salvador.</p></div></section>
<div class="report-stats">
    <article class="panel"><span>Ventas cobradas</span><strong><?= (int) $reporte['cantidad'] ?></strong></article>
    <article class="panel"><span>Ingreso total</span><strong>$<?= number_format((float) $reporte['total'], 2) ?></strong></article>
</div>
<section class="section-block">
    <div class="section-heading"><div><p class="eyebrow">Desde los tickets</p><h2>Ítems vendidos hoy</h2></div></div>
    <?php if ($reporte['productos'] === []): ?>
        <div class="empty"><p>Todavía no hay ventas en la fecha actual.</p><a class="button" href="venta_nueva.php">Cobrar venta</a></div>
    <?php else: ?>
        <div class="panel sales-panel"><div class="table-scroll"><table class="data-table">
            <thead><tr><th scope="col">SKU</th><th scope="col">Nombre al vender</th><th scope="col">Unidades</th><th scope="col">Ingreso</th></tr></thead>
            <tbody><?php foreach ($reporte['productos'] as $fila): ?>
                <tr><td><?= e($fila['sku']) ?></td><td><?= e($fila['nombre']) ?></td><td><?= (int) $fila['unidades'] ?></td><td>$<?= number_format((float) $fila['importe'], 2) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div></div>
    <?php endif; ?>
</section>
<section class="section-block">
    <div class="section-heading"><div><p class="eyebrow">Catálogo actual</p><h2>Valor por tipo</h2></div></div>
    <div class="feature-grid">
        <?php foreach ($catalogo as $tipo => $datos): ?>
            <article class="feature-card"><h3><?= e($tipo) ?></h3><p><?= (int) $datos['cantidad'] ?> ítems · suma de precios finales $<?= number_format($datos['valor'], 2) ?></p></article>
        <?php endforeach; ?>
    </div>
</section>
<?php pie(); ?>
