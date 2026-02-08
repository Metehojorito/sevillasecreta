/**
 * Sevilla Secreta - Gestión de Favoritos
 * Manejo de favoritos con localStorage
 */

const FavoritosManager = {
    STORAGE_KEY: 'sevilla_secreta_favoritos',
    
    /**
     * Obtener todos los IDs de favoritos
     */
    getAll() {
        try {
            const data = localStorage.getItem(this.STORAGE_KEY);
            return data ? JSON.parse(data) : [];
        } catch (error) {
            console.error('Error al leer favoritos:', error);
            return [];
        }
    },
    
    /**
     * Agregar un POI a favoritos
     */
    add(poiId) {
        const favoritos = this.getAll();
        
        if (!favoritos.includes(poiId)) {
            favoritos.push(poiId);
            this.save(favoritos);
            this.notifyChange('add', poiId);
            return true;
        }
        
        return false;
    },
    
    /**
     * Eliminar un POI de favoritos
     */
    remove(poiId) {
        let favoritos = this.getAll();
        const initialLength = favoritos.length;
        
        favoritos = favoritos.filter(id => id !== poiId);
        
        if (favoritos.length < initialLength) {
            this.save(favoritos);
            this.notifyChange('remove', poiId);
            return true;
        }
        
        return false;
    },
    
    /**
     * Toggle favorito (agregar si no existe, eliminar si existe)
     */
    toggle(poiId) {
        if (this.isFavorite(poiId)) {
            this.remove(poiId);
            return false;
        } else {
            this.add(poiId);
            return true;
        }
    },
    
    /**
     * Verificar si un POI está en favoritos
     */
    isFavorite(poiId) {
        return this.getAll().includes(poiId);
    },
    
    /**
     * Obtener cantidad de favoritos
     */
    count() {
        return this.getAll().length;
    },
    
    /**
     * Limpiar todos los favoritos
     */
    clear() {
        localStorage.removeItem(this.STORAGE_KEY);
        this.notifyChange('clear', null);
    },
    
    /**
     * Guardar favoritos en localStorage
     */
    save(favoritos) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(favoritos));
        } catch (error) {
            console.error('Error al guardar favoritos:', error);
        }
    },
    
    /**
     * Notificar cambios (para actualizar UI)
     */
    notifyChange(action, poiId) {
        // Disparar evento personalizado
        const event = new CustomEvent('favoritosChanged', {
            detail: { action, poiId, total: this.count() }
        });
        window.dispatchEvent(event);
    },
    
    /**
     * Exportar favoritos como JSON
     */
    export() {
        return JSON.stringify(this.getAll(), null, 2);
    },
    
    /**
     * Importar favoritos desde JSON
     */
    import(jsonString) {
        try {
            const favoritos = JSON.parse(jsonString);
            if (Array.isArray(favoritos)) {
                this.save(favoritos);
                this.notifyChange('import', null);
                return true;
            }
        } catch (error) {
            console.error('Error al importar favoritos:', error);
        }
        return false;
    }
};

/**
 * Actualizar icono de favorito en un botón
 */
function updateFavoritoButton(button, poiId) {
    const isFav = FavoritosManager.isFavorite(poiId);
    const icon = button.querySelector('.material-symbols-outlined');
    
    if (icon) {
        if (isFav) {
            icon.textContent = 'favorite';
            icon.style.fontVariationSettings = "'FILL' 1";
            icon.classList.remove('text-gray-400');
            icon.classList.add('text-primary');
        } else {
            icon.textContent = 'favorite';
            icon.style.fontVariationSettings = "'FILL' 0";
            icon.classList.add('text-gray-400');
            icon.classList.remove('text-primary');
        }
    }
}

/**
 * Cargar detalles de múltiples POIs desde la API
 */
async function loadFavoritosPOIs() {
    const favoritosIds = FavoritosManager.getAll();
    
    if (favoritosIds.length === 0) {
        return [];
    }
    
    try {
        // Cargar detalles de cada POI
        const promesas = favoritosIds.map(id => 
            fetch(`api/poi-detalle.php?id=${id}`)
                .then(r => r.json())
                .catch(err => ({ success: false, error: err.message }))
        );
        
        const resultados = await Promise.all(promesas);
        
        // Filtrar solo los exitosos
        return resultados
            .filter(r => r.success)
            .map(r => r.poi);
    } catch (error) {
        console.error('Error al cargar favoritos:', error);
        return [];
    }
}

/**
 * Renderizar badge con contador de favoritos
 */
function renderFavoritosBadge(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const count = FavoritosManager.count();
    
    if (count > 0) {
        element.textContent = count;
        element.classList.remove('hidden');
    } else {
        element.classList.add('hidden');
    }
}

// Listener global para cambios en favoritos
window.addEventListener('favoritosChanged', (event) => {
    const { action, poiId, total } = event.detail;
    
    // Actualizar badge si existe
    renderFavoritosBadge('favoritos-badge');
    
    // Mostrar notificación
    if (action === 'add') {
        if (window.SevillaSecreta) {
            window.SevillaSecreta.showToast('Añadido a favoritos', 'success');
        }
    } else if (action === 'remove') {
        if (window.SevillaSecreta) {
            window.SevillaSecreta.showToast('Eliminado de favoritos', 'info');
        }
    }
});

// Exportar para uso global
window.FavoritosManager = FavoritosManager;
window.updateFavoritoButton = updateFavoritoButton;
window.loadFavoritosPOIs = loadFavoritosPOIs;
window.renderFavoritosBadge = renderFavoritosBadge;