<?php
require_once __DIR__ . '/../config/auth.php';
requerirRol(['OPERADOR', 'ADMINISTRADOR']);
$pdo = getConexion();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $telefono = preg_replace('/\D/', '', $_POST['telefono'] ?? '');
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $msg = 'Sesión expirada.';
    } elseif ($nombre === '') {
        $msg = 'El nombre es obligatorio.';
    } else {
        $pdo->prepare('INSERT INTO clientes (nombre, telefono) VALUES (?, ?)')
            ->execute([$nombre, $telefono ?: null]);
        $msg = 'Cliente agregado.';
    }
}
$clientes = $pdo->query('SELECT nombre, telefono FROM clientes ORDER BY nombre')->fetchAll();

$titulo = 'Clientes';
require __DIR__ . '/../includes/header.php';
?>
<h3 class="fw-bold mb-3">Clientes</h3>
<?php if ($msg): ?><div class="alert alert-warning py-2"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card card-gd"><div class="card-header">Nuevo cliente</div>
      <form method="post" class="card-body">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" placeholder="3001234567"></div>
        <button class="btn btn-gd w-100">Guardar</button>
      </form></div>
  </div>
  <div class="col-lg-8">
    <div class="card card-gd"><div class="table-responsive"><table class="table mb-0">
      <thead><tr><th>Nombre</th><th>Teléfono</th></tr></thead><tbody>
      <?php foreach ($clientes as $c): ?><tr><td><?= e($c['nombre']) ?></td><td><?= e($c['telefono'] ?? '—') ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>