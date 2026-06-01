# Ventas superadmin

## Objetivo

El modulo de ventas del panel superadmin permite registrar ventas comerciales reales para Mati Boutique. Cada venta crea su cabecera, sus detalles y descuenta stock disponible del inventario.

## Flujo de registro

1. El superadmin abre `Comercial > Ventas > Nueva venta`.
2. Selecciona cliente opcional.
3. El vendedor se toma del usuario autenticado.
4. Selecciona tipo de venta: `fisica`, `online` u `otro`.
5. Selecciona metodo de pago.
6. Agrega una o varias variantes con cantidad.
7. El precio unitario se toma desde la variante (`prenda_variantes.pve_prv`).
8. El sistema calcula subtotal, descuento general y total.
9. Al guardar, la venta se registra dentro de una transaccion.

## Campos involucrados

Tabla `ventas`:

- `nve_ven`: numero automatico de venta.
- `cod_usuario_cliente_ven`: cliente opcional.
- `cod_usuario_vendedor_ven`: usuario autenticado que registra.
- `tiv_ven`: tipo de venta.
- `sub_ven`: subtotal antes del descuento.
- `des_ven`: descuento general.
- `tot_ven`: total final.
- `mpa_ven`: metodo de pago.
- `est_ven`: estado de venta.
- `fec_ven`: fecha de registro.

Tabla `detalle_ventas`:

- `cod_ventas_dve`: venta asociada.
- `cod_prenda_variantes_dve`: variante vendida.
- `can_dve`: cantidad vendida.
- `pun_dve`: precio unitario tomado desde la variante.
- `sub_dve`: subtotal de la linea.

Inventario:

- `inventarios.cdi_inv`: se descuenta con cada venta registrada.

## Validacion de stock

Antes de crear la venta, cada variante debe tener inventario y stock suficiente. Si `inventarios.cdi_inv` es menor que la cantidad vendida, la venta se bloquea con un mensaje claro: "No hay stock suficiente para esta variante."

## Transacciones

El registro usa `DB::transaction`:

1. Crear `ventas`.
2. Crear cada `detalle_ventas`.
3. Descontar `inventarios.cdi_inv`.
4. Confirmar transaccion.

El inventario se consulta con `lockForUpdate` durante el descuento para evitar inconsistencias por ventas simultaneas.

## Estados

- `registrada`: venta creada correctamente.
- `anulada`: estado reservado para anulaciones.

## Pendiente

- Anulacion de ventas con devolucion de stock mediante transaccion.
