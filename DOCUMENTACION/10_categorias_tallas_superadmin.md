# 10. Categorías y sugerencia de tallas en superadmin

## Objetivo del cambio

Conectar `categorias.gru_tal_cat` con el registro y edición de prendas para que el módulo superadmin sugiera tallas según el sistema y el grupo definidos en la categoría seleccionada.

El cambio no modifica rutas, migraciones, base de datos, login, storefront, dashboard ni reportes. La selección de talla en variantes sigue enviando el mismo valor operativo al backend: `prenda_variantes.cod_tallas_prv`.

## Campos involucrados

- `categorias.tip_tal_cat`: sistema de talla sugerido para la categoría.
- `categorias.gru_tal_cat`: grupo de talla sugerido para la categoría.
- `tallas.sis_tal`: sistema asociado a la talla.
- `tallas.gru_tal`: grupo asociado a la talla.
- `prenda_variantes.cod_tallas_prv`: talla seleccionada y persistida en la variante.

## Funcionamiento

Cuando el superadmin crea o edita una prenda, el formulario lee la categoría seleccionada y usa `tip_tal_cat` junto con `gru_tal_cat` para ordenar y destacar las tallas recomendadas.

La coincidencia principal se calcula con:

- `tallas.sis_tal = categorias.tip_tal_cat`
- `tallas.gru_tal = categorias.gru_tal_cat`

Ejemplos esperados:

- Si la categoría es PANTALONES, se sugieren primero tallas `NUMERICA / INFERIOR`.
- Si la categoría es POLERA, se sugieren primero tallas `ALFA / SUPERIOR`.
- Si la categoría es ACCESORIOS, se sugiere `UNICA / GENERAL`.

Si no hay coincidencias exactas entre categoría y tallas, el sistema muestra las tallas disponibles sin bloquear el registro. La sugerencia es una ayuda visual y operativa, no una validación restrictiva.

## Selección visual de tallas

El selector de tallas del editor de prendas muestra las opciones agrupadas por sistema y grupo, por ejemplo:

- `ALFA / SUPERIOR`: XS, S, M, L, XL.
- `NUMERICA / INFERIOR`: 32, 34, 36, 38, 40.
- `UNICA / GENERAL`: UNITALLA.

Las tallas recomendadas por la categoría aparecen primero y se destacan visualmente como recomendadas. Aun así, el valor enviado al backend no cambia: se conserva `cod_tallas_prv`.

## Archivos modificados

- `app/Models/Categoria.php`
- `app/Services/Superadmin/Resources/ResourceOptionsService.php`
- `app/Services/Superadmin/Resources/ResourceDetailViewService.php`
- `app/Services/Superadmin/Resources/ResourceValidationService.php`
- `app/Services/Superadmin/Resources/ResourceWriteService.php`
- `app/Services/Superadmin/Resources/ProductCatalogService.php`
- `resources/js/types/superadmin.ts`
- `resources/js/Components/Superadmin/SuperadminComboboxField.tsx`
- `resources/js/Pages/Superadmin/ProductCreate.tsx`

## Pruebas recomendadas

- Crear o editar una categoría con sistema y grupo de talla.
- Crear una prenda con categoría PANTALONES y verificar sugerencias `NUMERICA / INFERIOR`.
- Crear una prenda con categoría POLERA y verificar sugerencias `ALFA / SUPERIOR`.
- Crear una prenda con categoría ACCESORIOS y verificar sugerencias `UNICA / GENERAL`.
- Guardar una variante y verificar que `cod_tallas_prv` se mantiene correctamente.
- Editar una variante existente y confirmar que la talla seleccionada se conserva.

## Verificación técnica

Durante la implementación se ejecutaron las siguientes verificaciones:

- `./vendor/bin/sail artisan route:list` pasó correctamente y mostró 56 rutas.
- `npm run build` pasó correctamente con `tsc && vite build`.
- `php -l` vía Sail pasó en los archivos PHP modificados.

## Actualización del CRUD de tallas

### Objetivo

Actualizar el CRUD propio de tallas en superadmin para manejar de forma clara el segmento, el grupo de uso y el sistema de talla. Esto permite que las tallas creadas o editadas desde el maestro alimenten correctamente las sugerencias del registro y edición de prendas.

La actualización incorpora `seg_tal` y el cálculo automático de `ord_tal`.

### Campos involucrados

- `tallas.nom_tal`: nombre visible de la talla.
- `tallas.seg_tal`: segmento de talla (`ADULTO`, `INFANTIL`, `BEBE`).
- `tallas.sis_tal`: sistema de talla.
- `tallas.gru_tal`: grupo de uso.
- `tallas.ord_tal`: orden automático para visualización.
- `tallas.est_tal`: estado activo o inactivo.

### Flujo del formulario

El flujo correcto para crear o editar una talla es:

1. Segmento.
2. Grupo de uso.
3. Sistema de talla.
4. Nombre de talla.
5. Orden automático.
6. Estado.

El usuario no define manualmente `ord_tal`; el backend lo recalcula al guardar.

### Relación con categorías

La sugerencia de tallas en prendas cruza los metadatos de categoría con los metadatos de talla:

- `categorias.tip_tal_cat` se compara con `tallas.sis_tal`.
- `categorias.gru_tal_cat` se compara con `tallas.gru_tal`.
- Como las categorías actuales no tienen segmento, el registro de prendas prioriza `tallas.seg_tal = ADULTO` por defecto.

Cuando los valores coinciden, esas tallas aparecen primero y destacadas en el selector de variantes. El valor que se guarda en la variante sigue siendo `prenda_variantes.cod_tallas_prv`.

### Ejemplos

- `ADULTO / SUPERIOR / ALFA / M` → `40`
- `ADULTO / INFERIOR / NUMERICA / 38` → `1380`
- `ADULTO / CALZADO / CALZADO_US / 8.5 US` → `3085`
- `INFANTIL / GENERAL / EDAD / 4A` → `5040`
- `BEBE / GENERAL / MESES / 6-9M` → `7009`

### Reglas de orden automático

- `ALFA`: usa un mapa natural, por ejemplo `XXXS = 5`, `XXS = 10`, `XS = 20`, `S = 30`, `M = 40`, `L = 50`, `XL = 60`, `XXL = 70`.
- `NUMERICA`: `ord_tal = 1000 + numero * 10`.
- `CALZADO_US`: soporta medias tallas, por ejemplo `8.5 US = 3085`.
- `CALZADO_EU`: `36 EU = 4036`, `38 EU = 4038`.
- `EDAD`: `4A = 5040`.
- `ALTURA_CM`: `104 CM = 6104`.
- `MESES`: toma el límite superior, por ejemplo `6-9M = 7009`.
- `UNICA`: `UNITALLA`, `UNICA` y `ÚNICA` usan orden `10`.
- Si el formato no se reconoce, se usa `9999` como orden seguro.

El orden natural de tallas no depende de la demanda comercial. La demanda puede utilizarse después en reportes o recomendaciones, pero no para ordenar el maestro de tallas.

### Formulario de tallas

El formulario de crear y editar talla muestra:

- Segmento.
- Grupo de uso.
- Sistema de talla.
- Nombre de talla.
- Orden automático.
- Estado.

El conteo de variantes asociadas no se edita desde el formulario principal; se conserva como información de listado o detalle.

### Pruebas recomendadas

- Crear una talla `ADULTO / SUPERIOR / ALFA / M` y verificar orden `40`.
- Crear una talla `ADULTO / INFERIOR / NUMERICA / 38` y verificar orden `1380`.
- Crear una talla `ADULTO / CALZADO / CALZADO_US / 8.5 US` y verificar orden `3085`.
- Crear una talla `INFANTIL / GENERAL / EDAD / 4A` y verificar orden `5040`.
- Crear una talla `BEBE / GENERAL / MESES / 6-9M` y verificar orden `7009`.
- Editar una talla existente y confirmar que recalcula el orden.
- Verificar que la talla aparece correctamente en el registro de prendas según la categoría seleccionada.

## Segmento de talla en registro y edición de prendas

### Objetivo

Conectar el segmento de tallas con el formulario de registro y edición de prendas, sin crear campos nuevos en `prendas`. El segmento se usa como estado del formulario para ordenar, filtrar y destacar las tallas que se ofrecen al cargar variantes.

### Campos involucrados

- `tallas.seg_tal`: segmento de la talla (`ADULTO`, `INFANTIL`, `BEBE`).
- `tallas.sis_tal`: sistema de talla.
- `tallas.gru_tal`: grupo de uso.
- `tallas.ord_tal`: orden natural calculado para mostrar tallas.
- `categorias.tip_tal_cat`: sistema sugerido por categoría.
- `categorias.gru_tal_cat`: grupo sugerido por categoría.
- `prenda_variantes.cod_tallas_prv`: talla guardada en cada variante.

### Flujo

1. El superadmin selecciona el segmento de talla de la prenda.
2. El superadmin selecciona la categoría.
3. El sistema cruza `tallas.seg_tal`, `tallas.sis_tal` y `tallas.gru_tal` con el segmento seleccionado y los campos `categorias.tip_tal_cat` y `categorias.gru_tal_cat`.
4. El selector muestra primero las tallas recomendadas y agrupadas por segmento, grupo de uso y sistema.
5. Al guardar la variante se conserva el valor operativo `prenda_variantes.cod_tallas_prv`.

### Comportamiento de sugerencias

- `ADULTO + PANTALONES` recomienda tallas `ADULTO / INFERIOR / NUMERICA`.
- `ADULTO + POLERA` recomienda tallas `ADULTO / SUPERIOR / ALFA`.
- `INFANTIL` prioriza tallas infantiles, como `GENERAL / EDAD` o `GENERAL / ALTURA_CM`.
- `BEBE` prioriza tallas de bebé, como `GENERAL / MESES`.

Si no hay coincidencia exacta entre segmento, sistema y grupo, el formulario no bloquea el registro. En ese caso muestra tallas activas del segmento seleccionado; si tampoco existen, muestra todas las tallas activas disponibles e informa que no hay tallas recomendadas exactas para la categoría.

### Edición de prendas

Al editar una prenda existente, el formulario detecta el segmento predominante desde las tallas ya usadas en sus variantes. Si no puede detectarlo, usa `ADULTO` por defecto. Este valor solo afecta las sugerencias nuevas: no borra variantes, no reemplaza tallas ya guardadas y no cambia `cod_tallas_prv`.

### Aclaración

Por ahora el segmento se usa solo para sugerir tallas en el formulario. Si más adelante se necesita persistir el segmento propio de la prenda, se recomienda agregar un campo específico en `prendas`, pero esta tarea no crea migraciones ni modifica la base de datos.

### Pruebas recomendadas

- Crear prenda con segmento `ADULTO` y categoría PANTALONES; verificar sugerencia `NUMERICA / INFERIOR`.
- Crear prenda con segmento `ADULTO` y categoría POLERA; verificar sugerencia `ALFA / SUPERIOR`.
- Crear prenda con segmento `INFANTIL`; verificar que aparezcan tallas `EDAD` y `ALTURA_CM`.
- Crear prenda con segmento `BEBE`; verificar que aparezcan tallas `MESES`.
- Guardar variante y confirmar que `cod_tallas_prv` se mantiene correctamente.
- Editar una prenda existente y confirmar que las variantes actuales no se alteran.
