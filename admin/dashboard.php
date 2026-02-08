<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/security.php';

Security::startSecureSession();
Security::setSecurityHeaders();
Security::requireAuth(); // Requiere estar autenticado

$admin_nombre = $_SESSION['admin_nombre'] ?? 'Admin';

// Obtener estadísticas básicas
try {
    $db = Database::getInstance();
    
    $stats = [
        'total_pois' => $db->query("SELECT COUNT(*) as total FROM pois WHERE activo = 1")->fetchColumn(),
        'total_categorias' => $db->query("SELECT COUNT(*) as total FROM categorias WHERE activa = 1")->fetchColumn(),
        'total_fotos' => $db->query("SELECT COUNT(*) as total FROM fotos_pois")->fetchColumn(),
    ];
} catch (Exception $e) {
    $stats = ['total_pois' => 0, 'total_categorias' => 0, 'total_fotos' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <img src="../assets/img/logo.png" alt="Logo" class="w-10 h-10">
                <h1 class="text-xl font-bold text-gray-800">Panel de Administración</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">Hola, <strong><?php echo Security::escape($admin_nombre); ?></strong></span>
                <a href="logout.php" class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-700 font-medium">
                    <span class="material-symbols-outlined text-sm">logout</span>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Bienvenida -->
        <div class="bg-gradient-to-r from-[#f2930d] to-[#c27500] rounded-2xl p-8 text-white mb-8">
            <h2 class="text-3xl font-bold mb-2">¡Bienvenido de nuevo!</h2>
            <p class="text-white/90">Gestiona el contenido de Sevilla Secreta desde este panel</p>
        </div>
        
        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">POIs Activos</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['total_pois']; ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-xl">
                        <span class="material-symbols-outlined text-blue-600 text-3xl">location_on</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Categorías</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['total_categorias']; ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-xl">
                        <span class="material-symbols-outlined text-purple-600 text-3xl">category</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Fotos</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['total_fotos']; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-xl">
                        <span class="material-symbols-outlined text-green-600 text-3xl">photo_library</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menú de Gestión -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Gestionar POIs -->
            <a href="pois.php" class="bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border border-gray-100 group">
                <div class="flex items-start gap-4">
                    <div class="bg-[#f2930d]/10 p-3 rounded-xl group-hover:bg-[#f2930d]/20 transition">
                        <span class="material-symbols-outlined text-[#f2930d] text-3xl">explore</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-800 mb-1">Gestionar POIs</h3>
                        <p class="text-sm text-gray-600">Crear, editar y eliminar puntos de interés (con fotos)</p>
                    </div>
                </div>
            </a>
            
            <!-- Gestionar Categorías -->
            <a href="categorias.php" class="bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border border-gray-100 group">
                <div class="flex items-start gap-4">
                    <div class="bg-purple-100 p-3 rounded-xl group-hover:bg-purple-200 transition">
                        <span class="material-symbols-outlined text-purple-600 text-3xl">category</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-800 mb-1">Gestionar Categorías</h3>
                        <p class="text-sm text-gray-600">Organizar tipos de secretos</p>
                    </div>
                </div>
            </a>
            
            <!-- Ver Logs -->
            <a href="logs.php" class="bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border border-gray-100 group">
                <div class="flex items-start gap-4">
                    <div class="bg-gray-100 p-3 rounded-xl group-hover:bg-gray-200 transition">
                        <span class="material-symbols-outlined text-gray-600 text-3xl">description</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-800 mb-1">Logs de Seguridad</h3>
                        <p class="text-sm text-gray-600">Ver eventos del sistema</p>
                    </div>
                </div>
            </a>
            
            <!-- Ver App Pública -->
            <a href="../index.php" target="_blank" class="bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border border-gray-100 group">
                <div class="flex items-start gap-4">
                    <div class="bg-blue-100 p-3 rounded-xl group-hover:bg-blue-200 transition">
                        <span class="material-symbols-outlined text-blue-600 text-3xl">open_in_new</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-800 mb-1">Ver App Pública</h3>
                        <p class="text-sm text-gray-600">Abrir aplicación en nueva pestaña</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</body>
</html>
