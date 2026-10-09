<?php
/**
 * API pública de lugares y departamentos
 *  GET api/lugares.php          → todos los departamentos y lugares activos (con imágenes)
 *  GET api/lugares.php?id=5     → un lugar (suma una visita)
 */
require_once __DIR__ . '/../includes/funciones.php';

$pdo = db();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = $pdo->prepare('SELECT l.*, d.nombre AS departamento, d.color AS depto_color
                         FROM lugares l JOIN departamentos d ON d.id = l.departamento_id
                         WHERE l.id = ? AND l.activo = 1');
    $st->execute([$id]);
    $lugar = $st->fetch();
    if (!$lugar) json_out(['ok' => false, 'error' => 'Lugar no encontrado'], 404);

    $pdo->prepare('UPDATE lugares SET visitas = visitas + 1 WHERE id = ?')->execute([$id]);

    $im = $pdo->prepare('SELECT url, titulo, credito FROM imagenes WHERE lugar_id = ? ORDER BY principal DESC, orden, id');
    $im->execute([$id]);
    $lugar['imagenes'] = $im->fetchAll();
    $lugar['fuentes'] = parse_fuentes($lugar['fuentes']);
    $lugar['lat'] = (float)$lugar['lat'];
    $lugar['lng'] = (float)$lugar['lng'];
    json_out(['ok' => true, 'lugar' => $lugar]);
}

$deptos = $pdo->query('SELECT d.id, d.nombre, d.slug, d.cabecera, d.descripcion, d.color, d.imagen,
                              (SELECT COUNT(*) FROM lugares l WHERE l.departamento_id = d.id AND l.activo = 1) AS total
                       FROM departamentos d ORDER BY d.orden, d.nombre')->fetchAll();

$lugares = $pdo->query('SELECT l.id, l.departamento_id, l.nombre, l.slug, l.categoria, l.resumen, l.descripcion,
                               l.dato_curioso, l.importancia, l.habitantes, l.como_llegar, l.fuentes, l.lat, l.lng, l.map_x, l.map_y, l.destacado,
                               (SELECT COUNT(*) FROM preguntas p WHERE p.lugar_id = l.id) AS preguntas
                        FROM lugares l WHERE l.activo = 1 ORDER BY l.nombre')->fetchAll();

$imgs = $pdo->query('SELECT i.lugar_id, i.url, i.titulo, i.credito FROM imagenes i
                     JOIN lugares l ON l.id = i.lugar_id AND l.activo = 1
                     ORDER BY i.principal DESC, i.orden, i.id')->fetchAll();
$porLugar = [];
foreach ($imgs as $i) {
    $porLugar[$i['lugar_id']][] = ['url' => $i['url'], 'titulo' => $i['titulo'], 'credito' => $i['credito']];
}
foreach ($lugares as &$l) {
    $l['lat'] = (float)$l['lat'];
    $l['lng'] = (float)$l['lng'];
    $l['map_x'] = $l['map_x'] !== null ? (float)$l['map_x'] : null;
    $l['map_y'] = $l['map_y'] !== null ? (float)$l['map_y'] : null;
    $l['destacado'] = (int)$l['destacado'];
    $l['preguntas'] = (int)$l['preguntas'];
    $l['imagenes'] = $porLugar[$l['id']] ?? [];
    $l['fuentes'] = parse_fuentes($l['fuentes']);
}
unset($l);

json_out(['ok' => true, 'departamentos' => $deptos, 'lugares' => $lugares, 'categorias' => categorias()]);
