# Descripción General del Proyecto: **MORE**

Este documento proporciona una visión detallada de la arquitectura, flujo del sistema y base de datos del proyecto **MORE**, una plataforma orientada al sector "Fashion-Tech".

## 🛠️ Stack Tecnológico

El proyecto está construido sobre un stack moderno y de alto rendimiento:

- **Backend:** [Laravel 13](https://laravel.com/) (PHP ^8.3).
- **Frontend:** [Inertia.js](https://inertiajs.com/) con **React** y **TypeScript**.
- **Estilos:** [Tailwind CSS v4](https://tailwindcss.com/) (diseño premium y moderno).
- **Animaciones:** [Framer Motion](https://www.framer.com/motion/) (interacciones fluidas).
- **Herramientas de Construcción:** [Vite](https://vitejs.dev/) (para un desarrollo ultra rápido).
- **Iconos:** [Lucide-React](https://lucide.dev/).

---

## 🏗️ Estructura del Sistema

### 1. Backend (Laravel + Inertia)
Laravel actúa como el núcleo del sistema, gestionando la lógica de negocio, la base de datos y la autenticación. Inertia.js permite crear una aplicación de una sola página (SPA) utilizando rutas clásicas de Laravel.

- **Rutas Principales:** Definidas en `routes/web.php` y `routes/auth.php`.
- **Controladores:** Ubicados en `app/Http/Controllers`, encargados de procesar datos y renderizar vistas de Inertia.
- **Middleware:** Gestiona la seguridad (autenticación `auth` y verificación `verified`).

### 2. Frontend (React + TypeScript)
La interfaz de usuario es altamente interactiva y dinámica.
- **Componentes/Vistas:** Se encuentran en `resources/js/Pages`.
- **Interactividad:** Uso intensivo de Hooks de React y TypeScript para asegurar la integridad de los datos en el frontend.
- **Estética:** Se prioriza un diseño minimalista y "premium" ("editorial style").

---

## 📊 Base de Datos y Modelado

El sistema cuenta con una estructura de base de datos robusta para el sector moda:

### Entidades Principales:
1.  **Usuarios y Roles:** Gestión de acceso y perfiles.
2.  **Catálogo de Productos:**
    *   `prendas`: Tabla principal de productos.
    *   `categorias`, `marcas`, `estilos`, `colores`, `tallas`, `temporadas`: Tablas maestras para clasificación detallada.
    *   `prenda_variantes`: Gestiona combinaciones de talla/color.
    *   `imagenes_prenda`: Almacenamiento de múltiples fotos por producto.
3.  **Gestión de Inventario:**
    *   `inventarios`: Control de stock actual.
    *   `movimientos_inventario`: Historial de entradas y salidas.
4.  **Ventas:**
    *   `ventas` y `detalle_ventas`: Registro de transacciones comerciales.
5.  **Interacción e IA:**
    *   `favoritos`: Lista de deseos de los usuarios.
    *   `interacciones_usuarios`: Tracking de comportamiento.
    *   `detecciones_ia`: Información sobre procesamiento inteligente de imágenes o recomendaciones.

---

## 🚀 Flujo del Sistema

1.  **Visitante:**
    *   Aterriza en `Home` o `Welcome`.
    *   Explora el catálogo de prendas mediante filtros (Marca, Estilo, Temporada).
2.  **Autenticación:**
    *   Registro/Login mediante Laravel Breeze (personalizado).
    *   Verificación de cuenta.
3.  **Usuario Registrado:**
    *   Gestión de perfil en `/profile`.
    *   Acceso al `Dashboard` personalizable.
    *   Interacción con prendas (Favoritos).
4.  **Proceso de Compra (En desarrollo/Planificado):**
    *   Selección de variantes -> Venta -> Actualización automática de Inventario.

---

## 📂 Directorios Clave
- `app/`: Lógica del backend (Modelos, Controladores).
- `database/migrations/`: Definición de la estructura de la base de datos.
- `resources/js/`: Código fuente del frontend (React).
- `routes/`: Definición de todos los endpoints del sistema.
- `public/`: Archivos estáticos y compilados por Vite.
