# Benchmark de embeddings de texto

El cierre mantiene **E5-small seleccionado para embeddings de texto de 384 dimensiones** y el endpoint interno funciona en el runtime real. **La recuperación RAG no se aprueba: vector e híbrido abstienen sólo 9/16 negativos (56.25%), por debajo del gate congelado de 90%.** La promoción queda bloqueada; no se cambian pisos, modelos, relevancia o ranking para ocultar ese resultado.

La decisión congelada seleccionó **E5-small**, dimensión 384, exclusivamente para el espacio de texto. Los tres candidatos pasan el gate de embeddings positivos y recursos; E5-small tiene el menor p95 y MiniLM queda fuera del margen de empate del10%. Esta decisión no demuestra que el RAG completo pase sus gates de negativos, procedencia, SQL o herramientas.

Evaluación real local de modelos, sin mocks: tres procesos frescos secuenciales, CPU, cuatro hilos Torch, interop1, FP32. No se utilizó la GPU. Corpus23chunks; consultas positivas48, español31 e inglés17; negativos16 separados. La relevancia es una propuesta de Codex pendiente de revisión Mati, no ground truth humano independiente. No hubo entrenamiento, ajuste de pesos, cambio de consultas, prefijos o umbrales después de ver resultados.

## Protocolo y evidencia

El [protocolo congelado](/home/soto/Code/more/services/mati-ai/resources/rag/benchmark-protocol-v1.json) se selló antes de la inferencia: SHA256 `f60608c413e19989cbdee9cb3391a1ad724b5ab9fd870c1c21e55f3f61068836`. [Corpus](/home/soto/Code/more/services/mati-ai/resources/rag/corpus-v1.json) SHA `d1fd24f3a18e9c169c6197e6bf12da8a663359010ffc271c33918eb8893a3fe2`; [evaluación](/home/soto/Code/more/services/mati-ai/resources/rag/evaluation-v1.json) SHA `c09509b42e33622e9613ba2f64207947d78b62260d355f63da3c951304b6bdb4`.

[Lock privado de activos](/home/soto/Code/more/storage/app/ai-semantic/text-model-lock.json) SHA `e779f2cea4aaebfdbab94997c8311778d039e3c2e3a051f2ed9642e5e741b29b`. La preparación fue independiente del corpus, por lo que el lock tiene protocol_sha256=null; el benchmark verificó posteriormente perfiles y activos contra el protocolo congelado. No se usó un protocolo de datos ficticio. [Selección](/home/soto/Code/more/storage/app/ai-semantic/text-selection.json) y [log de ejecución](/home/soto/Code/more/storage/app/ai-semantic/text-benchmark.log) conservan decisión y ejecución. Los rankings, tiempos unitarios y vectores están en los artefactos privados de cada candidato; los vectores no se devuelven a React.

| Candidato | Modelo | Revisión | Dimensión | SHA256 results.json |
| --- | --- | --- | ---: | --- |
| e5-small | intfloat/multilingual-e5-small | `614241f622f53c4eeff9890bdc4f31cfecc418b3` | 384 | `84c00f8073884a110eae9577b9918c242f2914a4c7a6acab2cfe2cc1ad147bfc` |
| minilm | sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2 | `e8f8c211226b894fcb81acc59f3b34ba3efd5f42` | 384 | `5fe1ee81c2c5d124d001e02d7893c35fd50375345f232bfc44426068e2dbe343` |
| e5-base | intfloat/multilingual-e5-base | `d128750597153bb5987e10b1c3493a34e5a4502a` | 768 | `01b3457d4fe04a0f8f6c75ea496870e848d476c891ed70b22dbd8d0a0b380ca3` |

Fuente ejecutada `benchmark_embeddings.py` SHA `30e2a78a0c83c170541f0ece950f3e7a9feb656a32f23896e4ed3d622cb89f82`. Runtime: Python3.11.17; Torch2.13.0+cu130, Transformers5.14.1 y NumPy2.4.6. El sufijo CUDA de Torch identifica el paquete instalado; no implica que estas inferencias usaran CUDA. CUDA_VISIBLE_DEVICES fue vacío, los tensores se verificaron CPU y no se inicializó contexto CUDA.

## Definiciones y denominadores

La entrada exacta de cada chunk fue `title + "\n" + section + "\n" + content`. Se aplicó NFC y strip, luego prefijos congelados: query:/passage: en E5 y ninguno en MiniLM. Pooling: media de estados de tokens válidos según attention_mask, seguida de L2 FP32. El SHA de texto guarda UTF8 original; content_hash corresponde sólo al contenido, no a todo el texto de embedding.

Recall@k es la fracción de chunks relevantes recuperados dentro de los primerosk por consulta, promediada entre48 consultas. Por eso una consulta con tres relevantes puede tener Recall@1=1/3 aunque su primer resultado sea correcto. MRR usa el primer relevante en el ranking completo. nDCG@3 usa ganancias2^grade−1 y denominador ideal de los grados congelados. Rankings por producto escalar de vectores L2 y empates por chunk_id. No se aplica un piso de coseno al ranking positivo de este benchmark puro.

Un error positivo conservaría el denominador48 y aportaría cero; hubo0errores. Los negativos tienen relevancia vacía y no se incluyeron como ceros en las métricas positivas. Los valores de las tablas se redondean a9decimales; JSON conserva precisión completa y resultados por consulta.

| Candidato | N positivas | Recall@1 | Recall@3 | MRR | nDCG@3 | Gate embeddings |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| e5-small | 48 | 0.736111111 | 0.930555556 | 0.918055556 | 0.906347774 | PASS |
| minilm | 48 | 0.569444444 | 0.770833333 | 0.739725379 | 0.720112533 | PASS |
| e5-base | 48 | 0.736111111 | 0.965277778 | 0.928819444 | 0.925011826 | PASS |

| Candidato | Idioma | N | Recall@1 | Recall@3 | MRR | nDCG@3 |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| e5-small | es | 31 | 0.806451613 | 0.956989247 | 0.958064516 | 0.951180610 |
| e5-small | en | 17 | 0.607843137 | 0.882352941 | 0.845098039 | 0.824593779 |
| minilm | es | 31 | 0.655913978 | 0.817204301 | 0.816104594 | 0.785640845 |
| minilm | en | 17 | 0.411764706 | 0.686274510 | 0.600445633 | 0.600619728 |
| e5-base | es | 31 | 0.806451613 | 0.967741935 | 0.959677419 | 0.951254231 |
| e5-base | en | 17 | 0.607843137 | 0.960784314 | 0.872549020 | 0.877158027 |

El gate fijó Recall@3≥0.75, MRR≥0.60, nDCG@3≥0.65, Recall@3es≥0.75 y en≥0.60; todos pasan. E5-base obtiene mayor Recall@3/nDCG positivos; la regla de selección prioriza latencia entre quienes pasan. No se cambió la regla para favorecer esa mayor calidad.

## Latencia y memoria reales

Carga fría incluye tokenizer y AutoModel aCPU/eval; excluye descarga previa, importación inicial de librerías y verificación previa del checkpoint. Primer forward del corpus se informa aparte. p50/p95 se calculan con interpolación lineal sobre48 consultas positivas unitarias, una medición por consulta, después de codificar los23chunks. Incluyen normalización/tokenización/forward/pooling/L2, y excluyen ranking NumPy, red, SQL e híbrido. Son latencias de embedding, no p95 del RAG HTTP.

RSS basal se tomó tras importar ML y antes de cargar el modelo. Pico es el máximo de muestras RSS cada10ms y ru_maxrss del proceso. RSS incremental=pico−basal; incluye carga, corpus, consultas negativas y fixtures largos posteriores. Cada modelo se ejecutó en un proceso fresco para evitar contaminación de allocators. El tamaño de archivo de pesos no se presenta como RAM medida.

| Candidato | Cold load ms | Primer forward ms | p50 caliente ms | p95 caliente ms | RSS basal MiB | RSS pico MiB | RSS incremental MiB | VRAM MiB |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| e5-small | 2591.538950001 | 88.421778000 | 7.436918500 | 9.375380200 | 739.421875000 | 1259.046875000 | 519.625000000 | 0 |
| minilm | 2142.316856000 | 46.070094000 | 7.551897501 | 10.454659350 | 742.917968750 | 1507.410156250 | 764.492187500 | 0 |
| e5-base | 2156.183531999 | 75.688904000 | 22.557404501 | 29.447894850 | 742.843750000 | 1516.976562500 | 774.132812500 | 0 |

Gate recursos: cold≤20000ms, p95≤500ms, RSS incremental≤2048MiB, errores0 y VRAM0; todos pasan. VRAM0 se fundamenta en ejecución de tensoresCPU y ausencia de contexto CUDA en estos procesos; la memoria del servicio visual principal es una observación separada. La WSL tiene aproximadamente7.46GiB de RAM total; el coste del servicio HTTP añade proceso padre/hijo, imports, hashes y serialización que se medirán aparte.

Mínimo p95=9.375380199708161ms. Margen de10%=10.312918219678977ms. MiniLM p95=10.454659350307338ms supera ese margen, así que E5-small gana sin desempate por nDCG. E5-base p95=29.447894850272856ms también queda fuera. La decisión y checks detallados constan en text-selection.json.

## Truncación y comportamiento verificados

Los tres candidatos completaron23/23documentos,48/48positivas y16/16negativas con0errores y0truncaciones de corpus o consultas positivas. E5small/base fijan512tokens; MiniLM128, según su perfil SentenceTransformer, aunque su tokenizer declare512. El máximo observado en documentos E5-small fue99tokens incluyendo prefijo y especiales.

Determinismo: repetir la primera consulta tres veces produjo diferencia absoluta máxima0.0 en los tres candidatos, tolerancia1e−6. NFC/NFD de la fixture con acentos produjo diferencia0.0. Dos entradas vacías/whitespace fueron rechazadas. La fixture larga generó4004tokens antes de truncar para E5 y4002para MiniLM: documento truncado explícitamente y consulta rechazada. Esas fixtures no entran en las48métricas ni48latencias.

| Candidato | Coseno fixture original vs MAYÚSCULAS | Igual a tolerancia1e−6 | Conversión manual lower-case |
| --- | ---: | --- | --- |
| E5-small | 0.9470379993654713 | No | No |
| MiniLM | 0.29685387635079363 | No | No |
| E5-base | 0.9355558800433774 | No | No |

El comportamiento nativo distingue mayúsculas en esta fixture, especialmente MiniLM. La etiqueta do_lower_case del archivo de tokenizer no demuestra invariancia de sus vectores: se informa la observación real. NFC conserva acentos; no se prometen invariancias lingüísticas no medidas.

## Caso esencial camisa frente a blusa

q029 tiene tres relevantes: comparison:camisa-blusa#comparison grado3, category:3#definition y category:4#definition grado2. Son comparación, CAMISAS y BLUSAS; no una categoría comercial inventada ni una decisión de reclasificar un producto.

| Candidato | Top1 | Top2 | Top3 | Recall@1 | Recall@3 | MRR | nDCG@3 |
| --- | --- | --- | --- | ---: | ---: | ---: | ---: |
| E5-small | comparison:camisa-blusa | category:3 | category:4 | 1/3 | 1 | 1 | 1 |
| MiniLM | comparison:polera-knitwear | comparison:lower-garments | comparison:camisa-blusa | 0 | 1/3 | 1/3 | 0.33677195911498925 |
| E5-base | comparison:camisa-blusa | category:3 | category:4 | 1/3 | 1 | 1 | 1 |

E5-small no resuelve todas las consultas: q018 y q047 tienen Recall@3=0 y MRR0.2, con el relevante en rango5. Las métricas agregadas no se interpretan como garantía individual o certeza comercial.

## Negativos: límite del piso de coseno

Se aplicó únicamente el piso congelado al primer score de cada uno de los16negativos, sin cambiarlo tras ver los resultados. **Ningún candidato alcanza90% de abstención con el piso por sí solo.** Este resultado no se puede ocultar bajo el PASS de embeddings positivos. Las reglas de consulta dinámica y recuperación híbrida del protocolo requieren su evaluación RAG real separada; en este corte previo aún no se afirmaba un PASS end-to-end RAG; el cierre posterior confirma el FAIL de abstención.

| Candidato | Piso fijo | Abstiene por score | Tasa abstención N16 | Recuperaría contenido | Comparación con gate RAG |
| --- | ---: | ---: | ---: | ---: | --- |
| e5-small | 0.74 | 1/16 | 6.2500% | 15/16 | FAIL frente a90% |
| minilm | 0.35 | 14/16 | 87.5000% | 2/16 | FAIL frente a90% |
| e5-base | 0.74 | 4/16 | 25.0000% | 12/16 | FAIL frente a90% |

E5-small devolvería contenido en15/16negativos sólo por coseno, aunque muchos cosenos sean altos. Un score alto no es probabilidad de pertenecer al dominio ni autorización para responder stock/precio. MiniLM14/16=87.5% también queda bajo90%; no se sustituye al ganador a posteriori. El benchmark conservó los16rankings y scores por candidato para auditar la abstención y la política posterior.

## Activos, seguridad y pruebas

Los checkpoints Safetensors coinciden con los SHA oficiales de la investigación y con el lock; también se guardaron hashes de config/tokenizer/pooling/README. Las tres revisiones declaranMIT/MIT/Apache2 según modelo. En los tres snapshots preparados no aparece un archivo LICENSE/NOTICE separado: la evidencia fijada de licencia de pesos es la declaración de la ficha/model card con SHA, junto a fuentes primarias del código. No se afirma haber descargado una licencia inexistente, ni que los datasets de entrenamiento compartan esa licencia.

La primera preparación falló al convertir ModelCardData con dict(), antes de descargar pesos. Se reprodujo KeyError0 y se corrigió a to_dict(); la segunda preparación completó los hashes. No se ocultó el fallo ni se ejecutó inferencia con activos incompletos. Los modelos se descargaron explícitamente a /cache/text-embeddings; sólo ese cache nuevo se hizo legible al UID10001. No se modificaron permisos de SigLIP/EasyOCR/segmentación.

El runner lee offline las tres revisiones con trust_remote_code=False/use_safetensors=True/local_files_only=True. Los resultados privados y NPZ se crean con directorios700/archivos600; los pesos públicos del nuevo cache tienen dirs755/files644. El protocolo, corpus y evaluación se validan por SHA antes de forward. No hay llamadas a API externa durante inferencia, SQL/API de productos ni escrituras comerciales.

Pruebas sin modelo:12tests del benchmark verificaron recall multirelevante, nDCGgraduado, negativos fuera del promedio, MRRrango4, errores como0, interpolaciónp95, NFC, prefijos/revisiones fijos y rechazo de datos alterados. Con endpoint y compatibilidad de rutas existentes:57tests pasaron, una advertencia de deprecación Starlette/httpx; RuffF/E9 pasó en los archivos nuevos/tocados. Son pruebas de contrato y lógica con mocks donde corresponde, separadas de las inferencias reales anteriores. No convierten los P2 preexistentes de la suite general en PASS.

## Diseño de despliegue: corte anterior a la validación real

El perfil aprobado es E5-small, revisión614241f622f53c4eeff9890bdc4f31cfecc418b3,384dimensiones,L2,text-embedding-1.0.0 y preprocesamiento text-nfc-strip-meanpool-l2-e5querypassage512-1. Su aprobación corresponde al gate de embeddings. En este corte rag_gate_state era pending_actual_retrieval_evaluation; el cierre posterior de este informe registra el FAIL de recuperación.

POST /api/v1/rag/embed mantiene la autenticación interna, UUID y schema_version explícitos,1–16textos y8192codepoints por texto, con64KiB máximos de solicitud antes del parser. Devuelve identidad completa e idéntica en model/data.model, índices, vectores internos, norm, SHAUTF8 original y truncación. Query con más de512tokens reales se rechaza422; documentos exponen la truncación y warning. Modelo ausente, ocupado o timeout produce503seguro, nunca una descarga al atender una petición.

El backend crea lazy un solo hijoCPU con spawn, cuatro hilosTorch e interop1; mantiene el proceso visual y sus hilos. El semáforo permite una tarea pendiente y se libera cuando el Future termina, incluso si la petición ya devolviótimeout. Los archivos se verifican porSHA antes de cargar y no se acepta un modelo/prefix/path enviados por cliente. El Dockerfile sólo añade COPY resources para transportar el perfil/corpus versionados. En este corte aún faltaba la evidencia de despliegue. Las secciones posteriores aportan el probe aislado y el endpoint real; el p95 del benchmark no sustituye esos costes.

Este resultado no modifica prompts/pesos/embeddings visuales SigLIP, OCR, colorimetría ni el guard comercial. No entrena el futuro sistema de selección ni integra segmentación semántica.


## Integración real CPU en contenedor aislado

[Probe privado](/home/soto/Code/more/storage/app/ai-semantic/cpu-isolation-probe.json), SHA256 `d1d040ae6c075f9d4f6adbab819c3152ec8e3b4470b821693a8a119c00b3fa8a`, ejecutó el código actual montado de sólo lectura en la imagen previa `4cb86e63d5752679f4d901554e4fd63f663f743f21ffc76061f571d4f08c229f`, como UID10001, con caché de sólo lectura, red none y sin dispositivos GPU. Dos llamadas de fixtures al TextEmbeddingService cargaron el modelo real E5-small aprobado. El artefacto conserva metadatos, normas y recursos; excluye textos, vectores y credenciales. Fuente del probe SHA `a093f7d43a802d4e1d6de574056669d25f3d524e407832d275ab22df689ec58b`; perfil usado SHA `4ac11cac22a6eda86ca1cccf57daa086a9425c3acb20fbc3f3d4ec121793be61`.

El padre de esta prueba fijó explícitamente Torch8/interop16 como baseline controlado y conservó ambos valores después. **No es una lectura del padre Uvicorn principal.** El hijo reportó CPU4/interop1, ausencia de contexto CUDA, dimensión384 y normas1.0000000460704719/0.9999999633026502. La segunda llamada reutilizó el mismo hijo y modelo sin recarga. Los ocho checks del artefacto pasaron.

| Medida real del probe | Valor |
| --- | ---: |
| Arranque lazy completo de primera llamada | 8691.92110900076 ms |
| Carga tokenizer/model dentro del hijo | 2508.546381999622 ms |
| Verificación de integridad dentro del hijo | 1213.4180860011838 ms |
| Worker total primera llamada | 6210.96685700104 ms |
| Segunda llamada completa, caliente | 10.901835999902687 ms |
| RSS hijo antes de carga | 745.3125 MiB |
| RSS/pico hijo primera llamada | 1223.515625 MiB |
| RSS/pico hijo segunda llamada | 1224.765625 MiB |
| Memoria cgroup antes, padre controlado | 588427264 bytes |
| Memoria cgroup después, padre+hijo | 2128732160 bytes |

El cold lazy incluye costes que el cold del benchmark excluye: spawn, imports, lectura/validación del perfil, hashes y comunicación con el hijo. Su RSS y memoria de cgroup tampoco equivalen al RSS incremental del benchmark. N=2 fixtures es una prueba de integración, no una nueva muestra para p50/p95 ni una repetición de las48 consultas congeladas. La ejecución coincidió con preparación del build Docker, por lo que no se usa para sustituir el benchmark prerregistrado.

Un TestClient sobre la aplicación actual, con un token de fixture local y un perfil ausente, devolvió503/TEXT_MODEL_UNAVAILABLE, sin crear executor ni cargar modelo visual. Esa comprobación usa middleware y autenticación de la aplicación; no es una captura HTTP del contenedor principal. Este probe aislado no certifica el endpoint principal ni el RAG; su evidencia posterior se presenta en el cierre siguiente.

Después de completar el probe con exit0, otra invocación WSL para leer la evidencia falló con Wsl/Service/0x8007274c. Se recuperó la lectura por UNC y se preservó el JSON original; fue un fallo de transporte posterior, no una inferencia fallida ni una repetición del modelo.


## Cierre del endpoint principal: integración real completada

[Artefacto runtime](/home/soto/Code/more/storage/app/ai-semantic/text-runtime.json), SHA256 `0e57698a2e7989ae1c14a4a7d8d3535297a52c87af8d902f6ba4745f7b05aa8e`, registra el endpoint real `/api/v1/rag/embed` tras el despliegue, 14/14 checks PASS y ningún 500. La identidad coincide con E5-small fijado, espacio text, 384 dimensiones, L2 y pipeline/preprocesamiento aprobados. Se verificaron Unicode/NFC, normas unitarias y determinismo en respuestas reales; el artefacto no expone vectores ni credenciales.

| Caso observado en HTTP real | Status |
| --- | ---: |
| Token ausente / incorrecto | 401 / 401 |
| Unicode válido / repetición determinista | 200 / 200 |
| Texto vacío / batch vacío | 422 / 422 |
| 17 textos / 8193 caracteres / 1000 tokens | 422 / 422 / 422 |
| Documento largo: truncación explícita | 200 |
| Body superior al límite | 413 |
| Batch concurrente / consulta competidora | 200 / 503 |

La consulta competidora devolvió503 controlado por capacidad ocupada; no500 ni una cola ilimitada. Los 422 separan entradas inválidas del estado de indisponibilidad; el documento largo devuelve200 con truncación declarada, no conservación completa del texto. Las 401 demuestran que la autenticación sigue vigente.

| Primera petición real al endpoint desplegado | ms |
| --- | ---: |
| Wall externo observado | 18705.26909828186 |
| Total declarado en respuesta | 18818.303580001157 |
| Worker total | 14669.923946999916 |
| Carga tokenizer/model CPU | 6580.210821000946 |
| Verificación de archivos | 4069.386441999086 |

El wall externo es 113.034481719ms menor que el total interno declarado: se conservan ambas lecturas y su discordancia, sin restarlas para inferir tiempos de spawn ni tratarlas como un único reloj. El arranque real, hashes, IPC e imports no equivalen al cold de 2591.5389500005404 ms del benchmark. No se recalculó el gate de candidatos ni se afinó el timeout a partir de esta petición.

## Indexación e idempotencia reales

[Idempotencia](/home/soto/Code/more/storage/app/ai-semantic/rag-index-idempotence.json), SHA256 `fb58045fe7db8c65f45545d45ec77f19e8fe6043a771e4b4ab3d476c65e4206f`: primera ejecución indexó 23 chunks; la repetición indexó 0 y omitió 23, con counts_identical=true y rows_identical=true. Se observaron dos llamadas HTTP 200 de embeddings. El corpus conserva 23 documentos / 23 chunks y una identidad de modelo textual. No hubo mutación comercial en esta evidencia. Esas filas pertenecen al espacio de texto; no sustituyen embeddings visuales canónicos.

## RAG real: calidad positiva suficiente y abstención insuficiente

[Resumen RAG](/home/soto/Code/more/storage/app/ai-semantic/retrieval-summary.json), SHA256 `13623606be926df9d9a3b1f8c25e88c5a6113a4325dca05997734dfe5c89e054`; [casos](/home/soto/Code/more/storage/app/ai-semantic/retrieval-results.json), SHA256 `176ea67c72e0ed0d125996e506c9d9ae8a3a433b9773704927a67b3bc2592b7f`. Se ejecutaron 128 casos: 48 positivos + 16 negativos en cada uno de los modos vector e híbrido. No son 128 HTTP 200: la evidencia observa 115 HTTP 200 de embeddings,112 llamadas de esos casos y3 probes de filtros. Las 8 consultas dinámicas por modo se resolvieron con requires_tool sin requerir embedding, por lo que no añaden esas 16 llamadas HTTP.

| Modo | N positivos | Recall@1 | Recall@3 | MRR@3 | nDCG@3 | Cobertura positiva | Abstención negativa N16 | Gate RAG |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| vector | 48 | 0.736111111 | 0.930555556 | 0.909722222 | 0.906347774 | 48/48 (100%) | 9/16 (56.25%) | FAIL |
| hybrid | 48 | 0.736111111 | 0.930555556 | 0.913194444 | 0.906116132 | 48/48 (100%) | 9/16 (56.25%) | FAIL |

MRR aquí usa el ranking devuelto de hasta 3 resultados: un relevante fuera de Top3 aporta 0. El MRR del benchmark de embeddings usaba ranking completo, por lo que 0.9180555555555556 y los valores RAG anteriores no son métricas idénticas ni una regresión de pesos. Recall conserva el mismo denominador multirrelevante y nDCG mantiene grados congelados.

| Modo | p50 embed ms | p95 embed ms | p50 búsqueda ms | p95 búsqueda ms | p50 E2E ms | p95 E2E ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| vector | 13.568639755 | 29.736864567 | 5.006909370 | 10.130321980 | 20.454645157 | 39.391052723 |
| hybrid | 12.211561203 | 14.277851582 | 4.778385162 | 7.091832161 | 19.047975540 | 21.100091934 |

Estas latencias se registran en la evaluación de recuperación y no reemplazan las 48 latencias unitarias del benchmark. El gate de latencia E2E ≤ 1500 ms pasa, al igual que relevancia positiva, cobertura, filtros y procedencia. El híbrido obtiene p95 E2E 21.1000919342041 ms, pero la rapidez no compensa el fallo de abstención.

Ambos modos abstienen 9/16 negativos = 56.25%; recuperan falsamente 7/16, IDs n002,n003,n004,n005,n006,n007,n008. El gate exigía al menos 90%, que con N16 requiere 15 abstenciones. Las 8 consultas dinámicas por modo resolvieron correctamente requires_tool y no ofrecieron chunks como fuente autoritativa de stock/precio. El noveno negativo abstuvo por score. Las 7 recuperaciones OOD restantes siguen siendo un fallo real del prototipo. No se elevaron pisos, no se añadió una lista de estos IDs y no se cambió el modelo para hacerlo pasar.

### Consistencia exacta PostgreSQL frente a NumPy

La comparación de las 48 consultas positivas confirmó 48/48 Top3 exactos entre SQL/pgvector y NumPy; error máximo de coseno `2.7264383128944303e-07`. No fue necesario usar la excepción para empates numéricos. La precisión del almacenamiento/búsqueda es independiente de la calidad semántica de abstención: SQL correcto no implica dominio/OOD correcto.

### Camisa y blusa en recuperación real

q029 devolvió la comparación y ambas definiciones con source/source_version/corpus_version/content_hash/review_status. La procedencia conserva propuesta pendiente de revisión Mati; no transforma esa propuesta en una nueva categoría ni en una decisión comercial.

| Rank híbrido | Título | Chunk | Coseno real SQL | Ranking RRF |
| --- | --- | --- | ---: | ---: |
| 1 | CAMISA vs BLUSA | comparison:camisa-blusa#comparison | 0.8723098115736374 | 0.01639344262295082 |
| 2 | CAMISAS | category:3#definition | 0.8722339424808094 | 0.016129032258064516 |
| 3 | BLUSAS | category:4#definition | 0.8701352035838122 | 0.015873015873015872 |

Coseno y score RRF son señales diferentes y ninguno es probabilidad. El contenido recuperado no es una respuesta generada por un LLM ni una orden de cambiar un producto.

## Decisión final de promoción y conservación visual

El perfil **medido**, conservado por la evidencia anterior, tiene SHA256 `4ac11cac22a6eda86ca1cccf57daa086a9425c3acb20fbc3f3d4ec121793be61`. El [perfil final versionado](/home/soto/Code/more/services/mati-ai/resources/rag/model-profile.json) tiene SHA256 `ca82f5b59822da6666eb02fe921c253f3a73b1d0a76f053be6d73f0d095d498b`: sólo cambia metadatos, con `rag_gate_state=failed_negative_abstention` y `rag_gate_decision_sha256` para enlazar la decisión. Modelo, revisión, checkpoint, prefijos, dimensión, piso de coseno, normalización y pipeline permanecen iguales. Se sincroniza ese perfil final con la imagen; no representa un modelo nuevo ni otra medición. Los timings, consultas y resultados anteriores siguen vinculados al perfil medido, sin recalcularlos ni sobrescribir su evidencia.

[Decisión de recuperación](/home/soto/Code/more/storage/app/ai-semantic/retrieval-gate-decision.json), SHA256 `06f96264e0c176f5ad4a18c07a955b83ed0292a2ad148564c78b006c51fe68f1`: text_model_approved=true; rag_retrieval_approved=false; vector=false e hybrid=false. Motivo: negative_abstention_9_of_16_below_preregistered_90_percent. El comportamiento de la ruta pública queda en insufficient_evidence, chunks=[] y reason=RAG_GATE_NOT_APPROVED. La evaluación del prototipo requiere el override explícito de consola; no equivale a promoción pública. El piso E5 sigue 0.74 y MiniLM 0.35, con no_post_result_tuning=true.

[Regresión visual](/home/soto/Code/more/storage/app/ai-semantic/visual-after.json), SHA256 `dfd0c5136fb968aec23729d39dc0f979d309c664932cee80a5fae32ff276c017`, comparó la misma fotografía 13 antes/después y respondió 200. Checks: same_model=true, same_classification=true, embedding 768 L2 unchanged=true, delta máximo=0, commercial_color_guard=true, ocr_enabled_preserved=true y gpu_visual_preserved=true. Se conserva SigLIP 7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed. Es una comparación de una fotografía real, no una reejecución científica de todo N130 ni una prueba nueva de calidad OCR CER/WER.

El cierre autoriza el servicio interno de embeddings de texto evaluado y mantiene la recuperación RAG sin promoción. Los resultados negativos, las propuestas semánticas pendientes de revisión y los P2 previos siguen visibles. No se modificaron fuentes productivas, protocolos, pesos, prompts ni datos para redactar este cierre.
