<?php

declare(strict_types=1);

use App\Repositorios\RepositorioVentas;

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$ventas = (new RepositorioVentas($pdo))->listar();
encabezado('Ventas');
?>
<section class="page-top">
    <div><p class="eyebrow">Historial</p><h1>Ventas guardadas</h1><p>Consulta los tickets emitidos y sus importes originales.</p></div>
    <a class="button" href="venta_nueva.php">Nueva venta</a>
</section>
<?php if ($ventas === []): ?>
    <div class="empty"><h2>Aún no hay ventas</h2><p>El primer cobro aparecerá aquí.</p><a class="button" href="venta_nueva.php">Cobrar venta</a></div>
<?php else: ?>
    <div class="panel sales-panel">
        <div class="table-scroll"><table class="data-table">
            <thead><tr><th scope="col">Ticket</th><th scope="col">Fecha</th><th scope="col">Cliente</th><th scope="col">Total</th><th scope="col">Acción</th></tr></thead>
            <tbody>
            <?php foreach ($ventas as $venta): ?>
                <tr><td>#<?= (int) $venta['id'] ?></td><td><?= e($venta['creada_en']) ?></td><td><?= e($venta['cliente']) ?></td><td>$<?= number_format((float) $venta['total'], 2) ?></td><td><a class="text-link" href="venta.php?id=<?= (int) $venta['id'] ?>">Ver ticket</a></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
<?php endif; ?>
<?php pie(); ?>
