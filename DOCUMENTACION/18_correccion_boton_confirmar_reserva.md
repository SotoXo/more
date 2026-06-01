# Correccion del boton Confirmar reserva

## Problema corregido

El boton `Confirmar reserva` de la bolsa de reservas no ejecutaba ninguna accion. El usuario podia hacer clic, pero no habia peticion, validacion, mensaje de exito ni errores visibles.

## Causa encontrada

El boton estaba declarado como `type="button"` y no tenia `onClick`. Tampoco estaba dentro de un formulario que enviara los datos necesarios para crear una reserva real:

- comprobante de pago;
- monto depositado;
- metodo de pago;
- items y cantidades del carrito.

## Correccion aplicada

- Se agrego la ruta `POST /bolsa/confirmar`.
- `CarritoController@confirmar` valida que el usuario sea cliente autenticado.
- El backend transforma los items de la bolsa en `reservation_items`.
- Se reutiliza `ReservationService` para validar stock, anticipo minimo del 50%, comprobante y creacion transaccional.
- La UI de `Carrito.tsx` ahora muestra campos de comprobante, monto depositado y metodo de pago.
- El boton ahora es `submit`, muestra `Procesando reserva...` mientras envia y se bloquea contra doble envio.
- Los errores de Inertia se muestran en una alerta visible.
- En caso de exito, se redirige a `Mis reservas` con mensaje de confirmacion.

## Pruebas realizadas

- `php -l` sobre archivos PHP modificados.
- `./vendor/bin/sail artisan route:list`.
- `npm run build`.

No se ejecuto `npm run dev` porque el servidor ya esta iniciado con Sail.
