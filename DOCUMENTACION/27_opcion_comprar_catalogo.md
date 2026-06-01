# Compra online con carrito

## Cambio aplicado

Se reemplazo la compra directa incrustada en el detalle de producto por un flujo con carrito y checkout.

Se mantiene una opcion visible `Comprar` junto a `Reservar` en las prendas del catalogo y en el detalle de producto.

El flujo queda separado:

- `Reservar` mantiene el flujo actual de reservas.
- `Comprar` agrega prendas al carrito de compra online.

## Comportamiento actual habilitado

El cliente puede agregar prendas al carrito desde el detalle de producto. El pago no ocurre en el producto.

Si intenta finalizar checkout sin sesion, debe iniciar sesion y el backend devuelve:

`Debes iniciar sesion para finalizar la compra.`

En el detalle de producto se puede alternar entre:

- `Reservar`: conserva el flujo actual de bolsa/reservas.
- `Comprar`: permite seleccionar variante y cantidad, y agregar al carrito.

El boton del catalogo envia al detalle con `?accion=comprar` para abrir directamente la opcion de compra, sin mostrar formulario de pago.

## Flujo correcto

1. Cliente selecciona una prenda.
2. Presiona `Comprar`.
3. Selecciona variante y cantidad.
4. Agrega al carrito.
5. Puede seguir agregando mas prendas.
6. Entra a `/carrito`.
7. Revisa productos, cantidades, subtotal y total.
8. Presiona `Continuar con la compra`.
9. Entra a `/checkout`.
10. Paga el 100% con QR o transferencia bancaria.
11. Sube comprobante.
12. Se crea venta `en_revision`.
13. Se crean `detalle_ventas`.
14. Se crea `venta_pagos`.
15. El encargado revisa desde Gestion de ventas.

## Carrito

El carrito usa `localStorage`, sin migraciones.

Permite:

- agregar varias prendas.
- sumar cantidad si se agrega la misma variante.
- cambiar cantidad.
- quitar prendas.
- vaciar carrito.
- mantener productos al navegar.

El backend recalcula precios, stock y totales al confirmar checkout. No se confia en precios enviados por frontend.

## Checkout

La compra online queda separada de reservas:

- Requiere pago del 100% del total.
- No acepta adelantos.
- Requiere comprobante obligatorio.
- Crea la venta con `est_ven = en_revision`.
- Crea `detalle_ventas` para que el encargado vea las prendas compradas.
- Crea `venta_pagos` con monto declarado y comprobante.
- No descuenta inventario al crear la compra.

## Tabla venta_pagos

La tabla `venta_pagos` guarda la revision del pago de una compra online:

- `monto_declarado_vpg`: monto informado por el cliente.
- `monto_verificado_vpg`: monto revisado por el encargado.
- `metodo_pago_vpg`: `qr` o `transferencia_bancaria`.
- `comprobante_ruta_vpg`: ruta privada relativa en storage publico.
- `comprobante_url_vpg`: URL publica para abrir el comprobante desde el panel.
- `est_vpg`: `pendiente_revision`, `confirmado` o `rechazado`.
- `cod_usuario_verifica_vpg`, `fec_verificacion_vpg`, `obs_vpg`: datos de revision.

## Metodos de pago

Compra online solo debe aceptar:

- QR.
- Transferencia bancaria.

QR:

- URL publica: `/images/payments/qr-mati-boutique.png`.
- Archivo: `public/images/payments/qr-mati-boutique.png`.

Transferencia bancaria:

- Titular: LIMBER SOTO CAZU.
- Nro. de cuenta: 1003383438.

La configuracion centralizada esta en `resources/js/config/paymentMethods.ts`. Para venta online publica se debe usar `ONLINE_PAYMENT_METHOD_OPTIONS`, que solo expone QR y transferencia bancaria.

## Inventario

Al crear venta en revision:

- se valida `cdi_inv`.
- no se descuenta inventario.

Al confirmar venta desde Gestion de ventas:

- se valida stock con bloqueo.
- se descuenta `cdi_inv`.

Al marcar incompleta o cancelar:

- no se descuenta inventario.

## Revision desde ventas

El panel de Gestion de ventas ya contempla ventas `en_revision`, `registrada`, `incompleta` y `cancelada`.

La revision debe:

1. Confirmar solo si el monto verificado es igual al total.
2. Actualizar `venta_pagos.est_vpg = confirmado`.
3. Cambiar la venta a `registrada`.
4. Descontar inventario en ese momento.
5. Marcar como `incompleta` o `cancelada` si el pago no cubre el total o el comprobante es invalido.
