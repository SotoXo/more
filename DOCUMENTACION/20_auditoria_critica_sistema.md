# 20. Auditoria critica del sistema

## 1. Diferencia entre system_logs y audit_logs

`system_logs` registra actividad general: quien hizo una accion, en que modulo y sobre que entidad.

`audit_logs` registra cambios sensibles con trazabilidad antes/despues: que dato cambio, como estaba antes, como quedo despues, quien lo cambio y cuando.

Ambas tablas conviven:

- `system_logs`: contexto operativo y eventos de negocio.
- `audit_logs`: valores anteriores, valores nuevos y campos modificados.

## 2. Tabla audit_logs

Se agrega la migracion `2026_06_04_010000_create_audit_logs_table.php`.

Campos principales:

- `cod_usuario_aul`: usuario autenticado responsable, nullable.
- `tabla_aul`: tabla afectada.
- `registro_id_aul`: ID del registro afectado.
- `accion_aul`: `created`, `updated`, `deleted`, `restored` o `force_deleted`.
- `modulo_aul`: modulo operativo.
- `valores_anteriores_aul`: JSON con valores previos.
- `valores_nuevos_aul`: JSON con valores nuevos.
- `campos_modificados_aul`: JSON con campos auditados.
- `descripcion_aul`: texto legible del cambio.
- `nivel_aul`: `info`, `warning` o `critical`.
- `ip_aul`, `user_agent_aul`, `ruta_aul`: contexto HTTP.
- `created_at` y `updated_at`.

## 3. Modelo AuditLog

Se crea `app/Models/AuditLog.php`.

Configuracion:

- Tabla: `audit_logs`.
- Fillable para todos los campos de auditoria.
- Casts array para `valores_anteriores_aul`, `valores_nuevos_aul` y `campos_modificados_aul`.
- Relacion `usuario()` hacia `User` por `cod_usuario_aul -> users.id`.

## 4. Servicio AuditLogService

Se crea `app/Services/AuditLogService.php`.

Metodo principal:

```php
record(
    string $tabla,
    ?int $registroId,
    string $accion,
    string $modulo,
    array $valoresAnteriores = [],
    array $valoresNuevos = [],
    array $camposModificados = [],
    string $nivel = 'info',
    ?string $descripcion = null
)
```

El servicio captura automaticamente:

- Usuario autenticado.
- IP.
- User agent.
- Ruta actual.

Tambien sanitiza valores para omitir claves sensibles como password, remember token, tokens, comprobantes, URLs y rutas de archivos.

## 5. Observers implementados

Se implementa un observer compacto:

- `app/Observers/CriticalAuditObserver.php`

Este observer usa un mapa por tabla para auditar solo campos criticos. Se registra en `AppServiceProvider`.

Modelos observados:

- `Inventario`
- `Venta`
- `DetalleVenta`
- `Reserva`
- `DetalleReserva`
- `VentaPago`
- `ReservaPago`
- `Prenda`
- `PrendaVariante`
- `User`
- `Role`

## 6. Tablas auditadas

- `inventarios`
- `ventas`
- `detalle_ventas`
- `reservas`
- `detalle_reservas`
- `venta_pagos`
- `reserva_pagos`
- `prendas`
- `prenda_variantes`
- `users`
- `roles`

## 7. Campos criticos auditados

Inventario:

- `cdi_inv`
- `cre_inv`
- `ubi_inv`

Ventas:

- `tot_ven`
- `est_ven`
- `mpa_ven`
- `est_logistico_ven`
- `fec_entrega_ven`

Detalle de ventas:

- `can_dve`
- `pun_dve`
- `sub_dve`

Reservas:

- `est_res`
- `est_pago_res`
- `est_logistico_res`
- `tot_res`
- `ven_res`
- `fec_disponible_recojo_res`
- `fec_limite_recojo_res`
- `fec_recogida_res`

Detalle de reservas:

- `can_dre`
- `pun_dre`
- `sub_dre`

Pagos:

- `monto_declarado_vpg`
- `monto_verificado_vpg`
- `est_vpg`
- `metodo_pago_vpg`
- `obs_vpg`
- `monto_declarado_rpg`
- `monto_confirmado_rpg`
- `est_rpg`
- `metodo_pago_rpg`
- `obs_rpg`

Prendas y variantes:

- `prb_pre`
- `est_pre`
- `cod_categorias_pre`
- `cod_marcas_pre`
- `pve_prv`
- `est_prv`
- `cod_colores_prv`
- `cod_tallas_prv`
- `sku_prv`

Usuarios y roles:

- `cod_roles_usu`
- `est_usu`
- `email`
- `tel_usu`
- `nom_rol`
- `est_rol`

## 8. Vista /superadmin/auditoria

Se crea la vista `Auditoria critica`.

Rutas:

- `GET /superadmin/auditoria`
- `GET /superadmin/auditoria/{auditLog}`

La vista muestra:

- Fecha.
- Usuario.
- Tabla afectada.
- Registro.
- Accion.
- Modulo.
- Nivel.
- Campos modificados.
- Detalle con valores anteriores y nuevos.

## 9. Filtros disponibles

- Fecha desde.
- Fecha hasta.
- Usuario.
- Tabla.
- Accion.
- Modulo.
- Nivel.
- Busqueda por descripcion.

## 10. Seguridad aplicada

Las rutas estan dentro del grupo `auth + superadmin`.

Clientes y vendedores no pueden acceder a `/superadmin/auditoria` salvo que se cambie explicitamente la politica de acceso.

Los datos sensibles se sanitizan antes de escribirse en `audit_logs`.

## 11. Por que no se usaron triggers todavia

Esta fase usa Laravel Observers porque:

- Permiten integrarse con los modelos actuales sin tocar PostgreSQL.
- Mantienen la auditoria cerca de la capa de dominio de Laravel.
- Evitan introducir triggers antes de que se apruebe esa fase.
- Reducen riesgo de romper flujos comerciales existentes.

Los triggers PostgreSQL quedan como mejora futura opcional.

## 12. Pendientes

- Evaluar triggers PostgreSQL para auditoria a nivel base de datos.
- Definir backups y politica de retencion.
