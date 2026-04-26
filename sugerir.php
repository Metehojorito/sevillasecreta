<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/funciones.php';

$db = Database::getInstance();
$success = false;
$error = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $latitud = (float)($_POST['latitud'] ?? 0);
    $longitud = (float)($_POST['longitud'] ?? 0);
    
    // Validaciones
    if (empty($titulo)) {
        $error = 'El título es obligatorio';
    } elseif (empty($descripcion)) {
        $error = 'La descripción es obligatoria';
    } elseif ($categoria_id === 0) {
        $error = 'Debes seleccionar una categoría';
    } elseif ($latitud === 0.0 || $longitud === 0.0) {
        $error = 'Debes seleccionar una ubicación en el mapa';
    } elseif (strlen($titulo) < 5) {
        $error = 'El título debe tener al menos 5 caracteres';
    } elseif (strlen($descripcion) < 20) {
        $error = 'La descripción debe tener al menos 20 caracteres';
    } else {
        try {
            // Insertar sugerencia
            $db->query("
                INSERT INTO sugerencias_pois (
                    ciudad_id, categoria_id, titulo, descripcion, 
                    latitud, longitud, ip_usuario, user_agent, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ", [
                1, // Sevilla
                $categoria_id,
                $titulo,
                $descripcion,
                $latitud,
                $longitud,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $success = true;
            
            // Limpiar formulario
            $_POST = [];
            
        } catch (Exception $e) {
            $error = 'Error al enviar la sugerencia. Por favor, inténtalo de nuevo.';
        }
    }
}

// Obtener categorías
$categorias = $db->query("
    SELECT id, nombre, icono, color 
    FROM categorias 
    WHERE ciudad_id = 1 AND activa = 1 
    ORDER BY nombre ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugerir Lugar - Sevilla Secreta</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#f2930d",
                        "background-light": "#f8f7f5",
                        "background-dark": "#221b10"
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        #map-selector { height: 400px; }
        .icon-filled { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-background-light min-h-screen">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary to-orange-600 text-white p-6">
        <div class="max-w-4xl mx-auto">
            <a href="index.php" class="inline-flex items-center gap-2 text-white/80 hover:text-white mb-4 text-sm">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Volver
            </a>
            <h1 class="text-3xl font-bold mb-2">Sugerir un lugar secreto</h1>
            <p class="text-white/90">¿Conoces algún rincón especial de Sevilla? ¡Compártelo con nosotros!</p>
        </div>
    </div>
    
    <div class="max-w-4xl mx-auto px-6 py-8">
        
        <?php if ($success): ?>
        <!-- Mensaje de éxito -->
        <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-r-xl mb-8">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600 text-3xl icon-filled">check_circle</span>
                <div>
                    <h3 class="text-lg font-bold text-green-800 mb-1">¡Gracias por tu sugerencia!</h3>
                    <p class="text-green-700 mb-3">Tu propuesta ha sido enviada correctamente. Nuestro equipo la revisará pronto.</p>
                    <div class="flex gap-3">
                        <a href="categorias.php" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition text-sm">
                            <span class="material-symbols-outlined text-sm">explore</span>
                            Explorar lugares
                        </a>
                        <button onclick="location.reload()" class="inline-flex items-center gap-2 px-4 py-2 border border-green-600 text-green-600 rounded-lg font-medium hover:bg-green-50 transition text-sm">
                            <span class="material-symbols-outlined text-sm">add</span>
                            Sugerir otro lugar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <!-- Mensaje de error -->
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl mb-6">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <!-- Formulario -->
        <form method="POST" class="space-y-6">
            
            <!-- Categoría -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">category</span>
                        ¿Qué tipo de lugar es? <span class="text-red-500">*</span>
                    </span>
                </label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($categorias as $cat): ?>
                    <label class="relative cursor-pointer">
                        <input 
                            type="radio" 
                            name="categoria_id" 
                            value="<?php echo $cat['id']; ?>"
                            <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $cat['id']) ? 'checked' : ''; ?>
                            class="peer sr-only"
                            required
                        >
                        <div class="border-2 border-gray-200 peer-checked:border-primary peer-checked:bg-primary/5 rounded-lg p-3 transition hover:bg-gray-50">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-2xl icon-filled" style="color: <?php echo htmlspecialchars($cat['color']); ?>;">
                                    <?php echo htmlspecialchars($cat['icono']); ?>
                                </span>
                                <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($cat['nombre']); ?></span>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Título -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">title</span>
                        Nombre del lugar <span class="text-red-500">*</span>
                    </span>
                </label>
                <input 
                    type="text" 
                    name="titulo"
                    value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>"
                    placeholder="Ej: Placa conmemorativa de Cervantes"
                    maxlength="200"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                >
                <p class="text-xs text-gray-500 mt-2">Mínimo 5 caracteres</p>
            </div>
            
            <!-- Descripción -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">description</span>
                        Cuéntanos sobre este lugar <span class="text-red-500">*</span>
                    </span>
                </label>
                <textarea 
                    name="descripcion"
                    rows="6"
                    placeholder="¿Qué tiene de especial este lugar? ¿Por qué es un secreto de Sevilla?"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                ><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-500 mt-2">Mínimo 20 caracteres. Sé descriptivo para ayudarnos a entender su valor histórico o cultural.</p>
            </div>
            
            <!-- Ubicación -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">location_on</span>
                        ¿Dónde está? <span class="text-red-500">*</span>
                    </span>
                </label>
                
                <div class="mb-4">
                    <button type="button" onclick="usarMiUbicacion()" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-3 bg-primary text-white rounded-lg font-medium hover:bg-orange-600 transition">
                        <span class="material-symbols-outlined">my_location</span>
                        Usar mi ubicación actual
                    </button>
                    <p class="text-xs text-gray-500 mt-2">O haz click en el mapa para marcar la ubicación exacta</p>
                </div>
                
                <div id="map-selector" class="rounded-lg overflow-hidden border-2 border-gray-300 mb-2"></div>
                
                <input type="hidden" name="latitud" id="lat-input" value="<?php echo $_POST['latitud'] ?? '37.3890924'; ?>">
                <input type="hidden" name="longitud" id="lng-input" value="<?php echo $_POST['longitud'] ?? '-5.9844589'; ?>">
                
                <p class="text-xs text-gray-600 mt-2">
                    <span class="material-symbols-outlined text-xs align-middle">info</span>
                    El marcador rojo indica la ubicación seleccionada
                </p>
            </div>
            
            <!-- Info adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-xl">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue-600">info</span>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Sobre tu sugerencia:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-700">
                            <li>Será revisada por nuestro equipo antes de publicarse</li>
                            <li>Podemos contactarte si necesitamos más información</li>
                            <li>Tu nombre no será publicado (a menos que lo desees)</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="flex gap-4">
                <a href="categorias.php" class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-bold text-center  hover:bg-gray-50 transition flex items-center justify-center">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-bold hover:bg-orange-600 transition">
                    Enviar sugerencia
                </button>
            </div>
        </form>
        <?php endif; ?>
        
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Inicializar mapa
        const map = L.map('map-selector').setView([<?php echo $_POST['latitud'] ?? 37.3890924; ?>, <?php echo $_POST['longitud'] ?? -5.9844589; ?>], 13);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = L.marker([<?php echo $_POST['latitud'] ?? 37.3890924; ?>, <?php echo $_POST['longitud'] ?? -5.9844589; ?>]).addTo(map);
        
        // Click en el mapa
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            marker.setLatLng([lat, lng]);
            document.getElementById('lat-input').value = lat.toFixed(8);
            document.getElementById('lng-input').value = lng.toFixed(8);
        });
        
        // Usar ubicación actual
        async function usarMiUbicacion() {
            if (!navigator.geolocation) {
                alert('Tu navegador no soporta geolocalización');
                return;
            }
            
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject);
                });
                
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 16);
                
                document.getElementById('lat-input').value = lat.toFixed(8);
                document.getElementById('lng-input').value = lng.toFixed(8);
                
            } catch (error) {
                alert('No se pudo obtener tu ubicación. Marca el lugar manualmente en el mapa.');
            }
        }
    </script>
</body>
</html>
