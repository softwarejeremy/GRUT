<?php
require_once("php/verificar_sesion.php");
require_once("php/conexion.php");
if($_SESSION["rol"] != "Administrador"){
    header("Location: iniciar_sesion.html");
    exit;
}
$stmt = $conexion->prepare("SELECT u.nombre, u.apellido, e.centro_educativo, e.curso_educacion, u.correo, u.telefono FROM estudiante e INNER JOIN usuario u ON u.id_usuario = e.id_usuario ORDER BY e.id_estudiante");
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes | GRUT</title>
    <script src="scripts/preferencias.js"></script>
    <link rel="stylesheet" href="css/estilos_globales.css">
    <link rel="stylesheet" href="css/preferencias.css">
</head>
<body class="page-estudiantes">

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
            <h2><span aria-hidden="true">🎓</span> Estudiantes</h2>

            <div class="panel-actions">
                <button class="btn-add">+ Nuevo estudiante</button>
                <button class="btn-remove">- Borrar estudiante</button>
            </div>

            <div class="table-container" role="region" aria-label="Tabla de estudiantes" tabindex="0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Centro educativo</th>
                            <th>Curso de educación</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()){ ?>
                        <tr>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["nombre"]); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["apellido"]); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["centro_educativo"] ?? ""); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["curso_educacion"] ?? ""); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["correo"]); ?>" readonly><button class="edit-btn">✏️</button></td>
                            <td><input type="text" value="<?php echo htmlspecialchars($fila["telefono"] ?? ""); ?>" readonly><button class="edit-btn">✏️</button></td>
                        </tr>
                        <?php } ?>
                        <?php if($resultado->num_rows === 0){ ?>
                        <tr><td colspan="6">No hay estudiantes registrados</td></tr>
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
