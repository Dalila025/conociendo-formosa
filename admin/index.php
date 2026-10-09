<?php
/** Login del panel administrativo */
require_once __DIR__ . '/includes/bootstrap.php';

if (admin_logueado()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Freno simple contra fuerza bruta: 5 intentos fallidos → espera 5 minutos
    $_SESSION['login_intentos'] = $_SESSION['login_intentos'] ?? 0;
    if ($_SESSION['login_intentos'] >= 5 && time() - ($_SESSION['login_bloqueo'] ?? 0) < 300) {
        $error = 'Demasiados intentos. Esperá unos minutos.';
    } elseif (!csrf_check()) {
        $error = 'El formulario expiró, intentá de nuevo.';
    } else {
        $st = db()->prepare('SELECT * FROM usuarios WHERE usuario = ?');
        $st->execute([$usuario]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $u['id'];
            $_SESSION['admin_nombre'] = $u['nombre'];
            $_SESSION['admin_ultimo'] = time();
            $_SESSION['login_intentos'] = 0;
            db()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')->execute([$u['id']]);
            if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            }
            redirect('dashboard.php');
        }
        $_SESSION['login_intentos']++;
        $_SESSION['login_bloqueo'] = time();
        $error = 'Usuario o contraseña incorrectos.';
    }
}
$fondo = db()->query('SELECT imagen FROM slides WHERE activo = 1 ORDER BY orden LIMIT 1')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar · Panel Conociendo Formosa</title>
  <link rel="icon" href="../assets/img/favicon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= asset('assets/admin.css', __DIR__) ?>" rel="stylesheet">
</head>
<body class="login-page" style="--bg:url('<?= e(img_url($fondo ?: '', P)) ?>')">
  <div class="login-card">
    <div class="text-center mb-4">
      <span class="brand-mark big"><i class="fa-solid fa-sun"></i></span>
      <h1 class="login-title mt-3">Panel de administración</h1>
      <p class="text-muted mb-0">Conociendo Formosa</p>
    </div>
    <?php foreach (flashes() as $f): ?><div class="alert alert-<?= e($f['tipo']) ?> py-2 small"><?= e($f['msg']) ?></div><?php endforeach; ?>
    <?php if ($error): ?><div class="alert alert-danger py-2 small shake"><i class="fa-solid fa-circle-exclamation me-1"></i><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <div class="form-floating mb-3">
        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="usuario" value="<?= e($_POST['usuario'] ?? '') ?>" required autofocus>
        <label for="usuario"><i class="fa-solid fa-user me-1"></i> Usuario</label>
      </div>
      <div class="form-floating mb-3 position-relative">
        <input type="password" class="form-control" id="password" name="password" placeholder="contraseña" required>
        <label for="password"><i class="fa-solid fa-lock me-1"></i> Contraseña</label>
        <button type="button" class="btn-eye" data-toggle-pass="#password" aria-label="Mostrar contraseña"><i class="fa-solid fa-eye"></i></button>
      </div>
      <button class="btn btn-lapacho w-100 py-3">Ingresar <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i></button>
    </form>
    <p class="text-center mt-4 mb-0 small"><a href="../"><i class="fa-solid fa-arrow-left me-1"></i>Volver al sitio</a></p>
    <p class="text-center mt-3 mb-0 small text-muted">Desarrollado por <strong>Dalila Spindola Dev</strong></p>
  </div>
  <script>
    document.querySelectorAll('[data-toggle-pass]').forEach(b => b.addEventListener('click', () => {
      const i = document.querySelector(b.dataset.togglePass); i.type = i.type === 'password' ? 'text' : 'password';
      b.innerHTML = `<i class="fa-solid ${i.type === 'password' ? 'fa-eye' : 'fa-eye-slash'}"></i>`;
    }));
  </script>
</body>
</html>
