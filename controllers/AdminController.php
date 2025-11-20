<?php
// controllers/AdminController.php

require_once 'models/DashboardModel.php';

class AdminController {
    private $conn;
    private $dashboardModel;

    public function __construct($conn) {
        $this->conn = $conn;

        // Inicializar el modelo de dashboard
        $this->dashboardModel = new DashboardModel($conn);

        if (session_status() === PHP_SESSION_NONE)
            session_start();

        // Solo admin y operador
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrador', 'operador'])) {
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
    }

    public function dashboard($lang) {
        $GLOBALS['T'] = load_translations_db($this->conn, $lang, ['header', 'footer', 'admin']);
        $userName = $_SESSION['user_name'];
        $userRole = $_SESSION['user_role'];

        // Obtener TODOS los datos necesarios para el dashboard
        $ocupacionHabitaciones = $this->dashboardModel->obtenerOcupacionHabitaciones();
        $ocupacionPorTipo = $this->dashboardModel->obtenerOcupacionPorTipo();
        $estadisticasReservas = $this->dashboardModel->obtenerEstadisticasReservas();
        $reservasPorDia = $this->dashboardModel->obtenerReservasPorDia();
        $estadoReservas = $this->dashboardModel->obtenerEstadoReservas();
        $top5Habitaciones = $this->dashboardModel->obtenerTop5Habitaciones();

        // Incluir las vistas
        include 'views/layouts/header_admin.php';
        include 'views/admin/dashboard.php';
        include 'views/layouts/footer_admin.php';
    }
}