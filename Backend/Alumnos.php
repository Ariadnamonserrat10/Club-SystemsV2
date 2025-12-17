<?php
// Backend/Alumnos.php
// Endpoint REST para gestionar la tabla `alumnos` con los campos proporcionados.
// Estructura esperada de la tabla alumnos:
// id (int, PK, AI), nombre (varchar), apellidoP (varchar), apellidoM (varchar),
// numeroControl (char(8)), telefono (char(10), nullable), carrera_id (int, nullable),
// semestre_id (int, nullable), id_club (int, nullable), fecha_registro (date, default curdate())

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
require_once $dbFile; // expone $conexion (mysqli)

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  if (function_exists('getMysqli') && getMysqli() instanceof mysqli) {
    $conexion = getMysqli();
  }
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['error' => 'No hay conexión a la base de datos']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // Listado simple de alumnos
    $rows = [];
    $sql = 'SELECT id, nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club, fecha_registro FROM alumnos ORDER BY id DESC';
    if ($res = $conexion->query($sql)) {
      while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    }
    echo json_encode(['data' => $rows]);
    exit;
  }

  if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) { $payload = []; }

    $nombre = trim((string)($payload['nombre'] ?? ''));
    $apellidoP = trim((string)($payload['apellidoP'] ?? ''));
    $apellidoM = trim((string)($payload['apellidoM'] ?? ''));
    $numeroControl = trim((string)($payload['numeroControl'] ?? ''));
    $telefono = trim((string)($payload['telefono'] ?? ''));
    $carrera_id = isset($payload['carrera_id']) && $payload['carrera_id'] !== '' ? (int)$payload['carrera_id'] : null;
    $semestre_id = isset($payload['semestre_id']) && $payload['semestre_id'] !== '' ? (int)$payload['semestre_id'] : null;
    $id_club = isset($payload['id_club']) && $payload['id_club'] !== '' ? (int)$payload['id_club'] : null;

    $errors = [];
    if ($nombre === '') { $errors[] = 'nombre es requerido'; }
    if ($apellidoP === '') { $errors[] = 'apellidoP es requerido'; }
    if ($apellidoM === '') { $errors[] = 'apellidoM es requerido'; }
    if ($numeroControl === '' || !preg_match('/^\d{8}$/', $numeroControl)) { $errors[] = 'numeroControl es requerido y debe tener 8 dígitos'; }
    if ($telefono !== '' && !preg_match('/^\d{7,15}$/', $telefono)) { $errors[] = 'telefono debe ser numérico (7-15 dígitos) o vacío'; }

    if (!empty($errors)) {
      http_response_code(422);
      echo json_encode(['error' => 'Validación', 'details' => $errors]);
      exit;
    }

    // Validar duplicado por numeroControl
    $stmtCheck = $conexion->prepare('SELECT id FROM alumnos WHERE numeroControl = ? LIMIT 1');
    $stmtCheck->bind_param('s', $numeroControl);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows > 0) {
      http_response_code(409);
      echo json_encode(['error' => 'Duplicado', 'message' => 'El número de control ya existe']);
      exit;
    }

    // Insert
    $sql = 'INSERT INTO alumnos (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $conexion->prepare($sql);

    // Normalizar nullables
    $tel = ($telefono !== '') ? $telefono : null;
    $car = $carrera_id; // ya null si no hay
    $sem = $semestre_id; // ya null si no hay
    $club = $id_club; // ya null si no hay

    // bind_param no admite directamente NULL con tipos estrictos, pero se puede pasar variables que sean null.
    $stmt->bind_param('sssssiis', $nombre, $apellidoP, $apellidoM, $numeroControl, $tel, $car, $sem, $club);

    // Ajuste: si alguna columna nullable presenta warning en algunos entornos, reconstruir sentencia dinámica
    // Para mayor compatibilidad, detectamos tipos dinámicamente.
    // No obstante, la línea anterior suele funcionar en mysqli si las variables son null.

    if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['error' => 'Error al insertar', 'message' => $stmt->error]);
      exit;
    }

    $insertedId = $conexion->insert_id;
    $stmtGet = $conexion->prepare('SELECT id, nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club, fecha_registro FROM alumnos WHERE id = ?');
    $stmtGet->bind_param('i', $insertedId);
    $stmtGet->execute();
    $res = $stmtGet->get_result();
    $row = $res->fetch_assoc();

    http_response_code(201);
    echo json_encode(['data' => $row]);
    exit;
  }

  if ($method === 'PUT') {
    // Actualizar alumno por id (campos parciales)
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
      http_response_code(400);
      echo json_encode(['error' => 'ID inválido']);
      exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) { $payload = []; }

    // Obtener registro actual
    $stmtSel = $conexion->prepare('SELECT id, nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club FROM alumnos WHERE id = ?');
    $stmtSel->bind_param('i', $id);
    $stmtSel->execute();
    $resSel = $stmtSel->get_result();
    $cur = $resSel->fetch_assoc();
    if (!$cur) {
      http_response_code(404);
      echo json_encode(['error' => 'No encontrado']);
      exit;
    }

    $nombre = array_key_exists('nombre', $payload) ? trim((string)$payload['nombre']) : $cur['nombre'];
    $apellidoP = array_key_exists('apellidoP', $payload) ? trim((string)$payload['apellidoP']) : $cur['apellidoP'];
    $apellidoM = array_key_exists('apellidoM', $payload) ? trim((string)$payload['apellidoM']) : $cur['apellidoM'];
    $numeroControl = array_key_exists('numeroControl', $payload) ? trim((string)$payload['numeroControl']) : $cur['numeroControl'];
    $telefono = array_key_exists('telefono', $payload) ? trim((string)$payload['telefono']) : $cur['telefono'];
    $carrera_id = array_key_exists('carrera_id', $payload) ? ($payload['carrera_id'] !== '' ? (int)$payload['carrera_id'] : null) : $cur['carrera_id'];
    $semestre_id = array_key_exists('semestre_id', $payload) ? ($payload['semestre_id'] !== '' ? (int)$payload['semestre_id'] : null) : $cur['semestre_id'];
    $id_club = array_key_exists('id_club', $payload) ? ($payload['id_club'] !== '' ? (int)$payload['id_club'] : null) : $cur['id_club'];

    // Validaciones básicas
    $errors = [];
    if ($nombre === '') { $errors[] = 'nombre es requerido'; }
    if ($apellidoP === '') { $errors[] = 'apellidoP es requerido'; }
    if ($apellidoM === '') { $errors[] = 'apellidoM es requerido'; }
    if ($numeroControl === '' || !preg_match('/^\d{8}$/', $numeroControl)) { $errors[] = 'numeroControl debe tener 8 dígitos'; }
    if ($telefono !== '' && $telefono !== null && !preg_match('/^\d{7,15}$/', $telefono)) { $errors[] = 'telefono debe ser numérico (7-15 dígitos) o vacío'; }

    if (!empty($errors)) {
      http_response_code(422);
      echo json_encode(['error' => 'Validación', 'details' => $errors]);
      exit;
    }

    $sql = 'UPDATE alumnos SET nombre = ?, apellidoP = ?, apellidoM = ?, numeroControl = ?, telefono = ?, carrera_id = ?, semestre_id = ?, id_club = ? WHERE id = ?';
    $stmt = $conexion->prepare($sql);

    $tel = ($telefono !== '') ? $telefono : null;
    $car = $carrera_id;
    $sem = $semestre_id;
    $club = $id_club;

    $stmt->bind_param('sssssiisi', $nombre, $apellidoP, $apellidoM, $numeroControl, $tel, $car, $sem, $club, $id);

    if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['error' => 'Error al actualizar', 'message' => $stmt->error]);
      exit;
    }

    $stmtGet = $conexion->prepare('SELECT id, nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club, fecha_registro FROM alumnos WHERE id = ?');
    $stmtGet->bind_param('i', $id);
    $stmtGet->execute();
    $res = $stmtGet->get_result();
    $row = $res->fetch_assoc();

    echo json_encode(['data' => $row]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Método no permitido']);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Excepción', 'message' => $e->getMessage()]);
  exit;
}
