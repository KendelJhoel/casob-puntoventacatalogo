<?php

declare(strict_types=1);

if (getenv('CASOB_TEST_DB') !== '1') {
    fwrite(STDERR, "Configura CASOB_TEST_DB=1 y apunta a una base de prueba antes de ejecutar este script.\n");
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

use App\Infraestructura\Conexion;

function comprobarWeb(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

/** @return array{status:int,body:string,headers:array<int,string>} */
function pedir(string $url, string $ruta, string $metodo = 'GET', array $datos = [], string $cookie = ''): array
{
    $contexto = stream_context_create(['http' => [
        'method' => $metodo,
        'header' => "Cookie: $cookie\r\nContent-Type: application/x-www-form-urlencoded\r\n",
        'content' => $metodo === 'POST' ? http_build_query($datos) : '',
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 10,
    ]]);
    $cuerpo = file_get_contents($url . '/' . $ruta, false, $contexto);
    $cabeceras = $http_response_header ?? [];
    if ($cuerpo === false || !preg_match('~^HTTP/\S+ (\d+)~', $cabeceras[0] ?? '', $estado)) {
        throw new RuntimeException("No se pudo consultar $ruta. Inicia php -S localhost:8000 -t public.");
    }
    return ['status' => (int) $estado[1], 'body' => $cuerpo, 'headers' => $cabeceras];
}

$url = rtrim(getenv('CASOB_TEST_URL') ?: 'http://127.0.0.1:8000', '/');
$pdo = Conexion::crear(require __DIR__ . '/../config/config.php');
$fila = $pdo->query("SELECT id, nombre, precio_base, stock FROM items WHERE sku = 'SKU-002'")->fetch();
comprobarWeb($fila !== false && (int) $fila['stock'] >= 1, 'Falta la semilla SKU-002 con stock.');
$fisico = (int) $fila['id'];
$nombreOriginal = $fila['nombre'];
$precioOriginal = $fila['precio_base'];
$stockOriginal = (int) $fila['stock'];
$digital = (int) $pdo->query("SELECT id FROM items WHERE sku = 'DIG-001'")->fetchColumn();
$servicio = (int) $pdo->query("SELECT id FROM items WHERE sku = 'SRV-001'")->fetchColumn();
comprobarWeb($digital > 0 && $servicio > 0, 'Faltan las semillas digital y servicio.');
$ventasAntes = (int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
$ventaId = null;

try {
    foreach (['index.php', 'catalogo.php', 'crear.php', 'editar.php?id=' . $fisico,
        'detalle.php?id=' . $fisico, 'ventas.php', 'reporte.php'] as $pagina) {
        comprobarWeb(pedir($url, $pagina)['status'] === 200, "No abrió $pagina.");
    }

    $formulario = pedir($url, 'venta_nueva.php');
    comprobarWeb($formulario['status'] === 200, 'No abrió el formulario de venta.');
    preg_match('/name="_csrf" value="([^"]+)"/', $formulario['body'], $token);
    comprobarWeb(isset($token[1]), 'Falta el token CSRF.');
    $cookie = '';
    foreach ($formulario['headers'] as $cabecera) {
        if (stripos($cabecera, 'Set-Cookie: PHPSESSID=') === 0) {
            $cookie = explode(';', substr($cabecera, 12))[0];
            break;
        }
    }
    comprobarWeb($cookie !== '', 'Falta la cookie de sesión.');

    $cliente = 'Prueba web <script>alert(1)</script>';
    $lineas = [$fisico => 1, $digital => 1, $servicio => 1];
    comprobarWeb(pedir($url, 'venta_nueva.php', 'POST', ['_csrf' => 'incorrecto', 'cliente' => $cliente, 'cantidad' => $lineas], $cookie)['status'] === 403, 'Se aceptó un CSRF inválido.');
    comprobarWeb(pedir($url, 'venta_nueva.php', 'POST', ['_csrf' => $token[1], 'cliente' => '', 'cantidad' => $lineas], $cookie)['status'] === 422, 'Se aceptó cliente vacío.');
    comprobarWeb(pedir($url, 'venta_nueva.php', 'POST', ['_csrf' => $token[1], 'cliente' => $cliente, 'cantidad' => [$fisico => 0]], $cookie)['status'] === 422, 'Se aceptó venta vacía.');
    comprobarWeb((int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn() === $ventasAntes, 'Una petición inválida creó una venta.');

    $respuesta = pedir($url, 'venta_nueva.php', 'POST', ['_csrf' => $token[1], 'cliente' => $cliente, 'cantidad' => $lineas], $cookie);
    comprobarWeb($respuesta['status'] === 303, 'La venta válida no aplicó PRG.');
    foreach ($respuesta['headers'] as $cabecera) {
        if (preg_match('~^Location: venta\.php\?id=(\d+)~i', $cabecera, $enlace)) {
            $ventaId = (int) $enlace[1];
        }
    }
    comprobarWeb($ventaId !== null, 'La venta no redirigió a su ticket.');
    comprobarWeb((int) $pdo->query("SELECT COUNT(*) FROM venta_detalles WHERE venta_id = $ventaId")->fetchColumn() === 3, 'La venta no guardó las tres líneas.');
    comprobarWeb((int) $pdo->query("SELECT stock FROM items WHERE id = $fisico")->fetchColumn() === $stockOriginal - 1, 'No descontó el stock.');

    $ticket = pedir($url, 'venta.php?id=' . $ventaId);
    comprobarWeb($ticket['status'] === 200 && str_contains($ticket['body'], '&lt;script&gt;')
        && !str_contains($ticket['body'], '<script>alert(1)</script>')
        && str_contains($ticket['body'], $nombreOriginal), 'El ticket perdió datos o no escapó el cliente.');
    comprobarWeb(str_contains(pedir($url, 'ventas.php')['body'], 'venta.php?id=' . $ventaId), 'La venta no aparece en el historial.');
    comprobarWeb(str_contains(pedir($url, 'reporte.php')['body'], 'SKU-002'), 'La venta no aparece en el reporte diario.');

    $pdo->prepare('UPDATE items SET nombre = ?, precio_base = ? WHERE id = ?')->execute(['Nombre cambiado', '1.00', $fisico]);
    $historico = pedir($url, 'venta.php?id=' . $ventaId)['body'];
    comprobarWeb(str_contains($historico, $nombreOriginal) && !str_contains($historico, 'Nombre cambiado'), 'Editar el catálogo alteró el ticket histórico.');

    $stockActual = (int) $pdo->query("SELECT stock FROM items WHERE id = $fisico")->fetchColumn();
    $fallida = pedir($url, 'venta_nueva.php', 'POST', ['_csrf' => $token[1], 'cliente' => 'Sin stock', 'cantidad' => [$digital => 1, $fisico => $stockActual + 1]], $cookie);
    comprobarWeb($fallida['status'] === 422 && (int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn() === $ventasAntes + 1
        && (int) $pdo->query("SELECT stock FROM items WHERE id = $fisico")->fetchColumn() === $stockActual, 'La sobreventa alteró la base.');
    echo "Páginas, venta, CSRF, validación, PRG, ticket, reporte, historial, escape y rollback: OK.\n";
} finally {
    if ($ventaId !== null) {
        $pdo->prepare('DELETE FROM ventas WHERE id = ?')->execute([$ventaId]);
    }
    $pdo->prepare('UPDATE items SET nombre = ?, precio_base = ?, stock = ? WHERE id = ?')
        ->execute([$nombreOriginal, $precioOriginal, $stockOriginal, $fisico]);
}
