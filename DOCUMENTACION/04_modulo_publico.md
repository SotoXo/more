# 04. Módulo público

## Alcance del módulo público

El módulo público es la experiencia visible para visitantes y clientes. Incluye landing, catálogo, colecciones, detalle de producto, favoritos, bolsa y cuenta de usuario.

## Landing

Ruta: `/`

Controlador: `HomeController`

Vista: `resources/js/Pages/Home.tsx`

La landing carga:

- Novedades desde prendas activas.
- Selección secundaria desde prendas activas.
- Categorías activas de primer nivel.
- Marcas activas.

La pantalla usa carrusel hero, tarjetas editoriales, secciones de producto y enlaces hacia catálogo, novedades y editorial.

Si un usuario superadmin autenticado entra a `/`, el sistema lo redirige al dashboard superadmin.

## Catálogo y colecciones

Rutas:

- `/catalogo`
- `/mujer`
- `/hombre`
- `/novedades`
- `/accesorios`

Controlador: `CatalogoController`

Vista: `resources/js/Pages/Shop/Collection.tsx`

El catálogo muestra prendas activas (`est_pre = true`) y usa `ProductMapper` para entregar al frontend una estructura de producto lista para renderizar.

Las colecciones aplican criterios específicos:

- Mujer: prendas activas con `gen_pre = mujer`, excluyendo categoría Accesorios.
- Hombre: prendas activas con `gen_pre = hombre`, excluyendo categoría Accesorios.
- Accesorios: prendas activas cuya categoría se llama Accesorios.
- Novedades: prendas activas ordenadas por fecha de prenda y creación.
- Catálogo: prendas activas sin segmentación adicional.

## Filtros del catálogo

El catálogo permite filtrar en frontend por:

- Categorías.
- Tallas.
- Colores.
- Temporadas o estilos.
- Marcas.
- Ubicaciones.
- Rango de precio.
- Disponibilidad.

También permite ordenamiento por recomendados, nuevos, precio ascendente y precio descendente.

## Detalle de producto

Ruta: `/producto/{id}`

Controlador: `PrendaController`

Vista: `resources/js/Pages/Shop/Product.tsx`

El detalle solo carga prendas activas. Incluye:

- Galería.
- Nombre, descripción, marca, categoría, temporada y género.
- Precio según variante seleccionada.
- Colores disponibles.
- Tallas disponibles por color.
- Disponibilidad calculada por stock.
- Favorito.
- Acción de agregar a bolsa.
- Productos relacionados por categoría.
- Selección para completar el look.

## Favoritos

Ruta de acción: `POST /api/favoritos/toggle`

Ruta de cuenta: `/cuenta/favoritos`

Controlador: `FavoritoController`

Los favoritos requieren usuario autenticado. El sistema crea o elimina la relación entre usuario y prenda. En la vista de cuenta se listan las prendas favoritas activas, conservando el orden de guardado.

Si un visitante intenta usar favoritos desde el frontend, se lo redirige a login.

## Bolsa

Ruta: `/bolsa`

API:

- `POST /api/carrito/agregar`
- `PATCH /api/carrito/{varianteId}`
- `DELETE /api/carrito/{varianteId}`
- `DELETE /api/carrito`

Controlador: `CarritoController`

Vista: `resources/js/Pages/Carrito.tsx`

La bolsa funciona con sesión, no requiere autenticación. Permite:

- Agregar una variante.
- Validar que exista stock.
- Limitar cantidad de agregado entre 1 y 10.
- Ajustar cantidad.
- Eliminar ítems.
- Vaciar la bolsa.
- Calcular subtotal visual.

Estado parcial: la bolsa no ejecuta checkout real. El botón de checkout existe visualmente, pero no hay flujo de pago, creación de venta ni descuento automático de inventario desde esa acción.

## Cuenta de usuario

Rutas:

- `/cuenta`
- `/cuenta/pedidos`
- `/cuenta/reservas`
- `/cuenta/favoritos`

Controlador: `CuentaController`

El módulo de cuenta muestra resumen, pedidos, reservas y favoritos del usuario autenticado. Pedidos y reservas consultan tablas existentes, pero el sistema público todavía no genera ventas o reservas desde un flujo finalizado de ecommerce.

## Login y registro

El sistema usa rutas de Laravel Breeze adaptadas. Login redirige:

- Superadmin a `/superadmin`.
- Usuario común a `/` o al destino permitido.

El registro crea usuarios con rol cliente, si el rol cliente existe en la base de datos.

## Estado actual

Completado o avanzado:

- Landing pública.
- Catálogo navegable.
- Detalle de producto.
- Favoritos autenticados.
- Bolsa de sesión.
- Cuenta de usuario en lectura.

Parcial:

- Checkout.
- Confirmación de compra.
- Pago.
- Generación de venta desde bolsa.
- Reservas generadas desde storefront.
- Newsletter del footer, actualmente visual/preventivo sin envío.

