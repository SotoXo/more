# Fase 3B - Campana y dropdown de notificaciones

## 1. Componente creado

Se creo el componente reutilizable:

- `resources/js/Components/Notifications/NotificationBell.tsx`

El componente se encarga de:

- Cargar el resumen con `GET /notificaciones/resumen`.
- Mostrar contador de notificaciones no leidas.
- Abrir y cerrar el dropdown.
- Mostrar ultimas notificaciones.
- Marcar una notificacion como leida.
- Marcar todas las notificaciones como leidas.
- Navegar a la URL de la notificacion cuando existe.
- Manejar errores sin romper la pantalla.

El componente usa `axios` contra endpoints JSON y no usa Inertia router para estas acciones.

## 2. Integracion en storefront

Se integro la campana en:

- `resources/js/Components/landing/LandingHeader.tsx`

La campana se muestra solo si existe usuario autenticado. En desktop queda junto a carrito, bolsa y cuenta. En movil queda dentro del panel de acciones principales.

No se muestra campana para visitantes anonimos.

## 3. Integracion en panel administrativo

Se integro la campana en:

- `resources/js/Components/Superadmin/SuperadminTopbar.tsx`

La campana se muestra para superadmin/vendedor autenticado en el topbar administrativo. Se agrego soporte visible para escritorio, tablet y movil sin tocar el sidebar ni ResourceController.

## 4. Endpoints usados

El componente usa:

- `GET /notificaciones/resumen`
- `POST /notificaciones/{id}/leer`
- `POST /notificaciones/leer-todas`

Todos devuelven JSON y estan protegidos por `auth` desde backend.

## 5. Marcar una notificacion como leida

Al hacer clic en una notificacion:

1. Se llama a `POST /notificaciones/{id}/leer`.
2. Se actualiza el contador local.
3. Se marca visualmente como leida.
4. Si la notificacion tiene `url`, se navega con `window.location.assign`.

## 6. Marcar todas como leidas

El boton `Leer todas` llama a:

- `POST /notificaciones/leer-todas`

Luego:

- El contador baja a `0`.
- Todas las notificaciones visibles quedan marcadas como leidas.

## 7. Manejo de usuario no autenticado

La campana no se renderiza si no hay usuario autenticado.

Si el endpoint responde `401`, el componente:

- Limpia el contador.
- Limpia la lista.
- Cierra el dropdown.
- No rompe la pagina.

## 8. Pendientes

- Crear una pagina completa de notificaciones si el negocio la necesita.
- Agregar polling moderado o tiempo real con broadcast si se decide despues.
- Mostrar filtros por tipo/severidad en una pantalla completa futura.
- Revisar textos finales cuando se conecte la campana con pruebas de usuarios reales.
