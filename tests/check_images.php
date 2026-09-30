<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Servicios\GestorImagenes;

$ruta = tempnam(sys_get_temp_dir(), 'casob_img_');
if ($ruta === false) {
    throw new RuntimeException('No se pudo crear el archivo temporal.');
}

try {
    $gestor = new GestorImagenes(__DIR__ . '/../public/uploads');
    file_put_contents($ruta, 'esto no es una imagen');
    $archivo = ['error' => UPLOAD_ERR_OK, 'tmp_name' => $ruta, 'size' => filesize($ruta), 'name' => 'foto.png'];
    if ($gestor->validar($archivo, true) === null) {
        throw new RuntimeException('Se aceptó texto con nombre de imagen.');
    }

    file_put_contents($ruta, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII='));
    $archivo['size'] = filesize($ruta);
    $archivo['name'] = 'archivo.php';
    if ($gestor->validar($archivo, true) !== null) {
        throw new RuntimeException('Se rechazó una imagen real por su nombre original.');
    }
    $archivo['size'] = 2 * 1024 * 1024 + 1;
    if ($gestor->validar($archivo, true) === null) {
        throw new RuntimeException('Se aceptó una imagen por encima del límite.');
    }
    if ($gestor->validar([], true) === null || $gestor->validar([], false) !== null) {
        throw new RuntimeException('Falló la regla de imagen obligatoria.');
    }
    echo "MIME real, tamaño e imagen obligatoria: OK.\n";
} finally {
    unlink($ruta);
}
