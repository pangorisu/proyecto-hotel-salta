<?php
// File: views/admin/habitaciones/admin_reserva_procesar.php

// Este script procesa la confirmación de reserva vía POST y devuelve JSON

header('Content-Type: application/json; charset=utf-8');

try {
    // Incluir configuración y conexión
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../config/database.php';
    require_once __DIR__ . '/../../../models/AdminReservaModel.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
        exit;
    }

    // Recoger y sanitizar datos
    $solicitudId = isset($_POST['solicitud_id']) ? intval($_POST['solicitud_id']) : null;
    $habitacionId = isset($_POST['habitacion_id']) ? intval($_POST['habitacion_id']) : null;

    if (!$solicitudId || !$habitacionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
        exit;
    }

    $model = new AdminReservaModel($conn);

    // Confirmar reserva
    $model->confirmarReserva($solicitudId, $habitacionId);

    // TODO: Enviar correo al cliente (puedes agregar aquí la función de envío)

    echo json_encode(['success' => true, 'mensaje' => 'Reserva confirmada correctamente.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error al confirmar la reserva: ' . $e->getMessage()]);
}