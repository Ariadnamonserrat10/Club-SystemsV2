<?php
require_once __DIR__ . '/db.php';
$table = $_GET['table'] ?? 'usuarios';
$res = $conexion->query("DESCRIBE $table");
$schema = [];
while($row = $res->fetch_assoc()) {
    $schema[] = $row;
}
header('Content-Type: application/json');
echo json_encode($schema, JSON_PRETTY_PRINT);
?>
