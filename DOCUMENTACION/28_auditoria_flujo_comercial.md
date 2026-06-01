# Auditoria y correccion critica del flujo comercial

## Alcance

Se audito el flujo comercial conectado entre storefront, carrito de compra, checkout online, gestion de ventas, gestion de reservas, confirmacion de pagos e inventario.

No se crearon migraciones ni se modifico la estructura de base de datos.

## Hallazgos principales

1. La navbar publica ya tenia acceso al carrito de compras con contador, pero no existia un icono rapido independiente para Mis reservas.
2. El panel administrativo estaba protegido solo por rol superadmin. El rol vendedor existia en el sistema, pero quedaba bloqueado aunque el flujo comercial lo contempla como operador.
3. La confirmacion de venta online permitia avanzar aun cuando una venta online no tenia un pago asociado en venta_pagos. Eso podia dejar una venta registrada sin comprobante auditado.
4. El carrito de compra online usa localStorage, conserva productos al navegar y no mueve inventario.
5. El checkout recalcula y valida productos en backend, exige pago del 100%, comprobante y metodo QR o transferencia bancaria.
6. Las reservas mantienen la regla de anticipo minimo, stock reservado y conversion a venta al recogerse.

## Correcciones aplicadas

### Icono de carrito

Se mantuvo el icono de carrito en la navbar publica, con contador basado en el carrito local de compra online.

El enlace apunta a:

- `/carrito`

Texto accesible:

- `Carrito de compras`

### Icono de reservas

Se agrego un icono visual de reservas junto al carrito en desktop y movil.

El enlace apunta a:

- usuario autenticado: `/cuenta/reservas`
- usuario no autenticado: `/login`

Texto accesible:

- `Mis reservas`

### Validacion de roles

Se mantuvo acceso total para superadmin.

Se agrego soporte operativo para vendedor, limitado a:

- dashboard administrativo.
- perfil administrativo.
- recursos de la seccion comercial.

El vendedor no obtiene acceso general a seguridad, catalogo, inventario ni otros modulos no comerciales desde el middleware.

El cliente sigue sin poder acceder a rutas administrativas.

### Confirmacion de pago en ventas online

Antes de confirmar una venta online se valida que exista un registro asociado en `venta_pagos`.

Si no existe pago/comprobante asociado, la accion se bloquea con mensaje controlado:

- `La venta online no tiene pago ni comprobante asociado para revisar.`

La confirmacion mantiene estas reglas:

- monto verificado debe ser igual al total.
- no puede ser menor al total.
- no puede ser mayor al total.
- solo ventas en revision o incompletas pueden confirmarse.
- el inventario se descuenta solo despues de validar el pago.

### Inventario

Compra online:

- agregar al carrito no mueve inventario.
- crear venta en revision no mueve inventario.
- confirmar venta descuenta `inventarios.cdi_inv`.
- cancelar o marcar incompleta no mueve inventario.

Reservas:

- crear reserva baja `cdi_inv` y sube `cre_inv`.
- cancelar, rechazar o vencer libera stock.
- completar reserva baja `cre_inv` y no vuelve a tocar `cdi_inv`.

## Flujo validado

### Carrito a venta en revision

Producto -> Carrito -> Checkout -> Pago 100% -> Venta en revision -> Revision administrativa -> Venta registrada.

### Reserva a venta

Reserva -> Comprobante -> Revision de anticipo/pago total -> Lista para recojo -> Pago final si corresponde -> Venta registrada.

## Reglas de pago

Compra online:

- requiere 100% del total.
- solo QR o transferencia bancaria.
- comprobante obligatorio.

Reserva:

- requiere minimo 50% para confirmar anticipo.
- no permite monto mayor al total.
- al cerrar como recogida debe quedar pagada al 100%.

## Pendientes y riesgos

1. El panel de vendedor queda limitado por middleware a la seccion comercial, pero el dashboard puede mostrar accesos visuales a otros modulos. Si se desea una experiencia mas fina para vendedor, conviene ocultar esos enlaces segun rol.
2. Las ventas online creadas antiguamente sin `venta_pagos` no podran confirmarse hasta que se regularice su pago/comprobante o se cree una accion correctiva.
3. El carrito usa localStorage, por lo que el backend sigue siendo la fuente real para stock y precios al confirmar checkout.

## Pruebas recomendadas

1. Verificar navbar publica en desktop y movil: carrito y reservas visibles.
2. Ingresar a Mis reservas sin login: debe ir a login.
3. Agregar varias prendas al carrito y confirmar que el contador se actualiza.
4. Enviar checkout con pago exacto y comprobante: debe crear venta en revision y venta_pagos.
5. Confirmar una venta online con monto exacto: debe pasar a registrada y descontar inventario una sola vez.
6. Intentar confirmar venta online sin venta_pagos: debe mostrar error controlado.
7. Confirmar anticipo de reserva y luego cerrar recogida con pago total.
8. Validar que cliente no pueda entrar al panel administrativo.
9. Validar que vendedor pueda operar seccion comercial pero no seguridad/catalogo/inventario.
