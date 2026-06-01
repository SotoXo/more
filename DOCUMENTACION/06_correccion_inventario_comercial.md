# Correccion de inventario comercial - Mati Boutique

Fecha: 2026-05-24

## 1. Problema detectado

Las auditorias previas confirmaron que el sistema mezclaba dos interpretaciones de inventario:

- Algunas partes calculaban disponible como `cdi_inv - cre_inv`.
- Otras partes trataban `cdi_inv` como el disponible real.

Esa mezcla duplicaba el descuento cuando una prenda ya estaba separada, porque la reserva o venta online en revision ya mueve unidades desde disponible hacia comprometido.

## 2. Semantica oficial

Desde esta correccion queda establecida esta regla:

- `cdi_inv` = stock disponible real.
- `cre_inv` = stock comprometido o separado.
- `stock_total_operativo` = `cdi_inv + cre_inv`.
- `stock_vendible` = `cdi_inv`.

No se debe volver a calcular stock vendible como `cdi_inv - cre_inv`.

## 3. Reglas para carrito

- El carrito de compra no mueve inventario.
- El stock visible y los limites de cantidad se basan en `cdi_inv`.
- La separacion de stock ocurre recien cuando el cliente confirma checkout y sube comprobante.

## 4. Reglas para bolsa

- La bolsa de reservas no mueve inventario al agregar, editar o quitar items.
- El stock visible y los limites de cantidad se basan en `cdi_inv`.
- La separacion ocurre cuando se crea la reserva.

## 5. Reglas para reserva

La logica de reservas ya respetaba la semantica oficial:

- Reserva creada o por procesar: baja `cdi_inv` y sube `cre_inv`.
- Reserva rechazada, cancelada o vencida: sube `cdi_inv` y baja `cre_inv`.
- Reserva recogida y convertida en venta: baja `cre_inv`; `cdi_inv` no vuelve a bajar.

No se cambio la estructura de tablas ni se crearon migraciones.

## 6. Reglas para venta online en revision

La compra online ahora separa stock al confirmar checkout:

- Valida stock disponible con `cdi_inv`.
- Crea venta en estado `en_revision`.
- Crea `detalle_ventas`.
- Crea `venta_pagos`.
- Baja `cdi_inv`.
- Sube `cre_inv`.

Esto evita que otro cliente venda o reserve unidades que ya fueron pagadas y enviadas a revision.

## 7. Reglas para venta confirmada

Cuando el superadmin confirma una venta online en revision:

- La venta debe estar en estado `en_revision`.
- El monto verificado debe coincidir con el total.
- El pago se marca como confirmado.
- La venta pasa a `registrada`.
- Se asigna `cod_usuario_vendedor_ven`.
- Baja `cre_inv`.
- `cdi_inv` no vuelve a bajar.

Tambien se evita confirmar una venta ya confirmada o una venta que ya salio de revision.

## 8. Reglas para cancelacion o incompleta

Cuando una venta online en revision se cancela o se marca incompleta:

- La venta debe estar en estado `en_revision`.
- El pago se marca como rechazado.
- Sube `cdi_inv`.
- Baja `cre_inv`.

Esto evita liberar dos veces una venta ya cancelada, incompleta o confirmada.

## 9. Archivos modificados

- `app/Services/Superadmin/SaleCheckoutService.php`
  - Ahora la venta online en revision separa stock dentro de `DB::transaction` usando filas de inventario bloqueadas con `lockForUpdate`.

- `app/Services/Superadmin/SaleInventoryService.php`
  - Se agrego soporte para confirmar o liberar stock previamente separado.
  - Confirmar venta online baja `cre_inv`.
  - Cancelar o marcar incompleta devuelve stock a `cdi_inv` y baja `cre_inv`.

- `app/Services/Superadmin/SaleService.php`
  - Confirmar venta online ya no descuenta `cdi_inv`.
  - Cancelar o marcar incompleta libera el stock separado.
  - Se restringe la creacion manual de ventas online en revision desde el panel, porque ese camino no registra comprobante ni `venta_pagos`.

- `app/Services/ProductMapper.php`
  - El stock del catalogo publico ahora usa `cdi_inv` como disponible real.

- `app/Http/Controllers/PrendaController.php`
  - El detalle de producto ahora envia stock de variante basado en `cdi_inv`.

- `app/Services/Superadmin/Resources/AdminFormattingService.php`
  - El calculo generico de disponible ahora usa `cdi_inv`.

- `app/Services/Superadmin/Resources/ResourceViewDataService.php`
  - Las metricas visibles de productos/inventario ahora usan `cdi_inv` como disponible.

- `app/Services/Superadmin/Resources/ResourceDetailViewService.php`
  - La vista de inventario aclara que `cdi_inv` es stock disponible real y muestra total calculado como `cdi_inv + cre_inv`.

- `app/Services/Superadmin/DashboardStatsService.php`
  - Stock disponible, bajo stock, sin stock y stock por categoria ya no restan `cre_inv`.
  - El total operativo se muestra como informacion complementaria cuando hay stock comprometido.

## 10. Donde se encontro `cdi_inv - cre_inv`

Se corrigieron usos directos o equivalentes en:

- `app/Services/ProductMapper.php`
- `app/Http/Controllers/PrendaController.php`
- `app/Services/Superadmin/Resources/AdminFormattingService.php`
- `app/Services/Superadmin/Resources/ResourceViewDataService.php`
- `app/Services/Superadmin/DashboardStatsService.php`

Tambien se revisaron servicios de reservas y ventas. Las reservas ya trabajaban con la semantica correcta.

## 11. Pendientes

- Crear tabla `inventario_movimientos` para trazabilidad completa de entradas, salidas, reservas, cancelaciones, ventas, rechazos y ajustes.
- Corregir reportes con estados antiguos detectados en auditoria, especialmente estados de reservas como `activa`.
- Agregar vinculo persistente entre venta y reserva, por ejemplo `ventas.cod_reserva_origen_ven` o una tabla puente.
- Revisar ventas online `en_revision` creadas antes de esta correccion, porque pueden no tener stock separado en `cre_inv`.

## 12. Pruebas manuales recomendadas

1. Producto con `cdi_inv = 10` y `cre_inv = 0`; agregar al carrito. Esperado: inventario no cambia.
2. Crear venta online en revision por 2 unidades. Esperado: `cdi_inv = 8`, `cre_inv = 2`, venta `en_revision`, detalle y pago creados.
3. Confirmar venta online. Esperado: `cdi_inv = 8`, `cre_inv = 0`, venta `registrada`.
4. Crear otra venta online en revision y cancelarla. Esperado: `cdi_inv` vuelve al valor anterior y `cre_inv` baja.
5. Crear reserva por 2 unidades. Esperado: baja `cdi_inv` y sube `cre_inv`.
6. Cancelar o rechazar reserva. Esperado: sube `cdi_inv` y baja `cre_inv`.
7. Completar reserva y convertirla en venta. Esperado: baja `cre_inv`, `cdi_inv` no baja de nuevo y venta final creada.
8. Revisar catalogo y detalle de producto. Esperado: stock disponible mostrado corresponde a `cdi_inv`.
9. Confirmar que no queden `cdi_inv` ni `cre_inv` negativos.
10. Confirmar que las acciones Inertia redirigen con flash y no devuelven JSON puro.

