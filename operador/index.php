<?php
require_once __DIR__ . '/../config/auth.php';
$u = requerirRol(['OPERADOR', 'ADMINISTRADOR']);
$pdo = getConexion();

$st = $pdo->prepare("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM ventas WHERE usuario_id=? AND DATE(fecha)=CURDATE()");
$st->execute([$u['id']]);
$hoy = $st->fetch();

$titulo = 'Inicio operador';
require __DIR__ . '/../includes/header.php';
?>
<h3 class="fw-bold mb-4">Hola, <?= e($u['nombre']) ?></h3>
<div class="row g-3">
  <div class="col-md-4"><div class="card card-gd p-3"><small class="text-muted">Tus ventas de hoy</small><div class="stat"><?= (int)$hoy['c'] ?></div></div></div>
  <div class="col-md-4"><div class="card card-gd p-3"><small class="text-muted">Total de hoy</small><div class="stat">$<?= number_format($hoy['t'], 0, ',', '.') ?></div></div></div>
  <div class="col-md-4"><div class="card card-gd p-3 justify-content-center text-center">
    <a href="pos.php" class="btn btn-gd btn-lg mb-2"><i class="bi bi-cart3"></i> Abrir punto de venta</a>
    <a href="<?= BASE_URL ?>/cartera/index.php" class="btn btn-outline-gd">Ver cartera</a></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
