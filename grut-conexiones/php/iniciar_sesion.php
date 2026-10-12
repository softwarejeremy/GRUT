<?php

session_start();

require_once("conexion.php");

if($_SERVER['REQUEST_METHOD'] == 'POST'){

   $email = trim($_POST["email"] ?? "");
   $password = ($_POST["password"] ?? "");

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        die("Formato de correo invalido");
    }
    // Buscar usuario por correo
    $stmt = $conexion->prepare("SELECT u.id_usuario, u.nombre, u.contrasena, r.nombre AS rol FROM usuario u INNER JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario INNER JOIN roles r ON r.id_roles = ur.id_roles WHERE u.correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Correo o contraseña incorrectos.");
    }

    $user = $result->fetch_assoc();

    // Verificar contraseña
    if (!password_verify($password, $user['contrasena'])) {
        die("Correo o contraseña incorrectos.");
    }
    $stmt->close();

    session_regenerate_id(true);
    $_SESSION["id_usuario"] = $user["id_usuario"];
    $_SESSION["nombre"] = $user["nombre"];
    $_SESSION["rol"] = $user["rol"];

    if($user["rol"] == "Administrador"){
        header("Location:../inicio_administrador.php");
    } else if($user["rol"] == "Estudiante"){
        header("Location:../inicio_estudiante.php");
    } else{
        header("Location:../cuenta_conductor.php");
    }
    exit;
}

$conexion->close();

?>