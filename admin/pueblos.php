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
        $actual = null;
        if ($id) {
            $s = $pdo->prepare('SELECT imagen FROM pueblos WHERE id = ?');
            $s->execute([$id]);
            $actual = $s->fetchColumn() ?: null;
        }
        if ($accion === 'eliminar') {
            borrar_archivo($actual);
            $pdo->prepare('DELETE FROM pueblos WHERE id = ?')->execute([$id]);
            flash('success', 'Pueblo eliminado.');
            redirect('pueblos.php');
        }
        $nombre = trim($_POST['nombre'] ?? '');
        if (mb_strlen($nombre) < 2) throw new RuntimeException('Ingresá el nombre.');
        $d = [
            'nombre' => $nombre,
            'nombre_impuesto' => trim($_POST['nombre_impuesto'] ?? '') ?: null,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'color' => preg_match('/^#[0-9a-f]{6}$/i', $_POST['color'] ?? '') ? $_POST['color'] : '#C2552F',
            'icono' => preg_match('/^fa-[a-z0-9-]+$/', $_POST['icono'] ?? '') ? $_POST['icono'] : 'fa-people-group',
            'imagen' => procesar_imagen('imagen', 'pueblos', $actual),
            'orden' => (int)($_POST['orden'] ?? 0),
        ];
        if ($id) {
            $d['id'] = $id;
            $pdo->prepare('UPDATE pueblos SET nombre=:nombre, nombre_impuesto=:nombre_impuesto, descripcion=:descripcion, color=:color, icono=:icono, imagen=:imagen, orden=:orden WHERE id=:id')->execute($d);
        } else {
            $pdo->prepare('INSERT INTO pueblos (nombre, nombre_impuesto, descripcion, color, icono, imagen, orden) VALUES (:nombre,:nombre_impuesto,:descripcion,:color,:icono,:imagen,:orden)')->execute($d);
            $id = (int)$pdo->lastInsertId();
        }
        flash('success', 'Pueblo guardado.');
        redirect('pueblos.php?editar=' . $id);
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
        redirect('pueblos.php' . ($id ? '?editar=' . $id : ''));
    }
}

$pueblos = $pdo->query('SELECT * FROM pueblos ORDER BY orden, id')->fetchAll();
$edit = ['id' => 0, 'nombre' => '', 'nombre_impuesto' => '', 'descripcion' => '', 'color' => '#C2552F', 'icono' => 'fa-people-group', 'imagen' => null, 'orden' => count($pueblos) + 1];
foreach ($pueblos as $p) if ($p['id'] == $editId) $edit = $p;
$iconos = ['fa-people-group', 'fa-feather', 'fa-feather-pointed', 'fa-leaf', 'fa-water', 'fa-fish', 'fa-tree', 'fa-sun', 'fa-moon', 'fa-fire', 'fa-hand-holding-heart', 'fa-drum', 'fa-seedling', 'fa-mountain-sun', 'fa-dove'];

$titulo = 'Pueblos originarios';
$seccion = 'pueblos';
$acciones = '<a href="pueblos.php?editar=0#form" class="btn btn-lapacho btn-sm"><i class="fa-solid fa-plus me-1"></i> Nuevo</a>';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="row g-3">
      <?php foreach ($pueblos as $p): ?>
        <div class="col-md-6">
          <a href="?editar=<?= $p['id'] ?>#form" class="pueblo-admin <?= $edit['id'] == $p['id'] ? 'active' : '' ?>" style="--c:<?= e($p['color']) ?>">
            <span class="pi"><i class="fa-solid <?= e($p['icono']) ?>"></i></span>
            <div><strong><?= e($p['nombre']) ?></strong><?= $p['nombre_impuesto'] ? ' <small class="text-muted">(' . e($p['nombre_impuesto']) . ')</small>' : '' ?><p><?= e(mb_strimwidth($p['descripcion'], 0, 110, '…')) ?></p></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-xl-5" id="form">
    <form class="panel sticky-xl" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
      <div class="panel-head"><h2><i class="fa-solid fa-<?= $edit['id'] ? 'pen' : 'plus' ?>"></i> <?= $edit['id'] ? 'Editar ' . e($edit['nombre']) : 'Nuevo pueblo' ?></h2></div>
      <div class="row g-3">
        <div class="col-8"><label class="form-label fw-semibold">Nombre *</label><input type="text" name="nombre" class="form-control" value="<?= e($edit['nombre']) ?>" required></div>
        <div class="col-4"><label class="form-label fw-semibold">Color</label><input type="color" name="color" class="form-control form-control-color w-100" value="<?= e($edit['color']) ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">Nombre impuesto <small class="text-muted fw-normal">(cómo se los nombró desde afuera, ej. «Tobas»)</small></label><input type="text" name="nombre_impuesto" class="form-control" value="<?= e($edit['nombre_impuesto']) ?>" maxlength="80"></div>
        <div class="col-12"><label class="form-label fw-semibold">Descripción</label><textarea name="descripcion" class="form-control" rows="5"><?= e($edit['descripcion']) ?></textarea></div>
        <div class="col-9">
          <label class="form-label fw-semibold">Ícono</label>
          <div class="icon-picker">
            <?php foreach ($iconos as $ic): ?>
              <input type="radio" class="btn-check" name="icono" id="ic_<?= $ic ?>" value="<?= $ic ?>" <?= $edit['icono'] === $ic ? 'checked' : '' ?>>
              <label class="btn btn-light" for="ic_<?= $ic ?>"><i class="fa-solid <?= $ic ?>"></i></label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-3"><label class="form-label fw-semibold">Orden</label><input type="number" name="orden" class="form-control" value="<?= (int)$edit['orden'] ?>"></div>
        <div class="col-12"><?= campo_imagen('imagen', $edit['imagen'], 'Imagen (opcional)') ?></div>
      </div>
      <hr><button class="btn btn-lapacho w-100"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
    </form>
    <?php if ($edit['id']): ?>
      <form method="post" class="mt-2 text-end" data-confirm="¿Eliminar «<?= e($edit['nombre']) ?>»?">
        <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <button class="btn btn-link text-danger btn-sm"><i class="fa-solid fa-trash me-1"></i> Eliminar</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
