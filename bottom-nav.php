<?php
// bottom-nav.php
// Uso: require_once 'includes/bottom-nav.php';
// Requiere que favoritos.js esté cargado en la página.
// Acepta variable opcional $nav_active: 'explorar' | 'favoritos' | 'sugerir'
$nav_active = $nav_active ?? '';
?>
<nav class="fixed bottom-0 left-0 right-0 z-[1000] glass-effect border-t border-black/5 dark:border-white/10 px-8 h-20 pb-safe">
    <div class="flex items-center justify-around h-full max-w-md mx-auto">

        <a href="categorias.php" class="flex flex-col items-center gap-1 transition-colors
            <?php echo $nav_active === 'explorar' ? 'text-primary' : 'text-zinc-400 hover:text-primary'; ?>">
            <span class="material-symbols-outlined text-2xl
                <?php echo $nav_active === 'explorar' ? 'icon-filled' : ''; ?>">explore</span>
            <span class="text-[10px] font-bold uppercase tracking-tight">Explorar</span>
        </a>

        <a href="favoritos.php" class="flex flex-col items-center gap-1 transition-colors
            <?php echo $nav_active === 'favoritos' ? 'text-primary' : 'text-zinc-400 hover:text-primary'; ?>">
            <div class="relative">
                <span class="material-symbols-outlined text-2xl
                    <?php echo $nav_active === 'favoritos' ? 'icon-filled' : ''; ?>">favorite</span>
                <span id="favoritos-badge" class="hidden absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"></span>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-tight">Favoritos</span>
        </a>

        <a href="sugerir.php" class="flex flex-col items-center gap-1 transition-colors
            <?php echo $nav_active === 'sugerir' ? 'text-primary' : 'text-zinc-400 hover:text-primary'; ?>">
            <span class="material-symbols-outlined text-2xl
                <?php echo $nav_active === 'sugerir' ? 'icon-filled' : ''; ?>">add_location_alt</span>
            <span class="text-[10px] font-bold uppercase tracking-tight">Sugerir</span>
        </a>

    </div>
</nav>

<script>
    // Inicializa el badge de favoritos en cuanto el DOM esté listo.
    // Si la página ya llama a renderFavoritosBadge() manualmente, este bloque
    // lo detecta y no duplica la llamada.
    (function () {
        function initBadge() {
            if (typeof renderFavoritosBadge === 'function') {
                renderFavoritosBadge('favoritos-badge');
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initBadge);
        } else {
            initBadge();
        }
    })();
</script>
