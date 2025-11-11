<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

include __DIR__ . "/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$usuario = trim($data['usuario'] ?? '');
$password = (string)($data['password'] ?? '');

if ($usuario === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["error" => "Datos de entrada inválidos"]);
    exit;
}

$stmt = $conexion->prepare(
  "SELECT id AS id_usuario,
          nombre,
          apellidoP AS apellido_paterno,
          apellidoM AS apellido_materno,
          tipo,
          usuario,
          password
   FROM usuarios
   WHERE usuario = ?"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error preparando consulta: " . $conexion->error]);
    exit;
}
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        unset($user['password']);
        echo json_encode([
            "success" => true,
            "usuario" => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Usuario o contraseña incorrectos"]);
    }
} else {
    http_response_code(401);
    echo json_encode(["error" => "Usuario o contraseña incorrectos"]);
}

$stmt->close();
$conexion->close();
?>
