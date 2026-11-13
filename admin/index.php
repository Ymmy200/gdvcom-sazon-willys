<?php
require_once __DIR__ . '/../config/auth.php';
requerirRol(['ADMINISTRADOR']);
$pdo = getConexion();

$ventasHoy = $pdo->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha)=CURDATE()")->fetch();
$cartera   = $pdo->query("SELECT COALESCE(SUM(saldo),0) FROM ventas WHERE tipo_pago='CREDITO' AND saldo>0")->fetchColumn();
$bajos     = $pdo->query("SELECT nombre, stock, stock_minimo FROM productos WHERE activo=1 AND stock<=stock_minimo ORDER BY stock")->fetchAll();
$ultimas   = $pdo->query("SELECT v.id, v.fecha, v.total, v.tipo_pago, u.nombre AS vendedor
                          FROM ventas v JOIN usuarios u ON u.id=v.usuario_id ORDER BY v.id DESC LIMIT 8")->fetchAll();

$titulo = 'Panel del administrador';
require __DIR__ . '/../includes/header.php';
?>
<h3 class="fw-bold mb-4">Panel del administrador</h3>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card card-gd p-3"><small class="text-muted">Ventas de hoy</small><div class="stat"><?= (int)$ventasHoy['c'] ?></div></div></div>
  <div class="col-md-4"><div class="card card-gd p-3"><small class="text-muted">Total vendido hoy</small><div class="stat">$<?= number_format($ventasHoy['t'], 0, ',', '.') ?></div></div></div>
  <div class="col-md-4"><div class="card card-gd p-3"><small class="text-muted">Cartera pendiente</small><div class="stat">$<?= number_format($cartera, 0, ',', '.') ?></div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card card-gd"><div class="card-header">Últimas ventas</div>
      <div class="table-responsive"><table class="table mb-0 align-middle">
        <thead><tr><th>#</th><th>Fecha</th><th>Vendedor</th><th>Pago</th><th class="text-end">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($ultimas as $v): ?>
          <tr><td><?= $v['id'] ?></td><td><?= e($v['fecha']) ?></td><td><?= e($v['vendedor']) ?></td>
              <td><span class="badge text-bg-<?= $v['tipo_pago']==='CREDITO'?'warning':'success' ?>"><?= e($v['tipo_pago']) ?></span></td>
              <td class="text-end">$<?= number_format($v['total'], 0, ',', '.') ?></td>
              <td><a class="btn btn-sm btn-outline-gd" href="<?= BASE_URL ?>/pdf/comprobante.php?id=<?= $v['id'] ?>" target="_blank">Ver</a></td></tr>
        <?php endforeach; if (!$ultimas): ?><tr><td colspan="6" class="text-center text-muted py-3">Aún no hay ventas.</td></tr><?php endif; ?>
        </tbody></table></div></div>
  </div>
  <div class="col-lg-5">
    <div class="card card-gd"><div class="card-header"><i class="bi bi-exclamation-triangle text-warning"></i> Stock bajo</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($bajos as $b): ?>
          <li class="list-group-item d-flex justify-content-between"><?= e($b['nombre']) ?><span class="badge text-bg-danger"><?= (int)$b['stock'] ?> uds</span></li>
        <?php endforeach; if (!$bajos): ?><li class="list-group-item text-muted">Todo el inventario está bien.</li><?php endif; ?>
      </ul></div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
