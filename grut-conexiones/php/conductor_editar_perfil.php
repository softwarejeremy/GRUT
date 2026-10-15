<?php
// Tarea 9 (Bryan): guardar los cambios del perfil del conductor.
// Mismo esquema que php/actualizar_perfil.php (tarea 8): el mensaje vuelve en $_SESSION["perfil_msg"].
session_start();
require_once("conexion.php");

function volver($tipo, $mensaje){
    $_SESSION["perfil_msg"] = ["tipo" => $tipo, "texto" => $mensaje];
    header("Location: ../cuenta_conductor.php");
    exit;
}

// Solo se acepta POST (desde el formulario de cuenta_conductor.php)
if($_SERVER["REQUEST_METHOD"] != "POST"){
    header("Location: ../cuenta_conductor.php");
    exit;
}

// Solo conductores con sesión activa
if(!isset($_SESSION["id_usuario"]) || ($_SESSION["rol"] ?? "") != "Conductor"){
    header("Location: ../iniciar_sesion.html");
    exit;
}

// El id SIEMPRE sale de la sesión, nunca del formulario
$id_usuario = (int)$_SESSION["id_usuario"];

$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$licencia = trim($_POST["num-licencia"] ?? "");

// Validamos igual que en el registro, solo los campos que se pueden editar
$errores = [];
if(mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100){
    $errores[] = "El nombre debe tener de 2 a 100 caracteres.";
}
if(mb_strlen($apellido) < 2 || mb_strlen($apellido) > 100){
    $errores[] = "El apellido debe tener de 2 a 100 caracteres.";
}
if(!preg_match("/^[2678]\d{3}-\d{4}$/", $telefono)){
    $errores[] = "El teléfono debe llevar guion (ej: 7000-0000).";
}
if(mb_strlen($licencia) < 3 || mb_strlen($licencia) > 50){
    $errores[] = "La licencia debe tener de 3 a 50 caracteres.";
}
if(!empty($errores)){
    volver("error", implode(" ", $errores));
}

// Con transacción: o se actualizan las dos tablas o ninguna
try {
    $conexion->begin_transaction();

    $stmt = $conexion->prepare("UPDATE usuario SET nombre = ?, apellido = ?, telefono = ? WHERE id_usuario = ?");
    $stmt->bind_param("sssi", $nombre, $apellido, $telefono, $id_usuario);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE conductor SET licencia = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $licencia, $id_usuario);
    $stmt->execute();
    $stmt->close();

    $conexion->commit();
} catch(Throwable $e){
    $conexion->rollback();
    if($e instanceof mysqli_sql_exception && $e->getCode() == 1062){
        volver("error", "Esa licencia ya está registrada por otro conductor.");
    }
    error_log("conductor_editar_perfil: " . $e->getMessage());
    volver("error", "No se pudo guardar la información. Intenta de nuevo.");
}

volver("exito", "Tu información se actualizó correctamente.");
