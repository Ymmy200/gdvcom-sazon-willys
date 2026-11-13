<?php
require_once __DIR__ . '/../config/auth.php';
requerirRol(['ADMINISTRADOR']);
$pdo = getConexion();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $msg = 'Sesión expirada.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);
        if ($accion === 'guardar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $precio = (float)($_POST['precio'] ?? 0);
            $stock  = (int)($_POST['stock'] ?? 0);
            $min    = (int)($_POST['stock_minimo'] ?? 0);
            $cat    = (int)($_POST['categoria_id'] ?? 0) ?: null;
            if ($nombre === '' || $precio <= 0 || $stock < 0 || $min < 0) {
                $msg = 'Revisa los datos: nombre, precio mayor a 0 y stock sin negativos.';
            } elseif ($id > 0) {
                $pdo->prepare('UPDATE productos SET nombre=?, precio=?, stock=?, stock_minimo=?, categoria_id=? WHERE id=?')
                    ->execute([$nombre, $precio, $stock, $min, $cat, $id]);
                $msg = 'Producto actualizado.';
            } else {
                $pdo->prepare('INSERT INTO productos (nombre, precio, stock, stock_minimo, categoria_id) VALUES (?,?,?,?,?)')
                    ->execute([$nombre, $precio, $stock, $min, $cat]);
                $msg = 'Producto creado.';
            }
        } elseif ($accion === 'estado' && $id > 0) {
            $pdo->prepare('UPDATE productos SET activo = 1 - activo WHERE id=?')->execute([$id]);
            $msg = 'Estado cambiado.';
        }
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM productos WHERE id=?');
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch() ?: null;
}
$cats      = $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();
$productos = $pdo->query('SELECT p.*, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON c.id=p.categoria_id ORDER BY p.nombre')->fetchAll();

$titulo = 'Productos';
require __DIR__ . '/../includes/header.php';
?>
<h3 class="fw-bold mb-3">Productos e inventario</h3>
<?php if ($msg): ?><div class="alert alert-warning py-2"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card card-gd"><div class="card-header"><?= $editar ? 'Editar producto' : 'Nuevo producto' ?></div>
      <form method="post" class="card-body">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required value="<?= e($editar['nombre'] ?? '') ?>"></div>
        <div class="mb-2"><label class="form-label">Categoría</label>
          <select name="categoria_id" class="form-select"><option value="0">Sin categoría</option>
            <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($editar['categoria_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="row">
          <div class="col-12 mb-2"><label class="form-label">Precio</label><input type="number" step="100" min="1" name="precio" class="form-control" required value="<?= e($editar['precio'] ?? '') ?>"></div>
          <div class="col-6 mb-2"><label class="form-label">Stock</label><input type="number" min="0" name="stock" class="form-control" required value="<?= e($editar['stock'] ?? '0') ?>"></div>
          <div class="col-6 mb-2"><label class="form-label">Stock mínimo</label><input type="number" min="0" name="stock_minimo" class="form-control" required value="<?= e($editar['stock_minimo'] ?? '5') ?>"></div>
        </div>
        <button class="btn btn-gd w-100 mt-2">Guardar</button>
        <?php if ($editar): ?><a href="productos.php" class="btn btn-link w-100">Cancelar</a><?php endif; ?>
      </form></div>
  </div>
  <div class="col-lg-8">
    <div class="card card-gd"><div class="table-responsive"><table class="table mb-0 align-middle">
      <thead><tr><th>Producto</th><th>Categoría</th><th class="text-end">Precio</th><th class="text-center">Stock</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($productos as $p): ?>
        <tr class="<?= $p['activo'] ? '' : 'text-muted' ?>">
          <td><?= e($p['nombre']) ?></td><td><?= e($p['categoria'] ?? '—') ?></td>
          <td class="text-end">$<?= number_format($p['precio'], 0, ',', '.') ?></td>
          <td class="text-center"><span class="badge text-bg-<?= $p['stock'] <= $p['stock_minimo'] ? 'danger' : 'success' ?>"><?= (int)$p['stock'] ?></span></td>
          <td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td>
          <td class="text-nowrap">
            <a class="btn btn-sm btn-outline-gd" href="?editar=<?= $p['id'] ?>">Editar</a>
            <form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary"><?= $p['activo'] ? 'Desactivar' : 'Activar' ?></button></form>
          </td></tr>
      <?php endforeach; ?>
      </tbody></table></div></div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
