<?php
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['numero_control']) || empty($data['contrasena'])) {
    http_response_code(400);
    echo json_encode(["error" => "Datos de entrada inválidos"]);
    exit;
}

$numero_control = $data['numero_control'];
$contrasena = $data['contrasena'];

$stmt = $conexion->prepare("SELECT id AS id_usuario, nombre, apellidoP AS apellido_paterno, apellidoM AS apellido_materno, tipo_usuario, contrasena FROM usuarios WHERE num_control = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error preparando consulta: " . $conexion->error]);
    exit;
}
$stmt->bind_param("s", $numero_control);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($contrasena, $user['contrasena'])) {
        unset($user['contrasena']);
        echo json_encode([
            "success" => true,
            "usuario" => $user
        ]);
    } else {
        echo json_encode(["error" => "Contraseña incorrecta."]);
    }
} else {
    echo json_encode(["error" => "Usuario no encontrado."]);
}

$stmt->close();
$conexion->close();
?>
