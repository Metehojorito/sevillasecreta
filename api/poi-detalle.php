<?php
/**
 * API: Detalle de POI
 * 
 * GET /api/poi-detalle.php?slug=placa-cervantes&ciudad_slug=sevilla
 * GET /api/poi-detalle.php?id=1
 * Retorna el detalle completo de un POI con todas sus fotos
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getInstance();
    
    // Se puede buscar por ID o por slug
    $poiId = $_GET['id'] ?? null;
    $poiSlug = $_GET['slug'] ?? null;
    $ciudadSlug = $_GET['ciudad_slug'] ?? 'sevilla';
    
    if (!$poiId && !$poiSlug) {
        jsonError('Parámetro id o slug es requerido', 400);
    }
    
    // Construir query según parámetro
    if ($poiId) {
        $sql = "SELECT 
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.icono as categoria_icono,
                c.color as categoria_color,
                ci.nombre as ciudad_nombre,
                ci.slug as ciudad_slug
            FROM pois p
            INNER JOIN categorias c ON p.categoria_id = c.id
            INNER JOIN ciudades ci ON p.ciudad_id = ci.id
            WHERE p.id = ? AND p.activo = 1";
        $params = [$poiId];
    } else {
        $sql = "SELECT 
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.icono as categoria_icono,
                c.color as categoria_color,
                ci.nombre as ciudad_nombre,
                ci.slug as ciudad_slug
            FROM pois p
            INNER JOIN categorias c ON p.categoria_id = c.id
            INNER JOIN ciudades ci ON p.ciudad_id = ci.id
            WHERE p.slug = ? AND ci.slug = ? AND p.activo = 1";
        $params = [$poiSlug, $ciudadSlug];
    }
    
    $poi = $db->fetchOne($sql, $params);
    
    if (!$poi) {
        jsonError('POI no encontrado', 404);
    }
    
    // Obtener todas las fotos del POI
    $fotos = $db->fetchAll(
        "SELECT id, filename, orden FROM fotos_pois WHERE poi_id = ? ORDER BY orden ASC",
        [$poi['id']]
    );
    
    // Agregar ruta relativa a las fotos
    $fotosArray = [];
    foreach ($fotos as $foto) {
        $fotosArray[] = [
            'id' => $foto['id'],
            'url' => 'uploads/pois/' . $foto['filename'],
            'filename' => $foto['filename'],
            'orden' => $foto['orden']
        ];
    }
    
    // Calcular distancia si se proporcionan coordenadas del usuario
    $userLat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    $userLon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;
    
    $distancia = null;
    if ($userLat && $userLon) {
        $distanciaMetros = calcularDistancia($userLat, $userLon, $poi['latitud'], $poi['longitud']);
        $distancia = [
            'metros' => $distanciaMetros,
            'formateada' => formatearDistancia($distanciaMetros)
        ];
    }
    
    // Formatear respuesta
    $response = [
        'success' => true,
        'poi' => [
            'id' => $poi['id'],
            'titulo' => $poi['titulo'],
            'slug' => $poi['slug'],
            'descripcion' => $poi['descripcion'],
            'direccion' => $poi['direccion'],
            'latitud' => (float)$poi['latitud'],
            'longitud' => (float)$poi['longitud'],
            'horario' => $poi['horario'],
            'precio' => $poi['precio'],
            'categoria' => [
                'id' => $poi['categoria_id'],
                'nombre' => $poi['categoria_nombre'],
                'slug' => $poi['categoria_slug'],
                'icono' => $poi['categoria_icono'],
                'color' => $poi['categoria_color']
            ],
            'ciudad' => [
                'nombre' => $poi['ciudad_nombre'],
                'slug' => $poi['ciudad_slug']
            ],
            'fotos' => $fotosArray,
            'distancia' => $distancia,
            'created_at' => $poi['created_at'],
            'updated_at' => $poi['updated_at']
        ]
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    jsonError('Error al obtener detalle del POI: ' . $e->getMessage(), 500);
}