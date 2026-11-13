<?php
require_once __DIR__ . '/../config/auth.php';
requerirRol(['OPERADOR', 'ADMINISTRADOR']);
$pdo = getConexion();

$productos = $pdo->query('SELECT id, nombre, precio, stock FROM productos WHERE activo=1 ORDER BY nombre')->fetchAll();
$clientes  = $pdo->query('SELECT id, nombre FROM clientes ORDER BY nombre')->fetchAll();

$titulo = 'Punto de venta';
require __DIR__ . '/../includes/header.php';
?>
<h3 class="fw-bold mb-3">Punto de venta</h3>
<div class="row g-3">
  <div class="col-lg-7">
    <input id="buscar" class="form-control mb-3" placeholder="Buscar producto...">
    <div class="row g-3" id="productos">
      <?php foreach ($productos as $p): ?>
        <div class="col-6 col-md-4 item-producto" data-nombre="<?= e(mb_strtolower($p['nombre'])) ?>">
          <div class="card card-gd producto-card h-100 p-3 text-center <?= $p['stock'] < 1 ? 'agotado' : '' ?>"
               data-id="<?= $p['id'] ?>" data-nombre="<?= e($p['nombre']) ?>" data-precio="<?= (float)$p['precio'] ?>" data-stock="<?= (int)$p['stock'] ?>">
            <i class="bi bi-egg-fried fs-1" style="color:var(--gd-ambar-claro)"></i>
            <div class="fw-semibold"><?= e($p['nombre']) ?></div>
            <div class="text-danger fw-bold">$<?= number_format($p['precio'], 0, ',', '.') ?></div>
            <small class="text-muted stock-txt">Stock: <?= (int)$p['stock'] ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card card-gd">
      <div class="card-header"><i class="bi bi-cart3"></i> Carrito</div>
      <div class="card-body">
        <div id="vacio" class="text-muted text-center py-3">Toca un producto para agregarlo.</div>
        <table class="table table-sm align-middle d-none" id="tabla"><tbody id="lineas"></tbody></table>
        <hr>
        <div class="d-flex justify-content-between fs-5 fw-bold mb-3"><span>Total</span><span id="total">$0</span></div>
        <div class="mb-2"><label class="form-label">Cliente</label>
          <select id="cliente" class="form-select">
            <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="mb-3"><label class="form-label">Tipo de pago</label>
          <select id="tipo_pago" class="form-select"><option value="CONTADO">Contado</option><option value="CREDITO">Crédito (cartera)</option></select></div>
        <div id="aviso"></div>
        <button id="cobrar" class="btn btn-gd w-100 py-2" disabled>Registrar venta</button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF = <?= json_encode(csrfToken()) ?>;
const carrito = {}; // id -> {id,nombre,precio,stock,cant}
const fmt = n => '$' + Math.round(n).toLocaleString('es-CO');
const $ = id => document.getElementById(id);

function aviso(msg, tipo) { $('aviso').innerHTML = msg ? `<div class="alert alert-${tipo} py-2">${msg}</div>` : ''; }

function pintar() {
  const filas = Object.values(carrito);
  $('vacio').classList.toggle('d-none', filas.length > 0);
  $('tabla').classList.toggle('d-none', filas.length === 0);
  let total = 0;
  $('lineas').innerHTML = filas.map(i => {
    total += i.precio * i.cant;
    return `<tr><td>${i.nombre}<br><small class="text-muted">${fmt(i.precio)}</small></td>
      <td class="text-nowrap"><button class="btn btn-sm btn-outline-secondary" data-a="-" data-id="${i.id}">−</button>
      <span class="mx-2">${i.cant}</span>
      <button class="btn btn-sm btn-outline-secondary" data-a="+" data-id="${i.id}">+</button></td>
      <td class="text-end">${fmt(i.precio * i.cant)}</td>
      <td><button class="btn btn-sm text-danger" data-a="x" data-id="${i.id}"><i class="bi bi-x-lg"></i></button></td></tr>`;
  }).join('');
  $('total').textContent = fmt(total);
  $('cobrar').disabled = filas.length === 0;
}

document.querySelectorAll('.producto-card').forEach(card => {
  card.addEventListener('click', () => {
    const id = card.dataset.id, stock = +card.dataset.stock;
    if (stock < 1) return aviso('Producto agotado.', 'danger');
    const actual = carrito[id]?.cant || 0;
    if (actual + 1 > stock) return aviso(`Solo hay ${stock} unidades de ${card.dataset.nombre}.`, 'warning');
    carrito[id] = carrito[id] || { id, nombre: card.dataset.nombre, precio: +card.dataset.precio, stock, cant: 0 };
    carrito[id].cant++;
    aviso('');
    pintar();
  });
});

$('lineas').addEventListener('click', ev => {
  const b = ev.target.closest('button'); if (!b) return;
  const i = carrito[b.dataset.id];
  if (b.dataset.a === '+') { if (i.cant + 1 > i.stock) return aviso(`Stock máximo: ${i.stock}.`, 'warning'); i.cant++; }
  if (b.dataset.a === '-') { i.cant--; if (i.cant < 1) delete carrito[i.id]; }
  if (b.dataset.a === 'x') delete carrito[i.id];
  aviso(''); pintar();
});

$('buscar').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('.item-producto').forEach(el => el.classList.toggle('d-none', !el.dataset.nombre.includes(q)));
});

$('cobrar').addEventListener('click', async () => {
  $('cobrar').disabled = true;
  const items = Object.values(carrito).map(i => ({ id: +i.id, cantidad: i.cant }));
  try {
    const r = await fetch('procesar_venta.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf: CSRF, cliente_id: +$('cliente').value, tipo_pago: $('tipo_pago').value, items })
    });
    const d = await r.json();
    if (!d.ok) { aviso(d.error, 'danger'); $('cobrar').disabled = false; return; }
    // Actualiza el stock visible sin recargar
    items.forEach(i => {
      const c = document.querySelector(`.producto-card[data-id="${i.id}"]`);
      c.dataset.stock = +c.dataset.stock - i.cantidad;
      c.querySelector('.stock-txt').textContent = 'Stock: ' + c.dataset.stock;
      c.classList.toggle('agotado', +c.dataset.stock < 1);
    });
    Object.keys(carrito).forEach(k => delete carrito[k]);
    pintar();
    aviso(`Venta #${d.venta_id} registrada. <a href="../pdf/comprobante.php?id=${d.venta_id}" target="_blank">Ver comprobante</a>`, 'success');
  } catch (e) {
    aviso('No se pudo conectar con el servidor.', 'danger'); $('cobrar').disabled = false;
  }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
