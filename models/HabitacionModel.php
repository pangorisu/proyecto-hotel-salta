<?php
class HabitacionModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Obtiene todas las habitaciones disponibles (estado = 'disponible')
     */
    public function obtenerHabitaciones($lang, $tipoHabitacionId = null) {
        //$langCode = substr($lang, 0, 2);
        $langCode = $lang;

        $sql = "
            SELECT 
                h.id,
                h.numero,
                h.tipo_habitacion AS tipo_id,
                h.capacidad,
                h.precio_noche,
                h.imagen_principal,
                h.estado,
                h.piso,
                h.metros_cuadrados,

                -- Traducciones específicas
                ISNULL(ht.titulo, h.numero)        AS titulo,
                ISNULL(ht.resumen, '')             AS resumen,
                ISNULL(ht.descripcion, '')         AS descripcion,
                ISNULL(ht.imagen_url, h.imagen_principal) AS imagen_url,
                ISNULL(ht.imagen_alt, h.numero)    AS imagen_alt,

                -- Tipo traducido
                ISNULL(tt.nombre, CONCAT('Tipo ', h.tipo_habitacion)) AS tipo_nombre,
                ISNULL(tt.descripcion, '') AS tipo_descripcion

            FROM habitaciones h
            LEFT JOIN Habitacion_Traducciones ht 
                ON ht.habitacion_id = h.id 
            LEFT JOIN tipos_habitacion tt
                ON tt.tipo = h.tipo_habitacion 

                AND tt.idioma_codigo  = ht.idioma_codigo 
            WHERE h.estado = 'disponible' and  ht.idioma_codigo  = :langCodePattern
        ";

        if (!empty($tipoHabitacionId)) 
        {
            $sql .= " AND h.tipo_habitacion = :tipoHabitacionId";
        }

        $sql .= " ORDER BY h.piso, h.numero;";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':langCodePattern', $langCode , PDO::PARAM_STR);
        
        if (!empty($tipoHabitacionId)) 
        {
            $stmt->bindValue(':tipoHabitacionId', $tipoHabitacionId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        //return $stmt->errorinfo();
    }

    /**
     * Obtiene una habitación específica por ID
     */
    public function obtenerHabitacionPorId($id, $lang) {
        //$langCode = substr($lang, 0, 2);
        $langCode = $lang;

        $sql = "
            SELECT 
                h.id,
                h.numero,
                h.tipo_habitacion AS tipo_id,
                h.capacidad,
                h.precio_noche,
                h.imagen_principal,
                h.estado,
                h.piso,
                h.metros_cuadrados,

                -- Traducciones específicas
                ISNULL(ht.titulo, h.numero)        AS titulo,
                ISNULL(ht.descripcion, '')         AS descripcion,
                ISNULL(ht.resumen, '')             AS resumen,
                ISNULL(ht.imagen_url, h.imagen_principal) AS imagen_url,
                ISNULL(ht.imagen_alt, h.numero)    AS imagen_alt,

                -- Tipo traducido
                ISNULL(tt.nombre, CONCAT('Tipo ', h.tipo_habitacion)) AS tipo_nombre,
                ISNULL(tt.descripcion, '') AS tipo_descripcion

            FROM habitaciones h
            LEFT JOIN Habitacion_Traducciones ht 
                ON ht.habitacion_id = h.id 
            LEFT JOIN tipos_habitacion tt
                ON tt.tipo = h.tipo_habitacion 
                AND tt.idioma_codigo = ht.idioma_codigo
            WHERE ht.idioma_codigo=:langCodePattern and h.id = :id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':langCodePattern', $langCode, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las imágenes asociadas a una habitación
     */
    public function obtenerImagenesHabitacion($id) {
        $sql = "
            SELECT ruta_imagen, descripcion, orden
            FROM imagenes_habitaciones
            WHERE habitacion_id = :id
            ORDER BY orden ASC, id ASC;
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tipos de habitación disponibles en el idioma solicitado
     */
    public function obtenerTiposHabitacion($lang) {
        $langCode = substr($lang, 0, 2);
        $sql = "
            SELECT tipo AS tipo_id, nombre, descripcion
            FROM tipos_habitacion
            WHERE idioma_codigo LIKE :langCodePattern
              AND activo = 1
            ORDER BY nombre ASC;
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':langCodePattern', $langCode . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}