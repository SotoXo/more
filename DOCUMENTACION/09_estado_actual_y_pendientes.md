# 09. Estado actual y pendientes

## Partes bien resueltas

### Catálogo público

El catálogo público está avanzado. Permite navegar colecciones, filtrar productos, ordenar resultados, ver disponibilidad, colores, tallas, marcas y temporadas.

### Detalle de producto

El detalle de producto está bien estructurado. Usa variantes reales, calcula disponibilidad, permite seleccionar color y talla, gestiona favoritos y agrega a bolsa.

### Favoritos

Favoritos está funcional para usuarios autenticados. Tiene persistencia en base de datos y vista de cuenta.

### Bolsa de sesión

La bolsa funciona como carrito temporal. Permite agregar, actualizar y eliminar ítems de sesión con validación de stock.

### Superadmin de catálogo

El panel superadmin tiene un núcleo sólido para:

- Prendas.
- Variantes.
- Imágenes.
- Categorías.
- Marcas.
- Colores.
- Tallas.
- Temporadas.

El editor de prendas es la pieza más completa del sistema administrativo.

### Seguridad superadmin

La protección de acceso y reglas críticas del superadmin están bien cubiertas: usuarios activos, roles activos, rol superadmin, autoprotección y último superadmin activo.

## Partes parciales

### Checkout

Existe bolsa y botón de checkout, pero no hay checkout funcional. No hay confirmación de compra, pago, creación de venta desde bolsa ni actualización transaccional de inventario.

### Reservas públicas

El sistema muestra rutas y vistas de reservas en cuenta, y existen tablas de reservas, pero no hay un flujo público completo que cree reservas desde el storefront.

### Ventas

Existen modelos, migraciones, consultas y vistas administrativas de ventas, pero no un flujo completo para registrar ventas desde el checkout o desde un módulo vendedor.

### Inventario

El inventario está conectado a variantes y permite consulta de existencias, reservado y alertas. Sin embargo, no hay movimientos de inventario, kardex, ajustes reales ni auditoría de entradas/salidas.

### Reportes

Reportes ofrece una vista filtrable útil sobre variantes, precios y stock. No exporta archivos ni representa todavía un sistema analítico completo.

### Logs

La bitácora muestra actividad reciente agregada desde varias tablas. No es una auditoría real de cambios, no registra usuario, acción, antes/después ni trazabilidad detallada.

### Newsletter

El formulario del footer existe visualmente y previene el envío por defecto, pero no registra suscripciones ni envía correos.

## Partes que no deben venderse como terminadas

No deben presentarse como completas:

- Checkout.
- Pasarela de pago.
- Confirmación de pedidos.
- Generación automática de ventas desde bolsa.
- Reservas desde tienda pública.
- Módulo vendedor.
- Movimientos de inventario.
- Ajustes de inventario.
- Auditoría completa.
- Newsletter funcional.
- Reportería exportable o BI completo.

## Inconsistencias o puntos a revisar

### Referencias visuales heredadas

Existen estilos y componentes antiguos o de transición, como `Navigation.jsx`, `style.css`, `home.css`, `navigation.css` y `footer.css`. El storefront actual usa principalmente `LandingHeader`, `LandingFooter`, componentes `shop` y estilos globales `more-*`. Conviene limpiar o documentar qué archivos son legacy para evitar confusión futura.

### Ruta antigua de carrito

El componente `Navigation.jsx` usa `/cart`, pero la ruta real del sistema es `/bolsa`. El header actual de landing usa `/bolsa`, por lo que la inconsistencia parece estar en un componente anterior o no principal. Debe revisarse antes de reutilizar ese componente.

### Estados comerciales

Ventas y reservas existen como tablas y vistas de consulta. Falta definir si se crearán desde checkout público, panel vendedor, superadmin o ambos.

### Codificación visual en algunos textos

En la salida de terminal algunos textos aparecen con caracteres mal representados por la consola. Los archivos fuente parecen contener español con acentos, pero conviene revisar codificación en el editor si aparecen caracteres extraños en UI.

## Próximos pasos recomendados

1. Definir flujo de checkout: datos requeridos, método de pago, creación de venta, confirmación y estado inicial.
2. Definir si la bolsa genera venta directa o reserva.
3. Implementar transacción de checkout con validación final de stock.
4. Registrar descuento o reserva de inventario de forma consistente.
5. Crear módulo de movimientos de inventario antes de habilitar ajustes reales.
6. Definir panel vendedor si el rol vendedor tendrá operación propia.
7. Implementar auditoría real para cambios críticos en usuarios, roles, prendas, variantes e inventario.
8. Revisar y limpiar componentes o estilos legacy.
9. Agregar pruebas específicas para reglas críticas: último superadmin, roles del sistema, variantes duplicadas, stock y checkout cuando se implemente.
10. Documentar procedimientos operativos para alta de prendas, control de imágenes y mantenimiento de maestros.

## Estado global

El sistema se encuentra en una fase sólida de catálogo y administración superadmin, con storefront funcional para exploración y precompra. El cierre comercial todavía está pendiente. La presentación del proyecto debe enfatizar que MORE ya tiene una base seria para catálogo boutique y operación administrativa, pero que el ecommerce transaccional completo requiere una siguiente etapa.

