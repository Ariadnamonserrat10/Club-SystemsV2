<?php
// Evitar que errores PHP rompan el JSON
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    if (!file_exists('db.php')) {
        throw new Exception("El archivo db.php no existe");
    }

    include_once 'db.php';
    
    // db.php crea $conexion (o $conn)
    if (!isset($conexion) && !isset($conn)) {
        throw new Exception("No se pudo obtener la conexión desde db.php");
    }
    
    $mysqli = isset($conexion) ? $conexion : $conn;

    if ($mysqli->connect_error) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }

    // 1. Create table if not exists
    $sql_create = "
    CREATE TABLE IF NOT EXISTS evaluacion (
        id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
        nombre_estudiante VARCHAR(150) NOT NULL,
        nombre_club VARCHAR(150) NOT NULL,
        periodo_realizacion DATE NOT NULL,
        criterio_1 TINYINT NOT NULL,
        criterio_2 TINYINT NOT NULL,
        criterio_3 TINYINT NOT NULL,
        criterio_4 TINYINT NOT NULL,
        criterio_5 TINYINT NOT NULL,
        criterio_6 TINYINT NOT NULL,
        criterio_7 TINYINT NOT NULL,
        observaciones TEXT,
        valor_numerico INT NOT NULL,
        nivel_desempeno INT NOT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (!$mysqli->query($sql_create)) {
        throw new Exception("Error creando tabla: " . $mysqli->error);
    }

    // 2. Adjust constraint to allow 1-5 if needed (MySQL ignores modify in some versions, but try to alter)
    // Se intenta relajar la restricción de 1-4 a 1-5 si es posible.
    // O simplemente ignorar el check.
    // En versiones modernas de MariaDB/MySQL ALTER TABLE ... DROP CHECK ...
    // Como no sabemos el nombre, intentamos MODIFYColumn para quitar CHECK inline en createTable future

    // 3. Handle POST request
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw);

        if (!$data) {
            throw new Exception("No se recibieron datos JSON válidos");
        }

        // Validate required fields
        if (
            !isset($data->nombre_estudiante) || 
            !isset($data->nombre_club) || 
            !isset($data->periodo_realizacion) ||
            !isset($data->criterio_1) ||
            !isset($data->valor_numerico) ||
            !isset($data->nivel_desempeno)
        ) {
            throw new Exception("Faltan campos obligatorios");
        }

        $query = "INSERT INTO evaluacion 
            (nombre_estudiante, nombre_club, periodo_realizacion, 
             criterio_1, criterio_2, criterio_3, criterio_4, criterio_5, criterio_6, criterio_7, 
             observaciones, valor_numerico, nivel_desempeno)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception("Error en prepare: " . $mysqli->error);
        }

        // Tipos: sss iiiiiii s ii
        // s = string, i = int
        // Total 13 params
        $stmt->bind_param(
            "sssiiiiiiisii",
            $data->nombre_estudiante,
            $data->nombre_club,
            $data->periodo_realizacion,
            $data->criterio_1,
            $data->criterio_2,
            $data->criterio_3,
            $data->criterio_4,
            $data->criterio_5,
            $data->criterio_6,
            $data->criterio_7,
            $data->observaciones,
            $data->valor_numerico,
            $data->nivel_desempeno
        );

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Evaluacion guardada', 'id' => $stmt->insert_id]);
        } else {
            throw new Exception("Error al insertar: " . $stmt->error);
        }
        $stmt->close();

    } else if ($method === 'GET') {
        // Buscar evaluación por ID o por alumno/club
        if (isset($_GET['type']) && $_GET['type'] === 'list' && isset($_GET['nombre_club'])) {
            $club = $_GET['nombre_club'];
            // Retorna lista de nombres de estudiantes evaluados en este club
            // TRIM(nombre_club) LIKE TRIM(?)
            $sql = "SELECT nombre_estudiante FROM evaluacion WHERE TRIM(nombre_club) = TRIM(?)";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) throw new Exception("Error prepare: " . $mysqli->error);
            $stmt->bind_param("s", $club);
            $stmt->execute();
            $result = $stmt->get_result();
            $nombres = [];
            while ($row = $result->fetch_assoc()) {
                $nombres[] = $row['nombre_estudiante'];
            }
            echo json_encode(['status' => 'success', 'data' => $nombres]);
        } else if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM evaluacion WHERE id_evaluacion = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else if (isset($_GET['nombre_estudiante']) && isset($_GET['nombre_club'])) {
            $nombre = $_GET['nombre_estudiante'];
            $club = $_GET['nombre_club'];
            // Obtener la última evaluación
            // Normalizamos espacios y case insensitive
            // TRIM(nombre_estudiante) LIKE TRIM(?)
            $sql = "SELECT * FROM evaluacion WHERE TRIM(nombre_estudiante) = TRIM(?) AND nombre_club = ? ORDER BY id_evaluacion DESC LIMIT 1";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ss", $nombre, $club);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            // Listar todo, opcional
            echo json_encode(['status' => 'success', 'data' => []]);
        }
    } else {
        throw new Exception("Método no permitido");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
