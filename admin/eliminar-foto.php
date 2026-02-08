<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/security.php';
require_once '../includes/funciones.php';

Security::startSecureSession();
Security::setSecurityHeaders();
Security::requireAuth();

$fotoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$poiId = isset($_GET['poi_id']) ? (int)$_GET['poi_id'] : 0;

if ($fotoId && $poiId) {
    $db = Database::getInstance();
    
    // Obtener info de la foto
    $foto = $db->query("SELECT filename FROM fotos_pois WHERE id = ? AND poi_id = ?", [$fotoId, $poiId])->fetch();
    
    if ($foto) {
        // Eliminar archivo
        deleteImage($foto['filename'], 'pois');
        
        // Eliminar de BD
        $db->query("DELETE FROM fotos_pois WHERE id = ?", [$fotoId]);
        
        Security::logSecurityEvent('foto_deleted', ['foto_id' => $fotoId, 'poi_id' => $poiId]);
    }
}

header('Location: poi-form.php?id=' . $poiId);
exit;
