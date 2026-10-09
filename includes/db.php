<?php
/**
 * Conexión PDO (singleton)
 */
require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            $msg = APP_DEBUG ? $ex->getMessage() : 'No se pudo conectar a la base de datos.';
            if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => $msg]);
            } else {
                echo '<div style="font-family:sans-serif;padding:2rem;max-width:640px;margin:3rem auto;border-radius:12px;background:#fff3f3;border:1px solid #f3c2c2">'
                   . '<h2>Error de conexión</h2><p>' . htmlspecialchars($msg) . '</p>'
                   . '<p>Revisá <code>config/config.php</code> e importá <code>database/formosa.sql</code>.</p></div>';
            }
            exit;
        }
    }
    return $pdo;
}
