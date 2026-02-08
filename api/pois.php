<?php
/**
 * API: POIs
 * 
 * GET /api/pois.php?categoria_slug=placas-conmemorativas&ciudad_slug=sevilla
 * GET /api/pois.php?categoria_slug=placas-conmemorativas&ciudad_slug=sevilla&lat=37.389&lon=-5.992
 * Retorna todos los POIs de una categoría, opcionalmente con distancia calculada
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getInstance();
    
    // Parámetros requeridos
    $categoriaSlug = $_GET['categoria_slug'] ?? null;
    $ciudadSlug = $_GET['ciudad_slug'] ?? 'sevilla';
    
    if (!$categoriaSlug) {
        jsonError('Parámetro categoria_slug es requerido', 400);
    }
    
    // Parámetros opcionales para calcular distancia
    $userLat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    $userLon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;
    
    // Verificar que la categoría existe
    $categoria = $db->fetchOne(
        "SELECT c.*, ci.nombre as ciudad_nombre 
        FROM categorias c
        INNER JOIN ciudades ci ON c.ciudad_id = ci.id
        WHERE c.slug = ? AND ci.slug = ? AND c.activa = 1 AND ci.activa = 1",
        [$categoriaSlug, $ciudadSlug]
    );
    
    if (!$categoria) {
        jsonError('Categoría no encontrada', 404);
    }
    
    // Obtener POIs de la categoría
    $pois = $db->fetchAll(
        "SELECT 
            p.id,
            p.titulo,
            p.slug,
            p.descripcion,
            p.direccion,
            p.latitud,
            p.longitud,
            p.horario,
            p.precio,
            c.nombre as categoria_nombre,
            c.slug as categoria_slug,
            c.icono as categoria_icono,
            c.color as categoria_color
        FROM pois p
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE p.categoria_id = ? AND p.activo = 1
        ORDER BY p.titulo ASC",
        [$categoria['id']]
    );
    
    // Agregar fotos y calcular distancia para cada POI
    foreach ($pois as &$poi) {
        // Obtener primera foto
        $foto = $db->fetchOne(
            "SELECT filename FROM fotos_pois WHERE poi_id = ? ORDER BY orden ASC LIMIT 1",
            [$poi['id']]
        );
        $poi['foto_principal'] = $foto ? 'uploads/pois/' . $foto['filename'] : null;
        
        // Calcular distancia si se proporcionaron coordenadas del usuario
        if ($userLat && $userLon) {
            $distanciaMetros = calcularDistancia($userLat, $userLon, $poi['latitud'], $poi['longitud']);
            $poi['distancia_metros'] = $distanciaMetros;
            $poi['distancia_formateada'] = formatearDistancia($distanciaMetros);
        }
        
        // Convertir coordenadas a float
        $poi['latitud'] = (float)$poi['latitud'];
        $poi['longitud'] = (float)$poi['longitud'];
    }
    
    // Ordenar por distancia si se calculó
    if ($userLat && $userLon) {
        usort($pois, function($a, $b) {
            return $a['distancia_metros'] <=> $b['distancia_metros'];
        });
    }
    
    jsonResponse([
        'success' => true,
        'categoria' => [
            'id' => $categoria['id'],
            'nombre' => $categoria['nombre'],
            'slug' => $categoria['slug'],
            'icono' => $categoria['icono'],
            'color' => $categoria['color'],
            'ciudad' => $categoria['ciudad_nombre']
        ],
        'total' => count($pois),
        'pois' => $pois
    ]);
    
} catch (Exception $e) {
    jsonError('Error al obtener POIs: ' . $e->getMessage(), 500);
}