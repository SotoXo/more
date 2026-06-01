# 05. Módulo superadmin

## Acceso

Ruta base: `/superadmin`

Middleware: `auth` y `superadmin`

El acceso está restringido a usuarios activos con rol activo y rol superadmin. Si la cuenta está inactiva o el rol no es válido, la sesión se cierra y el usuario vuelve a login con mensaje de error.

## Dashboard

Controlador: `Superadmin\DashboardController`

Vista: `resources/js/Pages/Superadmin/Dashboard.tsx`

El dashboard muestra:

- Usuarios registrados.
- Roles activos.
- Prendas.
- Variantes.
- Imágenes.
- Ventas.
- Reservas.
- Favoritos.
- Existencias críticas.
- Prendas sin imagen.
- Últimos registros de prenda, venta y reserva.

Es una vista de resumen y navegación, no una pantalla de edición.

## Navegación del panel

La navegación se define en `resources/js/lib/superadmin.ts` y agrupa:

- Panel.
- Seguridad.
- Catálogo.
- Inventario.
- Comercial.
- Análisis.

El sidebar muestra badges para algunos módulos, por ejemplo consulta o no listo.

## Recursos CRUD reales

El controlador `Superadmin\ResourceController` define como CRUD real:

- Roles.
- Usuarios.
- Categorías.
- Marcas.
- Colores.
- Tallas.
- Temporadas.
- Prendas.

Estos recursos permiten crear, consultar, editar y guardar registros reales.

## Recursos CRUD secundarios

Se definen como CRUD real secundario:

- Variantes.
- Imágenes.

Son recursos persistentes, pero dependen de prendas y deben tratarse como gestión subordinada. La edición más completa de variantes e imágenes se concentra en el editor de prendas.

## Recursos solo lectura

Los siguientes módulos consultan datos, métricas y detalles, pero no habilitan creación o edición operativa desde su pantalla:

- Consulta de inventario.
- Alertas de inventario.
- Consulta de ventas.
- Consulta de reservas.
- Favoritos guardados.
- Reportes de consulta.
- Bitácora.

## Recursos no listos

`inventario.ajustes` se declara como no listo. La vista presenta recomendaciones o plantillas informativas, pero no ejecuta ajustes de inventario ni guarda cambios operativos.

## Seguridad

### Roles

CRUD real con restricciones:

- Los nombres se normalizan a mayúsculas.
- Los roles del sistema no pueden duplicarse.
- Los roles del sistema no pueden cambiar de nombre.
- Los roles del sistema deben permanecer activos.
- No se pueden eliminar roles del sistema.
- No se puede eliminar un rol con usuarios asociados.

Roles del sistema reconocidos:

- `superadmin`
- `vendedor`
- `cliente`

### Usuarios

CRUD real con restricciones:

- Los usuarios no se eliminan; se desactivan.
- El superadmin no puede desactivar su propio usuario desde el panel.
- El superadmin no puede cambiar su propio rol a uno no superadmin.
- Se protege el último superadmin activo.
- Los usuarios se pueden filtrar por rol, estado y búsqueda.
- La creación desde cabecera está orientada a superadmin y vendedor; cliente existe como rol, pero el cliente común se crea por registro público.

## Catálogo

### Categorías

CRUD real. Soporta categoría padre y perfil de talla (`ALFA`, `NUMERICA`, `UNITALLA`). No puede eliminarse una categoría con subcategorías o prendas asociadas.

### Marcas

CRUD real. No puede eliminarse una marca con prendas asociadas.

### Colores

CRUD real. Maneja nombre, familia, HEX específico, HEX dominante, orden y estado. No puede eliminarse un color con variantes asociadas.

### Tallas

CRUD real. Maneja nombre, orden, sistema y grupo. No puede eliminarse una talla con variantes asociadas.

### Temporadas

CRUD real. Maneja nombre, código, descripción, orden y estado. No puede eliminarse una temporada con prendas asociadas.

### Prendas

CRUD real y módulo más importante del catálogo. El editor de prenda permite:

- Datos generales.
- Categoría.
- Marca.
- Temporada.
- Género.
- Precio base.
- Estado visible/oculto.
- Imagen principal.
- Galería.
- Variantes.
- Inventario inicial por variante.
- Ubicación.
- Creación inline de maestros.

## Inventario

### Consulta de existencias

Muestra prenda, variante, color, talla, SKU, disponible, reservado, total calculado y ubicacion por variante. Permite editar de forma controlada la cantidad disponible y la ubicacion. La cantidad reservada queda en solo lectura porque debe modificarse desde la logica de reservas.

### Alertas de inventario

Lista productos sin stock, productos con bajo stock, variantes sin inventario y prendas sin imagen. La referencia detallada del modulo esta en `DOCUMENTACION/13_inventario_superadmin.md`.

### Ajustes sugeridos

Vista no lista. No ejecuta cambios reales.

## Comercial

### Ventas

Consulta ventas existentes, clientes, vendedores, totales, estados y detalles. Tambien permite registrar ventas reales desde el panel, creando `detalle_ventas` y descontando `inventarios.cdi_inv` dentro de una transaccion. La anulacion con devolucion de stock queda documentada como pendiente en `DOCUMENTACION/14_ventas_superadmin.md`.

### Reservas

Consulta reservas existentes, vencimientos, estados y detalles. Tambien permite registrar reservas reales desde el panel, moviendo stock disponible a reservado. Las acciones de cancelar y completar actualizan inventario dentro de una transaccion. La conversion de reserva en venta y el comando para vencidas quedan pendientes en `DOCUMENTACION/15_reservas_superadmin.md`.

### Favoritos guardados

Consulta favoritos registrados por clientes.

## Reportes

Controlador: `Superadmin\ReportsController`

Servicio: `App\Services\Superadmin\ReportQueryService`

Reportes operativos consolida informacion de inventario, ventas, reservas y catalogo. Muestra stock disponible y reservado, productos sin stock o con bajo stock, variantes sin inventario, ventas por rango de fechas, totales vendidos, productos mas vendidos, ventas por vendedor, ventas por metodo de pago, estados de reservas, prendas mas reservadas y favoritos por prenda.

Los filtros disponibles son fecha inicio, fecha fin, vendedor, metodo de pago, estado de venta, estado de reserva, cliente y responsable. Los filtros se aplican a ventas y reservas; inventario y catalogo reflejan el estado actual.

Es un reporte de consulta, no un generador de archivos ni un módulo BI completo.

Pendiente: exportacion de reportes a PDF/Excel/CSV.

## Logs

Controlador: `Superadmin\LogsController`

La bitácora reúne registros recientes de usuarios, prendas, ventas y reservas. No es una auditoría transaccional completa con historial de cambios campo por campo.

