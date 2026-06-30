# 43. Panel vendedor: reservas

## 1. Objetivo de la Fase 4

La Fase 4 implementa reservas presenciales desde el panel vendedor de Mati Boutique. El vendedor puede listar sus reservas, crear una reserva para un cliente existente, separar stock, registrar el pago inicial confirmado automaticamente, ver el detalle, registrar pagos adicionales y cancelar una reserva con liberacion de stock.

Esta fase no implementa revision de comprobantes subidos por clientes, recojos/entregas ni conversion de reserva a venta.

## 2. Rutas implementadas

Todas las rutas viven dentro del grupo `auth` + `vendedor`.

- `GET /vendedor/reservas` -> `vendedor.reservas.index`
- `GET /vendedor/reservas/crear` -> `vendedor.reservas.create`
- `POST /vendedor/reservas` -> `vendedor.reservas.store`
- `GET /vendedor/reservas/{reserva}` -> `vendedor.reservas.show`
- `POST /vendedor/reservas/{reserva}/pagos` -> `vendedor.reservas.payments.store`
- `POST /vendedor/reservas/{reserva}/cancelar` -> `vendedor.reservas.cancel`

No se agregaron rutas de edicion libre, eliminacion, recojo, entrega ni conversion a venta.

## 3. Backend

Se creo:

- `app/Http/Controllers/Vendedor/VendedorReservationController.php`
- `app/Services/Vendedor/VendedorReservationService.php`

Se reutilizan servicios existentes:

- `ReservationNumberService` para numeracion `RES-YYYYMMDD-####`.
- `ReservationInventoryService` para separar y liberar stock con `lockForUpdate`.
- `ReservationPaymentService` para registrar pagos confirmados y actualizar saldo.
- `PickupLogisticsService` para fechas de disponibilidad, vencimiento y origen de stock.
- `InventoryMovementService` indirectamente desde `ReservationInventoryService` para Kardex.

## 4. Flujo de reserva

1. El vendedor abre `/vendedor/reservas/crear`.
2. Selecciona un cliente existente.
3. Busca variantes activas por prenda, SKU, color, talla o ubicacion.
4. Agrega productos al carrito de reserva.
5. El sistema valida cantidad mayor a cero y que no supere `cdi_inv`.
6. Registra observacion opcional.
7. Registra pago inicial.
8. Si el metodo es QR o transferencia bancaria, sube comprobante.
9. Al guardar, el backend valida nuevamente cliente, stock y monto.
10. Dentro de `DB::transaction`, crea reserva, detalle, separa stock, registra Kardex, crea pago confirmado y logs.

No se implementa creacion rapida de clientes. En esta fase se requiere seleccionar un cliente existente.

## 5. Separacion de stock

La semantica de inventario se mantiene:

- `cdi_inv` = stock disponible real.
- `cre_inv` = stock comprometido/separado.

No se calcula disponible como `cdi_inv - cre_inv`.

Al crear una reserva:

- Se valida que cada variante tenga `cdi_inv` suficiente.
- Se resta la cantidad reservada de `cdi_inv`.
- Se suma la misma cantidad a `cre_inv`.
- La operacion se hace con inventario bloqueado por `lockForUpdate`.
- Si falla cualquier paso, no se crea reserva, detalle, pago ni movimiento.

Ejemplo:

- Antes: `cdi_inv = 5`, `cre_inv = 1`.
- Reserva de 2 unidades.
- Despues: `cdi_inv = 3`, `cre_inv = 3`.

## 6. Cancelacion y liberacion de stock

La cancelacion exige motivo.

Cuando se cancela una reserva activa:

- `cdi_inv` sube.
- `cre_inv` baja.
- Se registra Kardex `liberacion_reserva`.
- Se registra log.
- La reserva queda `est_res = cancelada` y `est_logistico_res = cancelada`.

No se permite cancelar desde vendedor reservas ya canceladas, vencidas, rechazadas, completadas o recogidas. Esto evita liberar stock dos veces.

Si la reserva tiene pagos confirmados, la UI muestra advertencia. La devolucion de dinero queda fuera de esta fase.

## 7. Pagos registrados por vendedor

Pago registrado por vendedor = pago confirmado automaticamente.

Aplica para:

- `efectivo`
- `qr`
- `transferencia_bancaria`

Efectivo no requiere comprobante. QR y transferencia bancaria requieren comprobante. El pago se guarda con `est_rpg = confirmado`, `monto_confirmado_rpg = monto_declarado_rpg`, verificador igual al vendedor autenticado y fecha de verificacion inmediata.

## 8. Diferencia con comprobantes subidos por clientes

Esta fase no cambia el flujo de comprobantes subidos por clientes.

- Pago registrado por vendedor: confirmado automaticamente.
- Comprobante subido por cliente: pendiente de revision y fuera de esta fase.

La revision de comprobantes de clientes queda para Fase 5.

## 9. Pago minimo y maximo

Se respeta la regla existente del sistema:

- Pago inicial minimo: 50% del total.
- Pago maximo inicial: 100% del total.
- No se permite monto menor o igual a 0.
- No se permite pagar mas que el total.
- Los pagos adicionales pueden cubrir hasta el saldo pendiente.
- No se permite pagar mas que el saldo.

Los pagos adicionales no modifican stock.

## 10. Kardex

Al crear reserva, se registra movimiento:

- `tipo_movimiento_inm = reserva`
- `referencia_tipo_inm = reserva`
- `referencia_id_inm = id_reserva`
- cdi antes/despues
- cre antes/despues
- usuario autenticado
- descripcion `Reserva registrada por vendedor.`

Al cancelar reserva, se registra movimiento:

- `tipo_movimiento_inm = liberacion_reserva`
- `referencia_tipo_inm = reserva`
- `referencia_id_inm = id_reserva`
- cdi antes/despues
- cre antes/despues
- usuario autenticado
- descripcion `Reserva cancelada por vendedor. Stock liberado.`

No se registra Kardex si la operacion falla.

## 11. Logs generados

Se generan logs especificos para el panel vendedor:

- `reserva_vendedor_creada`
- `pago_reserva_vendedor_confirmado`
- `stock_separado_por_reserva`
- `reserva_vendedor_cancelada`
- `stock_liberado_por_reserva_cancelada`

Ademas, el Kardex conserva su log interno `movimiento_kardex`, y el servicio de pagos conserva el log existente `pago_confirmado`.

## 12. Seguridad

El acceso sigue protegido por `EnsureVendedor`.

Reglas aplicadas:

- Cliente no accede a `/vendedor/reservas`.
- Vendedor ve reservas registradas por su usuario.
- Superadmin puede entrar a `/vendedor` para supervision y ve reservas globales.
- Vendedor no accede a `/superadmin/reservas`.
- Vendedor no edita ni elimina reservas libremente.
- Vendedor no ajusta stock manualmente.
- Vendedor no accede al Kardex completo; solo ve resumen de movimientos asociados a su reserva.
- Vendedor no revisa comprobantes subidos por clientes en esta fase.

## 13. Dashboard vendedor

El dashboard vendedor ahora conecta datos reales para:

- Reservas activas.
- Reservas vencidas o proximas a vencer dentro de la nota de la metrica.

Se mantienen ventas y stock de fases anteriores. Recojos pendientes no se conectan en esta fase.

## 14. Pruebas manuales

1. Iniciar sesion como vendedor y abrir `/vendedor/reservas`.
   Esperado: carga el listado de reservas del vendedor.

2. Abrir `/vendedor/reservas/crear`.
   Esperado: permite seleccionar cliente y ver productos reales con imagen, SKU, color, talla, precio y `cdi_inv`.

3. Intentar reservar cantidad mayor al stock.
   Esperado: la UI bloquea el guardado y el backend rechaza si se fuerza el envio.

4. Crear reserva con efectivo.
   Esperado: reserva creada, pago confirmado, `cdi_inv` baja, `cre_inv` sube, Kardex y logs registrados.

5. Crear reserva con QR.
   Esperado: exige comprobante, pago confirmado automaticamente y sin revision admin.

6. Crear reserva con transferencia bancaria.
   Esperado: mismo comportamiento que QR.

7. Registrar pago adicional.
   Esperado: pago confirmado automaticamente, no modifica stock y no permite exceder saldo.

8. Cancelar reserva.
   Esperado: exige motivo, libera stock, registra `liberacion_reserva` y no permite liberar dos veces.

9. Revisar dashboard vendedor.
   Esperado: reservas activas muestra dato real.

10. Iniciar sesion como cliente e intentar abrir `/vendedor/reservas`.
    Esperado: no tiene acceso.

11. Revisar superadmin.
    Esperado: `/superadmin` sigue funcionando y reservas existentes no se rompen.

## 15. Pendientes para Fase 5

- Revision de comprobantes subidos por clientes.
- Acciones seguras para aprobar o rechazar comprobantes desde vendedor.
- Recojos y entregas desde vendedor.
- Conversion de reserva a venta desde vendedor si se decide reutilizar el flujo existente.
- Gestion de devoluciones para reservas canceladas con pagos confirmados.
- Creacion rapida de cliente desde vendedor.
- Pruebas automatizadas de reserva, pago adicional y cancelacion.
