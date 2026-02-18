<?php
// Backend/asignacion_cargos.php
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
        // Obtener todos los usuarios que tienen un cargo asignado
        $query = "SELECT id, nombre, apellidoP, apellidoM, cargo FROM usuarios WHERE cargo IS NOT NULL AND cargo != ''";
        $result = $conexion->query($query);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    if ($method === 'POST') {
        // Asignar un cargo a un usuario
        $payload = json_decode(file_get_contents('php://input'), true);
        $cargo = isset($payload['cargo']) ? trim($payload['cargo']) : '';
        $id_usuario = isset($payload['id_usuario']) ? (int)$payload['id_usuario'] : 0;

        if (empty($cargo) || $id_usuario <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Cargo e id_usuario son requeridos']);
            exit;
        }

        // 1. Limpiar este cargo de cualquier otro usuario que lo tenga
        $stmtClear = $conexion->prepare("UPDATE usuarios SET cargo = NULL WHERE cargo = ?");
        $stmtClear->bind_param("s", $cargo);
        $stmtClear->execute();

        // 2. Asignar el cargo al nuevo usuario
        $stmtSet = $conexion->prepare("UPDATE usuarios SET cargo = ? WHERE id = ?");
        $stmtSet->bind_param("si", $cargo, $id_usuario);
        
        if ($stmtSet->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cargo asignado correctamente']);
        } else {
            throw new Exception("Error al asignar cargo: " . $stmtSet->error);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
