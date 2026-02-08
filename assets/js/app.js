/**
 * Sevilla Secreta - JavaScript Principal
 * Funciones globales y utilidades comunes
 */

// Configuración global
const APP_CONFIG = {
    ciudadSlug: 'sevilla',
    apiBase: '/sevilla-secreta/api',
    uploadBase: '/sevilla-secreta/uploads'
};

/**
 * Realizar petición fetch con manejo de errores
 */
async function fetchAPI(endpoint, params = {}) {
    try {
        const queryString = new URLSearchParams(params).toString();
        const url = `${APP_CONFIG.apiBase}/${endpoint}${queryString ? '?' + queryString : ''}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Error desconocido');
        }
        
        return data;
    } catch (error) {
        console.error('Error en fetchAPI:', error);
        throw error;
    }
}

/**
 * Mostrar mensaje toast (notificación temporal)
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-[9999] px-6 py-3 rounded-lg shadow-lg text-white font-medium transition-all transform translate-x-0 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 
        'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animar entrada
    setTimeout(() => toast.classList.add('opacity-100'), 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Copiar texto al portapapeles
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copiado al portapapeles', 'success');
        return true;
    } catch (error) {
        console.error('Error al copiar:', error);
        showToast('No se pudo copiar', 'error');
        return false;
    }
}

/**
 * Obtener ubicación del usuario
 */
function getUserLocation() {
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
            (error) => {
                reject(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    });
}

/**
 * Formatear distancia para mostrar
 */
function formatDistance(meters) {
    if (meters < 1000) {
        return Math.round(meters) + 'm';
    } else {
        return (meters / 1000).toFixed(1) + 'km';
    }
}

/**
 * Detectar si estamos en modo oscuro
 */
function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}

/**
 * Toggle modo oscuro
 */
function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    const isDark = isDarkMode();
    localStorage.setItem('darkMode', isDark ? 'true' : 'false');
    return isDark;
}

/**
 * Inicializar modo oscuro desde localStorage
 */
function initDarkMode() {
    const savedMode = localStorage.getItem('darkMode');
    if (savedMode === 'true') {
        document.documentElement.classList.add('dark');
    } else if (savedMode === 'false') {
        document.documentElement.classList.remove('dark');
    } else {
        // Si no hay preferencia guardada, usar la del sistema
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    }
}

/**
 * Truncar texto con puntos suspensivos
 */
function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength).trim() + '...';
}

/**
 * Debounce para búsquedas
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Validar si una URL es válida
 */
function isValidURL(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

/**
 * Scroll suave a un elemento
 */
function smoothScrollTo(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Detectar si estamos en dispositivo móvil
 */
function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * Obtener parámetro de URL
 */
function getURLParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Actualizar parámetro de URL sin recargar
 */
function updateURLParameter(name, value) {
    const url = new URL(window.location);
    url.searchParams.set(name, value);
    window.history.pushState({}, '', url);
}

// Inicializar modo oscuro al cargar
document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
});

// Exportar para uso global
window.SevillaSecreta = {
    fetchAPI,
    showToast,
    copyToClipboard,
    getUserLocation,
    formatDistance,
    isDarkMode,
    toggleDarkMode,
    truncateText,
    debounce,
    isValidURL,
    smoothScrollTo,
    isMobile,
    getURLParameter,
    updateURLParameter
};