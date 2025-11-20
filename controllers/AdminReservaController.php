<?php
// File: controllers/AdminReservaController.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); // Muestra todos los errores, advertencias y avisos
ini_set('log_errors', 1);

require_once 'models/AdminReservaModel.php';

class AdminReservaController 
{
    private $conn;
    private $model;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
        $this->model = new AdminReservaModel($conn);
    }

    /**
     * Mostrar listado de solicitudes con filtro por estado
     * @param string $estado
     */
    public function XXXXXXlistarSolicitudes(string $estado = 'pendiente') 
    {
        $solicitudes = $this->model->obtenerSolicitudesPorEstado($estado);
        // Cargar vista con listado
        include 'views/admin/habitaciones/solicitudes_listado.php';
    }
    public function listarSolicitudes(string $estado = 'pendiente') 
    {
        try {
            $solicitudes = $this->model->obtenerSolicitudesPorEstado($estado);
            $conn = $this->conn; // pasa conexión
            include 'views/layouts/header_admin.php';
            include 'views/admin/reservas/solicitudes_listado.php';
            include 'views/layouts/footer_admin.php';
    
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }

    /**
     * Mostrar formulario de confirmación para una solicitud
     * @param int $solicitudId
     */
    public function mostrarFormularioConfirmacion(int $solicitudId) {
        $solicitud = $this->model->obtenerSolicitudPorId($solicitudId);
        if (!$solicitud) {
            echo "Solicitud no encontrada.";
            exit;
        }

        // Obtener habitaciones disponibles para las fechas y tipo de habitación solicitada
        $habitaciones = $this->model->obtenerHabitacionesDisponibles(
            $solicitud['fecha_desde'],
            $solicitud['fecha_hasta'],
            $solicitud['tipo_habitacion_id']
        );

        // Cargar vista con formulario
        include  'views/admin/habitaciones/admin_reserva_confirmar.php';
    }

    /**
     * Procesar confirmación de reserva (POST)
     * @param array $postData
     */
    public function procesarConfirmacion(array $postData) {
        // Validar datos mínimos
        if (empty($postData['solicitud_id']) || empty($postData['habitacion_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos.']);
            exit;
        }

        $solicitudId = intval($postData['solicitud_id']);
        $habitacionId = intval($postData['habitacion_id']);

        try {
            $this->model->confirmarReserva($solicitudId, $habitacionId);

            // Aquí podrías agregar el envío de correo al cliente

            echo json_encode(['success' => true, 'mensaje' => 'Reserva confirmada correctamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'mensaje' => 'Error al confirmar la reserva: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Método para obtener habitaciones disponibles vía AJAX (filtrado dinámico)
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @param int $tipoHabitacionId
     */
    public function obtenerHabitacionesDisponiblesAjax(string $fechaDesde, string $fechaHasta, int $tipoHabitacionId) {
        $habitaciones = $this->model->obtenerHabitacionesDisponibles($fechaDesde, $fechaHasta, $tipoHabitacionId);
        header('Content-Type: application/json');
        echo json_encode($habitaciones);
        exit;
    }

    
}