<?php
// EJECUTAR UNA SOLA VEZ desde el navegador y luego BORRAR este archivo.
require_once __DIR__ . '/config/conexion.php';
$pdo = getConexion();
$usuarios = [
    ['Willy (Administrador)', 'admin',    'Admin123*', 'ADMINISTRADOR'],
    ['Operador de caja',      'operador', 'Oper123*',  'OPERADOR'],
];
$sql = 'INSERT INTO usuarios (nombre, usuario, clave_hash, rol) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE clave_hash = VALUES(clave_hash), rol = VALUES(rol)';
$st = $pdo->prepare($sql);
foreach ($usuarios as [$n, $u, $c, $r]) {
    $st->execute([$n, $u, password_hash($c, PASSWORD_DEFAULT), $r]);
}
echo 'Usuarios creados. Ya puedes ir a <a href="login.php">login.php</a>. Borra este archivo (instalar_usuarios.php).';
