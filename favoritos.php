<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Mis Favoritos - <?php echo APP_NAME; ?></title>
    
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
                    colors: { "primary": "#f2930d", "background-light": "#f8f7f5", "background-dark": "#221b10" },
                    fontFamily: { "display": ["Plus Jakarta Sans", "sans-serif"], "serif": ["Playfair Display", "serif"] },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#1c160d] dark:text-[#fcfaf8] antialiased">
    <div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        <header class="sticky top-0 z-50 flex items-center glass-effect p-4 pb-2 justify-between border-b border-black/5 dark:border-white/5">
            <div onclick="window.location.href='categorias.php'" class="flex size-10 shrink-0 items-center justify-center rounded-full hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer">
                <span class="material-symbols-outlined text-2xl">arrow_back_ios_new</span>
            </div>
            <h1 class="font-serif text-xl font-bold leading-tight tracking-tight text-center flex-1">Mis Favoritos</h1>
            <div class="flex size-10"></div>
        </header>
        
        <main class="flex-1 pb-24">
            <div id="favoritos-list" class="p-4 space-y-4">
                <div class="text-center py-12">
                    <div class="spinner mx-auto"></div>
                    <p class="mt-4 text-gray-500">Cargando favoritos...</p>
                </div>
            </div>
            
            <div id="empty-state" class="hidden flex-col items-center justify-center p-12 text-center mt-12">
                <div class="w-32 h-32 mb-6 opacity-20 dark:opacity-40">
                    <span class="material-symbols-outlined text-8xl text-primary">heart_broken</span>
                </div>
                <p class="text-lg font-medium text-gray-600 dark:text-gray-300">Aún no tienes secretos guardados</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Explora el mapa para descubrir los tesoros de Sevilla.</p>
                <button onclick="window.location.href='categorias.php'" class="mt-8 px-6 py-2.5 bg-primary text-white font-bold rounded-full text-sm shadow-md">Descubrir Ahora</button>
            </div>
        </main>
        
        <nav class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around border-t border-black/10 dark:border-white/10 glass-effect px-8 h-20 pb-safe">
            <a class="flex flex-col items-center gap-1 text-gray-400 dark:text-gray-500 hover:text-primary transition-colors" href="categorias.php">
                <span class="material-symbols-outlined text-2xl">explore</span>
                <span class="text-[10px] font-bold uppercase tracking-tight">Explorar</span>
            </a>
            <a class="flex flex-col items-center gap-1 text-primary" href="favoritos.php">
                <span class="material-symbols-outlined text-2xl icon-filled">favorite</span>
                <span class="text-[10px] font-bold uppercase tracking-tight">Favoritos</span>
            </a>
        </nav>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/favoritos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => cargarFavoritos());

        async function cargarFavoritos() {
            const favoritosIds = FavoritosManager.getAll();
            if (favoritosIds.length === 0) {
                mostrarEstadoVacio();
                return;
            }
            
            try {
                const pois = await loadFavoritosPOIs();
                if (pois.length === 0) {
                    mostrarEstadoVacio();
                } else {
                    renderFavoritos(pois);
                }
            } catch (error) {
                mostrarError();
            }
        }

        function renderFavoritos(pois) {
            const container = document.getElementById('favoritos-list');
            let html = '';
            pois.forEach(poi => {
                const fotoUrl = poi.fotos && poi.fotos.length > 0 ? poi.fotos[0].url : 'assets/img/placeholder-poi.svg';
                html += `
                    <div class="bg-white dark:bg-white/5 rounded-2xl overflow-hidden shadow-sm border border-black/5 dark:border-white/5 flex p-3 gap-4 active:scale-[0.98] transition-transform cursor-pointer" onclick="window.location.href='detalle.php?slug=${poi.slug}'">
                        <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl">
                            <img alt="${poi.titulo}" class="h-full w-full object-cover" src="${fotoUrl}"/>
                        </div>
                        <div class="flex flex-col justify-between flex-1 py-1">
                            <div>
                                <h3 class="font-bold text-base leading-snug line-clamp-1">${poi.titulo}</h3>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="material-symbols-outlined text-[14px] icon-gold">${poi.categoria.icono}</span>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">${poi.categoria.nombre}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1 text-gray-400">
                                    <span class="material-symbols-outlined text-[14px]">location_on</span>
                                    <span class="text-xs font-medium">${poi.direccion ? poi.direccion.split(',')[0] : 'Sevilla'}</span>
                                </div>
                                <button onclick="eliminarFavorito(event, ${poi.id})" class="material-symbols-outlined text-red-500 text-2xl cursor-pointer hover:scale-110 transition">heart_broken</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        function eliminarFavorito(event, poiId) {
            event.stopPropagation();
            FavoritosManager.remove(poiId);
            cargarFavoritos();
        }

        function mostrarEstadoVacio() {
            document.getElementById('favoritos-list').innerHTML = '';
            document.getElementById('empty-state').classList.remove('hidden');
            document.getElementById('empty-state').classList.add('flex');
        }

        function mostrarError() {
            document.getElementById('favoritos-list').innerHTML = `
                <div class="text-center py-8">
                    <span class="material-symbols-outlined text-4xl text-red-500">error</span>
                    <p class="text-red-500 mt-2">Error al cargar favoritos</p>
                    <button onclick="cargarFavoritos()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg">Reintentar</button>
                </div>
            `;
        }
    </script>
</body>
</html>