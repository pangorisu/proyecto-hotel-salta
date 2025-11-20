<?php
// models/DashboardModel.php

class DashboardModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene las estadísticas de ocupación de habitaciones
     * Incluye: ocupadas, disponibles, mantenimiento, cerradas y porcentaje de ocupación
     */
    public function obtenerOcupacionHabitaciones() {
        try {
            // Actualizar estados de habitaciones basado en reservas activas
            $this->actualizarEstadosHabitaciones();

            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                        SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                        SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
                        SUM(CASE WHEN estado = 'cerrada' THEN 1 ELSE 0 END) as cerradas
                    FROM habitaciones";

            $stmt = $this->pdo->query($sql);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calcular porcentaje de ocupación (solo sobre habitaciones operativas)
            $total = $resultado['total'];
            $ocupadas = $resultado['ocupadas'];
            $cerradas = $resultado['cerradas'];
            $mantenimiento = $resultado['mantenimiento'];
            
            // Habitaciones operativas = total - cerradas - mantenimiento
            $operativas = $total - $cerradas - $mantenimiento;
            
            if ($operativas > 0) {
                $porcentaje = ($ocupadas / $operativas) * 100;
            } else {
                $porcentaje = 0;
            }

            return [
                'total' => (int)$total,
                'ocupadas' => (int)$ocupadas,
                'disponibles' => (int)$resultado['disponibles'],
                'mantenimiento' => (int)$mantenimiento,
                'cerradas' => (int)$cerradas,
                'porcentaje_ocupacion' => round($porcentaje, 2)
            ];

        } catch (PDOException $e) {
            error_log("Error en obtenerOcupacionHabitaciones: " . $e->getMessage());
            return [
                'total' => 0,
                'ocupadas' => 0,
                'disponibles' => 0,
                'mantenimiento' => 0,
                'cerradas' => 0,
                'porcentaje_ocupacion' => 0
            ];
        }
    }

    /**
     * Obtiene la ocupación por tipo de habitación
     */
    public function obtenerOcupacionPorTipo() {
        try {
            $sql = "SELECT 
                        th.tipo,
                        th.nombre as tipo_nombre,
                        COUNT(h.id) as total,
                        SUM(CASE WHEN h.estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas
                    FROM tipos_habitacion th
                    LEFT JOIN habitaciones h ON th.tipo = h.tipo_habitacion
                    WHERE th.idioma_codigo = 'es-AR'
                    GROUP BY th.tipo, th.nombre
                    ORDER BY th.tipo";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerOcupacionPorTipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas de reservas (hoy, check-ins, check-outs, próximas)
     */
    public function obtenerEstadisticasReservas() {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM reservas 
                         WHERE CAST(fecha_entrada AS DATE) = CAST(GETDATE() AS DATE)) as reservas_hoy,
                        
                        (SELECT COUNT(*) FROM reservas 
                         WHERE CAST(fecha_entrada AS DATE) = CAST(GETDATE() AS DATE) 
                         AND estado = 'confirmada') as checkins_pendientes,
                        
                        (SELECT COUNT(*) FROM reservas 
                         WHERE CAST(fecha_salida AS DATE) = CAST(GETDATE() AS DATE) 
                         AND estado = 'confirmada') as checkouts_pendientes,
                        
                        (SELECT COUNT(*) FROM reservas 
                         WHERE fecha_entrada BETWEEN GETDATE() AND DATEADD(DAY, 7, GETDATE())
                         AND estado = 'confirmada') as proximas_reservas";

            $stmt = $this->pdo->query($sql);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'reservas_hoy' => (int)$resultado['reservas_hoy'],
                'checkins_pendientes' => (int)$resultado['checkins_pendientes'],
                'checkouts_pendientes' => (int)$resultado['checkouts_pendientes'],
                'proximas_reservas' => (int)$resultado['proximas_reservas']
            ];

        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticasReservas: " . $e->getMessage());
            return [
                'reservas_hoy' => 0,
                'checkins_pendientes' => 0,
                'checkouts_pendientes' => 0,
                'proximas_reservas' => 0
            ];
        }
    }

    /**
     * Obtiene reservas por día (últimos 7 días)
     */
    public function obtenerReservasPorDia() {
        try {
            $sql = "SELECT 
                        CAST(fecha_entrada AS DATE) as fecha,
                        COUNT(*) as total
                    FROM reservas
                    WHERE fecha_entrada >= DATEADD(DAY, -7, GETDATE())
                    GROUP BY CAST(fecha_entrada AS DATE)
                    ORDER BY fecha ASC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerReservasPorDia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el estado de las reservas (confirmadas, en espera, canceladas)
     */
    public function obtenerEstadoReservas() {
        try {
            $sql = "SELECT 
                        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as en_espera,
                        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
                    FROM reservas
                    WHERE fecha_entrada >= DATEADD(MONTH, -1, GETDATE())";

            $stmt = $this->pdo->query($sql);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'confirmadas' => (int)$resultado['confirmadas'],
                'en_espera' => (int)$resultado['en_espera'],
                'canceladas' => (int)$resultado['canceladas']
            ];

        } catch (PDOException $e) {
            error_log("Error en obtenerEstadoReservas: " . $e->getMessage());
            return [
                'confirmadas' => 0,
                'en_espera' => 0,
                'canceladas' => 0
            ];
        }
    }

    /**
     * Obtiene el top 5 de tipos de habitación más reservados
     */
    public function obtenerTop5Habitaciones() {
        try {
            $sql = "SELECT TOP 5
                        th.tipo,
                        th.nombre as tipo_nombre,
                        COUNT(r.id) as total_reservas
                    FROM tipos_habitacion th
                    LEFT JOIN habitaciones h ON th.tipo = h.tipo_habitacion
                    LEFT JOIN reservas r ON h.id = r.id_habitacion
                    WHERE th.idioma_codigo = 'es-AR'
                        AND r.fecha_entrada >= DATEADD(MONTH, -1, GETDATE())
                        AND r.estado IN ('confirmada', 'completada')
                    GROUP BY th.tipo, th.nombre
                    ORDER BY total_reservas DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerTop5Habitaciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza el estado de las habitaciones basado en las reservas activas
     */
    private function actualizarEstadosHabitaciones() {
        try {
            // Marcar como ocupadas las habitaciones con reservas activas
            $sqlOcupadas = "UPDATE habitaciones
                           SET estado = 'ocupada'
                           WHERE id IN (
                               SELECT DISTINCT id_habitacion
                               FROM reservas
                               WHERE estado = 'confirmada'
                                   AND CAST(GETDATE() AS DATE) BETWEEN CAST(fecha_entrada AS DATE) AND CAST(fecha_salida AS DATE)
                           )
                           AND estado NOT IN ('mantenimiento', 'cerrada')";

            $this->pdo->exec($sqlOcupadas);

            // Marcar como disponibles las habitaciones sin reservas activas
            $sqlDisponibles = "UPDATE habitaciones
                              SET estado = 'disponible'
                              WHERE id NOT IN (
                                  SELECT DISTINCT id_habitacion
                                  FROM reservas
                                  WHERE estado = 'confirmada'
                                      AND CAST(GETDATE() AS DATE) BETWEEN CAST(fecha_entrada AS DATE) AND CAST(fecha_salida AS DATE)
                              )
                              AND estado NOT IN ('mantenimiento', 'cerrada')";

            $this->pdo->exec($sqlDisponibles);

        } catch (PDOException $e) {
            error_log("Error en actualizarEstadosHabitaciones: " . $e->getMessage());
        }
    }

    /**
     * Obtiene habitaciones por estado específico
     */
    public function obtenerHabitacionesPorEstado($estado) {
        try {
            $sql = "SELECT 
                        h.id,
                        h.numero_habitacion,
                        h.estado,
                        th.nombre as tipo_nombre
                    FROM habitaciones h
                    LEFT JOIN tipos_habitacion th ON h.tipo_habitacion = th.tipo AND th.idioma_codigo = 'es-AR'
                    WHERE h.estado = :estado
                    ORDER BY h.numero_habitacion ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en obtenerHabitacionesPorEstado: " . $e->getMessage());
            return [];
        }
    }
}