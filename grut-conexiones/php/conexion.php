<?php

$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "transito_escolar";
$conexion = mysqli_connect($host, $usuario, $password, $base_datos);

if(!$conexion){
    die("Error de conexion: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

?>