<?php
require_once __DIR__ . '/db.php';
$r = $conexion->query('SHOW TABLES');
while($row = $r->fetch_row()) { echo $row[0] . PHP_EOL; }
?>
