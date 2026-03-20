<?php
// Script para migrar datos de MySQL a SQLite
// Ejecutar una vez para poblar la base SQLite

require_once __DIR__ . '/db.php'; // Esto creará las tablas en SQLite si no existen

// Conectar a MySQL para leer datos
$mysql = new mysqli("127.0.0.1", "Encargado", "1234", "sistema_clubs", 3307);
if ($mysql->connect_error) {
    die("Error MySQL: " . $mysql->connect_error);
}
$mysql->set_charset("utf8mb4");

// Obtener datos de MySQL
$usuarios = $mysql->query("SELECT * FROM usuarios")->fetch_all(MYSQLI_ASSOC);
$clubs = $mysql->query("SELECT * FROM clubs")->fetch_all(MYSQLI_ASSOC);
$carreras = $mysql->query("SELECT * FROM carreras")->fetch_all(MYSQLI_ASSOC);
$alumnos = $mysql->query("SELECT * FROM alumnos")->fetch_all(MYSQLI_ASSOC);
$alumnos_pendientes = $mysql->query("SELECT * FROM alumnos_pendientes")->fetch_all(MYSQLI_ASSOC);
$asistencias = $mysql->query("SELECT * FROM asistencias")->fetch_all(MYSQLI_ASSOC);
$auditoria = $mysql->query("SELECT * FROM auditoria")->fetch_all(MYSQLI_ASSOC);
$evaluacion = $mysql->query("SELECT * FROM evaluacion")->fetch_all(MYSQLI_ASSOC);
$asignacion_cargos = $mysql->query("SELECT * FROM asignacion_cargos")->fetch_all(MYSQLI_ASSOC);
$configuracion = $mysql->query("SELECT * FROM configuracion")->fetch_all(MYSQLI_ASSOC);

// Insertar en SQLite
$pdo = getConnection(); // PDO

// Insertar usuarios
$stmt = $pdo->prepare("INSERT OR REPLACE INTO usuarios (id, nombre, apellidoP, apellidoM, usuario, password, tipo, foto, club_asignado, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($usuarios as $u) {
    $stmt->execute([$u['id'], $u['nombre'], $u['apellidoP'], $u['apellidoM'], $u['usuario'], $u['password'], $u['tipo'], $u['foto'], $u['club_asignado'], $u['creado_en']]);
}

// Insertar clubs
$stmt = $pdo->prepare("INSERT OR REPLACE INTO clubs (id, nombre, tipo, descripcion, cupo_limite, cupo_ocupado, id_responsable, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($clubs as $c) {
    $stmt->execute([$c['id'], $c['nombre'], $c['tipo'], $c['descripcion'], $c['cupo_limite'], $c['cupo_ocupado'], $c['id_responsable'], $c['creado_en']]);
}

// Insertar carreras
$stmt = $pdo->prepare("INSERT OR REPLACE INTO carreras (id, nombre, descripcion, creado_en) VALUES (?, ?, ?, ?)");
foreach ($carreras as $c) {
    $stmt->execute([$c['id'], $c['nombre'], $c['descripcion'], $c['creado_en']]);
}

// Insertar alumnos
$stmt = $pdo->prepare("INSERT OR REPLACE INTO alumnos (id, nombre, apellidoP, apellidoM, numero_control, carrera_id, semestre, telefono, email, club_asignado, fecha_inscripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($alumnos as $a) {
    $stmt->execute([$a['id'], $a['nombre'], $a['apellidoP'], $a['apellidoM'], $a['numero_control'], $a['carrera_id'], $a['semestre'], $a['telefono'], $a['email'], $a['club_asignado'], $a['fecha_inscripcion']]);
}

// Insertar alumnos_pendientes
$stmt = $pdo->prepare("INSERT OR REPLACE INTO alumnos_pendientes (id, nombre, apellidoP, apellidoM, numero_control, carrera, semestre, telefono, email, club_solicitado, fecha_solicitud, estatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($alumnos_pendientes as $ap) {
    $stmt->execute([$ap['id'], $ap['nombre'], $ap['apellidoP'], $ap['apellidoM'], $ap['numero_control'], $ap['carrera'], $ap['semestre'], $ap['telefono'], $ap['email'], $ap['club_solicitado'], $ap['fecha_solicitud'], $ap['estatus']]);
}

// Insertar asistencias
$stmt = $pdo->prepare("INSERT OR REPLACE INTO asistencias (id, alumno_id, club_id, fecha, presente) VALUES (?, ?, ?, ?, ?)");
foreach ($asistencias as $as) {
    $stmt->execute([$as['id'], $as['alumno_id'], $as['club_id'], $as['fecha'], $as['presente']]);
}

// Insertar auditoria
$stmt = $pdo->prepare("INSERT OR REPLACE INTO auditoria (id, id_usuario, accion, descripcion, fecha) VALUES (?, ?, ?, ?, ?)");
foreach ($auditoria as $au) {
    $stmt->execute([$au['id'], $au['id_usuario'], $au['accion'], $au['descripcion'], $au['fecha']]);
}

// Insertar evaluacion
$stmt = $pdo->prepare("INSERT OR REPLACE INTO evaluacion (id, id_alumno, nombre_club, calificacion, comentario, fecha_evaluacion) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($evaluacion as $e) {
    $stmt->execute([$e['id'], $e['id_alumno'], $e['nombre_club'], $e['calificacion'], $e['comentario'], $e['fecha_evaluacion']]);
}

// Insertar asignacion_cargos
$stmt = $pdo->prepare("INSERT OR REPLACE INTO asignacion_cargos (id, id_usuario, cargo, titulo_cargo, fecha_asignacion) VALUES (?, ?, ?, ?, ?)");
foreach ($asignacion_cargos as $ac) {
    $stmt->execute([$ac['id'], $ac['id_usuario'], $ac['cargo'], $ac['titulo_cargo'], $ac['fecha_asignacion']]);
}

// Insertar configuracion
$stmt = $pdo->prepare("INSERT OR REPLACE INTO configuracion (id, clave, valor, descripcion) VALUES (?, ?, ?, ?)");
foreach ($configuracion as $conf) {
    $stmt->execute([$conf['id'], $conf['clave'], $conf['valor'], $conf['descripcion']]);
}

echo "Migración completada.\n";
?>