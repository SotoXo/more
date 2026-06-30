# Fase 6 - Recojos y entregas en panel vendedor

## Objetivo

Esta fase habilita `/vendedor/recojos` como bandeja operativa para entrega fisica de ventas y recojo de reservas. El vendedor puede consultar operaciones, verificar pago confirmado al 100% y marcar como entregada/recogida cuando corresponde.

## Rutas

Rutas bajo middleware `auth` + `vendedor`:

- `GET /vendedor/recojos` (`vendedor.recojos.index`)
- `GET /vendedor/recojos/ventas/{venta}` (`vendedor.recojos.ventas.show`)
- `POST /vendedor/recojos/ventas/{venta}/entregar` (`vendedor.recojos.ventas.deliver`)
- `GET /vendedor/recojos/reservas/{reserva}` (`vendedor.recojos.reservas.show`)
- `POST /vendedor/recojos/reservas/{reserva}/entregar` (`vendedor.recojos.reservas.deliver`)

## Backend

Archivos creados:

- `app/Http/Controllers/Vendedor/VendedorPickupController.php`
- `app/Services/Vendedor/VendedorPickupService.php`

El servicio del panel vendedor arma la bandeja, calcula pago confirmado, valida bloqueos y delega los cambios reales en servicios centrales existentes.

Servicios reutilizados:

- `App\Services\Superadmin\SalePickupService`
- `App\Services\Superadmin\ReservationService`
- `App\Services\Superadmin\ReservationInventoryService` indirectamente desde `ReservationService::complete`
- `App\Services\SystemLogService`

## Regla de pago confirmado al 100%

Para ventas se suman solo pagos `venta_pagos.est_vpg = confirmado` usando `monto_verificado_vpg`.

Para reservas se suman solo pagos `reserva_pagos.est_rpg = confirmado` usando `monto_confirmado_rpg`.

No se cuentan pagos:

- `pendiente_revision`
- `rechazado`
- anulados o estados no confirmados

Si el monto confirmado es menor al total, el sistema bloquea la accion con:

`No se puede entregar porque el pago no esta confirmado al 100%.`

## Flujo de entrega de venta

La pantalla `resources/js/Pages/Vendedor/Pickups/SaleShow.tsx` muestra venta, cliente, vendedor, pagos, comprobante, productos y ubicacion.

Al entregar:

1. `VendedorPickupService` valida que el pago confirmado cubra el 100%.
2. Valida que la venta este `registrada`.
3. Valida que `est_logistico_ven = lista_recojo`.
4. Delega en `SalePickupService::markDelivered`.
5. Se actualiza estado logistico a `entregada`, fecha y usuario de entrega.
6. Se registra `venta_entregada_por_vendedor`.

## Inventario en venta entregada

Entregar una venta no modifica inventario:

- No baja `cdi_inv`.
- No toca `cre_inv`.
- No registra Kardex de stock desde esta fase.

Esto es correcto porque:

- Venta directa ya desconto `cdi_inv` en Fase 3.
- Compra online ya consumio stock separado al aprobar el comprobante en el flujo central.

## Flujo de recojo de reserva

La pantalla `resources/js/Pages/Vendedor/Pickups/ReservationShow.tsx` muestra reserva, cliente, responsable, pagos, comprobantes, productos, ubicacion y `cre_inv`.

Al marcar recogida:

1. `VendedorPickupService` valida pago confirmado al 100%.
2. Valida que la reserva este `confirmada` o `lista_recojo`.
3. Valida que `est_logistico_res = lista_recojo`.
4. Valida que no este vencida.
5. Valida que exista `cre_inv` suficiente por variante.
6. Delega en `ReservationService::complete`.
7. El servicio central convierte la reserva en venta con `ReservationToSaleService`.
8. El servicio central consume stock comprometido con `ReservationInventoryService::complete`.
9. Se registra `reserva_recogida_por_vendedor`.
10. Si se genero venta, se registra `reserva_convertida_a_venta_desde_recojo`.

## Inventario en reserva recogida

La reserva ya separo stock al crearse:

- bajo `cdi_inv`
- subio `cre_inv`

Al recoger:

- No baja `cdi_inv`.
- Reduce `cre_inv`.
- No devuelve stock a disponible.
- No descuenta doble.

Ejemplo:

- Antes: `cdi_inv = 3`, `cre_inv = 2`
- Recogida de 2 unidades: `cdi_inv = 3`, `cre_inv = 0`

## Kardex

Venta entregada:

- No registra movimiento Kardex de stock.

Reserva recogida:

- Reutiliza `ReservationInventoryService::complete`.
- Registra movimiento `conversion_reserva_venta`.
- El movimiento deja `cdi_inv` igual y reduce `cre_inv`.

## Dashboard

`Recojos pendientes` ahora muestra operaciones listas para entregar o recoger:

- ventas pagadas al 100% con `est_logistico_ven = lista_recojo`
- reservas pagadas al 100%, no vencidas, con `est_logistico_res = lista_recojo` y `cre_inv` suficiente

No cuenta operaciones bloqueadas por pago pendiente como listas. La nota de la metrica indica si existen bloqueadas.

## Seguridad

- Clientes no acceden a `/vendedor/recojos` porque la ruta esta protegida con middleware `vendedor`.
- Vendedor no puede entregar sin pago confirmado al 100%.
- Vendedor no edita ventas ni reservas desde estas pantallas.
- Vendedor no modifica stock manualmente.
- Superadmin conserva acceso normal a su panel.

## Logs

Eventos agregados desde el panel vendedor:

- `venta_entregada_por_vendedor`
- `reserva_recogida_por_vendedor`
- `entrega_bloqueada_por_pago_pendiente`
- `reserva_convertida_a_venta_desde_recojo`

Ademas se mantienen logs centrales existentes:

- `venta_entregada`
- `reserva_recogida`
- `reserva_convertida_venta`
- movimientos Kardex `conversion_reserva_venta` cuando aplica

## Pruebas manuales recomendadas

1. Entrar como vendedor a `/vendedor/recojos`.
2. Revisar pestaña `Ventas listas`.
3. Revisar pestaña `Reservas listas`.
4. Revisar pestaña `Bloqueadas por pago`.
5. Abrir una venta lista y verificar productos/pagos.
6. Abrir una reserva lista y verificar productos/pagos/`cre_inv`.
7. Intentar entregar una operacion bloqueada y confirmar que no permite por pago.
8. Entregar una venta pagada al 100% y verificar que no cambian `cdi_inv` ni `cre_inv`.
9. Recoger una reserva pagada al 100% y verificar que `cdi_inv` no baja y `cre_inv` se reduce.
10. Revisar logs y dashboard.

## Pendientes

- Flujo vendedor para marcar operaciones de deposito como `lista_recojo` si se quiere delegar preparacion/traslado al vendedor.
- Reportes especificos de entregas por vendedor si se requieren en una fase posterior.
