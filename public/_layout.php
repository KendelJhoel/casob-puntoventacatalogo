<?php

declare(strict_types=1);

function encabezado(string $titulo): void
{
    $aviso = $_SESSION['_aviso'] ?? null;
    unset($_SESSION['_aviso']);
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($titulo) ?> · Caso B</title>
        <link rel="stylesheet" href="assets/styles.css">
    </head>
    <body>
        <header class="site-header">
            <div class="container header-inner">
                <a class="brand" href="index.php" aria-label="Caso B, ir al inicio"><span class="brand-mark">B</span><span>CASO B <small>Punto de venta</small></span></a>
                <nav aria-label="Navegación principal">
                    <a href="index.php">Inicio</a>
                    <a href="catalogo.php">Catálogo</a>
                    <a class="nav-action" href="crear.php">Nuevo ítem</a>
                </nav>
            </div>
        </header>
        <main class="container">
            <?php if (is_array($aviso)): ?>
                <p class="alert <?= $aviso['tipo'] === 'error' ? 'alert-error' : 'alert-ok' ?>" role="status"><?= e($aviso['mensaje']) ?></p>
            <?php endif; ?>
    <?php
}

function pie(): void
{
    ?>
        </main>
        <footer class="site-footer"><div class="container">Caso B · Catálogo y punto de venta</div></footer>
    </body>
    </html>
    <?php
}
