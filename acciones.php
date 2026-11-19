<?php
// Este archivo procesa las acciones que realiza el administrador sobre el inventario.

// Cargamos las funciones del sistema y verificamos que el usuario haya iniciado sesión.
require_once __DIR__ . '/funciones.php';
requiere_login();

// Solo permitimos solicitudes POST para modificar la información.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir();
}

// Verificamos la seguridad del formulario y conectamos con la base de datos.
verificar_csrf();
require_once __DIR__ . '/conexion.php';

// Obtenemos la acción solicitada y el ID del producto.
$accion = $_POST['accion'] ?? '';
$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
$id = ($id !== false && $id > 0) ? $id : 0;

// Función para mostrar mensajes de error y regresar al inventario.
// También conserva los datos del formulario cuando ocurre un error.
function mensaje_error(string $mensaje, bool $duplicado = false, array $datos = []): void
{
    $_SESSION['error_inventario'] = $mensaje;
    $_SESSION['error_duplicado'] = $duplicado;

    if ($datos) $_SESSION['formulario_anterior'] = $datos;

    redirigir();
}

try {

    // REGISTRAR O ACTUALIZAR PRODUCTOS
    if ($accion === 'guardar') {

        // Recibimos los datos del formulario y eliminamos espacios innecesarios.
        $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $marca = trim((string)($_POST['marca'] ?? ''));
        $categoria = trim((string)($_POST['categoria'] ?? ''));
        $precio = trim((string)($_POST['precio'] ?? ''));
        $stock = trim((string)($_POST['stock'] ?? '')));

        // Guardamos los datos para recuperarlos si ocurre algún error.
        $datos = compact('id', 'codigo', 'nombre', 'marca', 'categoria', 'precio', 'stock');

        // Comprobamos que todos los campos estén completos.
        if (in_array('', [$codigo, $nombre, $marca, $categoria, $precio, $stock], true)) {
            mensaje_error('Todos los campos son obligatorios.', false, $datos);
        }

        // Validamos el formato del código SKU.
        // Debe tener entre 3 y 30 caracteres: letras, números o guiones.
        if (!preg_match('/^[A-Z0-9-]{3,30}$/D', $codigo)) {
            mensaje_error('El código debe tener de 3 a 30 caracteres: letras, números o guiones.', false, $datos);
        }

        // Validamos la longitud del nombre y de la marca.
        if (strlen($nombre) < 3 || strlen($nombre) > 120 || strlen($marca) > 60) {
            mensaje_error('Revisa el nombre (3 a 120 caracteres) y la marca (máximo 60 caracteres).', false, $datos);
        }

        // Comprobamos que la categoría exista entre las opciones del sistema.
        if (!in_array($categoria, categorias(), true)) {
            mensaje_error('Selecciona una categoría válida.', false, $datos);
        }

        // Validamos que el precio sea correcto y tenga máximo dos decimales.
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $precio) || (float)$precio < 1000 || (float)$precio > 99999999.99) {
            mensaje_error('El precio debe ser un valor en pesos desde $1.000, con máximo dos decimales.', false, $datos);
        }

        // Comprobamos que el stock sea un número entero no negativo.
        if (!preg_match('/^\d+$/D', $stock) || (float)$stock > 2147483647) {
            mensaje_error('La cantidad debe ser un número entero mayor o igual a cero.', false, $datos);
        }

        // Verificamos que no exista otro producto con el mismo código SKU.
        // Si estamos editando, no contamos el producto actual.
        $stmt = $pdo->prepare('SELECT id FROM productos WHERE codigo = ? AND id <> ? LIMIT 1');
        $stmt->execute([$codigo, $id]);

        if ($stmt->fetchColumn() !== false) {
            mensaje_error('Ya existe un producto registrado con este código. Consulta el inventario para actualizar sus existencias.', true, $datos);
        }

        // Si el producto tiene ID, actualizamos su información.
        if ($id > 0) {

            // Comprobamos que el producto exista.
            $existe = $pdo->prepare('SELECT id FROM productos WHERE id = ?');
            $existe->execute([$id]);

            if (!$existe->fetchColumn()) mensaje_error('El producto que intentas editar no existe.', false, $datos);

            // Actualizamos los datos en MySQL.
            $stmt = $pdo->prepare('UPDATE productos SET codigo=?, nombre=?, marca=?, categoria=?, precio=?, stock=? WHERE id=?');
            $stmt->execute([$codigo, $nombre, $marca, $categoria, $precio, (int)$stock, $id]);

            $_SESSION['ok_inventario'] = 'Producto actualizado correctamente.';

        } else {

            // Si no tiene ID, registramos un producto nuevo.
            $stmt = $pdo->prepare('INSERT INTO productos (codigo,nombre,marca,categoria,precio,stock) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$codigo, $nombre, $marca, $categoria, $precio, (int)$stock]);

            $_SESSION['ok_inventario'] = 'Producto agregado correctamente.';
        }

        redirigir();
    }

    // Para las siguientes acciones necesitamos un ID válido.
    if ($id === 0) mensaje_error('Selecciona un producto válido.');

    // ELIMINAR PRODUCTO
    if ($accion === 'eliminar') {

        // Eliminamos el producto de la base de datos utilizando su ID.
        $stmt = $pdo->prepare('DELETE FROM productos WHERE id = ?');
        $stmt->execute([$id]);

        // Mostramos un mensaje según el resultado de la operación.
        $_SESSION[$stmt->rowCount() > 0 ? 'ok_inventario' : 'error_inventario'] =
            $stmt->rowCount() > 0 ? 'Producto eliminado correctamente.' : 'El producto ya no existe.';

        redirigir();
    }

    // AUMENTAR EXISTENCIAS
    if ($accion === 'sumar_stock') {

        // Agregamos una unidad al stock sin superar el valor máximo permitido.
        $stmt = $pdo->prepare('UPDATE productos SET stock = stock + 1 WHERE id = ? AND stock < 2147483647');
        $stmt->execute([$id]);

        $_SESSION[$stmt->rowCount() > 0 ? 'ok_inventario' : 'error_inventario'] =
            $stmt->rowCount() > 0 ? 'Se agregó una unidad al inventario.' : 'No se pudo aumentar el stock de este producto.';

        redirigir();
    }

    // DISMINUIR EXISTENCIAS
    if ($accion === 'restar_stock') {

        // Descontamos una unidad únicamente si el producto tiene existencias.
        $stmt = $pdo->prepare('UPDATE productos SET stock = stock - 1 WHERE id = ? AND stock > 0');
        $stmt->execute([$id]);

        $_SESSION[$stmt->rowCount() > 0 ? 'ok_inventario' : 'error_inventario'] =
            $stmt->rowCount() > 0 ? 'Se descontó una unidad del inventario.' : 'No se puede disminuir el stock: está agotado o el producto no existe.';

        redirigir();
    }

    // Mostramos un error si la acción solicitada no existe.
    mensaje_error('La acción solicitada no está disponible.');

} catch (PDOException $e) {

    // Registramos el error de MySQL para facilitar su revisión.
    error_log('TecniStore error en inventario: ' . $e->getMessage());

    // Si el error corresponde a un SKU duplicado, mostramos un aviso específico.
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        mensaje_error('Ya existe un producto registrado con este código. Consulta el inventario para actualizar sus existencias.', true, $_POST);
    }

    // Para otros errores, mostramos un mensaje general al administrador.
    mensaje_error('No se pudo completar la operación. Verifica los datos e inténtalo nuevamente.', false, $accion === 'guardar' ? $_POST : []);
}
