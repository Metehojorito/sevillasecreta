<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/security.php';
require_once '../includes/funciones.php';

Security::startSecureSession();
Security::setSecurityHeaders();
Security::requireAuth();

$db = Database::getInstance();
$errors = [];

// Modo
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

// Cargar POI si es edición
$poi = null;
$fotos = [];
if ($isEdit) {
    $poi = $db->query("SELECT * FROM pois WHERE id = ?", [$id])->fetch();
    if (!$poi) {
        header('Location: pois.php');
        exit;
    }
    $fotos = $db->query("SELECT id, filename, orden FROM fotos_pois WHERE poi_id = ? ORDER BY orden ASC", [$id])->fetchAll();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF inválido';
    } else {
        
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $titulo = Security::sanitizeInput($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $direccion = Security::sanitizeInput($_POST['direccion'] ?? '');
        $latitud = (float)($_POST['latitud'] ?? 0);
        $longitud = (float)($_POST['longitud'] ?? 0);
        $horario = Security::sanitizeInput($_POST['horario'] ?? '');
        $precio = Security::sanitizeInput($_POST['precio'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validaciones
        if (empty($titulo)) $errors[] = 'El título es obligatorio';
        if (empty($descripcion)) $errors[] = 'La descripción es obligatoria';
        if ($categoria_id === 0) $errors[] = 'Debes seleccionar una categoría';
        if ($latitud === 0.0 || $longitud === 0.0) $errors[] = 'Debes seleccionar una ubicación en el mapa';
        
        // Generar slug
        $slug = generateSlug($titulo);
        
        // Verificar slug único
        $checkSlug = $db->query(
            "SELECT id FROM pois WHERE slug = ? AND id != ?",
            [$slug, $id ?? 0]
        )->fetch();
        
        if ($checkSlug) {
            $errors[] = 'Ya existe un POI con ese título';
        }
        
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    // Actualizar
                    $db->query("
                        UPDATE pois SET
                            categoria_id = ?,
                            titulo = ?,
                            slug = ?,
                            descripcion = ?,
                            direccion = ?,
                            latitud = ?,
                            longitud = ?,
                            horario = ?,
                            precio = ?,
                            activo = ?
                        WHERE id = ?
                    ", [$categoria_id, $titulo, $slug, $descripcion, $direccion, $latitud, $longitud, 
                        $horario ?: null, $precio ?: null, $activo, $id]);
                    
                    $poiId = $id;
                    Security::logSecurityEvent('poi_updated', ['id' => $id]);
                } else {
                    // Crear
                    $maxOrden = $db->query("SELECT MAX(orden) as max FROM pois WHERE ciudad_id = 1")->fetch();
                    $nuevoOrden = ($maxOrden['max'] ?? 0) + 1;
                    
                    $db->query("
                        INSERT INTO pois (ciudad_id, categoria_id, titulo, slug, descripcion, direccion, latitud, longitud, horario, precio, orden, activo)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ", [1, $categoria_id, $titulo, $slug, $descripcion, $direccion, $latitud, $longitud, 
                        $horario ?: null, $precio ?: null, $nuevoOrden, $activo]);
                    
                    $poiId = $db->getLastInsertId();
                    Security::logSecurityEvent('poi_created', ['id' => $poiId, 'titulo' => $titulo]);
                }
                
                // Gestionar fotos (máximo 3)
                $fotosActuales = $db->query("SELECT COUNT(*) as total FROM fotos_pois WHERE poi_id = ?", [$poiId])->fetch();
                $totalFotos = (int)$fotosActuales['total'];
                
                if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['name'])) {
                    $cantidadNuevas = count(array_filter($_FILES['fotos']['name']));
                    
                    if ($totalFotos + $cantidadNuevas > 3) {
                        $errors[] = 'Máximo 3 fotos por POI';
                    } else {
                        foreach ($_FILES['fotos']['name'] as $key => $name) {
                            if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $_FILES['fotos']['name'][$key],
                                    'type' => $_FILES['fotos']['type'][$key],
                                    'tmp_name' => $_FILES['fotos']['tmp_name'][$key],
                                    'error' => $_FILES['fotos']['error'][$key],
                                    'size' => $_FILES['fotos']['size'][$key]
                                ];
                                
                                $filename = uploadImage($file, 'pois');
                                if ($filename) {
                                    $maxOrdenFoto = $db->query("SELECT MAX(orden) as max FROM fotos_pois WHERE poi_id = ?", [$poiId])->fetch();
                                    $ordenFoto = ($maxOrdenFoto['max'] ?? 0) + 1;
                                    
                                    $db->query("
                                        INSERT INTO fotos_pois (poi_id, filename, orden)
                                        VALUES (?, ?, ?)
                                    ", [$poiId, $filename, $ordenFoto]);
                                }
                            }
                        }
                    }
                }
                
                if (empty($errors)) {
                    header('Location: pois.php?success=' . ($isEdit ? 'updated' : 'created'));
                    exit;
                }
                
            } catch (Exception $e) {
                $errors[] = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
}

// Obtener categorías
$categorias = $db->query("SELECT id, nombre, color, icono FROM categorias WHERE ciudad_id = 1 AND activa = 1 ORDER BY nombre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Editar' : 'Nuevo'; ?> POI - Admin</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        #map-selector { height: 400px; }
    </style>
</head>
<body class="bg-gray-50">
    
    <?php include 'includes/navbar.php'; ?>
    
    <div class="max-w-4xl mx-auto px-6 py-8">
        
        <div class="mb-8">
            <a href="pois.php" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800 mb-4">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Volver a POIs
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo $isEdit ? 'Editar POI' : 'Nuevo POI'; ?>
            </h1>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r">
            <ul class="list-disc list-inside text-red-700 space-y-1">
                <?php foreach ($errors as $error): ?>
                <li><?php echo Security::escape($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            
            <!-- Categoría -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Categoría <span class="text-red-500">*</span>
                </label>
                <select name="categoria_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none">
                    <option value="">Seleccionar categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($poi['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo Security::escape($cat['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Título -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Título <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="titulo" 
                    required
                    maxlength="200"
                    value="<?php echo Security::escape($poi['titulo'] ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none"
                    placeholder="Ej: Placa de Miguel de Cervantes"
                >
            </div>
            
            <!-- Descripción -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Descripción <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="descripcion" 
                    required
                    rows="6"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none"
                    placeholder="Describe este punto de interés..."
                ><?php echo Security::escape($poi['descripcion'] ?? ''); ?></textarea>
            </div>
            
            <!-- Ubicación -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Ubicación <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="direccion" 
                    value="<?php echo Security::escape($poi['direccion'] ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none mb-4"
                    placeholder="Dirección"
                >
                <div id="map-selector" class="rounded-lg overflow-hidden border border-gray-300 mb-2"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Latitud</label>
                        <input 
                            type="number" 
                            name="latitud" 
                            id="lat-input"
                            step="0.000001"
                            value="<?php echo $poi['latitud'] ?? '37.3890924'; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            readonly
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Longitud</label>
                        <input 
                            type="number" 
                            name="longitud" 
                            id="lng-input"
                            step="0.000001"
                            value="<?php echo $poi['longitud'] ?? '-5.9844589'; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            readonly
                        >
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Haz click en el mapa para establecer la ubicación</p>
            </div>
            
            <!-- Horario y Precio -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Horario (opcional)</label>
                        <input 
                            type="text" 
                            name="horario" 
                            value="<?php echo Security::escape($poi['horario'] ?? ''); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none"
                            placeholder="Ej: 10:00 - 18:00"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Precio (opcional)</label>
                        <input 
                            type="text" 
                            name="precio" 
                            value="<?php echo Security::escape($poi['precio'] ?? ''); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none"
                            placeholder="Ej: Gratis"
                        >
                    </div>
                </div>
            </div>
            
            <!-- Fotos -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Fotos (máximo 3)
                </label>
                
                <?php if (!empty($fotos)): ?>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <?php foreach ($fotos as $foto): ?>
                    <div class="relative group">
                        <img src="../uploads/pois/<?php echo Security::escape($foto['filename']); ?>" class="w-full h-32 object-cover rounded-lg">
                        <button type="button" onclick="eliminarFoto(<?php echo $foto['id']; ?>)" class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (count($fotos) < 3): ?>
                <input 
                    type="file" 
                    name="fotos[]"
                    accept="image/*"
                    multiple
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-[#f2930d] file:text-white file:font-medium hover:file:bg-[#d9820b] file:cursor-pointer"
                >
                <p class="text-xs text-gray-500 mt-2">Quedan <?php echo 3 - count($fotos); ?> fotos disponibles. JPG, PNG o WebP. Máximo 5MB por imagen.</p>
                <?php else: ?>
                <p class="text-sm text-gray-600">Ya tienes el máximo de 3 fotos. Elimina alguna para subir otra.</p>
                <?php endif; ?>
            </div>
            
            <!-- Activo -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="activo"
                        <?php echo ($poi['activo'] ?? 1) ? 'checked' : ''; ?>
                        class="w-5 h-5 text-[#f2930d] rounded border-gray-300 focus:ring-[#f2930d]"
                    >
                    <div>
                        <span class="font-medium text-gray-800">POI activo</span>
                        <p class="text-xs text-gray-500">Los POIs inactivos no se mostrarán en la app</p>
                    </div>
                </label>
            </div>
            
            <!-- Botones -->
            <div class="flex gap-4">
                <a href="pois.php" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 text-center transition">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 px-6 py-3 bg-[#f2930d] text-white rounded-lg font-bold hover:bg-[#d9820b] transition">
                    <?php echo $isEdit ? 'Actualizar' : 'Crear'; ?> POI
                </button>
            </div>
        </form>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Mapa
        const map = L.map('map-selector').setView([<?php echo $poi['latitud'] ?? 37.3890924; ?>, <?php echo $poi['longitud'] ?? -5.9844589; ?>], 15);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        
        let marker = L.marker([<?php echo $poi['latitud'] ?? 37.3890924; ?>, <?php echo $poi['longitud'] ?? -5.9844589; ?>]).addTo(map);
        
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            marker.setLatLng([lat, lng]);
            document.getElementById('lat-input').value = lat.toFixed(6);
            document.getElementById('lng-input').value = lng.toFixed(6);
        });
        
        function eliminarFoto(id) {
            if (confirm('¿Eliminar esta foto?')) {
                window.location.href = `eliminar-foto.php?id=${id}&poi_id=<?php echo $id; ?>`;
            }
        }
    </script>
</body>
</html>
