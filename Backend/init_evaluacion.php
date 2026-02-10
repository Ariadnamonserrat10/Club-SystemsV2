<?php
header('Content-Type: application/json');

include_once 'db.php';
$database = new Database();
$db = $database->connect();

$sql = "
CREATE TABLE IF NOT EXISTS evaluacion (
    id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT,
    nombre_estudiante VARCHAR(150),
    nombre_club VARCHAR(150),
    periodo_realizacion VARCHAR(100),

    -- Criterios de evaluación (1 a 5)
    criterio_1 TINYINT NOT NULL CHECK (criterio_1 BETWEEN 1 AND 5),
    criterio_2 TINYINT NOT NULL CHECK (criterio_2 BETWEEN 1 AND 5),
    criterio_3 TINYINT NOT NULL CHECK (criterio_3 BETWEEN 1 AND 5),
    criterio_4 TINYINT NOT NULL CHECK (criterio_4 BETWEEN 1 AND 5),
    criterio_5 TINYINT NOT NULL CHECK (criterio_5 BETWEEN 1 AND 5),
    criterio_6 TINYINT NOT NULL CHECK (criterio_6 BETWEEN 1 AND 5),
    criterio_7 TINYINT NOT NULL CHECK (criterio_7 BETWEEN 1 AND 5),

    -- Observaciones
    observaciones TEXT,

    -- Valores finales (1 a 4)
    valor_numerico DECIMAL(3,2) NOT NULL CHECK (valor_numerico BETWEEN 1 AND 4),
    nivel_desempeno VARCHAR(50) NOT NULL,
    -- (Nota: nivel_desempeno puede ser texto 'Excelente', 'Bueno' pero la base de datos lo pide INT?
    -- El usuario pidió: nivel_desempeno INT NOT NULL CHECK (nivel_desempeno BETWEEN 1 AND 4)
    -- Ajustaremos a INT para seguir instrucciones.
    
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";

// Ajustaremos la columna nivel_desempeno para que sea compatible si ya existiese
// Pero como es create IF NOT EXISTS, solo funcionará si no existe.
// Si el usuario quiere forzar la estructura exacta:
$sql_strict = "
CREATE TABLE IF NOT EXISTS evaluacion (
    id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estudiante VARCHAR(150) NOT NULL,
    nombre_club VARCHAR(150) NOT NULL,
    periodo_realizacion DATE NOT NULL,
    criterio_1 TINYINT NOT NULL CHECK (criterio_1 BETWEEN 1 AND 5),
    criterio_2 TINYINT NOT NULL CHECK (criterio_2 BETWEEN 1 AND 5),
    criterio_3 TINYINT NOT NULL CHECK (criterio_3 BETWEEN 1 AND 5),
    criterio_4 TINYINT NOT NULL CHECK (criterio_4 BETWEEN 1 AND 5),
    criterio_5 TINYINT NOT NULL CHECK (criterio_5 BETWEEN 1 AND 5),
    criterio_6 TINYINT NOT NULL CHECK (criterio_6 BETWEEN 1 AND 5),
    criterio_7 TINYINT NOT NULL CHECK (criterio_7 BETWEEN 1 AND 5),
    observaciones TEXT,
    valor_numerico INT NOT NULL CHECK (valor_numerico BETWEEN 1 AND 4),
    nivel_desempeno INT NOT NULL CHECK (nivel_desempeno BETWEEN 1 AND 4),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";

try {
    $stmt = $db->prepare($sql_strict);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Tabla evaluacion creada o ya existente"]);
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error creando tabla: " . $e->getMessage()]);
}
?>
