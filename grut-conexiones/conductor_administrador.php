<?php
// Tarea 11 (Fabiola): mostrar los conductores. Tarea 12 (Bryan): agregar, editar y borrar.
// Un conductor = fila en usuario + fila en conductor + rol "Conductor" en usuario_rol.
require_once("php/admin_comun.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    comprobar_csrf();

    function validar_conductor($campo, $valor){
        if($campo == "nombre" || $campo == "apellido"){
            return texto_valido($valor, 2, 100) ? "" : "Nombre y apellido: de 2 a 100 caracteres.";
        }
        if($campo == "licencia"){
            return texto_valido($valor, 3, 50) ? "" : "Licencia: de 3 a 50 caracteres.";
        }
        if($campo == "correo"){
            return (filter_var($valor, FILTER_VALIDATE_EMAIL) && mb_strlen($valor) <= 150) ? "" : "Correo no válido.";
        }
        if($campo == "telefono"){
            return telefono_valido($valor) ? "" : "El teléfono debe llevar guion (ej: 7000-0000).";
        }
        if($campo == "contrasena"){
            return mb_strlen($valor) >= 6 ? "" : "La contraseña debe tener al menos 6 caracteres.";
        }
        return "Campo desconocido.";
    }

    $accion = $_POST["accion"] ?? "";
    $en_transaccion = false;
    try {
        if($accion == "crear"){
            $d = [];
            foreach(["nombre", "apellido", "licencia", "correo", "contrasena", "telefono"] as $campo){
                $d[$campo] = trim($_POST[$campo] ?? "");
                $error = validar_conductor($campo, $d[$campo]);
                if($error != ""){ responder(false, $error, 422); }
            }
            $hash = password_hash($d["contrasena"], PASSWORD_DEFAULT);

            $conexion->begin_transaction();
            $en_transaccion = true;

            $stmt = $conexion->prepare("INSERT INTO usuario (nombre, apellido, correo, telefono, contrasena) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $d["nombre"], $d["apellido"], $d["correo"], $d["telefono"], $hash);
            $stmt->execute();
            $id_usuario = $conexion->insert_id;
            $stmt->close();

            $stmt = $conexion->prepare("INSERT INTO conductor (id_usuario, licencia) VALUES (?, ?)");
            $stmt->bind_param("is", $id_usuario, $d["licencia"]);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("INSERT INTO usuario_rol (id_usuario, id_roles) SELECT ?, id_roles FROM roles WHERE nombre = 'Conductor'");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            if($stmt->affected_rows !== 1){
                throw new RuntimeException("No existe el rol Conductor en la tabla roles.");
            }
            $stmt->close();

            $conexion->commit();
            responder(true, "Conductor creado.");
        }

        if($accion == "actualizar"){
            $id = (int)($_POST["id"] ?? 0);   // id_conductor
            $campo = $_POST["campo"] ?? "";
            $valor = trim($_POST["valor"] ?? "");
            if($id <= 0){ responder(false, "ID no válido.", 422); }
            $error = validar_conductor($campo, $valor);
            if($error != "" || $campo == "contrasena"){ responder(false, $error ?: "Campo desconocido.", 422); }

            if($campo == "licencia"){
                $stmt = $conexion->prepare("UPDATE conductor SET licencia = ? WHERE id_conductor = ?");
            } else {
                // El nombre de la columna sale de esta lista, nunca del formulario
                $columnas = ["nombre" => "nombre", "apellido" => "apellido", "correo" => "correo", "telefono" => "telefono"];
                $stmt = $conexion->prepare("UPDATE usuario u INNER JOIN conductor c ON c.id_usuario = u.id_usuario SET u." . $columnas[$campo] . " = ? WHERE c.id_conductor = ?");
            }
            $stmt->bind_param("si", $valor, $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Cambio guardado.");
        }

        if($accion == "eliminar"){
            $id = (int)($_POST["id"] ?? 0);   // id_conductor
            $stmt = $conexion->prepare("SELECT id_usuario FROM conductor WHERE id_conductor = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if(!$fila){ responder(false, "El conductor no existe.", 404); }
            $id_usuario = (int)$fila["id_usuario"];

            $conexion->begin_transaction();
            $en_transaccion = true;

            // Sus rutas quedan sin conductor asignado
            $stmt = $conexion->prepare("UPDATE rutas SET id_conductor = NULL WHERE id_conductor = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("DELETE FROM conductor WHERE id_conductor = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("DELETE FROM usuario_rol WHERE id_usuario = ? AND id_roles = (SELECT id_roles FROM roles WHERE nombre = 'Conductor')");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();

            // El usuario se borra solo si no es también estudiante ni tiene otros roles
            $stmt = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = ? AND NOT EXISTS (SELECT 1 FROM estudiante WHERE id_usuario = ?) AND NOT EXISTS (SELECT 1 FROM usuario_rol WHERE id_usuario = ?)");
            $stmt->bind_param("iii", $id_usuario, $id_usuario, $id_usuario);
            $stmt->execute();
            $stmt->close();

            $conexion->commit();
            responder(true, "Conductor eliminado.");
        }

        responder(false, "Acción desconocida.", 400);
    } catch(Throwable $e){
        if($en_transaccion){
            $conexion->rollback();
        }
        if($e instanceof mysqli_sql_exception){
            responder_error_mysql($e, "No se puede borrar: el conductor tiene datos relacionados.");
        }
        error_log("Error en conductor_administrador.php: " . $e->getMessage());
        responder(false, "No se pudo completar la acción.", 500);
    }
}

$stmt = $conexion->prepare("SELECT c.id_conductor, u.nombre, u.apellido, c.licencia, u.correo, u.telefono FROM conductor c INNER JOIN usuario u ON u.id_usuario = c.id_usuario ORDER BY c.id_conductor");
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conductores | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-conductores" data-csrf="<?php echo htmlspecialchars($csrf); ?>">

    <header class="navbar">
        <div class="logo">
            <img src="imagenes/logotipo-512.png" alt="Logotipo de la empresa de transporte">
            <h1>GRUT</h1>
        </div>
        <nav class="navlinks" aria-label="Navegación principal">
            <ul>
                <li><a class="btn" href="inicio.html">Inicio</a></li>
                <li><a class="btn" href="como_funciona.html">Cómo funciona</a></li>
                <li><a class="btn" href="soporte.html">Soporte</a></li>
                <li><a class="btn" href="configuracion.html">Configuración</a></li>
                <li><a class="btn" href="iniciar_sesion.html">Cuenta</a></li>
            </ul>
        </nav>
    </header>

    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <main id="contenido" tabindex="-1">
        <a href="inicio_administrador.php" class="back-link">&larr; Regresar a elegir</a>

        <section class="panel">
            <h2><span aria-hidden="true">🧑‍✈️</span> Conductor</h2>

            <div class="panel-actions">
                <button type="button" class="btn-add" id="btnNuevo">+ Nuevo conductor</button>
                <button type="button" class="btn-remove" id="btnBorrar">- Borrar conductor</button>
            </div>

            <div class="table-container" role="region" aria-label="Tabla de conductores" tabindex="0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Licencia</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()){ ?>
                        <tr data-id="<?php echo (int)$fila["id_conductor"]; ?>" data-nombre="al conductor <?php echo htmlspecialchars($fila["nombre"] . " " . $fila["apellido"]); ?>">
                            <td><input type="text" data-campo="nombre" value="<?php echo htmlspecialchars($fila["nombre"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar nombre">✏️</button></td>
                            <td><input type="text" data-campo="apellido" value="<?php echo htmlspecialchars($fila["apellido"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar apellido">✏️</button></td>
                            <td><input type="text" data-campo="licencia" value="<?php echo htmlspecialchars($fila["licencia"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar licencia">✏️</button></td>
                            <td><input type="text" data-campo="correo" value="<?php echo htmlspecialchars($fila["correo"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar correo">✏️</button></td>
                            <td><input type="text" data-campo="telefono" value="<?php echo htmlspecialchars($fila["telefono"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar teléfono">✏️</button></td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Borrar" aria-label="Borrar conductor">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="5">No hay conductores registrados</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <dialog id="dlgNuevo">
        <form id="formNuevo" method="dialog">
            <h3>Nuevo conductor</h3>
            <input name="nombre" placeholder="Nombre" maxlength="100" required>
            <input name="apellido" placeholder="Apellido" maxlength="100" required>
            <input name="licencia" placeholder="Licencia (ej. 0000-000000-000-0)" maxlength="50" required>
            <input name="correo" type="email" placeholder="Correo" maxlength="150" required>
            <input name="contrasena" type="password" placeholder="Contraseña (mín. 6)" minlength="6" required autocomplete="new-password">
            <input name="telefono" placeholder="Teléfono (ej. 7000-0000)" pattern="[2678][0-9]{3}-[0-9]{4}" required>
            <menu>
                <button type="button" id="cancelarNuevo">Cancelar</button>
                <button type="submit">Guardar</button>
            </menu>
        </form>
    </dialog>

    <div id="toast" role="status" aria-live="polite"></div>

    <footer>
        <h2>IG: @GRUT_updates</h2>
        <h2>FB: @GRUT_updates</h2>
        <h2>WAPP: +503 0000-0000</h2>
        <h2>X: @GRUT_updates</h2>
    </footer>

    <script src="scripts/admin_crud.js"></script>
</body>
</html>
