<?php
require_once __DIR__ . '/../config/auth.php';
header('Content-Type: application/json; charset=utf-8');

function responder(bool $ok, array $extra = []): void
{
    echo json_encode(['ok' => $ok] + $extra);
    exit;
}

$u = usuarioActual();
if (!$u || !in_array($u['rol'], ['OPERADOR', 'ADMINISTRADOR'], true)) {
    http_response_code(401);
    responder(false, ['error' => 'Sesión no válida.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responder(false, ['error' => 'Método no permitido.']);
}

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos) || !csrfValido($datos['csrf'] ?? null)) {
    http_response_code(403);
    responder(false, ['error' => 'Token de seguridad inválido. Recarga la página.']);
}

$tipoPago  = ($datos['tipo_pago'] ?? '') === 'CREDITO' ? 'CREDITO' : 'CONTADO';
$clienteId = (int)($datos['cliente_id'] ?? 0) ?: null;
$items     = $datos['items'] ?? [];

if (!is_array($items) || count($items) === 0) {
    responder(false, ['error' => 'El carrito está vacío.']);
}
if ($tipoPago === 'CREDITO' && !$clienteId) {
    responder(false, ['error' => 'Selecciona un cliente para ventas a crédito.']);
}

// Une líneas repetidas del mismo producto
$cantidades = [];
foreach ($items as $it) {
    $id  = (int)($it['id'] ?? 0);
    $cnt = (int)($it['cantidad'] ?? 0);
    if ($id <= 0 || $cnt <= 0) {
        responder(false, ['error' => 'Hay productos con datos inválidos.']);
    }
    $cantidades[$id] = ($cantidades[$id] ?? 0) + $cnt;
}

$pdo = getConexion();
try {
    $pdo->beginTransaction();
    $total = 0;
    $lineas = [];

    foreach ($cantidades as $id => $cnt) {
        // FOR UPDATE bloquea la fila: evita vender el mismo stock dos veces
        $st = $pdo->prepare('SELECT nombre, precio, stock FROM productos WHERE id=? AND activo=1 FOR UPDATE');
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p) {
            throw new RuntimeException('Un producto ya no está disponible.');
        }
        if ($p['stock'] < $cnt) {
            throw new RuntimeException("Stock insuficiente de {$p['nombre']} (quedan {$p['stock']}).");
        }
        $sub = $p['precio'] * $cnt;   // el precio siempre sale de la BD, no del navegador
        $total += $sub;
        $lineas[] = [$id, $cnt, $p['precio'], $sub];
    }

    $saldo = $tipoPago === 'CREDITO' ? $total : 0;
    $pdo->prepare('INSERT INTO ventas (usuario_id, cliente_id, tipo_pago, total, saldo) VALUES (?,?,?,?,?)')
        ->execute([$u['id'], $clienteId, $tipoPago, $total, $saldo]);
    $ventaId = (int)$pdo->lastInsertId();

    $insD = $pdo->prepare('INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)');
    $upd  = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');
    foreach ($lineas as [$id, $cnt, $precio, $sub]) {
        $insD->execute([$ventaId, $id, $cnt, $precio, $sub]);
        $upd->execute([$cnt, $id]);
    }

    $pdo->commit();
    responder(true, ['venta_id' => $ventaId, 'total' => $total]);
} catch (RuntimeException $e) {
    $pdo->rollBack();
    responder(false, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Venta fallida: ' . $e->getMessage());
    http_response_code(500);
    responder(false, ['error' => 'Error interno al registrar la venta.']);
}
