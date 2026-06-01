# Auditoria y correccion del modulo de reservas

## Correccion aplicada

Se audito el flujo de reservas para evitar errores 500, reservas sin cuenta, clientes invalidos y movimientos incorrectos de inventario.

## Cuenta obligatoria

- Sin login no se puede iniciar el flujo de reserva desde storefront.
- La ruta `/bolsa` y las rutas `/api/carrito/*` ahora requieren autenticacion.
- Si una persona no autenticada presiona reservar desde producto, se informa: "Debes iniciar sesion para realizar una reserva." y se redirige a login.
- En storefront solo una cuenta con rol `cliente` activo puede usar la bolsa de reservas.
- En superadmin/vendedor se debe seleccionar un cliente valido y activo.

## Validaciones principales

Al crear reserva desde superadmin se valida:

- usuario autenticado;
- cliente obligatorio, activo y con rol cliente;
- al menos una prenda;
- cantidad mayor a 0;
- inventario existente;
- stock suficiente;
- comprobante obligatorio;
- monto declarado obligatorio y mayor a 0;
- anticipo minimo del 50%;
- metodo de pago obligatorio.

## Estados revisados

Estados validos de reserva:

- `por_procesar`
- `confirmada`
- `lista_recojo`
- `completada`
- `cancelada`
- `vencida`
- `rechazada`

Estados validos de pago:

- `pendiente_revision`
- `anticipo_confirmado`
- `pagado_total`
- `rechazado`

Estados validos de `reserva_pagos`:

- `pendiente_revision`
- `confirmado`
- `rechazado`

## Inventario

Se corrigio la lectura de disponible: `cdi_inv` representa stock disponible y `cre_inv` representa stock reservado. No debe calcularse disponible como `cdi_inv - cre_inv`.

Movimientos:

- Crear reserva: baja `cdi_inv`, sube `cre_inv`.
- Cancelar, rechazar o vencer: sube `cdi_inv`, baja `cre_inv`.
- Completar: baja `cre_inv` y no vuelve a tocar `cdi_inv`.

La liberacion y completado validan que exista stock reservado suficiente para evitar negativos o dobles movimientos.

## Comprobante

El comprobante sigue siendo obligatorio para crear reserva o registrar pago posterior:

- formatos: jpg, jpeg, png, webp;
- maximo: 4 MB;
- almacenamiento: disco publico;
- guarda ruta y URL en `reserva_pagos`;
- si falla el guardado, retorna validacion clara y no error 500.

## Pruebas realizadas

- Lint PHP de archivos modificados.
- `./vendor/bin/sail artisan route:list`.
- `npm run build`.

`./vendor/bin/sail artisan test` queda bloqueado por una migracion previa de temporadas con columna duplicada `fec_ini_tem`, no por reservas.
