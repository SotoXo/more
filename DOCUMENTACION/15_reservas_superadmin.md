# Reservas superadmin

## Objetivo

El modulo de reservas del panel superadmin permite separar stock real para un cliente, registrar comprobantes, verificar pagos humanos y controlar recojo. La reserva no crea ventas.

## Flujo de registro

1. El superadmin abre `Comercial > Reservas > Nueva reserva`.
2. Selecciona cliente.
3. El responsable se toma del usuario autenticado.
4. Agrega una o varias variantes con cantidad.
5. El sistema calcula subtotal, descuento, total y anticipo minimo.
6. Registra monto declarado, metodo y comprobante.
7. El sistema valida stock y anticipo minimo del 50%.
8. Al guardar, la reserva queda `por_procesar` y el pago queda `pendiente_revision`.

## Verificacion

Desde el detalle de reserva se puede:

- aprobar pago con monto confirmado;
- rechazar comprobante con observacion;
- agregar pago posterior con comprobante;
- marcar como lista para recojo;
- cancelar;
- completar;
- marcar como vencida.

## Movimiento de stock

Al registrar una reserva:

- `inventarios.cdi_inv = cdi_inv - cantidad reservada`
- `inventarios.cre_inv = cre_inv + cantidad reservada`

Al cancelar, rechazar o vencer:

- `inventarios.cdi_inv = cdi_inv + cantidad reservada`
- `inventarios.cre_inv = cre_inv - cantidad reservada`

Al completar:

- `inventarios.cre_inv = cre_inv - cantidad reservada`

La operacion usa transacciones y `lockForUpdate` sobre inventario.

## Fechas de recojo

- `TIENDA`: disponible hoy y limite hoy + 30 dias.
- `DEPOSITO`: disponible hoy + 3 dias y limite disponible + 30 dias.
- Mixto: usa la fecha mas lejana y origen `MIXTO`.

## Reservas vencidas visuales

Una reserva se muestra visualmente vencida cuando esta en `por_procesar`, `confirmada` o `lista_recojo` y `fec_limite_recojo_res < now()`.

El sistema no cambia automaticamente el estado. La accion humana `Marcar vencida` libera stock.

## Pendientes

- Convertir reserva en venta cuando se trabaje el modulo de ventas.
- Comando programado para marcar reservas vencidas automaticamente.
