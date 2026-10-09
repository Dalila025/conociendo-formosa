<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();
$editId = (int)($_GET['editar'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($accion === 'eliminar') {
            $n = $pdo->prepare('SELECT COUNT(*) FROM lugares WHERE departamento_id = ?');
            $n->execute([$id]);
            if ($n->fetchColumn()) throw new RuntimeException('No se puede eliminar: el departamento tiene lugares. Movelos o eliminalos primero.');
            $img = $pdo->prepare('SELECT imagen FROM departamentos WHERE id = ?');
            $img->execute([$id]);
            borrar_archivo($img->fetchColumn() ?: null);
            $pdo->prepare('DELETE FROM departamentos WHERE id = ?')->execute([$id]);
            flash('success', 'Departamento eliminado.');
            redirect('departamentos.php');
        }
        if ($accion === 'guardar') {
            $nombre = trim($_POST['nombre'] ?? '');
            if (mb_strlen($nombre) < 2) throw new RuntimeException('Ingresá el nombre del departamento.');
            $actual = null;
            if ($id) {
                $s = $pdo->prepare('SELECT imagen FROM departamentos WHERE id = ?');
                $s->execute([$id]);
                $actual = $s->fetchColumn() ?: null;
            }
            $d = [
                'nombre' => $nombre,
                'slug' => slug_unico('departamentos', $nombre, $id),
                'cabecera' => trim($_POST['cabecera'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'color' => preg_match('/^#[0-9a-f]{6}$/i', $_POST['color'] ?? '') ? $_POST['color'] : '#1F9E89',
                'imagen' => procesar_imagen('imagen', 'departamentos', $actual),
                'orden' => (int)($_POST['orden'] ?? 0),
            ];
            if ($id) {
                $d['id'] = $id;
                $pdo->prepare('UPDATE departamentos SET nombre=:nombre, slug=:slug, cabecera=:cabecera, descripcion=:descripcion, color=:color, imagen=:imagen, orden=:orden WHERE id=:id')->execute($d);
            } else {
                $pdo->prepare('INSERT INTO departamentos (nombre, slug, cabecera, descripcion, color, imagen, orden) VALUES (:nombre,:slug,:cabecera,:descripcion,:color,:imagen,:orden)')->execute($d);
                $id = (int)$pdo->lastInsertId();
            }
            flash('success', 'Departamento guardado.');
            redirect('departamentos.php?editar=' . $id);
        }
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
        redirect('departamentos.php' . ($id ? '?editar=' . $id : ''));
    }
}

$deptos = $pdo->query('SELECT d.*, (SELECT COUNT(*) FROM lugares l WHERE l.departamento_id = d.id) n FROM departamentos d ORDER BY orden, nombre')->fetchAll();
$edit = ['id' => 0, 'nombre' => '', 'cabecera' => '', 'descripcion' => '', 'color' => '#1F9E89', 'imagen' => null, 'orden' => count($deptos) + 1];
foreach ($deptos as $d) if ($d['id'] == $editId) $edit = $d;

$titulo = 'Departamentos';
$seccion = 'departamentos';
$subtitulo = 'Los 9 departamentos de la provincia, con su color e imagen';
$acciones = '<a href="departamentos.php?editar=0#form" class="btn btn-lapacho btn-sm"><i class="fa-solid fa-plus me-1"></i> Nuevo</a>';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="depto-admin-grid">
      <?php foreach ($deptos as $d): ?>
        <a href="?editar=<?= $d['id'] ?>#form" class="depto-admin <?= $edit['id'] == $d['id'] ? 'active' : '' ?>" style="--c:<?= e($d['color']) ?>">
          <div class="dimg" style="<?= $d['imagen'] ? "background-image:url('" . e(img_url($d['imagen'], P)) . "')" : '' ?>"></div>
          <div class="dbody"><strong><?= e($d['nombre']) ?></strong><small><?= e($d['cabecera']) ?></small><span class="badge bg-light text-dark mt-1"><?= $d['n'] ?> lugares</span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-xl-5" id="form">
    <form class="panel sticky-xl" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
      <div class="panel-head"><h2><i class="fa-solid fa-<?= $edit['id'] ? 'pen' : 'plus' ?>"></i> <?= $edit['id'] ? 'Editar ' . e($edit['nombre']) : 'Nuevo departamento' ?></h2></div>
      <div class="row g-3">
        <div class="col-8"><label class="form-label fw-semibold">Nombre *</label><input type="text" name="nombre" class="form-control" value="<?= e($edit['nombre']) ?>" required></div>
        <div class="col-4"><label class="form-label fw-semibold">Color</label><input type="color" name="color" class="form-control form-control-color w-100" value="<?= e($edit['color']) ?>"></div>
        <div class="col-8"><label class="form-label fw-semibold">Cabecera</label><input type="text" name="cabecera" class="form-control" value="<?= e($edit['cabecera']) ?>"></div>
        <div class="col-4"><label class="form-label fw-semibold">Orden</label><input type="number" name="orden" class="form-control" value="<?= (int)$edit['orden'] ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">Descripción</label><textarea name="descripcion" class="form-control" rows="4"><?= e($edit['descripcion']) ?></textarea></div>
        <div class="col-12"><?= campo_imagen('imagen', $edit['imagen'], 'Imagen de portada') ?></div>
      </div>
      <hr>
      <div class="d-flex gap-2">
        <button class="btn btn-lapacho flex-grow-1"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
        <?php if ($edit['id']): ?>
          <a href="lugares.php?depto=<?= $edit['id'] ?>" class="btn btn-light" title="Ver lugares"><i class="fa-solid fa-location-dot"></i></a>
        <?php endif; ?>
      </div>
    </form>
    <?php if ($edit['id']): ?>
      <form method="post" class="mt-2 text-end" data-confirm="¿Eliminar el departamento <?= e($edit['nombre']) ?>?">
        <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <button class="btn btn-link text-danger btn-sm"><i class="fa-solid fa-trash me-1"></i> Eliminar departamento</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
