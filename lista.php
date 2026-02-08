<?php
require_once 'includes/config.php';
$categoriaSlug = $_GET['categoria'] ?? null;
if (!$categoriaSlug) {
    header('Location: categorias.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Lista - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="assets/img/icon-512.png">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/app.css">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { "primary": "#f2930d", "background-light": "#f8f7f5", "background-dark": "#221b10" },
                    fontFamily: { "display": ["Plus Jakarta Sans", "sans-serif"] },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-[#1c160d] dark:text-[#fcfaf8]">
    <div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
        <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md">
            <div class="flex items-center p-4 pb-2 justify-between">
                <button onclick="window.location.href='categorias.php'" class="text-primary flex size-10 shrink-0 items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">arrow_back</span>
                </button>
                <h1 id="categoria-nombre" class="text-xl font-bold leading-tight tracking-tight flex-1 px-2">Cargando...</h1>
                <div class="flex w-10"></div>
            </div>
            <div class="px-4 py-3">
                <label class="flex flex-col min-w-40 h-12 w-full">
                    <div class="flex w-full flex-1 items-stretch rounded-xl h-full shadow-sm">
                        <div class="text-primary flex border-none bg-white dark:bg-[#322818] items-center justify-center pl-4 rounded-l-xl">
                            <span class="material-symbols-outlined">search</span>
                        </div>
                        <input id="search-input" class="form-input flex w-full min-w-0 flex-1 border-none bg-white dark:bg-[#322818] focus:ring-0 px-4 rounded-r-xl text-base font-normal" placeholder="Buscar secretos..." value=""/>
                    </div>
                </label>
            </div>
        </header>
        
        <main id="pois-list" class="flex flex-col gap-4 p-4 pb-24">
            <div class="text-center py-12">
                <div class="spinner mx-auto"></div>
                <p class="mt-4 text-gray-500">Cargando secretos...</p>
            </div>
        </main>
        
        <nav class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around bg-white dark:bg-[#322818] border-t border-[#f4eee7] dark:border-[#3d311d] px-8 h-20 pb-safe">
            <button onclick="window.location.href='categorias.php'" class="flex flex-col items-center justify-center gap-1 text-gray-400 dark:text-gray-500">
                <span class="material-symbols-outlined text-2xl">explore</span>
                <span class="text-[10px] font-bold uppercase tracking-tight">Explorar</span>
            </button>
            <button onclick="window.location.href='favoritos.php'" class="flex flex-col items-center justify-center gap-1 text-gray-400 dark:text-gray-500">
                <div class="relative">
                    <span class="material-symbols-outlined text-2xl">favorite</span>
                    <span id="favoritos-badge" class="hidden absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"></span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-tight">Favoritos</span>
            </button>
        </nav>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/favoritos.js"></script>
    <script>
        const categoriaSlug = '<?php echo htmlspecialchars($categoriaSlug); ?>';
        let todosLosPOIs = [];
        
        document.addEventListener('DOMContentLoaded', () => {
            cargarPOIs();
            renderFavoritosBadge('favoritos-badge');
            document.getElementById('search-input').addEventListener('input', SevillaSecreta.debounce((e) => {
                filtrarPOIs(e.target.value);
            }, 300));
        });

        async function cargarPOIs() {
            try {
                let params = { categoria_slug: categoriaSlug, ciudad_slug: 'sevilla' };
                try {
                    const loc = await SevillaSecreta.getUserLocation();
                    params.lat = loc.lat;
                    params.lon = loc.lon;
                } catch (e) {}
                
                const data = await SevillaSecreta.fetchAPI('pois.php', params);
                document.getElementById('categoria-nombre').textContent = data.categoria.nombre;
                todosLosPOIs = data.pois;
                renderPOIs(data.pois);
            } catch (error) {
                mostrarError('Error al cargar los secretos');
            }
        }

        function renderPOIs(pois) {
            const container = document.getElementById('pois-list');
            if (pois.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-8">No se encontraron secretos</p>';
                return;
            }
            
            let html = '';
            pois.forEach(poi => {
                const fotoUrl = poi.foto_principal || 'assets/img/placeholder-poi.svg';
                html += `
                    <div class="group flex flex-col overflow-hidden rounded-xl bg-white dark:bg-[#322818] shadow-sm border border-[#f4eee7] dark:border-[#3d311d] cursor-pointer" onclick="window.location.href='detalle.php?slug=${poi.slug}'">
                        <div class="relative w-full aspect-[16/9] overflow-hidden">
                            <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105" style="background-image: url('${fotoUrl}');"></div>
                            ${poi.distancia_formateada ? `<div class="absolute top-3 right-3 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg flex items-center gap-1"><span class="material-symbols-outlined text-xs">near_me</span>${poi.distancia_formateada}</div>` : ''}
                        </div>
                        <div class="flex flex-col p-4 gap-2">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-bold leading-tight group-hover:text-primary transition-colors">${poi.titulo}</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm leading-normal line-clamp-2">${SevillaSecreta.truncateText(poi.descripcion, 120)}</p>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-[#f4eee7] dark:border-[#3d311d]">
                                <div class="flex items-center gap-1 text-[#9c7a49] dark:text-primary/70">
                                    <span class="material-symbols-outlined text-sm">${poi.categoria_icono}</span>
                                    <span class="text-xs font-medium uppercase tracking-wider">${poi.precio || 'Disponible'}</span>
                                </div>
                                <button class="flex items-center gap-1 text-primary text-sm font-bold">
                                    Descubrir <span class="material-symbols-outlined text-base">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        function filtrarPOIs(query) {
            if (!query || query.length < 2) {
                renderPOIs(todosLosPOIs);
                return;
            }
            const q = query.toLowerCase();
            const filtrados = todosLosPOIs.filter(poi => 
                poi.titulo.toLowerCase().includes(q) ||
                poi.descripcion.toLowerCase().includes(q) ||
                (poi.direccion && poi.direccion.toLowerCase().includes(q))
            );
            renderPOIs(filtrados);
        }

        function mostrarError(mensaje) {
            document.getElementById('pois-list').innerHTML = `
                <div class="text-center py-8">
                    <span class="material-symbols-outlined text-4xl text-red-500">error</span>
                    <p class="text-red-500 mt-2">${mensaje}</p>
                    <button onclick="cargarPOIs()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg">Reintentar</button>
                </div>
            `;
        }
    </script>
</body>
</html>