<?php
/**
 * API de reseñas
 *  GET  api/resenas.php?pagina=1   → reseñas aprobadas + estadísticas
 *  POST api/resenas.php            → nueva reseña (JSON o form) con token CSRF
 */
require_once __DIR__ . '/../includes/funciones.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?? []);

    if (!csrf_check($in['csrf'] ?? null)) {
        json_out(['ok' => false, 'error' => 'La sesión expiró. Recargá la página e intentá de nuevo.'], 419);
    }
    // Honeypot anti-spam: campo oculto que los humanos no completan
    if (!empty($in['website'])) json_out(['ok' => true, 'mensaje' => '¡Gracias!']);

    $nombre = trim(mb_substr($in['nombre'] ?? '', 0, 80));
    $ciudad = trim(mb_substr($in['ciudad'] ?? '', 0, 80));
    $coment = trim(mb_substr($in['comentario'] ?? '', 0, 1000));
    $cal    = (int)($in['calificacion'] ?? 0);
    $lugar  = !empty($in['lugar_id']) ? (int)$in['lugar_id'] : null;

    $errores = [];
    if (mb_strlen($nombre) < 2)  $errores[] = 'Ingresá tu nombre.';
    if ($cal < 1 || $cal > 5)    $errores[] = 'Elegí una calificación de 1 a 5 estrellas.';
    if (mb_strlen($coment) < 10) $errores[] = 'El comentario debe tener al menos 10 caracteres.';
    if ($errores) json_out(['ok' => false, 'error' => implode(' ', $errores)], 422);

    if ($lugar) {
        $chk = $pdo->prepare('SELECT id FROM lugares WHERE id = ? AND activo = 1');
        $chk->execute([$lugar]);
        if (!$chk->fetch()) $lugar = null;
    }

    // Límite: 1 reseña por minuto por IP
    $rl = $pdo->prepare('SELECT COUNT(*) FROM resenas WHERE ip = ? AND creado > (NOW() - INTERVAL 1 MINUTE)');
    $rl->execute([ip_cliente()]);
    if ($rl->fetchColumn() > 0) json_out(['ok' => false, 'error' => 'Esperá un minuto antes de enviar otra reseña.'], 429);

    $estado = cfg('resenas_moderar', '0') === '1' ? 'pendiente' : 'aprobada';
    $pdo->prepare('INSERT INTO resenas (nombre, ciudad, calificacion, comentario, lugar_id, estado, ip) VALUES (?,?,?,?,?,?,?)')
        ->execute([$nombre, $ciudad ?: null, $cal, $coment, $lugar, $estado, ip_cliente()]);

    json_out([
        'ok' => true,
        'estado' => $estado,
        'mensaje' => $estado === 'aprobada' ? '¡Gracias por tu reseña! Ya está publicada.' : '¡Gracias! Tu reseña se publicará cuando sea revisada.',
    ]);
}

// ---- GET ----
$porPagina = 6;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$stats = $pdo->query("SELECT COUNT(*) total, COALESCE(ROUND(AVG(calificacion),1),0) promedio,
                             SUM(calificacion=5) c5, SUM(calificacion=4) c4, SUM(calificacion=3) c3,
                             SUM(calificacion=2) c2, SUM(calificacion=1) c1
                      FROM resenas WHERE estado = 'aprobada'")->fetch();

$st = $pdo->prepare("SELECT r.id, r.nombre, r.ciudad, r.calificacion, r.comentario, r.creado, l.nombre AS lugar
                     FROM resenas r LEFT JOIN lugares l ON l.id = r.lugar_id
                     WHERE r.estado = 'aprobada' ORDER BY r.creado DESC LIMIT $porPagina OFFSET $offset");
$st->execute();

json_out([
    'ok'      => true,
    'stats'   => array_map(fn($v) => is_numeric($v) ? +$v : $v, $stats),
    'resenas' => $st->fetchAll(),
    'mas'     => ($offset + $porPagina) < (int)$stats['total'],
]);
