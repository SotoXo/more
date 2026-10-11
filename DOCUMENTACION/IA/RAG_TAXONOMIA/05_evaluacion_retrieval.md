# Evaluación real de retrieval

**Gate RAG: FAIL. Modelo textual: E5-small aprobado. Búsqueda pública: NO PROMOVIDA y bloqueada.** El prototipo recupera bien conocimiento positivo, pero no satisface abstención:9/16 negativas (56,25%), frente a≥90% preregistrado. No se modificaron corpus, consultas, relevancia, candidato, prefixes, floors ni ranking después de ver resultados. `retrieval_approved=false` es independiente de `approved=true` del modelo textual.

## Condiciones e inputs

Se evaluó la base principal local DB laravel / host pgsql mediante Laravel→FastAPI autenticado→CPUworker→PostgreSQL 18.6+pgvector 0.8.7. El harness explícito CLI habilita evaluación del prototipo; una petición web no puede activar ese bypass. No se guardaron productos ni runs visuales. El corpus y evaluación son una propuesta pequeña Codex pendiente de revisión Mati; no equivalen a relevancia humana independiente.

- Protocolo SHA256 `f60608c413e19989cbdee9cb3391a1ad724b5ab9fd870c1c21e55f3f61068836`.
- Corpus SHA256 `d1fd24f3a18e9c169c6197e6bf12da8a663359010ffc271c33918eb8893a3fe2`:23 documentos / 23 chunks semánticos, versión 1.0.0.
- Evaluación SHA256 `c09509b42e33622e9613ba2f64207947d78b62260d355f63da3c951304b6bdb4`:48 positivas (31 ES / 17 EN) y16 negativas (8 sin evidencia / 8 dinámicas).
- Modelo`intfloat/multilingual-e5-small`, revisión`614241f622f53c4eeff9890bdc4f31cfecc418b3`,384 dimensiones TEXT/FP32/L2,4 hilos CPU, preprocessing `text-nfc-strip-meanpool-l2-e5querypassage512-1`, pipeline `text-embedding-1.0.0`.

Tipos activos: color_knowledge=2, comparison=6, taxonomy_category=11, technical_explanation=4. No se inventaron políticas de devolución/garantía ni precios/stock. No se indexaron logs, auditorías crudas, código, tests o diffs. El chunking conserva categoría/definición/comparación como unidad; entrada de embedding=title+newline+section+newline+content. Los23 chunks actuales caben sin truncación.

## Métricas congeladas

| Modo | N | Recall@1 | Recall@3 | MRR@3 | nDCG@3 | Cobertura positiva | Abstención negativa |
|---|---:|---:|---:|---:|---:|---:|---:|
| vector |48|0.736111|0.930556|0.909722|0.906348|100%|9/16 (56,25%)|
| hybrid |48|0.736111|0.930556|0.913194|0.906116|100%|9/16 (56,25%)|

Recall es la media de fracción de todos los chunks relevantes recuperados por consulta, no Hit@K. El RAG devuelve máximo 3 chunks y MRR usa sólo esos resultados (MRR@3); el benchmark textual03 usa el ranking completo, por lo que su MRR no es idéntico por definición. Relevancia graduada2^grado−1 para nDCG. Queries sin recuperación permanecen en denominador; aquí todas las positivas recibieron al menos un chunk. Cobertura100% no implica recuperar todos los relevantes.

| Tipo positivo | N | Vector Recall@3 | Híbrido Recall@3 | Vector MRR@3 | Híbrido MRR@3 |
|---|---:|---:|---:|---:|---:|
|category_comparison|4|1.000000|1.000000|1.000000|1.000000|
|color_knowledge|2|1.000000|1.000000|1.000000|1.000000|
|conceptual_role|2|0.333333|0.333333|0.500000|0.500000|
|english_comparison|4|0.750000|0.750000|1.000000|1.000000|
|english_paraphrase|11|0.909091|0.909091|0.742424|0.757576|
|exact_category|11|1.000000|1.000000|1.000000|1.000000|
|semantic_paraphrase|6|1.000000|1.000000|0.916667|0.916667|
|small_typo|4|1.000000|1.000000|1.000000|1.000000|
|technical_explanation|4|1.000000|1.000000|1.000000|1.000000|

Híbrido usa PostgreSQL FTS español y RRF k=60 sólo sobre candidatos permitidos por el piso 0.74 o un nombre/alias completo exacto curado. No rescata documentos por compartir cualquier palabra. En este conjunto no mejora Recall@3 ni resuelve OOD; el pequeño cambio de MRR/nDCG no justifica afirmar superioridad general. No se añadió ANN, HNSW, IVFFlat ni otro vectorDB.

## Caso requerido CAMISA/BLUSA

Query q029: **¿Cuál es la diferencia entre una camisa y una blusa?** En ambos modos el resultado real es:

| Rank | Título | Chunk | Cosine similarity |
|---|---|---|---:|
|1|CAMISA vs BLUSA|`comparison:camisa-blusa#comparison`|0.872309812|
|2|CAMISAS|`category:3#definition`|0.872233942|
|3|BLUSAS|`category:4#definition`|0.870135204|

Los tres relevantes están prioritariamente en top-3; Recall@3=1 para esta consulta. Es una recuperación de contexto, no una respuesta generada ni clasificación visual. Las similitudes no son probabilidades.

## Negativas y abstención

Las 8 consultas dinámicas se interceptan como `insufficient_evidence`,chunks[],tool_required=true,reason`DYNAMIC_FACT_REQUIRES_TOOL`, sin llamar al embedding. No se implementaron Tools de negocio; la señal indica autoridad Laravel/DB futura. Sólo una consulta sin evidencia (capital de Mongolia) se abstuvo por piso. Las otras 7devolvieron contexto irrelevante:

| ID | Consulta sin evidencia | Resultado incorrecto del prototipo |
|---|---|---|
|n002|How do I repair a car engine?|RAG y Tools Laravel (cosine0.75395)|
|n003|¿Cómo se hace pan de masa madre?|Prendas inferiores: FALDAS y PANTALONES (cosine0.77853)|
|n004|Explain orbital mechanics for satellites.|Retrieval local sin LLM (cosine0.77385)|
|n005|¿Cuál es la composición química de este medicamento?|CAMISA vs BLUSA (cosine0.79339)|
|n006|What is the exchange rate today?|RAG y Tools Laravel (cosine0.75975)|
|n007|¿Cuántos días tengo para devolver una compra en Mati?|Familia de color y tono comercial (cosine0.80654)|
|n008|What is Mati's warranty policy for jewelry?|POLOS Y KNITWEAR (cosine0.77999)|

Incluye políticas de Mati que no existen en el corpus, no sólo preguntas fuera de moda. Este fallo invalida promoción. El piso 0.74 preregistrado no separa adecuadamente evidencia pertinente y texto ajeno en E5. No se elevó después del resultado ni se agregó un filtro léxico diseñado con estos errores. Corregir abstención requiere otro diseño/evaluación independiente, preservando este test como resultado fallido conocido; no reutilizarlo como holdout tras tuning.

La ruta pública devuelve `status=insufficient_evidence`,chunks[],reason`RAG_GATE_NOT_APPROVED` antes de DB/embedding cuando no existe aprobación RAG. Los tests muestran que usuario autenticado y parámetros de evaluación web tampoco evaden la guardia. La señal de datos dinámicos permanece tool_required. La fundación implementada es un prototipo verificable, no RAG funcional aprobado para un asistente.

## Procedencia, filtros y PostgreSQL exacto

Todos los chunks devueltos conservan documento, sección,source/source_version/corpus_version/content_hash y rank;100% coinciden con el corpus congelado. Los excerpts no incluyen vectores, SQL generado ni instrucciones ejecutables. `review_status` conserva el estado de propuesta pendiente del dominio. Filtros document_type=taxonomy_category,corpus_version=1.0.0 ysource_version inexistente pasaron; el último abstiene. Statusactive es restricción obligatoria en SQL, no una opción para recuperar versiones inactivas.

Comparación real SQL pgvector vsNumPy cosine sobre vectors384persistidos y48 queries: **48/48 top-3 exactos**, sin necesidad de tolerar empates. Máxima diferencia de similitud2.7264383128944303e−7. El comparador admite1e−5sólo para empates numéricos; ninguna diferencia de rank fue excusada así. El fixture aislado 3D adicional coincide 3/3 con error1.1920928910669204e−8, pero no sustituye esta prueba real del ganador.

## Persistencia y correcciones verificadas

Sólo se aplicó la nueva migración RAG. Estado inicial0 filas; primer índice1modelo/23docs/23 chunks/23vectors; repetición indexed 0 / skipped 23. Los cuatro fingerprints completos fueron idénticos, incluidos timestamps. Tras revisión se repitió y volvió a quedar idéntico.

Pruebas finales aisladas 38 / 150 aserciones en PostgreSQL 18.6/pgvector 0.8.7: idempotencia, versión nueva invalida anteriores, fallos previos no activan parcialmente, CHECKdimension/norm, parameterbinding, conjunto inmutable de documentos por corpus_version y hash. Se exige nueva versión al añadir/quitar/cambiar documentos; doble verificación antes de inferencia y dentro advisory lock. El contrato de cliente también exige data.input_type exacto para impedir confundir prefixes query/document. Ambos fixes surgieron de revisión de fallos reales, sin modificar scoring ni inputs. Contenedor efímero retirado, sin puertos públicos ni volúmenes comerciales.

## Latencias y recursos

| Modo | Embed p50/p95ms | Search p50/p95ms | E2E p50/p95ms |
|---|---|---|---|
|vector|13.569/29.737|5.007/10.130|20.455/39.391|
|hybrid|12.212/14.278|4.778/7.092|19.048/21.100|

Son consultas positivas warm de esta ejecución, secuenciales y sin carga concurrente comercial controlada. El orden fue vector y luego híbrido: la menor latencia híbrida no demuestra ventaja algorítmica. El gate warm 1500 ms se cumple ampliamente. BenchmarktextualCPU puro p95=9.375 ms; agregar HTTP/bootstrap/SQL cambia la distribución.

Primera petición al endpoint principal tras despliegue:wall 18705.269 ms,total respuesta 18818.304 ms,carga modelo 6580.211 ms y verificación de hash 4069.386 ms, incluyendo además spawn/imports/IPC. Los relojes no son directamente sumables: wall del cliente quedó113,034ms por debajo del total del servidor, discrepancia registrada sin atribuir causa ni usarla como gate. Se midió al finalizar un build grande; no atribuir la diferencia al modelo sin aislar IO. El probe CPU controlado separado mostró cold wall 8691.921 ms,carga modelo 2508.546 ms,hash 1213.418 ms,warm 10.902 ms,RSS del hijo 1223.516 MiB (warm 1224.766 MiB); no es medición del padre Uvicorn real. Texto CPU no inicializaCUDA y tieneVRAM 0; visión conservaGPU. Memoria de contenedor/cgroup incluye padre,hijo ycache, no equivale a RSS del modelo. Véase03 yartefacto cpu-isolation-probe.json para unidades exactas.

## HTTP y regresión

128 casos RAG (64×2) más3 filtros:115 llamadas reales a `/api/v1/rag/embed`,todas HTTP 200; las16 dinámicas de los dos modos no llaman al modelo. Indexación inicial 2 llamadas HTTP 200. Probe textual real: auth ausente/incorrecta 401; Unicode/determinismo/doc.truncado/batch concurrente 200; entradas vacías/lista17/8193caracteres/más de 512 tokens 422; body 65537 bytes 413; consulta concurrente 503 busy.14 controles correctos y ningún 500. Perfil ausente 503 se verificó en app real TestClient del probecontrolado; timeout/retención de capacidad en prueba unitaria de regresión.

La misma imagen 13 se analizó por FastAPI puro antes/después:HTTP 200, misma revisiónSigLIP,clasificación exacta y embedding 768 idéntico (delta máxima 0). OCR sigue habilitado y GPU visual sigue cuda;guard de color comercial insufficient_evidence y automatic_assignment=false. Se corrigieron sólo lectores del harness para campos `embedding.values` ycapabilities.colorimetry; no se cambió lógica visual. Ningún análisis se guardó como run/producto.

Guard final:34 fingerprints comerciales/originales 134 / vectores visuales 134 / identidades / conteos AI todosidénticos. AI previo:4 modelos / 147 runs / 11 sugerencias / 2 revisiones / 134 embeddings. classification FULL / embedding FULL / color NONE y AUD-07 mitigado permanecen. Ningún entrenamiento/LLM/feedback humano nuevo.

## Evidencias y estado final

`storage/app/ai-semantic/`:retrieval-results.json,retrieval-summary.json,sql-numpy-inputs.json,retrieval-gate-decision.json,rag-index-idempotence.json,post-review-checks.json,text-runtime.json,visual-before/after.json,catalog-before/final.json,preservation-files.json. `storage/app/ai-rag/`:isolated-persistence-pass3.json ysql-numpy-isolated-agreement-pass3.json. Artefactos privados; SHA de inputs incluido en resultados, sin tokens/vectores en respuestas públicas.

**Resultado: fundación implementada y auditable; embeddings textuales elegibles; RAG prototipo no promovido por fallo de abstención.** El contrato humano está preparado y desconectado; no hay dataset entrenable. No git add, commit ni push.

El perfil evaluado fue SHA4ac11cac22a6eda86ca1cccf57daa086a9425c3acb20fbc3f3d4ec121793be61. El perfil final SHAca82f5b59822da6666eb02fe921c253f3a73b1d0a76f053be6d73f0d095d498b sólo añade el estado del gate fallido y el SHA de su decisión; todas las identidades numéricas y métricas originales permanecen iguales. Runtime final contiene exactamente el perfil final, con retrieval_approved=false; health/embed HTTP200 y regresión imagen13 final idéntica. El contenedor warm completo ocupa aproximadamente 2.366GiB / 7.287GiB según docker stats (muestra, no pico ni RSS del modelo).
