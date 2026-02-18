<?php
require_once __DIR__ . '/db.php';

$sql = "ALTER TABLE usuarios ADD COLUMN cargo VARCHAR(50) DEFAULT NULL";
if ($conexion->query($sql)) {
    echo "Columna 'cargo' agregada exitosamente.\n";
} else {
    echo "Error al agregar columna 'cargo': " . $conexion->error . "\n";
}

// También asegurar que existan los índices necesarios si no los hay
$conexion->query("CREATE INDEX idx_usuario_cargo ON usuarios(cargo)");
?>
