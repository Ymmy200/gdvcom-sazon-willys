<?php
require_once __DIR__ . '/config/auth.php';
iniciarSesionSegura();

if (usuarioActual()) {
    header('Location: ' . rutaInicioPorRol(usuarioActual()['rol']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'Sesión expirada, recarga la página.';
    } elseif ($usuario === '' || $clave === '') {
        $error = 'Completa usuario y contraseña.';
    } else {
        $stmt = getConexion()->prepare('SELECT id, nombre, usuario, clave_hash, rol FROM usuarios WHERE usuario = :u AND activo = 1 LIMIT 1');
        $stmt->execute([':u' => $usuario]);
        $row = $stmt->fetch();

        if ($row && password_verify($clave, $row['clave_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => (int)$row['id'], 'nombre' => $row['nombre'], 'rol' => $row['rol']];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            header('Location: ' . rutaInicioPorRol($row['rol']));
            exit;
        }
        usleep(400000); // frena intentos repetidos
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar | GDVCOM</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-fondo d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-11 col-sm-8 col-md-5 col-lg-4">
        <div class="card card-gd p-4">
          <div class="text-center mb-3">
            <i class="bi bi-egg-fried" style="font-size:2.6rem;color:var(--gd-ambar)"></i>
            <h4 class="fw-bold mt-2 mb-0">Sazón Willy</h4>
            <small class="text-muted">GDVCOM · Gestión de ventas y cartera</small>
          </div>
          <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
          <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <div class="mb-3">
              <label class="form-label">Usuario</label>
              <input type="text" name="usuario" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" name="clave" class="form-control" required>
            </div>
            <button class="btn btn-gd w-100 py-2">Ingresar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
