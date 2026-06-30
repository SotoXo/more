# Fase 8 - Panel vendedor: Mis reportes y notificaciones

## 1. Objetivo

La Fase 8 implementa la seccion `Mis reportes` del panel vendedor en `/vendedor/reportes`.

El objetivo es entregar un resumen operativo de lectura sobre ventas, reservas, pagos gestionados, recojos/entregas, reportes de prendas danadas y notificaciones operativas.

Esta fase no modifica datos comerciales, inventario ni Kardex.

## 2. Rutas implementadas

- `GET /vendedor/reportes` (`vendedor.reportes.index`): vista principal de reportes.
- `GET /vendedor/reportes/exportar` (`vendedor.reportes.export`): CSV del periodo filtrado.
- `GET /vendedor/reportes/imprimir` (`vendedor.reportes.print`): vista imprimible del periodo filtrado.

Todas las rutas estan bajo el grupo `auth` + `vendedor`.

## 3. Controlador y servicio

Se agrego `VendedorReportController`.

Se agrego `VendedorReportService`, responsable de:

- preparar filtros de fecha;
- construir tarjetas resumen;
- consultar tablas operativas;
- generar datos para graficos simples;
- exportar CSV;
- preparar vista de impresion;
- cargar notificaciones recientes del usuario autenticado.

El servicio solo realiza consultas. No llama servicios de inventario, ventas, reservas, pagos o Kardex que escriban datos.

## 4. Filtros disponibles

La vista permite filtrar por:

- fecha desde;
- fecha hasta.

Por defecto se muestra un rango de 30 dias, desde hoy menos 29 dias hasta hoy.

Los filtros se aplican tambien a CSV e impresion.

## 5. Tarjetas resumen

La pantalla muestra:

- Ventas realizadas.
- Total vendido.
- Reservas registradas.
- Reservas activas.
- Pagos confirmados.
- Comprobantes rechazados.
- Recojos realizados.
- Reportes de dano.
- Alertas de stock consultables.

No se muestran utilidades, ganancias globales, auditoria, backups, logs globales ni reportes financieros administrativos.

## 6. Tablas del reporte

La vista incluye tablas estilo planilla:

- Ventas del vendedor.
- Reservas del vendedor.
- Pagos gestionados.
- Recojos y entregas.
- Prendas danadas reportadas.

Para mantener la pantalla liviana, cada tabla muestra los registros mas recientes del periodo. El CSV incluye el periodo filtrado completo.

## 7. Graficos

No se instalaron librerias nuevas.

Se agregaron graficos simples con barras CSS:

- ventas por dia;
- reservas por estado;
- pagos por metodo;
- recojos por estado.

## 8. Exportacion e impresion

Se implemento CSV en `/vendedor/reportes/exportar`.

La exportacion respeta:

- filtros de fecha;
- alcance del usuario vendedor;
- supervision global si el usuario autenticado es superadmin.

Se implemento vista de impresion en `/vendedor/reportes/imprimir`, reutilizando los mismos datos filtrados.

No se implemento PDF.

## 9. Notificaciones integradas

No se creo un sistema paralelo.

Se reutiliza:

- `NotificationController`;
- rutas `/notificaciones`;
- `NotificationBell`;
- notificaciones de base de datos existentes.

La pagina muestra notificaciones operativas recientes y permite:

- marcar una notificacion como leida;
- marcar todas como leidas;
- abrir la URL asociada si existe.

Las notificaciones operativas existentes cubren eventos comerciales como pagos pendientes, reservas, ventas y recojos segun los servicios actuales.

## 10. Seguridad y alcance de datos

Para vendedor normal:

- ventas: solo `cod_usuario_vendedor_ven = usuario autenticado`;
- reservas: solo `cod_usuario_responsable_res = usuario autenticado`;
- pagos: solo pagos donde el usuario figura como verificador;
- recojos/entregas: solo operaciones donde el usuario figura como entregador;
- prendas danadas: solo `cod_usuario_reporta_pdr = usuario autenticado`;
- notificaciones: solo notificaciones del usuario autenticado.

Para superadmin entrando al panel vendedor:

- se mantiene modo supervision global;
- no se exponen auditoria critica, backups, logs globales ni reportes administrativos completos;
- el panel superadmin completo sigue disponible en `/superadmin`.

Cliente no accede porque las rutas estan protegidas por middleware `vendedor`.

## 11. Regla de no modificar stock

La Fase 8 es solo consulta:

- no modifica `cdi_inv`;
- no modifica `cre_inv`;
- no cambia estados logisticos;
- no cambia estados comerciales;
- no crea, confirma, rechaza ni cancela operaciones.

## 12. Regla de no tocar Kardex

No se registra Kardex ni se crean movimientos de inventario desde `Mis reportes`.

## 13. Pruebas manuales recomendadas

1. Iniciar sesion como vendedor y abrir `/vendedor/reportes`.
2. Confirmar que carga tarjetas resumen y tablas.
3. Filtrar por fechas y validar que el periodo cambia.
4. Revisar ventas y confirmar que solo son del vendedor autenticado.
5. Revisar reservas y confirmar que solo son del vendedor autenticado.
6. Revisar pagos gestionados y confirmar que no permite cambiar estados.
7. Revisar recojos/entregas y confirmar que no permite entregar desde reportes.
8. Revisar prendas danadas y confirmar que solo muestra reportes creados por el vendedor.
9. Exportar CSV y confirmar que respeta filtros.
10. Abrir impresion y confirmar que contiene los mismos datos filtrados.
11. Revisar notificaciones y marcar una o todas como leidas.
12. Iniciar sesion como cliente y confirmar que no accede.
13. Abrir `/superadmin/reportes` y confirmar que sigue funcionando.

## 14. Pendientes finales

- Agregar filtros avanzados por metodo/estado si el volumen operativo lo requiere.
- Agregar paginacion por tabla si se necesita revisar mas historial en pantalla.
- Crear notificaciones adicionales para reportes de prendas danadas actualizados si superadmin define ese flujo.
