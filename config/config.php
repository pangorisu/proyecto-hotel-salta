<?php
// Configuración general del sistema
session_start();

define('SITE_NAME', 'Hotel Reservas');
define('SITE_URL', '');
define('ADMIN_EMAIL', 'admin@hotel.com');

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Rutas
define('ROOT_PATH', __DIR__ . '/..');
define('ASSETS_PATH', SITE_URL . '/assets');
define('UPLOADS_PATH', SITE_URL . '/uploads');
?>
