<?php
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Administrador"){
    header("Location: iniciar_sesion.html");
    exit;
}
$stmt = $conexion->prepare("SELECT id_usuario, nombre, apellido, correo, telefono FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION["id_usuario"]);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$datos){
    header("Location: iniciar_sesion.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de administrador | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-inicio-administrador">

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
        <h1 class="welcome">¡Bienvenido, <?php echo htmlspecialchars($datos["nombre"]); ?>!</h1>

        <section class="panel">
            <h2><span aria-hidden="true">👤</span> Información de usuario</h2>

            <div class="user-grid">
                <div class="field">
                    <label for="nombre">Nombre</label>
                    <div class="field-input">
                        <input type="text" id="nombre" value="<?php echo htmlspecialchars($datos["nombre"]); ?>" readonly>
                    </div>
                </div>

                <div class="field">
                    <label for="apellido">Apellido</label>
                    <div class="field-input">
                        <input type="text" id="apellido" value="<?php echo htmlspecialchars($datos["apellido"]); ?>" readonly>
                    </div>
                </div>

                <div class="field">
                    <label for="correo">Correo electrónico</label>
                    <div class="field-input">
                        <input type="email" id="correo" value="<?php echo htmlspecialchars($datos["correo"]); ?>" readonly>
                    </div>
                </div>

                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <div class="field-input">
                        <input type="tel" id="telefono" value="<?php echo htmlspecialchars($datos["telefono"] ?? ""); ?>" readonly>
                    </div>
                </div>

                <div class="field field-full">
                    <label for="id-usuario">ID usuario</label>
                    <div class="field-input">
                        <input type="text" id="id-usuario" value="<?php echo htmlspecialchars($datos["id_usuario"]); ?>" readonly>
                    </div>
                </div>

                <!-- Botón "Editar información": fuera de esta tarea (solo se muestran los datos)
                <div>
                    <a class="btn">Editar información</a>
                </div>
                -->
            </div>
        </section>

        <nav class="dashboard-nav" aria-label="Secciones de administración">
            <ul>
                <li><a href="rutas_administrador.php"><span aria-hidden="true">🚌</span> RUTAS: <span>ver detalles</span></a></li>
                <li><a href="paradas_administrador.php"><span aria-hidden="true">📍</span> PARADAS: <span>ver detalles</span></a></li>
                <li><a href="buses_administrador.php"><span aria-hidden="true">👀</span> BUSES: <span>ver detalles</span></a></li>
                <li><a href="conductor_administrador.php"><span aria-hidden="true">🧑‍✈️</span> CONDUCTOR: <span>ver detalles</span></a></li>
                <li><a href="estudiante_administrador.php"><span aria-hidden="true">🎓</span> ESTUDIANTE: <span>ver detalles</span></a></li>
                <li><a href="facturas_administrador.php"><span aria-hidden="true">🧾</span> FACTURAS: <span>ver detalles</span></a></li>
            </ul>
        </nav>
    </main>

    <footer>
        <h2>IG: @GRUT_updates</h2>
        <h2>FB: @GRUT_updates</h2>
        <h2>WAPP: +503 0000-0000</h2>
        <h2>X: @GRUT_updates</h2>
    </footer>

</body>
</html>
