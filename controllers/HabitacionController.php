<?php
require_once 'models/HabitacionModel.php';

class HabitacionController {
    private $model;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new HabitacionModel($conn);
    }

    public function index($lang) 
    {
        $habitaciones = $this->model->obtenerHabitaciones($lang);
        $conn = $this->conn; // pasa conexión
        include 'views/pages/habitaciones.php';
    }

    public function detalle($id, $lang) {
        $habitacion = $this->model->obtenerHabitacionPorId($id, $lang);
        $imagenes = $this->model->obtenerImagenesHabitacion($id);
        $conn = $this->conn; // pasa conexión
        include 'views/pages/habitacion-detalle.php';
    }
}