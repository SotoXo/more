# Reserva a venta y gestion de ventas

## Problema corregido

Una reserva recogida debe verse como una venta real en Mati Boutique. La conversion ya creaba registros comerciales, pero la gestion de ventas no mostraba con claridad el origen desde reserva, cliente, vendedor, prendas, cantidades ni SKU.

## Flujo reserva a venta

Al usar la accion `Marcar como recogido y registrar venta`:

1. Se valida que la reserva no este cancelada, rechazada, vencida ni completada.
2. Se valida que tenga detalles de reserva.
3. Se valida que no exista saldo pendiente o se registra el pago restante.
4. Se valida stock reservado suficiente en `inventarios.cre_inv`.
5. Se crea una venta real en `ventas`.
6. Se crean sus lineas en `detalle_ventas`.
7. Se baja solamente `cre_inv`.
8. Se marca la reserva como `completada`.

Todo ocurre dentro de una transaccion.

## Datos guardados en ventas

La venta creada desde reserva guarda:

- `cod_usuario_cliente_ven`: cliente de la reserva.
- `cod_usuario_vendedor_ven`: usuario que marca la reserva como recogida; si no existe, responsable de la reserva.
- `tiv_ven`: `reserva`.
- `sub_ven`: subtotal de la reserva.
- `des_ven`: descuento de la reserva.
- `tot_ven`: total de la reserva.
- `mpa_ven`: ultimo metodo confirmado de pago o `reserva`.
- `est_ven`: `registrada`.
- `fec_ven`: fecha actual.
- `nve_ven`: referencia unica basada en la reserva cuando es posible (`VEN-RES-{nre_res}`), con fallback al correlativo de ventas.

## Datos guardados en detalle_ventas

Cada linea de `detalle_reservas` se copia a `detalle_ventas`:

- variante.
- cantidad.
- precio unitario congelado.
- subtotal congelado.
- fecha actual.

No se recalcula el precio desde la variante.

## Gestion de ventas

El listado de ventas muestra tambien ventas provenientes de reservas:

- cliente.
- vendedor.
- tipo/origen.
- badge `Desde reserva` para `tiv_ven = reserva`.
- productos vendidos.
- total.
- metodo de pago.
- estado.
- cantidad de prendas.
- SKU.
- fecha.

El detalle de venta muestra los productos vendidos con:

- imagen si existe.
- nombre de prenda.
- codigo de prenda.
- SKU.
- color.
- talla.
- cantidad.
- precio unitario.
- subtotal.

## Reportes y dashboard

Las ventas con `tiv_ven = reserva` se leen desde la misma tabla `ventas`, por lo tanto se incluyen en:

- conteo de ventas.
- total vendido.
- productos mas vendidos desde `detalle_ventas`.
- ventas recientes.
- ventas por metodo y vendedor.

La tabla del reporte de ventas ahora marca el origen `Desde reserva` y enlaza al detalle correcto en `/superadmin/comercial/ventas/{id}`.

## Inventario

La reserva ya desconto disponible al crearse:

- reserva: `cdi_inv` baja y `cre_inv` sube.
- recogido/venta: `cre_inv` baja.
- `cdi_inv` no se descuenta nuevamente.

Ejemplo:

- Antes: `cdi_inv = 10`, `cre_inv = 0`.
- Reserva 2: `cdi_inv = 8`, `cre_inv = 2`.
- Recogido: `cdi_inv = 8`, `cre_inv = 0`.

## Evitar duplicados

No se crea una venta duplicada porque:

- el boton no debe mostrarse para reservas completadas.
- backend bloquea reservas ya completadas.
- el numero de venta intenta usar la referencia de reserva y debe ser unico.

Pendiente futuro:

- Agregar `cod_reservas_ven` a `ventas` para trazabilidad directa reserva a venta.
