# Fase 4 - Ventas responsivas y logistica de recojo

## 1. Objetivo

Se mejoro el modulo de ventas para separar informacion comercial de informacion logistica, reducir columnas en el listado y habilitar acciones operativas de recojo/entrega sin tocar inventario ni crear migraciones.

## 2. Listado de ventas

El listado de ventas ahora usa columnas esenciales en desktop:

- Cliente
- Total
- Estado venta
- Estado recojo
- Fecha
- Accion

La informacion secundaria se mantiene disponible en el detalle:

- Vendedor / verificador
- Tipo de venta
- Productos completos
- SKU
- Metodo de pago
- Comprobante
- Recibo
- Cantidades detalladas

## 3. Tabla desktop y cards movil

`SuperadminDataTable` ahora mantiene tabla en pantallas grandes y usa cards responsivas en tablet/movil.

Las cards muestran:

- Numero de venta
- Cliente
- Total
- Estado comercial
- Estado logistico
- Fecha
- Boton `Ver detalle`

Esto evita scroll horizontal incomodo en pantallas medianas o pequenas.

## 4. Estados comerciales

Los estados comerciales de `ventas.est_ven` se muestran con etiquetas legibles:

- `en_revision` => En revision
- `registrada` => Venta confirmada
- `incompleta` => Venta incompleta
- `cancelada` => Venta cancelada

## 5. Estados logisticos

Los estados logisticos de `ventas.est_logistico_ven` se muestran separados:

- `pendiente_revision` => Pendiente de revision
- `pendiente_preparacion` => Pendiente de preparacion
- `pendiente_traslado` => Pendiente de traslado
- `lista_recojo` => Lista para recojo
- `entregada` => Entregada
- `cancelada` => Cancelada

## 6. Detalle de venta

El detalle de venta muestra secciones mas claras:

- Resumen de venta.
- Pago.
- Recojo / entrega.
- Productos vendidos como tabla relacionada.
- Recibo cuando corresponde.

La seccion de recojo muestra:

- Origen de stock.
- Punto de recojo.
- Fecha disponible.
- Fecha limite.
- Fecha de entrega.
- Usuario que entrego.

## 7. Acciones logisticas

Se creo `App\Services\Superadmin\SalePickupService`.

Acciones:

- `markReadyForPickup(Venta $venta, User $user)`
- `markDelivered(Venta $venta, User $user)`

Validaciones:

- La venta debe estar `registrada`.
- Solo se puede marcar lista para recojo si esta `pendiente_traslado` o `pendiente_preparacion`.
- Solo se puede marcar entregada si esta `lista_recojo`.
- No se permite entregar ventas en revision, incompletas, canceladas o ya entregadas.
- No se modifica inventario.

## 8. Rutas agregadas

Rutas protegidas por `auth` y middleware administrativo:

- `POST /superadmin/comercial/ventas/{venta}/lista-recojo`
- `POST /superadmin/comercial/ventas/{venta}/entregar`

Ambas devuelven `redirect()->back()` con flash para evitar respuestas JSON en acciones Inertia.

## 9. Notificaciones

Se reutiliza `CommercialNotificationService`.

Al marcar lista para recojo:

- Se notifica al cliente: `Tu compra ya esta lista para recojo.`

Al marcar entregada:

- Se notifica al cliente: `Tu compra fue entregada correctamente.`

## 10. Pendientes

- Validar visualmente en navegadores reales el ancho de cards en distintos dispositivos.
- Agregar filtros especificos por estado logistico si el equipo operativo los necesita.
- Crear vinculo persistente venta-reserva si se decide formalizar trazabilidad completa.
- Mantener monitoreo para evitar que vistas antiguas dependan de columnas tecnicas del listado.
