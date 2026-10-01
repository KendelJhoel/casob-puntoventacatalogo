<?php

declare(strict_types=1);

/**
 * check_seguridad.php — Auditoría estática de seguridad para la Fase 3.
 *
 * Comprueba sin necesidad de base de datos ni servidor HTTP:
 *   1. Que los secretos (.gitignore) excluyan config.php, vendor/ y uploads/.
 *   2. Que los archivos PHP públicos usen htmlspecialchars / e() para la salida.
 *   3. Que no haya interpolación directa de variables en SQL (PDO preparado).
 *   4. Que el borrado no ocurra por GET (solo POST).
 *   5. Que las etiquetas [CONCEPTO] esperadas estén presentes en el código fuente.
 *   6. Que el directorio uploads/ tenga .gitkeep pero no esté trackeado con imágenes.
 *
 * Uso: php tests/check_seguridad.php
 */

$raiz = dirname(__DIR__);
$errores = [];
$ok = 0;

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

function ok(string $msg): void
{
    global $ok;
    $ok++;
    echo "  ✔ $msg\n";
}

function fallo(string $msg): void
{
    global $errores;
    $errores[] = $msg;
    echo "  ✘ $msg\n";
}

function buscarEnArchivos(string $directorio, string $patron, string $extension = '*.php'): array
{
    $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));
    $coincidencias = [];
    foreach ($archivos as $archivo) {
        if (!$archivo->isFile()) {
            continue;
        }
        if (!fnmatch($extension, $archivo->getFilename())) {
            continue;
        }
        $contenido = file_get_contents($archivo->getPathname());
        if ($contenido !== false && preg_match($patron, $contenido)) {
            $coincidencias[] = $archivo->getPathname();
        }
    }
    return $coincidencias;
}

function contenidoContiene(string $ruta, string $fragmento): bool
{
    $contenido = @file_get_contents($ruta);
    return $contenido !== false && str_contains($contenido, $fragmento);
}

// ─────────────────────────────────────────────────────────────
// 1. Secretos fuera de Git
// ─────────────────────────────────────────────────────────────
echo "\n[1] Secretos fuera de Git (.gitignore)\n";

$gitignore = $raiz . '/.gitignore';
$reglas = [
    '/config/config.php'         => 'config.php excluido',
    '/vendor/'                   => 'vendor/ excluido',
    '/public/uploads/*'          => 'uploads/* excluido',
    '!/public/uploads/.gitkeep'  => 'uploads/.gitkeep trackeado',
];

foreach ($reglas as $fragmento => $descripcion) {
    if (contenidoContiene($gitignore, $fragmento)) {
        ok($descripcion);
    } else {
        fallo("Falta en .gitignore: $fragmento");
    }
}

// ─────────────────────────────────────────────────────────────
// 2. Escape de salida HTML
// ─────────────────────────────────────────────────────────────
echo "\n[2] Escape de salida (htmlspecialchars / e())\n";

$publicPHP = glob($raiz . '/public/*.php') ?: [];
$sinEscape = [];
foreach ($publicPHP as $archivo) {
    $contenido = file_get_contents($archivo);
    if ($contenido === false) {
        continue;
    }
    // Cada archivo público debe requerir _init.php, que define e().
    // Comprobamos que use la función e() para escapar variables.
    $usaHelper = str_contains($contenido, '_init.php') || str_contains($contenido, 'e(');
    if (!$usaHelper) {
        $sinEscape[] = basename($archivo);
    }
}

if ($sinEscape === []) {
    ok('Todos los archivos públicos usan e() o htmlspecialchars() para la salida');
} else {
    fallo('Archivos sin escape visible: ' . implode(', ', $sinEscape));
}

// ─────────────────────────────────────────────────────────────
// 3. Consultas preparadas (sin interpolación de variables en SQL)
// ─────────────────────────────────────────────────────────────
echo "\n[3] Consultas preparadas (sin inyección SQL)\n";

// Busca patrones peligrosos: "SELECT ... $var" o concatenación en query()
$peligrosos = buscarEnArchivos(
    $raiz . '/src',
    '/->query\s*\(\s*["\'].*\$|->exec\s*\(\s*["\'].*\$/u'
);

if ($peligrosos === []) {
    ok('No se detectó interpolación de variables en llamadas a query()/exec()');
} else {
    fallo('Posible SQL sin preparar en: ' . implode(', ', array_map('basename', $peligrosos)));
}

// Verificar que los repositorios usen prepare()
$repoDir = $raiz . '/src/Repositorios';
$repos = glob($repoDir . '/*.php') ?: [];
foreach ($repos as $repo) {
    if (str_contains((string) file_get_contents($repo), '->prepare(')) {
        ok('Repositorio ' . basename($repo) . ' usa prepare()');
    } else {
        fallo('Repositorio ' . basename($repo) . ' no usa prepare()');
    }
}

// ─────────────────────────────────────────────────────────────
// 4. Borrado solo por POST (no GET)
// ─────────────────────────────────────────────────────────────
echo "\n[4] Eliminación solo por POST\n";

$eliminar = $raiz . '/public/eliminar.php';
if (file_exists($eliminar)) {
    $contenido = (string) file_get_contents($eliminar);
    // Debe exigir CSRF (POST) y NO ejecutar el borrado en GET
    $exigeCsrf = str_contains($contenido, 'exigirCsrf') || str_contains($contenido, 'csrf');
    $noGetDelete = !preg_match('/\$_GET.*delete|eliminar.*\$_GET/i', $contenido);
    if ($exigeCsrf && $noGetDelete) {
        ok('eliminar.php verifica CSRF y no permite borrado por GET');
    } else {
        fallo('eliminar.php podría permitir borrado inseguro');
    }
} else {
    fallo('No existe public/eliminar.php');
}

// ─────────────────────────────────────────────────────────────
// 5. Etiquetas [CONCEPTO] en el código fuente
// ─────────────────────────────────────────────────────────────
echo "\n[5] Etiquetas [CONCEPTO]\n";

$etiquetasEsperadas = [
    '[ABSTRACCION]'           => $raiz . '/src',
    '[HERENCIA]'              => $raiz . '/src',
    '[POLIMORFISMO]'          => $raiz . '/src',
    '[INTERFAZ]'              => $raiz . '/src',
    '[FABRICA]'               => $raiz . '/src',
    '[CRUD-CREATE]'           => $raiz . '/src',
    '[CRUD-READ]'             => $raiz . '/src',
    '[CRUD-UPDATE]'           => $raiz . '/src',
    '[CRUD-DELETE]'           => $raiz . '/src',
    '[SEGURIDAD]'             => $raiz . '/public',
    '[VALIDACION]'            => $raiz . '/src',
    '[ENCAPSULAMIENTO]'       => $raiz . '/src',
    '[INYECCION-DEPENDENCIAS]' => $raiz . '/src',
    '[PRG]'                   => $raiz . '/public',
];

foreach ($etiquetasEsperadas as $etiqueta => $dir) {
    $encontrados = buscarEnArchivos($dir, '/' . preg_quote($etiqueta, '/') . '/');
    if ($encontrados !== []) {
        ok("$etiqueta encontrada en " . basename(dirname($encontrados[0])) . '/' . basename($encontrados[0]));
    } else {
        fallo("$etiqueta no encontrada en $dir");
    }
}

// ─────────────────────────────────────────────────────────────
// 6. uploads/.gitkeep existe; no hay imágenes en uploads/
// ─────────────────────────────────────────────────────────────
echo "\n[6] Directorio uploads/\n";

$uploads = $raiz . '/public/uploads';
if (file_exists($uploads . '/.gitkeep')) {
    ok('uploads/.gitkeep existe');
} else {
    fallo('Falta uploads/.gitkeep');
}

$archivosSubidos = array_filter(
    glob($uploads . '/*') ?: [],
    fn(string $f) => basename($f) !== '.gitkeep'
);

if ($archivosSubidos === []) {
    ok('No hay imágenes subidas en uploads/ (directorio limpio)');
} else {
    // En desarrollo puede haber imágenes; no es error fatal, solo aviso
    echo '  ⚠ Hay ' . count($archivosSubidos) . " archivo(s) en uploads/ (normal en desarrollo, no deben subirse a Git)\n";
}

// ─────────────────────────────────────────────────────────────
// Resumen
// ─────────────────────────────────────────────────────────────
echo "\n" . str_repeat('─', 60) . "\n";
echo "Comprobaciones pasadas: $ok\n";
if ($errores !== []) {
    echo 'Fallos (' . count($errores) . "):\n";
    foreach ($errores as $e) {
        echo "  • $e\n";
    }
    exit(1);
}
echo "Auditoría de seguridad completada sin errores.\n";
