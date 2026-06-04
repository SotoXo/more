# 21. Copias de seguridad del sistema

## 1. Objetivo

Se agrega un modulo de backups manuales para que el superadmin pueda generar, consultar y descargar copias de seguridad de la base de datos PostgreSQL de Mati Boutique.

El modulo no instala paquetes nuevos, no usa triggers PostgreSQL y no expone archivos desde `public/`.

## 2. Tabla database_backups

Se crea la migracion `2026_06_04_020000_create_database_backups_table.php`.

Campos principales:

- `nombre_bak`: nombre legible del backup.
- `archivo_bak`: nombre del archivo generado.
- `ruta_bak`: ruta relativa dentro de `storage/app`.
- `disco_bak`: disco usado, por defecto `local`.
- `tamano_bak`: tamano del archivo en bytes.
- `estado_bak`: `pendiente`, `exitoso` o `fallido`.
- `mensaje_bak`: resultado o error seguro del proceso.
- `cod_usuario_bak`: usuario que genero el backup.
- `fec_bak`: fecha y hora operativa del intento.
- `created_at` y `updated_at`.

La tabla incluye indices por estado, usuario y fecha.

## 3. Modelo DatabaseBackup

Se crea `app/Models/DatabaseBackup.php`.

Configuracion:

- Tabla: `database_backups`.
- Fillable para todos los campos del backup.
- Casts:
  - `fec_bak` como `datetime`.
  - `tamano_bak` como `integer`.
- Relacion `usuario()` hacia `User` por `cod_usuario_bak -> users.id`.

## 4. Servicio DatabaseBackupService

Se crea `app/Services/DatabaseBackupService.php`.

Responsabilidades:

- Crear el registro inicial con estado `pendiente`.
- Generar nombres con formato `mati_boutique_backup_YYYYMMDD_HHMMSS.sql`.
- Crear la carpeta de almacenamiento si no existe.
- Validar que `pg_dump` este disponible dentro del contenedor Laravel.
- Ejecutar el respaldo de PostgreSQL.
- Actualizar el registro a `exitoso` o `fallido`.
- Calcular y guardar el tamano del archivo.
- Guardar mensajes de error seguros sin contrasena.
- Registrar eventos en `system_logs`.
- Validar descarga protegida y evitar path traversal.

## 5. Uso de pg_dump

El servicio toma la conexion activa desde `config/database.php`.

El comando ejecutado sigue esta estructura:

```bash
pg_dump -h {host} -p {port} -U {user} -d {database} --clean --if-exists --no-owner --no-privileges -f {ruta_archivo}
```

La contrasena se pasa mediante la variable de entorno `PGPASSWORD` del proceso y no se escribe en logs ni metadata.

## 6. Ubicacion de backups

Los archivos se guardan en:

```text
storage/app/backups/database
```

La ruta almacenada en base de datos es relativa a `storage/app`, por ejemplo:

```text
backups/database/mati_boutique_backup_20260604_153000.sql
```

No se guardan archivos en `public/` y no existe enlace publico directo.

## 7. Rutas creadas

Las rutas estan dentro del grupo `auth + superadmin` y se declararon antes del wildcard de recursos:

- `GET /superadmin/backups`
- `POST /superadmin/backups/generar`
- `GET /superadmin/backups/{backup}/descargar`

Controlador:

- `app/Http/Controllers/Superadmin/DatabaseBackupController.php`

## 8. Vista /superadmin/backups

Se crea `resources/js/Pages/Superadmin/Backups.tsx`.

La vista muestra:

- Encabezado `Copias de seguridad`.
- Cards de resumen:
  - Total de backups.
  - Último backup exitoso.
  - Backups fallidos.
  - Tamaño total aproximado.
- Boton `Generar backup` con estado de carga y bloqueo contra doble clic.
- Historial con fecha, archivo, usuario, tamano, estado, mensaje y descarga.
- Version responsive para escritorio y movil.

Tambien se agrega la opcion `Copias de seguridad` en el sidebar de superadmin, dentro de la seccion `Analisis`.

## 9. Seguridad aplicada

- Solo superadmin puede acceder a la pantalla.
- Solo superadmin puede generar backups.
- Solo superadmin puede descargar backups.
- La descarga pasa por controlador protegido.
- Se valida que el backup tenga estado `exitoso`.
- Se valida que el archivo exista antes de descargar.
- Se verifica que la ruta real permanezca dentro de `storage/app/backups/database`.
- No se registra `DB_PASSWORD` en logs.
- No se exponen rutas publicas ni archivos en `public/`.

## 10. Logs generados

Se registran eventos en `system_logs` con modulo `backups`:

- `backup_generado`: nivel `info`.
- `backup_fallido`: nivel `critical`.
- `backup_descargado`: nivel `info`.

La metadata evita contrasenas y solo guarda datos operativos como ID, nombre de archivo, tamano y estado.

## 11. Limitaciones actuales

- Los backups son manuales.
- No hay programacion automatica todavia.
- No hay subida a nube todavia.
- No se implementa eliminacion de backups en esta fase.
- No se implementan triggers PostgreSQL.

## 12. Pendientes

- Programar backups automaticos.
- Agregar rotacion de backups antiguos.
- Definir politica de retencion.
- Evaluar almacenamiento externo o nube.
- Evaluar triggers PostgreSQL opcionales si se aprueba una fase futura.
