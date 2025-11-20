<?php
/**
 * Controlador: reservaController
 * Procesa las solicitudes de reserva desde el formulario público (AJAX)
 */

// En desarrollo: mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Siempre responder JSON
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/i18n_db.php';
    require_once __DIR__ . '/../models/ReservaModel.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $idioma = $_SESSION['idioma'] ?? detectLanguage();
    $GLOBALS['LOCALE'] = $idioma;
    $GLOBALS['T'] = load_translations_db($conn, $idioma, ['home', 'agradecimiento']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Método no permitido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Action: checkAvailability | confirmReservation
    $action = $_POST['action'] ?? 'checkAvailability';

    $reservaModel = new ReservaModel($conn);

    switch ($action) {
        case 'checkAvailability':
            checkAvailability($reservaModel, $idioma);
            break;

        case 'confirmReservation':
            confirmReservation($reservaModel, $idioma);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'mensaje' => 'Acción no reconocida'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error: ' . $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea'   => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * FASE 1: solo verificar disponibilidad (no guarda reserva todavía)
 */
function checkAvailability(ReservaModel $reservaModel, string $idioma)
{
    $data = [
        'nombre'            => trim($_POST['nombre'] ?? ''),
        'email'             => trim($_POST['email'] ?? ''),
        'telefono'          => trim($_POST['telefono'] ?? ''),
        'num_habitaciones'  => (int)($_POST['num_habitaciones'] ?? 1),
        'num_adultos'       => (int)($_POST['num_adultos'] ?? 1),
        'fecha_desde'       => $_POST['fecha_desde'] ?? '',
        'fecha_hasta'       => $_POST['fecha_hasta'] ?? '',
        'mensaje'           => trim($_POST['mensaje'] ?? ''),
        'idioma_codigo'     => $idioma,
        'ip_origen'         => $_SERVER['REMOTE_ADDR'] ?? null,
        'tipo_habitacion_id'=> isset($_POST['tipo_habitacion_id']) ? (int)$_POST['tipo_habitacion_id'] : null,
    ];

    $errors = [];

    if ($data['nombre'] === '') $errors[] = 'El nombre es obligatorio.';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if ($data['fecha_desde'] === '') $errors[] = 'Fecha de entrada es obligatoria.';
    if ($data['fecha_hasta'] === '') $errors[] = 'Fecha de salida es obligatoria.';
    if ($data['num_habitaciones'] < 1) $errors[] = 'Número de habitaciones inválido.';
    if ($data['num_adultos'] < 1) $errors[] = 'Número de adultos inválido.';
    if (empty($data['tipo_habitacion_id'])) $errors[] = 'Debe seleccionar un tipo de habitación.';

    if ($data['fecha_desde'] && $data['fecha_hasta']) {
        if (strtotime($data['fecha_desde']) >= strtotime($data['fecha_hasta'])) {
            $errors[] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors'  => $errors
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Verificar disponibilidad en BD
    $dispo = $reservaModel->verificarDisponibilidad(
        $data['tipo_habitacion_id'],
        $data['fecha_desde'],
        $data['fecha_hasta'],
        $data['num_adultos']
    );

    // Normalizar respuesta
    $disponible = !empty($dispo['disponible']) ? (int)$dispo['disponible'] : 0;
    $libres     = isset($dispo['habitaciones_libres']) ? (int)$dispo['habitaciones_libres'] : 0;
    $mensaje    = $dispo['mensaje'] ?? 'Error al verificar la disponibilidad.';

    http_response_code(200);
    echo json_encode([
        'success'            => true,
        'disponible'         => (bool)$disponible,
        'habitaciones_libres'=> $libres,
        'mensaje'            => $mensaje,
        // devolvemos los datos para que el front los re-use en la fase de pago si quiere
        'datos_reserva'      => $data
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * FASE 2: confirmar reserva con pago simulado o pago posterior
 */
function confirmReservation(ReservaModel $reservaModel, string $idioma)
{
    // ============================================
    // NUEVO: Detectar modo de pago
    // ============================================
    $modoPago = trim($_POST['modo_pago'] ?? 'inmediato');
    
    // Datos de reserva (los manda el front de nuevo)
    $data = [
        'nombre'            => trim($_POST['nombre'] ?? ''),
        'email'             => trim($_POST['email'] ?? ''),
        'telefono'          => trim($_POST['telefono'] ?? ''),
        'num_habitaciones'  => (int)($_POST['num_habitaciones'] ?? 1),
        'num_adultos'       => (int)($_POST['num_adultos'] ?? 1),
        'fecha_desde'       => $_POST['fecha_desde'] ?? '',
        'fecha_hasta'       => $_POST['fecha_hasta'] ?? '',
        'mensaje'           => trim($_POST['mensaje'] ?? ''),
        'idioma_codigo'     => $idioma,
        'ip_origen'         => $_SERVER['REMOTE_ADDR'] ?? null,
        'tipo_habitacion_id'=> isset($_POST['tipo_habitacion_id']) ? (int)$_POST['tipo_habitacion_id'] : null,
    ];

    $errors = [];

    // Validaciones reserva (SIEMPRE obligatorias)
    if ($data['nombre'] === '') $errors[] = 'El nombre es obligatorio.';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if ($data['fecha_desde'] === '') $errors[] = 'Fecha de entrada es obligatoria.';
    if ($data['fecha_hasta'] === '') $errors[] = 'Fecha de salida es obligatoria.';
    if ($data['num_adultos'] < 1) $errors[] = 'Número de adultos inválido.';
    if (empty($data['tipo_habitacion_id'])) $errors[] = 'Debe seleccionar un tipo de habitación.';

    if ($data['fecha_desde'] && $data['fecha_hasta']) {
        if (strtotime($data['fecha_desde']) >= strtotime($data['fecha_hasta'])) {
            $errors[] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
        }
    }

    // ============================================
    // NUEVO: Validar datos de pago SOLO si modo_pago es 'inmediato'
    // ============================================
    $pago = null;
    
    if ($modoPago === 'inmediato') {
        $pago = [
            'nombre_tarjeta'   => trim($_POST['nombre_tarjeta'] ?? ''),
            'numero_tarjeta'   => preg_replace('/\D/', '', $_POST['numero_tarjeta'] ?? ''),
            'mes_vencimiento'  => trim($_POST['mes_vencimiento'] ?? ''),
            'anio_vencimiento' => trim($_POST['anio_vencimiento'] ?? ''),
            'cvv'              => trim($_POST['cvv'] ?? ''),
            'tipo_tarjeta'     => trim($_POST['tipo_tarjeta'] ?? '')
        ];

        // Validaciones pago
        if ($pago['nombre_tarjeta'] === '') $errors[] = 'El nombre en la tarjeta es obligatorio.';
        if ($pago['numero_tarjeta'] === '' || strlen($pago['numero_tarjeta']) < 13 || strlen($pago['numero_tarjeta']) > 19) {
            $errors[] = 'Número de tarjeta inválido.';
        }
        if ($pago['mes_vencimiento'] === '' || !preg_match('/^(0[1-9]|1[0-2])$/', $pago['mes_vencimiento'])) {
            $errors[] = 'Mes de vencimiento inválido.';
        }
        if ($pago['anio_vencimiento'] === '' || !preg_match('/^\d{2,4}$/', $pago['anio_vencimiento'])) {
            $errors[] = 'Año de vencimiento inválido.';
        }
        if ($pago['cvv'] === '' || !preg_match('/^\d{3,4}$/', $pago['cvv'])) {
            $errors[] = 'CVV inválido.';
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors'  => $errors
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // (Opcional) Re-verificar disponibilidad justo antes de confirmar
    $dispo = $reservaModel->verificarDisponibilidad(
        $data['tipo_habitacion_id'],
        $data['fecha_desde'],
        $data['fecha_hasta'],
        $data['num_adultos']
    );

    if (empty($dispo['disponible'])) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Mientras completabas los datos de pago, la disponibilidad cambió: ' . ($dispo['mensaje'] ?? '')
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // ============================================
    // NUEVO: Procesar según modo de pago
    // ============================================
    
    if ($modoPago === 'posterior') {
        // ========================================
        // PAGO POSTERIOR: Guardar como solicitud pendiente
        // ========================================
        
        // TODO: Llamar a método del modelo para guardar solicitud pendiente
        // Ejemplo: $reservaModel->guardarSolicitudPendiente($data);
        
        $codigoSolicitud = 'SOL-' . date('YmdHis') . '-' . rand(100, 999);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'titulo'  => __t_db('agradecimiento.titulo_solicitud', '¡Solicitud recibida!'),
            'mensaje' => __t_db('agradecimiento.mensaje_solicitud', 'Hemos registrado tu solicitud de reserva. Nuestro equipo se pondrá en contacto contigo para completar el pago y confirmar tu reserva.'),
            'codigo_solicitud' => $codigoSolicitud
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // ========================================
        // PAGO INMEDIATO: Procesar pago y confirmar reserva
        // ========================================
        
        // Datos de pago a guardar (sin datos sensibles)
        $datosPagoGuardar = [
            'tipo_tarjeta'    => $pago['tipo_tarjeta'] ?: 'desconocida',
            'nombre_tarjeta'  => $pago['nombre_tarjeta'],
            'ultimos_4'       => substr($pago['numero_tarjeta'], -4),
            'mes_vencimiento' => $pago['mes_vencimiento'],
            'anio_vencimiento'=> $pago['anio_vencimiento']
            // No guardamos CVV ni número completo
        ];

        // Aquí deberías tener un método en ReservaModel para crear la reserva definitiva
        // Por ahora simulamos un código de reserva
        $codigoReserva = 'RSV-' . date('YmdHis') . '-' . rand(100, 999);

        // TODO: llamar a $reservaModel->crearReservaConfirmada($data, $datosPagoGuardar);
        // Por ahora solo simulamos que fue bien:

        http_response_code(200);
        echo json_encode([
            'success'        => true,
            'titulo'         => __t_db('agradecimiento.titulo', '¡Gracias por tu reserva!'),
            'mensaje'        => __t_db('agradecimiento.mensaje_confirmada', 'Tu reserva ha sido confirmada. Te hemos enviado un correo con los detalles.'),
            'codigo_reserva' => $codigoReserva
        ], JSON_UNESCAPED_UNICODE);
    }
}