<?php

session_start();
if(!isset($_SESSION["id_usuario"])){
    header("Location: iniciar_sesion.html");
    exit;
}

?>