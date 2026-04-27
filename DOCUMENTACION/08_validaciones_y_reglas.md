# 08. Validaciones y reglas de negocio

## Normalización general

El sistema normaliza varios campos antes de guardar:

- Nombres de usuarios a mayúsculas.
- Apellidos a mayúsculas.
- Roles a mayúsculas, salvo roles del sistema ya existentes.
- Categorías a mayúsculas.
- Marcas a mayúsculas.
- Colores a mayúsculas.
- Familias de color a mayúsculas.
- Tallas a mayúsculas.
- Temporadas a mayúsculas.
- Códigos de temporada a mayúsculas.
- Nombre de prenda a mayúsculas.
- SKU y códigos adicionales a mayúsculas.
- Email a minúsculas.
- Teléfono solo con dígitos.

## Usuarios

Reglas principales:

- Nombre requerido, máximo 100 caracteres, letras y espacios, en mayúsculas.
- Apellido paterno requerido, máximo 100 caracteres, letras y espacios, en mayúsculas.
- Apellido materno opcional en administración, requerido en registro público según formulario actual, con letras y espacios.
- Teléfono requerido en validación compartida, 8 dígitos, debe empezar con 6 o 7.
- Email requerido, minúsculas, formato válido, máximo 255 y único.
- Rol requerido en administración.
- Estado booleano.
- Contraseña requerida al crear usuario y opcional al editar.
- Contraseña mínima de 8 caracteres, con letras, mayúsculas/minúsculas y números.
- Confirmación de contraseña obligatoria cuando se envía contraseña.

## Roles

Reglas principales:

- Nombre requerido, máximo 50 caracteres.
- Solo letras y espacios.
- Nombre único.
- Descripción opcional, máximo 255.
- Estado booleano.
- Los roles del sistema no pueden duplicarse.
- Los roles del sistema no pueden cambiar de nombre.
- Los roles del sistema deben permanecer activos.
- Los roles del sistema no pueden eliminarse.
- Un rol con usuarios asociados no puede eliminarse.

## Protección del último superadmin

Cuando se edita un usuario superadmin activo, el sistema cuenta los superadmins activos. Si solo queda uno:

- No permite desactivarlo.
- No permite cambiar su rol a uno distinto de superadmin.

Además, el usuario autenticado no puede desactivarse ni quitarse su propio rol superadmin.

## Categorías

Reglas:

- Nombre requerido, máximo 100 caracteres.
- Solo letras y espacios.
- Nombre único normalizado.
- Descripción opcional, máximo 255.
- Categoría padre opcional existente.
- Perfil de talla opcional: `ALFA`, `NUMERICA` o `UNITALLA`.
- Estado booleano.
- La categoría padre no puede ser la misma categoría.
- No se puede eliminar una categoría con subcategorías.
- No se puede eliminar una categoría con prendas asociadas.

## Marcas

Reglas:

- Nombre requerido, máximo 100 caracteres.
- Permite letras, números, espacios y signos comerciales simples.
- Nombre único normalizado.
- Descripción opcional, máximo 255.
- Estado booleano.
- No se puede eliminar una marca con prendas asociadas.

## Colores

Reglas:

- Nombre requerido, máximo 50 caracteres.
- Solo letras y espacios.
- Nombre único normalizado.
- Familia opcional, letras y espacios.
- HEX específico requerido con formato `#RRGGBB`.
- HEX dominante opcional con formato `#RRGGBB`.
- Orden entero mínimo 0.
- Estado booleano.
- No se puede eliminar un color con variantes asociadas.

## Tallas

Reglas:

- Nombre requerido, máximo 20 caracteres.
- Solo letras y números, sin espacios.
- Nombre único.
- Orden requerido, entero mínimo 0.
- Sistema opcional: `UNICA`, `ALFA` o `NUMERICA`.
- Grupo opcional: `GENERAL`, `SUPERIOR` o `INFERIOR`.
- Estado booleano.
- No se puede eliminar una talla con variantes asociadas.

## Temporadas

Reglas:

- Nombre requerido, máximo 50 caracteres.
- Solo letras y espacios.
- Nombre único normalizado.
- Código opcional, máximo 10 caracteres, letras, números y guiones.
- Descripción opcional.
- Orden entero mínimo 0.
- Estado booleano.
- No se puede eliminar una temporada con prendas asociadas.

## Prendas

Reglas:

- Nombre requerido, máximo 150 caracteres.
- Permite letras, números, espacios, guiones, puntos y guion bajo.
- No puede duplicarse una prenda con mismo nombre, categoría y marca.
- Categoría requerida y existente.
- Marca requerida y existente.
- Temporada requerida y existente.
- Género requerido: `mujer`, `hombre` o `unisex`.
- Precio base requerido, numérico y mayor a 0.
- Estado booleano.
- Imagen principal requerida al crear.
- Imagen principal y galería deben ser imágenes válidas de hasta 6 MB.
- Debe existir al menos una variante operativa.
- Si la prenda es única, debe quedar una sola variante activa.
- Si usa variantes, debe registrar al menos dos configuraciones.
- No se puede repetir combinación de color y talla.
- No puede quedar sin imagen principal o imagen identificable.
- Las variantes enviadas en edición deben pertenecer a la prenda.
- Las imágenes existentes enviadas en edición deben pertenecer a la prenda.

## Variantes

Reglas:

- Prenda requerida y existente.
- Color requerido y existente.
- Talla requerida y existente.
- SKU requerido, único, máximo 50, letras, números, guiones y guion bajo.
- Código adicional opcional, único, máximo 100, letras, números y guiones.
- Precio de venta requerido, mayor a 0.
- Costo interno opcional, mínimo 0.
- El costo interno no puede ser mayor al precio de venta.
- Estado booleano.
- No puede duplicarse combinación prenda + color + talla.
- No se puede quitar una variante con ventas o reservas asociadas.
- No se puede eliminar una variante con inventario, imágenes, ventas o reservas asociadas.

## Imágenes

Reglas:

- Prenda requerida y existente.
- Variante opcional, pero si se informa debe pertenecer a la prenda.
- Archivo requerido al crear.
- Archivo opcional al editar.
- Debe ser imagen válida hasta 6 MB.
- Orden requerido, entero mínimo 0.
- Fuente opcional.
- Si una imagen se marca como principal, el sistema desmarca las demás principales de la misma prenda.
- Si una prenda queda sin principal, se asigna como principal la primera imagen disponible por orden.

## Inventario

Reglas:

- Una línea de inventario pertenece a una variante.
- Una variante solo puede tener una línea de inventario.
- Disponible inicial requerido, entero mínimo 0.
- Reservado requerido, entero mínimo 0.
- Reservado no puede ser mayor que disponible.
- Ubicación requerida: `DEPOSITO` o `TIENDA`.
- No se puede eliminar una línea de inventario mientras tenga cantidades registradas.

## Bolsa

Reglas:

- Variante requerida y existente.
- Cantidad requerida entre 1 y 10.
- No se puede agregar una variante sin stock disponible.
- La cantidad final se limita al stock disponible.
- El carrito se guarda en sesión.

## Favoritos

Reglas:

- Requiere autenticación.
- Prenda requerida y existente.
- La combinación usuario-prenda es única en base de datos.
- La acción `toggle` crea el favorito si no existe y lo elimina si ya existe.

