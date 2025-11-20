<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
checkRole(['usuario', 'operador', 'administrador']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <h1>Mi Panel</h1>
    <p>Bienvenido, <?php echo $_SESSION['user_nombre']; ?></p>
    <nav>
        <a href="mis-reservas.php">Mis Reservas</a>
        <a href="/habitaciones.php">Ver Habitaciones</a>
        <a href="/logout.php">Cerrar Sesión</a>
    </nav>
</body>
</html>
