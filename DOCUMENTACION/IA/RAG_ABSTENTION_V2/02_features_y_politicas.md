# Features y políticas de abstención V2

Esta fase compara tres gates de answerability sobre el mismo espacio textual E5-small y el mismo corpus de 23 documentos. No genera respuestas, no ejecuta herramientas y no modifica el modelo de embeddings, las taxonomías ni los consumidores visuales. La promoción pública sigue bloqueada hasta que un representante congelado pase CAL, HOLD y la suite crítica, y root apruebe el artefacto.

El protocolo se fijó antes de extraer señales: `services/mati-ai/scripts/gate_v2/protocol.json`, SHA256 `60b678fb8a99cdb9ac1c94b6d13e49fe2092c4751eeb9f921794d54a11f05a8b`. Su sello privado es `storage/app/ai-rag-gate-v2/preregistration-seal.json`, SHA256 `bbb3487685e113887a89af54574245772ebcbc0635ea3cb8c2f5bed897b116bb`. Las métricas se publican posteriormente en 03 y 04; este documento describe decisiones previas a los resultados.

## Seis señales, en orden obligatorio

La fuente es `services/mati-ai/resources/rag_gate_v2/feature-schema.json`. PHP calcula las señales y exporta su orden y sus hashes. Python consume esos escalares; no implementa una segunda normalización del texto.

| Orden | Feature | Fórmula / dominio |
|---:|---|---|
| 1 | `vector_top1` | Primer cosine exacto de vector Top3, o 0 si no existe. Rango validado [−1.000001, 1.000001], tolerancia numérica; no se recorta el dato. |
| 2 | `margin_1_2` | max(0, primer cosine − segundo cosine); posiciones ausentes se rellenan con 0. Rango [0, 2.000002], consistente con la tolerancia de ambos cosines. |
| 3 | `lexical_top1` | Primer `ts_rank_cd` estrictamente positivo de FTS; 0 si falta. Finito y ≥0. |
| 4 | `vector_lexical_agreement` | Número de IDs de chunk comunes entre vector Top3 y los tres primeros resultados léxicos positivos, dividido por 3; [0,1]. |
| 5 | `exact_taxonomy_name_match` | 1 si la consulta normalizada contiene un título o alias curado de categoría completo, con límites de tokens; 0 en otro caso. No exige igualdad de toda la consulta. |
| 6 | `normalized_keyword_overlap` | Tokens útiles únicos de la consulta presentes en la unión de tokens útiles de `search_text` de vector Top3, divididos por los tokens útiles únicos de la consulta; denominador vacío →0. |

No existe feature de etiqueta correcta, grupo, split, familia semántica, expected IDs, corrección del retrieval ni coordenadas del embedding de 384 dimensiones. Esos metadatos se usan exclusivamente para construir el target binario y calcular métricas. La regresión recibe una matriz N×6; ningún identificador de consulta entra en ella.

El padding permite representar 0–3 resultados, pero ausencia de chunks vectoriales impide answerability por una regla común de disponibilidad. No se convierte un cosine ni una coincidencia léxica en probabilidad de verdad.

## Normalización y evidencia

La normalización autorizada es PHP: UTF-8 válido → `Str::ascii` → minúsculas → caracteres ajenos a letras/números convertidos en espacios → colapso de espacios. El SHA256 de la consulta corresponde a sus bytes UTF-8 originales. Tokens útiles significa tokens únicos de la versión normalizada menos las stopwords ES/EN selladas; no hay stemming y se conservan números.

Se conservan específicamente `no/not/never/nunca`. La lista mantiene `sin` como functionword: no se afirma que preserve todas las formas de negación. La lista completa se encuentra en el schema y en el protocolo, y no se modifica después de resultados.

Se ejecutan dos búsquedas exactas, con el mismo espacio textual, documentos activos y filtros: vector y FTS. FTS conserva su configuración `spanish`; no se cambia para favorecer resultados ingleses. Su efecto en EN se reporta como límite. Los scores léxicos se usan para el gate; el contexto entregado, si se acepta la consulta, conserva el vector Top3 y su desempate por ID de chunk. V1 RRF permanece intacto.

Sólo el corpus completo de 23 documentos, Top3 y ausencia de filtros son elegibles para promover V2. Otras solicitudes requieren un gate adecuado para ese contexto; no se extrapola esta validación.

## Políticas congeladas

### Threshold

`vector_top1 ≥ t`, con seis valores CAL: 0.70, 0.75, 0.80, 0.85, 0.90 y 0.95. El score publicado es cosine sin calibración.

### Multi-signal

`vector_top1 ≥ v AND (exact_taxonomy_name_match = 1 OR (lexical_top1 > 0 AND normalized_keyword_overlap ≥ o AND vector_lexical_agreement ≥ 1/3))`.

Se comparan diez reglas: v ∈ {0.75,0.80,0.85,0.90,0.95} y o ∈ {0.25,0.50}. `lexical_min=0` con operador estrictamente `gt`, y agreement=1/3, se fijan antes de medir. Un alias exacto puede suplir evidencia léxica, pero nunca evita el piso vectorial. Este diseño no demuestra que una afirmación concreta esté soportada; los hard negatives son necesarios para medir ese riesgo.

El score es el indicador 0/1 de la regla. Su AUROC/AP, cuando tienen denominadores válidos, son diagnósticos de un score de dos niveles; no representan una curva probabilística fina.

### Regresión logística regularizada

Se realiza un único fit binario de `RAG_ANSWERABLE` frente a `TOOL_REQUIRED + INSUFFICIENT_EVIDENCE`. StandardScaler se ajusta exclusivamente con TRAIN; la regresión también. Configuración fija: C=1, L2, solver `lbfgs`, max_iter=1000, intercept=true, class_weight=None y random_state=0. No hay búsqueda de C, features ni solver.

CAL sólo compara siete umbrales del mismo modelo: 0.30,0.40,0.50,0.60,0.70,0.80 y 0.90. El nombre es `answerability_score`, un sigmoid no calibrado; no se interpreta como certeza factual. No se aplica Platt, isotonic ni calibración posterior.

El artefacto portable contiene seis coeficientes, intercepto, seis medias y escalas de StandardScaler y el umbral. No utiliza pickle, clases serializadas ni código remoto. El evaluador usa sigmoid estable para evitar overflow y rechaza resultados no finitos.

La documentación oficial de [LogisticRegression](https://scikit-learn.org/stable/modules/generated/sklearn.linear_model.LogisticRegression.html) y [StandardScaler](https://scikit-learn.org/stable/modules/generated/sklearn.preprocessing.StandardScaler.html) sustenta estos métodos. La versión ejecutada se registra desde el entorno local; no se presupone que coincida con la versión de la documentación web.

## Router común y comparación honesta

Todos los candidatos comparten el mismo router determinista de tres clases y la misma política de ambigüedad. La consulta dinámica con referente/solicitud explícitos requiere una herramienta; las anáforas sin referente requieren información. Las preguntas conceptuales sobre RAG/tools/evidencia no se convierten automáticamente en herramientas. El JSON de reglas y su hash forman parte del contexto sellado.

Las consultas se extraen también cuando el router las identifica como tools. Para cada candidato se reportan:

1. Predicción binaria cruda, sin override del router.
2. Decisión operacional tras el router y la disponibilidad de chunks.
3. Matriz de confusión de las tres clases.

Así se ve cuánto del rechazo de tools proviene del router. Rechazar una consulta tool como `INSUFFICIENT_EVIDENCE` puede ser correcto en el binario NOT_RAG, pero incorrecto en routing; la suite crítica exige `TOOL_REQUIRED` para los casos dinámicos obligatorios.

## Selección y gates

Hay 23 configuraciones de política (6+10+7) y un fit de LR. CAL elige exactamente un representante por familia. TRAIN se reporta como diagnóstico y no es un requisito adicional de elegibilidad.

Se prefiere cualquier candidato que pase CAL. Entre PASS de la misma familia: menor FAR, mayor recall positivo, mayor F1, umbral/vector_min más alto, overlap más alto y candidate_id estable. Si ningún candidato de una familia pasa CAL, se elige un representante diagnóstico: menor FAR entre los que alcanzan recall≥.90; si ninguno alcanza ese recall, mayor F1, luego recall y menor FAR. Los desempates quedan sellados.

HOLD evalúa una vez los tres representantes congelados. Sólo son promovibles los que eran elegibles en CAL y además pasan todos los gates de HOLD y la suite crítica. Entre ellos, la jerarquía fijada por simplicidad es threshold → multi-signal → LR. No se cambia un umbral después de HOLD ni se elige otra configuración de la rejilla. Si ninguno era elegible en CAL, HOLD se conserva como diagnóstico y la decisión será NO_PROMOTION, incluso si alguno pasa HOLD.

| Gate operacional | Requisito y denominador |
|---|---|
| Recall positivo | TP/(TP+FN) ≥.90; todos los positivos, incluidos rechazados |
| Negative rejection | TN/(TN+FP) ≥.90 |
| FAR | FP/(TN+FP) ≤.10 |
| F1 | 2TP/(2TP+FP+FN) ≥.90 |
| Query HitRecall@3 en positivos aceptados | Queries TP con algún documento alternativo de soporte en vector Top3 / queries TP ≥.90 |
| Cada subgrupo negativo | Negative rejection≥.90 por separado para tools, unsupported in-domain, OOD y ambiguous |
| Tools críticos | Cero bypass TOOL→RAG y routing TOOL_REQUIRED correcto en todos los ejemplos dinámicos obligatorios, incluidos inventario |
| Camisa/blusa crítico | Consulta aceptada y los tres documentos comparación+CAMISAS+BLUSAS en Top3 |

Un denominador requerido vacío vuelve ineligible al candidato. Los gates usan point estimates; no se inventa un requisito sobre el límite inferior de un CI.

El autor congeló los expected IDs como alternativas suficientes (`any_supporting_chunk`). Por ello, el gate primario es query-level HitRecall@3. Se reporta además la media fraccional `|expected_docs∩Top3|/|expected_docs|`, y la cobertura pooled, como diagnósticos separados. El R@3 fraccional de V1 no es una comparación directa con este gate primario. La consulta crítica de camisa/blusa es una excepción conjuntiva: requiere los tres documentos.

## Métricas y validación del artefacto

Se reportan accuracy, balanced accuracy, precision, recall, F1, specificity/negative rejection, FAR, FRR, MCC, AUROC y PR-AUC. PR-AUC se define como `sklearn.average_precision_score`, integral por escalones; no es área trapezoidal. [Las definiciones oficiales](https://scikit-learn.org/stable/modules/model_evaluation.html) se enlazan como referencia.

Los intervalos Wilson95% corresponden a proporciones binomiales con numerator/denominator explícitos: accuracy, precision, recall, specificity, FAR, FRR y query-hit R@3. [SciPy documenta Wilson](https://docs.scipy.org/doc/scipy/reference/generated/scipy.stats._result_classes.BinomTestResult.proportion_ci.html). No se calcula un falso Wilson para F1, MCC, AUROC/AP o la media fraccional de documentos. Denominador 0 implica null, incluido MCC degenerado. Los CI son informativos y advierten el tamaño pequeño de CAL/HOLD; no se usan para optimizar.

Cada JSON valida versiones, orden exacto de seis features, números finitos y dominios. La identidad E5-small/revisión/384/L2/text permanece fija. El contexto contiene seis hashes: corpus, profile, text_space_key, feature-schema, routing-rules y runtime-bundle. Una discrepancia de contexto bloquea el uso. Los hashes del protocolo, input, fuente, scaler y modelo permiten reproducir el resultado.

Las pruebas sintéticas cubren NaN/Inf/bools, features faltantes/adicionales/reordenadas, versiones, dimensiones, contexto obsoleto, sigmoid extremo, lexical0, umbrales inclusivos, padding/no chunks y métricas. Los golden fixtures comparan PHP/Python sin consultar el dataset ni inferir embeddings; la tolerancia absoluta del score es 1e−10.

## Barrera de HOLD

`train_cal.py` lee únicamente `train-cal-queries.json` y una matriz train/cal sellada; rechaza splits ajenos. `evaluate_holdout.py begin` requiere autorización root separada, modelo/contexto/protocolo/fuentes congelados y crea `holdout-started.json` con O_CREAT|O_EXCL y fsync antes de leer bytes de HOLD. Si falla, el marcador permanece.

Tras abrirlo, se exportan id/query y el campo split=holdout controlado para validar la etapa; split nunca es una feature. No se exportan etiquetas, familias ni expected IDs al extractor. La evaluación final requiere otra marca exclusiva, conserva la misma fuente y modelos, y genera una única decisión. No se borra el marcador, no hay retry ni reset de resultados. El programa no activa el endpoint público: un PASS sólo habilita revisión de promoción por root; NO_PROMOTION mantiene el bloqueo.

Las latencias de extracción PHP y gate matemático Python se separan. Una suma offline no se presentará como medición real de endpoint público. Los resultados y error analysis posteriores deben mantener esta distinción.
