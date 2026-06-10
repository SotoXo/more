# 35. Correccion de color en registro de prendas

## 1. Alcance

Esta correccion ajusta el flujo de color en `ProductCreate.tsx` sin migraciones, sin paquetes nuevos y sin cambios en `ResourceController`.

El color de una prenda se persiste en las filas comerciales de variantes mediante:

```ts
variant_rows[].cod_colores_prv
```

No se persiste como atributo directo de `prendas`.

## 2. Causa

El registro de prendas ya usaba `variant_rows` como fuente de verdad para color, talla, precio, stock y ubicacion. Sin embargo, algunas acciones creaban nuevas filas con `cod_colores_prv` vacio o no aplicaban el color sugerido cuando no habia una fila activa resuelta.

Eso podia provocar que el flujo pidiera color nuevamente despues de aplicar o crear un color desde la imagen.

## 3. Ajuste aplicado

Se centralizo la escritura de valores de variante para que el color se aplique sobre la primera fila activa.

Tambien se agrego un sembrado comercial para que nuevas filas conserven el color base cuando corresponde:

- Color sugerido desde imagen.
- Color creado desde una muestra.
- Seleccion manual de color comercial.
- Activacion de variantes.
- Nueva fila de variante.
- Tallas sugeridas generadas automaticamente.
- Retorno a prenda unica.

## 4. Comportamiento esperado

En prenda unica, el color queda en la primera fila activa de `variant_rows` y la validacion comercial deja de pedirlo despues de aplicarlo.

En prendas con variantes, las filas nuevas toman el color base si todavia no tienen uno. Las filas que el usuario cambie manualmente conservan su color.

## 5. Verificacion

Se debe verificar:

- `npm run build`
- Crear una prenda unica, aplicar color sugerido y avanzar sin que se vuelva a pedir color.
- Crear color desde una muestra y confirmar que queda seleccionado.
- Activar variantes y confirmar que las nuevas filas conservan el color base.
- Aplicar tallas sugeridas y confirmar que las filas generadas mantienen el color.
