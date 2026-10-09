<?php
/**
 * Configuración general · Conociendo Formosa
 * Desarrollado por Dalila Spindola Dev
 *
 * Editá estos valores según tu servidor (XAMPP, WAMP, Laragon, hosting…).
 */

// ---- Base de datos ----
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'formosa_turismo');
define('DB_USER', 'root');      // XAMPP por defecto: root
define('DB_PASS', '');          // XAMPP por defecto: vacío
define('DB_CHARSET', 'utf8mb4');

// ---- Aplicación ----
define('APP_NAME', 'Conociendo Formosa');
define('APP_DEBUG', true);      // Poner en false en producción
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ---- Rutas ----
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', 'uploads');              // relativo a la raíz del sitio
define('UPLOAD_MAX_MB', 6);
define('UPLOAD_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
