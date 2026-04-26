# 🔐 Sevilla Secreta

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Leaflet](https://img.shields.io/badge/Leaflet-1.9.4-199900?logo=leaflet&logoColor=white)](https://leafletjs.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3.x-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![PWA](https://img.shields.io/badge/PWA-Ready-5A0FC8?logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)

> Descubre el patrimonio urbano oculto de Sevilla: placas conmemorativas, estatuas, monumentos y rincones secretos.

**Sevilla Secreta** es una Progressive Web App (PWA) para explorar el patrimonio histórico y cultural menos conocido de Sevilla. Permite a los usuarios descubrir puntos de interés cercanos, navegar por categorías temáticas, escuchar audioguías y marcar sus favoritos.

---

## 📱 Características

### **Aplicación Pública**
- ✅ **Exploración por categorías** - Placas conmemorativas, estatuas, monumentos, alcantarillas artísticas, etc.
- ✅ **Mapa interactivo** - Visualiza POIs con Leaflet.js, marcadores personalizados y geolocalización
- ✅ **Vista lista** - Filtrado y búsqueda de puntos de interés
- ✅ **Detalle de POI** - Carrusel de fotos, descripción, ubicación, horarios y precios
- ✅ **Audioguía** - Text-to-Speech con Web Speech API (español)
- ✅ **Favoritos** - Sistema de marcadores con localStorage
- ✅ **Geolocalización** - Calcula distancia desde tu ubicación actual
- ✅ **Compartir** - Web Share API con fallback a clipboard
- ✅ **Dark Mode** - Tema claro/oscuro persistente
- ✅ **PWA Ready** - Instalable como app nativa (manifest.json)
- ✅ **Responsive** - Optimizado para móvil, tablet y escritorio
- ✅ **Sugerencias de usuarios** - Formulario para proponer nuevos lugares secretos

### **Panel de Administración**
- 🔒 **Login seguro** - Protección CSRF, rate limiting, session hijacking prevention
- 📊 **Dashboard** - Estadísticas de POIs, categorías, fotos y sugerencias pendientes
- 🗂️ **CRUD de Categorías** - Drag & drop para ordenar, color picker, selector de iconos Material Symbols
- 📍 **CRUD de POIs** - Selector de ubicación con mapa Leaflet, gestión de hasta 3 fotos por POI
- 🖼️ **Gestión de fotos** - Upload con validación estricta (WebP, JPEG, PNG), preview y eliminación
- 💡 **Gestión de sugerencias** - Revisión, edición y publicación directa de lugares propuestos por usuarios
- 🔐 **Seguridad** - Tokens CSRF, sanitización XSS, logging de eventos, prepared statements

---

## 🛠️ Tecnologías

### **Frontend**
- **HTML5** + **Tailwind CSS 3** (CDN)
- **JavaScript ES6** - Módulos externos reutilizables
- **Leaflet.js 1.9.4** - Mapas interactivos
- **Material Symbols** - Iconografía de Google
- **Web Speech API** - Síntesis de voz
- **localStorage API** - Persistencia de favoritos y tema

### **Backend**
- **PHP 7.4+** - Lógica del servidor
- **MySQL 5.7+ / MariaDB** - Base de datos relacional
- **PDO** - Prepared statements para prevenir SQL injection

### **Seguridad**
- **CSRF Tokens** - Protección contra Cross-Site Request Forgery
- **XSS Prevention** - Sanitización con `htmlspecialchars()`
- **SQL Injection Protection** - PDO prepared statements
- **Session Security** - Regeneración de IDs, validación de User Agent
- **Rate Limiting** - Máximo 5 intentos de login cada 15 minutos
- **Password Hashing** - Bcrypt (cost 12)
- **Security Logging** - Registro de eventos en `/logs/security.log`

### **Librerías Externas**
- **Sortable.js** - Drag & drop en listados del admin
- **Google Fonts** - Plus Jakarta Sans

---

## 📂 Estructura del Proyecto

```
sevilla-secreta/
├── admin/                      # Panel de administración
│   ├── login.php              # Login seguro
│   ├── dashboard.php          # Dashboard principal
│   ├── categorias.php         # CRUD categorías (listado)
│   ├── categoria-form.php     # Formulario crear/editar categoría
│   ├── pois.php               # CRUD POIs (listado)
│   ├── poi-form.php           # Formulario crear/editar POI
│   ├── sugerencias.php        # Gestión de sugerencias de usuarios
│   ├── eliminar-foto.php      # Eliminar fotos de POIs
│   ├── logout.php             # Cierre de sesión
│   ├── icons-data.js          # 130+ iconos Material Symbols
│   └── includes/
│       └── navbar.php         # Navegación del admin
├── api/                       # Endpoints JSON
│   ├── categorias.php         # GET categorías activas
│   ├── pois.php               # GET POIs por categoría
│   └── poi-detalle.php        # GET detalle de POI
├── assets/
│   ├── css/
│   │   └── app.css            # Estilos globales y utilidades
│   ├── js/
│   │   ├── app.js             # Funciones globales (fetchAPI, toasts, etc.)
│   │   ├── favoritos.js       # FavoritosManager (localStorage)
│   │   ├── mapa.js            # MapaManager (Leaflet utilities)
│   │   └── voz.js             # AudioguiaManager (Web Speech API)
│   └── img/
│       ├── logo.png           # Logo principal
│       ├── icon-192.png       # PWA icon
│       ├── icon-512.png       # PWA icon
│       ├── placeholder-poi.svg
│       └── placeholder-categoria.svg
├── includes/
│   ├── config.php             # Configuración global
│   ├── db.php                 # Clase Database (PDO singleton)
│   ├── funciones.php          # Funciones auxiliares (slug, upload, etc.)
│   ├── security.php           # Clase Security (CSRF, XSS, sessions)
│   ├── asset-helper.php       # Helper para cache busting automático
│   └── bottom-nav.php         # Barra de navegación inferior (componente reutilizable)
├── logs/
│   └── security.log           # Registro de eventos de seguridad
├── uploads/
│   ├── categorias/            # Imágenes de portada de categorías
│   └── pois/                  # Fotos de POIs (máx 3 por POI)
├── index.php                  # Splash screen
├── categorias.php             # Grid de categorías
├── mapa.php                   # Mapa interactivo
├── lista.php                  # Listado de POIs
├── detalle.php                # Detalle de POI
├── favoritos.php              # Página de favoritos
├── sugerir.php                # Formulario para sugerir nuevos lugares
├── manifest.json              # PWA manifest
├── favicon.ico                # Favicon
├── database.sql               # Schema de base de datos
└── README.md                  # Este archivo
```

---

## 🚀 Instalación

### **Requisitos**
- **PHP 7.4+** con extensiones: `pdo`, `pdo_mysql`, `fileinfo`, `gd`
- **MySQL 5.7+** o **MariaDB 10.3+**
- **Servidor web** (Apache o Nginx)

### **Pasos**

1. **Clonar repositorio**
   ```bash
   git clone https://github.com/tu-usuario/sevilla-secreta.git
   cd sevilla-secreta
   ```

2. **Importar base de datos**
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configurar credenciales** (si es necesario)
   
   Edita `includes/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'sevilla_secreta');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Crear carpetas de uploads**
   ```bash
   mkdir -p uploads/pois uploads/categorias logs
   chmod 755 uploads/pois uploads/categorias logs
   ```

5. **Aplicar migración de formato de categorías** (si usas una BD antigua)
   ```bash
   mysql -u root -p sevilla_secreta < migration-add-formato.sql
   ```

6. **Acceder a la aplicación**
   - **App pública:** `http://localhost/sevilla-secreta/`
   - **Panel admin:** `http://localhost/sevilla-secreta/admin/`
   
   **Credenciales por defecto:**
   - Usuario: `admin`
   - Contraseña: `Admin123!` (cámbiala tras primer login)

---

## 🗄️ Base de Datos

### **Tablas Principales**

#### `ciudades`
Ciudades disponibles en la aplicación.
- `id`, `nombre`, `slug`, `latitud`, `longitud`, `activa`

#### `categorias`
Tipos de secretos urbanos.
- `id`, `ciudad_id`, `nombre`, `slug`, `icono`, `color`, `imagen_portada`, `formato` (1x1 o 1x2), `orden`, `activa`

#### `pois`
Puntos de interés.
- `id`, `ciudad_id`, `categoria_id`, `titulo`, `slug`, `descripcion`, `direccion`, `latitud`, `longitud`, `horario`, `precio`, `orden`, `activo`

#### `fotos_pois`
Fotos de POIs (máximo 3 por POI).
- `id`, `poi_id`, `filename`, `orden`

#### `admin`
Usuarios administradores.
- `id`, `usuario`, `password`, `nombre`, `created_at`

#### `sugerencias_pois`
Lugares propuestos por usuarios pendientes de revisión.
- `id`, `ciudad_id`, `categoria_id`, `titulo`, `descripcion`, `latitud`, `longitud`
- `estado` — `pendiente` | `aprobada` | `rechazada`
- `ip_usuario`, `user_agent` — trazabilidad del origen
- `notas_admin`, `revisado_at`, `revisado_por` — metadatos de revisión

---

## 💡 Flujo de Sugerencias

Los usuarios pueden proponer nuevos lugares directamente desde la app pública. El proceso completo es:

```
Usuario rellena sugerir.php
        │
        ▼
sugerencias_pois (estado = pendiente)
        │
        ▼
Admin recibe alerta en dashboard
        │
        ├─► Descartar → se elimina de la BD
        │
        └─► Aceptar → se revisa y edita el contenido
                    → se crea el POI en la tabla pois
                    → se elimina la sugerencia
```

1. **El usuario** accede a `sugerir.php` desde el botón "Sugerir" de la barra de navegación inferior, rellena título, descripción, categoría y marca la ubicación en el mapa.
2. **El dashboard** muestra un contador con las sugerencias pendientes. Si hay alguna, la tarjeta se resalta en ámbar.
3. **El administrador** accede a `admin/sugerencias.php`, ve el listado filtrable por estado y abre el modal de detalle de cada sugerencia.
4. En el modal puede **editar** el contenido (título, descripción, categoría, dirección, horario, precio) antes de publicar, ver la ubicación en un mapa mini y elegir entre **aceptar** (crea el POI automáticamente) o **descartar** (elimina la sugerencia).

---

## 🔒 Seguridad

### **Protecciones Implementadas**

| Vulnerabilidad | Protección |
|----------------|------------|
| **SQL Injection** | PDO prepared statements en todas las queries |
| **XSS** | `htmlspecialchars()` con `ENT_QUOTES` en todos los outputs |
| **CSRF** | Tokens únicos por sesión validados en cada POST |
| **Session Hijacking** | Validación de User Agent, regeneración periódica de session ID |
| **Session Fixation** | `session_regenerate_id(true)` en cada login |
| **Brute Force** | Rate limiting (máx 5 intentos cada 15 min) |
| **Timing Attacks** | Delay de 0.5s en login fallido |
| **Path Traversal** | Validación de rutas en upload de archivos |
| **File Upload** | Validación MIME con `finfo`, extensiones permitidas, tamaño máx 5MB |
| **Clickjacking** | Header `X-Frame-Options: DENY` |

### **Logging**
Todos los eventos de seguridad se registran en `/logs/security.log`:
- Logins exitosos/fallidos
- Rate limit excedido
- Ataques CSRF detectados
- Creación/edición/eliminación de contenido
- Sugerencias aceptadas o descartadas

---

## 📱 PWA (Progressive Web App)

La aplicación es instalable como app nativa:

1. **En Chrome móvil:** "Agregar a pantalla de inicio"
2. **En Safari iOS:** Compartir → "Añadir a pantalla de inicio"
3. **En Chrome escritorio:** Icono de instalación en la barra de direcciones

**Características PWA:**
- ✅ Manifest.json configurado
- ✅ Iconos 192x192 y 512x512
- ✅ Tema color personalizado (#f2930d)
- ⏳ Service Worker (pendiente para offline)

---

## 🎨 Personalización

### **Cambiar colores**

Edita las variables en `tailwind.config` (presente en cada página):

```javascript
tailwind.config = {
    theme: {
        extend: {
            colors: {
                "primary": "#f2930d",        // Color principal (naranja)
                "background-light": "#f8f7f5", // Fondo claro
                "background-dark": "#221b10",  // Fondo oscuro
            }
        }
    }
}
```

### **Añadir nueva categoría**

1. Panel admin → **Categorías** → **Nueva Categoría**
2. Completa el formulario:
   - Nombre (genera slug automáticamente)
   - Icono (selector con 130+ iconos Material Symbols)
   - Color (color picker)
   - Formato (cuadrado 1x1 o rectangular 1x2)
   - Imagen de portada (opcional)
3. Guardar → Se añade al final del orden

### **Añadir nuevo POI**

1. Panel admin → **POIs** → **Nuevo POI**
2. Completa el formulario:
   - Categoría (dropdown)
   - Título y descripción
   - Ubicación (click en mapa para coordenadas)
   - Dirección, horario y precio (opcionales)
   - Hasta 3 fotos
3. Guardar → Se añade al final del orden

### **Gestionar sugerencias**

1. Panel admin → **Sugerencias** (o directamente desde el dashboard si hay pendientes)
2. Filtra por estado: **Pendientes** o **Todas**
3. Pulsa **Ver** en cualquier sugerencia para abrir el modal de detalle:
   - Revisa la descripción original y la ubicación en el mapa mini
   - Edita título, categoría, descripción, dirección, horario y precio
   - **Aceptar y publicar** → crea el POI automáticamente
   - **Descartar** → elimina la sugerencia de la BD

---

## 🐛 Problemas Conocidos

### **Web Speech API en iOS**
- La síntesis de voz funciona pero **no permite pausa/reanudación**.
- **Solución actual:** Al pausar, se detiene completamente. Al reanudar, comienza desde el principio.

### **Geolocalización requiere HTTPS**
- En producción, la geolocalización solo funciona con HTTPS.
- En localhost HTTP funciona sin problemas.

---

## 📋 Roadmap

- [ ] Service Worker para funcionamiento offline
- [ ] Cache de mapas para uso sin conexión
- [ ] Rutas entre POIs
- [ ] Sistema de comentarios/valoraciones
- [ ] Gamificación (insignias por visitas)
- [ ] Exportar ruta a Google Maps
- [ ] Multiidioma (inglés, francés)
- [ ] Notificaciones push para nuevos POIs cercanos
- [ ] Integración con redes sociales

---

## 👥 Contribuir

¡Las contribuciones son bienvenidas! Si encuentras un bug o tienes una idea:

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está bajo la licencia **MIT**. Consulta el archivo `LICENSE` para más detalles.

---

## 📧 Contacto

**Proyecto:** Sevilla Secreta  
**GitHub:** [https://github.com/tu-usuario/sevilla-secreta](https://github.com/tu-usuario/sevilla-secreta)

---

## 🙏 Agradecimientos

- **Leaflet.js** - Biblioteca de mapas interactivos
- **Tailwind CSS** - Framework CSS utility-first
- **Material Symbols** - Iconografía de Google
- **OpenStreetMap** - Datos cartográficos
- **CARTO** - Tiles de mapa

---

<div align="center">
  <img src="assets/img/logo.png" alt="Sevilla Secreta" width="120">
  <br><br>
  <strong>Hecho con ❤️ en Sevilla</strong>
</div>
