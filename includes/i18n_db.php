<?php
/**
 * Sistema de internacionalización (i18n) basado en base de datos
 * Gestiona detección de idioma, carga de traducciones y contenidos multiidioma
 */

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar dependencias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Constantes de configuración
define('DEFAULT_LOCALE_DB', 'es-AR');
define('SUPPORTED_LOCALES_DB', ['es-AR', 'en-US']);

/**
 * Detecta el idioma del usuario según prioridad:
 * 1. Parámetro GET ?lang=xx-YY
 * 2. Parámetro POST lang
 * 3. Sesión previa
 * 4. Cabecera Accept-Language del navegador
 * 5. Idioma por defecto
 * 
 * @return string Código de idioma (ej: 'es-AR', 'en-US')
 */
function detect_locale_db(): string {
    // 1. Query string
    if (!empty($_GET['lang'])) {
        $lang = sanitize_lang($_GET['lang']);
        if (in_array($lang, SUPPORTED_LOCALES_DB, true)) {
            $_SESSION['locale'] = $lang;
            return $lang;
        }
    }

    // 2. POST
    if (!empty($_POST['lang'])) {
        $lang = sanitize_lang($_POST['lang']);
        if (in_array($lang, SUPPORTED_LOCALES_DB, true)) {
            $_SESSION['locale'] = $lang;
            return $lang;
        }
    }

    // 3. Sesión
    if (!empty($_SESSION['locale']) && in_array($_SESSION['locale'], SUPPORTED_LOCALES_DB, true)) {
        return $_SESSION['locale'];
    }

    // 4. Accept-Language del navegador
    $accepted = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $guess = map_accept_language_to_supported($accepted);
    $_SESSION['locale'] = $guess;
    return $guess;
}

/**
 * Sanitiza un código de idioma
 * @param string $lang
 * @return string
 */
function sanitize_lang(string $lang): string {
    // Patrón: xx-YY (ej: es-AR, en-US)
    if (preg_match('/^[a-z]{2}-[A-Z]{2}$/', $lang)) {
        return $lang;
    }
    return DEFAULT_LOCALE_DB;
}

/**
 * Mapea la cabecera Accept-Language a un idioma soportado
 * @param string $accept
 * @return string
 */
function map_accept_language_to_supported(string $accept): string {
    $accept = strtolower($accept);
    
    // Español (Argentina)
    if (strpos($accept, 'es-ar') !== false) {
        return 'es-AR';
    }
    
    // Español (genérico)
    if (strpos($accept, 'es') !== false) {
        return 'es-AR';
    }
    
    // Inglés (Estados Unidos)
    if (strpos($accept, 'en-us') !== false) {
        return 'en-US';
    }
    
    // Inglés (genérico)
    if (strpos($accept, 'en') !== false) {
        return 'en-US';
    }
    
    return DEFAULT_LOCALE_DB;
}

/**
 * Carga traducciones desde la base de datos
 * @param PDO $conn Conexión a la base de datos
 * @param string $locale Código de idioma
 * @param array $pages Array de páginas a cargar (ej: ['header','home','footer'])
 * @return array Array asociativo [clavetexto => traduccion]
 */
function load_translations_db($conn, string $locale, array $pages): array {
    if (empty($pages)) {
        return [];
    }

    try {
        $placeholders = implode(',', array_fill(0, count($pages), '?'));
        $sql = "SELECT clavetexto, traduccion
                FROM dbo.Traducciones
                WHERE idioma_codigo = ?
                  AND pagina IN ($placeholders)";
        
        $params = array_merge([$locale], $pages);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['clavetexto']] = $row['traduccion'];
        }
        
        return $result;
    } catch (PDOException $e) {
        logMessage("Error cargando traducciones: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * Carga contenidos largos desde la base de datos
 * @param PDO $conn Conexión a la base de datos
 * @param string $locale Código de idioma
 * @param string $page Página (ej: 'home', 'habitaciones')
 * @param string|null $module Módulo específico (opcional)
 * @return array Array agrupado por módulo
 */
function load_contents_db($conn, string $locale, string $page, ?string $module = null): array {
    try {
        $sql = "SELECT modulo, clave, titulo, subtitulo, cuerpo, imagen_url, imagen_alt, orden
                FROM dbo.Contenidos
                WHERE idioma_codigo = ?
                  AND pagina = ?
                  AND (? IS NULL OR modulo = ?)
                  AND (activo = 1 OR activo IS NULL)
                ORDER BY COALESCE(orden, 9999), clave";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$locale, $page, $module, $module]);
        
        $out = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[$r['modulo']][] = $r;
        }
        
        return $out;
    } catch (PDOException $e) {
        logMessage("Error cargando contenidos: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * Carga elementos de galería desde la base de datos
 * @param PDO $conn Conexión a la base de datos
 * @param string $locale Código de idioma
 * @param string $page Página
 * @param string|null $categoria Categoría específica (opcional)
 * @return array Array de elementos de galería
 */
function load_gallery_db($conn, string $locale, string $page, ?string $categoria = null): array {
    try {
        $sql = "SELECT id, categoria, imagen_url, imagen_alt, texto, orden
                FROM dbo.Galeria
                WHERE idioma_codigo = ?
                  AND pagina = ?
                  AND (? IS NULL OR categoria = ?)
                  AND (activo = 1 OR activo IS NULL)
                ORDER BY COALESCE(orden, 9999), id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$locale, $page, $categoria, $categoria]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logMessage("Error cargando galería: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * Obtiene los idiomas disponibles desde la base de datos
 * @param PDO $conn Conexión a la base de datos
 * @return array Array de idiomas con código, nombre y bandera_url
 */
function get_available_languages($conn): array {
    try {
        $sql = "SELECT codigo, nombre, bandera_url
                FROM dbo.Idiomas
                WHERE activo = 1
                ORDER BY nombre";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logMessage("Error cargando idiomas: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * Carga traducciones de habitaciones
 * @param PDO $conn Conexión a la base de datos
 * @param string $locale Código de idioma
 * @param int|null $habitacion_id ID de habitación específica (opcional)
 * @return array Array de traducciones de habitaciones
 */
function load_room_translations_db($conn, string $locale, ?int $habitacion_id = null): array {
    try {
        $sql = "SELECT ht.habitacion_id, ht.titulo, ht.resumen, ht.descripcion, 
                       ht.imagen_url, ht.imagen_alt,
                       h.codigo, h.precio, h.estado
                FROM dbo.Habitacion_Traducciones ht
                INNER JOIN dbo.Habitaciones h ON ht.habitacion_id = h.id
                WHERE ht.idioma_codigo = ?
                  AND (? IS NULL OR ht.habitacion_id = ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$locale, $habitacion_id, $habitacion_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logMessage("Error cargando traducciones de habitaciones: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * Helper de traducción global
 * Busca una clave en el array de traducciones cargado
 * @param string $key Clave de traducción
 * @param string $fallback Texto por defecto si no se encuentra la clave
 * @return string Traducción o fallback
 */
function __t_db(string $key, string $fallback = ''): string {
    return $GLOBALS['T'][$key] ?? ($fallback !== '' ? $fallback : $key);
}

/**
 * Carga slides del banner principal
 * @param PDO $conn Conexión a la base de datos
 * @param string $locale Código de idioma
 * @return array Array de slides ordenados
 */
function load_banner_slides($conn, string $locale): array {
    try {
        $sql = "SELECT id, titulo, subtitulo, texto_boton, url_boton, 
                       imagen_nombre, imagen_alt, duracion_segundos
                FROM dbo.Banner_Slides
                WHERE idioma_codigo = ?
                  AND activo = 1
                ORDER BY orden ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$locale]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logMessage("Error cargando slides del banner: " . $e->getMessage(), 'error');
        return [];
    }
}

// Inicialización global del sistema i18n
$GLOBALS['LOCALE'] = detect_locale_db();
$GLOBALS['T'] = []; // Se llenará por página según necesidad

/**
 * ============================================
 * ALIASES DE COMPATIBILIDAD
 * Para mantener compatibilidad con código legacy
 * ============================================
 */

/**
 * Alias para detectLanguage (usado en controladores)
 */
function detectLanguage() {
    return detect_locale_db();
}

/**
 * Alias para getAvailableLanguages
 */
function getAvailableLanguages($conn) {
    return get_available_languages($conn);
}
?>