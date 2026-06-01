# Catalogo publico - logica de genero, temporada y disponibilidad

## Objetivo del cambio

Mejorar la logica del catalogo publico de Mati Boutique para que el storefront ordene y filtre prendas de forma comercial sin ocultar productos activos innecesariamente.

El cambio centraliza en `StorefrontCatalogService` la consulta publica de catalogo, colecciones por genero, accesorios, novedades, filtros iniciales, filtro de temporada y orden por prioridad de temporada.

## Genero unisex

Reglas aplicadas:

- Catalogo general: muestra todas las prendas activas, sin filtro de genero por defecto.
- Mujer: muestra prendas con `prendas.gen_pre = mujer` y `prendas.gen_pre = unisex`.
- Hombre: muestra prendas con `prendas.gen_pre = hombre` y `prendas.gen_pre = unisex`.
- Filtro explicito `genero=hombre`: incluye hombre + unisex.
- Filtro explicito `genero=mujer`: incluye mujer + unisex.
- Filtro explicito `genero=unisex`: muestra solo unisex.

No se duplican registros y no se modifica el valor almacenado en base de datos.

## Temporadas

Una temporada se considera vigente cuando:

- `temporadas.est_tem = true`
- `temporadas.fec_ini_tem <= fecha actual`
- `temporadas.fec_fin_tem >= fecha actual`

Si una temporada no tiene fechas completas, la consulta no falla; simplemente no se clasifica como vigente.

## Compatibilidad PostgreSQL

El ordenamiento por temporada usa SQL raw dentro de un `CASE`. En PostgreSQL los campos booleanos no deben compararse contra `1`, porque eso genera error de operador entre `boolean` e `integer`.

Por eso, dentro del `orderByRaw` se usa:

- `temporada_orden.est_tem IS TRUE`

La fecha actual se pasa como binding del `orderByRaw`, no concatenada en el SQL. La logica de temporada vigente y permanente se mantiene sin cambios.

El orden natural del catalogo publico es:

1. Prendas de temporada vigente.
2. Prendas de temporada permanente.
3. Prendas de proximas temporadas.
4. Prendas de temporadas finalizadas.
5. Prendas sin temporada definida.
6. Orden secundario por `temporadas.ord_tem`, `prendas.fec_pre`, `prendas.updated_at`, `prendas.created_at` e `id_prenda`.

## Prendas permanentes

Una temporada se considera permanente si:

- `temporadas.cod_tem = PER`, o
- `temporadas.nom_tem` contiene `PERMANENTE`.

Las prendas permanentes aparecen al inicio junto con la temporada vigente, porque son prendas base disponibles durante todo el ano.

## Filtro de temporada

Cuando el usuario filtra por una temporada especifica, el catalogo incluye:

- prendas de la temporada seleccionada, y
- prendas permanentes.

Cuando el usuario filtra explicitamente por permanente (`PER` o `PERMANENTE`), el catalogo muestra solo prendas permanentes.

La misma regla se replica en el filtro de cliente para evitar que React oculte permanentes despues de recibirlas del servidor.

## Campos involucrados

- `prendas.gen_pre`
- `prendas.cod_temporadas_pre`
- `temporadas.cod_tem`
- `temporadas.nom_tem`
- `temporadas.fec_ini_tem`
- `temporadas.fec_fin_tem`
- `temporadas.ord_tem`
- `temporadas.est_tem`
- `inventarios.cdi_inv`
- `inventarios.cre_inv`
- `colores.fam_col`
- `colores.nom_col`

## Disponibilidad publica

La disponibilidad se calcula con el stock disponible por variante:

- Sin stock: inventario disponible igual a 0.
- Bajo stock: inventario disponible mayor a 0 y menor o igual a 3.
- Disponible: inventario disponible mayor a 3.

En la interfaz publica se muestran etiquetas comerciales:

- `Disponible`
- `Bajo stock`
- `Sin stock`

La card puede seguir mostrando nombres de color (`colores.nom_col`), pero el filtro publico por color usa familias (`colores.fam_col`) cuando estan disponibles.
