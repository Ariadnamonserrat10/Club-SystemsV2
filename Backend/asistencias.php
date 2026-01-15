<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/db.php';

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  if (function_exists('getMysqli') && getMysqli() instanceof mysqli) {
    $conexion = getMysqli();
  }
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Conexión a BD no disponible']);
  exit;
}

// Crea tabla si no existe para evitar fallos en ambientes nuevos
function ensureTables(mysqli $db) {
  $sql1 = "CREATE TABLE IF NOT EXISTS asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT NOT NULL,
    fecha DATE NOT NULL,
    presente TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_alumno_fecha (alumno_id, fecha),
    INDEX idx_fecha (fecha),
    INDEX idx_alumno (alumno_id),
    CONSTRAINT fk_asist_alumno FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
  @$db->query($sql1);

  $sql2 = "CREATE TABLE IF NOT EXISTS fechas_club (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    fecha DATE NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_club_fecha (club_id, fecha),
    INDEX idx_fc_club (club_id),
    INDEX idx_fc_fecha (fecha)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
  @$db->query($sql2);
}

ensureTables($conexion);

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // GET /asistencias.php?club_id=ID
    $clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
    if ($clubId <= 0) {
      http_response_code(400);
      echo json_encode(['status' => 'error', 'message' => 'club_id requerido']);
      exit;
    }

    // Obtener alumnos del club
    $stmtA = $conexion->prepare('SELECT id, nombre, apellidoP, apellidoM FROM alumnos WHERE id_club = ? ORDER BY apellidoP ASC, apellidoM ASC, nombre ASC');
    $stmtA->bind_param('i', $clubId);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    $alumnos = [];
    $alumnoIds = [];
    while ($row = $resA->fetch_assoc()) { $alumnos[] = $row; $alumnoIds[] = (int)$row['id']; }

    // Si no hay alumnos, devolver fechas desde fechas_club y asistencias vacías
    if (empty($alumnoIds)) {
      $stmtF = $conexion->prepare('SELECT fecha FROM fechas_club WHERE club_id = ? ORDER BY fecha ASC');
      $stmtF->bind_param('i', $clubId);
      $stmtF->execute();
      $resF = $stmtF->get_result();
      $fechas = [];
      while ($r = $resF->fetch_assoc()) { $fechas[] = $r['fecha']; }
      echo json_encode(['status' => 'success', 'data' => ['fechas' => $fechas, 'asistencias' => [], 'alumnos' => $alumnos]]);
      exit;
    }

    // Obtener asistencias para esos alumnos
    $placeholders = implode(',', array_fill(0, count($alumnoIds), '?'));
    $types = str_repeat('i', count($alumnoIds));
    $stmt = $conexion->prepare("SELECT alumno_id, fecha, presente FROM asistencias WHERE alumno_id IN ($placeholders) ORDER BY fecha ASC");
    $stmt->bind_param($types, ...$alumnoIds);
    $stmt->execute();
    $res = $stmt->get_result();

    $fechasSet = [];
    $asistencias = []; // { alumno_id: { 'YYYY-MM-DD': bool } }
    while ($row = $res->fetch_assoc()) {
      $aid = (int)$row['alumno_id'];
      $fecha = $row['fecha']; // YYYY-MM-DD
      $pres = (int)$row['presente'] === 1;
      $fechasSet[$fecha] = true;
      if (!isset($asistencias[$aid])) $asistencias[$aid] = [];
      $asistencias[$aid][$fecha] = $pres;
    }

    // Unir fechas registradas por club aunque no existan asistencias
    $stmtF = $conexion->prepare('SELECT fecha FROM fechas_club WHERE club_id = ? ORDER BY fecha ASC');
    $stmtF->bind_param('i', $clubId);
    $stmtF->execute();
    $resF = $stmtF->get_result();
    while ($r = $resF->fetch_assoc()) { $fechasSet[$r['fecha']] = true; }

    $fechas = array_keys($fechasSet);
    sort($fechas);

    echo json_encode(['status' => 'success', 'data' => [
      'fechas' => $fechas,
      'asistencias' => $asistencias,
      'alumnos' => $alumnos
    ]]);
    exit;
  }

  if ($method === 'POST') {
    // POST body: { club_id, fecha: 'YYYY-MM-DD', registros: [{ alumno_id, presente }] }
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) $payload = [];

    $clubId = isset($payload['club_id']) ? (int)$payload['club_id'] : 0;
    $fecha = isset($payload['fecha']) ? trim((string)$payload['fecha']) : '';
    $registros = isset($payload['registros']) && is_array($payload['registros']) ? $payload['registros'] : [];

    $errors = [];
    if ($clubId <= 0) $errors[] = 'club_id requerido';
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $errors[] = 'fecha requerida en formato YYYY-MM-DD';

    if (!empty($errors)) {
      http_response_code(422);
      echo json_encode(['status' => 'error', 'message' => 'Validación', 'details' => $errors]);
      exit;
    }

    // Registrar fecha para el club (aunque no haya alumnos)
    $stmtFC = $conexion->prepare('INSERT IGNORE INTO fechas_club (club_id, fecha) VALUES (?, ?)');
    $stmtFC->bind_param('is', $clubId, $fecha);
    @$stmtFC->execute();

    // Validar que los alumno_id pertenezcan al club
    $ids = array_map(fn($r) => (int)($r['alumno_id'] ?? 0), $registros);
    $ids = array_values(array_filter(array_unique($ids), fn($v) => $v > 0));

    if (!empty($ids)) {
      $place = implode(',', array_fill(0, count($ids), '?'));
      $types = str_repeat('i', count($ids));
      $stmtV = $conexion->prepare("SELECT id FROM alumnos WHERE id IN ($place) AND id_club = ?");
      $types2 = $types . 'i';
      $stmtV->bind_param($types2, ...$ids, $clubId);
      $stmtV->execute();
      $resV = $stmtV->get_result();
      $validIds = [];
      while ($row = $resV->fetch_assoc()) { $validIds[] = (int)$row['id']; }
      $validMap = array_flip($validIds);
    } else {
      $validMap = [];
    }

    // Insertar o actualizar registros de asistencia para esa fecha
    $stmtIns = $conexion->prepare('INSERT INTO asistencias (alumno_id, fecha, presente) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE presente = VALUES(presente)');

    $count = 0;
    foreach ($registros as $r) {
      $aid = (int)($r['alumno_id'] ?? 0);
      if ($aid <= 0) continue;
      if (!isset($validMap[$aid])) continue; // ignorar alumnos fuera del club
      $pres = !empty($r['presente']) ? 1 : 0;
      $stmtIns->bind_param('isi', $aid, $fecha, $pres);
      $ok = $stmtIns->execute();
      if ($ok) $count++;
    }

    echo json_encode(['status' => 'success', 'message' => "Registros guardados: $count"]);
    exit;
  }

  if ($method === 'PUT') {
    // PUT /asistencias.php  body: { alumno_id, fecha: 'YYYY-MM-DD', presente: bool }
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) $payload = [];

    $alumnoId = isset($payload['alumno_id']) ? (int)$payload['alumno_id'] : 0;
    $fecha = isset($payload['fecha']) ? trim((string)$payload['fecha']) : '';
    $presente = !empty($payload['presente']) ? 1 : 0;

    if ($alumnoId <= 0 || $fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
      http_response_code(422);
      echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
      exit;
    }

    $stmt = $conexion->prepare('INSERT INTO asistencias (alumno_id, fecha, presente) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE presente = VALUES(presente)');
    $stmt->bind_param('isi', $alumnoId, $fecha, $presente);
    if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar', 'error' => $stmt->error]);
      exit;
    }

    echo json_encode(['status' => 'success']);
    exit;
  }

  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
