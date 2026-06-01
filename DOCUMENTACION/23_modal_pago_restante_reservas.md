# Modal de pago restante en reservas

## Problema corregido

El detalle de reserva usaba formularios nativos del navegador para pedir datos de acciones administrativas. En el flujo de recogido aparecia un prompt como:

`localhost dice`

Esto no respetaba la experiencia visual de Mati Boutique.

## Nuevo flujo

La accion `Marcar como recogido y registrar venta` abre un modal propio del panel superadmin cuando la reserva tiene saldo pendiente.

El modal muestra:

- Total de la reserva.
- Monto ya confirmado.
- Saldo pendiente.
- Monto recibido ahora.
- Metodo de pago final.
- Observacion opcional.

El boton principal es:

`Registrar pago y finalizar venta`

## Validaciones en frontend

Antes de enviar la accion por Inertia se valida:

- monto recibido obligatorio.
- monto recibido igual al saldo pendiente.
- no permitir monto menor al saldo.
- no permitir monto mayor al saldo.
- metodo de pago final obligatorio.

Mensajes visibles:

- `Ingresa el monto recibido.`
- `El monto recibido debe completar el saldo pendiente.`
- `El monto no puede ser mayor al saldo pendiente.`
- `Selecciona el metodo de pago.`

Los errores se muestran dentro del modal.

## Acciones afectadas

El detalle de superadmin reemplaza `window.prompt()` y `window.confirm()` por un modal visual para las acciones de reserva:

- aprobar adelanto o pago total.
- rechazar comprobante.
- marcar lista para recojo.
- marcar como recogido y registrar venta.
- registrar pago restante y generar venta.
- marcar vencida.
- cancelar reserva.

La accion sigue usando Inertia con redirect y flash desde backend.

## Regla UX

En los flujos principales de reservas no se deben usar:

- `alert()`
- `confirm()`
- `prompt()`
- `window.alert()`
- `window.confirm()`
- `window.prompt()`

Las acciones deben usar componentes visuales del sistema.
