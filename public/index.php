<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$items = $repo->listar();
encabezado('Inicio');
?>
<section class="hero">
    <div>
        <p class="eyebrow">Administración del catálogo</p>
        <h1>Tu punto de venta, en un solo lugar.</h1>
        <p>Organiza productos físicos, archivos digitales y servicios. Consulta el catálogo y cobra ventas con información guardada en MySQL.</p>
        <div class="actions">
            <a class="button" href="catalogo.php">Ver catálogo <span aria-hidden="true">↗</span></a>
            <a class="button button-ghost" href="crear.php">Agregar ítem</a>
            <a class="button button-ghost" href="venta_nueva.php">Cobrar venta</a>
        </div>
    </div>
    <div class="hero-stat" aria-label="Resumen del catálogo">
        <span class="stat-number"><?= count($items) ?></span>
        <span>ítems en el catálogo</span>
        <small>Datos actuales de la base</small>
    </div>
</section>

<section class="section-block" aria-labelledby="tipos-titulo">
    <div class="section-heading"><div><p class="eyebrow">Tres formas de vender</p><h2 id="tipos-titulo">Todo en el mismo catálogo</h2></div></div>
    <div class="feature-grid">
        <article class="feature-card"><span class="feature-icon" aria-hidden="true">▣</span><h3>Productos físicos</h3><p>Existencias controladas y precio final con envío.</p></article>
        <article class="feature-card"><span class="feature-icon" aria-hidden="true">◈</span><h3>Productos digitales</h3><p>Enlaces de descarga y descuento automático.</p></article>
        <article class="feature-card"><span class="feature-icon" aria-hidden="true">◇</span><h3>Servicios</h3><p>Agenda por fecha y tarifa de gestión.</p></article>
    </div>
</section>
<?php pie(); ?>
