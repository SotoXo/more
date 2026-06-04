# Inventario superadmin

## Objetivo

El modulo de inventario del panel superadmin permite consultar, filtrar y actualizar existencias de variantes de prendas en Mati Boutique. La pantalla principal es Existencias y la vista complementaria es Alertas de stock.

## Campos involucrados

- `inventarios.cdi_inv`: cantidad disponible para la variante.
- `inventarios.cre_inv`: cantidad reservada para la variante. En la edicion de existencias queda en solo lectura porque debe ser actualizado por la logica de reservas.
- `inventarios.ubi_inv`: ubicacion operativa. Es opcional y se normaliza al guardar.
- `inventarios.cod_prenda_variantes_inv`: variante de prenda asociada al registro de inventario.

## Estados visuales

- Disponible: `cdi_inv > 3`.
- Bajo stock: `cdi_inv > 0` y `cdi_inv <= 3`.
- Sin stock: `cdi_inv = 0`.
- Reservado: `cre_inv > 0`.

## Relacion con variantes

El inventario se registra por variante, no directamente por prenda. Cada fila de existencias cruza prenda, variante, color, talla, SKU, disponible, reservado, total calculado y ubicacion. El total operativo mostrado en el panel se calcula como `cdi_inv + cre_inv`.

## Alertas de stock

Alertas de stock ayuda a revisar:

- productos sin stock;
- productos con bajo stock;
- variantes sin inventario;
- prendas sin imagen.

## Kardex de movimientos

El historial operativo de stock se consulta en `/superadmin/inventario/movimientos`. El Kardex registra automaticamente entradas, reservas, ventas, liberaciones, ajustes manuales y cambios de ubicacion que nacen desde cambios reales de inventario.

Ver `DOCUMENTACION/22_kardex_movimientos_inventario.md`.

## Pendiente

- Reportes avanzados de rotacion y valorizacion de inventario.
