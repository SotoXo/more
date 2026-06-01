# Correccion de pantalla blanca por Vite

## Problema

La aplicacion quedaba en blanco aunque el documento principal de Laravel respondia `200`.

En Network fallaban recursos del frontend como:

- `@vite/client`
- `resources/js/app.tsx`
- `@react-refresh`

## Causa

Existia el archivo `public/hot` con el contenido:

```text
http://localhost:5173
```

Cuando ese archivo existe, Laravel asume que debe cargar los assets desde el servidor de desarrollo de Vite.

La auditoria mostro que el puerto `5173` estaba publicado por Docker, pero dentro del contenedor no habia un servidor Vite atendiendo `http://127.0.0.1:5173/@vite/client`.

Por eso Laravel generaba etiquetas para el dev server, pero el navegador no podia cargar los scripts de React/Inertia.

## Correccion aplicada

Se elimino `public/hot` para que Laravel vuelva a usar los assets compilados desde `public/build/manifest.json`.

No se ejecuto `npm run dev` ni `sail npm run dev`.

## Verificacion tecnica

- `npm run build` pasa correctamente, por lo que no habia error de TypeScript, import roto ni componente React mal exportado.
- `resources/js/app.tsx` mantiene `createInertiaApp` y el resolver de paginas correcto.
- Las paginas nuevas `CarritoCompra` y `Checkout` tienen export default y son resolubles por Inertia.
- Las rutas `/carrito`, `/checkout`, `/compras/online` y `/cuenta/reservas` existen.

## Recomendacion operativa

Si se desea trabajar con HMR, iniciar nuevamente Vite con `sail npm run dev`.

Si no se esta usando HMR, no debe quedar `public/hot`, porque Laravel intentara cargar scripts desde Vite aunque ya exista un build valido.
