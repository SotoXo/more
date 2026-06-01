# Auditoría general del sistema Mati Boutique

Fecha de auditoría: 2026-05-24  
Alcance: Laravel + React/TypeScript + Inertia.  
Restricciones respetadas: no se corrigió código funcional, no se crearon migraciones, no se tocó base de datos y no se ejecutó servidor de desarrollo.

## 1. Resumen ejecutivo

El sistema está en estado funcional parcial-avanzado. Existe una base sólida para catálogo público, bolsa de reservas, compra online, cuenta de cliente, panel superadmin, CRUDs principales, inventario, reservas, ventas, pagos, comprobantes y recibos. `npm run build` compila correctamente, por lo que no hay imports TypeScript rotos ni páginas Inertia faltantes detectables en build.

El riesgo principal antes de seguir desarrollando es operativo, no de compilación: hay inconsistencias de inventario y reportes que pueden mostrar stock incorrecto, enlaces administrativos rotos en reportes, métricas de reservas con estados antiguos y riesgo de sobreventa en compras online pendientes de revisión porque no separan ni descuentan stock hasta confirmación administrativa.

La auditoría no pudo obtener `route:list` real porque Sail reportó que Docker no está iniciado. Se revisaron rutas estáticamente en `routes/web.php`.

## 2. Verificaciones ejecutadas

### `./vendor/bin/sail artisan route:list`

Resultado: falló por entorno.

Salida exacta:

```text
Docker is not running.
```

Impacto: no se pudo validar la tabla real de rutas generada por Laravel. Se hizo revisión manual del archivo de rutas.

### `npm run build`

Resultado: exitoso.

Resumen de salida:

```text
> build
> tsc && vite build

vite v8.0.3 building client environment for production...
✓ 2866 modules transformed.
✓ built in 1.06s
```

Impacto: no se detectaron imports rotos, errores TypeScript ni páginas Inertia inexistentes durante compilación.

### `php -l`

Resultado: no ejecutado.

Motivo: `php` no está disponible directamente en WSL y Sail no puede usarse mientras Docker no esté iniciado.

Salida relevante:

```text
/bin/bash: line 1: php: command not found
```

## 3. Estado por módulo

| Módulo | Estado actual | Prioridad |
|---|---|---|
| Catálogo público | Con errores/riesgos | Alta |
| Carrito de compras | Incompleto/con riesgo | Alta |
| Bolsa de reservas | Funcional con riesgos | Alta |
| Checkout de compra directa | Funcional con riesgo crítico | Alta |
| Checkout de reserva | Funcional con riesgos | Alta |
| Mis reservas | Funcional parcial | Media |
| Historial de reservas | Funcional parcial | Media |
| Historial de compras | Funcional parcial | Media |
| Panel superadmin | Funcional con inconsistencias | Alta |
| CRUDs de catálogo | Funcional avanzado | Media |
| Inventario | Funcional con inconsistencia crítica | Alta |
| Reservas | Funcional avanzado con riesgos | Alta |
| Ventas | Funcional parcial/avanzado | Alta |
| Pagos y comprobantes | Funcional parcial | Alta |
| Recibos de reserva y venta | Funcional parcial | Media |
| Clientes | Incompleto como módulo propio | Media |
| Reportes operativos | Implementado con errores | Alta |

## 4. Revisión por módulo

### 1. Catálogo público

Estado actual: con errores/riesgos.

Qué funciona:

- Existen rutas públicas para `/`, `/catalogo`, `/mujer`, `/hombre`, `/novedades`, `/accesorios`, `/colecciones`, `/editorial` y `/producto/{id}`.
- Las páginas Inertia existen y compilan: `Home`, `Shop/Collection`, `Shop/Product`, `Shop/Editorial`.
- Hay filtros por temporada, género, marca, categoría y ubicación.
- Favoritos se integran en cards y detalle de producto.

Qué no funciona o puede fallar:

- El catálogo calcula stock disponible como `cdi_inv - cre_inv` en `ProductMapper`, pero los servicios de reserva ya mueven unidades desde `cdi_inv` hacia `cre_inv`. Si `cdi_inv` representa disponible real, el catálogo resta dos veces lo reservado y puede ocultar stock existente.
- El detalle de producto replica el mismo criterio en variantes.
- Si una prenda no tiene inventario o variantes activas, el usuario puede ver estados poco claros.

Qué falta:

- Definir una regla única de inventario: si `cdi_inv` es disponible o si es total físico.
- Estados visuales consistentes para variantes sin inventario.
- Pruebas de catálogo con stock reservado.

Riesgos:

- Productos aparecen agotados o con bajo stock aunque sí existan unidades disponibles.
- Pérdida de ventas/reservas por disponibilidad mal calculada.

### 2. Carrito de compras

Estado actual: incompleto/con riesgo.

Qué funciona:

- Existe carrito de compra directa en frontend usando `localStorage` (`mati_boutique_purchase_cart`).
- Rutas `/carrito` y `/checkout` existen.
- Permite cambiar cantidades, remover productos y vaciar carrito.

Qué no funciona o puede fallar:

- El carrito vive solo en cliente; no revalida ni sincroniza stock hasta enviar checkout.
- La cantidad máxima viene del stock calculado en pantalla, que puede estar subestimado por la regla `cdi_inv - cre_inv`.
- No hay endpoint server-side para consultar/actualizar carrito de compra directa antes del checkout.

Qué falta:

- Revalidación server-side antes de mostrar checkout o al menos antes de confirmar pago.
- Manejo explícito de carrito desactualizado si el stock cambió.

Riesgos:

- Cliente paga por productos que ya no estarán disponibles al momento de revisión.
- Carrito puede quedar obsoleto indefinidamente en `localStorage`.

### 3. Bolsa de reservas

Estado actual: funcional con riesgos.

Qué funciona:

- Existe `/bolsa` con flujo de selección, resumen y pago de anticipo.
- API de bolsa (`/api/carrito/agregar`, `PATCH`, `DELETE`, `DELETE /api/carrito`) maneja sesión.
- Confirmación `/bolsa/confirmar` requiere autenticación.
- La reserva valida cliente, comprobante, método de pago, anticipo mínimo 50% y stock.
- Al crear reserva, se reserva stock dentro de transacción.

Qué no funciona o puede fallar:

- Agregar a bolsa no requiere sesión; esto parece intencional, pero el frontend muestra mensaje de login ante 401/403 aunque la ruta permite sesión anónima.
- La disponibilidad mostrada al cliente puede estar mal por cálculo doble de stock.
- Si el usuario no es rol cliente, la confirmación falla, pero esto ocurre tarde.

Qué falta:

- Mensaje preventivo cuando el usuario autenticado no tiene rol cliente.
- Política clara de expiración automática de reservas vencidas.

Riesgos:

- Usuario avanza con bolsa y pago visual, pero falla al confirmar por rol.
- Stock visible incorrecto afecta la selección.

### 4. Checkout de compra directa

Estado actual: funcional con riesgo crítico.

Qué funciona:

- `/checkout` está protegido por `auth`.
- `POST /compras/online` crea una venta `en_revision`, guarda comprobante y detalle de venta.
- Valida pago exacto del 100%, método QR/transferencia y stock al momento del envío.

Qué no funciona o puede fallar:

- La compra online no descuenta ni reserva inventario al crear la venta `en_revision`.
- El stock se valida con lock, pero no se modifica; al terminar la transacción, otro cliente puede comprar la misma unidad.
- El descuento real de inventario ocurre recién al confirmar la venta online.

Qué falta:

- Separar stock para ventas online pendientes o definir estado de stock comprometido.
- Flujo para devolver/rechazar pagos cuando ya no hay stock al confirmar.
- Mostrar claramente al cliente que la compra queda en revisión y no garantiza stock hasta confirmación, si se mantiene el diseño actual.

Riesgos:

- Sobreventa o múltiples pagos por la misma unidad.
- Conflicto operativo: cliente paga 100%, pero admin no puede confirmar por falta de stock.

### 5. Checkout de reserva

Estado actual: funcional con riesgos.

Qué funciona:

- Pago de anticipo entre 50% y 100%.
- Comprobante obligatorio.
- Métodos QR y transferencia.
- Validaciones frontend y backend alineadas en lo principal.
- Reserva queda `por_procesar`, pago `pendiente_revision` y stock reservado.

Qué no funciona o puede fallar:

- Comprobante usa reglas `file|mimes` en backend; no siempre usa regla `image`.
- No hay validación visible de cuenta inactiva/rol cliente antes de enviar.
- No se verificó ejecución real por falta de Docker.

Qué falta:

- Pruebas end-to-end de creación de reserva con archivo.
- Mensajería de estados posteriores a revisión administrativa.

Riesgos:

- Rechazo tardío por rol/cuenta.
- Archivos con MIME válido pero no imagen real, según configuración de validación.

### 6. Mis reservas

Estado actual: funcional parcial.

Qué funciona:

- `/cuenta/reservas` muestra reservas actuales.
- Incluye estado de reserva, estado de pago, saldo, fechas de recojo y links a recibos cuando aplican.

Qué no funciona o puede fallar:

- Depende de estados hardcodeados: actuales (`por_procesar`, `confirmada`, `lista_recojo`) e históricos (`completada`, `recogida`, `cancelada`, `vencida`, `rechazada`).
- Si aparece un nuevo estado, puede desaparecer de ambas listas.

Qué falta:

- Normalizar todos los estados posibles en una fuente única.
- Pruebas de visualización por estado.

Riesgos:

- Reservas con estado nuevo/inconsistente no aparecen en cuenta.

### 7. Historial de reservas

Estado actual: funcional parcial.

Qué funciona:

- Está dentro de `/cuenta/reservas` como `historialReservas`.
- Lista reservas completadas, recogidas, canceladas, vencidas y rechazadas.

Qué no funciona o puede fallar:

- No existe ruta separada `/cuenta/reservas/historial`.
- La vista concentra reservas actuales, historial y compras, lo que puede crecer demasiado.

Qué falta:

- Separación UI/UX si el volumen aumenta.
- Paginación.

Riesgos:

- Carga lenta en clientes con muchas reservas.

### 8. Historial de compras

Estado actual: funcional parcial.

Qué funciona:

- `/cuenta/pedidos` existe y muestra ventas del cliente.
- `/cuenta/reservas` también recibe `compras`.
- Recibo de compra se habilita cuando venta está `registrada`.

Qué no funciona o puede fallar:

- Hay duplicidad conceptual entre `/cuenta/pedidos` y compras dentro de `/cuenta/reservas`.
- No hay paginación.
- Compra online `en_revision` no tiene recibo hasta confirmación.

Qué falta:

- Decidir si compras viven solo en `Pedidos` o también como bloque de reservas.
- Estados visuales claros para `en_revision`, `incompleta`, `cancelada`.

Riesgos:

- Confusión de cliente entre reserva convertida en venta y compra directa online.

### 9. Panel superadmin

Estado actual: funcional con inconsistencias.

Qué funciona:

- Panel `/superadmin` existe y usa `DashboardStatsService`.
- Navegación por Seguridad, Catálogo, Inventario, Comercial, Análisis.
- Middleware permite superadmin y restringe vendedor a flujo comercial.

Qué no funciona o puede fallar:

- Dashboard calcula stock disponible como `sum(cdi_inv) - sum(cre_inv)`, inconsistente con `InventoryStatusService`, que trata `cdi_inv` como disponible.
- Bajo stock y sin stock también usan `cdi_inv - cre_inv`.
- Estados de reservas activas en dashboard son amplios y no excluyen `rechazada`.

Qué falta:

- Unificar el cálculo de stock usando `InventoryStatusService`.
- Definir permisos más granulares para vendedores si no deben aprobar/rechazar pagos.

Riesgos:

- Dashboard puede mostrar alertas falsas de inventario.
- Vendedor puede operar más acciones comerciales de las deseadas.

### 10. CRUDs de catálogo

Estado actual: funcional avanzado.

Qué funciona:

- `ResourceController` centraliza index/create/show/edit/store/update/destroy.
- Existen recursos de roles, usuarios, categorías, marcas, colores, tallas, temporadas, prendas, variantes, imágenes y stock.
- Producto tiene formulario especializado con variantes, galería e inventario inicial.
- Validaciones de catálogos son fuertes en nombres, unicidad y dependencias.

Qué no funciona o puede fallar:

- El CRUD secundario de inventario tiene create deshabilitado para `inventario.stock`, pero edición parece posible por `supportsCrud`.
- `variant_rows.*.stock_inicial` exige mínimo 1; no permite crear variante agotada o preventa sin stock.
- El `ResourceController` depende de definiciones dinámicas: una ruta mal generada termina en 404 por comodín.

Qué falta:

- Pruebas de cada recurso con sus validaciones.
- Documentar qué CRUDs son principales y cuáles complementarios.

Riesgos:

- Cambios en ResourceController pueden afectar todo el panel.
- Errores de definición pueden romper módulos completos.

### 11. Inventario

Estado actual: funcional con inconsistencia crítica.

Qué funciona:

- Existe modelo `Inventario` con `cdi_inv`, `cre_inv`, ubicación y relación a variante.
- Reservas mueven disponible a reservado.
- Ventas físicas descuentan disponible.
- Completar reserva reduce reservado.
- Cancelar/rechazar/vencer reserva libera reservado a disponible.

Qué no funciona o puede fallar:

- Hay dos interpretaciones activas de stock:
  - `InventoryStatusService` y `Inventario::stockDisponible()` usan `cdi_inv` como disponible.
  - `ProductMapper`, `DashboardStatsService` y algunos reportes usan `cdi_inv - cre_inv`.
- Reportes enlazan inventario a `/superadmin/inventario/existencias/{id}`, pero el recurso real es `/superadmin/inventario/stock/{id}`.

Qué falta:

- Fuente única de verdad para disponible/reservado/total.
- Conciliación de datos existentes si se corrige la semántica.

Riesgos:

- Stock mal mostrado, alertas falsas y decisiones operativas incorrectas.
- Posibles ventas/reservas bloqueadas visualmente aunque exista stock.

### 12. Reservas

Estado actual: funcional avanzado con riesgos.

Qué funciona:

- Crear reserva desde cliente y superadmin.
- Validación de cliente activo rol cliente.
- Anticipo mínimo 50%.
- Comprobante pendiente de revisión.
- Aprobar/rechazar pago, cancelar, marcar lista para recojo, vencer, completar y convertir a venta.
- Acciones usan transacciones.

Qué no funciona o puede fallar:

- No hay tarea automática visible para vencer reservas.
- Reportes siguen buscando estado `activa`, pero los estados reales son `por_procesar`, `confirmada`, `lista_recojo`, etc.
- Algunas acciones usan prompts genéricos en UI para montos/metodos, sin componentes específicos de archivo salvo formulario de pago final.

Qué falta:

- Automatización o comando para reservas vencidas.
- Pruebas de transición de estados.
- Mapa único de estados.

Riesgos:

- Reservas vencidas siguen separando stock hasta acción manual.
- Métricas operativas no reflejan reservas reales.

### 13. Ventas

Estado actual: funcional parcial/avanzado.

Qué funciona:

- Venta manual/física desde superadmin descuenta inventario inmediatamente.
- Venta online queda `en_revision` y se confirma/rechaza/cancela.
- Venta desde reserva se genera al completar recojo.
- Números de venta y reserva se generan con prefijos diarios.

Qué no funciona o puede fallar:

- Venta online no reserva stock mientras está en revisión.
- Venta manual online desde superadmin puede quedar `en_revision` sin comprobante asociado si no se usa el flujo de compra online.
- No hay pago asociado para venta manual salvo campos de venta.

Qué falta:

- Modelo operativo claro para pagos de ventas físicas vs online.
- Separación de stock para ventas online pendientes.

Riesgos:

- Sobreventa.
- Ventas online incompletas sin conciliación de stock/pago.

### 14. Pagos y comprobantes

Estado actual: funcional parcial.

Qué funciona:

- `reserva_pagos` y `venta_pagos` existen como modelos.
- Comprobantes se guardan en disco público.
- Se registran montos declarados y verificados/confirmados.
- Admin puede aprobar o rechazar comprobantes.

Qué no funciona o puede fallar:

- Validación de comprobante usa principalmente `mimes`; convendría reforzar con `image`.
- No se observan políticas de retención, eliminación o auditoría de archivos.
- No hay vista dedicada de comprobantes separada del detalle operativo.

Qué falta:

- Estándar de seguridad para archivos subidos.
- Registro/auditoría formal de quién revisó cada pago en todas las variantes de flujo.

Riesgos:

- Archivos públicos expuestos si las URLs se comparten.
- Revisión manual sin trazabilidad suficiente para producción.

### 15. Recibos de reserva y venta

Estado actual: funcional parcial.

Qué funciona:

- Rutas admin y cliente para recibos de reserva y venta.
- Control de propiedad para recibos de cliente.
- Recibo de reserva se emite con pago confirmado.
- Recibo de venta se emite cuando venta está `registrada`.

Qué no funciona o puede fallar:

- Recibo usa vista Blade fuera de Inertia; está bien, pero requiere QA visual independiente.
- Venta relacionada a reserva se detecta por número `VEN-RES-{nre_res}`; si cambia la numeración, se rompe la relación.

Qué falta:

- Identificador explícito entre venta y reserva.
- Exportación PDF si será requisito operativo.

Riesgos:

- Relación reserva-venta frágil por depender del número.

### 16. Clientes

Estado actual: incompleto como módulo propio.

Qué funciona:

- Clientes existen como usuarios con rol `cliente`.
- Registro público asigna rol cliente.
- Superadmin puede gestionar usuarios.
- Reservas y ventas relacionan cliente.

Qué no funciona o puede fallar:

- No hay módulo CRM dedicado de clientes.
- No hay ficha cliente con historial consolidado de compras, reservas, pagos y favoritos desde superadmin.

Qué falta:

- Vista operativa de cliente.
- Filtros por cliente en reportes y comercial más visibles.

Riesgos:

- Atención al cliente depende de navegar entre usuarios, ventas y reservas.

### 17. Reportes operativos

Estado actual: implementado con errores.

Qué funciona:

- `/superadmin/reportes` existe.
- Reporta inventario, ventas, reservas y catálogo.
- Tiene filtros por fecha, vendedor, método, estado, cliente y responsable.

Qué no funciona o puede fallar:

- Métrica de reservas activas busca `est_res = activa`, estado que no corresponde al flujo actual.
- Reservas vencidas también se calculan contra `activa`.
- Link de inventario apunta a `/superadmin/inventario/existencias/{id}`, ruta inexistente.
- Link de reservas recientes apunta a `/superadmin/reservas/{id}`, ruta inexistente.

Qué falta:

- Actualizar reportes al mapa real de estados.
- Validar enlaces de filas.
- Agregar pruebas básicas de reportes.

Riesgos:

- Reportes dan números incorrectos.
- Clics desde reportes terminan en 404.

## 5. Rutas revisadas

No se detectaron duplicados evidentes por lectura estática en `routes/web.php`.

Rutas públicas principales:

- `GET /`
- `GET /catalogo`
- `GET /mujer`
- `GET /hombre`
- `GET /novedades`
- `GET /accesorios`
- `GET /producto/{id}`
- `GET /colecciones`
- `GET /editorial`
- `GET /bolsa`
- `GET /carrito`

Rutas autenticadas cliente:

- `POST /bolsa/confirmar`
- `GET /checkout`
- `GET /cuenta`
- `GET /cuenta/pedidos`
- `GET /cuenta/reservas`
- `GET /cuenta/favoritos`
- `GET /mis-reservas/{reserva}/recibo`
- `GET /mis-compras/{venta}/recibo`

Rutas API/session:

- `POST /api/carrito/agregar`
- `PATCH /api/carrito/{varianteId}`
- `DELETE /api/carrito/{varianteId}`
- `DELETE /api/carrito`
- `POST /api/favoritos/toggle`

Rutas superadmin:

- `GET /superadmin`
- `GET /superadmin/perfil`
- `GET /superadmin/reportes`
- `GET /superadmin/logs`
- `GET /superadmin/comercial/reservas/{reserva}/recibo`
- `GET /superadmin/comercial/ventas/{venta}/recibo`
- Rutas comodín `/{section}/{resource}` para ResourceController.

Rutas probablemente rotas detectadas:

- `/superadmin/inventario/existencias/{id}` en reportes. El recurso real es `/superadmin/inventario/stock/{id}`.
- `/superadmin/reservas/{id}` en reportes. El recurso real es `/superadmin/comercial/reservas/{id}`.
- Componentes antiguos `Navigation.jsx` referencian `/cart`, pero no parecen estar en uso dentro del flujo actual compilado principal.

## 6. Debilidades detectadas

- Semántica inconsistente de inventario (`cdi_inv` vs `cdi_inv - cre_inv`).
- Compra online no separa stock durante revisión.
- Reportes usan estados antiguos (`activa`) incompatibles con estados actuales.
- Enlaces rotos dentro de reportes.
- Cliente no tiene módulo operativo propio.
- No hay paginación visible en historial de reservas/compras.
- Comprobantes requieren endurecimiento de validación y política de almacenamiento.
- Permisos de vendedor son amplios para todo recurso comercial.
- Falta una fuente centralizada de estados de reserva, venta y pago.

## 7. Riesgos críticos antes de producción

1. Sobreventa en compra online: el cliente paga 100%, pero el stock no se separa hasta aprobación administrativa.
2. Stock visible incorrecto por doble resta de reservado en catálogo/dashboard/reportes.
3. Reportes operativos con cifras incorrectas por estados obsoletos.
4. Enlaces 404 desde reportes.
5. Reservas vencidas no parecen cambiar estado automáticamente ni liberar stock sin acción manual.
6. Relación reserva-venta basada en número de venta, no en foreign key.

## 8. Pendientes funcionales

- Definir política de stock para compras online en revisión.
- Crear o consolidar módulo de clientes.
- Separar historial de compras/reservas si el volumen crece.
- Agregar vista/panel de comprobantes si administración lo necesita.
- Definir vencimiento automático/manual de reservas.
- Mejorar estados visibles para compras `en_revision`, `incompleta`, `cancelada`.
- Añadir paginación en cuenta cliente y panel si hay muchos registros.

## 9. Pendientes técnicos

- Unificar cálculo de inventario en un único servicio.
- Actualizar reportes a estados reales.
- Corregir URLs de reportes.
- Agregar pruebas de flujos críticos: reserva, pago, rechazo, cancelación, venta online, conversión reserva-venta.
- Agregar pruebas de autorización por rol.
- Revisar uploads con regla `image`, tamaño, storage y acceso público.
- Revisar si `public/build` debe estar versionado o ignorado según estrategia de despliegue.
- Ejecutar `route:list` real cuando Docker/Sail esté disponible.

## 10. Errores encontrados

### Críticos

- Compra online no reserva/descuenta stock al crear venta `en_revision`.
- Cálculo de stock inconsistente: catálogo y dashboard pueden restar reservado dos veces.
- Reportes de reservas usan estado `activa`, pero el flujo usa `por_procesar`, `confirmada`, `lista_recojo`, `completada`, `cancelada`, `vencida`, `rechazada`.

### Altos

- Links rotos en reportes:
  - `/superadmin/inventario/existencias/{id}`
  - `/superadmin/reservas/{id}`
- Métricas de bajo stock/sin stock en dashboard no coinciden con `InventoryStatusService`.
- Reservas vencidas dependen de acción manual visible.

### Medios

- Cliente sin módulo dedicado.
- Duplicidad de presentación de compras entre `Pedidos` y `Reservas`.
- Falta paginación.
- Validación de comprobantes puede endurecerse.

## 11. Recomendaciones

1. Corregir primero la semántica de inventario y documentarla: `cdi_inv` disponible, `cre_inv` reservado, total físico `cdi_inv + cre_inv`.
2. Cambiar compra online para separar stock al crear venta `en_revision` o crear un estado de stock comprometido.
3. Corregir reportes: estados reales y URLs rotas.
4. Agregar pruebas de integración para reservas, ventas y pagos antes de nuevos módulos.
5. Auditar permisos de vendedor y decidir si puede aprobar/rechazar pagos.
6. Crear un mapa centralizado de estados para reservas, ventas y pagos.
7. Reintentar `./vendor/bin/sail artisan route:list` cuando Docker esté disponible.

## 12. Prioridad de corrección

### Alta

- Inventario: unificar cálculo.
- Compra online: reservar/comprometer stock.
- Reportes: corregir estados y rutas.
- Reservas vencidas: definir proceso de vencimiento y liberación.
- Permisos: validar alcance de vendedor.

### Media

- Clientes: ficha o módulo propio.
- Historiales: paginación y separación.
- Comprobantes: endurecer validación y política de almacenamiento.
- Recibos: relación explícita reserva-venta.

### Baja

- Limpieza de componentes legacy no usados.
- Ajustes de copy/acentos en mensajes.
- Mejoras UI/UX de formularios con prompts administrativos.

