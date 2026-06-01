# Cierre de reserva y pago final

## Problema corregido

El detalle de reserva tenia varias acciones y formularios visibles en la misma pantalla. Eso hacia confuso el flujo principal para cerrar una reserva, registrar el pago restante y generar la venta.

## Nuevo orden de la vista

La pantalla de detalle ahora prioriza un bloque superior llamado `Finalizar reserva`.

Este bloque muestra:

- Total de la reserva.
- Monto ya confirmado.
- Saldo pendiente.
- Estado de pago.
- Estado de reserva.

Luego aparecen el resumen de la reserva, productos, historial y acciones secundarias.

## Monto recibido por defecto

Cuando existe saldo pendiente, el campo `Monto recibido ahora` se precarga con el saldo pendiente de la reserva.

Regla aplicada:

- el monto recibido debe ser igual al saldo pendiente.
- no se permite un monto menor.
- no se permite un monto mayor.

## Metodo de pago final

Para el cierre desde superadmin/cajero se permiten:

- Efectivo.
- QR.
- Transferencia bancaria.

La reserva online inicial se mantiene con QR y transferencia bancaria, pero el cierre en tienda permite efectivo porque el cliente puede pagar el saldo en caja.

## Comprobante segun metodo

Si el metodo final es `efectivo`:

- no se solicita comprobante.
- se registra el pago confirmado sin ruta ni URL de comprobante.
- se muestra el texto `Pago recibido en caja de la tienda.`

Si el metodo final es `qr` o `transferencia_bancaria`:

- se solicita comprobante obligatorio.
- se aceptan imagenes JPG, JPEG, PNG o WEBP.
- el tamano maximo es 4MB.
- se guarda la ruta y URL del comprobante en `reserva_pagos`.

## Accion final

Al enviar el bloque `Finalizar reserva`, se usa la accion existente de reserva:

- registra el pago restante.
- deja `saldo_res = 0`.
- deja `est_pago_res = pagado_total`.
- deja `est_res = completada`.
- genera la venta.
- genera `detalle_ventas`.
- descuenta solamente el stock reservado.

No se descuenta nuevamente el stock disponible.

## Regla UX

Los flujos principales de reserva no deben usar formularios nativos del navegador como `prompt`, `confirm` o `alert`.

Tampoco deben competir varios formularios principales en la misma vista. El cierre de reserva debe estar concentrado en el bloque superior.
