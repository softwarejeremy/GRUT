<?php
// Tarea 11 (Fabiola): mostrar paradas, recorridos y horarios. Tarea 12 (Bryan): agregar, editar y borrar.
// Tablas: paradas; ruta_parada (qué parada va en qué ruta y en qué orden); horarios (días y horas).
require_once("php/admin_comun.php");

$dias = ["lunes" => "Lunes", "martes" => "Martes", "miercoles" => "Miércoles", "jueves" => "Jueves",
         "viernes" => "Viernes", "sabado" => "Sábado", "domingo" => "Domingo"];

// ---------- Tarea 12 (Bryan): agregar, editar y borrar ----------
// scripts/admin_crud.js envía cada cambio por POST (fetch) y aquí se responde en JSON.
// Todas las consultas son preparadas (?) y el nombre de la columna sale de una lista fija.
if($_SERVER["REQUEST_METHOD"] == "POST"){
    comprobar_csrf();

    function coordenada_valida($valor, $limite){
        return $valor === "" || (is_numeric($valor) && abs((float)$valor) <= $limite);
    }
    function hora_valida($valor){
        return preg_match("/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/", $valor) === 1;
    }

    $accion = $_POST["accion"] ?? "";
    $en_transaccion = false;
    try {
        // ---------- Paradas ----------
        if($accion == "crear"){
            $nombre = trim($_POST["nombre"] ?? "");
            $direccion = trim($_POST["direccion"] ?? "");
            $latitud = trim($_POST["latitud"] ?? "");
            $longitud = trim($_POST["longitud"] ?? "");
            if(!texto_valido($nombre, 2, 100)){ responder(false, "El nombre debe tener de 2 a 100 caracteres.", 422); }
            if(mb_strlen($direccion) > 255){ responder(false, "La dirección no puede pasar de 255 caracteres.", 422); }
            if(!coordenada_valida($latitud, 90) || !coordenada_valida($longitud, 180)){ responder(false, "Latitud o longitud no válidas.", 422); }
            $latitud = $latitud === "" ? null : $latitud;
            $longitud = $longitud === "" ? null : $longitud;
            $stmt = $conexion->prepare("INSERT INTO paradas (nombre, direccion, latitud, longitud) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $direccion, $latitud, $longitud);
            $stmt->execute();
            $stmt->close();
            responder(true, "Parada creada.");
        }

        if($accion == "actualizar"){
            $id = (int)($_POST["id"] ?? 0);   // id_parada
            $campo = $_POST["campo"] ?? "";
            $valor = trim($_POST["valor"] ?? "");
            if($id <= 0){ responder(false, "ID no válido.", 422); }
            if($campo == "nombre" && !texto_valido($valor, 2, 100)){ responder(false, "El nombre debe tener de 2 a 100 caracteres.", 422); }
            if($campo == "direccion" && mb_strlen($valor) > 255){ responder(false, "La dirección no puede pasar de 255 caracteres.", 422); }
            if($campo == "latitud" && !coordenada_valida($valor, 90)){ responder(false, "Latitud no válida (entre -90 y 90).", 422); }
            if($campo == "longitud" && !coordenada_valida($valor, 180)){ responder(false, "Longitud no válida (entre -180 y 180).", 422); }

            // El nombre de la columna sale de esta lista, nunca del formulario
            $columnas = ["nombre" => "nombre", "direccion" => "direccion", "latitud" => "latitud", "longitud" => "longitud"];
            if(!isset($columnas[$campo])){ responder(false, "Campo desconocido.", 422); }
            $valor = ($valor === "" && $campo != "nombre") ? null : $valor;
            $stmt = $conexion->prepare("UPDATE paradas SET " . $columnas[$campo] . " = ? WHERE id_parada = ?");
            $stmt->bind_param("si", $valor, $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Cambio guardado.");
        }

        if($accion == "eliminar"){
            $id = (int)($_POST["id"] ?? 0);
            // Si la parada está en alguna ruta, MySQL no deja borrarla (error 1451)
            $stmt = $conexion->prepare("DELETE FROM paradas WHERE id_parada = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Parada eliminada.");
        }

        // ---------- Recorridos: parada dentro de una ruta, con su horario ----------
        if($accion == "crear_recorrido"){
            $id_ruta = (int)($_POST["id_ruta"] ?? 0);
            $id_parada = (int)($_POST["id_parada"] ?? 0);
            $orden = trim($_POST["orden"] ?? "");
            $dia = $_POST["dia_semana"] ?? "";
            $salida = $_POST["hora_salida"] ?? "";
            $llegada = $_POST["hora_llegada"] ?? "";
            if($id_ruta <= 0 || $id_parada <= 0){ responder(false, "Elige la ruta y la parada.", 422); }
            if(!ctype_digit($orden) || (int)$orden < 1){ responder(false, "El orden debe ser un número desde 1.", 422); }
            if(!isset($dias[$dia])){ responder(false, "Elige un día.", 422); }
            if(!hora_valida($salida) || !hora_valida($llegada)){ responder(false, "Escribe la hora de salida y la de llegada.", 422); }
            $orden = (int)$orden;

            $conexion->begin_transaction();
            $en_transaccion = true;

            // Si la parada ya está en la ruta se reutiliza (y se actualiza su orden)
            $stmt = $conexion->prepare("SELECT id_ruta_parada FROM ruta_parada WHERE id_ruta = ? AND id_parada = ?");
            $stmt->bind_param("ii", $id_ruta, $id_parada);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if($fila){
                $id_ruta_parada = (int)$fila["id_ruta_parada"];
                $stmt = $conexion->prepare("UPDATE ruta_parada SET orden = ? WHERE id_ruta_parada = ?");
                $stmt->bind_param("ii", $orden, $id_ruta_parada);
            } else {
                $stmt = $conexion->prepare("INSERT INTO ruta_parada (id_ruta, id_parada, orden) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $id_ruta, $id_parada, $orden);
            }
            $stmt->execute();
            if(!$fila){
                $id_ruta_parada = $conexion->insert_id;
            }
            $stmt->close();

            $stmt = $conexion->prepare("INSERT INTO horarios (id_ruta_parada, dia_semana, hora_salida, hora_llegada) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $id_ruta_parada, $dia, $salida, $llegada);
            $stmt->execute();
            $stmt->close();

            $conexion->commit();
            responder(true, "Parada asignada a la ruta.");
        }

        if($accion == "actualizar_recorrido"){
            $id = (int)($_POST["id"] ?? 0);   // id_ruta_parada
            $valor = trim($_POST["valor"] ?? "");
            if(($_POST["campo"] ?? "") != "orden"){ responder(false, "Campo desconocido.", 422); }
            if(!ctype_digit($valor) || (int)$valor < 1){ responder(false, "El orden debe ser un número desde 1.", 422); }
            $orden = (int)$valor;
            $stmt = $conexion->prepare("UPDATE ruta_parada SET orden = ? WHERE id_ruta_parada = ?");
            $stmt->bind_param("ii", $orden, $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Cambio guardado.");
        }

        if($accion == "eliminar_recorrido"){
            $id = (int)($_POST["id"] ?? 0);   // id_ruta_parada
            $conexion->begin_transaction();
            $en_transaccion = true;
            $stmt = $conexion->prepare("DELETE FROM horarios WHERE id_ruta_parada = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $stmt = $conexion->prepare("DELETE FROM ruta_parada WHERE id_ruta_parada = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $conexion->commit();
            responder(true, "Parada quitada de la ruta.");
        }

        responder(false, "Acción desconocida.", 400);
    } catch(Throwable $e){
        if($en_transaccion){
            $conexion->rollback();
        }
        if($e instanceof mysqli_sql_exception){
            responder_error_mysql($e, "Esta parada está asignada a una ruta. Quítala de la ruta antes de borrarla.");
        }
        error_log("Error en paradas_administrador.php: " . $e->getMessage());
        responder(false, "No se pudo completar la acción.", 500);
    }
}

// ---------- Tarea 11 (Fabiola): consultas para mostrar los datos en la página ----------
$stmt = $conexion->prepare("SELECT id_parada, nombre, direccion, latitud, longitud FROM paradas ORDER BY nombre");
$stmt->execute();
$paradas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conexion->prepare("SELECT id_ruta, nombre FROM rutas ORDER BY nombre");
$stmt->execute();
$rutas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recorridos: una fila por parada dentro de una ruta, con todos sus horarios
$stmt = $conexion->prepare("SELECT rp.id_ruta_parada, r.nombre AS ruta, p.nombre AS parada, rp.orden, h.dia_semana, h.hora_salida, h.hora_llegada FROM ruta_parada rp INNER JOIN rutas r ON r.id_ruta = rp.id_ruta INNER JOIN paradas p ON p.id_parada = rp.id_parada LEFT JOIN horarios h ON h.id_ruta_parada = rp.id_ruta_parada ORDER BY r.nombre, rp.orden, FIELD(h.dia_semana, 'lunes','martes','miercoles','jueves','viernes','sabado','domingo'), h.hora_salida");
$stmt->execute();
$resultado = $stmt->get_result();
$recorridos = [];
while($fila = $resultado->fetch_assoc()){
    $id = $fila["id_ruta_parada"];
    if(!isset($recorridos[$id])){
        $recorridos[$id] = ["ruta" => $fila["ruta"], "parada" => $fila["parada"], "orden" => $fila["orden"], "horarios" => []];
    }
    if($fila["dia_semana"] !== null){
        $recorridos[$id]["horarios"][] = $dias[$fila["dia_semana"]] . ": sale " . substr($fila["hora_salida"], 0, 5) . ", llega " . substr($fila["hora_llegada"], 0, 5);
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paradas | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-paradas" data-csrf="<?php echo htmlspecialchars($csrf); ?>">

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
            <h2><span aria-hidden="true">🚏</span> Paradas</h2>

            <!-- Tarea 12: botones para agregar y para activar el modo borrar -->
            <div class="panel-actions">
                <button type="button" class="btn-add" id="btnNuevo">+ Nueva parada</button>
                <button type="button" class="btn-remove" id="btnBorrar">- Borrar parada</button>
            </div>

            <!-- Tarea 11: tabla con los datos de la base (un while = una fila por registro).
                 Tarea 12: el lápiz edita el campo, las listas se guardan al cambiar y 🗑️ borra la fila. -->
            <div class="table-container" role="region" aria-label="Tabla de paradas" tabindex="0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Parada</th>
                            <th>Dirección</th>
                            <th>Latitud</th>
                            <th>Longitud</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($paradas as $fila){ ?>
                        <tr data-id="<?php echo (int)$fila["id_parada"]; ?>" data-nombre="la parada <?php echo htmlspecialchars($fila["nombre"]); ?>">
                            <td><input type="text" data-campo="nombre" required minlength="2" maxlength="100" data-error="El nombre debe tener de 2 a 100 caracteres." value="<?php echo htmlspecialchars($fila["nombre"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar nombre">✏️</button></td>
                            <td><input type="text" data-campo="direccion" maxlength="255" data-error="La dirección no puede pasar de 255 caracteres." value="<?php echo htmlspecialchars($fila["direccion"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar dirección">✏️</button></td>
                            <td><input type="number" step="any" min="-90" max="90" data-campo="latitud" data-error="La latitud debe ser un número entre -90 y 90." value="<?php echo htmlspecialchars($fila["latitud"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar latitud">✏️</button></td>
                            <td><input type="number" step="any" min="-180" max="180" data-campo="longitud" data-error="La longitud debe ser un número entre -180 y 180." value="<?php echo htmlspecialchars($fila["longitud"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar longitud">✏️</button></td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Borrar" aria-label="Borrar parada">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if(count($paradas) === 0){ ?>
                        <tr><td colspan="4">No hay paradas registradas</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Tareas 11 y 12: recorridos (ruta_parada) y horarios. Esto es lo que ven el estudiante (ruta.php) y el conductor. -->
        <section class="panel">
            <h2><span aria-hidden="true">🕐</span> Recorridos y horarios</h2>

            <div class="panel-actions">
                <button type="button" class="btn-add" data-abre="dlgRecorrido">+ Asignar parada a una ruta</button>
            </div>

            <div class="table-container" role="region" aria-label="Tabla de recorridos" tabindex="0">
                <table class="data-table" data-actualizar="actualizar_recorrido" data-eliminar="eliminar_recorrido">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Parada</th>
                            <th>Orden</th>
                            <th>Horarios</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recorridos as $id => $r){ ?>
                        <tr data-id="<?php echo (int)$id; ?>" data-nombre="la parada <?php echo htmlspecialchars($r["parada"]); ?> de la ruta <?php echo htmlspecialchars($r["ruta"]); ?> (con sus horarios)">
                            <td class="texto-secundario"><?php echo htmlspecialchars($r["ruta"]); ?></td>
                            <td class="texto-secundario"><?php echo htmlspecialchars($r["parada"]); ?></td>
                            <td><input type="number" min="1" step="1" required data-campo="orden" data-error="El orden debe ser un número entero desde 1." value="<?php echo (int)$r["orden"]; ?>" readonly><button type="button" class="edit-btn" aria-label="Editar orden">✏️</button></td>
                            <td class="texto-secundario"><?php echo $r["horarios"] ? htmlspecialchars(implode(" · ", $r["horarios"])) : "Sin horario"; ?></td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Quitar de la ruta" aria-label="Quitar parada de la ruta">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if(count($recorridos) === 0){ ?>
                        <tr><td colspan="4">No hay paradas asignadas a rutas</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Tarea 12: formulario para agregar (lo abre el botón "+ Nuevo"); el JS lo valida antes de enviar -->
    <dialog id="dlgNuevo">
        <form id="formNuevo" method="dialog">
            <h3>Nueva parada</h3>
            <input name="nombre" placeholder="Nombre de la parada" required minlength="2" maxlength="100" data-error="El nombre debe tener de 2 a 100 caracteres.">
            <input name="direccion" placeholder="Dirección" maxlength="255" data-error="La dirección no puede pasar de 255 caracteres.">
            <input name="latitud" type="number" step="any" min="-90" max="90" placeholder="Latitud (opcional, ej. 13.6929)" data-error="La latitud debe ser un número entre -90 y 90.">
            <input name="longitud" type="number" step="any" min="-180" max="180" placeholder="Longitud (opcional, ej. -89.2182)" data-error="La longitud debe ser un número entre -180 y 180.">
            <menu>
                <button type="button" id="cancelarNuevo">Cancelar</button>
                <button type="submit">Guardar</button>
            </menu>
        </form>
    </dialog>

    <dialog id="dlgRecorrido">
        <form class="form-crud" data-accion="crear_recorrido" method="dialog">
            <h3>Asignar parada a una ruta</h3>
            <select name="id_ruta" required data-error="Elige la ruta.">
                <option value="" selected disabled>Ruta</option>
                <?php foreach($rutas as $r){ ?>
                <option value="<?php echo (int)$r["id_ruta"]; ?>"><?php echo htmlspecialchars($r["nombre"]); ?></option>
                <?php } ?>
            </select>
            <select name="id_parada" required data-error="Elige la parada.">
                <option value="" selected disabled>Parada</option>
                <?php foreach($paradas as $p){ ?>
                <option value="<?php echo (int)$p["id_parada"]; ?>"><?php echo htmlspecialchars($p["nombre"]); ?></option>
                <?php } ?>
            </select>
            <input name="orden" type="number" min="1" step="1" placeholder="Orden en la ruta (1 = primera)" required data-error="El orden debe ser un número entero desde 1.">
            <select name="dia_semana" required data-error="Elige un día.">
                <option value="" selected disabled>Día</option>
                <?php foreach($dias as $valor => $texto){ ?>
                <option value="<?php echo $valor; ?>"><?php echo $texto; ?></option>
                <?php } ?>
            </select>
            <label for="rec-salida">Hora de salida</label>
            <input name="hora_salida" id="rec-salida" type="time" required data-error="Escribe la hora de salida.">
            <label for="rec-llegada">Hora de llegada</label>
            <input name="hora_llegada" id="rec-llegada" type="time" required data-error="Escribe la hora de llegada.">
            <menu>
                <button type="button" data-cierra>Cancelar</button>
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
