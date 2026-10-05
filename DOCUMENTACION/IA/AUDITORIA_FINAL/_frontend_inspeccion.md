# Inspección frontend y contratos, previa a pruebas

2026-10-04 America/La_Paz. Solo inspección estática; no certifica ejecución. HEAD fde26bd2d1aebb733733802001f6ce63d5599792, branch feature/ia-colorimetria.

## Flujo y autoridad confirmados

ProductCreate.tsx:398–405 monta GarmentIntelligentAssistant antes del form comercial y comparte draft useForm/catálogos reales. GarmentIntelligentAssistant.tsx:275–319 llama por Axios exclusivamente /superadmin/ai/{operación}, timeout 65 s, multipart image o JSON existing_image_id. Barcode y OCR usan captura dedicada. React no conoce token interno ni FastAPI ni DB.

routes/web.php:167–182 define capabilities/analyze/barcode/label/similar/match/external-search/prepare-publication/feedback/catalog-image/external-reference/prepared-images, en superadmin y throttle común 30/min. AiController.php:32–35 repite rol. CatalogAiService.php:17–30 obtiene taxonomías activas. MatiAiClient.php:38–48 envía bearer privado, X-Request-Id, contrato 1, bytes, taxonomías; sin redirects.

AiController.php:395–418 persiste ejecución, construye sugerencias/preview, oculta valores embedding y devuelve run_id. AiReviewService.php:16–31 separa embedding JSON de result y asocia modelo/revisión/pipeline. AiReviewService.php:34–119 solo propone IDs existentes para categoría/color/marca/talla/patrón, strings para datos OCR/externos.

garmentAssistant.ts:61–84 exige campo permitido, valor escalar, catálogo activo y talla compatible con categoría/segmento. Color/talla/GTIN van a variante seleccionada. No aplica precio/costo/inventario/stock. Lote categoría antes de talla, una propuesta por campo y conserva valores existentes (:119–136). Aplicación individual requiere reemplazo explícito y detecta campo cambiado durante confirmación (Assistant:255–265,398–403).

Feedback accepted/corrected/rejected: queue sessionStorage por usuario/formulario, TTL 24 h, máximo 100 al recuperar, timeout 10 s. Si sessionStorage está bloqueado queda memoria. Laravel restringe al propietario y no guarda comercio (AiController:267–280). AiReviewService:162–224 conserva stage=form y stage=saved usando valor realmente persistido y bloqueo. El formulario sigue editable durante inferencia; submit comercial no depende de FastAPI. Fallback degraded da aviso manual.

Preview externa solo ruta Laravel owner/run UUID; imágenes autorizadas necesitan estado owned/licensed/provider_authorized/public_domain y evidencia (helper:146–165; Assistant:434–444). QR URL solo texto sin navegación automática (Assistant:407–412). Enlaces http/https sin credenciales con noopener.

Colorimetría presenta paleta, roles, porcentajes aproximados, tono descriptivo separado del catálogo, advertencias de luz/fondo (Assistant:87–126). Coseno visual muestra [-1,1] con frase explícita de no probabilidad ni identidad (:141–155). Calidad sigue métricas técnicas, no medición de exactitud de IA. Compatibilidad cromática tiene endpoint privado FastAPI y servicio/cliente Laravel sin consumidor React/ruta UI. No hay generador actual de outfits/recomendación en frontend IA.

## Herramienta de color frontend que no es IA

ProductCreate.tsx:60–70 conserva selección manual por canvas/promedio RGB, distancia euclídea RGB, HSL y rangos de familias/swatch fijos. closestColorOption usa distancia RGB <=24; nombre sugerido <=95. No es ML ni DeltaE00; separar sus métricas del pipeline colorimétrico server-side.

## Hallazgos (sin corregir)

### FE-01 — P1 — UI/build no contiene el asistente actual

Archivo public/build/manifest.json, assets/ProductCreate-Br6F6_ny.js, resources/views/app.blade.php:17. Manifest/chunk mtime 2026-09-26 22:41:26 -0400; source asistente 2026-10-03 13:54 y helper 13:59. public/hot ausente. Chunk del manifest no contiene ni «Asistente inteligente» ni «Prendas similares por fotografía», «Colores de la prenda», «mati-ai:pending-reviews:», «Colorimetría de la prenda». Reproducir leyendo entrada ProductCreate del manifest, buscar cadenas y stat; corroborar HTTP real antes de build. Impacto: despliegue del manifest existente carece de la implementación completa. Propuesta futura: build/despliegue coherentes. En auditoría preservar baseline y compilar a directorio aislado sin reemplazar public/build.

### FE-02 — P2 — sugerencias visuales históricas tras cambiar foto

Assistant:190 borra analysisRun/checkedQuery al cambiar imagen, pero results permanece. :201–207 agrega todas las sugerencias/topSuggestions; solo latestColorimetry/latestSimilar usa analysisImageKey. :285 añade respuestas tardías aunque la foto cambió. Reproducir: analizar A, cambiar B, cards/aplicar lote antes de analizar B. Impacto: sugerencias de A pueden aplicarse a B sin identificación clara de imagen. Propuesta: ligar visibilidad/aplicación de todas las sugerencias visuales a identidad de imagen y distinguir histórico. Pendiente reproducción browser.

### FE-03 — P2 — feedback no idempotente por decisión

AiReviewService:162–175 siempre crea revisión form. Assistant:225–240 retiene decisión si HTTP falla y reintenta. Migration AI:48–58 tiene form_review_id único para stage=saved, pero no event/request ID único para form. Reproducir mismo POST feedback con mismo suggestion/action/value/variant en entorno aislado. Impacto: respuesta perdida puede duplicar eventos y acceptance/correction rate. Propuesta: idempotency key por decisión y métricas por última decisión válida. Pendiente prueba runtime.

### FE-04 — P3 — renombrado semántico de score

AiReviewService:45–56 guarda visual similarity_score bajo confidence_score junto a color/OCR/ranking y clamp [-1,1]. Assistant:324–328 muestra puntuación, no probabilidad. Reproducir comparando classification.top_k upstream vs suggestions Laravel. Impacto: API/DB ambiguas para análisis posterior; UI evita certeza. Propuesta: raw_score/score_kind explícitos.

### FE-05 — P2 — estados UI simplificados

Assistant:78 solo labels completed/partial/failed, pero MatiAiClient acepta insufficient_evidence/degraded. Assistant:303 ambos muestran Resultado parcial y :450 Revisar resultado. AiResponse tampoco tipa error. Reproducir fallback degraded/abstención insuficiente. Impacto: UI confunde servicio caído y abstención válida, no muestra error_code. Propuesta: mensajes/labels por estado sin bloquear form.

### FE-06 — P2 — guard frontend parcial de respuesta

Assistant:281–285 solo exige data object y status string; no valida arrays/nested enums/request_id/contract_version. suggestions object podría fallar al map/flatMap/render. Reproducir respuesta malformada en harness aislado. Laravel filtra envelope básico upstream pero no todos nested fields. Propuesta: validación de shape al límite.

### FE-07 — P3 — capabilities sin consumidor React

routes/web.php:168 y Assistant:339–379. grep resources/js no encuentra consumer capabilities ni compatibility. Botones usan archivo/busy, sin readiness. Impacto: se descubre indisponibilidad al intentar módulo; endpoint sigue siendo útil diagnóstico. Propuesta si UX lo requiere: consumir capacidades reales. No afirmar código muerto automáticamente.

## Contratos coherentes

OCR normalize_label:111–130 produce gtin_candidates strings, coincide React. Barcode checksum null/bool coincide UI. URLs imágenes restringidas a rutas locales protegidas. IDs activos revisados al aplicar. Embedding values ocultos en UI. Coseno no se presenta como probabilidad.

## Contraste documentos

IMPLEMENTACION/01,03,04,05 describen fuente acorde con flujos inspeccionados; build no incorpora esos cambios. IMPLEMENTACION/02 reconoce sin tests/build/inferencia, no certificado. Contratos V1/V2 contract_version=1.0 y tablas propuestas ai_analysis_results/ai_feedback son diseño histórico; código usa contrato 1 y ai_runs/ai_suggestions/ai_human_reviews. Outfits/recommendations de V2 siguen diseño.

## Validación posterior segura

16 tests helpers Node, no render/HTTP real. 7 MatiAiClientTest usan Http::fake. 14 MatiAiWorkflowTest usan SQLite :memory:, Storage fake, Http fake y CatalogAiService mock, no PostgreSQL/pgvector/inferencia real. phpunit.xml no fija DB_CONNECTION: imponer sqlite :memory: para suites y evitar RefreshDatabase contra comercio. Build ordinario npm run build altera public/build: verificar build a salida aislada.

