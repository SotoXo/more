# UI/UX de reportes operativos

## Alcance

Esta fase reorganiza la experiencia visual de `/superadmin/reportes` sin cambiar la logica de negocio ni las consultas validadas de reportes.

No se crearon migraciones, no se instalaron paquetes, no se implementaron backups, no se implementaron `audit_logs`, no se implementaron triggers y no se agrego PDF/Excel real.

## Problema anterior

La pagina mostraba todos los reportes en una sola secuencia vertical: metricas generales, filtros, ventas, reservas, pagos, inventario, clientes, catalogo, graficos, tablas y acciones.

Aunque los datos funcionaban, la vista generaba demasiado scroll, ocultaba las tablas importantes y hacia que las acciones de exportacion se perdieran dentro del contenido.

## Nueva organizacion

La pagina ahora usa navegacion por secciones desde el sidebar principal del panel administrativo. En la seccion `Analisis`, `Reportes operativos` funciona como modulo padre expandible y muestra sus submodulos:

- Resumen
- Ventas
- Reservas
- Pagos
- Inventario
- Clientes

Solo se renderiza el contenido de la seccion activa. Esto evita una pantalla infinita y mantiene el modulo como un panel operativo mas claro.

Se elimino la navegacion interna duplicada dentro de `/superadmin/reportes`: ya no hay tabs horizontales, submenú lateral interno ni panel de secciones dentro del contenido.

La seccion activa se conserva con query string:

- `/superadmin/reportes?section=resumen`
- `/superadmin/reportes?section=ventas`
- `/superadmin/reportes?section=reservas`
- `/superadmin/reportes?section=pagos`
- `/superadmin/reportes?section=inventario`
- `/superadmin/reportes?section=clientes`

No se agregaron rutas nuevas para las secciones, evitando conflictos con `ResourceController`.

## Resumen operativo

La seccion Resumen muestra una vista rapida del negocio con metricas de:

- Ventas confirmadas
- Ventas en revision
- Reservas pendientes
- Pagos pendientes
- Stock bajo
- Clientes frecuentes

Tambien muestra graficos generales de estados de ventas, estados de reservas y stock operativo. La informacion de catalogo queda como senal complementaria dentro del resumen, sin crear una pestana adicional.

## Secciones de reporte

Cada seccion muestra solo su propio contenido:

- Ventas: metricas de ventas, estados, origen, tendencia, productos vendidos y tabla de ventas.
- Reservas: estados actuales, productos reservados y tabla de reservas.
- Pagos: pagos por estado, metodo, montos declarados/verificados y tabla combinada de pagos.
- Inventario: `cdi_inv` como disponible real, `cre_inv` como comprometido, stock bajo, sin stock y tabla de alertas.
- Clientes: clientes frecuentes, compras, reservas y tabla de movimiento.

No se creo una seccion principal llamada "Exportaciones".

## Exportaciones

Las exportaciones quedan dentro de cada seccion correspondiente:

- Ventas: `/superadmin/reportes/exportar/ventas.csv` y `/superadmin/reportes/imprimir/ventas`
- Reservas: `/superadmin/reportes/exportar/reservas.csv` y `/superadmin/reportes/imprimir/reservas`
- Pagos: `/superadmin/reportes/exportar/pagos.csv` y `/superadmin/reportes/imprimir/pagos`
- Inventario: `/superadmin/reportes/exportar/inventario.csv` y `/superadmin/reportes/imprimir/inventario`
- Clientes: `/superadmin/reportes/exportar/clientes.csv` y `/superadmin/reportes/imprimir/clientes`

Los botones visibles son solo "Exportar CSV" y "Vista imprimible". No se muestran acciones de PDF/Excel real porque todavia no estan implementadas.

## Filtros

Los filtros siguen aplicandose a todas las secciones. Para reducir ruido visual, quedaron divididos asi:

- Filtros principales visibles: periodo, fecha desde y fecha hasta.
- Filtros avanzados colapsables: vendedor, metodo de pago, estado de venta, estado de reserva, cliente, responsable y ordenamiento si aplica.

Las exportaciones siguen usando la query actual para respetar los filtros aplicados.

Al aplicar o limpiar filtros se conserva `section` para no sacar al usuario de la seccion activa.

## Responsive

En desktop, el sidebar principal muestra `Reportes operativos` como opcion expandible dentro de `Analisis`, con sus subitems indentados y estado activo visible.

En tablet y movil, la navegacion se mantiene en el sidebar principal movil. No se duplica la navegacion dentro del contenido. Las metricas mantienen grilla responsive y las tablas conservan el patron existente de tarjetas en mobile y tabla con scroll interno en desktop.

## Archivos modificados

- `resources/js/Pages/Superadmin/Reports.tsx`
- `resources/js/Components/Superadmin/SuperadminLayout.tsx`
- `resources/js/Components/Superadmin/SuperadminSidebar.tsx`
- `resources/js/Components/Superadmin/Reports/types.ts`
- `resources/js/Components/Superadmin/Reports/ReportFilters.tsx`
- `resources/js/Components/Superadmin/Reports/ReportMetricGrid.tsx`
- `resources/js/Components/Superadmin/Reports/ReportCharts.tsx`
- `resources/js/Components/Superadmin/Reports/ReportExportActions.tsx`
- `resources/js/Components/Superadmin/Reports/ReportSectionContent.tsx`
- `resources/js/Components/Superadmin/Reports/ReportSummary.tsx`
- `resources/js/lib/superadmin.ts`
- `resources/js/types/superadmin.ts`

## Verificacion

Comandos de verificacion requeridos para esta fase:

- `./vendor/bin/sail artisan route:list`
- `npm run build`

No corresponde `php -l` en esta fase porque no se modificaron archivos PHP.
