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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Mapa - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="assets/img/icon-512.png">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
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
    <style>
        #map { height: 100%; width: 100%; }
        
        /* Marcador seleccionado - fondo blanco + borde naranja */
        .leaflet-marker-icon.custom-marker.marker-selected .rounded-full {
            background-color: #fff !important;
            transform: scale(1.2);
            border-color: #f2930d !important;
        }
        
        /* Marcador seleccionado - icono naranja */
        .leaflet-marker-icon.custom-marker.marker-selected .material-symbols-outlined {
            color: #f2930d !important;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#1c160d] dark:text-white overflow-hidden">
    <div class="relative flex h-screen w-full flex-col overflow-hidden">
        <div class="fixed top-0 left-0 right-0 z-[1000] glass-effect border-b border-black/5 dark:border-white/10 px-4 py-4">
            <div class="flex items-center justify-between max-w-md mx-auto">
                <button onclick="window.location.href='categorias.php'" class="flex size-10 items-center justify-center rounded-full bg-white dark:bg-zinc-800 shadow-sm">
                    <span class="material-symbols-outlined">arrow_back</span>
                </button>
                <div class="flex flex-col items-center">
                    <h1 class="text-sm font-bold uppercase tracking-widest text-primary">Mapa de Secretos</h1>
                    <h2 id="categoria-nombre" class="text-lg font-extrabold leading-tight">Cargando...</h2>
                </div>
                <div class="flex size-10"></div>
            </div>
        </div>
        
        <div class="relative flex-1 w-full" style="margin-top: 80px;">
            <div id="map"></div>
        </div>
        
        <div class="absolute right-4 top-28 flex flex-col gap-3 z-[900]">
            <button onclick="mapaApp.map.zoomIn()" class="flex size-12 items-center justify-center rounded-xl bg-white dark:bg-zinc-900 shadow-xl">
                <span class="material-symbols-outlined">add</span>
            </button>
            <button onclick="mapaApp.map.zoomOut()" class="flex size-12 items-center justify-center rounded-xl bg-white dark:bg-zinc-900 shadow-xl">
                <span class="material-symbols-outlined">remove</span>
            </button>
            <button onclick="mapaApp.centrarEnUsuario()" class="flex size-12 items-center justify-center rounded-xl bg-white dark:bg-zinc-900 shadow-xl text-primary">
                <span class="material-symbols-outlined">my_location</span>
            </button>
        </div>
        
        <div class="absolute left-4 top-28 z-[900]">
            <button onclick="window.location.href='lista.php?categoria=<?php echo htmlspecialchars($categoriaSlug); ?>'" class="flex items-center gap-2 px-4 py-3 rounded-full bg-primary text-white font-bold shadow-xl">
                <span class="material-symbols-outlined">format_list_bulleted</span>
                <span class="text-sm">Vista Lista</span>
            </button>
        </div>
        
        <div id="poi-card" class="fixed bottom-24 left-0 right-0 z-[900] px-4 hidden"></div>
        
        <nav class="fixed bottom-0 left-0 right-0 z-[1000] glass-effect border-t border-black/5 dark:border-white/10 px-8 h-20 pb-safe">
            <div class="flex items-center justify-around h-full max-w-md mx-auto">
                <a href="categorias.php" class="flex flex-col items-center gap-1 text-zinc-400">
                    <span class="material-symbols-outlined text-2xl">explore</span>
                    <span class="text-[10px] font-bold uppercase tracking-tight">Explorar</span>
                </a>
                <a href="favoritos.php" class="flex flex-col items-center gap-1 text-zinc-400">
                    <div class="relative">
                        <span class="material-symbols-outlined text-2xl">favorite</span>
                        <span id="favoritos-badge" class="hidden absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"></span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-tight">Favoritos</span>
                </a>
            </div>
        </nav>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/favoritos.js"></script>
    <script src="assets/js/mapa.js"></script>
    <script>
        const mapaApp = {
            map: null,
            markers: [],
            selectedMarker: null,
            categoriaSlug: '<?php echo htmlspecialchars($categoriaSlug); ?>',
            categoria: null,
            pois: [],
            
            async init() {
                this.map = MapaManager.init('map');
                await this.cargarPOIs();
                renderFavoritosBadge('favoritos-badge');
            },
            
            async cargarPOIs() {
                try {
                    let params = { categoria_slug: this.categoriaSlug, ciudad_slug: 'sevilla' };
                    try {
                        const loc = await SevillaSecreta.getUserLocation();
                        params.lat = loc.lat;
                        params.lon = loc.lon;
                    } catch (e) {}
                    
                    const data = await SevillaSecreta.fetchAPI('pois.php', params);
                    this.categoria = data.categoria;
                    this.pois = data.pois;
                    document.getElementById('categoria-nombre').textContent = data.categoria.nombre;
                    this.renderMarkers();
                } catch (error) {
                    SevillaSecreta.showToast('Error al cargar POIs', 'error');
                }
            },
            
            renderMarkers() {
                MapaManager.clearMarkers();
                this.pois.forEach(poi => {
                    const marker = MapaManager.addMarker(poi.latitud, poi.longitud, {
                        iconName: this.categoria.icono,
                        color: this.categoria.color,
                        onClick: () => this.mostrarTarjeta(poi, marker)
                    });
                    marker._poiId = poi.id;
                    this.markers.push(marker);
                });
                if (this.pois.length > 0) {
                    MapaManager.fitBounds(this.pois.map(p => [p.latitud, p.longitud]));
                }
            },
            
            mostrarTarjeta(poi, marker) {
                this.cerrarTarjeta();
                this.selectedMarker = marker;
                const markerElement = marker.getElement();
                if (markerElement) {
                    markerElement.classList.add('marker-selected');
                }
                
                const card = document.getElementById('poi-card');
                const fotoUrl = poi.foto_principal || 'assets/img/placeholder-poi.svg';
                const isFav = FavoritosManager.isFavorite(poi.id);
                
                card.innerHTML = `
                    <div class="max-w-md mx-auto bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl p-4 border border-black/5 flex gap-4 relative">
                        <button onclick="mapaApp.cerrarTarjeta()" class="absolute -top-2 -right-2 bg-white dark:bg-zinc-800 rounded-full p-2 shadow-lg z-10 hover:bg-gray-100 dark:hover:bg-zinc-700 transition">
                            <span class="material-symbols-outlined text-zinc-500 dark:text-zinc-300 !text-lg">close</span>
                        </button>
                        <div class="size-24 shrink-0 overflow-hidden rounded-xl bg-zinc-100">
                            <img src="${fotoUrl}" class="w-full h-full object-cover" alt="${poi.titulo}"/>
                        </div>
                        <div class="flex flex-col justify-between flex-1 py-1 pr-6">
                            <div>
                                ${poi.distancia_formateada ? `
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold uppercase">Cercano</span>
                                        <span class="text-zinc-500 text-xs font-medium">A ${poi.distancia_formateada}</span>
                                    </div>
                                ` : ''}
                                <h3 class="text-lg font-extrabold">${poi.titulo}</h3>
                                <p class="text-sm text-zinc-500 line-clamp-1">${poi.descripcion.substring(0, 80)}...</p>
                            </div>
                            <div class="flex items-center gap-3 mt-2">
                                <button onclick="window.location.href='detalle.php?slug=${poi.slug}'" class="flex-1 bg-primary text-white h-10 rounded-lg text-sm font-bold flex items-center justify-center gap-2 px-4">
                                    <span class="material-symbols-outlined !text-lg">near_me</span>
                                    Ver detalle
                                </button>
                                <button id="fav-btn-${poi.id}" onclick="mapaApp.toggleFavorito(${poi.id})" class="size-10 bg-background-light dark:bg-zinc-800 rounded-lg flex items-center justify-center border">
                                    <span class="material-symbols-outlined !text-lg ${isFav ? 'icon-filled text-primary' : 'text-gray-400'}">favorite</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                card.classList.remove('hidden');
            },
            
            cerrarTarjeta() {
                document.getElementById('poi-card').classList.add('hidden');
                if (this.selectedMarker) {
                    const markerElement = this.selectedMarker.getElement();
                    if (markerElement) {
                        markerElement.classList.remove('marker-selected');
                    }
                    this.selectedMarker = null;
                }
            },
            
            toggleFavorito(poiId) {
                const added = FavoritosManager.toggle(poiId);
                const btn = document.getElementById(`fav-btn-${poiId}`);
                if (btn) {
                    const icon = btn.querySelector('.material-symbols-outlined');
                    if (added) {
                        icon.classList.add('icon-filled', 'text-primary');
                        icon.classList.remove('text-gray-400');
                    } else {
                        icon.classList.remove('icon-filled', 'text-primary');
                        icon.classList.add('text-gray-400');
                    }
                }
            },
            
            async centrarEnUsuario() {
                try {
                    await MapaManager.centerOnUser();
                } catch (error) {
                    SevillaSecreta.showToast('No se pudo obtener tu ubicación', 'error');
                }
            }
        };
        
        document.addEventListener('DOMContentLoaded', () => mapaApp.init());
    </script>
</body>
</html>