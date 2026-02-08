<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/security.php';

Security::startSecureSession();
Security::setSecurityHeaders();
Security::requireAuth();

$db = Database::getInstance();

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Token CSRF inválido');
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            // Eliminar fotos asociadas
            $fotos = $db->query("SELECT filename FROM fotos_pois WHERE poi_id = ?", [$id])->fetchAll();
            foreach ($fotos as $foto) {
                deleteImage($foto['filename'], 'pois');
            }
            $db->query("DELETE FROM fotos_pois WHERE poi_id = ?", [$id]);
            
            // Eliminar POI
            $db->query("DELETE FROM pois WHERE id = ?", [$id]);
            Security::logSecurityEvent('poi_deleted', ['id' => $id]);
            header('Location: pois.php?success=deleted');
            exit;
        } catch (Exception $e) {
            $error = 'Error al eliminar el POI';
        }
    }
    
    // Actualizar orden
    if ($_POST['action'] === 'reorder' && isset($_POST['order'])) {
        $order = json_decode($_POST['order'], true);
        try {
            foreach ($order as $index => $id) {
                $db->query("UPDATE pois SET orden = ? WHERE id = ?", [$index + 1, $id]);
            }
            Security::logSecurityEvent('pois_reordered');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Filtros
$categoriaFiltro = $_GET['categoria'] ?? null;
$busqueda = $_GET['q'] ?? null;

// Construir query
$query = "
    SELECT p.id, p.titulo, p.slug, p.direccion, p.orden, p.activo, p.created_at,
           c.nombre as categoria_nombre, c.color as categoria_color, c.icono as categoria_icono,
           (SELECT COUNT(*) FROM fotos_pois WHERE poi_id = p.id) as total_fotos
    FROM pois p
    INNER JOIN categorias c ON c.id = p.categoria_id
    WHERE p.ciudad_id = 1
";

$params = [];

if ($categoriaFiltro) {
    $query .= " AND p.categoria_id = ?";
    $params[] = $categoriaFiltro;
}

if ($busqueda) {
    $query .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ? OR p.direccion LIKE ?)";
    $busquedaLike = '%' . $busqueda . '%';
    $params[] = $busquedaLike;
    $params[] = $busquedaLike;
    $params[] = $busquedaLike;
}

$query .= " ORDER BY p.orden ASC, p.id DESC";

$pois = $db->query($query, $params)->fetchAll();

// Obtener categorías para filtro
$categorias = $db->query("SELECT id, nombre, color FROM categorias WHERE ciudad_id = 1 AND activa = 1 ORDER BY nombre ASC")->fetchAll();

$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar POIs - Admin</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sortable-ghost { opacity: 0.4; }
    </style>
</head>
<body class="bg-gray-50">
    
    <?php include 'includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Gestionar POIs</h1>
                <p class="text-gray-600 mt-1">Puntos de interés y secretos de Sevilla</p>
            </div>
            <a href="poi-form.php" class="inline-flex items-center gap-2 bg-[#f2930d] text-white font-bold px-6 py-3 rounded-lg shadow-lg hover:bg-[#d9820b] transition">
                <span class="material-symbols-outlined">add</span>
                Nuevo POI
            </a>
        </div>
        
        <!-- Mensajes -->
        <?php if ($success === 'created'): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r">
            <p class="text-green-700 font-medium">✓ POI creado exitosamente</p>
        </div>
        <?php elseif ($success === 'updated'): ?>
        <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r">
            <p class="text-blue-700 font-medium">✓ POI actualizado exitosamente</p>
        </div>
        <?php elseif ($success === 'deleted'): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r">
            <p class="text-red-700 font-medium">✓ POI eliminado</p>
        </div>
        <?php endif; ?>
        
        <!-- Filtros y búsqueda -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" class="flex gap-4">
                <div class="flex-1">
                    <input 
                        type="text" 
                        name="q" 
                        placeholder="Buscar POIs..."
                        value="<?php echo Security::escape($busqueda ?? ''); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none"
                    >
                </div>
                <select name="categoria" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#f2930d] outline-none">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoriaFiltro == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo Security::escape($cat['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-6 py-2 bg-[#f2930d] text-white rounded-lg font-medium hover:bg-[#d9820b] transition">
                    Filtrar
                </button>
                <?php if ($busqueda || $categoriaFiltro): ?>
                <a href="pois.php" class="px-6 py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition">
                    Limpiar
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Info -->
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-blue-600">info</span>
                <div>
                    <p class="text-sm text-blue-800 font-medium">Arrastra y suelta para reordenar</p>
                    <p class="text-xs text-blue-600 mt-1">El orden se guarda automáticamente</p>
                </div>
            </div>
        </div>
        
        <!-- Listado -->
        <?php if (empty($pois)): ?>
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">explore</span>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No hay POIs</h3>
            <p class="text-gray-600 mb-6">Crea el primer punto de interés</p>
            <a href="poi-form.php" class="inline-flex items-center gap-2 bg-[#f2930d] text-white font-bold px-6 py-3 rounded-lg">
                <span class="material-symbols-outlined">add</span>
                Nuevo POI
            </a>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase w-12"></th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">POI</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Categoría</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Fotos</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody id="sortable-list" class="divide-y divide-gray-200">
                    <?php foreach ($pois as $poi): ?>
                    <tr class="hover:bg-gray-50 transition cursor-move" data-id="<?php echo $poi['id']; ?>">
                        <td class="px-6 py-4">
                            <span class="material-symbols-outlined text-gray-400 cursor-grab">drag_indicator</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-semibold text-gray-800"><?php echo Security::escape($poi['titulo']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo Security::escape($poi['direccion'] ?? 'Sin dirección'); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-xs font-medium text-white" style="background-color: <?php echo Security::escape($poi['categoria_color']); ?>;">
                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;"><?php echo Security::escape($poi['categoria_icono']); ?></span>
                                <?php echo Security::escape($poi['categoria_nombre']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">
                                <?php echo $poi['total_fotos']; ?>/3
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($poi['activo']): ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                Activo
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>
                                Inactivo
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="poi-form.php?id=<?php echo $poi['id']; ?>" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                    Editar
                                </a>
                                <button onclick="confirmarEliminacion(<?php echo $poi['id']; ?>, '<?php echo Security::escape($poi['titulo']); ?>')" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal eliminar -->
    <div id="modal-eliminar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-600">warning</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800">¿Eliminar POI?</h3>
            </div>
            <p class="text-gray-600 mb-6">
                ¿Estás seguro de que quieres eliminar "<span id="poi-nombre" class="font-semibold"></span>"? 
                Esta acción no se puede deshacer y eliminará también todas las fotos asociadas.
            </p>
            <form method="POST" id="form-eliminar">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="poi-id">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                        Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Sortable
        const sortableList = document.getElementById('sortable-list');
        if (sortableList) {
            Sortable.create(sortableList, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'sortable-ghost',
                onEnd: guardarOrden
            });
        }
        
        async function guardarOrden() {
            const rows = document.querySelectorAll('#sortable-list tr');
            const order = Array.from(rows).map(row => row.dataset.id);
            
            try {
                const response = await fetch('pois.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'reorder',
                        order: JSON.stringify(order),
                        csrf_token: '<?php echo Security::generateCSRFToken(); ?>'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('Orden actualizado', 'success');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function confirmarEliminacion(id, nombre) {
            document.getElementById('poi-id').value = id;
            document.getElementById('poi-nombre').textContent = nombre;
            document.getElementById('modal-eliminar').classList.remove('hidden');
        }
        
        function cerrarModal() {
            document.getElementById('modal-eliminar').classList.add('hidden');
        }
        
        function mostrarNotificacion(mensaje, tipo) {
            const div = document.createElement('div');
            div.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium ${tipo === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            div.textContent = mensaje;
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }
    </script>
</body>
</html>
