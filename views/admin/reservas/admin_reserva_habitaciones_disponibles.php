<?php
// File: views/admin/habitaciones/admin_reserva_habitaciones_disponibles.php

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../config/database.php';
    require_once __DIR__ . '/../../../models/AdminReservaModel.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Validar método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
        exit;
    }

    // Recoger y sanitizar parámetros
    $fechaDesde = $_GET['fecha_desde'] ?? null;
    $fechaHasta = $_GET['fecha_hasta'] ?? null;
    $tipoHabitacionId = isset($_GET['tipo_habitacion_id']) ? intval($_GET['tipo_habitacion_id']) : null;

    if (!$fechaDesde || !$fechaHasta || !$tipoHabitacionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensaje' => 'Parámetros incompletos']);
        exit;
    }

    $model = new AdminReservaModel($conn);
    $habitaciones = $model->obtenerHabitacionesDisponibles($fechaDesde, $fechaHasta, $tipoHabitacionId);

    echo json_encode($habitaciones);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}