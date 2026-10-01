<?php

declare(strict_types=1);

/** @var array<string,mixed> $datos */
/** @var array<string,string> $errores */
/** @var string $accion */
/** @var string $textoBoton */
/** @var ?string $imagenActual */
/** @var bool $imagenObligatoria */

$tipos = ['fisico' => 'Producto físico', 'digital' => 'Producto digital', 'servicio' => 'Servicio'];
?>
<?php if ($errores): ?>
    <div class="alert alert-error" role="alert"><strong>Revisa el formulario.</strong> Los campos señalados necesitan corrección.</div>
<?php endif; ?>
<form class="panel" method="post" action="<?= e($accion) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
    <div class="form-grid">
        <div class="field">
            <label for="sku">SKU</label>
            <input id="sku" name="sku" type="text" required maxlength="40" pattern="[A-Za-z0-9_\-]+" value="<?= e($datos['sku'] ?? '') ?>" aria-invalid="<?= isset($errores['sku']) ? 'true' : 'false' ?>" aria-describedby="sku-ayuda<?= isset($errores['sku']) ? ' sku-error' : '' ?>">
            <small id="sku-ayuda">Letras, números y guiones. Debe ser único.</small>
            <?php if (isset($errores['sku'])): ?><span class="error" id="sku-error"><?= e($errores['sku']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" required aria-invalid="<?= isset($errores['tipo']) ? 'true' : 'false' ?>">
                <option value="">Selecciona un tipo</option>
                <?php foreach ($tipos as $valor => $etiqueta): ?>
                    <option value="<?= e($valor) ?>" <?= ($datos['tipo'] ?? 'fisico') === $valor ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['tipo'])): ?><span class="error"><?= e($errores['tipo']) ?></span><?php endif; ?>
        </div>
        <div class="field field-full">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" type="text" required maxlength="150" value="<?= e($datos['nombre'] ?? '') ?>" aria-invalid="<?= isset($errores['nombre']) ? 'true' : 'false' ?>">
            <?php if (isset($errores['nombre'])): ?><span class="error"><?= e($errores['nombre']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="precio_base">Precio base ($)</label>
            <input id="precio_base" name="precio_base" type="number" required min="0.01" max="99999999.99" step="0.01" value="<?= e($datos['precio_base'] ?? '') ?>" aria-invalid="<?= isset($errores['precio_base']) ? 'true' : 'false' ?>">
            <?php if (isset($errores['precio_base'])): ?><span class="error"><?= e($errores['precio_base']) ?></span><?php endif; ?>
        </div>
        <?php foreach (array_keys($tipos) as $tipo): ?>
            <?php $campo = App\Modelos\ItemFactory::campoPropio($tipo); ?>
            <?php $activo = ($datos['tipo'] ?? 'fisico') === $tipo; ?>
            <div class="field" data-campo-tipo="<?= e($tipo) ?>" <?= $activo ? '' : 'hidden' ?>>
                <label for="<?= e($campo['campo']) ?>"><?= e($campo['etiqueta']) ?></label>
                <input id="<?= e($campo['campo']) ?>" name="<?= e($campo['campo']) ?>" type="<?= e($campo['tipo']) ?>" value="<?= e($datos[$campo['campo']] ?? '') ?>" <?= $campo['restricciones'] ?> <?= $activo ? 'required' : 'disabled' ?> aria-invalid="<?= isset($errores[$campo['campo']]) ? 'true' : 'false' ?>">
                <?php if (isset($errores[$campo['campo']])): ?><span class="error"><?= e($errores[$campo['campo']]) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="field field-full">
            <label for="imagen">Imagen <?= $imagenObligatoria ? '(obligatoria)' : '(opcional)' ?></label>
            <?php if ($imagenActual): ?><img class="preview" src="<?= e(rutaImagen($imagenActual)) ?>" alt="Imagen actual del ítem"><?php endif; ?>
            <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" <?= $imagenObligatoria ? 'required' : '' ?> aria-invalid="<?= isset($errores['imagen']) ? 'true' : 'false' ?>">
            <small>JPG, PNG o WEBP, hasta 2 MB.</small>
            <?php if (isset($errores['imagen'])): ?><span class="error"><?= e($errores['imagen']) ?></span><?php endif; ?>
        </div>
    </div>
    <div class="form-actions"><button class="button" type="submit"><?= e($textoBoton) ?></button><a class="button button-ghost" href="catalogo.php">Cancelar</a></div>
</form>
<script src="assets/forms.js" defer></script>
