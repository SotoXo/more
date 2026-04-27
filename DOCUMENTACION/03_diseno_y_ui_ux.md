# 03. Diseño y UI/UX

## Lenguaje visual general

MORE usa un lenguaje visual oscuro, editorial y premium. La identidad pública se apoya en fondos profundos, fotografía de moda, tarjetas con bordes sutiles, transparencias, blur, tipografía serif para titulares y botones de estilo boutique.

El tono visual busca transmitir:

- Boutique premium.
- Moda curada.
- Elegancia sobria.
- Experiencia editorial.
- Sensación de marca cuidada y aspiracional.

## Storefront público

El storefront usa:

- Fondo oscuro con gradientes suaves.
- Fotografías grandes como recurso principal.
- Titulares serif de gran escala.
- Microcopy editorial.
- Botones redondeados tipo píldora.
- Chips de categoría, estado y filtros.
- Transiciones con Framer Motion.
- Carruseles con Embla Carousel.
- Iconografía Lucide para acciones como favoritos, bolsa, búsqueda y navegación.

El diseño público prioriza emoción, exploración y deseo de producto. El catálogo no se presenta como una tabla operativa, sino como una experiencia de descubrimiento.

## Panel superadmin

El panel superadmin tiene una identidad distinta: más sobria, administrativa y densa. Mantiene el fondo oscuro y el lenguaje premium, pero con una composición más funcional.

Elementos característicos:

- Sidebar persistente con grupos de navegación.
- Topbar fija.
- Métricas en tarjetas.
- Tablas de datos.
- Filtros, ordenamientos y estados de módulo.
- Badges para diferenciar CRUD real, solo lectura y no listo.
- Formularios seccionados.
- Drawers, modales, toasts y estados vacíos.

El objetivo del superadmin es permitir operación repetida, lectura rápida y control claro del estado de cada módulo.

## Diferencias entre storefront y superadmin

| Aspecto | Storefront | Superadmin |
| --- | --- | --- |
| Propósito | Inspirar y vender | Administrar y controlar |
| Composición | Editorial, visual, amplia | Densa, estructurada, operativa |
| Datos | Productos curados y filtros comerciales | Métricas, registros, estados, acciones |
| Navegación | Header superior y footer | Sidebar, topbar y breadcrumbs |
| Tono | Boutique premium | Administrativo sobrio |
| Acción principal | Explorar, guardar, agregar a bolsa | Crear, editar, consultar, filtrar |

## Decisiones UI/UX aplicadas

- El catálogo se organiza por colecciones y filtros.
- La página de producto permite decidir color y talla antes de agregar a bolsa.
- Los favoritos redirigen a login si el usuario no está autenticado.
- El superadmin muestra estado funcional del módulo para evitar confundir consulta con CRUD.
- Las prendas tienen alta guiada, no un formulario plano simple.
- Los maestros pueden crearse inline desde el editor de prenda.
- Los módulos de consulta mantienen acciones limitadas para no sugerir operaciones inexistentes.
- Las alertas y reportes se presentan como apoyo operativo, no como motor analítico completo.

## Criterio visual seguido

La identidad pública se acerca a una estética boutique, premium y editorial. La administración conserva parte de la marca, pero baja el dramatismo visual para favorecer lectura, consistencia y mantenimiento.

Hay estilos heredados en archivos como `style.css`, `home.css`, `navigation.css` y `footer.css`. El storefront actual usa principalmente los componentes nuevos de `landing` y `shop`, además de utilidades globales `more-*` en `app.css`. Es importante mantener consistencia futura para no mezclar demasiado estilos legacy con la línea visual nueva.

