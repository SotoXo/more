# Kardex de movimientos de inventario

## 1. Que es el Kardex

El Kardex de Mati Boutique es el historial operativo de movimientos de stock. No es una pantalla de carga inicial: registra automaticamente lo que ya ocurrio en inventario, ventas, reservas y ajustes administrativos.

La regla de inventario se mantiene:

- `cdi_inv` es stock disponible real.
- `cre_inv` es stock comprometido o separado.
- No se calcula disponible como `cdi_inv - cre_inv`.

## 2. Inventario, audit_logs y Kardex

- `inventarios` guarda el estado actual de cada variante.
- `audit_logs` conserva auditoria critica de cambios sensibles, incluyendo valores antes/despues de `inventarios`.
- `inventario_movimientos` guarda una bitacora operativa y legible para responder que variante se movio, cuanto se movio, que usuario lo origino y cual fue la referencia comercial.

## 3. Tabla `inventario_movimientos`

La migracion `2026_06_04_030000_create_inventario_movimientos_table.php` crea:

- `cod_inventario_inm`
- `cod_prenda_variante_inm`
- `tipo_movimiento_inm`
- `cantidad_inm`
- `cdi_antes_inm`
- `cdi_despues_inm`
- `cre_antes_inm`
- `cre_despues_inm`
- `ubicacion_antes_inm`
- `ubicacion_despues_inm`
- `referencia_tipo_inm`
- `referencia_id_inm`
- `cod_usuario_inm`
- `descripcion_inm`
- `metadata_inm`
- timestamps

Incluye indices por inventario, variante, tipo, referencia, usuario y fecha.

## 4. Modelo `InventarioMovimiento`

El modelo `App\Models\InventarioMovimiento` usa la tabla `inventario_movimientos`, castea metadata y cantidades, y expone relaciones con:

- `inventario`
- `variante`
- `usuario`

Tambien se agregaron relaciones desde `Inventario` y `PrendaVariante` hacia sus movimientos.

## 5. Servicio `InventoryMovementService`

`App\Services\InventoryMovementService` centraliza el registro de Kardex. El metodo principal es `record(...)` y los helpers operativos son:

- `entrada`
- `reserva`
- `liberacionReserva`
- `venta`
- `ventaEnRevision`
- `cancelacionVenta`
- `ajusteManual`
- `cambioUbicacion`
- `conversionReservaVenta`
- `danoConfirmado`
- `reparacionDanio`

El servicio no modifica inventario. Solo registra el movimiento con usuario autenticado, ubicacion, referencia y metadata sanitizada.

## 6. Tipos de movimiento

Implementados:

- `entrada`
- `reserva`
- `liberacion_reserva`
- `venta_en_revision`
- `venta`
- `cancelacion_venta`
- `ajuste_manual`
- `cambio_ubicacion`
- `conversion_reserva_venta`
- `dano_confirmado`
- `reparacion_danio`

`correccion` queda contemplado como tipo posible para una fase futura, pero no se crea desde UI en esta etapa.

Los tipos `dano_confirmado` y `reparacion_danio` pertenecen al modulo independiente de prendas dañadas. Solo se registran cuando el reporte esta vinculado a inventario y la accion de revision modifica `cdi_inv`.

## 7. Flujo de entrada de stock

Cuando se crea inventario con stock inicial desde ficha de prenda o desde Existencias, se registra:

- tipo `entrada`
- cantidad igual al stock disponible inicial
- `cdi_antes = 0`
- `cdi_despues = cdi_inv`
- `cre_antes = 0`
- `cre_despues = cre_inv`

Ejemplo: stock inicial 30 genera `entrada +30`.

## 8. Flujo de reserva

Al crear una reserva, el sistema separa stock:

- `cdi_inv` baja.
- `cre_inv` sube.
- Kardex registra `reserva`.
- La referencia es la reserva.

## 9. Flujo de venta

Venta directa desde panel:

- `cdi_inv` baja.
- `cre_inv` no cambia.
- Kardex registra `venta`.
- La referencia es la venta.

Compra online enviada a revision:

- `cdi_inv` baja.
- `cre_inv` sube.
- Kardex registra `venta_en_revision`.

Confirmacion de venta online:

- `cre_inv` baja.
- `cdi_inv` no vuelve a bajar.
- Kardex registra `venta`.

## 10. Cancelacion y liberacion

Reserva rechazada, cancelada o vencida:

- `cdi_inv` sube.
- `cre_inv` baja.
- Kardex registra `liberacion_reserva`.

Venta online cancelada o incompleta:

- `cdi_inv` sube.
- `cre_inv` baja.
- Kardex registra `cancelacion_venta`.

## 11. Cambio de ubicacion

Cuando cambia `ubi_inv`, Kardex registra `cambio_ubicacion` con:

- `ubicacion_antes_inm`
- `ubicacion_despues_inm`
- cantidad `0` si no hubo cambio de stock

Si el administrador cambia stock y ubicacion a la vez, se registra el ajuste de stock y tambien el cambio de ubicacion.

## 11.1 Prendas dañadas

El modulo `/superadmin/prendas-daniadas` no registra Kardex al crear un reporte.

Cuando se confirma un daño con inventario vinculado y `afecta_inventario_pdr = true`:

- `cdi_inv` baja por la cantidad dañada.
- `cre_inv` no cambia.
- Kardex registra `dano_confirmado`.
- La referencia es `prenda_danio` con el id del reporte.

Cuando se repara un reporte confirmado que habia afectado inventario:

- `cdi_inv` sube por la cantidad reparada.
- `cre_inv` no cambia.
- Kardex registra `reparacion_danio`.
- La referencia es `prenda_danio` con el id del reporte.

Baja definitiva no descuenta stock por segunda vez; solo cambia el estado del reporte.

## 12. Vista administrativa

Ruta:

`/superadmin/inventario/movimientos`

Nombre visible:

`Kardex de inventario`

La vista muestra:

- tarjetas de resumen
- filtros por fecha, tipo, variante/SKU, prenda, usuario, referencia y descripcion
- tabla responsive
- detalle de movimiento en modal
- exportacion CSV en `/superadmin/inventario/movimientos/exportar.csv`

## 13. Seguridad aplicada

- Solo usuarios autenticados con middleware `superadmin` pueden acceder.
- Clientes y vendedores no tienen rutas de consulta.
- La UI es solo lectura.
- No hay formulario para crear movimientos manualmente.
- Metadata sensible se filtra en el servicio antes de persistir.
- El Kardex se genera desde cambios reales de inventario.

## 14. Puntos conectados

- `SaleInventoryService`
  - venta directa
  - confirmacion de stock separado
  - liberacion por venta cancelada o incompleta
- `SaleCheckoutService`
  - compra online enviada a revision
- `ReservationInventoryService`
  - reserva
  - liberacion
  - conversion reserva/venta
- `ReservationService`
  - referencias y metadata de reservas
- `ProductCatalogService`
  - stock inicial y ajustes desde ficha de prenda
  - cambios de ubicacion
- `ResourceControllerActions`
  - stock inicial y ajustes desde Existencias

## 15. Logs

Se generan `system_logs` para:

- `movimiento_kardex`
- `ajuste_kardex`
- `consulta_kardex`
- `exportacion_kardex`

Los `audit_logs` existentes se mantienen para inventarios y no son reemplazados por Kardex.

## 16. Reportes

El reporte operativo de inventario incluye una metrica de movimientos recientes con enlace al Kardex.

## 17. Pendientes

- Reportes avanzados de rotacion de inventario.
- Analisis por costo promedio o valorizacion.
- Triggers PostgreSQL opcionales, si en una fase futura se requiere respaldo a nivel base de datos.
