# Modulo de reservas con anticipo y comprobante

## Objetivo

El modulo de reservas de Mati Boutique permite separar prendas con stock real, registrar comprobantes de pago, verificar manualmente el anticipo o pago total y controlar las fechas de recojo sin tocar ventas ni reportes.

## Flujo completo

1. Cliente o vendedor registra una reserva desde superadmin.
2. El sistema valida cliente, variantes, cantidades, comprobante, monto declarado y stock disponible.
3. Se exige anticipo minimo del 50% del total antes de guardar.
4. En transaccion, el stock pasa de disponible a reservado.
5. Se crea la reserva en estado `por_procesar` y el pago en `pendiente_revision`.
6. Superadmin revisa el comprobante y registra el monto confirmado.
7. Si el pago cumple el 50%, la reserva queda `confirmada`.
8. Si el pago cubre el total, el estado de pago queda `pagado_total`.
9. La reserva puede pasar a `lista_recojo` y luego `completada`.
10. Si se cancela, rechaza o marca vencida, el stock reservado vuelve a disponible.

## Estados de reserva

- `por_procesar`: reserva creada con comprobante pendiente de revision.
- `confirmada`: pago verificado con minimo 50%.
- `lista_recojo`: prenda disponible para entrega.
- `completada`: cliente recogio la prenda.
- `cancelada`: reserva anulada con stock liberado.
- `vencida`: no fue recogida dentro del plazo.
- `rechazada`: comprobante invalido o pago menor al minimo.

## Estados de pago

En `reservas.est_pago_res`:

- `pendiente_revision`
- `anticipo_confirmado`
- `pagado_total`
- `rechazado`

En `reserva_pagos.est_rpg`:

- `pendiente_revision`
- `confirmado`
- `rechazado`

## Regla del 50%

El anticipo minimo para reservar es el 50% del total. Si el monto declarado inicial no alcanza ese minimo, el formulario bloquea el guardado y muestra: "El anticipo minimo para reservar es del 50% del total."

Durante la verificacion, si el monto confirmado no alcanza el 50% y no existe pago confirmado previo, el comprobante se rechaza, la reserva queda `rechazada` y se libera stock.

## Comprobante

Los comprobantes se guardan como imagen en el disco publico de Laravel:

- formatos aceptados: jpg, jpeg, png, webp
- tamano maximo: 4 MB
- ruta guardada en `comprobante_ruta_rpg`
- URL publica guardada en `comprobante_url_rpg`

El panel superadmin permite abrir el comprobante desde el detalle de la reserva.

## Fechas de recojo

- Stock en `TIENDA`: disponible hoy.
- Stock en `DEPOSITO`: disponible hoy + 3 dias.
- Reserva mixta: usa la fecha mas lejana y `origen_stock_res = MIXTO`.
- El limite de recojo es la fecha disponible + 30 dias.
- El punto de recojo es "Recojo en tienda Mati Boutique".

## Movimiento de inventario

Al crear reserva:

- `cdi_inv = cdi_inv - cantidad`
- `cre_inv = cre_inv + cantidad`

Al cancelar, rechazar o vencer:

- `cdi_inv = cdi_inv + cantidad`
- `cre_inv = cre_inv - cantidad`

Al completar:

- `cre_inv = cre_inv - cantidad`
- `cdi_inv` no vuelve a descontarse porque ya se desconto al crear la reserva.

## Tablas usadas

- `reservas`
- `detalle_reservas`
- `reserva_pagos`
- `inventarios`

## Pendiente futuro

Queda documentado como mejora futura un comando programado para marcar reservas vencidas automaticamente. Por ahora el panel muestra visualmente las vencidas y ofrece accion humana para marcarlas como `vencida`.
