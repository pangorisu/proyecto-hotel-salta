<?php
// Configuración de conexión a SQL Server
define('DB_SERVER', 'MI SERVIDOR');
define('DB_DATABASE', 'ReservaHotel');
define('DB_USERNAME', 'usrhotel');
define('DB_PASSWORD', 'usrhotel');

try {
    $conn = new PDO("sqlsrv:Server=" . DB_SERVER . ";Database=" . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
