# Benchmark de modelos RAG V2

Resultado de desarrollo: **ningún candidato elegible en CALIBRATION**. Los tres representantes se conservaron como diagnóstico para una evaluación final única; no se consideran aprobados por tener el mejor score relativo. La selección no utilizó el benchmark V1 ni el holdout.

## Diseño y lectura de métricas

TRAIN: 108 (36 positivos/72 negativos). CALIBRATION: 36 (12/24). Un único ajuste de LR sobre TRAIN; StandardScaler también sólo TRAIN. Los 23 candidatos son seis umbrales de coseno, diez reglas multi-signal y siete cortes del mismo LR. El routing compartido está congelado.

“Operational” mide RAG sí/no después del router; “raw binary” mide únicamente el gate. Las matrices de tres clases se muestran aparte: rechazar RAG no implica acertar TOOL_REQUIRED. Todos los scores son no calibrados. AUROC se calcula con el score correspondiente; PR-AUC es average_precision_score (integral escalonada), no área trapezoidal. MCC y tasas sin denominador se muestran N/A; no se inventan ceros.

## Curvas completas TRAIN/CALIBRATION

TP/FP/TN/FN son conteos absolutos operativos. Los denominadores son 36/72 para TRAIN y 12/24 para CALIBRATION. Las curvas raw, subgrupos e intervalos están en train-cal-curves.json; ese artefacto omite predicciones. Las predicciones de los representantes seleccionados están en representative-threshold.json, representative-multi_signal.json y representative-logistic_regression.json.

### threshold

| Candidato | Split | TP | FP | TN | FN | Precision | Recall | F1 | Rejection | FAR | CAL elegible |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| threshold_t070 | TRAIN | 36 | 51 | 21 | 0 | 0.413793 | 1.000000 | 0.585366 | 0.291667 | 0.708333 | FAIL |
| threshold_t070 | CAL | 11 | 16 | 8 | 1 | 0.407407 | 0.916667 | 0.564103 | 0.333333 | 0.666667 | FAIL |
| threshold_t075 | TRAIN | 36 | 48 | 24 | 0 | 0.428571 | 1.000000 | 0.600000 | 0.333333 | 0.666667 | FAIL |
| threshold_t075 | CAL | 11 | 13 | 11 | 1 | 0.458333 | 0.916667 | 0.611111 | 0.458333 | 0.541667 | FAIL |
| threshold_t080 | TRAIN | 33 | 29 | 43 | 3 | 0.532258 | 0.916667 | 0.673469 | 0.597222 | 0.402778 | FAIL |
| threshold_t080 | CAL | 10 | 5 | 19 | 2 | 0.666667 | 0.833333 | 0.740741 | 0.791667 | 0.208333 | FAIL |
| threshold_t085 | TRAIN | 24 | 2 | 70 | 12 | 0.923077 | 0.666667 | 0.774194 | 0.972222 | 0.027778 | FAIL |
| threshold_t085 | CAL | 6 | 0 | 24 | 6 | 1.000000 | 0.500000 | 0.666667 | 1.000000 | 0.000000 | FAIL |
| threshold_t090 | TRAIN | 9 | 0 | 72 | 27 | 1.000000 | 0.250000 | 0.400000 | 1.000000 | 0.000000 | FAIL |
| threshold_t090 | CAL | 2 | 0 | 24 | 10 | 1.000000 | 0.166667 | 0.285714 | 1.000000 | 0.000000 | FAIL |
| threshold_t095 | TRAIN | 0 | 0 | 72 | 36 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |
| threshold_t095 | CAL | 0 | 0 | 24 | 12 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |

### multi_signal

| Candidato | Split | TP | FP | TN | FN | Precision | Recall | F1 | Rejection | FAR | CAL elegible |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| multi_v075_o025 | TRAIN | 27 | 33 | 39 | 9 | 0.450000 | 0.750000 | 0.562500 | 0.541667 | 0.458333 | FAIL |
| multi_v075_o025 | CAL | 6 | 9 | 15 | 6 | 0.400000 | 0.500000 | 0.444444 | 0.625000 | 0.375000 | FAIL |
| multi_v075_o050 | TRAIN | 27 | 33 | 39 | 9 | 0.450000 | 0.750000 | 0.562500 | 0.541667 | 0.458333 | FAIL |
| multi_v075_o050 | CAL | 6 | 9 | 15 | 6 | 0.400000 | 0.500000 | 0.444444 | 0.625000 | 0.375000 | FAIL |
| multi_v080_o025 | TRAIN | 25 | 23 | 49 | 11 | 0.520833 | 0.694444 | 0.595238 | 0.680556 | 0.319444 | FAIL |
| multi_v080_o025 | CAL | 5 | 4 | 20 | 7 | 0.555556 | 0.416667 | 0.476190 | 0.833333 | 0.166667 | FAIL |
| multi_v080_o050 | TRAIN | 25 | 23 | 49 | 11 | 0.520833 | 0.694444 | 0.595238 | 0.680556 | 0.319444 | FAIL |
| multi_v080_o050 | CAL | 5 | 4 | 20 | 7 | 0.555556 | 0.416667 | 0.476190 | 0.833333 | 0.166667 | FAIL |
| multi_v085_o025 | TRAIN | 20 | 2 | 70 | 16 | 0.909091 | 0.555556 | 0.689655 | 0.972222 | 0.027778 | FAIL |
| multi_v085_o025 | CAL | 3 | 0 | 24 | 9 | 1.000000 | 0.250000 | 0.400000 | 1.000000 | 0.000000 | FAIL |
| multi_v085_o050 | TRAIN | 20 | 2 | 70 | 16 | 0.909091 | 0.555556 | 0.689655 | 0.972222 | 0.027778 | FAIL |
| multi_v085_o050 | CAL | 3 | 0 | 24 | 9 | 1.000000 | 0.250000 | 0.400000 | 1.000000 | 0.000000 | FAIL |
| multi_v090_o025 | TRAIN | 8 | 0 | 72 | 28 | 1.000000 | 0.222222 | 0.363636 | 1.000000 | 0.000000 | FAIL |
| multi_v090_o025 | CAL | 0 | 0 | 24 | 12 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |
| multi_v090_o050 | TRAIN | 8 | 0 | 72 | 28 | 1.000000 | 0.222222 | 0.363636 | 1.000000 | 0.000000 | FAIL |
| multi_v090_o050 | CAL | 0 | 0 | 24 | 12 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |
| multi_v095_o025 | TRAIN | 0 | 0 | 72 | 36 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |
| multi_v095_o025 | CAL | 0 | 0 | 24 | 12 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |
| multi_v095_o050 | TRAIN | 0 | 0 | 72 | 36 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |
| multi_v095_o050 | CAL | 0 | 0 | 24 | 12 | N/A | 0.000000 | 0.000000 | 1.000000 | 0.000000 | FAIL |

### logistic_regression

| Candidato | Split | TP | FP | TN | FN | Precision | Recall | F1 | Rejection | FAR | CAL elegible |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| lr_t030 | TRAIN | 28 | 6 | 66 | 8 | 0.823529 | 0.777778 | 0.800000 | 0.916667 | 0.083333 | FAIL |
| lr_t030 | CAL | 9 | 1 | 23 | 3 | 0.900000 | 0.750000 | 0.818182 | 0.958333 | 0.041667 | FAIL |
| lr_t040 | TRAIN | 28 | 4 | 68 | 8 | 0.875000 | 0.777778 | 0.823529 | 0.944444 | 0.055556 | FAIL |
| lr_t040 | CAL | 8 | 1 | 23 | 4 | 0.888889 | 0.666667 | 0.761905 | 0.958333 | 0.041667 | FAIL |
| lr_t050 | TRAIN | 27 | 2 | 70 | 9 | 0.931034 | 0.750000 | 0.830769 | 0.972222 | 0.027778 | FAIL |
| lr_t050 | CAL | 8 | 0 | 24 | 4 | 1.000000 | 0.666667 | 0.800000 | 1.000000 | 0.000000 | FAIL |
| lr_t060 | TRAIN | 27 | 1 | 71 | 9 | 0.964286 | 0.750000 | 0.843750 | 0.986111 | 0.013889 | FAIL |
| lr_t060 | CAL | 7 | 0 | 24 | 5 | 1.000000 | 0.583333 | 0.736842 | 1.000000 | 0.000000 | FAIL |
| lr_t070 | TRAIN | 25 | 0 | 72 | 11 | 1.000000 | 0.694444 | 0.819672 | 1.000000 | 0.000000 | FAIL |
| lr_t070 | CAL | 7 | 0 | 24 | 5 | 1.000000 | 0.583333 | 0.736842 | 1.000000 | 0.000000 | FAIL |
| lr_t080 | TRAIN | 22 | 0 | 72 | 14 | 1.000000 | 0.611111 | 0.758621 | 1.000000 | 0.000000 | FAIL |
| lr_t080 | CAL | 6 | 0 | 24 | 6 | 1.000000 | 0.500000 | 0.666667 | 1.000000 | 0.000000 | FAIL |
| lr_t090 | TRAIN | 19 | 0 | 72 | 17 | 1.000000 | 0.527778 | 0.690909 | 1.000000 | 0.000000 | FAIL |
| lr_t090 | CAL | 3 | 0 | 24 | 9 | 1.000000 | 0.250000 | 0.400000 | 1.000000 | 0.000000 | FAIL |

## Representantes congelados

| Método | Candidato | Parámetros | CAL elegible | Selección |
| --- | --- | --- | --- | --- |
| threshold | threshold_t075 | 0.750000 | FAIL | diagnostic_recall_at_least_0.90 |
| multi_signal | multi_v080_o050 | 0.800000 | FAIL | diagnostic_f1 |
| logistic_regression | lr_t030 | 0.300000 | FAIL | diagnostic_f1 |

Threshold: coseno ≥0.75. Multi-signal: coseno ≥0.80 y (alias taxonómico completo presente o (FTS>0, overlap≥0.50, agreement≥1/3)). LR: answerability_score≥0.30. Cada familia seleccionó únicamente en CAL según la prioridad del protocolo. Al no haber PASS, las razones diagnostic_recall_at_least_0.90/diagnostic_f1 no autorizan promoción.

### Métricas completas: operational

| Método | Split | N | Pos/Neg | TP/FP/TN/FN | Accuracy | Balanced accuracy | Precision | Recall | F1 | Specificity | FAR | FRR | MCC | AUROC | PR-AUC (AP) |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| threshold | TRAIN | 108 | 36/72 | 36/48/24/0 | 0.555556 | 0.666667 | 0.428571 | 1.000000 | 0.600000 | 0.333333 | 0.666667 | 0.000000 | 0.377964 | 0.928241 | 0.906548 |
| threshold | CAL | 36 | 12/24 | 11/13/11/1 | 0.611111 | 0.687500 | 0.458333 | 0.916667 | 0.611111 | 0.458333 | 0.541667 | 0.083333 | 0.375000 | 0.895833 | 0.886869 |
| multi_signal | TRAIN | 108 | 36/72 | 25/23/49/11 | 0.685185 | 0.687500 | 0.520833 | 0.694444 | 0.595238 | 0.680556 | 0.319444 | 0.305556 | 0.355756 | 0.732060 | 0.488127 |
| multi_signal | CAL | 36 | 12/24 | 5/4/20/7 | 0.694444 | 0.625000 | 0.555556 | 0.416667 | 0.476190 | 0.833333 | 0.166667 | 0.583333 | 0.272166 | 0.687500 | 0.462963 |
| logistic_regression | TRAIN | 108 | 36/72 | 28/6/66/8 | 0.870370 | 0.847222 | 0.823529 | 0.777778 | 0.800000 | 0.916667 | 0.083333 | 0.222222 | 0.704855 | 0.928241 | 0.912281 |
| logistic_regression | CAL | 36 | 12/24 | 9/1/23/3 | 0.888889 | 0.854167 | 0.900000 | 0.750000 | 0.818182 | 0.958333 | 0.041667 | 0.250000 | 0.745499 | 0.899306 | 0.896128 |

### Métricas completas: raw_binary

| Método | Split | N | Pos/Neg | TP/FP/TN/FN | Accuracy | Balanced accuracy | Precision | Recall | F1 | Specificity | FAR | FRR | MCC | AUROC | PR-AUC (AP) |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| threshold | TRAIN | 108 | 36/72 | 36/67/5/0 | 0.379630 | 0.534722 | 0.349515 | 1.000000 | 0.517986 | 0.069444 | 0.930556 | 0.000000 | 0.155794 | 0.903164 | 0.890139 |
| threshold | CAL | 36 | 12/24 | 12/20/4/0 | 0.444444 | 0.583333 | 0.375000 | 1.000000 | 0.545455 | 0.166667 | 0.833333 | 0.000000 | 0.250000 | 0.937500 | 0.922900 |
| multi_signal | TRAIN | 108 | 36/72 | 25/33/39/11 | 0.592593 | 0.618056 | 0.431034 | 0.694444 | 0.531915 | 0.541667 | 0.458333 | 0.305556 | 0.223221 | 0.618056 | 0.401181 |
| multi_signal | CAL | 36 | 12/24 | 6/6/18/6 | 0.666667 | 0.625000 | 0.500000 | 0.500000 | 0.500000 | 0.750000 | 0.250000 | 0.500000 | 0.250000 | 0.625000 | 0.416667 |
| logistic_regression | TRAIN | 108 | 36/72 | 28/7/65/8 | 0.861111 | 0.840278 | 0.800000 | 0.777778 | 0.788732 | 0.902778 | 0.097222 | 0.222222 | 0.685466 | 0.909722 | 0.902937 |
| logistic_regression | CAL | 36 | 12/24 | 10/1/23/2 | 0.916667 | 0.895833 | 0.909091 | 0.833333 | 0.869565 | 0.958333 | 0.041667 | 0.166667 | 0.810163 | 0.944444 | 0.932900 |

### Routing de tres clases en desarrollo

Filas=clase real; columnas=RAG_ANSWERABLE, TOOL_REQUIRED, INSUFFICIENT_EVIDENCE. Cada matriz conserva las tres clases originales.

**threshold / TRAIN**; accuracy tres clases=0.537037

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 36 | 0 | 0 |
| TOOL_REQUIRED | 16 | 19 | 1 |
| INSUFFICIENT_EVIDENCE | 32 | 1 | 3 |

**threshold / CAL**; accuracy tres clases=0.611111

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 11 | 1 | 0 |
| TOOL_REQUIRED | 5 | 7 | 0 |
| INSUFFICIENT_EVIDENCE | 8 | 0 | 4 |

**multi_signal / TRAIN**; accuracy tres clases=0.611111

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 25 | 0 | 11 |
| TOOL_REQUIRED | 10 | 19 | 7 |
| INSUFFICIENT_EVIDENCE | 13 | 1 | 22 |

**multi_signal / CAL**; accuracy tres clases=0.611111

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 5 | 1 | 6 |
| TOOL_REQUIRED | 2 | 7 | 3 |
| INSUFFICIENT_EVIDENCE | 2 | 0 | 10 |

**logistic_regression / TRAIN**; accuracy tres clases=0.712963

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 28 | 0 | 8 |
| TOOL_REQUIRED | 1 | 19 | 16 |
| INSUFFICIENT_EVIDENCE | 5 | 1 | 30 |

**logistic_regression / CAL**; accuracy tres clases=0.777778

| GT | RAG_ANSWERABLE | TOOL_REQUIRED | INSUFFICIENT_EVIDENCE |
| --- | --- | --- | --- |
| RAG_ANSWERABLE | 9 | 1 | 2 |
| TOOL_REQUIRED | 1 | 7 | 4 |
| INSUFFICIENT_EVIDENCE | 0 | 0 | 12 |

## LR offline reproducible

Modelo único: binario RAG_ANSWERABLE=1, NOT_RAG=0. Solver lbfgs, L2, C=1, class_weight=None, max_iter=1000, seed=0, 12 iteraciones; 108 filas de ajuste (36/72), cero filas CAL/HOLD en fit. Se usan seis escalares, nunca el vector384. Implementación [LogisticRegression](https://scikit-learn.org/stable/modules/generated/sklearn.linear_model.LogisticRegression.html) y [StandardScaler](https://scikit-learn.org/stable/modules/generated/sklearn.preprocessing.StandardScaler.html).

| Orden | Feature | Coeficiente estandarizado | Media TRAIN | Escala TRAIN |
| --- | --- | --- | --- | --- |
| 1 | vector_top1 | 1.488633 | 0.820495 | 0.044621 |
| 2 | margin_1_2 | 0.987948 | 0.017461 | 0.022962 |
| 3 | lexical_top1 | 0.005121 | 0.000455 | 0.003691 |
| 4 | vector_lexical_agreement | 0.007066 | 0.006173 | 0.044939 |
| 5 | exact_taxonomy_name_match | -0.274567 | 0.712963 | 0.452379 |
| 6 | normalized_keyword_overlap | 0.540715 | 0.268338 | 0.258952 |

Intercepto=-0.8159530670595732; corte=0.3. JSON privado conserva precisión completa, orden, scaler, contexto, hashes y versiones. No hay pickle, ajuste online, Platt ni isotonic. Los coeficientes explican este modelo, no prueban causalidad ni certeza.

Paridad sklearn→JSON en 144 filas: delta máximo 2.220446049250313e-16. Paridad Python↔PHP: 11 goldens, error máximo 0.

| Metadata | Valor |
| --- | --- |
| train_feature_label_rows_sha256 | 0e3eff58c4e6c5124fab821ae0225c984cc21d49bde1f2b7cc01dc5520d36194 |
| train_cal_author_sha256 | 244bcc7480a2abc03919155f3981cfaeb6c4b807ffeece551ec5dfa4020360d0 |
| feature_export_sha256 | 2621f371e1514bc4457847f760bc06556805aabee5af760c33ac630b01aee25b |
| cal_rows_sha256 | dc917a9f0300f9c2e20683b82f07ea22877fb2b364ad1021386b354ac68b1fb5 |
| protocol_sha256 | 60b678fb8a99cdb9ac1c94b6d13e49fe2092c4751eeb9f921794d54a11f05a8b |

| Entorno | Valor |
| --- | --- |
| Python | 3.11.15 |
| CPU | AMD Ryzen 7 9700X 8-Core Processor |
| numpy | 2.4.6 |
| scikit-learn | 1.9.0 |
| torch | 2.13.0+cu130 |
| transformers | 5.14.1 |
| scipy | 1.17.1 |

## Congelación

| Artefacto | SHA-256 |
| --- | --- |
| Protocolo | 60b678fb8a99cdb9ac1c94b6d13e49fe2092c4751eeb9f921794d54a11f05a8b |
| Dataset | 88abee677c55733b6a531d1c230d4ec05d49f9465b81028689faf856db73ef01 |
| Splits | 188625e31ad3f23b21a9613ef2878462ca50bec8788ba242066b673cdd1ae133 |
| TRAIN/CAL features | 2621f371e1514bc4457847f760bc06556805aabee5af760c33ac630b01aee25b |
| Selección sellada | 40b7cf7a2258d5b708d2bbcd6aa0af962546494935f82e4179e2fadd1002bca0 |
| threshold_t075 | 23c533fca4468faaedac8fc96de9c07cf673ff19ecd9fbd87da9fb3a1a6f3fbd |
| multi_v080_o050 | 08ff366698486ce2c6e532fff9d33ef0f7e047981160b05fa93a006e1e462622 |
| lr_t030 | 94d8e9868edc57f5102e9707af1146c51573d88e12190346e5e84f117bf989b0 |

Selección sellada UTC: 2026-10-06T02:52:31.910820+00:00. La prioridad de promoción fijada fue threshold → multi-signal → LR entre soluciones que pasen todos los gates; nunca “mayor AUROC”. Ninguno era elegible en CAL. Los resultados del holdout se documentan sin ajustes en 04_holdout_final.md.
