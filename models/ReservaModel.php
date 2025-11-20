<?php
/**
 * Modelo: ReservaModel
 * Maneja la lógica de negocio para reservas y solicitudes web
 */

class ReservaModel {
    /** @var PDO */
    private $db;

    /**
     * Constructor
     * @param PDO $dbConnection - Conexión PDO a SQL Server
     */
    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Obtiene tipos de habitación por idioma
     *
     * @param string $idiomaCodigo (ej: 'es-AR', 'en-US')
     * @return array
     */
    public function obtenerTiposHabitacionPorIdioma(string $idiomaCodigo): array {
        try {
            $sql = "
                SELECT id, tipo, nombre, descripcion
                FROM tipos_habitacion
                WHERE idioma_codigo = :idioma
                  AND activo = 1
                ORDER BY tipo ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':idioma', $idiomaCodigo);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error en obtenerTiposHabitacionPorIdioma: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guarda una solicitud de reserva desde el formulario público
     * (equivalente a la antigua función global guardarSolicitudReserva)
     *
     * @param array $data - Datos del formulario POST
     * @return bool - true si se guardó correctamente, false en caso contrario
     */
    public function guardarSolicitudReserva(array $data): bool {
        // Sanitización de datos (usamos helpers privados similares a sanitize(...) que ya tenías)
        $nombre       = $this->sanitize($data['nombre'] ?? '');
        $email        = $this->sanitizeEmail($data['email'] ?? '');
        $telefono     = $this->sanitize($data['telefono'] ?? '');
        $habitaciones = $this->sanitizeInt($data['num_habitaciones'] ?? 1);
        $adultos      = $this->sanitizeInt($data['num_adultos'] ?? 1);
        $desde        = $data['fecha_desde'] ?? null;
        $hasta        = $data['fecha_hasta'] ?? null;
        $mensaje      = $this->sanitize($data['mensaje'] ?? '');
        $idioma       = $data['idioma_codigo'] ?? ($GLOBALS['LOCALE'] ?? 'es-AR');
        $ip           = $data['ip_origen'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $tipoHabId    = $this->sanitizeInt($data['tipo_habitacion_id'] ?? 0);

        // Validación básica
        if (!($nombre && $email && $desde && $hasta)) {
            return false;
        }

        // Validación de fechas
        if (strtotime($desde) >= strtotime($hasta)) {
            return false;
        }

        try {
            $sql = "INSERT INTO dbo.solicitudes_reserva_web
                    (nombre, email, telefono, num_habitaciones, num_adultos,
                     fecha_desde, fecha_hasta, mensaje, idioma_codigo, ip_origen, tipo_habitacion_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                $nombre, 
                $email, 
                $telefono,
                $habitaciones, 
                $adultos,
                $desde, 
                $hasta, 
                $mensaje,
                $idioma, 
                $ip,
                $tipoHabId
            ]);
        } catch (PDOException $e) {
            error_log("Error al guardar solicitud de reserva: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todas las solicitudes de reserva pendientes
     * (equivalente a la antigua función global obtenerSolicitudesPendientes)
     *
     * @return array - Array de solicitudes
     */
    public function obtenerSolicitudesPendientes(): array {
        try {
            $sql = "SELECT * FROM dbo.solicitudes_reserva_web 
                    WHERE estado = 'pendiente' 
                    ORDER BY fecha_envio DESC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener solicitudes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Alias antiguo: insertarReservaWeb
     */
    public function insertarReservaWeb(array $data) {
        try {
            $sql = "
                INSERT INTO solicitudes_reserva_web
                (nombre, email, telefono, num_habitaciones, num_adultos, fecha_desde, fecha_hasta, 
                 mensaje, idioma_codigo, ip_origen, tipo_habitacion_id)
                VALUES
                (:nombre, :email, :telefono, :num_habitaciones, :num_adultos, :fecha_desde, :fecha_hasta, 
                 :mensaje, :idioma_codigo, :ip_origen, :tipo_habitacion_id)
            ";
            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':nombre'             => $data['nombre'],
                ':email'              => $data['email'],
                ':telefono'           => $data['telefono'],
                ':num_habitaciones'   => $data['num_habitaciones'],
                ':num_adultos'        => $data['num_adultos'],
                ':fecha_desde'        => $data['fecha_desde'],
                ':fecha_hasta'        => $data['fecha_hasta'],
                ':mensaje'            => $data['mensaje'],
                ':idioma_codigo'      => $data['idioma_codigo'],
                ':ip_origen'          => $_SERVER['REMOTE_ADDR'],
                ':tipo_habitacion_id' => $data['tipo_habitacion_id']
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al insertar reserva web: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica disponibilidad llamando al SP sp_verificar_disponibilidad.
     *
     * @param int    $tipoHabitacionId  ID de tipos_habitacion
     * @param string $fechaDesde        Formato 'Y-m-d'
     * @param string $fechaHasta        Formato 'Y-m-d'
     * @param int    $numAdultos
     *
     * @return array ['disponible' => 0|1, 'habitaciones_libres' => int, 'mensaje' => string]
     */
    public function verificarDisponibilidad($tipoHabitacionId, $fechaDesde, $fechaHasta, $numAdultos): array {
        try {
            $sql = "
                EXEC sp_verificar_disponibilidad 
                    @tipo_habitacion_id = :tipo_habitacion_id,
                    @fecha_desde        = :fecha_desde,
                    @fecha_hasta        = :fecha_hasta,
                    @num_adultos        = :num_adultos
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':tipo_habitacion_id', (int)$tipoHabitacionId, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_desde',        $fechaDesde);
            $stmt->bindValue(':fecha_hasta',        $fechaHasta);
            $stmt->bindValue(':num_adultos',        (int)$numAdultos, PDO::PARAM_INT);

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'disponible'          => 0,
                    'habitaciones_libres' => 0,
                    'mensaje'             => 'Error al verificar la disponibilidad.'
                ];
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error en verificarDisponibilidad: " . $e->getMessage());
            return [
                'disponible'          => 0,
                'habitaciones_libres' => 0,
                'mensaje'             => 'Error al verificar la disponibilidad: ' . $e->getMessage()
            ];
        }
    }

    // ========== MÉTODOS AUXILIARES DE SANITIZACIÓN ==========

    private function sanitize($str): string {
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeEmail($email): string {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    private function sanitizeInt($val): int {
        return (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
    }
}