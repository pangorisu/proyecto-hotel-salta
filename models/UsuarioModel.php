<?php
class UsuarioModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // === Funciones existentes ===
    public function obtenerUsuarioPorEmail($email) {
        $sql = "SELECT * FROM usuarios WHERE email = :email AND estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarUltimaConexion($id) {
        $stmt = $this->conn->prepare("UPDATE usuarios SET ultima_conexion = GETDATE() WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // === Nuevas funciones para CRUD ===

    // Obtener lista de usuarios con búsqueda y paginación
    public function obtenerTodos($buscar = null, $limit = 100, $offset = 0) {
        $sql = "SELECT id, nombre, apellido, email, telefono, rol, 
                CASE WHEN estado = 1 THEN 'activo' ELSE 'inactivo' END AS estado,
                ultima_conexion, fecha_registro
                FROM usuarios";
        $params = [];

        if ($buscar) {
            $sql .= " WHERE nombre LIKE :q OR apellido LIKE :q OR email LIKE :q";
            $params[':q'] = "%$buscar%";
        }

        $sql .= " ORDER BY rol, nombre OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar total (para paginación)
    public function contar($buscar = null) {
        $sql = "SELECT COUNT(*) AS total FROM usuarios";
        $params = [];

        if ($buscar) {
            $sql .= " WHERE nombre LIKE :q OR apellido LIKE :q OR email LIKE :q";
            $params[':q'] = "%$buscar%";
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Obtener usuario por ID
    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT id, nombre, apellido, email, telefono, rol, 
                                      CASE WHEN estado = 1 THEN 'activo' ELSE 'inactivo' END AS estado
                                      FROM usuarios WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Validar si el email ya existe (excluyendo un ID)
    public function emailExiste($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = :email";
        if ($excludeId) $sql .= " AND id <> :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        if ($excludeId) $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);

        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    // Crear usuario
    public function crear($data) {
        $estadoBit = ($data['estado'] === 'activo' || $data['estado'] == 1) ? 1 : 0;
        
        $sql = "INSERT INTO usuarios (nombre, apellido, email, telefono, rol, estado, password_hash, fecha_registro)
                VALUES (:nombre, :apellido, :email, :telefono, :rol, :estado, :password_hash, GETDATE())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':apellido', $data['apellido']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':telefono', $data['telefono'] ?? null);
        $stmt->bindValue(':rol', $data['rol']);
        $stmt->bindValue(':estado', $estadoBit, PDO::PARAM_INT);
        $stmt->bindValue(':password_hash', $data['password_hash']);
        return $stmt->execute();
    }

    // Actualizar usuario
    public function actualizar($id, $data) {
        $estadoBit = ($data['estado'] === 'activo' || $data['estado'] == 1) ? 1 : 0;
        
        $sql = "UPDATE usuarios 
                SET nombre = :nombre, 
                    apellido = :apellido, 
                    email = :email,
                    telefono = :telefono, 
                    rol = :rol,
                    estado = :estado
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':apellido', $data['apellido']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':telefono', $data['telefono'] ?? null);
        $stmt->bindValue(':rol', $data['rol']);
        $stmt->bindValue(':estado', $estadoBit, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Actualizar solo la contraseña
    public function actualizarPassword($id, $passwordHash) {
        $stmt = $this->conn->prepare("UPDATE usuarios SET password_hash = :ph WHERE id = :id");
        $stmt->bindValue(':ph', $passwordHash);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Eliminar usuario
    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>