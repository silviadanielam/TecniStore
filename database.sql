-- Base de datos del sistema web TecniStore.
-- Este archivo crea la base de datos y las tablas necesarias para su funcionamiento.

-- Creamos la base de datos si todavía no existe.
-- Utilizamos utf8mb4 para permitir caracteres especiales y tildes.
CREATE DATABASE IF NOT EXISTS `TecniStore_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Seleccionamos la base de datos donde se crearán las tablas.
USE `TecniStore_db`;

-- TABLA DE PRODUCTOS
-- Almacena la información de los productos y sus existencias.
CREATE TABLE IF NOT EXISTS `productos` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `codigo` VARCHAR(30) NOT NULL,
    `nombre` VARCHAR(120) NOT NULL,
    `marca` VARCHAR(60) NOT NULL,
    `categoria` VARCHAR(60) NOT NULL,
    `precio` DECIMAL(10,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- El ID identifica cada producto y el código SKU no puede repetirse.
    PRIMARY KEY (`id`),
    CONSTRAINT `uk_productos_codigo` UNIQUE (`codigo`),

    -- Evitamos registrar precios menores a $1.000 o existencias negativas.
    CONSTRAINT `chk_productos_precio` CHECK (`precio` >= 1000),
    CONSTRAINT `chk_productos_stock` CHECK (`stock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- TABLA DE ADMINISTRADORES
-- Almacena los datos necesarios para iniciar sesión en TecniStore.
-- La contraseña se guarda como un hash generado desde PHP.
CREATE TABLE IF NOT EXISTS `administradores` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `correo` VARCHAR(150) NOT NULL,
    `contrasena_hash` VARCHAR(255) NOT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Cada administrador tiene un ID y el correo no puede repetirse.
    PRIMARY KEY (`id`),
    CONSTRAINT `uk_administradores_correo` UNIQUE (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Las tablas se crean vacías.
-- La cuenta inicial se configura mediante configurar_admin.php
-- o crear_admin_cli.php.
