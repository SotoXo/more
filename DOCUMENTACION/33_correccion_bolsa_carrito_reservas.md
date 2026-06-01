# Correccion de navegacion: carrito, bolsa y perfil

## Concepto corregido

La navegacion publica separa tres conceptos:

- Carrito: compras directas.
- Bolsa: reservas con anticipo.
- Perfil: seguimiento, historial y cuenta del cliente.

## Carrito de compras

Ruta final: `/carrito`.

El carrito usa el estado local de compra directa y la clave:

- `mati_boutique_purchase_cart`

Flujo:

Producto -> carrito -> checkout -> pago 100% -> comprobante -> venta en revision.

La pantalla del carrito muestra productos para compra directa, cantidades, precios, subtotales, total y el boton "Continuar con la compra". No muestra anticipo, total reserva ni textos de reserva.

## Bolsa de reservas

Ruta final: `/bolsa`.

La bolsa usa la sesion de Laravel del flujo de reservas y puede armarse antes del login:

- `carrito`

Flujo:

Producto -> bolsa -> resumen de reserva -> pago minimo 50% y maximo 100% -> comprobante -> reserva en revision.

La bolsa muestra "Tus prendas para reservar", total reserva, anticipo minimo requerido, monto maximo permitido y el boton "Continuar con la reserva".

La confirmacion final usa:

- `POST /bolsa/confirmar`

## Botones de producto

En el detalle de producto hay dos acciones:

- Comprar: agrega la prenda al carrito de compra directa y muestra "Prenda agregada al carrito."
- Reservar: agrega la prenda a la bolsa de reservas y muestra "Prenda agregada a la bolsa de reservas."

Ninguna accion abre el formulario de pago dentro del detalle del producto.

## Iconos y contadores

La navbar publica muestra dos accesos independientes:

- Icono de carrito: lleva a `/carrito` y cuenta productos de compra directa desde `mati_boutique_purchase_cart`.
- Icono de bolsa: lleva a `/bolsa` y cuenta prendas de reserva desde la sesion `carrito`.

Los contadores no comparten estado ni rutas.

## Perfil del cliente

El perfil no reemplaza al carrito ni a la bolsa. Es solo para seguimiento e historial.

Opciones principales:

- Mi cuenta.
- Reservas.
- Compras.
- Cerrar sesion.

En Perfil -> Reservas (`/cuenta/reservas`) el cliente ve:

- Reservas actuales.
- Historial de reservas.
- Historial de compras.

## Seguridad

El cliente puede agregar prendas al carrito de compra y a la bolsa de reservas antes de confirmar.

El checkout de compra y la confirmacion final de reserva requieren autenticacion.

Las vistas de cuenta filtran informacion por el usuario autenticado:

- Reservas: `cod_usuario_cliente_res = auth()->id()`.
- Compras: `cod_usuario_cliente_ven = auth()->id()`.

El cliente no puede ver reservas o compras de otros usuarios desde el storefront.
