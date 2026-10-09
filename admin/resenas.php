<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();
$estado = $_GET['estado'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    if (in_array($accion, ['aprobada', 'oculta', 'pendiente'], true)) {
        $pdo->prepare('UPDATE resenas SET estado = ? WHERE id = ?')->execute([$accion, $id]);
        flash('success', 'Reseña marcada como ' . $accion . '.');
    } elseif ($accion === 'eliminar') {
        $pdo->prepare('DELETE FROM resenas WHERE id = ?')->execute([$id]);
        flash('success', 'Reseña eliminada.');
    } elseif ($accion === 'editar') {
        $com = trim($_POST['comentario'] ?? '');
        $cal = max(1, min(5, (int)($_POST['calificacion'] ?? 5)));
        $nom = trim($_POST['nombre'] ?? '');
        if ($com && $nom) {
            $pdo->prepare('UPDATE resenas SET nombre = ?, calificacion = ?, comentario = ? WHERE id = ?')->execute([$nom, $cal, $com, $id]);
            flash('success', 'Reseña actualizada.');
        }
    }
    redirect('resenas.php' . ($estado ? '?estado=' . urlencode($estado) : ''));
}

$cont = $pdo->query("SELECT estado, COUNT(*) n FROM resenas GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);
$sql = 'SELECT r.*, l.nombre lugar FROM resenas r LEFT JOIN lugares l ON l.id = r.lugar_id';
if (in_array($estado, ['aprobada', 'pendiente', 'oculta'], true)) {
    $st = $pdo->prepare($sql . ' WHERE r.estado = ? ORDER BY r.creado DESC');
    $st->execute([$estado]);
} else {
    $st = $pdo->query($sql . ' ORDER BY r.creado DESC');
}
$resenas = $st->fetchAll();

$titulo = 'Reseñas';
$seccion = 'resenas';
$subtitulo = 'Moderá lo que opinan las personas que visitan el sitio';
require __DIR__ . '/includes/header.php';
?>
<div class="seg-tabs mb-4">
  <a href="resenas.php" class="<?= !$estado ? 'active' : '' ?>">Todas <span><?= array_sum($cont) ?></span></a>
  <a href="?estado=pendiente" class="<?= $estado === 'pendiente' ? 'active' : '' ?>">Pendientes <span><?= $cont['pendiente'] ?? 0 ?></span></a>
  <a href="?estado=aprobada" class="<?= $estado === 'aprobada' ? 'active' : '' ?>">Publicadas <span><?= $cont['aprobada'] ?? 0 ?></span></a>
  <a href="?estado=oculta" class="<?= $estado === 'oculta' ? 'active' : '' ?>">Ocultas <span><?= $cont['oculta'] ?? 0 ?></span></a>
</div>

<?php if (!$resenas): ?>
  <div class="panel empty-state"><i class="fa-regular fa-comments"></i><p>No hay reseñas en esta vista.</p></div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($resenas as $r): ?>
    <div class="col-lg-6">
      <div class="review-admin estado-b-<?= $r['estado'] ?>">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <strong><?= e($r['nombre']) ?></strong> <?= $r['ciudad'] ? '<small class="text-muted">· ' . e($r['ciudad']) . '</small>' : '' ?>
            <div class="text-warning"><?= str_repeat('★', $r['calificacion']) ?><span class="text-black-50"><?= str_repeat('☆', 5 - $r['calificacion']) ?></span></div>
          </div>
          <span class="badge estado-<?= $r['estado'] ?>"><?= ucfirst($r['estado']) ?></span>
        </div>
        <p class="my-2"><?= nl2br(e($r['comentario'])) ?></p>
        <small class="text-muted"><i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($r['creado'])) ?><?= $r['lugar'] ? ' · <i class="fa-solid fa-location-dot"></i> ' . e($r['lugar']) : '' ?></small>
        <div class="d-flex flex-wrap gap-1 mt-3">
          <?php foreach (['aprobada' => ['Publicar', 'fa-check', 'success'], 'oculta' => ['Ocultar', 'fa-eye-slash', 'secondary']] as $acc => [$t, $ic, $c]): if ($r['estado'] === $acc) continue; ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="accion" value="<?= $acc ?>">
              <button class="btn btn-sm btn-outline-<?= $c ?>"><i class="fa-solid <?= $ic ?> me-1"></i><?= $t ?></button></form>
          <?php endforeach; ?>
          <button class="btn btn-sm btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#ed<?= $r['id'] ?>"><i class="fa-solid fa-pen me-1"></i>Editar</button>
          <form method="post" data-confirm="¿Eliminar la reseña de <?= e($r['nombre']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="accion" value="eliminar">
            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
        </div>
        <form method="post" class="collapse mt-3" id="ed<?= $r['id'] ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="accion" value="editar">
          <div class="row g-2">
            <div class="col-8"><input type="text" name="nombre" class="form-control form-control-sm" value="<?= e($r['nombre']) ?>"></div>
            <div class="col-4"><select name="calificacion" class="form-select form-select-sm"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= $r['calificacion'] == $i ? 'selected' : '' ?>><?= $i ?> ★</option><?php endfor; ?></select></div>
            <div class="col-12"><textarea name="comentario" class="form-control form-control-sm" rows="3"><?= e($r['comentario']) ?></textarea></div>
          </div>
          <button class="btn btn-sm btn-lapacho mt-2">Guardar</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
