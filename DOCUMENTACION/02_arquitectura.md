# 02. Arquitectura

## Stack tecnológico

El sistema utiliza un stack Laravel + React con Inertia:

- **Backend:** PHP 8.3 y Laravel 13.
- **Frontend:** React 18 con TypeScript.
- **Conexión backend/frontend:** Inertia.js 2.
- **Ruteo compartido:** Laravel routes + Ziggy.
- **Base de datos:** PostgreSQL según la configuración actual de entorno.
- **Estilos:** Tailwind CSS 4 y CSS propio.
- **Build frontend:** Vite.
- **UI auxiliar:** Headless UI, Lucide React, Framer Motion y Embla Carousel.
- **Autenticación base:** Laravel Breeze adaptado.

## Estructura general del proyecto

Las carpetas principales del sistema son:

- `app/Http/Controllers`: controladores públicos, de autenticación y superadmin.
- `app/Http/Middleware`: middleware de Inertia y protección superadmin.
- `app/Models`: modelos Eloquent del dominio.
- `app/Services`: servicios de transformación de datos, especialmente `ProductMapper`.
- `app/Support`: reglas compartidas de validación, especialmente usuarios.
- `database/migrations`: estructura de tablas.
- `database/seeders`: datos base de roles, usuarios y catálogo.
- `routes`: rutas web, auth y consola.
- `resources/js`: frontend React, páginas, componentes, tipos y datos visuales.
- `resources/css`: estilos globales.
- `resources/views`: plantilla raíz de Inertia.

## Organización backend

Laravel actúa como núcleo de aplicación. Las rutas reciben la solicitud, invocan controladores, consultan modelos Eloquent y devuelven páginas Inertia con datos ya mapeados para React.

Los controladores principales son:

- `HomeController`: landing pública con novedades, selección, categorías y marcas.
- `CatalogoController`: colecciones públicas, catálogo, editorial y colecciones curadas.
- `PrendaController`: detalle de producto.
- `CarritoController`: bolsa basada en sesión.
- `FavoritoController`: favoritos de usuario.
- `CuentaController`: resumen de cuenta, pedidos, reservas y favoritos.
- `Superadmin\ResourceController`: controlador genérico del panel administrativo.
- `Superadmin\DashboardController`: métricas principales del panel.
- `Superadmin\ReportsController`: reportes filtrables de variantes e inventario.
- `Superadmin\LogsController`: bitácora agregada desde usuarios, prendas, ventas y reservas.
- `Superadmin\ProfileController`: edición de perfil y contraseña del superadmin.

## Organización frontend

El frontend está en `resources/js` y se organiza en:

- `Pages`: páginas Inertia públicas, de auth, cuenta, shop y superadmin.
- `Components`: componentes reutilizables de formularios, navegación, storefront y superadmin.
- `Layouts`: layouts de Breeze y vistas autenticadas.
- `types`: contratos TypeScript para datos de tienda, cuenta y superadmin.
- `lib`: utilidades de catálogo y superadmin.
- `data`: datos visuales editoriales del storefront.

Las páginas no consumen una API REST separada para render inicial. Reciben props desde Inertia. Algunas acciones usan endpoints JSON, por ejemplo favoritos y bolsa.

## Rutas principales

### Público

- `/`: landing.
- `/catalogo`: catálogo completo.
- `/mujer`: colección mujer.
- `/hombre`: colección hombre.
- `/novedades`: novedades.
- `/accesorios`: accesorios.
- `/producto/{id}`: detalle de prenda activa.
- `/colecciones`: página curada de colecciones.
- `/editorial`: página editorial.
- `/bolsa`: bolsa de compra.

### APIs internas públicas

- `POST /api/carrito/agregar`.
- `PATCH /api/carrito/{varianteId}`.
- `DELETE /api/carrito/{varianteId}`.
- `DELETE /api/carrito`.
- `POST /api/favoritos/toggle`, protegida por autenticación.

### Cuenta

- `/cuenta`.
- `/cuenta/pedidos`.
- `/cuenta/reservas`.
- `/cuenta/favoritos`.

### Superadmin

- `/superadmin`.
- `/superadmin/perfil`.
- `/superadmin/reportes`.
- `/superadmin/logs`.
- `/superadmin/{section}/{resource}`.
- `/superadmin/{section}/{resource}/nuevo`.
- `/superadmin/{section}/{resource}/{record}`.
- `/superadmin/{section}/{resource}/{record}/editar`.

## Conexión entre capas

El flujo general es:

1. Laravel define una ruta en `routes/web.php` o `routes/auth.php`.
2. El controlador consulta modelos Eloquent.
3. Los datos se preparan, normalizan o transforman. Para tienda se usa `ProductMapper`.
4. El controlador responde con `Inertia::render`.
5. React recibe props tipadas y renderiza la pantalla.
6. Acciones como favoritos o bolsa pueden usar `axios` o `router` de Inertia.
7. La base de datos persiste entidades y relaciones mediante Eloquent.

## Modelo general de datos

El dominio se organiza alrededor de:

- `roles` y `users` para acceso.
- `categorias`, `marcas`, `colores`, `tallas`, `temporadas` como maestros.
- `prendas` como entidad central de catálogo.
- `prenda_variantes` para combinaciones color/talla/precio/código.
- `imagenes_prenda` para portada, galería e imágenes asociadas.
- `inventarios` para disponibilidad y reserva por variante.
- `ventas` y `detalle_ventas` como registros comerciales.
- `reservas` y `detalle_reservas` como apartados.
- `favoritos` como relación usuario-prenda.

## Enfoque arquitectónico

El sistema sigue un enfoque monolítico moderno: Laravel centraliza autorización, datos, reglas y render inicial; React maneja experiencia de usuario rica; Inertia evita duplicar una API completa para páginas internas.

El panel superadmin utiliza un controlador genérico basado en definiciones de recursos. Esto reduce duplicación para índices, formularios, vistas detalle, métricas, filtros, estados y acciones.

