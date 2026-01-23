<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sistema_clubs";

$conexion = new mysqli($host, $user, $pass, $dbname);

if ($conexion->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Error de conexión: " . $conexion->connect_error]));
}

$conexion->set_charset("utf8mb4");

// Alias opcional para compatibilidad
$conn = $conexion;

// Función no intrusiva para exponer mysqli de forma estándar
if (!function_exists('getMysqli')) {
    function getMysqli() {
        global $conexion;
        return $conexion instanceof mysqli ? $conexion : null;
    }
}
?>
