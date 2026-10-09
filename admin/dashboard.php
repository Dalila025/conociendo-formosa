<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();

$pdo = db();
$c = $pdo->query("SELECT
    (SELECT COUNT(*) FROM lugares) lugares,
    (SELECT COUNT(*) FROM lugares WHERE activo=0) inactivos,
    (SELECT COUNT(*) FROM imagenes) fotos,
    (SELECT COUNT(*) FROM preguntas) preguntas,
    (SELECT COUNT(*) FROM resenas) resenas,
    (SELECT COUNT(*) FROM resenas WHERE estado='pendiente') pendientes,
    (SELECT COALESCE(ROUND(AVG(calificacion),1),0) FROM resenas WHERE estado='aprobada') promedio,
    (SELECT COUNT(*) FROM puntajes) partidas,
    (SELECT COALESCE(SUM(visitas),0) FROM lugares) visitas")->fetch();

$sinFoto = $pdo->query('SELECT l.id, l.nombre, d.nombre depto FROM lugares l JOIN departamentos d ON d.id=l.departamento_id
                        WHERE NOT EXISTS (SELECT 1 FROM imagenes i WHERE i.lugar_id = l.id) ORDER BY d.orden, l.nombre')->fetchAll();
$top = $pdo->query('SELECT id, nombre, visitas, categoria FROM lugares ORDER BY visitas DESC LIMIT 6')->fetchAll();
$ultimas = $pdo->query('SELECT r.*, l.nombre lugar FROM resenas r LEFT JOIN lugares l ON l.id=r.lugar_id ORDER BY r.creado DESC LIMIT 5')->fetchAll();
$porDepto = $pdo->query('SELECT d.nombre, d.color, COUNT(l.id) n FROM departamentos d LEFT JOIN lugares l ON l.departamento_id=d.id GROUP BY d.id ORDER BY d.orden')->fetchAll();
$maxDepto = max(array_column($porDepto, 'n') ?: [1]);

$titulo = 'Escritorio';
$seccion = 'dashboard';
$subtitulo = '¡Hola, ' . e($_SESSION['admin_nombre']) . '! Este es el resumen de tu sitio.';
$acciones = '<a href="lugar_form.php" class="btn btn-lapacho btn-sm"><i class="fa-solid fa-plus me-1"></i> Nuevo lugar</a>';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-4">
  <?php
  $tarjetas = [
      ['Lugares', $c['lugares'], 'fa-location-dot', 'lapacho', 'lugares.php', $c['inactivos'] ? $c['inactivos'] . ' ocultos' : 'todos visibles'],
      ['Fotos', $c['fotos'], 'fa-images', 'humedal', 'imagenes.php', count($sinFoto) . ' lugares sin foto'],
      ['Preguntas', $c['preguntas'], 'fa-circle-question', 'sol', 'preguntas.php', $c['partidas'] . ' partidas jugadas'],
      ['Reseñas', $c['resenas'], 'fa-star', 'tierra', 'resenas.php', '★ ' . $c['promedio'] . ' · ' . $c['pendientes'] . ' pendientes'],
  ];
  foreach ($tarjetas as $k => [$t, $n, $ic, $tono, $url, $extra]): ?>
    <div class="col-6 col-xl-3">
      <a href="<?= $url ?>" class="stat-card tone-<?= $tono ?>" style="--d:<?= $k * 80 ?>ms">
        <div class="stat-icon"><i class="fa-solid <?= $ic ?>"></i></div>
        <div><span class="stat-label"><?= $t ?></span><strong class="stat-num" data-count="<?= (int)$n ?>"><?= (int)$n ?></strong><small><?= e($extra) ?></small></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-xl-7">
    <div class="panel h-100">
      <div class="panel-head"><h2><i class="fa-solid fa-camera"></i> Lugares que necesitan foto</h2><span class="badge bg-warning text-dark"><?= count($sinFoto) ?></span></div>
      <?php if ($sinFoto): ?>
        <p class="text-muted small">Estos lugares se muestran con una ilustración de color. Subí una foto real para que luzcan mejor.</p>
        <div class="list-group list-group-flush scroll-list">
          <?php foreach ($sinFoto as $l): ?>
            <div class="list-group-item d-flex align-items-center gap-3">
              <span class="ph-mini"><i class="fa-regular fa-image"></i></span>
              <div class="flex-grow-1"><strong><?= e($l['nombre']) ?></strong><br><small class="text-muted"><?= e($l['depto']) ?></small></div>
              <a href="imagenes.php?lugar=<?= $l['id'] ?>" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-upload me-1"></i> Subir</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state"><i class="fa-solid fa-circle-check"></i><p>¡Todos los lugares tienen foto!</p></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="panel mb-4">
      <div class="panel-head"><h2><i class="fa-solid fa-map"></i> Lugares por departamento</h2></div>
      <?php foreach ($porDepto as $d): ?>
        <div class="bar-row">
          <span><?= e($d['nombre']) ?></span>
          <div class="bar-track"><div class="bar-fill" style="--w:<?= round($d['n'] / $maxDepto * 100) ?>%;background:<?= e($d['color']) ?>"></div></div>
          <b><?= $d['n'] ?></b>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="panel">
      <div class="panel-head"><h2><i class="fa-solid fa-fire"></i> Más visitados</h2><small class="text-muted"><?= (int)$c['visitas'] ?> visitas</small></div>
      <ol class="top-list">
        <?php foreach ($top as $t): ?>
          <li><a href="lugar_form.php?id=<?= $t['id'] ?>"><?= e($t['nombre']) ?></a><span><?= (int)$t['visitas'] ?> <i class="fa-regular fa-eye"></i></span></li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
  <div class="col-12">
    <div class="panel">
      <div class="panel-head"><h2><i class="fa-solid fa-comments"></i> Últimas reseñas</h2><a href="resenas.php" class="small">Ver todas</a></div>
      <?php if ($ultimas): ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <tbody>
              <?php foreach ($ultimas as $r): ?>
                <tr>
                  <td style="width:180px"><strong><?= e($r['nombre']) ?></strong><br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['creado'])) ?></small></td>
                  <td class="text-warning text-nowrap"><?= str_repeat('★', $r['calificacion']) ?><span class="text-black-50"><?= str_repeat('☆', 5 - $r['calificacion']) ?></span></td>
                  <td><?= e(mb_strimwidth($r['comentario'], 0, 120, '…')) ?><?= $r['lugar'] ? '<br><small class="text-muted"><i class="fa-solid fa-location-dot"></i> ' . e($r['lugar']) . '</small>' : '' ?></td>
                  <td><span class="badge estado-<?= $r['estado'] ?>"><?= ucfirst($r['estado']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state"><i class="fa-regular fa-comments"></i><p>Todavía no hay reseñas.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
