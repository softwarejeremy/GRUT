<?php
// Tarea 12 (Bryan): funciones comunes de las páginas del administrador.
// Cada página *_administrador.php lo incluye al inicio:
//   - comprueba la sesión y el rol "Administrador"
//   - si la petición es POST, la página responde en JSON (crear, actualizar, eliminar)
require_once(__DIR__ . "/verificar_sesion.php");
require_once(__DIR__ . "/conexion.php");

if(($_SESSION["rol"] ?? "") != "Administrador"){
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        responder(false, "Tu sesión no tiene permisos de administrador.", 403);
    }
    header("Location: iniciar_sesion.html");
    exit;
}

// Token contra envíos desde otras páginas (CSRF)
function token_csrf(){
    if(!isset($_SESSION["csrf"])){
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

// Respuesta JSON para el JavaScript de la página (scripts/admin_crud.js)
function responder($ok, $mensaje, $codigo = 200){
    http_response_code($codigo);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["ok" => $ok, "msg" => $mensaje]);
    exit;
}

// Se llama al principio de cada bloque POST
function comprobar_csrf(){
    if(!hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")){
        responder(false, "La página caducó. Recárgala e inténtalo de nuevo.", 403);
    }
}

// Mensajes claros para los errores de MySQL más comunes
function responder_error_mysql($e, $mensaje_en_uso){
    if($e->getCode() == 1062){
        responder(false, "Ese dato ya existe (correo, placa o licencia repetidos).", 409);
    }
    if($e->getCode() == 1451){
        responder(false, $mensaje_en_uso, 409);
    }
    error_log("Error en " . basename($_SERVER["SCRIPT_NAME"]) . ": " . $e->getMessage());
    responder(false, "Error de base de datos.", 500);
}

function texto_valido($valor, $minimo, $maximo){
    $largo = mb_strlen(trim($valor));
    return $largo >= $minimo && $largo <= $maximo;
}

// Mismo formato de teléfono que en el registro: 7000-0000
function telefono_valido($valor){
    return preg_match("/^[2678]\d{3}-\d{4}$/", $valor) === 1;
}

$csrf = token_csrf();
?>
