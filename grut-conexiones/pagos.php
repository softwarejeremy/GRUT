<?php
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Estudiante"){
    header("Location: iniciar_sesion.html");
    exit;
}

// Del id_usuario de la sesión sacamos su id_estudiante
$stmt = $conexion->prepare("SELECT id_estudiante FROM estudiante WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION["id_usuario"]);
$stmt->execute();
$est = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$est){
    header("Location: inicio_estudiante.php");
    exit;
}
$id_estudiante = (int)$est["id_estudiante"];

// Facturas del estudiante
$stmt = $conexion->prepare(
    "SELECT id_factura, descripcion, fecha_emision, fecha_vencimiento, monto, metodo_pago, estado_pago
     FROM facturas
     WHERE id_estudiante = ?
     ORDER BY fecha_vencimiento DESC"
);
$stmt->bind_param("i", $id_estudiante);
$stmt->execute();
$resultado = $stmt->get_result();

$facturas = [];
while($fila = $resultado->fetch_assoc()){
    $facturas[] = $fila;
}
$stmt->close();

// Textos para mostrar
function formato_fecha($fecha){
    return $fecha ? date("d/m/Y", strtotime($fecha)) : "—";
}
// Clase CSS y texto de la etiqueta según estado_pago
function datos_estado($estado){
    switch($estado){
        case "pagado":  return ["badge-paid", "&#10003; PAGADO"];
        case "vencido": return ["badge-expired", "X VENCIDO"];
        default:        return ["badge-pending", "PENDIENTE"];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos y facturas - GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-pagos">

    <header class="navbar">
        <div class="logo">
            <img src="imagenes/logotipo-512.png" alt="Logotipo de GRUT">
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
        <a href="inicio_estudiante.php" class="btn-back">&larr; Salir de pagos y facturas</a>

        <section class="payments-card">
            <h2>Pagos y factura</h2>

            <div class="payments-container">
                <div class="table-container" role="region" aria-label="Tabla de pagos" tabindex="0">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>id_factura</th>
                            <th>Descripción</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th>Monto</th>
                            <th>Método de pago</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($facturas) > 0){ ?>
                            <?php foreach($facturas as $fila){ ?>
                                <?php
                                    $estado = strtolower(trim((string)$fila["estado_pago"]));
                                    list($clase, $texto) = datos_estado($estado);
                                ?>
                                <tr>
                                    <td><div class="cell-box"><?php echo htmlspecialchars($fila["id_factura"]); ?></div></td>
                                    <td><div class="cell-box"><?php echo htmlspecialchars($fila["descripcion"] ?? ""); ?></div></td>
                                    <td><div class="cell-box"><?php echo htmlspecialchars(formato_fecha($fila["fecha_emision"])); ?></div></td>
                                    <td><div class="cell-box"><?php echo htmlspecialchars(formato_fecha($fila["fecha_vencimiento"])); ?></div></td>
                                    <td><div class="cell-box">$<?php echo htmlspecialchars(number_format((float)$fila["monto"], 2)); ?></div></td>
                                    <td>
                                        <?php if($estado == "pendiente"){ ?>
                                            <!-- El pago en línea no entra en esta etapa: el botón no hace nada -->
                                            <button type="button" class="btn-action-pay">CLICK PARA PAGAR &rarr;</button>
                                        <?php } elseif(!empty($fila["metodo_pago"])){ ?>
                                            <div class="cell-box"><?php echo htmlspecialchars($fila["metodo_pago"]); ?></div>
                                        <?php } else { ?>
                                            <div class="badge badge-no-registered">No registrado</div>
                                        <?php } ?>
                                    </td>
                                    <td><div class="badge <?php echo $clase; ?>"><?php echo $texto; ?></div></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="7"><div class="cell-box">Aún no tienes facturas.</div></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-info">
            <span><strong>IG:</strong> @GRUT_updates</span>
            <span><strong>FB:</strong> @GRUT_updates</span>
            <span><strong>WAPP:</strong> +503 0000-0000</span>
            <span><strong>X:</strong> @GRUT_updates</span>
        </div>
    </footer>

</body>
</html>
