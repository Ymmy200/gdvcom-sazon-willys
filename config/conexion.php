<?php
// Configuración general y conexión PDO. Ajusta los datos a tu XAMPP.
const DB_HOST = 'localhost';
const DB_NAME = 'gdvcom';
const DB_USER = 'root';
const DB_PASS = '';          // En XAMPP por defecto no tiene contraseña
const BASE_URL = '/gdvcom';  // Nombre de la carpeta dentro de htdocs

function getConexion(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('Error de conexión: ' . $e->getMessage());
            http_response_code(500);
            exit('No se pudo conectar con la base de datos.');
        }
    }
    return $pdo;
}
