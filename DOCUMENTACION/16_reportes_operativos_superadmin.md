# 16. Reportes operativos superadmin

## Objetivo

El modulo de reportes operativos permite revisar informacion administrativa de Mati Boutique sin convertir la pantalla en un BI avanzado. Es una vista de solo lectura para controlar inventario, ventas, reservas y catalogo desde datos reales de la base.

## Reportes disponibles

### Inventario

Muestra:

- Stock disponible total desde `inventarios.cdi_inv`.
- Stock reservado total desde `inventarios.cre_inv`.
- Productos sin stock.
- Productos con bajo stock.
- Stock por categoria.
- Stock por marca.
- Stock por ubicacion.
- Variantes sin inventario.
- Tabla de variantes con alerta de stock.

La regla operativa de bajo stock es: `cdi_inv > 0` y `cdi_inv <= 3`.

### Ventas

Muestra:

- Ventas por rango de fechas.
- Total vendido desde `ventas.tot_ven`.
- Cantidad de ventas.
- Descuentos acumulados desde `ventas.des_ven`.
- Productos mas vendidos desde `detalle_ventas.can_dve`.
- Ventas por vendedor.
- Ventas por metodo de pago.
- Tabla de ultimas ventas del filtro.

Los datos se consultan en `ventas` y `detalle_ventas`. No se modifica stock desde reportes.

### Reservas

Muestra:

- Reservas activas.
- Reservas vencidas visualmente.
- Reservas canceladas.
- Reservas completadas.
- Prendas mas reservadas desde `detalle_reservas.can_dre`.
- Tabla de reservas recientes.

Una reserva se muestra como vencida cuando `est_res = activa` y `ven_res` es anterior a la fecha actual. La pantalla no cambia automaticamente el estado.

### Catalogo

Muestra:

- Prendas por categoria.
- Prendas por marca.
- Prendas por temporada.
- Favoritos por prenda.
- Productos mas guardados.

Los favoritos se leen desde la relacion de `prendas` con `favoritos`.

## Filtros disponibles

Los filtros de la pantalla son:

- Fecha inicio.
- Fecha fin.
- Vendedor.
- Metodo de pago.
- Estado de venta.
- Estado de reserva.
- Cliente.
- Responsable.

Los filtros de fecha, vendedor, metodo de pago y estado de venta aplican al reporte de ventas. Los filtros de fecha, estado de reserva, cliente y responsable aplican al reporte de reservas. Inventario y catalogo muestran el estado actual porque no son reportes historicos.

## Servicio

Las consultas estan centralizadas en `App\Services\Superadmin\ReportQueryService`.

`Superadmin\ReportsController` queda como controlador delgado y solo entrega el payload del servicio a Inertia.

## Exportacion

Pendiente:

- Exportacion de reportes a PDF/Excel/CSV.

No se implemento exportacion en esta fase porque no existia soporte previo consolidado y el objetivo actual es consulta operativa en pantalla.
