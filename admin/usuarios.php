<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();
$yo = (int)$_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $usuario = strtolower(trim($_POST['usuario'] ?? ''));
        $pass = $_POST['password'] ?? '';
        if (!$nombre || !preg_match('/^[a-z0-9._-]{3,50}$/', $usuario)) {
            flash('danger', 'Nombre requerido. El usuario debe tener 3+ caracteres (letras, números, . _ -).');
        } elseif (strlen($pass) < 8) {
            flash('danger', 'La contraseña debe tener al menos 8 caracteres.');
        } else {
            try {
                $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password) VALUES (?,?,?)')->execute([$nombre, $usuario, password_hash($pass, PASSWORD_DEFAULT)]);
                flash('success', "Usuario «$usuario» creado.");
            } catch (PDOException $ex) {
                flash('danger', 'Ese nombre de usuario ya existe.');
            }
        }
    } elseif ($accion === 'clave') {
        $st = $pdo->prepare('SELECT password FROM usuarios WHERE id = ?');
        $st->execute([$yo]);
        if (!password_verify($_POST['actual'] ?? '', $st->fetchColumn())) {
            flash('danger', 'La contraseña actual no es correcta.');
        } elseif (strlen($_POST['nueva'] ?? '') < 8) {
            flash('danger', 'La nueva contraseña debe tener al menos 8 caracteres.');
        } elseif (($_POST['nueva'] ?? '') !== ($_POST['repetir'] ?? '')) {
            flash('danger', 'Las contraseñas nuevas no coinciden.');
        } else {
            $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([password_hash($_POST['nueva'], PASSWORD_DEFAULT), $yo]);
            flash('success', 'Tu contraseña fue actualizada.');
        }
    } elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        if ($id === $yo) flash('danger', 'No podés eliminar tu propio usuario.');
        else { $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]); flash('success', 'Usuario eliminado.'); }
    }
    redirect('usuarios.php');
}

$usuarios = $pdo->query('SELECT id, nombre, usuario, ultimo_acceso, creado FROM usuarios ORDER BY nombre')->fetchAll();
$titulo = 'Usuarios';
$seccion = 'usuarios';
$subtitulo = 'Personas que pueden administrar el sitio';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="panel">
      <div class="panel-head"><h2><i class="fa-solid fa-users"></i> Administradores</h2></div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th>Nombre</th><th>Usuario</th><th>Último acceso</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
              <tr>
                <td><span class="avatar-sm me-2"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span><?= e($u['nombre']) ?> <?= $u['id'] == $yo ? '<span class="badge bg-success-subtle text-success">Vos</span>' : '' ?></td>
                <td><code><?= e($u['usuario']) ?></code></td>
                <td><small><?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : '—' ?></small></td>
                <td class="text-end">
                  <?php if ($u['id'] != $yo): ?>
                    <form method="post" data-confirm="¿Eliminar a <?= e($u['nombre']) ?>?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <form class="panel mb-4" method="post">
      <?= csrf_field() ?><input type="hidden" name="accion" value="clave">
      <div class="panel-head"><h2><i class="fa-solid fa-key"></i> Cambiar mi contraseña</h2></div>
      <input type="password" name="actual" class="form-control mb-2" placeholder="Contraseña actual" required>
      <input type="password" name="nueva" class="form-control mb-2" placeholder="Nueva contraseña (mín. 8)" minlength="8" required>
      <input type="password" name="repetir" class="form-control mb-3" placeholder="Repetir nueva contraseña" required>
      <button class="btn btn-dark w-100">Actualizar contraseña</button>
    </form>
    <form class="panel" method="post">
      <?= csrf_field() ?><input type="hidden" name="accion" value="crear">
      <div class="panel-head"><h2><i class="fa-solid fa-user-plus"></i> Nuevo administrador</h2></div>
      <input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre completo" required>
      <input type="text" name="usuario" class="form-control mb-2" placeholder="Usuario" required>
      <input type="password" name="password" class="form-control mb-3" placeholder="Contraseña (mín. 8)" minlength="8" required>
      <button class="btn btn-lapacho w-100"><i class="fa-solid fa-plus me-1"></i> Crear usuario</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
