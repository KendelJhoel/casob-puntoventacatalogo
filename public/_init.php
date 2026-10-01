<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infraestructura\Conexion;
use App\Repositorios\RepositorioItems;

session_start();

function e(mixed $valor): string
{
    return htmlspecialchars(is_scalar($valor) ? (string) $valor : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); // [SEGURIDAD]
}

function csrf(): string
{
    return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
}

// [SEGURIDAD] Comparación del token de sesión antes de modificar datos.
function exigirCsrf(): void
{
    if (!isset($_POST['_csrf']) || !is_string($_POST['_csrf']) || !hash_equals(csrf(), $_POST['_csrf'])) {
        http_response_code(403);
        exit('Solicitud inválida. Recarga la página y vuelve a intentarlo.');
    }
}

function aviso(string $mensaje, string $tipo = 'ok'): void
{
    $_SESSION['_aviso'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
}

function redirigir(string $ruta): never
{
    header('Location: ' . $ruta, true, 303); // [PRG]
    exit;
}

function rutaImagen(?string $nombre): string
{
    return $nombre ? 'uploads/' . rawurlencode($nombre) : 'assets/sin-imagen.svg';
}

$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    http_response_code(503);
    exit('Falta config/config.php. Copia config/config.example.php y completa la conexión local.');
}

try {
    $pdo = Conexion::crear(require $configPath);
    $repo = new RepositorioItems($pdo);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('No se pudo conectar con la base de datos. Revisa la configuración local.');
}
