@echo off
chcp 65001 >nul
echo ============================================
echo   CREANDO ESTRUCTURA DEL PROYECTO
echo   Sistema de Reservas Hoteleras
echo   Directamente en carpeta actual
echo ============================================
echo.

REM ============================================
REM Estructura de carpetas principales
REM ============================================
echo [1/8] Creando carpetas principales...
mkdir assets
mkdir assets\css
mkdir assets\js
mkdir assets\images
mkdir assets\images\habitaciones
mkdir assets\images\galeria
mkdir assets\fonts

mkdir config
mkdir includes
mkdir admin
mkdir operador
mkdir usuario
mkdir api
mkdir uploads
mkdir uploads\habitaciones
mkdir uploads\usuarios

echo [2/8] Creando carpetas de vistas...
mkdir views
mkdir views\layouts
mkdir views\pages
mkdir views\admin
mkdir views\operador
mkdir views\usuario

echo [3/8] Creando carpetas de lógica...
mkdir controllers
mkdir models
mkdir middleware

echo [4/8] Creando carpetas de utilidades...
mkdir utils
mkdir logs

REM ============================================
REM Crear archivos de configuración
REM ============================================
echo [5/8] Creando archivos de configuración...

REM config/database.php
(
echo ^<?php
echo // Configuración de conexión a SQL Server
echo define^('DB_SERVER', 'localhost'^);
echo define^('DB_DATABASE', 'ReservaHotel'^);
echo define^('DB_USERNAME', 'sa'^);
echo define^('DB_PASSWORD', 'tuPassword'^);
echo.
echo try {
echo     $conn = new PDO^("sqlsrv:Server=" . DB_SERVER . ";Database=" . DB_DATABASE, DB_USERNAME, DB_PASSWORD^);
echo     $conn-^>setAttribute^(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION^);
echo } catch^(PDOException $e^) {
echo     die^("Error de conexión: " . $e-^>getMessage^(^)^);
echo }
echo ?^>
) > config\database.php

REM config/config.php
(
echo ^<?php
echo // Configuración general del sistema
echo session_start^(^);
echo.
echo define^('SITE_NAME', 'Hotel Reservas'^);
echo define^('SITE_URL', 'http://localhost'^);
echo define^('ADMIN_EMAIL', 'admin@hotel.com'^);
echo.
echo // Zona horaria
echo date_default_timezone_set^('America/Argentina/Buenos_Aires'^);
echo.
echo // Rutas
echo define^('ROOT_PATH', __DIR__ . '/..''^);
echo define^('ASSETS_PATH', SITE_URL . '/assets'^);
echo define^('UPLOADS_PATH', SITE_URL . '/uploads'^);
echo ?^>
) > config\config.php

REM ============================================
REM Crear archivos de includes
REM ============================================
echo [6/8] Creando archivos de includes...

REM includes/functions.php
(
echo ^<?php
echo // Funciones auxiliares del sistema
echo.
echo function sanitize^($data^) {
echo     return htmlspecialchars^(strip_tags^(trim^($data^)^)^);
echo }
echo.
echo function redirect^($url^) {
echo     header^("Location: " . $url^);
echo     exit^(^);
echo }
echo.
echo function isLoggedIn^(^) {
echo     return isset^($_SESSION['user_id']^);
echo }
echo.
echo function getUserRole^(^) {
echo     return $_SESSION['user_rol'] ?? null;
echo }
echo.
echo function checkRole^($roles^) {
echo     if ^(!isLoggedIn^(^)^) redirect^('/login.php'^);
echo     if ^(!in_array^(getUserRole^(^), $roles^)^) redirect^('/index.php'^);
echo }
echo ?^>
) > includes\functions.php

REM includes/auth.php
(
echo ^<?php
echo // Sistema de autenticación
echo require_once __DIR__ . '/../config/database.php';
echo require_once __DIR__ . '/functions.php';
echo.
echo function login^($email, $password^) {
echo     global $conn;
echo     $stmt = $conn-^>prepare^("SELECT * FROM usuarios WHERE email = ? AND estado = 1"^);
echo     $stmt-^>execute^([$email]^);
echo     $user = $stmt-^>fetch^(PDO::FETCH_ASSOC^);
echo.    
echo     if ^($user ^&^& password_verify^($password, $user['password_hash']^)^) {
echo         $_SESSION['user_id'] = $user['id'];
echo         $_SESSION['user_nombre'] = $user['nombre'];
echo         $_SESSION['user_rol'] = $user['rol'];
echo         return true;
echo     }
echo     return false;
echo }
echo.
echo function logout^(^) {
echo     session_destroy^(^);
echo     redirect^('/login.php'^);
echo }
echo ?^>
) > includes\auth.php

REM ============================================
REM Crear archivos principales
REM ============================================
echo [7/8] Creando archivos principales...

REM index.php
(
echo ^<?php
echo require_once 'config/config.php';
echo require_once 'config/database.php';
echo require_once 'includes/functions.php';
echo include 'views/layouts/header.php';
echo include 'views/pages/home.php';
echo include 'views/layouts/footer.php';
echo ?^>
) > index.php

REM login.php
(
echo ^<?php
echo require_once 'config/config.php';
echo require_once 'includes/auth.php';
echo.
echo if ^($_SERVER['REQUEST_METHOD'] == 'POST'^) {
echo     $email = sanitize^($_POST['email']^);
echo     $password = $_POST['password'];
echo.    
echo     if ^(login^($email, $password^)^) {
echo         $rol = getUserRole^(^);
echo         if ^($rol == 'administrador'^) redirect^('/admin/dashboard.php'^);
echo         if ^($rol == 'operador'^) redirect^('/operador/dashboard.php'^);
echo         redirect^('/usuario/dashboard.php'^);
echo     } else {
echo         $error = "Credenciales incorrectas";
echo     }
echo }
echo.
echo include 'views/pages/login.php';
echo ?^>
) > login.php

REM logout.php
(
echo ^<?php
echo require_once 'config/config.php';
echo require_once 'includes/auth.php';
echo logout^(^);
echo ?^>
) > logout.php

REM register.php
(
echo ^<?php
echo require_once 'config/config.php';
echo require_once 'config/database.php';
echo include 'views/pages/register.php';
echo ?^>
) > register.php

REM habitaciones.php
(
echo ^<?php
echo require_once 'config/config.php';
echo require_once 'config/database.php';
echo include 'views/layouts/header.php';
echo include 'views/pages/habitaciones.php';
echo include 'views/layouts/footer.php';
echo ?^>
) > habitaciones.php

REM contacto.php
(
echo ^<?php
echo require_once 'config/config.php';
echo require_once 'config/database.php';
echo include 'views/layouts/header.php';
echo include 'views/pages/contacto.php';
echo include 'views/layouts/footer.php';
echo ?^>
) > contacto.php

REM galeria.php
(
echo ^<?php
echo require_once 'config/config.php';
echo include 'views/layouts/header.php';
echo include 'views/pages/galeria.php';
echo include 'views/layouts/footer.php';
echo ?^>
) > galeria.php

REM ============================================
REM Crear archivos de vistas
REM ============================================

REM views/layouts/header.php
(
echo ^<!DOCTYPE html^>
echo ^<html lang="es"^>
echo ^<head^>
echo     ^<meta charset="UTF-8"^>
echo     ^<meta name="viewport" content="width=device-width, initial-scale=1.0"^>
echo     ^<title^>^<?php echo SITE_NAME; ?^>^</title^>
echo     ^<link rel="stylesheet" href="^<?php echo ASSETS_PATH; ?^>/css/style.css"^>
echo     ^<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"^>
echo ^</head^>
echo ^<body^>
echo     ^<header^>
echo         ^<nav^>
echo             ^<a href="/index.php"^>Inicio^</a^>
echo             ^<a href="/habitaciones.php"^>Habitaciones^</a^>
echo             ^<a href="/galeria.php"^>Galería^</a^>
echo             ^<a href="/contacto.php"^>Contacto^</a^>
echo             ^<?php if^(isLoggedIn^(^)^): ?^>
echo                 ^<a href="/logout.php"^>Cerrar Sesión^</a^>
echo             ^<?php else: ?^>
echo                 ^<a href="/login.php"^>Iniciar Sesión^</a^>
echo             ^<?php endif; ?^>
echo         ^</nav^>
echo     ^</header^>
echo     ^<main^>
) > views\layouts\header.php

REM views/layouts/footer.php
(
echo     ^</main^>
echo     ^<footer^>
echo         ^<p^>^&copy; 2025 ^<?php echo SITE_NAME; ?^>. Todos los derechos reservados.^</p^>
echo     ^</footer^>
echo     ^<script src="^<?php echo ASSETS_PATH; ?^>/js/script.js"^>^</script^>
echo ^</body^>
echo ^</html^>
) > views\layouts\footer.php

REM views/pages/home.php
(
echo ^<section class="hero"^>
echo     ^<h1^>Bienvenido a nuestro Hotel^</h1^>
echo     ^<p^>Reserva tu estadía con nosotros^</p^>
echo ^</section^>
) > views\pages\home.php

REM views/pages/login.php
(
echo ^<!DOCTYPE html^>
echo ^<html lang="es"^>
echo ^<head^>
echo     ^<meta charset="UTF-8"^>
echo     ^<meta name="viewport" content="width=device-width, initial-scale=1.0"^>
echo     ^<title^>Iniciar Sesión^</title^>
echo     ^<link rel="stylesheet" href="/assets/css/style.css"^>
echo ^</head^>
echo ^<body^>
echo     ^<div class="login-container"^>
echo         ^<h2^>Iniciar Sesión^</h2^>
echo         ^<?php if^(isset^($error^)^): ?^>
echo             ^<p class="error"^>^<?php echo $error; ?^>^</p^>
echo         ^<?php endif; ?^>
echo         ^<form method="POST" action=""^>
echo             ^<input type="email" name="email" placeholder="Email" required^>
echo             ^<input type="password" name="password" placeholder="Contraseña" required^>
echo             ^<button type="submit"^>Ingresar^</button^>
echo         ^</form^>
echo         ^<p^>^<a href="/register.php"^>¿No tienes cuenta? Regístrate^</a^>^</p^>
echo     ^</div^>
echo ^</body^>
echo ^</html^>
) > views\pages\login.php

REM views/pages/habitaciones.php
(
echo ^<section class="habitaciones"^>
echo     ^<h1^>Nuestras Habitaciones^</h1^>
echo     ^<?php
echo     $stmt = $conn-^>query^("SELECT * FROM habitaciones WHERE estado = 'disponible'"^);
echo     while ^($hab = $stmt-^>fetch^(PDO::FETCH_ASSOC^)^): ?^>
echo         ^<div class="habitacion-card"^>
echo             ^<h3^>^<?php echo $hab['nombre']; ?^>^</h3^>
echo             ^<p^>^<?php echo $hab['descripcion']; ?^>^</p^>
echo             ^<p^>Precio: $^<?php echo $hab['precio_noche']; ?^> por noche^</p^>
echo         ^</div^>
echo     ^<?php endwhile; ?^>
echo ^</section^>
) > views\pages\habitaciones.php

REM views/pages/contacto.php
(
echo ^<section class="contacto"^>
echo     ^<h1^>Contáctanos^</h1^>
echo     ^<form method="POST" action="/api/enviar-mensaje.php"^>
echo         ^<input type="text" name="nombre" placeholder="Nombre" required^>
echo         ^<input type="email" name="email" placeholder="Email" required^>
echo         ^<input type="text" name="asunto" placeholder="Asunto" required^>
echo         ^<textarea name="mensaje" placeholder="Mensaje" required^>^</textarea^>
echo         ^<button type="submit"^>Enviar^</button^>
echo     ^</form^>
echo ^</section^>
) > views\pages\contacto.php

REM views/pages/galeria.php
(
echo ^<section class="galeria"^>
echo     ^<h1^>Galería^</h1^>
echo     ^<div class="galeria-grid"^>
echo         ^<!-- Aquí irán las imágenes --^>
echo     ^</div^>
echo ^</section^>
) > views\pages\galeria.php

REM ============================================
REM Crear dashboards
REM ============================================

REM admin/dashboard.php
(
echo ^<?php
echo require_once '../config/config.php';
echo require_once '../includes/functions.php';
echo checkRole^(['administrador']^);
echo ?^>
echo ^<!DOCTYPE html^>
echo ^<html^>
echo ^<head^>
echo     ^<title^>Panel Administrador^</title^>
echo     ^<link rel="stylesheet" href="/assets/css/style.css"^>
echo ^</head^>
echo ^<body^>
echo     ^<h1^>Panel de Administrador^</h1^>
echo     ^<p^>Bienvenido, ^<?php echo $_SESSION['user_nombre']; ?^>^</p^>
echo     ^<nav^>
echo         ^<a href="habitaciones.php"^>Gestionar Habitaciones^</a^>
echo         ^<a href="operadores.php"^>Gestionar Operadores^</a^>
echo         ^<a href="/logout.php"^>Cerrar Sesión^</a^>
echo     ^</nav^>
echo ^</body^>
echo ^</html^>
) > admin\dashboard.php

REM operador/dashboard.php
(
echo ^<?php
echo require_once '../config/config.php';
echo require_once '../includes/functions.php';
echo checkRole^(['operador', 'administrador']^);
echo ?^>
echo ^<!DOCTYPE html^>
echo ^<html^>
echo ^<head^>
echo     ^<title^>Panel Operador^</title^>
echo     ^<link rel="stylesheet" href="/assets/css/style.css"^>
echo ^</head^>
echo ^<body^>
echo     ^<h1^>Panel de Operador^</h1^>
echo     ^<p^>Bienvenido, ^<?php echo $_SESSION['user_nombre']; ?^>^</p^>
echo     ^<nav^>
echo         ^<a href="mapa-habitaciones.php"^>Mapa de Habitaciones^</a^>
echo         ^<a href="reservas.php"^>Gestionar Reservas^</a^>
echo         ^<a href="pagos.php"^>Procesar Pagos^</a^>
echo         ^<a href="mensajes.php"^>Mensajes^</a^>
echo         ^<a href="/logout.php"^>Cerrar Sesión^</a^>
echo     ^</nav^>
echo ^</body^>
echo ^</html^>
) > operador\dashboard.php

REM usuario/dashboard.php
(
echo ^<?php
echo require_once '../config/config.php';
echo require_once '../includes/functions.php';
echo checkRole^(['usuario', 'operador', 'administrador']^);
echo ?^>
echo ^<!DOCTYPE html^>
echo ^<html^>
echo ^<head^>
echo     ^<title^>Mi Panel^</title^>
echo     ^<link rel="stylesheet" href="/assets/css/style.css"^>
echo ^</head^>
echo ^<body^>
echo     ^<h1^>Mi Panel^</h1^>
echo     ^<p^>Bienvenido, ^<?php echo $_SESSION['user_nombre']; ?^>^</p^>
echo     ^<nav^>
echo         ^<a href="mis-reservas.php"^>Mis Reservas^</a^>
echo         ^<a href="/habitaciones.php"^>Ver Habitaciones^</a^>
echo         ^<a href="/logout.php"^>Cerrar Sesión^</a^>
echo     ^</nav^>
echo ^</body^>
echo ^</html^>
) > usuario\dashboard.php

REM ============================================
REM Crear archivo .htaccess
REM ============================================
(
echo RewriteEngine On
echo RewriteCond %%{REQUEST_FILENAME} !-f
echo RewriteCond %%{REQUEST_FILENAME} !-d
) > .htaccess

REM ============================================
REM Crear README
REM ============================================
echo [8/8] Creando documentación...
(
echo # Sistema de Reservas Hoteleras
echo.
echo ## Estructura del Proyecto
echo.
echo - **/config** - Archivos de configuración
echo - **/assets** - CSS, JS, imágenes
echo - **/views** - Vistas HTML/PHP
echo - **/controllers** - Lógica de negocio
echo - **/models** - Modelos de datos
echo - **/admin** - Panel de administrador
echo - **/operador** - Panel de operador
echo - **/usuario** - Panel de usuario
echo.
echo ## Instalación
echo.
echo 1. Ejecutar crear_estructura.bat en htdocs
echo 2. Importar base de datos SQL Server ^(ReservaHotel^)
echo 3. Configurar config/database.php con tu contraseña
echo 4. Acceder a http://localhost
echo.
echo ## Usuarios por defecto
echo.
echo - Admin: admin@hotel.com / password
echo - Operador: operador@hotel.com / password
echo - Usuario: cliente@gmail.com / password
) > README.md

echo.
echo ============================================
echo   ✓ ESTRUCTURA CREADA EXITOSAMENTE
echo ============================================
echo.
echo Ubicación: Carpeta actual ^(htdocs^)
echo Base de datos: ReservaHotel
echo.
echo Próximos pasos:
echo 1. Editar config/database.php con tu contraseña de SQL Server
echo 2. Importar la base de datos ReservaHotel
echo 3. Acceder a http://localhost
echo.
pause