<?php
// Funciones auxiliares del sistema

function XXXXXXXXXsanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function XXXXXXXXXredirect($url) {
    header("Location: " . $url);
    exit();
}

function XXXXXXisLoggedIn() {
    return isset($_SESSION['user_id']);
}

function XXXXXXXgetUserRole() {
    return $_SESSION['user_rol'] ?? null;
}

function checkRole($roles) {
    if (!isLoggedIn()) redirect('/login.php');
    if (!in_array(getUserRole(), $roles)) redirect('/index.php');
}


/**
 * Funciones auxiliares del sistema
 * Incluye helpers de sesión, autenticación, sanitización y utilidades generales
 */

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si el usuario está autenticado
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtiene el ID del usuario actual
 * @return int|null
 */
function getUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtiene el rol del usuario actual
 * @return string|null 'administrador', 'operador', 'usuario'
 */
function getUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Obtiene el email del usuario actual
 * @return string|null
 */
function getUserEmail(): ?string {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Verifica si el usuario tiene un rol específico
 * @param string $role
 * @return bool
 */
function hasRole(string $role): bool {
    return isLoggedIn() && getUserRole() === $role;
}

/**
 * Verifica si el usuario es administrador
 * @return bool
 */
function isAdmin(): bool {
    return hasRole('administrador');
}

/**
 * Verifica si el usuario es operador
 * @return bool
 */
function isOperator(): bool {
    return hasRole('operador');
}

/**
 * Redirige a una URL
 * @param string $url
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Redirige si el usuario NO está autenticado
 * @param string $redirect_to URL de destino (por defecto login.php)
 */
function requireLogin(string $redirect_to = '/login.php'): void {
    if (!isLoggedIn()) {
        redirect($redirect_to);
    }
}

/**
 * Redirige si el usuario NO tiene el rol especificado
 * @param string $role
 * @param string $redirect_to
 */
function requireRole(string $role, string $redirect_to = '/index.php'): void {
    requireLogin();
    if (!hasRole($role)) {
        redirect($redirect_to);
    }
}

/**
 * Cierra la sesión del usuario
 */
function logout(): void {
    session_unset();
    session_destroy();
    redirect('/index.php');
}

/**
 * Sanitiza una cadena de texto
 * @param string $str
 * @return string
 */
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza un email
 * @param string $email
 * @return string|false
 */
function sanitizeEmail(string $email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Valida un email
 * @param string $email
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitiza un número entero
 * @param mixed $value
 * @return int
 */
function sanitizeInt($value): int {
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitiza un número decimal
 * @param mixed $value
 * @return float
 */
function sanitizeFloat($value): float {
    return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Genera un token CSRF y lo guarda en sesión
 * @return string
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatea una fecha en español o inglés según el idioma actual
 * @param string $date Fecha en formato SQL (Y-m-d H:i:s)
 * @param string $format Formato de salida (por defecto 'd/m/Y')
 * @return string
 */
function formatDate(string $date, string $format = 'd/m/Y'): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    return date($format, $timestamp);
}

/**
 * Formatea un precio con símbolo de moneda
 * @param float $amount
 * @param string $currency Símbolo de moneda (por defecto '$')
 * @return string
 */
function formatPrice(float $amount, string $currency = '$'): string {
    return $currency . number_format($amount, 2, ',', '.');
}

/**
 * Sube un archivo al servidor
 * @param array $file Array $_FILES['nombre']
 * @param string $destination Carpeta de destino (relativa a htdocs)
 * @param array $allowed_types Tipos MIME permitidos
 * @param int $max_size Tamaño máximo en bytes (por defecto 5MB)
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function uploadFile(array $file, string $destination, array $allowed_types = [], int $max_size = 5242880): array {
    // Validar que el archivo existe
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'filename' => null, 'error' => 'No se recibió ningún archivo'];
    }

    // Validar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Error al subir el archivo'];
    }

    // Validar tamaño
    if ($file['size'] > $max_size) {
        return ['success' => false, 'filename' => null, 'error' => 'El archivo es demasiado grande'];
    }

    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!empty($allowed_types) && !in_array($mime, $allowed_types, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Tipo de archivo no permitido'];
    }

    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('file_', true) . '.' . $extension;
    $filepath = rtrim($destination, '/') . '/' . $filename;

    // Crear directorio si no existe
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'error' => null];
    }

    return ['success' => false, 'filename' => null, 'error' => 'No se pudo guardar el archivo'];
}

/**
 * Elimina un archivo del servidor
 * @param string $filepath Ruta completa del archivo
 * @return bool
 */
function deleteFile(string $filepath): bool {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Genera un slug a partir de un texto
 * @param string $text
 * @return string
 */
function slugify(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', strtolower($text));
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Registra un mensaje en el log
 * @param string $message
 * @param string $level 'info', 'warning', 'error'
 */
function logMessage(string $message, string $level = 'info'): void {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $log_file = $log_dir . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Devuelve la URL actual completa
 * @return string
 */
function getCurrentUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return "$protocol://$host$uri";
}

/**
 * Agrega o actualiza un parámetro en la URL actual
 * @param string $key
 * @param string $value
 * @return string
 */
function addQueryParam(string $key, string $value): string {
    $url = parse_url(getCurrentUrl());
    parse_str($url['query'] ?? '', $params);
    $params[$key] = $value;
    $query = http_build_query($params);
    return $url['path'] . ($query ? '?' . $query : '');
}
?>
