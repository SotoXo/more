# Diccionario de datos - Mati Boutique

Actualizado: 2026-06-17.

Origen de verificacion: base PostgreSQL `laravel`, con 40 migraciones aplicadas y 33 tablas publicas.

## roles

- `id_rol` -> identificador unico del rol.
- `nom_rol` -> nombre del rol.
- `des_rol` -> descripcion del rol.
- `est_rol` -> estado activo/inactivo del rol.
- `fec_rol` -> fecha funcional de registro del rol.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## users

- `id` -> identificador unico del usuario.
- `email` -> correo electronico del usuario.
- `email_verified_at` -> fecha de verificacion del correo electronico.
- `password` -> contrasena cifrada del usuario.
- `remember_token` -> token de recordatorio de sesion.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `nom_usu` -> nombre del usuario.
- `app_usu` -> apellido paterno del usuario.
- `apm_usu` -> apellido materno del usuario.
- `tel_usu` -> telefono del usuario.
- `cod_roles_usu` -> rol asignado al usuario.
- `est_usu` -> estado activo/inactivo del usuario.
- `fec_usu` -> fecha funcional de registro del usuario.

## categorias

- `id_categoria` -> identificador unico de la categoria.
- `nom_cat` -> nombre de la categoria.
- `des_cat` -> descripcion de la categoria.
- `cod_categoria_padre_cat` -> categoria padre asociada.
- `est_cat` -> estado activo/inactivo de la categoria.
- `fec_cat` -> fecha funcional de registro de la categoria.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `tip_tal_cat` -> tipo de sistema de talla sugerido para la categoria.
- `gru_tal_cat` -> grupo de talla sugerido para la categoria.

## marcas

- `id_marca` -> identificador unico de la marca.
- `nom_mar` -> nombre de la marca.
- `des_mar` -> descripcion de la marca.
- `est_mar` -> estado activo/inactivo de la marca.
- `fec_mar` -> fecha funcional de registro de la marca.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## colores

- `id_color` -> identificador unico del color.
- `nom_col` -> nombre real o tono real del color.
- `hex_col` -> codigo hexadecimal real del color.
- `est_col` -> estado activo/inactivo del color.
- `fec_col` -> fecha funcional de registro del color.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `fam_col` -> familia o color dominante asociado.
- `hex_dom_col` -> codigo hexadecimal del color dominante.
- `ord_col` -> orden de visualizacion del color.

## tallas

- `id_talla` -> identificador unico de la talla.
- `nom_tal` -> nombre visible de la talla.
- `ord_tal` -> orden de visualizacion de la talla.
- `est_tal` -> estado activo/inactivo de la talla.
- `fec_tal` -> fecha funcional de registro de la talla.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `sis_tal` -> sistema de talla asociado.
- `gru_tal` -> grupo de uso de la talla.
- `seg_tal` -> segmento de la talla: adulto, infantil o bebe.

## temporadas

- `id_temporada` -> identificador unico de la temporada.
- `nom_tem` -> nombre de la temporada.
- `des_tem` -> descripcion de la temporada.
- `est_tem` -> estado activo/inactivo de la temporada.
- `fec_tem` -> fecha funcional de registro de la temporada.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `cod_tem` -> codigo corto o clave de la temporada.
- `ord_tem` -> orden de visualizacion de la temporada.
- `fec_ini_tem` -> fecha de inicio de la temporada.
- `fec_fin_tem` -> fecha de finalizacion de la temporada.

## prendas

- `id_prenda` -> identificador unico de la prenda.
- `nom_pre` -> nombre de la prenda.
- `des_pre` -> descripcion de la prenda.
- `cod_categorias_pre` -> categoria asociada a la prenda.
- `cod_marcas_pre` -> marca asociada a la prenda.
- `cod_temporadas_pre` -> temporada asociada a la prenda.
- `gen_pre` -> genero o publico objetivo de la prenda.
- `prb_pre` -> precio base de la prenda.
- `est_pre` -> estado activo/inactivo de la prenda.
- `cod_usuario_registra_pre` -> usuario que registro la prenda.
- `fec_pre` -> fecha funcional de registro de la prenda.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## prenda_variantes

- `id_prenda_variante` -> identificador unico de la variante de prenda.
- `cod_prendas_prv` -> prenda asociada a la variante.
- `cod_colores_prv` -> color asociado a la variante.
- `cod_tallas_prv` -> talla asociada a la variante.
- `sku_prv` -> codigo SKU de la variante.
- `cob_prv` -> codigo de barras de la variante.
- `pve_prv` -> precio de venta de la variante.
- `cos_prv` -> costo de la variante.
- `est_prv` -> estado activo/inactivo de la variante.
- `fec_prv` -> fecha funcional de registro de la variante.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## imagenes_prenda

- `id_imagen_prenda` -> identificador unico de la imagen de la prenda.
- `cod_prendas_igp` -> prenda asociada a la imagen.
- `cod_prenda_variantes_igp` -> variante de prenda asociada a la imagen.
- `rut_igp` -> ruta de almacenamiento de la imagen.
- `url_igp` -> URL publica o accesible de la imagen.
- `pri_igp` -> indicador de imagen principal.
- `ord_igp` -> orden de visualizacion de la imagen.
- `fue_igp` -> fuente u origen de la imagen.
- `fec_igp` -> fecha funcional de registro de la imagen.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## inventarios

- `id_inventario` -> identificador unico del inventario.
- `cod_prenda_variantes_inv` -> variante de prenda controlada por el inventario.
- `cdi_inv` -> cantidad disponible.
- `cre_inv` -> cantidad reservada.
- `ubi_inv` -> ubicacion fisica o logica del inventario.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## inventario_movimientos

- `id` -> identificador unico del movimiento de inventario.
- `cod_inventario_inm` -> inventario asociado al movimiento.
- `cod_prenda_variante_inm` -> variante de prenda afectada.
- `tipo_movimiento_inm` -> tipo de movimiento realizado.
- `cantidad_inm` -> cantidad movida.
- `cdi_antes_inm` -> cantidad disponible antes del movimiento.
- `cdi_despues_inm` -> cantidad disponible despues del movimiento.
- `cre_antes_inm` -> cantidad reservada antes del movimiento.
- `cre_despues_inm` -> cantidad reservada despues del movimiento.
- `ubicacion_antes_inm` -> ubicacion anterior del inventario.
- `ubicacion_despues_inm` -> ubicacion posterior del inventario.
- `referencia_tipo_inm` -> tipo de entidad que origino el movimiento.
- `referencia_id_inm` -> identificador de la entidad que origino el movimiento.
- `cod_usuario_inm` -> usuario que origino o registro el movimiento.
- `descripcion_inm` -> descripcion legible del movimiento.
- `metadata_inm` -> datos adicionales del movimiento en formato JSON.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## ventas

- `id_venta` -> identificador unico de la venta.
- `nve_ven` -> numero de venta.
- `cod_usuario_cliente_ven` -> usuario cliente asociado a la venta.
- `cod_usuario_vendedor_ven` -> usuario vendedor asociado a la venta.
- `tiv_ven` -> tipo de venta.
- `sub_ven` -> subtotal de la venta.
- `des_ven` -> descuento aplicado a la venta.
- `tot_ven` -> total final de la venta.
- `mpa_ven` -> metodo de pago principal de la venta.
- `est_ven` -> estado comercial de la venta.
- `fec_ven` -> fecha funcional de registro de la venta.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `est_logistico_ven` -> estado logistico de la venta.
- `fec_disponible_recojo_ven` -> fecha desde la que la venta esta disponible para recojo.
- `fec_limite_recojo_ven` -> fecha limite para recojo de la venta.
- `punto_recojo_ven` -> punto o direccion de recojo.
- `origen_stock_ven` -> origen del stock usado en la venta.
- `fec_entrega_ven` -> fecha de entrega o recojo confirmado.
- `cod_usuario_entrega_ven` -> usuario que registro la entrega.

## detalle_ventas

- `id_detalle_venta` -> identificador unico del detalle de venta.
- `cod_ventas_dve` -> venta asociada al detalle.
- `cod_prenda_variantes_dve` -> variante de prenda vendida.
- `can_dve` -> cantidad vendida.
- `pun_dve` -> precio unitario de venta.
- `sub_dve` -> subtotal del detalle de venta.
- `fec_dve` -> fecha funcional de registro del detalle.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## venta_pagos

- `id_venta_pago` -> identificador unico del pago de venta.
- `cod_ventas_vpg` -> venta asociada al pago.
- `monto_declarado_vpg` -> monto declarado por el cliente o vendedor.
- `monto_verificado_vpg` -> monto confirmado durante la revision.
- `metodo_pago_vpg` -> metodo de pago usado.
- `comprobante_ruta_vpg` -> ruta interna del comprobante.
- `comprobante_url_vpg` -> URL publica o accesible del comprobante.
- `est_vpg` -> estado de revision del pago.
- `cod_usuario_verifica_vpg` -> usuario que verifico el pago.
- `fec_verificacion_vpg` -> fecha de verificacion del pago.
- `obs_vpg` -> observacion de revision del pago.
- `fec_vpg` -> fecha funcional de registro del pago.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## reservas

- `id_reserva` -> identificador unico de la reserva.
- `nre_res` -> numero de reserva.
- `cod_usuario_cliente_res` -> usuario cliente asociado a la reserva.
- `cod_usuario_responsable_res` -> usuario responsable que registro la reserva.
- `est_res` -> estado comercial de la reserva.
- `ven_res` -> fecha de vencimiento de la reserva.
- `obs_res` -> observacion general de la reserva.
- `fec_res` -> fecha funcional de registro de la reserva.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `sub_res` -> subtotal de la reserva.
- `des_res` -> descuento aplicado a la reserva.
- `tot_res` -> total final de la reserva.
- `monto_pagado_res` -> monto pagado acumulado de la reserva.
- `saldo_res` -> saldo pendiente de la reserva.
- `est_pago_res` -> estado general del pago de la reserva.
- `fec_disponible_recojo_res` -> fecha desde la que la reserva esta disponible para recojo.
- `fec_limite_recojo_res` -> fecha limite para recojo de la reserva.
- `punto_recojo_res` -> punto o direccion de recojo.
- `origen_stock_res` -> origen del stock usado en la reserva.
- `cod_usuario_verifica_pago_res` -> usuario que verifico el pago principal.
- `fec_verificacion_pago_res` -> fecha de verificacion del pago principal.
- `obs_pago_res` -> observacion de revision de pago.
- `est_logistico_res` -> estado logistico de la reserva.
- `fec_recogida_res` -> fecha de recojo confirmado.
- `cod_usuario_entrega_res` -> usuario que registro la entrega o recojo.

## detalle_reservas

- `id_detalle_reserva` -> identificador unico del detalle de reserva.
- `cod_reservas_dre` -> reserva asociada al detalle.
- `cod_prenda_variantes_dre` -> variante de prenda reservada.
- `can_dre` -> cantidad reservada.
- `fec_dre` -> fecha funcional de registro del detalle.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.
- `pun_dre` -> precio unitario congelado de la reserva.
- `sub_dre` -> subtotal del detalle de reserva.

## reserva_pagos

- `id_reserva_pago` -> identificador unico del pago de reserva.
- `cod_reservas_rpg` -> reserva asociada al pago.
- `monto_declarado_rpg` -> monto declarado en el pago.
- `monto_confirmado_rpg` -> monto confirmado durante la revision.
- `porcentaje_rpg` -> porcentaje de pago aplicado sobre la reserva.
- `metodo_pago_rpg` -> metodo de pago usado.
- `comprobante_ruta_rpg` -> ruta interna del comprobante.
- `comprobante_url_rpg` -> URL publica o accesible del comprobante.
- `est_rpg` -> estado de revision del pago.
- `cod_usuario_verifica_rpg` -> usuario que verifico el pago.
- `fec_verificacion_rpg` -> fecha de verificacion del pago.
- `obs_rpg` -> observacion de revision del pago.
- `fec_rpg` -> fecha funcional de registro del pago.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## favoritos

- `id_favorito` -> identificador unico del favorito.
- `cod_usuarios_fav` -> usuario que marco el favorito.
- `cod_prendas_fav` -> prenda marcada como favorita.
- `fec_fav` -> fecha funcional de registro del favorito.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## prenda_danio_reportes

- `id` -> identificador unico del reporte de prenda danada.
- `codigo_pdr` -> codigo unico del reporte.
- `nombre_prenda_pdr` -> nombre de la prenda reportada.
- `descripcion_prenda_pdr` -> descripcion de la prenda reportada.
- `cod_prenda_pdr` -> prenda asociada al reporte.
- `cod_prenda_variante_pdr` -> variante asociada al reporte.
- `cod_inventario_pdr` -> inventario asociado al reporte.
- `cod_usuario_reporta_pdr` -> usuario que reporto el dano.
- `cod_usuario_revisa_pdr` -> usuario que reviso el reporte.
- `causa_pdr` -> causa registrada del dano.
- `tipo_danio_pdr` -> tipo de dano reportado.
- `severidad_pdr` -> severidad del dano.
- `cantidad_pdr` -> cantidad de unidades afectadas.
- `estado_pdr` -> estado del reporte.
- `decision_pdr` -> decision tomada tras la revision.
- `observacion_pdr` -> observacion inicial del reporte.
- `observacion_revision_pdr` -> observacion registrada durante la revision.
- `fecha_reporte_pdr` -> fecha funcional del reporte.
- `fecha_revision_pdr` -> fecha funcional de la revision.
- `afecta_inventario_pdr` -> indica si el dano afecta inventario.
- `metadata_pdr` -> datos adicionales del reporte en formato JSON.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## prenda_danio_imagenes

- `id` -> identificador unico de la imagen del reporte de dano.
- `cod_prenda_danio_reporte_pdi` -> reporte de dano asociado.
- `ruta_pdi` -> ruta interna de la imagen.
- `url_pdi` -> URL publica o accesible de la imagen.
- `descripcion_pdi` -> descripcion de la imagen.
- `orden_pdi` -> orden de visualizacion de la imagen.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## system_logs

- `id` -> identificador unico del registro de actividad.
- `cod_usuario_slg` -> usuario asociado al evento.
- `rol_slg` -> rol del usuario al momento del evento.
- `modulo_slg` -> modulo del sistema donde ocurrio el evento.
- `accion_slg` -> accion registrada.
- `entidad_tipo_slg` -> tipo de entidad relacionada.
- `entidad_id_slg` -> identificador de la entidad relacionada.
- `descripcion_slg` -> descripcion legible del evento.
- `nivel_slg` -> nivel de importancia del evento.
- `ip_slg` -> direccion IP del solicitante.
- `user_agent_slg` -> agente de navegador o cliente usado.
- `metodo_http_slg` -> metodo HTTP de la solicitud.
- `ruta_slg` -> ruta donde ocurrio el evento.
- `metadata_slg` -> datos adicionales del evento en formato JSON.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## audit_logs

- `id` -> identificador unico del registro de auditoria.
- `cod_usuario_aul` -> usuario asociado al cambio auditado.
- `tabla_aul` -> tabla afectada por el cambio.
- `registro_id_aul` -> identificador del registro afectado.
- `accion_aul` -> accion auditada.
- `modulo_aul` -> modulo que origino el cambio.
- `valores_anteriores_aul` -> valores antes del cambio en formato JSON.
- `valores_nuevos_aul` -> valores despues del cambio en formato JSON.
- `campos_modificados_aul` -> lista de campos modificados en formato JSON.
- `descripcion_aul` -> descripcion legible del cambio auditado.
- `nivel_aul` -> nivel de importancia del cambio.
- `ip_aul` -> direccion IP del solicitante.
- `user_agent_aul` -> agente de navegador o cliente usado.
- `ruta_aul` -> ruta donde ocurrio el cambio.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## database_backups

- `id` -> identificador unico del respaldo.
- `nombre_bak` -> nombre visible del respaldo.
- `archivo_bak` -> nombre del archivo generado.
- `ruta_bak` -> ruta interna del archivo de respaldo.
- `disco_bak` -> disco de almacenamiento usado.
- `tamano_bak` -> tamano del archivo de respaldo.
- `estado_bak` -> estado de generacion del respaldo.
- `mensaje_bak` -> mensaje tecnico o resultado del respaldo.
- `cod_usuario_bak` -> usuario que solicito el respaldo.
- `fec_bak` -> fecha funcional de generacion del respaldo.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## notifications

- `id` -> identificador UUID de la notificacion.
- `type` -> clase o tipo de notificacion.
- `notifiable_type` -> tipo de entidad notificada.
- `notifiable_id` -> identificador de la entidad notificada.
- `data` -> contenido de la notificacion en formato JSON.
- `read_at` -> fecha de lectura de la notificacion.
- `created_at` -> fecha de creacion del registro en Laravel.
- `updated_at` -> fecha de ultima actualizacion del registro en Laravel.

## cache

- `key` -> clave del registro de cache.
- `value` -> valor almacenado en cache.
- `expiration` -> tiempo de expiracion del registro.

## cache_locks

- `key` -> clave del bloqueo de cache.
- `owner` -> propietario del bloqueo.
- `expiration` -> tiempo de expiracion del bloqueo.

## sessions

- `id` -> identificador unico de la sesion.
- `user_id` -> usuario asociado a la sesion.
- `ip_address` -> direccion IP de la sesion.
- `user_agent` -> agente de navegador o cliente usado.
- `payload` -> contenido serializado de la sesion.
- `last_activity` -> marca temporal de ultima actividad.

## password_reset_tokens

- `email` -> correo electronico asociado al token.
- `token` -> token de restablecimiento de contrasena.
- `created_at` -> fecha de creacion del token.

## jobs

- `id` -> identificador unico del trabajo en cola.
- `queue` -> cola donde se registro el trabajo.
- `payload` -> contenido serializado del trabajo.
- `attempts` -> cantidad de intentos ejecutados.
- `reserved_at` -> marca temporal de reserva del trabajo.
- `available_at` -> marca temporal desde la que el trabajo esta disponible.
- `created_at` -> marca temporal de creacion del trabajo.

## job_batches

- `id` -> identificador unico del lote de trabajos.
- `name` -> nombre del lote.
- `total_jobs` -> cantidad total de trabajos del lote.
- `pending_jobs` -> cantidad de trabajos pendientes.
- `failed_jobs` -> cantidad de trabajos fallidos.
- `failed_job_ids` -> identificadores de trabajos fallidos.
- `options` -> opciones serializadas del lote.
- `cancelled_at` -> marca temporal de cancelacion.
- `created_at` -> marca temporal de creacion del lote.
- `finished_at` -> marca temporal de finalizacion del lote.

## failed_jobs

- `id` -> identificador unico del trabajo fallido.
- `uuid` -> identificador UUID del trabajo fallido.
- `connection` -> conexion de cola usada.
- `queue` -> cola donde fallo el trabajo.
- `payload` -> contenido serializado del trabajo.
- `exception` -> excepcion o error registrado.
- `failed_at` -> fecha en la que fallo el trabajo.

## migrations

- `id` -> identificador unico del registro de migracion.
- `migration` -> nombre de la migracion ejecutada.
- `batch` -> lote de ejecucion de la migracion.
