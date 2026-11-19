
<?php
// Este archivo establece la conexión entre TecniStore y la base de datos MySQL.

// Indicamos dónde se encuentra el archivo con los datos de conexión.
$rutaConfiguracion = __DIR__ . '/config.local.php';

// Verificamos que el archivo de configuración exista.
if (!is_file($rutaConfiguracion)) {
    http_response_code(500);
    exit('Falta config.local.php. Verifica la configuración de conexión a MySQL.');
}

// Cargamos los datos del servidor, la base de datos, el usuario y la contraseña.
$config = require $rutaConfiguracion;

try {
    // Establecemos la conexión con MySQL mediante PDO.
    $pdo = new PDO(
        "mysql:host={$config['servidor']};dbname={$config['baseDatos']};charset=utf8mb4",
        $config['usuario'],
        $config['dbClave'],
        [
            // Configuramos cómo se manejan los errores y los resultados de las consultas.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {

    // Si ocurre un error, lo registramos y mostramos un mensaje general.
    error_log('TecniStore: no fue posible conectar con MySQL. ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa MySQL y config.local.php.');
}
