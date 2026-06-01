# Correccion reserva recogida con saldo pendiente

## Regla corregida

Una reserva recogida debe representar una compra cerrada. Por lo tanto no puede quedar con saldo pendiente ni con pago en adelanto.

Estado valido para una reserva recogida:

- `reservas.est_res = completada`
- `reservas.est_pago_res = pagado_total`
- `reservas.saldo_res = 0`
- venta creada en `ventas`
- detalles creados en `detalle_ventas`

## Flujo correcto

1. Cliente reserva y paga adelanto.
2. Superadmin/cajero confirma el adelanto.
3. La reserva queda con saldo pendiente.
4. Al recoger, se registra el pago final exacto.
5. Se actualiza el pago de la reserva a `pagado_total`.
6. Se crea la venta.
7. Se crean los detalles de venta.
8. Se marca la reserva como `completada`.

El estado visible es:

`Reserva y compra completada`

## Pago final obligatorio

Si una reserva tiene saldo pendiente, la accion de recogido solicita:

- monto recibido ahora.
- metodo de pago final.
- observacion opcional.

Validaciones:

- El monto recibido debe ser igual al saldo pendiente.
- No puede ser menor al saldo.
- No puede ser mayor al saldo.
- No se completa si queda saldo pendiente.

Mensaje esperado si se intenta completar sin saldo:

`La reserva aun tiene saldo pendiente. Registra el pago restante antes de marcarla como recogida.`

## Creacion de venta

La venta creada desde reserva guarda:

- `nve_ven`: `VEN-RES-{nre_res}` cuando es posible.
- `cod_usuario_cliente_ven`: cliente de la reserva.
- `cod_usuario_vendedor_ven`: usuario que marca recogido o responsable como respaldo.
- `tiv_ven`: `reserva`.
- `sub_ven`, `des_ven`, `tot_ven`: importes de la reserva.
- `mpa_ven`: ultimo metodo de pago confirmado o `reserva`.
- `est_ven`: `registrada`.
- `fec_ven`: fecha actual.

## Detalle de venta

Cada `detalle_reservas` se copia a `detalle_ventas`:

- variante.
- cantidad.
- precio unitario congelado.
- subtotal congelado.

No se recalcula con el precio actual de la variante.

## Inventario

Al crear la reserva:

- baja `cdi_inv`.
- sube `cre_inv`.

Al recoger y registrar venta:

- baja `cre_inv`.
- no vuelve a bajar `cdi_inv`.

## Reservas inconsistentes existentes

No se corrigen automaticamente.

Si una reserva figura como recogida/completada pero tiene saldo pendiente, pago incompleto o venta faltante, el panel muestra alerta operativa y accion correctiva:

`Registrar pago restante y generar venta`

La accion:

- registra el pago final confirmado.
- deja `saldo_res = 0`.
- deja `est_pago_res = pagado_total`.
- genera la venta si no existe.
- normaliza `est_res = completada`.
- no descuenta inventario nuevamente, porque se asume que la reserva ya habia sido marcada como recogida previamente.

## Evitar duplicados

Sin migraciones no existe `cod_reservas_ven`.

Protecciones actuales:

- no se muestra el flujo normal si la reserva ya esta completada.
- backend bloquea completar dos veces.
- la venta usa numero `VEN-RES-{nre_res}`.
- si ya existe esa venta, no se crea otra.

Pendiente futuro:

- agregar `cod_reservas_ven` a `ventas` para trazabilidad directa.
