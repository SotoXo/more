# Correccion de reservas: revision, recojo y venta

## Objetivo

El modulo de reservas queda orientado al flujo real de Mati Boutique:

1. Cliente reserva prendas y sube comprobante.
2. Cajero o superadmin revisa el comprobante.
3. Se confirma adelanto o pago total.
4. La reserva puede pasar a lista para recojo.
5. Al marcarla como recogida, se cierra la reserva y se crea una venta.

## Estados visibles para cliente

El cliente no debe ver estados tecnicos como `por_procesar` o `anticipo_confirmado`.

Mapeo visible:

- `por_procesar`: Estamos revisando tu reserva. Te notificaremos cuando sea confirmada.
- `pendiente_revision`: Tu comprobante fue recibido y esta pendiente de revision.
- `anticipo_confirmado`: Tu reserva fue confirmada con adelanto. Tienes un saldo pendiente antes o al momento del recojo.
- `pagado_total`: Tu reserva fue pagada completamente.
- `lista_recojo`: Tu prenda esta lista para recojo.
- `completada`: Tu reserva fue recogida correctamente.
- `rechazada`: Tu reserva fue rechazada. Revisa la observacion o comunicate con la tienda.
- `cancelada`: Tu reserva fue cancelada.
- `vencida`: Tu reserva vencio porque no fue recogida dentro del plazo establecido.

## Estados visibles para superadmin

El panel mantiene los estados internos, pero los muestra con labels operativos:

- En revision.
- Adelanto confirmado.
- Pagado completamente.
- Lista para recojo.
- Recogida.
- Cancelada.
- Rechazada.
- Vencida.

El listado de reservas muestra datos claves para revision:

- Cliente.
- Total.
- Monto declarado.
- Monto confirmado.
- Saldo.
- Estado de pago.
- Estado de reserva.
- Fecha disponible de recojo.

## Revision de comprobante

La vista de detalle de reserva muestra:

- Total de reserva.
- Anticipo minimo requerido.
- Monto declarado por el cliente.
- Monto confirmado.
- Saldo pendiente.
- Comprobante visible y enlace para abrirlo.
- Observacion de pago y observacion de reserva.

Las acciones de Inertia siguen devolviendo redirect con flash o errores de validacion visibles.

## Reglas de adelanto y pago total

- El adelanto minimo es 50% del total.
- El monto confirmado no puede superar el saldo pendiente.
- Si el primer monto confirmado no alcanza el 50%, no se aprueba y se muestra error.
- Si el total pagado llega al total de la reserva, el estado de pago pasa a `pagado_total`.
- Si queda saldo, el estado de pago queda como `anticipo_confirmado`.

## Marcar como recogido

La accion visible es `Marcar como recogido`.

Reglas:

- Solo aplica para reservas `confirmada` o `lista_recojo`.
- No aplica para reservas canceladas, rechazadas, vencidas o ya recogidas.
- Si existe saldo pendiente, se solicita monto recibido ahora y metodo de pago final.
- El monto final debe cubrir exactamente el saldo pendiente.
- No se permite pagar mas que el saldo.
- Al cubrir el saldo, se registra un `reserva_pagos` confirmado.

## Conversion a venta

Al marcar una reserva como recogida:

- Se crea un registro en `ventas`.
- Se crean registros en `detalle_ventas`.
- La venta usa:
  - cliente de la reserva.
  - vendedor igual al usuario autenticado que marca como recogido.
  - `tiv_ven = reserva`.
  - `sub_ven`, `des_ven` y `tot_ven` copiados de la reserva.
  - metodo de pago principal tomado del ultimo pago confirmado o `reserva`.
  - `est_ven = registrada`.
- El detalle de venta copia:
  - variante reservada.
  - cantidad.
  - precio unitario congelado `detalle_reservas.pun_dre`.
  - subtotal congelado `detalle_reservas.sub_dre`.

La conversion vive en `ReservationToSaleService` para no cargar controladores ni ResourceController.

## Movimiento de inventario

- Crear reserva: baja `cdi_inv` y sube `cre_inv`.
- Cancelar, rechazar o vencer: sube `cdi_inv` y baja `cre_inv`.
- Marcar como recogido: baja `cre_inv`.
- Al convertir a venta no se vuelve a descontar `cdi_inv`, porque ya se desconto al crear la reserva.

## Pendiente tecnico

Actualmente no existe un campo directo para relacionar venta con reserva. No se creo migracion por restriccion del alcance.

Pendiente recomendado:

- Relacionar ventas con reservas mediante campo `cod_reservas_ven` en una futura migracion.

Mientras tanto, se evita duplicar ventas validando que una reserva completada no pueda volver a marcarse como recogida.
