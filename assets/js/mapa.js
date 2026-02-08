/**
 * Sevilla Secreta - Gestión de Mapas
 * Utilidades para trabajar con Leaflet.js
 */

const MapaManager = {
    map: null,
    markers: [],
    userMarker: null,
    userLocation: null,
    
    /**
     * Inicializar mapa
     */
    init(elementId, options = {}) {
        const defaultOptions = {
            center: [37.3890924, -5.9844589], // Sevilla
            zoom: 13,
            zoomControl: false, // Deshabilitar botones de zoom
            tileLayer: 'carto-light' // 'carto-light', 'carto-dark', 'osm'
        };
        
        const config = { ...defaultOptions, ...options };
        
        // Crear mapa
        this.map = L.map(elementId, {
            zoomControl: config.zoomControl
        }).setView(config.center, config.zoom);
        
        // Agregar tiles según configuración
        this.addTileLayer(config.tileLayer);
        
        return this.map;
    },
    
    /**
     * Agregar capa de tiles
     */
    addTileLayer(type = 'carto-light') {
        let tileUrl, attribution;
        
        switch (type) {
            case 'carto-light':
                tileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
                break;
            case 'carto-dark':
                tileUrl = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
                break;
            case 'osm':
            default:
                tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
                break;
        }
        
        L.tileLayer(tileUrl, {
            attribution: attribution,
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(this.map);
    },
    
    /**
     * Crear icono personalizado para marcador
     */
    createCustomIcon(iconName, color = '#f2930d') {
        return L.divIcon({
            className: 'custom-marker',
            html: `
                <div class="flex flex-col items-center">
                    <div class="p-2 rounded-full shadow-lg border-2 border-white" style="background-color: ${color}">
                        <span class="material-symbols-outlined text-lg text-white">${iconName}</span>
                    </div>
                    <div class="w-1 h-3 -mt-1 rounded-full shadow-md" style="background-color: ${color}"></div>
                </div>
            `,
            iconSize: [40, 50],
            iconAnchor: [20, 50],
            popupAnchor: [0, -50]
        });
    },
    
    /**
     * Agregar marcador
     */
    addMarker(lat, lon, options = {}) {
        const icon = options.icon || this.createCustomIcon(options.iconName || 'location_on', options.color);
        
        const marker = L.marker([lat, lon], { icon }).addTo(this.map);
        
        if (options.popup) {
            marker.bindPopup(options.popup);
        }
        
        if (options.onClick) {
            marker.on('click', options.onClick);
        }
        
        this.markers.push(marker);
        
        return marker;
    },
    
    /**
     * Agregar múltiples marcadores desde array de POIs
     */
    addPOIsMarkers(pois, categoria, onClickCallback) {
        this.clearMarkers();
        
        pois.forEach(poi => {
            this.addMarker(poi.latitud, poi.longitud, {
                iconName: categoria.icono,
                color: categoria.color,
                onClick: () => onClickCallback(poi)
            });
        });
        
        // Ajustar vista para mostrar todos los marcadores
        if (pois.length > 0) {
            this.fitBounds(pois.map(p => [p.latitud, p.longitud]));
        }
    },
    
    /**
     * Limpiar todos los marcadores
     */
    clearMarkers() {
        this.markers.forEach(marker => this.map.removeLayer(marker));
        this.markers = [];
    },
    
    /**
     * Mostrar ubicación del usuario
     */
    async showUserLocation() {
        try {
            const location = await this.getUserLocation();
            
            if (this.userMarker) {
                this.map.removeLayer(this.userMarker);
            }
            
            this.userMarker = L.circleMarker([location.lat, location.lon], {
                radius: 8,
                fillColor: '#3b82f6',
                color: '#fff',
                weight: 2,
                fillOpacity: 0.8
            }).addTo(this.map);
            
            this.userLocation = location;
            
            return location;
        } catch (error) {
            console.error('Error al obtener ubicación:', error);
            throw error;
        }
    },
    
    /**
     * Obtener ubicación del usuario
     */
    getUserLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocalización no disponible'));
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lon: position.coords.longitude
                    });
                },
                (error) => reject(error),
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        });
    },
    
    /**
     * Centrar mapa en ubicación del usuario
     */
    async centerOnUser(zoom = 15) {
        try {
            const location = await this.showUserLocation();
            this.map.setView([location.lat, location.lon], zoom);
            return location;
        } catch (error) {
            console.error('No se pudo centrar en usuario:', error);
            throw error;
        }
    },
    
    /**
     * Ajustar vista del mapa para mostrar todos los puntos
     */
    fitBounds(points, options = {}) {
        if (points.length === 0) return;
        
        const bounds = L.latLngBounds(points);
        this.map.fitBounds(bounds, {
            padding: [50, 50],
            ...options
        });
    },
    
    /**
     * Agregar control de geolocalización
     */
    addGeolocationControl(options = {}) {
        const control = L.control({ position: options.position || 'topright' });
        
        control.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = `
                <a href="#" class="leaflet-control-geolocation" title="Mi ubicación">
                    <span class="material-symbols-outlined">my_location</span>
                </a>
            `;
            
            div.onclick = (e) => {
                e.preventDefault();
                this.centerOnUser();
            };
            
            return div;
        };
        
        control.addTo(this.map);
        return control;
    },
    
    /**
     * Calcular distancia entre dos puntos (Haversine)
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Radio de la Tierra en metros
        const dLat = this.toRad(lat2 - lat1);
        const dLon = this.toRad(lon2 - lon1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Distancia en metros
    },
    
    /**
     * Convertir grados a radianes
     */
    toRad(degrees) {
        return degrees * (Math.PI / 180);
    },
    
    /**
     * Destruir mapa
     */
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
            this.markers = [];
            this.userMarker = null;
        }
    }
};

// Exportar para uso global
window.MapaManager = MapaManager;