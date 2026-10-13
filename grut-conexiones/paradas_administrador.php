<?php
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Administrador"){
    header("Location: iniciar_sesion.html");
    exit;
}
$stmt = $conexion->prepare("SELECT r.nombre AS ruta, p.nombre AS parada, p.direccion, h.hora_llegada, h.hora_salida FROM ruta_parada rp INNER JOIN rutas r ON r.id_ruta = rp.id_ruta INNER JOIN paradas p ON p.id_parada = rp.id_parada LEFT JOIN horarios h ON h.id_ruta_parada = rp.id_ruta_parada ORDER BY r.id_ruta, rp.orden, h.dia_semana, h.hora_salida");
$stmt->execute();
$resultado = $stmt->get_result();
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
<body class="page-paradas">

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
            <h2><span aria-hidden="true">🚏</span> Paradas</h2>

            <div class="panel-actions">
                <button class="btn-add">+ Nueva ruta</button>
                <button class="btn-remove">- Borrar ruta</button>
            </div>

            <div class="table-container" role="region" aria-label="Tabla de paradas" tabindex="0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Parada</th>
                            <th>Institución</th>
                            <th>Hora llegada</th>
                            <th>Hora salida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()){ ?>
                        <tr>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["ruta"]); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["parada"]); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["direccion"] ?? ""); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["hora_llegada"] ? date("g:i:s a", strtotime($fila["hora_llegada"])) : ""); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["hora_salida"] ? date("g:i:s a", strtotime($fila["hora_salida"])) : ""); ?>" readonly><button class="edit-btn">✏️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="5">No hay paradas registradas</td></tr>
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
