# Correccion de cantidad en reservas y prioridad por checkout - Mati Boutique

Fecha: 2026-05-24

## 1. Reserva con cantidades

El detalle de producto ahora permite elegir la cantidad antes de agregar una prenda a la bolsa de reservas.

Reglas aplicadas:

- Minimo visual: 1 unidad.
- Maximo visual: stock disponible real de la variante (`cdi_inv`).
- Botones `+` y `-` para ajustar cantidad.
- Si se intenta superar el stock visible, se muestra: `No hay stock suficiente para reservar esa cantidad.`
- La cantidad seleccionada se envia a la bolsa mediante `/api/carrito/agregar`.

## 2. La bolsa no separa stock

La bolsa de reservas sigue siendo solo una intencion de reserva.

No se mueve inventario cuando:

- Se agrega una prenda a la bolsa.
- Se agrega la misma variante nuevamente.
- Se cambia cantidad.
- Se quita una prenda.
- Se navega por la bolsa.

Por lo tanto, estos campos no cambian hasta enviar el checkout de reserva:

- `inventarios.cdi_inv`
- `inventarios.cre_inv`

## 3. Stock separado al enviar checkout con comprobante

La reserva real se asegura cuando el cliente envia el checkout de reserva con:

- Usuario autenticado.
- Monto declarado entre 50% y 100%.
- Metodo permitido: QR o transferencia bancaria.
- Comprobante obligatorio.
- Stock suficiente validado en backend.

En ese momento se crea:

- `reservas` con `est_res = por_procesar`.
- `reservas.est_pago_res = pendiente_revision`.
- `detalle_reservas` con cantidad, precio congelado y subtotal.
- `reserva_pagos` con `est_rpg = pendiente_revision`.

## 4. El admin solo valida comprobante

No se cambio la logica correcta actual:

- Al aprobar comprobante, el admin no vuelve a mover inventario.
- El stock ya esta separado desde el checkout de reserva.
- Si el admin rechaza o cancela, el stock se devuelve.

## 5. Prioridad por checkout valido

La regla oficial queda:

`El primero que envia la reserva con comprobante y pasa la validacion de stock en backend consigue la reserva.`

No gana quien agrega primero a la bolsa.

Ejemplo:

1. Cliente A agrega una prenda a la bolsa.
2. Cliente B agrega la misma prenda a la bolsa.
3. Cliente B envia primero el checkout con comprobante.
4. Backend valida stock y separa inventario.
5. Cliente B obtiene la reserva.
6. Cliente A intenta enviar despues.
7. Si ya no hay stock, recibe error y no se crea reserva parcial.

## 6. Validacion backend con lockForUpdate

La validacion critica sigue en backend.

Al crear reserva, `ReservationService` ejecuta una transaccion y `ReservationInventoryService` bloquea la fila de inventario con `lockForUpdate`.

Reglas:

- Si `cdi_inv >= cantidad`, se separa stock.
- Si `cdi_inv < cantidad`, se lanza error.
- Si falla un item, se aborta toda la transaccion.
- No se crea reserva parcial.
- No se crea pago parcial.
- No queda inventario movido parcialmente.

Mensaje reforzado:

- `Ya no hay stock disponible para {nombre de prenda}.`

## 7. Movimiento de inventario

Semantica oficial mantenida:

- `cdi_inv` = stock disponible real.
- `cre_inv` = stock comprometido/separado.

Al crear reserva validada:

- `cdi_inv` baja por la cantidad reservada.
- `cre_inv` sube por la cantidad reservada.

Al rechazar o cancelar:

- `cdi_inv` sube.
- `cre_inv` baja.

Al aprobar comprobante:

- No se mueve inventario nuevamente.

## 8. Si ya no hay stock

Si otro cliente envio primero su reserva y ya no queda disponibilidad:

- El backend rechaza el checkout.
- No se crea la reserva.
- No se crea pago.
- No se mueve inventario.
- La UI muestra el error de validacion devuelto por backend.

## 9. Diferencia entre carrito y bolsa

Carrito de compra directa:

- Usa el flujo de compra.
- Pago 100%.
- Crea venta online en revision.
- No se modifico en esta fase.

Bolsa de reservas:

- Usa el flujo de reserva.
- Pago entre 50% y 100%.
- Crea reserva `por_procesar`.
- Separa stock recien al enviar checkout valido con comprobante.

## 10. Archivos modificados

- `resources/js/Pages/Shop/Product.tsx`
  - Selector de cantidad para reserva.
  - Envio de cantidad real a la bolsa.
  - Mensaje si se supera stock visible.

- `resources/js/Pages/Carrito.tsx`
  - Textos de checkout ajustados a "Pago de reserva" y "Enviar reserva a revision".
  - La bolsa mantiene cantidades editables, subtotales, total, anticipo minimo y maximo.

- `app/Http/Controllers/CarritoController.php`
  - Se elimina el limite fijo `max:10`.
  - Agregar la misma variante suma cantidad.
  - Si la suma supera stock visible, devuelve error en vez de ajustar silenciosamente.
  - Mensaje de login para finalizar reserva.

- `app/Services/Superadmin/ReservationService.php`
  - Mensajes de stock insuficiente mas claros.
  - Mensaje de autenticacion ajustado.
  - Carga de prenda para errores especificos por producto.

- `app/Services/Superadmin/ReservationInventoryService.php`
  - Mensaje especifico cuando el stock bloqueado con `lockForUpdate` ya no alcanza.

## 11. Pruebas realizadas

Verificaciones automaticas ejecutadas:

- `./vendor/bin/sail artisan route:list`: exitoso, 65 rutas listadas.
- `npm run build`: exitoso, TypeScript y Vite compilaron correctamente.
- `php -l` via Sail:
  - `app/Http/Controllers/CarritoController.php`: sin errores de sintaxis.
  - `app/Services/Superadmin/ReservationService.php`: sin errores de sintaxis.
  - `app/Services/Superadmin/ReservationInventoryService.php`: sin errores de sintaxis.

Pruebas manuales recomendadas:

1. Abrir producto con stock disponible.
2. Elegir cantidad 2 y presionar Reservar.
3. Confirmar que la bolsa recibe cantidad 2 y el inventario no cambia.
4. Agregar la misma variante con cantidad 1.
5. Confirmar que la bolsa queda con cantidad 3.
6. Cambiar cantidad en bolsa y revisar subtotales/total.
7. Intentar superar stock visible.
8. Enviar reserva valida y confirmar que se crea `por_procesar`.
9. Confirmar `detalle_reservas.can_dre`, `pun_dre`, `sub_dre`.
10. Confirmar `reserva_pagos.est_rpg = pendiente_revision`.
11. Confirmar que `cdi_inv` baja y `cre_inv` sube solo al enviar checkout.
12. Aprobar desde admin y confirmar que inventario no vuelve a moverse.
13. Rechazar/cancelar y confirmar que el stock vuelve.
14. Revisar que compra directa siga separada y funcionando.
