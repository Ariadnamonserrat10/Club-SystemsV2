<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db.php';

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Conexión a BD no disponible']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $sql = "SELECT id, nombre, abreviatura FROM carreras ORDER BY id ASC";
            $result = $conexion->query($sql);
            $rows = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['id'] = (int)$row['id'];
                    $rows[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['nombre']) || !isset($data['abreviatura'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Nombre y abreviatura son obligatorios']);
                exit;
            }
            $stmt = $conexion->prepare("INSERT INTO carreras (nombre, abreviatura) VALUES (?, ?)");
            $stmt->bind_param("ss", $data['nombre'], $data['abreviatura']);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'id' => $conexion->insert_id]);
            } else {
                throw new Exception($conexion->error);
            }
            break;

        case 'PUT':
            $id = $_GET['id'] ?? null;
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$id || !isset($data['nombre']) || !isset($data['abreviatura'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID, nombre y abreviatura son obligatorios']);
                exit;
            }
            $stmt = $conexion->prepare("UPDATE carreras SET nombre = ?, abreviatura = ? WHERE id = ?");
            $stmt->bind_param("ssi", $data['nombre'], $data['abreviatura'], $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Carrera actualizada']);
            } else {
                throw new Exception($conexion->error);
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID es obligatorio']);
                exit;
            }
            $stmt = $conexion->prepare("DELETE FROM carreras WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Carrera eliminada']);
            } else {
                throw new Exception($conexion->error);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
