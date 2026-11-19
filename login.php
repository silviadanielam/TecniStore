<?php
// Este archivo permite al administrador iniciar sesión en TecniStore.

// Cargamos las funciones del sistema y la configuración de sesiones.
require_once __DIR__ . '/funciones.php';

// Si el administrador ya inició sesión, lo enviamos al panel de inventario.
if (!empty($_SESSION['administrador_id'])) {
    redirigir('index.php');
}

// Variables para mostrar mensajes de error y conservar el correo ingresado.
$error = '';
$correo = '';

// Procesamos los datos cuando el formulario se envía.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificamos que el formulario tenga un token de seguridad válido.
    verificar_csrf();

    // Recibimos el correo y la contraseña.
    // La contraseña se conserva exactamente como fue escrita.
    $correo = trim((string)($_POST['correo'] ?? ''));
    $clave = (string)($_POST['clave'] ?? '');
    $ahora = time();

    // Comprobamos si la cuenta está temporalmente bloqueada por intentos fallidos.
    if ($ahora < (int)($_SESSION['bloqueo_hasta'] ?? 0)) {
        $error = 'Demasiados intentos. Prueba de nuevo dentro de unos minutos.';

    // Validamos que el correo sea correcto y que se haya ingresado una contraseña.
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $clave === '') {
        $error = 'Escribe tu correo y contraseña para continuar.';

    } else {
        try {
            // Conectamos con MySQL y buscamos el administrador por su correo.
            require_once __DIR__ . '/conexion.php';

            $stmt = $pdo->prepare('SELECT id, correo, contrasena_hash FROM administradores WHERE correo = ? LIMIT 1');
            $stmt->execute([$correo]);
            $admin = $stmt->fetch();

            // Comprobamos que el administrador exista y que la contraseña sea correcta.
            if ($admin && password_verify($clave, (string)$admin['contrasena_hash'])) {

                // Renovamos la sesión y guardamos los datos del administrador.
                session_regenerate_id(true);
                $_SESSION['administrador_id'] = (int)$admin['id'];
                $_SESSION['administrador_correo'] = (string)$admin['correo'];
                $_SESSION['ultima_actividad'] = time();

                // Borramos los intentos fallidos y permitimos el acceso al inventario.
                unset($_SESSION['intentos_login'], $_SESSION['bloqueo_hasta']);
                redirigir('index.php');
            }

            // Si las credenciales son incorrectas, aumentamos los intentos fallidos.
            $_SESSION['intentos_login'] = (int)($_SESSION['intentos_login'] ?? 0) + 1;

            // Después de cinco intentos fallidos, bloqueamos el acceso durante cinco minutos.
            if ($_SESSION['intentos_login'] >= 5) {
                $_SESSION['bloqueo_hasta'] = $ahora + 300;
                $_SESSION['intentos_login'] = 0;
                $error = 'Demasiados intentos. Prueba de nuevo dentro de cinco minutos.';
            } else {
                $error = 'El correo o la contraseña no son correctos.';
            }

        } catch (PDOException $e) {

            // Si ocurre un error de MySQL, lo registramos y mostramos un mensaje general.
            error_log('TecniStore error login: ' . $e->getMessage());
            $error = 'No se pudo comprobar el acceso. Revisa que MySQL esté funcionando.';
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso al inventario | TecniStore</title>

    <!-- Cargamos Bootstrap y los estilos personalizados de TecniStore. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>

<body class="login-fondo">

<!-- Contenedor principal de la pantalla de inicio de sesión. -->
<main class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
    <section class="card tarjeta shadow-sm acceso-tarjeta w-100">
        <div class="card-body p-4 p-md-5">

            <!-- Nombre del sistema e instrucciones de acceso. -->
            <div class="text-center mb-4">
                <div class="icono-tienda mb-3" aria-hidden="true">T</div>
                <h1 class="h3 fw-bold azul-oscuro">TecniStore</h1>
                <h2 class="h5 mt-3 mb-1">Acceso al inventario</h2>
                <p class="text-secondary small">Ingresa con tu cuenta de administrador.</p>
            </div>

            <!-- Mostramos un mensaje cuando ocurre un error de autenticación. -->
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= escapar($error) ?></div>
            <?php endif; ?>

            <!-- Avisamos si la sesión anterior terminó por inactividad. -->
            <?php if (isset($_GET['expirada'])): ?>
                <div class="alert alert-warning" role="alert">
                    Tu sesión terminó por inactividad. Inicia sesión nuevamente.
                </div>
            <?php endif; ?>

            <!-- Formulario para ingresar el correo y la contraseña. -->
            <form method="POST" action="login.php" autocomplete="off">

                <!-- Token de seguridad del formulario. -->
                <input type="hidden" name="csrf_token" value="<?= escapar(token_csrf()) ?>">

                <div class="mb-3">
                    <label for="correo" class="form-label fw-semibold">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" class="form-control"
                           autocomplete="off" maxlength="150" required value="<?= escapar($correo) ?>">
                </div>

                <div class="mb-4">
                    <label for="clave" class="form-label fw-semibold">Contraseña</label>

                    <div class="input-group">
                        <input type="password" id="clave" name="clave" class="form-control"
                               autocomplete="new-password" required>

                        <!-- Botón para mostrar u ocultar la contraseña. -->
                        <button type="button" class="btn btn-outline-secondary" id="mostrarClave"
                                aria-controls="clave" aria-pressed="false">Mostrar</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
            </form>

        </div>
    </section>
</main>

<script>
// Obtenemos el botón y el campo de contraseña.
const boton = document.getElementById('mostrarClave');
const campo = document.getElementById('clave');

// Cambiamos entre mostrar y ocultar la contraseña al presionar el botón.
boton.addEventListener('click', () => {
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    boton.textContent = mostrar ? 'Ocultar' : 'Mostrar';
    boton.setAttribute('aria-pressed', String(mostrar));
});
</script>

</body>
</html>
