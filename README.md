# Conociendo Formosa 🌸

Producto digital educativo (trabajo final de *Análisis de la Realidad Sociocultural de Formosa*, Eje 2): mapa interactivo con 28 lugares de cultura, naturaleza y paseo, cada uno con su color (3 por departamento, 4 en la capital), cada uno con **¿Qué es?**, **¿Por qué es importante?**, **¿Quiénes lo habitan?**, fotos reales y fuentes. Incluye juegos, galería, reseñas, sección del proyecto, bibliografía y panel administrativo.

**Desarrollado por Dalila Spindola Dev** · Trabajo grupal con Kevin Galeano, Ramiro Escobar y Facundo Caballero (investigación).

🌐 **Sitio online:** http://conociendo-formosa.freedev.app

---

## Tecnologías

| Capa | Herramientas |
|---|---|
| Front | HTML5, CSS3 (variables, animaciones), JavaScript (sin frameworks), Bootstrap 5.3, Leaflet 1.9 (mapa real: satélite, relieve y calles), SVG propio (mapa ilustrado), Font Awesome 6 |
| Back | PHP 8+ con **PDO** (consultas preparadas) |
| Base de datos | MySQL / MariaDB (`utf8mb4`) |

## Instalación (XAMPP / WAMP / Laragon)

1. Copiá la carpeta `formosa-turismo` dentro de `htdocs` (XAMPP) o `www` (WAMP/Laragon).
2. Abrí **phpMyAdmin** → pestaña *Importar* → elegí `database/formosa.sql` → *Continuar*.
   (Crea la base `formosa_turismo` con todas las tablas y los datos.)
3. Copiá `config/config.example.php` como **`config/config.php`** y revisá los datos (por defecto: usuario `root`, contraseña vacía, como en XAMPP). Ese archivo no se sube a GitHub porque guarda contraseñas.
4. Entrá a **http://localhost/formosa-turismo/**
5. Panel: **http://localhost/formosa-turismo/admin/**
   - Usuario: `admin`
   - Contraseña: `admin123` → **cambiala** en *Usuarios → Cambiar mi contraseña*.

> Requisitos: PHP 8.0 o superior con las extensiones `pdo_mysql` y `fileinfo` (vienen activas en XAMPP).

## Estructura de carpetas

```
formosa-turismo/
├── index.php                 ← Sitio público (una sola página con todas las secciones)
├── lugar.php                 ← Ficha completa de cada lugar (se abre en una pestaña nueva)
├── .htaccess                 ← Bloquea acceso directo a config/, includes/, database/
├── config/
│   └── config.example.php    ← Modelo de configuración (copialo como config.php)
├── includes/
│   ├── db.php                ← Conexión PDO (singleton)
│   └── funciones.php         ← Helpers: escape, CSRF, subida segura de imágenes, etc.
├── api/                      ← Endpoints JSON usados por el sitio
│   ├── lugares.php           ← Departamentos + lugares + imágenes
│   ├── preguntas.php         ← Preguntas para los juegos
│   ├── resenas.php           ← Listar / crear reseñas
│   └── puntajes.php          ← Ranking de los juegos
├── assets/
│   ├── css/style.css         ← Estilos y efectos del sitio
│   ├── js/
│   │   ├── utils.js          ← Utilidades, modal de lugar, lightbox
│   │   ├── mapa.js           ← Mapa ilustrado interactivo (SVG: zoom, arrastre, filtros)
│   │   ├── lugar.js          ← Ficha del lugar: galería, pestañas y desafío
│   │   ├── juegos.js         ← Trivia, Memotest, ¿Dónde queda?
│   │   ├── resenas.js        ← Reseñas
│   │   └── main.js           ← Arranque, slider, animaciones, buscador
│   └── img/
│       ├── mapa-formosa.svg  ← Mapa ilustrado por departamentos (vectorizado del diseño original)
│       └── favicon.svg
├── admin/                    ← Panel administrativo
│   ├── index.php             ← Login
│   ├── dashboard.php         ← Escritorio con estadísticas
│   ├── lugares.php           ← Listado, ocultar, destacar, eliminar
│   ├── lugar_form.php        ← Crear / editar lugar (con mapa para marcar la ubicación)
│   ├── imagenes.php          ← Subir (varias a la vez), ordenar, editar, reemplazar, portada, quitar
│   ├── preguntas.php         ← Preguntas del juego
│   ├── departamentos.php     ← Departamentos (color, imagen, descripción)
│   ├── pueblos.php           ← Pueblos originarios
│   ├── slides.php            ← Imágenes de portada (slider)
│   ├── contenido.php         ← Textos del sitio, proyecto, contacto, redes
│   ├── bibliografia.php      ← Fuentes y bibliografía
│   ├── resenas.php           ← Moderar reseñas
│   ├── puntajes.php          ← Ranking de juegos
│   ├── usuarios.php          ← Administradores y cambio de contraseña
│   ├── includes/             ← bootstrap, header, footer del panel
│   └── assets/               ← admin.css, admin.js
├── uploads/                  ← Fotos subidas desde el panel (con .htaccess que impide ejecutar scripts)
└── database/
    └── formosa.sql           ← Estructura + datos iniciales
```

## Qué incluye el sitio

- **Portada** con slider de imágenes reales (efecto Ken Burns), pétalos de lapacho cayendo y contadores animados.
- **Sobre Formosa**: historia, cultura y naturaleza.
- **Sobre este proyecto**: presentación en primera persona y eje elegido (editable en *Textos del sitio*).
- **Mapa interactivo ilustrado**: el mapa de Formosa por departamentos (diseño original, ahora en SVG), con zoom al tocar un departamento, arrastre y 28 lugares (3 por departamento, 4 en la capital), filtros por categoría y departamento, buscador, vista satélite, tarjeta de vista previa y ficha de cada lugar en una **pestaña nueva** (`lugar.php`) con tres preguntas (¿Qué es? / ¿Por qué es importante? / ¿Quiénes lo habitan?), galería, dato curioso, cómo llegar, desafío y fuentes consultadas.
- **Departamentos**: tarjetas que llevan al mapa filtrado.
- **Destacados**: carrusel con efecto 3D.
- **Pueblos originarios**: Qom, Wichí, Pilagá y Nivaclé.
- **Juegos**: Trivia (10 preguntas con tiempo), Memotest con fotos y «¿Dónde queda?»; cada uno con ranking guardado en la base.
- **Galería** con visor a pantalla completa.
- **Reseñas** con estrellas, promedio y barras (con moderación opcional).
- **Buscador global** (botón de lupa o `Ctrl + K`).
- **Fuentes y bibliografía**: programa del Eje 2, fuentes oficiales, artículos y material audiovisual (editable en *Fuentes y bibliografía* del panel).
- **Pie de página**: «Desarrollado por Dalila Spindola Dev».

## Paleta de colores

| Color | Hex | Inspiración |
|---|---|---|
| Monte | `#0E3B2E` | Verde profundo del monte chaqueño |
| Humedal | `#1F9E89` | Esteros, bañados y riachos |
| Lapacho | `#E05A8A` | La flor del lapacho rosado |
| Sol | `#F2A541` | Atardeceres sobre el río Paraguay |
| Tierra | `#C2552F` | Barro, artesanía y raíces originarias |
| Arena | `#FBF6EC` | Fondo cálido |

Tipografías: **Fraunces** (títulos) y **Outfit** (textos), desde Google Fonts.

## Imágenes

- Las fotos iniciales son **fotografías reales de Formosa de Wikimedia Commons** (costanera, catedral, Casa de Gobierno, Bañado La Estrella, jabirúes, Parque Nacional Río Pilcomayo, Las Lomitas, etc.), enlazadas por URL y guardadas en la tabla `imagenes`.
- Los lugares que todavía no tienen foto se muestran con una ilustración del color de su departamento. El **Escritorio** del panel lista cuáles faltan, con un botón para subirlas.
- Desde el panel podés **subir** (arrastrando varias a la vez), **pegar una URL**, **editar** título/crédito, **reemplazar**, **elegir portada**, **ordenar arrastrando** y **eliminar**.

## Seguridad aplicada

- Consultas **PDO preparadas** en todo el proyecto.
- Contraseñas con `password_hash()` / `password_verify()`.
- Token **CSRF** en todos los formularios y en las APIs que escriben.
- Validación real de imágenes (extensión + tipo MIME + `getimagesize`) y límite de tamaño.
- `uploads/.htaccess` impide ejecutar scripts subidos.
- Freno de intentos de login, expiración de sesión del panel (2 h), anti-spam en reseñas (campo trampa + 1 por minuto por IP).

## Antes de publicar en un hosting

1. En `config/config.php` poné `APP_DEBUG` en `false` y cargá las credenciales del hosting.
2. Cambiá la contraseña del usuario `admin`.
3. Revisá en *Textos del sitio* el email, teléfono y redes sociales.
4. Verificá la ubicación de los lugares: en *Editar lugar* hacé clic sobre el mapa ilustrado para mover el marcador, y ajustá las coordenadas reales (usadas en «Cómo llegar») arrastrando el marcador del mapa de calles.
