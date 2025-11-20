<?php
require_once 'models/ServicioModel.php';

class ServiciosAdminController {
    private $conn;
    private $model;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new ServicioModel($conn);

        // Seguridad
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrador', 'operador'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    public function index($lang) {
        $GLOBALS['T'] = load_translations_db(
            $this->conn,
            $lang,
            ['admin', 'servicios_admin']
        );
        include 'views/layouts/header_admin.php';
        include 'views/admin/servicios/index.php';
        include 'views/layouts/footer_admin.php';
    }

    public function listar() {
        header('Content-Type: application/json');
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'es-AR';
        $buscar = $_GET['q'] ?? null;
        $datos = $this->model->obtenerTodos($lang, $buscar);
        echo json_encode(['success' => true, 'data' => $datos]);
    }

    public function obtener() {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            return;
        }
        $servicio = $this->model->obtenerPorId($id);
        echo json_encode(['success' => true, 'data' => $servicio]);
    }

    public function guardar() {
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? null;
        $icono = $_POST['icono'] ?? '';
        $orden = $_POST['orden'] ?? 0;
        $estado = $_POST['estado'] ?? 'activo';
        $traducciones = json_decode($_POST['traducciones'] ?? '[]', true);

        if (empty($traducciones)) {
            echo json_encode(['success' => false, 'message' => __t_db('servicios_admin.msg.missing_translation', 'Debe agregar al menos una traducción')]);
            return;
        }

        if ($id) {
            $ok = $this->model->actualizar($id, $icono, $orden, $estado, $traducciones);
            print_r ($_REQUEST);
        } else {
            $ok = $this->model->crear($icono, $orden, $estado, $traducciones);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? __t_db('servicios_admin.msg.saved', 'Servicio guardado correctamente')
                : __t_db('servicios_admin.msg.error', 'Error al guardar')
        ]);
    }

    public function eliminar() {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => __t_db('servicios_admin.msg.missing', 'ID no especificado')]);
            return;
        }
        $ok = $this->model->eliminar($id);
        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? __t_db('servicios_admin.msg.deleted', 'Servicio eliminado correctamente')
                : __t_db('servicios_admin.msg.error', 'Error al eliminar')
        ]);
    }
}
?>