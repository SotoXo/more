# Auditoria de ventas, reservas, recojo, estados y notificaciones - Mati Boutique

Fecha: 2026-05-24

Alcance: ventas directas, ventas online, ventas desde reserva, reservas, bolsa, checkout de reserva, recojo, estados comerciales, estados logisticos, recibos, comprobantes, rutas y notificaciones internas.

Restricciones aplicadas: no se corrigio codigo funcional, no se crearon migraciones, no se toco base de datos y no se ejecuto servidor de desarrollo.

## 1. Resumen ejecutivo

El sistema ya tiene una base comercial funcional para ventas y reservas:

- Compra directa online crea venta en revision, detalle de venta y pago.
- Reserva crea reserva, detalle, pago, fechas de recojo y separa inventario.
- La reserva puede confirmarse, rechazarse, cancelarse, vencerse, marcarse lista para recojo y completarse como venta.
- Existen recibos de reserva y venta, visibles despues de validaciones.

Pero hay brechas importantes antes de produccion:

- El modulo de ventas no es realmente responsivo; depende de una tabla horizontal con demasiadas columnas.
- Ventas no tienen estado logistico propio.
- Reservas mezclan estado comercial y logistico dentro de `reservas.est_res`.
- Compra directa confirmada queda como `registrada`, pero no pasa a `lista para recojo`, `pendiente de traslado` ni `entregada`.
- No existe modulo operativo de notificaciones internas.
- El boton Reservar del detalle de producto agrega siempre 1 unidad; la cantidad puede editarse despues en la bolsa, pero no se elige antes de agregar.
- La regla "primero que paga consigue la reserva" se cumple parcialmente: la bolsa no separa stock y el backend valida stock al confirmar, pero actualmente la reserva separa stock al crear la reserva `por_procesar`, antes de que el admin confirme el comprobante.

Estado general: funcional para operacion basica, incompleto para logistica, notificaciones y experiencia responsiva.

## 2. Estado actual de ventas

Estado actual: funcional comercialmente, incompleto logisticamente.

Que funciona:

- Existen ventas en revision: `ventas.est_ven = en_revision`.
- Existen ventas confirmadas: `ventas.est_ven = registrada`.
- Existen ventas incompletas y canceladas: `incompleta`, `cancelada`.
- La compra online crea `ventas`, `detalle_ventas` y `venta_pagos`.
- La confirmacion de pago pasa la venta a `registrada` y confirma `venta_pagos`.
- Las ventas desde reserva se crean con `tiv_ven = reserva` y `est_ven = registrada`.
- Existe recibo de venta para ventas registradas.
- Cliente puede ver compras en `cuenta/pedidos`.
- Admin/superadmin opera ventas desde ResourceController.

Que no funciona o falta:

- No existe estado logistico de venta.
- No hay campos de venta para `lista_recojo`, `pendiente_traslado`, `recogida` o `entregada`.
- No hay fecha disponible de recojo en ventas directas.
- No hay punto de recojo/origen de stock en ventas directas.
- No hay accion administrativa para marcar una venta confirmada como lista para recojo o entregada.
- Una venta confirmada deja de mostrar acciones operativas, salvo recibo.
- Venta desde reserva no tiene vinculo persistente con reserva; se infiere por numero `VEN-RES-{nre_res}`.

Riesgo:

- El negocio puede confirmar pagos, pero no controlar la entrega final de compras directas.
- El cliente puede saber que su venta fue confirmada, pero no si ya esta lista para recojo.
- Admin no tiene flujo claro para separar venta confirmada de venta entregada.

Prioridad: alta.

## 3. Estado actual de reservas

Estado actual: funcional, pero mezcla estados comerciales y logisticos.

Que funciona:

- Boton Reservar agrega producto a la bolsa de reservas.
- La bolsa permite cambiar cantidades con botones `+` y `-`.
- El checkout valida anticipo minimo 50% y maximo 100%.
- El comprobante es obligatorio para QR/transferencia.
- El backend valida stock disponible con `cdi_inv`.
- Al confirmar checkout se crea reserva `por_procesar`.
- Se crea `detalle_reservas` con cantidad, precio unitario y subtotal.
- Se crea `reserva_pagos`.
- Se calcula fecha disponible de recojo segun ubicacion de inventario.
- Admin puede aprobar o rechazar comprobante.
- Admin puede marcar reserva como `lista_recojo`.
- Admin puede completar/recoger reserva, cobrar saldo final si corresponde y convertirla en venta.
- No permite marcar como recogida si hay saldo pendiente.

Que no funciona o falta:

- En detalle de producto, Reservar envia `cantidad: 1`; no hay selector de cantidad para reserva antes de agregar.
- La cantidad si puede ajustarse despues en la bolsa.
- La reserva se separa al crear `por_procesar`, no cuando el admin confirma comprobante.
- `lista_recojo`, `completada`, `recogida` y `vencida` viven en `est_res`, mezclando estado comercial con logistico.
- No existe campo logistico separado para reserva.
- No existe notificacion automatica al cliente cuando queda lista para recojo.

Riesgo:

- El usuario puede sentir que no eligio cantidad al reservar desde producto.
- El stock queda retenido por reservas en revision aunque el comprobante todavia no este validado.
- La UI y reportes deben interpretar un solo campo para dos conceptos distintos.

Prioridad: alta para estados logisticos; media para selector de cantidad en producto.

## 4. Problemas de responsividad en ventas

Estado actual: no responsivo de forma adecuada.

La tabla de ventas muestra demasiadas columnas:

- Registro
- Cliente
- Vendedor
- Tipo
- Productos
- Total
- Metodo
- Estado
- Prendas
- SKU
- Fecha
- Consulta/acciones

El componente `SuperadminDataTable` usa una tabla `min-w-full` dentro de `overflow-x-auto`. Esto evita romper completamente la pantalla, pero en pantallas medianas o pequenas obliga a scroll horizontal y puede cortar acciones o contexto.

Datos que deberian moverse al detalle o a una vista expandible:

- Vendedor.
- Productos.
- SKU.
- Prendas.
- Metodo, si ya hay detalle de pago.

Recomendacion:

Opcion recomendada: A + D.

- Desktop: tabla con columnas esenciales.
- Movil/tablet: cards responsivas.
- Mover productos, SKU y vendedor al detalle de venta.

Columnas sugeridas para tabla desktop:

- Registro.
- Cliente.
- Tipo.
- Total.
- Estado comercial.
- Estado logistico futuro.
- Fecha.
- Acciones.

Prioridad: media-alta.

## 5. Estado comercial vs estado logistico

Estado actual: mezclado.

Estado comercial:

- Pago y validacion.
- Ejemplos actuales: `en_revision`, `registrada`, `incompleta`, `cancelada`, `por_procesar`, `confirmada`, `rechazada`, `anticipo_confirmado`, `pagado_total`, `pendiente_revision`, `confirmado`, `rechazado`.

Estado logistico:

- Preparacion, traslado, recojo y entrega.
- Ejemplos actuales o esperados: `lista_recojo`, `recogida`, `completada`, `vencida`, `pendiente_traslado`, `pendiente_preparacion`, `entregada`.

Mezclas detectadas:

- `reservas.est_res` mezcla revision comercial con recojo/logistica.
- `ventas.est_ven` solo cubre estado comercial; no cubre recojo ni entrega.
- `vencida` se usa como estado de reserva, pero logisticamente depende de fechas de recojo.
- `completada` y `recogida` se tratan como cierre de reserva y entrega, pero no existe una fecha de recogida dedicada.

Recomendacion clara:

- Mantener estados comerciales para pago/validacion.
- Agregar estados logisticos separados para reservas y ventas.
- En UI, mostrar ambos como badges independientes: "Pago/venta" y "Recojo/entrega".

## 6. Reglas de recojo por tienda/deposito

Regla esperada:

- TIENDA: lista para recojo inmediatamente, fecha disponible hoy.
- DEPOSITO: pendiente de traslado/preparacion, fecha disponible hoy + 3 dias.
- MIXTO: usar fecha mas lejana y estado pendiente de preparacion/mixto.

Estado actual en reservas:

- `ReservationPickupService` calcula fechas y origen:
  - Si alguna ubicacion es `DEPOSITO`, fecha disponible = hoy + 3 dias.
  - Si no, fecha disponible = hoy.
  - Si hay mas de una ubicacion, `origen_stock_res = MIXTO`.
  - Punto de recojo = `Recojo en tienda Mati Boutique`.
- La reserva se crea como `por_procesar`, no como `lista_recojo`.
- Al confirmar pago, queda `confirmada`.
- Existe accion manual `Lista para recojo`, pero no automatica segun ubicacion.

Estado actual en ventas directas:

- No se detecta calculo de recojo por ubicacion.
- No existen campos logisticos de venta.
- No existe accion para marcar lista para recojo, pendiente de traslado, recogida o entregada.

Estado actual en ventas desde reserva:

- Heredan indirectamente el flujo de reserva.
- La venta generada queda `registrada`.
- No queda estado logistico propio en la venta.

Cumplimiento:

- Reservas: calculo de fecha/origen existe, automatizacion de estado no.
- Ventas directas: no cumple.
- Ventas desde reserva: cumple cierre comercial, pero no trazabilidad logistica propia.

## 7. Riesgo de cantidad en reservas

Estado actual:

- En detalle de producto, el boton Reservar agrega siempre `cantidad: 1`.
- En bolsa de reservas, el cliente si puede aumentar o disminuir cantidad.
- Backend acepta `reservation_items.*.can_dre` y valida `min:1`.
- `CarritoController` valida stock al actualizar cantidad.
- `ReservationService` vuelve a validar stock al confirmar reserva.

Conclusion:

- El sistema permite cantidad, pero no desde el primer paso del producto.
- No reserva siempre una sola unidad si el cliente modifica cantidad en la bolsa.
- La UX no es ideal porque la seleccion de cantidad para reserva esta separada del boton Reservar.

Prioridad: media.

## 8. Regla "primero que paga consigue la reserva"

Regla esperada:

- Agregar a bolsa no asegura stock.
- La reserva real se asegura al confirmar checkout en backend.
- El backend valida stock con bloqueo.
- Si otro cliente ya gano el stock, el segundo recibe mensaje claro.

Estado actual:

- Agregar a bolsa no mueve inventario.
- Bolsa valida stock visible, pero no bloquea.
- Al confirmar checkout, `ReservationService::createReservation` usa transaccion.
- `ReservationInventoryService::reserve` usa `lockForUpdate`.
- Si no hay stock suficiente, se lanza error de validacion.
- La reserva se crea `por_procesar` y separa inventario inmediatamente.

Cumplimiento:

- Cumple que la bolsa no asegura stock.
- Cumple que el backend valida stock y bloquea inventario al crear reserva.
- Cumple que dos clientes pueden tener la misma prenda en bolsa.
- Cumple parcialmente el mensaje esperado: usa mensajes como "No hay stock suficiente para esta prenda/variante", no exactamente "Ya no hay stock disponible para esta prenda."
- No cumple literalmente si se interpreta "pago confirmado por admin" como momento de ganar reserva, porque hoy gana quien confirma checkout y crea reserva `por_procesar`, aun antes de validacion administrativa del comprobante.

Recomendacion:

- Mantener la separacion al crear reserva si el objetivo es evitar sobreventa despues de subir comprobante.
- Si negocio exige que solo comprobante validado gane stock, se necesita cambiar el modelo: reservas en revision sin separar o con hold temporal expirable. Esto tiene riesgo de sobreventa percibida y debe decidirse con cuidado.

Prioridad: alta como decision de negocio.

## 9. Estado de notificaciones

Estado actual: no existe modulo operativo de notificaciones.

Lo que existe:

- `User` usa trait `Notifiable` de Laravel.
- Existe controlador de notificacion de verificacion de email de Laravel.

Lo que no se encontro:

- Tabla `notifications`.
- Migracion de notifications.
- Clases `app/Notifications/*`.
- Eventos/listeners comerciales.
- Componente de campana/notificaciones.
- Icono de notificaciones internas.
- Bandeja de notificaciones para cliente/admin.
- Notificaciones de ventas, reservas, recojo, vencimientos o comprobantes pendientes.

Notificaciones minimas recomendadas:

Cliente:

- Compra enviada a revision.
- Compra confirmada.
- Compra lista para recojo.
- Compra cancelada.
- Reserva enviada a revision.
- Reserva confirmada.
- Reserva lista para recojo.
- Reserva rechazada.
- Reserva vencida.
- Recibo disponible.

Admin/vendedor:

- Nueva compra en revision.
- Nueva reserva en revision.
- Comprobante pendiente.
- Reserva lista para recojo.
- Reserva proxima a vencer.
- Stock bajo critico.

Recomendacion de fases:

1. Crear base de notificaciones internas con tabla `notifications` de Laravel y dropdown/campana.
2. Emitir notificaciones desde acciones criticas de venta/reserva.
3. Agregar recordatorios programados para reservas proximas a vencer.
4. Opcional posterior: correo/WhatsApp si el negocio lo requiere.

Prioridad: alta para operacion.

## 10. Recibos y comprobantes

Estado actual: funcional con limites.

Comprobante/voucher:

- Lo sube el cliente en compra online o reserva.
- QR y transferencia requieren archivo.
- Admin revisa desde acciones de venta/reserva.
- Estados de pagos: `pendiente_revision`, `confirmado`, `rechazado`.

Recibo emitido por Mati Boutique:

- Recibo de reserva disponible cuando hay pago confirmado o `est_pago_res` confirmado.
- Recibo de venta disponible cuando `ventas.est_ven = registrada`.
- Recibo final de venta desde reserva disponible si existe venta generada.
- Cliente solo puede ver sus recibos por rutas de cuenta.
- Admin puede ver recibos por rutas de superadmin.
- No aparece recibo de venta antes de confirmar venta.

Limites:

- No hay persistencia de recibos como documento/snapshot.
- Recibo de venta desde reserva depende de detectar venta por numero.
- El recibo de venta dice "venta confirmada", pero no refleja estado logistico porque no existe.

Prioridad: media.

## 11. Rutas revisadas

Comando ejecutado:

```bash
./vendor/bin/sail artisan route:list
```

Resultado: exitoso. Se listaron 65 rutas.

Rutas relevantes detectadas:

```text
GET    /carrito
GET    /bolsa
POST   /bolsa/confirmar
GET    /checkout
POST   /compras/online
POST   /api/carrito/agregar
PATCH  /api/carrito/{varianteId}
DELETE /api/carrito/{varianteId}
GET    /cuenta/pedidos
GET    /cuenta/reservas
GET    /mis-compras/{venta}/recibo
GET    /mis-reservas/{reserva}/recibo
GET    /superadmin/comercial/reservas/{reserva}/recibo
GET    /superadmin/comercial/ventas/{venta}/recibo
GET    /superadmin/{section}/{resource}
POST   /superadmin/{section}/{resource}
PUT    /superadmin/{section}/{resource}/{record}
PATCH  /superadmin/{section}/{resource}/{record}
DELETE /superadmin/{section}/{resource}/{record}
```

Rutas faltantes para flujo logistico:

- No hay ruta especifica para marcar venta lista para recojo.
- No hay ruta especifica para marcar venta pendiente de traslado.
- No hay ruta especifica para marcar venta recogida/entregada.
- No hay rutas de notificaciones internas.
- Reservas usan acciones por `PUT/PATCH /superadmin/comercial/reservas/{record}` via ResourceController, no rutas dedicadas.

## 12. Migraciones recomendadas

No se implementaron migraciones. Propuestas futuras:

### Estado logistico para ventas

- Tabla: `ventas`
- Campo: `est_logistico_ven`
- Tipo: `string(30)` con default sugerido `pendiente_preparacion`
- Motivo: separar estado de pago/venta de estado de recojo/entrega.
- Riesgo si no se agrega: ventas confirmadas seguiran sin trazabilidad de entrega.
- Prioridad: alta.

### Fechas y origen de recojo para ventas

- Tabla: `ventas`
- Campo: `fec_disponible_recojo_ven`
- Tipo: `timestamp nullable`
- Motivo: indicar desde cuando el cliente puede recoger una compra directa.
- Riesgo si no se agrega: no se puede notificar ni ordenar compras listas para recojo.
- Prioridad: alta.

- Tabla: `ventas`
- Campo: `fec_limite_recojo_ven`
- Tipo: `timestamp nullable`
- Motivo: controlar vencimiento operativo de compras no recogidas.
- Riesgo si no se agrega: no hay seguimiento de compras abandonadas.
- Prioridad: media.

- Tabla: `ventas`
- Campo: `punto_recojo_ven`
- Tipo: `string(150) nullable`
- Motivo: guardar punto de recojo de la venta.
- Riesgo si no se agrega: recibos/notificaciones no pueden informar lugar de recojo.
- Prioridad: media.

- Tabla: `ventas`
- Campo: `origen_stock_ven`
- Tipo: `string(30) nullable`
- Motivo: indicar TIENDA, DEPOSITO o MIXTO.
- Riesgo si no se agrega: no se puede calcular ni auditar traslado.
- Prioridad: media.

### Entrega de ventas

- Tabla: `ventas`
- Campo: `fec_entrega_ven`
- Tipo: `timestamp nullable`
- Motivo: registrar cuando la compra fue recogida/entregada.
- Riesgo si no se agrega: venta registrada se confunde con venta entregada.
- Prioridad: alta.

- Tabla: `ventas`
- Campo: `cod_usuario_entrega_ven`
- Tipo: `foreignId nullable -> users.id`
- Motivo: registrar quien entrego la prenda.
- Riesgo si no se agrega: no hay responsabilidad operativa.
- Prioridad: media.

### Estado logistico para reservas

- Tabla: `reservas`
- Campo: `est_logistico_res`
- Tipo: `string(30) nullable`
- Motivo: separar recojo/preparacion de `est_res`.
- Riesgo si no se agrega: `est_res` seguira mezclando pago, reserva y logistica.
- Prioridad: alta.

- Tabla: `reservas`
- Campo: `fec_recogida_res`
- Tipo: `timestamp nullable`
- Motivo: guardar fecha real de recojo.
- Riesgo si no se agrega: solo se sabe que esta completada, no cuando se entrego.
- Prioridad: media.

### Notificaciones

- Tabla: `notifications`
- Campo: estandar Laravel
- Tipo: migracion Laravel `notifications`
- Motivo: habilitar notificaciones internas para clientes y admins.
- Riesgo si no se agrega: el sistema depende de que usuarios revisen manualmente panel/cuenta.
- Prioridad: alta.

### Vinculo venta-reserva

- Tabla: `ventas`
- Campo: `cod_reserva_origen_ven`
- Tipo: `foreignId nullable -> reservas.id_reserva`
- Motivo: reemplazar inferencia por numero `VEN-RES-{nre_res}`.
- Riesgo si no se agrega: trazabilidad fragil de ventas desde reserva.
- Prioridad: alta.

## 13. Recomendaciones priorizadas

Prioridad alta:

1. Separar estado comercial y estado logistico en ventas.
2. Agregar estado logistico separado para reservas.
3. Agregar acciones de venta: marcar lista para recojo, pendiente de traslado, recogida/entregada.
4. Automatizar recojo al confirmar pago segun ubicacion: TIENDA hoy, DEPOSITO hoy + 3 dias, MIXTO fecha mas lejana.
5. Crear modulo de notificaciones internas.
6. Definir formalmente si "gana la reserva" al checkout con comprobante subido o al comprobante aprobado.
7. Agregar vinculo persistente venta-reserva.

Prioridad media:

1. Redisenar listado de ventas con tabla desktop y cards responsive.
2. Agregar selector de cantidad para reservas en detalle de producto.
3. Ajustar mensajes de stock agotado en checkout de reserva a "Ya no hay stock disponible para esta prenda."
4. Persistir fecha real de recogida/entrega.
5. Reflejar estado logistico en recibos y cuenta del cliente.

Prioridad baja:

1. Renombrar internamente rutas/API de bolsa que aun usan `carrito`.
2. Pulir etiquetas visuales de estados tecnicos.
3. Persistir snapshots de recibos si el negocio requiere auditoria documental.

## 14. Verificacion

### route:list

Resultado: exitoso.

```text
Showing [65] routes
```

### npm run build

Resultado: exitoso.

```text
> build
> tsc && vite build
✓ 2866 modules transformed.
✓ built in 1.48s
```

### php -l

No se ejecuto `php -l` porque esta auditoria no modifico codigo PHP funcional. Solo se creo documentacion.

