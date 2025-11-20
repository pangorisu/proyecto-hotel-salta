<?php
// views/admin/habitaciones/listado.php
require_once '../../../config/database.php';
require_once '../../../controllers/HabitacionCRUDController.php';

$lang = 'es-AR'; // o según tu lógica de idioma
$controller = new HabitacionCRUDController($conn, $lang);
$controller->index();