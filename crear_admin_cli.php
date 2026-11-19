<?php

// Este archivo permite crear la cuenta inicial del administrador
// de TecniStore desde la terminal del computador.

// Verificamos que el archivo se ejecute desde una terminal y no desde el navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este archivo solo se puede ejecutar desde una terminal.');
}

// Conectamos con la base de datos MySQL.
require_once __DIR__ . '/conexion.php';

// Comprobamos si ya existe una cuenta de administrador.
// Si existe, no permitimos crear otra.
if ((int)$pdo->query('SELECT COUNT(*) FROM administradores')->fetchColumn() > 0) {
    exit("Ya existe una cuenta de administrador.\n");
}

// Mostramos las instrucciones para crear la cuenta.
fwrite(STDOUT, "TecniStore - Configuración de administrador (uso local)\n");

// Solicitamos el correo electrónico.
fwrite(STDOUT, "Correo electrónico: ");
$correo = trim((string)fgets(STDIN));

// Solicitamos la contraseña del administrador.
fwrite(STDOUT, "Contraseña nueva (mínimo 10 caracteres, se verá al escribir): ");
$clave = trim((string)fgets(STDIN));

// Validamos que el correo sea correcto y que la contraseña
// tenga mínimo 10 caracteres.
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150 || strlen($clave) < 10) {
    exit("Datos no válidos. Revisa el correo y la longitud de la contraseña.\n");
}

try {
    // Guardamos el administrador en MySQL mediante una consulta preparada.
    // La contraseña se almacena como un hash para protegerla.
    $stmt = $pdo->prepare('INSERT INTO administradores (correo, contrasena_hash) VALUES (?, ?)');
    $stmt->execute([$correo, password_hash($clave, PASSWORD_DEFAULT)]);

    // Confirmamos que la cuenta fue creada correctamente.
    fwrite(STDOUT, "Cuenta creada. Ya puedes iniciar sesión desde login.php.\n");

} catch (PDOException $e) {

    // Si ocurre un error, lo registramos y mostramos un mensaje general.
    error_log('TecniStore error crear admin CLI: ' . $e->getMessage());
    exit("No se pudo crear la cuenta.\n");
}

