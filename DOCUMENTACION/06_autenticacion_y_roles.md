# 06. Autenticación y roles

## Base de autenticación

La autenticación parte de Laravel Breeze, con vistas React personalizadas. El sistema incluye:

- Registro.
- Login.
- Logout.
- Recuperación de contraseña.
- Confirmación de contraseña.
- Verificación de email.
- Edición de perfil Breeze.
- Perfil específico superadmin.

## Roles del sistema

Los roles del sistema son:

- `superadmin`
- `vendedor`
- `cliente`

Estos nombres se tratan como roles protegidos en el modelo `Role`.

## Registro público

El registro público crea usuarios con rol `cliente`. Antes de crear el usuario, el sistema:

- Normaliza nombres y apellidos a mayúsculas.
- Limpia el teléfono para conservar dígitos.
- Normaliza el email a minúsculas.
- Valida la contraseña.
- Busca el rol `cliente`.

Si el rol cliente no existe, el registro falla con un mensaje de configuración.

## Login

El login usa `LoginRequest` y luego aplica redirecciones según rol:

- Si el usuario es superadmin activo con rol activo, redirige a `/superadmin`.
- Si el destino intentado era superadmin y el usuario no tiene permiso, lo envía a `/` con mensaje de error.
- En casos normales, redirige al destino previsto o a `/`.

## Middleware superadmin

El middleware `EnsureSuperadmin` protege el panel. Valida:

1. Que exista usuario autenticado.
2. Que el usuario esté activo.
3. Que tenga un rol activo y válido.
4. Que el rol sea superadmin.

Si el usuario está inactivo o no tiene rol válido, se cierra la sesión, se invalida la sesión y se regenera el token. Después se redirige a login.

Si el usuario no es superadmin, se redirige al dashboard general con mensaje de error.

## Estado activo/inactivo

Los usuarios tienen campo `est_usu`. Un usuario inactivo no debe conservar acceso a superadmin. El middleware fuerza salida al detectar esa condición.

Los roles tienen campo `est_rol`. Un usuario con rol inactivo tampoco es válido para superadmin.

## Reglas críticas del superadmin

El sistema protege:

- Que el superadmin no se desactive a sí mismo.
- Que el superadmin no cambie su propio rol a un rol sin acceso.
- Que no se desactive el último superadmin activo.
- Que no se cambie el rol del último superadmin activo.
- Que los roles del sistema no sean renombrados.
- Que los roles del sistema no sean desactivados.
- Que los roles del sistema no sean eliminados.

## Eliminación de usuarios

Los usuarios no se eliminan desde el panel. La regla definida es dar de baja cambiando su estado a inactivo.

## Vendedor y cliente

El rol vendedor existe como parte del modelo de seguridad y puede crearse desde el panel. En el estado actual, no hay un panel vendedor independiente documentado en rutas. El rol cliente se utiliza principalmente para usuarios registrados del storefront.

