<?php
// Uso: $titulo = 'Mi página'; require __DIR__.'/../includes/header.php'; (después de requerirRol)
$u = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'GDVCOM') ?> | Sazón Willy</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-gd shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?= e(rutaInicioPorRol($u['rol'])) ?>"><i class="bi bi-egg-fried me-1"></i>GDVCOM · Sazón Willy</a>
    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <?php if ($u['rol'] === 'ADMINISTRADOR'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/index.php">Panel</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/productos.php">Productos</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/operador/pos.php">Punto de venta</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/cartera/index.php">Cartera</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/cartera/clientes.php">Clientes</a></li>
      </ul>
      <span class="navbar-text me-3 text-light small"><i class="bi bi-person-circle"></i> <?= e($u['nombre']) ?> (<?= e($u['rol']) ?>)</span>
      <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>/logout.php">Salir</a>
    </div>
  </div>
</nav>
<main class="container pb-5">
