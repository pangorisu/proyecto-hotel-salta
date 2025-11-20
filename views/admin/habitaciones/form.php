<style>
    .fade {
    opacity: 1 !important;
    } 
</style>

<?php
// views/admin/habitaciones/form.php

// Funciones auxiliares para mostrar valores y errores
function val($field, $lang = null) {
    global $data;
    if ($lang) {
        return htmlspecialchars($data[$field][$lang] ?? '');
    }
    return htmlspecialchars($data[$field] ?? '');
}
function err($field) {
    global $errores;
    return $errores[$field] ?? '';
}
?>

<div class="container mt-4">
    <h2><?= isset($data['id']) ? 'Editar Habitación' : 'Nueva Habitación' ?></h2>

    <?php if (!empty($errores['general'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="index.php?controller=habitacionesAdmin&action=<?= isset($data['id']) ? 'update' : 'store' ?>">
        <?php if (isset($data['id'])): ?>
            <input type="hidden" name="id" value="<?= (int)$data['id'] ?>">
        <?php endif; ?>

        <div class="form-group mb-3">
            <label for="numero">Número</label>
            <input type="text" name="numero" id="numero" class="form-control" value="<?= val('numero') ?>" required>
            <small class="text-danger"><?= err('numero') ?></small>
        </div>

        <div class="form-group mb-3">
            <label for="tipo_habitacion">Tipo de Habitación</label>
            <select name="tipo_habitacion" id="tipo_habitacion" class="form-control" required>
                <option value="">Seleccione...</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?= $tipo['tipo'] ?>" <?= (val('tipo_habitacion') == $tipo['tipo']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-danger"><?= err('tipo_habitacion') ?></small>
        </div>

        <div class="form-group mb-3">
            <label for="capacidad">Capacidad</label>
            <input type="number" name="capacidad" id="capacidad" class="form-control" value="<?= val('capacidad') ?>" min="1" required>
            <small class="text-danger"><?= err('capacidad') ?></small>
        </div>

        <div class="form-group mb-3">
            <label for="precio_noche">Precio por Noche</label>
            <input type="number" step="0.01" name="precio_noche" id="precio_noche" class="form-control" value="<?= val('precio_noche') ?>" min="0" required>
            <small class="text-danger"><?= err('precio_noche') ?></small>
        </div>

        <div class="form-group mb-3">
            <label for="estado">Estado</label>
            <select name="estado" id="estado" class="form-control" required>
                <?php
                $estados = ['cerrada', 'mantenimiento', 'ocupada', 'disponible'];
                foreach ($estados as $estado):
                ?>
                    <option value="<?= $estado ?>" <?= (val('estado') == $estado) ? 'selected' : '' ?>><?= ucfirst($estado) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-danger"><?= err('estado') ?></small>
        </div>

        <div class="form-group mb-3">
            <label for="piso">Piso</label>
            <input type="number" name="piso" id="piso" class="form-control" value="<?= val('piso') ?>">
        </div>

        <div class="form-group mb-3">
            <label for="metros_cuadrados">Metros Cuadrados</label>
            <input type="number" step="0.01" name="metros_cuadrados" id="metros_cuadrados" class="form-control" value="<?= val('metros_cuadrados') ?>">
        </div>

        <!-- Imagen principal -->
        <div class="form-group mb-3">
            <label for="imagen_principal">Imagen Principal (JPG o PNG)</label>
            <input type="file" name="imagen_principal" id="imagen_principal" accept="image/jpeg,image/png" <?= isset($data['id']) ? '' : 'required' ?>>
            <small class="text-danger"><?= err('imagen_principal') ?></small>
            <?php if (!empty($data['imagen_principal'])): ?>
                <div class="mt-2">
                    <img src="<?= htmlspecialchars($data['imagen_principal']) ?>" alt="Imagen Principal" style="max-width: 200px; border-radius: 4px;">
                    <input type="hidden" name="imagen_principal_actual" value="<?= htmlspecialchars($data['imagen_principal']) ?>">
                </div>
            <?php endif; ?>
        </div>

        <!-- Traducciones -->
        <h4>Traducciones</h4>
        <ul class="nav nav-tabs" id="langTabs" role="tablist">
            <?php foreach ($idiomas as $i => $lang): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" id="tab-<?= $lang ?>" data-bs-toggle="tab" data-bs-target="#content-<?= $lang ?>" type="button" role="tab" aria-controls="content-<?= $lang ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                        <?= $lang ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="tab-content border border-top-0 p-3">
            <?php foreach ($idiomas as $i => $lang): ?>
                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="content-<?= $lang ?>" role="tabpanel" aria-labelledby="tab-<?= $lang ?>">
                    <div class="form-group mb-3">
                        <label for="nombre_<?= $lang ?>">Nombre (<?= $lang ?>)</label>
                        <input type="text" name="nombre[<?= $lang ?>]" id="nombre_<?= $lang ?>" class="form-control" value="<?= val('nombre', $lang) ?>" required>
                        <small class="text-danger"><?= err("nombre_$lang") ?></small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="titulo_<?= $lang ?>">Título (<?= $lang ?>)</label>
                        <input type="text" name="titulo[<?= $lang ?>]" id="titulo_<?= $lang ?>" class="form-control" value="<?= val('titulo', $lang) ?>" required>
                        <small class="text-danger"><?= err("titulo_$lang") ?></small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="resumen_<?= $lang ?>">Resumen (<?= $lang ?>)</label>
                        <textarea name="resumen[<?= $lang ?>]" id="resumen_<?= $lang ?>" class="form-control" rows="2"><?= val('resumen', $lang) ?></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="descripcion_<?= $lang ?>">Descripción (<?= $lang ?>)</label>
                        <textarea name="descripcion[<?= $lang ?>]" id="descripcion_<?= $lang ?>" class="form-control" rows="5"><?= val('descripcion', $lang) ?></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="imagen_url_<?= $lang ?>">URL Imagen (<?= $lang ?>)</label>
                        <input type="text" name="imagen_url[<?= $lang ?>]" id="imagen_url_<?= $lang ?>" class="form-control" value="<?= val('imagen_url', $lang) ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label for="imagen_alt_<?= $lang ?>">Alt Imagen (<?= $lang ?>)</label>
                        <input type="text" name="imagen_alt[<?= $lang ?>]" id="imagen_alt_<?= $lang ?>" class="form-control" value="<?= val('imagen_alt', $lang) ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Imágenes adicionales -->
        <div class="form-group mb-3 mt-4">
            <label for="imagenes_adicionales">Imágenes Adicionales (JPG o PNG, múltiples)</label>
            <input type="file" name="imagenes_adicionales[]" id="imagenes_adicionales" accept="image/jpeg,image/png" multiple>
        </div>

        <!-- Mostrar imágenes adicionales existentes con opción a eliminar -->
        <?php if (!empty($data['imagenes'])): ?>
            <div class="mb-3">
                <label>Imágenes existentes:</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($data['imagenes'] as $img): ?>
                        <div style="position: relative; width: 150px;">
                            <img src="<?= htmlspecialchars($img['ruta_imagen']) ?>" alt="<?= htmlspecialchars($img['descripcion']) ?>" style="width: 100%; border-radius: 4px;">
                            <a href="eliminarImagen.php?id=<?= $img['id'] ?>&habitacion_id=<?= $data['id'] ?>" 
                               onclick="return confirm('¿Eliminar esta imagen?');"
                               style="position: absolute; top: 5px; right: 5px; color: red; font-weight: bold; text-decoration: none;">X</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary"><?= isset($data['id']) ? 'Actualizar' : 'Crear' ?></button>
        <a href="listado.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<!-- Bootstrap JS para tabs (si no está incluido) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>