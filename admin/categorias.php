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
            $db->query("DELETE FROM categorias WHERE id = ?", [$id]);
            Security::logSecurityEvent('categoria_deleted', ['id' => $id]);
            header('Location: categorias.php?success=deleted');
            exit;
        } catch (Exception $e) {
            $error = 'Error al eliminar la categoría';
        }
    }
    
    // Actualizar orden
    if ($_POST['action'] === 'reorder' && isset($_POST['order'])) {
        $order = json_decode($_POST['order'], true);
        try {
            foreach ($order as $index => $id) {
                $db->query("UPDATE categorias SET orden = ? WHERE id = ?", [$index + 1, $id]);
            }
            Security::logSecurityEvent('categorias_reordered');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Obtener todas las categorías ordenadas
$categorias = $db->query("
    SELECT id, nombre, icono, color, imagen_portada, formato, orden, activa, created_at
    FROM categorias 
    WHERE ciudad_id = 1
    ORDER BY orden ASC, id ASC
")->fetchAll();

$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Categorías - Admin</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sortable-ghost { opacity: 0.4; }
        .sortable-drag { cursor: grabbing !important; }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Gestionar Categorías</h1>
                <p class="text-gray-600 mt-1">Organiza los tipos de secretos de Sevilla</p>
            </div>
            <a href="categoria-form.php" class="inline-flex items-center gap-2 bg-[#f2930d] text-white font-bold px-6 py-3 rounded-lg shadow-lg hover:bg-[#d9820b] transition">
                <span class="material-symbols-outlined">add</span>
                Nueva Categoría
            </a>
        </div>
        
        <!-- Mensaje de éxito -->
        <?php if ($success === 'created'): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r">
            <p class="text-green-700 font-medium">✓ Categoría creada exitosamente</p>
        </div>
        <?php elseif ($success === 'updated'): ?>
        <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r">
            <p class="text-blue-700 font-medium">✓ Categoría actualizada exitosamente</p>
        </div>
        <?php elseif ($success === 'deleted'): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r">
            <p class="text-red-700 font-medium">✓ Categoría eliminada</p>
        </div>
        <?php endif; ?>
        
        <!-- Info de ordenación -->
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-blue-600">info</span>
                <div>
                    <p class="text-sm text-blue-800 font-medium">Arrastra y suelta para reordenar</p>
                    <p class="text-xs text-blue-600 mt-1">El orden se guarda automáticamente</p>
                </div>
            </div>
        </div>
        
        <!-- Listado de categorías -->
        <?php if (empty($categorias)): ?>
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">category</span>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No hay categorías</h3>
            <p class="text-gray-600 mb-6">Crea la primera categoría para empezar</p>
            <a href="categoria-form.php" class="inline-flex items-center gap-2 bg-[#f2930d] text-white font-bold px-6 py-3 rounded-lg">
                <span class="material-symbols-outlined">add</span>
                Nueva Categoría
            </a>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-12"></th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Formato</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Creada</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="sortable-list" class="divide-y divide-gray-200">
                    <?php foreach ($categorias as $cat): ?>
                    <tr class="hover:bg-gray-50 transition cursor-move" data-id="<?php echo $cat['id']; ?>">
                        <td class="px-6 py-4">
                            <span class="material-symbols-outlined text-gray-400 cursor-grab">drag_indicator</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: <?php echo Security::escape($cat['color']); ?>;">
                                    <span class="material-symbols-outlined text-white text-xl" style="font-variation-settings: 'FILL' 1;"><?php echo Security::escape($cat['icono']); ?></span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo Security::escape($cat['nombre']); ?></p>
                                    <p class="text-xs text-gray-500">Orden: <?php echo $cat['orden']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-xs font-medium <?php echo $cat['formato'] === '1x2' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                <span class="material-symbols-outlined text-sm"><?php echo $cat['formato'] === '1x2' ? 'rectangle' : 'crop_square'; ?></span>
                                <?php echo $cat['formato'] === '1x2' ? 'Rectangular (1x2)' : 'Cuadrado (1x1)'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($cat['activa']): ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                Activa
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>
                                Inactiva
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo date('d/m/Y', strtotime($cat['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="categoria-form.php?id=<?php echo $cat['id']; ?>" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                    Editar
                                </a>
                                <button onclick="confirmarEliminacion(<?php echo $cat['id']; ?>, '<?php echo Security::escape($cat['nombre']); ?>')" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition">
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
    
    <!-- Modal de confirmación de eliminación -->
    <div id="modal-eliminar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-600">warning</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800">¿Eliminar categoría?</h3>
            </div>
            <p class="text-gray-600 mb-6">
                ¿Estás seguro de que quieres eliminar la categoría "<span id="categoria-nombre" class="font-semibold"></span>"? 
                Esta acción no se puede deshacer.
            </p>
            <form method="POST" id="form-eliminar">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="categoria-id">
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
        // Inicializar Sortable.js para drag & drop
        const sortableList = document.getElementById('sortable-list');
        if (sortableList) {
            Sortable.create(sortableList, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    guardarOrden();
                }
            });
        }
        
        // Guardar nuevo orden
        async function guardarOrden() {
            const rows = document.querySelectorAll('#sortable-list tr');
            const order = Array.from(rows).map(row => row.dataset.id);
            
            try {
                const response = await fetch('categorias.php', {
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
                    mostrarNotificacion('Orden actualizado correctamente', 'success');
                }
            } catch (error) {
                console.error('Error al guardar orden:', error);
            }
        }
        
        // Confirmar eliminación
        function confirmarEliminacion(id, nombre) {
            document.getElementById('categoria-id').value = id;
            document.getElementById('categoria-nombre').textContent = nombre;
            document.getElementById('modal-eliminar').classList.remove('hidden');
        }
        
        // Cerrar modal
        function cerrarModal() {
            document.getElementById('modal-eliminar').classList.add('hidden');
        }
        
        // Notificación temporal
        function mostrarNotificacion(mensaje, tipo) {
            const div = document.createElement('div');
            div.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transition ${
                tipo === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            div.textContent = mensaje;
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }
    </script>
</body>
</html>