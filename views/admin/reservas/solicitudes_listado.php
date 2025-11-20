<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); // Muestra todos los errores, advertencias y avisos
ini_set('log_errors', 1);
// File: views/admin/solicitudes_listado.php

// Asegurarse que $solicitudes y $estado estén definidos
$estado = $estado ?? 'pendiente';
?>
<div class="row">
  <div class="col-md-12">
    <h3>Listado de Solicitudes de Reserva</h3>

<form method="GET" action="">
    <label for="estadoFiltro">Filtrar por estado:</label>
    <select name="estado" id="estadoFiltro" onchange="this.form.submit()">
        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
        <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
    </select>
</form>

<table class="table table-striped table-bordered" id="tablaUsuarios">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Fecha Desde</th>
            <th>Fecha Hasta</th>
            <th>Tipo Habitación</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($solicitudes)): ?>
            <tr>
                <td colspan="9" style="text-align:center;">No se encontraron solicitudes para el filtro seleccionado.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                    <td><?= htmlspecialchars($solicitud['id']) ?></td>
                    <td><?= htmlspecialchars($solicitud['nombre']) ?></td>
                    <td><?= htmlspecialchars($solicitud['email']) ?></td>
                    <td><?= htmlspecialchars($solicitud['telefono']) ?></td>
                    <td><?= htmlspecialchars($solicitud['fecha_desde']) ?></td>
                    <td><?= htmlspecialchars($solicitud['fecha_hasta']) ?></td>
                    <td><?= htmlspecialchars($solicitud['tipo_habitacion_id']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($solicitud['estado'])) ?></td>
                    <td>
                        <?php if ($solicitud['estado'] === 'pendiente'): ?>
                            <button class="btn btn-primary"  type="button" onclick="abrirConfirmacion(<?= $solicitud['id'] ?>)">Confirmar</button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
  </div>
</div>
<!-- Contenedor para el modal o formulario de confirmación -->
<div id="confirmacionReservaModal" style="display:none;"></div>

<script>
function abrirConfirmacion(solicitudId) {
    // Cargar formulario de confirmación vía AJAX
    fetch('views/admin/habitaciones/admin_reserva_confirmar.php?id=' + solicitudId)
        .then(response => response.text())
        .then(html => {
            const modal = document.getElementById('confirmacionReservaModal');
            modal.innerHTML = html;
            modal.style.display = 'block';
        })
        .catch(error => {
            alert('Error al cargar el formulario de confirmación.');
            console.error(error);
        });
}
</script>