<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
$pdo = db();

$lugarId = (int)($_GET['lugar'] ?? 0);

/* ---- Reordenar (AJAX) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'ordenar') {
    if (!csrf_check()) json_out(['ok' => false, 'error' => 'CSRF'], 419);
    $ids = array_map('intval', explode(',', $_POST['ids'] ?? ''));
    $st = $pdo->prepare('UPDATE imagenes SET orden = ? WHERE id = ? AND lugar_id = ?');
    foreach ($ids as $i => $imgId) $st->execute([$i, $imgId, $lugarId]);
    json_out(['ok' => true]);
}

verificar_post();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $imgId = (int)($_POST['id'] ?? 0);
    try {
        switch ($accion) {
            case 'subir':
                $archivos = archivos_multiples($_FILES['fotos'] ?? []);
                $ok = 0; $fallos = [];
                $max = (int)$pdo->query("SELECT COALESCE(MAX(orden),-1) FROM imagenes WHERE lugar_id = $lugarId")->fetchColumn();
                $hayPrincipal = (int)$pdo->query("SELECT COUNT(*) FROM imagenes WHERE lugar_id = $lugarId AND principal = 1")->fetchColumn();
                foreach ($archivos as $f) {
                    if ($f['error'] === UPLOAD_ERR_NO_FILE) continue;
                    try {
                        $ruta = subir_imagen($f, 'lugares');
                        $pdo->prepare('INSERT INTO imagenes (lugar_id, url, titulo, credito, principal, orden) VALUES (?,?,?,?,?,?)')
                            ->execute([$lugarId, $ruta, pathinfo($f['name'], PATHINFO_FILENAME), trim($_POST['credito'] ?? '') ?: null, $hayPrincipal ? 0 : 1, ++$max]);
                        $hayPrincipal = 1; $ok++;
                    } catch (RuntimeException $ex) { $fallos[] = $f['name'] . ': ' . $ex->getMessage(); }
                }
                if ($ok) flash('success', "Se subieron $ok foto(s).");
                foreach ($fallos as $fl) flash('danger', $fl);
                if (!$ok && !$fallos) flash('warning', 'No seleccionaste ninguna foto.');
                break;

            case 'url':
                $url = trim($_POST['url'] ?? '');
                if (!url_imagen_valida($url)) throw new RuntimeException('Ingresá una URL válida (https://…).');
                $hayPrincipal = (int)$pdo->query("SELECT COUNT(*) FROM imagenes WHERE lugar_id = $lugarId AND principal = 1")->fetchColumn();
                $pdo->prepare('INSERT INTO imagenes (lugar_id, url, titulo, credito, principal, orden) VALUES (?,?,?,?,?,999)')
                    ->execute([$lugarId, $url, trim($_POST['titulo'] ?? '') ?: null, trim($_POST['credito'] ?? '') ?: null, $hayPrincipal ? 0 : 1]);
                flash('success', 'Imagen agregada desde URL.');
                break;

            case 'editar':
                $st = $pdo->prepare('SELECT * FROM imagenes WHERE id = ? AND lugar_id = ?');
                $st->execute([$imgId, $lugarId]);
                $img = $st->fetch();
                if (!$img) throw new RuntimeException('La imagen no existe.');
                $nueva = procesar_imagen('reemplazo', 'lugares', $img['url']) ?? $img['url'];
                $pdo->prepare('UPDATE imagenes SET url = ?, titulo = ?, credito = ? WHERE id = ?')
                    ->execute([$nueva, trim($_POST['titulo'] ?? '') ?: null, trim($_POST['credito'] ?? '') ?: null, $imgId]);
                flash('success', 'Imagen actualizada.');
                break;

            case 'principal':
                $pdo->prepare('UPDATE imagenes SET principal = (id = ?) WHERE lugar_id = ?')->execute([$imgId, $lugarId]);
                flash('success', 'Foto de portada actualizada.');
                break;

            case 'eliminar':
                $st = $pdo->prepare('SELECT * FROM imagenes WHERE id = ? AND lugar_id = ?');
                $st->execute([$imgId, $lugarId]);
                if ($img = $st->fetch()) {
                    borrar_archivo($img['url']);
                    $pdo->prepare('DELETE FROM imagenes WHERE id = ?')->execute([$imgId]);
                    if ($img['principal']) {
                        $pdo->prepare('UPDATE imagenes SET principal = 1 WHERE lugar_id = ? ORDER BY orden LIMIT 1')->execute([$lugarId]);
                    }
                    flash('success', 'Foto eliminada.');
                }
                break;
        }
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
    }
    redirect('imagenes.php?lugar=' . $lugarId);
}

$lugares = $pdo->query('SELECT l.id, l.nombre, d.nombre depto, (SELECT COUNT(*) FROM imagenes i WHERE i.lugar_id = l.id) n
                        FROM lugares l JOIN departamentos d ON d.id = l.departamento_id ORDER BY d.orden, l.nombre')->fetchAll();
$lugar = null; $imagenes = [];
if ($lugarId) {
    $st = $pdo->prepare('SELECT l.*, d.nombre depto FROM lugares l JOIN departamentos d ON d.id = l.departamento_id WHERE l.id = ?');
    $st->execute([$lugarId]);
    $lugar = $st->fetch();
    if ($lugar) {
        $st = $pdo->prepare('SELECT * FROM imagenes WHERE lugar_id = ? ORDER BY principal DESC, orden, id');
        $st->execute([$lugarId]);
        $imagenes = $st->fetchAll();
    }
}

$titulo = 'Fotos de los lugares';
$seccion = 'imagenes';
$subtitulo = $lugar ? e($lugar['nombre']) . ' · ' . e($lugar['depto']) : 'Elegí un lugar para administrar sus fotos';
$acciones = $lugar ? '<a href="lugar_form.php?id=' . $lugarId . '" class="btn btn-light btn-sm"><i class="fa-solid fa-pen me-1"></i> Editar lugar</a>' : '';
$usarSortable = true;
require __DIR__ . '/includes/header.php';
?>
<div class="panel mb-4">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
    <label class="fw-semibold me-1"><i class="fa-solid fa-location-dot text-danger"></i> Lugar:</label>
    <select name="lugar" class="form-select" style="max-width:480px" data-autosubmit>
      <option value="">— Elegí un lugar —</option>
      <?php $grupo = null; foreach ($lugares as $l):
        if ($grupo !== $l['depto']) { echo $grupo ? '</optgroup>' : ''; $grupo = $l['depto']; echo '<optgroup label="' . e($grupo) . '">'; } ?>
        <option value="<?= $l['id'] ?>" <?= $lugarId == $l['id'] ? 'selected' : '' ?>><?= e($l['nombre']) ?> (<?= $l['n'] ?> fotos)</option>
      <?php endforeach; echo $grupo ? '</optgroup>' : ''; ?>
    </select>
  </form>
</div>

<?php if (!$lugar): ?>
  <div class="panel empty-state"><i class="fa-solid fa-images"></i><p>Seleccioná un lugar para ver, subir, editar o quitar sus fotos.</p></div>
<?php else: ?>
<div class="row g-4">
  <div class="col-xl-4">
    <div class="panel mb-4">
      <div class="panel-head"><h2><i class="fa-solid fa-cloud-arrow-up"></i> Subir fotos</h2></div>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="accion" value="subir">
        <label class="drop-zone big" data-multi>
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span><strong>Arrastrá varias fotos</strong> o hacé clic</span>
          <small>JPG, PNG, WEBP · máx. <?= UPLOAD_MAX_MB ?> MB c/u</small>
          <input type="file" name="fotos[]" accept="image/*" multiple hidden>
        </label>
        <div class="multi-preview"></div>
        <input type="text" name="credito" class="form-control form-control-sm mt-2" placeholder="Crédito / autor (opcional)">
        <button class="btn btn-lapacho w-100 mt-3"><i class="fa-solid fa-upload me-1"></i> Subir</button>
      </form>
    </div>
    <div class="panel">
      <div class="panel-head"><h2><i class="fa-solid fa-link"></i> Agregar desde URL</h2></div>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="accion" value="url">
        <input type="url" name="url" class="form-control mb-2" placeholder="https://…" required>
        <input type="text" name="titulo" class="form-control mb-2" placeholder="Título / descripción">
        <input type="text" name="credito" class="form-control mb-2" placeholder="Crédito (ej. Wikimedia Commons)">
        <button class="btn btn-dark w-100"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
      </form>
    </div>
  </div>
  <div class="col-xl-8">
    <div class="panel">
      <div class="panel-head">
        <h2><i class="fa-solid fa-images"></i> Galería (<?= count($imagenes) ?>)</h2>
        <?php if (count($imagenes) > 1): ?><small class="text-muted"><i class="fa-solid fa-up-down-left-right"></i> Arrastrá para ordenar</small><?php endif; ?>
      </div>
      <?php if (!$imagenes): ?>
        <div class="empty-state"><i class="fa-regular fa-image"></i><p>Este lugar todavía no tiene fotos.<br>En el sitio se muestra con una ilustración de color.</p></div>
      <?php else: ?>
        <div class="img-grid" id="imgGrid" data-lugar="<?= $lugarId ?>" data-csrf="<?= csrf_token() ?>">
          <?php foreach ($imagenes as $im): ?>
            <div class="img-item <?= $im['principal'] ? 'is-main' : '' ?>" data-id="<?= $im['id'] ?>">
              <div class="img-thumb" style="background-image:url('<?= e(img_url($im['url'], P)) ?>')">
                <?php if ($im['principal']): ?><span class="main-tag"><i class="fa-solid fa-crown"></i> Portada</span><?php endif; ?>
                <span class="drag-handle" title="Arrastrar"><i class="fa-solid fa-grip"></i></span>
              </div>
              <div class="img-info">
                <strong><?= e($im['titulo'] ?: 'Sin título') ?></strong>
                <small class="text-muted"><?= e($im['credito'] ?: (str_starts_with($im['url'], 'http') ? 'URL externa' : 'Archivo subido')) ?></small>
              </div>
              <div class="img-actions">
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editImg<?= $im['id'] ?>" title="Editar"><i class="fa-solid fa-pen"></i></button>
                <?php if (!$im['principal']): ?>
                  <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="principal"><input type="hidden" name="id" value="<?= $im['id'] ?>">
                    <button class="btn btn-sm btn-light" title="Usar como portada"><i class="fa-solid fa-crown text-warning"></i></button></form>
                <?php endif; ?>
                <a class="btn btn-sm btn-light" href="<?= e(img_url($im['url'], P)) ?>" target="_blank" title="Ver"><i class="fa-solid fa-up-right-from-square"></i></a>
                <form method="post" data-confirm="¿Eliminar esta foto?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $im['id'] ?>">
                  <button class="btn btn-sm btn-light text-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button></form>
              </div>
            </div>

            <div class="modal fade" id="editImg<?= $im['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <form class="modal-content" method="post" enctype="multipart/form-data">
                  <?= csrf_field() ?><input type="hidden" name="accion" value="editar"><input type="hidden" name="id" value="<?= $im['id'] ?>">
                  <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Editar foto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-6"><label class="form-label">Título</label><input type="text" name="titulo" class="form-control" value="<?= e($im['titulo']) ?>"></div>
                      <div class="col-md-6"><label class="form-label">Crédito</label><input type="text" name="credito" class="form-control" value="<?= e($im['credito']) ?>"></div>
                      <div class="col-12"><?= campo_imagen('reemplazo', $im['url'], 'Reemplazar imagen', true) ?></div>
                    </div>
                  </div>
                  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lapacho"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
