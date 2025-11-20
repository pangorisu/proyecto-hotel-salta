<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/i18n_db.php';

// Detectar idioma actual
$GLOBALS['LOCALE'] = detect_locale_db();
$GLOBALS['T'] = load_translations_db($conn, $GLOBALS['LOCALE'], ['header', 'footer']);
$lang = $GLOBALS['LOCALE'];

// ==== ROUTER SIMPLE ====
$controllerName = $_GET['controller'] ?? null;
$action = $_GET['action'] ?? null;

// Si hay controlador → sección MVC
if ($controllerName) {
    switch ($controllerName) {
        case 'habitacion':
            require_once 'controllers/HabitacionController.php';
            $controller = new HabitacionController($conn);
            if ($action === 'detalle' && isset($_GET['id'])) {
                $controller->detalle((int)$_GET['id'], $lang);
            } else {
                $controller->index($lang);
            }
            break;
        // podrás agregar más controladores aquí (ej. reservas, usuarios, etc.)
        case 'introduccion':
            require_once 'controllers/IntroduccionController.php';
            $controller = new IntroduccionController($conn);
            if ($action === 'index' || !$action) {
                $controller->index($lang);
            } else {
                include 'views/pages/404.php';
            }
            break;
        
        case 'contacto':
            require_once 'controllers/ContactoController.php';
            $controller = new ContactoController($conn);
            if ($action === 'index' || !$action) {
                $controller->index($lang);
            } else {
                include 'views/pages/404.php';
            }
            break;
        case 'adminReserva':
            require_once 'controllers/AdminReservaController.php';
            //$conn = /* tu conexión PDO */;
            $adminReservaController = new AdminReservaController($conn);

            switch ($action) 
            {
                case 'listarSolicitudes':
                    $estado = $_GET['estado'] ?? 'pendiente';
                    $adminReservaController->listarSolicitudes($estado);
                    break;

                case 'mostrarFormularioConfirmacion':
                    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                    $adminReservaController->mostrarFormularioConfirmacion($id);
                    break;

                case 'procesarConfirmacion':
                    $adminReservaController->procesarConfirmacion($_POST);
                    break;

                case 'obtenerHabitacionesDisponibles':
                    $fechaDesde = $_GET['fecha_desde'] ?? '';
                    $fechaHasta = $_GET['fecha_hasta'] ?? '';
                    $tipoHabitacionId = isset($_GET['tipo_habitacion_id']) ? intval($_GET['tipo_habitacion_id']) : 0;
                    $adminReservaController->obtenerHabitacionesDisponiblesAjax($fechaDesde, $fechaHasta, $tipoHabitacionId);
                    break;

                default:
                    // Acción por defecto o error
                    echo "Acción no encontrada";
                    break;
            }
            break;
        case 'galeria':
            require_once 'controllers/GaleriaController.php';
            $controller = new GaleriaController($conn);
            if ($action === 'index' || !$action) {
                $controller->index($lang);
            } else {
                include 'views/pages/404.php';
            }
            break;

        case 'auth':
            require_once 'controllers/AuthController.php';
            $controller = new AuthController($conn);

            if ($action === 'login') {
                $controller->login($lang);
            } elseif ($action === 'logout') {
                $controller->logout();
            } 
            break;
        case 'admin':
            require_once 'controllers/AdminController.php';
            $controller = new AdminController($conn);

            if ($action === 'dashboard') {
                $controller->dashboard($lang);
            } else {
                include 'views/pages/404.php';
            }
            break;
        
        
        case 'usuariosAdmin':
            // Solo admin u operador
            if (!isset($_SESSION['user_id'])) {
                header('Location: index.php?controller=auth&action=login'); exit;
            }
            if (!in_array($_SESSION['user_role'], ['administrador', 'operador'])) {
                header('Location: index.php'); exit;
            }

            require_once 'controllers/UsuariosAdminController.php';
            $controller = new UsuariosAdminController($conn);

            // Vistas HTML
            if ($action === 'index') {
                $controller->index($lang);
            }
            // Endpoints AJAX
            elseif ($action === 'listar') { $controller->listar(); }
            elseif ($action === 'guardar') { $controller->guardar(); }
            elseif ($action === 'eliminar') { $controller->eliminar(); }
            else {
                include 'views/pages/404.php';
            }
            break;
        case 'serviciosAdmin':
            // Solo admin u operador
            if (!isset($_SESSION['user_id'])) {
                header('Location: index.php?controller=auth&action=login'); exit;
            }
            if (!in_array($_SESSION['user_role'], ['administrador', 'operador'])) {
                header('Location: index.php'); exit;
            }
        
            require_once 'controllers/ServiciosAdminController.php';
            $controller = new ServiciosAdminController($conn);
        
            // Vistas HTML
            if ($action === 'index') {
                $controller->index($lang);
            }
            // Endpoints AJAX
            elseif ($action === 'listar') { $controller->listar(); }
            elseif ($action === 'obtener') { $controller->obtener(); }
            elseif ($action === 'guardar') { $controller->guardar(); }
            elseif ($action === 'eliminar') { $controller->eliminar(); }
            else {
                include 'views/pages/404.php';
            }
            break;

        case 'habitacionesAdmin':
            require_once 'controllers/HabitacionCRUDController.php';
            $controller = new HabitacionCRUDController($conn, $lang);
            switch ($action) {
                case 'index':
                    $controller->index();
                    break;
                case 'create':
                    $controller->create();
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'edit':
                    $controller->edit();
                    break;
                case 'update':
                    $controller->update();
                    break;
                case 'delete':
                    $controller->delete();
                    break;
                case 'eliminarImagen':
                    $controller->eliminarImagen();
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;

        default:
            include 'views/pages/404.php';
    }
}
// Si no hay controlador → Cargar home normal
else {
    $GLOBALS['T'] = load_translations_db($conn, $GLOBALS['LOCALE'], ['header', 'home', 'footer']);
    $HOME_CONTENT = load_contents_db($conn, $GLOBALS['LOCALE'], 'home');
    include 'views/layouts/header.php';
    include 'views/pages/home.php';
    include 'views/layouts/footer.php';
}