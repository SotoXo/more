# Fase 5 - Revision de comprobantes en panel vendedor

## Alcance

Esta fase habilita el modulo `/vendedor/pagos` para revisar comprobantes subidos por clientes. No registra pagos del vendedor: los pagos presenciales/QR/transferencia cargados desde ventas o reservas del panel vendedor siguen en sus flujos propios y quedan confirmados automaticamente alli.

Rutas agregadas bajo `auth` + `vendedor`:

- `GET /vendedor/pagos` (`vendedor.pagos.index`)
- `GET /vendedor/pagos/{pago}` (`vendedor.pagos.show`)
- `POST /vendedor/pagos/{pago}/aprobar` (`vendedor.pagos.approve`)
- `POST /vendedor/pagos/{pago}/rechazar` (`vendedor.pagos.reject`)

El parametro `{pago}` usa claves compuestas: `venta-{id_venta_pago}` o `reserva-{id_reserva_pago}`.

## Criterio de comprobante de cliente

Como no existe un campo de uploader/origen en `venta_pagos` ni `reserva_pagos`, el criterio queda documentado asi:

- Ventas: pago con comprobante, `est_vpg` en revision/aprobado/rechazado, y venta asociada con `tiv_ven = online`.
- Reservas pendientes: pago con comprobante y `est_rpg = pendiente_revision`.
- Reservas ya revisadas desde este modulo: pago con comprobante, estado aprobado/rechazado y observacion marcada como `comprobante de cliente`.

Esto evita mezclar los pagos registrados por vendedor, especialmente los QR/transferencia confirmados automaticamente en Fase 3 y Fase 4.

## Pantallas

`resources/js/Pages/Vendedor/Payments/Index.tsx` muestra:

- Pestañas Pendientes, Aprobados y Rechazados.
- Filtros por estado, tipo, metodo, fecha y busqueda.
- Tabla con fecha, tipo, numero, cliente, metodo, monto, estado, origen y accion de revision.

`resources/js/Pages/Vendedor/Payments/Show.tsx` muestra:

- Datos del pago, cliente y operacion asociada.
- Monto declarado, esperado, pagado y saldo.
- Vista del comprobante imagen/PDF.
- Historial basico de pagos de la misma venta o reserva.
- Acciones de aprobar o rechazar cuando el comprobante sigue pendiente.

La aprobacion pide confirmacion con el texto: `¿Confirmar que el comprobante es correcto?`.
El rechazo exige motivo.

## Servicios y estados

La logica nueva vive en:

- `app/Http/Controllers/Vendedor/VendedorPaymentReviewController.php`
- `app/Services/Vendedor/VendedorPaymentReviewService.php`

Para aprobar o rechazar no se crean estados paralelos. Se reutilizan estados existentes:

- `pendiente_revision`
- `confirmado`
- `rechazado`

Ventas online:

- Aprobar delega en `App\Services\Superadmin\SaleService::confirmOnlineSale`.
- Rechazar delega en `App\Services\Superadmin\SaleService::markOnlineSaleIncomplete`.

Reservas:

- Aprobar delega en `App\Services\Superadmin\ReservationService::verifyLatestPendingPayment`.
- Rechazar delega en `App\Services\Superadmin\ReservationService::rejectLatestPendingPayment`.

## Inventario y Kardex

`VendedorPaymentReviewService` no escribe inventario ni Kardex directamente y no calcula disponibilidad como `cdi_inv - cre_inv`.

Cuando se aprueba o rechaza, se respetan los servicios centrales existentes. Esos servicios ya tienen la responsabilidad de consumir o liberar stock separado cuando el flujo comercial lo requiere:

- Al confirmar una venta online, el servicio central confirma el stock previamente separado y evita duplicar la salida inicial.
- Al marcar incompleta una venta online o rechazar una reserva, el servicio central libera el stock separado si ese era el comportamiento vigente.

La Fase 5 no agrega movimientos de inventario nuevos ni duplica movimientos de Kardex por cuenta propia.

## Dashboard

La metrica `Pagos pendientes` ahora cuenta comprobantes reales de clientes:

- Ventas online con pago `pendiente_revision` y comprobante.
- Reservas con pago `pendiente_revision` y comprobante.

## Auditoria

Ademas de los logs centrales ya existentes, esta fase registra:

- `pago_cliente_revisado_por_vendedor`
- `pago_cliente_aprobado_por_vendedor`
- `pago_cliente_rechazado_por_vendedor`

Si quien opera desde el panel vendedor es superadmin, las acciones cambian a:

- `pago_cliente_revisado_por_superadmin`
- `pago_cliente_aprobado_por_superadmin`
- `pago_cliente_rechazado_por_superadmin`

## Verificacion

No se debe ejecutar aprobacion ni rechazo en pruebas visuales sin elegir datos reales, porque ambas acciones cambian estados comerciales.
