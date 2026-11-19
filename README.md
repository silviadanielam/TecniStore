# TecniStore – Sistema Web de Gestión de Inventario

TecniStore es un sistema web de gestión de inventario desarrollado con **PHP, MySQL, HTML5, CSS3 y Bootstrap 5**, como proyecto de la asignatura Electiva: Desarrollo de Aplicaciones Web.

Está diseñado para una tienda ficticia de productos tecnológicos y permite administrar productos, controlar existencias y consultar información del inventario mediante un panel con acceso restringido.

## Aplicación publicada

**https://tecnistore.infinityfree.io/login.php**

La aplicación se encuentra desplegada en InfinityFree. Para ingresar, se utilizan las credenciales de demostración proporcionadas en el documento académico.

## Funcionalidades

- Inicio y cierre de sesión del administrador.
- Registro, consulta, edición y eliminación de productos.
- Gestión de código SKU único, nombre, marca, categoría, precio y stock.
- Búsqueda de productos por código, nombre o categoría.
- Incremento y disminución de existencias mediante botones +1 y -1.
- Alertas visuales de productos disponibles, con inventario bajo o agotados.
- Indicadores de productos, unidades disponibles y valor del inventario.
- Validaciones de datos y mensajes de confirmación o error.
- Diseño responsive para computadores y dispositivos móviles.

## Tecnologías utilizadas

- **Frontend:** HTML5, CSS3 y Bootstrap 5.
- **Backend:** PHP.
- **Base de datos:** MySQL con conexión PDO.
- **Entorno local:** Laragon y HeidiSQL.
- **Despliegue web:** InfinityFree.
- **Control de versiones:** Git y GitHub.

## Archivos principales

- `index.php`: panel de inventario, formularios, búsqueda e indicadores.
- `acciones.php`: gestión de productos y existencias.
- `login.php` y `logout.php`: autenticación y cierre de sesión.
- `funciones.php`: sesiones, validaciones y funciones compartidas.
- `conexion.php` y `config.local.php`: conexión a MySQL en Laragon.
- `configurar_admin.php`: creación inicial del administrador desde localhost.
- `crear_admin_cli.php`: alternativa para crear el administrador desde la terminal.
- `database.sql`: estructura de las tablas `productos` y `administradores`.
- `assets/css/estilos.css`: estilos personalizados de la aplicación.

## Instalación y ejecución local

1. Instalar Laragon e iniciar Apache y MySQL.
2. Descargar el repositorio y ubicar la carpeta del proyecto en `C:\laragon\www\tecnistore`.
3. Ejecutar `database.sql` mediante HeidiSQL o phpMyAdmin para crear la base de datos y sus tablas.
4. Verificar los datos de conexión en `config.local.php`. El archivo está configurado para Laragon con usuario `root` y contraseña vacía.
5. Abrir `http://localhost/tecnistore/configurar_admin.php` desde el mismo computador.
6. Registrar un correo electrónico y una contraseña de mínimo 10 caracteres. Después de crear la cuenta, eliminar `configurar_admin.php` de la instalación local.
7. Ingresar a `http://localhost/tecnistore/login.php` con las credenciales creadas.

La cuenta de administrador también puede configurarse desde una terminal local mediante `php crear_admin_cli.php`.

La base de datos se entrega sin usuarios ni productos registrados, por lo que cada persona puede crear su propia cuenta y realizar pruebas.

## Seguridad

El sistema utiliza sesiones PHP, contraseñas almacenadas mediante hash, consultas preparadas con PDO, protección CSRF, validación de datos y restricciones para evitar códigos SKU duplicados y existencias negativas.

## Despliegue

TecniStore fue desarrollado inicialmente en Laragon y posteriormente publicado en InfinityFree, mediante la importación de la base de datos MySQL y la carga de los archivos al servidor.

GitHub contiene el código fuente para su consulta y ejecución local, mientras que InfinityFree permite acceder a la aplicación funcionando en Internet.
