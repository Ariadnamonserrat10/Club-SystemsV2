<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $clave = $_GET['clave'] ?? null;
        if ($clave) {
            $stmt = $conexion->prepare("SELECT valor FROM configuracion WHERE clave = ?");
            $stmt->bind_param("s", $clave);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $row['valor'] ?? null]);
        } else {
            $res = $conexion->query("SELECT clave, valor FROM configuracion");
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $data[$row['clave']] = $row['valor'];
            }
            echo json_encode(['status' => 'success', 'data' => $data]);
        }
        exit;
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!isset($payload['clave']) || !isset($payload['valor'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Faltan campos clave o valor']);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        $stmt->bind_param("ss", $payload['clave'], $payload['valor']);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Configuración guardada']);
        } else {
            throw new Exception($stmt->error);
        }
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
