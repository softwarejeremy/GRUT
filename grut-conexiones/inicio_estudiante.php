<?php
// Panel del estudiante.
// Tarea 8 (Fabiola): sección "Usuario" con el formulario para editar el perfil.
// Tarea 5 (Gustavo): sección "Rutas" con las rutas reales del estudiante.
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Estudiante"){
    header("Location: iniciar_sesion.html");
    exit;
}

// ---------- Tarea 8: datos del perfil ----------
$stmt = $conexion->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.telefono, e.curso_educacion, e.centro_educativo FROM usuario u INNER JOIN estudiante e ON e.id_usuario = u.id_usuario WHERE u.id_usuario = ?");
$stmt->bind_param("i", $_SESSION["id_usuario"]);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---------- Tarea 5: rutas del estudiante ----------
// estudiante -> estudiante_ruta -> rutas -> conductor -> usuario (nombre del conductor)
// y rutas -> ruta_bus -> buses (placa). Se parte del id_usuario de la sesión.
// Si una ruta tiene varios buses, sus placas salen juntas ("P-101, P-102").
$stmt = $conexion->prepare("SELECT r.id_ruta, r.nombre, r.descripcion, uc.nombre AS nombre_conductor, uc.apellido AS apellido_conductor, GROUP_CONCAT(DISTINCT b.placa ORDER BY b.placa SEPARATOR ', ') AS placas, COUNT(DISTINCT rp.id_ruta_parada) AS total_paradas FROM estudiante e INNER JOIN estudiante_ruta er ON er.id_estudiante = e.id_estudiante INNER JOIN rutas r ON r.id_ruta = er.id_ruta LEFT JOIN conductor c ON c.id_conductor = r.id_conductor LEFT JOIN usuario uc ON uc.id_usuario = c.id_usuario LEFT JOIN ruta_bus rb ON rb.id_ruta = r.id_ruta LEFT JOIN buses b ON b.id_bus = rb.id_bus LEFT JOIN ruta_parada rp ON rp.id_ruta = r.id_ruta WHERE e.id_usuario = ? GROUP BY r.id_ruta, r.nombre, r.descripcion, uc.nombre, uc.apellido ORDER BY r.nombre");
$stmt->bind_param("i", $_SESSION["id_usuario"]);
$stmt->execute();
$rutas = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio (Estudiante) - GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-inicio-estudiante">

    <!-- Header / Navbar -->
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
                    <!-- Tarea 14 (Andrea): "Cuenta" lleva al inicio de sesión -->
                    <li><a class="btn" href="iniciar_sesion.html">Cuenta</a></li>
                </ul>
            </nav>
    </header>

    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <main id="contenido" tabindex="-1">
        <!-- Título principal -->
        <h1 class="welcome-title">¡Bienvenido, <?php echo htmlspecialchars($datos["nombre"]); ?>!</h1>

        <!-- Mensaje de éxito o error al guardar el perfil -->
        <?php if (!empty($_SESSION["perfil_msg"])): ?>
            <p class="mensaje-<?php echo htmlspecialchars($_SESSION["perfil_msg"]["tipo"]); ?>" role="alert">
                <?php echo htmlspecialchars($_SESSION["perfil_msg"]["texto"]); ?>
            </p>
            <?php unset($_SESSION["perfil_msg"]); ?>
        <?php endif; ?>

        <!-- SECCIÓN 1 (tarea 8): datos del usuario. "Editar información" habilita los campos
             y scripts/validacion_perfil.js los valida antes de enviarlos a php/actualizar_perfil.php -->
        <section class="card-wrapper">
            <h2><span aria-hidden="true">👤</span> Usuario</h2>
            <div class="inner-card">
                <!-- Aquí se muestran los errores de la validación con JavaScript -->
                <p class="mensaje-error" id="aviso-perfil" role="alert" hidden></p>
                <form class="user-grid" id="form-perfil" method="POST" action="php/actualizar_perfil.php" data-perfil>
                    <!-- Nombre -->
                    <div class="input-group">
                        <label for="nombre">Nombre</label>
                        <div class="field-with-icon">
                            <input type="text" id="nombre" name="nombre" placeholder="" value="<?php echo htmlspecialchars($datos["nombre"]); ?>" maxlength="100" required readonly>
                        </div>
                    </div>

                    <!-- Apellido -->
                    <div class="input-group">
                        <label for="apellido">Apellido</label>
                        <div class="field-with-icon">
                            <input type="text" id="apellido" name="apellido" placeholder="" value="<?php echo htmlspecialchars($datos["apellido"]); ?>" maxlength="100" required readonly>
                        </div>
                    </div>

                    <!-- Curso de educación -->
                    <div class="input-group">
                        <label for="curso">Curso de educación</label>
                        <div class="field-with-icon">
                            <input type="text" id="curso" name="curso" placeholder="" value="<?php echo htmlspecialchars($datos["curso_educacion"]); ?>" maxlength="50" required readonly>
                        </div>
                    </div>

                    <!-- Correo electrónico (no se edita en esta tarea, no se envía) -->
                    <div class="input-group">
                        <label for="email">Correo electrónico</label>
                        <div class="field-with-icon">
                            <input type="email" id="email" placeholder="" value="<?php echo htmlspecialchars($datos["correo"]); ?>" readonly>
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="input-group">
                        <label for="telefono">Teléfono</label>
                        <div class="field-with-icon">
                            <input type="tel" id="telefono" name="telefono" placeholder="" value="<?php echo htmlspecialchars($datos["telefono"]); ?>" maxlength="9" pattern="[2678][0-9]{3}-[0-9]{4}" title="Con guion, ej: 7000-0000" required readonly>
                        </div>
                    </div>

                    <!-- Centro educativo -->
                    <div class="input-group">
                        <label for="centro">Centro educativo</label>
                        <div class="field-with-icon">
                            <input type="text" id="centro" name="centro" placeholder="" value="<?php echo htmlspecialchars($datos["centro_educativo"]); ?>" maxlength="150" required readonly>
                        </div>
                    </div>

                    <!-- ID usuario (solo informativo, sin name: NO se envía; el PHP usa la sesión) -->
                    <div class="input-group full-width">
                        <label for="id_usuario">ID usuario</label>
                        <div class="field-with-icon single">
                            <input type="number" id="id_usuario" value="<?php echo htmlspecialchars($_SESSION["id_usuario"]); ?>" readonly>
                        </div>
                    </div>

                    <div>
                        <button type="button" class="btn" id="btn-editar">Editar información</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- SECCIÓN 2: Rutas (tarea 5). Solo se consultan: las asigna el administrador. -->
        <section class="card-wrapper">
            <h2><span aria-hidden="true">🚌</span> Rutas</h2>
            <div class="inner-card">
                <?php if($rutas->num_rows === 0){ ?>
                    <p class="sin-rutas">Aún no tienes rutas asignadas. Cuando el administrador te inscriba en una ruta, aparecerá aquí.</p>
                <?php } else { ?>
                <div class="routes-grid">
                    <?php while($fila = $rutas->fetch_assoc()){ ?>
                    <div class="route-card-item">
                        <h3><?php echo htmlspecialchars(mb_strtoupper($fila["nombre"])); ?></h3>
                        <div class="route-info-group">
                            <div class="route-info-box">
                                <p><strong>Conductor:</strong> <?php echo htmlspecialchars($fila["nombre_conductor"] !== null ? $fila["nombre_conductor"] . " " . $fila["apellido_conductor"] : "Sin asignar"); ?></p>
                                <p><strong>Bus:</strong> <?php echo htmlspecialchars($fila["placas"] ?? "Sin asignar"); ?></p>
                                <p><strong>Paradas:</strong> <?php echo (int)$fila["total_paradas"]; ?></p>
                            </div>
                        </div>
                        <a href="ruta.php?id=<?php echo (int)$fila["id_ruta"]; ?>" class="btn-details">Ver detalles...</a>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        </section>

        <!-- SECCIÓN 3: enlace a pagos.php (tarea 7) -->
        <section class="card-wrapper">
            <h2><span aria-hidden="true">💳</span> Pagos y facturas</h2>
            <div class="inner-card payments-summary-card">
                <p>Mantén tus cuentas al día. Te invitamos a revisar tus pagos y facturas pendientes para asegurar que todo esté al corriente.</p>
                <a href="pagos.php" class="btn-details">Ver detalles...</a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-info">
            <span><strong>IG:</strong> @GRUT_updates</span>
            <span><strong>FB:</strong> @GRUT_updates</span>
            <span><strong>WAPP:</strong> +503 0000-0000</span>
            <span><strong>X:</strong> @GRUT_updates</span>
        </div>
    </footer>

    <script src="scripts/validacion_perfil.js"></script>

</body>
</html>
