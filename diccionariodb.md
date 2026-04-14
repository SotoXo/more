# Diccionario de campos - Versión funcional Mati Boutique

## roles

- `id_rol` → identificador único del rol
- `nom_rol` → nombre del rol
- `des_rol` → descripción del rol
- `est_rol` → estado del rol
- `fec_rol` → fecha de registro del rol
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## users

- `id` → identificador único del usuario
- `email` → correo electrónico del usuario
- `email_verified_at` → fecha de verificación del correo electrónico
- `password` → contraseña del usuario
- `remember_token` → token de recordatorio de sesión
- `nom_usu` → nombre del usuario
- `app_usu` → apellido paterno del usuario
- `apm_usu` → apellido materno del usuario
- `tel_usu` → teléfono del usuario
- `cod_roles_usu` → código del rol asociado al usuario
- `est_usu` → estado del usuario
- `fec_usu` → fecha de registro del usuario
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## categorias

## categorias

- `id_categoria` → identificador único de la categoría
- `nom_cat` → nombre de la categoría
- `des_cat` → descripción de la categoría
- `tip_tal_cat` → tipo de sistema de talla sugerido para la categoría
- `cod_categoria_padre_cat` → código de la categoría padre asociado a la categoría
- `est_cat` → estado de la categoría
- `fec_cat` → fecha de registro de la categoría
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## marcas

- `id_marca` → identificador único de la marca
- `nom_mar` → nombre de la marca
- `des_mar` → descripción de la marca
- `est_mar` → estado de la marca
- `fec_mar` → fecha de registro de la marca
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## colores

- `id_color` → identificador único del color
- `nom_col` → nombre real o tono real del color
- `fam_col` → familia o color dominante asociado al color
- `hex_col` → código hexadecimal real del color
- `hex_dom_col` → código hexadecimal del color dominante o base
- `ord_col` → orden de visualización del color en filtros o paletas
- `est_col` → estado del color
- `fec_col` → fecha de registro del color
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## tallas

## tallas

- `id_talla` → identificador único de la talla
- `nom_tal` → nombre visible de la talla
- `sis_tal` → sistema de talla asociado a la talla
- `gru_tal` → grupo de uso de la talla según tipo de prenda
- `ord_tal` → orden de visualización de la talla
- `est_tal` → estado de la talla
- `fec_tal` → fecha de registro de la talla
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## temporadas

## temporadas

- `id_temporada` → identificador único de la temporada
- `nom_tem` → nombre de la temporada
- `cod_tem` → código corto o clave de la temporada
- `des_tem` → descripción de la temporada
- `ord_tem` → orden de visualización de la temporada
- `est_tem` → estado de la temporada
- `fec_tem` → fecha de registro de la temporada
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## prendas

- `id_prenda` → identificador único de la prenda
- `nom_pre` → nombre de la prenda
- `des_pre` → descripción de la prenda
- `cod_categorias_pre` → código de la categoría asociado a la prenda
- `cod_marcas_pre` → código de la marca asociado a la prenda
- `cod_temporadas_pre` → código de la temporada asociado a la prenda
- `gen_pre` → género de la prenda
- `prb_pre` → precio base de la prenda
- `est_pre` → estado de la prenda
- `cod_usuario_registra_pre` → código del usuario que registra la prenda
- `fec_pre` → fecha de registro de la prenda
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## prenda_variantes

- `id_prenda_variante` → identificador único de la variante de prenda
- `cod_prendas_prv` → código de la prenda asociado a la variante
- `cod_colores_prv` → código del color asociado a la variante
- `cod_tallas_prv` → código de la talla asociado a la variante
- `sku_prv` → código SKU de la variante
- `cob_prv` → código de barras de la variante
- `pve_prv` → precio de venta de la variante
- `cos_prv` → costo de la variante
- `est_prv` → estado de la variante
- `fec_prv` → fecha de registro de la variante
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## imagenes_prenda

- `id_imagen_prenda` → identificador único de la imagen de la prenda
- `cod_prendas_igp` → código de la prenda asociado a la imagen
- `cod_prenda_variante_igp` → código de la variante de prenda asociado a la imagen
- `rut_igp` → ruta de almacenamiento de la imagen
- `url_igp` → dirección o enlace de acceso a la imagen
- `pri_igp` → indicador de imagen principal
- `ord_igp` → orden de visualización de la imagen
- `fue_igp` → fuente de la imagen
- `fec_igp` → fecha de registro de la imagen
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## inventarios

- `id_inventario` → identificador único del inventario
- `cod_prenda_variantes_inv` → código de la variante de prenda asociado al inventario
- `cdi_inv` → cantidad disponible en inventario
- `cre_inv` → cantidad reservada en inventario
- `ubi_inv` → ubicación física del inventario
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## ventas

- `id_venta` → identificador único de la venta
- `nve_ven` → número de venta
- `cod_usuario_cliente_ven` → código del usuario cliente asociado a la venta
- `cod_usuario_vendedor_ven` → código del usuario vendedor asociado a la venta
- `tiv_ven` → tipo de venta
- `sub_ven` → subtotal de la venta
- `des_ven` → descuento de la venta
- `tot_ven` → total final de la venta
- `mpa_ven` → método de pago de la venta
- `est_ven` → estado de la venta
- `fec_ven` → fecha de registro de la venta
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## detalle_ventas

- `id_detalle_venta` → identificador único del detalle de venta
- `cod_ventas_dve` → código de la venta asociado al detalle
- `cod_prenda_variantes_dve` → código de la variante de prenda asociado al detalle
- `can_dve` → cantidad vendida
- `pun_dve` → precio unitario de venta
- `sub_dve` → subtotal del detalle de venta
- `fec_dve` → fecha de registro del detalle de venta
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## reservas

- `id_reserva` → identificador único de la reserva
- `nre_res` → número de reserva
- `cod_usuario_cliente_res` → código del usuario cliente asociado a la reserva
- `cod_usuario_responsable_res` → código del usuario responsable que registra la reserva
- `est_res` → estado de la reserva
- `ven_res` → fecha de vencimiento de la reserva
- `obs_res` → observación de la reserva
- `fec_res` → fecha de registro de la reserva
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## detalle_reservas

- `id_detalle_reserva` → identificador único del detalle de reserva
- `cod_reservas_dre` → código de la reserva asociado al detalle
- `cod_prenda_variantes_dre` → código de la variante de prenda asociado al detalle
- `can_dre` → cantidad reservada
- `fec_dre` → fecha de registro del detalle de reserva
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel

## favoritos

- `id_favorito` → identificador único del favorito
- `cod_usuarios_fav` → código del usuario asociado al favorito
- `cod_prendas_fav` → código de la prenda asociado al favorito
- `fec_fav` → fecha de registro del favorito
- `created_at` → fecha de creación del registro en Laravel
- `updated_at` → fecha de última actualización del registro en Laravel
