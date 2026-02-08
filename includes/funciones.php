<?php
/**
 * Sevilla Secreta - Funciones Auxiliares
 * 
 * Funciones de utilidad general para la aplicación
 */

/**
 * Sanitizar cadenas de texto para evitar XSS
 */
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generar slug a partir de un texto
 */
function generateSlug($text) {
    // Reemplazar acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    // Convertir a minúsculas
    $text = strtolower($text);
    // Reemplazar caracteres no alfanuméricos con guiones
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    // Eliminar guiones al inicio y final
    $text = trim($text, '-');
    return $text;
}

/**
 * Calcular distancia entre dos puntos geográficos (Haversine)
 * Retorna la distancia en metros
 */
function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Radio de la Tierra en metros
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return round($distance);
}

/**
 * Formatear distancia para mostrar (m o km)
 */
function formatearDistancia($metros) {
    if ($metros < 1000) {
        return $metros . 'm';
    } else {
        return round($metros / 1000, 1) . 'km';
    }
}

/**
 * Verificar si el usuario está autenticado como admin
 */
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Requerir autenticación de admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /admin/index.php');
        exit;
    }
}

/**
 * Subir archivo de imagen
 * Retorna el nombre del archivo o false si hay error
 */
function uploadImage($file, $folder = 'pois') {
    $uploadDir = UPLOADS_DIR . $folder . '/';
    
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validar extensión
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }
    
    // Validar MIME type (con fallback si fileinfo no está disponible)
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    if (function_exists('finfo_open')) {
        // Método preferido: usar fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            return false;
        }
    } else {
        // Fallback: confiar en el MIME type del navegador
        if (!in_array($file['type'], $allowedMimes)) {
            return false;
        }
    }
    
    // Validar tamaño (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Generar nombre único
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }
    
    return false;
}

/**
 * Eliminar archivo de imagen
 */
function deleteImage($filename, $folder = 'pois') {
    $filepath = UPLOADS_DIR . $folder . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Responder con JSON (para APIs)
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Responder con error JSON
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}