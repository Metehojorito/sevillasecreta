<?php
require_once '../includes/config.php';
require_once '../includes/security.php';

Security::startSecureSession();

// Logging antes de destruir sesión
if (Security::isAuthenticated()) {
    Security::logSecurityEvent('logout', [
        'usuario' => Security::getAuthUser(),
        'admin_id' => Security::getAuthId()
    ]);
}

// Destruir sesión de forma segura
Security::destroySession();

// Redirigir al login
header('Location: login.php');
exit;
