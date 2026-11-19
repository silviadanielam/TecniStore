<?php

// Configuración de la conexión de TecniStore con MySQL en Laragon.
// Estos datos se utilizan para acceder a la base de datos local.

return [
    'servidor' => '127.0.0.1',       // Dirección del servidor MySQL.
    'baseDatos' => 'TecniStore_db',  // Nombre de la base de datos.
    'usuario' => 'root',            // Usuario de MySQL.
    'dbClave' => '',                // Contraseña de MySQL (vacía por defecto en Laragon).
];

