# Fase 3A - Notificaciones internas backend

## 1. Tabla notifications

La tabla `notifications` ya existe mediante la migracion estandar de Laravel creada en la fase previa. El modelo `User` ya usa el trait `Notifiable`, por lo que se aprovecha el sistema nativo de Laravel Notifications con canal `database`.

En esta fase no se crearon migraciones nuevas ni se modifico la estructura de base de datos.

## 2. Clase Notification creada

Se creo `App\Notifications\CommercialNotification`.

Usa solo el canal `database` y guarda una estructura reutilizable:

- `title`
- `message`
- `type`
- `url`
- `icon`
- `severity`
- `entity_type`
- `entity_id`

Tipos usados o preparados:

- `reserva_creada`
- `reserva_confirmada`
- `reserva_rechazada`
- `reserva_cancelada`
- `reserva_lista_recojo`
- `reserva_recogida`
- `venta_creada`
- `venta_confirmada`
- `venta_cancelada`
- `venta_lista_recojo`
- `venta_entregada`
- `pago_pendiente`

## 3. Servicio CommercialNotificationService

Se creo `App\Services\CommercialNotificationService` para centralizar mensajes y destinatarios.

Metodos principales:

- `notifyCustomerReservationCreated`
- `notifyAdminsReservationPending`
- `notifyCustomerReservationConfirmed`
- `notifyCustomerReservationRejected`
- `notifyCustomerReservationCancelled`
- `notifyCustomerReservationReadyForPickup`
- `notifyCustomerReservationPickedUp`
- `notifyCustomerSaleCreated`
- `notifyAdminsSalePending`
- `notifyCustomerSaleConfirmed`
- `notifyCustomerSaleCancelled`
- `notifyCustomerSaleReadyForPickup`
- `notifyCustomerSaleDelivered`

El equipo operativo se resuelve buscando usuarios activos con roles activos `superadmin` o `vendedor`.

## 4. Eventos conectados

### Reservas

- Al crear reserva: se notifica al cliente y al equipo operativo.
- Al aprobar reserva: se notifica al cliente segun `est_logistico_res`.
- Al rechazar reserva: se notifica al cliente.
- Al cancelar reserva: se notifica al cliente.
- Al marcar reserva lista para recojo: se notifica al cliente solo si no estaba ya logisticamente lista.
- Al recoger reserva y convertirla en venta: se notifica al cliente.

### Ventas

- Al crear compra online en revision: se notifica al cliente y al equipo operativo.
- Al confirmar venta online: se notifica al cliente segun `est_logistico_ven`.
- Al marcar venta incompleta: se notifica al cliente como compra no confirmada.
- Al cancelar venta online: se notifica al cliente como compra no confirmada.

Las notificaciones conectadas dentro de transacciones se disparan mediante `DB::afterCommit` para evitar guardar avisos si la transaccion falla.

## 5. Rutas creadas

Todas las rutas estan protegidas por `auth` y devuelven JSON para uso futuro con `fetch` o `axios`.

- `GET /notificaciones`
- `GET /notificaciones/resumen`
- `POST /notificaciones/{id}/leer`
- `POST /notificaciones/leer-todas`

No se implemento una pagina visual ni navegacion Inertia para notificaciones en esta fase.

## 6. Datos devueltos

Cada notificacion devuelve:

- `id`
- `title`
- `message`
- `type`
- `url`
- `severity`
- `icon`
- `read_at`
- `created_at`
- `entity_type`
- `entity_id`

El resumen devuelve:

- `unread_count`
- `latest_notifications`

## 7. Seguridad aplicada

- Solo usuarios autenticados pueden consultar notificaciones.
- `markAsRead` busca la notificacion dentro de las notificaciones del usuario autenticado.
- No se permite marcar como leida una notificacion de otro usuario.
- La consulta limita la cantidad de registros devueltos.

## 8. Pendientes

- Crear campana visual en navbar.
- Crear dropdown de notificaciones.
- Mostrar contador en layout.
- Crear pagina completa de notificaciones si se decide usarla.
- Agregar notificaciones para vencimientos automaticos si se implementa un job.
- Agregar broadcast o email en fases futuras si el negocio lo requiere.
