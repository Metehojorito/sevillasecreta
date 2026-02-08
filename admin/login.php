<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/security.php';

// Iniciar sesión segura
Security::startSecureSession();
Security::setSecurityHeaders();

// Si ya está autenticado, redirigir al dashboard
if (Security::isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validar token CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf_attack', ['action' => 'login']);
        $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        
        $usuario = Security::sanitizeInput($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Rate limiting
        $rateLimit = Security::checkRateLimit($usuario, 5, 900);
        
        if (is_array($rateLimit) && isset($rateLimit['blocked'])) {
            $minutes = ceil($rateLimit['remaining_seconds'] / 60);
            $error = "Demasiados intentos fallidos. Intenta de nuevo en {$minutes} minutos.";
            Security::logSecurityEvent('rate_limit_exceeded', ['usuario' => $usuario]);
        } else {
            
            if (empty($usuario) || empty($password)) {
                $error = 'Por favor completa todos los campos.';
            } else {
                
                try {
                    $db = Database::getInstance();
                    $stmt = $db->query("SELECT id, usuario, password, nombre FROM admin WHERE usuario = ?", [$usuario]);
                    $admin = $stmt->fetch();
                    
                    if ($admin && Security::verifyPassword($password, $admin['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_usuario'] = $admin['usuario'];
                        $_SESSION['admin_nombre'] = $admin['nombre'];
                        $_SESSION['login_time'] = time();
                        
                        Security::logSecurityEvent('login_success', ['usuario' => $usuario]);
                        unset($_SESSION['rate_limit_' . md5($usuario)]);
                        
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'Usuario o contraseña incorrectos.';
                        Security::logSecurityEvent('login_failed', ['usuario' => $usuario]);
                        usleep(500000);
                    }
                } catch (Exception $e) {
                    $error = 'Error del sistema. Intenta de nuevo más tarde.';
                    Security::logSecurityEvent('login_error', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .golden-gradient { background: linear-gradient(135deg, #f2930d 0%, #c27500 100%); }
    </style>
</head>
<body class="bg-[#f8f7f5] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-block mb-4 bg-white rounded-2xl p-4 shadow-lg">
                <img src="../assets/img/logo_color.png" alt="Sevilla Secreta" class="w-20 h-20">
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Panel de Administración</h1>
            <p class="text-gray-600">Sevilla Secreta</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r">
                <div class="flex items-center">
                    <span class="material-symbols-outlined text-red-500 mr-2">error</span>
                    <p class="text-sm text-red-700"><?php echo Security::escape($error); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Usuario</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center material-symbols-outlined text-gray-400">person</span>
                        <input type="text" name="usuario" required maxlength="50" class="w-full pl-10 pr-3 py-3 border rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none" placeholder="Tu usuario">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Contraseña</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center material-symbols-outlined text-gray-400">lock</span>
                        <input type="password" name="password" required maxlength="255" class="w-full pl-10 pr-3 py-3 border rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none" placeholder="Tu contraseña">
                    </div>
                </div>
                
                <button type="submit" class="w-full golden-gradient text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-xl transition active:scale-95">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="mt-8 pt-6 border-t text-center">
                <p class="text-xs text-gray-500 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">verified_user</span>
                    Protección CSRF, Rate Limiting y Hashing
                </p>
            </div>
        </div>
        
        <div class="text-center mt-6">
            <a href="../index.php" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-[#f2930d]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Volver a la app pública
            </a>
        </div>
    </div>
</body>
</html>
