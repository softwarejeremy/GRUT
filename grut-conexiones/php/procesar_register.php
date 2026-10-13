<?php
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$transaccionIniciada = false;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    require_once __DIR__ . "/conexion.php";

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $correo = trim($_POST["email"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $contrasena = $_POST["password"] ?? "";
    $confirmar = $_POST["confirmPassword"] ?? "";
    $centro = trim($_POST["centro"] ?? "");
    $curso = trim($_POST["curso"] ?? "");

    if(empty($nombre) || empty($apellido) || empty($correo) || empty($telefono) || empty($contrasena) || empty($confirmar) || empty($centro) || empty($curso)){
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Ingresa un correo electrónico válido.']);
        exit;
    }

    if(!preg_match("/^[2678]\d{3}-\d{4}$/", $telefono)){
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'El teléfono debe llevar guion (ej: 7000-0000).']);
        exit;
    }

    if(strlen($contrasena) < 6){
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
        exit;
    }

    if($contrasena !== $confirmar){
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
        exit;
    }

    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    $conexion->begin_transaction();
    $transaccionIniciada = true;

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
    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException("No existe el rol Estudiante en la tabla roles.");
    }
    $stmt->close();

    $conexion->commit();
    $transaccionIniciada = false;

    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Registro exitoso']);
} catch (Throwable $e) {
    if (isset($conexion) && $transaccionIniciada) {
        try {
            $conexion->rollback();
        } catch (Throwable $rollbackError) {
            error_log("Error al revertir el registro: " . $rollbackError->getMessage());
        }
    }

    error_log("Error en procesar_register.php: " . $e->getMessage());
    http_response_code($e instanceof mysqli_sql_exception && $e->getCode() == 1062 ? 409 : 500);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e instanceof mysqli_sql_exception && $e->getCode() == 1062
            ? 'Ese correo ya está registrado.'
            : 'No se pudo completar el registro. Revisa la conexión o la configuración del servidor.'
    ]);
}
?>