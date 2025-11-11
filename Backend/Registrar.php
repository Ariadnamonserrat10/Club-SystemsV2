<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit; // preflight
}

$data = json_decode(file_get_contents("php://input"), true);

// Depuración opcional: habilita con ?debug=1 para ver payload recibido
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode(["debug_payload" => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

if (!$data) {
  http_response_code(400);
  echo json_encode(["status" => "error", "message" => "No se recibieron datos"]);
  exit;
}

$nombre = trim($data["nombre"] ?? $data["Nombre"] ?? "");
$apellidoP = trim($data["apellidoP"] ?? $data["apellido_paterno"] ?? $data["apellidoPaterno"] ?? "");
$apellidoM = trim($data["apellidoM"] ?? $data["apellido_materno"] ?? $data["apellidoMaterno"] ?? "");
$tipo = strtoupper(trim($data["tipo"] ?? $data["tipo_usuario"] ?? $data["tipoUsuario"] ?? ""));
$usuario = trim($data["usuario"] ?? $data["Usuario"] ?? "");
$password_raw = (string)($data["password"] ?? $data["contrasena"] ?? $data["contrasenaPlano"] ?? "");

// Campos MONITOR opcionales
$numeroControl = trim($data["numeroControl"] ?? $data["numero_control"] ?? $data["numControl"] ?? "");
$telefono = trim($data["telefono"] ?? $data["tel"] ?? "");
$carrera_id = $data["carrera_id"] ?? $data["carreraId"] ?? null;
$semestre_id = $data["semestre_id"] ?? $data["semestreId"] ?? null;

// Validaciones base
if ($nombre === '' || $apellidoP === '' || $apellidoM === '' || $tipo === '' || $usuario === '') {
  http_response_code(422);
  echo json_encode([
    "status" => "error",
    "message" => "Faltan datos obligatorios (nombre, apellidos, tipo, usuario)",
    "campos_recibidos" => [
      "nombre" => $nombre,
      "apellidoP" => $apellidoP,
      "apellidoM" => $apellidoM,
      "tipo" => $tipo,
      "usuario" => $usuario
    ]
  ]);
  exit;
}

if ($password_raw === '') {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Contraseña vacía"]);
  exit;
}

if (!in_array($tipo, ['OFICINA','MONITOR'], true)) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Tipo inválido. Use OFICINA o MONITOR"]);
  exit;
}

// Reglas específicas para MONITOR
if ($tipo === 'MONITOR') {
  if ($numeroControl === '') {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "numeroControl es obligatorio para MONITOR"]);
    exit;
  }
}

// Normalizar enteros opcionales (NULL si no vienen)
$carrera_id = ($carrera_id === '' || $carrera_id === null) ? null : (int)$carrera_id;
$semestre_id = ($semestre_id === '' || $semestre_id === null) ? null : (int)$semestre_id;

$hash = password_hash($password_raw, PASSWORD_BCRYPT);

// Verificar duplicados: usuario y numeroControl (si aplica)
// 1) usuario
$stmt_check_u = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
if (!$stmt_check_u) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Error preparando verificación usuario: " . $conexion->error]);
  exit;
}
$stmt_check_u->bind_param("s", $usuario);
$stmt_check_u->execute();
$stmt_check_u->store_result();
if ($stmt_check_u->num_rows > 0) {
  http_response_code(409);
  echo json_encode(["status" => "error", "message" => "Usuario ya registrado"]);
  exit;
}

// 2) numeroControl sólo para MONITOR
if ($tipo === 'MONITOR') {
  $stmt_check_nc = $conexion->prepare("SELECT id FROM usuarios WHERE numeroControl = ?");
  if (!$stmt_check_nc) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error preparando verificación numeroControl: " . $conexion->error]);
    exit;
  }
  $stmt_check_nc->bind_param("s", $numeroControl);
  $stmt_check_nc->execute();
  $stmt_check_nc->store_result();
  if ($stmt_check_nc->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "numeroControl ya registrado"]);
    exit;
  }
}

// Inserción
if ($tipo === 'MONITOR') {
  $sql = "INSERT INTO usuarios (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, usuario, password, tipo)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $conexion->prepare($sql);
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error preparando inserción: " . $conexion->error]);
    exit;
  }
  // Para permitir NULL en ints, usamos bind_param con tipos y pasamos variables que pueden ser NULL mediante set to null y usar 'i' aceptará 0; para NULL verdadero, usaremos bind con mysqli_stmt::bind_param y luego setear -> send_long_data no aplica. Simplificamos: si null, pasamos NULL con $stmt->bind_param no soporta directamente; usamos ->bind_param y luego ->execute() con ->bind_param admite null si la variable es NULL y mysqlnd lo permite.
  $stmt->bind_param("sssssiisss", $nombre, $apellidoP, $apellidoM, $numeroControl, $telefono, $carrera_id, $semestre_id, $usuario, $hash, $tipo);
} else { // OFICINA
  $sql = "INSERT INTO usuarios (nombre, apellidoP, apellidoM, usuario, password, tipo)
          VALUES (?, ?, ?, ?, ?, ?)";
  $stmt = $conexion->prepare($sql);
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error preparando inserción: " . $conexion->error]);
    exit;
  }
  $stmt->bind_param("ssssss", $nombre, $apellidoP, $apellidoM, $usuario, $hash, $tipo);
}

if ($stmt->execute()) {
  echo json_encode(["status" => "success", "message" => "Usuario registrado exitosamente", "id" => $stmt->insert_id ?? null]);
} else {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Error al registrar usuario: " . $stmt->error]);
}
?>
