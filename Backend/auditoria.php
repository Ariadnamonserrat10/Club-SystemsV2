<?php
// Backend/auditoria.php
// Endpoint REST para gestionar la tabla `auditoria`.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$root = __DIR__;
$dbFile = $root . DIRECTORY_SEPARATOR . 'db.php';
if (!file_exists($dbFile)) {
  http_response_code(500);
  echo json_encode(['error' => 'No se encontró Backend/db.php']);
  exit;
}
require_once $dbFile;

// Obtener conexión mysqli
if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['error' => 'No se pudo obtener conexión a la base de datos']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  switch ($method) {
    case 'GET':
      // Obtener registros de auditoría con información del usuario
      $sql = 'SELECT 
                a.id,
                a.id_usuario,
                a.accion,
                a.descripcion,
                a.fecha,
                CONCAT(u.nombre, " ", u.apellidoP, " ", COALESCE(u.apellidoM, "")) AS usuario_nombre,
                u.tipo AS usuario_tipo
              FROM auditoria a
              LEFT JOIN usuarios u ON a.id_usuario = u.id
              ORDER BY a.fecha DESC
              LIMIT 100';
      
      $result = $conexion->query($sql);
      if (!$result) {
        throw new Exception('Error en la consulta: ' . $conexion->error);
      }
      
      $rows = [];
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
      
      echo json_encode(['data' => $rows]);
      break;

    case 'POST':
      // Registrar nueva acción en auditoría
      $payload = json_decode(file_get_contents('php://input'), true);
      if (!is_array($payload)) $payload = [];

      $id_usuario = isset($payload['id_usuario']) ? (int)$payload['id_usuario'] : null;
      $accion = isset($payload['accion']) ? trim($payload['accion']) : '';
      $descripcion = isset($payload['descripcion']) ? trim($payload['descripcion']) : '';

      $errors = [];
      if ($id_usuario === null || $id_usuario <= 0) {
        $errors[] = 'id_usuario es requerido';
      }
      if ($accion === '') {
        $errors[] = 'accion es requerida';
      }

      if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['error' => 'Validación', 'details' => $errors]);
        break;
      }

      $stmt = $conexion->prepare('INSERT INTO auditoria (id_usuario, accion, descripcion) VALUES (?, ?, ?)');
      if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conexion->error);
      }
      
      $stmt->bind_param('iss', $id_usuario, $accion, $descripcion);
      
      if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
      }

      $id = $conexion->insert_id;
      $stmt->close();
      
      // Retornar el registro creado con info del usuario
      $stmt = $conexion->prepare('SELECT 
                              a.id,
                              a.id_usuario,
                              a.accion,
                              a.descripcion,
                              a.fecha,
                              CONCAT(u.nombre, " ", u.apellidoP, " ", COALESCE(u.apellidoM, "")) AS usuario_nombre,
                              u.tipo AS usuario_tipo
                            FROM auditoria a
                            LEFT JOIN usuarios u ON a.id_usuario = u.id
                            WHERE a.id = ?');
      
      if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conexion->error);
      }
      
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $stmt->close();

      http_response_code(201);
      echo json_encode(['data' => $row]);
      break;

    default:
      http_response_code(405);
      echo json_encode(['error' => 'Método no permitido']);
      break;
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Excepción', 'message' => $e->getMessage()]);
}
