# Auditoria de ventas directas online

## Estado encontrado

El modulo de ventas directas ya tiene `ventas` y `detalle_ventas`, pero la tabla `ventas` solo guarda cabecera comercial:

- numero de venta.
- cliente.
- vendedor.
- tipo.
- subtotal.
- descuento.
- total.
- metodo de pago.
- estado.
- fecha.

No existen campos ni tabla asociada para guardar el pago declarado, el monto verificado, comprobante o revision del encargado para ventas online.

## Campos o tabla faltante

Para completar el flujo publico de compra online con comprobante hace falta una migracion futura. Opcion recomendada: crear tabla `venta_pagos`.

Campos sugeridos:

- `id_venta_pago`.
- `cod_ventas_vpg`.
- `monto_declarado_vpg`.
- `monto_verificado_vpg`.
- `metodo_pago_vpg`.
- `comprobante_ruta_vpg`.
- `comprobante_url_vpg`.
- `est_vpg`.
- `cod_usuario_verifica_vpg`.
- `fec_verificacion_vpg`.
- `obs_vpg`.
- `fec_vpg`.

Alternativa menos flexible: agregar estos campos directamente a `ventas`:

- `monto_declarado_ven`.
- `monto_verificado_ven`.
- `comprobante_ruta_ven`.
- `comprobante_url_ven`.
- `cod_usuario_verifica_pago_ven`.
- `fec_verificacion_pago_ven`.
- `obs_pago_ven`.

Tambien seria conveniente permitir `cod_usuario_vendedor_ven` nullable para ventas online publicas hasta que un encargado las revise.

No se creo ninguna migracion en esta correccion.

## Flujo corregido sin migracion

Mientras no exista almacenamiento de comprobantes de venta:

- una venta `online` creada desde superadmin queda con `est_ven = en_revision`.
- no descuenta inventario al crearse.
- conserva sus `detalle_ventas` para revision.
- al confirmarse desde superadmin, se valida que el monto verificado sea exactamente el total.
- solo al confirmar pasa a `registrada` y descuenta inventario.
- si el monto es menor puede marcarse como `incompleta`.
- si el comprobante es invalido puede marcarse como `cancelada`, aunque la observacion no puede persistirse hasta crear campos de pago.

## Diferencia con reservas

Reservas:

- permiten anticipo desde 50%.
- separan stock.
- se convierten en venta al recoger.

Ventas directas:

- requieren pago del 100%.
- no aceptan anticipo.
- deben revisarse antes de descontar inventario.
- solo `registrada` cuenta como venta confirmada.

## Metodos de pago online

Para venta online se permiten:

- QR.
- Transferencia bancaria.

QR:

- URL publica: `/images/payments/qr-mati-boutique.png`.
- Archivo: `public/images/payments/qr-mati-boutique.png`.

Transferencia bancaria:

- Titular: LIMBER SOTO CAZU.
- Numero de cuenta: 1003383438.

## Estados de venta

Estados internos usados:

- `en_revision`: comprobante pendiente de revision.
- `registrada`: venta confirmada.
- `cancelada`: comprobante rechazado o venta anulada.
- `incompleta`: monto verificado menor al total.

Labels visibles:

- En revision.
- Venta confirmada.
- Venta cancelada.
- Venta incompleta.

## Inventario

Venta presencial:

- descuenta stock al registrarse.

Venta online en revision:

- valida stock disponible.
- no descuenta stock al crearse.
- descuenta stock solo cuando se confirma.

Venta cancelada o incompleta:

- no descuenta inventario.

## Vista cliente

Los pedidos muestran mensajes amigables:

- En revision: `Estamos revisando tu comprobante. Te notificaremos cuando tu compra sea confirmada.`
- Confirmada: `Tu compra fue confirmada correctamente.`
- Incompleta: `El monto pagado no cubre el total de la compra. La venta no fue confirmada.`
- Cancelada: `Tu compra fue cancelada. Revisa la observacion o comunicate con la tienda.`

## Pendiente

El flujo publico `bolsa/confirmar` todavia usa reservas porque no existe persistencia de comprobantes para ventas. Para cambiarlo a compra online real se debe aprobar primero la migracion de pagos/comprobantes de ventas.
