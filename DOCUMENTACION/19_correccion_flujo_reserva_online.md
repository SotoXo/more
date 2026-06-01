# Correccion del flujo de reserva online

## Objetivo

El flujo publico de reservas de Mati Boutique queda organizado como una reserva con proceso de compra online:

1. Seleccion de prendas desde el catalogo.
2. Revision de la bolsa con las prendas para reservar.
3. Resumen de reserva con cantidades, subtotales, total, minimo y maximo permitido.
4. Pago de anticipo con metodo de pago, datos de pago y comprobante.
5. Confirmacion final de la reserva.

La reserva real se crea solo en el paso final, cuando el cliente confirma con monto declarado, metodo permitido y comprobante.

## Validacion de monto

La regla de pago online es:

- Minimo permitido: 50% del total de la reserva.
- Maximo permitido: 100% del total de la reserva.
- No se permite confirmar con un monto menor al 50%.
- No se permite confirmar con un monto mayor al total.

Mensajes visibles:

- `El anticipo minimo para reservar es del 50% del total.`
- `El monto no puede ser mayor al total de la reserva.`

Esta validacion existe en frontend y en backend mediante `ReservationService::validateCreate`.

## Metodos de pago

Para la reserva online solo se aceptan:

- `qr`: QR.
- `transferencia_bancaria`: Transferencia bancaria.

No se muestran ni se aceptan efectivo, tarjeta, otro metodo ni pago en tienda para este flujo.

La configuracion visible del frontend esta centralizada en:

- `resources/js/config/paymentMethods.ts`

## QR de pago

El QR fijo de Mati Boutique se lee como recurso publico:

- Archivo esperado: `public/images/payments/qr-mati-boutique.png`
- URL publica: `/images/payments/qr-mati-boutique.png`

Cuando el cliente selecciona QR, la interfaz muestra la imagen y el texto:

- `Escanea el QR y sube el comprobante de pago.`

El QR no se guarda en base de datos.

## Transferencia bancaria

Cuando el cliente selecciona transferencia bancaria, la interfaz muestra:

- Cuenta bancaria Mati Boutique
- Titular: `LIMBER SOTO CAZU`
- Nro. de cuenta: `1003383438`

## Correccion al agregar prendas

El error de Inertia podia aparecer porque la bolsa usaba acciones de Inertia contra endpoints que devuelven JSON (`/api/carrito/*`).

Correccion aplicada:

- Agregar prendas desde producto sigue usando `axios`.
- Actualizar cantidad y quitar prendas desde la bolsa ahora usan `axios`.
- La confirmacion final de la reserva mantiene `useForm().post('/bolsa/confirmar')`, porque esa accion devuelve redirect Inertia con mensajes.

Asi se evita el error:

`All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.`

## Cuenta obligatoria

La bolsa y la confirmacion de reserva mantienen autenticacion obligatoria:

- Sin login no se puede acceder a `/bolsa`.
- El cliente autenticado reserva para su propio usuario.
- El backend valida que el usuario exista, este activo y tenga rol cliente.

## Comprobante

El comprobante es obligatorio en la confirmacion final:

- Formatos permitidos: jpg, jpeg, png, webp.
- Tamano maximo: 4 MB.
- Mensaje visible si falta: `Sube el comprobante de pago.`

## Pruebas realizadas

Verificacion tecnica solicitada:

- `./vendor/bin/sail artisan route:list`
- `npm run build`
- `php -l` en archivos PHP modificados.

Pruebas manuales recomendadas:

- Cliente sin login intenta reservar.
- Cliente con login agrega una prenda.
- Cliente agrega otra prenda y no aparece error Inertia.
- Cliente revisa resumen antes de pagar.
- Monto menor al 50% muestra error.
- Monto mayor al 100% muestra error.
- Metodo QR muestra `/images/payments/qr-mati-boutique.png`.
- Metodo transferencia muestra titular y numero de cuenta.
- Confirmar sin comprobante muestra error.
- Confirmar correctamente crea reserva por procesar, pago pendiente y mueve inventario.
