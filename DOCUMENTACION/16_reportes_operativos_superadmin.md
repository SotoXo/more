# 16. Reportes operativos superadmin

## Objetivo

El modulo de reportes operativos permite revisar informacion administrativa de Mati Boutique sin convertir la pantalla en BI ni inteligencia artificial. Es una vista de solo lectura para controlar ventas, reservas, pagos, inventario, clientes y catalogo desde datos reales.

Esta fase no crea migraciones, no instala paquetes, no exporta PDF/Excel, no implementa backups y no crea auditoria persistente.

## Ruta y servicio

- Ruta: `/superadmin/reportes`
- Controlador: `App\Http\Controllers\Superadmin\ReportsController`
- Servicio: `App\Services\Superadmin\ReportQueryService`
- Vista: `resources/js/Pages/Superadmin/Reports.tsx`

El controlador se mantiene delgado y entrega a Inertia el payload construido por el servicio.

## Filtros disponibles

La pantalla incluye:

- Periodo: hoy, ultimos 7 dias, mes actual, rango personalizado.
- Fecha inicio.
- Fecha fin.
- Vendedor.
- Metodo de pago.
- Estado de venta.
- Estado de reserva.
- Cliente.
- Responsable.

Cuando el periodo es `hoy`, `ultimos_7_dias` o `mes_actual`, el servicio calcula el rango automaticamente. Cuando se usa `personalizado`, se respetan `date_from` y `date_to`.

## Reportes disponibles

### Ventas

Muestra:

- Ventas confirmadas (`registrada`).
- Ventas en revision (`en_revision`).
- Ventas canceladas o incompletas (`cancelada`, `incompleta`).
- Ventas online (`tiv_ven = online`).
- Ventas desde reservas (`tiv_ven = reserva`).
- Total vendido.
- Productos mas vendidos.
- Ventas por vendedor.
- Ventas por metodo de pago.
- Ultimas ventas del filtro.

Graficos:

- Donut de estados de ventas.
- Donut de origen de ventas.
- Tendencia de ventas por fecha.
- Barras de productos mas vendidos.

### Reservas

Muestra estados actuales del flujo:

- `por_procesar`
- `confirmada`
- `lista_recojo`
- `completada`
- `cancelada`
- `vencida`
- `rechazada`

Tambien muestra prendas mas reservadas y reservas recientes. Una reserva vigente puede mostrarse como vencida visualmente si supera `fec_limite_recojo_res` o, como respaldo, `ven_res`.

Se corrigio el uso anterior de `est_res = activa`; el reporte ya no depende de ese estado antiguo.

Graficos:

- Donut de reservas por estado.
- Barras de productos mas reservados.

### Pagos

Combina pagos de:

- `venta_pagos`
- `reserva_pagos`

Muestra:

- Pagos pendientes de revision (`pendiente_revision`).
- Pagos confirmados (`confirmado`).
- Pagos rechazados (`rechazado`).
- Montos declarados.
- Montos verificados o confirmados.
- Pagos por metodo.
- Pagos recientes.

Graficos:

- Donut de pagos por estado.
- Barras de pagos por metodo.

### Inventario

Muestra:

- Stock disponible desde `inventarios.cdi_inv`.
- Stock comprometido desde `inventarios.cre_inv`.
- Productos sin stock.
- Productos con bajo stock.
- Variantes sin inventario.
- Stock por categoria.
- Stock por marca.
- Stock por ubicacion.
- Variantes con alerta de stock.

La regla operativa de bajo stock es: `cdi_inv > 0` y `cdi_inv <= 3`.

Graficos:

- Donut de stock operativo.
- Barras de stock por categoria.

### Clientes

Muestra clientes frecuentes combinando:

- Compras asociadas al cliente.
- Reservas asociadas al cliente.

El ranking se calcula por cantidad total de movimientos: compras mas reservas.

Graficos:

- Barras de clientes frecuentes.

### Catalogo

Mantiene informacion de catalogo y favoritos:

- Prendas por categoria.
- Prendas por marca.
- Prendas por temporada.
- Favoritos por prenda.

## Pendientes fuera de esta fase

- Exportacion PDF.
- Exportacion Excel.
- CSV temporal.
- Tabla `audit_logs`.
- Observers/events de auditoria.
- Triggers PostgreSQL.
- Copias de seguridad.
- Historial persistente de backups.
- Logs transaccionales con valores anteriores y nuevos.

## Verificacion de la fase

Para esta fase se debe verificar:

- `./vendor/bin/sail artisan route:list`
- `npm run build`
- `./vendor/bin/sail php -l app/Services/Superadmin/ReportQueryService.php`

No se debe ejecutar `npm run dev` ni `sail npm run dev`.
