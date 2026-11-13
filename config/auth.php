<?php
require_once __DIR__ . '/conexion.php';

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_name('GDVCOMSESS');
        session_start();
    }
}

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    iniciarSesionSegura();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfValido(?string $token): bool
{
    iniciarSesionSegura();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function usuarioActual(): ?array
{
    iniciarSesionSegura();
    return $_SESSION['usuario'] ?? null;
}

function rutaInicioPorRol(string $rol): string
{
    return BASE_URL . ($rol === 'ADMINISTRADOR' ? '/admin/index.php' : '/operador/index.php');
}

// Exige sesión iniciada y que el rol esté permitido
function requerirRol(array $roles): array
{
    $u = usuarioActual();
    if (!$u) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if (!in_array($u['rol'], $roles, true)) {
        http_response_code(403);
        exit('Acceso denegado: tu rol no tiene permiso para esta sección.');
    }
    return $u;
}
