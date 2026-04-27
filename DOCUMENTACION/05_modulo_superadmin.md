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

Muestra disponibilidad, reservado y ubicación por variante. Es una consulta operativa, no un módulo de movimientos.

### Alertas de inventario

Lista registros con disponibilidad baja, calculada desde `cdi_inv - cre_inv`.

### Ajustes sugeridos

Vista no lista. No ejecuta cambios reales.

## Comercial

### Ventas

Consulta ventas existentes, clientes, vendedores, totales, estados y detalles. No crea ventas desde el panel en esta fase.

### Reservas

Consulta reservas existentes, vencimientos, estados y detalles. No crea reservas desde el panel en esta fase.

### Favoritos guardados

Consulta favoritos registrados por clientes.

## Reportes

Controlador: `Superadmin\ReportsController`

El reporte trabaja sobre variantes con relaciones de prenda, categoría, marca, temporada, color, talla e inventario. Permite filtrar por categoría, marca, temporada, género, color, talla, stock, precio y fecha.

Es un reporte de consulta, no un generador de archivos ni un módulo BI completo.

## Logs

Controlador: `Superadmin\LogsController`

La bitácora reúne registros recientes de usuarios, prendas, ventas y reservas. No es una auditoría transaccional completa con historial de cambios campo por campo.

