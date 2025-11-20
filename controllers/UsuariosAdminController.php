<?php
require_once 'models/UsuarioModel.php';

class UsuariosAdminController {
    private $model;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new UsuarioModel($conn);
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrador', 'operador'])) {
            header('Location: index.php?controller=auth&action=login'); exit;
        }
    }

    public function index($lang) {
        // Cargar traducciones del módulo admin + usuarios
        $GLOBALS['T'] = load_translations_db($this->conn, $lang, ['admin', 'usuarios_admin']);
        include 'views/layouts/header_admin.php';
        include 'views/admin/usuarios/index.php';
        include 'views/layouts/footer_admin.php';
    }

    // AJAX: listar usuarios
    public function listar() {
        header('Content-Type: application/json');
        $buscar = $_GET['q'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int)($_GET['perPage'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $data = $this->model->obtenerTodos($buscar, $perPage, $offset);
        $total = $this->model->contar($buscar);
        echo json_encode(['success' => true, 'data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage]);
    }

    // AJAX: crear/actualizar usuario
    public function guardar() {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $rol = trim($_POST['rol'] ?? 'usuario');
        $estado = trim($_POST['estado'] ?? 'activo');
        $password = $_POST['password'] ?? null;

        if (!$nombre || !$apellido || !$email) {
            echo json_encode(['success' => false, 'message' => __t_db('usuarios_admin.msg.missing', 'Faltan datos obligatorios')]);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => __t_db('usuarios_admin.msg.email_invalid', 'Email inválido')]);
            return;
        }
        if ($this->model->emailExiste($email, $id)) {
            echo json_encode(['success' => false, 'message' => __t_db('usuarios_admin.msg.email_exists', 'El email ya existe')]);
            return;
        }

        if ($id) {
            $ok = $this->model->actualizar($id, [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'rol' => $rol,
                'estado' => $estado,
            ]);
            if ($ok && $password !== null && $password !== '') {
                $ok = $this->model->actualizarPassword($id, password_hash($password, PASSWORD_BCRYPT));
            }
        } else {
            if (!$password) $password = 'password'; // por defecto
            $ok = $this->model->crear([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'rol' => $rol,
                'estado' => $estado,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            ]);
        }

        echo json_encode(['success' => $ok, 'message' => $ok ? __t_db('usuarios_admin.msg.saved', 'Usuario guardado correctamente') : __t_db('usuarios_admin.msg.save_error', 'Error al guardar')]);
    }

    // AJAX: eliminar usuario
    public function eliminar() {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { 
            echo json_encode(['success' => false, 'message' => __t_db('usuarios_admin.msg.id_missing', 'ID no proporcionado')]); 
            return; 
        }

        // Seguridad: impedir que un usuario se elimine a sí mismo
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            echo json_encode(['success' => false, 'message' => __t_db('usuarios_admin.msg.cannot_delete_self', 'No puedes eliminar tu propio usuario')]); 
            return;
        }

        $ok = $this->model->eliminar($id);
        echo json_encode(['success' => $ok, 'message' => $ok ? __t_db('usuarios_admin.msg.deleted', 'Usuario eliminado correctamente') : __t_db('usuarios_admin.msg.delete_error', 'Error al eliminar')]);
    }
}
?>