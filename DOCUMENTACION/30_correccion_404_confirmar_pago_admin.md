# Correccion 404 al confirmar pagos en panel admin

## Problema

Al confirmar el pago de una venta online desde el panel administrativo se mostraba una pantalla blanca con `404 | Not Found`.

La accion del frontend no llamaba una ruta dedicada `confirmar-pago`; usaba la fachada del panel:

- `PUT /superadmin/comercial/ventas/{id}`
- nombre de ruta: `superadmin.resources.update`
- payload: `sale_action=confirm_online`, `mark_incomplete` o `cancel_online`

## Causa

La ruta existia en `route:list`, pero `ResourceControllerActions::update()` bloqueaba el recurso antes de ejecutar la accion.

La condicion solo permitia:

- recursos CRUD generales.
- `comercial.reservas`.

Como `comercial.ventas` no pertenece a `supportsCrud()`, Laravel abortaba con 404 antes de llegar a la logica de:

- confirmar venta online.
- marcar venta incompleta.
- cancelar venta online.

## Correccion aplicada

Se ajusto la validacion inicial de `ResourceControllerActions::update()` para permitir acciones operativas de ventas cuando el request trae `sale_action`.

Esto mantiene cerrada la actualizacion generica de ventas, pero habilita las acciones administrativas necesarias:

- `confirm_online`
- `mark_incomplete`
- `cancel_online`

Las reservas ya estaban permitidas por esa fachada y se mantienen igual.

## Flujo correcto

### Venta online en revision

1. El superadmin o vendedor abre la venta en revision.
2. Presiona confirmar venta.
3. El frontend envia `PUT /superadmin/comercial/ventas/{id}` con `sale_action=confirm_online`.
4. El backend valida el monto verificado.
5. Si el pago cubre el total:
   - `ventas.est_ven = registrada`.
   - `venta_pagos.est_vpg = confirmado`.
   - se asigna el usuario que verifica.
   - se descuenta inventario una sola vez.
6. La respuesta vuelve con redirect y flash message, no JSON puro.

### Reserva

Las reservas siguen usando:

- `PUT /superadmin/comercial/reservas/{id}`
- `reservation_action=verify_payment`, `reject_payment`, `complete` o similares.

## Validacion de rol

El middleware `superadmin` permite:

- superadmin: acceso completo al panel.
- vendedor: acceso operativo a rutas `superadmin.resources.*` dentro de la seccion `comercial`.
- cliente: sin acceso a acciones administrativas.

## Pruebas

Ejecutar:

```bash
./vendor/bin/sail artisan route:list
npm run build
```

Pruebas manuales recomendadas:

1. Abrir una venta online en revision.
2. Confirmar pago con monto exacto.
3. Verificar que no aparece `404 | Not Found`.
4. Confirmar que la venta pasa a `registrada`.
5. Verificar que el inventario baja una sola vez.
6. Abrir una reserva pendiente y confirmar anticipo o pago total.
7. Verificar que la reserva cambia de estado sin 404.
