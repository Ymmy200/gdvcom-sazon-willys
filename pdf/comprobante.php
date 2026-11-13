<?php
// Comprobante imprimible. Usa "Imprimir > Guardar como PDF" del navegador.
require_once __DIR__ . '/../config/auth.php';
requerirRol(['OPERADOR', 'ADMINISTRADOR']);
$pdo = getConexion();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT v.*, c.nombre AS cliente, u.nombre AS vendedor
                     FROM ventas v LEFT JOIN clientes c ON c.id=v.cliente_id JOIN usuarios u ON u.id=v.usuario_id WHERE v.id=?');
$st->execute([$id]);
$v = $st->fetch();
if (!$v) { http_response_code(404); exit('Venta no encontrada.'); }

$st = $pdo->prepare('SELECT d.*, p.nombre FROM detalle_ventas d JOIN productos p ON p.id=d.producto_id WHERE d.venta_id=?');
$st->execute([$id]);
$det = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Comprobante #<?= $id ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{font-family:system-ui,sans-serif;max-width:420px;margin:2rem auto;} @media print{.no-print{display:none}}</style></head>
<body>
<div class="text-center mb-3"><h4 class="mb-0">Sazón Willy</h4><small>Comprobante de venta #<?= $id ?></small></div>
<p class="small mb-2">Fecha: <?= e($v['fecha']) ?><br>Cliente: <?= e($v['cliente'] ?? 'Mostrador') ?><br>Atendió: <?= e($v['vendedor']) ?><br>Pago: <?= e($v['tipo_pago']) ?></p>
<table class="table table-sm"><thead><tr><th>Producto</th><th>Cant.</th><th class="text-end">Subtotal</th></tr></thead><tbody>
<?php foreach ($det as $d): ?><tr><td><?= e($d['nombre']) ?></td><td><?= (int)$d['cantidad'] ?></td><td class="text-end">$<?= number_format($d['subtotal'], 0, ',', '.') ?></td></tr><?php endforeach; ?>
</tbody><tfoot><tr><th colspan="2">Total</th><th class="text-end">$<?= number_format($v['total'], 0, ',', '.') ?></th></tr>
<?php if ($v['tipo_pago'] === 'CREDITO'): ?><tr><td colspan="2">Saldo pendiente</td><td class="text-end">$<?= number_format($v['saldo'], 0, ',', '.') ?></td></tr><?php endif; ?></tfoot></table>
<p class="text-center small">¡Gracias por tu compra!</p>
<button class="btn btn-dark w-100 no-print" onclick="window.print()">Imprimir / Guardar PDF</button>
</body></html>
