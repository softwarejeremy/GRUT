<?php
// Tarea 11 (Fabiola): mostrar los buses. Tarea 12 (Bryan): agregar, editar y borrar.
require_once("php/admin_comun.php");

$estados = ["activo", "mantenimiento", "inactivo"];

// ---------- Tarea 12 (Bryan): agregar, editar y borrar ----------
// scripts/admin_crud.js envía cada cambio por POST (fetch) y aquí se responde en JSON.
// Todas las consultas son preparadas (?) y el nombre de la columna sale de una lista fija.
if($_SERVER["REQUEST_METHOD"] == "POST"){
    comprobar_csrf();

    function validar_bus($campo, $valor, $estados){
        if($campo == "placa"){
            return texto_valido($valor, 3, 20) ? "" : "La placa debe tener de 3 a 20 caracteres.";
        }
        if($campo == "capacidad"){
            return (ctype_digit($valor) && (int)$valor >= 1 && (int)$valor <= 100) ? "" : "La capacidad debe ser un número entre 1 y 100.";
        }
        if($campo == "estado"){
            return in_array($valor, $estados, true) ? "" : "Estado no válido.";
        }
        return "Campo desconocido.";
    }

    $accion = $_POST["accion"] ?? "";
    try {
        if($accion == "crear"){
            $placa = trim($_POST["placa"] ?? "");
            $capacidad = trim($_POST["capacidad"] ?? "");
            $estado = $_POST["estado"] ?? "";
            foreach(["placa" => $placa, "capacidad" => $capacidad, "estado" => $estado] as $campo => $valor){
                $error = validar_bus($campo, $valor, $estados);
                if($error != ""){ responder(false, $error, 422); }
            }
            $stmt = $conexion->prepare("INSERT INTO buses (placa, capacidad, estado) VALUES (?, ?, ?)");
            $capacidad = (int)$capacidad;
            $stmt->bind_param("sis", $placa, $capacidad, $estado);
            $stmt->execute();
            $stmt->close();
            responder(true, "Bus creado.");
        }

        if($accion == "actualizar"){
            $id = (int)($_POST["id"] ?? 0);
            $campo = $_POST["campo"] ?? "";
            $valor = trim($_POST["valor"] ?? "");
            $error = validar_bus($campo, $valor, $estados);
            if($id <= 0){ responder(false, "ID no válido.", 422); }
            if($error != ""){ responder(false, $error, 422); }

            // El nombre de la columna sale de esta lista, nunca del formulario
            $columnas = ["placa" => "placa", "capacidad" => "capacidad", "estado" => "estado"];
            $stmt = $conexion->prepare("UPDATE buses SET " . $columnas[$campo] . " = ? WHERE id_bus = ?");
            $stmt->bind_param("si", $valor, $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Cambio guardado.");
        }

        if($accion == "eliminar"){
            $id = (int)($_POST["id"] ?? 0);
            $stmt = $conexion->prepare("DELETE FROM buses WHERE id_bus = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Bus eliminado.");
        }

        responder(false, "Acción desconocida.", 400);
    } catch(mysqli_sql_exception $e){
        responder_error_mysql($e, "Este bus está asignado a una ruta. Quítalo de la ruta antes de borrarlo.");
    }
}

// ---------- Tarea 11 (Fabiola): consultas para mostrar los datos en la página ----------
$stmt = $conexion->prepare("SELECT id_bus, placa, capacidad, estado FROM buses ORDER BY id_bus");
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buses | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-buses" data-csrf="<?php echo htmlspecialchars($csrf); ?>">

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
            <h2><span aria-hidden="true">🚍</span> Buses</h2>

            <!-- Tarea 12: botones para agregar y para activar el modo borrar -->
            <div class="panel-actions">
                <button type="button" class="btn-add" id="btnNuevo">+ Nuevo bus</button>
                <button type="button" class="btn-remove" id="btnBorrar">- Borrar bus</button>
            </div>

            <!-- Tarea 11: tabla con los datos de la base (un while = una fila por registro).
                 Tarea 12: el lápiz edita el campo, las listas se guardan al cambiar y 🗑️ borra la fila. -->
            <div class="table-container" role="region" aria-label="Tabla de buses" tabindex="0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Capacidad</th>
                            <th>Estado</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()){ ?>
                        <tr data-id="<?php echo (int)$fila["id_bus"]; ?>" data-nombre="el bus <?php echo htmlspecialchars($fila["placa"]); ?>">
                            <td><input type="text" data-campo="placa" required minlength="3" maxlength="20" data-error="La placa debe tener de 3 a 20 caracteres." value="<?php echo htmlspecialchars($fila["placa"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar placa">✏️</button></td>
                            <td><input type="number" data-campo="capacidad" required min="1" max="100" step="1" data-error="La capacidad debe ser un número entero entre 1 y 100." value="<?php echo htmlspecialchars($fila["capacidad"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar capacidad">✏️</button></td>
                            <td>
                                <select data-campo="estado" aria-label="Estado del bus">
                                    <?php foreach($estados as $opcion){ ?>
                                    <option value="<?php echo $opcion; ?>" <?php if(strtolower($fila["estado"] ?? "") == $opcion){ echo "selected"; } ?>><?php echo ucfirst($opcion); ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Borrar" aria-label="Borrar bus">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="3">No hay buses registrados</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Tarea 12: formulario para agregar (lo abre el botón "+ Nuevo"); el JS lo valida antes de enviar -->
    <dialog id="dlgNuevo">
        <form id="formNuevo" method="dialog">
            <h3>Nuevo bus</h3>
            <input name="placa" placeholder="Placa (ej. AB-106-789)" required minlength="3" maxlength="20" data-error="La placa debe tener de 3 a 20 caracteres.">
            <input name="capacidad" type="number" min="1" max="100" step="1" placeholder="Capacidad" required data-error="La capacidad debe ser un número entero entre 1 y 100.">
            <select name="estado" required>
                <?php foreach($estados as $opcion){ ?>
                <option value="<?php echo $opcion; ?>"><?php echo ucfirst($opcion); ?></option>
                <?php } ?>
            </select>
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
