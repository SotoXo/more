# Inspección Laravel y persistencia IA — previa a pruebas

Fecha local: 2026-10-04 (America/La_Paz). Resultado de lectura del código vigente, sin tests, inferencia, indexación, migraciones ni escrituras PostgreSQL. No demuestra runtime.

## Autoridad y flujo confirmados

Navegador → rutas `/superadmin/ai` con auth/superadmin/throttle30 por minuto (`routes/web.php:129,167–182`) → `AiController::actor()` verifica superadmin → taxonomías activas Laravel → `MatiAiClient` con Bearer/X-Request-Id/X-Contract-Version1, timeout30s/connect3s y sin redirects → FastAPI → Laravel valida, persiste sugerencias y devuelve sin vector.

Laravel mantiene autoridad comercial. `ProductCatalogService.php:36–68,78–109` guarda prendas/variantes/imágenes en la transacción normal y sólo después adjunta auditoría IA. `AiReviewService.php:122–130,178–224` usa savepoint y compara la decisión humana con el valor realmente guardado. Fallos de auditoría/vector no revierten el producto. Feedback no modifica campos comerciales (`AiController.php:267–280`).

Analyze/barcode/label admiten imagen upload o existing_image_id y nunca URL. CatalogImageReader restringe productos/, rechaza ../backslash/colon y valida6MB/40MP/MIME real (`:11–25`). ExternalReference recibe índices de un run propio y vuelve a validar bytes/SHA/12MB/40MP/MIME (`AiController.php:334–384`).

Preparación guarda original+thumbnail/card/detail WebP privados con SHA/MIME/tamaño comprobados y TTL24h (`AiPublicationService.php:16–54`). Token ligado a actor/aprobación/derechos/caducidad/no reutilización (`57–67`). Sólo guardado comercial publica derivados y vincula imagen/prenda (`70–97`). Cleanup elimina temporal vencido UUID bajo lock pero conserva auditoría (`CleanupMatiAiPrepared.php:28–46`); no tiene scheduler en routes/console.php.

GarmentCompatibilityService::compare es wrapper al endpoint privado con rules_version. MatiAiClient valida score0–100/version/enums/null si evidencia insuficiente. No se halló ruta ni consumidor productivo Laravel/UI; no declarar outfit/E2E implementado por existir un wrapper.

## Persistencia

- ai_model_versions: identidad única model_id/model_revision/pipeline_version.
- ai_runs: UUID=request_id, actor/prenda/modelo, input_hash, metadata/result, embedding JSON histórico, duración/error/TTL.
- ai_suggestions: field/value JSON/label/source/confidence_score/evidence; FK run.
- ai_human_reviews: prediction/confirmed_value/action/variant_index/stage form o saved; form_review_id único enlaza guardado con acción humana.
- ai_product_embeddings: prenda, imagen nullable, versión, run fuente nullable, SHA, dimensions, geometry/profile/L2/norms/metadata y vector nativo.

Migraciones incluyen FK coherentes: borrar imagen/run deja nullable el vínculo canónico; borrar prenda cascada vector; borrar modelo usado está restringido. Run sin prenda no es necesariamente huérfano (búsqueda o formulario sin guardar). Código: `2026_09_28_000001_create_catalog_ai_tables.php:10–60` y `2026_10_03_000001_create_ai_product_embeddings_table.php:17–49`.

AiReviewService::persist saca data.embedding de result y guarda JSON separado (`:16–31`). Modelo AiProductEmbedding oculta vector de serialización (`:10`). Controller elimina vector antes de JSON browser (`:95–96,414`). Similar sólo conserva identidad/resultados/hash sin duplicar vector query en ai_runs.

## Geometría pgvector

available exige feature enabled, driver pgsql, tablas y extensión vector (`CatalogEmbeddingStore.php:15–27`). Docker compila pgvector0.8.7 sobre PostgreSQL18 Alpine y conserva sail-pgsql (`compose.yaml:50–65`). FastAPI base sin puerto host; override dev sólo127.0.0.1:8001. GPU override reserva NVIDIA.

prepare deriva dimensiones de valores reales (1–16000), lista numérica finita, norma no cero, dimensión declarada coincidente y L2 estable. Modelo/revisión/pipeline completos; SHA revision40/64, no deterministic/unavailable. Perfil antiguo separado legacy por pipeline (`CatalogEmbeddingStore.php:34–96`). No vector(768) fijo ni compatibilidad por longitud sola.

Identidad completa: model_id+revisionSHA+pipeline_version+dimensions+embedding_version+preprocess_version+normalization. geometry_key resume perfil. storeRun verifica identidad contra FK modelo, SHA válido y asociación imagen/prenda (`99–123`). Unique `(prenda,content_hash,model_version,dimensions,geometry_key)`, ON CONFLICT DO NOTHING; force modifica fila canónica (`136–158`). CHECK DB norma~1/dimensión real.

Search exacto coseno `<=>`, similitud1-distancia en[-1,1]; CTE eligible/compatible materializados separan modelo/geometría/dimensiones antes de operar; ROW_NUMBER obtiene mejor imagen por prenda con orden estable. K12/max30/min_similarity0. Prenda activa, exclusión y filtros category/brand/season/color/size (`217–327`). No HNSW/IVFFlat. ANN no es requisito automático sin volumen/latencia.

SHA igual o similitud≥.98 marca possible_duplicate; es heurística visual, no identidad confirmada, probabilidad, gusto ni personalización. Endpoint similar sólo expone image/existing_image_id/limit, no restantes filtros (`AiController.php:74–78,125–128`).

## Indexación

Command `mati-ai:index-catalog`: batch1–500, limit1–10000, after-id para imagen, after-run-id por created_at+UUID para --from-runs, force sobre identidad canónica. Sólo imágenes principales locales de prendas activas; no galería completa. Avanza cursor incluyendo fallos y devuelve failure si alguno (`IndexMatiAiCatalog.php:19–25,30–81,123–136`). Backfill verifica SHA actual antes de relink image_id (`83–94`). Modo imágenes hace inferencia antes de comprobar existencia para conocer identidad real; fila idempotente no significa inferencia gratis (`97–117`). Guardado sólo indexa SHA analizado que sobrevivió en una imagen comercial (`AiReviewService.php:133–159`).

## Semántica de scores

ai_suggestions.confidence_score almacena señales heterogéneas: classification.similarity_score, color confidence heurístico, external.final_score. Limitado[-1,1], sin score_type/calibrated_probability de primer nivel (`AiReviewService.php:38–46,54–116`). Result/evidence puede conservar detalle; extraer sólo tabla suggestions pierde semántica. No equivale a accuracy/precision/recall/F1/certeza calibrada.

Matching exacto usa reglas score1 SKU/GTIN, .86 model_code, .05 conflicto marca/GTIN (`CatalogAiService.php:93–115`). matching blando FastAPI sobre candidatos enviados. Exact_identifier incluso con conflicto bloquea búsqueda externa y obliga revisión (`AiController.php:196,241–246`).

Feedback stage=form es evento explícito; stage=saved es valor realmente persistido. Accepted en formulario no demuestra guardado ni exactitud de predicción. Acceptance/correction rates necesitan denominador por decisiones únicas y distinguir etapas. Feedback no entrena ni calibra.

## Hallazgos estáticos y reproducción propuesta — sin aplicar correcciones

| ID | Prioridad | Archivo:líneas | Descripción y reproducción | Impacto/corrección propuesta |
|---|---|---|---|---|
| LAR-01 | P2 | CatalogAiService.php:35–60 | Soft limit100 sin order; exact limit100. Candidato válido fuera de conjunto no rankeado. | Recall acotado por truncamiento. Medir candidate coverage y retrieval más informado. |
| LAR-02 | P2 | CatalogAiService.php:35–60 | Matching no filtra est_pre/est_prv. SKU exacto inactivo puede devolverse. | Contrato de activos inconsistente con pgvector. Definir archivo etiquetado o filtro activo. |
| LAR-03 | P2 | CatalogAiService.php:132 | Duplicados SHA sólo primeras200 imágenes sin order. | Duplicado posterior no detectado. SHA indexado o recorrido exhaustivo por lotes. |
| LAR-04 | P2 | AiReviewService.php:162–174 | Replay de POST feedback crea otro form y, si vinculado, otro saved. | Retry infla eventos. Clave idempotente distinguible de nueva acción deliberada. |
| LAR-05 | P2 riesgo | CatalogEmbeddingStore.php:202–209 | Vigencia valida ID/propietario/path, no hash archivo. Bytes reescritos al mismo path dejan vector anterior vigente. Flujo normal usa nuevos paths. | Riesgo storage/directa modificación, no fallo demostrado del flujo normal. Objetos inmutables o invalidación por contenido. |
| LAR-06 | P2 riesgo | CatalogEmbeddingStore.php:254–259 | EXISTS color/talla separados: rojo/S+azul/M satisface rojo/M. | Falso cumplimiento de variante específica si ese contrato se requiere. Unificar EXISTS. Endpoint UI aún no expone esos filtros. |
| LAR-07 | P2 | AiController.php:395–418 | Run/suggestions se guardan sin transacción conjunta; fallo posterior devuelve fallback dejando registros parciales. | Auditoría incompleta. Transacción o estado explícito partial persistence. |
| LAR-08 | P3 | AiReviewService.php:43–45 | confidence_score heterogéneo sin semántica de primer nivel. | Exportación puede confundir señales/probabilidad. Añadir score_kind/método. |
| LAR-09 | P3 | MatiAiClient.php:70–75 / AiController.php:187 | Log sin revision/pipeline; match secundario nuevo request_id sin parent_id. | Correlación individual sí, end-to-end completa no. Parent correlation/identidad completa sin payload. |
| LAR-10 | P3 | routes/console.php / CleanupMatiAiPrepared.php:14–49 | Cleanup sin programación visible. | TTL bloquea acceso pero no retiro físico automático. Operación documentada/scheduler después de auditoría. |
| LAR-11 | P3 | GarmentCompatibilityService.php:19–32 / routes/web.php:167–183 | Sin consumidor/ruta UI. | Backend parcial distinto de feature E2E. Documentar y planificar consumidor aparte. |

Estos escenarios requieren reproducción aislada/runtime. No asignar P0/P1 por hipótesis sin evidencia comercial o ejecución.

## Contratos y observabilidad

MatiAiClient exige request_id coincidente, contract_version string1, enums/arrays/model strings. Compatibility y ColorimetryCatalogGuard validan profundo; matching sólo array matches y embedding se valida profundo al usar store. Taxonomías Laravel son activas y guard de color sólo conserva ID suministrado, iluminación fiable, ΔE/margen/foreground/confidence y swatch correcto. Fallback degraded en timeout/token/config/response/persistencia; pendiente resiliencia runtime.

Logs de estos servicios omiten payload, OCR, tokens y exceptions; sí incluyen request_id/op/duración/model_id/estado/error. ai_runs.result puede conservar OCR como dato oficial, distinto de log. FastAPI sin DB commercial según configuración/contexto; Laravel único escritor.

## Documentación vs implementación

IMPLEMENTACION/04_embeddings_pgvector.md describe código vigente y explícitamente validaciones no ejecutadas; su 768 deriva de checkpoint, no inferencia. No tomarlo como métrica de esta auditoría. Doc inicial `03_modulos_modelos_y_algoritmos.md:225` dice vector máximo2000; código vigente permite16000 (capacidad del tipo vs límites de indexación confundidos): documentación obsoletaP3. `04_contratos_api_y_datos.md:207,590–651,725` tiene known_images/ai_embeddings/esquemas propuestos; implementación posterior usa ai_product_embeddings/embedding/similar. Es diseño histórico superado, no razón para editarlo.

Redis está en Compose pero no hay uso Redis/jobs IA en servicios revisados; flujo síncrono. Cola/cache descritas como opciones/futuro. No se añadieron almacenes NoSQL por este módulo.

## Pendiente

Read-only DB: tablas/migraciones/extensión real, distribución taxonomías/productos, vector dims/norms/identity/hash/FK, reviews form/saved y SHA vigente, EXPLAIN ANALYZE de SELECT. Tests aislados de matching/feedback/contratos/resiliencia/persistencia. E2E browser y versión frontend servida. Indexación sólo tras muestras/conteos; coverage no se deduce de ai_runs.
