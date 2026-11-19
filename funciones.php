<?php

// Funciones compartidas del sistema TecniStore.
// Se utilizan para gestionar sesiones, validar formularios y mostrar información del inventario.

// CONFIGURACIÓN DE LA SESIÓN
// Iniciamos una sesión si todavía no existe una activa.
if (session_status() !== PHP_SESSION_ACTIVE) {

    // Configuramos la sesión y las opciones de seguridad de las cookies.
    ini_set('session.use_strict_mode', '1');
    session_name('TECNISTORESESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


// PROTECCIÓN DE DATOS
// Convierte caracteres especiales para mostrar información de forma segura en HTML.
function escapar($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


// GENERACIÓN DEL TOKEN CSRF
// Generamos un código de seguridad para proteger los formularios.
function token_csrf(): string
{
    // Si todavía no existe un token, creamos uno aleatorio.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


// VALIDACIÓN DEL TOKEN CSRF
// Comprobamos que el formulario tenga un token de seguridad válido.
function verificar_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    // Si el token no es válido, rechazamos la solicitud.
    if (!is_string($token) || !hash_equals(token_csrf(), $token)) {
        http_response_code(403);
        exit('La solicitud no es válida. Regresa a la página y vuelve a intentarlo.');
    }
}


// VERIFICACIÓN DEL INICIO DE SESIÓN
// Permite acceder al sistema únicamente a un administrador autenticado.
function requiere_login(): void
{
    $id = (int)($_SESSION['administrador_id'] ?? 0);

    // Si no existe una sesión de administrador, enviamos al inicio de sesión.
    if ($id <= 0) {
        header('Location: login.php');
        exit;
    }

    // Cerramos el acceso si han pasado más de 30 minutos sin actividad.
    if (time() - (int)($_SESSION['ultima_actividad'] ?? 0) > 1800) {
        unset($_SESSION['administrador_id'], $_SESSION['administrador_correo'], $_SESSION['ultima_actividad']);
        header('Location: login.php?expirada=1');
        exit;
    }

    // Actualizamos la hora de la última actividad.
    $_SESSION['ultima_actividad'] = time();
}


// CATEGORÍAS DE PRODUCTOS
// Devuelve las categorías disponibles para registrar productos.
function categorias(): array
{
    return ['Computadores', 'Monitores', 'Accesorios', 'Almacenamiento', 'Audio', 'Redes'];
}


// COLOR DEL ESTADO DEL INVENTARIO
// Asigna una clase de Bootstrap según la cantidad disponible.
function clase_stock(int $stock): string
{
    if ($stock === 0) return 'text-bg-danger';  // Rojo: agotado.
    if ($stock <= 3) return 'text-bg-warning';   // Naranja: inventario bajo.
    return 'text-bg-success';                   // Verde: disponible.
}


// TEXTO DEL ESTADO DEL INVENTARIO
// Muestra un mensaje según la cantidad de unidades disponibles.
function texto_stock(int $stock): string
{
    if ($stock === 0) return 'Agotado';
    if ($stock <= 3) return '¡Últimas ' . $stock . ' uds!';
    return $stock . ' disp.';
}


// REDIRECCIÓN ENTRE PÁGINAS
// Envía al usuario a otra página del sistema.
// Si no indicamos una dirección, regresa al panel principal.
function redirigir(string $url = 'index.php'): void
{
    header('Location: ' . $url);
    exit;
}
