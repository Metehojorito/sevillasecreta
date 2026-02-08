<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo APP_NAME; ?> - Descubre el patrimonio oculto</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="assets/img/icon-512.png">
    
    <!-- Meta tags -->
    <meta name="description" content="Descubre el patrimonio urbano oculto de Sevilla: placas conmemorativas, estatuas, monumentos y rincones secretos.">
    <meta name="theme-color" content="#f2930d">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f2930d",
                        "background-light": "#f8f7f5",
                        "background-dark": "#221b10",
                        "ocher-dark": "#8b4513",
                        "ocher-light": "#ffd700",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    
    <style>
        .golden-gradient {
            background: linear-gradient(135deg, #f2930d 0%, #d9820b 50%, #8b4513 100%);
        }
        body {
            min-height: 100vh;
            min-height: 100dvh;
        }
        @keyframes fillProgress {
            from { width: 0%; }
            to { width: 100%; }
        }
        .progress-bar {
            animation: fillProgress 2s ease-in-out forwards;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    <!-- Splash Screen Container -->
    <div class="relative flex h-screen w-full flex-col items-center justify-center overflow-hidden golden-gradient">
        
        <!-- Central Branding Area -->
        <div class="relative z-10 flex flex-col items-center px-8 text-center">
            <!-- Logo Real -->
            <div class="mb-8 flex items-center justify-center">
                <div class="relative h-40 w-40 flex items-center justify-center">
                    <img src="assets/img/logo.png" alt="Sevilla Secreta" class="w-full h-full object-contain drop-shadow-2xl filter brightness-0 invert">
                </div>
            </div>
            
            <!-- Typography -->
            <div class="space-y-2">
                <h1 class="text-white text-4xl font-extrabold tracking-widest uppercase">
                    Sevilla <span class="block">Secreta</span>
                </h1>
                <div class="h-1 w-12 bg-white/40 mx-auto rounded-full my-4"></div>
                <p class="text-white/80 text-sm font-medium tracking-[0.2em] uppercase">
                    Descubre el patrimonio oculto
                </p>
            </div>
        </div>
        
        <!-- Footer / Loading Indicator -->
        <div class="absolute bottom-16 left-0 right-0 px-12 flex flex-col items-center gap-4">
            <div class="w-full max-w-[200px] h-[2px] bg-white/20 rounded-full overflow-hidden">
                <!-- Progress Animation -->
                <div class="h-full bg-white rounded-full progress-bar"></div>
            </div>
            <p class="text-white/60 text-[10px] font-bold tracking-[0.3em] uppercase">
                Cargando la ciudad...
            </p>
        </div>
        
        <!-- iOS Home Indicator Safe Area spacing -->
        <div class="absolute bottom-2 w-32 h-1 bg-white/20 rounded-full"></div>
    </div>

    <script>
        // Redirigir a categorías después de 2 segundos
        setTimeout(() => {
            window.location.href = 'categorias.php';
        }, 2000);
    </script>
</body>
</html>