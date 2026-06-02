# 17. Exportacion de reportes operativos

## Objetivo

Esta fase agrega exportacion basica de reportes operativos sin instalar paquetes externos. No se implementa PDF real ni Excel real todavia.

No se crearon migraciones, no se instalaron paquetes, no se implementaron backups, no se crearon `audit_logs` y no se agregaron triggers.

## Exportaciones implementadas

Reportes disponibles:

- Ventas.
- Reservas.
- Pagos.
- Inventario.
- Clientes frecuentes.

Cada reporte tiene dos salidas:

- CSV descargable.
- Vista imprimible para usar imprimir o guardar como PDF desde el navegador.

## Rutas agregadas

CSV:

- `GET /superadmin/reportes/exportar/ventas.csv`
- `GET /superadmin/reportes/exportar/reservas.csv`
- `GET /superadmin/reportes/exportar/pagos.csv`
- `GET /superadmin/reportes/exportar/inventario.csv`
- `GET /superadmin/reportes/exportar/clientes.csv`

Vista imprimible:

- `GET /superadmin/reportes/imprimir/ventas`
- `GET /superadmin/reportes/imprimir/reservas`
- `GET /superadmin/reportes/imprimir/pagos`
- `GET /superadmin/reportes/imprimir/inventario`
- `GET /superadmin/reportes/imprimir/clientes`

Las rutas se declararon antes de `superadmin/{section}/{resource}` para evitar que `ResourceController` capture las exportaciones.

## Servicios y controlador

- `App\Services\Superadmin\ReportExportService`: genera CSV en UTF-8 con separador `;`.
- `App\Services\Superadmin\ReportPrintService`: arma el payload institucional de la vista imprimible.
- `App\Http\Controllers\Superadmin\ReportExportController`: expone CSV e impresion.

La fuente de datos se centraliza en `ReportQueryService::exportDataset()`, para reutilizar filtros y estados actuales.

## Formato CSV

Los CSV usan:

- Codificacion UTF-8 con BOM.
- Separador `;`.
- Encabezados claros.
- Sanitizacion basica de celdas para evitar formulas ejecutables al abrir en planillas.

Columnas principales:

- Ventas: numero, cliente, vendedor, tipo, estado, estado logistico, metodo de pago, productos vendidos, total y fecha.
- Reservas: numero, cliente, responsable, estado de reserva, estado de pago, estado logistico, total, monto pagado, saldo y fecha.
- Pagos: tipo, codigo de operacion, cliente, metodo, estado, monto declarado, monto verificado/confirmado y fecha.
- Inventario: prenda, SKU, color, talla, stock disponible, stock comprometido y estado de stock.
- Clientes: cliente, compras, reservas, total comprado, total reservado y ultima actividad.

## Vista imprimible

La vista imprimible incluye:

- Mati Boutique.
- Titulo del reporte.
- Rango de fechas aplicado.
- Fecha y hora de generacion.
- Usuario generador.
- Filtros aplicados.
- Resumen de totales.
- Tabla de datos.
- Pie institucional: `Reporte generado por el sistema de gestion comercial de Mati Boutique.`
- Boton imprimir.
- Boton volver.

No genera PDF desde backend. El PDF se obtiene manualmente con la opcion imprimir o guardar como PDF del navegador.

## Filtros respetados

Los reportes respetan los filtros disponibles cuando aplican:

- `period`
- `date_from`
- `date_to`
- `seller`
- `payment_method`
- `sale_status`
- `reservation_status`
- `customer`
- `responsible`

Si un filtro no aplica a un reporte, se ignora de forma segura.

## Seguridad

Las rutas estan dentro del grupo `auth` y `superadmin`.

El middleware actual solo permite al superadmin acceder a reportes, exportaciones e impresion. El vendedor no queda habilitado para exportar reportes en esta fase y el cliente no puede acceder.

No se guardan archivos exportados en `public/`.

## Pendiente

PDF real futuro:

- Evaluar `barryvdh/laravel-dompdf` si se aprueba instalar paquetes.

Excel real futuro:

- Evaluar `maatwebsite/excel` si se aprueba instalar paquetes.

Tambien quedan pendientes:

- Exportacion PDF backend.
- Exportacion XLSX real.
- Auditoria persistente.
- Backups.
- Logs transaccionales.
