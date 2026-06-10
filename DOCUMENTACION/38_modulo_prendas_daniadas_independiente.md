# Modulo independiente de prendas dañadas

## 1. Objetivo

El modulo `Prendas dañadas` permite registrar reportes administrativos de prendas con daño fisico, causa, tipo de daño, gravedad, cantidad afectada, observaciones, evidencia fotografica y decision de revision.

Sirve para control interno de calidad, perdidas, reparaciones y bajas definitivas.

## 2. Por que es independiente

El modulo no esta dentro de `ProductCreate`, no se abre desde el detalle de una prenda y no depende obligatoriamente de una prenda existente. Un superadmin puede registrar una prenda dañada detectada fisicamente aunque no exista aun una ficha, variante o inventario asociado.

## 3. Flujo de registro

1. El superadmin entra a `/superadmin/prendas-daniadas`.
2. Presiona `Registrar prenda dañada`.
3. Registra nombre o descripcion de la prenda, causa, tipo, gravedad, cantidad, observacion y evidencia opcional.
4. Opcionalmente vincula una variante con inventario.
5. El reporte se crea en estado `reportado`.
6. La creacion no mueve inventario y no registra Kardex.
7. La revision posterior puede confirmar, descartar, reparar, dar baja definitiva o resolver.

## 4. Campos principales

Tabla `prenda_danio_reportes`:

- `codigo_pdr`
- `nombre_prenda_pdr`
- `descripcion_prenda_pdr`
- `cod_prenda_pdr`
- `cod_prenda_variante_pdr`
- `cod_inventario_pdr`
- `cod_usuario_reporta_pdr`
- `cod_usuario_revisa_pdr`
- `causa_pdr`
- `tipo_danio_pdr`
- `severidad_pdr`
- `cantidad_pdr`
- `estado_pdr`
- `decision_pdr`
- `observacion_pdr`
- `observacion_revision_pdr`
- `fecha_reporte_pdr`
- `fecha_revision_pdr`
- `afecta_inventario_pdr`
- `metadata_pdr`

Tabla `prenda_danio_imagenes`:

- `cod_prenda_danio_reporte_pdi`
- `ruta_pdi`
- `url_pdi`
- `descripcion_pdi`
- `orden_pdi`

Las imagenes se guardan en el disco `public`, bajo `storage/app/public/prendas/danios`, y se exponen por el enlace simbolico de Laravel `/storage`.

## 5. Causas de daño

- `dano_en_tienda`
- `dano_en_transporte`
- `dano_por_manipulacion`
- `defecto_de_fabrica`
- `devolucion_danada`
- `desgaste`
- `mala_conservacion`
- `otro`

## 6. Tipos de daño

- `mancha`
- `roto`
- `cierre_danado`
- `boton_faltante`
- `costura_abierta`
- `decoloracion`
- `talla_mal_etiquetada`
- `defecto_fabrica`
- `otro`

## 7. Estados del reporte

- `reportado`
- `en_revision`
- `confirmado`
- `descartado`
- `reparado`
- `baja_definitiva`
- `resuelto`

## 8. Relacion opcional con inventario

El reporte puede crearse sin `cod_prenda_pdr`, sin `cod_prenda_variante_pdr` y sin `cod_inventario_pdr`. En ese caso queda como control administrativo y nunca registra Kardex.

Si se selecciona un inventario existente:

- se guardan los vinculos de prenda, variante e inventario;
- al crear el reporte no se mueve stock;
- `afecta_inventario_pdr` permite preparar el descuento para la confirmacion;
- si se confirma y corresponde, solo baja `cdi_inv`;
- si se repara y corresponde, solo sube `cdi_inv`;
- `cre_inv` no se modifica en este modulo.

## 9. Claridad de vinculacion con inventario

El formulario de registro muestra la seccion `Vinculacion opcional con inventario` para separar dos caminos:

- `Registrar daño sin afectar inventario`: crea un reporte administrativo, no mueve stock y no registra Kardex.
- `Vincular con una prenda existente`: permite seleccionar una variante con inventario para identificar la prenda y decidir si se descontara stock cuando el daño sea confirmado.

Cuando se selecciona una variante existente, la UI muestra:

- imagen principal de la variante o de la prenda;
- placeholder `Sin imagen registrada` si no existe imagen;
- nombre de prenda;
- SKU;
- color;
- talla;
- stock disponible actual `cdi_inv`;
- stock comprometido actual `cre_inv`;
- ubicacion;
- estado de la variante.

El texto anterior `Afectar inventario al confirmar` fue reemplazado por `Quitar del stock al confirmar el daño`.

La ayuda visible aclara que crear el reporte no mueve inventario. El descuento ocurre unicamente si el superadmin confirma el daño y el reporte fue creado con inventario vinculado y `afecta_inventario_pdr = true`.

La cantidad afectada se explica segun el caso:

- sin inventario: cantidad informativa;
- con inventario sin descuento: cantidad registrada sin modificar stock;
- con inventario y descuento marcado: cantidad que se descontara de `cdi_inv` al confirmar.

El detalle del reporte tambien indica si el caso es solo administrativo o si descontara/desconto stock al confirmarse, ademas de mostrar evidencia subida y la imagen de la prenda vinculada cuando existe.

## 10. Relacion con Kardex

El servicio `DamagedProductService` usa `InventoryMovementService` solo cuando existe inventario vinculado y el reporte fue marcado para afectar inventario.

Tipos agregados:

- `dano_confirmado`: registra cantidad negativa y referencia `prenda_danio`.
- `reparacion_danio`: registra cantidad positiva y referencia `prenda_danio`.

El Kardex no recalcula disponible como `cdi_inv - cre_inv`; registra los valores antes y despues que ya existen en inventario.

## 11. Logs y auditoria

Se generan `system_logs` en modulo `prendas_daniadas`:

- `reporte_danio_creado`
- `reporte_danio_en_revision`
- `reporte_danio_confirmado`
- `reporte_danio_descartado`
- `reporte_danio_reparado`
- `reporte_danio_baja_definitiva`
- `reporte_danio_resuelto`

Cuando se modifica `inventarios`, los observers existentes registran auditoria critica en `audit_logs`.

## 12. Rutas creadas

- `GET /superadmin/prendas-daniadas`
- `GET /superadmin/prendas-daniadas/crear`
- `POST /superadmin/prendas-daniadas`
- `GET /superadmin/prendas-daniadas/{reporte}`
- `POST /superadmin/prendas-daniadas/{reporte}/en-revision`
- `POST /superadmin/prendas-daniadas/{reporte}/confirmar`
- `POST /superadmin/prendas-daniadas/{reporte}/descartar`
- `POST /superadmin/prendas-daniadas/{reporte}/reparar`
- `POST /superadmin/prendas-daniadas/{reporte}/baja-definitiva`
- `POST /superadmin/prendas-daniadas/{reporte}/resolver`

Todas estan dentro del grupo `auth` + `superadmin` y se declararon antes del wildcard de `ResourceController`.

## 13. Seguridad aplicada

- Solo superadmin accede en esta fase.
- La creacion no descuenta stock.
- Confirmar solo aplica a reportes `reportado` o `en_revision`.
- Reparar solo aplica a reportes `confirmado`; si afectaron inventario, devuelve `cdi_inv` y registra Kardex.
- Baja definitiva solo aplica a reportes `confirmado` y no descuenta stock otra vez.
- Se valida cantidad mayor a 0.
- Se validan imagenes `jpg`, `jpeg`, `png`, `webp` de maximo 4 MB.
- Las modificaciones de inventario usan `DB::transaction` y `lockForUpdate`.

## 14. Pruebas manuales

1. Abrir `/superadmin/prendas-daniadas` y verificar listado independiente.
2. Crear reporte sin inventario y confirmar que no cambie inventario ni Kardex.
3. Crear reporte con inventario, `afecta_inventario_pdr = true`, y confirmar.
4. Verificar que `cdi_inv` baje y `cre_inv` no cambie.
5. Verificar Kardex `dano_confirmado` con referencia `prenda_danio`.
6. Reparar el reporte confirmado y verificar que `cdi_inv` suba.
7. Verificar Kardex `reparacion_danio`.
8. Dar baja definitiva y confirmar que no haya segundo descuento.
9. Probar acceso como cliente o vendedor sin permiso.

## 15. Pendientes

- Notificaciones especificas de cambios de estado para usuarios responsables.
- Exportacion CSV del modulo si se requiere para conciliacion.
- Adjuntar mas metadatos de revision cuando se definan politicas de calidad.
