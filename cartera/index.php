<?php
require_once __DIR__ . '/../config/auth.php';
requerirRol(['OPERADOR', 'ADMINISTRADOR']);
$pdo = getConexion();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ventaId = (int)($_POST['venta_id'] ?? 0);
    $monto   = (float)($_POST['monto'] ?? 0);
    if (!csrfValido($_POST['csrf'] ?? null)) {
        $msg = 'Sesión expirada.';
    } else {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("SELECT saldo FROM ventas WHERE id=? AND tipo_pago='CREDITO' FOR UPDATE");
            $st->execute([$ventaId]);
            $saldo = $st->fetchColumn();
            if ($saldo === false) throw new RuntimeException('Venta no encontrada.');
            if ($monto <= 0 || $monto > $saldo) throw new RuntimeException('El abono debe ser mayor a 0 y no superar el saldo.');
            $pdo->prepare('INSERT INTO abonos (venta_id, monto) VALUES (?,?)')->execute([$ventaId, $monto]);
            $pdo->prepare('UPDATE ventas SET saldo = saldo - ? WHERE id=?')->execute([$monto, $ventaId]);
            $pdo->commit();
            $msg = 'Abono registrado.';
        } catch (RuntimeException $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
        }
    }
}

$deudas = $pdo->query("SELECT v.id, v.fecha, v.total, v.saldo, c.nombre, c.telefono,
                              DATEDIFF(CURDATE(), DATE(v.fecha)) AS dias
                       FROM ventas v JOIN clientes c ON c.id=v.cliente_id
                       WHERE v.tipo_pago='CREDITO' AND v.saldo>0 ORDER BY v.fecha")->fetchAll();

$titulo = 'Cartera';
require __DIR__ . '/../includes/header.php';
?>
<h3 class="fw-bold mb-3">Cartera de clientes</h3>
<?php if ($msg): ?><div class="alert alert-warning py-2"><?= e($msg) ?></div><?php endif; ?>
<div class="card card-gd"><div class="table-responsive"><table class="table mb-0 align-middle">
  <thead><tr><th>Venta</th><th>Cliente</th><th>Días</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th style="width:260px">Abonar</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($deudas as $d): ?>
    <tr>
      <td>#<?= $d['id'] ?></td><td><?= e($d['nombre']) ?></td>
      <td><span class="badge text-bg-<?= $d['dias'] > 15 ? 'danger' : 'secondary' ?>"><?= (int)$d['dias'] ?></span></td>
      <td class="text-end">$<?= number_format($d['total'], 0, ',', '.') ?></td>
      <td class="text-end fw-bold">$<?= number_format($d['saldo'], 0, ',', '.') ?></td>
      <td><form method="post" class="input-group input-group-sm">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="venta_id" value="<?= $d['id'] ?>">
        <input type="number" name="monto" min="1" max="<?= (float)$d['saldo'] ?>" step="100" class="form-control" placeholder="Monto" required>
        <button class="btn btn-gd">Abonar</button></form></td>
      <td><?php if ($d['telefono']): ?><a class="btn btn-sm btn-outline-success" target="_blank"
          href="https://wa.me/57<?= e(preg_replace('/\D/', '', $d['telefono'])) ?>?text=<?= rawurlencode('Hola ' . $d['nombre'] . ', te recordamos un saldo pendiente de $' . number_format($d['saldo'], 0, ',', '.') . ' en Sazón Willy.') ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?></td>
    </tr>
  <?php endforeach; if (!$deudas): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay cuentas pendientes.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
