<?php
header('Content-Type: text/plain');

$host = "127.0.0.1";
$user = "Encargado";
$pass = "1234";
$dbname = "sistema_clubs";
$port = 3307;

echo "Intentando conectar a MySQL...\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "Port: $port\n";
echo "DB: $dbname\n";

$conexion = new mysqli($host, $user, $pass, $dbname, $port);

if ($conexion->connect_error) {
    echo "\n❌ ERROR DE CONEXIÓN:\n";
    echo "Código: " . $conexion->connect_errno . "\n";
    echo "Mensaje: " . $conexion->connect_error . "\n";
} else {
    echo "\n✅ CONEXIÓN EXITOSA!\n";
    echo "Versión del servidor: " . $conexion->server_info . "\n";
    $conexion->close();
}
?>
