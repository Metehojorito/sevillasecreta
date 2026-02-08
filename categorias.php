<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Categorías - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="assets/img/icon-512.png">
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/app.css">
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f2930d",
                        "background-light": "#f8f7f5",
                        "background-dark": "#221b10",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"],
                        "serif": ["Playfair Display", "serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#1c160d] dark:text-[#fcfaf8] antialiased">
    <div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        <!-- Top App Bar -->
        <header class="sticky top-0 z-50 flex items-center bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-md p-4 pb-2 justify-between border-b border-black/5 dark:border-white/5">
            <button onclick="SevillaSecreta.toggleDarkMode()" class="flex size-10 shrink-0 items-center justify-center rounded-full hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer">
                <span class="material-symbols-outlined text-2xl">dark_mode</span>
            </button>
            <h1 class="font-serif text-xl font-bold leading-tight tracking-tight text-center flex-1">Descubre Sevilla Secreta</h1>
            <div class="flex size-10"></div>
        </header>
        
        <!-- Main Content Section -->
        <main class="flex-1 pb-24">
            <div class="p-4">
                <p class="text-sm font-medium text-primary uppercase tracking-wider mb-2">Explora la ciudad</p>
                <h2 class="text-2xl font-bold leading-tight mb-6">Categorías Históricas</h2>
                
                <!-- Category Grid -->
                <div id="categorias-grid" class="grid grid-cols-2 gap-4">
                    <!-- Loading placeholder -->
                    <div class="col-span-2 text-center py-12">
                        <div class="spinner mx-auto"></div>
                        <p class="mt-4 text-gray-500">Cargando categorías...</p>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Bottom Navigation Bar -->
        <nav class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around border-t border-black/10 dark:border-white/10 bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-xl px-8 h-20 pb-safe">
            <a class="flex flex-col items-center gap-1 text-primary" href="categorias.php">
                <span class="material-symbols-outlined text-2xl icon-filled">explore</span>
                <span class="text-[10px] font-bold uppercase tracking-tight">Explorar</span>
            </a>
            <a class="flex flex-col items-center gap-1 text-gray-400 dark:text-gray-500 hover:text-primary transition-colors" href="favoritos.php">
                <div class="relative">
                    <span class="material-symbols-outlined text-2xl">favorite</span>
                    <span id="favoritos-badge" class="hidden absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"></span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-tight">Favoritos</span>
            </a>
        </nav>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/favoritos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            cargarCategorias();
            renderFavoritosBadge('favoritos-badge');
        });

        async function cargarCategorias() {
            try {
                const data = await SevillaSecreta.fetchAPI('categorias.php', { ciudad_slug: 'sevilla' });
                renderCategorias(data.categorias);
            } catch (error) {
                mostrarError('Error al cargar categorías');
            }
        }

        function renderCategorias(categorias) {
            const grid = document.getElementById('categorias-grid');
            
            if (categorias.length === 0) {
                grid.innerHTML = '<p class="col-span-2 text-center text-gray-500 py-8">No hay categorías disponibles</p>';
                return;
            }
            
            let html = '';
            
            categorias.forEach((cat) => {
                const formato = cat.formato || '1x1';
                const colSpan = formato === '1x2' ? 'col-span-2' : '';
                const aspectRatio = formato === '1x2' ? 'aspect-[16/9]' : 'aspect-[4/5]';
                const imagenUrl = cat.imagen_portada || 'assets/img/placeholder-categoria.svg';
                
                html += `
                    <div class="relative group cursor-pointer overflow-hidden rounded-xl ${aspectRatio} ${colSpan} bg-cover bg-center flex flex-col justify-end p-4 shadow-lg transition-transform active:scale-95"
                         style="background-image: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(0,0,0,0.8) 100%), url('${imagenUrl}');"
                         onclick="window.location.href='mapa.php?categoria=${cat.slug}'">
                        <div class="absolute top-4 left-4 backdrop-blur-md rounded-lg p-2" style="background-color: ${cat.color};">
                            <span class="material-symbols-outlined text-white icon-filled">${cat.icono}</span>
                        </div>
                        <p class="text-white text-lg font-bold leading-tight">${cat.nombre}</p>
                        ${cat.total_pois > 0 ? `<p class="text-white/70 text-xs mt-1">${cat.total_pois} ${cat.total_pois === 1 ? 'lugar' : 'lugares'}</p>` : ''}
                    </div>
                `;
            });
            
            grid.innerHTML = html;
        }

        function mostrarError(mensaje) {
            const grid = document.getElementById('categorias-grid');
            grid.innerHTML = `
                <div class="col-span-2 text-center py-8">
                    <span class="material-symbols-outlined text-4xl text-red-500">error</span>
                    <p class="text-red-500 mt-2">${mensaje}</p>
                    <button onclick="cargarCategorias()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg">Reintentar</button>
                </div>
            `;
        }
    </script>
</body>
</html>