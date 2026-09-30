<?php

declare(strict_types=1);

use App\Modelos\ItemFactory;
use App\Servicios\GestorImagenes;
use App\Servicios\Validador;

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$datos = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : ['tipo' => 'fisico'];
$errores = [];
$imagenes = new GestorImagenes(__DIR__ . '/uploads');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $errores = Validador::item($datos);
    $imagenEnviada = $_FILES['imagen'] ?? [];
    $errorImagen = $imagenes->validar($imagenEnviada, true);
    if ($errorImagen !== null) {
        $errores['imagen'] = $errorImagen;
    }

    if (!$errores) {
        $nombreImagen = null;
        try {
            $nombreImagen = $imagenes->guardar($imagenEnviada);
            $item = ItemFactory::crear([...$datos, 'imagen' => $nombreImagen]);
            $id = $repo->crear($item);
            aviso('Ítem agregado al catálogo.');
            redirigir('detalle.php?id=' . $id);
        } catch (PDOException $e) {
            $imagenes->eliminar($nombreImagen);
            error_log($e->getMessage());
            $errores['sku'] = ($e->errorInfo[1] ?? null) === 1062
                ? 'Este SKU ya existe.' : 'No se pudo guardar el ítem. Inténtalo de nuevo.';
        } catch (Throwable $e) {
            $imagenes->eliminar($nombreImagen);
            error_log($e->getMessage());
            $errores['imagen'] = 'No se pudo guardar el ítem. Inténtalo de nuevo.';
        }
    }
    http_response_code(422);
}

$accion = 'crear.php';
$textoBoton = 'Guardar ítem';
$imagenActual = null;
$imagenObligatoria = true;
encabezado('Nuevo ítem');
?>
<div class="page-top"><div><p class="eyebrow">Catálogo</p><h1>Nuevo ítem</h1><p>Completa los datos y elige una imagen para publicar el ítem.</p></div></div>
<?php require __DIR__ . '/_formulario_item.php'; ?>
<?php pie(); ?>
