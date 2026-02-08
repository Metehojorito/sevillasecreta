<?php
/**
 * API: Búsqueda
 * 
 * GET /api/buscar.php?q=cervantes&ciudad_slug=sevilla
 * Busca POIs por título o descripción
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getInstance();
    
    // Parámetros
    $query = $_GET['q'] ?? '';
    $ciudadSlug = $_GET['ciudad_slug'] ?? 'sevilla';
    $categoriaSlug = $_GET['categoria_slug'] ?? null; // Opcional: filtrar por categoría
    
    if (strlen($query) < 2) {
        jsonError('La búsqueda debe tener al menos 2 caracteres', 400);
    }
    
    // Verificar que la ciudad existe
    $ciudad = $db->fetchOne(
        "SELECT id FROM ciudades WHERE slug = ? AND activa = 1",
        [$ciudadSlug]
    );
    
    if (!$ciudad) {
        jsonError('Ciudad no encontrada', 404);
    }
    
    // Construir query de búsqueda
    $searchTerm = '%' . $query . '%';
    
    if ($categoriaSlug) {
        $sql = "SELECT 
                p.id,
                p.titulo,
                p.slug,
                p.descripcion,
                p.direccion,
                p.latitud,
                p.longitud,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.icono as categoria_icono,
                c.color as categoria_color
            FROM pois p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE p.ciudad_id = ? 
                AND c.slug = ?
                AND p.activo = 1 
                AND (p.titulo LIKE ? OR p.descripcion LIKE ? OR p.direccion LIKE ?)
            ORDER BY p.titulo ASC
            LIMIT 20";
        $params = [$ciudad['id'], $categoriaSlug, $searchTerm, $searchTerm, $searchTerm];
    } else {
        $sql = "SELECT 
                p.id,
                p.titulo,
                p.slug,
                p.descripcion,
                p.direccion,
                p.latitud,
                p.longitud,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug,
                c.icono as categoria_icono,
                c.color as categoria_color
            FROM pois p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE p.ciudad_id = ? 
                AND p.activo = 1 
                AND (p.titulo LIKE ? OR p.descripcion LIKE ? OR p.direccion LIKE ?)
            ORDER BY p.titulo ASC
            LIMIT 20";
        $params = [$ciudad['id'], $searchTerm, $searchTerm, $searchTerm];
    }
    
    $resultados = $db->fetchAll($sql, $params);
    
    // Agregar foto principal a cada resultado
    foreach ($resultados as &$resultado) {
        $foto = $db->fetchOne(
            "SELECT filename FROM fotos_pois WHERE poi_id = ? ORDER BY orden ASC LIMIT 1",
            [$resultado['id']]
        );
        $resultado['foto_principal'] = $foto ? UPLOADS_URL . 'pois/' . $foto['filename'] : null;
        
        // Acortar descripción para resultados de búsqueda
        $resultado['descripcion_corta'] = mb_substr($resultado['descripcion'], 0, 150) . '...';
        
        // Convertir coordenadas a float
        $resultado['latitud'] = (float)$resultado['latitud'];
        $resultado['longitud'] = (float)$resultado['longitud'];
    }
    
    jsonResponse([
        'success' => true,
        'query' => $query,
        'total' => count($resultados),
        'resultados' => $resultados
    ]);
    
} catch (Exception $e) {
    jsonError('Error en la búsqueda: ' . $e->getMessage(), 500);
}