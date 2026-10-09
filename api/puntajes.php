<?php
/**
 * Ranking de los juegos
 *  GET  api/puntajes.php?juego=trivia  → top 10
 *  POST api/puntajes.php               → guarda un puntaje
 */
require_once __DIR__ . '/../includes/funciones.php';
$pdo = db();
$juegos = ['trivia', 'memoria', 'ubicacion'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?? []);
    if (!csrf_check($in['csrf'] ?? null)) json_out(['ok' => false, 'error' => 'Sesión expirada.'], 419);

    $nombre = trim(mb_substr(strip_tags($in['nombre'] ?? ''), 0, 30));
    $juego  = in_array($in['juego'] ?? '', $juegos, true) ? $in['juego'] : 'trivia';
    $puntos = max(0, min(100000, (int)($in['puntos'] ?? 0)));
    $acier  = max(0, min(100, (int)($in['aciertos'] ?? 0)));
    $total  = max(0, min(100, (int)($in['total'] ?? 0)));
    if (mb_strlen($nombre) < 2) json_out(['ok' => false, 'error' => 'Ingresá un nombre.'], 422);

    // Evita spam: máximo 1 envío cada 10 s por sesión
    if (!empty($_SESSION['ultimo_puntaje']) && time() - $_SESSION['ultimo_puntaje'] < 10) {
        json_out(['ok' => false, 'error' => 'Esperá unos segundos.'], 429);
    }
    $_SESSION['ultimo_puntaje'] = time();

    $pdo->prepare('INSERT INTO puntajes (nombre, juego, puntos, aciertos, total) VALUES (?,?,?,?,?)')
        ->execute([$nombre, $juego, $puntos, $acier, $total]);
}

$juego = in_array($_GET['juego'] ?? ($in['juego'] ?? ''), $juegos, true) ? ($_GET['juego'] ?? $in['juego']) : 'trivia';
$st = $pdo->prepare('SELECT nombre, puntos, aciertos, total, creado FROM puntajes WHERE juego = ? ORDER BY puntos DESC, creado ASC LIMIT 10');
$st->execute([$juego]);
json_out(['ok' => true, 'ranking' => $st->fetchAll()]);
