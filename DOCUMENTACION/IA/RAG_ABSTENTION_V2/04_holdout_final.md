# Holdout final RAG V2

**NO_PROMOTION. Ganador NONE. Abstention NOT APPROVED; ruta pública BLOCKED.** E5 y el componente de recuperación conservan la aprobación previa; la fundación RAG completa no queda aprobada. LLM y tools no implementados.

## Ejecución única y congelación

| Evento UTC | Timestamp |
| --- | --- |
| Freeze | 2026-10-06T03:07:36.313545+00:00 |
| Marca previa a deserializar | 2026-10-06T03:07:36.374079+00:00 |
| Extracción real | 2026-10-06T03:08:02+00:00 |
| Evaluación exclusiva | 2026-10-06T03:08:13.666156+00:00 |
| Final | 2026-10-06T03:08:14.943336+00:00 |

36 consultas, 12 por clase, 27 español/9 inglés, 12 grupos; positivos fáciles3/difíciles9, negativos tool12/unsupported6/OOD3/ambiguos3. No grupos de entrenamiento compartidos. El extractor recibe id/query/split de control, sin ground truth. Una extracción con los tres modelos ya congelados y una evaluación final; evaluation_count=1. Markers exclusivos, sin retry, parámetros ni código medido modificados posteriormente.

| Artefacto | SHA-256 |
| --- | --- |
| Freeze | 4a5b8f4cd162b57e3e98d53bc3e6f884d2c17e8b90ebd261f0b9b79e69e319cb |
| Protocolo | 60b678fb8a99cdb9ac1c94b6d13e49fe2092c4751eeb9f921794d54a11f05a8b |
| Dataset | 88abee677c55733b6a531d1c230d4ec05d49f9465b81028689faf856db73ef01 |
| Splits | 188625e31ad3f23b21a9613ef2878462ca50bec8788ba242066b673cdd1ae133 |
| HOLDOUT autor | 332e13b848f64bd6535692f36edcdd77a64013cdafb059ecbd3e775ffcd37c4a |
| HOLDOUT features | 8ccf6e55bc5dc3000c3ce8e19c65cd8dc40a928a1ba344c1fface138728d6791 |
| Selección | 40b7cf7a2258d5b708d2bbcd6aa0af962546494935f82e4179e2fadd1002bca0 |
| Resultado final | 5c89f96862991c95ed50fa27e357089b2573cd84bd1ed21c1e453a49423e9a9d |
| threshold_t075 | 23c533fca4468faaedac8fc96de9c07cf673ff19ecd9fbd87da9fb3a1a6f3fbd |
| multi_v080_o050 | 08ff366698486ce2c6e532fff9d33ef0f7e047981160b05fa93a006e1e462622 |
| lr_t030 | 94d8e9868edc57f5102e9707af1146c51573d88e12190346e5e84f117bf989b0 |

Git HEAD=e422ef4c0a279172ca03818a4b16ffb501d493bd. Fuente runtime SHA=a15b3f1fca24496249ac3a704374cbb33fa2d53c2640a11734a1a2c071c5f3fb. Las siete fuentes Python y el bundle PHP permanecen iguales a freeze. Los artefactos privados conservan predicciones, matrices, hashes de código/modelos y trazabilidad completa.

## Comparación requerida

| Method | Positive Recall | Negative Rejection | FAR | FRR | F1 | MCC | AUROC | p95 gate Python ms | Interpretable | Promotion |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| threshold | 1.000000 | 0.208333 | 0.791667 | 0.000000 | 0.558140 | 0.283981 | 0.954861 | 0.008388 | sí | NO |
| multi_signal | 0.833333 | 0.750000 | 0.250000 | 0.166667 | 0.714286 | 0.553399 | 0.809028 | 0.011776 | sí | NO |
| logistic_regression | 0.833333 | 0.958333 | 0.041667 | 0.166667 | 0.869565 | 0.810163 | 0.975694 | 0.016221 | coeficientes escalares | NO |

p95 de esta tabla: gate portable Python, validación y reporting de soporte; no E2E HTTP. El E2E real conjunto se detalla abajo.

## Métricas binarias operational

| Método | N | Pos/Neg | TP/FP/TN/FN | Accuracy | Balanced accuracy | Precision | Recall | F1 | Specificity | FAR | FRR | MCC | AUROC | PR-AUC (AP) |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| threshold | 36 | 12/24 | 12/19/5/0 | 0.472222 | 0.604167 | 0.387097 | 1.000000 | 0.558140 | 0.208333 | 0.791667 | 0.000000 | 0.283981 | 0.954861 | 0.935556 |
| multi_signal | 36 | 12/24 | 10/6/18/2 | 0.777778 | 0.791667 | 0.625000 | 0.833333 | 0.714286 | 0.750000 | 0.250000 | 0.166667 | 0.553399 | 0.809028 | 0.585349 |
| logistic_regression | 36 | 12/24 | 10/1/23/2 | 0.916667 | 0.895833 | 0.909091 | 0.833333 | 0.869565 | 0.958333 | 0.041667 | 0.166667 | 0.810163 | 0.975694 | 0.957900 |

## Métricas binarias raw_binary

| Método | N | Pos/Neg | TP/FP/TN/FN | Accuracy | Balanced accuracy | Precision | Recall | F1 | Specificity | FAR | FRR | MCC | AUROC | PR-AUC (AP) |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| threshold | 36 | 12/24 | 12/24/0/0 | 0.333333 | 0.500000 | 0.333333 | 1.000000 | 0.500000 | 0.000000 | 1.000000 | 0.000000 | N/A | 0.951389 | 0.933392 |
| multi_signal | 36 | 12/24 | 10/7/17/2 | 0.750000 | 0.770833 | 0.588235 | 0.833333 | 0.689655 | 0.708333 | 0.291667 | 0.166667 | 0.511478 | 0.770833 | 0.545752 |
| logistic_regression | 36 | 12/24 | 10/2/22/2 | 0.888889 | 0.875000 | 0.833333 | 0.833333 | 0.833333 | 0.916667 | 0.083333 | 0.166667 | 0.750000 | 0.965278 | 0.943056 |

Operational incluye el router; raw_binary evalúa sólo el clasificador. AUROC y PR-AUC usan el score de cada variante; la regla multi-signal tiene score binario grueso. Los scores LR son answerability_score no calibrados.

## Evidencia positiva aceptada

| Método | Hit@3 queries | Cobertura documentos esperados | Macro set recall@3 |
| --- | --- | --- | --- |
| threshold | 12/12 | 14/14 | 1.000000 |
| multi_signal | 10/10 | 12/12 | 1.000000 |
| logistic_regression | 10/10 | 12/12 | 1.000000 |

Hit@3 requiere al menos una fuente suficiente entre alternativas esperadas. El caso crítico camisa/blusa exige conjuntamente los tres documentos. No comparar directamente este indicador con el recall fraccional histórico V1≈0.931.

## Intervalos Wilson 95 %

| Método | Tasa | Éxitos/N | Estimación | IC bajo | IC alto |
| --- | --- | --- | --- | --- | --- |
| threshold | accuracy | 17/36 | 0.472222 | 0.319860 | 0.629941 |
| threshold | precision | 12/31 | 0.387097 | 0.237331 | 0.561759 |
| threshold | answerable_recall | 12/12 | 1.000000 | 0.757506 | 1 |
| threshold | negative_rejection | 5/24 | 0.208333 | 0.092448 | 0.404705 |
| threshold | false_accept_rate | 19/24 | 0.791667 | 0.595295 | 0.907552 |
| threshold | false_reject_rate | 0/12 | 0.000000 | 0.000000 | 0.242494 |
| threshold | accepted_positive_query_hit_recall_at_3 | 12/12 | 1.000000 | 0.757506 | 1 |
| multi_signal | accuracy | 28/36 | 0.777778 | 0.619153 | 0.882837 |
| multi_signal | precision | 10/16 | 0.625000 | 0.386410 | 0.815188 |
| multi_signal | answerable_recall | 10/12 | 0.833333 | 0.551969 | 0.953035 |
| multi_signal | negative_rejection | 18/24 | 0.750000 | 0.551006 | 0.880006 |
| multi_signal | false_accept_rate | 6/24 | 0.250000 | 0.119994 | 0.448994 |
| multi_signal | false_reject_rate | 2/12 | 0.166667 | 0.046965 | 0.448031 |
| multi_signal | accepted_positive_query_hit_recall_at_3 | 10/10 | 1.000000 | 0.722467 | 1.000000 |
| logistic_regression | accuracy | 33/36 | 0.916667 | 0.781733 | 0.971251 |
| logistic_regression | precision | 10/11 | 0.909091 | 0.622642 | 0.983768 |
| logistic_regression | answerable_recall | 10/12 | 0.833333 | 0.551969 | 0.953035 |
| logistic_regression | negative_rejection | 23/24 | 0.958333 | 0.797582 | 0.992607 |
| logistic_regression | false_accept_rate | 1/24 | 0.041667 | 0.007393 | 0.202418 |
| logistic_regression | false_reject_rate | 2/12 | 0.166667 | 0.046965 | 0.448031 |
| logistic_regression | accepted_positive_query_hit_recall_at_3 | 10/10 | 1.000000 | 0.722467 | 1.000000 |

Intervalos informativos para tasas binomiales; gates sobre estimaciones puntuales preregistradas. Con sólo12 positivos y24 negativos los intervalos son amplios. No se atribuye Wilson a F1, MCC, AUROC, AP o recall fraccional.

## Subgrupos

| Método | Grupo | N | Pos/Neg | TP/FP/TN/FN | Recall | Rejection | F1 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| threshold | en | 9 | 3/6 | 3/5/1/0 | 1.000000 | 0.166667 | 0.545455 |
| threshold | es | 27 | 9/18 | 9/14/4/0 | 1.000000 | 0.222222 | 0.562500 |
| threshold | easy_positive | 3 | 3/0 | 3/0/0/0 | 1.000000 | N/A | 1.000000 |
| threshold | hard_negative | 21 | 0/21 | 0/16/5/0 | N/A | 0.238095 | 0.000000 |
| threshold | hard_positive | 9 | 9/0 | 9/0/0/0 | 1.000000 | N/A | 1.000000 |
| threshold | out_of_domain | 3 | 0/3 | 0/3/0/0 | N/A | 0.000000 | 0.000000 |
| threshold | tool_required | 12 | 0/12 | 0/8/4/0 | N/A | 0.333333 | 0.000000 |
| threshold | unsupported_in_domain | 6 | 0/6 | 0/6/0/0 | N/A | 0.000000 | 0.000000 |
| threshold | ood | 3 | 0/3 | 0/3/0/0 | N/A | 0.000000 | 0.000000 |
| threshold | ambiguous | 3 | 0/3 | 0/2/1/0 | N/A | 0.333333 | 0.000000 |
| multi_signal | en | 9 | 3/6 | 2/0/6/1 | 0.666667 | 1.000000 | 0.800000 |
| multi_signal | es | 27 | 9/18 | 8/6/12/1 | 0.888889 | 0.666667 | 0.695652 |
| multi_signal | easy_positive | 3 | 3/0 | 2/0/0/1 | 0.666667 | N/A | 0.800000 |
| multi_signal | hard_negative | 21 | 0/21 | 0/6/15/0 | N/A | 0.714286 | 0.000000 |
| multi_signal | hard_positive | 9 | 9/0 | 8/0/0/1 | 0.888889 | N/A | 0.941176 |
| multi_signal | out_of_domain | 3 | 0/3 | 0/0/3/0 | N/A | 1.000000 | N/A |
| multi_signal | tool_required | 12 | 0/12 | 0/4/8/0 | N/A | 0.666667 | 0.000000 |
| multi_signal | unsupported_in_domain | 6 | 0/6 | 0/2/4/0 | N/A | 0.666667 | 0.000000 |
| multi_signal | ood | 3 | 0/3 | 0/0/3/0 | N/A | 1.000000 | N/A |
| multi_signal | ambiguous | 3 | 0/3 | 0/0/3/0 | N/A | 1.000000 | N/A |
| logistic_regression | en | 9 | 3/6 | 1/0/6/2 | 0.333333 | 1.000000 | 0.500000 |
| logistic_regression | es | 27 | 9/18 | 9/1/17/0 | 1.000000 | 0.944444 | 0.947368 |
| logistic_regression | easy_positive | 3 | 3/0 | 3/0/0/0 | 1.000000 | N/A | 1.000000 |
| logistic_regression | hard_negative | 21 | 0/21 | 0/1/20/0 | N/A | 0.952381 | 0.000000 |
| logistic_regression | hard_positive | 9 | 9/0 | 7/0/0/2 | 0.777778 | N/A | 0.875000 |
| logistic_regression | out_of_domain | 3 | 0/3 | 0/0/3/0 | N/A | 1.000000 | N/A |
| logistic_regression | tool_required | 12 | 0/12 | 0/0/12/0 | N/A | 1.000000 | N/A |
| logistic_regression | unsupported_in_domain | 6 | 0/6 | 0/1/5/0 | N/A | 0.833333 | 0.000000 |
| logistic_regression | ood | 3 | 0/3 | 0/0/3/0 | N/A | 1.000000 | N/A |
| logistic_regression | ambiguous | 3 | 0/3 | 0/0/3/0 | N/A | 1.000000 | N/A |

LR: inglés sólo1/3 positivos aceptados; unsupported5/6 rechazados. El buen agregado no compensa estos fallos. Denominadores ausentes son N/A.

## Routing de tres clases

Filas=GT; columnas=RAG_ANSWERABLE, TOOL_REQUIRED, INSUFFICIENT_EVIDENCE. Rechazo binario de tools y routing correcto no son equivalentes.

### threshold; accuracy=0.472222

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 12 | 0 | 0 |
| TOOL_REQUIRED | 8 | 4 | 0 |
| INSUFFICIENT_EVIDENCE | 11 | 0 | 1 |

### multi_signal; accuracy=0.666667

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 10 | 0 | 2 |
| TOOL_REQUIRED | 4 | 4 | 4 |
| INSUFFICIENT_EVIDENCE | 2 | 0 | 10 |

### logistic_regression; accuracy=0.694444

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 10 | 0 | 2 |
| TOOL_REQUIRED | 0 | 4 | 8 |
| INSUFFICIENT_EVIDENCE | 1 | 0 | 11 |

El router compartido identifica sólo4/12 tools del holdout. Threshold deja8/12 tools como RAG; multi4/12. LR no deja ninguno como RAG pero clasifica8/12 como INSUFFICIENT_EVIDENCE. Las formas nuevas de reposición, descuento y saldo contable superan las reglas fijadas. No se ejecutó ninguna herramienta.

## Suite crítica y camisa/blusa

| Candidato | Tools críticos correctos/N | Bypass a RAG | Camisa all3 Top3 | Camisa aceptada |
| --- | --- | --- | --- | --- |
| threshold_t075 | 6/6 | 0 | PASS | FAIL |
| multi_v080_o050 | 6/6 | 0 | PASS | FAIL |
| lr_t030 | 6/6 | 0 | PASS | FAIL |

Las seis consultas críticas de stock, talla, precio, reserva, pago e inventario resultan TOOL_REQUIRED en los tres candidatos. La consulta “¿Qué diferencia hay entre una camisa y una blusa?” recupera comparison:camisa-blusa (0.872493), category:3/CAMISAS (0.868954), category:4/BLUSAS (0.867808), pero “hay” activa DYNAMIC_inventory_availability. Los gates binarios la aceptan; el router la deriva incorrectamente a tool. Resultado end-to-end del positivo: FAIL. Fuente y reglas quedan congeladas.

**threshold**: CAL elegible=false; HOLD fallos: negative_rejection, f1, false_accept_rate, negative_subgroup:tool_required, negative_subgroup:unsupported_in_domain, negative_subgroup:ood, negative_subgroup:ambiguous; crítico camisa/blusa FAIL; promotable=false.

**multi_signal**: CAL elegible=false; HOLD fallos: answerable_recall, negative_rejection, f1, false_accept_rate, negative_subgroup:tool_required, negative_subgroup:unsupported_in_domain; crítico camisa/blusa FAIL; promotable=false.

**logistic_regression**: CAL elegible=false; HOLD fallos: answerable_recall, f1, negative_subgroup:unsupported_in_domain; crítico camisa/blusa FAIL; promotable=false.

Gates principales: recall≥0.90, rejection≥0.90, FAR≤0.10, F1≥0.90, Hit@3 aceptados≥0.90, cero bypass tools críticos. El protocolo añadió conservadoramente elegibilidad CAL y rejection≥0.90 en cada subgrupo negativo, además del control positivo camisa/blusa. Ningún candidato pasa los criterios originales agregados, aun sin los criterios adicionales.

## Latencia CPU y HTTP

| Etapa | N | p50 ms | p95 ms | máximo ms |
| --- | --- | --- | --- | --- |
| Embedding cliente E5 + HTTP + validación | 36 | 22.047520 | 37.525237 | 162.401915 |
| SQL vector exacto | 36 | 1.647949 | 3.095925 | 14.715910 |
| SQL lexical PostgreSQL | 36 | 3.454447 | 5.897582 | 11.541128 |
| Features PHP | 36 | 0.340581 | 0.466585 | 0.813007 |
| Gates PHP agregados (3) | 36 | 0.041962 | 0.057995 | 0.062943 |
| E2E prototipo conjunto (3 gates) | 36 | 31.031966 | 49.659073 | 227.812052 |

Los tiempos de embedding son latencia del cliente real, no forward E5 puro. total_ms incluye la extracción y evaluación PHP conjunta de los tres candidatos; no es un endpoint aprobado desplegado. No sumar la latencia Python a esa medición. El máximo HOLD observado tampoco se elimina. Primera consulta crítica: embedding403.529ms/E2E466.303ms; se conserva como outlier de inicialización/transporte, sin atribuirlo con certeza al forward.

144/144 embeddings TRAIN/CAL y36/36 HOLD: HTTP200, integridad PASS, cero500. Probe real FastAPI:401 sin auth/token malo;422 payload inválido/query vacía;200 ES/EN/Unicode y repetición determinista, delta0.

Probe de kernel Laravel real (controlador/retriever/resources reales, usuario superadmin sólo en memoria, sesión array; sin mocks de evidencia):11/11 casos PASS;401 sin auth/bearer malo;422 inválido/vacío/largo;200 para consultas válidas de ambas lenguas, tool, unsupported, ambigua e injection. Todas las válidas permanecen insufficient_evidence / required_information / RAG_GATE_NOT_APPROVED / chunks=[]. No es login E2E de navegador.

## Decisión y preservación

Se guarda gate-decision.json explícito con approved=false, decision=NO_PROMOTION, winner=null y SHA del resultado. No se instala gate-model.json; los tres artefactos candidatos permanecen privados. El perfil V1 no se altera. Error/missing model/no aprobación continúa fail closed.

Post-check PASS:34 tablas comerciales idénticas,134 filas visuales idénticas, imágenes idénticas, AI counts4/147/11/2/134 sin cambios. RAG1modelo/23docs/23chunks/23embeddings con fingerprints de filas iguales antes/después. Runtime imagen, user, cmd, settings, cache mounts y perfil idénticos; mount order comparado por identidad, no por orden de presentación de Docker.

Los errores se analizan en 05_error_analysis.md sin tocar etiquetas, corpus, features, router, thresholds o pesos. Este holdout queda consumido; un nuevo experimento requiere V3 y separación nueva.
