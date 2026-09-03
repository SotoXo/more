# Auditoría integral de formularios y validaciones — Mati Boutique

**Fecha de corte:** 2026-09-02  
**Alcance:** aplicación Laravel 13 + React/TypeScript/Inertia, rutas web, formularios visibles, modales, acciones sin campos, filtros, exportaciones, validación del servidor, servicios de dominio, modelos y migraciones.  
**Modalidad:** revisión estática y no destructiva. No se ejecutaron escrituras contra la base de datos, migraciones, seeders, servidor de desarrollo ni instalaciones.

## 1. Resumen ejecutivo

Se identificaron **125 formularios o flujos de envío** y **604 ocurrencias de campos lógicos**. El conteo incluye formularios convencionales, formularios en modal, filas repetibles, filtros GET, carga de archivos, acciones de botón que envían una petición aun cuando no tengan campos y acciones de exportar/imprimir que reutilizan filtros.

La auditoría produce **46 hallazgos**:

| Prioridad | Cantidad | Lectura ejecutiva |
|---|---:|---|
| P0 | **2** | Riesgo inmediato de corrupción o pérdida silenciosa de inventario reservado/disponible. |
| P1 | **17** | Fallos de autorización, integridad, operación o privacidad con impacto alto. |
| P2 | **18** | Inconsistencias relevantes, controles incompletos o fallos recuperables. |
| P3 | **9** | Claridad, accesibilidad, mensajes y robustez menor. |
| **Total** | **46** | |

### Diez hallazgos más importantes

1. **P0 — Editar una prenda pone `cre_inv` en cero:** cada variante conservada recibe `cre_inv = 0`, aunque existan reservas o compras online activas.
2. **P0 — Actualizaciones perdidas de stock:** los editores administrativos de prenda/inventario no bloquean la fila; pueden sobrescribir cambios concurrentes hechos correctamente por ventas o reservas.
3. **P1 — Perfil Breeze inutilizable:** React envía `name` y `email`; Laravel exige `nombre`, `apellido_paterno`, `telefono`, etc., y esos nombres tampoco son fillable del modelo.
4. **P1 — Dos migraciones crean las mismas columnas de temporada:** una instalación limpia puede fallar al crear `fec_ini_tem` y `fec_fin_tem` por segunda vez.
5. **P1 — Alta/reutilización de colores tiene dos fallos:** falta `unique` en Laravel y el alta normal puede devolver `JsonResponse` desde un método tipado `RedirectResponse`.
6. **P1 — Reservas públicas aceptan variantes/prendas inactivas:** el carrito y el servicio final comprueban existencia y stock, pero no actividad.
7. **P1 — Checkout online no exige cliente activo:** la ruta solo exige sesión; una cuenta inactiva o un usuario interno autenticado puede iniciar una compra.
8. **P1 — Cancelar una reserva pagada no revierte sus pagos:** se libera inventario, pero los pagos confirmados permanecen confirmados y no hay flujo obligatorio de devolución/anulación.
9. **P1 — Una reserva puede marcarse vencida antes de la fecha límite:** la acción de vencimiento no compara la fecha actual con `fec_limite_recojo_res`.
10. **P1 — Comprobantes financieros quedan en almacenamiento público:** las rutas/URLs persistidas pueden exponer datos personales y bancarios sin autorización por descarga.

## 2. Método de conteo y límites de la revisión

### Qué se contó como formulario

- Un flujo se cuenta por intención y endpoint: crear, editar, eliminar, cambiar estado, confirmar, cancelar, entregar, aprobar, rechazar, filtrar, exportar o imprimir.
- Un botón con `POST`, `PATCH` o `DELETE` cuenta aunque no envíe campos, porque sigue siendo una superficie de validación/autorización.
- Crear y editar se cuentan por separado cuando pueden divergir (`required` en creación, `nullable` en edición, reglas `unique()->ignore(...)`, archivos opcionales, etc.).
- Las filas repetibles de imágenes, variantes e ítems se cuentan como un formulario con un esquema de fila, no como un número ilimitado de formularios.
- Cada exportación/impresión se cuenta porque recibe parámetros manipulables directamente en la URL.

### Qué se contó como campo

- Una ocurrencia es una clave lógica aceptada, visible o derivada dentro de un formulario. Los hijos de una fila repetible cuentan una vez por esquema.
- Los contratos incompatibles se cuentan por unión. Ejemplo: el perfil Breeze presenta `name,email`, pero el Request espera `nombre,apellido_paterno,apellido_materno,telefono,email`; esa superficie tiene seis claves lógicas distintas.
- No se cuentan IDs incluidos solo en la ruta, tokens CSRF, paginación automática ni el discriminador fijo de una acción sin entrada del usuario.
- En el editor agregado de prendas se contaron 30 controles de negocio; los nombres de contenedor (`variant_rows`, `gallery_images`, `existing_images`) no se duplican como controles.

### Evidencia y limitaciones

- Se revisaron `routes/web.php`, `routes/auth.php`, componentes TSX, controladores, Form Requests/validadores, servicios, modelos y migraciones.
- Se intentó comprobar disponibilidad del entorno con `docker compose ps`, pero el comando no respondió y fue interrumpido. **No se consultó la base de datos**. La columna “BD” de este informe se basa en migraciones y modelos.
- No se hizo prueba de navegador ni envío real de formularios, porque eso habría podido escribir datos.
- No se ejecutaron migraciones, tests con persistencia, `npm run dev`, `composer install`, `npm install` ni comandos de limpieza.

## 3. Inventario total

| Área | Formularios/acciones | Campos lógicos | Incluye |
|---|---:|---:|---|
| Autenticación y cuenta | 13 | 35 | Login, registro, recuperación, perfil, contraseñas, baja, verificación, newsletter. |
| Tienda y cliente | 11 | 24 | Catálogo, favoritos, carrito, reserva, checkout, notificaciones. |
| Superadmin: CRUD, inline y filtros | 42 | 262 | 10 recursos, inventario, eliminaciones, altas rápidas y listados. |
| Superadmin: operación | 39 | 198 | Reportes, logs, auditoría, movimientos, dañados, ventas, reservas, backup. |
| Vendedor | 20 | 85 | Listados, reportes, venta, reserva, pagos, recojo y dañados. |
| **Total** | **125** | **604** | |

### 3.1 Autenticación y cuenta — 13 / 35

| ID | Formulario o acción | Campos contados | Acceso | Validación dominante |
|---|---|---|---|---|
| A01 | Iniciar sesión | `email`, `password`, `remember` (3) | Invitado | `email` y `password` requeridos; normalización del correo; rate limit 5 intentos; cuenta y rol activos. |
| A02 | Registro de cliente | nombre, dos apellidos, teléfono, correo, contraseña y confirmación (7) | Invitado | Nombres en mayúscula, teléfono boliviano, correo único, contraseña fuerte. |
| A03 | Solicitar recuperación | `email` (1) | Invitado | Requerido y formato email. |
| A04 | Restablecer contraseña | token, email, contraseña y confirmación (4) | Invitado | Token/email requeridos; `Password::defaults()` + confirmación. |
| A05 | Confirmar contraseña | `password` (1) | Autenticado | Comparación con hash; mensaje genérico en error. |
| A06 | Reenviar verificación | sin campos (0) | Autenticado | Estado `hasVerifiedEmail()`. |
| A07 | Cerrar sesión | sin campos (0) | Autenticado | CSRF/sesión. |
| A08 | Editar perfil Breeze | unión `name`, `nombre`, apellidos, teléfono, email (6) | Autenticado | Contrato FE/BE roto; ver P1-01. |
| A09 | Cambiar contraseña Breeze | actual, nueva y confirmación (3) | Autenticado | `current_password`, defaults y `confirmed`. |
| A10 | Eliminar cuenta | `password` (1) | Autenticado | `current_password`; posible fallo por relaciones. |
| A11 | Editar perfil superadmin | nombres/apellidos, teléfono, email (5) | Superadmin | Reglas explícitas y correo único. |
| A12 | Cambiar contraseña superadmin | actual, nueva y confirmación (3) | Superadmin | Mixta, mayúsculas/minúsculas, números y confirmación. |
| A13 | Newsletter del pie | `email` (1) | Público | Solo `preventDefault`; no existe endpoint ni persistencia. |

### 3.2 Tienda y cliente — 11 / 24

| ID | Formulario o acción | Campos | Control real |
|---|---|---:|---|
| C01 | Filtros del catálogo | búsqueda/categoría/marca/género/talla/color/temporada/precio mínimo/precio máximo (9) | GET; filtrado servidor, sin Request formal. |
| C02 | Alternar favorito | prenda (1) | Auth + `exists`; no actividad. |
| C03 | Añadir al carrito de reserva | variante, cantidad (2) | `exists`, entero mínimo 1 y stock; no actividad. |
| C04 | Actualizar cantidad de carrito | cantidad (1) | Entero mínimo 1 y stock. |
| C05 | Quitar línea | 0 | ID de variante en ruta. |
| C06 | Vaciar carrito | 0 | Sesión. |
| C07 | Confirmar reserva | monto, método, comprobante, esquema de ítems (4) | Cliente activo, 50–100 %, QR/transferencia, archivo imagen 4 MB; precio/stock servidor. |
| C08 | Carrito local de compra | variante, cantidad (2) | LocalStorage; la seguridad final está en checkout. |
| C09 | Checkout online | ítems, monto, método, comprobante, observación (5) | Productos activos, pago exacto, QR/transferencia, imagen 4 MB y stock bloqueado. |
| C10 | Marcar notificación leída | 0 | Consulta acotada al usuario autenticado. |
| C11 | Marcar todas leídas | 0 | Actualización acotada al usuario autenticado. |

### 3.3 Superadmin: CRUD, altas rápidas y filtros — 42 / 262

| Grupo | Formularios | Campos | Detalle |
|---|---:|---:|---|
| Crear/editar 10 recursos | 20 | 168 | Roles (3×2), usuarios (9×2), categorías (6×2), marcas (3×2), colores (6×2), tallas (6×2), temporadas (7×2), prendas agregadas (30×2), variantes (8×2), imágenes (6×2). |
| Editar inventario | 1 | 4 | Variante, disponible, reservado, ubicación. No hay UI normal de creación. |
| Eliminar recursos | 9 | 0 | Rol, categoría, marca, color, talla, temporada, prenda, variante, imagen. |
| Alta rápida inline | 5 | 18 | Categoría (2), marca (2), color (3), talla (6), temporada (5). |
| Filtros de listados | 7 | 72 | Usuarios (5), prendas (12), variantes (14), inventario (10), alertas (10), ventas (11), reservas (10). |
| **Subtotal** | **42** | **262** | |

`inventario.ajustes` es una pantalla informativa, no un formulario de ajuste separado. Alertas y favoritos son listados/acciones de lectura; se contaron sus filtros o acciones reales, no tarjetas meramente visuales.

### 3.4 Superadmin: operación — 39 / 198

| Grupo | Formularios | Campos | Detalle |
|---|---:|---:|---|
| Filtro general de reportes | 1 | 9 | Rango, tipo, estado y demás dimensiones comerciales. |
| Exportar/imprimir reportes | 10 | 90 | Cinco familias × CSV/impresión; heredan nueve filtros manipulables. |
| Filtro de logs | 1 | 7 | Búsqueda, nivel, canal, evento y fechas. |
| Filtro de auditoría | 1 | 8 | Actor, acción, entidad, fechas y búsqueda. |
| Movimientos de inventario: filtro/exportar | 2 | 16 | Ocho parámetros reutilizados. |
| Filtro de dañados | 1 | 9 | Estado, tipo, severidad, relación y fechas. |
| Crear backup | 1 | 0 | Acción sin campos. |
| Reportar producto dañado | 1 | 12 | Datos, relaciones, cantidad, impacto e imágenes. |
| Seis transiciones de dañado | 6 | 12 | Revisar, confirmar, descartar, reparar, pérdida y resolver; decisión/observación. |
| Venta: crear + cinco acciones | 6 | 11 | Crear, confirmar, incompleta, cancelar, lista y entregar. |
| Reserva: crear + ocho acciones | 9 | 24 | Crear, pago, verificar, rechazar, cancelar, lista, completar, saldar y vencer. |
| **Subtotal** | **39** | **198** | |

### 3.5 Vendedor — 20 / 85

| Grupo | Formularios | Campos | Detalle |
|---|---:|---:|---|
| Ocho filtros operativos | 8 | 47 | Productos (8), stock (6), alertas (4), ventas (5), reservas (5), pagos (6), recojos (6), dañados (7). |
| Reporte, exportación e impresión | 3 | 6 | Rango desde/hasta reutilizado por las tres acciones. |
| Crear venta | 1 | 7 | Cliente, método, comprobante, observación, ítems/cantidad. |
| Crear reserva | 1 | 7 | Cliente, pago, comprobante, observación, ítems/cantidad. |
| Añadir pago | 1 | 4 | Monto, método, comprobante y observación. |
| Cancelar reserva | 1 | 1 | Motivo. |
| Aprobar/rechazar comprobante | 2 | 1 | Aprobar sin campos; rechazar con motivo. |
| Entregar venta/reserva | 2 | 0 | Acciones por ruta; verificaciones de estado/pago en servicio vendedor. |
| Reportar dañado | 1 | 12 | Misma estructura base; observación obligatoria y sin afectar stock. |
| **Subtotal** | **20** | **85** | |

## 4. Matriz campo por campo de los formularios maestros

Las reglas se transcriben en sintaxis Laravel para hacer visible el contrato exacto. “FE” resume el comportamiento de React y “BD” las restricciones inferidas de las migraciones.

### Roles, usuarios y cuenta

| Formulario / campo | Laravel | FE | BD / clasificación |
|---|---|---|---|
| Rol `nom_rol` | `required|string|max:50|regex letras/espacios|unique:roles` | Texto, normalización visual | `unique`; consistente, además hay duplicado normalizado. |
| Rol `des_rol` | `nullable|string|max:255` | Textarea | `varchar(255)`; consistente. |
| Rol `est_rol` | `nullable|boolean` | Switch | Boolean/cast; consistente. |
| Usuario admin `nom_usu`, `app_usu` | `required|string|max:100|regex` | Texto y normalización | `varchar(100)`; consistente. |
| Usuario admin `apm_usu` | `nullable|string|max:100|regex` | Opcional | Nullable; consistente. |
| Usuario admin `tel_usu` | `required|string|regex:/^[67]\d{7}$/` | Tipo teléfono | String; backend más fuerte. |
| Usuario admin `email` | `required|lowercase|email|max:255|unique` | Email | `unique`; consistente. |
| Usuario admin `cod_rol` | `required|integer|exists:roles` | Select limitado por UI | FK; backend acepta roles inactivos/no mostrados si se falsifica. |
| Usuario admin `est_usu` | `nullable|boolean` | Switch | Boolean; consistente. |
| Usuario admin contraseña/confirmación | Crear: requerida; editar: nullable; `confirmed|min:8|letters|mixedCase|numbers` | Dos passwords | Hash en servicio; política fuerte. |
| Registro nombres | `required|string|max:100`, mayúsculas/regex | Nombre `maxLength=50`; apellidos `maxLength=20` | FE **bloquea valores válidos** que BE/BD admiten. Materno requerido aunque BD es nullable. |
| Registro teléfono | `required|regex:/^[67]\d{7}$/` | Patrón/normalización | Consistente. |
| Registro contraseña | `confirmed|min:8|letters|mixedCase|numbers` | Validación equivalente parcial | Más fuerte que reset/Breeze. |
| Perfil Breeze | Request: `nombre`, `apellido_paterno`, `apellido_materno?`, `telefono`, `email` | Envía solo `name,email` | **Incompatible** con Request y `$fillable` real (`nom_usu`, `app_usu`, etc.). |

### Catálogo maestro

| Recurso / campo | Laravel | FE | BD / clasificación |
|---|---|---|---|
| Categoría `nom_cat` | `required|string|max:100|regex letras|unique` | Texto | `unique`; más control normalizado en servidor. |
| `des_cat` | `nullable|string|max:255` | Textarea | Compatible. |
| `cod_categoria_padre_cat` | `nullable|integer|exists` | Select | FK; evita padre directo igual, pero no ciclos A→B→A. |
| `tip_tal_cat` | `nullable|in:ALFA,NUMERICA,UNICA,UNITALLA` | Select | Enum solo aplicativo. |
| `gru_tal_cat` | `nullable|in:SUPERIOR,INFERIOR,GENERAL,ACCESORIOS,CALZADO` | Select | Enum solo aplicativo. |
| `est_cat` | `nullable|boolean` | Switch | Compatible. |
| Marca `nom_mar` | `required|string|max:100|regex letras/números/&.-|unique` | Texto | `unique`; consistente. |
| Marca `des_mar`, `est_mar` | `nullable|max:255`; `nullable|boolean` | Textarea/switch | Consistente. |
| Color `nom_col` | `required|string|max:50|regex letras` | Derivado/preparado | BD tiene `unique`, Laravel **no**; duplicado puede llegar como excepción SQL. |
| Color `fam_col` | `required|string|max:50|regex letras` | Selector/derivado | BD nullable; backend más fuerte. |
| Color `hex_col`, `hex_dom_col` | `required|regex /^#[0-9A-Fa-f]{6}$/` | Color/texto | BD nullable; backend más fuerte. |
| Color `ord_col`, `est_col` | `nullable|integer|min:0`; `nullable|boolean` | Número/switch | Sin CHECK de no negativo. |
| Talla `nom_tal` | `required|string|max:20|regex|unique` | Texto | `unique`; consistente. |
| Talla `seg_tal` | `required|in:ADULTO,INFANTIL,BEBE` | Select | Enum aplicativo. |
| Talla `ord_tal` | `nullable|integer|min:0` | Número | Sin CHECK. |
| Talla `sis_tal` | `required|in:ALFA,NUMERICA,UNICA,CALZADO_US,CALZADO_EU,CALZADO_UK,EDAD,ALTURA_CM,MESES` | Select | BD permite null; backend más fuerte. |
| Talla `gru_tal` | `required|in:SUPERIOR,INFERIOR,GENERAL,ACCESORIOS,CALZADO` | Select | BD permite null; backend más fuerte. |
| Talla `est_tal` | `nullable|boolean` | Switch | Consistente. |
| Temporada `nom_tem` | `required|string|max:50|regex|unique` | Texto | `unique`; consistente. |
| `cod_tem` | `required|string|max:10|regex alfanumérico-guion|unique` | Texto | Primera migración admite 20 y nullable: backend más estricto. |
| `des_tem` | `nullable|string|max:255` | Textarea | Compatible. |
| `fec_ini_tem` | `required|date` | Date | BD nullable; backend más fuerte. |
| `fec_fin_tem` | `required|date|after_or_equal:fec_ini_tem` | Date | Relación correcta en BE, sin CHECK en BD. |
| `ord_tem`, `est_tem` | entero mínimo 0; boolean nullable | Número/switch | Sin CHECK para orden. |

### Prenda agregada, variantes, imágenes e inventario

| Campo | Regla Laravel exacta/resumida | Comparación y riesgo |
|---|---|---|
| `nom_pre` | `required|string|max:150|regex letras/números/espacio/-_.` | Duplicado normalizado por nombre+categoría+marca en callback. |
| `des_pre` | `nullable|string` | Sin máximo en FE/BE; BD `text`. |
| `cod_categorias_pre` | `required|integer|exists:categorias` | No exige categoría activa. |
| `cod_marcas_pre` | `required|integer|exists:marcas` | No exige marca activa. |
| `cod_temporadas_pre` | `required|integer|exists:temporadas` | No exige temporada activa. |
| `gen_pre` | `required|in:mujer,hombre,unisex` | Consistente con opciones. |
| `prb_pre` | `required|numeric|min:0.01` | Sin `max`; BD decimal(10,2), posible overflow/500. |
| `est_pre`, `usa_variantes` | `nullable|boolean` | El nombre `usa_variantes` y algunos textos de talla no son semánticamente uniformes. |
| `imagen_principal` | crear `required`; editar `nullable`; `file|image|max:6144` | Máximo 6 MB, sin dimensiones/mimes explícitos/hash. |
| `fue_igp` | `nullable|string|max:50` | Metadato libre. |
| `existing_images.*.id` | `required|integer|exists` | Callback verifica que la imagen pertenezca a la prenda: defensa IDOR correcta. |
| `existing_images.*.ord_igp` | `required|integer|min:0` | Correcto; sin límite superior. |
| `existing_images.*.pri_igp/remove` | `nullable|boolean` | Callback exige al menos una imagen y una primaria. |
| `existing_images.*.fue_igp` | `nullable|string|max:50` | Consistente. |
| `gallery_images.*.archivo` | `nullable|file|image|max:6144` | `ord_igp` usa `required_with`; primaria/fuente opcionales. |
| `variant_rows` | `required|array|min:1` | Callback exige una fila activa y, si usa variantes, al menos dos. |
| `variant_rows.*.id` | `nullable|integer|exists` | Callback verifica pertenencia a la prenda: correcto. |
| `variant_rows.*.cod_colores_prv` | `required|integer|exists:colores` | No exige color activo. |
| `variant_rows.*.cod_tallas_prv` | `required|integer|exists:tallas` | No exige talla activa. |
| `variant_rows.*.cob_prv` | `nullable|string|max:100|regex alfanumérico-guion|distinct` | Callback comprueba unicidad en BD. |
| `variant_rows.*.pve_prv` | `required|numeric|min:0.01` | Sin máximo de decimal. |
| `variant_rows.*.cos_prv` | `nullable|numeric|min:0` | Callback exige costo ≤ precio. |
| `variant_rows.*.stock_inicial` | `required|integer|min:1` | En edición representa stock actual; impide guardar una variante agotada. |
| `variant_rows.*.ubi_inv` | `required|in:DEPOSITO,TIENDA` | Inventario genérico admite string hasta 80: contratos distintos. |
| `variant_rows.*.est_prv/remove` | `nullable|boolean` | Eliminar se bloquea si hay detalles/movimientos relacionados. |
| Variante separada `cod_prendas_prv/cod_colores_prv/cod_tallas_prv` | `required|integer|exists` | Callback evita combinación duplicada; no exige maestros activos. |
| Variante `sku_prv` | `required|string|max:50|regex|unique` | Índice único en BD. En alta agregada el SKU se genera. |
| Variante `cob_prv` | nullable, formato, máximo 100, unique | Índice único en BD. |
| Variante `pve_prv/cos_prv` | precio ≥ 0.01, costo ≥ 0 y costo ≤ precio | Falta máximo compatible con decimal(10,2). |
| Imagen separada relaciones | prenda requerida/exists; variante nullable/exists | Callback exige que variante pertenezca a la prenda: correcto. |
| Imagen `archivo_igp` | requerida al crear, opcional al editar; `file|image|max:6144` | Sin dimensiones/escaneo; borrado de archivo anterior después de commit. |
| Imagen primaria/orden/fuente | boolean; entero ≥0; texto ≤50 | Servicio preserva al menos una primaria en agregado. |
| Inventario `cod_prenda_variantes_inv` | `required|exists|unique` | Una fila por variante, respaldado por unique. |
| Inventario `cdi_inv` | `required|integer|min:0` | Modificable directamente por superadmin; no se exige motivo. |
| Inventario `cre_inv` | `required|integer|min:0` | UI lo deshabilita y callback impide cambiarlo en editor genérico. El agregado de prenda viola esa regla internamente. |
| Inventario `ubi_inv` | `nullable|string|max:80` | BD permite 100; backend más estricto. |

## 5. Formularios comerciales, estados y permisos

### 5.1 Venta superadmin

- Entrada: cliente nullable/`exists`, tipo `fisica|online|otro`, método requerido/máximo 30, descuento numérico ≥0, observación e ítems con variante `exists` y cantidad entera ≥1.
- El servidor recalcula precios y subtotal; el descuento se limita al subtotal. Una manipulación del precio en DevTools no cambia el precio persistido.
- El backend no exige que el cliente sea activo/rol cliente, que variante/prenda estén activas ni que el método pertenezca al catálogo salvo la condición QR/transferencia para online. El flujo finalmente rechaza crear una venta online desde esta pantalla.
- Estados: la UI ofrece confirmar/marcar incompleta/cancelar; el servicio solo acepta algunas acciones desde `en_revision`, aunque la UI también las muestra en `incompleta`.
- Logística superadmin exige venta `registrada` y `lista_recojo`, pero no recalcula pago confirmado al 100 % antes de entregar.

### 5.2 Reserva superadmin

- Cliente: requerido, existente, activo y con rol cliente mediante `isValidCustomer`.
- Ítems: variantes existentes, cantidades enteras ≥1; precio y totales se recalculan en servidor.
- Pago: monto >0, QR/transferencia, comprobante imagen ≤4 MB. Descuento ≥0 y ≤subtotal.
- Un descuento igual al subtotal produce total cero, pero aún permite un monto declarado positivo porque las comparaciones contra total se omiten cuando total no es positivo.
- Acciones: pago adicional, verificar, rechazar, cancelar, lista para recojo, completar, saldar y vencer. Hay bloqueos de estado y de saldo en varios servicios, pero faltan controles temporales en vencer/completar.

### 5.3 Tienda cliente

- La reserva toma el usuario y los ítems desde sesión; no confía en IDs de cliente ni precios enviados. Exige usuario activo con rol cliente y reserva 50–100 %.
- El carrito de reserva comprueba existencia y stock, pero no `est_pre`/`est_prv`; el servicio final tampoco lo corrige.
- El checkout online vuelve a cargar variantes activas, prendas activas, precios y stock dentro de transacciones con `lockForUpdate`. Exige pago exacto.
- La ruta de checkout solo usa middleware `auth`; el servicio no vuelve a exigir cuenta activa ni rol cliente.
- Comprobantes de cliente: QR/transferencia, imagen ≤4 MB. React valida más en checkout que en reserva; el backend es la autoridad final.

### 5.4 Vendedor

- Venta: cliente nullable y solo `exists`; método `efectivo|qr|transferencia`, pago exacto, archivo imagen/PDF ≤4 MB si no es efectivo, ítems activos y stock bloqueado. Falta validar rol/actividad del cliente.
- Reserva: cliente activo/rol cliente, pago 50–100 %, archivo condicional, productos/variantes activos y stock bloqueado. Es el contrato comercial más completo.
- Pago adicional: monto positivo y no mayor al saldo, método permitido y archivo condicional.
- Cancelación: requiere motivo mínimo 5 y, para vendedor normal, la reserva debe ser propia. Libera stock incluso si había pagos confirmados y deja esos pagos sin una transición de devolución.
- Revisión de comprobantes: la bandeja incluye todos los comprobantes subidos por clientes (no se filtra por vendedor/responsable). Cualquier usuario con middleware vendedor puede aprobar/rechazar cualquier candidato.
- Recojos: la bandeja también es global. El servicio vendedor sí exige pago confirmado al 100 %, estado/logística correctos, fecha no vencida en reserva y stock comprometido suficiente.
- Esta globalidad puede ser una caja/recojo central intencional; si cada vendedor debe limitarse a sus operaciones, constituye autorización horizontal insuficiente.

## 6. Inventario y concurrencia

### Semántica observada

- `cdi_inv`: cantidad disponible para nuevas ventas/reservas.
- `cre_inv`: cantidad ya comprometida por operaciones pendientes de completar/cancelar.
- Venta física directa: decrementa `cdi_inv`; no incrementa `cre_inv`.
- Venta online pendiente: decrementa `cdi_inv` e incrementa `cre_inv`; al confirmar consume `cre_inv`; al cancelar/incompletar restaura disponible y reduce comprometido.
- Reserva: decrementa `cdi_inv` e incrementa `cre_inv`; cancelar/rechazar/vencer revierte ambos; completar reduce `cre_inv` y convierte a venta sin volver a descontar disponible.

### Controles correctos

- Los servicios centrales de venta, checkout y reserva usan transacciones y bloqueos de fila para verificar stock en el momento de escribir.
- Los precios y totales se reconstruyen desde la base de datos.
- Se agrupan IDs de variante repetidos antes de comprobar stock, evitando el bypass por líneas duplicadas.
- Cancelaciones comerciales restauran cantidad con movimientos auditables; completar una reserva no duplica el consumo.

### Rotura crítica en mantenimiento de producto

`app/Services/Superadmin/Resources/ProductCatalogService.php:134-160` construye siempre:

```php
$inventoryPayload = [
    'cdi_inv' => (int) $row['stock_inicial'],
    'cre_inv' => 0,
    // ...
];

$variant->inventario->update($inventoryPayload);
```

Esto se ejecuta también al editar una prenda existente. Por tanto, guardar un cambio de nombre, imagen o precio puede borrar compromisos reales y convertir el stock visible en un valor manual del formulario. Además, la transacción no adquiere `lockForUpdate` sobre las filas de inventario; una venta/reserva concurrente puede completar primero y luego ser sobrescrita por la edición administrativa.

## 7. Archivos, fechas, números y duplicados

### Archivos

| Flujo | Tipos/tamaño | Persistencia | Observación |
|---|---|---|---|
| Imágenes de prenda | `file|image|max:6144` | Disco público | Sin dimensiones, mimes explícitos, hash duplicado ni análisis de contenido. Alta agregada limpia archivos creados si falla la transacción y borra antiguos después del éxito. |
| Daños | JPG/PNG/WebP, máximo 4 MB, múltiples | Disco público | Valida extensión/MIME y limpia archivos si falla la creación. Evidencia visible mediante URL. |
| Comprobante cliente/admin | Imagen máximo 4 MB | Disco público + URL/ruta | Dato financiero potencialmente sensible; falta descarga autorizada. |
| Comprobante vendedor | Imagen o PDF máximo 4 MB | Disco público + URL/ruta | Política distinta sin justificación visible. Algunos fallos posteriores a `store` pueden dejar huérfanos. |

No se observó validación explícita de dimensiones mínimas/máximas, imágenes de cero dimensiones, duplicado por hash, malware en PDF ni cuota total por operación. `image` de Laravel reduce parte del riesgo de archivos corruptos, pero no sustituye esas políticas.

### Fechas

- Temporada valida fecha final ≥ inicial: correcto.
- Varias consultas de reportes superadmin y listados vendedor usan `Carbon::parse` directamente; una fecha manipulada puede provocar 500.
- Otros módulos envuelven el parseo y silencian el error, aplicando filtros parciales o ignorándolos; eso evita 500 pero confunde al usuario.
- Ningún filtro revisado comunica sistemáticamente que `date_from <= date_to`; rangos invertidos suelen producir vacío.
- Vencer una reserva no exige `now >= fec_limite_recojo_res`; completar desde superadmin tampoco bloquea expresamente una reserva cuya fecha ya pasó. El flujo vendedor de recojo sí la bloquea.

### Números y dinero

- Cantidades se validan como enteros positivos en ventas/reservas y no negativos en inventario.
- Precios/costos/montos usan `numeric|min`, pero faltan máximos compatibles con `decimal(10,2)`, y no siempre se fuerza una escala de dos decimales antes de persistir.
- Se comprueba costo ≤ precio de variante.
- Se comprueba descuento ≤ subtotal, pero no se prohíbe total cero cuando el flujo exige pago.
- Los generadores basados en conteo/siguiente correlativo no están protegidos por bloqueo/sequence; dos altas simultáneas pueden chocar con el índice único.

### Duplicados

- Roles, categorías, marcas, tallas y temporadas combinan `unique` con comprobación normalizada en el servicio.
- Prenda comprueba duplicado normalizado por nombre+categoría+marca.
- Variante comprueba combinación prenda+color+talla, SKU, código de barras y duplicados dentro de la misma carga.
- Color es la excepción: la BD hace único `nom_col`, pero la regla Laravel no; el alta normal intenta reutilizar por hex/familia y el alta inline no comparte esa ruta.

## 8. Clasificación de consistencia frontend / backend / base de datos

| Clasificación | Casos principales | Dictamen |
|---|---|---|
| Consistente en las tres capas | Correo único, SKU/código de barras, teléfono, fechas de temporada, combo de variante, cantidad entera, costo ≤ precio | Mantener; el backend sigue siendo la autoridad. |
| Backend más fuerte que frontend | CRUD genérico, reserva cliente, archivos, duplicados normalizados, pertenencia de imágenes/variantes | Aceptable para seguridad, pero la UX muestra el error tarde. |
| Frontend más restrictivo que backend | Longitudes de nombres/apellidos de registro; algunos catálogos de método/ubicación | Puede rechazar datos que el servidor y la BD aceptarían. |
| Backend más restrictivo que BD | Temporadas requeridas, campos comerciales de color/talla, ubicación inventario 80 vs BD 100 | Válido si es regla de negocio; debe documentarse y probar importaciones/scripts. |
| Base de datos más fuerte que backend | `colores.nom_col` único; precisión decimal; varias FK | Riesgo de excepción 500 en lugar de 422 si el servicio no traduce el error. |
| Contrato roto | Perfil Breeze; respuesta de alta normal de color; migraciones duplicadas | Requiere corrección antes de ampliar funcionalidades. |
| Regla de negocio ausente | Actividad/rol de varias relaciones, fechas de vencimiento, pago antes de entrega superadmin, devolución al cancelar reserva | El dato “existe” pero no necesariamente es elegible. |
| Correcto pero confuso | `stock_inicial` usado como stock editable, globalidad de bandejas vendedor, acciones visibles en estados no aceptados | Renombrar/explicar y alinear UI con máquina de estados. |

## 9. Hallazgos priorizados

Cada fila incluye: formulario afectado, evidencia, diferencia entre comportamiento actual y esperado, reproducción sugerida, riesgo y recomendación. Las reproducciones deben ejecutarse solo en una base desechable o con fixtures controlados.

### P0 — Críticos (2)

| ID | Formulario y evidencia | Actual vs esperado / reproducción | Riesgo | Recomendación |
|---|---|---|---|---|
| **P0-01** | Editar prenda agregada. `app/Services/Superadmin/Resources/ProductCatalogService.php:134-160`; regla en `ResourceValidationService.php:93-103`. | **Actual:** todo guardado asigna a cada variante `cdi_inv = stock_inicial` y `cre_inv = 0`. **Esperado:** una edición de catálogo no altera compromisos; cualquier ajuste debe expresar delta/motivo. **Repro:** crear variante con `cdi=8, cre=2`, editar solo nombre y guardar; `cre` termina en 0. | Corrupción silenciosa: reservas/ventas online pierden respaldo contable y pueden liberar/vender unidades comprometidas. | Separar catálogo e inventario; al editar, no incluir `cre_inv`; tratar stock como ajuste con servicio central, motivo, delta y bloqueo. Añadir test de regresión con reserva activa. |
| **P0-02** | Editar prenda e inventario mientras se vende/reserva. `ProductCatalogService.php:73,134-160`; `ResourceControllerActions.php:302+`; servicios comerciales con `lockForUpdate`. | **Actual:** editores administrativos leen y escriben valores absolutos sin bloquear la fila de inventario. **Esperado:** toda mutación de stock participa en el mismo protocolo de lock/delta. **Repro:** A abre prenda con cdi 10; B vende 1 y confirma 9; A guarda su copia 10; vuelve a aparecer la unidad. | Actualización perdida, sobreventa o divergencia entre movimientos e inventario. Una transacción sin bloquear no evita el stale write. | Encapsular todas las mutaciones en un único servicio, `SELECT ... FOR UPDATE`, validar versión o usar actualización atómica; impedir escrituras absolutas desde formularios de catálogo. |

### P1 — Altos (17)

| ID | Formulario y archivos | Actual vs esperado / reproducción | Riesgo | Recomendación |
|---|---|---|---|---|
| **P1-01** | Perfil Breeze. `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.tsx:21-23`, `app/Http/Requests/ProfileUpdateRequest.php:20-28`, controlador/modelo User. | React envía `name,email`; Request exige nombres españoles y teléfono; aun enviándolos, `$fillable` usa `nom_usu/app_usu/...`. Repro: cambiar nombre y guardar: 422 por campos ausentes. | Perfil estándar no funciona y puede mostrar errores que no tienen control asociado. | Unificar claves con columnas reales o mapear explícitamente DTO→modelo; añadir feature test del formulario real. |
| **P1-02** | Migración de temporadas. `2026_04_14_222922_add_business_fields_to_temporadas_table.php:13-19` y `2026_05_11_014318_add_period_fields_to_temporadas_table.php:12-15`. | Ambas agregan fechas e índice sin guardas. Repro: `migrate:fresh` en BD vacía. Esperado: cada columna se crea una vez y cada `down` revierte solo lo propio. | Instalación/CI/rollback fallan; entornos divergen. | Consolidar historial con una migración correctiva compatible con instalaciones existentes y verificar fresh + rollback en CI. |
| **P1-03** | Crear color normal/inline. `ResourceValidationService.php:45-51`, `ResourceControllerActions.php:209,233-241,279-299`. | No hay `unique:colores,nom_col`; el alta normal puede retornar JSON desde método tipado `RedirectResponse`; inline no aplica reutilización. Repro: alta normal con hex reutilizable y alta inline con nombre derivado duplicado. | TypeError o excepción unique/500; experiencia distinta por入口. | Usar tipo unión/flujo de respuesta coherente, validación `unique()->ignore`, reutilización común y captura de conflicto concurrente. |
| **P1-04** | Carrito y confirmación de reserva cliente. Controlador de carrito + `app/Services/Superadmin/ReservationService.php`. | Solo se exige `exists`/stock; una variante o prenda inactiva todavía puede reservarse mediante petición forjada o carrito antiguo. | Venta de producto retirado, incumplimiento de reglas comerciales. | Requerir `est_prv` y `est_pre` activos al añadir y otra vez bajo lock al confirmar. |
| **P1-05** | Checkout online cliente. Rutas cliente y servicio de checkout. | La ruta exige `auth`, no cuenta activa/rol cliente; el servicio sí valida artículos activos, pero no elegibilidad del comprador. Repro: sesión de usuario inactivo o rol vendedor y POST válido. | Usuarios suspendidos o internos pueden comprar; política incoherente con reservas. | Middleware común `active + role:cliente` o policy en ambos flujos, con test de cada rol/estado. |
| **P1-06** | Crear venta superadmin. `app/Services/Superadmin/SaleService.php` y definición comercial. | Cliente/variante solo deben existir; método es texto ≤30, salvo condición online. Petición forjada acepta cliente inactivo/no cliente, artículo inactivo o método desconocido. | Datos comerciales inválidos y venta de inventario retirado. | Reglas `Rule::exists()->where(active/role)`, enum de métodos por tipo y revalidación dentro de la transacción. |
| **P1-07** | Crear venta vendedor. Servicio/controlador vendedor de ventas. | Artículos sí deben estar activos; cliente nullable solo usa `exists`, sin rol/actividad. | Recibos ligados a cuenta interna/inactiva; métricas y privacidad incorrectas. | Reutilizar `isValidCustomer` de reservas cuando se envía cliente. |
| **P1-08** | Revisar pagos y entregar desde vendedor. `VendedorPaymentReviewService.php:185-220`; `VendedorPickupService.php:323-364`. | Las consultas no se acotan por `venta.vendedor`/`reserva.responsable`; todos los vendedores pueden revisar/entregar todas las operaciones. Esperado depende de política. Repro: vendedor B abre URL de operación de A. | Autorización horizontal si no existe caja/recojo central; responsabilidad difusa incluso si es intencional. | Decidir política. Si es propio: policy + scope por actor. Si es global: permiso separado (`review_payments`, `deliver_orders`) y documentación/auditoría explícita. |
| **P1-09** | Venta/reserva con descuento. `SaleService.php`, `ReservationService.php`. | Se admite descuento igual al subtotal; reserva puede persistir total 0 con monto declarado positivo porque límites contra total quedan condicionados a total >0. | Pagos/saldos contradictorios, comprobantes sin deuda y reportes erróneos. | Definir si existe obsequio. Si no, exigir `discount < subtotal` y total ≥0.01; si sí, prohibir pago y usar flujo/razón especial. |
| **P1-10** | Cancelar reserva vendedor con pagos. `app/Services/Vendedor/VendedorReservationService.php`. | Se libera stock aunque haya pagos confirmados, pero no se anulan/reembolsan ni se bloquea la cancelación; solo se registra que existían. | Dinero del cliente queda confirmado en operación cancelada; conciliación manual insegura. | Máquina de estados de pago/devolución; bloquear cancelación hasta resolver o crear reembolso/nota de crédito transaccional. |
| **P1-11** | Marcar reserva vencida superadmin. `ReservationService`/acciones del ResourceController. | Se acepta una reserva activa sin exigir que haya llegado la fecha límite. Repro: reserva para mañana → “Vencer”. | Liberación anticipada de stock y pérdida de reserva válida. | Comprobar bajo lock `fec_limite_recojo_res <= now`; permiso excepcional separado y motivo obligatorio. |
| **P1-12** | Completar reserva superadmin. `ReservationService` y `ReservationToSaleService`. | Se comprueban estado/saldo, pero no se rechaza expresamente una fecha límite ya superada; vendedor recojo sí lo hace. | Operación vencida puede convertirse a venta desde otro panel; estados inconsistentes. | Centralizar elegibilidad de entrega/completado y usarla en ambos roles. |
| **P1-13** | Entregar venta superadmin. `app/Services/Superadmin/SalePickupService.php:57-83`. | Solo exige estado `registrada` y logística `lista_recojo`; no suma pagos confirmados. El servicio vendedor sí exige 100 %. | Entrega sin cobro completo mediante panel superadmin. | Compartir `paymentSummary/ensurePaidFull` en ambos flujos, dentro de la transacción. |
| **P1-14** | Transiciones de producto dañado. `app/Services/Superadmin/DamagedProductService.php` y definiciones de acciones. | `decision_pdr` admite cualquier decisión en varias acciones/estados. Puede resolverse con decisión “reparar” sin ejecutar la devolución de stock, o confirmar con decisión “descartar”. | Estado, decisión y movimiento físico dejan de representar lo mismo. | Matriz explícita acción×estado×decisión; decisión derivada de la acción o regla condicional; tests de transición y movimiento. |
| **P1-15** | Todos los comprobantes de pago. Servicios de checkout/reserva/venta y modelos de pago. | Se guardan en disco público con URL/ruta. Un enlace conocido puede saltarse la autorización de la pantalla. | Exposición de nombres, cuentas, importes u otra evidencia financiera. | Disco privado, endpoint de descarga firmado/autorizado, headers seguros, retención y borrado definidos. |
| **P1-16** | Editar categoría padre. `ResourceValidationService.php:32-38` y callback de jerarquía. | Se impide ser padre de sí misma, pero no A→B→C→A. Repro: B hija de A; editar A y elegir B. | Ciclos rompen breadcrumbs, árboles y recursión. | Validar que el nuevo padre no sea descendiente; opcional CTE y test de ciclo de dos y tres nodos. |
| **P1-17** | Editar prenda agotada. `ResourceValidationService.php:100`; `ProductCatalogService.php:572`. | `stock_inicial` es siempre `min:1`; una variante con cdi 0 no puede guardarse aunque se edite solo descripción/imagen. | Bloqueo operativo; invita a inventar stock para poder mantener el catálogo. | En creación puede ser ≥0/1 según negocio; en edición no modificar stock o aceptar 0. Renombrar a `stock_disponible` solo si realmente se ajusta. |

### P2 — Medios (18)

| ID | Formulario y evidencia | Actual vs esperado / reproducción | Riesgo | Recomendación |
|---|---|---|---|---|
| **P2-01** | CRUD genérico `SuperadminFormSection`/`ResourceForm.tsx`. | Validación FE ocurre al perder foco; no hay `required` HTML general ni bloqueo integral antes de submit. Repro: enviar vacío sin hacer blur. | Viaje 422 evitable, mensajes tardíos; backend sí protege. | Ejecutar validador común al submit, asociar `aria-invalid`/mensaje y enfocar primer error. |
| **P2-02** | Registro. Componente Register + `UserValidation`. | FE limita nombre a 50 y apellidos a 20; BE/BD hasta 100. | Datos legales válidos rechazados solo en UI. | Compartir constantes/esquema y tests de borde 20/21/50/51/100. |
| **P2-03** | Registro, reset, perfil y admin passwords. | Registro/admin exigen mezcla y número; reset/Breeze usan `Password::defaults()` que aquí queda en mínimo 8. | La misma cuenta puede bajar de política mediante otro formulario. | Configurar `Password::defaults()` central y reutilizarlo en todos los flujos. |
| **P2-04** | Eliminar cuenta Breeze. Controlador Profile. | Cierra sesión y luego elimina directamente; relaciones FK pueden fallar sin mensaje de dominio. | 500 y sesión terminada aunque la baja no concluya; historial puede impedir delete. | Política de anonimización/desactivación o servicio transaccional; comprobar dependencias antes de logout. |
| **P2-05** | Selects de categoría/marca/temporada/color/talla/prenda. Option services + `exists`. | Varias opciones incluyen inactivos o el backend acepta IDs inactivos forjados; no se distingue “inactivo”. | Relaciones nuevas con maestros retirados. | Filtrar activos en alta; conservar valor histórico solo en edición con etiqueta “inactivo”; repetir regla en backend. |
| **P2-06** | Precios, costos, descuentos, montos. Reglas comerciales y migraciones decimal(10,2). | No hay `max:99999999.99` ni validación uniforme de escala. | Excepción SQL/500 o redondeo inesperado. | Regla decimal/máximo compartida, normalización monetaria y pruebas de borde. |
| **P2-07** | Reportes superadmin y filtros vendedor. `ReportQueryService` y servicios de listados. | Algunos `Carbon::parse` no están protegidos. Repro: `date_from=no-es-fecha`. | 500 provocado por query manipulada. | Form Request para query: `nullable|date` y error 422/feedback; parsear solo valores validados. |
| **P2-08** | Todos los rangos de filtro. | No hay regla uniforme `date_to after_or_equal:date_from`. | Pantalla/CSV vacíos sin explicar causa. | Validación relacional y mensaje junto al rango. |
| **P2-09** | Filtros GET de recursos/logs/auditoría/dañados. | Enums, IDs y longitudes no siempre se validan; valores desconocidos se ignoran o devuelven vacío. | UX impredecible y consultas costosas por búsqueda excesiva. | Request por listado con whitelist, máximos y normalización; conservar mensajes en Inertia. |
| **P2-10** | Acciones de venta superadmin en detalle. UI de recurso + `SaleService`. | UI muestra confirmar/incompleta/cancelar en `incompleta`; servicio solo admite algunas desde `en_revision`. | Botones que siempre fallan con 422. | Derivar acciones visibles de la misma máquina de estados usada en servicio. |
| **P2-11** | Carga de comprobantes. Servicios comerciales. | Archivo puede guardarse antes de que falle una operación posterior y no todos los flujos lo eliminan. | Huérfanos, consumo de disco y datos sensibles retenidos. | Patrón común: registrar paths creados, borrar en catch y después de rollback; job de reconciliación. |
| **P2-12** | Imágenes/PDF. Reglas de archivo. | Sin dimensiones, resolución, hash, malware/estructura PDF ni cuota acumulada. | Bombas de imagen/PDF, consumo de recursos, contenido inútil. | Dimensiones/píxeles máximos, MIME permitido explícito, antivirus si aplica, hash y límites por operación. |
| **P2-13** | Comprobantes cliente/admin vs vendedor. | Cliente/admin aceptan imagen; vendedor acepta también PDF. FE tampoco explica una política única. | El mismo documento es válido o inválido según canal. | Definir política única o justificar por rol; centralizar regla y texto de ayuda. |
| **P2-14** | Generación de números/códigos comerciales y dañados. Servicios que calculan “siguiente” por conteo/búsqueda. | Dos solicitudes simultáneas pueden elegir el mismo valor antes del insert. | Colisión unique y 500 intermitente. | Sequence/UUID/tabla de correlativos bloqueada; reintento acotado ante conflicto unique. |
| **P2-15** | Reportar dañado con vínculo a inventario. Servicio de dañados. | La cantidad puede superar stock al reportar; el error aparece recién al confirmar afectación. | Expediente imposible de confirmar y trabajo duplicado. | Mostrar disponibilidad y validar de nuevo al crear si `afecta_stock`; mantener lock al confirmar. |
| **P2-16** | Visualizar reportes superadmin. `ReportsController`. | Un GET de lectura registra `reporte_visualizado` en BD. | GET deja de ser libre de efectos; ruido de auditoría/crawlers/reintentos. | Telemetría asíncrona separada o evento explícito; documentar si se mantiene. |
| **P2-17** | Newsletter del footer. Componente público. | `preventDefault` sin endpoint, feedback ni almacenamiento. | Formulario engañoso; usuario cree que se suscribió. | Eliminar hasta existir backend o implementar consentimiento, validación, doble opt-in y mensajes. |
| **P2-18** | Errores de formularios complejos. Product wizard/ventas/reservas. | Errores anidados pueden quedar lejos de la fila/paso y no siempre se enfoca el primer control; resumen global irregular. | Corrección difícil, especialmente móvil/teclado. | Mapa de errores por índice, abrir paso afectado, foco y resumen accesible con links. |

### P3 — Bajos (9)

| ID | Formulario y evidencia | Actual vs esperado / reproducción | Riesgo | Recomendación |
|---|---|---|---|---|
| **P3-01** | Mensajes comerciales. Varias cadenas muestran mojibake como `mÃ©todo`. | Error de codificación visible. | Menor confianza y textos difíciles de probar. | Normalizar UTF-8, corregir literales y añadir chequeo en CI. |
| **P3-02** | Etiquetas de formularios personalizados. | La marca de obligatorio no es uniforme y a veces depende solo del placeholder. | Accesibilidad/comprensión. | `required`, indicador textual y `aria-describedby` coherentes. |
| **P3-03** | Actualizar línea inexistente de carrito. Controlador carrito. | Algunos caminos responden como éxito/no-op si la línea ya no existe. | La UI puede afirmar actualización sin cambio. | 404/422 de dominio y refrescar carrito. |
| **P3-04** | Confirmar contraseña. Request/controlador auth. | El vacío termina en comprobación genérica en lugar de mensaje `required` específico. | Feedback pobre. | `required|string` antes de autenticar. |
| **P3-05** | Logs/auditoría/dañados con fecha inválida. Helpers con catch. | La fecha malformada se ignora silenciosamente. | Usuario confía en un filtro que no se aplicó. | Conservar tolerancia, pero devolver error visible y no ejecutar consulta ambigua. |
| **P3-06** | Descripción de prenda. `ResourceValidationService.php:72`. | `nullable|string` sin máximo; BD `text`. | Payload excesivo y UI poco predecible. | Máximo de negocio y contador; límite de request global. |
| **P3-07** | Alta de color. Preparadores de request. | Nombre/familia/hex dominante se derivan o normalizan sin que el contrato sea evidente; `uniqueColorName` no comprueba unicidad. | Mantenimiento engañoso y sorpresas al usuario. | Renombrar helper, mostrar valor final y documentar fuente de verdad. |
| **P3-08** | Filas de variante/imagen. | Mensajes con índices técnicos no siempre identifican color/talla/archivo de la fila. | Corrección más lenta. | Etiquetas de fila estables y mensajes “Variante Azul/M: …”. |
| **P3-09** | Favoritos. Acción pública autenticada. | Se puede guardar el ID de una prenda inactiva mediante petición directa; luego puede desaparecer del listado. | Registro huérfano lógico/confusión, sin impacto de stock. | Exigir prenda activa al agregar; permitir quitar aun inactiva. |

## 10. Seguridad y autorización: resultado específico

### Protecciones verificadas

- Las rutas de escritura están en middleware web y reciben protección CSRF de Laravel/Inertia.
- Superadmin y vendedor tienen middleware de rol/estado; `EnsureVendedor` acepta vendedor o superadmin activos.
- Recibos de cliente y notificaciones se consultan por el usuario autenticado, evitando IDOR directo.
- Las relaciones anidadas de imagen/variante enviadas al editor agregado se verifican contra la prenda actual.
- Los payloads se construyen desde datos validados y mapeos explícitos; no se observó asignación masiva ciega desde `$request->all()` en los flujos críticos revisados.
- Existen salvaguardas al editar/eliminar roles de sistema, al quitar privilegios propios y al proteger al último superadmin.

### Superficies pendientes

- La autorización vendedor para revisión/recojo es global por diseño de consulta, no por ownership. Requiere decisión funcional y permisos granulares.
- Varias reglas usan `exists` donde el negocio necesita además rol, actividad y/o pertenencia.
- Archivos financieros públicos eluden la autorización de las pantallas.
- En alta/edición dinámica la capacidad se decide por definición/middleware; conviene sumar Policies por registro para defensa en profundidad.
- La UI no es una barrera de seguridad: estados, IDs, campos deshabilitados y métodos se pueden alterar con DevTools. La mayoría de servicios lo contempla, pero las excepciones aparecen en los hallazgos.

## 11. Preguntas funcionales que requieren respuesta del negocio

Estas preguntas no deben resolverse solo “endureciendo validaciones”; cambian el comportamiento esperado.

1. ¿Una variante nueva puede iniciar con stock 0 o toda variante publicada debe iniciar con al menos 1?
2. ¿Editar una prenda debe permitir ajustar stock, o el stock solo debe cambiar mediante un movimiento explícito?
3. ¿Qué significa exactamente `cdi_inv`: físico disponible, vendible o físico menos comprometido? La lógica actual lo trata como vendible no comprometido.
4. ¿Puede un superadmin corregir `cre_inv`? Si sí, ¿con qué autorización, conciliación y motivo?
5. ¿Todos los vendedores forman una caja/recojo central, o cada vendedor solo debe revisar y entregar sus operaciones?
6. Si la bandeja es global, ¿todo vendedor puede abrir comprobantes financieros de todos los clientes?
7. ¿Se permite crear una venta sin cliente? ¿Qué datos mínimos exige una factura/recibo anónimo?
8. Si se elige cliente, ¿debe ser siempre una cuenta activa con rol `cliente`?
9. ¿Se permiten ventas/reservas de una prenda inactiva para liquidar stock, o “inactiva” prohíbe cualquier operación nueva?
10. ¿Un descuento del 100 % representa obsequio/promoción válida? ¿Debe existir un método de pago en ese caso?
11. ¿Puede cancelarse una reserva que ya tiene dinero confirmado? ¿Se reembolsa, se deja saldo a favor o se requiere aprobación?
12. ¿Quién puede forzar vencimiento antes de la fecha y cuál debe ser el motivo obligatorio?
13. ¿Una reserva vencida por fecha puede completarse si el cliente ya está presente, o debe reactivarse primero?
14. ¿“Confirmada” y “lista_recojo” son estados comerciales, logísticos o ambos? Hoy se mezclan con campos `est_res` y `est_logistico_res`.
15. ¿Superadmin puede entregar sin 100 % pagado, o debe obedecer el mismo control que vendedor?
16. ¿El método “otro” de venta admite cualquier texto o debe existir un catálogo cerrado?
17. ¿Cliente y superadmin deben poder subir PDF como comprobante igual que vendedor?
18. ¿Cuánto tiempo deben conservarse comprobantes y evidencias de daño? ¿Quién puede descargarlos?
19. ¿Se aceptan categorías con padre inactivo? ¿Qué ocurre con productos de una rama desactivada?
20. ¿La combinación única de variante debe incluir ubicación, o una misma talla/color puede tener inventario separado por depósito/tienda?
21. ¿`ubi_inv` es una ubicación libre o solo `DEPOSITO|TIENDA`? Los dos formularios discrepan.
22. ¿El teléfono boliviano `[67]########` es obligatorio para administradores y clientes de otros países?
23. ¿El apellido materno es obligatorio en registro aunque la BD lo permita nulo?
24. ¿Qué política de contraseña debe regir todos los puntos de cambio/restablecimiento?
25. ¿Eliminar cuenta significa borrado físico, desactivación o anonimización conservando ventas/reservas?
26. ¿Los reportes deben generar un registro de auditoría cada vez que se visualizan, incluso en GET/reintento?
27. ¿Un reporte dañado afecta stock al reportar o solo al confirmar? ¿Quién verifica físicamente la cantidad?
28. ¿`decision_pdr` debe elegirla el usuario o derivarse de la acción reparar/perder/descartar?
29. ¿El código correlativo debe ser legible/secuencial sin huecos, o se puede usar UUID/sequence con huecos?
30. ¿El newsletter debe funcionar ahora? Si sí, ¿qué consentimiento y doble opt-in se exige?

## 12. Preguntas y respuestas tipo docente (basadas en el proyecto)

### Arquitectura de validación

1. **¿Dónde está la autoridad final de un formulario React/Inertia?**  
   En Laravel. Un `required`, un select o un campo `disabled` de React se puede alterar; el servidor debe repetir formato, elegibilidad y autorización. El CRUD dinámico lo concentra en `ResourceValidationService`, mientras ventas/reservas usan sus servicios.

2. **¿Por qué `exists` no basta para una FK comercial?**  
   Porque solo prueba que la fila existe. Una variante puede existir pero estar inactiva, o un usuario puede existir y ser vendedor. Los hallazgos P1-04 a P1-07 muestran esa diferencia.

3. **¿Qué ventaja tiene `Rule::unique(...)->ignore($id)` al editar?**  
   Permite conservar el valor propio sin provocar un falso duplicado, pero sigue rechazando el valor de otra fila. Se usa en roles, marcas, tallas, temporadas, SKU y código de barras.

4. **¿Por qué el proyecto además compara nombres normalizados si ya hay `unique`?**  
   Para considerar iguales variantes de mayúsculas/espacios según la regla del negocio y no depender de la collation de la BD. Roles, categorías y otros maestros aplican esa defensa.

5. **¿Qué demuestra el fallo del perfil Breeze?**  
   Que validar bien cada capa no sirve si no comparten el mismo contrato. React usa `name`, el Request usa `nombre` y el modelo usa `nom_usu`; se requiere un DTO/mapeo o nombres unificados.

6. **¿Es suficiente validar en `onBlur`?**  
   No. Mejora feedback, pero el usuario puede enviar sin salir del campo o llamar al endpoint directamente. `ResourceForm` debe validar al submit y Laravel debe permanecer autoritativo.

7. **¿Para qué sirve una regla condicional como `required_with`?**  
   En galería, el orden solo es obligatorio cuando existe archivo. Evita exigir metadatos de una fila vacía del editor repetible.

8. **¿Qué validación evita un IDOR en imágenes existentes?**  
   No basta `exists:imagenes_prenda`; el callback verifica que cada ID enviado pertenezca a la prenda editada. El mismo patrón se usa con variantes.

### Inventario y transacciones

9. **¿Qué diferencia hay entre transacción y bloqueo pesimista?**  
   La transacción agrupa atomicidad; `lockForUpdate` evita que otra transacción modifique la misma fila antes de terminar. Una transacción sin lock todavía puede sobrescribir un valor leído antes.

10. **¿Por qué P0-02 es una “actualización perdida”?**  
    El editor A conserva cdi=10; B descuenta a 9; A escribe su valor absoluto 10. El commit de B existió, pero su efecto desaparece.

11. **¿Por qué `cre_inv = 0` es peor que un error visible?**  
    La operación termina con éxito y registra datos plausibles, pero borra obligaciones activas. El fallo puede descubrirse recién al entregar o conciliar.

12. **¿Cómo evita el proyecto el bypass por líneas duplicadas en una venta?**  
    Agrupa cantidades por variante antes de comparar con inventario. Sin ello, dos líneas de 4 podrían pasar individualmente contra stock 5.

13. **¿Por qué una reserva decrementa disponible e incrementa comprometido?**  
    La unidad deja de estar vendible, pero aún no se consumió definitivamente. Al completar solo baja comprometido; al cancelar se restaura disponible.

14. **¿Debe un ajuste administrativo escribir un total o un delta?**  
    Preferiblemente un delta con motivo, actor, valor anterior/posterior y lock. Facilita concurrencia y auditoría, y evita sobrescribir cambios ajenos.

15. **¿Qué debe probar un test concurrente de stock?**  
    Que dos operaciones con demanda total superior al disponible no puedan confirmar ambas, y que una edición administrativa no resucite unidades ya vendidas.

### Estados comerciales

16. **¿Qué es una máquina de estados en este proyecto?**  
    Es la lista explícita de transiciones permitidas para venta, reserva, pago, logística o daño. La UI debe mostrar exactamente las mismas acciones que el servicio acepta.

17. **¿Por qué ocultar un botón no protege una transición?**  
    Porque el usuario puede enviar la petición manualmente. El servicio debe validar el estado actual bajo lock; la UI solo evita errores accidentales.

18. **¿Qué inconsistencia tienen las acciones de venta superadmin?**  
    React expone algunas acciones también en `incompleta`, pero el servicio espera `en_revision`. El resultado es un botón legítimo que siempre devuelve error.

19. **¿Por qué hay que comprobar la fecha al “vencer”?**  
    El nombre de la acción expresa una precondición temporal. Sin `fecha <= now`, no es vencimiento sino cancelación anticipada disfrazada.

20. **¿Qué debe ocurrir con pagos al cancelar una reserva?**  
    Debe existir una decisión transaccional: bloquear, reembolsar, anular o convertir a saldo; dejarlos confirmados sin transición rompe conciliación.

21. **¿Por qué el control de pago al entregar debe estar centralizado?**  
    Vendedor lo exige y superadmin no. Dos implementaciones de la misma regla evolucionan distinto; un servicio/policy común evita el bypass por rol.

22. **¿Qué está bien en el recojo vendedor?**  
    Recalcula pago confirmado, exige 100 %, estado/logística, fecha válida para reserva y cantidad comprometida; no confía solo en que el botón estuviera habilitado.

### Archivos y privacidad

23. **¿Qué verifica `image` de Laravel y qué no?**  
    Verifica que el archivo sea una imagen reconocible; no define por sí solo dimensiones de negocio, duplicados, contenido apropiado, cuota acumulada o privacidad del almacenamiento.

24. **¿Por qué un archivo público es un problema aunque la pantalla tenga auth?**  
    La URL del disco público puede servirse sin pasar por el controlador/policy. Quien conoce o recibe el enlace puede acceder directamente.

25. **¿Por qué guardar el archivo antes de una transacción puede dejar huérfanos?**  
    El rollback revierte BD, no filesystem. Si una validación tardía/insert falla, hay que borrar expresamente el path creado.

26. **¿Qué patrón correcto ya existe en imágenes de producto?**  
    Acumular paths creados, limpiarlos en excepción y borrar archivos sustituidos solo después del éxito. Ese patrón debe reutilizarse en comprobantes.

27. **¿Por qué aceptar PDF para vendedor pero no cliente es un problema?**  
    No necesariamente es inseguro, pero sí un contrato inconsistente. Debe responder a una política documentada, no al componente desde el que se sube.

### Base de datos y concurrencia

28. **¿Qué valor aporta un índice unique si Laravel ya valida?**  
    Cierra la carrera entre “comprobar” e “insertar”. Dos peticiones pueden pasar validación; solo la BD garantiza unicidad final.

29. **¿Por qué aun con unique hay que capturar la excepción?**  
    El conflicto concurrente válido debe volver como 422/409 comprensible, no como 500. El caso de colores expone este hueco.

30. **¿Qué falla en las dos migraciones de temporada?**  
    Ambas intentan crear las mismas columnas e índice. El segundo `up` no es idempotente; además, los `down` pueden pelear por los mismos objetos.

31. **¿Por qué hace falta un máximo monetario?**  
    `numeric|min` acepta un valor mayor que `decimal(10,2)`. La BD lo rechazará o redondeará; validar el rango convierte un fallo técnico en error de campo.

32. **¿Por qué `count()+1` no es un generador seguro?**  
    Dos transacciones pueden leer el mismo conteo. Una sequence o fila de correlativo bloqueada entrega valores distintos atómicamente.

### Seguridad, permisos y UX

33. **¿Hay un IDOR confirmado en recibos/notificaciones del cliente?**  
    No en lo revisado: las consultas se acotan al usuario autenticado. Es un control positivo que debe conservarse con tests.

34. **¿La bandeja global de vendedor es automáticamente una vulnerabilidad?**  
    Depende de la política. El código la presenta como bandeja operativa central; si todos los vendedores tienen función de caja/recojo es intencional. Sin permiso granular ni política documentada, sigue siendo un riesgo de mínimo privilegio.

35. **¿Qué protege contra mass assignment?**  
    Validación, payloads mapeados y `$fillable`. Pero el perfil enseña que `$fillable` también puede descartar silenciosamente claves válidas si el mapeo no coincide.

36. **¿Por qué un GET que escribe auditoría merece revisión?**  
    Navegadores, prefetch, reintentos y crawlers pueden repetir GET. Si cada vista escribe, la auditoría mide tráfico técnico además de intención humana.

## 13. Casos de prueba manual recomendados

No se ejecutaron. Usar una BD efímera, almacenamiento temporal y cuentas de prueba. En las pruebas de concurrencia abrir dos sesiones y coordinar el envío; verificar tanto filas finales como movimientos/logs.

| ID | Caso / preparación | Pasos | Resultado esperado |
|---|---|---|---|
| T01 | Registro válido | Completar nombres, teléfono 7xxxxxxx, email nuevo y contraseña fuerte. | Cliente creado, normalizado, rol correcto y sesión según diseño. |
| T02 | Bordes de registro | Probar apellido de 21 y 100 caracteres desde UI y petición directa. | Mismo límite en FE/BE; hoy revelará P2-02. |
| T03 | Contraseñas | Cambiar a contraseña solo minúsculas mediante registro, reset, Breeze y admin. | Todos deben aplicar una política única; hoy divergen. |
| T04 | Login bloqueado | Cinco credenciales erróneas y sexto intento correcto; usuario/rol inactivo. | Rate limit y mensajes sin filtrar existencia; inactivos no acceden. |
| T05 | Perfil Breeze | Cambiar nombre/correo desde pantalla estándar. | Persistencia correcta sin 422; hoy falla P1-01. |
| T06 | Eliminar cuenta con historial | Cliente con venta/reserva intenta baja. | Respuesta de dominio y política definida; nunca 500/logout parcial. |
| T07 | CRUD vacío | Enviar rol/categoría/marca sin tocar controles. | FE bloquea y enfoca; backend devuelve errores equivalentes. |
| T08 | Duplicado normalizado | Crear categoría `Vestidos` y luego `  VESTIDOS `. | 422 legible antes de excepción BD. |
| T09 | Ciclo de categorías | A padre de B; intentar B padre de A y ciclo de tres nodos. | Rechazo 422 específico. |
| T10 | Color reutilizable | Crear color y repetir por alta normal e inline con mismo hex/familia/nombre derivado. | Reutilización o 422 coherente; nunca TypeError/500. |
| T11 | Temporada | Fin anterior/inicial igual/código duplicado. | Anterior rechazado; igual aceptado; duplicado 422. |
| T12 | Migraciones limpias | En CI/BD desechable ejecutar fresh, rollback y fresh de nuevo. | Sin columna/índice duplicado. |
| T13 | Prenda mínima | Crear con una variante, imagen primaria y valores válidos según `usa_variantes`. | Regla de una/dos variantes aplicada y SKU generado. |
| T14 | Variante duplicada | Dos filas misma talla+color; códigos de barra repetidos. | Error por fila antes de persistir nada. |
| T15 | Imagen ajena | Interceptar edición y enviar ID de imagen de otra prenda. | 422; no cambia ni elimina archivo ajeno. |
| T16 | Variante ajena | Enviar `variant_rows.*.id` perteneciente a otra prenda. | 422; no reasigna ni borra. |
| T17 | Costo/precio/overflow | Costo mayor a precio y precio por encima de decimal(10,2). | Ambos 422 con mensajes; hoy overflow puede llegar a BD. |
| T18 | Prenda agotada | Variante con cdi 0; editar solo descripción. | Debe guardar sin inventar stock; hoy P1-17. |
| T19 | Preservar compromiso | cdi 8/cre 2 con reserva activa; editar nombre/imagen. | cdi/cre y movimientos intactos; hoy P0-01. |
| T20 | Carrera edición/venta | Sesión A abre stock 10; B vende 1; A guarda. | Final 9 y movimiento conciliado; nunca 10. |
| T21 | Carrera dos ventas | Stock 1, dos sesiones compran simultáneamente. | Solo una confirma; la otra recibe sin stock. |
| T22 | Reserva de inactivo | Añadir activo, desactivar variante y confirmar; repetir con POST forjado. | Rechazo y carrito actualizado; hoy P1-04. |
| T23 | Checkout por rol/estado | POST válido como cliente activo, cliente inactivo, vendedor y superadmin. | Solo rol/estado definido por negocio; hoy auth solo. |
| T24 | Precio manipulado | Alterar precio/total/descuento en DevTools, mantener IDs/cantidades. | Servidor recalcula; no persiste precio cliente. |
| T25 | Líneas duplicadas | Stock 5, enviar variante dos veces con cantidad 3. | Rechazo por demanda agrupada 6. |
| T26 | Descuento 100 % | Subtotal 100, descuento 100 y pago 50/100 en venta y reserva. | Flujo definido: rechazo o regalo sin pago; nunca saldo incoherente. |
| T27 | Cancelación pagada | Reserva con pago confirmado; vendedor cancela. | Bloqueo o reembolso/anulación consistente y stock restaurado una vez. |
| T28 | Vencimiento temprano | Reserva con límite mañana; ejecutar vencer por URL. | 422; hoy puede liberar stock. |
| T29 | Completar vencida | Reserva pagada con fecha pasada desde superadmin y vendedor. | Ambos paneles dan la misma respuesta. |
| T30 | Entrega no pagada | Venta registrada/lista con pago parcial; entregar desde ambos roles. | Ambos bloquean con saldo. |
| T31 | Ownership vendedor | A crea operación; B intenta ver/revisar/entregar por URL. | Resultado según política explícita y permiso granular. |
| T32 | Rechazo de comprobante | Vendedor rechaza sin motivo y con 5+ caracteres; doble envío approve/reject. | Motivo requerido; solo primera transición válida bajo lock. |
| T33 | Archivos | Ejecutable renombrado, imagen corrupta, imagen enorme en píxeles, >4/6 MB, PDF según rol. | Rechazo consistente, sin archivo huérfano. |
| T34 | Privacidad comprobante | Copiar URL, cerrar sesión y abrir en sesión de otro cliente. | Acceso denegado; hoy disco público puede servirlo. |
| T35 | Daño y stock | Reportar cantidad mayor; confirmar, reparar, pérdida, resolver con decisiones cruzadas. | Solo matriz válida; cada movimiento exactamente una vez. |
| T36 | Carrera código de daño | Dos sesiones crean simultáneamente. | Códigos distintos, sin 500. |
| T37 | Fechas de filtro | Fecha imposible, string libre, desde>hasta, límites válidos. | Error visible y mismo criterio en pantalla/CSV/print. |
| T38 | Parámetros de filtro | Enum/ID inexistente, búsqueda muy larga, precio negativo. | 422/normalización documentada; sin 500 ni filtro silencioso. |
| T39 | Newsletter | Enviar email válido/inválido y revisar red/feedback. | Suscripción real con consentimiento o formulario ausente; hoy no hace nada. |
| T40 | Accesibilidad/error anidado | Teclado/lector; error en variante 8 e imagen 4. | Paso abre, foco llega al control, etiqueta y mensaje describen la fila. |

## 14. Orden recomendado de corrección

1. **Congelar el ajuste de stock desde edición de prenda** hasta corregir P0-01/P0-02. Añadir tests de compromiso y concurrencia antes de reabrirlo.
2. **Crear un único servicio de mutación de inventario** con locks, deltas, motivo, actor y movimiento; catálogo solo referencia ese servicio.
3. **Corregir contratos que impiden operar/instalar:** perfil Breeze, migraciones de temporada, alta normal/inline de colores y edición con stock 0.
4. **Centralizar elegibilidad comercial:** usuario activo/rol cliente, prenda/variante activa, método y estado; reutilizar reglas entre cliente, vendedor y superadmin.
5. **Formalizar máquinas de estados** de ventas, reservas, pagos, logística y dañados. La UI debe consumir capacidades calculadas por dominio.
6. **Resolver dinero en cancelaciones/descuentos:** reembolso/anulación/saldo a favor y tratamiento explícito de total cero.
7. **Mover comprobantes a privado** y aplicar autorización de descarga, retención y limpieza de huérfanos.
8. **Validar filtros y números:** Requests para GET, rangos de fecha, límites decimal y correlativos seguros.
9. **Compartir esquema FE/BE** para límites/políticas, sin quitar autoridad al servidor; bloquear submit y mejorar error/foco.
10. **Cerrar P3 y regresiones** con pruebas feature, tests de servicios y una matriz E2E por rol/estado.

## 15. Formularios que conviene estudiar primero

| Orden | Formulario/flujo | Motivo de estudio |
|---:|---|---|
| 1 | Editar prenda agregada | Contiene ambos P0 y mezcla catálogo, archivos, variantes e inventario. |
| 2 | Confirmar/cancelar/completar reserva | Enseña la semántica `cdi/cre`, pagos, locks y conversión a venta. |
| 3 | Checkout online | Buen ejemplo de recalcular precio/stock en servidor, con hueco de rol/estado de cuenta. |
| 4 | Editar inventario | Debe convertirse en ajuste auditable y compartir el protocolo de concurrencia. |
| 5 | Crear/editar color + inline | Caso compacto de regla BE/índice unique/tipo de respuesta/concurrencia. |
| 6 | Perfil Breeze | Caso claro de contrato FE–Request–modelo incompatible. |
| 7 | Revisión de pagos vendedor | Obliga a decidir autorización global vs ownership y privacidad del comprobante. |
| 8 | Recojo vendedor y superadmin | Dos implementaciones de una regla crítica de pago producen resultados distintos. |
| 9 | Producto dañado y transiciones | Combina archivo, estado, decisión y movimiento físico diferido. |
| 10 | Filtros/reportes/exportaciones | Superficie amplia de fechas y query strings; algunos errores son 500 o silenciosos. |

## 16. Mapa de fuentes principales revisadas

- Rutas: `routes/web.php`, `routes/auth.php`.
- Validación CRUD: `app/Services/Superadmin/Resources/ResourceValidationService.php`, `UserValidation.php`.
- Orquestación CRUD: `ResourceControllerActions.php`, `ResourceDefinitionService.php`, writer/options de recursos.
- Prenda agregada: `app/Services/Superadmin/Resources/ProductCatalogService.php` y servicio de imágenes.
- Frontend dinámico: `resources/js/Pages/Superadmin/ResourceForm.tsx`, secciones/campos del formulario y definiciones TSX.
- Auth/perfil: Requests/controladores de Auth y Profile; páginas bajo `resources/js/Pages/Auth` y `Pages/Profile`.
- Cliente: controladores/servicios de catálogo, carrito, reservas, checkout, favoritos, recibos y notificaciones.
- Comercial superadmin: `SaleService.php`, `ReservationService.php`, `ReservationToSaleService.php`, `SalePickupService.php`.
- Vendedor: servicios de ventas, reservas, revisión de pagos, recojos, reportes y dañados.
- Reportes/auditoría: `ReportQueryService.php`, controladores de reports/logs/audit/movimientos.
- Persistencia: modelos relacionados y todas las migraciones de usuarios, catálogo, inventario, ventas, reservas, pagos, movimientos, notificaciones, auditoría y productos dañados.

## 17. Conclusión de estudio

El proyecto tiene una base defensiva valiosa: validación central para recursos, precios reconstruidos en servidor, transacciones y bloqueos en los servicios comerciales, comprobación de pertenencia de IDs anidados y protección de operaciones sensibles de superadmin. El problema principal no es la ausencia general de validación, sino que **la misma regla de negocio se implementa de forma distinta según el punto de entrada**.

El concepto más importante para estudiar es la frontera entre catálogo e inventario. Una edición descriptiva no debe escribir cantidades, y toda cantidad debe cambiar mediante una operación de dominio serializable y auditable. Después, conviene estudiar contratos compartidos FE/BE, Policies por recurso y máquinas de estados únicas para que cliente, vendedor y superadmin no puedan producir resultados divergentes.

No se modificó código de aplicación ni datos. Este documento es el único archivo creado por la auditoría.
