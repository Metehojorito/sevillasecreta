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

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Token CSRF inválido');
    }

    $id = (int)($_POST['id'] ?? 0);

    // --- DESCARTAR ---
    if ($_POST['action'] === 'descartar' && $id) {
        try {
            $db->query("DELETE FROM sugerencias_pois WHERE id = ?", [$id]);
            Security::logSecurityEvent('sugerencia_descartada', ['id' => $id]);
            header('Location: sugerencias.php?success=descartada');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error al descartar la sugerencia';
        }
    }

    // --- ACEPTAR: crear POI y eliminar sugerencia ---
    if ($_POST['action'] === 'aceptar' && $id) {
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $titulo       = Security::sanitizeInput($_POST['titulo'] ?? '');
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $direccion    = Security::sanitizeInput($_POST['direccion'] ?? '');
        $latitud      = (float)($_POST['latitud'] ?? 0);
        $longitud     = (float)($_POST['longitud'] ?? 0);
        $horario      = Security::sanitizeInput($_POST['horario'] ?? '');
        $precio       = Security::sanitizeInput($_POST['precio'] ?? '');

        if (empty($titulo))       $errors[] = 'El título es obligatorio';
        if (empty($descripcion))  $errors[] = 'La descripción es obligatoria';
        if ($categoria_id === 0)  $errors[] = 'Debes seleccionar una categoría';
        if ($latitud === 0.0 || $longitud === 0.0) $errors[] = 'Las coordenadas no son válidas';

        if (empty($errors)) {
            try {
                $slug = generateSlug($titulo);

                // Garantizar slug único
                $checkSlug = $db->query(
                    "SELECT id FROM pois WHERE slug = ?", [$slug]
                )->fetch();
                if ($checkSlug) {
                    $slug = $slug . '-' . $id;
                }

                $maxOrden = $db->query("SELECT MAX(orden) as max FROM pois WHERE ciudad_id = 1")->fetch();
                $nuevoOrden = ($maxOrden['max'] ?? 0) + 1;

                $db->query("
                    INSERT INTO pois
                        (ciudad_id, categoria_id, titulo, slug, descripcion, direccion,
                         latitud, longitud, horario, precio, orden, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ", [
                    1, $categoria_id, $titulo, $slug, $descripcion,
                    $direccion ?: null, $latitud, $longitud,
                    $horario ?: null, $precio ?: null, $nuevoOrden
                ]);

                $poiId = $db->lastInsertId();

                // Actualizar sugerencia como aprobada y borrarla
                $db->query("DELETE FROM sugerencias_pois WHERE id = ?", [$id]);

                Security::logSecurityEvent('sugerencia_aceptada', [
                    'sugerencia_id' => $id,
                    'poi_id'        => $poiId,
                    'titulo'        => $titulo,
                ]);

                header('Location: sugerencias.php?success=aceptada');
                exit;

            } catch (Exception $e) {
                $errors[] = 'Error al crear el POI: ' . $e->getMessage();
            }
        }

        // Si hay errores volvemos a cargar la sugerencia para mostrar el modal de edición
        $sugerenciaConError = $db->query(
            "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color, c.icono as categoria_icono
             FROM sugerencias_pois s
             LEFT JOIN categorias c ON c.id = s.categoria_id
             WHERE s.id = ?", [$id]
        )->fetch();
    }
}

// Filtro de estado
$estadoFiltro = $_GET['estado'] ?? 'pendiente';
$estadosValidos = ['pendiente', 'aprobada', 'rechazada', 'todas'];
if (!in_array($estadoFiltro, $estadosValidos)) $estadoFiltro = 'pendiente';

$whereEstado = $estadoFiltro !== 'todas' ? "AND s.estado = ?" : "";
$params      = $estadoFiltro !== 'todas' ? [$estadoFiltro] : [];

$sugerencias = $db->query("
    SELECT s.*,
           c.nombre as categoria_nombre,
           c.color  as categoria_color,
           c.icono  as categoria_icono
    FROM sugerencias_pois s
    LEFT JOIN categorias c ON c.id = s.categoria_id
    WHERE s.ciudad_id = 1 $whereEstado
    ORDER BY s.created_at DESC
", $params)->fetchAll();

// Contadores por estado
$contadores = $db->query("
    SELECT estado, COUNT(*) as total
    FROM sugerencias_pois
    WHERE ciudad_id = 1
    GROUP BY estado
")->fetchAll(PDO::FETCH_KEY_PAIR);

$categorias = $db->query("
    SELECT id, nombre, color, icono
    FROM categorias
    WHERE ciudad_id = 1 AND activa = 1
    ORDER BY nombre ASC
")->fetchAll();

$success = $_GET['success'] ?? null;

// ID de sugerencia a abrir en el modal (por URL)
$verId = isset($_GET['ver']) ? (int)$_GET['ver'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugerencias - Admin</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        #map-detalle { height: 280px; }
        .icon-filled { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-gray-50">

    <?php include 'includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Sugerencias de usuarios</h1>
                <p class="text-gray-600 mt-1">Lugares propuestos por la comunidad</p>
            </div>
            <div class="flex items-center gap-3">
                <?php $pendientes = $contadores['pendiente'] ?? 0; ?>
                <?php if ($pendientes > 0): ?>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-800 rounded-lg font-semibold text-sm">
                    <span class="material-symbols-outlined text-base">pending</span>
                    <?php echo $pendientes; ?> pendiente<?php echo $pendientes !== 1 ? 's' : ''; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($success === 'aceptada'): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r">
            <p class="text-green-700 font-medium">✓ Sugerencia aceptada y POI creado exitosamente</p>
        </div>
        <?php elseif ($success === 'descartada'): ?>
        <div class="mb-6 bg-gray-50 border-l-4 border-gray-400 p-4 rounded-r">
            <p class="text-gray-700 font-medium">✓ Sugerencia descartada</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r">
            <ul class="list-disc list-inside text-red-700 space-y-1">
                <?php foreach ($errors as $err): ?>
                <li><?php echo Security::escape($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Filtros de estado -->
        <div class="flex gap-2 mb-6 flex-wrap">
            <?php
            $tabs = [
                'pendiente'  => ['label' => 'Pendientes',  'color' => 'amber'],
                'todas'      => ['label' => 'Todas',        'color' => 'gray'],
            ];
            foreach ($tabs as $tabEstado => $tab):
                $count = $tabEstado === 'todas'
                    ? array_sum($contadores)
                    : ($contadores[$tabEstado] ?? 0);
                $isActive = $estadoFiltro === $tabEstado;
                $colorClass = $isActive
                    ? 'bg-[#f2930d] text-white border-[#f2930d]'
                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50';
            ?>
            <a href="sugerencias.php?estado=<?php echo $tabEstado; ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border font-medium text-sm transition <?php echo $colorClass; ?>">
                <?php echo $tab['label']; ?>
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold
                    <?php echo $isActive ? 'bg-white/30 text-white' : 'bg-gray-100 text-gray-600'; ?>">
                    <?php echo $count; ?>
                </span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Listado -->
        <?php if (empty($sugerencias)): ?>
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">mark_email_read</span>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Sin sugerencias</h3>
            <p class="text-gray-600">No hay sugerencias <?php echo $estadoFiltro !== 'todas' ? $estadoFiltro . 's' : ''; ?> en este momento</p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Sugerencia</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Categoría</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($sugerencias as $sug): ?>
                    <tr class="hover:bg-gray-50 transition" id="row-<?php echo $sug['id']; ?>">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-gray-800"><?php echo Security::escape($sug['titulo']); ?></p>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-1 max-w-xs">
                                <?php echo Security::escape(mb_substr($sug['descripcion'], 0, 80)) . '...'; ?>
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($sug['categoria_nombre']): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-white"
                                  style="background-color: <?php echo Security::escape($sug['categoria_color']); ?>;">
                                <span class="material-symbols-outlined text-sm icon-filled">
                                    <?php echo Security::escape($sug['categoria_icono']); ?>
                                </span>
                                <?php echo Security::escape($sug['categoria_nombre']); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">Sin categoría</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($sug['estado'] === 'pendiente'): ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Pendiente
                            </span>
                            <?php elseif ($sug['estado'] === 'aprobada'): ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Aprobada
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Rechazada
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">
                                <?php echo date('d/m/Y H:i', strtotime($sug['created_at'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="verSugerencia(<?php echo $sug['id']; ?>)"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                Ver
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>


    <!-- ===================== MODAL VER / ACEPTAR ===================== -->
    <div id="modal-sugerencia" class="hidden fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full my-8 shadow-2xl" id="modal-content">

            <!-- Header modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">Detalle de sugerencia</h3>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Cuerpo modal: se rellena por JS -->
            <div id="modal-body" class="p-6">
                <div class="text-center py-8">
                    <div class="inline-block w-8 h-8 border-4 border-[#f2930d] border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-gray-500 mt-3 text-sm">Cargando…</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL CONFIRMAR DESCARTAR ===================== -->
    <div id="modal-descartar" class="hidden fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-red-600">delete_forever</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800">¿Descartar sugerencia?</h3>
            </div>
            <p class="text-gray-600 mb-6">
                Esta acción eliminará la sugerencia de la base de datos. No se puede deshacer.
            </p>
            <form method="POST" id="form-descartar">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="descartar">
                <input type="hidden" name="id" id="descartar-id">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModalDescartar()"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                        Descartar
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Datos de sugerencias disponibles en JS
        const SUGERENCIAS = <?php echo json_encode(array_column($sugerencias, null, 'id')); ?>;
        const CATEGORIAS  = <?php echo json_encode($categorias); ?>;
        const CSRF_TOKEN  = '<?php echo Security::generateCSRFToken(); ?>';

        let mapaDetalle = null;

        // ── Abrir modal ──────────────────────────────────────────────
        function verSugerencia(id) {
            const s = SUGERENCIAS[id];
            if (!s) return;

            document.getElementById('modal-sugerencia').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            renderModalBody(s);
        }

        function renderModalBody(s) {
            const categoriasOptions = CATEGORIAS.map(c =>
                `<option value="${c.id}" ${s.categoria_id == c.id ? 'selected' : ''}>${escHtml(c.nombre)}</option>`
            ).join('');

            const estadoBadge = {
                pendiente : '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pendiente</span>',
                aprobada  : '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Aprobada</span>',
                rechazada : '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rechazada</span>',
            }[s.estado] ?? '';

            document.getElementById('modal-body').innerHTML = `
                <!-- Info básica -->
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            ${estadoBadge}
                            <span class="text-xs text-gray-400">${formatFecha(s.created_at)}</span>
                        </div>
                        <h4 class="text-xl font-bold text-gray-800">${escHtml(s.titulo)}</h4>
                    </div>
                </div>

                <p class="text-gray-700 text-sm leading-relaxed mb-5">${escHtml(s.descripcion)}</p>

                <!-- Mapa mini -->
                <div id="map-detalle" class="rounded-xl overflow-hidden border border-gray-200 mb-5"></div>
                <p class="text-xs text-gray-400 mb-5">
                    <span class="font-medium">Coordenadas:</span> ${parseFloat(s.latitud).toFixed(6)}, ${parseFloat(s.longitud).toFixed(6)}
                </p>

                <!-- Formulario para aceptar -->
                <form method="POST" id="form-aceptar">
                    <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                    <input type="hidden" name="action" value="aceptar">
                    <input type="hidden" name="id" value="${s.id}">
                    <input type="hidden" name="latitud" value="${s.latitud}">
                    <input type="hidden" name="longitud" value="${s.longitud}">

                    <div class="border-t border-gray-100 pt-5 space-y-4">
                        <p class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#f2930d] text-base">edit_note</span>
                            Revisa y ajusta antes de publicar
                        </p>

                        <!-- Título -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Título</label>
                            <input type="text" name="titulo" required maxlength="200"
                                   value="${escHtml(s.titulo)}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#f2930d] outline-none">
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Categoría</label>
                            <select name="categoria_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#f2930d] outline-none">
                                <option value="">Seleccionar categoría</option>
                                ${categoriasOptions}
                            </select>
                        </div>

                        <!-- Descripción -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción</label>
                            <textarea name="descripcion" required rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#f2930d] outline-none resize-none">${escHtml(s.descripcion)}</textarea>
                        </div>

                        <!-- Dirección / Horario / Precio -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Dirección (opcional)</label>
                                <input type="text" name="direccion" maxlength="255"
                                       placeholder="Ej: Calle Sierpes, 12"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#f2930d] outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Horario</label>
                                <input type="text" name="horario" maxlength="255"
                                       placeholder="Ej: 10:00–18:00"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#f2930d] outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Precio</label>
                                <input type="text" name="precio" maxlength="100"
                                       placeholder="Ej: Gratis"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#f2930d] outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex gap-3 mt-6 pt-5 border-t border-gray-100">
                        <button type="button"
                                onclick="abrirModalDescartar(${s.id})"
                                class="flex items-center gap-2 px-4 py-2.5 border border-red-300 text-red-600 rounded-lg font-medium text-sm hover:bg-red-50 transition">
                            <span class="material-symbols-outlined text-base">delete</span>
                            Descartar
                        </button>
                        <div class="flex-1"></div>
                        <button type="button" onclick="cerrarModal()"
                                class="px-4 py-2.5 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 transition">
                            Cerrar
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#f2930d] text-white rounded-lg font-bold text-sm hover:bg-[#d9820b] transition">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            Aceptar y publicar
                        </button>
                    </div>
                </form>
            `;

            // Inicializar mapa mini
            setTimeout(() => {
                if (mapaDetalle) {
                    mapaDetalle.remove();
                    mapaDetalle = null;
                }
                const lat = parseFloat(s.latitud);
                const lng = parseFloat(s.longitud);
                mapaDetalle = L.map('map-detalle').setView([lat, lng], 16);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(mapaDetalle);
                L.marker([lat, lng]).addTo(mapaDetalle);
            }, 50);
        }

        // ── Cerrar modal principal ───────────────────────────────────
        function cerrarModal() {
            document.getElementById('modal-sugerencia').classList.add('hidden');
            document.body.style.overflow = '';
            if (mapaDetalle) { mapaDetalle.remove(); mapaDetalle = null; }
        }

        // ── Modal descartar ──────────────────────────────────────────
        function abrirModalDescartar(id) {
            document.getElementById('descartar-id').value = id;
            document.getElementById('modal-descartar').classList.remove('hidden');
        }

        function cerrarModalDescartar() {
            document.getElementById('modal-descartar').classList.add('hidden');
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-sugerencia').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });

        // ── Helpers ─────────────────────────────────────────────────
        function escHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function formatFecha(ts) {
            if (!ts) return '';
            const d = new Date(ts.replace(' ', 'T'));
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
                 + ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }

        // Abrir directamente por URL ?ver=ID
        <?php if ($verId && isset($sugerencias)): ?>
        document.addEventListener('DOMContentLoaded', () => verSugerencia(<?php echo $verId; ?>));
        <?php endif; ?>
    </script>
</body>
</html>
