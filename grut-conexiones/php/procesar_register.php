<?php

require_once("conexion.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $correo = trim($_POST["email"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $contrasena = $_POST["password"] ?? "";
    $confirmar = $_POST["confirmPassword"] ?? "";
    $centro = trim($_POST["centro"] ?? "");
    $curso = trim($_POST["curso"] ?? "");

    if(
        strlen($nombre) >= 1 &&
        strlen($apellido) >= 1 &&
        strlen($correo) >= 1 &&
        strlen($telefono) >= 1 &&
        strlen($contrasena) >= 1
    )
    {
        if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
            echo "Ingresa un correo electronico valido";
        } else if(!preg_match("/^[2678]\d{3}-\d{4}$/", $telefono)){
            echo "El telefono debe llevar guion (ej: 7000-0000)";
        } else if(strlen($contrasena) < 6){
            echo "La contraseña debe tener al menos 6 caracteres";
        } else if($contrasena !== $confirmar){
            echo "Las contraseñas no coinciden";
        } else{
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            $conexion->begin_transaction();

            try{
                $stmt = $conexion->prepare("INSERT INTO usuario (nombre, apellido, correo, telefono, contrasena) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nombre, $apellido, $correo, $telefono, $hash);
                $stmt->execute();
                $id_usuario = $conexion->insert_id;
                $stmt->close();

                $stmt = $conexion->prepare("INSERT INTO estudiante (id_usuario, curso_educacion, centro_educativo) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $id_usuario, $curso, $centro);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexion->prepare("INSERT INTO usuario_rol (id_usuario, id_roles) SELECT ?, id_roles FROM roles WHERE nombre = 'Estudiante'");
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                $stmt->close();

                $conexion->commit();
                header("Location: ../iniciar_sesion.html");
                exit;
            } catch(mysqli_sql_exception $e){
                $conexion->rollback();

                // 1062 = el correo ya existe (UNIQUE en usuario.correo)
                if($e->getCode() == 1062){
                    echo "Ese correo ya esta registrado";
                } else{
                    echo "Error al registrar: " . $e->getMessage();
                }
            }
        }
    } else{
        echo "Todos los campos son obligatorios";
    }
}
$conexion->close();


?>
