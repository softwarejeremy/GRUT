<?php
// Conexión a MySQL (base transito_escolar). Todas las páginas usan la variable $conexion.
// MYSQLI_REPORT_STRICT hace que los errores de MySQL lancen excepciones (try/catch).
$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "transito_escolar";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion = new mysqli($host, $usuario, $password, $base_datos);
$conexion->set_charset("utf8mb4");

?>