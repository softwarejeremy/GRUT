<?php
// Tarea 8 (Fabiola): guardar los cambios del perfil del estudiante.
// Lo envía el formulario de inicio_estudiante.php; el mensaje vuelve en $_SESSION["perfil_msg"].
session_start();
require_once("conexion.php");

// Que mysqli lance excepciones para poder usar try/catch con la transacción
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function volver($tipo, $mensaje) {
    $_SESSION["perfil_msg"] = ["tipo" => $tipo, "texto" => $mensaje];
    header("Location: ../inicio_estudiante.php");
    exit;
}

// Solo se acepta POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../inicio_estudiante.php");
    exit;
}

// Solo estudiantes con sesión activa
if (!isset($_SESSION["id_usuario"]) || ($_SESSION["rol"] ?? "") !== "Estudiante") {
    header("Location: ../iniciar_sesion.html");
    exit;
}

// El id SIEMPRE sale de la sesión (se ignora cualquier "id_usuario" del formulario)
$id_usuario = (int) $_SESSION["id_usuario"];

// Recibir y limpiar
$nombre   = trim($_POST["nombre"]   ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$curso    = trim($_POST["curso"]    ?? "");
$centro   = trim($_POST["centro"]   ?? "");

// Validar (el teléfono usa la misma expresión que procesar_register.php)
$errores = [];

$patron_nombre = '/^[\p{L}][\p{L}\s\'.-]*$/u';

if ($nombre === "" || mb_strlen($nombre) > 100 || !preg_match($patron_nombre, $nombre)) {
    $errores[] = "El nombre es obligatorio, solo letras y máximo 100 caracteres.";
}
if ($apellido === "" || mb_strlen($apellido) > 100 || !preg_match($patron_nombre, $apellido)) {
    $errores[] = "El apellido es obligatorio, solo letras y máximo 100 caracteres.";
}
if (!preg_match('/^[2678]\d{3}-\d{4}$/', $telefono)) {
    $errores[] = "El teléfono debe tener el formato 7222-3333 y empezar con 2, 6, 7 u 8.";
}
if ($curso === "" || mb_strlen($curso) > 50) {
    $errores[] = "El curso es obligatorio y máximo 50 caracteres.";
}
if ($centro === "" || mb_strlen($centro) > 150) {
    $errores[] = "El centro educativo es obligatorio y máximo 150 caracteres.";
}

if (!empty($errores)) {
    volver("error", implode(" ", $errores));
}

// Guardar con transacción: o se actualizan las dos tablas o ninguna
try {
    $conexion->begin_transaction();

    $stmt = $conexion->prepare("UPDATE usuario SET nombre = ?, apellido = ?, telefono = ? WHERE id_usuario = ?");
    $stmt->bind_param("sssi", $nombre, $apellido, $telefono, $id_usuario);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE estudiante SET curso_educacion = ?, centro_educativo = ? WHERE id_usuario = ?");
    $stmt->bind_param("ssi", $curso, $centro, $id_usuario);
    $stmt->execute();
    $stmt->close();

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    error_log("actualizar_perfil: " . $e->getMessage());
    volver("error", "No se pudo guardar la información. Intenta de nuevo.");
}

volver("exito", "Tu información se actualizó correctamente.");
