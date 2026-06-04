# 34. Pulido de seguridad y trazabilidad

## 1. Alcance

Esta fase aplica tres ajustes pequenos sin migraciones, sin paquetes nuevos, sin triggers y sin nuevos modulos:

- Proteger `POST /compras/online` con middleware `auth`.
- Ampliar metadata de logs para exportacion CSV.
- Ampliar metadata de logs para vista imprimible.
- Documentar politica futura de retencion y rotacion de backups.

## 2. Proteccion de /compras/online

La ruta mantiene el mismo nombre:

- `compras.online.store`

Cambio aplicado:

```php
Route::middleware('auth')
    ->post('/compras/online', [CompraOnlineController::class, 'store'])
    ->name('compras.online.store');
```

El controlador conserva su validacion interna como defensa adicional, pero un usuario no autenticado ya no debe llegar al `store`.

## 3. Metadata de exportacion CSV

`ReportExportService` mantiene el log `csv_exportado` y agrega:

```php
'filtros' => $this->reports->filtersMetadata($request)
```

La metadata queda con datos simples y relevantes, por ejemplo:

- `period`
- `date_from`
- `date_to`
- `seller`
- `payment_method`
- `sale_status`
- `reservation_status`
- `customer`
- `responsible`
- `section`

## 4. Metadata de vista imprimible

`ReportPrintService` mantiene el log `vista_imprimible_abierta` y agrega la misma metadata de filtros:

```php
'filtros' => $this->reports->filtersMetadata($request)
```

## 5. Servicio compartido de filtros

`ReportQueryService` expone `filtersMetadata(Request $request): array`.

El metodo normaliza valores simples, omite filtros vacios y evita duplicar codigo entre exportacion CSV y vista imprimible. La sanitizacion final sigue quedando a cargo de `SystemLogService`.

## 6. Backups

`DOCUMENTACION/21_copias_seguridad_sistema.md` ahora incluye la seccion `Politica futura de retencion y rotacion`.

No se implementa rotacion todavia.
