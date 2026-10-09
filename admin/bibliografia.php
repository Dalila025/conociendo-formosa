<?php
/** Fuentes y bibliografía generales del sitio */
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();
$cats = categorias_bibliografia();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($accion === 'eliminar') {
        $pdo->prepare('DELETE FROM bibliografia WHERE id = ?')->execute([$id]);
        flash('success', 'Fuente eliminada.');
    } elseif ($accion === 'guardar') {
        $cita = trim($_POST['cita'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $cat = isset($cats[$_POST['categoria'] ?? '']) ? $_POST['categoria'] : 'oficial';
        if (mb_strlen($cita) < 5) {
            flash('danger', 'Escribí la cita completa de la fuente.');
        } elseif ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            flash('danger', 'El enlace no es válido.');
        } else {
            $d = [$cat, $cita, $url ?: null, (int)($_POST['orden'] ?? 0)];
            if ($id) {
                $d[] = $id;
                $pdo->prepare('UPDATE bibliografia SET categoria = ?, cita = ?, url = ?, orden = ? WHERE id = ?')->execute($d);
                flash('success', 'Fuente actualizada.');
            } else {
                $pdo->prepare('INSERT INTO bibliografia (categoria, cita, url, orden) VALUES (?,?,?,?)')->execute($d);
                flash('success', 'Fuente agregada.');
            }
        }
    }
    redirect('bibliografia.php');
}

$items = $pdo->query('SELECT * FROM bibliografia ORDER BY FIELD(categoria, "programa","oficial","academica","audiovisual","periodistica"), orden, id')->fetchAll();
$porCat = [];
foreach ($items as $b) $porCat[$b['categoria']][] = $b;

$titulo = 'Fuentes y bibliografía';
$seccion = 'bibliografia';
$subtitulo = 'Se muestran en la sección «Fuentes» del sitio. Las fuentes de cada lugar se editan en su ficha.';
$acciones = '<button class="btn btn-lapacho btn-sm" data-bs-toggle="modal" data-bs-target="#modalFuente"><i class="fa-solid fa-plus me-1"></i> Nueva fuente</button>';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <?php foreach ($cats as $k => $t): ?>
    <div class="col-xl-6">
      <div class="panel h-100">
        <div class="panel-head"><h2><i class="fa-solid fa-book"></i> <?= e($t) ?></h2><span class="badge bg-light text-dark"><?= count($porCat[$k] ?? []) ?></span></div>
        <?php if (empty($porCat[$k])): ?>
          <p class="text-muted small mb-0">Sin fuentes en esta categoría.</p>
        <?php else: foreach ($porCat[$k] as $b): ?>
          <div class="biblio-admin">
            <div class="flex-grow-1">
              <p><?= e($b['cita']) ?></p>
              <?php if ($b['url']): ?><a href="<?= e($b['url']) ?>" target="_blank" rel="noopener" class="small"><i class="fa-solid fa-link"></i> <?= e(parse_url($b['url'], PHP_URL_HOST)) ?></a><?php endif; ?>
            </div>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalFuente" data-fuente='<?= e(json_encode($b, JSON_UNESCAPED_UNICODE)) ?>'><i class="fa-solid fa-pen"></i></button>
            <form method="post" data-confirm="¿Eliminar esta fuente?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $b['id'] ?>">
              <button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="modal fade" id="modalFuente" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" method="post" id="formFuente">
      <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="">
      <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-book-bookmark me-2"></i><span>Nueva fuente</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-8"><label class="form-label fw-semibold">Categoría</label>
            <select name="categoria" class="form-select"><?php foreach ($cats as $k => $t): ?><option value="<?= $k ?>"><?= e($t) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Orden</label><input type="number" name="orden" class="form-control" value="0"></div>
          <div class="col-12"><label class="form-label fw-semibold">Cita completa *</label><textarea name="cita" class="form-control" rows="3" required placeholder="Autor (año). Título. Editorial / Organismo."></textarea></div>
          <div class="col-12"><label class="form-label fw-semibold">Enlace (opcional)</label><input type="url" name="url" class="form-control" placeholder="https://…"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lapacho"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
    </form>
  </div>
</div>
<?php
$scriptsExtra = <<<JS
<script>
document.getElementById('modalFuente').addEventListener('show.bs.modal', (e) => {
  const f = document.getElementById('formFuente');
  const d = e.relatedTarget && e.relatedTarget.dataset.fuente ? JSON.parse(e.relatedTarget.dataset.fuente) : null;
  f.reset();
  f.querySelector('.modal-title span').textContent = d ? 'Editar fuente' : 'Nueva fuente';
  f.elements['id'].value = d ? d.id : '';
  if (d) { f.elements['categoria'].value = d.categoria; f.elements['orden'].value = d.orden; f.elements['cita'].value = d.cita; f.elements['url'].value = d.url || ''; }
});
</script>
JS;
require __DIR__ . '/includes/footer.php';
