# Auditoría de base de datos, modelos y relaciones

Fecha de auditoría: 2026-05-24  
Sistema: Mati Boutique, Laravel + React/TypeScript + Inertia  
Alcance: migraciones, modelos Eloquent, relaciones, campos usados por flujos comerciales y riesgos de esquema.  
Restricciones respetadas: no se corrigió código, no se crearon migraciones, no se ejecutó servidor de desarrollo y no se tocaron datos reales.

## 1. Resumen ejecutivo

La base de datos declarada por migraciones soporta la mayor parte de los flujos actuales: catálogo, prendas, variantes, colores, tallas, temporadas, inventario, reservas, pagos de reservas, ventas, pagos de ventas, favoritos e historiales derivados. Los modelos principales existen y las relaciones críticas de reserva, venta, detalle, pago, prenda, variante e inventario están implementadas.

Los riesgos principales están en cuatro puntos:

- Hay migraciones duplicadas para `temporadas.fec_ini_tem` y `temporadas.fec_fin_tem`; una instalación limpia puede fallar por columnas duplicadas.
- No existe relación persistente entre `reservas` y la `venta` generada al recoger; hoy se infiere por el número `VEN-RES-{nre_res}`.
- Los estados (`est_res`, `est_pago_res`, `est_ven`, `est_rpg`, `est_vpg`) son strings libres sin constraints ni fuente central única.
- El modelo `User` no tiene relaciones directas a reservas, ventas ni favoritos, aunque esos vínculos existen en base de datos.

## 2. Estado de tablas

| Tabla | Estado | Observación |
|---|---|---|
| `users` | Correcta con observaciones | Tiene datos personales, rol, estado y timestamps. Faltan relaciones Eloquent inversas. |
| `roles` | Correcta | Soporta roles del sistema y relación con usuarios. |
| `categorias` | Correcta | Soporta jerarquía, estado y perfil de tallaje. |
| `marcas` | Correcta | Suficiente para catálogo. |
| `colores` | Correcta | Incluye familia, HEX exacto, HEX dominante y orden. |
| `tallas` | Correcta | Incluye segmento, sistema, grupo y orden. |
| `temporadas` | Con error de migración | Campos suficientes, pero `fec_ini_tem` y `fec_fin_tem` se agregan en dos migraciones. |
| `prendas` | Correcta | Soporta categoría, marca, temporada, género, precio base, estado y usuario registrador. |
| `prenda_variantes` | Correcta | Soporta color, talla, SKU, código adicional, precio, costo y estado. |
| `imagenes_prenda` | Correcta | Soporta imagen por prenda y opcionalmente por variante. |
| `inventarios` | Correcta con riesgo semántico | Tiene disponible, reservado y ubicación. Falta auditoría/movimientos. |
| `reservas` | Correcta con observaciones | Tiene totales, saldo, estados, recojo, responsable y verificador. Falta FK a venta generada. |
| `detalle_reservas` | Correcta | Tiene variante, cantidad, precio unitario y subtotal. |
| `reserva_pagos` | Correcta | Tiene monto declarado/confirmado, comprobante, estado y verificador. |
| `ventas` | Correcta con observaciones | Soporta venta online/física/reserva, estados y vendedor nullable. Falta vínculo explícito a reserva origen. |
| `detalle_ventas` | Correcta | Tiene variante, cantidad, precio unitario y subtotal. |
| `venta_pagos` | Correcta | Tiene monto declarado/verificado, comprobante, estado y verificador. |
| `favoritos` | Correcta | Relaciona usuarios y prendas con unicidad. |

Tablas esperadas no encontradas:

- `recibos`: no existe. Actualmente los recibos se generan dinámicamente desde `ReceiptService`.
- `inventario_movimientos` o equivalente: no existe. No hay bitácora persistente de entradas/salidas/reservas/liberaciones.
- `reserva_venta` o campo `cod_reserva_ven`: no existe. La relación venta-reserva se infiere por número.

## 3. Revisión de campos por flujo

### Reservas

Campos existentes suficientes:

- Total: `reservas.tot_res`
- Subtotal: `reservas.sub_res`
- Descuento: `reservas.des_res`
- Monto pagado: `reservas.monto_pagado_res`
- Saldo: `reservas.saldo_res`
- Estado de pago: `reservas.est_pago_res`
- Estado de reserva: `reservas.est_res`
- Fecha de reserva/vencimiento: `fec_res`, `ven_res`
- Fechas de recojo: `fec_disponible_recojo_res`, `fec_limite_recojo_res`
- Punto/origen de recojo: `punto_recojo_res`, `origen_stock_res`
- Cliente/responsable/verificador: `cod_usuario_cliente_res`, `cod_usuario_responsable_res`, `cod_usuario_verifica_pago_res`
- Pagos/comprobantes: `reserva_pagos.*`

Observaciones:

- `est_res` nace con default antiguo `activa`, pero el flujo actual usa `por_procesar`, `confirmada`, `lista_recojo`, `completada`, `cancelada`, `vencida`, `rechazada`.
- No hay constraint de estados permitidos.
- No hay FK de reserva a venta generada.

### Ventas

Campos existentes suficientes:

- Número: `ventas.nve_ven`
- Cliente: `cod_usuario_cliente_ven`
- Vendedor: `cod_usuario_vendedor_ven`
- Tipo: `tiv_ven`
- Subtotal/descuento/total: `sub_ven`, `des_ven`, `tot_ven`
- Método de pago principal: `mpa_ven`
- Estado: `est_ven`
- Fecha: `fec_ven`
- Pagos/comprobantes/verificador: `venta_pagos.*`

Observaciones:

- `cod_usuario_vendedor_ven` fue hecho nullable con SQL directo para PostgreSQL. En el `.env` actual `DB_CONNECTION=pgsql`, así que calza con el entorno, pero la migración no es portable a MySQL/SQLite.
- No existe `cod_reserva_origen_ven` o similar para ventas creadas desde reserva.
- Estados como `en_revision`, `registrada`, `incompleta`, `cancelada` son strings libres.

### Inventario

Campos existentes suficientes:

- Variante: `cod_prenda_variantes_inv`
- Disponible: `cdi_inv`
- Reservado: `cre_inv`
- Ubicación: `ubi_inv`

Observaciones:

- La estructura soporta disponible y reservado, pero no registra movimientos históricos.
- La semántica debe quedar documentada: el código operativo de reservas mueve unidades desde `cdi_inv` hacia `cre_inv`, lo cual implica que `cdi_inv` debe ser disponible real y `cdi_inv + cre_inv` el total físico.

### Recibos

Estado actual:

- No hay tabla de recibos.
- Se generan dinámicamente desde `ReceiptService` usando datos de `reservas`, `ventas`, `reserva_pagos` y `venta_pagos`.

Riesgo:

- Si se necesita numeración legal, reemisión exacta, anulación, PDF firmado o auditoría fiscal, falta persistencia.

## 4. Estado de modelos

| Modelo | Estado | Observación |
|---|---|---|
| `User` | Incompleto | Tiene `rol`, pero faltan relaciones a reservas, ventas y favoritos. |
| `Role` | Correcto | Tiene `users`, casts y helpers de rol. |
| `Categoria` | Correcto | Tiene padre, hijos y prendas. |
| `Marca` | Correcto | Tiene prendas. |
| `Color` | Correcto | Tiene variantes. |
| `Talla` | Correcto | Tiene variantes. |
| `Temporada` | Correcto | Tiene prendas y casts de fechas. |
| `Prenda` | Correcto | Tiene categoría, marca, temporada, usuario, variantes, imágenes y favoritos. |
| `PrendaVariante` | Correcto | Tiene prenda, color, talla, inventario e imágenes. |
| `ImagenPrenda` | Correcto | Tiene prenda y variante. |
| `Inventario` | Correcto con observación | Tiene variante y casts. Falta relación a movimientos si se crea bitácora. |
| `Reserva` | Correcto con observación | Tiene cliente, responsable, detalles, pagos y verificador. Falta venta asociada. |
| `DetalleReserva` | Correcto | Tiene reserva y variante. |
| `ReservaPago` | Correcto | Tiene reserva y usuario verificador. |
| `Venta` | Correcto con observación | Tiene cliente, vendedor, detalles y pagos. Falta reserva origen y verificador directo si se desea. |
| `DetalleVenta` | Correcto | Tiene venta y variante. |
| `VentaPago` | Correcto | Tiene venta y usuario verificador. |
| `Favorito` | Correcto | Tiene usuario y prenda. |

## 5. Relaciones revisadas

Relaciones solicitadas y estado:

- `User -> rol`: existe.
- `User -> reservas`: falta como relación explícita.
- `User -> ventas`: falta como relación explícita.
- `User -> favoritos`: falta como relación explícita.
- `Reserva -> cliente`: existe.
- `Reserva -> responsable`: existe.
- `Reserva -> detalles`: existe.
- `Reserva -> pagos`: existe.
- `Reserva -> verificador`: existe como `usuarioVerificaPago`.
- `ReservaPago -> reserva`: existe.
- `ReservaPago -> verificador`: existe como `usuarioVerifica`.
- `Venta -> cliente`: existe.
- `Venta -> vendedor`: existe.
- `Venta -> detalles`: existe.
- `Venta -> pagos`: existe.
- `VentaPago -> venta`: existe.
- `VentaPago -> verificador`: existe como `usuarioVerifica`.
- `DetalleReserva -> reserva`: existe.
- `DetalleReserva -> variante`: existe.
- `DetalleVenta -> venta`: existe.
- `DetalleVenta -> variante`: existe.
- `PrendaVariante -> prenda`: existe.
- `PrendaVariante -> color`: existe.
- `PrendaVariante -> talla`: existe.
- `PrendaVariante -> inventario`: existe.
- `Inventario -> variante`: existe.

Relaciones funcionales recomendadas:

- `User::reservasCliente()`
- `User::reservasResponsable()`
- `User::reservasVerificadas()`
- `User::ventasCliente()`
- `User::ventasVendedor()`
- `User::pagosReservaVerificados()`
- `User::pagosVentaVerificados()`
- `User::favoritos()`
- `Reserva::venta()` si se agrega FK o tabla puente.
- `Venta::reservaOrigen()` si se agrega FK.

## 6. Campos faltantes o débiles

### Campos faltantes

| Tabla | Campo sugerido | Motivo | Prioridad |
|---|---|---|---|
| `ventas` | `cod_reserva_origen_ven` nullable FK a `reservas.id_reserva` | Relacionar venta generada desde reserva sin depender de `nve_ven`. | Alta |
| `recibos` | tabla nueva o campos de emisión | Persistir número, tipo, fecha, monto, snapshot y estado de recibos si se requiere trazabilidad. | Media |
| `inventario_movimientos` | tabla nueva | Auditar reservas, liberaciones, ventas, ajustes y correcciones. | Alta |
| `reservas` | campo opcional de motivo/código de cancelación o vencimiento | Diferenciar cancelación manual, rechazo de pago y vencimiento operativo. | Baja/Media |
| `ventas` | campo opcional de motivo de cancelación/incompleta | Registrar causa comercial sin depender solo de `venta_pagos.obs_vpg`. | Media |

### Campos débiles

- `reservas.est_res`: string libre, sin constraint.
- `reservas.est_pago_res`: string libre, sin constraint.
- `reserva_pagos.est_rpg`: string libre, sin constraint.
- `ventas.est_ven`: string libre, sin constraint.
- `venta_pagos.est_vpg`: string libre, sin constraint.
- `ventas.tiv_ven`: string libre, sin constraint.
- `ventas.mpa_ven` y métodos de pago de pagos: strings libres, sin tabla/catálogo.
- `inventarios.cdi_inv` y `inventarios.cre_inv`: no tienen constraint contra negativos a nivel DB.
- `detalle_reservas.can_dre` y `detalle_ventas.can_dve`: no tienen constraint DB `> 0`.

## 7. Riesgos detectados

### Críticos

- Migraciones duplicadas en `temporadas`: `2026_04_14_222922_add_business_fields_to_temporadas_table.php` y `2026_05_11_014318_add_period_fields_to_temporadas_table.php` agregan `fec_ini_tem` y `fec_fin_tem`. En una base limpia puede fallar la migración.
- Relación reserva-venta no persistida: los recibos y conciliación dependen del patrón de número `VEN-RES-{nre_res}`.
- Estados sin control de base de datos: una escritura errónea puede dejar reservas/ventas invisibles para reportes o acciones.

### Altos

- `User` incompleto para historial: falta navegación Eloquent directa a reservas, ventas y favoritos.
- Falta bitácora de inventario: no se puede auditar cómo cambió `cdi_inv`/`cre_inv`.
- `cod_usuario_vendedor_ven` nullable depende de migración con SQL PostgreSQL directo; no es portable.
- No hay persistencia de recibos si el negocio necesita comprobantes inmutables.

### Medios

- No hay constraints de cantidades no negativas o mayores a cero.
- Métodos de pago son strings libres.
- No hay relación directa de `Reserva` a comprobante principal; se consulta por pagos.
- El estado inicial viejo `activa` en `reservas.est_res` no coincide con el flujo actual.

## 8. Migraciones recomendadas

Estas migraciones son propuestas. No se implementaron.

### 1. Corregir duplicidad de columnas de temporada

- Tabla: `temporadas`
- Campo: `fec_ini_tem`, `fec_fin_tem`
- Tipo: `date nullable`
- Motivo: evitar que una instalación limpia intente crear columnas duplicadas.
- Riesgo si no se agrega/corrige: `php artisan migrate` puede fallar en entornos nuevos.
- Prioridad: Alta

Propuesta: consolidar las dos migraciones o hacer la segunda idempotente con `Schema::hasColumn` antes de agregar columnas.

### 2. Relacionar ventas con reservas

- Tabla: `ventas`
- Campo: `cod_reserva_origen_ven`
- Tipo: `foreignId nullable constrained reservas(id_reserva) nullOnDelete/restrict según regla de negocio`
- Motivo: trazabilidad directa de venta generada desde reserva.
- Riesgo si no se agrega: recibos, historial y conciliación dependen de formato de número.
- Prioridad: Alta

### 3. Crear bitácora de inventario

- Tabla: `inventario_movimientos`
- Campos sugeridos: `id`, `cod_inventario`, `cod_prenda_variante`, `tipo`, `cantidad`, `disponible_antes`, `disponible_despues`, `reservado_antes`, `reservado_despues`, `referencia_tipo`, `referencia_id`, `cod_usuario`, `observacion`, timestamps.
- Tipo: tabla nueva.
- Motivo: auditar ventas, reservas, liberaciones, vencimientos y ajustes.
- Riesgo si no se agrega: no se puede reconstruir historial de stock ni detectar discrepancias.
- Prioridad: Alta

### 4. Persistir recibos si se requiere trazabilidad legal

- Tabla: `recibos`
- Campos sugeridos: `id`, `tipo`, `numero`, `cod_reserva`, `cod_venta`, `cod_usuario_cliente`, `total`, `emitido_en`, `snapshot_json`, `estado`, timestamps.
- Tipo: tabla nueva.
- Motivo: guardar recibos emitidos como snapshot inmutable.
- Riesgo si no se agrega: un recibo cambia si cambian datos base o relaciones.
- Prioridad: Media

### 5. Agregar constraints de estados y cantidades

- Tabla: `reservas`, `ventas`, `reserva_pagos`, `venta_pagos`, `inventarios`, `detalle_reservas`, `detalle_ventas`
- Campo: estados y cantidades.
- Tipo: `check` constraints o validación DB equivalente.
- Motivo: impedir estados inválidos y cantidades negativas/cero.
- Riesgo si no se agrega: errores manuales o bugs pueden dejar datos incoherentes.
- Prioridad: Media/Alta

### 6. Normalizar métodos de pago

- Tabla: posible `metodos_pago`
- Campo: catálogo de método y estado.
- Tipo: tabla nueva o enum/check.
- Motivo: evitar variantes como `transferencia`, `transferencia_bancaria`, `qr`, `QR`.
- Riesgo si no se agrega: reportes fragmentados y validaciones duplicadas.
- Prioridad: Media

## 9. Prioridad de corrección

### Alta

- Corregir migraciones duplicadas de `temporadas`.
- Agregar vínculo persistente venta-reserva.
- Crear estrategia de auditoría de inventario.
- Centralizar estados y alinear defaults de migración con flujo actual.

### Media

- Completar relaciones inversas en `User`.
- Persistir recibos si el negocio requiere trazabilidad formal.
- Agregar constraints de cantidades y estados.
- Normalizar métodos de pago.

### Baja

- Agregar campos específicos de motivo para cancelación/vencimiento.
- Refinar nombres inconsistentes en mensajes y documentación interna.

## 10. Conclusión

La estructura actual sí soporta el MVP operativo de Mati Boutique, pero necesita endurecimiento antes de producción. El mayor problema no es ausencia masiva de tablas, sino trazabilidad y consistencia: migraciones duplicadas, estados libres, relación reserva-venta frágil y falta de bitácora de inventario.

Antes de desarrollar nuevos módulos, conviene aprobar un paquete pequeño de correcciones de esquema y relaciones Eloquent, empezando por migraciones de temporada, FK venta-reserva y auditoría de inventario.

