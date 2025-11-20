<?php
/**
 * Router: agradecimiento.php
 * Carga la vista de agradecimiento después de una reserva exitosa
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/i18n_db.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar idioma
$GLOBALS['LOCALE'] = detectLanguage();

// Cargar traducciones
$GLOBALS['T'] = load_translations_db($conn, $GLOBALS['LOCALE'], ['header', 'footer', 'agradecimiento']);

// Incluir header
include 'views/layouts/header.php';

// Incluir vista de agradecimiento
include 'views/pages/agradecimiento.php';

// Incluir footer
include 'views/layouts/footer.php';
?>