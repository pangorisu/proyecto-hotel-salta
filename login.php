<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (login($email, $password)) {
        $rol = getUserRole();
        if ($rol == 'administrador') redirect('/admin/dashboard.php');
        if ($rol == 'operador') redirect('/operador/dashboard.php');
        redirect('/usuario/dashboard.php');
    } else {
        $error = "Credenciales incorrectas";
    }
}

include 'views/pages/login.php';
?>
