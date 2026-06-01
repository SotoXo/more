# Recibos emitidos por Mati Boutique

## Objetivo

Se agrego la visualizacion de recibos HTML imprimibles emitidos por Mati Boutique para reservas y ventas.

Esta fase no crea migraciones ni guarda archivos PDF. Los recibos se generan dinamicamente desde la informacion existente.

## Voucher del cliente vs recibo de la tienda

### Comprobante subido por el cliente

- Es la imagen que el cliente sube al reservar o comprar.
- Puede corresponder a QR o transferencia bancaria.
- Se guarda en `reserva_pagos` o `venta_pagos`.
- Sirve para que superadmin o vendedor revise el pago.

### Recibo emitido por Mati Boutique

- Lo genera el sistema despues de que el pago fue verificado.
- Es una constancia imprimible de la operacion.
- No reemplaza el voucher del cliente.
- Se muestra como "Recibo emitido por Mati Boutique".

## Recibo de reserva

El recibo de reserva esta disponible cuando la reserva tiene pago confirmado:

- `est_pago_res = anticipo_confirmado`
- o `est_pago_res = pagado_total`
- o existe un registro confirmado en `reserva_pagos`.

Muestra:

- numero de reserva.
- fecha de emision.
- cliente.
- responsable que verifico.
- prendas reservadas con SKU, color, talla, cantidad, precio unitario y subtotal.
- total de reserva.
- monto pagado confirmado.
- saldo pendiente.
- estado de pago.
- fechas de recojo.
- punto de recojo.
- observacion.

## Recibo de venta

El recibo de venta esta disponible solo cuando:

- `ventas.est_ven = registrada`.

Aplica para:

- ventas online confirmadas.
- ventas creadas al recoger una reserva.
- ventas directas registradas.

Muestra:

- numero de venta.
- referencia de reserva si la venta fue generada desde una reserva.
- fecha de emision.
- cliente.
- vendedor o encargado que verifico.
- tipo de venta.
- productos vendidos con SKU, color, talla, cantidad, precio unitario y subtotal.
- subtotal.
- descuento.
- total pagado.
- metodo de pago.
- estado confirmado.
- observacion.

## Rutas

Panel operativo:

- `GET /superadmin/comercial/reservas/{reserva}/recibo`
- `GET /superadmin/comercial/ventas/{venta}/recibo`

Cliente:

- `GET /mis-reservas/{reserva}/recibo`
- `GET /mis-compras/{venta}/recibo`

## Permisos

Superadmin:

- puede ver todos los recibos operativos.

Vendedor:

- puede ver recibos operativos dentro del flujo comercial.

Cliente:

- solo puede ver recibos de sus propias reservas o compras.
- si intenta abrir un recibo ajeno, el backend responde con acceso denegado.

## Formato

El recibo se implementa como vista HTML imprimible:

- diseno limpio.
- marca Mati Boutique visible.
- tabla de productos.
- totales.
- boton Volver.
- boton Imprimir.

Se permite `window.print()` unicamente en el boton de impresion.

## Pendientes futuros

Si el negocio requiere recibos persistidos, se recomienda una migracion futura para guardar:

- numero de recibo.
- ruta del PDF generado.
- fecha de emision.
- usuario que emitio.
- tipo de recibo: reserva o venta.

No se implemento esa migracion en esta fase.
