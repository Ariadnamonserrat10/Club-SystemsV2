<?php
require_once __DIR__ . '/db.php';

echo "--- USUARIOS MONITORES ---\n";
$res = $conexion->query("SELECT id, nombre, usuario, club_asignado FROM usuarios WHERE tipo = 'MONITOR'");
while($row = $res->fetch_assoc()) {
    print_r($row);
    if ($row['club_asignado']) {
        $clubId = $row['club_asignado'];
        echo "   --- ALUMNOS EN CLUB $clubId ---\n";
        $stmt = $conexion->prepare("SELECT id, nombre, numeroControl FROM alumnos WHERE id_club = ?");
        $stmt->bind_param("i", $clubId);
        $stmt->execute();
        $resA = $stmt->get_result();
        while($al = $resA->fetch_assoc()) {
            print_r($al);
            $aid = $al['id'];
            echo "      --- ASISTENCIAS PARA ALUMNO $aid ---\n";
            $resAs = $conexion->query("SELECT * FROM asistencias WHERE id_alumno = $aid");
            while($as = $resAs->fetch_assoc()) {
                print_r($as);
            }
        }
    }
}
?>
