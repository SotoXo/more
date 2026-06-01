# Correccion de saldo en detalle de reserva

## Problema corregido

En el detalle de reserva podia mostrarse un saldo distinto entre el bloque superior y las tarjetas del detalle.

Ejemplo:

- Total de reserva: Bs 2,500.00.
- Monto confirmado: Bs 2,250.00.
- Saldo real: Bs 250.00.

El bloque superior podia mostrar Bs 0.00 porque no leia una fuente explicita de saldo pendiente.

## Fuente de verdad

Para la vista y para el cierre de reserva se usa:

`saldo_pendiente = max(total_reserva - monto_confirmado, 0)`

En campos del sistema:

`saldo_pendiente = max(tot_res - monto_pagado_res, 0)`

Esto evita que un `saldo_res` desfasado permita cerrar una reserva con deuda.

## Formulario superior

El bloque `Finalizar reserva` queda arriba y muestra:

- Total de la reserva.
- Monto ya confirmado.
- Saldo pendiente real.
- Estado de pago.
- Estado de reserva.

Si hay saldo pendiente, el formulario aparece inmediatamente.

## Opciones de monto

El cierre muestra dos opciones:

- `Cobrar saldo pendiente`: precarga el saldo real completo.
- `Ingresar monto especifico`: permite editar manualmente, pero solo finaliza si el monto completa exactamente el saldo.

Validaciones:

- monto obligatorio.
- monto no menor al saldo.
- monto no mayor al saldo.

## Metodos de pago final

Desde superadmin/cajero se permiten:

- Efectivo.
- QR.
- Transferencia bancaria.

Efectivo no requiere comprobante y registra el pago como recibido en caja.

QR y transferencia bancaria requieren comprobante de pago en formato JPG, JPEG, PNG o WEBP, maximo 4MB.

## Datos de pago

QR:

- `public/images/payments/qr-mati-boutique.png`
- URL publica: `/images/payments/qr-mati-boutique.png`

Transferencia bancaria:

- Titular: LIMBER SOTO CAZU.
- Nro. de cuenta: 1003383438.

## Finalizacion

Al finalizar correctamente:

- se registra el pago restante confirmado.
- se deja `saldo_res = 0`.
- se deja `est_pago_res = pagado_total`.
- se deja `est_res = completada`.
- se genera venta.
- se generan detalles de venta.
- baja el stock reservado.
- no vuelve a bajar el stock disponible.

No se usan `prompt`, `alert` ni `confirm` nativos en el cierre principal.
