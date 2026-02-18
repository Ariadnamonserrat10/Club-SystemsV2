<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

try {
    // 1. Obtener asignaciones de cargos (usuarios del sistema)
    $sqlCargos = "SELECT u.id, u.nombre, u.apellidoP, u.apellidoM, u.cargo
                  FROM usuarios u 
                  WHERE u.cargo IS NOT NULL AND u.cargo <> ''";
    $resCargos = $conexion->query($sqlCargos);
    
    $firmas = [
        'jefe_promocion' => null,
        'jefe_actividades' => null,
        'jefa_servicios' => null
    ];

    while ($row = $resCargos->fetch_assoc()) {
        $nombreFull = trim($row['nombre'] . ' ' . $row['apellidoP'] . ' ' . $row['apellidoM']);
        $firmas[$row['cargo']] = [
            'id' => (int)$row['id'],
            'nombre' => mb_strtoupper($nombreFull, 'UTF-8')
        ];
    }

    // 2. Obtener configuraciones manuales (sobrescriben o completan)
    $resConfig = $conexion->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'firma_%'");
    while ($row = $resConfig->fetch_assoc()) {
        $cargo = str_replace('firma_', '', $row['clave']);
        if (array_key_exists($cargo, $firmas) && !empty($row['valor'])) {
            // Si hay un valor manual, lo usamos (especialmente para jefa_servicios)
            if ($cargo === 'jefa_servicios') {
                $firmas[$cargo] = [
                    'id' => null,
                    'nombre' => mb_strtoupper($row['valor'], 'UTF-8')
                ];
            }
        }
    }

    echo json_encode(['status' => 'success', 'data' => $firmas]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
