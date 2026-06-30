# 40. Panel vendedor base

## 1. Objetivo

La fase 1 crea la base del panel vendedor de Mati Boutique. El objetivo es separar una experiencia operativa para el trabajo diario del vendedor sin implementar todavia ventas, reservas, pagos, confirmacion de comprobantes, recojos reales ni movimientos de inventario.

El panel queda preparado para fases posteriores:

- Consulta de prendas.
- Consulta de existencias.
- Ventas.
- Reservas.
- Pagos.
- Revision de comprobantes subidos por clientes.
- Recojos y entregas.
- Reporte de prendas daniadas.
- Reportes del vendedor.

## 2. Diferencia entre superadmin y vendedor

El superadmin conserva el panel `/superadmin`, con modulos administrativos, catalogo, seguridad, inventario completo, reportes globales, logs, auditoria y backups.

El vendedor usa `/vendedor`, con una navegacion limitada a operacion diaria. No ve el sidebar superadmin ni enlaces a roles, usuarios, categorias, marcas, colores, tallas, temporadas, Kardex completo, logs, auditoria, backups ni configuracion avanzada.

## 3. Rutas creadas

Todas las rutas estan protegidas con `auth` y `vendedor`.

- `GET /vendedor` -> `vendedor.dashboard`
- `GET /vendedor/prendas` -> `vendedor.prendas.index`
- `GET /vendedor/existencias` -> `vendedor.existencias.index`
- `GET /vendedor/alertas-stock` -> `vendedor.alertas-stock.index`
- `GET /vendedor/ventas` -> `vendedor.ventas.index`
- `GET /vendedor/reservas` -> `vendedor.reservas.index`
- `GET /vendedor/pagos` -> `vendedor.pagos.index`
- `GET /vendedor/recojos` -> `vendedor.recojos.index`
- `GET /vendedor/prendas-daniadas` -> `vendedor.prendas-daniadas.index`
- `GET /vendedor/reportes` -> `vendedor.reportes.index`

## 4. Layout creado

Archivo principal:

- `resources/js/Layouts/VendedorLayout.tsx`

El layout incluye:

- Sidebar vendedor independiente.
- Topbar.
- Nombre del usuario autenticado.
- Rol visible.
- Boton de cerrar sesion.
- Soporte de modo claro/oscuro con `mati-vendedor-theme`.
- Navegacion responsive para desktop y mobile.
- Estetica alineada a las variables visuales `sa-*` existentes de Mati Boutique.

## 5. Sidebar creado

Archivo de configuracion:

- `resources/js/lib/vendedor.ts`

Menu creado:

- Panel: Resumen.
- Catalogo: Consultar prendas.
- Inventario: Existencias, Alertas de stock, Reportar prenda daniada.
- Comercial: Ventas, Reservas, Pagos, Recojos / entregas.
- Analisis: Mis reportes.

No se reutiliza el sidebar completo de superadmin.

## 6. Paginas base creadas

- `resources/js/Pages/Vendedor/Dashboard.tsx`
- `resources/js/Pages/Vendedor/Products/Index.tsx`
- `resources/js/Pages/Vendedor/Inventory/Stock.tsx`
- `resources/js/Pages/Vendedor/Inventory/StockAlerts.tsx`
- `resources/js/Pages/Vendedor/Sales/Index.tsx`
- `resources/js/Pages/Vendedor/Reservations/Index.tsx`
- `resources/js/Pages/Vendedor/Payments/Index.tsx`
- `resources/js/Pages/Vendedor/Pickups/Index.tsx`
- `resources/js/Pages/Vendedor/DamagedProducts/Index.tsx`
- `resources/js/Pages/Vendedor/Reports/Index.tsx`
- `resources/js/Pages/Vendedor/Partials/VendedorModulePage.tsx`

Las paginas base muestran un mensaje claro de fase pendiente y no ejecutan operaciones reales.

## 7. Control de acceso aplicado

Se agrego `App\Http\Middleware\EnsureVendedor` y el alias `vendedor`.

Reglas:

- Usuario autenticado con rol `vendedor` puede acceder a `/vendedor`.
- Usuario autenticado con rol `superadmin` tambien puede acceder a `/vendedor` para supervision.
- Usuario con rol `cliente` no puede acceder a `/vendedor`.
- Usuario no autenticado es redirigido por `auth` al login.
- Usuario inactivo o con rol invalido es desconectado y enviado al login.
- El vendedor que intente entrar a `/superadmin` es redirigido a `/vendedor`.
- El superadmin mantiene acceso normal a `/superadmin`.

## 8. Modulos visibles para vendedor

- Resumen.
- Consultar prendas.
- Existencias.
- Alertas de stock.
- Ventas.
- Reservas.
- Pagos.
- Recojos / entregas.
- Reportar prenda daniada.
- Mis reportes.

## 9. Modulos no visibles para vendedor

- Roles.
- Usuarios.
- Categorias.
- Marcas.
- Colores.
- Tallas.
- Temporadas.
- Kardex / movimientos completos.
- Registro de actividad global.
- Auditoria critica.
- Copias de seguridad.
- Configuracion avanzada.
- Reportes administrativos globales.

## 10. Regla de pagos para fases posteriores

Esta regla queda documentada, pero no implementada en fase 1.

Pago registrado por vendedor:

- Puede ser efectivo, QR o transferencia.
- Se considera confirmado automaticamente.
- No requiere revision del administrador.
- Debe registrar quien confirmo el pago.

Comprobante subido por cliente desde compra online:

- Queda pendiente de revision.
- Administrador o vendedor pueden revisar el comprobante.
- Administrador o vendedor pueden aprobarlo o rechazarlo.
- Si se aprueba, recien se marca como confirmado.
- Si se rechaza, debe quedar observacion del rechazo.

Resumen:

- Pago registrado por vendedor = confirmado.
- Comprobante subido por cliente = pendiente de revision.
- Comprobante de cliente = revisado por admin o vendedor.

## 11. Pruebas manuales

1. Iniciar sesion como vendedor y abrir `/vendedor`.
   Esperado: carga el dashboard vendedor.

2. Iniciar sesion como cliente y abrir `/vendedor`.
   Esperado: no tiene acceso y vuelve al dashboard/cuenta.

3. Iniciar sesion como superadmin y abrir `/superadmin`.
   Esperado: el panel superadmin sigue cargando.

4. Iniciar sesion como superadmin y abrir `/vendedor`.
   Esperado: puede entrar para supervision.

5. Revisar sidebar vendedor.
   Esperado: solo muestra modulos operativos.

6. Abrir cada ruta `/vendedor/*`.
   Esperado: ninguna devuelve 404 o 500.

7. Probar modo claro/oscuro.
   Esperado: el layout mantiene coherencia visual.

8. Ejecutar `./vendor/bin/sail artisan route:list`.
   Esperado: aparecen las rutas `vendedor.*`.

9. Ejecutar `npm run build`.
   Esperado: build exitoso.

## 12. Pendientes

- Implementar consultas reales de prendas para vendedor.
- Implementar consulta segura de existencias sin cambiar semantica de inventario.
- Implementar alertas de stock sin recalcular `cdi_inv - cre_inv`.
- Implementar ventas reales reutilizando servicios existentes.
- Implementar reservas reales reutilizando servicios existentes.
- Implementar pagos y revision de comprobantes con la regla documentada.
- Implementar recojos / entregas.
- Implementar reporte real de prendas daniadas.
- Implementar reportes del vendedor filtrados por alcance operativo.
- Agregar pruebas automatizadas de acceso por rol si se define una suite de feature tests para este panel.

## 13. Inventario protegido

Esta fase no modifica inventario, no crea migraciones, no toca Kardex y no cambia la semantica:

- `cdi_inv` = stock disponible real.
- `cre_inv` = stock comprometido/separado.

No se recalcula disponible como `cdi_inv - cre_inv`.
