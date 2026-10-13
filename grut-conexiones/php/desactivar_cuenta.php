<?php
// TAREA 13: desactiva la cuenta del usuario con sesión iniciada.
// Ubicación: php/desactivar_cuenta.php (junto a conexion.php)

session_start();

// Sin sesión -> al login. (No se usa verificar_sesion.php porque desde php/
// redirigiría a php/iniciar_sesion.html, que no existe.)
if(!isset($_SESSION["id_usuario"])){
    header("Location: ../iniciar_sesion.html");
    exit;
}

// Solo se acepta POST (el formulario de configuracion.html)
if($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("Location: ../configuracion.html");
    exit;
}

require_once("conexion.php");

// El id SIEMPRE sale de la sesión, nunca del formulario
$id_usuario = (int) $_SESSION["id_usuario"];

$stmt = $conexion->prepare("UPDATE usuario SET activo = 0 WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);

if(!$stmt->execute()){
    $stmt->close();
    $conexion->close();
    die("No se pudo desactivar la cuenta. Inténtalo de nuevo.");
}

$stmt->close();
$conexion->close();

// Cerrar la sesión por completo
$_SESSION = [];
if(ini_get("session.use_cookies")){
    $p = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();

header("Location: ../inicio.html");
exit;
