# 42. Panel vendedor: ventas directas

## 1. Objetivo de la Fase 3

La Fase 3 implementa ventas directas desde el panel vendedor de Mati Boutique. El vendedor puede consultar sus ventas, crear una venta presencial, agregar variantes con stock disponible, registrar el pago y dejar el pago confirmado automaticamente.

Esta fase si descuenta stock. No implementa reservas, ventas desde reserva, revision de comprobantes subidos por clientes, ni flujos nuevos de recojo o entrega.

## 2. Rutas implementadas

Todas las rutas viven dentro del grupo `auth` + `vendedor`.

- `GET /vendedor/ventas` -> `vendedor.ventas.index`
- `GET /vendedor/ventas/crear` -> `vendedor.ventas.create`
- `POST /vendedor/ventas` -> `vendedor.ventas.store`
- `GET /vendedor/ventas/{venta}` -> `vendedor.ventas.show`

No se agregaron rutas de edicion, eliminacion ni cancelacion.

## 3. Backend

Se creo:

- `app/Http/Controllers/Vendedor/VendedorSalesController.php`
- `app/Services/Vendedor/VendedorSalesService.php`

El servicio prepara el listado, el formulario de creacion, el detalle de venta, las metricas del dashboard y el flujo transaccional de guardado.

Para el descuento de stock se reutiliza `App\Services\Superadmin\SaleInventoryService::reserveForSale()`, que bloquea el inventario con `lockForUpdate`, valida stock suficiente, descuenta `cdi_inv` y registra Kardex mediante `InventoryMovementService`.

## 4. Flujo de venta directa

1. El vendedor abre `/vendedor/ventas/crear`.
2. Busca variantes activas por prenda, SKU, color, talla o ubicacion.
3. Agrega productos al carrito de venta.
4. El sistema valida cantidad mayor a cero y que no supere `cdi_inv`.
5. Selecciona metodo de pago y registra monto pagado.
6. Para QR o transferencia bancaria se exige comprobante.
7. Al guardar, el backend vuelve a validar datos y stock.
8. Dentro de `DB::transaction` se crea venta, detalle, pago confirmado, descuento de stock, Kardex y logs.
9. Si cualquier paso falla, no se crea venta, detalle ni pago, y no se modifica inventario.

## 5. Regla de pagos registrados por vendedor

Pago registrado por vendedor = pago confirmado automaticamente.

Aplica para:

- `efectivo`
- `qr`
- `transferencia_bancaria`

Efectivo no requiere comprobante. QR y transferencia bancaria requieren comprobante porque el sistema ya maneja comprobantes para pagos digitales. Aun asi, al ser registrado por el vendedor, el pago queda con `est_vpg = confirmado`, `monto_verificado_vpg = monto_declarado_vpg`, usuario verificador igual al vendedor autenticado y fecha de verificacion inmediata.

## 6. Diferencia con comprobantes subidos por clientes

Esta fase no cambia la compra online ni la revision de comprobantes de clientes.

La diferencia queda asi:

- Pago registrado por vendedor: confirmado automaticamente.
- Comprobante subido por cliente desde compra online: pendiente de revision y fuera de esta fase.

## 7. Reglas de stock

La semantica de inventario se mantiene:

- `cdi_inv` = stock disponible real.
- `cre_inv` = stock comprometido/separado.

No se calcula disponible como `cdi_inv - cre_inv`.

Al crear una venta directa:

- Se valida que cada variante tenga `cdi_inv` suficiente.
- Se resta la cantidad vendida solo de `cdi_inv`.
- No se modifica `cre_inv`.
- El descuento ocurre con inventario bloqueado por `lockForUpdate`.
- Si no hay stock suficiente, se aborta toda la transaccion.

Ejemplo:

- Antes: `cdi_inv = 5`, `cre_inv = 1`.
- Venta directa de 2 unidades.
- Despues: `cdi_inv = 3`, `cre_inv = 1`.

## 8. Kardex

El Kardex se registra mediante `InventoryMovementService::venta()` desde `SaleInventoryService::reserveForSale()`.

El movimiento guarda:

- inventario
- variante
- `tipo_movimiento_inm = venta`
- cantidad vendida
- `cdi_antes_inm` y `cdi_despues_inm`
- `cre_antes_inm` y `cre_despues_inm`
- `referencia_tipo_inm = venta`
- `referencia_id_inm = id_venta`
- usuario autenticado
- descripcion `Venta directa registrada por vendedor.`

No se registra Kardex si la venta falla.

## 9. Logs generados

Se generan logs especificos para esta fase:

- `venta_vendedor_creada`
- `pago_vendedor_confirmado`
- `stock_descontado_por_venta`

Ademas, el Kardex conserva su log interno existente `movimiento_kardex`.

## 10. Seguridad

El acceso sigue protegido por `EnsureVendedor`.

Reglas aplicadas:

- Cliente no accede a `/vendedor/ventas`.
- Vendedor ve ventas directas fisicas registradas por su usuario.
- Superadmin puede entrar al panel vendedor para supervision y ve ventas directas fisicas globales.
- Vendedor no accede a `/superadmin/ventas`.
- No hay edicion, eliminacion ni cancelacion de ventas en esta fase.
- Vendedor no ajusta stock manualmente.
- Vendedor no accede al Kardex completo; solo ve resumen de movimientos asociados al detalle de su venta.
- Vendedor no confirma comprobantes subidos por clientes en esta fase.

## 11. Dashboard vendedor

El dashboard vendedor ahora conecta datos reales para:

- Ventas de hoy.
- Total vendido hoy.

Para usuario vendedor se cuentan ventas directas fisicas del usuario autenticado. Para superadmin en `/vendedor` se muestra supervision global de ventas directas fisicas.

Se mantienen pendientes o en cero:

- Reservas activas.
- Pagos pendientes de revision.
- Recojos pendientes.

## 12. Pruebas manuales

1. Iniciar sesion como vendedor y abrir `/vendedor/ventas`.
   Esperado: carga el listado de ventas directas del vendedor.

2. Abrir `/vendedor/ventas/crear`.
   Esperado: se ven productos reales con imagen o placeholder, SKU, color, talla, precio y stock `cdi_inv`.

3. Intentar vender una cantidad mayor al stock.
   Esperado: la UI bloquea el guardado y el backend rechaza si se fuerza el envio.

4. Registrar venta con efectivo.
   Esperado: venta creada, pago confirmado automaticamente, `cdi_inv` baja, `cre_inv` no cambia, Kardex y logs quedan registrados.

5. Registrar venta con QR.
   Esperado: exige comprobante, crea pago confirmado automaticamente y no queda pendiente de revision admin.

6. Registrar venta con transferencia bancaria.
   Esperado: mismo comportamiento que QR.

7. Abrir el detalle de venta.
   Esperado: se muestran productos, total, pago, comprobante si existe, estado y resumen Kardex.

8. Revisar dashboard vendedor.
   Esperado: ventas de hoy y total vendido hoy se actualizan.

9. Iniciar sesion como cliente e intentar abrir `/vendedor/ventas`.
   Esperado: no tiene acceso.

10. Iniciar sesion como superadmin y abrir `/superadmin`.
    Esperado: el panel superadmin sigue funcionando.

## 13. Pendientes para Fase 4

- Venta desde reserva.
- Revision de comprobantes subidos por clientes.
- Acciones seguras para pagos pendientes de compra online.
- Recojos y entregas reales desde vendedor.
- Reportes comerciales filtrados por vendedor.
- Prendas daniadas registradas desde vendedor.
- Pruebas automatizadas de flujo de venta si se define suite feature/end-to-end.
