<?php
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Administrador"){
    header("Location: iniciar_sesion.html");
    exit;
}
$stmt = $conexion->prepare("SELECT id_factura, id_estudiante, fecha_emision, fecha_vencimiento, monto, estado_pago FROM facturas ORDER BY id_factura");
$stmt->execute();
$resultado = $stmt->get_result();
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
<body class="page-facturas">

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

            <div class="table-container" role="region" aria-label="Tabla de facturas" tabindex="0">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>id_factura</th>
                            <th>id_estudiante</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th>Monto</th>
                            <th>Estado</th>
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
                        <tr>
                            <td><?php echo htmlspecialchars($fila["id_factura"]); ?></td>
                            <td><?php echo htmlspecialchars($fila["id_estudiante"]); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($fila["fecha_emision"]))); ?></td>
                            <td><?php echo htmlspecialchars($fila["fecha_vencimiento"] ? date("d/m/Y", strtotime($fila["fecha_vencimiento"])) : ""); ?></td>
                            <td><?php echo htmlspecialchars("$" . number_format((float)$fila["monto"], 2)); ?></td>
                            <td><span class="invoice-status <?php echo $clase; ?>"><span aria-hidden="true"><?php echo $icono; ?></span> <?php echo htmlspecialchars(strtoupper($estado)); ?></span></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="6">No hay facturas registradas</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer>
        <h2>IG: @GRUT_updates</h2>
        <h2>FB: @GRUT_updates</h2>
        <h2>WAPP: +503 0000-0000</h2>
        <h2>X: @GRUT_updates</h2>
    </footer>

</body>
</html>
