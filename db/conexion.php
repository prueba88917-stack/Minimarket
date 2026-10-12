<?php
/**
 * Conexión Robusta a Base de Datos MySQL
 * Compatible con:
 * 1. Entorno Local XAMPP
 * 2. Variables de Entorno de Railway (MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE o DATABASE_URL)
 * 3. Conexiones externas como DBeaver
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración por defecto (Local XAMPP)
$db_host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$db_user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$db_pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$db_name = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'don_luis_db');
$db_port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306');

// Si viene la variable DATABASE_URL de Railway (formato: mysql://user:pass@host:port/dbname)
$database_url = getenv('DATABASE_URL');
if (!empty($database_url)) {
    $db_parts = parse_url($database_url);
    if ($db_parts) {
        $db_host = $db_parts['host'] ?? $db_host;
        $db_port = $db_parts['port'] ?? $db_port;
        $db_user = $db_parts['user'] ?? $db_user;
        $db_pass = $db_parts['pass'] ?? $db_pass;
        $db_name = ltrim($db_parts['path'] ?? '', '/') ?: $db_name;
    }
}

try {
    $dsn = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
    // Primero conectamos para asegurar base de datos si es local
    $pdo_init = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Crear BD si no existe (útil en primera ejecución local)
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Conectar a la base de datos específica
    $dsn_db = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn_db, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Auto-migración silenciosa si no existen las tablas
    $check_table = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetch();
    if (!$check_table) {
        $sql_file = __DIR__ . '/datos.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $pdo->exec($sql);
        }
    }

} catch (PDOException $e) {
    // Si la conexión falla, se muestra un mensaje informativo amigable
    $db_error = $e->getMessage();
}

/**
 * Funciones de utilidad y seguridad
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador';
}

function requerirLogin() {
    if (!estaAutenticado()) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

function requerirAdmin() {
    requerirLogin();
    if (!esAdmin()) {
        header("Location: " . BASE_URL . "index.php?error=acceso_denegado");
        exit;
    }
}

function sanitizar($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatoMoneda($valor) {
    return '$' . number_format((float)$valor, 0, ',', '.');
}

// Definición de URL base relativa
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Limpiar ruta base
if (basename($script_dir) === 'pages') {
    $base_dir = dirname($script_dir);
} else {
    $base_dir = $script_dir;
}
$base_dir = rtrim($base_dir, '/') . '/';
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_dir);
}
