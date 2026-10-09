<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$lugar = [
    'departamento_id' => 0, 'nombre' => '', 'categoria' => 'cultura', 'resumen' => '', 'descripcion' => '',
    'dato_curioso' => '', 'importancia' => '', 'habitantes' => '', 'fuentes' => '', 'como_llegar' => '', 'lat' => -24.9, 'lng' => -59.9, 'map_x' => null, 'map_y' => null, 'destacado' => 0, 'activo' => 1,
];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM lugares WHERE id = ?');
    $st->execute([$id]);
    $lugar = $st->fetch() ?: redirect('lugares.php');
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'departamento_id' => (int)($_POST['departamento_id'] ?? 0),
        'nombre'          => trim($_POST['nombre'] ?? ''),
        'categoria'       => $_POST['categoria'] ?? '',
        'resumen'         => trim($_POST['resumen'] ?? ''),
        'descripcion'     => trim($_POST['descripcion'] ?? ''),
        'dato_curioso'    => trim($_POST['dato_curioso'] ?? ''),
        'importancia'     => trim($_POST['importancia'] ?? ''),
        'habitantes'      => trim($_POST['habitantes'] ?? ''),
        'fuentes'         => trim($_POST['fuentes'] ?? ''),
        'como_llegar'     => trim($_POST['como_llegar'] ?? ''),
        'lat'             => (float)str_replace(',', '.', $_POST['lat'] ?? 0),
        'lng'             => (float)str_replace(',', '.', $_POST['lng'] ?? 0),
        'map_x'           => ($_POST['map_x'] ?? '') !== '' ? max(0, min(100, (float)$_POST['map_x'])) : null,
        'map_y'           => ($_POST['map_y'] ?? '') !== '' ? max(0, min(100, (float)$_POST['map_y'])) : null,
        'destacado'       => isset($_POST['destacado']) ? 1 : 0,
        'activo'          => isset($_POST['activo']) ? 1 : 0,
    ];
    if (mb_strlen($d['nombre']) < 3) $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    if (!isset(categorias()[$d['categoria']])) $errores[] = 'Elegí una categoría válida.';
    $chk = $pdo->prepare('SELECT COUNT(*) FROM departamentos WHERE id = ?');
    $chk->execute([$d['departamento_id']]);
    if (!$chk->fetchColumn()) $errores[] = 'Elegí un departamento.';
    if ($d['lat'] < -27.5 || $d['lat'] > -21.5 || $d['lng'] < -63.5 || $d['lng'] > -57) $errores[] = 'Las coordenadas no corresponden a Formosa. Marcá el punto en el mapa.';
    if (mb_strlen($d['resumen']) > 255) $errores[] = 'El resumen no puede superar 255 caracteres.';

    if (!$errores) {
        $d['slug'] = slug_unico('lugares', $d['nombre'], $id);
        if ($id) {
            $d['id'] = $id;
            $pdo->prepare('UPDATE lugares SET departamento_id=:departamento_id, nombre=:nombre, slug=:slug, categoria=:categoria, resumen=:resumen,
                           descripcion=:descripcion, dato_curioso=:dato_curioso, importancia=:importancia, habitantes=:habitantes, fuentes=:fuentes, como_llegar=:como_llegar, lat=:lat, lng=:lng, map_x=:map_x, map_y=:map_y,
                           destacado=:destacado, activo=:activo WHERE id=:id')->execute($d);
            flash('success', 'Lugar actualizado.');
        } else {
            $pdo->prepare('INSERT INTO lugares (departamento_id, nombre, slug, categoria, resumen, descripcion, dato_curioso, importancia, habitantes, fuentes, como_llegar, lat, lng, map_x, map_y, destacado, activo)
                           VALUES (:departamento_id, :nombre, :slug, :categoria, :resumen, :descripcion, :dato_curioso, :importancia, :habitantes, :fuentes, :como_llegar, :lat, :lng, :map_x, :map_y, :destacado, :activo)')->execute($d);
            $id = (int)$pdo->lastInsertId();
            flash('success', 'Lugar creado. Ahora sumale fotos ✨');
            redirect('imagenes.php?lugar=' . $id);
        }
        redirect('lugar_form.php?id=' . $id);
    }
    $lugar = array_merge($lugar, $d);
}

$deptos = $pdo->query('SELECT id, nombre, color FROM departamentos ORDER BY orden')->fetchAll();
$titulo = $id ? 'Editar lugar' : 'Nuevo lugar';
$seccion = 'lugares';
$subtitulo = $id ? e($lugar['nombre']) : 'Completá los datos y marcá su ubicación en el mapa';
$acciones = $id ? '<a href="imagenes.php?lugar=' . $id . '" class="btn btn-light btn-sm"><i class="fa-solid fa-images me-1"></i> Fotos</a>
                  <a href="preguntas.php?lugar=' . $id . '" class="btn btn-light btn-sm"><i class="fa-solid fa-circle-question me-1"></i> Preguntas</a>' : '';
$usarMapa = true;
require __DIR__ . '/includes/header.php';
?>
<?php if ($errores): ?>
  <div class="alert alert-danger"><strong>Revisá estos puntos:</strong><ul class="mb-0"><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="row g-4" novalidate>
  <?= csrf_field() ?>
  <div class="col-xl-7">
    <div class="panel">
      <div class="panel-head"><h2><i class="fa-solid fa-circle-info"></i> Información</h2></div>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold">Nombre *</label>
          <input type="text" name="nombre" class="form-control form-control-lg" value="<?= e($lugar['nombre']) ?>" required maxlength="150">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Departamento *</label>
          <select name="departamento_id" class="form-select" required>
            <option value="">Elegí…</option>
            <?php foreach ($deptos as $d): ?><option value="<?= $d['id'] ?>" <?= $lugar['departamento_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['nombre']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Categoría *</label>
          <div class="cat-picker">
            <?php foreach (categorias() as $k => $c): ?>
              <input type="radio" class="btn-check" name="categoria" id="cat_<?= $k ?>" value="<?= $k ?>" <?= $lugar['categoria'] === $k ? 'checked' : '' ?>>
              <label class="btn" for="cat_<?= $k ?>" style="--c:<?= $c['color'] ?>"><i class="fa-solid <?= $c['icono'] ?>"></i> <?= $c['nombre'] ?></label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Resumen <small class="text-muted fw-normal">(aparece en tarjetas y en el mapa)</small></label>
          <input type="text" name="resumen" class="form-control" value="<?= e($lugar['resumen']) ?>" maxlength="255" data-count>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold"><span class="q-num">1</span> ¿Qué es? <small class="text-muted fw-normal">(su historia)</small></label>
          <textarea name="descripcion" class="form-control" rows="6"><?= e($lugar['descripcion']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold"><span class="q-num q2">2</span> ¿Por qué es importante? <small class="text-muted fw-normal">(flora, fauna y valor)</small></label>
          <textarea name="importancia" class="form-control" rows="5"><?= e($lugar['importancia']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold"><span class="q-num q3">3</span> ¿Quiénes lo habitan? <small class="text-muted fw-normal">(culturas, antepasados, pueblos originarios)</small></label>
          <textarea name="habitantes" class="form-control" rows="5"><?= e($lugar['habitantes']) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="fa-solid fa-lightbulb text-warning"></i> Dato curioso</label>
          <textarea name="dato_curioso" class="form-control" rows="3"><?= e($lugar['dato_curioso']) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="fa-solid fa-route text-success"></i> Cómo llegar</label>
          <textarea name="como_llegar" class="form-control" rows="3"><?= e($lugar['como_llegar']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold"><i class="fa-solid fa-book-bookmark text-primary"></i> Fuentes de este lugar</label>
          <textarea name="fuentes" class="form-control font-monospace small" rows="4" placeholder="Una fuente por línea: Cita completa|https://enlace (el enlace es opcional)"><?= e($lugar['fuentes']) ?></textarea>
          <div class="form-text">Una por línea. Separá la cita del enlace con «|». Ej.: <code>Administración de Parques Nacionales. Ficha del PN Río Pilcomayo.|https://…</code></div>
        </div>
        <div class="col-12 d-flex flex-wrap gap-4">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" <?= $lugar['activo'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Visible en el sitio</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="destacado" name="destacado" <?= $lugar['destacado'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="destacado">Mostrar en «Destacados»</label>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="panel sticky-xl">
      <div class="panel-head"><h2><i class="fa-solid fa-map-location-dot"></i> Ubicación</h2></div>
      <label class="form-label fw-semibold mb-1">En el mapa ilustrado del sitio</label>
      <p class="small text-muted mb-2">Hacé clic sobre el dibujo para poner el marcador. <span id="pickHint"><?= $lugar['map_x'] === null ? 'Todavía no tiene posición: se mostrará en el centro del departamento.' : '' ?></span></p>
      <div class="svg-picker" id="svgPicker">
        <?= file_get_contents(__DIR__ . '/../assets/img/mapa-formosa.svg') ?>
        <span class="svg-pin" id="svgPin" style="<?= $lugar['map_x'] === null ? 'display:none' : 'left:' . (float)$lugar['map_x'] . '%;top:' . (float)$lugar['map_y'] . '%' ?>"><i class="fa-solid fa-location-dot"></i></span>
      </div>
      <input type="hidden" name="map_x" id="map_x" value="<?= e($lugar['map_x']) ?>">
      <input type="hidden" name="map_y" id="map_y" value="<?= e($lugar['map_y']) ?>">
      <hr>
      <label class="form-label fw-semibold mb-1">Coordenadas reales (para «Cómo llegar» en Google Maps)</label>
      <p class="small text-muted">Hacé clic en el mapa o arrastrá el marcador para ubicar el lugar.</p>
      <div id="pickerMap" class="picker-map"></div>
      <div class="row g-2 mt-2">
        <div class="col-6"><label class="form-label small">Latitud</label><input type="text" name="lat" id="lat" class="form-control form-control-sm" value="<?= e($lugar['lat']) ?>"></div>
        <div class="col-6"><label class="form-label small">Longitud</label><input type="text" name="lng" id="lng" class="form-control form-control-sm" value="<?= e($lugar['lng']) ?>"></div>
      </div>
      <hr>
      <div class="d-flex gap-2">
        <button class="btn btn-lapacho flex-grow-1"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
        <a href="lugares.php" class="btn btn-light">Cancelar</a>
      </div>
    </div>
  </div>
</form>
<?php
$scriptsExtra = '<script>initPicker("pickerMap", "lat", "lng"); initSvgPicker();</script>';
require __DIR__ . '/includes/footer.php';
