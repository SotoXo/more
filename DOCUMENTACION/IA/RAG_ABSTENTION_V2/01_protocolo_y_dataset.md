# RAG Abstention V2: protocolo y dataset

Este experimento decide si Mati debe entregar evidencia textual. No genera una respuesta ni ejecuta herramientas. Las etiquetas se fijaron leyendo los 23 documentos actuales, antes de cualquier embedding, recuperación o ajuste V2. El autor es Codex; la curación no constituye ground truth revisado por especialistas de Mati ni una muestra de tráfico real.

## Congelación

Dataset rag_answerability_v2, esquema mati-rag-answerability-dataset-2, congelado el **2026-10-06T02:29:16.290989+00:00**. Corpus 1.0.0 SHA-256 d1fd24f3a18e9c169c6197e6bf12da8a663359010ffc271c33918eb8893a3fe2; taxonomía SHA-256 5ef5ecc95ee9f19bfb4fb2d1370f21610ac1b4b9ed97fdb63adb72e2dc267e84.

E5 permanece intfloat/multilingual-e5-small, revisión 614241f622f53c4eeff9890bdc4f31cfecc418b3, 384 dimensiones, CPU FP32 L2, cuatro threads y preprocess/prefijos aprobados. No se vuelven a comparar modelos. El benchmark V1 se conserva como HISTORICAL_BASELINE; sus consultas/resultados no se leyeron para autoría ni ajuste V2.

El protocolo científico preregistrado es [protocol.json](../../../../services/mati-ai/scripts/gate_v2/protocol.json), SHA-256 **60b678fb8a99cdb9ac1c94b6d13e49fe2092c4751eeb9f921794d54a11f05a8b**, authored UTC 2026-10-06T02:30:21.610813+00:00. El sello privado preregistration-seal.json tiene SHA-256 bbb3487685e113887a89af54574245772ebcbc0635ea3cb8c2f5bed897b116bb.

## Composición

| Split | N | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE | ES | EN | Grupos |
|---|---:|---:|---:|---:|---:|---:|---:|
| TRAIN | 108 | 36 | 36 | 36 | 81 | 27 | 19 |
| CALIBRATION | 36 | 12 | 12 | 12 | 27 | 9 | 10 |
| HOLDOUT | 36 | 12 | 12 | 12 | 27 | 9 | 12 |
| Total | 180 | 60 | 60 | 60 | 135 | 45 | 41 |

Se fija 75% español y 25% inglés. Las preguntas inglesas son formulaciones distintas, no traducciones completas de las españolas. No se infla N repitiendo una misma comparación: los grupos positivos cubren afirmaciones atómicas distintas de su evidencia estable.

RAG_ANSWERABLE exige respaldo en corpus y declara expected_document_ids, expected_chunk_ids y rationale específico. Los IDs múltiples son fuentes alternativas suficientes, según expected_match_policy=any_supporting_chunk. La métrica principal es **HitRecall@3 por consulta**: positivas aceptadas con al menos un chunk declarado en Top-3 / positivas aceptadas. También se reporta macro_expected_document_set_recall_at_3 como cobertura diagnóstica fraccional. Esta convención se fijó antes de recuperar; no se presenta como recall exhaustivo de todos los chunks ni se compara directamente con la métrica fraccional V1. El caso crítico camisa/blusa exige además los tres documentos específicos.

TOOL_REQUIRED exige referente comercial suficiente y datos/autoridad dinámicos: unidades, precio, talla comprable, reserva, pago, conciliación o análisis comercial. required_tool es sólo metadata conceptual del ground truth, no una feature ni una herramienta ejecutable.

INSUFFICIENT_EVIDENCE incluye atributos individuales no acreditados, premisas fabricadas, fuera de dominio e incompletas. Las carencias individuales se registran CORPUS_GAP; no se agregan documentos para corregir errores del benchmark.

Una consulta aislada carece de historial, imagen y antecedente. Si faltan producto, alternativas, operandos o criterio, se etiqueta insuficiente; no se inventa contexto. La existencia comercial de talla para un artículo identificado requiere Tool. La garantía de ajuste personal sin medidas queda insuficiente.

## Leakage y familias

El split manual preregistrado es 60/20/20. group_id, source_concept y template_family son disjuntos entre splits. Categorías y comparaciones conectadas permanecen juntas: ningún documento esperado positivo cruza splits. Los 23 documentos están representados; 19 aparecen en TRAIN/CALIBRATION. Su distribución se definió sin rendimiento de embeddings.

semantic_family es una categoría amplia de reporte que puede reaparecer con conceptos fuente y mecanismos concretos distintos. El lenguaje interrogativo general no define una plantilla semántica. Se agrupan afirmaciones relacionadas, equivalencias y patrones concretos del mismo propósito; no se asignan nombres artificiales para repartir paráfrasis entre splits. Referencias a categorías generales y hechos de SKUs comerciales son conceptos de autoridad distintos.

La revisión antes de congelar comprobó 180 queries únicas, cero cruces de grupo/concepto/plantilla/documentos esperados y cero pares cruzados con Jaccard de tokens normalizados >=0.65; el máximo fue 0.352941. Ese screening no demuestra independencia estadística ni reemplaza revisión humana y no entra en features.

| Split | Unsupported in-domain | Out-of-domain | Ambiguous | Positivas fáciles | Positivas difíciles |
|---|---:|---:|---:|---:|---:|
| TRAIN | 24 | 6 | 6 | 18 | 18 |
| CALIBRATION | 6 | 3 | 3 | 6 | 6 |
| HOLDOUT | 6 | 3 | 3 | 3 | 9 |

Hay hard negatives sobre atributos físicos concretos, fabricación/diseño, ajuste sin medidas, reglas inventadas y autoridad comercial próxima a taxonomía. Los porcentajes de subgrupo deben acompañarse de N.

## Evaluación preregistrada

Se comparan threshold coseno, política determinista multiseñal y Logistic Regression escalar regularizada. Sólo TRAIN/CALIBRATION permiten diseñar features/reglas/modelo/thresholds. Etiqueta, IDs esperados, familia real, grupo, split, correctitud y datos futuros están prohibidos como features.

Antes de la única evaluación HOLDOUT se sellan código, features, routing, parámetros, modelos, protocolo y selección. Se elige un representante por familia usando CALIBRATION; se evalúan conjuntamente una sola vez. Se favorece la solución más simple que pase todos los gates: THRESHOLD → MULTI_SIGNAL → LOGISTIC_REGRESSION. Si no hay elegible en calibración, el holdout es diagnóstico y no puede promover aunque arroje métricas favorables.

Gates fijados por el usuario: recall positivo >=0.90, rechazo negativo >=0.90, FAR <=0.10, F1 >=0.90, HitRecall@3 entre positivas aceptadas >=0.90 y cero bypass TOOL_REQUIRED hacia RAG en stock/precio/reserva/pago/inventario críticos. El protocolo añade conservadoramente rechazo >=0.90 en **cada** subgrupo negativo (tools, unsupported, OOD, ambiguous), elegibilidad previa en calibración y paso de suite crítica. Así los OOD fáciles no compensan aceptación de hechos individuales sin evidencia. Denominador requerido vacío implica ineligible.

Se aplican gates a estimaciones puntuales. Los CI Wilson 95% para proporciones son informativos, no un requisito posterior de límite inferior. F1/MCC/AUC y el recall fraccional diagnóstico no reciben intervalos binomiales artificiales. Se evalúan separadamente gate binario y routing de tres clases.

HOLDOUT tiene 12 positivas y 24 negativas: recall>=0.90 exige al menos 11/12; FAR<=0.10 permite como máximo 2/24. Sus subgrupos son pequeños, así que se reportan incertidumbre, conteos, error analysis completo y cobertura limitada. No se pretende establecer precisión general de producción con esa muestra.

Los ejemplos de aceptación explícitos del usuario forman una **suite crítica separada** y no ajustan parámetros. Incluyen camisa/blusa y las cinco consultas dinámicas requeridas. Después de abrir HOLDOUT no se retocan parámetros ni se repite ese experimento; cualquier mejora exige V3 independiente.

## Artefactos privados

Directorio storage/app/ai-rag-gate-v2/ en modo 0700; archivos 0600, sin secretos y fuera de Git.

| Archivo | SHA-256 |
|---|---|
| dataset-frozen.json | 88abee677c55733b6a531d1c230d4ec05d49f9465b81028689faf856db73ef01 |
| split-manifest.json | 188625e31ad3f23b21a9613ef2878462ca50bec8788ba242066b673cdd1ae133 |
| train-cal-queries.json | 244bcc7480a2abc03919155f3981cfaeb6c4b807ffeece551ec5dfa4020360d0 |
| holdout-sealed.json | 332e13b848f64bd6535692f36edcdd77a64013cdafb059ecbd3e775ffcd37c4a |

dataset-quality.json contiene composición/validaciones sin textos retenidos. author_dataset_v2.py conserva autoría privada y rechaza sobrescribir outputs congelados; tampoco va a Git. El validador versionado services/mati-ai/scripts/gate_v2/validate_dataset.py contiene sólo schema/checks: por defecto verifica hashes y lee únicamente 144 filas TRAIN/CALIBRATION. --final-audit sólo debe usarse tras sellado/evaluación final para comprobar consistencia completa.

El holdout no se transforma en features ni se usa semánticamente antes del barrier final. Se pueden verificar hashes por bytes sin deserializar labels. Los documentos 02–04 registran diseño de features, calibración, freeze y ejecución final.

La ruta pública conserva RAG_GATE_NOT_APPROVED y chunks=[] hasta aprobación versionada. Incluso aprobada, sólo answerable entrega chunks; Tools/insuficiente/modelo faltante/features inválidas/excepciones cierran la evidencia. Esta fase no depende de corregir feedback duplicate, stale A/B, lineage o idempotence del target selector; permanecen fuera de alcance junto con sus eventos humanos.
