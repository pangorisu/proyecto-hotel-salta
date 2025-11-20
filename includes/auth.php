<?php
// Sistema de autenticación
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function login($email, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    redirect('/login.php');
}
?>
