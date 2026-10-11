# Arquitectura RAG local

La fundación implementa INGEST → CHUNK → EMBED → STORE → RETRIEVE, sin generación de respuestas. Laravel controla acceso, fuentes, persistencia y búsqueda; FastAPI sólo produce embeddings textuales locales. El modelo aprobado por el gate textual es intfloat/multilingual-e5-small, 384 dimensiones L2, CPU. El recuperador no superó el gate preregistrado de abstención: se conserva como prototipo evaluable en consola y el endpoint falla cerrado sin chunks. La migración y contratos fueron comprobados en PostgreSQL efímero; el despliegue, índice y evaluación reales se consignan en [05_evaluacion_retrieval.md](05_evaluacion_retrieval.md) con su evidencia de cierre.

No cambia la estrategia visual: clasificación FULL, SigLIP FULL canónico 768 y guard comercial de color activo. Los vectores textuales usan almacenamiento e identidad independientes; RAG no es clasificador visual, autoridad comercial dinámica ni selector de prenda.

## Autoridad y flujo

~~~mermaid
flowchart LR
    A[Superadmin autenticado] --> B[Laravel: validar consulta y permisos]
    B --> C{Dato dinámico}
    C -->|Sí| D[insufficient_evidence / tool_required]
    C -->|No| P{Recuperación aprobada}
    P -->|No| J[RAG_GATE_NOT_APPROVED / chunks vacíos]
    P -->|Sí| E[FastAPI: embedding textual CPU]
    E --> F[Laravel: validar identidad, dimensión, SHA y L2]
    F --> G[PostgreSQL: vector exacto y FTS español]
    G --> H[Gate de evidencia y ranking RRF]
    H --> I[Chunks con procedencia o abstención]
~~~

El bypass de evaluación del diagrama exige configuración explícita en memoria y ejecución en consola. No convierte el endpoint web bloqueado en un recuperador aprobado.

IDs/nombres activos y referencias comerciales pertenecen a PostgreSQL/Laravel. La semántica extendida pertenece a [mati-taxonomy-v1.json](../../../services/mati-ai/resources/taxonomy/mati-taxonomy-v1.json), revisable y versionada. Las definiciones iniciales tienen estado pending_mati_domain_review; validar sus IDs no certifica aprobación experta de su significado. Su alcance y ambigüedades están en [01_taxonomia_mati.md](01_taxonomia_mati.md).

FastAPI no recibe credenciales PostgreSQL ni decide aplicar cambios. No hay LLM, SQL generado, entrenamiento, actualización de pesos por evento ni UI nueva. Los fragmentos recuperados son DATA; no ejecutan instrucciones, no se abren URLs de fuente ni paths aportados por usuario.

## Corpus curado y chunking

[Corpus-v1.json](../../../services/mati-ai/resources/rag/corpus-v1.json) contiene 23 documentos y 23 chunks semánticos. Su SHA256 congelado es d1fd24f3a18e9c169c6197e6bf12da8a663359010ffc271c33918eb8893a3fe2. No se indexan logs, auditorías crudas, código, tests, Git ni todo el repositorio.

El recurso fijo contiene schema_version mati-rag-corpus-1, corpus_version 1.0.0 y documents. Cada documento tiene ID lógico, document_type, título, source, source_version, metadata y chunks. Cada unidad conserva ID estable, sección, contenido y hashes. El input de embedding se define exactamente como title + LF + section + LF + content.

Chunk content_hash es SHA256 UTF8 del contenido exacto. Document content_hash es SHA256 de inputs de sus chunks, en orden y separados por dos LF. Snapshot_hash agrega el documento completo mediante JSON canónico ordenado, preservando orden de listas; detecta cambios silenciosos en procedencia, aliases o mappings aunque el texto no cambie.

La unidad de taxonomía es categoría/definición, comparación o conocimiento pequeño. No se corta mecánicamente por caracteres. El lector impone límites y rechaza hashes incoherentes, IDs duplicados, fuentes declaradas inactive y unidades vacías/largas; truncación reportada por FastAPI impide activar el corpus.

El comando autorizado es mati-ai:rag-index. No admite argumento de path, URL ni una fuente proporcionada por navegador. Sólo lee services/mati-ai/resources/rag/corpus-v1.json. Las fuentes son referencias de procedencia, sin descarga ni ejecución. La validación comprueba que category_ids referenciados existen activos en el catálogo, sin crearlos ni renombrarlos.

## Identidad textual aprobada

| Campo | Identidad |
|---|---|
| model_id | intfloat/multilingual-e5-small |
| revision | 614241f622f53c4eeff9890bdc4f31cfecc418b3 |
| dimensions | 384 |
| vector_space | text |
| normalization | l2 |
| preprocess_version | text-nfc-strip-meanpool-l2-e5querypassage512-1 |
| pipeline_version | text-embedding-1.0.0 |
| Runtime | CPU; instrucciones query/passage del perfil |
| Piso coseno preregistrado | 0,74; señal no calibrada |

[Model-profile.json](../../../services/mati-ai/resources/rag/model-profile.json) final tiene SHA256 ca82f5b59822da6666eb02fe921c253f3a73b1d0a76f053be6d73f0d095d498b, approved=true, retrieval_approved=false e identidad explícita. El perfil usado al medir la evaluación tenía SHA256 4ac11cac22a6eda86ca1cccf57daa086a9425c3acb20fbc3f3d4ec121793be61, conservado en los resultados originales. El cierre añade únicamente metadatos rag_gate_state=failed_negative_abstention y rag_gate_decision_sha256; mantiene modelo, revisión, prefijos query/passage, dimensión, preprocess y piso coseno. Ausencia de aprobación/perfil impide indexar y recuperar. El perfil no se elige por el texto del request. La aprobación del embedding es distinta de retrieval_approved: el retriever HTTP exige esta segunda aprobación y falla cerrado con RAG_GATE_NOT_APPROVED/chunks=[] si falta o es false. El harness de evaluación puede habilitar config rag.evaluation_mode=true sólo en memoria y ejecutándose en consola; ese bypass no está disponible desde un request web.

Modelo y gate se documentan en [02_investigacion_embeddings.md](02_investigacion_embeddings.md) y [03_benchmark_embeddings.md](03_benchmark_embeddings.md). La comparación textual no recalibra ni sustituye los prompts/revisión de SigLIP. Dimensión coincidente tampoco volvería comparables los espacios visual y textual.

## Tablas independientes

| Tabla | Función y claves |
|---|---|
| rag_model_versions | Identidad textual: modelo, revisión, pipeline, dimensiones, L2, preprocess y space_key único. Vector_space debe ser text. |
| rag_documents | Documento lógico más corpus_version únicos; source/source_version, content_hash, snapshot_hash, status y metadata. |
| rag_chunks | Unidades inmutables ligadas a documento; chunk_key/chunk_index únicos por documento, sección, texto, hash y search_text. |
| rag_embeddings | Chunk + modelo únicos; input_hash, dimensiones, norm, status y vector privado. |

La migración crea únicamente estas cuatro tablas y requiere pgvector ya instalado. Vector es flexible en dimensión, con CHECK vector_dims=dimensions y L2 entre 0,999 y 1,001. FK compuesta modelo/dimensiones exige que longitud declarada corresponda a la identidad textual. Normalización/preprocess residen en rag_model_versions, no se deducen de un nombre de modelo.

No usa ai_product_embeddings ni registra el modelo textual en ai_model_versions visual. No instala/elimina la extensión compartida, no agrega HNSW/IVFFlat, Redis ni otro motor vectorial. Rollback elimina sólo sus tablas en orden de dependencias.

## Activación transaccional e idempotencia

El ingestor valida corpus e identidad y obtiene todos los vectores antes de abrir la transacción de activación. Un fallo o truncación previa no desactiva la versión vigente. Dentro de la transacción se toma advisory lock, se valida inmutabilidad de nuevo, se materializa la versión completa y se activa junto a sus embeddings; documentos/embeddings anteriores quedan inactive.

Repetir el mismo snapshot, modelo y unidades completas devuelve skipped sin duplicar ni ejecutar inferencia innecesaria. Cambiar contenido, metadata o el conjunto de documentos dentro de corpus_version existente se rechaza con IMMUTABLE_CORPUS_VERSION_CHANGED. Un cambio real requiere nueva versión; sus chunks/vectores tienen nuevas identidades y las filas anteriores se conservan inactivas.

No se actualiza el contenido de un chunk dejando su vector anterior activo. La búsqueda exige simultáneamente documento activo, embedding activo y modelo/perfil compatible. La activación es global para el pequeño snapshot curado, no una edición parcial desde UI.

## Contrato interno de embedding

POST /api/v1/rag/embed usa token interno existente y envelope versión1. Request: schema_version mati-text-embed-request-1, request_id UUID, texts de 1 a 16 e input_type query/document. No requiere imagen ni accede al catálogo.

Cada salida tiene index, vector, dimensions, norm, text_sha256 y truncated; data.input_type debe coincidir exactamente con query/document solicitado. Esto impide confundir los prefijos E5 aunque el texto fuente tenga el mismo SHA. Data.model y model superior conservan identidad textual completa. Laravel comprueba UUID, completed, orden/cantidad, SHA UTF8 exacto de cada input, identidad/revisión/preprocess/L2/dimensión y valores finitos normalizados. Cualquier discrepancia falla cerrado.

El SHA identifica texto fuente exacto; NFC/trim e instrucciones forman parte del preprocess declarado. Inputs query demasiado largos se rechazan, no se truncan en silencio; Laravel también limita consulta a 1000 caracteres, batch/bytes y rechaza truncated=true antes de persistir. No se imprimen token, queries ni fragmentos en mensajes técnicos.

## Búsqueda exacta, FTS y abstención

POST /superadmin/ai/rag/search hereda auth, superadmin y throttle 30/min. Acepta query, limit 1..10, mode vector/hybrid y filtros document_type/source_version/corpus_version. Sólo el estado activo es recuperable; no hay parámetro para exponer embeddings o fuentes arbitrarias.

SQL usa parámetros para texto, vector y filtros. Una CTE materializada restringe modelo textual, dimensión y estados antes de calcular distancia coseno; evita operar espacios incompatibles. Se combina exact cosine search con to_tsvector/websearch_to_tsquery español, sin SQL generado ni ANN.

El gate de admisión está fijado antes de resultados. Vector exige piso coseno 0,74 para E5; híbrido sólo admite candidatas que cumplan piso o cuyo documento de categoría tenga nombre/alias curado exactamente igual a la consulta completa normalizada. Una palabra coincidente en una consulta OOD no rescata por sí sola un fragmento débil.

Normalización léxica compara caso/acentos/puntuación/espacios. El rescate exacto pertenece sólo a taxonomy_category. RRF usa constante60 y rankings vectorial/léxico de candidatas admitidas, con desempate estable por chunk_id. Cosine_similarity y ranking_score RRF son campos distintos; ninguno se presenta como porcentaje correcto.

Stock, precio, disponibilidad, reserva, pago e inventario detectados por guard determinista devuelven insufficient_evidence, tool_required=true y chunks=[] antes de embed. La guardia no convierte RAG en parser universal de intención ni consulta hechos dinámicos. Sin evidencia o con servicio/almacenamiento no disponible devuelve chunks=[]; no genera una respuesta de relleno.

## Respuesta y permisos

La respuesta tiene query_id, status, mode, chunks, score_kind, ranking_score_kind, generated_answer=false, tool_required, reason, identidad textual y timing. Cada chunk incluye document_id/key, chunk_id/key, título, excerpt, document_type, sección, source/source_version/corpus_version, content_hash, review_status, similarity y rank. El vector completo nunca llega al cliente.

La sesión superadmin controla acceso. Invitado JSON recibe401; vendedor es denegado mediante redirección302 al panel vendedor por middleware preexistente, sin ejecutar retrieval. No se alteró esa política global para esta fase.

## Verificación implementada y alcance

La suite final consolidada pasa38 pruebas/150 aserciones en PostgreSQL18.6 y pgvector0.8.7 efímeros. Incluye34 pruebas de contratos/permisos y cuatro de persistencia, con APP_ENV testing y assertions de identidad antes de mutar. Se aplicó sólo la nueva migración RAG; cada prueba de datos termina en rollback.

La cobertura comprende contrato HTTP fake, espacio visual rechazado, dimensión/norm/SHA/índice/truncación/tipo query-document, permisos, promoción CLI frente HTTP, filtros/path arbitrario, hashes, OOD léxico, guard dinámico sin request remoto, idempotencia, nueva versión/inactivación, membresía inmutable, fallo precommit, CHECKs y parámetros SQL. La revisión independiente encontró y permitió corregir dos defectos reales: tipo de embedding no validado y lista de documentos mutable bajo la misma versión; no se cambiaron corpus, ranking ni umbrales.

La fixture aislada de tres vectores3D coincidió Top-K SQL/NumPy3/3; máximo error coseno1,1920928910669204e-08. Es prueba mecánica del store/SQL, separada del benchmark ganador384D. Artefactos finales: storage/app/ai-rag/isolated-persistence-pass3.json, sql-isolated-fixture-pass3.json y sql-numpy-isolated-agreement-pass3.json.

Se conserva el primer intento isolated-persistence.json, que falló preparando extensión antes de tests; se reforzó readiness TCP del helper. No fue fallo del modelo ni de la migración. El contenedor semantic-rag-testing/semantic_testing, sin puertos publicados ni volúmenes comerciales y con contraseña sólo en memoria, fue retirado al finalizar. La segunda y tercera ejecuciones conservaron evidencias separadas.

El índice real contiene un modelo textual y23 documentos/chunks/embeddings. Primera ingestión indexó23; repetición indexó0 y omitió23 con counts/huellas de filas idénticas, según storage/app/ai-semantic/rag-index-idempotence.json. Las48 queries reales coincidieron en Top3 SQL/NumPy48/48, error coseno máximo2,726438e-07; esta evidencia384D es distinta de la fixture3D aislada.

La evaluación híbrida real sobre48 positivas obtuvo Recall@1=0,736111, Recall@3=0,930556, MRR@3=0,913194 y nDCG@3=0,906116; cobertura100%. Las16 negativas sólo se abstuvieron9/16=56,25%, con siete falsas recuperaciones OOD. Las ocho dinámicas fueron rechazadas. P95 observado: total21,100ms, embedding14,278ms y búsqueda7,092ms; no acredita cargas concurrentes ni hardware distinto.

El gate exige abstención negativa≥90% y falla en ambos modos vector/híbrido, aunque ranking positivo, latencia y procedencia cumplan. retrieval_approved permanece false; HTTP responde insufficient_evidence/RAG_GATE_NOT_APPROVED/chunks=[] sin inferencia. La decisión está en storage/app/ai-semantic/retrieval-gate-decision.json; retrieval-summary.json y retrieval-results.json preservan resultados completos, sin ajuste posterior de umbral ni dataset. Aprobar el modelo de embedding no equivale a aprobar recuperación útil y segura.

La guardia final SELECT-only storage/app/ai-semantic/catalog-final.json, comparada con catalog-before.json, verifica34 huellas comerciales iguales,134 SHA de imágenes iguales y134 identidades/valores canónicos SigLIP intactos. Los counts visuales siguen4 modelos/147 runs/11 sugerencias/2 reviews/134 embeddings; las11 FK y dimensión768/L2 permanecen válidas. post-review-checks.json confirma repetición principal post-fix con0 indexados/23 omitidos, huellas idénticas y respuesta pública cerrada. visual-after.json conserva clasificación, vector768 sin cambio de valores, guard de color, OCR y GPU visual. Estas evidencias de cierre son independientes de las fixtures aisladas.

La evaluación de48 positivas/16 negativas, métricas/latencia e idempotencia reales se describe en [05_evaluacion_retrieval.md](05_evaluacion_retrieval.md). [06_dataset_futuro_target_selection.md](06_dataset_futuro_target_selection.md) diseña eventos sin capturarlos ni entrenar. [07_limitaciones_y_siguiente_fase.md](07_limitaciones_y_siguiente_fase.md) distingue RAG/Tools/LLM futuro y las precondiciones P2.
