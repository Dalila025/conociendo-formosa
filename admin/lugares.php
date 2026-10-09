<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    switch ($_POST['accion'] ?? '') {
        case 'eliminar':
            $imgs = $pdo->prepare('SELECT url FROM imagenes WHERE lugar_id = ?');
            $imgs->execute([$id]);
            foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $u) borrar_archivo($u);
            $pdo->prepare('DELETE FROM lugares WHERE id = ?')->execute([$id]);
            flash('success', 'Lugar eliminado junto con sus fotos y preguntas.');
            break;
        case 'toggle':
            $pdo->prepare('UPDATE lugares SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            flash('success', 'Visibilidad actualizada.');
            break;
        case 'destacar':
            $pdo->prepare('UPDATE lugares SET destacado = 1 - destacado WHERE id = ?')->execute([$id]);
            flash('success', 'Destacado actualizado.');
            break;
    }
    redirect('lugares.php?' . http_build_query(array_intersect_key($_GET, array_flip(['depto', 'cat', 'q']))));
}

$depto = (int)($_GET['depto'] ?? 0);
$cat = $_GET['cat'] ?? '';
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($depto) { $where[] = 'l.departamento_id = ?'; $params[] = $depto; }
if (isset(categorias()[$cat])) { $where[] = 'l.categoria = ?'; $params[] = $cat; }
if ($q !== '') { $where[] = '(l.nombre LIKE ? OR l.resumen LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

$sql = 'SELECT l.*, d.nombre depto, d.color,
          (SELECT url FROM imagenes i WHERE i.lugar_id = l.id ORDER BY principal DESC, orden LIMIT 1) foto,
          (SELECT COUNT(*) FROM imagenes i WHERE i.lugar_id = l.id) nfotos,
          (SELECT COUNT(*) FROM preguntas p WHERE p.lugar_id = l.id) npreg
        FROM lugares l JOIN departamentos d ON d.id = l.departamento_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY d.orden, l.nombre';
$st = $pdo->prepare($sql);
$st->execute($params);
$lugares = $st->fetchAll();
$deptos = $pdo->query('SELECT id, nombre FROM departamentos ORDER BY orden')->fetchAll();

$titulo = 'Lugares';
$seccion = 'lugares';
$subtitulo = count($lugares) . ' lugares de cultura, naturaleza y paseo';
$acciones = '<a href="lugar_form.php" class="btn btn-lapacho btn-sm"><i class="fa-solid fa-plus me-1"></i> Nuevo lugar</a>';
require __DIR__ . '/includes/header.php';
?>
<form class="panel filters mb-4" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small">Buscar</label>
      <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span><input type="search" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Nombre o resumen…"></div>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Departamento</label>
      <select name="depto" class="form-select" data-autosubmit>
        <option value="0">Todos</option>
        <?php foreach ($deptos as $d): ?><option value="<?= $d['id'] ?>" <?= $depto == $d['id'] ? 'selected' : '' ?>><?= e($d['nombre']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Categoría</label>
      <select name="cat" class="form-select" data-autosubmit>
        <option value="">Todas</option>
        <?php foreach (categorias() as $k => $c): ?><option value="<?= $k ?>" <?= $cat === $k ? 'selected' : '' ?>><?= $c['nombre'] ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-dark flex-grow-1"><i class="fa-solid fa-filter"></i> Filtrar</button>
      <a href="lugares.php" class="btn btn-light" title="Limpiar"><i class="fa-solid fa-xmark"></i></a>
    </div>
  </div>
</form>

<?php if (!$lugares): ?>
  <div class="panel empty-state"><i class="fa-solid fa-map-location-dot"></i><p>No hay lugares con esos filtros.</p><a href="lugar_form.php" class="btn btn-lapacho">Crear el primero</a></div>
<?php else: ?>
<div class="place-grid">
  <?php foreach ($lugares as $l): ?>
    <article class="place-card <?= $l['activo'] ? '' : 'is-off' ?>">
      <div class="place-img" style="<?= $l['foto'] ? "background-image:url('" . e(img_url($l['foto'], P)) . "')" : "background:linear-gradient(135deg," . e($l['color']) . ",#0E3B2E)" ?>">
        <?= $l['foto'] ? '' : '<i class="fa-regular fa-image"></i>' ?>
        <div class="place-badges"><?= badge_categoria($l['categoria']) ?><?= $l['destacado'] ? ' <span class="badge bg-warning text-dark"><i class="fa-solid fa-star"></i></span>' : '' ?></div>
        <?php if (!$l['activo']): ?><span class="off-label"><i class="fa-solid fa-eye-slash"></i> Oculto</span><?php endif; ?>
      </div>
      <div class="place-body">
        <small class="text-muted"><i class="fa-solid fa-map-pin" style="color:<?= e($l['color']) ?>"></i> <?= e($l['depto']) ?></small>
        <h3><?= e($l['nombre']) ?></h3>
        <div class="place-stats">
          <a href="imagenes.php?lugar=<?= $l['id'] ?>"><i class="fa-solid fa-images"></i> <?= $l['nfotos'] ?></a>
          <a href="preguntas.php?lugar=<?= $l['id'] ?>"><i class="fa-solid fa-circle-question"></i> <?= $l['npreg'] ?></a>
          <span><i class="fa-regular fa-eye"></i> <?= (int)$l['visitas'] ?></span>
        </div>
      </div>
      <div class="place-actions">
        <a href="lugar_form.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-dark"><i class="fa-solid fa-pen"></i> Editar</a>
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>"><input type="hidden" name="accion" value="destacar">
          <button class="btn btn-sm btn-light" title="<?= $l['destacado'] ? 'Quitar de destacados' : 'Destacar' ?>"><i class="fa-<?= $l['destacado'] ? 'solid' : 'regular' ?> fa-star text-warning"></i></button>
        </form>
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>"><input type="hidden" name="accion" value="toggle">
          <button class="btn btn-sm btn-light" title="<?= $l['activo'] ? 'Ocultar' : 'Mostrar' ?>"><i class="fa-solid <?= $l['activo'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
        </form>
        <form method="post" data-confirm="¿Eliminar «<?= e($l['nombre']) ?>»? Se borrarán también sus fotos y preguntas.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>"><input type="hidden" name="accion" value="eliminar">
          <button class="btn btn-sm btn-light text-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
