<?php
require_once __DIR__ . '/../models/ReservaModel.php';

class HomeController {
    private $conn;
    private $reservaModel;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->reservaModel = new ReservaModel($conn);
    }

    public function getTiposHabitacion($idioma) {
        return $this->reservaModel->obtenerTiposHabitacionPorIdioma($idioma);
    }
}