# Investigación de embeddings de texto para Mati

Fecha de investigación: 2026-10-05. Estado: selección previa al benchmark; este documento no contiene resultados de inferencia ni declara un ganador. El espacio de texto es independiente de los embeddings visuales SigLIP de 768 dimensiones.

Se investigaron cuatro modelos y se prerregistraron tres para CPU. E5-small y E5-base permiten comparar dos tamaños de una misma familia; MiniLM multilingüe es un baseline anterior, útil por su coste. Nomic v2 MoE se excluye de esta ejecución porque su ruta oficial necesita código remoto. La selección final depende del corpus Mati congelado, del gate cuantitativo y de medidas locales.

## Identidades y activos fijados

Las revisiones y metadatos se verificaron mediante las fichas oficiales y la API de Hugging Face con información de blobs. La consulta de metadatos no descargó checkpoints. Los hashes siguientes corresponden al blob oficial `model.safetensors`; la preparación volverá a verificar el archivo completo y conservará hashes de configuración, tokenizer y licencias disponibles.

| Modelo | Revisión inmutable | Dimensión | Elementos F32 declarados en Safetensors | Bytes del checkpoint | Licencia declarada de pesos | Decisión previa |
| --- | --- | ---: | ---: | ---: | --- | --- |
| intfloat/multilingual-e5-small | `614241f622f53c4eeff9890bdc4f31cfecc418b3` | 384 | 117653760 | 470641600 | MIT | Benchmark CPU |
| sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2 | `e8f8c211226b894fcb81acc59f3b34ba3efd5f42` | 384 | 117653760 | 470641600 | Apache-2.0 | Benchmark CPU; baseline anterior |
| intfloat/multilingual-e5-base | `d128750597153bb5987e10b1c3493a34e5a4502a` | 768 | 278043648 | 1112201288 | MIT | Benchmark CPU |
| nomic-ai/nomic-embed-text-v2-moe | `1066b6599d099fbb93dfcb64f9c37a7c9e503e85` | 768 | 475292928 | 1901187232 | Apache-2.0 | Excluido de la ejecución estricta sin código remoto |

Los elementos F32 no equivalen necesariamente al número de parámetros entrenables: los metadatos incluyen además buffers I64, 512 elementos en E5-small/MiniLM y 514 en E5-base. El tamaño del archivo es evidencia de almacenamiento, **no RAM medida**; el benchmark medirá RSS incluyendo carga e inferencia. No se ha inferido viabilidad a partir de la RTX 5060: esta fase usa CPU y debe consumir cero VRAM.

| Modelo | SHA256 oficial del checkpoint |
| --- | --- |
| E5-small | `1a55775f53449dac10a2bcbc312469fac40b96d53198c407081a831f81c98477` |
| MiniLM multilingüe | `eaa086f0ffee582aeb45b36e34cdd1fe2d6de2bef61f8a559a1bbc9bd955917b` |
| E5-base | `a18a44fad1d0b46ded15928144138cff1135d5cc8233bdd90be5f18822de09a7` |
| Nomic v2 MoE, sólo investigación | `097012b27af76d80af74fed4bc2ccc9091245286f776adf03ad1758a24ade9a0` |

Las fichas de [E5-small](https://huggingface.co/intfloat/multilingual-e5-small), [E5-base](https://huggingface.co/intfloat/multilingual-e5-base), [MiniLM multilingüe](https://huggingface.co/sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2) y [Nomic v2 MoE](https://huggingface.co/nomic-ai/nomic-embed-text-v2-moe) son las fuentes primarias de identidad, dimensiones y licencia. La reproducción cargará estas revisiones, no `main`.

## Ruta de inferencia y perfil previo

| Modelo | Soporte lingüístico declarado | Prefijo consulta / documento | Límite fijado | Pooling y normalización | Ruta local |
| --- | --- | --- | ---: | --- | --- |
| E5-small | Multilingüe, incluye español e inglés | `query: ` / `passage: ` | 512 tokens, incluyendo tokens especiales y prefijo | Media con attention mask, L2, FP32 | AutoTokenizer + AutoModel, Bert nativo |
| MiniLM multilingüe | 50 idiomas, español e inglés | Vacío / vacío | 128 tokens, perfil SentenceTransformer oficial | Media con attention mask, L2, FP32 | AutoTokenizer + AutoModel, Bert nativo |
| E5-base | Multilingüe, incluye español e inglés | `query: ` / `passage: ` | 512 tokens, incluyendo tokens especiales y prefijo | Media con attention mask, L2, FP32 | AutoTokenizer + AutoModel, XLM-Roberta nativo |
| Nomic v2 MoE | 101 idiomas declarados; ejemplo en español | `search_query: ` / `search_document: ` | 512 en su perfil de embeddings | Media con attention mask, L2 | La ficha exige `trust_remote_code=True`; no se ejecuta |

E5 necesita los prefijos ingleses aun cuando el texto esté en español. Sus cosenos tienen una distribución propia del entrenamiento contrastivo; los scores no son probabilidades. El [informe técnico de E5](https://arxiv.org/abs/2402.05672) y la [ruta de referencia E5](https://github.com/microsoft/unilm/tree/master/e5) fundamentan esa familia; sus métricas públicas no sustituyen la evaluación Mati.

El perfil MiniLM oficial limita las secuencias a 128, aunque el tokenizer declara 512. Se respetan 128 para no atribuir a la ficha un comportamiento diferente. Su tokenizer fijado declara `do_lower_case=true` y el perfil SentenceTransformer `do_lower_case=false`; se conserva el comportamiento del tokenizer nativo, sin conversión manual de mayúsculas. La ficha oficial muestra media con máscara y dimensión 384. Es un baseline anterior, no una novedad de 2025.

El [trabajo Nomic v2 MoE de 2025](https://arxiv.org/abs/2502.07972) presenta una alternativa moderna. Sin embargo, su `config.json` incluye `auto_map` hacia código Nomic y la receta oficial pide código remoto. No se considera un problema demostrado de licencia; se excluye por el requisito operativo `trust_remote_code=False` y por no disponer de RAM/latencia locales medidas.

BAAI/BGE-M3 se examinó como alternativa adicional: su [ficha oficial](https://huggingface.co/BAAI/bge-m3) declara vectores densos de 1024 dimensiones, contexto de hasta 8192 tokens y licencia MIT. Se dejó fuera del conjunto acotado porque introduce otro perfil más grande y el benchmark autorizado prioriza 384/768 en CPU. No se afirma que sea incompatible comercialmente o imposible de ejecutar en esta máquina.

## Código, pesos y datos de entrenamiento

La licencia declarada de cada checkpoint permite considerar su uso comercial dentro de sus condiciones; conservar atribuciones y avisos sigue siendo necesario. No se interpreta como una autorización universal sobre todos los textos usados para entrenarlo, ni como asesoramiento legal.

El código de inferencia existente usa [Transformers, Apache-2.0](https://github.com/huggingface/transformers/blob/main/LICENSE). La implementación de referencia [E5 en UNILM](https://github.com/microsoft/unilm/blob/master/LICENSE) publica MIT. [Sentence Transformers](https://github.com/huggingface/sentence-transformers/blob/main/LICENSE) y [Contrastors/Nomic](https://github.com/nomic-ai/contrastors/blob/main/LICENSE) publican Apache-2.0; Mati no necesita instalar Sentence Transformers ni Contrastors para los tres candidatos nativos. La preparación archivará el README y cualquier LICENSE/NOTICE del snapshot, además de la etiqueta de licencia de la API fijada a la revisión.

Los datos de entrenamiento tienen condiciones independientes de la licencia del código y de los pesos. El informe E5 describe mezcla multilingüe y ajuste supervisado; no se descargará ni redistribuirá esa mezcla. Las fichas MiniLM/Nomic describen entrenamiento externo y tampoco constituyen una licencia uniforme de todos sus datasets. El benchmark utiliza exclusivamente el corpus Mati versionado y sus consultas de evaluación, con procedencia explícita. No necesita datos externos para entrenar ni afinar modelos.

## Protocolo fijado antes de resultados

El corpus y la evaluación se congelarán con SHA256 antes de cualquier forward. La entrada de cada chunk es exactamente `title + "\n" + section + "\n" + content`; `content_hash` corresponde únicamente al contenido UTF8. Para codificar se aplica NFC y strip, después el prefijo del perfil; `text_sha256` siempre conserva el texto original recibido. No se modifica el corpus para obtener mejores métricas después del benchmark.

Los tres perfiles fijan media de los estados de tokens válidos según `attention_mask` y normalización L2 en FP32. La truncación utiliza el límite nativo prerregistrado y se contabiliza por documento/consulta con longitud anterior al truncado. Una consulta API que exceda el límite del perfil se rechazará; los documentos deben exponer `truncated` y la ingestión debe registrar su efecto. Nunca se presenta truncación silenciosa como preservación completa del texto.

La máquina WSL observada tiene aproximadamente 7.46 GiB de RAM total y 5.87 GiB disponible al preflight. El runner ejecutará cada candidato en un proceso fresco, CPU, cuatro hilos Torch, sin GPU. Carga fría y RSS se miden por candidato; p50/p95 se calculan sobre consultas positivas unitarias después de codificar el corpus. Se publican N y latencias individuales, errores y truncaciones.

Los rankings de evaluación se calculan por producto escalar de vectores L2, con empate determinista por chunk_id. Recall@1/@3 promedia la fracción de chunks relevantes recuperados por consulta; MRR usa la posición del primer relevante en el ranking completo; nDCG@3 usa grados congelados y ganancia `2^grade-1`. Las consultas negativas sin relevantes se analizan aparte y no entran como ceros en esos promedios. Las métricas español/inglés tienen denominadores propios. El orden y la relevancia del dataset se congelan antes del resultado.

| Condición del gate de embeddings puros | Umbral previo |
| --- | ---: |
| Recall@3 total | ≥ 0.75 |
| MRR total | ≥ 0.60 |
| nDCG@3 total | ≥ 0.65 |
| Recall@3 español | ≥ 0.75 |
| Recall@3 inglés | ≥ 0.60 |
| p95 consulta unitaria CPU, caliente | ≤ 500 ms |
| Pico RSS incremental sobre RSS previo a carga | ≤ 2048 MiB |
| Carga fría tokenizer + modelo CPU | ≤ 20 s |
| Errores / uso VRAM | 0 / 0 |
| Revisión/pesos/licencia/configuración | Fijados; nativos; carga offline; sin código remoto |

Entre los candidatos que cumplen todas las condiciones se prioriza menor p95; si la diferencia frente al mínimo es ≤10%, se prefiere mayor nDCG@3. Si ninguno pasa, la decisión es NONE y no se activa un sustituto sin evaluación.

El filtro posterior de RAG se prerregistró con coseno mínimo 0.74 para E5 y 0.35 para MiniLM. Es un filtro de abstención exploratorio que debe evaluarse con negativos/OOD, no una probabilidad calibrada ni una corrección de la relevancia positiva. El ranking híbrido y la exactitud PostgreSQL/pgvector se evaluarán aparte; no cambian el gate de embeddings puros.

## Preparación y servicio sin efectos sobre visión

La preparación requiere autorización explícita y descarga una sola vez las tres revisiones a `/cache/text-embeddings` en el volumen persistente. Sólo ese nuevo subdirectorio puede quedar legible por UID10001; no se cambian permisos de SigLIP, EasyOCR o segmentación. Se prefieren Safetensors y activos del tokenizer; se excluyen pesos `.bin` duplicados y código remoto. El lock conserva SHA256 del protocolo, revisiones, archivos y perfil.

La carga de benchmark y peticiones usa `local_files_only=True`, `trust_remote_code=False`, CPU explícita y `use_safetensors=True`. Faltan activos: se informa indisponibilidad. No hay descargas al atender una petición ni fallback hacia un modelo no evaluado.

El backend propuesto usa un único proceso CPU separado, creado de forma lazy con spawn, para no cambiar los hilos globales del proceso FastAPI que mantiene visión/OCR. El padre conserva un worker GPU; el hijo usa cuatro hilos CPU y ninguna GPU. Sólo una tarea puede estar pendiente: el lock permanece hasta que termina realmente, incluso si la petición vence por timeout. El endpoint interno mantiene la autenticación existente y responde 503 controlado si el modelo está ausente, ocupado o falla.

`POST /api/v1/rag/embed` aceptará esquema explícito, UUID, tipo query/document y 1–16 textos, con longitud y bytes acotados. Devuelve identidad textual, dimensión, normalización, pipeline/preprocesamiento, índice, norm, SHA del texto original y truncación. Los vectores no se exponen al frontend. El perfil ganador será un recurso versionado; no depende de parámetros de modelo suministrados por el cliente. No se llama a un LLM ni se altera el espacio visual.

## Congelación de entradas verificada

El protocolo se congeló antes de la inferencia en resources/rag/benchmark-protocol-v1.json: SHA256 f60608c413e19989cbdee9cb3391a1ad724b5ab9fd870c1c21e55f3f61068836. Fija tres candidatos, 23 documentos/chunks, 48 consultas positivas y 16 negativas separadas. Corpus SHA256 d1fd24f3a18e9c169c6197e6bf12da8a663359010ffc271c33918eb8893a3fe2; evaluación SHA256 c09509b42e33622e9613ba2f64207947d78b62260d355f63da3c951304b6bdb4.

La relevancia es una propuesta curada por Codex pendiente de revisión del dominio Mati, no ground truth humano. La consulta q029 requiere tres chunks distintos de camisa, blusa y comparación, con grados 2/2/3. Recall conserva el denominador de relevantes por consulta; recuperar uno no equivale a recuperar todos. Un error de consulta se cuenta como cero y conserva el denominador de 48; no se oculta eliminándolo del promedio.

El protocolo también congela un gate posterior RAG: Recall@3≥0.75, MRR≥0.60, nDCG@3≥0.65, cobertura positiva≥0.80, abstención negativa≥0.90, p95 E2E caliente≤1500ms y procedencia completa. Las consultas dinámicas de stock/precio requieren una herramienta y no reciben chunks como respuesta autoritativa. Estos requisitos no alteran el gate de embeddings puros ni demuestran que el modelo ya los cumpla.

La preparación inicial detectó un KeyError al convertir ModelCardData con dict() y se detuvo antes de descargar pesos. Se corrigió mediante su interfaz to_dict() y se reprodujo el fallo anterior para verificar el diagnóstico. Las pruebas sin inferencia del runner comprueban relevancia graduada, denominadores, errores, percentiles, NFC, perfiles inmutables y rechazo de corpus alterado: 12 pruebas pasaron. Los resultados reales y cualquier incidencia posterior pertenecen al informe de benchmark.
