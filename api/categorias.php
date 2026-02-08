<?php
/**
 * API: Categorías
 * 
 * GET /api/categorias.php?ciudad_slug=sevilla
 * Retorna todas las categorías activas de una ciudad
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getInstance();
    
    // Obtener slug de ciudad (por defecto: sevilla)
    $ciudadSlug = $_GET['ciudad_slug'] ?? 'sevilla';
    
    // Verificar que la ciudad existe y está activa
    $ciudad = $db->fetchOne(
        "SELECT id, nombre, slug FROM ciudades WHERE slug = ? AND activa = 1",
        [$ciudadSlug]
    );
    
    if (!$ciudad) {
        jsonError('Ciudad no encontrada', 404);
    }
    
    // Obtener categorías de la ciudad ordenadas por 'orden'
    $categorias = $db->fetchAll(
        "SELECT 
            id,
            nombre,
            slug,
            icono,
            imagen_portada,
            color,
            formato,
            orden
        FROM categorias 
        WHERE ciudad_id = ? AND activa = 1 
        ORDER BY orden ASC, nombre ASC",
        [$ciudad['id']]
    );
    
    // Contar POIs por categoría
    foreach ($categorias as &$categoria) {
        $count = $db->fetchOne(
            "SELECT COUNT(*) as total FROM pois WHERE categoria_id = ? AND activo = 1",
            [$categoria['id']]
        );
        $categoria['total_pois'] = (int)$count['total'];
    }
    
    jsonResponse([
        'success' => true,
        'ciudad' => $ciudad,
        'categorias' => $categorias
    ]);
    
} catch (Exception $e) {
    jsonError('Error al obtener categorías: ' . $e->getMessage(), 500);
}