# 11. Temporadas en superadmin

## Objetivo

Incorporar las fechas de inicio y fin al CRUD de temporadas del panel superadmin para registrar, editar, listar y visualizar correctamente el periodo de vigencia de cada temporada.

El cambio no modifica rutas, migraciones, base de datos, login, storefront, dashboard ni reportes.

## Campos involucrados

- `temporadas.nom_tem`: nombre de temporada.
- `temporadas.cod_tem`: código corto.
- `temporadas.des_tem`: descripción.
- `temporadas.fec_ini_tem`: fecha de inicio.
- `temporadas.fec_fin_tem`: fecha de fin.
- `temporadas.ord_tem`: orden de visualización.
- `temporadas.est_tem`: estado activo o inactivo.

## Formulario

El formulario de crear y editar temporada muestra:

- Nombre de temporada.
- Código corto.
- Descripción.
- Fecha de inicio.
- Fecha de fin.
- Orden de visualización.
- Estado.

Las fechas se muestran como campos de fecha visibles, no como datos técnicos ocultos. El orden controla cómo aparece la temporada en listados y selectores.

## Reglas

- `fec_fin_tem` no puede ser menor que `fec_ini_tem`.
- `cod_tem` debe ser único, ignorando el registro actual al editar.
- El badge de vigencia es solo visual.
- `est_tem` no se cambia automáticamente por la fecha.

## Estados visuales

- `Temporada vigente`: la fecha actual está entre `fec_ini_tem` y `fec_fin_tem`.
- `Próxima`: la fecha actual es anterior a `fec_ini_tem`.
- `Finalizada`: la fecha actual es posterior a `fec_fin_tem`.
- `Sin periodo definido`: falta alguna fecha.

## Relación con prendas

Las prendas siguen usando `prendas.cod_temporadas_pre` para asociarse con una temporada.

En el registro y edición de prendas, el selector de temporada muestra nombre, código y periodo cuando existe, por ejemplo:

`OTOÑO-INVIERNO (OI) · 01/03/2026 - 31/08/2026`

El valor enviado al backend no cambia: se conserva `cod_temporadas_pre`.

## Pruebas recomendadas

- Crear temporada con fecha de inicio y fecha de fin.
- Editar temporada y cambiar fechas.
- Intentar guardar una fecha de fin menor a la fecha de inicio.
- Verificar que se muestra el badge `Temporada vigente`, `Próxima`, `Finalizada` o `Sin periodo definido`.
- Verificar que el listado muestra el periodo.
- Crear o editar una prenda y revisar que el selector de temporada muestra nombre, código y periodo.
- Confirmar que `cod_temporadas_pre` sigue guardándose correctamente.
