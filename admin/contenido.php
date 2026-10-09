<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();
$items = $pdo->query('SELECT * FROM config ORDER BY FIELD(grupo, "proyecto","general","inicio","sobre","contacto","redes","resenas"), clave')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('UPDATE config SET valor = ? WHERE clave = ?');
    try {
        foreach ($items as $it) {
            $k = $it['clave'];
            if ($it['tipo'] === 'image') {
                $valor = procesar_imagen('cfg_' . $k, 'config', $it['valor'] ?: null);
            } elseif ($it['tipo'] === 'bool') {
                $valor = isset($_POST['cfg'][$k]) ? '1' : '0';
            } else {
                if (!array_key_exists($k, $_POST['cfg'] ?? [])) continue;
                $valor = trim($_POST['cfg'][$k]);
            }
            $st->execute([$valor, $k]);
        }
        flash('success', 'Textos del sitio guardados.');
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
    }
    redirect('contenido.php');
}

$grupos = [
    'proyecto' => ['Sobre este proyecto (trabajo final)', 'fa-graduation-cap'],
    'general' => ['General', 'fa-gear'], 'inicio' => ['Portada', 'fa-house'], 'sobre' => ['Sección «Sobre Formosa»', 'fa-book-open'],
    'contacto' => ['Contacto', 'fa-envelope'], 'redes' => ['Redes sociales', 'fa-share-nodes'], 'resenas' => ['Reseñas', 'fa-star'],
];
$porGrupo = [];
foreach ($items as $it) $porGrupo[$it['grupo']][] = $it;

$titulo = 'Textos del sitio';
$seccion = 'contenido';
$subtitulo = 'Editá los títulos, textos, contacto y redes que se ven en la página';
require __DIR__ . '/includes/header.php';
?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-4">
    <?php foreach ($grupos as $g => [$nombre, $ic]): if (empty($porGrupo[$g])) continue; ?>
      <div class="col-xl-6">
        <div class="panel h-100">
          <div class="panel-head"><h2><i class="fa-solid <?= $ic ?>"></i> <?= $nombre ?></h2></div>
          <?php foreach ($porGrupo[$g] as $it): $name = 'cfg[' . $it['clave'] . ']'; ?>
            <div class="mb-3">
              <?php if ($it['tipo'] === 'image'): ?>
                <?= campo_imagen('cfg_' . $it['clave'], $it['valor'] ?: null, $it['etiqueta']) ?>
              <?php elseif ($it['tipo'] === 'bool'): ?>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch" name="<?= $name ?>" id="c_<?= $it['clave'] ?>" <?= $it['valor'] === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="c_<?= $it['clave'] ?>">Moderar reseñas antes de publicarlas</label>
                  <div class="form-text">Si está activo, las reseñas nuevas quedan «pendientes» hasta que las apruebes.</div>
                </div>
              <?php elseif ($it['tipo'] === 'textarea'): ?>
                <label class="form-label fw-semibold"><?= e($it['etiqueta']) ?></label>
                <textarea name="<?= $name ?>" class="form-control" rows="<?= $it['clave'] === 'proyecto_texto' ? 12 : 3 ?>"><?= e($it['valor']) ?></textarea>
              <?php else: ?>
                <label class="form-label fw-semibold"><?= e($it['etiqueta']) ?></label>
                <input type="text" name="<?= $name ?>" class="form-control" value="<?= e($it['valor']) ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="save-bar"><span><i class="fa-solid fa-circle-info me-1"></i> Los cambios se ven al recargar el sitio.</span><button class="btn btn-lapacho"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios</button></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
