<?php
// "Guardia" de las páginas privadas (base del proyecto, sección 3 del LEEME).
// Si no hay sesión iniciada, manda al inicio de sesión. Cada página comprueba después su rol.
session_start();
if(!isset($_SESSION["id_usuario"])){
    header("Location: iniciar_sesion.html");
    exit;
}

?>