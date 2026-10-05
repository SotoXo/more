# Resultados de validación técnica, funcional, científica y E2E

Auditoría: 2026-10-04, America/La_Paz. **La IA privada funciona con limitaciones; el formulario IA por imagen está roto.** El despliegue principal además carece de servicio FastAPI, token y migraciones IA. HTTP200 y suites parciales no eliminan esos bloqueos. Primero se explicó la fuente en00; después se ejecutaron validaciones.

## Dataset y reproducibilidad

Branch feature/ia-colorimetria; HEAD fde26bd2d1aebb733733802001f6ce63d5599792. Había cambios del usuario antes de comenzar. Catálogo134 prendas/134 imágenes/134 variantes,131SHA únicos. Duplicados[10,11] y[14,15] se contaron una vez; grupo[2,5] se excluyó completo por categorías contradictorias. Resultado:130 fotos comerciales evaluadas,10 categorías representadas de11activas,10 marcas representadas de12,35 etiquetas de color,0patrones confirmados. CONJUNTOS sóloN3; ACCESORIOS N0. Distribuciones por categoría/marca/color/patrón y referencias por fila en evaluation_manifest.json. No es holdout independiente; no se entrenó ni ajustó nada.

Manifest usado durante inferencia SHA256 3d012c94367ffefda2dd6382aa902df22271a201bb69e98da8a014abca101a28. Final f24d4901949a70769df86f8b85fce7ee85d5ed5e0b90c1bb57e8b77c07c61c5e. Se añadieron aliases descriptivos/campos de elegibilidad requeridos, conservando fotos/hashes/etiquetas/taxonomías. _manifest_lineage.md conserva ambas identidades sin inventar que el archivo final se usó originalmente.

Near-duplicates: pHash Hamming≤4 produjo14 pares candidatos, todos inspeccionados en diagnostic_images.png;0copias confirmadas de la misma fotografía. Eran prendas/cortes/colores/escenas diferentes. Incluso Hamming0 en81/82 (jerseyazul/redtrenzado) no implica identidad. No se eliminaron prendas distintas por ese proxy; no se garantiza detectar toda copia fuera del umbral. Exact SHA se controló antes de medir.

## Estado por módulo

| Módulo | Estado | Evidencia / límite |
|---|---|---|
|Validación, normalización y calidad|FUNCIONA CON LIMITACIONES|Límites y formatos probados; calidad heurística confunde fondo blanco con highlights|
|Clasificación SigLIP privada|FUNCIONA CON LIMITACIONES|130 inferencias reales,Top1 63.85%,abstención90%|
|Patrón zero-shot|NO EVALUABLE|Implementado,0 etiquetas confirmadas para accuracy|
|Foreground|FUNCIONA CON LIMITACIONES|Objeto conectado, no máscara semántica de prenda; contaminación observada|
|Colorimetría privada|FUNCIONA CON LIMITACIONES|84/130 paletas; falta color físico/monocolor confirmado|
|Embedding privado|FUNCIONA|130vectores768/L2 con identidad de espacio comprobada|
|pgvector principal|NO FUNCIONA|No extensión ni tablas IA; migraciones pendientes|
|Persistencia/búsqueda pgvector aislada|FUNCIONA CON LIMITACIONES|130/130persistidos yacuerdoNumPy; fallo combinacióncolor/talla|
|Command indexación aislado|FUNCIONA CON LIMITACIONES|Muestra limit1 skip/force;130 vectores por servicio, no command masivo|
|Barcode/QR privado|FUNCIONA CON LIMITACIONES|30/36 variantes sintéticas,0/6recortadas; no dataset fotográfico anotado|
|OCR EasyOCR|NO FUNCIONA|3/3OCR_UNAVAILABLE, pesos locales faltantes|
|Parser etiquetas|FUNCIONA CON LIMITACIONES|3/3 textos controlados, no OCR; score marca ignora confianza OCR|
|Matching interno|FUNCIONA CON LIMITACIONES|SKU/texto UIaislada;soft100/134,activos no filtrados|
|Búsqueda/imagen externa|PARCIAL|Adapters/seguridad implementados,0fuente activa; operación real no evaluable|
|Preparación publicación|FUNCIONA CON LIMITACIONES|HTTP/WebP/EXIF/SHA/derechos probados; sin publicarproductos|
|Feedback humano|FUNCIONA CON LIMITACIONES|Predicción preservada,replay duplica decisión aislada;sin historia real|
|Compatibilidad cromática|PARCIAL|Motor privado determinista, sinruta/controller/UI consumidor|
|Formulario IA por imagen|NO FUNCIONA|5casosUI500 porvalidateProhibitedWith inexistente|
|Formulario manual con FastAPI apagado|FUNCIONA CON LIMITACIONES|1/1controlaislado avanzal Paso2,sin submit comercial|
|UI autenticada principal|NO EVALUABLE|No sesiónsuperadmin válida disponible;cuenta real no modificada|
|Redis paraIA|NO IMPLEMENTADO|Redis general existe;noqueue/cache/jobIA constatado|
|NoSQL paraIA|NO IMPLEMENTADO|No agregado, no requerido por alcance|
|Outfits/recomendación/RAG/generación/VTO|NO IMPLEMENTADO|Diseño/futuro documental,no funciones actuales|

## Métricas actuales

Modelo visual SigLIP google/siglip-base-patch16-224, revisión7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed, pipeline registration-1.2.0. ColorCV determinista. Dataset real deduplicadoN130 salvo indicado; todos los resultados proceden de ejecuciones, sin retocar thresholds/prompts.

| Métrica | Valor observado | N | Modelo/dataset | Interpretación | Confiabilidad |
|---|---|---|---|---|---|
|Top1 ranking|83/130=63.85%|130|SigLIP/fotosreales|Coincide primera categoría|Proxy comercial reproducible,no consenso experto|
|Top3 ranking|116/130=89.23%|130|SigLIP/mismo|Etiqueta entre3|Mismo límite|
|Macro-F1/weighted-F1|0.66492/0.64853|130/10clases|SigLIP/mismo|Promedio clases/soporte|N3CONJUNTOS,N0ACCESORIOS|
|MacroPrecision/Recall|0.73087/0.69461|130|SigLIP/mismo|Pureza/cobertura por clase|No confundirPrecision con Accuracy|
|Coverage/accuracycubierta|13/130=10% /11/13=84.62%|130/13|SigLIP/mismo|Sinabstención/aciertoscubiertos|N13pequeño;top3 sigue disponible|
|HTTPéxito/completed|130/130 /8/130|130|Pipeline real|Transporte/sinpartial|Partial122noerrorHTTP|
|Paleta disponible|84/130=64.62%|130|CV/fotosreales|No vacía|Sin máscara humana/IoU|
|PrimaryIDcoverage/accuracyproxy|35/130=26.92% /18/35=51.43%|130/35|CV/etiquetavariante|IDvscolorcomercial|NO accuracy física/monocolorfuerte|
|Familia primary proxy|36/63=57.14%|63|CV/fam_col|Familiaamplia|Salida puede usar catálogo,noindependiente|
|ΔE00 swatchexpected|media14.497/mediana 9.968/p90 29.634|84|CV/topcluster vsHEXcomercial|Distancia al color esperado|Proxy,no colorinstrumental|
|CIEDE2000 cálculo|34/34 ±0.00005|34 pares|Determinista/referenciaSharma|Corrección numérica|Fuerte para esos casos,no segmentación|
|Barcode valor exacto|30/36=83.33%;clean6/6|36/6|OpenCV+ZXing/sintético|Decoder real variantes|No etiquetasfotografiadas|
|OCR CER/WER|NO EVALUABLE|0OCRútil,3 intentos|EasyOCR/sintético|Pesos faltantes|No asignar0/100%CER|
|Recall@1/@5/@10|0.05314/0.16681/0.25551|130 queries|SigLIP/galería130|Fracciónpositivos misma categoría|Proxy categoría,NO identidad|
|Hit@1/@5/@10|0.58462/0.87692/0.97692|130 queries|SigLIP/mismo|Algúnpositivo entreK|Distinto de Recall@K|
|MRR/nDCG@10|0.70305/0.40206|130 queries|SigLIP/mismo|Primerpositivo/ordenútil|Mismalimitaciónrelevancia|
|SQLvsNumPy top30|130/130orden/conjuntos|130|PG18.3/vector0.8.2aislado|Error coseno máx2.389e-7|Valida búsqueda,no relevancia|
|HTTP analyze p50/p95|221.96/410.82ms|130|SigLIP CUDA+CV/fotosvariadas|PipelinecalienteFastAPI|No E2ELaravel|
|HTTP embedding p50/p95|16.21/20.89ms|20|SigLIP CUDA/fotorepetida|Embeddingcaliente|No mezcla con analyze|
|11promptsCUDA/CPU p50|30.56/329.73ms|9/device|SigLIPfloat32/foto33|Microbenchmarksecuencial|CPU tras OScache,no diskcold|
|VRAM pesos/pico|775.23/833.39MiB|1carga/10 inferencias|SigLIPfloat32CUDA|Allocated/peakPyTorch|Globalescritorio aparte|
|SQLp50/p95|4.330/4.784ms|130|pgvector0.8.2aislado|Laravel+DBsinGPU|Volumenpequeño,exactscan|
|E2Ecasos seleccionados|2/7=28.57%|7|UIaislada/catálogo+fixtures|2matching,5image500|Diagnóstico,no estimadortráfico|
|Concurrenciapequeña|0/8 errores,4 workers|8|HTTP embedding real|SinOOMobservado|No loadmax|
|Compatibilidad simetría|435/435real;465/465sintética|435/465|Reglas/paletas|Propiedad del motor|NO estética nioutfits|

No medibles: color físico/monocolor confirmado,foreground IoU,CER/WER/F1entidadesreal,identity retrieval,calidad estética,acceptance/correctionhumana real. Las métricas de color son proxies ylas de retrieval usan relevancia misma categoría, no mismo SKU.

## Validación de software

| Ejecución | Resultado | Alcance |
|---|---|---|
|pytest -m 'not model_integration'|103 passed, 5 failed, 1 deselected|Baseline falla; fixtures/expectativas desfasadas respecto del contrato actual|
|pytest -m model_integration|1 passed, 1 warning|Modelo real cacheado, imagen sintética beige; no accuracy|
|Ruff / mypy|8 issues /21 errores en7 archivos de47 fuentes|No corregidos|
|Mocks adaptados sólo en scripts/audit|9/9 checks|Diagnóstico; no vuelve verde la suite original|
|Controles sintéticos Python|92/92 checks|Incluye34 pares CIEDE2000, contratos, seguridad, compatibilidad; no92 fotos|
|Node helpers|23/23 passed,16 de IA|No render ni HTTP E2E|
|npm run build WSL /contenedor|exit1 EACCES /exit0 tsc+Vite8.0.3|Build normal solicitado,2918 módulos; sin modificar permisos/source|
|Laravel cliente|7/7 passed,13 assertions|Http::fake, no FastAPI real|
|Laravel suite estándar|fatal255 antes de tests|private run() choca con método final PHPUnit12;14 workflow no ejecutados|
|Laravel resto, excluyendo sólo archivo no cargable|95/95 passed,493 assertions,exit0|Base aislada; no declarar full suite verde|
|pgvector negativos|17/17 checks|Incluye reproducción del fallo combinación color/talla|
|HTTP FastAPI|10 endpoints privados ejercitados (incluye capabilities)|401/403/413/422 esperados diferenciados de éxitos|
|Browser|5 fallos500/7 casos válidos|Bug Laravel previo a inferencia;2 matching sí funcionan|

Las cinco fallas pytest: FakeModel sin embedding_metadata; fixture uniforme ahora abstiene foreground; identidad legacy incompatible; dominant_color null bajo uniformidad; revisión fake pinned-sha no corresponde alSHA inmutable resuelto. Se diagnosticaron sin editar tests originales. Fixtures adaptadas de auditoría son mocks y no estimaciones científicas. Controles SSRF usan DNS/HTTP mocks; no equivalen a un pentest de redes abiertas.

## Entorno y configuración

| Componente | Observado |
|---|---|
|WSL/runtime Python|Sistema3.12.3; servicio .venv3.11.15; uv0.12.1|
|PHP/Laravel|Principal8.5.5/Laravel13.1.1; UI aislada PHP8.5.9, diferencia explícita|
|Node/npm|WSL24.14.1/11.11.0; contenedor24.14.1/11.13.0|
|FastAPI/torch/torchvision|0.141.1/2.13.0+cu130/0.28.0+cu130|
|Transformers/NumPy/Pillow|5.14.1/2.4.6/12.3.0|
|EasyOCR/zxing-cpp/httpx|1.7.2/3.1.1/0.28.1|
|pytest/ruff/mypy|9.1.1/0.16.1/2.3.0|
|Docker/Desktop/Compose|29.7.2/4.90.0(238679)/5.1.4|
|DB principal|PostgreSQL18.3 Alpine; plpgsql1.0, sin vector/tablas IA;2 migraciones pendientes|
|DB vectores aislada|PostgreSQL18.3 Debian/pgvector0.8.2; sin volumen/puerto comercial|
|Build DB del repo|PostgreSQL18.6 Alpine/pgvector0.8.7; migrate limpio/rollback aislados|
|Redis|8.6.1 healthy general; ningún uso IA actual|
|GPU|RTX5060, driver591.86, CUDA13.0, compute12.0; disponible; nvidia8151MiB /torch8546484224bytes|

Principal: MATI_AI_ENABLED=true, vector enabled, token vacío, URL http://mati-ai:8000, timeout30/connect3. Python .env ausente: SigLIP/deviceauto/localonlytrue/OCRenabledtrue/downloadfalse son defaults. Revisión configurada main se resuelve alSHA inmutable existente para inferencia. configuration_presence.json registra variables ausentes/vacías/configuradas sin sus valores secretos. Tokens/env temporales sólo de procesos/contenedores propios; no se editó .env comercial.

Límite Laravel6MiB/40MP frente a Python10MiB/24MP defaults: bytes6–10MiB o píxeles24–40MP no tienen aceptación consistente. Todo el dataset quedó dentro de ambas capas. No se cambiaron límites.

FastAPI Docker build exit0, contenedor propio healthy en127.0.0.1:8002 con GPU/cacheRO: foto real HTTP200 partial/vector768/mismoSHA, load3207.971ms,total5031.601ms,pico856.83MiB. Native N130 se midió en127.0.0.1:8001 y no se confunde con esta muestra Docker N1. Build descargó dependencias, no nuevos pesos. GPU nvidia-smi en Docker yCompose dev+gpu config validaron disponibilidad. La aplicación principal no se recreó.

Dockerfile PGvector0.8.7 se construyó exit0; postgres:18-alpine flotante produjo18.6, frente al principal18.3. Contenedor nuevo/DBtesting/tmpfs sin datos comerciales; guards host+testing antes de migrate normal. Migraciones limpias exit0,5 tablas IA, embedding tipo vector sin dimensión fija. Rollbackstep1 exit0 elimina ai_product_embeddings y conserva extensión0.8.7, target087_postrollback.json. No se aplicó sobre volumen comercial. SQL/performance130 vectores anterior sigue siendo PG18.3/0.8.2, no adjudicar a0.8.7.

## Seguridad, privacidad y observabilidad

Bearer privado con comparación temporal segura; docs/OpenAPI/CORS desactivados, sólo /health público. Laravel auth/superadmin/throttle30/min. Fotos por upload/IDlocal con restricción de paths. Fetch externo sólo HTTPS con allowlist, DNS público pinned, límites/redirects/robots. Controles IPv4/IPv6/private/linklocal/metadata/credenciales/esquemas/redirects pasaron con fixtures controlados. QRURL sentinel real0peticiones. Derechosunknown/prohibited403; cuatro estados autorizados+aprobación200 por HTTP. Preparación técnica no genera prendas ni publica automáticamente.

Logs productivos omiten tokens/payload/OCR/fotos. OCR raw sí puede persistirse en ai_runs como dato de auditoría, distinto del log. Evidencia propia vive en storage ignorado; no tokens. Warning Transformers bos/eos fuera delvocabulario no impidió inferencia; no se demostró error matemático por ello. Browser500 sólo Server Error, sin stacktrace; servidor contiene la causa.

| Código | HTTP/estado | Sentido, mensaje y recuperación |
|---|---|---|
|UNAUTHORIZED_SERVICE|401 real|Token ausente/incorrecto; cliente degrada y mantiene edición manual|
|INVALID_REQUEST|422 real|Contrato/body/fields inválidos; revisar entrada|
|IMAGE_TOO_LARGE/PAYLOAD_TOO_LARGE|413 real|Límite bytes; no inferencia|
|INVALID_IMAGE/DECODE_FAILED|422, códigos en fuente|Foto inválida/decodificación; revisar archivo|
|RIGHTS_NOT_APPROVED|403 real|No preparación sin aprobación/derechos|
|OCR_UNAVAILABLE|200 partial en label real|Modelo no preparado; entrada manual sigue disponible|
|SERVICE_NOT_CONFIGURED|degraded cliente|Token principal vacío; no request remoto|
|CONNECTION_TIMEOUT/INVALID_RESPONSE|degraded, mocks cliente|Fallback seguro; distinguir de inferencia real|
|validateProhibitedWith inexistente|500 Laravel real|Bug anterior alclient; aviso genéricoUI, continúa manual|

error_catalog_static.json lista todos los códigos localizados en excepciones con archivo/línea y los marca como fuente, no todos ejecutados. X-Request-Id header/envelope coinciden por HTTP; suite cliente7mocks verifica correlación. Log Laravel registra request_id/op/duration/model/status/error, carece revision/pipeline; subrequestmatching usa IDnuevo sinparent, correlación completa limitada. OCRno preparado/malformedresponses/timeout no son errores de clasificación.

RSS20embeddings +128KiB/pico814.7MiB estable;8 requests/4 workers sinOOM. No demuestra ausencia de fuga a largo plazo ni capacidad de alta carga.

## Build y preservación

Baseline ProductCreate-Br6F6_ny.js no contenía asistente. Build normal autorizado regeneró public/build ignorado; ahora ProductCreate-tYfO0olu.js con SHA256 f6eb4220d78745726c7016330fc3dfe2c081d07850125ae523ff675275551ffc. HTTP=disco y browser aislado cargó ese chunk. Sí cambiaron assets servidos por el build solicitado; no sería exacto afirmar que no hubo ningún efecto observable. No se corrigió source/config/comercio durante la auditoría.

Snapshot SQL comercial read-only antes/después idéntico:68b7050be96007dba2c27976f1f15ebad0ed34652791899f85c93e88f2788868. Huérfanos comerciales0; huérfanos IA principal NO EVALUABLE porque tablas ausentes, no afirmar0. Preservación hashes427 fuentes y Git final en _preservacion_y_git.md/final_state.json. Todos los contenedores/procesos propios de auditoría se detienen al cierre, principales conservados.

Reportes04–07: módulos especializados;08:hallazgos;09:prioridades;10:defensa. Evidencias storage/app/ai-audit, scripts sólo services/mati-ai/scripts/audit. Ninguna corrección propuesta implementada; sin entrenamiento, tuning, productos alterados, commit ni push.
