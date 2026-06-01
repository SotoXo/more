# Auditoria completa del flujo comercial - Mati Boutique

Fecha de auditoria: 2026-05-24

Alcance: catalogo publico, carrito de compras, bolsa de reservas, checkout de compra directa, checkout de reserva, ventas online en revision, confirmacion de ventas, reservas, pagos, inventario, comprobantes, recibos, historiales del cliente, reportes relacionados y rutas.

Restricciones aplicadas: no se corrigio codigo, no se crearon migraciones, no se tocaron datos reales y no se ejecuto ningun servidor de desarrollo.

## 1. Resumen ejecutivo

El flujo comercial esta implementado de forma amplia y ya cubre los caminos principales de compra directa y reserva: catalogo, seleccion de variante, carrito separado para compra, bolsa separada para reserva, checkouts, comprobantes, ventas en revision, reservas por procesar, confirmacion administrativa, recibos e historial de cliente.

Sin embargo, el sistema no esta listo para produccion sin correcciones previas. Los riesgos mas importantes estan en inventario, reportes/rutas y pagos online en revision.

Hallazgos principales:

- El carrito de compra directa y la bolsa de reservas estan separados funcionalmente, pero usan nombres internos confusos: la bolsa usa rutas/API/session con nombre `carrito`.
- La compra directa crea venta, detalle de venta y pago en revision, pero no separa inventario al recibir el comprobante. Esto puede causar sobreventa despues de que un cliente ya pago.
- La reserva si separa inventario al crear la reserva: baja `cdi_inv` y sube `cre_inv`.
- Hay inconsistencia critica de inventario: algunas consultas usan `cdi_inv` como disponible real y otras calculan disponible como `cdi_inv - cre_inv`.
- Reportes y dashboard conservan estados/rutas antiguas, incluyendo uso de estado `activa` y enlaces probables 404.
- Los recibos existen como vistas dinamicas, pero no hay persistencia formal de recibos emitidos.
- La conversion reserva -> venta existe, pero se vincula de forma fragil por numero `VEN-RES-{nre_res}` y no por llave foranea persistente.

Estado general: funcional con riesgos criticos. Recomendacion: no avanzar con nuevos modulos comerciales hasta corregir inventario, rutas de reportes y reglas de ventas online en revision.

## 2. Estado del carrito de compras

Estado actual: incompleto con riesgo medio.

Que funciona:

- Existe pagina de carrito de compras en `/carrito`.
- Usa estado propio del frontend mediante localStorage.
- La clave usada es `mati_boutique_purchase_cart`.
- Tiene contador propio con evento `mati-boutique:purchase-cart-updated`.
- Permite modificar cantidades y quitar productos.
- No mueve inventario antes del checkout, lo cual es correcto para carrito.
- Envia al checkout de compra directa en `/checkout`.

Que no funciona o queda debil:

- La clave no coincide con la sugerencia esperada `mati_cart_items`, aunque si esta separada.
- El stock usado para limitar cantidades viene desde el catalogo y puede estar calculado incorrectamente como `cdi_inv - cre_inv`.
- El carrito vive solo en navegador; si el stock cambia en servidor, el usuario puede llegar al checkout con cantidades obsoletas. El backend vuelve a validar, pero la experiencia puede fallar tarde.

Riesgos:

- Mensajes tardios de stock insuficiente al confirmar checkout.
- Posible confusion tecnica porque el flujo de reservas tambien usa rutas/API con palabra `carrito`.

Prioridad: media.

## 3. Estado de la bolsa de reservas

Estado actual: funcional con deuda de nombres y riesgo de confusion.

Que funciona:

- Existe pagina de bolsa en `/bolsa`.
- Usa sesion Laravel, no localStorage.
- La bolsa no mueve inventario al agregar productos, lo cual es correcto.
- El stock se separa recien al confirmar la reserva.
- Tiene contador propio en navbar mediante prop Inertia `reservation_bag_count` y evento `mati-boutique:reservation-bag-updated`.
- Permite editar cantidades y eliminar items.
- Envia al checkout/confirmacion de reserva en la misma pagina `/bolsa`.

Que no funciona o queda debil:

- Internamente usa session key `carrito`.
- Las rutas de API para bolsa son `/api/carrito/agregar`, `/api/carrito/{varianteId}` y `/api/carrito`.
- Esto no mezcla datos con el carrito de compra directa, pero si deja ambiguedad semantica fuerte para mantenimiento.

Riesgos:

- Futuros cambios pueden romper la separacion carrito/bolsa por nombres ambiguos.
- Integraciones futuras podrian asumir que `/api/carrito` pertenece a compra directa cuando en realidad alimenta reservas.

Prioridad: media.

## 4. Estado de checkout compra directa

Estado actual: funcional con riesgo critico de inventario.

Flujo real:

Producto -> Comprar -> Carrito -> Checkout -> Pago 100% -> Subir comprobante -> Venta en revision -> Admin confirma -> Venta registrada -> Inventario se descuenta.

Que funciona:

- El checkout existe en `/checkout`.
- Solo permite metodos online: `qr` y `transferencia_bancaria`.
- Exige comprobante.
- Exige que el monto declarado sea exactamente igual al total.
- Envia a `POST /compras/online`.
- Crea `ventas`.
- Crea `detalle_ventas`.
- Crea `venta_pagos`.
- La venta queda inicialmente en estado `en_revision`.
- El vendedor puede ser `null` durante revision.
- La confirmacion administrativa registra vendedor/verificador.

Que no funciona o queda debil:

- Al crear la venta en revision no se mueve inventario.
- Esto corresponde a la opcion 1: no reservar stock hasta confirmar venta.
- Si otro usuario compra o reserva el mismo producto antes de la revision, puede ocurrir sobreventa.
- La venta manual online desde panel administrativo puede quedar `en_revision` sin pago asociado si no se controla por UI/servicio.

Riesgos:

- Cliente paga y sube comprobante, pero el stock podria agotarse antes de que el admin confirme.
- Al confirmar, si ya no hay stock, la venta no podria registrarse correctamente.
- No hay estado transaccional que separe stock pagado en revision.

Prioridad: alta.

## 5. Estado de checkout reserva

Estado actual: funcional con riesgos controlables.

Flujo real:

Producto -> Reservar -> Bolsa de reservas -> Pago 50% a 100% -> Subir comprobante -> Reserva por procesar -> Admin revisa -> Reserva confirmada -> Cliente recoge -> Si falta saldo, paga saldo final -> Reserva completada -> Se genera venta final.

Que funciona:

- La reserva exige usuario autenticado y rol cliente activo.
- El anticipo minimo es 50%.
- No permite pagar mas que el total.
- QR y transferencia usan comprobante obligatorio en la reserva inicial.
- Crea `reservas`.
- Crea `detalle_reservas`.
- Crea `reserva_pagos`.
- Congela precio unitario y subtotal en `detalle_reservas`.
- Al crear reserva separa stock: baja `cdi_inv` y sube `cre_inv`.
- Si se rechaza, cancela o vence, libera stock: sube `cdi_inv` y baja `cre_inv`.
- Para completar/recoger exige saldo cero.
- Si falta saldo, registra pago final antes de completar.
- Convierte reserva a venta al completarse.

Que no funciona o queda debil:

- La conversion a venta no tiene una llave persistente venta-reserva.
- La deteccion de venta asociada depende del numero `VEN-RES-{nre_res}`.
- Los estados siguen siendo strings libres.
- La bolsa usa nombres internos de carrito.

Riesgos:

- Duplicidad o perdida de relacion si cambia el formato del numero de venta.
- Dificultad para auditar historicamente que venta salio de que reserva.

Prioridad: alta para relacion venta-reserva; media para nombres internos.

## 6. Estado de ventas

Estado actual: funcional con riesgos altos en venta online en revision.

Que funciona:

- Existen ventas online en estado `en_revision`.
- El panel de ventas permite acciones administrativas mediante ResourceController.
- Confirmar venta online valida monto total.
- Confirmar venta online cambia venta a `registrada`.
- Confirmar venta online confirma pago y registra verificador.
- Cancelar venta online marca venta `cancelada`.
- Marcar venta incompleta existe para pagos menores.
- Cliente no usa rutas directas de confirmacion administrativa; las acciones estan bajo rutas de superadmin.

Que no funciona o queda debil:

- La venta online no separa inventario al crearse.
- La confirmacion descuenta `cdi_inv`; esto evita doble descuento en el modelo actual, pero aumenta riesgo de sobreventa.
- La venta online manual creada desde admin puede no tener `venta_pagos`.
- No se detecta una tabla o campo persistente para origen de reserva.

Riesgos:

- Sobreventa.
- Ventas online sin pago asociado si se crean por camino administrativo no controlado.
- Trazabilidad incompleta de ventas generadas desde reservas.

Prioridad: alta.

## 7. Estado de reservas

Estado actual: funcional con algunas deudas tecnicas.

Que funciona:

- Reserva inicial queda `por_procesar`.
- Al confirmar pago pasa a `confirmada`.
- Rechazo de pago deja reserva `rechazada` y libera inventario.
- Cancelacion/vencimiento libera inventario.
- El pago final al recojo puede ser efectivo, QR o transferencia.
- QR/transferencia final requieren comprobante.
- No se permite completar con saldo pendiente.
- Al completar baja `cre_inv` y no vuelve a bajar `cdi_inv`.
- Genera venta final desde reserva.

Que no funciona o queda debil:

- Falta relacion persistente con la venta final.
- No hay estados controlados por enum o tabla.
- La auditoria de movimientos de inventario no queda persistida en una tabla de movimientos.

Riesgos:

- Dificultad para auditar cancelaciones, vencimientos, conversiones y ventas derivadas.
- Fragilidad ante cambios de nombres de estados.

Prioridad: alta.

## 8. Estado de pagos

Estado actual: funcional con riesgos de integridad y control.

Pagos de ventas:

- `venta_pagos` guarda monto declarado.
- Guarda monto verificado al confirmar.
- Guarda metodo, comprobante, estado, verificador, fecha y observacion.
- La compra online exige QR/transferencia y comprobante.
- La confirmacion exige que el monto verificado sea igual al total.
- Pago menor puede marcar venta incompleta.
- Comprobante invalido puede llevar a cancelacion/rechazo.

Debilidades en pagos de ventas:

- Venta online en revision puede existir sin separar stock.
- Venta online manual podria quedar sin `venta_pagos`.
- Estados de pago son strings libres.

Pagos de reservas:

- `reserva_pagos` guarda monto declarado, monto confirmado, porcentaje, metodo, comprobante, estado, verificador, fecha y observacion.
- Primer pago exige minimo 50% y maximo 100%.
- Pago final no permite exceder saldo.
- Efectivo en pago final no exige comprobante.
- QR/transferencia exigen comprobante.

Debilidades en pagos de reservas:

- Estados libres.
- No hay tabla de recibos emitidos.
- No hay tabla de auditoria separada para cambios de estado de pago.

Riesgos:

- Pagos confirmados dependen de disciplina de servicio/controlador, no de constraints fuertes.
- Dificultad para auditar modificaciones historicas.

Prioridad: alta para ventas online; media para reservas.

## 9. Estado de inventario

Estado actual: con riesgo critico.

Semantica recomendada:

- `cdi_inv` = cantidad disponible real.
- `cre_inv` = cantidad comprometida/separada.
- `stock_total_operativo` = `cdi_inv + cre_inv`.
- `stock_vendible` = `cdi_inv`.

Cumplimiento actual:

- Reserva creada: cumple la semantica recomendada. Baja `cdi_inv`, sube `cre_inv`.
- Reserva rechazada/cancelada/vencida: cumple. Sube `cdi_inv`, baja `cre_inv`.
- Reserva completada y convertida en venta: cumple. Baja `cre_inv`, no vuelve a bajar `cdi_inv`.
- Carrito de compras: correcto, no mueve inventario.
- Bolsa de reservas: correcto, no mueve inventario.
- Compra online en revision: no cumple la recomendacion mas segura. No mueve inventario.
- Venta online confirmada: descuenta `cdi_inv` al confirmar.

Problema critico:

- El backend de catalogo y reportes mezcla dos interpretaciones. Algunos puntos usan `cdi_inv` como disponible real; otros calculan `max(0, cdi_inv - cre_inv)`.
- Si `cdi_inv` ya representa disponible real, restar `cre_inv` reduce artificialmente el stock visible.
- Si `cdi_inv` representa total, las operaciones de reserva que bajan `cdi_inv` y suben `cre_inv` rompen la contabilidad.

Archivos probables:

- `app/Http/Controllers/PrendaController.php`: calcula stock de variante como `cdi_inv - cre_inv`.
- `app/Services/ProductMapper.php`: calcula stock disponible como `cdi_inv - cre_inv`.
- `app/Services/Superadmin/DashboardStatsService.php`: calcula stock disponible y bajo stock con `cdi_inv - cre_inv`.
- `app/Services/Superadmin/ReportQueryService.php`: reportes pueden leer inventario con semantica inconsistente.

Riesgos:

- Stock visible menor al real.
- Productos ocultos o marcados sin stock indebidamente.
- Sobreventa en compras online en revision.
- Doble descuento si se cambia un flujo sin normalizar toda la semantica.
- Imposibilidad de auditar movimientos sin tabla `inventario_movimientos`.

Prioridad: critica.

## 10. Estado de recibos

Estado actual: funcional como vista dinamica, incompleto como documento persistente.

Que funciona:

- Hay recibo de reserva para cliente y admin.
- Hay recibo de venta para cliente y admin.
- El cliente solo puede acceder a sus recibos.
- Admin puede acceder a recibos bajo rutas de superadmin.
- Recibo de reserva aparece cuando existe pago confirmado o estado de pago confirmado.
- Recibo de venta aparece cuando la venta esta `registrada`.
- La venta desde reserva puede tener recibo si se encuentra la venta generada.

Que no funciona o queda debil:

- No hay persistencia de recibo emitido como registro propio.
- No hay numero/documento de recibo persistido independiente.
- No hay snapshot historico del recibo; se renderiza desde datos actuales.
- La relacion recibo de venta desde reserva depende de encontrar la venta por numero.

Riesgos:

- Si cambian datos de cliente, producto, precio o formato, el recibo historico podria variar.
- Auditoria fiscal/operativa limitada.

Prioridad: media.

## 11. Estado de historiales del cliente

Estado actual: funcional con deuda de presentacion.

Que funciona:

- `Mis reservas actuales` separa estados `por_procesar`, `confirmada` y `lista_recojo`.
- `Historial de reservas` separa `completada`, `recogida`, `cancelada`, `vencida` y `rechazada`.
- `Historial de compras` lista ventas del cliente, incluyendo ventas online en revision, registradas, incompletas o canceladas.
- Las consultas filtran por usuario cliente autenticado.
- El cliente no ve registros de otros clientes segun las consultas revisadas.

Que no funciona o queda debil:

- No se detecto una ruta separada de `mis recibos`.
- Las compras tambien se cargan dentro de la pagina de reservas/cuenta, lo que puede duplicar informacion entre vistas.
- Si la UI muestra estados tecnicos directamente, pueden verse etiquetas crudas como `en_revision` o `por_procesar`.

Riesgos:

- Confusion para el cliente por estados tecnicos.
- Listados grandes sin paginacion pueden degradar experiencia.

Prioridad: media.

## 12. Estado de reportes relacionados

Estado actual: con errores probables.

Que funciona:

- Existen servicios de dashboard y reportes operativos.
- Leen ventas, reservas, inventario, pagos y productos bajo stock.
- Pueden listar ventas registradas y metricas generales.

Que no funciona:

- Se detecto uso del estado antiguo `activa` para reservas activas/vencidas.
- El flujo actual usa estados como `por_procesar`, `confirmada`, `lista_recojo`, `completada`, `rechazada`, `cancelada`, `vencida`.
- Hay enlaces probables rotos:
  - `/superadmin/inventario/existencias/{id}`
  - `/superadmin/reservas/{id}`
- La ruta real de reservas administrativas esta bajo `/superadmin/comercial/reservas/{record}`.
- La ruta real de stock administrativo esta bajo `/superadmin/inventario/stock/{record}`.
- Los reportes de inventario pueden usar calculos inconsistentes de stock disponible.

Riesgos:

- Dashboard con conteos incorrectos.
- Reportes de reservas activas en cero aunque existan reservas.
- Enlaces 404 desde reportes.
- Decisiones operativas basadas en stock incorrecto.

Prioridad: alta.

## 13. Errores encontrados

### Error critico: inventario con doble semantica

- Archivo probable: `app/Http/Controllers/PrendaController.php`, `app/Services/ProductMapper.php`, `app/Services/Superadmin/DashboardStatsService.php`.
- Flujo afectado: catalogo, detalle de producto, carrito, bolsa, reportes, dashboard.
- Causa probable: no esta definida de forma unica la semantica de `cdi_inv` y `cre_inv`.
- Riesgo: stock visible incorrecto, sobreventa o bloqueo falso de ventas.
- Prioridad: critica.
- Recomendacion: adoptar formalmente `cdi_inv` como disponible real y eliminar calculos `cdi_inv - cre_inv` para stock vendible.

### Error critico: compra online en revision no separa stock

- Archivo probable: `app/Services/Superadmin/SaleCheckoutService.php`.
- Flujo afectado: compra directa online.
- Causa probable: se eligio descontar inventario recien al confirmar venta.
- Riesgo: sobreventa despues de que el cliente ya pago.
- Prioridad: alta.
- Recomendacion: mover compra online en revision al esquema de separacion: al crear venta baja `cdi_inv` y sube `cre_inv`; al confirmar baja `cre_inv`; al cancelar/incompleta devuelve `cre_inv` a `cdi_inv`.

### Error alto: reportes usan estado antiguo `activa`

- Archivo probable: `app/Services/Superadmin/ReportQueryService.php`.
- Flujo afectado: reportes y dashboard.
- Causa probable: estados antiguos no alineados con flujo actual.
- Riesgo: reservas activas/vencidas mal contadas.
- Prioridad: alta.
- Recomendacion: alinear reportes con estados actuales.

### Error alto: rutas probables 404 en reportes

- Archivo probable: `app/Services/Superadmin/ReportQueryService.php`.
- Flujo afectado: reportes administrativos.
- Causa probable: enlaces antiguos o no alineados con ResourceController.
- Riesgo: admin entra a enlaces rotos.
- Prioridad: alta.
- Recomendacion: usar `/superadmin/comercial/reservas/{record}` y `/superadmin/inventario/stock/{record}`.

### Error alto: venta online administrativa puede no tener pago asociado

- Archivo probable: `app/Services/Superadmin/SaleService.php`.
- Flujo afectado: ventas online creadas desde panel.
- Causa probable: el servicio de venta manual no crea `venta_pagos` para ventas online.
- Riesgo: venta `en_revision` imposible de confirmar correctamente o sin comprobante.
- Prioridad: alta.
- Recomendacion: prohibir venta online manual sin pago/comprobante o crear pago asociado de forma obligatoria.

### Error medio: bolsa usa nombres de carrito

- Archivo probable: `app/Http/Controllers/CarritoController.php`, rutas `/api/carrito/*`, session key `carrito`.
- Flujo afectado: reservas.
- Causa probable: reutilizacion historica de concepto carrito.
- Riesgo: confusion de mantenimiento y errores futuros.
- Prioridad: media.
- Recomendacion: renombrar progresivamente a bolsa/reservation bag sin romper compatibilidad.

## 14. Riesgos criticos

- Sobreventa en compra directa online por no separar stock al crear venta en revision.
- Stock mostrado incorrectamente por calculos `cdi_inv - cre_inv`.
- Reportes operativos con estados antiguos y enlaces rotos.
- Falta de trazabilidad fuerte entre reserva y venta final.
- Falta de tabla de movimientos de inventario.
- Estados libres en reservas, ventas y pagos.
- Recibos no persistidos como documentos historicos.

## 15. Recomendaciones priorizadas

### Prioridad alta

1. Definir y aplicar una unica semantica de inventario: `cdi_inv` disponible real, `cre_inv` comprometido.
2. Cambiar compra online en revision a separacion de stock: baja `cdi_inv`, sube `cre_inv`.
3. Confirmar venta online sin descontar dos veces: si ya estaba separada, solo baja `cre_inv`.
4. Cancelar o marcar incompleta una venta online en revision devolviendo stock separado.
5. Corregir reportes que usan estado `activa`.
6. Corregir enlaces administrativos rotos en reportes.
7. Agregar vinculo persistente entre venta y reserva.
8. Bloquear o completar correctamente ventas online administrativas sin pago asociado.

### Prioridad media

1. Crear tabla `inventario_movimientos` para entradas, salidas, reservas, cancelaciones, ventas y ajustes.
2. Centralizar estados de ventas, reservas y pagos en enums/clases constantes o tablas controladas.
3. Renombrar internamente bolsa para no depender de session/API `carrito`.
4. Persistir recibos emitidos si se requiere auditoria historica.
5. Mejorar etiquetas de estados para cliente.
6. Agregar paginacion en historiales.

### Prioridad baja

1. Alinear nombres de localStorage con convencion sugerida si se decide estandarizar.
2. Pulir textos de UI para distinguir claramente comprobante del cliente y recibo de Mati Boutique.
3. Revisar componentes heredados o nombres antiguos para reducir deuda tecnica.

## 16. Resultado de verificaciones

### route:list

Comando ejecutado:

```bash
./vendor/bin/sail artisan route:list
```

Resultado: exitoso.

Resumen:

- Se listaron 65 rutas.
- Existen rutas publicas para catalogo, producto, carrito, bolsa y checkout.
- Existen rutas de cliente para cuenta, pedidos, reservas, favoritos y recibos.
- Existen rutas de superadmin mediante ResourceController.
- No se detectaron rutas dedicadas con nombre directo para confirmar pagos; esas acciones se manejan dentro de actualizaciones del ResourceController.

Rutas comerciales relevantes detectadas:

```text
GET    /carrito
GET    /bolsa
POST   /bolsa/confirmar
GET    /checkout
POST   /compras/online
POST   /api/carrito/agregar
PATCH  /api/carrito/{varianteId}
DELETE /api/carrito/{varianteId}
DELETE /api/carrito
POST   /api/favoritos/toggle
GET    /cuenta/pedidos
GET    /cuenta/reservas
GET    /mis-compras/{venta}/recibo
GET    /mis-reservas/{reserva}/recibo
GET    /superadmin/{section}/{resource}
GET    /superadmin/{section}/{resource}/{record}
POST   /superadmin/{section}/{resource}
PUT    /superadmin/{section}/{resource}/{record}
PATCH  /superadmin/{section}/{resource}/{record}
DELETE /superadmin/{section}/{resource}/{record}
```

Riesgo de rutas:

- `/superadmin/inventario/existencias/{id}` no corresponde al recurso real revisado para stock.
- `/superadmin/reservas/{id}` no corresponde al recurso real revisado para reservas comerciales.

### npm run build

Comando ejecutado:

```bash
npm run build
```

Resultado: exitoso.

Salida relevante:

```text
> build
> tsc && vite build
vite v8.0.3 building client environment for production...
✓ 2866 modules transformed.
✓ built in 915ms
```

### php -l via Sail

Archivos revisados:

```text
app/Services/Superadmin/SaleCheckoutService.php
app/Services/Superadmin/SaleService.php
app/Services/Superadmin/ReservationService.php
app/Services/Superadmin/ReportQueryService.php
```

Resultado: sin errores de sintaxis en los archivos revisados.

