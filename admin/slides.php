<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'ordenar') {
    if (!csrf_check()) json_out(['ok' => false], 419);
    $st = $pdo->prepare('UPDATE slides SET orden = ? WHERE id = ?');
    foreach (array_map('intval', explode(',', $_POST['ids'] ?? '')) as $i => $id) $st->execute([$i + 1, $id]);
    json_out(['ok' => true]);
}
verificar_post();
$editId = (int)($_GET['editar'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        $actual = null;
        if ($id) {
            $s = $pdo->prepare('SELECT imagen FROM slides WHERE id = ?');
            $s->execute([$id]);
            $actual = $s->fetchColumn() ?: null;
        }
        if ($accion === 'eliminar') {
            borrar_archivo($actual);
            $pdo->prepare('DELETE FROM slides WHERE id = ?')->execute([$id]);
            flash('success', 'Diapositiva eliminada.');
            redirect('slides.php');
        }
        if ($accion === 'toggle') {
            $pdo->prepare('UPDATE slides SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            redirect('slides.php');
        }
        $img = procesar_imagen('imagen', 'slides', $actual);
        if (!$img) throw new RuntimeException('La diapositiva necesita una imagen.');
        $d = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'subtitulo' => trim($_POST['subtitulo'] ?? ''),
            'imagen' => $img,
            'boton_texto' => trim($_POST['boton_texto'] ?? ''),
            'boton_link' => trim($_POST['boton_link'] ?? '#mapa'),
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];
        if ($id) {
            $d['id'] = $id;
            $pdo->prepare('UPDATE slides SET titulo=:titulo, subtitulo=:subtitulo, imagen=:imagen, boton_texto=:boton_texto, boton_link=:boton_link, activo=:activo WHERE id=:id')->execute($d);
        } else {
            $d['orden'] = (int)$pdo->query('SELECT COALESCE(MAX(orden),0)+1 FROM slides')->fetchColumn();
            $pdo->prepare('INSERT INTO slides (titulo, subtitulo, imagen, boton_texto, boton_link, activo, orden) VALUES (:titulo,:subtitulo,:imagen,:boton_texto,:boton_link,:activo,:orden)')->execute($d);
            $id = (int)$pdo->lastInsertId();
        }
        flash('success', 'Diapositiva guardada.');
        redirect('slides.php?editar=' . $id);
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
        redirect('slides.php' . ($id ? '?editar=' . $id : ''));
    }
}

$slides = $pdo->query('SELECT * FROM slides ORDER BY orden, id')->fetchAll();
$edit = ['id' => 0, 'titulo' => '', 'subtitulo' => '', 'imagen' => null, 'boton_texto' => 'Explorar', 'boton_link' => '#mapa', 'activo' => 1];
foreach ($slides as $s) if ($s['id'] == $editId) $edit = $s;

$titulo = 'Portada (slider)';
$seccion = 'slides';
$subtitulo = 'Las imágenes grandes que rotan al abrir el sitio';
$acciones = '<a href="slides.php?editar=0#form" class="btn btn-lapacho btn-sm"><i class="fa-solid fa-plus me-1"></i> Nueva</a>';
$usarSortable = true;
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-xl-7">
    <p class="small text-muted"><i class="fa-solid fa-up-down-left-right"></i> Arrastrá para cambiar el orden.</p>
    <div class="slide-list" id="slideList" data-csrf="<?= csrf_token() ?>">
      <?php foreach ($slides as $s): ?>
        <div class="slide-item <?= $s['activo'] ? '' : 'is-off' ?> <?= $edit['id'] == $s['id'] ? 'active' : '' ?>" data-id="<?= $s['id'] ?>">
          <span class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></span>
          <div class="sthumb" style="background-image:url('<?= e(img_url($s['imagen'], P)) ?>')"></div>
          <div class="flex-grow-1"><strong><?= e($s['titulo'] ?: '(sin título)') ?></strong><br><small class="text-muted"><?= e(mb_strimwidth($s['subtitulo'], 0, 80, '…')) ?></small></div>
          <a href="?editar=<?= $s['id'] ?>#form" class="btn btn-sm btn-light"><i class="fa-solid fa-pen"></i></a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="toggle"><input type="hidden" name="id" value="<?= $s['id'] ?>">
            <button class="btn btn-sm btn-light" title="<?= $s['activo'] ? 'Ocultar' : 'Mostrar' ?>"><i class="fa-solid <?= $s['activo'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button></form>
          <form method="post" data-confirm="¿Eliminar esta diapositiva?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $s['id'] ?>">
            <button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
        </div>
      <?php endforeach; ?>
      <?php if (!$slides): ?><div class="panel empty-state"><i class="fa-solid fa-panorama"></i><p>No hay diapositivas. Se mostrará un fondo verde.</p></div><?php endif; ?>
    </div>
  </div>
  <div class="col-xl-5" id="form">
    <form class="panel sticky-xl" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
      <div class="panel-head"><h2><i class="fa-solid fa-<?= $edit['id'] ? 'pen' : 'plus' ?>"></i> <?= $edit['id'] ? 'Editar diapositiva' : 'Nueva diapositiva' ?></h2></div>
      <div class="row g-3">
        <div class="col-12"><?= campo_imagen('imagen', $edit['imagen'], 'Imagen de fondo', true) ?></div>
        <div class="col-12"><label class="form-label fw-semibold">Título</label><input type="text" name="titulo" class="form-control" value="<?= e($edit['titulo']) ?>" maxlength="150"></div>
        <div class="col-12"><label class="form-label fw-semibold">Subtítulo</label><textarea name="subtitulo" class="form-control" rows="2" maxlength="255"><?= e($edit['subtitulo']) ?></textarea></div>
        <div class="col-6"><label class="form-label fw-semibold">Texto del botón</label><input type="text" name="boton_texto" class="form-control" value="<?= e($edit['boton_texto']) ?>"></div>
        <div class="col-6"><label class="form-label fw-semibold">Enlace</label>
          <select name="boton_link" class="form-select">
            <?php foreach (['#mapa' => 'Mapa', '#departamentos' => 'Departamentos', '#destacados' => 'Destacados', '#pueblos' => 'Pueblos', '#juegos' => 'Juegos', '#galeria' => 'Galería', '#resenas' => 'Reseñas'] as $v => $t): ?>
              <option value="<?= $v ?>" <?= $edit['boton_link'] === $v ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" id="sact" <?= $edit['activo'] ? 'checked' : '' ?>><label class="form-check-label" for="sact">Activa</label></div></div>
      </div>
      <hr><button class="btn btn-lapacho w-100"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
