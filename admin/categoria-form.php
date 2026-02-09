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
$success = false;

// Modo: crear o editar
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

// Cargar datos si es edición
$categoria = null;
if ($isEdit) {
    $categoria = $db->query("SELECT * FROM categorias WHERE id = ?", [$id])->fetch();
    if (!$categoria) {
        header('Location: categorias.php');
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF inválido';
    } else {
        
        $nombre = Security::sanitizeInput($_POST['nombre'] ?? '');
        $icono = Security::sanitizeInput($_POST['icono'] ?? 'location_on');
        $color = Security::sanitizeInput($_POST['color'] ?? '#f2930d');
        $formato = Security::sanitizeInput($_POST['formato'] ?? '1x1');
        $activa = isset($_POST['activa']) ? 1 : 0;
        
        // Validaciones
        if (empty($nombre)) {
            $errors[] = 'El nombre es obligatorio';
        }
        
        if (empty($icono)) {
            $errors[] = 'Debes seleccionar un icono';
        }
        
        if (!in_array($formato, ['1x1', '1x2'])) {
            $errors[] = 'Formato inválido';
        }
        
        // Generar slug
        $slug = generateSlug($nombre);
        
        // Verificar slug único (excepto si es el mismo registro en edición)
        $checkSlug = $db->query(
            "SELECT id FROM categorias WHERE slug = ? AND id != ?",
            [$slug, $id ?? 0]
        )->fetch();
        
        if ($checkSlug) {
            $errors[] = 'Ya existe una categoría con ese nombre';
        }
        
        // Gestionar imagen de portada
        $imagenPortada = $isEdit ? $categoria['imagen_portada'] : null;
        
        // Eliminar imagen si se solicitó
        if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] === '1' && $imagenPortada) {
            deleteImage(basename($imagenPortada), 'categorias');
            $imagenPortada = null;
        }
        
        // Procesar imagen recortada
        if (isset($_POST['cropped_image_categoria']) && !empty($_POST['cropped_image_categoria'])) {
            $base64Image = $_POST['cropped_image_categoria'];
            
            // Extraer datos de la imagen base64
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $imageData = base64_decode($base64Image);
                
                if ($imageData !== false) {
                    // Eliminar imagen anterior si existe
                    if ($imagenPortada) {
                        deleteImage(basename($imagenPortada), 'categorias');
                    }
                    
                    // Generar nombre único
                    $filename = uniqid() . '_' . time() . '.jpg';
                    $uploadPath = UPLOADS_DIR . 'categorias/' . $filename;
                    
                    // Crear directorio si no existe
                    if (!is_dir(UPLOADS_DIR . 'categorias/')) {
                        mkdir(UPLOADS_DIR . 'categorias/', 0755, true);
                    }
                    
                    // Guardar archivo
                    if (file_put_contents($uploadPath, $imageData)) {
                        $imagenPortada = 'uploads/categorias/' . $filename;
                    } else {
                        $errors[] = 'Error al guardar la imagen';
                    }
                }
            }
        }
        
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    // Actualizar
                    $db->query("
                        UPDATE categorias SET
                            nombre = ?,
                            slug = ?,
                            icono = ?,
                            color = ?,
                            imagen_portada = ?,
                            formato = ?,
                            activa = ?
                        WHERE id = ?
                    ", [$nombre, $slug, $icono, $color, $imagenPortada, $formato, $activa, $id]);
                    
                    Security::logSecurityEvent('categoria_updated', ['id' => $id]);
                    header('Location: categorias.php?success=updated');
                    exit;
                } else {
                    // Crear
                    $maxOrden = $db->query("SELECT MAX(orden) as max FROM categorias WHERE ciudad_id = 1")->fetch();
                    $nuevoOrden = ($maxOrden['max'] ?? 0) + 1;
                    
                    $db->query("
                        INSERT INTO categorias (ciudad_id, nombre, slug, icono, color, imagen_portada, formato, orden, activa)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ", [1, $nombre, $slug, $icono, $color, $imagenPortada, $formato, $nuevoOrden, $activa]);
                    
                    Security::logSecurityEvent('categoria_created', ['nombre' => $nombre]);
                    header('Location: categorias.php?success=created');
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Editar' : 'Nueva'; ?> Categoría - Admin</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 8px; }
        .crop-container { max-height: 400px; }
        #crop-preview-cat { width: 200px; height: 200px; overflow: hidden; border-radius: 8px; border: 2px solid #e5e7eb; }
    </style>
</head>
<body class="bg-gray-50">
    
    <?php include 'includes/navbar.php'; ?>
    
    <div class="max-w-4xl mx-auto px-6 py-8">
        
        <div class="mb-8">
            <a href="categorias.php" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800 mb-4">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Volver a categorías
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo $isEdit ? 'Editar Categoría' : 'Nueva Categoría'; ?>
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
        
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-8">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            
            <!-- Nombre -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Nombre de la categoría <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="nombre" 
                    required
                    maxlength="100"
                    value="<?php echo Security::escape($categoria['nombre'] ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] focus:border-transparent outline-none transition"
                    placeholder="Ej: Placas Conmemorativas"
                >
                <p class="text-xs text-gray-500 mt-1">El slug se generará automáticamente</p>
            </div>
            
            <!-- Icono -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Icono <span class="text-red-500">*</span>
                </label>
                <button type="button" onclick="abrirSelectorIconos()" class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-[#f2930d] transition flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div id="icono-preview" class="w-12 h-12 rounded-lg bg-[#f2930d] flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings: 'FILL' 1;" id="icono-preview-icon">
                                <?php echo Security::escape($categoria['icono'] ?? 'location_on'); ?>
                            </span>
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-800" id="icono-nombre"><?php echo Security::escape($categoria['icono'] ?? 'location_on'); ?></p>
                            <p class="text-xs text-gray-500">Click para cambiar</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-gray-400">search</span>
                </button>
                <input type="hidden" name="icono" id="icono-input" value="<?php echo Security::escape($categoria['icono'] ?? 'location_on'); ?>">
            </div>
            
            <!-- Color -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Color del icono
                </label>
                <div class="flex items-center gap-4">
                    <input 
                        type="color" 
                        name="color" 
                        id="color-picker"
                        value="<?php echo Security::escape($categoria['color'] ?? '#f2930d'); ?>"
                        class="w-16 h-12 rounded-lg border border-gray-300 cursor-pointer"
                    >
                    <input 
                        type="text" 
                        id="color-hex"
                        value="<?php echo Security::escape($categoria['color'] ?? '#f2930d'); ?>"
                        maxlength="7"
                        pattern="^#[0-9A-Fa-f]{6}$"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none"
                        placeholder="#f2930d"
                    >
                </div>
            </div>
            
            <!-- Formato -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Formato de visualización <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative cursor-pointer">
                        <input 
                            type="radio" 
                            name="formato" 
                            value="1x1"
                            <?php echo ($categoria['formato'] ?? '1x1') === '1x1' ? 'checked' : ''; ?>
                            class="peer sr-only"
                        >
                        <div class="border-2 border-gray-300 peer-checked:border-[#f2930d] peer-checked:bg-[#f2930d]/5 rounded-lg p-4 transition h-full">
                            <div class="flex flex-col items-center gap-2 h-full justify-center">
                                <div class="w-16 h-16 border-2 border-current rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-2xl">crop_square</span>
                                </div>
                                <div class="text-center">
                                    <p class="font-semibold text-gray-800">Cuadrado</p>
                                    <p class="text-xs text-gray-500">1x1 (50% ancho)</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="relative cursor-pointer">
                        <input 
                            type="radio" 
                            name="formato" 
                            value="1x2"
                            <?php echo ($categoria['formato'] ?? '1x1') === '1x2' ? 'checked' : ''; ?>
                            class="peer sr-only"
                        >
                        <div class="border-2 border-gray-300 peer-checked:border-[#f2930d] peer-checked:bg-[#f2930d]/5 rounded-lg p-4 transition h-full">
                            <div class="flex flex-col items-center gap-2 h-full justify-center">
                                <div class="w-20 h-10 border-2 border-current rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-2xl">rectangle</span>
                                </div>
                                <div class="text-center">
                                    <p class="font-semibold text-gray-800">Rectangular</p>
                                    <p class="text-xs text-gray-500">1x2 (100% ancho)</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-2">Define cómo se mostrará la categoría en el grid de la app pública</p>
            </div>
            
            <!-- Imagen de portada -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Imagen de portada (opcional)
                </label>
                
                <?php if ($isEdit && $categoria['imagen_portada']): ?>
                <div class="mb-4">
                    <div class="relative group inline-block max-w-md">
                        <img src="../<?php echo Security::escape($categoria['imagen_portada']); ?>" class="w-full h-48 object-cover rounded-lg border-2 border-gray-200">
                        <button type="button" onclick="eliminarImagenCategoria()" class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-2 opacity-0 group-hover:opacity-100 transition shadow-lg">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                    <input type="hidden" name="eliminar_imagen" id="eliminar-imagen-input" value="0">
                </div>
                <?php endif; ?>
                
                <div id="preview-imagen-categoria" class="mb-4 hidden">
                    <div class="relative group inline-block max-w-md">
                        <img id="preview-img-cat" src="" class="w-full h-48 object-cover rounded-lg border-2 border-green-500">
                        <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                            Nueva
                        </div>
                        <button type="button" onclick="eliminarImagenCategoriaRecortada()" class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-2 opacity-0 group-hover:opacity-100 transition shadow-lg">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </div>
                
                <button type="button" onclick="document.getElementById('imagen-cat-input').click()" class="px-4 py-3 border-2 border-dashed border-[#f2930d] text-[#f2930d] rounded-lg font-medium hover:bg-[#f2930d]/5 transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">add_photo_alternate</span>
                    <?php echo ($isEdit && $categoria['imagen_portada']) ? 'Cambiar imagen' : 'Agregar imagen'; ?>
                </button>
                <input type="file" id="imagen-cat-input" accept="image/*" class="hidden" onchange="abrirCropperCategoria(this)">
                <p class="text-xs text-gray-500 mt-2">La imagen se recortará en formato cuadrado (800x800px). JPG, PNG o WebP. Máximo 5MB.</p>
                
                <!-- Hidden input para imagen recortada -->
                <input type="hidden" name="cropped_image_categoria" id="cropped-image-categoria">
            </div>
            
            <!-- Activa -->
            <div class="mb-8">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="activa"
                        <?php echo ($categoria['activa'] ?? 1) ? 'checked' : ''; ?>
                        class="w-5 h-5 text-[#f2930d] rounded border-gray-300 focus:ring-[#f2930d]"
                    >
                    <div>
                        <span class="font-medium text-gray-800">Categoría activa</span>
                        <p class="text-xs text-gray-500">Las categorías inactivas no se mostrarán en la app</p>
                    </div>
                </label>
            </div>
            
            <!-- Botones -->
            <div class="flex gap-4">
                <a href="categorias.php" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 text-center transition">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 px-6 py-3 bg-[#f2930d] text-white rounded-lg font-bold hover:bg-[#d9820b] transition">
                    <?php echo $isEdit ? 'Actualizar' : 'Crear'; ?> Categoría
                </button>
            </div>
        </form>
    </div>
    
    <!-- Modal selector de iconos -->
    <div id="modal-iconos" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Seleccionar Icono</h3>
                    <button onclick="cerrarSelectorIconos()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <input 
                    type="text" 
                    id="buscar-icono" 
                    placeholder="Buscar iconos..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none"
                    oninput="filtrarIconos()"
                >
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div id="iconos-grid" class="icon-grid"></div>
            </div>
        </div>
    </div>
    
    <script src="icons-data.js"></script>
    <script>
        // Sincronizar color picker con input hex
        const colorPicker = document.getElementById('color-picker');
        const colorHex = document.getElementById('color-hex');
        const iconoPreview = document.getElementById('icono-preview');
        
        colorPicker.addEventListener('input', (e) => {
            colorHex.value = e.target.value;
            iconoPreview.style.backgroundColor = e.target.value;
        });
        
        colorHex.addEventListener('input', (e) => {
            const valor = e.target.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(valor)) {
                colorPicker.value = valor;
                iconoPreview.style.backgroundColor = valor;
            }
        });
        
        // Modal de iconos
        function abrirSelectorIconos() {
            document.getElementById('modal-iconos').classList.remove('hidden');
            cargarIconos();
        }
        
        function cerrarSelectorIconos() {
            document.getElementById('modal-iconos').classList.add('hidden');
        }
        
        function cargarIconos() {
            const grid = document.getElementById('iconos-grid');
            grid.innerHTML = MATERIAL_ICONS.map(icon => `
                <button type="button" onclick="seleccionarIcono('${icon}')" class="flex flex-col items-center gap-2 p-3 rounded-lg hover:bg-gray-100 transition" data-icon="${icon}">
                    <span class="material-symbols-outlined text-2xl">${icon}</span>
                    <span class="text-[10px] text-gray-600 text-center">${icon.replace(/_/g, ' ')}</span>
                </button>
            `).join('');
        }
        
        function seleccionarIcono(icon) {
            document.getElementById('icono-input').value = icon;
            document.getElementById('icono-preview-icon').textContent = icon;
            document.getElementById('icono-nombre').textContent = icon;
            cerrarSelectorIconos();
        }
        
        function filtrarIconos() {
            const busqueda = document.getElementById('buscar-icono').value.toLowerCase();
            const botones = document.querySelectorAll('#iconos-grid button');
            botones.forEach(btn => {
                const icon = btn.dataset.icon;
                btn.style.display = icon.includes(busqueda) ? 'flex' : 'none';
            });
        }
    </script>
    
    <!-- Modal Cropper Categoría -->
    <div id="modal-cropper-cat" class="hidden fixed inset-0 bg-black/80 z-[1000] flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-800">Recortar imagen de portada</h3>
                    <button onclick="cerrarCropperCategoria()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-1">Ajusta el área de recorte (será un cuadrado perfecto 800x800)</p>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <div class="crop-container">
                            <img id="imagen-crop-cat" src="" alt="Imagen a recortar">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Preview</label>
                        <div id="crop-preview-cat" class="mx-auto"></div>
                        <div class="mt-4 space-y-2">
                            <button type="button" onclick="cropperCat.zoom(0.1)" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">zoom_in</span>
                                Acercar
                            </button>
                            <button type="button" onclick="cropperCat.zoom(-0.1)" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">zoom_out</span>
                                Alejar
                            </button>
                            <button type="button" onclick="cropperCat.rotate(90)" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">rotate_right</span>
                                Rotar 90°
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-6 border-t border-gray-200 flex gap-3">
                <button type="button" onclick="cerrarCropperCategoria()" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" onclick="guardarCropCategoria()" class="flex-1 px-6 py-3 bg-[#f2930d] text-white rounded-lg font-bold hover:bg-[#d9820b]">
                    Guardar recorte
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        // Sistema de Cropper para Categorías
        let cropperCat = null;
        
        function abrirCropperCategoria(input) {
            const file = input.files[0];
            if (!file) return;
            
            // Validar tamaño
            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo es demasiado grande. Máximo 5MB.');
                input.value = '';
                return;
            }
            
            // Validar tipo
            if (!file.type.match(/image\/(jpeg|jpg|png|webp)/)) {
                alert('Formato no válido. Solo JPG, PNG o WebP.');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('imagen-crop-cat');
                img.src = e.target.result;
                
                document.getElementById('modal-cropper-cat').classList.remove('hidden');
                
                if (cropperCat) {
                    cropperCat.destroy();
                }
                
                cropperCat = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    preview: '#crop-preview-cat'
                });
            };
            reader.readAsDataURL(file);
        }
        
        function cerrarCropperCategoria() {
            document.getElementById('modal-cropper-cat').classList.add('hidden');
            if (cropperCat) {
                cropperCat.destroy();
                cropperCat = null;
            }
            document.getElementById('imagen-cat-input').value = '';
        }
        
        function guardarCropCategoria() {
            if (!cropperCat) return;
            
            // Obtener canvas con la imagen recortada (800x800)
            const canvas = cropperCat.getCroppedCanvas({
                width: 800,
                height: 800,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            // Convertir a blob (JPEG)
            canvas.toBlob(function(blob) {
                const reader = new FileReader();
                reader.onloadend = function() {
                    const base64data = reader.result;
                    
                    // Guardar en input hidden
                    document.getElementById('cropped-image-categoria').value = base64data;
                    
                    // Mostrar preview
                    const previewContainer = document.getElementById('preview-imagen-categoria');
                    const previewImg = document.getElementById('preview-img-cat');
                    previewImg.src = base64data;
                    previewContainer.classList.remove('hidden');
                    
                    cerrarCropperCategoria();
                };
                reader.readAsDataURL(blob);
                
            }, 'image/jpeg', 0.9);
        }
        
        function eliminarImagenCategoria() {
            if (confirm('¿Eliminar la imagen de portada actual?')) {
                document.getElementById('eliminar-imagen-input').value = '1';
                event.target.closest('.relative').style.opacity = '0.3';
            }
        }
        
        function eliminarImagenCategoriaRecortada() {
            document.getElementById('cropped-image-categoria').value = '';
            document.getElementById('preview-imagen-categoria').classList.add('hidden');
            document.getElementById('preview-img-cat').src = '';
        }
    </script>
</body>
</html>