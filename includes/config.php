<?php
/**
 * Sevilla Secreta - Configuración General
 * 
 * Define las constantes de configuración de la aplicación
 */

// Configuración de la base de datos
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sevilla_secreta');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración general de la aplicación
define('APP_NAME', 'Sevilla Secreta');
define('APP_URL', 'http://localhost/sevilla-secreta');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('UPLOADS_URL', APP_URL . '/uploads/');

// Configuración de sesiones (para el admin)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Timezone
date_default_timezone_set('Europe/Madrid');

// Errores (cambiar a 0 en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);