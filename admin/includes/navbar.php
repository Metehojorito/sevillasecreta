<nav class="bg-white border-b border-gray-200 px-6 py-4 mb-8">
    <div class="flex items-center justify-between max-w-7xl mx-auto">
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="flex items-center gap-3">
                <img src="../assets/img/logo.png" alt="Logo" class="w-10 h-10">
                <h1 class="text-xl font-bold text-gray-800">Panel Admin</h1>
            </a>
            <nav class="hidden md:flex items-center gap-1">
                <a href="dashboard.php" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                    Dashboard
                </a>
                <a href="categorias.php" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                    Categorías
                </a>
                <a href="pois.php" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                    POIs
                </a>
            </nav>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">
                <strong><?php echo Security::escape($_SESSION['admin_nombre'] ?? 'Admin'); ?></strong>
            </span>
            <a href="logout.php" class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-700 font-medium transition">
                <span class="material-symbols-outlined text-sm">logout</span>
                Salir
            </a>
        </div>
    </div>
</nav>
