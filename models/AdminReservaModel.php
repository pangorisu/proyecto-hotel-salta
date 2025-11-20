<?php
// File: models/AdminReservaModel.php

class AdminReservaModel {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Obtener solicitudes de reserva por estado o todas
     * @param string $estado Estado de la solicitud ('pendiente', 'confirmada', 'cancelada', 'todos')
     * @return array
     */
    public function obtenerSolicitudesPorEstado(string $estado = 'pendiente'): array {
        if ($estado === 'todos') {
            $sql = "SELECT * FROM solicitudes_reserva_web ORDER BY fecha_envio DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT * FROM solicitudes_reserva_web WHERE estado = :estado ORDER BY fecha_envio DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':estado' => $estado]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Obtener habitaciones disponibles para un rango de fechas y tipo de habitación
     * @param string $fechaDesde Fecha inicio (YYYY-MM-DD)
     * @param string $fechaHasta Fecha fin (YYYY-MM-DD)
     * @param int $tipoHabitacionId ID del tipo de habitación
     * @return array
     */
    public function obtenerHabitacionesDisponibles(string $fechaDesde, string $fechaHasta, int $tipoHabitacionId): array {
        $sql = "SELECT * FROM habitaciones h
                WHERE h.tipo_habitacion = :tipo
                  AND h.estado = 'disponible'
                  AND h.id NOT IN (
                      SELECT r.id_habitacion FROM reservas r
                      WHERE NOT (r.fecha_salida <= :fechaDesde OR r.fecha_entrada >= :fechaHasta)
                  )";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':tipo' => $tipoHabitacionId,
            ':fechaDesde' => $fechaDesde,
            ':fechaHasta' => $fechaHasta
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener datos de una solicitud por ID
     * @param int $solicitudId
     * @return array|null
     */
    public function obtenerSolicitudPorId(int $solicitudId): ?array {
        $sql = "SELECT * FROM solicitudes_reserva_web WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $solicitudId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Confirmar reserva: insertar en reservas, actualizar estado solicitud y habitación
     * @param int $solicitudId
     * @param int $habitacionId
     * @return bool
     * @throws Exception
     */
    public function confirmarReserva(int $solicitudId, int $habitacionId): bool {
        try {
            $this->conn->beginTransaction();

            // Obtener solicitud con bloqueo para evitar race conditions
            $sqlSolicitud = "SELECT * FROM solicitudes_reserva_web WHERE id = :id FOR UPDATE";
            $stmt = $this->conn->prepare($sqlSolicitud);
            $stmt->execute([':id' => $solicitudId]);
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$solicitud) {
                throw new Exception("Solicitud no encontrada");
            }

            // Insertar reserva
            $sqlInsert = "INSERT INTO reservas 
                (id_usuario, id_habitacion, fecha_entrada, fecha_salida, numero_huespedes, precio_total, estado, estado_pago, metodo_pago, notas, fecha_reserva, fecha_actualizacion)
                VALUES 
                (NULL, :id_habitacion, :fecha_entrada, :fecha_salida, :numero_huespedes, 0, 'confirmada', 'pendiente', NULL, :notas, GETDATE(), GETDATE())";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->execute([
                ':id_habitacion' => $habitacionId,
                ':fecha_entrada' => $solicitud['fecha_desde'],
                ':fecha_salida' => $solicitud['fecha_hasta'],
                ':numero_huespedes' => $solicitud['num_adultos'],
                ':notas' => $solicitud['mensaje']
            ]);

            // Actualizar estado solicitud
            $sqlUpdateSolicitud = "UPDATE solicitudes_reserva_web SET estado = 'confirmada' WHERE id = :id";
            $stmtUpdate = $this->conn->prepare($sqlUpdateSolicitud);
            $stmtUpdate->execute([':id' => $solicitudId]);

            // Actualizar estado habitación
            $sqlUpdateHabitacion = "UPDATE habitaciones SET estado = 'ocupada' WHERE id = :id";
            $stmtUpdateHab = $this->conn->prepare($sqlUpdateHabitacion);
            $stmtUpdateHab->execute([':id' => $habitacionId]);

            $this->conn->commit();

            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}