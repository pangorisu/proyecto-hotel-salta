<?php
class ServicioModel 
{
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Obtiene todos los servicios con traducción según idioma
     */
    public function obtenerTodos($idioma_codigo = 'es-AR', $buscar = null) {
        $sql = "SELECT s.id, s.icono, s.orden, 
                       CASE WHEN s.estado = 1 THEN 'activo' ELSE 'inactivo' END AS estado,
                       t.nombre, t.descripcion
                FROM servicios s
                LEFT JOIN servicio_traducciones t ON s.id = t.servicio_id AND t.idioma_codigo = :idioma_codigo";
        $params = [':idioma_codigo' => $idioma_codigo];

        if ($buscar) {
            $sql .= " WHERE t.nombre LIKE :q OR t.descripcion LIKE :q";
            $params[':q'] = "%$buscar%";
        }

        $sql .= " ORDER BY s.orden ASC, s.id DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) $stmt->bindValue($key, $val);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un servicio por ID con sus traducciones
     */
    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM servicios WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$servicio) return null;

        $stmtT = $this->conn->prepare("SELECT idioma_codigo, nombre, descripcion 
                                       FROM servicio_traducciones 
                                       WHERE servicio_id = :id");
        $stmtT->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtT->execute();
        $servicio['traducciones'] = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        return $servicio;
    }

    /**
     * Crear servicio con sus traducciones
     */
    public function crear($icono, $orden, $estado, $traducciones) {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("INSERT INTO servicios (icono, orden, estado, fecha_creacion)
                                          VALUES (:icono, :orden, :estado, GETDATE())");
            $stmt->execute([
                ':icono' => $icono,
                ':orden' => $orden,
                ':estado' => ($estado == 'activo') ? 1 : 0
            ]);

            $servicioId = $this->conn->lastInsertId();

            foreach ($traducciones as $t) {
                if (!empty($t['nombre'])) {
                    $this->agregarTraduccion($servicioId, $t['idioma_codigo'], $t['nombre'], $t['descripcion']);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al crear servicio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar servicio
     */
    public function actualizar($id, $icono, $orden, $estado, $traducciones) {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("UPDATE servicios SET icono = :icono, orden = :orden, estado = :estado WHERE id = :id");
            $stmt->execute([
                ':icono' => $icono,
                ':orden' => $orden,
                ':estado' => ($estado == 'activo') ? 1 : 0,
                ':id' => $id
            ]);

            $this->eliminarTraducciones($id);
            foreach ($traducciones as $t) {
                if (!empty($t['nombre'])) {
                    $this->agregarTraduccion($id, $t['idioma_codigo'], $t['nombre'], $t['descripcion']);
                }
            }

            $this->conn->commit();
            //return true;
            return $stmt->errorinfo();
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al actualizar servicio: " . $e->getMessage());
            return false;
        }
    }

}