# Cómo funciona toda la IA: explicación previa a validación

Fecha de auditoría: 2026-10-04, America/La_Paz. Rama `feature/ia-colorimetria`; HEAD `fde26bd2d1aebb733733802001f6ce63d5599792`. Este primer entregable se creó tras inspección estática y ANTES de ejecutar tests, inferencia o benchmarks. Las afirmaciones describen código; no certifican operación. El alcance incluye cambios locales previos, conservados.

## Arquitectura, autoridad y datos

React (`resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx`, `resources/js/lib/garmentAssistant.ts`, `ProductCreate.tsx`) llama rutas autenticadas `/superadmin/ai/*`. `routes/web.php` aplica auth, rol superadmin y throttling. `AiController` valida archivo/imagen existente, consulta vocabularios y persiste auditoría. `MatiAiClient` agrega Bearer, request ID y timeouts y llama HTTP a FastAPI. El navegador no conoce el token ni consulta PostgreSQL ni FastAPI directamente.

Laravel es autoridad sobre IDs, estado comercial, productos, variantes, inventario y valores definitivos. FastAPI recibe bytes de una imagen y listas mínimas de categorías/colores/patrones/marcas/tallas o candidatos de matching. No recibe DSN PostgreSQL y no guarda productos. Devuelve envelope contract `1`, pipeline `registration-1.2.0`, estado, data, warnings, abstentions, model/revision, timing_ms y human_review_required=true. La revisión humana decide aplicar sugerencias al formulario; guardar usa el flujo comercial normal.

Persistencia: `AiReviewService`, `AiRun`, `AiSuggestion`, `AiHumanReview` y migración `2026_09_28_000001_create_catalog_ai_tables.php` conservan predicción original, sugerencias por campo, aceptación/corrección/rechazo y valor guardado. `ai_model_versions` identifica modelo/revisión/pipeline. `ai_runs.embedding` conserva histórico interno; el vector se elimina de respuestas React. `CatalogEmbeddingStore` mantiene tabla canónica `ai_product_embeddings` cuando está disponible. `AiPublicationService` mantiene originales/derivados privados temporales y los adjunta al guardado aprobado; el TTL configurado es 24 horas. Que existan migraciones no significa que estén aplicadas: eso se comprobará después.

## Modelos, CPU y GPU

`services/mati-ai/src/mati_ai/core/config.py` y `core/model_manager.py`: provider default `siglip`, checkpoint `google/siglip-base-patch16-224`; alternativa configurable `clip`, `openai/clip-vit-base-patch32`. No se asume el modelo activo sólo por defaults: caché, capabilities e inferencia lo resolverán. Carga perezosa de AutoProcessor/AutoModel con trust_remote_code=false, local_files_only=true. Se exige revisión inmutable resuelta; main no es por sí mismo identidad reproducible. Un modelo por worker; Docker usa workers=1. Lock de carga/inferencia con timeout 2s, retry tras fallo 60s. Device auto/cuda elige CUDA si disponible; fallos de movimiento o inferencia CUDA admiten CPU. Se usa inference_mode, sin entrenamiento; no se fija media precisión explícitamente.

CPU realiza validación, calidad, segmentación/color, códigos, matching, compatibilidad, extracción HTML, publicación y EasyOCR. EasyOCR usa es/en, gpu=false, thumbnail hasta 1800, readtext detail=1, paragraph=false, workers=0. OCR bruto y extracción normalizada son etapas distintas; el modelo OCR y sus pesos requieren disponibilidad local.

## Inventario confirmado en código

| Módulo | Archivo principal (bajo services/mati-ai/src/mati_ai salvo indicación) | Técnica / algoritmo | Input → output | Persistencia / métrica potencial | Cómputo |
|---|---|---|---|---|---|
| Validación técnica | vision/validation.py | PIL decode, límites, EXIF/sRGB, SHA y hash perceptual | bytes → imagen/metadata | ai_runs / rejection rate | CPU |
| Calidad | vision/quality.py | CV clásica, heurísticas | RGB → blur/exposure/contrast/warnings | ai_runs / warnings | CPU |
| Foreground | vision/segmentation.py | regiones de fondo conectadas al borde en Lab, CIEDE2000, morfología | RGB/Lab → máscara/evidencia/abstención | ai_runs / coverage; IoU requiere máscaras humanas | CPU |
| Color / colorimetría | vision/color.py, color_distance.py | cuantización/clustering determinista y CIELAB D65, DeltaE00 | foreground → primary/palette/family/temperature/lightness/chroma | ai_runs / color exact/family/DeltaE proxy | CPU |
| Categoría | vision/classification.py, core/model_manager.py | Vision Language Transformer, zero-shot | foto completa + prompts → top3/abstención | sugerencias / Accuracy/F1 | GPU o CPU |
| Patrón | vision/attributes.py | zero-shot mismo embedding multimodal | foto + patrones → candidato | sugerencias / F1 requiere labels | GPU o CPU |
| Embedding | contracts/embedding.py, ModelManager | features visuales proyectadas normalizadas L2 | foto → vector/metadatos | ai_runs + ai_product_embeddings / norma/dimensión | GPU o CPU |
| Similitud/duplicados | vision/embeddings.py; Laravel CatalogAiService/CatalogEmbeddingStore | SHA exacto, hash perceptual metadata y coseno; no equivalentes | archivo/vector → candidatos | auditoría / Recall@K con relevancia humana | CPU/SQL |
| pgvector | app/Services/AI/CatalogEmbeddingStore.php | búsqueda vectorial exacta con distancia coseno | vector compatible → K prendas | PostgreSQL / agreement y latencia | SQL CPU |
| Barcode / QR | identification/codes.py | OpenCV + ZXing-C++, checksum GTIN determinista | imagen → valor/symbology/box/checksum | ai_runs / exact decode | CPU |
| OCR | identification/ocr.py | EasyOCR, deep learning preentrenado | etiqueta → texto/regiones/confidence | ai_runs / CER/WER requiere transcripción | CPU |
| Normalización OCR | identification/normalization.py | regex + difflib + reglas | texto → marca/talla/modelo/SKU/GTIN/composición | sugerencias / field F1 | CPU |
| Matching interno | identification/matching.py; Laravel CatalogAiService | reglas + similitud textual/visual | query + candidatos → ranking | ai_runs / identidad Top1/MRR requiere pares | CPU |
| Enrichment / scraping | enrichment/service.py, adapters/manual.py, adapters/html.py | extracción estructurada y ranking; NO red neuronal | consulta → candidatos externos | ai_runs / success/parser rates | CPU/red |
| Safe fetch | enrichment/safe_fetch.py | allowlist/IP/DNS/redirect/robots/límites | URL autorizada → recurso | controles / blocked unsafe requests | CPU/red |
| Publicación | publication/pipeline.py; Laravel AiPublicationService | orientación/sRGB/EXIF/resize/WebP/hash | imagen + derechos/aprobación → derivados | temporales privados + producto al guardar / size/latency | CPU |
| Feedback | app/Services/AI/AiReviewService.php | regla y auditoría humana | acción → review/form + saved final | ai_suggestions/ai_human_reviews / tasas operacionales | Laravel/SQL |
| Compatibilidad cromática | compatibility/engine.py, rules.py | reglas matemáticas versionadas | dos colorimetrías → score 0–100/explicación | endpoint FastAPI + wrapper Laravel; sin UI detectada / propiedades | CPU |
| Outfits/recomendación personal/RAG/VTO/generación | búsqueda de fuentes y rutas | NO IMPLEMENTADOS | — | V2 es propuesta, no funcionalidad ejecutable | — |
| Redis/colas IA | Compose y config Laravel | infraestructura general presente; flujo IA síncrono, sin jobs IA detectados | — | no se añade queue IA | — |

## Qué significa cada score

Visual: `ModelManager.infer` normaliza image_embeds y text_embeds L2 y calcula `(vectors @ texts.T)[0]`: coseno, teóricamente [-1,1], mayor=mayor alineación. No usa logits/sigmoid/softmax para la clasificación, aunque SigLIP haya sido entrenado con pérdida sigmoide. NO ES PROBABILIDAD y no hay calibración implementada. `classify` ordena top3 y abstiene si top1<0.08 o margen top1-top2<0.005. Umbrales hardcoded heurísticos; no hay evidencia experimental de selección en código. Una categoría puede conservar top3 aunque abstenga.

Prompts: una sola frase por clase, `This is a photo of {alias}.`; usa únicamente aliases[0] si Laravel lo envía, de otro modo tabla inglesa. POLERA→t-shirt, POLOS Y KNITWEAR→polo shirt or knitwear, CONJUNTOS→matching clothing sets. Sin ensemble/media de prompts. Clases compuestas/solapadas son posibles riesgos, no causas demostradas antes de medir.

Matching: SKU exacto=1; GTIN válido exacto=0.98; modelo exacto=0.94 con marca concordante o 0.86 sin ella. Otros=min(0.79, 0.35·texto+0.32·visual+0.45·SHAexacto+0.05·marca+0.04·categoría+0.03·color). Conflicto GTIN limita a 0.05; modelo multiplica 0.4; marca 0.5. SequenceMatcher mide parecido textual, no semántica NLP aprendida. Coseno sólo se añade si toda identidad embedding coincide. Score es regla de ranking, no probabilidad de identidad.

Calidad: preview hasta 1024; blur=Var(Laplaciano(gris)), warning si <50. Sombras=fracción gris<10 (>0.55 warning); luces=fracción gris>245 (>0.65); contraste=P95-P5 (<30); resolución mínima <300 warning. Son heurísticas, no accuracy de calidad.

Foreground: banda exterior 4%, agrupación Lab por bins [6,8,8], modo/mediana del fondo, DeltaE00 y conectividad. Fondo uniforme exige soporte>=0.82, P90<=7, todos lados>=0.65 y 3 lados>=0.82. Threshold fondo clamp(P90+3,6,11). Área central candidata>=4%, foreground>=8%, componente dominante>=0.88, sin otro componente>=3.5%, fuga borde<=0.05, área<=0.88, centro>=0.45, offset<=0.22, distancia foreground mediana>=10. Confianza=min(0.94, .30·support+.20·max(0,1-P90/10)+.20·min(1,dist/22)+.15·share+.10·min(1,centro/.8)+.05·min(1,edge/.3)); sólo >=0.70 usa máscara, erosionada 3×3. NO es detector semántico de prendas, ni probabilidad de segmentación; fotos editoriales/personas pueden abstener o contaminar. Sin máscaras humanas no se medirá IoU.

Colorimetría: Lab D65 separa L* luminosidad y a*/b* oponentes; croma C*=sqrt(a*²+b*²), hue=atan2(b*,a*). HSV contribuye descripción/compatibilidad. CIEDE2000 compara colores Lab perceptualmente: menor distancia significa mayor similitud cromática. El mapeo al catálogo usa swatches canónicos y sus umbrales de código; no son universales. family/temperature/confidence son reglas. Color comercial confirmado es PROXY, nunca medición espectrofotométrica. Multicolor se evalúa separado del single-color.

Compatibilidad: rules.py define hue RYB artístico interpolado desde HSV (distinto hue Lab), monocromático 18°, análogo 55°, complementario 180±25°, triadas tolerancia 22°. Combina temperatura 0.10, luminosidad 0.20, hue 0.55, contraste 0.15; ajustes por saturación y complejidad cap 0.20. Score 0–100 heurístico versionado, NO accuracy estética ni recomendación personalizada. Paletas sin foreground/coverage suficiente pueden abstener. Primary/palette/temperature/lightness/chroma/family pueden servir a futuro motor de outfits; éste no existe aún.

## Embeddings y PostgreSQL

PostgreSQL sigue siendo motor relacional, con transacciones, FK, índices y JSON. pgvector añade tipo vector y operadores; no sustituye PostgreSQL. Migración nueva usa `embedding vector` SIN N fijo; CHECK exige vector_dims(embedding)=dimensions, dimensión 1–16000 y norma 0.999–1.001. La dimensión debe obtenerse por len(vector) real y confrontarse al esquema vivo; no se inferirá por documentación. Vectores CLIP/SigLIP, distinta revisión, pipeline, dimensión, embedding_version, preprocess_version o normalización no deben mezclarse.

Query `embedding <=> CAST(? AS vector)` devuelve distancia coseno; similarity=1-distance. CTE MATERIALIZED filtra espacio compatible antes del operador, luego mejor imagen por prenda y orden estable. No hay HNSW/IVFFlat; búsqueda exacta O(M·D) conceptual sobre M vectores compatibles. Nearest neighbor significa vecinos de menor distancia, no identidad ni gusto. Se auditarán datos, índices y agreement contra brute force. Indexación sólo genera datos IA; esta auditoría conservará datos comerciales y usará instalación aislada para escrituras de prueba.

## Códigos, URLs, OCR, fuentes e imágenes

Decoder ofrece EAN13/EAN8/UPCA/UPCE/CODE128/QR; soporte real se comprobará con fixtures conocidos. Checksum válido valida estructura, no existencia ni identidad del producto. QR URL se devuelve como dato; no se navega por detección. OCR bruto usa red; regex/normalización/extracción posterior son reglas y pueden fallar aun con texto legible.

Enrichment requiere fuente configurada con autorización/allowlist: tener adapters no prueba fuente operativa. SafeFetch aplica protocolos HTTPS (fetch externo rechaza HTTP), redes públicas, redirecciones y límites; se probarán privados/protocolos peligrosos con fixtures seguros. Ranking externo y derechos requieren contraste. Unknown admite referencia, no publicación; prohibited se bloquea. Preparación genera WebP thumbnail/card/detail sin generación de contenido, controla aprobación/derechos y conserva procedencia. Se auditarán tamaños, EXIF, perfil/color y no ampliación.

## Fallos y límites

MatiAiClient convierte token vacío/disabled, timeout/conexión, HTTP fallido o respuesta inválida en fallback seguro. Formulario manual no debe depender de IA; se verificará navegador. Pipeline de análisis puede retornar partial conservando calidad/color cuando modelo falta. OCR ausente retorna partial con OCR_UNAVAILABLE; clasificador abstiene por umbral/margen. Scores individuales no son métricas del dataset; confidence OCR no es CER/WER, coseno no es Recall@K, compatibilidad no es estética objetiva, aceptación humana no es exactitud científica.

No hay entrenamiento/calibración ni RAG, generación de imágenes/prendas, outfits o recomendaciones personales en el código actual. Las guías V2 describen propuestas. Los resultados de validación, límites por entorno, muestras y fallos se documentarán en 01–10 y evidencia raw, sin corregir el sistema.

## Fuentes de inspección y discrepancias iniciales

Se inspeccionaron documentación IA original/V2/IMPLEMENTACION y fuentes de cada dominio mediante revisión paralela; notas detalladas `_python_inspeccion.md`, `_laravel_inspeccion.md`, `_frontend_inspeccion.md` complementan este mapa. La UI compilada del 26/09 no contiene funciones React del 03/10; se conserva evidencia previa a build. Compatibilidad tiene FastAPI/wrapper pero sin ruta/UI Laravel. El matching limita candidatos y la vigencia vectorial por ruta no comprueba bytes sustituidos en el mismo path. Son hallazgos estáticos pendientes de evidencia operacional, no correcciones autorizadas.
