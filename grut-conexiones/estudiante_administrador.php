<?php
// Tarea 11 (Fabiola): mostrar los estudiantes. Tarea 12 (Bryan): agregar, editar y borrar.
// Un estudiante = fila en usuario + fila en estudiante + rol "Estudiante" (igual que php/procesar_register.php).
require_once("php/admin_comun.php");

// Mismas opciones que el registro (registro.html, tarea 15)
$centros = ["Centro Escolar Cantón Dolores Apulo", "Colegio San Patricio", "Universidad Don Bosco", "Colegio Bautista", "Universidad Tecnológica de El Salvador"];
$cursos = ["Primaria", "Secundaria", "Bachillerato", "Universitario"];

// ---------- Tarea 12 (Bryan): agregar, editar y borrar ----------
// scripts/admin_crud.js envía cada cambio por POST (fetch) y aquí se responde en JSON.
// Todas las consultas son preparadas (?) y el nombre de la columna sale de una lista fija.
if($_SERVER["REQUEST_METHOD"] == "POST"){
    comprobar_csrf();

    function validar_estudiante($campo, $valor){
        if($campo == "nombre" || $campo == "apellido"){
            return texto_valido($valor, 2, 100) ? "" : "Nombre y apellido: de 2 a 100 caracteres.";
        }
        if($campo == "centro_educativo"){
            return texto_valido($valor, 2, 150) ? "" : "Elige un centro educativo.";
        }
        if($campo == "curso_educacion"){
            return texto_valido($valor, 2, 50) ? "" : "Elige un curso.";
        }
        if($campo == "correo"){
            return (filter_var($valor, FILTER_VALIDATE_EMAIL) && mb_strlen($valor) <= 150) ? "" : "Correo no válido.";
        }
        if($campo == "telefono"){
            return telefono_valido($valor) ? "" : "El teléfono debe llevar guion (ej: 7000-0000).";
        }
        if($campo == "activo"){
            // Tarea 13: 1 = puede iniciar sesión, 0 = cuenta desactivada
            return ($valor === "0" || $valor === "1") ? "" : "Valor no válido.";
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
            foreach(["nombre", "apellido", "centro_educativo", "curso_educacion", "correo", "telefono", "contrasena"] as $campo){
                $d[$campo] = trim($_POST[$campo] ?? "");
                $error = validar_estudiante($campo, $d[$campo]);
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

            $stmt = $conexion->prepare("INSERT INTO estudiante (id_usuario, curso_educacion, centro_educativo) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_usuario, $d["curso_educacion"], $d["centro_educativo"]);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("INSERT INTO usuario_rol (id_usuario, id_roles) SELECT ?, id_roles FROM roles WHERE nombre = 'Estudiante'");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            if($stmt->affected_rows !== 1){
                throw new RuntimeException("No existe el rol Estudiante en la tabla roles.");
            }
            $stmt->close();

            $conexion->commit();
            responder(true, "Estudiante creado.");
        }

        if($accion == "actualizar"){
            $id = (int)($_POST["id"] ?? 0);   // id_estudiante
            $campo = $_POST["campo"] ?? "";
            $valor = trim($_POST["valor"] ?? "");
            if($id <= 0){ responder(false, "ID no válido.", 422); }
            $error = validar_estudiante($campo, $valor);
            if($error != "" || $campo == "contrasena"){ responder(false, $error ?: "Campo desconocido.", 422); }

            // El nombre de la columna sale de estas listas, nunca del formulario
            $de_estudiante = ["centro_educativo" => "centro_educativo", "curso_educacion" => "curso_educacion"];
            $de_usuario = ["nombre" => "nombre", "apellido" => "apellido", "correo" => "correo", "telefono" => "telefono", "activo" => "activo"];
            if(isset($de_estudiante[$campo])){
                $stmt = $conexion->prepare("UPDATE estudiante SET " . $de_estudiante[$campo] . " = ? WHERE id_estudiante = ?");
            } else {
                $stmt = $conexion->prepare("UPDATE usuario u INNER JOIN estudiante e ON e.id_usuario = u.id_usuario SET u." . $de_usuario[$campo] . " = ? WHERE e.id_estudiante = ?");
            }
            $stmt->bind_param("si", $valor, $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Cambio guardado.");
        }

        if($accion == "eliminar"){
            $id = (int)($_POST["id"] ?? 0);   // id_estudiante
            $stmt = $conexion->prepare("SELECT id_usuario FROM estudiante WHERE id_estudiante = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if(!$fila){ responder(false, "El estudiante no existe.", 404); }
            $id_usuario = (int)$fila["id_usuario"];

            $conexion->begin_transaction();
            $en_transaccion = true;

            // Si tiene rutas o facturas, MySQL no deja borrarlo (error 1451)
            $stmt = $conexion->prepare("DELETE FROM estudiante WHERE id_estudiante = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("DELETE FROM usuario_rol WHERE id_usuario = ? AND id_roles = (SELECT id_roles FROM roles WHERE nombre = 'Estudiante')");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();

            // El usuario se borra solo si no le quedan otros roles ni es conductor
            $stmt = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = ? AND NOT EXISTS (SELECT 1 FROM conductor WHERE id_usuario = ?) AND NOT EXISTS (SELECT 1 FROM estudiante WHERE id_usuario = ?) AND NOT EXISTS (SELECT 1 FROM usuario_rol WHERE id_usuario = ?)");
            $stmt->bind_param("iiii", $id_usuario, $id_usuario, $id_usuario, $id_usuario);
            $stmt->execute();
            $stmt->close();

            $conexion->commit();
            responder(true, "Estudiante eliminado.");
        }

        responder(false, "Acción desconocida.", 400);
    } catch(Throwable $e){
        if($en_transaccion){
            $conexion->rollback();
        }
        if($e instanceof mysqli_sql_exception){
            responder_error_mysql($e, "Este estudiante tiene rutas o facturas. Quítalo de sus rutas (página Rutas) y borra sus facturas antes de borrarlo.");
        }
        error_log("Error en estudiante_administrador.php: " . $e->getMessage());
        responder(false, "No se pudo completar la acción.", 500);
    }
}

// ---------- Tarea 11 (Fabiola): consultas para mostrar los datos en la página ----------
$stmt = $conexion->prepare("SELECT e.id_estudiante, u.nombre, u.apellido, e.centro_educativo, e.curso_educacion, u.correo, u.telefono, u.activo FROM estudiante e INNER JOIN usuario u ON u.id_usuario = e.id_usuario ORDER BY e.id_estudiante");
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

// Lista desplegable; si el dato guardado no está en la lista, se agrega para no perderlo
function opciones($lista, $actual){
    if($actual !== "" && !in_array($actual, $lista, true)){
        $lista[] = $actual;
    }
    foreach($lista as $opcion){
        $marcado = ($opcion === $actual) ? " selected" : "";
        echo "<option value=\"" . htmlspecialchars($opcion) . "\"" . $marcado . ">" . htmlspecialchars($opcion) . "</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-estudiantes" data-csrf="<?php echo htmlspecialchars($csrf); ?>">

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
                <!-- Tarea 14 (Andrea): "Cuenta" lleva al inicio de sesión -->
                <li><a class="btn" href="iniciar_sesion.html">Cuenta</a></li>
            </ul>
        </nav>
    </header>

    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <main id="contenido" tabindex="-1">
        <a href="inicio_administrador.php" class="back-link">&larr; Regresar a elegir</a>

        <section class="panel">
            <h2><span aria-hidden="true">🎓</span> Estudiantes</h2>

            <!-- Tarea 12: botones para agregar y para activar el modo borrar -->
            <div class="panel-actions">
                <button type="button" class="btn-add" id="btnNuevo">+ Nuevo estudiante</button>
                <button type="button" class="btn-remove" id="btnBorrar">- Borrar estudiante</button>
            </div>

            <!-- Tarea 11: tabla con los datos de la base (un while = una fila por registro).
                 Tarea 12: el lápiz edita el campo, las listas se guardan al cambiar y 🗑️ borra la fila. -->
            <div class="table-container" role="region" aria-label="Tabla de estudiantes" tabindex="0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Centro educativo</th>
                            <th>Curso de educación</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Cuenta activa</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()){ ?>
                        <tr data-id="<?php echo (int)$fila["id_estudiante"]; ?>" data-nombre="al estudiante <?php echo htmlspecialchars($fila["nombre"] . " " . $fila["apellido"]); ?>">
                            <td><input type="text" data-campo="nombre" required minlength="2" maxlength="100" data-error="Nombre y apellido: de 2 a 100 caracteres." value="<?php echo htmlspecialchars($fila["nombre"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar nombre">✏️</button></td>
                            <td><input type="text" data-campo="apellido" required minlength="2" maxlength="100" data-error="Nombre y apellido: de 2 a 100 caracteres." value="<?php echo htmlspecialchars($fila["apellido"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar apellido">✏️</button></td>
                            <td><select data-campo="centro_educativo" aria-label="Centro educativo"><?php opciones($centros, $fila["centro_educativo"] ?? ""); ?></select></td>
                            <td><select data-campo="curso_educacion" aria-label="Curso de educación"><?php opciones($cursos, $fila["curso_educacion"] ?? ""); ?></select></td>
                            <td><input type="email" data-campo="correo" required maxlength="150" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" data-error="Escribe un correo válido (ej. nombre@correo.com)." value="<?php echo htmlspecialchars($fila["correo"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar correo">✏️</button></td>
                            <td><input type="text" data-campo="telefono" required pattern="[2678][0-9]{3}-[0-9]{4}" maxlength="9" data-error="El teléfono debe tener el formato 7000-0000 y empezar con 2, 6, 7 u 8." value="<?php echo htmlspecialchars($fila["telefono"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar teléfono">✏️</button></td>
                            <!-- Tarea 13: el administrador puede reactivar una cuenta desactivada -->
                            <td><select data-campo="activo" aria-label="Cuenta activa"><option value="1"<?php echo (int)$fila["activo"] === 1 ? " selected" : ""; ?>>Sí</option><option value="0"<?php echo (int)$fila["activo"] === 1 ? "" : " selected"; ?>>No (desactivada)</option></select></td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Borrar" aria-label="Borrar estudiante">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="7">No hay estudiantes registrados</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Tarea 12: formulario para agregar (lo abre el botón "+ Nuevo"); el JS lo valida antes de enviar -->
    <dialog id="dlgNuevo">
        <form id="formNuevo" method="dialog">
            <h3>Nuevo estudiante</h3>
            <input name="nombre" placeholder="Nombre" required minlength="2" maxlength="100" data-error="Nombre y apellido: de 2 a 100 caracteres.">
            <input name="apellido" placeholder="Apellido" required minlength="2" maxlength="100" data-error="Nombre y apellido: de 2 a 100 caracteres.">
            <select name="centro_educativo" required data-error="Elige un centro educativo.">
                <option value="" selected disabled>Centro educativo</option>
                <?php opciones($centros, ""); ?>
            </select>
            <select name="curso_educacion" required data-error="Elige un curso de educación.">
                <option value="" selected disabled>Curso de educación</option>
                <?php opciones($cursos, ""); ?>
            </select>
            <input name="correo" type="email" placeholder="Correo" required maxlength="150" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" data-error="Escribe un correo válido (ej. nombre@correo.com).">
            <input name="telefono" placeholder="Teléfono (ej. 7000-0000)" required pattern="[2678][0-9]{3}-[0-9]{4}" maxlength="9" data-error="El teléfono debe tener el formato 7000-0000 y empezar con 2, 6, 7 u 8.">
            <input name="contrasena" type="password" placeholder="Contraseña (mín. 6)" minlength="6" required autocomplete="new-password" data-error="La contraseña debe tener al menos 6 caracteres.">
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

    <!-- Tarea 12: JavaScript que valida y envía los cambios al PHP de esta misma página -->
    <script src="scripts/admin_crud.js"></script>
</body>
</html>
