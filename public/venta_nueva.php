<?php

declare(strict_types=1);

use App\Excepciones\StockInsuficienteException;
use App\Repositorios\RepositorioVentas;

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$items = $repo->listar();
$cliente = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['cliente'] ?? '') : '';
$cantidades = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['cantidad'] ?? []) : [];
$errores = [];
$errorGeneral = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    if (!is_string($cliente) || trim($cliente) === '' || mb_strlen(trim($cliente)) > 150) {
        $errores['cliente'] = 'Escribe el nombre del cliente (máximo 150 caracteres).';
    }
    if (!is_array($cantidades)) {
        $cantidades = [];
        $errorGeneral = 'Selecciona cantidades válidas.';
    }

    $seleccion = [];
    foreach ($cantidades as $id => $cantidad) {
        if (!is_int($cantidad) && !is_string($cantidad)) {
            $errores[$id] = 'Usa un número entero.';
        } elseif ($cantidad === '' || $cantidad === '0' || $cantidad === 0) {
            continue;
        } elseif (!preg_match('/^[1-9][0-9]*$/D', (string) $cantidad) || (float) $cantidad > 1000000) {
            $errores[$id] = 'Ingresa entre 0 y 1000000 unidades.';
        } else {
            $seleccion[$id] = $cantidad;
        }
    }
    if ($seleccion === [] && $errorGeneral === null) {
        $errorGeneral = 'Elige al menos un ítem para cobrar.';
    }

    if ($errores === [] && $errorGeneral === null) {
        try {
            $ventaId = (new RepositorioVentas($pdo))->registrar($cliente, $seleccion);
            aviso('Venta guardada correctamente.');
            redirigir('venta.php?id=' . $ventaId); // [PRG]
        } catch (StockInsuficienteException | InvalidArgumentException $e) {
            $errorGeneral = $e->getMessage();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errorGeneral = 'No se pudo guardar la venta. Inténtalo de nuevo.';
        }
    }
    http_response_code(422);
}

encabezado('Cobrar venta');
?>
<section class="page-top">
    <div><p class="eyebrow">Punto de venta</p><h1>Nueva venta</h1><p>Escribe el cliente y la cantidad de cada ítem. Deja en cero lo que no se venderá.</p></div>
</section>
<?php if ($errorGeneral !== null || $errores): ?>
    <div class="alert alert-error" role="alert"><?= e($errorGeneral ?? 'Revisa los campos señalados.') ?></div>
<?php endif; ?>
<?php if ($items === []): ?>
    <div class="empty"><h2>Aún no hay ítems para vender</h2><p>Agrega productos o servicios al catálogo.</p><a class="button" href="crear.php">Crear ítem</a></div>
<?php else: ?>
    <form class="panel" action="venta_nueva.php" method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
        <div class="field field-full">
            <label for="cliente">Cliente</label>
            <input id="cliente" name="cliente" type="text" required maxlength="150" value="<?= e($cliente) ?>" aria-invalid="<?= isset($errores['cliente']) ? 'true' : 'false' ?>">
            <?php if (isset($errores['cliente'])): ?><span class="error"><?= e($errores['cliente']) ?></span><?php endif; ?>
        </div>
        <div class="sale-items">
            <?php foreach ($items as $item): ?>
                <?php $id = (int) $item->getDatabaseId(); ?>
                <div class="sale-item">
                    <div><strong><?= e($item->getNombre()) ?></strong><small><?= e($item->getId()) ?> · <?= e($item->getEtiquetaTipo()) ?><?= e($item->getInfoCatalogo()) ?></small></div>
                    <span>$<?= number_format($item->calcularPrecioFinal(), 2) ?></span>
                    <div class="field">
                        <label for="cantidad-<?= $id ?>">Cantidad de <?= e($item->getNombre()) ?></label>
                        <input id="cantidad-<?= $id ?>" name="cantidad[<?= $id ?>]" type="number" min="0" max="1000000" step="1" value="<?= e($cantidades[$id] ?? '0') ?>" aria-invalid="<?= isset($errores[$id]) ? 'true' : 'false' ?>">
                        <?php if (isset($errores[$id])): ?><span class="error"><?= e($errores[$id]) ?></span><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-actions"><button class="button" type="submit">Guardar venta</button><a class="button button-ghost" href="catalogo.php">Cancelar</a></div>
    </form>
<?php endif; ?>
<?php pie(); ?>
