<?php
// File: views/admin/habitaciones/admin_reserva_confirmar.php

// Se espera recibir $_GET['id'] con el ID de la solicitud
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>ID de solicitud inválido.</p>";
    exit;
}

$solicitudId = intval($_GET['id']);

// Incluir modelo y crear instancia (ajustar ruta si es necesario)
require_once __DIR__ . '/../../../models/AdminReservaModel.php';

// Asumimos que $conn está disponible globalmente o incluir configuración
global $conn;
if (!$conn) {
    echo "<p>Error: conexión a la base de datos no disponible.</p>";
    exit;
}

$model = new AdminReservaModel($conn);
$solicitud = $model->obtenerSolicitudPorId($solicitudId);

if (!$solicitud) {
    echo "<p>Solicitud no encontrada.</p>";
    exit;
}

// Obtener habitaciones disponibles para las fechas y tipo de habitación solicitada
$habitaciones = $model->obtenerHabitacionesDisponibles(
    $solicitud['fecha_desde'],
    $solicitud['fecha_hasta'],
    $solicitud['tipo_habitacion_id']
);

// Para permitir cambiar tipo de habitación y actualizar habitaciones disponibles,
// necesitamos obtener todos los tipos de habitación activos para un select
// Asumimos que existe método para obtener tipos de habitación (puede estar en otro modelo)
require_once __DIR__ . '/../../../models/AdminHabitacionModel.php';
$habitacionModel = new AdminHabitacionModel($conn);
$tiposHabitacion = $habitacionModel->obtenerTiposHabitacionActivos();

?>

<div style="background:#fff; padding:20px; border:1px solid #ccc; max-width:600px; margin:auto;">
    <h3>Confirmar Reserva #<?= htmlspecialchars($solicitud['id']) ?></h3>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($solicitud['nombre']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($solicitud['email']) ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($solicitud['telefono']) ?></p>
    <p><strong>Fechas:</strong> <?= htmlspecialchars($solicitud['fecha_desde']) ?> a <?= htmlspecialchars($solicitud['fecha_hasta']) ?></p>
    <p><strong>Tipo de Habitación Solicitada:</strong> <?= htmlspecialchars($solicitud['tipo_habitacion_id']) ?></p>

    <form id="formConfirmarReserva">
        <input type="hidden" name="solicitud_id" value="<?= htmlspecialchars($solicitud['id']) ?>">

        <label for="tipo_habitacion_select">Tipo de Habitación:</label>
        <select id="tipo_habitacion_select" name="tipo_habitacion_id" required>
            <?php foreach ($tiposHabitacion as $tipo): ?>
                <option value="<?= $tipo['id'] ?>" <?= $tipo['id'] == $solicitud['tipo_habitacion_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tipo['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br><br>

        <label for="habitacion_select">Habitación Disponible:</label>
        <select id="habitacion_select" name="habitacion_id" required>
            <?php if (empty($habitaciones)): ?>
                <option value="">No hay habitaciones disponibles para este tipo y fechas</option>
            <?php else: ?>
                <?php foreach ($habitaciones as $hab): ?>
                    <option value="<?= $hab['id'] ?>">
                        <?= htmlspecialchars($hab['numero'] ?? 'Habitación ' . $hab['id']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <br><br>

        <button type="submit">Confirmar Reserva</button>
        <button type="button" onclick="cerrarModal()">Cancelar</button>
    </form>

    <div id="mensajeConfirmacion" style="margin-top:15px;"></div>
</div>

<script src="/assets/js/AdminReserva.js"></script>

<script>
function cerrarModal() {
    document.getElementById('confirmacionReservaModal').style.display = 'none';
}

// Manejo del cambio de tipo de habitación para actualizar habitaciones disponibles
document.getElementById('tipo_habitacion_select').addEventListener('change', function() {
    const tipoId = this.value;
    const fechaDesde = "<?= $solicitud['fecha_desde'] ?>";
    const fechaHasta = "<?= $solicitud['fecha_hasta'] ?>";

    fetch(`/admin_reserva_habitaciones_disponibles.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&tipo_habitacion_id=${tipoId}`)
        .then(res => res.json())
        .then(data => {
            const habitacionSelect = document.getElementById('habitacion_select');
            habitacionSelect.innerHTML = '';

            if (data.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No hay habitaciones disponibles para este tipo y fechas';
                habitacionSelect.appendChild(option);
            } else {
                data.forEach(hab => {
                    const option = document.createElement('option');
                    option.value = hab.id;
                    option.textContent = hab.numero || `Habitación ${hab.id}`;
                    habitacionSelect.appendChild(option);
                });
            }
        })
        .catch(err => {
            console.error('Error al cargar habitaciones disponibles:', err);
        });
});

// Manejo del submit para confirmar reserva (envío AJAX)
document.getElementById('formConfirmarReserva').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('/admin_reserva_procesar.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        const mensajeDiv = document.getElementById('mensajeConfirmacion');
        if (response.success) {
            mensajeDiv.innerHTML = `<p style="color:green;">${response.mensaje}</p>`;
            // Opcional: refrescar listado o cerrar modal después de un tiempo
            setTimeout(() => {
                cerrarModal();
                location.reload();
            }, 2000);
        } else {
            mensajeDiv.innerHTML = `<p style="color:red;">${response.mensaje}</p>`;
        }
    })
    .catch(err => {
        console.error('Error al procesar confirmación:', err);
    });
});
</script>