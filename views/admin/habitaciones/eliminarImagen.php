<?php
// views/admin/habitaciones/eliminarImagen.php
require_once '../../../config/database.php';
require_once '../../../controllers/HabitacionCRUDController.php';

$lang = 'es-AR';
$controller = new HabitacionCRUDController($conn, $lang);
$controller->eliminarImagen();