<?php
/**
 * Conociendo Formosa · Sitio público
 * Desarrollado por Dalila Spindola Dev
 */
require_once __DIR__ . '/includes/funciones.php';

$pdo     = db();
$slides  = $pdo->query('SELECT * FROM slides WHERE activo = 1 ORDER BY orden, id')->fetchAll();
$pueblos = $pdo->query('SELECT * FROM pueblos ORDER BY orden, id')->fetchAll();
$stats   = $pdo->query("SELECT (SELECT COUNT(*) FROM departamentos) deptos,
                               (SELECT COUNT(*) FROM lugares WHERE activo=1) lugares,
                               (SELECT COUNT(*) FROM pueblos) pueblos,
                               (SELECT COUNT(*) FROM preguntas) preguntas")->fetch();
$cats    = categorias();
$porCategoria = $pdo->query('SELECT categoria, COUNT(*) FROM lugares WHERE activo = 1 GROUP BY categoria')->fetchAll(PDO::FETCH_KEY_PAIR);
$biblio  = $pdo->query('SELECT * FROM bibliografia ORDER BY orden, id')->fetchAll();
$porCat  = [];
foreach ($biblio as $b) $porCat[$b['categoria']][] = $b;
$token   = csrf_token();
$anio    = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(cfg('sitio_nombre', APP_NAME)) ?> · Cultura, naturaleza y paseos</title>
  <meta name="description" content="<?= e(cfg('hero_texto')) ?>">
  <meta name="theme-color" content="#0E3B2E">
  <meta name="csrf-token" content="<?= $token ?>">
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,800;1,9..144,500&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<!-- ============ PRELOADER ============ -->
<div id="preloader" aria-hidden="true">
  <div class="loader-flower">
    <?php for ($i = 0; $i < 5; $i++): ?><span style="--i:<?= $i ?>"></span><?php endfor; ?>
  </div>
  <p class="loader-text">Conociendo <em>Formosa</em></p>
</div>

<canvas id="petalos" aria-hidden="true"></canvas>
<div class="scroll-progress" id="scrollProgress"></div>

<!-- ============ NAVBAR ============ -->
<nav class="navbar navbar-expand-lg fixed-top nav-glass" id="mainNav">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#inicio">
      <span class="brand-mark"><i class="fa-solid fa-sun"></i></span>
      <span class="brand-text">Conociendo <strong>Formosa</strong></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Abrir menú">
      <span class="toggler-bar"></span><span class="toggler-bar"></span><span class="toggler-bar"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="#sobre">Formosa</a></li>
        <li class="nav-item"><a class="nav-link" href="#proyecto">Proyecto</a></li>
        <li class="nav-item"><a class="nav-link" href="#mapa">Mapa</a></li>
        <li class="nav-item"><a class="nav-link" href="#departamentos">Departamentos</a></li>
        <li class="nav-item"><a class="nav-link" href="#pueblos">Pueblos</a></li>
        <li class="nav-item"><a class="nav-link" href="#juegos">Juegos</a></li>
        <li class="nav-item"><a class="nav-link" href="#galeria">Galería</a></li>
        <li class="nav-item"><a class="nav-link" href="#resenas">Reseñas</a></li>
        <li class="nav-item ms-lg-2">
          <button class="btn btn-icon" id="btnBuscarGlobal" title="Buscar un lugar" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ============ HERO ============ -->
<header id="inicio" class="hero">
  <div class="hero-slides">
    <?php foreach ($slides as $i => $s): ?>
      <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
        <div class="hero-bg" style="background-image:url('<?= e(img_url($s['imagen'])) ?>')"></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$slides): ?>
      <div class="hero-slide active"><div class="hero-bg hero-bg--fallback"></div></div>
    <?php endif; ?>
  </div>
  <div class="hero-overlay"></div>
  <div class="hero-grain"></div>

  <div class="container hero-content">
    <span class="hero-kicker reveal"><i class="fa-solid fa-location-dot"></i> Formosa · Argentina</span>
    <?php foreach ($slides ?: [['titulo' => cfg('hero_titulo'), 'subtitulo' => cfg('hero_texto'), 'boton_texto' => 'Explorar', 'boton_link' => '#mapa']] as $i => $s): ?>
      <div class="hero-caption <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
        <h1 class="hero-title"><?= e($s['titulo']) ?></h1>
        <p class="hero-sub"><?= e($s['subtitulo']) ?></p>
        <?php if (!empty($s['boton_texto'])): ?>
          <a href="<?= e($s['boton_link'] ?: '#mapa') ?>" class="btn btn-lapacho btn-lg magnetic">
            <?= e($s['boton_texto']) ?> <i class="fa-solid fa-arrow-right ms-1"></i>
          </a>
        <?php endif; ?>
        <a href="#juegos" class="btn btn-ghost btn-lg ms-sm-2 mt-2 mt-sm-0"><i class="fa-solid fa-gamepad me-1"></i> Jugar</a>
      </div>
    <?php endforeach; ?>

    <div class="hero-stats reveal">
      <div><strong class="counter" data-to="<?= (int)$stats['deptos'] ?>">0</strong><span>Departamentos</span></div>
      <div><strong class="counter" data-to="<?= (int)$stats['lugares'] ?>">0</strong><span>Lugares</span></div>
      <div><strong class="counter" data-to="<?= (int)$stats['pueblos'] ?>">0</strong><span>Pueblos originarios</span></div>
      <div><strong class="counter" data-to="<?= (int)$stats['preguntas'] ?>">0</strong><span>Desafíos</span></div>
    </div>
  </div>

  <?php if (count($slides) > 1): ?>
  <div class="hero-dots" role="tablist">
    <?php foreach ($slides as $i => $s): ?>
      <button class="<?= $i === 0 ? 'active' : '' ?>" data-go="<?= $i ?>" aria-label="Ir a la imagen <?= $i + 1 ?>"><span></span></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <a href="#sobre" class="scroll-hint" aria-label="Bajar"><span></span></a>
</header>

<!-- ============ SOBRE FORMOSA ============ -->
<section id="sobre" class="section section-arena">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5 reveal">
        <span class="eyebrow">Conociendo Formosa</span>
        <h2 class="section-title"><?= e(cfg('sobre_titulo')) ?></h2>
        <p class="lead-text"><?= e(cfg('sobre_intro')) ?></p>
        <div class="about-photo tilt">
          <img src="<?= e(img_url(cfg('sobre_imagen'))) ?>" alt="Historia de Formosa" loading="lazy" onerror="this.closest('.about-photo').classList.add('no-img')">
          <span class="about-badge"><i class="fa-solid fa-calendar-days"></i> Fundada en 1879</span>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="about-cards">
          <?php
          $bloques = [
              ['Historia', 'fa-landmark-flag', cfg('sobre_historia'), 'sol'],
              ['Cultura', 'fa-guitar', cfg('sobre_cultura'), 'lapacho'],
              ['Naturaleza', 'fa-leaf', cfg('sobre_naturaleza'), 'humedal'],
          ];
          foreach ($bloques as $k => [$t, $ic, $txt, $tono]): ?>
            <article class="about-card reveal tone-<?= $tono ?>" style="--d:<?= $k * 120 ?>ms">
              <div class="about-icon"><i class="fa-solid <?= $ic ?>"></i></div>
              <div>
                <h3><?= $t ?></h3>
                <p><?= e($txt) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ SOBRE ESTE PROYECTO ============ -->
<section id="proyecto" class="section section-white">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5 reveal">
        <span class="eyebrow"><?= e(cfg('proyecto_titulo', 'Sobre este proyecto')) ?></span>
        <h2 class="section-title">Un mapa para mirar Formosa de otra manera</h2>
        <div class="eje-card">
          <i class="fa-solid fa-compass"></i>
          <div><small>Eje temático elegido</small><strong><?= e(cfg('proyecto_eje')) ?></strong></div>
        </div>
        <div class="guide-steps">
          <div><span>1</span><strong>¿Qué es?</strong><small>Su historia</small></div>
          <div><span>2</span><strong>¿Por qué es importante?</strong><small>Flora, fauna y valor</small></div>
          <div><span>3</span><strong>¿Quiénes lo habitan?</strong><small>Culturas y pueblos originarios</small></div>
        </div>
      </div>
      <div class="col-lg-7 reveal" style="--d:120ms">
        <article class="author-letter">
          <?php foreach (preg_split('/\R\R+/', cfg('proyecto_texto')) as $par): ?>
            <p><?= e($par) ?></p>
          <?php endforeach; ?>
          <footer>
            <span class="author-avatar"><?= e(mb_substr(cfg('proyecto_autora', 'D'), 0, 1)) ?></span>
            <div><strong><?= e(cfg('proyecto_autora')) ?></strong><small><?= e(cfg('proyecto_institucion')) ?></small></div>
          </footer>
        </article>

        <?php /* ===== Equipo: se edita en Panel → Textos del sitio → «Sobre este proyecto» ===== */
        $integrantes = array_filter(array_map('trim', preg_split('/\R/', cfg('proyecto_integrantes'))));
        if (cfg('proyecto_grupo') || $integrantes): ?>
          <div class="team-card">
            <div class="team-head">
              <span class="team-icon"><i class="fa-solid fa-users"></i></span>
              <div><small>Trabajo grupal</small><strong><?= e(cfg('proyecto_grupo')) ?></strong></div>
            </div>
            <div class="team-author">
              <span class="author-avatar sm"><?= e(mb_substr(cfg('proyecto_autora', 'D'), 0, 1)) ?></span>
              <div>
                <strong><?= e(cfg('proyecto_autora')) ?></strong>
                <small><i class="fa-solid fa-code me-1"></i><?= e(cfg('proyecto_rol_autora')) ?></small>
              </div>
            </div>
            <?php if ($integrantes): ?>
              <p class="team-label">Integrantes del grupo</p>
              <?php if (cfg('proyecto_rol_integrantes')): ?><p class="team-role"><i class="fa-solid fa-magnifying-glass me-1"></i><?= e(cfg('proyecto_rol_integrantes')) ?></p><?php endif; ?>
              <ul class="team-list">
                <?php foreach ($integrantes as $nombre): ?><li><i class="fa-solid fa-user"></i><?= e($nombre) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ============ MAPA INTERACTIVO ============ -->
<section id="mapa" class="section section-monte">
  <div class="container-fluid px-lg-5">
    <div class="text-center mb-4 reveal">
      <span class="eyebrow eyebrow-light">Mapa interactivo</span>
      <h2 class="section-title text-white">Recorré Formosa punto por punto</h2>
      <p class="text-white-75 mx-auto" style="max-width:640px">Cada color es una forma de conocer Formosa: <b style="color:#F2A541">cultura</b>, <b style="color:#8fd19e">naturaleza</b> o <b style="color:#9ec1ff">paseo</b>. Tocá un punto para descubrir su historia y su desafío.</p>
    </div>

    <div class="interest-picker reveal" role="group" aria-label="¿Qué querés conocer?">
      <p class="interest-title">¿Qué querés conocer? <small>Elegí una opción y el mapa te marca los lugares</small></p>
      <div class="interest-grid">
        <?php foreach ($cats as $k => $c): ?>
          <button class="interest-card" data-cat="<?= $k ?>" style="--c:<?= $c['color'] ?>" aria-pressed="false">
            <span class="ic-icon"><i class="fa-solid <?= $c['icono'] ?>"></i></span>
            <span class="ic-text"><strong><?= e($c['nombre']) ?></strong><small><?= e($c['descripcion'] ?? '') ?></small></span>
            <span class="ic-count"><?= (int)($porCategoria[$k] ?? 0) ?> <small>lugares</small></span>
          </button>
        <?php endforeach; ?>
        <button class="interest-card all active" data-cat="todo" aria-pressed="true">
          <span class="ic-icon"><i class="fa-solid fa-map-location-dot"></i></span>
          <span class="ic-text"><strong>Todo</strong><small>Ver todos los lugares de la provincia</small></span>
          <span class="ic-count"><?= array_sum($porCategoria) ?> <small>lugares</small></span>
        </button>
      </div>
    </div>

    <div class="map-shell reveal">
      <aside class="map-panel" id="mapPanel">
        <div class="map-search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="search" id="mapSearch" placeholder="Buscar lugar, pueblo, departamento…" autocomplete="off">
        </div>

        <label class="map-label">Departamentos</label>
        <div class="depto-chips" id="deptoChips">
          <button class="chip chip-depto active" data-depto="0">Todos</button>
        </div>

        <div class="map-list-head">
          <span id="mapCount">0 lugares</span>
          <button class="link-btn" id="btnResetMapa"><i class="fa-solid fa-rotate-left"></i> Reiniciar</button>
        </div>
        <ul class="map-list" id="mapList"></ul>
      </aside>

      <div class="map-wrap">
        <div class="map-stage" id="mapStage" data-cat="todo">
          <div class="map-canvas" id="mapCanvas">
            <?= file_get_contents(__DIR__ . '/assets/img/mapa-formosa.svg') ?>
            <div class="map-markers" id="mapMarkers"></div>
          </div>
          <div class="map-tip" id="mapTip" hidden></div>
        </div>
        <!-- Mapa real (satélite / relieve): se carga recién cuando se elige -->
        <div class="map-real" id="mapReal" hidden></div>
        <div class="map-mode" role="group" aria-label="Tipo de mapa">
          <button class="active" data-modo="ilustrado" aria-pressed="true"><i class="fa-solid fa-map"></i><span>Mi mapa</span></button>
          <button data-modo="real" aria-pressed="false"><i class="fa-solid fa-earth-americas"></i><span>Mapa real</span></button>
        </div>
        <div class="map-controls">
          <button id="mapZoomIn" aria-label="Acercar"><i class="fa-solid fa-plus"></i></button>
          <button id="mapZoomOut" aria-label="Alejar"><i class="fa-solid fa-minus"></i></button>
          <button id="mapZoomReset" aria-label="Ver toda la provincia" title="Ver toda la provincia"><i class="fa-solid fa-expand"></i></button>
        </div>
        <div class="map-compass" aria-hidden="true">
          <svg viewBox="0 0 64 64"><path d="M32 4 L37 32 L32 60 L27 32 Z" fill="#0E3B2E"/><path d="M32 4 L37 32 L32 32 Z" fill="#E05A8A"/><path d="M4 32 L32 27 L60 32 L32 37 Z" fill="#5f6f68"/><circle cx="32" cy="32" r="3" fill="#fff"/></svg>
          <span class="n">N</span><span class="s">S</span><span class="e">E</span><span class="o">O</span>
        </div>
        <p class="map-hint"><i class="fa-solid fa-hand-pointer"></i> Tocá un departamento para acercarte</p>
        <div class="map-legend">
          <?php foreach ($cats as $k => $c): ?>
            <span title="<?= e($c['descripcion'] ?? '') ?>"><i style="background:<?= $c['color'] ?>"></i><?= $c['nombre'] ?></span>
          <?php endforeach; ?>
        </div>
        <button class="map-toggle-panel d-lg-none" id="btnTogglePanel"><i class="fa-solid fa-list"></i> Lista</button>

        <!-- Tarjeta de vista previa -->
        <div class="map-preview" id="mapPreview" hidden>
          <button class="map-preview-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
          <div class="map-preview-img"></div>
          <div class="map-preview-body">
            <span class="badge-cat"></span>
            <h4></h4>
            <p></p>
            <div class="d-flex gap-2">
              <button class="btn btn-lapacho btn-sm flex-grow-1" data-action="ver"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver ficha completa</button>
              <button class="btn btn-sol btn-sm" data-action="jugar" title="Jugar desafío"><i class="fa-solid fa-gamepad"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="wave wave-bottom"><svg viewBox="0 0 1440 80" preserveAspectRatio="none"><path d="M0,40 C240,90 480,0 720,30 C960,60 1200,10 1440,40 L1440,80 L0,80 Z"/></svg></div>
</section>

<!-- ============ DEPARTAMENTOS ============ -->
<section id="departamentos" class="section section-arena">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5 reveal">
      <div>
        <span class="eyebrow">9 departamentos</span>
        <h2 class="section-title mb-0">Cada rincón, una historia</h2>
      </div>
      <p class="text-muted mb-0" style="max-width:420px">Tocá un departamento para verlo en el mapa con sus lugares de cultura, naturaleza y paseo.</p>
    </div>
    <div class="depto-grid" id="deptoGrid"></div>
  </div>
</section>

<!-- ============ DESTACADOS ============ -->
<section id="destacados" class="section section-white overflow-hidden">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4 reveal">
      <div>
        <span class="eyebrow">Imperdibles</span>
        <h2 class="section-title mb-0">Lugares destacados</h2>
      </div>
      <div class="carousel-arrows">
        <button class="btn-round" data-scroll="-1" aria-label="Anterior"><i class="fa-solid fa-arrow-left"></i></button>
        <button class="btn-round" data-scroll="1" aria-label="Siguiente"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>
  </div>
  <div class="featured-track" id="featuredTrack"></div>
</section>

<!-- ============ PUEBLOS ORIGINARIOS ============ -->
<section id="pueblos" class="section section-tierra">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="eyebrow eyebrow-light">Raíces vivas</span>
      <h2 class="section-title text-white">Pueblos originarios de Formosa</h2>
      <p class="text-white-75 mx-auto" style="max-width:640px">Formosa es una de las provincias con mayor diversidad indígena del país. Conocé a los pueblos que habitan este territorio desde mucho antes de que existieran los mapas. Fijate en sus nombres: varios de los que se usan comúnmente fueron puestos desde afuera; acá los llamamos como ellos se nombran.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($pueblos as $k => $p): ?>
        <div class="col-md-6 col-lg-3 reveal" style="--d:<?= $k * 100 ?>ms">
          <article class="pueblo-card" style="--c:<?= e($p['color']) ?>">
            <?php if ($p['imagen']): ?>
              <div class="pueblo-img" style="background-image:url('<?= e(img_url($p['imagen'])) ?>')"></div>
            <?php endif; ?>
            <div class="pueblo-icon"><i class="fa-solid <?= e($p['icono']) ?>"></i></div>
            <h3><?= e($p['nombre']) ?></h3>
            <?php if (!empty($p['nombre_impuesto'])): ?>
              <span class="nombre-impuesto" title="Nombre con el que se los nombró desde afuera"><i class="fa-solid fa-tag"></i> Nombre impuesto: <?= e($p['nombre_impuesto']) ?></span>
            <?php endif; ?>
            <p><?= e($p['descripcion']) ?></p>
            <button class="link-btn link-light" data-buscar="<?= e($p['nombre']) ?>">Ver en el mapa <i class="fa-solid fa-arrow-right"></i></button>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="pattern-guarda" aria-hidden="true"></div>
</section>

<!-- ============ JUEGOS ============ -->
<section id="juegos" class="section section-arena">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="eyebrow">Aprender jugando</span>
      <h2 class="section-title">¿Cuánto sabés de Formosa?</h2>
      <p class="text-muted mx-auto" style="max-width:600px">Tres juegos para poner a prueba lo que descubriste. Sumá puntos y entrá al ranking.</p>
    </div>

    <ul class="nav game-tabs justify-content-center mb-4 reveal" role="tablist">
      <li><button class="active" data-bs-toggle="tab" data-bs-target="#tab-trivia" type="button"><i class="fa-solid fa-circle-question"></i> Trivia Formosa</button></li>
      <li><button data-bs-toggle="tab" data-bs-target="#tab-memoria" type="button"><i class="fa-solid fa-clone"></i> Memotest</button></li>
      <li><button data-bs-toggle="tab" data-bs-target="#tab-ubicacion" type="button"><i class="fa-solid fa-map-location-dot"></i> ¿Dónde queda?</button></li>
    </ul>

    <div class="tab-content reveal">
      <!-- Trivia -->
      <div class="tab-pane fade show active" id="tab-trivia">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="game-card" id="trivia">
              <div class="game-start">
                <div class="game-emoji">🌿</div>
                <h3>Trivia Formosa</h3>
                <p>10 preguntas al azar sobre lugares de toda la provincia. ¡Respondé rápido: el tiempo suma puntos!</p>
                <button class="btn btn-lapacho btn-lg" data-trivia-start><i class="fa-solid fa-play me-1"></i> Comenzar</button>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="ranking-card">
              <h4><i class="fa-solid fa-trophy"></i> Ranking</h4>
              <ol class="ranking" data-ranking="trivia"><li class="empty">Todavía no hay puntajes.</li></ol>
            </div>
          </div>
        </div>
      </div>
      <!-- Memoria -->
      <div class="tab-pane fade" id="tab-memoria">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="game-card" id="memoria">
              <div class="memo-head">
                <div><i class="fa-solid fa-shoe-prints"></i> Movimientos: <strong data-memo="mov">0</strong></div>
                <div><i class="fa-solid fa-stopwatch"></i> <strong data-memo="time">0:00</strong></div>
                <button class="btn btn-sm btn-outline-dark" data-memo-start><i class="fa-solid fa-shuffle me-1"></i> Nuevo juego</button>
              </div>
              <div class="memo-grid" id="memoGrid"></div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="ranking-card">
              <h4><i class="fa-solid fa-trophy"></i> Ranking</h4>
              <ol class="ranking" data-ranking="memoria"><li class="empty">Todavía no hay puntajes.</li></ol>
            </div>
          </div>
        </div>
      </div>
      <!-- Ubicación -->
      <div class="tab-pane fade" id="tab-ubicacion">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="game-card" id="ubicacion">
              <div class="game-start">
                <div class="game-emoji">🧭</div>
                <h3>¿Dónde queda?</h3>
                <p>Te mostramos un lugar y vos elegís en qué departamento está. 8 rondas, ¡a ver si conocés el mapa!</p>
                <button class="btn btn-lapacho btn-lg" data-ubi-start><i class="fa-solid fa-play me-1"></i> Comenzar</button>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="ranking-card">
              <h4><i class="fa-solid fa-trophy"></i> Ranking</h4>
              <ol class="ranking" data-ranking="ubicacion"><li class="empty">Todavía no hay puntajes.</li></ol>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ GALERÍA ============ -->
<section id="galeria" class="section section-white">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="eyebrow">Postales</span>
      <h2 class="section-title">Galería de Formosa</h2>
    </div>
    <div class="gallery" id="gallery"></div>
  </div>
</section>

<!-- ============ RESEÑAS ============ -->
<section id="resenas" class="section section-monte">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="eyebrow eyebrow-light">Tu opinión importa</span>
      <h2 class="section-title text-white">Reseñas de visitantes</h2>
    </div>
    <div class="row g-4">
      <div class="col-lg-5 reveal">
        <div class="review-summary">
          <div class="d-flex align-items-center gap-4">
            <div class="review-avg">
              <strong id="revAvg">0.0</strong>
              <div class="stars-static" id="revStars"></div>
              <small id="revTotal">0 reseñas</small>
            </div>
            <div class="review-bars flex-grow-1" id="revBars"></div>
          </div>
        </div>

        <form class="review-form" id="reviewForm" novalidate>
          <h4><i class="fa-solid fa-pen-nib"></i> Dejá tu reseña</h4>
          <input type="hidden" name="csrf" value="<?= $token ?>">
          <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <div class="star-input" role="radiogroup" aria-label="Calificación">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="calificacion" id="st<?= $i ?>" value="<?= $i ?>">
              <label for="st<?= $i ?>" title="<?= $i ?> estrellas"><i class="fa-solid fa-star"></i></label>
            <?php endfor; ?>
          </div>
          <div class="row g-2">
            <div class="col-sm-6">
              <div class="form-floating">
                <input type="text" class="form-control" id="revNombre" name="nombre" placeholder="Tu nombre" maxlength="80" required>
                <label for="revNombre">Tu nombre *</label>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-floating">
                <input type="text" class="form-control" id="revCiudad" name="ciudad" placeholder="Ciudad" maxlength="80">
                <label for="revCiudad">¿De dónde sos?</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-floating">
                <select class="form-select" id="revLugar" name="lugar_id"><option value="">Sobre el sitio en general</option></select>
                <label for="revLugar">¿Sobre qué lugar?</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-floating">
                <textarea class="form-control" id="revComent" name="comentario" placeholder="Comentario" style="height:120px" maxlength="1000" required></textarea>
                <label for="revComent">Contanos tu experiencia *</label>
              </div>
              <div class="char-count"><span id="revChars">0</span>/1000</div>
            </div>
          </div>
          <button type="submit" class="btn btn-lapacho w-100 mt-3"><i class="fa-solid fa-paper-plane me-1"></i> Publicar reseña</button>
        </form>
      </div>
      <div class="col-lg-7">
        <div class="review-list" id="reviewList"></div>
        <div class="text-center mt-3"><button class="btn btn-ghost" id="btnMasResenas" hidden>Ver más reseñas</button></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FUENTES ============ -->
<section id="fuentes" class="section section-arena">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="eyebrow">De dónde sale la información</span>
      <h2 class="section-title">Fuentes y bibliografía</h2>
      <p class="text-muted mx-auto" style="max-width:640px">Para armar este mapa usé la bibliografía del programa del seminario y fuentes oficiales. Cada lugar muestra además sus propias fuentes al final de su ficha.</p>
    </div>
    <div class="biblio-grid">
      <?php foreach (categorias_bibliografia() as $k => $titulo): if (empty($porCat[$k])) continue; ?>
        <div class="biblio-col reveal">
          <h3><?= e($titulo) ?></h3>
          <ul>
            <?php foreach ($porCat[$k] as $b): ?>
              <li><?= e($b['cita']) ?><?php if ($b['url']): ?> <a href="<?= e($b['url']) ?>" target="_blank" rel="noopener" aria-label="Abrir fuente"><i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="footer">
  <div class="footer-top">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <a class="navbar-brand d-flex align-items-center gap-2 mb-3" href="#inicio">
            <span class="brand-mark"><i class="fa-solid fa-sun"></i></span>
            <span class="brand-text">Conociendo <strong>Formosa</strong></span>
          </a>
          <p class="text-white-75"><?= e(cfg('footer_texto')) ?></p>
          <div class="socials">
            <a href="<?= e(cfg('red_facebook', '#')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="<?= e(cfg('red_instagram', '#')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="<?= e(cfg('red_youtube', '#')) ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          </div>
        </div>
        <div class="col-6 col-lg-2">
          <h5>Explorar</h5>
          <ul class="footer-links">
            <li><a href="#mapa">Mapa interactivo</a></li>
            <li><a href="#departamentos">Departamentos</a></li>
            <li><a href="#destacados">Destacados</a></li>
            <li><a href="#pueblos">Pueblos originarios</a></li>
          </ul>
        </div>
        <div class="col-6 col-lg-2">
          <h5>Participá</h5>
          <ul class="footer-links">
            <li><a href="#juegos">Juegos</a></li>
            <li><a href="#galeria">Galería</a></li>
            <li><a href="#resenas">Reseñas</a></li>
            <li><a href="#fuentes">Fuentes</a></li>
            <li><a href="admin/">Administración</a></li>
          </ul>
        </div>
        <div class="col-lg-4">
          <h5>Contacto</h5>
          <ul class="footer-contact">
            <li><i class="fa-solid fa-location-dot"></i> <?= e(cfg('contacto_direccion')) ?></li>
            <li><i class="fa-solid fa-envelope"></i> <a href="mailto:<?= e(cfg('contacto_email')) ?>"><?= e(cfg('contacto_email')) ?></a></li>
            <li><i class="fa-solid fa-phone"></i> <?= e(cfg('contacto_telefono')) ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span>© <?= $anio ?> <?= e(cfg('sitio_nombre', APP_NAME)) ?>. Trabajo final · Análisis de la Realidad Sociocultural de Formosa · Fotografías: Wikimedia Commons.</span>
      <span class="dev-credit">Desarrollado con <i class="fa-solid fa-heart"></i> por <strong>Dalila Spindola Dev</strong></span>
    </div>
  </div>
</footer>

<button class="to-top" id="toTop" aria-label="Volver arriba"><i class="fa-solid fa-arrow-up"></i></button>

<!-- ============ VENTANA DE LA FICHA DEL LUGAR ============ -->
<div class="ficha-window" id="fichaWindow" hidden role="dialog" aria-modal="true" aria-label="Ficha del lugar">
  <div class="fw-box">
    <div class="fw-bar">
      <span class="fw-dot"></span><strong class="fw-title"></strong>
      <button class="fw-close" aria-label="Cerrar y volver al mapa"><i class="fa-solid fa-xmark"></i><span class="d-none d-sm-inline ms-1">Cerrar</span></button>
    </div>
    <div class="fw-loader"><div class="spinner-border text-success"></div></div>
    <iframe title="Ficha del lugar" src="about:blank"></iframe>
  </div>
</div>

<!-- ============ BUSCADOR GLOBAL ============ -->
<div class="search-overlay" id="searchOverlay" hidden>
  <div class="search-box">
    <div class="search-input">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" id="globalSearch" placeholder="¿Qué querés conocer?" autocomplete="off">
      <kbd>Esc</kbd>
    </div>
    <ul class="search-results" id="globalResults"></ul>
  </div>
</div>

<!-- ============ LIGHTBOX ============ -->
<div class="lightbox" id="lightbox" hidden>
  <button class="lb-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
  <button class="lb-prev" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
  <figure><img src="" alt=""><figcaption></figcaption></figure>
  <button class="lb-next" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
</div>

<div class="toast-zone" id="toastZone"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script src="<?= asset('assets/js/utils.js') ?>"></script>
<script src="<?= asset('assets/js/mapa.js') ?>"></script>
<script src="<?= asset('assets/js/juegos.js') ?>"></script>
<script src="<?= asset('assets/js/resenas.js') ?>"></script>
<script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
