<?php
// Tarea 11 (Fabiola): mostrar las facturas. Tarea 12: agregar, editar, borrar y "Marcar como pagada".
require_once("php/admin_comun.php");

$estados = ["pendiente", "pagado", "vencido"];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    comprobar_csrf();

    function fecha_valida($valor){
        $fecha = DateTime::createFromFormat("Y-m-d", $valor);
        return $fecha && $fecha->format("Y-m-d") === $valor;
    }
    function monto_valido($valor){
        return is_numeric($valor) && (float)$valor > 0 && (float)$valor < 100000000;
    }

    $accion = $_POST["accion"] ?? "";
    try {
        if($accion == "crear"){
            $id_estudiante = (int)($_POST["id_estudiante"] ?? 0);
            $emision = $_POST["fecha_emision"] ?? "";
            $vencimiento = $_POST["fecha_vencimiento"] ?? "";
            $monto = trim($_POST["monto"] ?? "");
            $descripcion = trim($_POST["descripcion"] ?? "");
            $estado = $_POST["estado_pago"] ?? "";
            $metodo = trim($_POST["metodo_pago"] ?? "");
            if($id_estudiante <= 0){ responder(false, "Elige un estudiante.", 422); }
            if(!fecha_valida($emision)){ responder(false, "La fecha de emisión no es válida.", 422); }
            if($vencimiento !== "" && !fecha_valida($vencimiento)){ responder(false, "La fecha de vencimiento no es válida.", 422); }
            if(!monto_valido($monto)){ responder(false, "El monto debe ser un número mayor que 0.", 422); }
            if(!in_array($estado, $estados, true)){ responder(false, "Estado no válido.", 422); }
            if(mb_strlen($descripcion) > 255 || mb_strlen($metodo) > 50){ responder(false, "La descripción o el método de pago son demasiado largos.", 422); }
            $vencimiento = $vencimiento === "" ? null : $vencimiento;
            $metodo = $metodo === "" ? null : $metodo;

            $stmt = $conexion->prepare("INSERT INTO facturas (id_estudiante, fecha_emision, fecha_vencimiento, monto, estado_pago, metodo_pago, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $id_estudiante, $emision, $vencimiento, $monto, $estado, $metodo, $descripcion);
            $stmt->execute();
            $stmt->close();
            responder(true, "Factura creada.");
        }

        if($accion == "actualizar"){
            $id = (int)($_POST["id"] ?? 0);   // id_factura
            $campo = $_POST["campo"] ?? "";
            $valor = trim($_POST["valor"] ?? "");
            if($id <= 0){ responder(false, "ID no válido.", 422); }
            if($campo == "descripcion" && mb_strlen($valor) > 255){ responder(false, "La descripción no puede pasar de 255 caracteres.", 422); }
            if($campo == "fecha_vencimiento" && $valor !== "" && !fecha_valida($valor)){ responder(false, "Fecha no válida (año-mes-día).", 422); }
            if($campo == "monto" && !monto_valido($valor)){ responder(false, "El monto debe ser un número mayor que 0.", 422); }

            // El nombre de la columna sale de esta lista, nunca del formulario
            $columnas = ["descripcion" => "descripcion", "fecha_vencimiento" => "fecha_vencimiento", "monto" => "monto"];
            if(!isset($columnas[$campo])){ responder(false, "Campo desconocido.", 422); }
            $valor = ($valor === "" && $campo == "fecha_vencimiento") ? null : $valor;
            $stmt = $conexion->prepare("UPDATE facturas SET " . $columnas[$campo] . " = ? WHERE id_factura = ?");
            $stmt->bind_param("si", $valor, $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Cambio guardado.");
        }

        if($accion == "pagar"){
            $id = (int)($_POST["id"] ?? 0);
            $stmt = $conexion->prepare("UPDATE facturas SET estado_pago = 'pagado' WHERE id_factura = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Factura marcada como pagada.");
        }

        if($accion == "eliminar"){
            $id = (int)($_POST["id"] ?? 0);
            $stmt = $conexion->prepare("DELETE FROM facturas WHERE id_factura = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            responder(true, "Factura eliminada.");
        }

        responder(false, "Acción desconocida.", 400);
    } catch(mysqli_sql_exception $e){
        responder_error_mysql($e, "No se puede borrar esta factura.");
    }
}

$stmt = $conexion->prepare("SELECT f.id_factura, f.id_estudiante, u.nombre, u.apellido, f.descripcion, f.fecha_emision, f.fecha_vencimiento, f.monto, f.estado_pago FROM facturas f INNER JOIN estudiante e ON e.id_estudiante = f.id_estudiante INNER JOIN usuario u ON u.id_usuario = e.id_usuario ORDER BY f.id_factura");
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

$stmt = $conexion->prepare("SELECT e.id_estudiante, u.nombre, u.apellido FROM estudiante e INNER JOIN usuario u ON u.id_usuario = e.id_usuario ORDER BY u.nombre, u.apellido");
$stmt->execute();
$estudiantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-facturas" data-csrf="<?php echo htmlspecialchars($csrf); ?>">

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

        <section class="panel invoice-panel">
            <h2>Facturas</h2>

            <div class="panel-actions">
                <button type="button" class="btn-add" id="btnNuevo">+ Nueva factura</button>
                <button type="button" class="btn-remove" id="btnBorrar">- Borrar factura</button>
            </div>

            <div class="table-container" role="region" aria-label="Tabla de facturas" tabindex="0">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Estudiante</th>
                            <th>Descripción</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th class="col-borrar" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()){ ?>
                        <?php
                            $estado = strtolower($fila["estado_pago"] ?? "");
                            if($estado == "pagado"){
                                $clase = "status-paid"; $icono = "✓";
                            } else {
                                $clase = "status-pending"; $icono = "⚠";
                                if($estado === ""){ $estado = "pendiente"; }
                            }
                        ?>
                        <tr data-id="<?php echo (int)$fila["id_factura"]; ?>" data-nombre="la factura <?php echo (int)$fila["id_factura"]; ?>">
                            <td><?php echo htmlspecialchars($fila["id_factura"]); ?></td>
                            <td><?php echo htmlspecialchars($fila["nombre"] . " " . $fila["apellido"]); ?></td>
                            <td><input type="text" data-campo="descripcion" value="<?php echo htmlspecialchars($fila["descripcion"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar descripción">✏️</button></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($fila["fecha_emision"]))); ?></td>
                            <td><input type="date" data-campo="fecha_vencimiento" value="<?php echo htmlspecialchars($fila["fecha_vencimiento"] ?? ""); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar vencimiento">✏️</button></td>
                            <td><input type="number" step="0.01" min="0.01" data-campo="monto" value="<?php echo htmlspecialchars($fila["monto"]); ?>" readonly><button type="button" class="edit-btn" aria-label="Editar monto">✏️</button></td>
                            <td>
                                <div class="estado-celda">
                                    <span class="invoice-status <?php echo $clase; ?>"><span aria-hidden="true"><?php echo $icono; ?></span> <?php echo htmlspecialchars(strtoupper($estado)); ?></span>
                                    <?php if($estado != "pagado"){ ?>
                                    <button type="button" class="btn-accion" data-accion="pagar">Marcar como pagada</button>
                                    <?php } ?>
                                </div>
                            </td>
                            <td class="col-borrar"><button type="button" class="del-btn" title="Borrar" aria-label="Borrar factura">🗑️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="7">No hay facturas registradas</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <dialog id="dlgNuevo">
        <form id="formNuevo" method="dialog">
            <h3>Nueva factura</h3>
            <select name="id_estudiante" required>
                <option value="" selected disabled>Estudiante</option>
                <?php foreach($estudiantes as $est){ ?>
                <option value="<?php echo (int)$est["id_estudiante"]; ?>"><?php echo htmlspecialchars($est["nombre"] . " " . $est["apellido"]); ?></option>
                <?php } ?>
            </select>
            <input name="descripcion" placeholder="Descripción (ej. Mensualidad noviembre)" maxlength="255" required>
            <label for="fac-emision">Fecha de emisión</label>
            <input name="fecha_emision" id="fac-emision" type="date" value="<?php echo date("Y-m-d"); ?>" required>
            <label for="fac-vencimiento">Fecha de vencimiento</label>
            <input name="fecha_vencimiento" id="fac-vencimiento" type="date">
            <input name="monto" type="number" step="0.01" min="0.01" placeholder="Monto ($)" required>
            <select name="estado_pago" required>
                <?php foreach($estados as $opcion){ ?>
                <option value="<?php echo $opcion; ?>"><?php echo ucfirst($opcion); ?></option>
                <?php } ?>
            </select>
            <input name="metodo_pago" placeholder="Método de pago (opcional)" maxlength="50">
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
