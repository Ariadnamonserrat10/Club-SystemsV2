<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
  http_response_code(400);
  echo json_encode(["status" => "error", "message" => "No se recibieron datos"]);
  exit;
}

// Normalizar campos posibles desde frontend
$nombre = trim($input['nombre'] ?? $input['Nombre'] ?? '');
$apellidoP = trim($input['apellidoP'] ?? $input['apellido_paterno'] ?? $input['apellidoPaterno'] ?? '');
$apellidoM = trim($input['apellidoM'] ?? $input['apellido_materno'] ?? $input['apellidoMaterno'] ?? '');
$numeroControl = trim($input['numeroControl'] ?? $input['numero_control'] ?? $input['numero_controlo'] ?? '');
$telefono = trim($input['telefono'] ?? '');
$carrera_id = isset($input['carrera']) ? (int)$input['carrera'] : (isset($input['carrera_id']) ? (int)$input['carrera_id'] : null);
$semestre_id = isset($input['semestre']) ? (int)$input['semestre'] : (isset($input['semestre_id']) ? (int)$input['semestre_id'] : null);
$usuario = trim($input['usuario'] ?? $input['Usuario'] ?? '');
$password_raw = (string)($input['password'] ?? $input['contrasena'] ?? '');
$tipo = strtoupper(trim($input['tipo_usuario'] ?? $input['tipo'] ?? 'OFICINA'));
$club_asignado = isset($input['club_asignado']) && $input['club_asignado'] !== '' ? (int)$input['club_asignado'] : null;
$foto = trim($input['foto'] ?? null);

// Validaciones básicas
if ($nombre === '' || $apellidoP === '' || $usuario === '' || $password_raw === '') {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Faltan campos requeridos (nombre, apellidoP, usuario o contraseña)"]);
  exit;
}

// Verificar duplicados: por usuario o por número de control si se proporcionó
if ($usuario !== '') {
  $q = "SELECT id FROM usuarios WHERE usuario = ?";
  $stmtc = $conexion->prepare($q);
  if ($stmtc) {
    $stmtc->bind_param("s", $usuario);
    $stmtc->execute();
    $stmtc->store_result();
    if ($stmtc->num_rows > 0) {
      echo json_encode(["status" => "error", "message" => "Nombre de usuario ya registrado"]);
      exit;
    }
  }
}
if ($numeroControl !== '') {
  $q2 = "SELECT id FROM usuarios WHERE numeroControl = ?";
  $stmtc2 = $conexion->prepare($q2);
  if ($stmtc2) {
    $stmtc2->bind_param("s", $numeroControl);
    $stmtc2->execute();
    $stmtc2->store_result();
    if ($stmtc2->num_rows > 0) {
      echo json_encode(["status" => "error", "message" => "Número de control ya registrado"]);
      exit;
    }
  }
}

// Hashear contraseña
$password_hash = password_hash($password_raw, PASSWORD_BCRYPT);

// Preparar INSERT (usar columnas existentes en tu tabla)
$query = "INSERT INTO usuarios (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, usuario, password, tipo, club_asignado, foto)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($query);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Error al preparar consulta: " . $conexion->error]);
  exit;
}

// Normalizar valores que pueden ser null
$telefono_val = $telefono !== '' ? $telefono : null;
$carrera_val = $carrera_id !== null ? $carrera_id : null;
$semestre_val = $semestre_id !== null ? $semestre_id : null;
$club_val = $club_asignado !== null ? $club_asignado : null;
$foto_val = $foto !== '' ? $foto : null;

// bind_param no acepta null directamente para tipos "s" — usar variables y convertir a cadenas vacías si es null
$stmt->bind_param(
  "ssssiiissis s",
  $nombre,
  $apellidoP,
  $apellidoM,
  $numeroControl,
  $telefono_val,
  $carrera_val,
  $semestre_val,
  $usuario,
  $password_hash,
  $tipo,
  $club_val,
  $foto_val
);

// Nota: si tu versión de PHP/MariaDB no admite bind con tipos mixtos y null, usa esta alternativa:
try {
  // Ejecutar y comprobar
  if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    echo json_encode(["status" => "success", "message" => "Usuario registrado exitosamente", "id" => (int)$newId]);
  } else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al registrar usuario: " . $stmt->error]);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Excepción: " . $e->getMessage()]);
}
?>
