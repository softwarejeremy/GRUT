<?php
// Tarea 9 (Bryan): panel del conductor con sus datos y la ruta que tiene asignada.
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Conductor"){
    header("Location: iniciar_sesion.html");
    exit;
}

$stmt = $conexion->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.telefono, c.licencia, c.id_conductor FROM usuario u INNER JOIN conductor c ON c.id_usuario = u.id_usuario WHERE u.id_usuario = ?");
$stmt->bind_param("i", $_SESSION["id_usuario"]);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Tiene el rol Conductor pero no tiene fila en la tabla conductor (ver sección 8 del LEEME)
if(!$datos){
    die("Tu cuenta de conductor no está completa: falta tu fila en la tabla conductor. Avisa a un administrador.");
}

// ---------- Ruta asignada: rutas (por id_conductor) -> ruta_bus -> buses ----------
// Rutas asignadas al conductor (rutas.id_conductor) con su bus (ruta_bus -> buses)
$stmt = $conexion->prepare("SELECT r.id_ruta, r.nombre, r.descripcion, b.placa, b.capacidad, b.estado AS estado_bus FROM rutas r LEFT JOIN ruta_bus rb ON rb.id_ruta = r.id_ruta LEFT JOIN buses b ON b.id_bus = rb.id_bus WHERE r.id_conductor = ? ORDER BY r.id_ruta");
$stmt->bind_param("i", $datos["id_conductor"]);
$stmt->execute();
$resultado = $stmt->get_result();
$rutas = [];
while($fila = $resultado->fetch_assoc()){
    // Si una ruta tiene varios buses, se queda la primera fila
    if(!isset($rutas[$fila["id_ruta"]])){
        $rutas[$fila["id_ruta"]] = $fila;
    }
}
$stmt->close();

$dias = ["lunes" => "Lunes", "martes" => "Martes", "miercoles" => "Miércoles", "jueves" => "Jueves",
         "viernes" => "Viernes", "sabado" => "Sábado", "domingo" => "Domingo"];

foreach($rutas as $id_ruta => $ruta){
    // Paradas en orden, con sus horarios (ruta_parada -> paradas, horarios)
    $stmt = $conexion->prepare("SELECT p.nombre, p.direccion, p.latitud, p.longitud, rp.orden, h.dia_semana, h.hora_salida, h.hora_llegada FROM ruta_parada rp INNER JOIN paradas p ON p.id_parada = rp.id_parada LEFT JOIN horarios h ON h.id_ruta_parada = rp.id_ruta_parada WHERE rp.id_ruta = ? ORDER BY rp.orden, FIELD(h.dia_semana, 'lunes','martes','miercoles','jueves','viernes','sabado','domingo')");
    $stmt->bind_param("i", $id_ruta);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $paradas = [];
    while($fila = $resultado->fetch_assoc()){
        $clave = $fila["orden"] . "-" . $fila["nombre"];
        if(!isset($paradas[$clave])){
            $paradas[$clave] = ["nombre" => $fila["nombre"], "direccion" => $fila["direccion"],
                                "latitud" => $fila["latitud"], "longitud" => $fila["longitud"], "horarios" => []];
        }
        if($fila["dia_semana"] !== null){
            $paradas[$clave]["horarios"][] = $dias[$fila["dia_semana"]] . " " . substr($fila["hora_salida"], 0, 5) . " – " . substr($fila["hora_llegada"], 0, 5);
        }
    }
    $stmt->close();
    $rutas[$id_ruta]["paradas"] = array_values($paradas);

    // Colegios/universidades: los centros de los estudiantes inscritos en la ruta
    $stmt = $conexion->prepare("SELECT DISTINCT e.centro_educativo FROM estudiante_ruta er INNER JOIN estudiante e ON e.id_estudiante = er.id_estudiante WHERE er.id_ruta = ? AND e.centro_educativo IS NOT NULL AND e.centro_educativo <> '' ORDER BY e.centro_educativo");
    $stmt->bind_param("i", $id_ruta);
    $stmt->execute();
    $rutas[$id_ruta]["centros"] = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "centro_educativo");
    $stmt->close();

    // Enlace a Google Maps con las paradas que tienen coordenadas, en orden
    $puntos = [];
    foreach($rutas[$id_ruta]["paradas"] as $p){
        if($p["latitud"] !== null && $p["longitud"] !== null){
            $puntos[] = $p["latitud"] . "," . $p["longitud"];
        }
    }
    $url_mapa = "";
    if(count($puntos) == 1){
        $url_mapa = "https://www.google.com/maps/search/?api=1&query=" . urlencode($puntos[0]);
    } elseif(count($puntos) > 1){
        $url_mapa = "https://www.google.com/maps/dir/?api=1&origin=" . urlencode($puntos[0])
                  . "&destination=" . urlencode($puntos[count($puntos) - 1]);
        $intermedios = array_slice($puntos, 1, -1);
        if($intermedios){
            $url_mapa .= "&waypoints=" . urlencode(implode("|", $intermedios));
        }
    }
    $rutas[$id_ruta]["url_mapa"] = $url_mapa;
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inicio (conductor) - GRUT</title>
        <script src="scripts/preferencias.js"></script>
        <link rel="stylesheet" href="css/estilos_globales.css">
        <link rel="stylesheet" href="css/preferencias.css">
    </head>
    <body class="page-cuenta-conductor">
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
        <!-- Título principal -->
        <h1 class="welcome-title">¡Bienvenido, <?php echo htmlspecialchars($datos["nombre"]); ?>!</h1>

        <!-- Tarea 9: mensaje de éxito o error al guardar el perfil (php/conductor_editar_perfil.php) -->
        <?php if(!empty($_SESSION["perfil_msg"])){ ?>
            <p class="mensaje-<?php echo htmlspecialchars($_SESSION["perfil_msg"]["tipo"]); ?>" role="alert">
                <?php echo htmlspecialchars($_SESSION["perfil_msg"]["texto"]); ?>
            </p>
            <?php unset($_SESSION["perfil_msg"]); ?>
        <?php } ?>

            <section class="panel">

                <!-- Tarea 9: datos personales (usuario + conductor). Se editan con scripts/validacion_perfil.js -->
                <h3 class="panel-encabezado"><span aria-hidden="true">👤</span> Información de usuario</h3>

                <div class="panel-cuerpo">

                 <!-- Aquí se muestran los errores de la validación con JavaScript -->
                 <p class="mensaje-error" id="aviso-perfil" role="alert" hidden></p>
                 <form id="form-conductor" method="POST" action="php/conductor_editar_perfil.php" data-perfil>

                    <div class="fila-campos">
                        <div class="campo campo-editable">
                            <label for="nombre">Nombre</label>
                            <div class="input-editable">
                                <input type="text" id="nombre" name="nombre" placeholder="Nombre" maxlength="100" required readonly value="<?php echo htmlspecialchars($datos["nombre"]); ?>">
                            </div>
                        </div>

                        <div class="campo campo-editable">
                            <label for="apellido">Apellido</label>
                            <div class="input-editable">
                                <input type="text" id="apellido" name="apellido" placeholder="Apellido" maxlength="100" required readonly value="<?php echo htmlspecialchars($datos["apellido"]); ?>">
                            </div>
                        </div>

                        <div class="campo campo-editable">
                            <label for="num-licencia">No de licencia</label>
                            <div class="input-editable">
                                <input type="text" id="num-licencia" name="num-licencia" placeholder="ej: 0000-000000-000-0" maxlength="50" required readonly value="<?php echo htmlspecialchars($datos["licencia"]); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="fila-campos">
                        <div class="campo campo-editable">
                            <label for="correo">Correo electrónico</label>
                            <div class="input-editable">
                                <!-- El correo no se edita aquí (sin name: no se envía) -->
                                <input type="email" id="correo" placeholder="nombre@correo.com" readonly value="<?php echo htmlspecialchars($datos["correo"]); ?>">
                            </div>
                        </div>

                        <div class="campo campo-editable">
                            <label for="telefono">Teléfono</label>
                            <div class="input-editable">
                                <input type="tel" id="telefono" name="telefono" placeholder="0000-0000" maxlength="9" pattern="[2678][0-9]{3}-[0-9]{4}" title="Con guion, ej: 7000-0000" required readonly value="<?php echo htmlspecialchars($datos["telefono"] ?? ""); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="fila-campos">
                        <div class="campo">
                            <label for="id-usuario">ID usuario</label>
                            <!-- Solo informativo (sin name): el PHP usa el id de la sesión -->
                            <input type="text" id="id-usuario" value="<?php echo (int)$datos["id_usuario"]; ?>" readonly>
                        </div>
                    </div>

                    <br>
                <div>
                    <button type="button" class="btn" id="btn-editar">Editar información</button>
                </div>

</form>

            </section>

            <section class="panel">

                <!-- Tarea 9: ruta, paradas en orden con horario, centros educativos, bus y capacidad -->
                <h3 class="panel-encabezado"><span aria-hidden="true">🚦</span> Seguimiento e información de rutas</h3>
 <!-- aria-hidden es para ocultar el icono en pantallas de lectura -->
                <div class="panel-cuerpo">

                    <h4 class="panel-subtitulo">Información</h4>
    <?php if(count($rutas) == 0){ ?>
                    <p>Todavía no tienes una ruta asignada. Consulta con un administrador.</p>
    <?php } ?>
    <?php foreach($rutas as $ruta){ ?>
                    <ul class="lista-info">
                        <li><strong>🇸🇻 Ruta a seguir:</strong> <?php echo htmlspecialchars($ruta["nombre"]); ?></li>
                        <?php if(!empty($ruta["descripcion"])){ ?>
                        <li><strong><span aria-hidden="true">🛣️</span> Recorrido:</strong> <?php echo htmlspecialchars($ruta["descripcion"]); ?></li>
                        <?php } ?>
                        <li>
                            <strong><span aria-hidden="true">📍</span> Paradas (en orden):</strong>
                            <ul class="sublista">
                                <?php if(!empty($ruta["paradas"])){ ?>
                                    <?php foreach($ruta["paradas"] as $parada){ ?>
                                        <li>
                                            <?php echo htmlspecialchars($parada["nombre"]); ?>
                                            <?php if(!empty($parada["horarios"])){ ?>
                                                (<?php echo htmlspecialchars(implode(", ", $parada["horarios"])); ?>)
                                            <?php } ?>
                                        </li>
                                    <?php } ?>
                                <?php } else { ?>
                                    <li>Esta ruta todavía no tiene paradas.</li>
                                <?php } ?>
                            </ul>
                        </li>
                        <li>
                            <strong><span aria-hidden="true">🏫</span> Colegios/universidades de los estudiantes:</strong>
                            <ul class="sublista">
                                <?php if(!empty($ruta["centros"])){ ?>
                                    <?php foreach($ruta["centros"] as $centro){ ?>
                                        <li><?php echo htmlspecialchars($centro); ?></li>
                                    <?php } ?>
                                <?php } else { ?>
                                    <li>No hay estudiantes inscritos en esta ruta.</li>
                                <?php } ?>
                            </ul>
                        </li>
                        <li><strong><span aria-hidden="true">🚌</span> Bus a manejar:</strong> <?php echo htmlspecialchars($ruta["placa"] ?? "Sin bus asignado"); ?><?php if(!empty($ruta["estado_bus"])){ echo " (" . htmlspecialchars($ruta["estado_bus"]) . ")"; } ?></li>
                        <li><strong><span aria-hidden="true">👥</span> Capacidad de personas:</strong> <?php echo htmlspecialchars($ruta["capacidad"] ?? "—"); ?></li>
                    </ul>
    <?php } ?>
                </div>

            </section>

            <section class="panel">

                <!-- Tarea 9: enlace a Google Maps con las coordenadas de las paradas -->
                <h3 class="panel-encabezado"><span aria-hidden="true">🗺️</span> Ver ruta</h3>

                <div class="panel-cuerpo ver-ruta">
    <?php if(count($rutas) == 0){ ?>
                    <p>No hay información disponible para esta ruta.</p>
    <?php } ?>
    <?php foreach($rutas as $ruta){ ?>
                    <?php if($ruta["url_mapa"] != ""){ ?>
                    <a class="mapa-preview" href="<?php echo htmlspecialchars($ruta["url_mapa"]); ?>" target="_blank" rel="noopener" aria-label="Abrir la ruta en Google Maps">
                        <svg viewBox="0 0 200 140" xmlns="http://www.w3.org/2000/svg" class="mapa-svg" aria-hidden="true">
                            <rect width="200" height="140" fill="#dfe9e0"/>
                            <rect x="0" y="0" width="60" height="60" fill="#cfe3d4"/>
                            <rect x="120" y="20" width="70" height="50" fill="#cfe3d4"/>
                            <rect x="20" y="80" width="80" height="55" fill="#cfe3d4"/>
                            <path d="M15 120 L70 90 L110 70 L150 40 L185 20" stroke="#e05a3c" stroke-width="4" fill="none" stroke-linecap="round"/>
                            <circle cx="15" cy="120" r="5" fill="#1a4a63"/>
                            <circle cx="185" cy="20" r="5" fill="#e05a3c"/>
                        </svg>
                    </a>
                    <?php } ?>

                    <div class="ruta-detalle">
                        <h4><?php echo htmlspecialchars($ruta["nombre"]); ?></h4>
                        <?php if($ruta["url_mapa"] != ""){ ?>
                        <!-- target="_blank" abre una pestaña nueva; rel="noopener" evita que esa página controle la nuestra -->
                        <a href="<?php echo htmlspecialchars($ruta["url_mapa"]); ?>" target="_blank" rel="noopener">Visualizar en Google Maps</a>
                        <?php } else { ?>
                        <p>Las paradas de esta ruta todavía no tienen coordenadas para mostrar el mapa.</p>
                        <?php } ?>
                        <?php if(!empty($ruta["paradas"])){ ?>
                        <p class="ruta-direccion"><?php echo htmlspecialchars(implode(" → ", array_column($ruta["paradas"], "nombre"))); ?></p>
                        <?php } ?>
                    </div>
    <?php } ?>
                </div>

            </section>

        </main>

        <footer class="footer-social">
            <p>
                <span>IG: @GRUT_updates</span>
                <span>FB: @GRUT_updates</span>
                <span>WAPP: +503 0000-0000</span>
                <span>X: @GRUT_updates</span>
            </p>
        </footer>

        <!-- Editar y validar el perfil (tareas 8 y 9) -->
        <script src="scripts/validacion_perfil.js"></script>
    </body>
</html>
