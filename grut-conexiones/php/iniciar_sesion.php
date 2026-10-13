<?php
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    require_once __DIR__ . "/conexion.php";

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Formato de correo inválido.']);
        exit;
    }
    
    $stmt = $conexion->prepare("SELECT u.id_usuario, u.nombre, u.contrasena, u.activo, r.nombre AS rol FROM usuario u INNER JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario INNER JOIN roles r ON r.id_roles = ur.id_roles WHERE u.correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['contrasena'])) {
        $stmt->close();
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Correo o contraseña incorrectos.']);
        exit;
    }
    $stmt->close();

    // Rechazar cuentas desactivadas (tarea 13)
    if ((int) $user["activo"] !== 1) {
        http_response_code(403);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Esta cuenta está desactivada. Contacta a un administrador para reactivarla.']);
        exit;
    }

    // Solo estos roles tienen panel; a los demás no se les crea la sesión (tarea 4)
    if ($user["rol"] != "Conductor" && $user["rol"] != "Administrador" && $user["rol"] != "Estudiante") {
        http_response_code(403);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Tu tipo de cuenta todavía no tiene panel disponible.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION["id_usuario"] = $user["id_usuario"];
    $_SESSION["nombre"] = $user["nombre"];
    $_SESSION["rol"] = $user["rol"];

    if($user["rol"] == "Administrador"){
        $redirectUrl = "inicio_administrador.php";
    } else if($user["rol"] == "Estudiante"){
        $redirectUrl = "inicio_estudiante.php";
    } else if($user["rol"] == "Conductor"){
        $redirectUrl = "cuenta_conductor.php";
    }

    ob_clean();
    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
} catch (Throwable $e) {
    error_log("Error en iniciar_sesion.php: " . $e->getMessage());
    http_response_code(500);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo iniciar sesión por un problema del servidor.'
    ]);
}
?>