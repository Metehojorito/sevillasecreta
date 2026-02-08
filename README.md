# Sevilla Secreta 🗝️

Aplicación web/móvil para descubrir patrimonio urbano oculto en Sevilla: placas conmemorativas, estatuas, alcantarillas artísticas, monumentos y rincones secretos.

## 🚀 Instalación

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.2+
- Apache con mod_rewrite (opcional)
- Extensiones PHP: PDO, pdo_mysql, gd

### Pasos de instalación

1. **Clonar o descargar el proyecto**
   ```bash
   cd /tu/directorio/www
   # Colocar los archivos en la carpeta sevilla-secreta/
   ```

2. **Crear la base de datos**
   - Abrir phpMyAdmin o tu gestor de MySQL
   - Importar el archivo `database.sql`
   - Esto creará la base de datos, tablas y datos de prueba

3. **Configurar la conexión**
   - Editar `includes/config.php`
   - Modificar las credenciales de base de datos si es necesario:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'sevilla_secreta');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Configurar permisos**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/pois/
   ```

5. **Acceder a la aplicación**
   - Frontend: `http://localhost/sevilla-secreta/`
   - Panel Admin: `http://localhost/sevilla-secreta/admin/`
     - Usuario: `admin`
     - Contraseña: `admin123`

## 📁 Estructura del proyecto

```
sevilla-secreta/
├── admin/                # Panel de administración
├── api/                  # Endpoints JSON
├── assets/               # CSS, JS, imágenes
├── includes/             # Archivos PHP de configuración
├── uploads/              # Archivos subidos (fotos POIs)
├── index.php            # Splash screen
├── categorias.php       # Listado de categorías
├── mapa.php             # Vista de mapa
├── lista.php            # Vista de lista
├── detalle.php          # Detalle de POI
├── favoritos.php        # Favoritos guardados
└── database.sql         # Script de base de datos
```

## 🗄️ Base de datos

### Tablas principales:
- `ciudades` - Ciudades disponibles en la app
- `categorias` - Categorías de POIs (placas, estatuas, etc.)
- `pois` - Puntos de interés
- `fotos_pois` - Fotos asociadas a cada POI
- `admin` - Usuarios administradores

## 🔧 Tecnologías

- **Backend:** PHP puro
- **Base de datos:** MySQL/MariaDB
- **Frontend:** HTML5, TailwindCSS (CDN), JavaScript vanilla
- **Mapas:** Leaflet.js + Carto tiles (OpenStreetMap)
- **Iconos:** Material Symbols (Google)
- **Tipografía:** Plus Jakarta Sans, Playfair Display

## 📱 Características

- ✅ Múltiples ciudades (preparado, iniciando con Sevilla)
- ✅ Categorización de POIs
- ✅ Vista de mapa interactivo
- ✅ Vista de lista con búsqueda
- ✅ Favoritos (localStorage, sin login)
- ✅ Cálculo de distancia en tiempo real
- ✅ Lectura de voz de descripciones (Web Speech API)
- ✅ Responsive design
- ✅ Modo oscuro/claro
- ✅ Panel de administración

## 🎨 Paleta de colores

- **Primario:** `#f2930d` (Dorado sevillano)
- **Fondo claro:** `#f8f7f5`
- **Fondo oscuro:** `#221b10`

## 📝 Próximas mejoras

- [ ] API REST completa
- [ ] Sistema de usuarios con login
- [ ] Rutas/itinerarios guardados
- [ ] Compartir POIs en RRSS
- [ ] Notificaciones de POIs cercanos
- [ ] Empaquetado con Capacitor para tiendas
- [ ] PWA con service workers

## 👤 Créditos

Desarrollado para descubrir y preservar el patrimonio oculto de Sevilla.

---

**Licencia:** Proyecto personal - Todos los derechos reservados