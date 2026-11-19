<?php
// Panel principal del sistema de gestión de inventario TecniStore.
// Cargamos las funciones y comprobamos que el administrador haya iniciado sesión.
require_once __DIR__ . '/funciones.php';
requiere_login();

// Conectamos con MySQL para consultar los productos.
require_once __DIR__ . '/conexion.php';

// Recuperamos los mensajes enviados desde acciones.php.
// Se muestran una sola vez y después se eliminan de la sesión.
$error = $_SESSION['error_inventario'] ?? '';
$errorDuplicado = (bool)($_SESSION['error_duplicado'] ?? false);
$ok = $_SESSION['ok_inventario'] ?? '';
$formularioAnterior = $_SESSION['formulario_anterior'] ?? null;
unset($_SESSION['error_inventario'], $_SESSION['error_duplicado'], $_SESSION['ok_inventario'], $_SESSION['formulario_anterior']);

// Preparamos los campos vacíos para registrar un producto nuevo.
$productoEditar = [
    'id' => 0, 'codigo' => '', 'nombre' => '', 'marca' => '',
    'categoria' => 'Accesorios', 'precio' => '', 'stock' => 0
];

// Si se seleccionó Editar, buscamos los datos del producto para llenar el formulario.
if (isset($_GET['editar'])) {
    $idEditar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    if ($idEditar !== false && $idEditar > 0) {
        $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
        $stmt->execute([$idEditar]);
        $encontrado = $stmt->fetch();
        if ($encontrado) $productoEditar = $encontrado;
    }
}
// Si ocurrió un error al guardar, recuperamos los datos ingresados en el formulario.
if (is_array($formularioAnterior)) {
    $productoEditar = array_merge($productoEditar, array_intersect_key($formularioAnterior, $productoEditar));
}

// Consultamos los productos. Si hay una búsqueda, filtramos por SKU, nombre o categoría.
$busqueda = trim((string)($_GET['q'] ?? ''));
if ($busqueda !== '') {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE codigo LIKE ? OR nombre LIKE ? OR categoria LIKE ? ORDER BY id DESC');
    $termino = '%' . $busqueda . '%';
    $stmt->execute([$termino, $termino, $termino]);
} else {
    $stmt = $pdo->query('SELECT * FROM productos ORDER BY id DESC');
}
// Guardamos los productos encontrados para mostrarlos en el panel.
$productos = $stmt->fetchAll();

// Calculamos las unidades y el valor del inventario de los productos mostrados.
$totalUnidades = 0;
$valorInventario = 0;
foreach ($productos as $p) {
    $totalUnidades += (int)$p['stock'];
    $valorInventario += (float)$p['precio'] * (int)$p['stock'];
}
// Generamos el token de seguridad que se incluirá en los formularios.
$token = token_csrf();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de inventario | TecniStore</title>
  <!-- Cargamos Bootstrap y nuestros estilos personalizados. -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="container-xl py-3 py-md-4">
  <!-- Encabezado del panel y opción para cerrar sesión. -->
  <header class="encabezado p-3 p-md-4 mb-4 shadow-sm d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
      <h1 class="h3 fw-bold mb-1">TecniStore - Gestión de inventario</h1>
      <div class="small text-white-50">Panel administrativo</div>
    </div>
    <form method="POST" action="logout.php" class="mb-0">
      <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
      <button class="btn btn-light btn-sm" type="submit">Cerrar sesión</button>
    </form>
  </header>

  <!-- Mostramos mensajes de confirmación o error después de una operación. -->
  <?php if ($ok !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="status">
      <?= escapar($ok) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  <?php endif; ?>
  <?php if ($errorDuplicado): ?>
    <div class="alert alert-danger d-flex align-items-start gap-3" role="alert">
      <span class="icono-alerta-duplicado" aria-hidden="true">!</span>
      <div><strong class="d-block mb-1">Producto ya registrado</strong><?= escapar($error) ?></div>
    </div>
  <?php elseif ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= escapar($error) ?></div>
  <?php endif; ?>

  <!-- Indicadores calculados con los productos visibles, incluso al buscar. -->
  <section class="row row-cols-1 row-cols-md-3 g-3 mb-4" aria-label="Resumen del inventario">
    <div class="col"><div class="indicador bg-white p-3 h-100 shadow-sm">
      <div class="text-secondary small mb-1">Productos listados</div>
      <div class="valor"><?= count($productos) ?></div>
    </div></div>
    <div class="col"><div class="indicador bg-white p-3 h-100 shadow-sm">
      <div class="text-secondary small mb-1">Unidades en stock</div>
      <div class="valor"><?= (int)$totalUnidades ?> uds.</div>
    </div></div>
    <div class="col"><div class="indicador bg-white p-3 h-100 shadow-sm">
      <div class="text-secondary small mb-1">Valor del inventario</div>
      <div class="valor">$<?= number_format($valorInventario, 0, ',', '.') ?></div>
    </div></div>
  </section>

  <div class="row g-4 align-items-start">
    <!-- Formulario para registrar un producto o modificar sus datos. -->
    <section class="col-12 col-xl-4" aria-label="Formulario de productos">
      <div class="card tarjeta shadow-sm"><div class="card-body p-3 p-md-4">
        <h2 class="h5 fw-bold mb-3"><?= (int)$productoEditar['id'] > 0 ? 'Editar producto #' . (int)$productoEditar['id'] : 'Nuevo producto' ?></h2>
        <!-- Enviamos los datos a acciones.php para guardarlos en MySQL. -->
        <form method="POST" action="acciones.php">
          <!-- Enviamos el token y la acción solicitada con el formulario. -->
          <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
          <input type="hidden" name="accion" value="guardar">
          <input type="hidden" name="id" value="<?= (int)$productoEditar['id'] ?>">

          <div class="mb-3">
            <label for="codigo" class="form-label fw-semibold">Código del producto (SKU)</label>
            <input type="text" id="codigo" name="codigo" class="form-control" minlength="3" maxlength="30"
                   pattern="[A-Za-z0-9-]{3,30}" placeholder="Ej.: TEC-001" required
                   value="<?= escapar($productoEditar['codigo']) ?>">
            <div class="form-text">Debe ser único. Utiliza letras, números o guiones.</div>
          </div>
          <div class="mb-3">
            <label for="nombre" class="form-label fw-semibold">Nombre del producto</label>
            <input type="text" id="nombre" name="nombre" class="form-control" minlength="3" maxlength="120" required value="<?= escapar($productoEditar['nombre']) ?>">
          </div>
          <div class="mb-3">
            <label for="marca" class="form-label fw-semibold">Marca</label>
            <input type="text" id="marca" name="marca" class="form-control" maxlength="60" required value="<?= escapar($productoEditar['marca']) ?>">
          </div>
          <div class="mb-3">
            <label for="categoria" class="form-label fw-semibold">Categoría</label>
            <select id="categoria" name="categoria" class="form-select" required>
              <?php foreach (categorias() as $cat): ?>
                <option value="<?= escapar($cat) ?>" <?= $productoEditar['categoria'] === $cat ? 'selected' : '' ?>><?= escapar($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="precio" class="form-label fw-semibold">Precio unitario ($ COP)</label>
            <input type="number" id="precio" name="precio" class="form-control" min="1000" max="99999999.99" step="0.01" required value="<?= escapar($productoEditar['precio']) ?>">
          </div>
          <div class="mb-3">
            <label for="stock" class="form-label fw-semibold">Stock (unidades)</label>
            <input type="number" id="stock" name="stock" class="form-control" min="0" max="2147483647" step="1" required value="<?= escapar($productoEditar['stock']) ?>">
          </div>
          <button type="submit" class="btn btn-primary w-100"><?= (int)$productoEditar['id'] > 0 ? 'Guardar cambios' : 'Agregar al inventario' ?></button>
          <?php if ((int)$productoEditar['id'] > 0): ?>
            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Cancelar edición</a>
          <?php endif; ?>
        </form>
      </div></div>
    </section>

    <!-- Consulta del inventario: búsqueda y listado adaptable a cada pantalla. -->
    <section class="col-12 col-xl-8" aria-label="Lista de productos">
      <div class="card tarjeta shadow-sm"><div class="card-body p-3 p-md-4">
        <h2 class="h5 fw-bold mb-3">Productos del inventario</h2>
        <!-- El formulario GET permite buscar sin modificar la base de datos. -->
        <form method="GET" action="index.php" class="row g-2 mb-3">
          <div class="col-12 col-sm">
            <label for="busqueda" class="visually-hidden">Buscar productos</label>
            <input type="search" id="busqueda" name="q" class="form-control" placeholder="Buscar por código, nombre o categoría..." value="<?= escapar($busqueda) ?>">
          </div>
          <div class="col-12 col-sm-auto d-grid"><button type="submit" class="btn btn-primary">Buscar</button></div>
          <?php if ($busqueda !== ''): ?>
            <div class="col-12 col-sm-auto d-grid"><a href="index.php" class="btn btn-outline-secondary">Limpiar</a></div>
          <?php endif; ?>
        </form>

        <!-- Si no hay resultados, mostramos un aviso en lugar del listado. -->
        <?php if (count($productos) === 0): ?>
          <div class="alert alert-light border mb-0">No se encontraron productos.</div>
        <?php else: ?>
          <!-- En pantallas grandes, mostramos los productos en una tabla. -->
          <div class="table-responsive d-none d-xl-block">
            <table class="table table-hover table-striped align-middle tabla-productos mb-0">
              <thead class="table-light"><tr>
                <th scope="col">Código</th><th scope="col">Producto</th><th scope="col">Marca</th>
                <th scope="col">Categoría</th><th scope="col">Precio</th><th scope="col">Stock</th><th scope="col">Acciones</th>
              </tr></thead>
              <tbody>
                <?php foreach ($productos as $p): ?>
                  <tr>
                    <td class="small fw-semibold text-nowrap"><?= escapar($p['codigo']) ?></td>
                    <td class="fw-semibold nombre-producto"><?= escapar($p['nombre']) ?></td>
                    <td><?= escapar($p['marca']) ?></td>
                    <td><?= escapar($p['categoria']) ?></td>
                    <td>$<?= number_format((float)$p['precio'], 0, ',', '.') ?></td>
                    <td><span class="badge <?= clase_stock((int)$p['stock']) ?>"><?= escapar(texto_stock((int)$p['stock'])) ?></span></td>
                    <!-- Botones para editar, ajustar existencias o eliminar el producto. -->
                    <td><div class="acciones">
                      <a href="index.php?editar=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                      <form method="POST" action="acciones.php">
                        <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                        <input type="hidden" name="accion" value="sumar_stock">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success boton-accion" aria-label="Sumar una unidad a <?= escapar($p['nombre']) ?>">+1</button>
                      </form>
                      <form method="POST" action="acciones.php">
                        <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                        <input type="hidden" name="accion" value="restar_stock">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary boton-accion" aria-label="Restar una unidad a <?= escapar($p['nombre']) ?>" <?= (int)$p['stock'] === 0 ? 'disabled' : '' ?>>-1</button>
                      </form>
                      <form method="POST" action="acciones.php" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                        <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Borrar</button>
                      </form>
                    </div></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- En celulares y pantallas más pequeñas, usamos tarjetas por producto. -->
          <div class="d-xl-none">
            <?php foreach ($productos as $p): ?>
              <article class="border rounded-3 p-3 mb-3 bg-white">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <div class="min-w-0">
                    <div class="small text-secondary">Código: <?= escapar($p['codigo']) ?></div>
                    <h3 class="h6 fw-bold mb-1 nombre-producto"><?= escapar($p['nombre']) ?></h3>
                  </div>
                  <span class="badge <?= clase_stock((int)$p['stock']) ?> text-wrap"><?= escapar(texto_stock((int)$p['stock'])) ?></span>
                </div>
                <dl class="row g-1 mb-3 small">
                  <dt class="col-4 text-secondary fw-normal">Marca</dt><dd class="col-8 mb-0 nombre-producto"><?= escapar($p['marca']) ?></dd>
                  <dt class="col-4 text-secondary fw-normal">Categoría</dt><dd class="col-8 mb-0"><?= escapar($p['categoria']) ?></dd>
                  <dt class="col-4 text-secondary fw-normal">Precio</dt><dd class="col-8 mb-0 fw-bold">$<?= number_format((float)$p['precio'], 0, ',', '.') ?></dd>
                </dl>
                <div class="d-flex flex-wrap gap-2">
                  <a href="index.php?editar=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                  <form method="POST" action="acciones.php">
                    <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                    <input type="hidden" name="accion" value="sumar_stock">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-success">+1</button>
                  </form>
                  <form method="POST" action="acciones.php">
                    <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                    <input type="hidden" name="accion" value="restar_stock">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" <?= (int)$p['stock'] === 0 ? 'disabled' : '' ?>>-1</button>
                  </form>
                  <form method="POST" action="acciones.php" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                    <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Borrar</button>
                  </form>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div></div>
    </section>
  </div>
  <footer class="text-center text-secondary small py-4">TecniStore - Proyecto académico de gestión de inventario</footer>
</main>
<!-- Cargamos los componentes interactivos de Bootstrap, como el cierre de alertas. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
