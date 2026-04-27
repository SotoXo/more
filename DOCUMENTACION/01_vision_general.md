# 01. Visión general del sistema

## Qué es MORE

MORE es una plataforma web para una boutique de moda, orientada a combinar experiencia de ecommerce con administración interna de catálogo. El sistema está construido alrededor de prendas, variantes, imágenes, inventario y usuarios con roles.

El proyecto tiene dos grandes superficies:

- **Módulo público o storefront:** experiencia visual de tienda para visitantes y clientes.
- **Módulo superadmin:** panel administrativo protegido para operación y mantenimiento del sistema.

## Objetivo del sistema

El objetivo actual es permitir que una boutique pueda presentar prendas con una experiencia visual premium, organizar el catálogo por categorías, marcas, temporadas, colores y tallas, y administrar la información central desde un panel interno.

Hasta este punto, el sistema está más avanzado en catálogo, gestión de prendas y control superadmin que en cierre transaccional completo de ecommerce.

## Módulos actuales

### Módulos públicos

- Landing principal.
- Catálogo general.
- Colecciones por mujer, hombre, novedades y accesorios.
- Página editorial y página de colecciones curadas.
- Detalle de producto.
- Favoritos para usuarios autenticados.
- Bolsa basada en sesión.
- Cuenta de usuario con vistas de resumen, pedidos, reservas y favoritos.
- Login, registro, recuperación de contraseña y verificación de email heredados de Laravel Breeze y personalizados visualmente.

### Módulos administrativos

- Dashboard superadmin.
- Perfil superadmin.
- Roles.
- Usuarios.
- Categorías.
- Marcas.
- Colores.
- Tallas.
- Temporadas.
- Prendas.
- Variantes.
- Imágenes.
- Consulta de inventario.
- Alertas de inventario.
- Ajustes sugeridos de inventario.
- Consulta de ventas.
- Consulta de reservas.
- Favoritos guardados.
- Reportes de consulta.
- Bitácora o logs agregados.

## Alcance actual

El sistema permite navegar el storefront, consultar productos activos, filtrar el catálogo, revisar detalle de producto, seleccionar color y talla, guardar favoritos y agregar variantes a una bolsa de sesión.

En administración, permite CRUD real en roles, usuarios, maestros de catálogo y prendas. Las prendas incluyen creación guiada con imagen principal, galería, variantes e inventario inicial. Las variantes e imágenes también existen como recursos secundarios con gestión propia.

Ventas, reservas, reportes, logs e inventario tienen soporte de consulta, métricas y visualización, pero no constituyen todavía un flujo comercial completo de venta, checkout, pago, despacho o movimiento de inventario.

## Enfoque ecommerce

La parte ecommerce se concentra en:

- Presentación visual de marca y prendas.
- Catálogo navegable por colección.
- Filtros por categoría, talla, color, marca, temporada, ubicación, precio y disponibilidad.
- Detalle con galería, variantes, disponibilidad, precio, productos relacionados y piezas para completar el look.
- Favoritos persistentes por usuario.
- Bolsa en sesión con agregar, actualizar, eliminar y vaciar.

No debe venderse todavía como ecommerce completo, porque no hay checkout funcional, integración de pagos, confirmación de compra ni generación de venta desde la bolsa.

## Enfoque administrativo

La parte administrativa se concentra en:

- Control de acceso por rol superadmin.
- Mantenimiento de usuarios internos y clientes.
- Gestión de maestros de catálogo.
- Alta y edición de prendas con variantes, imágenes e inventario.
- Consulta operativa de inventario, ventas, reservas y favoritos.
- Reportes filtrables sobre variantes y existencias.

El panel superadmin sí representa el núcleo más maduro del sistema, especialmente para catálogo.

