<?php
/**
 * Funciones auxiliares compartidas (sitio público, API y panel)
 */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** Escapa texto para HTML */
function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Devuelve la URL utilizable de una imagen.
 * Las imágenes pueden ser externas (https://…) o subidas (uploads/…).
 * $prefix permite usarla desde subcarpetas (ej. '../' en el panel).
 */
function img_url(?string $path, string $prefix = ''): string
{
    if (!$path) return '';
    if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, 'data:')) return $path;
    return $prefix . ltrim($path, '/');
}

/**
 * Devuelve la ruta de un archivo CSS/JS con su versión (fecha de modificación).
 * Así el navegador descarga la versión nueva cada vez que el archivo cambia.
 * $base: carpeta física desde la que se resuelve la ruta (por defecto, la raíz del sitio).
 */
function asset(string $ruta, ?string $base = null): string
{
    $archivo = rtrim($base ?? ROOT_PATH, '/\\') . '/' . ltrim($ruta, '/');
    $v = is_file($archivo) ? filemtime($archivo) : time();
    return $ruta . '?v=' . $v;
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower(preg_replace('~[^A-Za-z0-9]+~', '-', $text));
    return trim($text, '-') ?: 'item-' . time();
}

/** Genera un slug único para una tabla */
function slug_unico(string $tabla, string $texto, int $ignorarId = 0): string
{
    $base = slugify($texto);
    $slug = $base;
    $i = 2;
    $st = db()->prepare("SELECT COUNT(*) FROM `$tabla` WHERE slug = ? AND id <> ?");
    while (true) {
        $st->execute([$slug, $ignorarId]);
        if (!$st->fetchColumn()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

/** Lee toda la configuración en un arreglo clave => valor (cacheado) */
function config_all(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = db()->query('SELECT clave, valor FROM config')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cfg;
}

function cfg(string $clave, string $def = ''): string
{
    return config_all()[$clave] ?? $def;
}

/** Respuesta JSON y fin */
function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- CSRF ---------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------------- Mensajes flash ---------------- */
function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------------- Subida de imágenes ---------------- */
/**
 * Valida y guarda una imagen subida. Devuelve la ruta relativa (uploads/carpeta/archivo.ext)
 * @throws RuntimeException
 */
function subir_imagen(array $file, string $carpeta): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errores = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el tamaño permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo es demasiado grande.',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
            UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        ];
        throw new RuntimeException($errores[$file['error']] ?? 'Error al subir el archivo.');
    }
    if ($file['size'] > UPLOAD_MAX_MB * 1024 * 1024) {
        throw new RuntimeException('La imagen supera los ' . UPLOAD_MAX_MB . ' MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_EXT, true)) {
        throw new RuntimeException('Formato no permitido. Usá: ' . implode(', ', UPLOAD_EXT));
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $mimes, true) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('El archivo no es una imagen válida.');
    }
    $carpeta = preg_replace('~[^a-z0-9_-]~', '', strtolower($carpeta)) ?: 'varios';
    $dir = UPLOAD_PATH . '/' . $carpeta;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de destino.');
    }
    $nombre = date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return UPLOAD_URL . '/' . $carpeta . '/' . $nombre;
}

/** Normaliza el arreglo $_FILES de un input multiple */
function archivos_multiples(array $files): array
{
    $out = [];
    if (!is_array($files['name'] ?? null)) return [$files];
    foreach ($files['name'] as $i => $n) {
        $out[] = [
            'name' => $n, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i], 'size' => $files['size'][$i],
        ];
    }
    return $out;
}

/** Borra un archivo subido localmente (ignora URLs externas) */
function borrar_archivo(?string $path): void
{
    if (!$path || preg_match('~^(https?:)?//~i', $path)) return;
    $real = realpath(ROOT_PATH . '/' . $path);
    if ($real && str_starts_with($real, realpath(UPLOAD_PATH)) && is_file($real)) {
        @unlink($real);
    }
}

/** Valida una URL de imagen externa */
function url_imagen_valida(string $url): bool
{
    return (bool)filter_var($url, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $url);
}

function categorias(): array
{
    return [
        'cultura'    => ['nombre' => 'Cultura',    'color' => '#C2552F', 'icono' => 'fa-landmark',
                         'pregunta' => 'Quiero conocer la cultura',
                         'descripcion' => 'Historia, pueblos originarios, costumbres y tradiciones'],
        'naturaleza' => ['nombre' => 'Naturaleza', 'color' => '#2E8B3E', 'icono' => 'fa-leaf',
                         'pregunta' => 'Quiero conocer la naturaleza',
                         'descripcion' => 'Flora, fauna, humedales, ríos y monte'],
        'paseo'      => ['nombre' => 'Paseo',      'color' => '#2B6CD9', 'icono' => 'fa-camera',
                         'pregunta' => 'Quiero salir de paseo',
                         'descripcion' => 'Ciudades, costaneras, playas, fiestas y pueblos para visitar'],
    ];
}

/**
 * Convierte el texto de fuentes (una por línea: "Cita|URL") en un arreglo.
 */
function parse_fuentes(?string $txt): array
{
    $out = [];
    foreach (preg_split('/\R/', (string)$txt) as $linea) {
        $linea = trim($linea);
        if ($linea === '') continue;
        [$cita, $url] = array_pad(explode('|', $linea, 2), 2, '');
        $url = trim($url);
        $out[] = ['cita' => trim($cita), 'url' => preg_match('~^https?://~i', $url) ? $url : ''];
    }
    return $out;
}

function categorias_bibliografia(): array
{
    return [
        'programa'     => 'Bibliografía del programa (Eje 2)',
        'oficial'      => 'Fuentes oficiales',
        'academica'    => 'Artículos académicos',
        'audiovisual'  => 'Material audiovisual',
        'periodistica' => 'Notas periodísticas',
    ];
}

function ip_cliente(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}
