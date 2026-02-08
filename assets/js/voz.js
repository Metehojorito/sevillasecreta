/**
 * Sevilla Secreta - Audioguía con Web Speech API
 * Text-to-Speech para descripción de POIs
 */

const AudioguiaManager = {
    synthesis: window.speechSynthesis,
    utterance: null,
    isPlaying: false,
    isPaused: false,
    currentText: null,
    
    /**
     * Inicializar audioguía con un texto
     */
    init(text, options = {}) {
        // Detener cualquier reproducción anterior
        this.stop();
        
        this.currentText = text;
        
        // No creamos utterance aquí, se crea en play()
        // Esto permite recrearlo cada vez en móviles
        
        return this;
    },
    
    /**
     * Reproducir audio
     */
    play() {
        if (!this.currentText) {
            console.warn('Audioguía no inicializada');
            return false;
        }
        
        // Detener cualquier reproducción en curso
        this.synthesis.cancel();
        
        // Recrear utterance (necesario para móviles)
        this.utterance = new SpeechSynthesisUtterance(this.currentText);
        this.utterance.lang = 'es-ES';
        this.utterance.rate = 0.9;
        this.utterance.pitch = 1.0;
        this.utterance.volume = 1.0;
        
        // Reasignar eventos
        this.utterance.onstart = () => {
            this.isPlaying = true;
            this.isPaused = false;
            this.notifyChange('start');
        };
        
        this.utterance.onend = () => {
            this.isPlaying = false;
            this.isPaused = false;
            this.notifyChange('end');
        };
        
        this.utterance.onerror = (event) => {
            // Ignorar error 'interrupted' (es normal al cancelar)
            if (event.error === 'interrupted') {
                return;
            }
            
            console.error('Error en audioguía:', event);
            this.isPlaying = false;
            this.isPaused = false;
            this.notifyChange('error');
        };
        
        // Reproducir
        this.synthesis.speak(this.utterance);
        return true;
    },
    
    /**
     * Pausar audio (detener completamente)
     */
    pause() {
        this.synthesis.cancel();
        this.isPlaying = false;
        this.isPaused = false;
        this.notifyChange('pause');
        return true;
    },
    
    /**
     * Detener audio
     */
    stop() {
        this.synthesis.cancel();
        this.isPlaying = false;
        this.isPaused = false;
        this.notifyChange('stop');
    },
    
    /**
     * Toggle reproducir/detener
     */
    toggle() {
        // Si está reproduciendo, detener
        if (this.isPlaying) {
            this.pause();
            return 'paused';
        }
        
        // Si está detenido, reproducir desde el inicio
        this.play();
        return 'playing';
    },
    
    /**
     * Verificar si está reproduciendo
     */
    getIsPlaying() {
        return this.isPlaying && !this.isPaused;
    },
    
    /**
     * Obtener voces disponibles en español
     */
    getSpanishVoices() {
        const voices = this.synthesis.getVoices();
        return voices.filter(voice => voice.lang.startsWith('es'));
    },
    
    /**
     * Cambiar voz
     */
    setVoice(voiceName) {
        if (!this.utterance) return false;
        
        const voices = this.synthesis.getVoices();
        const voice = voices.find(v => v.name === voiceName);
        
        if (voice) {
            this.utterance.voice = voice;
            return true;
        }
        
        return false;
    },
    
    /**
     * Ajustar velocidad de reproducción
     */
    setRate(rate) {
        if (!this.utterance) return false;
        
        // rate: 0.1 a 10 (0.9 es más lento, 1.1 es más rápido)
        this.utterance.rate = Math.max(0.1, Math.min(10, rate));
        return true;
    },
    
    /**
     * Notificar cambios de estado
     */
    notifyChange(event) {
        const customEvent = new CustomEvent('audioguiaChanged', {
            detail: {
                event,
                isPlaying: this.isPlaying,
                isPaused: this.isPaused
            }
        });
        window.dispatchEvent(customEvent);
    },
    
    /**
     * Verificar si el navegador soporta Web Speech API
     */
    isSupported() {
        return 'speechSynthesis' in window;
    }
};

/**
 * Actualizar UI de botón de audio
 */
function updateAudioButton(button, isPlaying) {
    const icon = button.querySelector('.material-symbols-outlined');
    const text = button.querySelector('span:nth-child(2)');
    const waves = button.querySelectorAll('.audio-wave-bar');
    
    if (isPlaying) {
        if (icon) icon.textContent = 'pause';
        if (text) text.textContent = 'Pausar';
        waves.forEach(w => w.classList.remove('paused'));
    } else {
        if (icon) icon.textContent = 'volume_up';
        if (text) text.textContent = 'Escuchar';
        waves.forEach(w => w.classList.add('paused'));
    }
}

/**
 * Crear botón de audioguía
 */
function createAudioButton(text, containerId) {
    if (!AudioguiaManager.isSupported()) {
        console.warn('Web Speech API no soportada en este navegador');
        return null;
    }
    
    const button = document.createElement('button');
    button.className = 'flex items-center gap-2 px-4 py-2 rounded-full bg-primary text-white shadow-md active:scale-95 transition-all';
    button.innerHTML = `
        <span class="material-symbols-outlined text-xl">volume_up</span>
        <span class="text-sm font-bold">Escuchar</span>
        <div class="flex items-end gap-0.5 h-3 ml-1">
            <div class="audio-wave-bar wave-1 paused"></div>
            <div class="audio-wave-bar wave-2 paused"></div>
            <div class="audio-wave-bar wave-3 paused"></div>
            <div class="audio-wave-bar wave-4 paused"></div>
        </div>
    `;
    
    // Inicializar audioguía
    AudioguiaManager.init(text);
    
    // Toggle al hacer click
    button.onclick = () => {
        const state = AudioguiaManager.toggle();
        updateAudioButton(button, state === 'playing');
    };
    
    // Listener para cambios de estado
    window.addEventListener('audioguiaChanged', (event) => {
        updateAudioButton(button, event.detail.isPlaying);
    });
    
    // Agregar al contenedor si se especifica
    if (containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.appendChild(button);
        }
    }
    
    return button;
}

/**
 * Detener audioguía al salir de la página
 */
window.addEventListener('beforeunload', () => {
    AudioguiaManager.stop();
});

// Exportar para uso global
window.AudioguiaManager = AudioguiaManager;
window.updateAudioButton = updateAudioButton;
window.createAudioButton = createAudioButton;