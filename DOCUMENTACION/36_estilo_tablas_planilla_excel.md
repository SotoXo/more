# Estilo de tablas tipo planilla

## Observacion corregida

Se ajusto la UI de tablas administrativas para que se lean mas como una planilla tipo Excel: con lineas visibles entre filas y columnas, encabezados mas marcados y celdas mejor delimitadas.

El cambio es solo visual. No se modificaron consultas, rutas, permisos, controladores, migraciones, exportaciones ni logica de negocio.

## Componentes y vistas afectadas

El ajuste principal se aplico en:

- `resources/css/app.css`
- `resources/js/Components/Superadmin/SuperadminDataTable.tsx`

Ese componente se reutiliza en:

- listados CRUD del superadmin
- `ResourceIndex`
- tablas relacionadas de `ResourceShow`
- reportes operativos
- movimientos de inventario

Tambien se ajustaron tablas manuales en:

- `resources/js/Pages/Superadmin/Logs.tsx`
- `resources/js/Pages/Superadmin/Auditoria.tsx`
- `resources/js/Pages/Superadmin/Backups.tsx`
- `resources/js/Pages/Superadmin/ReportPrint.tsx`

## Estilo aplicado

Se agregaron clases reutilizables:

- `sa-table-wrapper`
- `sa-table-scroll`
- `sa-table`
- `sa-table-card-grid`
- `sa-table-card`
- `sa-table-card__field`

Las tablas ahora tienen:

- borde externo del contenedor
- separacion visible entre columnas
- separacion visible entre filas
- encabezado diferenciado
- zebra suave
- hover discreto por fila
- padding uniforme en celdas
- columnas numericas alineadas segun la configuracion existente
- acciones ordenadas al final de la fila

## Modo claro y oscuro

El estilo usa variables existentes del panel administrativo:

- `--admin-border`
- `--admin-surface`
- `--admin-surface-soft`
- `--admin-text`
- `--admin-muted`
- `--admin-accent`

Asi las lineas mantienen contraste en modo oscuro y se vuelven grises suaves en modo claro, sin romper la marca Mati Boutique.

## Responsive

En tablet y desktop, `SuperadminDataTable` muestra tabla completa con scroll horizontal cuando hace falta.

En movil, se conserva el patron de cards para no sacrificar usabilidad, pero cada campo queda delimitado como celda mediante `sa-table-card__field`.

Las tablas manuales de logs, auditoria y backups siguen el mismo criterio: tabla desde tablet y cards en movil.

## Vista imprimible

La vista imprimible de reportes mantiene formato Mati Boutique y ahora refuerza:

- bordes visibles en tabla
- encabezados diferenciados
- filas alternadas suaves
- celdas delimitadas para imprimir o guardar PDF desde navegador

## Restricciones respetadas

- No se crearon migraciones.
- No se instalaron paquetes.
- No se tocaron rutas.
- No se cambio logica de negocio.
- No se modifico `ResourceController`.
- No se ejecuto `npm run dev` ni `sail npm run dev`.

## Verificacion

Se ejecuto:

- `npm run build`

No correspondio `php -l` porque no se modificaron archivos PHP.
