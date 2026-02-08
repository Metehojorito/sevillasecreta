<?php
require_once 'includes/config.php';
$poiSlug = $_GET['slug'] ?? null;
if (!$poiSlug) {
    header('Location: categorias.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Detalle - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="assets/img/icon-512.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
        #mini-map { height: 160px; width: 100%; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#1c160d] dark:text-white antialiased">
    <div class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between p-4 glass-effect border-b border-gray-200 dark:border-white/10">
        <button onclick="window.history.back()" class="flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-[#32291a] shadow-sm">
            <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <h2 class="text-base font-bold leading-tight">Detalle del Secreto</h2>
        <button id="favorite-btn" onclick="toggleFavorito()" class="flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-[#32291a] shadow-sm">
            <span class="material-symbols-outlined text-gray-400">favorite</span>
        </button>
    </div>
    
    <main class="pt-16 pb-40">
        <div class="relative w-full overflow-hidden">
            <div id="carousel" class="flex overflow-x-auto snap-x snap-mandatory hide-scrollbar gap-4 px-4 py-4"></div>
            <div id="carousel-dots" class="flex justify-center gap-1.5 mt-2"></div>
        </div>
        
        <div class="px-5 pt-6 pb-2">
            <div id="categoria-badge" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 dark:bg-primary/20 text-primary mb-3"></div>
            <h1 id="poi-titulo" class="text-3xl font-extrabold leading-tight tracking-tight">Cargando...</h1>
        </div>
        
        <div class="px-5 py-4">
            <div id="ubicacion-card" class="flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-[#32291a] border border-gray-100 dark:border-white/5 shadow-sm"></div>
        </div>
        
        <div id="info-adicional" class="px-5 py-2 hidden"></div>
        
        <div class="px-5 pt-4 flex items-center justify-between">
            <h3 class="text-lg font-bold">La historia detrás</h3>
            <button id="audio-btn" onclick="toggleAudio()" class="flex items-center gap-2 px-4 py-2 rounded-full bg-primary text-white shadow-md active:scale-95 transition-all">
                <span class="material-symbols-outlined text-xl">volume_up</span>
                <span class="text-sm font-bold">Escuchar</span>
                <div id="audio-waves" class="flex items-end gap-0.5 h-3 ml-1">
                    <div class="audio-wave-bar wave-1 paused"></div>
                    <div class="audio-wave-bar wave-2 paused"></div>
                    <div class="audio-wave-bar wave-3 paused"></div>
                    <div class="audio-wave-bar wave-4 paused"></div>
                </div>
            </button>
        </div>
        
        <div id="descripcion" class="px-5 py-2 space-y-4"></div>
        
        <div class="px-5 py-6">
            <div id="mini-map" class="w-full rounded-xl overflow-hidden"></div>
        </div>
    </main>
    
    <div class="fixed bottom-0 left-0 right-0 z-[1000] p-5 glass-effect border-t border-gray-200 dark:border-white/10 flex gap-4">
        <button id="directions-btn" onclick="abrirNavegacion()" class="flex-1 flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-95">
            <span class="material-symbols-outlined">directions</span>
            Cómo llegar
        </button>
        <button onclick="compartir()" class="flex items-center justify-center w-16 h-14 bg-transparent border-2 border-primary/30 dark:border-primary/50 text-primary rounded-xl transition-all active:scale-95">
            <span class="material-symbols-outlined">share</span>
        </button>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/favoritos.js"></script>
    <script src="assets/js/voz.js?v=1.2"></script>
    <script>
        const poiSlug = '<?php echo htmlspecialchars($poiSlug); ?>';
        let poiData = null;
        let miniMap = null;
        
        document.addEventListener('DOMContentLoaded', () => cargarDetalle());

        async function cargarDetalle() {
            try {
                const data = await SevillaSecreta.fetchAPI('poi-detalle.php', { slug: poiSlug, ciudad_slug: 'sevilla' });
                poiData = data.poi;
                renderDetalle(data.poi);
            } catch (error) {
                alert('Error al cargar el detalle');
                window.history.back();
            }
        }

        function renderDetalle(poi) {
            document.getElementById('poi-titulo').textContent = poi.titulo;
            document.getElementById('categoria-badge').innerHTML = `
                <span class="material-symbols-outlined text-sm">${poi.categoria.icono}</span>
                <span class="text-xs font-bold uppercase tracking-wider">${poi.categoria.nombre}</span>
            `;
            
            const carousel = document.getElementById('carousel');
            const dots = document.getElementById('carousel-dots');
            if (poi.fotos && poi.fotos.length > 0) {
                carousel.innerHTML = poi.fotos.map(foto => `
                    <div class="snap-center shrink-0 w-[85vw] aspect-[4/5] rounded-xl overflow-hidden shadow-lg">
                        <div class="w-full h-full bg-center bg-no-repeat bg-cover" style="background-image: url('${foto.url}');"></div>
                    </div>
                `).join('');
                dots.innerHTML = poi.fotos.map((_, i) => `<div class="dot w-2 h-2 rounded-full ${i === 0 ? 'bg-primary' : 'bg-gray-300 dark:bg-gray-600'}"></div>`).join('');
                
                // Actualizar dots cuando se hace scroll en el carrusel
                carousel.addEventListener('scroll', () => {
                    const scrollLeft = carousel.scrollLeft;
                    const itemWidth = carousel.children[0].offsetWidth;
                    const gap = 12; // gap-3 = 12px
                    const activeIndex = Math.round(scrollLeft / (itemWidth + gap));
                    
                    // Actualizar clases de los dots
                    const dotElements = dots.querySelectorAll('.dot');
                    dotElements.forEach((dot, i) => {
                        if (i === activeIndex) {
                            dot.classList.remove('bg-gray-300', 'dark:bg-gray-600');
                            dot.classList.add('bg-primary');
                        } else {
                            dot.classList.remove('bg-primary');
                            dot.classList.add('bg-gray-300', 'dark:bg-gray-600');
                        }
                    });
                });
            } else {
                carousel.innerHTML = `<div class="snap-center shrink-0 w-[85vw] aspect-[4/5] rounded-xl overflow-hidden shadow-lg"><div class="w-full h-full bg-center bg-no-repeat bg-cover" style="background-image: url('assets/img/placeholder-poi.svg');"></div></div>`;
            }
            
            document.getElementById('ubicacion-card').innerHTML = `
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary">
                    <span class="material-symbols-outlined">location_on</span>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-tight">Ubicación</p>
                    <p class="text-sm font-semibold">${poi.direccion || 'Dirección no disponible'}</p>
                </div>
                <button onclick="SevillaSecreta.copyToClipboard('${poi.direccion}')" class="material-symbols-outlined text-gray-400">content_copy</button>
            `;
            
            if (poi.horario || poi.precio) {
                const infoAdicional = document.getElementById('info-adicional');
                infoAdicional.classList.remove('hidden');
                infoAdicional.innerHTML = `<div class="grid grid-cols-2 gap-3">
                    ${poi.horario ? `<div class="bg-white dark:bg-[#32291a] rounded-lg p-3 border border-gray-100 dark:border-white/5"><p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase mb-1">Horario</p><p class="text-sm font-semibold">${poi.horario}</p></div>` : ''}
                    ${poi.precio ? `<div class="bg-white dark:bg-[#32291a] rounded-lg p-3 border border-gray-100 dark:border-white/5"><p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase mb-1">Precio</p><p class="text-sm font-semibold">${poi.precio}</p></div>` : ''}
                </div>`;
            }
            
            const parrafos = poi.descripcion.split('\n').filter(p => p.trim());
            document.getElementById('descripcion').innerHTML = parrafos.map(p => `<p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">${p}</p>`).join('');
            
            AudioguiaManager.init(poi.descripcion);
            actualizarBotonFavorito();
            initMiniMapa(poi.latitud, poi.longitud);
        }

        function initMiniMapa(lat, lon) {
            miniMap = L.map('mini-map', {
                center: [lat, lon],
                zoom: 15,
                zoomControl: false,
                dragging: false,
                touchZoom: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false
            });
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(miniMap);
            L.marker([lat, lon]).addTo(miniMap);
        }

        function toggleAudio() {
            const state = AudioguiaManager.toggle();
            updateAudioButton(document.getElementById('audio-btn'), state === 'playing');
        }
        
        // Listener para actualizar botón cuando el audio termina
        window.addEventListener('audioguiaChanged', (event) => {
            const button = document.getElementById('audio-btn');
            if (button) {
                updateAudioButton(button, event.detail.isPlaying);
            }
        });

        function abrirNavegacion() {
            if (poiData) {
                window.open(`https://www.google.com/maps/dir/?api=1&destination=${poiData.latitud},${poiData.longitud}`, '_blank');
            }
        }

        async function compartir() {
            if (!poiData) return;
            const shareData = {
                title: poiData.titulo,
                text: SevillaSecreta.truncateText(poiData.descripcion, 100),
                url: window.location.href
            };
            
            // Verificar si Web Share API está disponible y puede compartir
            if (navigator.share) {
                try {
                    await navigator.share(shareData);
                    SevillaSecreta.showToast('Compartido correctamente', 'success');
                } catch (err) {
                    // Usuario canceló o error
                    if (err.name !== 'AbortError') {
                        // Si falló por otra razón, copiar al portapapeles
                        await copiarEnlace();
                    }
                }
            } else {
                // Web Share API no disponible (desktop o HTTP)
                await copiarEnlace();
            }
        }
        
        async function copiarEnlace() {
            try {
                await navigator.clipboard.writeText(window.location.href);
                SevillaSecreta.showToast('Enlace copiado al portapapeles', 'success');
            } catch (err) {
                // Fallback si clipboard tampoco funciona
                const textarea = document.createElement('textarea');
                textarea.value = window.location.href;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    SevillaSecreta.showToast('Enlace copiado', 'success');
                } catch (e) {
                    SevillaSecreta.showToast('No se pudo copiar. Intenta desde HTTPS', 'error');
                }
                document.body.removeChild(textarea);
            }
        }

        function toggleFavorito() {
            if (!poiData) return;
            FavoritosManager.toggle(poiData.id);
            actualizarBotonFavorito();
        }

        function actualizarBotonFavorito() {
            if (!poiData) return;
            const btn = document.getElementById('favorite-btn');
            updateFavoritoButton(btn, poiData.id);
        }
    </script>
</body>
</html>