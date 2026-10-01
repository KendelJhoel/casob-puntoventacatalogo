<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require __DIR__ . '/../views/layout/encabezado.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$item = $id ? $repo->buscar($id) : null;
if ($item === null) {
    http_response_code(404);
    encabezado('No encontrado');
    echo '<section class="empty section-block"><h1>Ítem no encontrado</h1><p>Puede que ya no esté en el catálogo.</p><a class="button" href="catalogo.php">Volver al catálogo</a></section>';
    pie();
    exit;
}

$campo = App\Modelos\ItemFactory::campoPropio($item->getTipo());
$valor = $item->getCamposPropios()[$campo['campo']];
encabezado($item->getNombre());
?>
<div class="page-top"><a class="text-link" href="catalogo.php"><span aria-hidden="true">←</span> Volver al catálogo</a></div>
<article class="detail-grid">
    <img class="detail-image" src="<?= e(rutaImagen($item->getImagen())) ?>" alt="Imagen de <?= e($item->getNombre()) ?>">
    <div class="detail-copy">
        <span class="badge"><?= e($item->getEtiquetaTipo()) ?></span>
        <h1><?= e($item->getNombre()) ?></h1>
        <p class="price">$<?= number_format($item->calcularPrecioFinal(), 2) ?> <small>precio final</small></p>
        <dl class="detail-list">
            <dt>SKU</dt><dd><?= e($item->getId()) ?></dd>
            <dt>Precio base</dt><dd>$<?= number_format($item->getPrecioBase(), 2) ?></dd>
            <dt><?= e($campo['etiqueta']) ?></dt><dd><?= e($valor) ?></dd>
            <dt>Detalle</dt><dd><?= e($item->obtenerDetalle()) ?></dd>
        </dl>
        <div class="detail-actions"><a class="button" href="editar.php?id=<?= (int) $item->getDatabaseId() ?>">Editar ítem</a><a class="button button-ghost" href="eliminar.php?id=<?= (int) $item->getDatabaseId() ?>">Eliminar</a></div>
    </div>
</article>
<?php pie(); ?>
