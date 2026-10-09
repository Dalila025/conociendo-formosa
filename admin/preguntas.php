<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();
$lugarId = (int)($_GET['lugar'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($accion === 'eliminar') {
        $pdo->prepare('DELETE FROM preguntas WHERE id = ?')->execute([$id]);
        flash('success', 'Pregunta eliminada.');
    } elseif ($accion === 'guardar') {
        $d = [
            'lugar_id' => (int)($_POST['lugar_id'] ?? 0),
            'pregunta' => trim($_POST['pregunta'] ?? ''),
            'opcion_a' => trim($_POST['opcion_a'] ?? ''),
            'opcion_b' => trim($_POST['opcion_b'] ?? ''),
            'opcion_c' => trim($_POST['opcion_c'] ?? ''),
            'opcion_d' => trim($_POST['opcion_d'] ?? ''),
            'correcta' => in_array($_POST['correcta'] ?? '', ['a', 'b', 'c', 'd'], true) ? $_POST['correcta'] : '',
            'explicacion' => trim($_POST['explicacion'] ?? '') ?: null,
        ];
        $faltan = !$d['lugar_id'] || mb_strlen($d['pregunta']) < 5 || !$d['correcta']
               || in_array('', [$d['opcion_a'], $d['opcion_b'], $d['opcion_c'], $d['opcion_d']], true);
        if ($faltan) {
            flash('danger', 'Completá el lugar, la pregunta, las 4 opciones y marcá la correcta.');
        } elseif ($id) {
            $d['id'] = $id;
            $pdo->prepare('UPDATE preguntas SET lugar_id=:lugar_id, pregunta=:pregunta, opcion_a=:opcion_a, opcion_b=:opcion_b, opcion_c=:opcion_c,
                           opcion_d=:opcion_d, correcta=:correcta, explicacion=:explicacion WHERE id=:id')->execute($d);
            flash('success', 'Pregunta actualizada.');
        } else {
            $pdo->prepare('INSERT INTO preguntas (lugar_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, correcta, explicacion)
                           VALUES (:lugar_id, :pregunta, :opcion_a, :opcion_b, :opcion_c, :opcion_d, :correcta, :explicacion)')->execute($d);
            flash('success', 'Pregunta agregada.');
        }
        $lugarId = $d['lugar_id'] ?: $lugarId;
    }
    redirect('preguntas.php' . ($lugarId ? '?lugar=' . $lugarId : ''));
}

$lugares = $pdo->query('SELECT l.id, l.nombre, d.nombre depto, (SELECT COUNT(*) FROM preguntas p WHERE p.lugar_id = l.id) n
                        FROM lugares l JOIN departamentos d ON d.id = l.departamento_id ORDER BY d.orden, l.nombre')->fetchAll();
$sql = 'SELECT p.*, l.nombre lugar FROM preguntas p JOIN lugares l ON l.id = p.lugar_id';
if ($lugarId) {
    $st = $pdo->prepare($sql . ' WHERE p.lugar_id = ? ORDER BY p.id');
    $st->execute([$lugarId]);
} else {
    $st = $pdo->query($sql . ' ORDER BY l.nombre, p.id');
}
$preguntas = $st->fetchAll();

$titulo = 'Preguntas del juego';
$seccion = 'preguntas';
$subtitulo = count($preguntas) . ' preguntas · se usan en la Trivia y en el desafío de cada lugar';
$acciones = '<button class="btn btn-lapacho btn-sm" data-bs-toggle="modal" data-bs-target="#modalPregunta" data-nueva><i class="fa-solid fa-plus me-1"></i> Nueva pregunta</button>';
require __DIR__ . '/includes/header.php';

function opciones_lugares(array $lugares, int $sel = 0): string
{
    $html = ''; $g = null;
    foreach ($lugares as $l) {
        if ($g !== $l['depto']) { $html .= ($g ? '</optgroup>' : '') . '<optgroup label="' . e($l['depto']) . '">'; $g = $l['depto']; }
        $html .= '<option value="' . $l['id'] . '"' . ($sel == $l['id'] ? ' selected' : '') . '>' . e($l['nombre']) . ' (' . $l['n'] . ')</option>';
    }
    return $html . ($g ? '</optgroup>' : '');
}
?>
<div class="panel mb-4">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
    <label class="fw-semibold">Filtrar por lugar:</label>
    <select name="lugar" class="form-select" style="max-width:460px" data-autosubmit>
      <option value="">Todos los lugares</option><?= opciones_lugares($lugares, $lugarId) ?>
    </select>
  </form>
</div>

<?php if (!$preguntas): ?>
  <div class="panel empty-state"><i class="fa-solid fa-circle-question"></i><p>No hay preguntas para este filtro.</p></div>
<?php else: ?>
<div class="q-list">
  <?php foreach ($preguntas as $p): ?>
    <div class="q-item">
      <div class="flex-grow-1">
        <small class="text-muted"><i class="fa-solid fa-location-dot"></i> <?= e($p['lugar']) ?></small>
        <h3><?= e($p['pregunta']) ?></h3>
        <div class="q-opts">
          <?php foreach (['a', 'b', 'c', 'd'] as $k): ?>
            <span class="<?= $p['correcta'] === $k ? 'ok' : '' ?>"><b><?= strtoupper($k) ?></b> <?= e($p['opcion_' . $k]) ?><?= $p['correcta'] === $k ? ' <i class="fa-solid fa-check"></i>' : '' ?></span>
          <?php endforeach; ?>
        </div>
        <?php if ($p['explicacion']): ?><p class="small text-muted mb-0 mt-2"><i class="fa-solid fa-comment-dots"></i> <?= e($p['explicacion']) ?></p><?php endif; ?>
      </div>
      <div class="d-flex flex-column gap-1">
        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalPregunta"
                data-pregunta='<?= e(json_encode($p, JSON_UNESCAPED_UNICODE)) ?>' title="Editar"><i class="fa-solid fa-pen"></i></button>
        <form method="post" data-confirm="¿Eliminar esta pregunta?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="btn btn-sm btn-light text-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button></form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="modalPregunta" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" method="post" id="formPregunta">
      <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="">
      <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-circle-question me-2"></i><span>Nueva pregunta</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold">Lugar *</label>
          <select name="lugar_id" class="form-select" required><option value="">Elegí…</option><?= opciones_lugares($lugares, $lugarId) ?></select></div>
        <div class="mb-3"><label class="form-label fw-semibold">Pregunta *</label><input type="text" name="pregunta" class="form-control" maxlength="255" required></div>
        <label class="form-label fw-semibold">Opciones * <small class="text-muted fw-normal">(marcá la correcta)</small></label>
        <?php foreach (['a', 'b', 'c', 'd'] as $k): ?>
          <div class="input-group mb-2 opt-row">
            <span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correcta" value="<?= $k ?>" required aria-label="Correcta <?= $k ?>"></span>
            <span class="input-group-text fw-bold"><?= strtoupper($k) ?></span>
            <input type="text" name="opcion_<?= $k ?>" class="form-control" maxlength="150" required>
          </div>
        <?php endforeach; ?>
        <div class="mt-3"><label class="form-label fw-semibold">Explicación <small class="text-muted fw-normal">(se muestra al responder)</small></label><input type="text" name="explicacion" class="form-control" maxlength="255"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lapacho"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
    </form>
  </div>
</div>
<?php
$scriptsExtra = <<<JS
<script>
document.getElementById('modalPregunta').addEventListener('show.bs.modal', (e) => {
  const f = document.getElementById('formPregunta');
  const btn = e.relatedTarget;
  const p = btn && btn.dataset.pregunta ? JSON.parse(btn.dataset.pregunta) : null;
  f.reset();
  f.querySelector('.modal-title span').textContent = p ? 'Editar pregunta' : 'Nueva pregunta';
  f.elements['id'].value = p ? p.id : '';
  if (p) {
    ['lugar_id','pregunta','opcion_a','opcion_b','opcion_c','opcion_d','explicacion'].forEach(k => f.elements[k].value = p[k] ?? '');
    f.querySelector('[name=correcta][value="' + p.correcta + '"]').checked = true;
  } else if ({$lugarId}) { f.elements['lugar_id'].value = '{$lugarId}'; }
});
</script>
JS;
require __DIR__ . '/includes/footer.php';
