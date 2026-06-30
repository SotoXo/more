# 41. Panel vendedor: consulta operativa

## 1. Objetivo de la Fase 2

La Fase 2 conecta el panel vendedor con datos reales de catalogo e inventario para consulta operativa. El vendedor puede responder preguntas sobre prendas, tallas, colores, ubicaciones y disponibilidad sin entrar a modulos administrativos.

No se implementan ventas, reservas, pagos, revision de comprobantes, recojos ni entregas.

## 2. Rutas completadas

Las rutas se mantienen dentro del grupo `auth` + `vendedor`.

- `GET /vendedor/prendas` -> `vendedor.prendas.index`
- `GET /vendedor/prendas/{prenda}` -> `vendedor.prendas.show`
- `GET /vendedor/existencias` -> `vendedor.existencias.index`
- `GET /vendedor/alertas-stock` -> `vendedor.alertas-stock.index`

No se agregaron rutas de creacion, edicion, eliminacion ni ajuste.

## 3. Vistas implementadas

- `resources/js/Pages/Vendedor/Products/Index.tsx`
- `resources/js/Pages/Vendedor/Products/Show.tsx`
- `resources/js/Pages/Vendedor/Inventory/Stock.tsx`
- `resources/js/Pages/Vendedor/Inventory/StockAlerts.tsx`

Tambien se agregaron componentes compartidos para esta fase:

- `resources/js/Pages/Vendedor/Partials/VendedorFilterPanel.tsx`
- `resources/js/Pages/Vendedor/Partials/VendedorReadOnlyTable.tsx`
- `resources/js/Pages/Vendedor/Partials/VendedorSummaryGrid.tsx`

## 4. Datos mostrados al vendedor

### Consultar prendas

Muestra:

- Imagen principal o placeholder `Sin imagen`.
- Nombre de prenda.
- Categoria.
- Marca.
- Temporada.
- Genero.
- Precio base.
- Estado.
- Total disponible.
- Cantidad de variantes activas.
- Colores.
- Tallas.

Filtros:

- Busqueda por nombre, SKU, color, talla o ubicacion.
- Categoria.
- Marca.
- Color.
- Talla.
- Disponibilidad.
- Ubicacion.
- Estado.

Acciones disponibles:

- Ver detalle.
- Ver existencias relacionadas.

### Detalle de prenda

Muestra:

- Imagen principal.
- Galeria si existe.
- Nombre.
- Descripcion.
- Categoria.
- Marca.
- Temporada.
- Genero.
- Precio.
- Estado.
- Disponible.
- Comprometido.
- Variantes con SKU, color, talla, precio, disponible, comprometido, ubicacion y nivel de stock.

No tiene botones de edicion.

### Existencias

Muestra inventario en tabla estilo planilla:

- Imagen.
- Prenda.
- SKU.
- Color.
- Talla.
- Precio.
- Disponible.
- Comprometido.
- Ubicacion.
- Estado.
- Nivel de stock.

Filtros:

- Busqueda por prenda, SKU, color o ubicacion.
- Color.
- Talla.
- Ubicacion.
- Stock.
- Estado.

### Alertas de stock

Muestra variantes con stock bajo:

- Imagen.
- Prenda.
- SKU.
- Color.
- Talla.
- Disponible.
- Comprometido.
- Ubicacion.
- Nivel de alerta.

## 5. Reglas de solo lectura

El panel vendedor en esta fase:

- No crea prendas.
- No edita prendas.
- No elimina prendas.
- No modifica cantidades.
- No ajusta inventario.
- No registra Kardex.
- No cambia ubicaciones.
- No cambia estados.
- No expone botones administrativos.

## 6. Regla de inventario

La regla se mantiene intacta:

- `cdi_inv` = stock disponible real.
- `cre_inv` = stock comprometido/separado.

Las vistas muestran ambos valores por separado. No se calcula disponible como `cdi_inv - cre_inv`.

## 7. Alertas de stock

Para esta fase se usa una regla temporal de consulta:

- Alerta si `cdi_inv <= 2`.
- Sin stock si `cdi_inv = 0`.
- Critico si `cdi_inv = 1`.
- Bajo si `cdi_inv = 2`.

No se creo configuracion nueva en base de datos.

## 8. Dashboard actualizado

El dashboard vendedor ahora conecta datos reales para:

- Prendas disponibles.
- Existencias consultables.
- Alertas de stock.

Se mantienen como pendientes o en cero:

- Ventas de hoy.
- Total vendido hoy.
- Reservas activas.
- Pagos pendientes.
- Recojos pendientes.

## 9. Backend y servicio

Se creo:

- `app/Services/Vendedor/VendedorInventoryQueryService.php`

El servicio prepara:

- Metricas del dashboard.
- Prendas consultables.
- Detalle de prenda.
- Existencias.
- Alertas de stock.
- Opciones de filtros.

Es un servicio de lectura. No guarda datos, no modifica inventario y no registra movimientos.

## 10. Permisos del vendedor

El acceso sigue protegido por `EnsureVendedor`.

Reglas:

- Vendedor accede a `/vendedor/prendas`, `/vendedor/existencias` y `/vendedor/alertas-stock`.
- Cliente no accede a rutas `/vendedor`.
- Superadmin conserva acceso a `/superadmin`.
- Superadmin puede entrar a `/vendedor` para supervision, segun lo definido en Fase 1.

## 11. Pruebas manuales

1. Iniciar sesion como vendedor y abrir `/vendedor/prendas`.
   Esperado: se muestran prendas reales con filtros.

2. Buscar por nombre, SKU, color o talla.
   Esperado: la lista se filtra sin mostrar botones de editar o eliminar.

3. Abrir detalle de prenda.
   Esperado: se ve informacion completa de la prenda y variantes en modo lectura.

4. Abrir `/vendedor/existencias`.
   Esperado: se muestran variantes reales con `cdi_inv` como disponible y `cre_inv` como comprometido.

5. Abrir `/vendedor/alertas-stock`.
   Esperado: se muestran variantes con `cdi_inv <= 2`.

6. Iniciar sesion como cliente e intentar entrar a rutas `/vendedor`.
   Esperado: no tiene acceso.

7. Iniciar sesion como superadmin y abrir `/superadmin`.
   Esperado: el panel administrativo sigue funcionando.

8. Ejecutar `./vendor/bin/sail artisan route:list`.
   Esperado: se listan las rutas `vendedor.*`, incluida `vendedor.prendas.show`.

9. Ejecutar `npm run build`.
   Esperado: build exitoso.

## 12. Pendientes para Fase 3

- Consulta avanzada de ventas del vendedor.
- Flujo de venta real reutilizando servicios existentes.
- Flujo de reservas real reutilizando servicios existentes.
- Registro de pagos por vendedor con confirmacion automatica.
- Revision de comprobantes subidos por clientes.
- Recojos y entregas.
- Reporte real de prendas daniadas desde vendedor.
- Reportes del vendedor filtrados por su alcance operativo.
