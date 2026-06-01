# Fase 2 - Logistica de recojo: modelos y servicios

## 1. Objetivo

Esta fase conecta los campos logisticos ya migrados en ventas y reservas, sin crear nuevas migraciones y sin implementar todavia la interfaz de notificaciones internas.

## 2. Campos logisticos conectados

### Ventas

- `est_logistico_ven`
- `fec_disponible_recojo_ven`
- `fec_limite_recojo_ven`
- `punto_recojo_ven`
- `origen_stock_ven`
- `fec_entrega_ven`
- `cod_usuario_entrega_ven`

El modelo `Venta` ahora permite asignar estos campos, castea las fechas logisticas como `datetime` y expone la relacion `usuarioEntrega()`.

### Reservas

- `est_logistico_res`
- `fec_recogida_res`
- `cod_usuario_entrega_res`

El modelo `Reserva` ahora permite asignar estos campos, castea `fec_recogida_res` como `datetime` y expone la relacion `usuarioEntrega()`.

## 3. Estados logisticos oficiales

### Ventas

- `pendiente_revision`
- `pendiente_preparacion`
- `pendiente_traslado`
- `lista_recojo`
- `entregada`
- `cancelada`

### Reservas

- `pendiente_revision`
- `pendiente_preparacion`
- `pendiente_traslado`
- `lista_recojo`
- `recogida`
- `cancelada`
- `vencida`

## 4. Reglas TIENDA / DEPOSITO / MIXTO

Se creo `App\Services\Superadmin\PickupLogisticsService` para centralizar la logica de recojo.

- Si todo el stock esta en `TIENDA`, el origen es `TIENDA`, el estado logistico es `lista_recojo`, la fecha disponible es hoy y el limite es hoy + 30 dias.
- Si todo el stock esta en `DEPOSITO`, el origen es `DEPOSITO`, el estado logistico es `pendiente_traslado`, la fecha disponible es hoy + 3 dias y el limite es fecha disponible + 30 dias.
- Si el stock es mixto, el origen es `MIXTO`, el estado logistico es `pendiente_preparacion`, la fecha disponible usa la fecha mas lejana y el limite es fecha disponible + 30 dias.
- El punto de recojo queda como `Recojo en tienda Mati Boutique`.

## 5. Reservas aprobadas

Cuando el admin aprueba un comprobante de reserva:

- Se mantiene la logica comercial actual.
- No se mueve inventario nuevamente.
- Se recalcula la logistica desde los inventarios de las prendas reservadas.
- `est_logistico_res` queda como `lista_recojo`, `pendiente_traslado` o `pendiente_preparacion` segun el origen de stock.
- Se actualizan tambien `fec_disponible_recojo_res`, `fec_limite_recojo_res`, `punto_recojo_res`, `origen_stock_res` y `ven_res`.

Por compatibilidad, no se elimino todavia el uso anterior de `est_res = lista_recojo` en la accion manual.

## 6. Ventas confirmadas

Cuando una venta online en revision se confirma:

- Se mantiene la logica de pago actual.
- Se mantiene la logica de inventario corregida: baja `cre_inv` y no vuelve a bajar `cdi_inv`.
- Se calcula la logistica desde los inventarios de los detalles de venta.
- `est_logistico_ven` queda como `lista_recojo`, `pendiente_traslado` o `pendiente_preparacion` segun el origen de stock.

Cuando una venta online se cancela o queda incompleta, `est_logistico_ven` pasa a `cancelada`.

## 7. Ventas desde reserva

Cuando una reserva se recoge y se convierte en venta:

- La venta se crea como `registrada`.
- La venta queda logisticamente como `entregada`.
- `fec_entrega_ven` queda con la fecha actual.
- `cod_usuario_entrega_ven` queda con el usuario que marco el recojo.
- Se copian desde la reserva el punto de recojo, origen de stock y fechas de recojo.
- La reserva queda con `est_logistico_res = recogida`.
- La reserva registra `fec_recogida_res` y `cod_usuario_entrega_res`.

## 8. Notificaciones

La tabla `notifications` ya existe segun la fase previa ejecutada por el usuario y `User` ya usa `Notifiable`.

En esta fase no se implemento todavia:

- Campana de notificaciones.
- Dropdown.
- Contador.
- Rutas para marcar como leida.
- Pagina de notificaciones.
- Clases `Notification` comerciales.

## 9. Pendientes

- Implementar UI de notificaciones internas.
- Crear rutas de notificaciones y marcado como leida.
- Mejorar responsividad del modulo de ventas.
- Revisar vistas que aun dependan visualmente de `est_res = lista_recojo`.
- Crear vinculo persistente venta-reserva si aun no existe.
- Agregar trazabilidad completa de movimientos con `inventario_movimientos` en una fase futura.

## 10. Verificacion tecnica

- Se ejecuto `php -l` via Sail sobre los archivos PHP modificados.
- Se debe validar manualmente el flujo con productos en `TIENDA`, `DEPOSITO` y combinaciones `MIXTO`.
