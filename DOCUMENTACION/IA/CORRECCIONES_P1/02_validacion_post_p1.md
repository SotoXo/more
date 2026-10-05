# Validación posterior al lote P1

Fecha: 2026-10-04, America/La_Paz; cierre E2E registrado el 2026-10-05 a las 00:57:33 UTC, todavía 2026-10-04 en hora local. El despliegue local privado, OCR y pgvector están operativos y la clasificación mantiene exactamente el baseline. **Cierre actual: seis hallazgos resueltos y AUD-07 mitigado operacionalmente. Los uploads de analyze, EAN13, QR y OCR, junto con la degradación OCR y continuidad manual, pasan en Chrome; A/E con imagen existente y F con apagado real ya habían pasado. La suite completa conserva errores P2.** HTTP200 se observó directamente en los access logs de FastAPI de las repeticiones y OCR deshabilitado; el HTTP de Laravel se infiere de ruta, UUID, contrato y UI, sin captura numérica del transporte del navegador. La calidad científica OCR y la segmentación semántica siguen sin acreditarse.

Evidencias de este lote: `storage/app/ai-p1/`. Los controles Python de color y sus logs generales están en `storage/app/ai-audit/p1-*`. La auditoría original de `AUDITORIA_FINAL` se conserva como baseline histórico. Primero se leyeron los 17 documentos exigidos y se guardó el preflight de 638 archivos; las modificaciones comenzaron después de confirmar ese snapshot.

## 1. Alcance y niveles de prueba

- Tests con `Http::fake` y fixtures sintéticas verifican contratos, abstención y control de aplicación; no calidad predictiva ni disponibilidad real.
- HTTP FastAPI ejecuta el proveedor real, con token local y modelos preparados. El baseline N130 usa fotografías reales y SigLIP real.
- La prueba directa del controlador Laravel ejecuta validación, cliente HTTP real, FastAPI y PostgreSQL; sus escrituras IA se revierten transaccionalmente. No atraviesa middleware web ni navegador.
- Chrome autenticado sobre la aplicación principal ejecutó A/E con imagen existente, F con apagado real, y una aplicación humana explícita sin guardar el producto. El cierre posterior ejecutó cinco casos funcionales: upload analyze, EAN13, QR URL, OCR y OCR deshabilitado con continuación manual. Se conservaron nueve runs: cuatro iniciales, uno con OCR deshabilitado y cuatro repeticiones para registrar HTTP FastAPI. No se cuentan las repeticiones como nueve casos independientes ni como muestras nuevas de calidad científica.

No se estima exactitud física del color, IoU de máscara, CER/WER científico ni calidad estética. Laravel sigue siendo la autoridad comercial; FastAPI no consulta ni escribe PostgreSQL comercial.

## 2. Suites, lint y build

| Ejecución | Resultado real | Alcance / evidencia |
|---|---|---|
| Python P1 relacionado | 11 passed, 1 warning; 6,03 s | Siete tests nuevos y cuatro relacionados existentes; `p1-python-color-tests.txt` |
| Python general, excluye sólo marker model_integration | 113 passed, 2 failed, 1 deselected, 1 warning; 5,38 s | `p1-python-baseline.txt`; los dos fallos P2 previos se conservan |
| Python model integration | 1 passed, 115 deselected, 1 warning; 17,99 s | Pesos reales cacheados; ejecución CUDA coordinada con runtime real; `python-model-integration.log` |
| Ruff check src tests | 8 hallazgos previos, 0 nuevos | `p1-python-ruff.txt`; no se presenta como verde |
| Mypy src | 16 errores previos en 7 archivos; 47 fuentes | `p1-python-mypy.txt`; la anotación del objeto colorimetría elimina cinco diagnósticos previos del mismo objeto y evita errores nuevos, sin alterar runtime |
| MatiAiClientTest | 7/7 pasan | HTTP mock, no inferencia real |
| MatiAiWorkflowTest | Los 14 se ejecutan; 13 pasan y 1 error | 73 assertions en primera ejecución tras rename-only; `laravel-workflow-first.log` |
| Nuevos MatiAiP1Test | 43/43 pasan | 32 casos de entradas, publicación, abstención, feedback y gate de color con fixtures |
| Laravel targeted | 63 pasan, 1 error; 64 total, 408 assertions | `laravel-p1-targeted-final.log` |
| Laravel estándar completa | 155 pasan, 1 error; 156 total, 962 assertions; exit 2 | `phpunit.xml` estándar, sin exclusiones; `laravel-full-final.log` y `laravel-full-final-exit.json` |
| Frontend helpers completos | 27/27 pasan; 0 skips; 99,94 ms | Incluye 20 casos IA y 7 comerciales; `frontend-tests-final.log` |
| Build normal npm run build | exit 0; TypeScript + Vite, 2.918 módulos | `frontend-build.log`, `frontend-build-evidence.json` |
| Asset servido | HTTP 200; 143.052 bytes; disco=HTTP | `served-frontend-asset.json`; también observado en Chrome |

Antes de PHPUnit se verificaron `DB_HOST=more-ai-p1-pg-testing-20261004`, `DB_DATABASE=testing` y `current_database()=testing`, PostgreSQL 18.6. Los tests IA que usan SQLite fijan `:memory:` explícitamente. La suite no se ejecutó contra el catálogo comercial ni se usó `migrate:fresh` sobre la base principal. Evidencia: `testing-db-guard.json`, `frontend-test-isolation-guard.json`.

El bloqueo AUD-04 por el helper `run()` incompatible con PHPUnit 12 desaparece: la suite inicia, ejecuta todos sus casos y termina reportando el error restante. **Esto no equivale a que toda la suite esté verde.** El error es `test_manual_normalization_works_before_ai_migration_is_applied`: SQLite rechaza quitar `modelo_pre` mientras `prendas_modelo_pre_index` aún la referencia. Se observó inmediatamente tras renombrar el helper y antes de las demás correcciones. Se conserva como P2 fuera del lote.

Los dos fallos Python restantes son fixtures anteriores: matching usa revisión legacy `r` y espera un ranking incompatible con el guard vigente; ModelManager usa `pinned-sha`, que no es SHA hexadecimal inmutable, y espera ese literal frente a la revisión cacheada resuelta. No se omitieron ni eliminaron tests para ocultarlos. Persiste el warning Starlette/httpx.

Asset actual `ProductCreate-CYON5l_Q.js`, SHA256 `419acd5bc561091e416c740e95268f2617a342183d6982fac813ea29eff2bbd3`. HTTP y disco coinciden. AUD-08 permanece como antecedente de build resuelto; no vuelve a clasificarse P1 activo.

## 3. FastAPI local: vida, autenticación y preparación

`runtime-validation.json`/`.log` registran HTTP real sobre el servicio local principal `more-mati-ai-1`, privado en red Docker y con exposición de desarrollo en loopback. El token compartido se almacena en configuración local; la evidencia registra presencia, nunca el valor.

| Control | Resultado |
|---|---|
| GET /health | HTTP 200, status ok; únicamente liveness |
| Capabilities sin token | 401 |
| Capabilities con token incorrecto | 401 |
| Capabilities con token correcto | 200 |
| Embedding real | HTTP 200, completed; dimensión 768, norma L2 0,9999999895, finito |
| Analyze imagen13 | HTTP 200, partial; paleta informativa y abstención comercial |
| Barcode sintético EAN-13 | HTTP 200, completed; valor 4006381333931, formato correcto, checksum true |
| QR sintético URL | HTTP 200, completed; URL como dato, contains_url=true, QR_URL_NOT_FETCHED; checksum GTIN null |
| Label OCR sintética | HTTP 200, completed; texto y regiones reales EasyOCR CPU |

La primera llamada embedding registró 5.817,76 ms wall; imagen13 634,91 ms; primera etiqueta OCR 3.554,37 ms; EAN-13 14,31 ms y QR 8,51 ms. Son muestras N1 y workloads distintos, algunas con inicialización; no constituyen percentiles ni SLA.

Capabilities tras la carga separa modelo preparado de `/health`: SigLIP cargado, revisión inmutable y dimensión verificada al generar; OCR enabled/CPU/es-en, pesos presentes, reader_loaded=true y download_enabled=false. `weights_presence_is_not_load_validation=true` evita convertir presencia de archivo en garantía de carga; `quality_evaluated=false` evita afirmar accuracy OCR.

El apagado controlado para el caso F terminó con reinicio del servicio. `post-outage-readiness.json` confirma health ok, embedding 200/completed, label 200/completed, modelo y lector OCR cargados nuevamente. El servicio principal queda restaurado operativo.

## 4. OCR: lectura real, preparación y límites

Se preparó EasyOCR 1.7.2 explícitamente durante setup, CPU, idiomas es/en, mediante el script existente `prepare_models.py --ocr`; la descarga no ocurre durante requests. Cache persistente fuera del repositorio: volumen `more_mati-ai-cache`, ruta del contenedor `/cache/easyocr`. Setup observado: 25,296 s. Runtime conserva downloads deshabilitadas.

| Peso | Tamaño | MD5 validado | SHA256 registrado |
|---|---:|---|---|
| craft_mlt_25k.pth, CRAFT | 83.152.330 bytes | 2f8227d2def4037cdb3b34389dcf9ec1 | 4a5efbfb48b4081100544e75e1e2b57f8de3d84f213004b14b85fd4b3748db17 |
| latin_g2.pth, reconocimiento latino | 15.406.141 bytes | 469869130aad1a34e8f9086f4262bc59 | aaa95be1c4a9cb3496879bed7c520886ce1164f89e026f0c54488394e74e8c55 |

Evidencias: `ocr-setup.log`, `ocr-weights.json`, `ocr_synthetic-http.json`, `label-synthetic-ground-truth.json`. La etiqueta sintética conocida produjo literalmente estas cinco líneas mediante el motor real:

```text
ZARA
TALLA M
REF: MATI-123
95% COTTON
5% ELASTANE
```

Se obtuvieron cinco regiones con cajas y confidences OCR aproximadamente 0,6803–0,9999. El parser devuelve marca ZARA, talla M, modelo MATI-123, algodón 95% y elastano 5%. Los scores 1,0 de marca/talla reflejan coincidencia textual contra el catálogo; no sustituyen las confidences del OCR ni acreditan certeza comercial calibrada.

Esta lectura inicial prueba disponibilidad y funcionamiento sobre N1 imagen sintética legible. No se dispone de un conjunto suficiente de etiquetas fotográficas transcritas para CER/WER/F1 científico y no se informa una tasa de calidad general. Al corte intermedio de 20:32 UTC faltaba el caso D desde el navegador. Ese recorrido y la degradación por OCR deshabilitado se completaron después, como acredita la sección 8: AUD-06 queda resuelto operacionalmente, sin convertir las repeticiones de la misma etiqueta en un conjunto científico independiente.

## 5. Baseline real comparable N130

Se reutiliza el mismo manifest final SHA256 `f24d4901949a70769df86f8b85fce7ee85d5ed5e0b90c1bb57e8b77c07c61c5e`, los mismos 130 IDs/hashes/etiquetas y exclusiones históricas. Catálogo: 134 imágenes, 131 SHA únicos; dos grupos repetidos deduplicados y grupo [2,5] excluido por categorías contradictorias. No se añadieron exclusiones ni se cambiaron etiquetas al observar resultados.

Modelo: `google/siglip-base-patch16-224`, revisión `7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed`, pipeline `registration-1.2.0`, vector 768/L2. `baseline-comparison.json` comprueba **130/130 objetos classification exactamente idénticos**, misma identidad de modelo y mismos hashes. Las 130 inferencias usaron CUDA; 0 CPU.

| Métrica | Post-P1 | Denominador / interpretación |
|---|---:|---|
| Top1 ranking | 83/130 = 63,8462% | Etiqueta comercial, proxy sin anotación experta independiente |
| Top3 ranking | 116/130 = 89,2308% | Incluye filas que se abstienen; ranking informativo |
| Macro-F1 | 0,664920056 | Diez clases con soporte; ACCESORIOS N0 excluida del macro |
| Coverage categoría | 13/130 = 10% | Decisiones no abstencionistas |
| Accuracy cubierta | 11/13 = 84,6154% | No reemplaza accuracy global; N13 pequeño |
| Abstención categoría | 117/130 = 90% | Mismos scores/umbrales/márgenes |
| HTTP exitoso | 130/130 | Transporte, no calidad predictiva |
| Envelope completed / partial | 0 / 130 | Abstención comercial explícita en todas las imágenes, sin error HTTP |
| Paleta técnica no vacía | 84/130 = 64,6154% | Mismo número que baseline |
| Estado técnico color completed / insufficient | 84 / 46 | No es estado comercial |
| Color comercial insufficient | 130/130 | Identidad espacial de prenda no verificada |
| Imagen con algún ID color aplicable | 0/130 | Descenso intencional por fail-closed; antes 69/130 |
| Primary con ID aplicable | 0/130 | Antes 35/130; no se fabrica un ID para sostener cobertura |
| Analyze HTTP warm p50 / p95 | 142,409 / 269,106 ms | N130; min 63,037, media 160,628, max 716,355 ms |
| VRAM pico observado | 856,77 MiB | Telemetría del proceso PyTorch; no consumo global de escritorio |

Evidencias: `classification_metrics.json`, `classification_predictions.json`, `baseline-comparison.json` y `classification-run.log`. La latencia anterior era HTTP nativo y la actual es HTTP Docker caliente; no es una comparación causal controlada ni demuestra que la mitigación acelere el modelo. No se cambiaron dtype, prompts o thresholds para mejorar números.

La comprobación CPU adicional `p1-color-baseline-comparison.json` conserva **130/130 observaciones técnicas exactas**: HEX/RGB/Lab, proporciones, familias, descripción, ΔE, confianza heurística, roles, foreground, cobertura, propiedades, métricas y status. Los 130 hashes originales permanecen iguales. Se retiran únicamente autorizaciones comerciales y se añade evidencia explícita de la limitación.

Imagen13 sigue conteniendo jersey verde, pantalón oscuro y bolso; la heurística aún acepta una silueta y puede observar negro como principal. El negro permanece diagnóstico sin ID aplicable. **No se afirma segmentación semántica resuelta:** AUD-07 está operacionalmente mitigado y requiere ese desarrollo posterior.

## 6. PostgreSQL, migraciones, muestra e indexación

Antes de cambiar PostgreSQL se creó un backup lógico custom: `backups/laravel-before-p1.dump`, 225.295 bytes, SHA256 `5bbcd4c8d3b1090808e71343d027703e0b449a5c3848b465e0781edfe3184152`. `pg_dump` y `pg_restore --list` terminaron en 0; 413 entradas TOC. Se comprobó existencia/tamaño/lectura del archivo; **no se realizó restauración del backup** ni se presenta esa comprobación como prueba de restore. Evidencia: `backup-validation.json`.

El PostgreSQL principal local ahora es 18.6 Alpine y pgvector 0.8.7, host pgsql, base laravel, conservando su volumen. pgvector es extensión de PostgreSQL. Migraciones normales `2026_09_28_000001_create_catalog_ai_tables` y `2026_10_03_000001_create_ai_product_embeddings_table` aparecen DONE; no se usó migrate:fresh, DROP DATABASE/SCHEMA ni eliminación del volumen.

El guard final confirma cinco tablas IA, `prendas.modelo_pre` nullable, vector sin N fijo, 11 FKs validadas, dimensión real 768/L2 y referencias consistentes. La reversibilidad up→down→up se ejecutó exclusivamente en `p1_migration_testing`, host `more-ai-p1-pg-testing-20261004`, sin volumen comercial: ambos up crean cinco tablas/11 FKs/modelo_pre; down quita las tablas/columna IA y conserva la extensión. No se hizo rollback destructivo sobre principal. Evidencias: `migrations-main.log`, `isolated-up-down-up.json`, `final-catalog.json`.

| Ejecución comando existente | Processed | Indexed | Updated | Skipped | Failed |
|---|---:|---:|---:|---:|---:|
| Muestra inicial | 8 | 8 | 0 | 0 | 0 |
| Repetición muestra | 8 | 0 | 0 | 8 | 0 |
| Completa, tras validar muestra | 134 | 126 | 0 | 8 | 0 |
| Repetición completa | 134 | 0 | 0 | 134 | 0 |

Se usó `mati-ai:index-catalog` en modo normal, sin force/from-runs ni un indexador alternativo. La ejecución completa fue `--limit=10000 --batch-size=10`. Cobertura final: 134/134 productos/imágenes elegibles, 134 canónicos. Este denominador operacional incluye todo el catálogo elegible; no se confunde con las 130 fotos deduplicadas del benchmark científico.

Ocho queries sobre la muestra comparan `CatalogEmbeddingStore`/pgvector exact search con fuerza bruta NumPy float64: 8/8 órdenes idénticos, error absoluto máximo de coseno 1,8840e-7, tolerancia 1e-5. No se repitió inferencia ni hubo escrituras en esa comparación. Valida geometría/query y separación de espacio; no relevancia comercial ni identidad del producto. Evidencias: `sample-validation.json`, `sample-numpy-agreement.json`, `full-index-validation.json`.

El corte anterior al recorrido de navegador, `final-catalog.json` (20:09:25 UTC), conserva 34/34 fingerprints comerciales y 134/134 hashes de imágenes. Sus recuentos IA eran 2 model_versions, 135 runs, 0 suggestions, 0 human_reviews y 134 product_embeddings. Se conserva este snapshot como evidencia de ese momento, no como recuento del cierre posterior.

El corte intermedio posterior al primer recorrido de navegador, `final-after-browser.json` (20:32:52 UTC), vuelve a confirmar 34/34 fingerprints comerciales, 134/134 SHA de imágenes y canónicos idénticos. Recuentos de ese corte histórico: **2 model_versions, 138 runs, 3 suggestions, 2 human_reviews y 134 product_embeddings**; cobertura 134/134. Los registros IA adicionales corresponden a los recorridos de análisis/similares y aplicación explícita descritos en la sección 8; no acreditan modificaciones comerciales. `ai_counts_identical=false` refleja esos eventos nuevos. La idempotencia declarada se limita a los canónicos y a las repeticiones del comando, no a toda request del sistema ni a una congelación de todos los contadores IA.

El cierre E2E final, `e2e-closure-catalog-final.json` (2026-10-05 00:55:35 UTC), confirma de nuevo **34/34 huellas comerciales, 134/134 SHA de imágenes y 134 canónicos idénticos** al corte intermedio. Sus recuentos actuales son **4 model_versions, 147 runs, 11 suggestions, 2 human_reviews y 134 product_embeddings**; cobertura 134/134. Las nueve nuevas ejecuciones UI y ocho sugerencias adicionales son actividad IA esperada; no se aplicaron esas sugerencias ni se hizo submit comercial durante este cierre. Las dos revisiones corresponden al caso humano anterior y conservan el límite explicado en la sección 8.

Las cuatro identidades de metadatos verificadas mediante SELECT en modo lectura son:

| ID | model_id | model_revision | pipeline_version | Registro |
|---|---|---|---|---|
| 1 | google/siglip-base-patch16-224 | 7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed | registration-1.2.0 | Identidad visual previa, intacta |
| 143 | laravel-catalog | 1 | 1 | Matching local previo |
| 148 | deterministic | 1 | registration-1.2.0 | Registrada al ejecutar barcode UI; también identifica el envelope OCR deshabilitado |
| 150 | easyocr_cpu | 1.7.2 | registration-1.2.0 | Registrada al ejecutar OCR UI |

El aumento de 2 a 4 refleja el primer registro persistido de las identidades del decodificador determinista y EasyOCR ya preparados. **No implica un nuevo modelo ni cambio del checkpoint SigLIP**, cuya revisión sigue siendo idéntica. Los valores ID discontinuos no representan un número de modelos ejecutados.


## 7. Controlador con backend real: 24 casos

`controller-runtime-probe.json` y `.log` registran llamadas directas a los cuatro métodos de controlador usando el cliente real, token real, FastAPI y PostgreSQL principal. `mock_http=false`, `commercial_product_writes=false`; escrituras IA dentro de transacción finalmente revertida.

| Entrada | analyze | barcode | label | similar |
|---|---:|---:|---:|---:|
| Upload válido | 200 | 200 | 200 | 200 |
| existing_image_id válido | 200 | 200 | 200 | 200 |
| Ambos | 422 | 422 | 422 | 422 |
| Ninguno | 422 | 422 | 422 | 422 |
| ID inexistente | 422 | 422 | 422 | 422 |
| Imagen inválida | 422 | 422 | 422 | 422 |

Resultado: 24/24 cumplen lo esperado; los ocho válidos alcanzan backend real. Demuestra exclusión de fuentes y ausencia del 500 anterior en este recorrido. **No atraviesa middleware de sesión/rol/throttle, render React, selección de archivo ni eventos del navegador.** No se etiqueta como 24 casos E2E web ni sustituye los recorridos de Chrome. Los uploads y B/C/D se comprobaron después y por separado; la sección 8 conserva tanto el corte intermedio como su cierre posterior.

## 8. Navegador principal: corte histórico y cierre E2E

### Corte intermedio de 20:32 UTC: A/E/F cumplidos; uploads y B/C/D pendientes

En ese corte intermedio se usó Cua sobre Chrome existente con sesión superadmin válida en la aplicación principal; no se creó ni modificó ninguna cuenta. Chrome observó el mismo asset nuevo servido por HTTP. `browser-e2e.json` registra los recorridos y sus DOM/capturas. A y E emplean el camino implementado `existing_image_id` de una fotografía ya guardada; no requirieron upload, habilitar file-URL ni eludir su bloqueo.

| Caso solicitado | Estado de navegador sólo al corte intermedio | Resultado / límite en ese momento |
|---|---|---|
| A: foto / analyze y color | PASA con imagen existente 13 del producto 11 | Resultado partial, Top-3 exploratorio, abstenciones visibles y paleta informativa; variante upload sigue bloqueada |
| B: barcode | BLOQUEADO antes de cargar fixture local | Permiso file-URL de la extensión Chrome ausente; backend real probado por separado |
| C: QR | BLOQUEADO antes de cargar fixture local | Mismo bloqueo; no se registra como fallo HTTP |
| D: OCR | BLOQUEADO antes de cargar fixture local | OCR backend real útil, pero label UI sigue pendiente |
| E: similares por imagen | PASA con imagen existente 13 | Doce candidatas reales; variante upload sigue bloqueada |
| F: FastAPI apagado, formulario manual | PASA con apagado real controlado | Campos manuales conservados, avance al Paso2, 0 productos guardados; servicio restaurado |

### Abstención visible y flujo manual

En edición del producto 11, imagen 13 `jersey-alpaca-frueyyc3.jpg`, analyze mostró partial y el Top-3:

- PANTALONES 0,060.
- POLOS Y KNITWEAR 0,045.
- ABRIGOS 0,019.

La UI mostró abstención de categoría y patrón, cero botones Aplicar para esos resultados, aclaración de similitud coseno sin probabilidad, y color comercial con paleta exclusivamente informativa. Nombre, categoría, marca y género manuales se conservaron y se avanzó al Paso2. No se guardó el producto. Evidencias: `browser-analyze-existing-dom.txt`, `browser-abstention-existing.jpg` y `browser-e2e.json`.

El caso E presentó doce candidatas. La primera fue SOBRECAMISA REGULAR FIT con similitud mostrada 0,811 y aviso explícito de que similitud no implica identidad. No se guardó producto ni se convirtió esa similitud en certeza comercial. Evidencias: `browser-similar-existing-dom.txt`, `browser-similar-existing.jpg`.

### Propuesta positiva y aplicación humana explícita

Se eligió producto 6 / imagen 8 porque su clasificación del baseline congelado ya tiene `abstained=false`; la selección sólo ejercita la rama aplicable y no modifica el benchmark, sus exclusiones o umbrales. La UI mostró tres tarjetas de categoría revisables. Se hizo una única acción explícita Aplicar sobre PANTALONES, coseno mostrado 0,082. Apareció el badge Aplicada y “Categoría aplicado. Puedes seguir editando antes de guardar.” El campo nombre continuó habilitado. La categoría elegida ya coincidía con el valor del formulario; no hubo submit ni cambio comercial. Evidencias: `browser-positive-human-apply-dom.txt`, `browser-positive-human-apply.jpg`, `browser-e2e.json`.

Esta observación, junto con los tests, acredita AUD-05: ranking abstencionista visible sin propuestas aplicables y propuesta positiva que requiere acción humana y conserva el formulario editable. No acredita exactitud científica por el score ni un nuevo guardado.

`browser-human-review-observation.json` contiene dos filas accepted para una misma suggestion: una stage=form y otra stage=saved, enlazada mediante form_review_id. El comportamiento **preexistente** de `recordFeedback` llama `recordSavedDecision` cuando el run ya está vinculado a un producto existente; compara la propuesta con el valor actualmente persistido. Aquí la categoría ya coincidía, por lo que crea el registro saved aunque el navegador no haya hecho un nuevo submit. Estas dos filas **no prueban un nuevo guardado comercial, no son dos aplicaciones ni una tasa de aceptación científica**. Los 34 fingerprints comerciales permanecen idénticos.

### Outage y límites pendientes en el corte intermedio

Para F, el navegador mostró “La búsqueda por similitud no está disponible; la comprobación de códigos exactos sí se realizó.” Se avanzó al Paso2 y no se hizo submit comercial. Después se reinició FastAPI y se comprobó preparación de embedding/OCR. Evidencias: `outage-start.json`, `outage-end.json`, `post-outage-readiness.json`, `browser-e2e.json`, `browser-fallback-step2.jpg`. La captura se guardó después de restaurar FastAPI y muestra el aviso conservado de la request de caída junto con Paso2.

Al corte intermedio, los uploads y B/C/D permanecían bloqueados antes de introducir los archivos por el permiso de la extensión. **No son fallos HTTP y no se calcula una tasa 0/5, 0/3 ni un porcentaje global mezclando casos ejecutados con bloqueados.** Tampoco se cuentan como aprobados por los tests del controlador/HTTP privado. La petición de habilitar manualmente el acceso a archivos locales seguía sin respuesta al corte registrado. El control del navegador rechazó abrir chrome://extensions porque sólo admite HTTP/HTTPS; se solicitó al usuario habilitar el permiso manualmente.

Al corte intermedio, AUD-01 seguía pendiente del criterio E2E de uploads y AUD-06 seguía pendiente de label UI; AUD-05 ya estaba resuelto con abstención y aplicación explícita. Esos estados pendientes describen esa etapa y quedan sustituidos por el cierre siguiente. Las suites, el build, el baseline N130 y sus resultados numéricos se mantienen sin cambios.


### Cierre posterior: cinco casos funcionales y nueve runs reales

Tras reconectar la extensión de Chrome se cargaron las cuatro imágenes preparadas mediante el selector de archivos de la aplicación, en la sesión autenticada y en un borrador nuevo. `e2e-closure-browser.json` registra cinco casos passed y **14 archivos de evidencia: siete JPG y siete DOM**, con tamaños, SHA y paths absolutos. La lectura y la degradación OCR se observaron visualmente; no hubo click Aplicar de sugerencias ni submit comercial en este cierre.

| Caso del cierre | Resultado UI / persistencia | Evidencia principal |
|---|---|---|
| Upload analyze, fotografía real imagen13 | partial; categoría abstencionista; paleta informativa sin color comercial aplicable; image_id enviado null | e2e-closure-analyze.jpg / -dom.txt |
| B: EAN13 | 4006381333931, EAN-13, checksum true; propuesta sin aplicación automática | e2e-closure-barcode.jpg / -dom.txt |
| C: QR URL | https://example.org/product/42 como texto; página conservada; sin navegación automática; QR_URL_NOT_FETCHED | e2e-closure-qr.jpg / -dom.txt |
| D: OCR preparado | ZARA, TALLA M, REF: MATI-123, 95% COTTON, 5% ELASTANE; cinco regiones y parser marca/talla/modelo/composición | e2e-closure-ocr.jpg / -dom.txt; e2e-closure-ocr-restored.jpg / -dom.txt |
| OCR deshabilitado temporalmente | partial, OCR_UNAVAILABLE y cero regiones; aviso «No se pudo leer la etiqueta automáticamente. Puedes escribir los datos visibles.»; nombre/categoría/marca/género conservados, descripción editable y avance a Paso2 sin guardar | e2e-closure-ocr-degraded.jpg / -dom.txt; e2e-closure-ocr-degraded-step2.jpg / -dom.txt |

El caso E de similares y F de caída real permanecen acreditados por la etapa anterior; el cierre no los transforma en casos nuevos ni repite sus métricas. El permiso que impedía cargar los fixtures ya no bloqueó este recorrido y no quedó una autorización pendiente.

`e2e-closure-browser-runs.json` se extrajo con Laravel bootstrap y PostgreSQL principal protegidos por driver/base esperados y transacción **READ ONLY**. Contiene sólo campos permitidos: rutas, UUID, tiempo UTC, image SHA, image_id, estado y las secciones necesarias de classification/color, barcode y OCR. Excluye actores, cabeceras, cookies, tokens, entorno y vectores. Se verificaron nueve UUID, nueve SHA de fixture iguales al resultado y nueve IDs de request coincidentes; todos los image_id son null porque fueron uploads.

| Etapa / operación | Created UTC | Request ID | Envelope |
|---|---|---|---|
| Inicial analyze | 2026-10-04 23:08:39 | fa8c237f-636f-4e7e-acbf-1c7a20af0c44 | partial |
| Inicial EAN13 | 2026-10-04 23:09:15 | 7602eda4-26ba-44b4-8fcd-e6a21045ed6d | completed |
| Inicial QR | 2026-10-04 23:09:53 | 5a78f02b-750a-44e5-9aa0-ca3b8ab77757 | completed |
| Inicial OCR | 2026-10-04 23:10:31 | f823db3c-db51-4a2f-9b57-fd02160ef207 | completed |
| OCR deshabilitado | 2026-10-04 23:17:25 | 842a1c06-61a0-461a-bd9b-72a2ae0864ae | partial |
| Repetición analyze | 2026-10-04 23:18:48 | 6bbd4986-61f3-44c8-8b4b-de8d5ca5a994 | partial |
| Repetición EAN13 | 2026-10-04 23:19:01 | 702d2d47-6b9b-4c65-85fe-671e918d31eb | completed |
| Repetición QR | 2026-10-04 23:19:22 | c3d1cc09-5dee-4ced-af9f-58e95c57936a | completed |
| Repetición OCR | 2026-10-04 23:19:26 | 235826e3-69eb-4298-856a-e80d352cf2bf | completed |

Los created_at de PostgreSQL son timestamp sin zona y la aplicación usa America/La_Paz: el filtro exclusivo 19:07:57 local equivale a 23:07:57 UTC. El JSON conserva ambos tiempos explícitos. No se filtró directamente por 23:07:57 sobre la columna local.

El OCR deshabilitado conserva raw_text y normalized_text vacíos y cero regiones. Los dos OCR completados conservan las cinco líneas y cinco regiones con cuadriláteros, textos y confidencias 0,999926 / 0,680336 / 0,981583 / 0,757490 / 0,835796, junto con ZARA/id1, M/id4, MATI-123, algodón95% y elastano5%. El último OCR completado es el UUID 235826e3…; su lectura persistida y su UI coinciden. Son repeticiones de la misma etiqueta sintética, no nuevas etiquetas independientes ni una evaluación CER/WER.

### HTTP observado, HTTP inferido y alternativa de captura

FastAPI normalmente arrancaba con `--no-access-log`. Los cuatro runs iniciales no tienen access log numérico; se habilitó temporalmente el logger de acceso y se repitieron los cuatro flujos, además del caso OCR deshabilitado. `e2e-closure-restored-http.json` contiene los **cuatro POST HTTP200 observados** de analyze, EAN13, QR y label; `e2e-closure-ocr-disabled-http.json` contiene el POST label **HTTP200 observado** del caso partial/OCR_UNAVAILABLE. Así, los cinco casos funcionales tienen transporte FastAPI 200 observado en sus ejecuciones con logging. Los logs de acceso no incluyen cabeceras ni cuerpos. El logger Uvicorn no contiene request UUID: la correlación se basa en ruta, orden y timestamp, junto con el run persistido y SHA conocidos, no en un UUID leído de la línea HTTP.

**No se capturó el código HTTP numérico del transporte Chrome→Laravel.** `e2e-closure-http-access.json` conserva rutas y tiempos del servidor Laravel; `ai_runs`, sus UUID y envelopes validados coinciden con la UI exitosa. El controlador devuelve JSON con status HTTP200 por defecto. Por ello, el 200 Laravel es una inferencia sustentada por implementación, ruta/tiempo, UUID, contrato y resultado UI, y no se presenta como observación directa de Network ni como captura de middleware. La ausencia de 500 en los recorridos observados no se convierte en cobertura de cualquier entrada o condición.

La revisión automática rechazó la propuesta de captura de paquetes mediante socket AF_PACKET; **no se ejecutó**. Se usaron los registros existentes y el logger de acceso temporal de FastAPI como alternativa de aplicación, sin reintentar la captura ni eludir la restricción. No hay una petición de permiso pendiente para ese mecanismo ni para los fixtures del cierre.

### Restauración exacta del runtime y preparación final

`e2e-closure-runtime-before.json` y `e2e-closure-runtime-restored.json` confirman igualdad de imagen Docker, comando, entorno permitido, mounts y puertos; todos los checks son true. Los mounts se compararon sin orden porque Docker devolvió las mismas entradas en distinto orden. Se restauraron el comando original con `--no-access-log`, OCR enabled=true, download_enabled=false, dispositivo CUDA, local_files_only=true, revisión SigLIP inmutable, volumen more_mati-ai-cache y exposición 127.0.0.1:8001. No se dejó una configuración temporal de OCR o logging.

`e2e-closure-final-readiness.json` (2026-10-05 00:55:03 UTC) acredita health HTTP200, embedding HTTP200/completed con dimensión768, normaL2 0,9999999895 y valores finitos, misma identidad/revisión SigLIP; label HTTP200/completed con cinco regiones y parser esperado; capabilities HTTP200 con OCR enabled/CPU, ambos pesos presentes, reader_loaded=true y download_enabled=false. El entorno restaurado confirma CUDA; el campo device del resumen visual es null y no se usa como prueba independiente del dispositivo. El servicio principal quedó saludable.

La ejecución de este cierre no volvió a correr suites, build ni benchmark N130. Los 130 objetos classification y las 130 observaciones de color ya comparadas permanecen como evidencia congelada; los fallos Python/SQLite P2, Ruff8, Mypy16 y warning previo permanecen visibles. AUD-01 se cierra con uploads y controles E2E observados; AUD-06 se cierra operacionalmente con OCR preparado, label UI, degradación y restauración. Ninguno de esos cierres certifica calidad científica de OCR o semántica del foreground.

## 9. Estado que permite esta evidencia

| ID | Estado de cierre | Fundamento |
|---|---|---|
| AUD-01 | RESUELTO | Regla soportada, controlador real 24/24, A/E con imagen existente y uploads de analyze/EAN13/QR/OCR en Chrome; FastAPI200 observado en repeticiones y límite HTTP Laravel explícito |
| AUD-02 | RESUELTO local | Servicio privado, token compartido, auth 401/401/200, inferencia CUDA y recuperación tras apagado |
| AUD-03 | RESUELTO local | Backup previo, pgvector/migraciones/FKs, query NumPy y 134 canónicos idempotentes; datos comerciales preservados |
| AUD-04 | RESUELTO bloqueo de carga | Los 14 workflow y la suite estándar completa se ejecutan; error residual SQLite P2 explícito |
| AUD-05 | RESUELTO | UI real preserva Top-3 abstencionista, cero Aplicar, aviso coseno y patrón; caso positivo exige click humano y conserva edición sin guardar |
| AUD-06 | RESUELTO operacional | Pesos y runtime sin download; label UI con texto/parser y respuesta persistida con cinco regiones, OCR deshabilitado con continuidad manual y restauración verificada; CER/WER científico no certificado |
| AUD-07 | MITIGADO operacionalmente | IDs comerciales 0/130 sin alterar observaciones ni umbrales; segmentación semántica sigue pendiente |

Los hashes preflight de clasificación/prompts, atributos, ModelManager, foreground, CIEDE2000, matching interno/externo, contrato embedding y motor/reglas de compatibilidad permanecen idénticos. Las constantes y helpers matemáticos del módulo color mantienen AST idéntico; auditoría histórica 20/20 hashes conservados (`python-p1-invariants.json`). No entrenamiento/fine-tuning, nuevos modelos, RAG, LLM, outfits, segmentador DL, Redis IA o ANN.

La verificación Git final conserva rama/HEAD y los cambios previos; no faltan archivos preexistentes ni hay operaciones add/commit/push/reset/restore. `git diff --check` reporta avisos EOF ya presentes en las cuatro fuentes indicadas por `git-final.json`; los conteos de líneas vacías antes/después se mantienen. La comparación incremental registra salidas vacías y `p1_new_whitespace_errors=false`; el exit 1 de diff --no-index indica diferencias y no acredita por sí solo un error whitespace (`git-whitespace-comparison.json`). No se confunde ese aviso con error funcional ni se afirma diff-check verde. El reporte global se compara contra el preflight, no se atribuyen todos sus cambios a P1.

El cierre actual suma seis hallazgos resueltos y uno mitigado operacionalmente; conserva como historia los estados pendientes del corte intermedio. Este documento registra lo ejecutado y las limitaciones científicas o P2 que permanecen. La evidencia sintética/mock, matemática, de fotos reales, backend HTTP y navegador conserva sus denominadores y límites propios.

Cierre de recursos previo: runtime-final.json confirma Laravel/PostgreSQL/Redis/FastAPI running y FastAPI healthy en loopback; el PostgreSQL aislado de testing quedó detenido, sin eliminar volúmenes. El cierre E2E posterior restauró el runtime original y revalidó preparación visual/OCR y catálogo mediante e2e-closure-runtime-restored.json, e2e-closure-final-readiness.json y e2e-closure-catalog-final.json. Secret-scan.json registra archivos verificados, token local configurado y cero coincidencias de su valor en evidencia/código/informes no privados.
