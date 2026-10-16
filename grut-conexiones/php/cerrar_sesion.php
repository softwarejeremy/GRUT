<?php
// Tarea 1 (Jeremy): cerrar sesión. Se usa desde el enlace "Cerrar sesión" de configuracion.html.
// Se vacían los datos de la sesión, se destruye y se vuelve a la página de inicio.
session_start();
session_unset();
session_destroy();
header("Location: ../inicio.html");
exit;

?>