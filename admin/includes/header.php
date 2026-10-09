<?php
/** Cabecera + barra lateral del panel. Requiere $titulo y $seccion definidos. */
$pendientes = (int)db()->query("SELECT COUNT(*) FROM resenas WHERE estado='pendiente'")->fetchColumn();
$menu = [
    'dashboard'     => ['Escritorio', 'fa-gauge-high', 'dashboard.php'],
    'lugares'       => ['Lugares', 'fa-location-dot', 'lugares.php'],
    'imagenes'      => ['Fotos', 'fa-images', 'imagenes.php'],
    'preguntas'     => ['Preguntas del juego', 'fa-circle-question', 'preguntas.php'],
    'departamentos' => ['Departamentos', 'fa-map', 'departamentos.php'],
    'pueblos'       => ['Pueblos originarios', 'fa-people-group', 'pueblos.php'],
    'slides'        => ['Portada (slider)', 'fa-panorama', 'slides.php'],
    'contenido'     => ['Textos del sitio', 'fa-pen-to-square', 'contenido.php'],
    'bibliografia'  => ['Fuentes y bibliografía', 'fa-book-bookmark', 'bibliografia.php'],
    'resenas'       => ['Reseñas', 'fa-star', 'resenas.php'],
    'puntajes'      => ['Ranking juegos', 'fa-trophy', 'puntajes.php'],
    'usuarios'      => ['Usuarios', 'fa-user-shield', 'usuarios.php'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?> · Panel Conociendo Formosa</title>
  <link rel="icon" href="../assets/img/favicon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <?php if (!empty($usarMapa)): ?><link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet"><?php endif; ?>
  <link href="<?= asset('assets/admin.css', __DIR__ . '/..') ?>" rel="stylesheet">
</head>
<body class="admin">
<aside class="sidebar" id="sidebar">
  <a href="dashboard.php" class="sb-brand">
    <span class="brand-mark"><i class="fa-solid fa-sun"></i></span>
    <span>Conociendo<br><strong>Formosa</strong></span>
  </a>
  <nav class="sb-nav">
    <?php foreach ($menu as $k => [$txt, $ic, $url]): ?>
      <a href="<?= $url ?>" class="<?= $seccion === $k ? 'active' : '' ?>">
        <i class="fa-solid <?= $ic ?>"></i><span><?= $txt ?></span>
        <?php if ($k === 'resenas' && $pendientes): ?><em class="sb-badge"><?= $pendientes ?></em><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sb-foot">
    <a href="../" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>Ver sitio</span></a>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Salir</span></a>
  </div>
</aside>
<div class="sb-backdrop" id="sbBackdrop"></div>

<main class="admin-main">
  <header class="topbar">
    <button class="btn btn-light btn-sm d-lg-none" id="sbToggle" aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
    <div>
      <h1 class="topbar-title"><?= e($titulo) ?></h1>
      <?php if (!empty($subtitulo)): ?><p class="topbar-sub"><?= $subtitulo ?></p><?php endif; ?>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <?= $acciones ?? '' ?>
      <div class="user-pill"><span class="avatar-sm"><?= e(mb_strtoupper(mb_substr($_SESSION['admin_nombre'] ?? 'A', 0, 1))) ?></span><span class="d-none d-md-inline"><?= e($_SESSION['admin_nombre'] ?? '') ?></span></div>
    </div>
  </header>

  <div class="admin-content">
    <?php foreach (flashes() as $f): ?>
      <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show flash" role="alert">
        <i class="fa-solid <?= $f['tipo'] === 'success' ? 'fa-circle-check' : ($f['tipo'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-info') ?> me-2"></i><?= e($f['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
