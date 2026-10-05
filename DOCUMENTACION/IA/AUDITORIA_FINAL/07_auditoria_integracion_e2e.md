# Auditoría de integración end-to-end

Fecha local: 2026-10-04. La fuente se inspeccionó y explicó antes de ejecutar validación. Se diferencian fuente, assets servidos, pruebas unitarias con mocks, HTTP real y navegación.

## Build y versión servida

En baseline el manifest servido estaba desfasado: ProductCreate-Br6F6_ny.js, SHA256 b46b5faa4a834fdacd1a5d854a65216f2c4dc17403bf12cb61caad2f8f790b34, mtime26/09, no contiene siquiera el Asistente inteligente posterior. public/hot ausente; Blade usa @vite. Evidencia storage/app/ai-audit/frontend_prebuild.json y frontend_prebuild_manifest.json.

Se ejecutó npm run build normal por instrucción expresa apartado53. WSL falló EACCES por assets root:root; retry dentro del contenedor principal pasó sin cambios en código ni permisos. Manifest actual ProductCreate-tYfO0olu.js, SHA256 f6eb4220d78745726c7016330fc3dfe2c081d07850125ae523ff675275551ffc. HTTP real servido200/140221bytes con hash idéntico al disco, frontend_http_asset.json. Source/build actual coinciden en módulos IA inspeccionados. No se considera la discrepancia inicial como inexistente por haber compilado durante la auditoría.

## Pruebas de integración existentes

| Capa | Ejecución real | Alcance |
|---|---|---|
| Helpers React | 23/23 pass,16 de IA | Reglas/campos/URLs/cola, no render ni requests HTTP |
| Cliente Laravel IA | 7/7 pass,13 assertions | Http::fake: auth/correlation/envelope/fallback, no FastAPI real |
| Workflow Laravel IA | No ejecutados14 | Fatal helper run final PHPUnit12; suite estándar aborta255 |
| Otras suites Laravel | 95/95 pass493 assertions | Excluyendo sólo archivo no cargable mediante config audit, PostgreSQL aislado; algunas usan SQLite |
| Feedback duplicado | 2misma decisión→2form reviews | Probe sintético DB aislada rollback; predicción sin sobrescritura |

Causa/impacto/propuesta del workflow: tests/Feature/MatiAiWorkflowTest.php:94 define private run(), choca con PHPUnit\\Framework\\TestCase::run final. Impide ejecutar suite completa estándar aunque --filter seleccione otros casos. Corrección propuesta renombrar helper en otra tarea; no corregido ahora.

Logs completos en storage/app/ai-audit/ frontend_tests.log, frontend_build*.log, laravel_ai_tests.log, laravel_ai_unit_tests.log, laravel_full_baseline.log y laravel_remaining_suite_direct.log. El wrapper artisan devolvió1 por --configuration repetido pese95pass; PHPUnit directo repitió95 tests con exit0.

## Aislamiento y límites de la evidencia

PostgreSQL comercial no recibió migraciones ni escritura de auditoría. Su despliegue actual carece de tablas IA/vector; la instancia aislada dispone de PG18.3/pgvector0.8.2 y migraciones actuales. Docker repo espera0.8.7; la suite aislada no prueba todavía esa imagen específica.

UI de auditoría: http://localhost:8088, container more-ai-audit-laravel-20261004, misma fuente/build y catálogo real copiado de forma read-only a DB testing del contenedor separado. Usuario sintético audit@example.test id20/role superadmin; ninguna cuenta real modificada. SESSION_COOKIE separada. PHP8.5.9 en app audit frente a8.5.5 principal/tests. FastAPI real root responde /health vía host.docker.internal:8001; token efímero no expuesto.

## Inspección de comportamiento del formulario

La autoridad comercial sigue en el submit habitual Laravel. Análisis/feedback no guardan producto. Aplicación manual revisa IDs activos y talla; lote conserva valores existentes; reemplazo individual requiere acción explícita. Preview externo usa rutas Laravel owner-bound; QR URL sólo texto; publicación exige derechos y aprobación. Fallback conserva la edición manual por diseño; su verificación navegador corresponde a la evidencia del auditor principal.

Hallazgos estáticos de UI: sugerencias antiguas no se filtran por cambio de foto (Assistant:190,201–207,285); states degraded/insufficient_evidence usan mensaje parcial; guard runtime incompleto para nested arrays; capabilities no consumidas React. FE-01..08 en _frontend_inspeccion.md/_frontend_validation.md.

Las decisiones de feedback no constituyen accuracy científica; son conducta de quien revisa. Tests con fixtures/mocks tampoco certifican calidad sobre fotos comerciales. La bitácora de navegador se incorpora a este mismo informe por el auditor principal.



## Navegador real: ejecución posterior de root

Se usó Playwright contra Laravel aislado8088, con login de usuario sintético, fuente actual/build normal y copia de catálogo. La aplicación actual80 no dispone de sesión superadmin válida para esta auditoría; token IA vacío/tablas pendientes se comprobaron aparte. PHP aislado8.5.9 difiere del principal8.5.5. No se guardaron prendas.

| Caso | UI / HTTP | Evidencia y límite |
|---|---|---|
|foto_prenda_y_colorimetria|NO FUNCIONA / 500|prohibited_with validator unknown|
|barcode|NO FUNCIONA / 500|same Laravel validator|
|qr|NO FUNCIONA / 500|same validator so no UI decoder result|
|ocr|NO FUNCIONA / 500|same validator; OCR model separately unavailable|
|matching_soft|FUNCIONA CON LIMITACIONES / 200|dato comercial o síntetico definido, muestra operativa pequeña|
|matching_exact_sku|FUNCIONA / 200|dato comercial o síntetico definido, muestra operativa pequeña|
|similares_pgvector|NO FUNCIONA / 500|Laravel validator before vector service|
|compatibilidad_ui|NO IMPLEMENTADO / None|FastAPI endpoint exists; no Laravel route or React UI|
|fastapi_off_manual_fallback|FUNCIONA CON LIMITACIONES / 200|similarity unavailable, exact-code check done|

Análisis y similares sobre imagen31 devolvieron500; EAN13, QRURL y etiqueta subidos desde controles reales también500. Causa registrada por servidor: validateProhibitedWith no existe. El JSON respondió Server Error, sin stack trace. La UI mostró aviso seguro y mantuvo nombre/campos. Matching textual del producto31 mostró primera coincidencia31score 0.282; SKU CAM-00031 mostró único exacto score 1.

Se apagó SOLO FastAPI de auditoría; consulta textual sin SKU devolvió200/statuscompleted más aviso de similitud no disponible. Se navegó al Paso2 manual; botón de continuar seguía habilitado. El statuscompleted describe comprobación local, no éxito remoto. N=7 solicitudes normales UI (5 imágenes bloqueadas,2matching exitosos):2/7=28.57% de cumplimiento de esos casos seleccionados; no estimador de tráfico real ni mezcla con el control de outage. Fallback manual1/1 controlado. Compatibilidad/color UI completa no evaluable por bloqueo/no UI.

La inferencia y colorimetría sí se ejecutaron por HTTP FastAPI directamente130 fotos, y SQL vector130consultas aisladas; esos éxitos NO certifican los botones Laravel rotos. Decoder HTTP3fixtures y QRsentinel0peticiones están en http_barcode_qr.json.

Evidencia: browser_e2e_summary.json, browser-audit-network.txt, laravel_image_http_blocker.json y screenshots browser-matching-isolated.png/browser-fallback-manual.png, bajo storage/app/ai-audit. El navegador cargó ProductCreate-tYfO0olu.js después del build, no el anterior ProductCreate-Br6F6_ny.js.

## Estado final de Docker y preservación

FastAPI del repo construido yprobadoGPU encontenedorpropio127.0.0.1:8002,healthy/fotoreal200/vector768/mismoSHA,sinrecrearappprincipal. PostgreSQL18.6Alpine/vector0.8.7 construido/migrate/rollbackaislados suplementa, no cambialaUIaisladaPG18.3/0.8.2. Ver01 ytarget087*. Fuentes427comparadas porSHAcontrabaselineenfinal_state.json. Contenedores/procesos propios sedetienenalcierre, principalesconservados. _preservacion_y_git.md registra cierre/evidencia.
