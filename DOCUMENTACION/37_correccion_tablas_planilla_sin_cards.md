# Correccion de tablas tipo planilla sin cards duplicadas

## Observacion corregida

La observacion inicial era marcar mejor las lineas de las tablas para que se vean como planillas tipo Excel.

En el ajuste anterior, algunas vistas podian mostrar registros como cards y tabla al mismo tiempo, especialmente en Kardex de inventario. Eso alargaba la pantalla y duplicaba la informacion.

## Ajuste aplicado

Se revirtio el rediseño excesivo y se dejo el cambio limitado al estilo visual de tabla:

- una sola representacion por listado
- tabla tipo planilla desde tablet y desktop
- cards solo en pantallas moviles estrechas
- sin duplicar cards y tabla al mismo tiempo
- sin convertir cada celda en caja tipo formulario

## Componentes y vistas afectadas

Se ajustaron:

- `resources/css/app.css`
- `resources/js/Components/Superadmin/SuperadminDataTable.tsx`
- `resources/js/Pages/Superadmin/Logs.tsx`
- `resources/js/Pages/Superadmin/Auditoria.tsx`
- `resources/js/Pages/Superadmin/Backups.tsx`

El componente `SuperadminDataTable` cubre listados CRUD, Kardex de inventario, reportes operativos y tablas relacionadas que ya reutilizan el componente central.

## Kardex

En `/superadmin/inventario/movimientos`, los movimientos deben mostrarse como filas de una sola tabla tipo planilla desde tablet/desktop.

Las cards responsive quedan limitadas a pantallas moviles estrechas y no deben coexistir con la tabla.

## Estilo mantenido

Se mantienen lineas visibles y elegantes:

- borde externo
- borde entre columnas
- borde entre filas
- encabezado diferenciado
- hover suave
- scroll horizontal cuando la tabla no entra

## Responsive

- Desktop: solo tabla tipo planilla.
- Tablet: tabla tipo planilla con scroll horizontal.
- Movil estrecho: cards compactas si hace falta, sin tabla duplicada.

## Restricciones respetadas

- No se crearon migraciones.
- No se instalaron paquetes.
- No se tocaron rutas.
- No se modifico logica de negocio.
- No se modifico `ResourceController`.
- No se ejecutó `npm run dev` ni `sail npm run dev`.

## Verificacion

Comando requerido:

- `npm run build`

No corresponde `php -l` porque no se modificaron archivos PHP.
