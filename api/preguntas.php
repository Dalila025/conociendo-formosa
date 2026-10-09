<?php
/**
 * API de preguntas para los juegos
 *  GET api/preguntas.php?lugar=3     → preguntas de un lugar
 *  GET api/preguntas.php?random=10   → N preguntas al azar de toda la provincia
 */
require_once __DIR__ . '/../includes/funciones.php';

$sql = 'SELECT p.id, p.pregunta, p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d, p.correcta, p.explicacion,
               l.id AS lugar_id, l.nombre AS lugar, d.nombre AS departamento
        FROM preguntas p
        JOIN lugares l ON l.id = p.lugar_id AND l.activo = 1
        JOIN departamentos d ON d.id = l.departamento_id';

if (isset($_GET['lugar'])) {
    $st = db()->prepare($sql . ' WHERE l.id = ? ORDER BY p.id');
    $st->execute([(int)$_GET['lugar']]);
} else {
    $n = max(3, min(30, (int)($_GET['random'] ?? 10)));
    $st = db()->prepare($sql . ' ORDER BY RAND() LIMIT ' . $n);
    $st->execute();
}

$out = array_map(fn($p) => [
    'id'          => (int)$p['id'],
    'pregunta'    => $p['pregunta'],
    'opciones'    => ['a' => $p['opcion_a'], 'b' => $p['opcion_b'], 'c' => $p['opcion_c'], 'd' => $p['opcion_d']],
    'correcta'    => $p['correcta'],
    'explicacion' => $p['explicacion'],
    'lugar_id'    => (int)$p['lugar_id'],
    'lugar'       => $p['lugar'],
    'departamento'=> $p['departamento'],
], $st->fetchAll());

json_out(['ok' => true, 'preguntas' => $out]);
