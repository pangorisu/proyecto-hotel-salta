<?php
// views/admin/habitaciones/listado.php
// Variables disponibles: $habitaciones, $totalPages, $page

// Mostrar mensaje si existe
function mostrarMensaje($msg) {
    $mensajes = [
        'created' => 'Habitación creada correctamente.',
        'updated' => 'Habitación actualizada correctamente.',
        'deleted' => 'Habitación eliminada correctamente.',
        'img_deleted' => 'Imagen eliminada correctamente.',
    ];
    return $mensajes[$msg] ?? htmlspecialchars($msg);
}
?>

<div class="container mt-4">
    <h2>Listado de Habitaciones</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= mostrarMensaje($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <a href="crear.php" class="btn btn-primary mb-3">Nueva Habitación</a>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Número</th>
                <th>Tipo</th>
                <th>Capacidad</th>
                <th>Precio por Noche</th>
                <th>Estado</th>
                <th>Piso</th>
                <th>Metros Cuadrados</th>
                <th>Imagen Principal</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($habitaciones)): ?>
                <tr><td colspan="10" class="text-center">No hay habitaciones.</td></tr>
            <?php else: ?>
                <?php foreach ($habitaciones as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['id']) ?></td>
                        <td><?= htmlspecialchars($h['numero']) ?></td>
                        <td><?= htmlspecialchars($h['tipo_nombre'] ?? '') ?></td>
                        <td><?= htmlspecialchars($h['capacidad']) ?></td>
                        <td>$<?= number_format($h['precio_noche'], 2) ?></td>
                        <td><?= ucfirst(htmlspecialchars($h['estado'])) ?></td>
                        <td><?= htmlspecialchars($h['piso']) ?></td>
                        <td><?= htmlspecialchars($h['metros_cuadrados']) ?></td>
                        <td>
                            <?php if (!empty($h['imagen_principal'])): ?>
                                <img src="<?= htmlspecialchars($h['imagen_principal']) ?>" alt="Imagen" style="max-width: 100px; border-radius: 4px;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="editar.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="eliminar.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta habitación?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación simple -->
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>