<?php
require_once __DIR__ . '/includes/bootstrap.php';
requiere_login();
verificar_post();
$pdo = db();
$juegos = ['trivia' => 'Trivia Formosa', 'memoria' => 'Memotest', 'ubicacion' => '¿Dónde queda?'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'eliminar') {
        $pdo->prepare('DELETE FROM puntajes WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('success', 'Puntaje eliminado.');
    } elseif (($_POST['accion'] ?? '') === 'reiniciar' && isset($juegos[$_POST['juego'] ?? ''])) {
        $pdo->prepare('DELETE FROM puntajes WHERE juego = ?')->execute([$_POST['juego']]);
        flash('success', 'Ranking de «' . $juegos[$_POST['juego']] . '» reiniciado.');
    }
    redirect('puntajes.php');
}

$titulo = 'Ranking de juegos';
$seccion = 'puntajes';
$subtitulo = 'Los mejores puntajes de cada juego. Podés quitar nombres inapropiados o reiniciar un ranking.';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <?php foreach ($juegos as $k => $nombre):
    $st = $pdo->prepare('SELECT * FROM puntajes WHERE juego = ? ORDER BY puntos DESC, creado LIMIT 30');
    $st->execute([$k]);
    $rows = $st->fetchAll(); ?>
    <div class="col-xl-4">
      <div class="panel h-100">
        <div class="panel-head">
          <h2><i class="fa-solid fa-trophy text-warning"></i> <?= $nombre ?></h2>
          <?php if ($rows): ?>
            <form method="post" data-confirm="¿Reiniciar el ranking de <?= $nombre ?>?"><?= csrf_field() ?><input type="hidden" name="accion" value="reiniciar"><input type="hidden" name="juego" value="<?= $k ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-rotate-left"></i></button></form>
          <?php endif; ?>
        </div>
        <?php if (!$rows): ?>
          <div class="empty-state"><i class="fa-solid fa-gamepad"></i><p>Sin partidas aún.</p></div>
        <?php else: ?>
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>#</th><th>Nombre</th><th class="text-end">Puntos</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($rows as $i => $r): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= e($r['nombre']) ?><br><small class="text-muted"><?= $r['aciertos'] ?>/<?= $r['total'] ?> · <?= date('d/m/y', strtotime($r['creado'])) ?></small></td>
                  <td class="text-end fw-bold"><?= $r['puntos'] ?></td>
                  <td class="text-end"><form method="post" data-confirm="¿Quitar este puntaje?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-link text-danger p-0"><i class="fa-solid fa-xmark"></i></button></form></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
