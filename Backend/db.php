<?php
// Habilitar errores para depuración si no se definió lo contrario
if (ini_get('display_errors') === '0') {
    // Si el script ya decidió ocultar errores, respetamos eso
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Usar SQLite si el archivo existe (producción), sino MySQL (desarrollo)
$dbPath = __DIR__ . '/../database/sistema_clubs.db';

if (file_exists($dbPath)) {
    // Usar SQLite
    try {
        if (!class_exists('PDO')) {
            throw new Exception("La extensión PHP 'PDO' no está instalada o habilitada");
        }

        $conexion = new PDO("sqlite:$dbPath");
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        crearTablasSiNoExisten($conexion);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
} else {
    // Usar MySQL (desarrollo)
    $host = "127.0.0.1";
    $user = "Encargado";
    $pass = "1234";
    $dbname = "sistema_clubs";
    $port = 3307;

    try {
        if (!class_exists('mysqli')) {
            throw new Exception("La extensión PHP 'mysqli' no está instalada o habilitada en php.ini");
        }

        $conexion = new mysqli($host, $user, $pass, $dbname, $port);

        if ($conexion->connect_error) {
            throw new Exception("Error de conexión MySQL (" . $conexion->connect_errno . "): " . $conexion->connect_error);
        }

        $conexion->set_charset("utf8mb4");
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Función para crear tablas si no existen
function crearTablasSiNoExisten($pdo) {
    // Tabla usuarios
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        apellidoP TEXT,
        apellidoM TEXT,
        usuario TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        tipo TEXT NOT NULL,
        foto TEXT,
        club_asignado INTEGER,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabla clubs
    $pdo->exec("CREATE TABLE IF NOT EXISTS clubs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        tipo TEXT DEFAULT 'CULTURAL',
        descripcion TEXT,
        cupo_limite INTEGER DEFAULT 30,
        cupo_ocupado INTEGER DEFAULT 0,
        id_responsable INTEGER,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_responsable) REFERENCES usuarios(id)
    )");

    // Tabla carreras
    $pdo->exec("CREATE TABLE IF NOT EXISTS carreras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        descripcion TEXT,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabla alumnos
    $pdo->exec("CREATE TABLE IF NOT EXISTS alumnos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        apellidoP TEXT,
        apellidoM TEXT,
        numero_control TEXT UNIQUE NOT NULL,
        carrera_id INTEGER,
        semestre INTEGER,
        telefono TEXT,
        email TEXT,
        club_asignado INTEGER,
        fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (carrera_id) REFERENCES carreras(id),
        FOREIGN KEY (club_asignado) REFERENCES clubs(id)
    )");

    // Tabla alumnos_pendientes
    $pdo->exec("CREATE TABLE IF NOT EXISTS alumnos_pendientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        apellidoP TEXT,
        apellidoM TEXT,
        numero_control TEXT UNIQUE NOT NULL,
        carrera TEXT,
        semestre INTEGER,
        telefono TEXT,
        email TEXT,
        club_solicitado INTEGER,
        fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
        estatus TEXT DEFAULT 'PENDIENTE',
        FOREIGN KEY (club_solicitado) REFERENCES clubs(id)
    )");

    // Tabla asistencias
    $pdo->exec("CREATE TABLE IF NOT EXISTS asistencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        alumno_id INTEGER NOT NULL,
        club_id INTEGER NOT NULL,
        fecha DATE NOT NULL,
        presente INTEGER DEFAULT 0,
        FOREIGN KEY (alumno_id) REFERENCES alumnos(id),
        FOREIGN KEY (club_id) REFERENCES clubs(id),
        UNIQUE(alumno_id, fecha)
    )");

    // Tabla auditoria
    $pdo->exec("CREATE TABLE IF NOT EXISTS auditoria (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario INTEGER,
        accion TEXT NOT NULL,
        descripcion TEXT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
    )");

    // Tabla evaluacion
    $pdo->exec("CREATE TABLE IF NOT EXISTS evaluacion (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_alumno INTEGER NOT NULL,
        nombre_club TEXT NOT NULL,
        calificacion INTEGER,
        comentario TEXT,
        fecha_evaluacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_alumno) REFERENCES alumnos(id)
    )");

    // Tabla asignacion_cargos
    $pdo->exec("CREATE TABLE IF NOT EXISTS asignacion_cargos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario INTEGER NOT NULL,
        cargo TEXT NOT NULL,
        titulo_cargo TEXT,
        fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
    )");

    // Tabla configuracion
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        clave TEXT UNIQUE NOT NULL,
        valor TEXT,
        descripcion TEXT
    )");

    // Insertar datos iniciales si las tablas están vacías
    insertarDatosIniciales($pdo);
}

function insertarDatosIniciales($pdo) {
    // Verificar si ya hay usuarios
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        // Insertar usuario admin
        $pdo->exec("INSERT INTO usuarios (nombre, apellidoP, apellidoM, usuario, password, tipo) VALUES 
            ('Admin', 'Sistema', '', 'admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'OFICINA')");

        // Insertar usuario monitor de ejemplo
        $pdo->exec("INSERT INTO usuarios (nombre, apellidoP, apellidoM, usuario, password, tipo) VALUES 
            ('Ari', 'Monitor', '', 'ari', '" . password_hash('160223', PASSWORD_DEFAULT) . "', 'MONITOR')");
    }

    // Verificar si ya hay carreras
    $stmt = $pdo->query("SELECT COUNT(*) FROM carreras");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO carreras (nombre, descripcion) VALUES 
            ('Ingeniería en Sistemas', 'Carrera de sistemas computacionales'),
            ('Ingeniería Civil', 'Carrera de construcción'),
            ('Administración', 'Carrera de negocios')");
    }

    // Verificar si ya hay clubs
    $stmt = $pdo->query("SELECT COUNT(*) FROM clubs");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO clubs (nombre, tipo, descripcion, cupo_limite) VALUES 
            ('Club de Robótica', 'TECNOLOGICO', 'Club para entusiastas de la robótica', 25),
            ('Club de Música', 'CULTURAL', 'Club para amantes de la música', 50),
            ('Club Deportivo', 'DEPORTIVO', 'Club para actividades físicas', 40)");
    }
}

// Función para obtener la conexión PDO
function getPDO() {
    global $conexion;
    return $conexion;
}

// Alias para compatibilidad (aunque ahora usamos PDO)
$conn = $conexion;
