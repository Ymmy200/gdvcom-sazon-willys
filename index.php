<?php
require_once __DIR__ . '/config/auth.php';
$u = usuarioActual();
header('Location: ' . ($u ? rutaInicioPorRol($u['rol']) : BASE_URL . '/login.php'));
exit;
