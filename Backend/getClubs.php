<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";

$query = "SELECT id, nombre FROM clubs ORDER BY nombre";
$result = $conexion->query($query);
if (!$result) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $conexion->error]);
  exit;
}

$clubs = [];
while ($row = $result->fetch_assoc()) {
  $clubs[] = ["id" => (int)$row["id"], "nombre" => $row["nombre"]];
}

echo json_encode(["status" => "success", "data" => $clubs]);