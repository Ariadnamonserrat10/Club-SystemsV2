<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

try {
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
    exit;
  }

  $tipo = isset($payload['tipo']) ? trim($payload['tipo']) : '';
  $alumnos = isset($payload['alumnos']) && is_array($payload['alumnos']) ? array_map('intval', $payload['alumnos']) : [];
  $clubId = isset($payload['club_id']) ? (int)$payload['club_id'] : 0;
  $fechas = isset($payload['fechas']) && is_array($payload['fechas']) ? $payload['fechas'] : [];

  if ($tipo === 'baja') {
    if (empty($alumnos)) {
      echo json_encode(['status' => 'error', 'message' => 'No hay alumnos indicados']);
      exit;
    }
    $stmt = $conexion->prepare('UPDATE alumnos SET id_club = NULL WHERE id = ?');
    $count = 0;
    foreach ($alumnos as $aid) {
      $stmt->bind_param('i', $aid);
      if ($stmt->execute()) $count++;
    }
    echo json_encode(['status' => 'success', 'message' => "Alumnos dados de baja: $count"]);
    exit;
  }

  if ($tipo === 'inscribir_acreditar') {
    if ($clubId <= 0 || empty($alumnos)) {
      http_response_code(422);
      echo json_encode(['status' => 'error', 'message' => 'club_id y alumnos requeridos']);
      exit;
    }

    // 1) Actualizar id_club para los alumnos
    $stmtUpd = $conexion->prepare('UPDATE alumnos SET id_club = ? WHERE id = ?');
    $countUpd = 0;
    foreach ($alumnos as $aid) {
      $stmtUpd->bind_param('ii', $clubId, $aid);
      if ($stmtUpd->execute()) $countUpd++;
    }

    // 2) Insertar/actualizar asistencias para las fechas indicadas
    $countAs = 0;
    if (!empty($fechas)) {
      $stmtIns = $conexion->prepare('INSERT INTO asistencias (id_alumno, fecha, presente) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE presente = VALUES(presente)');
      foreach ($fechas as $f) {
        // validar formato YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) continue;
        foreach ($alumnos as $aid) {
          $stmtIns->bind_param('is', $aid, $f);
          if ($stmtIns->execute()) $countAs++;
        }
      }
    }

    echo json_encode(['status' => 'success', 'message' => "Alumnos actualizados: $countUpd, asistencias guardadas: $countAs"]);
    exit;
  }

  http_response_code(422);
  echo json_encode(['status' => 'error', 'message' => 'tipo no soportado']);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
