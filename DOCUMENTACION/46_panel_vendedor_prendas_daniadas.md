# Fase 7 - Panel vendedor: prendas danadas

## 1. Objetivo

La Fase 7 permite que usuarios con acceso al panel vendedor registren reportes de prendas danadas desde `/vendedor/prendas-daniadas`.

El vendedor puede listar sus reportes, crear un reporte nuevo, adjuntar evidencia, vincular opcionalmente una prenda o variante existente y consultar el detalle/estado del caso.

## 2. Reportar no es confirmar dano

El panel vendedor solo reporta el incidente. La confirmacion del dano, el descarte, la reparacion, la baja definitiva y la resolucion siguen perteneciendo al modulo superadmin de prendas danadas.

Todo reporte creado desde vendedor queda con:

- `estado_pdr = reportado`.
- `afecta_inventario_pdr = false`.
- `cod_usuario_reporta_pdr = usuario vendedor autenticado`.
- metadata `origen = panel_vendedor`.

## 3. Rutas implementadas

- `GET /vendedor/prendas-daniadas` (`vendedor.prendas-daniadas.index`): listado filtrable.
- `GET /vendedor/prendas-daniadas/crear` (`vendedor.prendas-daniadas.create`): formulario de alta.
- `POST /vendedor/prendas-daniadas` (`vendedor.prendas-daniadas.store`): crea reporte.
- `GET /vendedor/prendas-daniadas/{reporte}` (`vendedor.prendas-daniadas.show`): detalle.

No existen rutas vendedor para confirmar, descartar, reparar, baja definitiva, resolver ni quitar stock.

## 4. Controlador y servicios

Se agrego `VendedorDamagedProductController`.

Se agrego `VendedorDamagedProductService` para:

- aplicar alcance vendedor/superadmin en panel vendedor;
- validar reglas del formulario vendedor;
- preparar payloads Inertia;
- serializar listado, creacion, detalle y KPI dashboard;
- forzar `afecta_inventario_pdr = false`.

El guardado delega en `Superadmin\DamagedProductService::createVendorReport()`, reutilizando el modelo, las tablas existentes y el almacenamiento de evidencia del modulo central.

## 5. Campos del formulario

Requeridos:

- `nombre_prenda_pdr`.
- `causa_pdr`.
- `tipo_danio_pdr`.
- `severidad_pdr`.
- `cantidad_pdr` mayor a 0.
- `observacion_pdr`.

Opcionales:

- `descripcion_prenda_pdr`.
- `cod_inventario_pdr`.
- `cod_prenda_variante_pdr`.
- `cod_prenda_pdr`.
- `imagenes`.

Las imagenes aceptan `jpg`, `jpeg`, `png` y `webp`, con maximo de 4 MB por archivo.

## 6. Vinculacion opcional con inventario

El vendedor puede vincular una variante/inventario existente para identificar mejor la prenda danada.

La pantalla muestra:

- imagen o placeholder;
- nombre de prenda;
- SKU;
- color;
- talla;
- `cdi_inv`;
- `cre_inv`;
- ubicacion.

La vinculacion es solo administrativa. No habilita descuento de stock y no mezcla `cdi_inv` con `cre_inv`.

## 7. Regla de no afectar stock

Crear un reporte desde vendedor no modifica inventario:

- no cambia `cdi_inv`;
- no cambia `cre_inv`;
- no marca baja;
- no confirma dano;
- no descuenta unidades aunque haya inventario vinculado.

La bandera `afecta_inventario_pdr` se fuerza en `false` en backend, incluso si alguien intenta enviarla manualmente.

## 8. Regla de no registrar Kardex

La creacion desde vendedor no registra Kardex ni llama servicios de movimientos de inventario.

El Kardex solo puede aparecer en fases administrativas donde el superadmin confirme y aplique una decision que corresponda.

## 9. Logs generados

Al crear un reporte desde vendedor se registra `SystemLogService`:

- modulo: `prendas_daniadas`;
- accion: `reporte_danio_creado_por_vendedor`;
- entidad: reporte creado;
- metadata: reporte, codigo, estado, cantidad, inventario/variante/prenda vinculada, vendedor y si tiene prenda vinculada.

## 10. Seguridad

- El grupo de rutas usa middleware `auth` + `vendedor`.
- El middleware permite vendedores y superadmin, segun el comportamiento vigente del panel vendedor.
- Vendedor ve solo sus reportes.
- Superadmin dentro del panel vendedor puede supervisar todos los reportes, pero sin acciones administrativas en estas vistas.
- Cliente no accede.
- El modulo superadmin conserva sus rutas completas y acciones administrativas.

## 11. Dashboard vendedor

Se agrego el KPI `Reportes de danos`, con conteo real de reportes en `reportado` o `en_revision`.

Para vendedor cuenta sus propios reportes pendientes. Para superadmin en panel vendedor cuenta pendientes totales.

No se alteran los KPIs previos de ventas, reservas, pagos, recojos, inventario ni alertas.

## 12. Pruebas manuales recomendadas

1. Iniciar sesion como vendedor y abrir `/vendedor/prendas-daniadas`.
2. Crear reporte sin inventario vinculado y confirmar que queda `reportado`.
3. Verificar que `cdi_inv` y `cre_inv` no cambian.
4. Verificar que no se crea Kardex.
5. Crear reporte con inventario vinculado y confirmar que solo guarda referencia.
6. Subir imagenes de evidencia y revisar que se muestren en detalle.
7. Abrir detalle y confirmar que no hay acciones administrativas.
8. Intentar acceder como cliente y confirmar bloqueo.
9. Revisar el modulo superadmin de prendas danadas y confirmar que mantiene sus acciones completas.

## 13. Pendientes para superadmin

El superadmin mantiene la responsabilidad de revisar, confirmar, descartar, reparar, dar baja definitiva o resolver reportes. Si decide afectar inventario, debe hacerlo desde el modulo superadmin y bajo las reglas centrales existentes.
