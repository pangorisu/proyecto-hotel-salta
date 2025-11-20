<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
checkRole(['operador', 'administrador']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel Operador</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <h1>Panel de Operador</h1>
    <p>Bienvenido, <?php echo $_SESSION['user_nombre']; ?></p>
    <nav>
        <a href="mapa-habitaciones.php">Mapa de Habitaciones</a>
        <a href="reservas.php">Gestionar Reservas</a>
        <a href="pagos.php">Procesar Pagos</a>
        <a href="mensajes.php">Mensajes</a>
        <a href="/logout.php">Cerrar Sesión</a>
    </nav>
</body>
</html>
