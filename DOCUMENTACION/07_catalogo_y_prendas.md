# 07. Catálogo y prendas

## Prendas como centro del catálogo

La entidad `Prenda` es el centro del catálogo. Una prenda agrupa:

- Nombre.
- Descripción.
- Categoría.
- Marca.
- Temporada.
- Género.
- Precio base.
- Estado visible/oculto.
- Usuario que registra.
- Fecha de registro.
- Variantes.
- Imágenes.
- Favoritos.

El storefront no muestra maestros aislados; muestra prendas activas transformadas por `ProductMapper`.

## Maestros relacionados

### Categorías

Permiten clasificar prendas y definir un perfil de talla:

- `ALFA`
- `NUMERICA`
- `UNITALLA`

También soportan jerarquía con categoría padre.

### Marcas

Identifican la firma o marca comercial asociada a la prenda.

### Colores

Definen colores para variantes. Manejan:

- Nombre.
- Familia.
- HEX específico.
- HEX dominante.
- Orden.
- Estado.

El HEX específico se usa para swatches y visualización. El HEX dominante permite agrupar o representar el color de forma más general.

### Tallas

Definen tallas disponibles. Manejan:

- Nombre.
- Sistema.
- Grupo.
- Orden.
- Estado.

El sistema infiere metadatos de talla para apoyar la organización.

### Temporadas

Agrupan prendas por edición, temporada o línea comercial. Incluyen nombre, código, descripción, orden y estado.

## Variantes

Una variante representa una combinación comercial de:

- Prenda.
- Color.
- Talla.
- Código interno `sku_prv`.
- Código adicional `cob_prv`.
- Precio de venta.
- Costo interno opcional.
- Estado.
- Inventario.

Las variantes son necesarias para que el detalle de producto permita seleccionar color/talla y para que la bolsa agregue un ítem concreto.

## Inventario

El inventario se registra por variante, no directamente por prenda. Cada inventario contiene:

- Variante.
- Cantidad disponible o inicial (`cdi_inv`).
- Cantidad reservada (`cre_inv`).
- Ubicación (`DEPOSITO` o `TIENDA`).

La disponibilidad se calcula como:

`max(0, cdi_inv - cre_inv)`

## Imágenes

Las imágenes se relacionan con una prenda y opcionalmente con una variante. Manejan:

- Ruta interna.
- URL pública.
- Indicador de imagen principal.
- Orden.
- Fuente.
- Fecha.

El sistema garantiza que exista una imagen principal por prenda cuando hay imágenes disponibles. Si se marca una imagen como principal, las demás de la misma prenda se desmarcan.

## Imagen principal y galería

La imagen principal identifica la prenda en catálogo y detalle. La galería se ordena por:

1. Imagen principal primero.
2. Orden ascendente.

En creación de prenda, la imagen principal es obligatoria. En edición, se permite conservar imágenes existentes, subir nuevas o cambiar cuál es principal, pero no dejar la prenda sin imagen identificable.

## Prenda única vs prenda con variantes

El editor maneja el campo `usa_variantes` para diferenciar:

- **Prenda única:** debe quedar una sola configuración activa.
- **Prenda con variantes:** debe registrar al menos dos configuraciones.

Aunque se hable de prenda única, internamente sigue existiendo una variante, porque inventario, color, talla y bolsa dependen de variante.

## Códigos internos

El sistema genera códigos internos a partir de la categoría o nombre de la prenda:

- Prefijo de tres letras.
- ID de prenda con relleno.
- Sufijo incremental cuando hay múltiples variantes.

Ejemplo conceptual: `CAM-00015-02`.

## Flujo de alta de prenda

1. El superadmin entra a nueva prenda.
2. Define datos base: nombre, descripción, categoría, marca, temporada, género, precio y estado.
3. Sube imagen principal.
4. Agrega imágenes de galería si corresponde.
5. Define si usa variantes.
6. Registra color, talla, precio, costo opcional, stock inicial y ubicación por variante.
7. El sistema valida duplicados y consistencia.
8. Se guarda la prenda, variantes, inventario e imágenes dentro de transacción.

## Flujo público de producto

1. El cliente entra al catálogo o colección.
2. El sistema muestra prendas activas.
3. El cliente abre un detalle.
4. El detalle carga variantes activas e inventario.
5. El cliente selecciona color.
6. El sistema acota tallas disponibles para ese color.
7. Se muestra disponibilidad por variante.
8. El cliente puede guardar favorito o agregar a bolsa.

## Estado del catálogo

El catálogo es una de las partes más maduras del sistema. La administración de prendas, variantes, imágenes e inventario inicial está avanzada. Aún falta cerrar el flujo transaccional posterior: reservas reales desde tienda, checkout, venta y movimientos de inventario.

