<?php
/**
 * Arranque del panel administrativo
 */
require_once __DIR__ . '/../../includes/funciones.php';

const P = '../'; // prefijo para llegar a la raíz del sitio desde /admin

function admin_logueado(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requiere_login(): void
{
    if (!admin_logueado()) {
        redirect('index.php');
    }
    // Expira la sesión tras 2 horas de inactividad
    if (time() - ($_SESSION['admin_ultimo'] ?? 0) > 7200) {
        session_unset();
        flash('warning', 'Tu sesión expiró. Ingresá nuevamente.');
        redirect('index.php');
    }
    $_SESSION['admin_ultimo'] = time();
}

/** Corta la petición POST si el token CSRF no es válido */
function verificar_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_check()) {
        flash('danger', 'El formulario expiró. Volvé a intentarlo.');
        redirect($_SERVER['REQUEST_URI']);
    }
}

/**
 * Campo de imagen reutilizable: permite subir un archivo o pegar una URL.
 */
function campo_imagen(string $name, ?string $actual, string $label = 'Imagen', bool $requerido = false): string
{
    $src = $actual ? e(img_url($actual, P)) : '';
    $id = 'img_' . $name;
    ob_start(); ?>
    <div class="img-field" data-img-field>
      <label class="form-label fw-semibold"><?= e($label) ?><?= $requerido ? ' *' : '' ?></label>
      <div class="img-field-box">
        <div class="img-preview <?= $src ? 'has-img' : '' ?>" style="<?= $src ? "background-image:url('$src')" : '' ?>">
          <span class="img-empty"><i class="fa-regular fa-image"></i><small>Sin imagen</small></span>
        </div>
        <div class="flex-grow-1">
          <label class="drop-zone" for="<?= $id ?>_file">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span><strong>Subí una imagen</strong> o arrastrala aquí</span>
            <small>JPG, PNG, WEBP o GIF · máx. <?= UPLOAD_MAX_MB ?> MB</small>
            <input type="file" id="<?= $id ?>_file" name="<?= $name ?>_file" accept="image/*" hidden>
          </label>
          <div class="input-group input-group-sm mt-2">
            <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
            <input type="url" class="form-control" name="<?= $name ?>_url" placeholder="…o pegá la URL de una imagen" value="<?= preg_match('~^https?://~', (string)$actual) ? e($actual) : '' ?>">
          </div>
          <?php if ($actual && !$requerido): ?>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="<?= $name ?>_quitar" id="<?= $id ?>_q" value="1">
              <label class="form-check-label small text-danger" for="<?= $id ?>_q">Quitar imagen</label>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php return ob_get_clean();
}

/**
 * Procesa un campo_imagen. Devuelve la nueva ruta/URL (o la actual si no cambió).
 * @throws RuntimeException
 */
function procesar_imagen(string $name, string $carpeta, ?string $actual): ?string
{
    $file = $_FILES[$name . '_file'] ?? null;
    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        $nueva = subir_imagen($file, $carpeta);
        if ($actual) borrar_archivo($actual);
        return $nueva;
    }
    $url = trim($_POST[$name . '_url'] ?? '');
    if ($url !== '' && $url !== $actual) {
        if (!url_imagen_valida($url)) throw new RuntimeException('La URL de la imagen no es válida.');
        if ($actual) borrar_archivo($actual);
        return $url;
    }
    if (!empty($_POST[$name . '_quitar'])) {
        if ($actual) borrar_archivo($actual);
        return null;
    }
    return $actual;
}

function badge_categoria(string $cat): string
{
    $c = categorias()[$cat] ?? ['nombre' => $cat, 'color' => '#888', 'icono' => 'fa-tag'];
    return '<span class="badge rounded-pill" style="background:' . $c['color'] . '"><i class="fa-solid ' . $c['icono'] . ' me-1"></i>' . $c['nombre'] . '</span>';
}
