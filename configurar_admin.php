<?php
// Este archivo permite crear la cuenta inicial del administrador de TecniStore.
// Solo funciona desde el computador local y cuando no existe un administrador.

require_once __DIR__ . '/funciones.php';

// Verificamos que se acceda desde localhost.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Este formulario solo está disponible desde el computador local.');
}

// Conectamos con MySQL y comprobamos si ya existe un administrador.
require_once __DIR__ . '/conexion.php';

$total = (int)$pdo->query('SELECT COUNT(*) FROM administradores')->fetchColumn();
$yaExiste = $total > 0;
$error = '';
$creado = false;

// Procesamos el formulario únicamente si todavía no existe un administrador.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$yaExiste) {
    verificar_csrf();

    // Recibimos el correo, la contraseña y su confirmación.
    $correo = trim((string)($_POST['correo'] ?? ''));
    $clave = (string)($_POST['clave'] ?? '');
    $confirmar = (string)($_POST['confirmar'] ?? '');

    // Validamos el correo, la longitud de la contraseña y su confirmación.
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150) {
        $error = 'Escribe un correo electrónico válido.';
    } elseif (strlen($clave) < 10) {
        $error = 'La contraseña debe tener al menos 10 caracteres.';
    } elseif ($clave !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            // Guardamos el administrador en MySQL.
            // La contraseña se almacena como un hash para protegerla.
            $stmt = $pdo->prepare('INSERT INTO administradores (correo, contrasena_hash) VALUES (?, ?)');
            $stmt->execute([$correo, password_hash($clave, PASSWORD_DEFAULT)]);

            $creado = true;
            $yaExiste = true;

        } catch (PDOException $e) {
            // Registramos el error y mostramos un mensaje general.
            error_log('TecniStore error crear admin: ' . $e->getMessage());
            $error = 'No se pudo crear la cuenta. Revisa la base de datos.';
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración inicial | TecniStore</title>

    <!-- Cargamos Bootstrap y los estilos personalizados. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>

<body class="login-fondo">
<main class="container d-flex align-items-center justify-content-center min-vh-100 py-4">
    <section class="card tarjeta shadow-sm acceso-tarjeta w-100">
        <div class="card-body p-4">

            <h1 class="h4 fw-bold azul-oscuro">Configuración inicial de TecniStore</h1>

            <!-- Mostramos un mensaje cuando la cuenta se crea correctamente. -->
            <?php if ($creado): ?>
                <div class="alert alert-success">
                    Cuenta creada correctamente. Ya puedes iniciar sesión.
                </div>
                <p><strong>Elimina el archivo configurar_admin.php de la carpeta del proyecto.</strong></p>
                <a class="btn btn-primary" href="login.php">Ir al inicio de sesión</a>

            <!-- Si ya existe un administrador, no permitimos crear otro. -->
            <?php elseif ($yaExiste): ?>
                <div class="alert alert-info">
                    Ya existe una cuenta de administrador. No se permiten registros adicionales.
                </div>
                <p>Si todavía conservas este archivo, elimínalo de la carpeta del proyecto.</p>
                <a class="btn btn-primary" href="login.php">Ir al inicio de sesión</a>

            <!-- Si no existe un administrador, mostramos el formulario de registro. -->
            <?php else: ?>
                <p class="text-secondary small">
                    Este formulario solo funciona en localhost y cuando no existe ningún administrador.
                </p>

                <!-- Mostramos un mensaje si los datos ingresados no son válidos. -->
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?= escapar($error) ?></div>
                <?php endif; ?>

                <!-- Formulario para crear la cuenta inicial. -->
                <form method="POST" action="configurar_admin.php">

                    <!-- Token de seguridad para validar el envío del formulario. -->
                    <input type="hidden" name="csrf_token" value="<?= escapar(token_csrf()) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="correo">Correo electrónico</label>
                        <input class="form-control" type="email" name="correo" id="correo"
                               maxlength="150" required autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="clave">Contraseña (mínimo 10 caracteres)</label>
                        <input class="form-control" type="password" name="clave" id="clave"
                               minlength="10" required autocomplete="new-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="confirmar">Confirmar contraseña</label>
                        <input class="form-control" type="password" name="confirmar" id="confirmar"
                               minlength="10" required autocomplete="new-password">
                    </div>

                    <button class="btn btn-primary w-100" type="submit">Crear administrador</button>
                </form>
            <?php endif; ?>

        </div>
    </section>
</main>
</body>
</html>

