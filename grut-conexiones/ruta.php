<?php
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Estudiante"){
    header("Location: iniciar_sesion.html");
    exit;
}

// Id de la ruta que viene en la dirección: ruta.php?id=...
if(!isset($_GET["id"]) || !ctype_digit($_GET["id"]) || (int)$_GET["id"] <= 0){
    header("Location: inicio_estudiante.php");
    exit;
}
$id_ruta = (int)$_GET["id"];

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

// SEGURIDAD: la ruta debe pertenecer a este estudiante (estudiante_ruta)
$stmt = $conexion->prepare(
    "SELECT r.nombre
     FROM rutas r
     INNER JOIN estudiante_ruta er ON r.id_ruta = er.id_ruta
     WHERE er.id_estudiante = ? AND r.id_ruta = ?"
);
$stmt->bind_param("ii", $id_estudiante, $id_ruta);
$stmt->execute();
$datos_ruta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$datos_ruta){
    header("Location: inicio_estudiante.php");
    exit;
}
$nombre_ruta = $datos_ruta["nombre"];

// Paradas de la ruta en orden, con sus horarios
$stmt = $conexion->prepare(
    "SELECT p.nombre AS nombre_parada, p.direccion,
            h.dia_semana, h.hora_salida, h.hora_llegada
     FROM ruta_parada rp
     INNER JOIN paradas p ON rp.id_parada = p.id_parada
     LEFT JOIN horarios h ON rp.id_ruta_parada = h.id_ruta_parada
     WHERE rp.id_ruta = ?
     ORDER BY rp.orden ASC,
              FIELD(h.dia_semana, 'lunes','martes','miercoles','jueves','viernes','sabado','domingo')"
);
$stmt->bind_param("i", $id_ruta);
$stmt->execute();
$resultado = $stmt->get_result();

$paradas = [];
while($fila = $resultado->fetch_assoc()){
    $paradas[] = $fila;
}
$stmt->close();

// Textos para mostrar
$dias = [
    "lunes" => "Lunes", "martes" => "Martes", "miercoles" => "Miércoles",
    "jueves" => "Jueves", "viernes" => "Viernes", "sabado" => "Sábado",
    "domingo" => "Domingo"
];
function formato_hora($hora){
    return $hora ? substr($hora, 0, 5) : "N/A";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRUT - Detalles de la ruta</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-ruta">

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
    <main class="main-container" id="contenido" tabindex="-1">
        <a href="inicio_estudiante.php" class="back-link">&larr; Salir de detalles</a>

        <div class="outer-card">
            <h1 class="outer-card-title">Detalles de la ruta.</h1>

            <div class="inner-card">
                <section class="route-section">
                    <h2 class="route-title"><?php echo htmlspecialchars($nombre_ruta); ?></h2>

                    <div class="cards-grid">
                        <?php if(count($paradas) > 0){ ?>
                            <?php foreach($paradas as $parada){ ?>
                                <div class="stop-card">
                                    <div><strong>Parada:</strong> <?php echo htmlspecialchars($parada["nombre_parada"]); ?></div>
                                    <div><strong>Dirección:</strong> <?php echo htmlspecialchars($parada["direccion"] ?? "No especificada"); ?></div>
                                    <div><strong>Día:</strong> <?php echo htmlspecialchars($dias[$parada["dia_semana"]] ?? "N/A"); ?></div>
                                    <div><strong>Hora entrada:</strong> <?php echo htmlspecialchars(formato_hora($parada["hora_llegada"])); ?></div>
                                    <div><strong>Hora salida:</strong> <?php echo htmlspecialchars(formato_hora($parada["hora_salida"])); ?></div>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <p>No se encontraron paradas registradas para esta ruta.</p>
                        <?php } ?>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <footer class="footer-social">
        <p>
            <span>IG: @GRUT_updates</span>
            <span>FB: @GRUT_updates</span>
            <span>WAPP: +503 0000-0000</span>
            <span>X: @GRUT_updates</span>
        </p>
    </footer>
</body>
</html>
