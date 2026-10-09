<?php
/**
 * Conociendo Formosa · Ficha de un lugar (se abre en una pestaña nueva)
 * Desarrollado por Dalila Spindola Dev
 */
require_once __DIR__ . '/includes/funciones.php';
$pdo = db();

$slug = (string)($_GET['l'] ?? '');
$st = $pdo->prepare('SELECT l.*, d.nombre AS depto, d.slug AS depto_slug, d.color AS depto_color, d.cabecera
                     FROM lugares l JOIN departamentos d ON d.id = l.departamento_id
                     WHERE (l.slug = ? OR l.id = ?) AND l.activo = 1');
$st->execute([$slug, (int)$slug]);
$l = $st->fetch();

if (!$l) {
    http_response_code(404);
}
else {
    $pdo->prepare('UPDATE lugares SET visitas = visitas + 1 WHERE id = ?')->execute([$l['id']]);
    $im = $pdo->prepare('SELECT * FROM imagenes WHERE lugar_id = ? ORDER BY principal DESC, orden, id');
    $im->execute([$l['id']]);
    $imagenes = $im->fetchAll();
    $np = $pdo->prepare('SELECT COUNT(*) FROM preguntas WHERE lugar_id = ?');
    $np->execute([$l['id']]);
    $nPreg = (int)$np->fetchColumn();
    $ot = $pdo->prepare('SELECT l.id, l.nombre, l.slug, l.categoria, l.resumen,
                           (SELECT url FROM imagenes i WHERE i.lugar_id = l.id ORDER BY principal DESC, orden LIMIT 1) foto
                         FROM lugares l WHERE l.departamento_id = ? AND l.id <> ? AND l.activo = 1 ORDER BY l.nombre');
    $ot->execute([$l['departamento_id'], $l['id']]);
    $otros = $ot->fetchAll();
    $fuentes = parse_fuentes($l['fuentes']);
    $cat = categorias()[$l['categoria']] ?? ['nombre' => $l['categoria'], 'color' => '#888', 'icono' => 'fa-location-dot', 'descripcion' => ''];
}
$titulo = $l ? $l['nombre'] : 'Lugar no encontrado';
$embed = !empty($_GET['embed']); // abierto dentro de la ventana flotante del mapa

function parrafos(?string $txt): string
{
    $txt = trim((string)$txt);
    if ($txt === '') return '<p class="text-muted">Pronto sumaremos esta información.</p>';
    return implode('', array_map(fn($p) => '<p>' . e($p) . '</p>', preg_split('/\R\s*\R/', $txt)));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?> · <?= e(cfg('sitio_nombre', APP_NAME)) ?></title>
  <meta name="description" content="<?= e($l['resumen'] ?? '') ?>">
  <meta name="theme-color" content="#0E3B2E">
  <?php if ($l && $imagenes): ?><meta property="og:image" content="<?= e(img_url($imagenes[0]['url'])) ?>"><?php endif; ?>
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,800;1,9..144,500&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="page-lugar<?= $embed ? ' embed' : '' ?>">

<nav class="navbar fixed-top nav-glass scrolled">
  <div class="container flex-nowrap">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <span class="brand-mark"><i class="fa-solid fa-sun"></i></span>
      <span class="brand-text">Conociendo <strong>Formosa</strong></span>
    </a>
    <a href="index.php#mapa" class="btn btn-ghost btn-sm" aria-label="Volver al mapa"><i class="fa-solid fa-map"></i><span class="d-none d-sm-inline ms-1">Volver al mapa</span></a>
  </div>
</nav>

<?php if (!$l): ?>
  <main class="section text-center" style="padding-top:10rem;min-height:70vh">
    <div class="container">
      <div class="game-emoji">🧭</div>
      <h1 class="section-title">No encontramos ese lugar</h1>
      <p class="text-muted">Puede que se haya ocultado o que el enlace esté incompleto.</p>
      <a href="index.php#mapa" class="btn btn-lapacho">Ir al mapa</a>
    </div>
  </main>
<?php else: ?>

<header class="lugar-hero" style="--c:<?= e($l['depto_color']) ?>">
  <div class="lugar-hero-slides" id="heroSlides">
    <?php if ($imagenes): foreach ($imagenes as $i => $im): ?>
      <div class="lh-slide <?= $i ? '' : 'active' ?>" style="background-image:url('<?= e(img_url($im['url'])) ?>')" data-titulo="<?= e($im['titulo'] ?: $l['nombre']) ?>"></div>
    <?php endforeach; else: ?>
      <div class="lh-slide active ph" style="--c:<?= e($l['depto_color']) ?>"><i class="fa-solid <?= $cat['icono'] ?> ph-icon"></i></div>
    <?php endif; ?>
  </div>
  <div class="lugar-hero-overlay"></div>
  <div class="container lugar-hero-content">
    <nav class="crumbs" aria-label="Ubicación">
      <a href="index.php">Inicio</a><i class="fa-solid fa-chevron-right"></i>
      <a href="index.php#mapa">Mapa</a><i class="fa-solid fa-chevron-right"></i>
      <span>Depto. <?= e($l['depto']) ?></span>
    </nav>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <span class="badge-cat" style="--c:<?= $cat['color'] ?>"><i class="fa-solid <?= $cat['icono'] ?>"></i> <?= $cat['nombre'] ?></span>
      <span class="dep-pill"><i class="fa-solid fa-map-pin"></i> Departamento <?= e($l['depto']) ?></span>
    </div>
    <h1><?= e($l['nombre']) ?></h1>
    <p class="cat-note" style="--c:<?= $cat['color'] ?>"><i class="fa-solid <?= $cat['icono'] ?>"></i><span>Lugar de <strong><?= e(mb_strtolower($cat['nombre'])) ?></strong>: <?= e(mb_strtolower($cat['descripcion'] ?? '')) ?>.</span></p>
    <p class="lead-hero"><?= e($l['resumen']) ?></p>
    <?php if (count($imagenes) > 1): ?>
      <div class="lh-thumbs">
        <?php foreach ($imagenes as $i => $im): ?>
          <button class="<?= $i ? '' : 'active' ?>" data-i="<?= $i ?>" style="background-image:url('<?= e(img_url($im['url'])) ?>')" aria-label="Foto <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php if ($imagenes): ?>
    <button class="lh-zoom" id="btnAmpliar"><i class="fa-solid fa-expand me-1"></i> Ver fotos</button>
    <span class="lh-cap" id="heroCap"><?= e($imagenes[0]['titulo'] ?: $l['nombre']) ?> · <?= e($imagenes[0]['credito'] ?: '') ?></span>
  <?php else: ?>
    <span class="lh-cap">Foto pendiente · se puede cargar desde el panel</span>
  <?php endif; ?>
</header>

<main class="section section-arena lugar-main">
  <div class="container">
    <div class="row g-4 g-xl-5">
      <div class="col-lg-8">
        <ul class="nav lugar-tabs mb-1" role="tablist">
          <li><button class="active" data-bs-toggle="tab" data-bs-target="#t-que" type="button" data-hash="historia"><i class="fa-solid fa-book-open"></i> ¿Qué es?</button></li>
          <li><button data-bs-toggle="tab" data-bs-target="#t-imp" type="button" data-hash="importancia"><i class="fa-solid fa-leaf"></i> ¿Por qué es importante?</button></li>
          <li><button data-bs-toggle="tab" data-bs-target="#t-hab" type="button" data-hash="habitantes"><i class="fa-solid fa-people-group"></i> ¿Quiénes lo habitan?</button></li>
          <?php if ($nPreg): ?><li><button data-bs-toggle="tab" data-bs-target="#t-juego" type="button" data-hash="juego" id="tabJuego"><i class="fa-solid fa-gamepad"></i> Desafío</button></li><?php endif; ?>
        </ul>
        <div class="tab-content lugar-panel">
          <section class="tab-pane fade show active" id="t-que">
            <p class="lt-lead"><i class="fa-solid fa-book-open"></i> Su historia</p>
            <div class="lugar-text"><?= parrafos($l['descripcion']) ?></div>
            <?php if ($l['dato_curioso']): ?>
              <div class="dato-box"><i class="fa-solid fa-lightbulb"></i><div><strong>¿Sabías que…?</strong><p><?= e($l['dato_curioso']) ?></p></div></div>
            <?php endif; ?>
            <button class="btn btn-ghost-dark btn-sm mt-4" data-next="#t-imp">Siguiente: ¿Por qué es importante? <i class="fa-solid fa-arrow-right ms-1"></i></button>
          </section>
          <section class="tab-pane fade" id="t-imp">
            <p class="lt-lead"><i class="fa-solid fa-leaf"></i> Flora, fauna y valor del lugar</p>
            <div class="lugar-text"><?= parrafos($l['importancia']) ?></div>
            <button class="btn btn-ghost-dark btn-sm mt-4" data-next="#t-hab">Siguiente: ¿Quiénes lo habitan? <i class="fa-solid fa-arrow-right ms-1"></i></button>
          </section>
          <section class="tab-pane fade" id="t-hab">
            <p class="lt-lead"><i class="fa-solid fa-people-group"></i> Culturas, antepasados y pueblos originarios</p>
            <div class="lugar-text"><?= parrafos($l['habitantes']) ?></div>
            <?php if ($nPreg): ?><button class="btn btn-lapacho btn-sm mt-4" data-next="#t-juego"><i class="fa-solid fa-gamepad me-1"></i> ¿Te animás al desafío?</button><?php endif; ?>
          </section>
          <?php if ($nPreg): ?>
            <section class="tab-pane fade" id="t-juego"><div id="quizLugar" data-lugar="<?= (int)$l['id'] ?>"></div></section>
          <?php endif; ?>
        </div>

        <?php if ($fuentes): ?>
          <section class="lugar-card mt-4">
            <h2><i class="fa-solid fa-book-bookmark"></i> Fuentes consultadas</h2>
            <ol class="fuentes-list">
              <?php foreach ($fuentes as $f): ?>
                <li><?= e($f['cita']) ?><?php if ($f['url']): ?> <a href="<?= e($f['url']) ?>" target="_blank" rel="noopener">Ver fuente <i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif; ?></li>
              <?php endforeach; ?>
            </ol>
          </section>
        <?php endif; ?>
      </div>

      <aside class="col-lg-4">
        <div class="lugar-card mini-map-card">
          <h2><i class="fa-solid fa-location-dot"></i> Dónde queda</h2>
          <div class="mini-map" data-depto="<?= e($l['depto_slug']) ?>">
            <?= file_get_contents(__DIR__ . '/assets/img/mapa-formosa.svg') ?>
            <?php if ($l['map_x'] !== null): ?>
              <span class="mini-pin" style="left:<?= (float)$l['map_x'] ?>%;top:<?= (float)$l['map_y'] ?>%;--c:<?= $cat['color'] ?>"><i class="fa-solid <?= $cat['icono'] ?>"></i></span>
            <?php endif; ?>
          </div>
          <p class="small text-muted mb-3">Departamento <strong><?= e($l['depto']) ?></strong> · Cabecera: <?= e($l['cabecera']) ?></p>
          <?php if ($l['como_llegar']): ?><p class="mb-3"><i class="fa-solid fa-route text-success me-1"></i> <?= e($l['como_llegar']) ?></p><?php endif; ?>
          <div class="d-grid gap-2">
            <a class="btn btn-humedal" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)$l['lat'] ?>,<?= (float)$l['lng'] ?>"><i class="fa-solid fa-diamond-turn-right me-1"></i> Cómo llegar (Google Maps)</a>
            <a class="btn btn-ghost-dark" href="index.php?ver=<?= (int)$l['id'] ?>#mapa" data-padre="ver" data-id="<?= (int)$l['id'] ?>"><i class="fa-solid fa-map me-1"></i> Ver en el mapa interactivo</a>
          </div>
        </div>

        <div class="lugar-card">
          <div class="d-grid gap-2">
            <button class="btn btn-ghost-dark" id="btnCompartir" data-url="lugar.php?l=<?= e($l['slug']) ?>" data-titulo="<?= e($l['nombre']) ?>"><i class="fa-solid fa-share-nodes me-1"></i> Compartir</button>
            <a class="btn btn-ghost-dark" href="index.php?resenar=<?= (int)$l['id'] ?>#resenas" data-padre="resenar" data-id="<?= (int)$l['id'] ?>"><i class="fa-solid fa-star me-1"></i> Dejar una reseña</a>
          </div>
        </div>

        <?php if ($otros): ?>
          <div class="lugar-card">
            <h2><i class="fa-solid fa-compass"></i> Más en <?= e($l['depto']) ?></h2>
            <div class="otros-list">
              <?php foreach ($otros as $o): $oc = categorias()[$o['categoria']] ?? ['nombre' => $o['categoria'], 'color' => '#888', 'icono' => 'fa-location-dot']; ?>
                <a href="lugar.php?l=<?= e($o['slug']) ?><?= $embed ? '&amp;embed=1' : '' ?>" class="otro" data-slug="<?= e($o['slug']) ?>">
                  <span class="thumb <?= $o['foto'] ? '' : 'ph' ?>" style="<?= $o['foto'] ? "background-image:url('" . e(img_url($o['foto'])) . "')" : '--c:' . e($l['depto_color']) ?>"><?= $o['foto'] ? '' : '<i class="fa-solid ' . $oc['icono'] . '"></i>' ?></span>
                  <span><strong><?= e($o['nombre']) ?></strong><small><?= $oc['nombre'] ?></small></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</main>
<?php endif; ?>

<footer class="footer">
  <div class="footer-bottom">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span>© <?= date('Y') ?> <?= e(cfg('sitio_nombre', APP_NAME)) ?> · Fotografías: Wikimedia Commons.</span>
      <span class="dev-credit">Desarrollado con <i class="fa-solid fa-heart"></i> por <strong>Dalila Spindola Dev</strong></span>
    </div>
  </div>
</footer>

<div class="lightbox" id="lightbox" hidden>
  <button class="lb-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
  <button class="lb-prev" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
  <figure><img src="" alt=""><figcaption></figcaption></figure>
  <button class="lb-next" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
</div>
<div class="toast-zone" id="toastZone"></div>

<?php if ($l): ?>
<script>
  window.LUGAR_FOTOS = <?= json_encode(array_map(fn($i) => ['url' => img_url($i['url']), 'titulo' => $i['titulo'] ?: $l['nombre'], 'sub' => $i['credito'] ?: ''], $imagenes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script src="<?= asset('assets/js/utils.js') ?>"></script>
<script src="<?= asset('assets/js/lugar.js') ?>"></script>
</body>
</html>
