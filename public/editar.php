<?php

declare(strict_types=1);

use App\Modelos\ItemFactory;
use App\Servicios\GestorImagenes;
use App\Servicios\Validador;

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$itemActual = $id ? $repo->buscar($id) : null;
if ($itemActual === null) {
    http_response_code(404);
    encabezado('No encontrado');
    echo '<section class="empty section-block"><h1>Ítem no encontrado</h1><a class="button" href="catalogo.php">Volver al catálogo</a></section>';
    pie();
    exit;
}

$propios = $itemActual->getCamposPropios();
$datos = [
    'sku' => $itemActual->getId(),
    'nombre' => $itemActual->getNombre(),
    'precio_base' => number_format($itemActual->getPrecioBase(), 2, '.', ''),
    'tipo' => $itemActual->getTipo(),
    ...$propios,
    'fecha_agenda' => $propios['fecha_agenda'] ? str_replace(' ', 'T', substr($propios['fecha_agenda'], 0, 16)) : null,
];
$errores = [];
$imagenes = new GestorImagenes(__DIR__ . '/uploads');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $datos = $_POST;
    $errores = Validador::item($datos);
    $imagenEnviada = $_FILES['imagen'] ?? [];
    $errorImagen = $imagenes->validar($imagenEnviada, false);
    if ($errorImagen !== null) {
        $errores['imagen'] = $errorImagen;
    }

    if (!$errores) {
        $nuevaImagen = null;
        try {
            $nuevaImagen = $imagenes->guardar($imagenEnviada, false);
            $item = ItemFactory::crear([...$datos, 'id' => $id, 'imagen' => $nuevaImagen ?? $itemActual->getImagen()]);
            if (!$repo->actualizar($item)) {
                throw new RuntimeException('El ítem ya no existe.');
            }
            if ($nuevaImagen !== null) {
                $imagenes->eliminar($itemActual->getImagen());
            }
            aviso('Cambios guardados en el catálogo.');
            redirigir('detalle.php?id=' . $id);
        } catch (PDOException $e) {
            $imagenes->eliminar($nuevaImagen);
            error_log($e->getMessage());
            $errores['sku'] = ($e->errorInfo[1] ?? null) === 1062
                ? 'Este SKU ya existe.' : 'No se pudieron guardar los cambios.';
        } catch (Throwable $e) {
            $imagenes->eliminar($nuevaImagen);
            error_log($e->getMessage());
            $errores['imagen'] = 'No se pudieron guardar los cambios.';
        }
    }
    http_response_code(422);
}

$accion = 'editar.php?id=' . $id;
$textoBoton = 'Guardar cambios';
$imagenActual = $itemActual->getImagen();
$imagenObligatoria = false;
encabezado('Editar ítem');
?>
<div class="page-top"><div><p class="eyebrow">Catálogo</p><h1>Editar ítem</h1><p>Actualiza los datos o reemplaza la imagen actual.</p></div></div>
<?php require __DIR__ . '/_formulario_item.php'; ?>
<?php pie(); ?>
