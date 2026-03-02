<?php
// Backend/AlumnosPendientes.php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

try {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    require_once __DIR__ . '/db.php';

    if (!isset($conexion) || !($conexion instanceof mysqli)) {
      if (function_exists('getMysqli') && getMysqli() instanceof mysqli) {
        $conexion = getMysqli();
      }
    }

    if (!isset($conexion) || !($conexion instanceof mysqli)) {
      throw new Exception('No hay conexión a la base de datos');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Listar alumnos
        $rows = [];
        $sql = 'SELECT * FROM alumnos_pendientes ORDER BY fecha_creacion DESC';
        if ($res = $conexion->query($sql)) {
          while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        }
        echo json_encode(['data' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);
        if (!is_array($payload)) { 
            throw new Exception('Payload de POST inválido o vacío: ' . substr($rawInput, 0, 100)); 
        }
        $items = isset($payload['nombre']) ? [$payload] : $payload;

        // Verificar si existe la columna opciones_nombres
        $hasOpcionesCol = false;
        $resCols = $conexion->query("SHOW COLUMNS FROM alumnos_pendientes LIKE 'opciones_nombres'");
        if ($resCols && $resCols->num_rows > 0) { $hasOpcionesCol = true; }

        $conexion->begin_transaction();
        try {
            // Desactivar FK checks para permitir semestre_id como valor directo (no siempre hay tabla semestres poblada)
            $conexion->query('SET FOREIGN_KEY_CHECKS=0');

            $cols = "nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, club_solicitado_id, estado";
            $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?";
            $types = "sssssiiis";
            if ($hasOpcionesCol) {
                $cols .= ", opciones_nombres";
                $placeholders .= ", ?";
                $types .= "s";
            }

            $sql = "INSERT INTO alumnos_pendientes ($cols) VALUES ($placeholders)";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) throw new Exception("Error preparando INSERT: " . $conexion->error);

            foreach ($items as $item) {
                $nombre = trim((string)($item['nombre'] ?? ''));
                $apellidoP = trim((string)($item['apellidoP'] ?? ''));
                $apellidoM = trim((string)($item['apellidoM'] ?? ''));
                $numeroControl = trim((string)($item['control'] ?? $item['numeroControl'] ?? ''));
                $telefono = trim((string)($item['telefono'] ?? ''));
                
                $carrera_id = (isset($item['carrera_id']) && $item['carrera_id'] !== '' && $item['carrera_id'] !== null) ? (int)$item['carrera_id'] : null;
                $semestre_id = (isset($item['semestre_id']) && $item['semestre_id'] !== '' && $item['semestre_id'] !== null) ? (int)$item['semestre_id'] : null;
                $club_solicitado_id = (isset($item['club_solicitado_id']) && $item['club_solicitado_id'] !== '' && $item['club_solicitado_id'] !== null) ? (int)$item['club_solicitado_id'] : null;
                $estado = $item['estado'] ?? 'PENDIENTE';

                if ($hasOpcionesCol) {
                    $opciones = isset($item['opciones']) ? (is_array($item['opciones']) ? implode(',', $item['opciones']) : (string)$item['opciones']) : null;
                    $stmt->bind_param($types, $nombre, $apellidoP, $apellidoM, $numeroControl, $telefono, $carrera_id, $semestre_id, $club_solicitado_id, $estado, $opciones);
                } else {
                    $stmt->bind_param($types, $nombre, $apellidoP, $apellidoM, $numeroControl, $telefono, $carrera_id, $semestre_id, $club_solicitado_id, $estado);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Error al insertar registro (" . ($nombre ?: 'Sin nombre') . "): " . $stmt->error);
                }
            }

            $conexion->query('SET FOREIGN_KEY_CHECKS=1');
            $conexion->commit();
            echo json_encode(['status' => 'success', 'message' => count($items) . ' registros procesados']);
            exit;
        } catch (Throwable $pe) {
            $conexion->rollback();
            throw $pe;
        }
    }

    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) throw new Exception('ID inválido');

        $payload = json_decode(file_get_contents('php://input'), true);
        $estado = $payload['estado'] ?? null;
        $club_solicitado_id = isset($payload['club_solicitado_id']) ? (int)$payload['club_solicitado_id'] : null;
        $motivo_rechazo = $payload['motivo_rechazo'] ?? null;

        if (!$estado) throw new Exception('Estado requerido');

        $sql = 'UPDATE alumnos_pendientes SET estado = ?, club_solicitado_id = IFNULL(?, club_solicitado_id), motivo_rechazo = IFNULL(?, motivo_rechazo) WHERE id = ?';
        $stmt = $conexion->prepare($sql);
        if (!$stmt) throw new Exception("Error preparando UPDATE: " . $conexion->error);
        
        $stmt->bind_param('sisi', $estado, $club_solicitado_id, $motivo_rechazo, $id);
        if (!$stmt->execute()) {
          throw new Exception("Error al actualizar: " . $stmt->error);
        }

        echo json_encode(['status' => 'success', 'message' => 'Registro actualizado']);
        exit;
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) throw new Exception('ID inválido');

        $stmt = $conexion->prepare('DELETE FROM alumnos_pendientes WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
          throw new Exception("Error al eliminar: " . $stmt->error);
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);

} catch (Throwable $e) {
    if (isset($conexion) && $conexion->connect_errno === 0 && $conexion->in_transaction) {
        $conexion->rollback();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor', 'message' => $e->getMessage()]);
}
