<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/funciones.php';

if (!isset($_SESSION['usuario_id'])) {
    redirect(BASE_URL . '/login.php');
}

$usuario = obtenerUsuario($_SESSION['usuario_id']);
if (!$usuario || !$usuario['activo']) {
    session_destroy();
    redirect(BASE_URL . '/login.php');
}

define('USUARIO_ID', $usuario['id']);
define('USUARIO_NOMBRE', $usuario['nombre_completo']);
define('USUARIO_USERNAME', $usuario['username']);
define('USUARIO_ROL', $usuario['id_rol']);
