# 12. Colores en superadmin

## Objetivo

Automatizar el alta y edición de colores para que el administrador seleccione el color exacto desde una paleta visual y el sistema sugiera o genere nombre, familia, color dominante y orden.

El cambio no modifica rutas, migraciones, base de datos, login, storefront, dashboard ni reportes.

## Campos involucrados

- `colores.nom_col`: nombre específico visible del color.
- `colores.fam_col`: familia principal usada para filtros.
- `colores.hex_col`: color exacto en formato hexadecimal.
- `colores.hex_dom_col`: color dominante o base de la familia.
- `colores.ord_col`: orden de visualización.
- `colores.est_col`: estado activo o inactivo.

## Concepto

- `nom_col` identifica el tono específico y se genera automáticamente, por ejemplo `VERDE BOSQUE OSCURO`, `AZUL MARINO` o `ROSA PALO`.
- `fam_col` agrupa colores similares para filtros, por ejemplo `AZUL`.
- `hex_col` representa el color exacto seleccionado desde la paleta visual.
- `hex_dom_col` representa el color dominante/base asignado por la familia.
- `ord_col` se calcula automáticamente según la familia.

## Funcionamiento

1. El usuario selecciona `hex_col` desde la paleta visual.
2. El sistema sugiere `fam_col` usando tono, saturación y luminosidad para evitar que colores muy oscuros se clasifiquen automáticamente como negro.
3. El sistema compara el color contra una tabla interna de tonos conocidos por familia.
4. El sistema asigna `hex_dom_col` con el HEX base de la familia detectada.
5. El sistema genera `nom_col` con el tono conocido más cercano.
6. Si no encuentra un tono cercano, usa familia más un modificador simple como `CLARO`, `OSCURO`, `SUAVE` o `INTENSO`.
7. El sistema genera `ord_col` según la familia y los colores existentes.

El backend recalcula estos datos antes de validar y guardar, por lo que el flujo no depende únicamente del frontend.

## Corrección de guardado automático

El formulario mantiene `nom_col`, `hex_dom_col` y `ord_col` como campos automáticos ocultos dentro del payload. Aunque no se muestran como inputs manuales, viajan con el formulario y el backend vuelve a generarlos antes de validar.

Esto corrige el caso en que la vista previa mostraba los valores automáticos, pero el guardado podía fallar si alguno de esos datos no estaba presente en el estado del formulario.

## Familias de color

El CRUD usa una paleta base de familias:

- `NEGRO` -> `#111111`
- `BLANCO` -> `#F7F6F2`
- `GRIS` -> `#8A8F98`
- `BEIGE` -> `#D8C3A5`
- `CAMEL` -> `#B88956`
- `MARRON` -> `#6F4E37`
- `AZUL` -> `#1D4ED8`
- `CELESTE` -> `#6F9FD8`
- `VERDE` -> `#16A34A`
- `ROJO` -> `#B91C1C`
- `BORGONA` -> `#8A2332`
- `ROSA` -> `#D28FA0`
- `AMARILLO` -> `#EAB308`
- `NARANJA` -> `#EA580C`
- `MORADO` -> `#7E22CE`
- `DORADO` -> `#C8A96A`
- `PLATEADO` -> `#C0C0C0`

## Tonos conocidos

Además de la familia, el sistema mantiene una tabla interna de tonos específicos. Algunos ejemplos:

- `VERDE`: `VERDE BOSQUE OSCURO`, `VERDE BOTELLA`, `VERDE PINO OSCURO`, `VERDE OLIVA`, `VERDE ESMERALDA`, `VERDE MENTA`, `VERDE SALVIA`, `VERDE LIMON`.
- `AZUL`: `AZUL MARINO`, `AZUL PETROLEO`, `AZUL NOCHE`, `AZUL REY`, `AZUL CIELO`, `CELESTE`, `TURQUESA`.
- `ROJO`: `ROJO VINO`, `ROJO CEREZA`, `ROJO RUBI`, `ROJO LADRILLO`, `ROJO INTENSO`.
- `ROSA`: `ROSA PALO`, `ROSA PASTEL`, `ROSA FUCSIA`, `ROSA EMPOLVADO`.
- `BEIGE`, `CAMEL`, `MARRON`: `CREMA`, `BEIGE ARENA`, `BEIGE CLARO`, `CAMEL`, `MARRON CHOCOLATE`, `MARRON CAFE`, `MARRON TABACO`.
- `GRIS`: `GRIS PERLA`, `GRIS CEMENTO`, `GRIS ACERO`, `GRIS OSCURO`.
- `MORADO`: `LILA`, `LAVANDA`, `MORADO UVA`, `MORADO OSCURO`.
- `NEGRO` y `BLANCO`: `NEGRO`, `NEGRO CARBON`, `BLANCO`, `BLANCO HUESO`, `MARFIL`.

Por ejemplo, `#0D2608` debe caer en familia `VERDE` y sugerir un nombre específico como `VERDE BOSQUE OSCURO`, no solo `VERDE OSCURO`.

## Orden automático

`ord_col` no se muestra como campo manual en crear o editar. El backend calcula el orden según una base por familia:

- `NEGRO`: `100`
- `BLANCO`: `200`
- `GRIS`: `300`
- `BEIGE`: `400`
- `CAMEL`: `500`
- `MARRON`: `600`
- `AZUL`: `700`
- `CELESTE`: `750`
- `VERDE`: `800`
- `ROJO`: `900`
- `BORGONA`: `950`
- `ROSA`: `1000`
- `AMARILLO`: `1100`
- `NARANJA`: `1200`
- `MORADO`: `1300`
- `DORADO`: `1400`
- `PLATEADO`: `1500`

Si ya existen colores dentro de la misma familia, se usa el siguiente orden disponible en incrementos de `10`. Al editar un color que conserva la misma familia, se mantiene su orden actual.

## Flujo del formulario

El formulario principal muestra:

- Selector visual de color exacto.
- Vista previa con nombre sugerido, familia, color exacto y dominante.
- Selector visual de familia de color.
- Estado.

No se muestran como campos manuales `nom_col`, `hex_dom_col`, `ord_col`, variantes asociadas ni fecha de alta. Esos datos se generan, se muestran en listado/detalle o se conservan como información técnica secundaria.

## Ejemplos

- `VERDE BOSQUE OSCURO / VERDE / #0D2608 / #16A34A`
- `AZUL MARINO / AZUL / #1F2A44 / #1D4ED8`
- `CAMEL / CAMEL / #D3A478 / #B88956`
- `ROSA PALO / ROSA / #D8A7B1 / #D28FA0`
- `VERDE OLIVA / VERDE / #66704A / #16A34A`

## Integración con registro de prendas

El registro y edición de prendas reutiliza la misma lógica de colores para que el administrador no tenga que salir al CRUD de colores antes de crear una variante.

Campos involucrados:

- `colores.nom_col`
- `colores.fam_col`
- `colores.hex_col`
- `colores.hex_dom_col`
- `colores.ord_col`
- `prenda_variantes.cod_colores_prv`

Flujo:

1. En una variante, el usuario puede seleccionar un color existente desde una paleta visual agrupada por familia.
2. El selector permite buscar por nombre de color, familia o HEX.
3. Si el color no existe, el usuario puede elegir `Crear color`.
4. El sistema permite seleccionar el color exacto con picker o tomarlo desde la imagen principal de la prenda.
5. A partir de `hex_col`, el sistema genera `nom_col`, detecta `fam_col`, asigna `hex_dom_col` y calcula `ord_col`.
6. Antes de crear un registro nuevo, el backend busca si ya existe un color con el mismo `hex_col`.
7. Si no hay coincidencia exacta, busca un color muy parecido dentro de la misma `fam_col` usando distancia RGB baja.
8. Si existe, reutiliza ese color y asigna su id a `prenda_variantes.cod_colores_prv`.
9. Si no existe, crea el color y asigna el nuevo id a `prenda_variantes.cod_colores_prv`.

No se crea tabla `prenda_variante_colores` y no se manejan colores secundarios o terciarios. Cada variante mantiene un solo color principal mediante `cod_colores_prv`.

Mensajes esperados:

- `Color existente encontrado`: el color se reutiliza y no se duplica.
- `Nuevo color detectado`: el color se creará al confirmar.
- `No se pudo guardar el nuevo color`: error general si falla la creación inline.

## Pruebas recomendadas

- Crear color usando el selector visual.
- Crear `#D3A478` y verificar que se guarda como `CAMEL` o tono camel equivalente.
- Verificar que `hex_col` se guarde automáticamente.
- Verificar que `nom_col` se genere automáticamente.
- Verificar que `fam_col` se sugiera automáticamente.
- Verificar que `hex_dom_col` se asigne según la familia.
- Verificar que `ord_col` se guarde sin input manual.
- Editar un color existente.
- Cambiar color exacto y verificar que se actualice la sugerencia.
- Confirmar que variantes no aparece en crear/editar.
- Revisar listado y detalle de color.
- Crear prenda, agregar variante y seleccionar un color existente desde swatch.
- Crear prenda, capturar `#0D2608` desde imagen y verificar que se crea o reutiliza como color verde específico.
- Capturar un color igual a uno existente y verificar que no se duplica.
- Confirmar que la variante guarda `cod_colores_prv`.
- Confirmar que `ResourceController` sigue delgado.
