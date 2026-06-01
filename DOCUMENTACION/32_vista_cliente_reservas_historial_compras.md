# Vista cliente: reservas actuales, historial y compras

## Objetivo

Se separo la vista del cliente para que "Mis reservas" no mezcle reservas activas con reservas finalizadas.

La pantalla de reservas del cliente ahora muestra tres apartados:

1. Mis reservas.
2. Historial de reservas.
3. Mis compras.

## Reservas actuales

La pestana "Mis reservas" muestra solamente reservas que todavia requieren seguimiento:

- `por_procesar`
- `confirmada`
- `lista_recojo`

No se muestran aqui reservas cerradas, canceladas, vencidas o rechazadas.

Mensajes visibles:

- `por_procesar`: "Estamos revisando tu reserva. Te notificaremos cuando sea confirmada."
- `confirmada`: "Tu reserva fue confirmada."
- `lista_recojo`: "Tu prenda esta lista para recojo."
- `anticipo_confirmado`: "Tu reserva fue confirmada con adelanto."
- `pagado_total`: "Tu reserva fue pagada completamente."

## Historial de reservas

La pestana "Historial de reservas" muestra reservas finalizadas o cerradas:

- `completada`
- `recogida`
- `cancelada`
- `vencida`
- `rechazada`

Muestra numero de reserva, fecha, productos, total, monto pagado, saldo, estado final y recibos disponibles.

## Mis compras

La pestana "Mis compras" muestra ventas asociadas al usuario autenticado:

- `ventas.cod_usuario_cliente_ven = auth()->id()`

Estados visibles:

- `en_revision`: "Estamos revisando tu comprobante."
- `registrada`: "Compra confirmada."
- `incompleta`: "Compra incompleta."
- `cancelada`: "Compra cancelada."

Muestra productos, total, metodo de pago, comprobante subido si existe y recibo de venta si la compra esta confirmada.

## Recibos

Reservas actuales:

- muestran recibo de reserva si el pago ya fue confirmado.

Historial de reservas:

- muestra recibo de reserva si existe.
- muestra recibo de venta si la reserva termino en venta.

Mis compras:

- muestra recibo de venta solo cuando `est_ven = registrada`.
- si la compra esta en revision, indica que el recibo estara disponible cuando el pago sea confirmado.

## Navegacion del cliente

El icono de reservas del storefront sigue llevando a la pantalla de reservas del cliente.

Desde esa pantalla el cliente puede cambiar entre:

- Mis reservas.
- Historial de reservas.
- Mis compras.

El carrito sigue siendo independiente y no se mezcla con reservas.

## Seguridad

Todas las consultas se filtran en backend por el usuario autenticado:

- reservas: `cod_usuario_cliente_res = auth()->id()`
- ventas: `cod_usuario_cliente_ven = auth()->id()`

Los recibos tambien validan pertenencia antes de renderizarse.

Las acciones administrativas como confirmar pago, cancelar administrativamente, marcar recogido o registrar venta siguen reservadas para el panel superadmin/vendedor.
