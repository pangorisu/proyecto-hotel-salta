<?php
// models/HabitacionCRUDModel.php

class HabitacionCRUDModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // Listar habitaciones con traducción y tipo según idioma, paginado
    public function listarHabitaciones($idioma, $limit = 10, $offset = 0) {
        $sql = "SELECT h.id, h.numero, h.capacidad, h.precio_noche, h.estado, h.piso, h.metros_cuadrados,
                       ht.nombre AS nombre_trad, ht.titulo AS titulo_trad,
                       th.nombre AS tipo_nombre,
                       h.imagen_principal
                FROM habitaciones h
                LEFT JOIN Habitacion_Traducciones ht 
                ON h.id = ht.habitacion_id 
                --AND ht.idioma_codigo = :idioma
                LEFT JOIN tipos_habitacion th ON h.tipo_habitacion = th.tipo AND th.idioma_codigo = ht.idioma_codigo
                WHERE ht.idioma_codigo = :idioma
                ORDER BY h.id DESC
                OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idioma', $idioma, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar total de habitaciones para paginación
    public function contarHabitaciones() {
        $sql = "SELECT COUNT(*) FROM habitaciones";
        $stmt = $this->conn->query($sql);
        return (int)$stmt->fetchColumn();
    }

    // Obtener habitación por id con traducciones e imágenes
    public function obtenerHabitacion($id, $idioma) {
        $sql = "SELECT h.*, ht.nombre, ht.titulo, ht.resumen, ht.descripcion, ht.imagen_url, ht.imagen_alt,
                       th.nombre AS tipo_nombre
                FROM habitaciones h
                LEFT JOIN Habitacion_Traducciones ht 
                ON h.id = ht.habitacion_id 
                LEFT JOIN tipos_habitacion th ON h.tipo_habitacion = th.tipo AND th.idioma_codigo = ht.idioma_codigo
                WHERE ht.idioma_codigo = :idioma AND h.id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idioma', $idioma, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($habitacion) {
            $habitacion['imagenes'] = $this->obtenerImagenes($id);
        }
        return $habitacion;
    }

    // Obtener imágenes de una habitación
    public function obtenerImagenes($habitacion_id) {
        $sql = "SELECT * FROM imagenes_habitaciones WHERE habitacion_id = :habitacion_id ORDER BY orden ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':habitacion_id', $habitacion_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener tipos de habitación activos para un idioma
    public function obtenerTiposHabitacion($idioma) {
        $sql = "SELECT id, tipo, nombre FROM tipos_habitacion WHERE activo = 1 AND idioma_codigo = :idioma ORDER BY nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idioma', $idioma, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear habitación (datos generales)
    public function crearHabitacion($data) {
        $sql = "INSERT INTO habitaciones (numero, tipo_habitacion, capacidad, precio_noche, imagen_principal, estado, piso, metros_cuadrados, fecha_creacion, fecha_actualizacion)
                VALUES (:numero, :tipo_habitacion, :capacidad, :precio_noche, :imagen_principal, :estado, :piso, :metros_cuadrados, GETDATE(), GETDATE())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':numero', $data['numero']);
        $stmt->bindParam(':tipo_habitacion', $data['tipo_habitacion'], PDO::PARAM_INT);
        $stmt->bindParam(':capacidad', $data['capacidad'], PDO::PARAM_INT);
        $stmt->bindParam(':precio_noche', $data['precio_noche']);
        $stmt->bindParam(':imagen_principal', $data['imagen_principal']);
        $stmt->bindParam(':estado', $data['estado']);
        $stmt->bindParam(':piso', $data['piso'], PDO::PARAM_INT);
        $stmt->bindParam(':metros_cuadrados', $data['metros_cuadrados']);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Actualizar habitación (datos generales)
    public function actualizarHabitacion($id, $data) {
        $sql = "UPDATE habitaciones SET numero = :numero, tipo_habitacion = :tipo_habitacion, capacidad = :capacidad, precio_noche = :precio_noche,
                imagen_principal = :imagen_principal, estado = :estado, piso = :piso, metros_cuadrados = :metros_cuadrados, fecha_actualizacion = GETDATE()
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':numero', $data['numero']);
        $stmt->bindParam(':tipo_habitacion', $data['tipo_habitacion'], PDO::PARAM_INT);
        $stmt->bindParam(':capacidad', $data['capacidad'], PDO::PARAM_INT);
        $stmt->bindParam(':precio_noche', $data['precio_noche']);
        $stmt->bindParam(':imagen_principal', $data['imagen_principal']);
        $stmt->bindParam(':estado', $data['estado']);
        $stmt->bindParam(':piso', $data['piso'], PDO::PARAM_INT);
        $stmt->bindParam(':metros_cuadrados', $data['metros_cuadrados']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Eliminar habitación (cascade elimina traducciones e imágenes)
    public function eliminarHabitacion($id) {
        $sql = "DELETE FROM habitaciones WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Crear o actualizar traducción (upsert)
    public function guardarTraduccion($habitacion_id, $idioma, $data) {
        // Verificar si existe
        $sqlCheck = "SELECT id FROM Habitacion_Traducciones WHERE habitacion_id = :habitacion_id AND idioma_codigo = :idioma";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bindParam(':habitacion_id', $habitacion_id, PDO::PARAM_INT);
        $stmtCheck->bindParam(':idioma', $idioma, PDO::PARAM_STR);
        $stmtCheck->execute();
        $existe = $stmtCheck->fetchColumn();

        if ($existe) {
            $sql = "UPDATE Habitacion_Traducciones SET nombre = :nombre, titulo = :titulo, resumen = :resumen, descripcion = :descripcion,
                    imagen_url = :imagen_url, imagen_alt = :imagen_alt WHERE habitacion_id = :habitacion_id AND idioma_codigo = :idioma";
        } else {
            $sql = "INSERT INTO Habitacion_Traducciones (habitacion_id, idioma_codigo, nombre, titulo, resumen, descripcion, imagen_url, imagen_alt)
                    VALUES (:habitacion_id, :idioma, :nombre, :titulo, :resumen, :descripcion, :imagen_url, :imagen_alt)";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':habitacion_id', $habitacion_id, PDO::PARAM_INT);
        $stmt->bindParam(':idioma', $idioma, PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':titulo', $data['titulo']);
        $stmt->bindParam(':resumen', $data['resumen']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':imagen_url', $data['imagen_url']);
        $stmt->bindParam(':imagen_alt', $data['imagen_alt']);
        return $stmt->execute();
    }

    // Guardar imagen habitación
    public function guardarImagen($habitacion_id, $ruta_imagen, $descripcion, $orden = 0) {
        $sql = "INSERT INTO imagenes_habitaciones (habitacion_id, ruta_imagen, descripcion, orden, fecha_subida)
                VALUES (:habitacion_id, :ruta_imagen, :descripcion, :orden, GETDATE())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':habitacion_id', $habitacion_id, PDO::PARAM_INT);
        $stmt->bindParam(':ruta_imagen', $ruta_imagen);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':orden', $orden, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Eliminar imagen por id
    public function eliminarImagen($id) {
        $sql = "DELETE FROM imagenes_habitaciones WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>