<?php
require_once __DIR__ . '/../config/auth.php';
requerirRol(['OPERADOR', 'ADMINISTRADOR']);
$pdo = getConexion();
$ventas = $pdo->query('SELECT id, fecha, total, tipo_pago FROM ventas ORDER BY id DESC LIMIT 50')->fetchAll();

$titulo = 'Comprobantes';
require __DIR__ . '/../includes/header.php';
?>
<h3 class="fw-bold mb-3">Comprobantes de venta</h3>
<div class="card card-gd"><div class="table-responsive"><table class="table mb-0 align-middle">
  <thead><tr><th>#</th><th>Fecha</th><th>Pago</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
  <?php foreach ($ventas as $v): ?>
    <tr><td><?= $v['id'] ?></td><td><?= e($v['fecha']) ?></td><td><?= e($v['tipo_pago']) ?></td>
        <td class="text-end">$<?= number_format($v['total'], 0, ',', '.') ?></td>
        <td><a class="btn btn-sm btn-outline-gd" target="_blank" href="comprobante.php?id=<?= $v['id'] ?>">Abrir</a></td></tr>
  <?php endforeach; if (!$ventas): ?><tr><td colspan="5" class="text-center text-muted py-3">Sin ventas todavía.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
