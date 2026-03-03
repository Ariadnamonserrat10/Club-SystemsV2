<?php
// Habilitar errores para depuración si no se definió lo contrario
if (ini_get('display_errors') === '0') {
    // Si el script ya decidió ocultar errores, respetamos eso
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

$host = "127.0.0.1";
$user = "Encargado";
$pass = "1234";
$dbname = "sistema_clubs";
$port = 3307;

try {
    // Verificar si la extensión mysqli está instalada
    if (!class_exists('mysqli')) {
        throw new Exception("La extensión PHP 'mysqli' no está instalada o habilitada en php.ini");
    }

    $conexion = new mysqli($host, $user, $pass, $dbname, $port);

    if ($conexion->connect_error) {
        throw new Exception("Error de conexión MySQL (" . $conexion->connect_errno . "): " . $conexion->connect_error);
    }

    $conexion->set_charset("utf8mb4");
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    die(json_encode(["error" => $e->getMessage()]));
}

// Alias opcional para compatibilidad
$conn = $conexion;

// Función no intrusiva para exponer mysqli de forma estándar
if (!function_exists('getMysqli')) {
    function getMysqli() {
        global $conexion;
        return $conexion instanceof mysqli ? $conexion : null;
    }
}
