<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require __DIR__ . '/../views/layout/encabezado.php';

$items = $repo->listar();
encabezado('Catálogo');
?>
<section class="page-top">
    <div><p class="eyebrow">Catálogo actual</p><h1>Todos los ítems</h1><p><?= count($items) ?> disponibles en la base de datos</p></div>
    <a class="button" href="crear.php">Agregar ítem</a>
</section>

<?php if (!$items): ?>
    <div class="empty"><h2>El catálogo está vacío</h2><p>Agrega el primer producto o servicio para comenzar.</p><a class="button" href="crear.php">Crear ítem</a></div>
<?php else: ?>
    <div class="card-grid section-block">
        <?php foreach ($items as $item): ?>
            <article class="item-card">
                <img src="<?= e(rutaImagen($item->getImagen())) ?>" alt="Imagen de <?= e($item->getNombre()) ?>" loading="lazy">
                <div class="item-card-body">
                    <span class="badge"><?= e($item->getEtiquetaTipo()) ?></span>
                    <h2><?= e($item->getNombre()) ?></h2>
                    <p><?= e($item->getId()) ?><?= e($item->getInfoCatalogo()) ?></p>
                    <span class="price">$<?= number_format($item->calcularPrecioFinal(), 2) ?></span>
                    <div class="card-actions"><a class="text-link" href="detalle.php?id=<?= (int) $item->getDatabaseId() ?>">Ver detalle <span aria-hidden="true">→</span></a><a class="text-link" href="editar.php?id=<?= (int) $item->getDatabaseId() ?>">Editar</a><a class="text-link" href="eliminar.php?id=<?= (int) $item->getDatabaseId() ?>">Eliminar</a></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php pie(); ?>
