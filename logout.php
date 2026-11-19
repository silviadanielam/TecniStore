<?php

// Este archivo permite cerrar la sesión del administrador de TecniStore.

// Cargamos las funciones del sistema y verificamos que exista una sesión activa.
require_once __DIR__ . '/funciones.php';
requiere_login();

// Solo permitimos cerrar sesión mediante una solicitud POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigir();

// Verificamos que la solicitud tenga un token de seguridad válido.
verificar_csrf();

// Eliminamos los datos del administrador guardados en la sesión.
$_SESSION = [];

// Si la sesión utiliza cookies, eliminamos la cookie del navegador.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Finalizamos la sesión del administrador.
session_destroy();

// Redirigimos al usuario a la pantalla de inicio de sesión.
redirigir('login.php');

