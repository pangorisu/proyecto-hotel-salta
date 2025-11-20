<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'models/UsuarioModel.php';

class AuthController {
    private $model;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new UsuarioModel($conn);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Mostrar formulario de login o procesar envío
    public function login($lang) 
    {
        $error = null;

        // Si ya está logueado → redirigir según rol
        if (isset($_SESSION['user_id'])) {
            if (in_array($_SESSION['user_role'], ['administrador', 'operador'])) {
                header('Location: index.php?controller=admin&action=dashboard');
            } else {
                header('Location: index.php');
            }
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $usuario = $this->model->obtenerUsuarioPorEmail($email);

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                // Crear variables de sesión
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_name'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                $_SESSION['user_role'] = $usuario['rol'];

                // Actualizar última conexión
                $this->model->actualizarUltimaConexion($usuario['id']);

                // Redirigir según rol
                
                if (in_array($usuario['rol'], ['administrador', 'operador'])) {
                    header('Location: index.php?controller=admin&action=dashboard');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = ($lang == 'es-AR')
                    ? 'Correo o contraseña incorrectos.'
                    : 'Incorrect email or password.';
            }
        }

        $conn = $this->conn;
        include 'views/layouts/header_min.php';
        include 'views/pages/login.php';
        include 'views/layouts/footer_min.php';
    }

    // Cerrar sesión
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }
}