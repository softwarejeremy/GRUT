<?php
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Estudiante"){
    header("Location: iniciar_sesion.html");
    exit;
}
$stmt = $conexion->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.telefono, e.curso_educacion, e.centro_educativo FROM usuario u INNER JOIN estudiante e ON e.id_usuario = u.id_usuario WHERE u.id_usuario = ?");
$stmt->bind_param("i", $_SESSION["id_usuario"]);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
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
    <style>
        .mensaje-exito, .mensaje-error { padding: 12px 16px; border-radius: 8px; margin: 0 0 16px; font-weight: bold; }
        .mensaje-exito { background: #d4edda; color: #155724; }
        .mensaje-error { background: #f8d7da; color: #721c24; }
    </style>
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

        <!-- SECCIÓN 1: Usuario con Inputs de Formulario -->
        <section class="card-wrapper">
            <h2><span aria-hidden="true">👤</span> Usuario</h2>
            <div class="inner-card">
                <form class="user-grid" id="form-perfil" method="POST" action="php/actualizar_perfil.php">
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
                            <input type="tel" id="telefono" name="telefono" placeholder="" value="<?php echo htmlspecialchars($datos["telefono"]); ?>" maxlength="9" required readonly>
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

        <!-- SECCIÓN 2: Rutas -->
        <section class="card-wrapper">
            <h2><span aria-hidden="true">🚌</span> Rutas</h2>
            <div class="inner-card">
                <div class="routes-grid">
                    
                    <!-- Tarjeta Ruta 15 -->
                    <div class="route-card-item">
                        <h3>RUTA 15</h3>
                        <div class="route-info-group">
                            <div class="route-info-box">
                                <p><strong>Conductor:</strong> Juan Pérez</p>
                                <p><strong>Bus:</strong> ABC-123</p>
                                <p><strong>Estado:</strong> Activa</p>
                            </div>

                        </div>
                        <a href="ruta.html" class="btn-details">Ver detalles...</a>
                    </div>

                    <!-- Tarjeta Ruta 41-A -->
                    <div class="route-card-item">
                        <h3>RUTA 41-A</h3>
                        <div class="route-info-group">
                            <div class="route-info-box">
                                <p><strong>Conductor:</strong> María Campos</p>
                                <p><strong>Bus:</strong> DEF-456</p>
                                <p><strong>Estado:</strong> Activa</p>
                            </div>

                        </div>
                        <a href="ruta.html" class="btn-details">Ver detalles...</a>
                    </div>

                    <!-- Tarjeta Ruta 41-D -->
                    <div class="route-card-item">
                        <h3>RUTA 41-D</h3>
                        <div class="route-info-group">
                            <div class="route-info-box">
                                <p><strong>Conductor:</strong> José Aguilar</p>
                                <p><strong>Bus:</strong> GHI-789</p>
                                <p><strong>Estado:</strong> Inactiva</p>
                            </div>

                        </div>
                        <a href="ruta.html" class="btn-details">Ver detalles...</a>
                    </div>

                <div>
                    <a class="btn">Añadir  otra nueva ruta +</a> 
                </div>

                <div>
                    <a class="btn">Eliminar una ruta -</a>
                </div>

                    </div>

                </div>

                <!-- Botón Añadir RUTA -->
            </div>
        </section>

        <!-- SECCIÓN 3: Pagos y facturas -->
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

    <script>
    // Primer clic: habilita los campos. Segundo clic ("Guardar"): valida y envía.
    const form = document.getElementById("form-perfil");
    const btn = document.getElementById("btn-editar");
    const editables = ["nombre", "apellido", "curso", "telefono", "centro"]
        .map(id => document.getElementById(id));
    let editando = false;

    btn.addEventListener("click", () => {
        if (!editando) {
            editables.forEach(i => i.removeAttribute("readonly"));
            btn.textContent = "Guardar";
            editando = true;
            editables[0].focus();
        } else if (form.reportValidity()) {
            form.submit();
        }
    });
    </script>

</body>
</html>
