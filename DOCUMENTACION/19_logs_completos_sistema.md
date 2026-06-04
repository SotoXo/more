# 19. Logs completos del sistema

## 1. Tabla system_logs

Se agrega la migracion `2026_06_04_000000_create_system_logs_table.php` para crear `system_logs`.

Campos principales:

- `cod_usuario_slg`: usuario autenticado que ejecuto la accion, nullable.
- `rol_slg`: rol capturado al momento del evento.
- `modulo_slg` y `accion_slg`: clasificacion operativa del evento.
- `entidad_tipo_slg` y `entidad_id_slg`: registro afectado cuando aplica.
- `descripcion_slg`: resumen legible de la accion.
- `nivel_slg`: `info`, `warning` o `critical`.
- `ip_slg`, `user_agent_slg`, `metodo_http_slg`, `ruta_slg`: contexto HTTP.
- `metadata_slg`: JSON con datos simples de trazabilidad.
- `created_at` y `updated_at`.

La tabla incluye indices por usuario, modulo, accion, nivel, fecha y entidad afectada.

## 2. Modelo SystemLog

Se crea `app/Models/SystemLog.php`.

Configuracion:

- Tabla: `system_logs`.
- `fillable` para todos los campos de log.
- Cast de `metadata_slg` como `array`.
- Relacion `usuario()` hacia `User` por `cod_usuario_slg -> users.id`.

## 3. Servicio SystemLogService

Se crea `app/Services/SystemLogService.php`.

Metodo principal:

```php
log(
    string $modulo,
    string $accion,
    string $descripcion,
    ?Model $entidad = null,
    string $nivel = 'info',
    array $metadata = []
)
```

Helpers disponibles:

- `info()`
- `warning()`
- `critical()`

El servicio captura automaticamente usuario autenticado, rol, IP, user agent, metodo HTTP y ruta actual. No falla si no hay usuario autenticado.

Tambien sanitiza metadata para omitir claves sensibles como passwords, tokens, rutas, URLs, archivos y comprobantes.

## 4. Eventos registrados

Autenticacion:

- Inicio de sesion exitoso.
- Cierre de sesion.
- Intento fallido de inicio de sesion.

Usuarios y seguridad:

- Rol creado, actualizado y eliminado.
- Usuario creado y actualizado.
- Usuario activado/desactivado.
- Cambio de rol.

Catalogo base:

- Categoria, marca, color, talla y temporada creadas, actualizadas y eliminadas.

Prendas, variantes e imagenes:

- Prenda creada, actualizada, activada/desactivada y eliminada.
- Variante creada, actualizada, activada/desactivada y eliminada.
- Imagen agregada, actualizada y eliminada.

Inventario:

- Stock actualizado manualmente.
- Stock inicial registrado para variantes.
- Cambio de ubicacion fisica.

Reservas:

- Reserva creada y enviada a revision.
- Reserva aprobada.
- Reserva rechazada.
- Reserva cancelada.
- Reserva lista para recojo.
- Reserva recogida.
- Reserva convertida o vinculada a venta.
- Reserva vencida.

Ventas:

- Venta directa creada.
- Compra online enviada a revision.
- Venta online confirmada.
- Venta incompleta.
- Venta cancelada.
- Venta lista para recojo.
- Venta entregada.

Pagos:

- Comprobante subido.
- Pago confirmado.
- Pago rechazado.
- Monto verificado registrado dentro del evento de confirmacion.

Reportes y exportaciones:

- Reportes operativos visualizados.
- CSV exportado.
- Vista imprimible abierta.

Notificaciones:

- No se genera log independiente por cada notificacion para evitar ruido y duplicidad. El evento comercial que origina la notificacion queda registrado.

## 5. Vista /superadmin/logs

La vista `Registro de actividad` ahora consume datos reales desde `system_logs`.

Muestra:

- Fecha y hora.
- Usuario.
- Rol.
- Modulo.
- Accion.
- Descripcion.
- Entidad afectada.
- Nivel.
- IP.
- Ruta.

Tambien incluye cards de resumen:

- Logs de hoy.
- Acciones criticas.
- Acciones warning.
- Ultimo usuario activo.
- Modulo con mas actividad.

## 6. Filtros disponibles

Filtros implementados:

- Fecha desde.
- Fecha hasta.
- Usuario.
- Modulo.
- Accion.
- Nivel.
- Busqueda por descripcion.

La tabla usa paginacion y conserva filtros en la navegacion.

## 7. Seguridad aplicada

Las rutas permanecen dentro del grupo:

```php
Route::middleware(['auth', 'superadmin'])
    ->prefix('superadmin')
```

Rutas:

- `GET /superadmin/logs`
- `GET /superadmin/logs/{log}`

Solo superadmin puede consultar logs. Clientes y vendedores no tienen acceso salvo cambio explicito futuro de permisos.

El servicio no registra passwords, tokens ni comprobantes completos dentro de metadata.

## 8. Logs vs auditoria critica

Esta fase implementa logs operativos persistentes.

No implementa:

- `audit_logs`.
- Valores anteriores/nuevos completos.
- Comparacion exhaustiva antes/despues.
- Triggers PostgreSQL.
- Backups.

`system_logs` puede guardar metadata simple de trazabilidad, como total de venta, numero de reserva, estado actual, ID de recurso o monto confirmado.

## 9. Pendientes

- Crear `audit_logs` con valores antes/despues cuando se apruebe la fase de auditoria critica.
- Evaluar triggers si se aprueba auditoria a nivel base de datos.
- Definir estrategia de backups.
