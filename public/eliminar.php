<?php

declare(strict_types=1);

use App\Servicios\GestorImagenes;

require __DIR__ . '/_init.php';
require __DIR__ . '/../views/layout/encabezado.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$item = $id ? $repo->buscar($id) : null;
if ($item === null) {
    http_response_code(404);
    encabezado('No encontrado');
    echo '<section class="empty section-block"><h1>Ítem no encontrado</h1><a class="button" href="catalogo.php">Volver al catálogo</a></section>';
    pie();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    try {
        $repo->eliminar($id); // Solo POST puede modificar datos.
        (new GestorImagenes(__DIR__ . '/uploads'))->eliminar($item->getImagen());
        aviso('Ítem eliminado. Los tickets anteriores se conservan.');
        redirigir('catalogo.php');
    } catch (PDOException $e) {
        error_log($e->getMessage());
        aviso('No se pudo eliminar el ítem.', 'error');
        redirigir('eliminar.php?id=' . $id);
    }
}

encabezado('Eliminar ítem');
?>
<div class="page-top"><div><p class="eyebrow">Catálogo</p><h1>Eliminar ítem</h1></div></div>
<section class="panel danger-panel">
    <h2>¿Eliminar “<?= e($item->getNombre()) ?>”?</h2>
    <p>El ítem desaparecerá del catálogo. Las ventas registradas conservarán sus datos históricos.</p>
    <form method="post" action="eliminar.php?id=<?= (int) $id ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
        <div class="form-actions"><button class="button button-danger" type="submit">Sí, eliminar</button><a class="button button-ghost" href="detalle.php?id=<?= (int) $id ?>">Cancelar</a></div>
    </form>
</section>
<?php pie(); ?>
