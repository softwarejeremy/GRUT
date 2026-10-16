<?php
// Tarea 11 (Fabiola): mostrar las rutas. Tarea 12 (Bryan): agregar, editar y borrar.
// El conductor está en rutas.id_conductor; el bus, en la tabla ruta_bus.
require_once("php/admin_comun.php");

// ---------- Tarea 12 (Bryan): agregar, editar y borrar ----------
// scripts/admin_crud.js envía cada cambio por POST (fetch) y aquí se responde en JSON.
// Todas las consultas son preparadas (?) y el nombre de la columna sale de una lista fija.
if($_SERVER["REQUEST_METHOD"] == "POST"){
    comprobar_csrf();

    // "" = sin asignar; si no, debe existir en su tabla
    function id_opcional($conexion, $valor, $tabla){
        if($valor === ""){
            return null;
        }
        $consultas = [
            "conductor" => "SELECT 1 FROM conductor WHERE id_conductor = ?",
            "buses" => "SELECT 1 FROM buses WHERE id_bus = ?"
        ];
        $id = (int)$valor;
        $stmt = $conexion->prepare($consultas[$tabla]);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_row();
        $stmt->close();
        if(!$existe){
            responder(false, "La opción elegida ya no existe. Recarga la página.", 422);
        }
        return $id;
    }

    function asignar_bus($conexion, $id_ruta, $id_bus){
        $stmt = $conexion->prepare("DELETE FROM ruta_bus WHERE id_ruta = ?");
        $stmt->bind_param("i", $id_ruta);
        $stmt->execute();
        $stmt->close();
        if($id_bus !== null){
            $stmt = $conexion->prepare("INSERT INTO ruta_bus (id_ruta, id_bus) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_ruta, $id_bus);
            $stmt->execute();
            $stmt->close();
        }
    }

    $accion = $_POST["accion"] ?? "";
    $en_transaccion = false;
    try {
        if($accion == "crear"){
            $nombre = trim($_POST["nombre"] ?? "");
            $descripcion = trim($_POST["descripcion"] ?? "");
            if(!texto_valido($nombre, 2, 100)){ responder(false, "El nombre debe tener de 2 a 100 caracteres.", 422); }
            if(mb_strlen($descripcion) > 255){ responder(false, "La descripción no puede pasar de 255 caracteres.", 422); }
            $id_conductor = id_opcional($conexion, $_POST["id_conductor"] ?? "", "conductor");
            $id_bus = id_opcional($conexion, $_POST["id_bus"] ?? "", "buses");

            $conexion->begin_transaction();
            $en_transaccion = true;
            $stmt = $conexion->prepare("INSERT INTO rutas (nombre, descripcion, id_conductor) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nombre, $descripcion, $id_conductor);
            $stmt->execute();
            $id_ruta = $conexion->insert_id;
            $stmt->close();
            asignar_bus($conexion, $id_ruta, $id_bus);
            $conexion->commit();
            responder(true, "Ruta creada.");
        }

        if($accion == "actualizar"){
            $id = (int)($_POST["id"] ?? 0);   // id_ruta
            $campo = $_POST["campo"] ?? "";
            $valor = trim($_POST["valor"] ?? "");
            if($id <= 0){ responder(false, "ID no válido.", 422); }

            if($campo == "nombre"){
                if(!texto_valido($valor, 2, 100)){ responder(false, "El nombre debe tener de 2 a 100 caracteres.", 422); }
                $stmt = $conexion->prepare("UPDATE rutas SET nombre = ? WHERE id_ruta = ?");
                $stmt->bind_param("si", $valor, $id);
            } elseif($campo == "descripcion"){
                if(mb_strlen($valor) > 255){ responder(false, "La descripción no puede pasar de 255 caracteres.", 422); }
                $stmt = $conexion->prepare("UPDATE rutas SET descripcion = ? WHERE id_ruta = ?");
                $stmt->bind_param("si", $valor, $id);
            } elseif($campo == "id_conductor"){
                $id_conductor = id_opcional($conexion, $valor, "conductor");
                $stmt = $conexion->prepare("UPDATE rutas SET id_conductor = ? WHERE id_ruta = ?");
                $stmt->bind_param("ii", $id_conductor, $id);
            } elseif($campo == "id_bus"){
                $id_bus = id_opcional($conexion, $valor, "buses");
                $conexion->begin_transaction();
                $en_transaccion = true;
                asignar_bus($conexion, $id, $id_bus);
                $conexion->commit();
                responder(true, "Cambio guardado.");
            } else {
                responder(false, "Campo desconocido.", 422);
            }
            $stmt->execute();
            $stmt->close();
            responder(true, "Cambio guardado.");
        }

        if($accion == "eliminar"){
            $id = (int)($_POST["id"] ?? 0);
            $conexion->begin_transaction();
            $en_transaccion = true;
            $stmt = $conexion->prepare("DELETE FROM ruta_bus WHERE id_ruta = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            // Si tiene estudiantes o paradas, MySQL no deja borrarla (error 1451)
            $stmt = $conexion->prepare("DELETE FROM rutas WHERE id_ruta = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $conexion->commit();
            responder(true, "Ruta eliminada.");
        }

        // ---------- Inscripciones (complemento de la tarea 5): qué estudiante viaja en qué ruta (tabla estudiante_ruta) ----------
        if($accion == "inscribir"){
            $id_estudiante = (int)($_POST["id_estudiante"] ?? 0);
            $id_ruta = (int)($_POST["id_ruta"] ?? 0);
            if($id_estudiante <= 0 || $id_ruta <= 0){ responder(false, "Elige el estudiante y la ruta.", 422); }
            // Si el estudiante ya está en esa ruta, MySQL lo rechaza (error 1062, clave única)
            $stmt = $conexion->prepare("INSERT INTO estudiante_ruta (id_estudiante, id_ruta) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_estudiante, $id_ruta);
            $stmt->execute();
            $stmt->close();
            responder(true, "Estudiante inscrito en la ruta.");
        }

        if($accion == "desinscribir"){
            $id = (int)($_POST["id"] ?? 0);   // id_estudiante_ruta
            $stmt = $conexion->prepare("DELETE FROM estudiante_ruta WHERE id_estudiante_ruta = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Estudiante quitado de la ruta.");
        }

        responder(false, "Acción desconocida.", 400);
    } catch(Throwable $e){
        if($en_transaccion){
            $conexion->rollback();
        }
        if($e instanceof mysqli_sql_exception && $e->getCode() == 1062 && $accion == "inscribir"){
            responder(false, "Ese estudiante ya está inscrito en esa ruta.", 409);
        }
        if($e instanceof mysqli_sql_exception){
            responder_error_mysql($e, "Esta ruta tiene estudiantes o paradas asignadas. Quítalos antes de borrarla.");
        }
        error_log("Error en rutas_administrador.php: " . $e->getMessage());
        responder(false, "No se pudo completar la acción.", 500);
    }
}

// ---------- Tarea 11 (Fabiola): consultas para mostrar los datos en la página ----------
$stmt = $conexion->prepare("SELECT r.id_ruta, r.nombre, r.descripcion, r.id_conductor, MIN(rb.id_bus) AS id_bus FROM rutas r LEFT JOIN ruta_bus rb ON rb.id_ruta = r.id_ruta GROUP BY r.id_ruta, r.nombre, r.descripcion, r.id_conductor ORDER BY r.id_ruta");
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

$stmt = $conexion->prepare("SELECT c.id_conductor, u.nombre, u.apellido FROM conductor c INNER JOIN usuario u ON u.id_usuario = c.id_usuario ORDER BY u.nombre, u.apellido");
$stmt->execute();
$conductores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conexion->prepare("SELECT id_bus, placa FROM buses ORDER BY placa");
$stmt->execute();
$buses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Inscripciones (complemento de la tarea 5): estudiantes inscritos en cada ruta (estudiante_ruta -> estudiante -> usuario)
$stmt = $conexion->prepare("SELECT er.id_estudiante_ruta, r.nombre AS ruta, u.nombre, u.apellido, e.centro_educativo FROM estudiante_ruta er INNER JOIN rutas r ON r.id_ruta = er.id_ruta INNER JOIN estudiante e ON e.id_estudiante = er.id_estudiante INNER JOIN usuario u ON u.id_usuario = e.id_usuario ORDER BY r.nombre, u.nombre, u.apellido");
$stmt->execute();
$inscripciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conexion->prepare("SELECT e.id_estudiante, u.nombre, u.apellido FROM estudiante e INNER JOIN usuario u ON u.id_usuario = e.id_usuario ORDER BY u.nombre, u.apellido");
$stmt->execute();
$estudiantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conexion->prepare("SELECT id_ruta, nombre FROM rutas ORDER BY nombre");
$stmt->execute();
$lista_rutas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function opciones_conductor($conductores, $actual){
    echo "<option value=\"\">Sin conductor</option>";
    foreach($conductores as $c){
        $marcado = ((string)$c["id_conductor"] === (string)$actual) ? " selected" : "";
        echo "<option value=\"" . (int)$c["id_conductor"] . "\"" . $marcado . ">" . htmlspecialchars($c["nombre"] . " " . $c["apellido"]) . "</option>";
    }
}

function opciones_bus($buses, $actual){
    echo "<option value=\"\">Sin bus</option>";
    foreach($buses as $b){
        $marcado = ((string)$b["id_bus"] === (string)$actual) ? " selected" : "";
        echo "<option value=\"" . (int)$b["id_bus"] . "\"" . $marcado . ">" . htmlspecialchars($b["placa"]) . "</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutas | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-rutas" data-csrf="<?php echo htmlspecialchars($csrf); ?>">

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
            <h2><span aria-hidden="true">🚌</span> Rutas</h2>

            <!-- Tarea 12: botones para agregar y para activar el modo borrar -->
            <div class="panel-actions">
                <button type="button" class="btn-add" id="btnNuevo">+ Nueva ruta</button>
                <button type="button" class="btn-remove" id="btnBorrar">- Borrar ruta</button>
            </div>

            <!-- Tarea 11: tabla con los datos de la base (un while = una fila por registro).
                 Tarea 12: el lápiz edita el campo, las listas se guardan al cambiar y 🗑️ borra la fila. -->
            <div class="table-container" role="region" aria-label="Tabla de rutas" tabindex="0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Descripción</th>
                            <th>Conductor</th>
                            <th>Bus</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()){ ?>
                        <tr data-id="<?php echo (int)$fila["id_ruta"]; ?>" data-nombre="la ruta <?php echo htmlspecialchars($fila["nombre"]); ?>">
                            <td><input type="text" data-campo="nombre" required minlength="2" maxlength="100" data-error="El nombre debe tener de 2 a 100 caracteres." value="<?php echo htmlspecialchars($fila["nombre"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar nombre">✏️</button></td>
                            <td><input type="text" data-campo="descripcion" maxlength="255" data-error="La descripción no puede pasar de 255 caracteres." value="<?php echo htmlspecialchars($fila["descripcion"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar descripción">✏️</button></td>
                            <td><select data-campo="id_conductor" aria-label="Conductor"><?php opciones_conductor($conductores, $fila["id_conductor"]); ?></select></td>
                            <td><select data-campo="id_bus" aria-label="Bus"><?php opciones_bus($buses, $fila["id_bus"]); ?></select></td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Borrar" aria-label="Borrar ruta">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="4">No hay rutas registradas</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Inscripciones: el administrador asigna las rutas a cada estudiante.
             Esto es lo que ve el estudiante en su panel (tarea 5). -->
        <section class="panel">
            <h2><span aria-hidden="true">🎒</span> Estudiantes inscritos en cada ruta</h2>

            <div class="panel-actions">
                <button type="button" class="btn-add" data-abre="dlgInscripcion">+ Inscribir estudiante</button>
            </div>

            <div class="table-container" role="region" aria-label="Tabla de estudiantes inscritos" tabindex="0">
                <table class="data-table" data-eliminar="desinscribir">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Estudiante</th>
                            <th>Centro educativo</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inscripciones as $fila){ ?>
                        <tr data-id="<?php echo (int)$fila["id_estudiante_ruta"]; ?>" data-nombre="a <?php echo htmlspecialchars($fila["nombre"] . " " . $fila["apellido"]); ?> de la ruta <?php echo htmlspecialchars($fila["ruta"]); ?>">
                            <td class="texto-secundario"><?php echo htmlspecialchars($fila["ruta"]); ?></td>
                            <td class="texto-secundario"><?php echo htmlspecialchars($fila["nombre"] . " " . $fila["apellido"]); ?></td>
                            <td class="texto-secundario"><?php echo htmlspecialchars($fila["centro_educativo"] ?? ""); ?></td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Quitar de la ruta" aria-label="Quitar estudiante de la ruta">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if(count($inscripciones) === 0){ ?>
                        <tr><td colspan="3">No hay estudiantes inscritos en rutas</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <dialog id="dlgInscripcion">
        <form class="form-crud" data-accion="inscribir" method="dialog">
            <h3>Inscribir estudiante en una ruta</h3>
            <label for="ins-estudiante">Estudiante</label>
            <select name="id_estudiante" id="ins-estudiante" required data-error="Elige un estudiante.">
                <option value="" selected disabled>Elige un estudiante</option>
                <?php foreach($estudiantes as $est){ ?>
                <option value="<?php echo (int)$est["id_estudiante"]; ?>"><?php echo htmlspecialchars($est["nombre"] . " " . $est["apellido"]); ?></option>
                <?php } ?>
            </select>
            <label for="ins-ruta">Ruta</label>
            <select name="id_ruta" id="ins-ruta" required data-error="Elige una ruta.">
                <option value="" selected disabled>Elige una ruta</option>
                <?php foreach($lista_rutas as $r){ ?>
                <option value="<?php echo (int)$r["id_ruta"]; ?>"><?php echo htmlspecialchars($r["nombre"]); ?></option>
                <?php } ?>
            </select>
            <menu>
                <button type="button" data-cierra>Cancelar</button>
                <button type="submit">Guardar</button>
            </menu>
        </form>
    </dialog>

    <!-- Tarea 12: formulario para agregar (lo abre el botón "+ Nuevo"); el JS lo valida antes de enviar -->
    <dialog id="dlgNuevo">
        <form id="formNuevo" method="dialog">
            <h3>Nueva ruta</h3>
            <input name="nombre" placeholder="Nombre (ej. Ruta 15)" required minlength="2" maxlength="100" data-error="El nombre debe tener de 2 a 100 caracteres.">
            <input name="descripcion" placeholder="Descripción del recorrido" maxlength="255">
            <label for="nuevo-conductor">Conductor</label>
            <select name="id_conductor" id="nuevo-conductor"><?php opciones_conductor($conductores, ""); ?></select>
            <label for="nuevo-bus">Bus</label>
            <select name="id_bus" id="nuevo-bus"><?php opciones_bus($buses, ""); ?></select>
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
