<?php
/**
 * Sevilla Secreta - Clase de Seguridad
 * 
 * Funciones de seguridad para prevenir:
 * - SQL Injection (ya cubierto por PDO preparado)
 * - Session Hijacking
 * - Session Fixation
 * - XSS
 * - CSRF
 */

class Security {
    
    /**
     * Iniciar sesión segura
     */
    public static function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuración segura de sesión
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Prevenir Session Fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
            
            // Validar sesión (prevenir Session Hijacking)
            self::validateSession();
        }
    }
    
    /**
     * Validar integridad de la sesión
     */
    private static function validateSession() {
        // Validar User Agent
        if (isset($_SESSION['HTTP_USER_AGENT'])) {
            if ($_SESSION['HTTP_USER_AGENT'] !== md5($_SERVER['HTTP_USER_AGENT'])) {
                self::destroySession();
                return false;
            }
        } else {
            $_SESSION['HTTP_USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT']);
        }
        
        // Validar IP (opcional, puede causar problemas con proxies/VPN)
        // if (isset($_SESSION['REMOTE_ADDR'])) {
        //     if ($_SESSION['REMOTE_ADDR'] !== $_SERVER['REMOTE_ADDR']) {
        //         self::destroySession();
        //         return false;
        //     }
        // } else {
        //     $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        // }
        
        // Regenerar ID de sesión periódicamente (cada 30 min)
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        return true;
    }
    
    /**
     * Destruir sesión de forma segura
     */
    public static function destroySession() {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    /**
     * Generar token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitizar output para prevenir XSS
     */
    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar y sanitizar input
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return trim(strip_tags($data));
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function isAuthenticated() {
        return isset($_SESSION['admin_id']) && isset($_SESSION['admin_usuario']);
    }
    
    /**
     * Requerir autenticación (redirigir si no está logueado)
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            header('Location: /sevilla-secreta/admin/login.php');
            exit;
        }
    }
    
    /**
     * Obtener ID del admin autenticado
     */
    public static function getAuthId() {
        return $_SESSION['admin_id'] ?? null;
    }
    
    /**
     * Obtener usuario del admin autenticado
     */
    public static function getAuthUser() {
        return $_SESSION['admin_usuario'] ?? null;
    }
    
    /**
     * Rate limiting simple (intentos de login)
     */
    public static function checkRateLimit($identifier, $max_attempts = 5, $timeframe = 900) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Si ha pasado el timeframe, resetear
        if (time() - $data['first_attempt'] > $timeframe) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Si superó el límite
        if ($data['attempts'] >= $max_attempts) {
            $remaining = $timeframe - (time() - $data['first_attempt']);
            return [
                'blocked' => true,
                'remaining_seconds' => $remaining
            ];
        }
        
        // Incrementar intentos
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * Hashear contraseña
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verificar contraseña
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Validar fortaleza de contraseña
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Generar contraseña aleatoria segura
     */
    public static function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    /**
     * Logging de eventos de seguridad
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user' => self::getAuthUser() ?? 'guest',
            'details' => $details
        ];
        
        $line = json_encode($entry) . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Prevenir clickjacking
     */
    public static function setSecurityHeaders() {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // header('Content-Security-Policy: default-src \'self\''); // Ajustar según necesidades
    }
}
