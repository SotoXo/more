# Error analysis del holdout RAG V2

**Resultado inmutable: NO_PROMOTION.** Este documento lee las predicciones ya producidas; no vuelve a puntuar ni ejecuta consultas. No se modifica corpus, etiquetas, routing, features, parámetros o modelos tras ver errores. Las causas son inferencias del análisis, no nuevos labels ni hechos causales demostrados.

26 falsos positivos +4 falsos negativos =30 instancias entre candidatos (algunas consultas se repiten entre métodos). Threshold:19FP/0FN; multi-signal:6FP/2FN; LR:1FP/2FN. Hay cuatro consultas FN únicas, por lo que no existe un quinto falso negativo que reportar.

FP: NOT_RAG real aceptado como RAG. FN: RAG real rechazado. Routing de tres clases se analiza aparte: LR rechaza12/12 tools como RAG pero8/12 quedan mal en INSUFFICIENT_EVIDENCE; esos errores no son FP binarios. El control camisa/blusa también falla por routing fuera del holdout.

## Cinco FP prioritarios

Selección cualitativa posterior para revisión, sin utilizarla para calibrar. Los códigos y estados son fixtures sintéticos del benchmark.

| ID | Consulta | Candidatos con FP | Motivo |
| --- | --- | --- | --- |
| v2q109 | ¿Ya ingresó la reposición de blusas BLU-85 anunciada para hoy? | threshold_t075, multi_v080_o050 | Datos comerciales dinámicos sin tool authority |
| v2q111 | ¿El descuento anunciado para el blazer BL-62 ya está aplicado en caja? | threshold_t075, multi_v080_o050 | Datos comerciales dinámicos sin tool authority |
| v2q117 | Después de mi depósito TX-84, ¿qué saldo queda pendiente en la venta VE-95? | threshold_t075 | Datos comerciales dinámicos sin tool authority |
| v2q119 | ¿El conteo físico de cinco faldas FA-83 coincide con el saldo registrado? | threshold_t075, multi_v080_o050 | Datos comerciales dinámicos sin tool authority |
| v2q178 | ¿Hasta qué temperatura bajo cero protege exactamente el abrigo AB-99? | threshold_t075, multi_v080_o050, lr_t030 | Hecho térmico sin evidencia |

## Todos los FN observados (cuatro)

| ID | Método | Consulta | Score | Fuente esperada en Top3 |
| --- | --- | --- | --- | --- |
| v2q059 | multi_v080_o050 | ¿Qué puede contaminar una paleta de píxeles extraída de la foto de una prenda? | 0.000000000 | color:observed-vs-commercial |
| v2q060 | multi_v080_o050 | Before applying a pixel palette to commercial color, what target evidence is missing without a verified garment identity? | 0.000000000 | color:observed-vs-commercial |
| v2q057 | lr_t030 | If an external label groups shirt and blouse together, should Mati collapse its two categories? | 0.152343506 | comparison:camisa-blusa |
| v2q058 | lr_t030 | When shirt and blouse cues overlap, what evidence and review does the comparison call for? | 0.161124697 | comparison:camisa-blusa |

## Detalle de todas las instancias FP/FN

Los documentos se identifican por document_key semántica y chunk_key; sus IDs numéricos de PostgreSQL no son ground truth. Top3 conserva orden y cosenos reales. Lexical=[] significa sin hits FTS, lexical_top1=0 y agreement=0; no significa corpus vacío.

### 1. threshold_t075 / v2q109 / FP

Consulta: ¿Ya ingresó la reposición de blusas BLU-85 anunciada para hoy?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.811661206; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Estado actual de reposición e inventario.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | category:4 | category:4#definition | 0.811661206 |
| 2 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.801993978 |
| 3 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.785727122 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8116612062858097, "margin_1_2": 0.009667228381129656, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 2. threshold_t075 / v2q110 / FP

Consulta: Has the restock shipment for polo PL-48 been received yet?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.754387424; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Llegada real de reposición, no categoría.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:polera-knitwear | comparison:polera-knitwear#comparison | 0.754387424 |
| 2 | technical:retrieval-only | technical:retrieval-only#explanation | 0.748512968 |
| 3 | category:8 | category:8#definition | 0.748299226 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7543874236576227, "margin_1_2": 0.005874455325272976, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 3. threshold_t075 / v2q111 / FP

Consulta: ¿El descuento anunciado para el blazer BL-62 ya está aplicado en caja?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.805238485; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Estado efectivo de descuento comercial.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.805238485 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.799716616 |
| 3 | category:2 | category:2#definition | 0.793489481 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8052384853363037, "margin_1_2": 0.0055218696256360555, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 4. threshold_t075 / v2q113 / FP

Consulta: ¿Repusieron la talla 40 del pantalón PT-81 después de agotarse?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.810466170; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Reposición comercial de talla y referencia identificadas.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:lower-garments | comparison:lower-garments#comparison | 0.810466170 |
| 2 | category:7 | category:7#definition | 0.797797394 |
| 3 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.786057042 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8104661703109741, "margin_1_2": 0.01266877638085906, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 5. threshold_t075 / v2q114 / FP

Consulta: ¿Volvió a entrar la camisa CM-95 en talla S esta semana?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.795857131; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Estado actual de variante de talla explícita.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.795857131 |
| 2 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.790043425 |
| 3 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.788184164 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7958571308638354, "margin_1_2": 0.005813706194021884, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 6. threshold_t075 / v2q117 / FP

Consulta: Después de mi depósito TX-84, ¿qué saldo queda pendiente en la venta VE-95?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.789589345; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Saldo actual exige registros de pagos y venta.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | technical:rag-vs-tools | technical:rag-vs-tools#explanation | 0.789589345 |
| 2 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.780942652 |
| 3 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.779497120 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7895893454551753, "margin_1_2": 0.008646693117308035, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0.1}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 7. threshold_t075 / v2q118 / FP

Consulta: Does sale VE-73 still show a balance after yesterday’s transfer?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.786626577; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Saldo y aplicación de transferencia comerciales.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | technical:similarity | technical:similarity#explanation | 0.786626577 |
| 2 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.770382152 |
| 3 | technical:retrieval-only | technical:retrieval-only#explanation | 0.768981860 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7866265773773193, "margin_1_2": 0.01624442515479474, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 8. threshold_t075 / v2q119 / FP

Consulta: ¿El conteo físico de cinco faldas FA-83 coincide con el saldo registrado?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=0.810922790; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Conciliación con inventario/Kardex actual; sólo conceptual.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.810922790 |
| 2 | technical:similarity | technical:similarity#explanation | 0.809419036 |
| 3 | category:6 | category:6#definition | 0.807923913 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8109227902247199, "margin_1_2": 0.0015037543131598552, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.1111111111111111}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 9. threshold_t075 / v2q169 / FP

Consulta: ¿La blusa BLU-96 tiene certificado OEKO-TEX vigente?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.821139883; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no certificación del artículo.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.821139883 |
| 2 | category:4 | category:4#definition | 0.814861916 |
| 3 | color:observed-vs-commercial | color:observed-vs-commercial#knowledge | 0.794319510 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8211398827350224, "margin_1_2": 0.006277967010973273, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 10. threshold_t075 / v2q170 / FP

Consulta: ¿Cuál es el número de certificado de comercio justo del jersey KN-87?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.793739927; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no número ni acreditación por pieza.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.793739927 |
| 2 | comparison:polera-knitwear | comparison:polera-knitwear#comparison | 0.791782332 |
| 3 | category:8 | category:8#definition | 0.780193521 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7937399271881751, "margin_1_2": 0.00195759523167649, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.14285714285714285}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 11. threshold_t075 / v2q171 / FP

Consulta: Has dress VS-98 been independently certified as hypoallergenic?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.753887787; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no prueba de certificación concreta.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.753887787 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.750524864 |
| 3 | category:5 | category:5#definition | 0.747964457 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.753887787229552, "margin_1_2": 0.0033629237226726616, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.25}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 12. threshold_t075 / v2q172 / FP

Consulta: ¿Cómo se interpreta un resultado de hemoglobina baja?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.809289575; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Fuera de dominio; no datos clínicos en corpus.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.809289575 |
| 2 | color:observed-vs-commercial | color:observed-vs-commercial#knowledge | 0.808577657 |
| 3 | technical:similarity | technical:similarity#explanation | 0.808357608 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8092895746231137, "margin_1_2": 0.0007119178771972656, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): GATE_FAILURE / IRRELEVANT_RETRIEVAL: documentos técnicamente próximos no sustentan esta consulta fuera de dominio; similitud≥corte no certifica evidencia.

### 13. threshold_t075 / v2q173 / FP

Consulta: ¿Qué tratamiento corresponde a una lesión de rodilla?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.783712102; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Fuera de dominio; no medicina.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | technical:retrieval-only | technical:retrieval-only#explanation | 0.783712102 |
| 2 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.771950272 |
| 3 | comparison:lower-garments | comparison:lower-garments#comparison | 0.766312687 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7837121019535129, "margin_1_2": 0.011761829864296969, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): GATE_FAILURE / IRRELEVANT_RETRIEVAL: documentos técnicamente próximos no sustentan esta consulta fuera de dominio; similitud≥corte no certifica evidencia.

### 14. threshold_t075 / v2q174 / FP

Consulta: Which antibiotic should be used for a throat infection?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.758324623; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Fuera de dominio; no recomendaciones terapéuticas.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.758324623 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.746558816 |
| 3 | technical:retrieval-only | technical:retrieval-only#explanation | 0.744211301 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7583246231079102, "margin_1_2": 0.011765807456823763, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): GATE_FAILURE / IRRELEVANT_RETRIEVAL: documentos técnicamente próximos no sustentan esta consulta fuera de dominio; similitud≥corte no certifica evidencia.

### 15. threshold_t075 / v2q176 / FP

Consulta: ¿Van juntos?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.814501166; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: No se identifican los elementos de la relación.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.814501166 |
| 2 | category:10 | category:10#definition | 0.804314113 |
| 3 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.791451931 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8145011663436947, "margin_1_2": 0.010187053808993096, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): GATE_FAILURE / referencia incompleta: se acepta evidencia cercana para una pregunta ambigua sin referente. Política fija exige abstención; AMBIGUOUS_LABEL no se usa para relabelar el error.

### 16. threshold_t075 / v2q177 / FP

Consulta: ¿Hace juego con aquello?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.814675737; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Faltan ambos referentes verificables.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.814675737 |
| 2 | technical:rag-vs-tools | technical:rag-vs-tools#explanation | 0.799888563 |
| 3 | technical:similarity | technical:similarity#explanation | 0.790828288 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8146757373020507, "margin_1_2": 0.014787174139279213, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): GATE_FAILURE / referencia incompleta: se acepta evidencia cercana para una pregunta ambigua sin referente. Política fija exige abstención; AMBIGUOUS_LABEL no se usa para relabelar el error.

### 17. threshold_t075 / v2q178 / FP

Consulta: ¿Hasta qué temperatura bajo cero protege exactamente el abrigo AB-99?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.844541023; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no aislamiento cuantificado individual; categoría no lo garantiza.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:cold-weather | comparison:cold-weather#comparison | 0.844541023 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.817688368 |
| 3 | category:1 | category:1#definition | 0.811049044 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8445410225068929, "margin_1_2": 0.026852654864528258, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 18. threshold_t075 / v2q179 / FP

Consulta: ¿Cuántos grados de aislamiento térmico proporciona el suéter KN-94?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.798153591; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no medición térmica individual.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:cold-weather | comparison:cold-weather#comparison | 0.798153591 |
| 2 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.786330687 |
| 3 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.779228118 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7981535912660618, "margin_1_2": 0.011822904081089547, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.25}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 19. threshold_t075 / v2q180 / FP

Consulta: Can coat AB-97 guarantee safe warmth at minus thirty degrees Celsius?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.799112856; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no garantía térmica específica del artículo.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:cold-weather | comparison:cold-weather#comparison | 0.799112856 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.780861450 |
| 3 | technical:similarity | technical:similarity#explanation | 0.771445797 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.7991128563880977, "margin_1_2": 0.018251406005700632, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 20. multi_v080_o050 / v2q059 / FN

Consulta: ¿Qué puede contaminar una paleta de píxeles extraída de la foto de una prenda?

GT: RAG_ANSWERABLE; decisión: INSUFFICIENT_EVIDENCE; score=0.000000000; raw_accepted=False; operational_accepted=False.

Justificación GT congelada: Enumera fondo, piel, iluminación y accesorios.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | color:observed-vs-commercial | color:observed-vs-commercial#knowledge | 0.891307473 |
| 2 | comparison:cold-weather | comparison:cold-weather#comparison | 0.811127208 |
| 3 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.808655858 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8913074731826846, "margin_1_2": 0.08018026484576002, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0.42857142857142855}

Expected fuentes: color:observed-vs-commercial; supporting_document_hit=True.

Causa probable (inferencia): GATE_FAILURE: fuente esperada en Top3 y coseno≥0.80, pero sin alias completo ni hits FTS. FTS usa conjunción de términos y pierde evidencia de paráfrasis; la rama lexical falla (overlap también insuficiente). No es fallo de retrieval.

### 21. multi_v080_o050 / v2q060 / FN

Consulta: Before applying a pixel palette to commercial color, what target evidence is missing without a verified garment identity?

GT: RAG_ANSWERABLE; decisión: INSUFFICIENT_EVIDENCE; score=0.000000000; raw_accepted=False; operational_accepted=False.

Justificación GT congelada: Se requiere atribuir píxeles a prenda objetivo y validar catálogo; sin identidad mantener color manual.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | color:observed-vs-commercial | color:observed-vs-commercial#knowledge | 0.844274885 |
| 2 | color:family-vs-variant | color:family-vs-variant#knowledge | 0.774921652 |
| 3 | comparison:polera-knitwear | comparison:polera-knitwear#comparison | 0.758738929 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8442748850993644, "margin_1_2": 0.06935323318142195, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 0, "normalized_keyword_overlap": 0.07692307692307693}

Expected fuentes: color:observed-vs-commercial; supporting_document_hit=True.

Causa probable (inferencia): GATE_FAILURE: fuente esperada en Top3 y coseno≥0.80, pero sin alias completo ni hits FTS. FTS usa conjunción de términos y pierde evidencia de paráfrasis; la rama lexical falla (overlap también insuficiente). No es fallo de retrieval.

### 22. multi_v080_o050 / v2q109 / FP

Consulta: ¿Ya ingresó la reposición de blusas BLU-85 anunciada para hoy?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=1.000000000; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Estado actual de reposición e inventario.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | category:4 | category:4#definition | 0.811661206 |
| 2 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.801993978 |
| 3 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.785727122 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8116612062858097, "margin_1_2": 0.009667228381129656, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 23. multi_v080_o050 / v2q111 / FP

Consulta: ¿El descuento anunciado para el blazer BL-62 ya está aplicado en caja?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=1.000000000; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Estado efectivo de descuento comercial.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.805238485 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.799716616 |
| 3 | category:2 | category:2#definition | 0.793489481 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8052384853363037, "margin_1_2": 0.0055218696256360555, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 24. multi_v080_o050 / v2q113 / FP

Consulta: ¿Repusieron la talla 40 del pantalón PT-81 después de agotarse?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=1.000000000; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Reposición comercial de talla y referencia identificadas.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:lower-garments | comparison:lower-garments#comparison | 0.810466170 |
| 2 | category:7 | category:7#definition | 0.797797394 |
| 3 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.786057042 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8104661703109741, "margin_1_2": 0.01266877638085906, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 25. multi_v080_o050 / v2q119 / FP

Consulta: ¿El conteo físico de cinco faldas FA-83 coincide con el saldo registrado?

GT: TOOL_REQUIRED; decisión: RAG_ANSWERABLE; score=1.000000000; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: Conciliación con inventario/Kardex actual; sólo conceptual.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:vestido-conjunto | comparison:vestido-conjunto#comparison | 0.810922790 |
| 2 | technical:similarity | technical:similarity#explanation | 0.809419036 |
| 3 | category:6 | category:6#definition | 0.807923913 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8109227902247199, "margin_1_2": 0.0015037543131598552, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.1111111111111111}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): ROUTING_ERROR + FALSE_EVIDENCE_ACCEPTANCE: el router no reconoce el estado dinámico de reposición/descuento/saldo/conteo de esta formulación; el gate acepta similitud taxonómica sin autoridad comercial. No se ejecuta tool.

### 26. multi_v080_o050 / v2q169 / FP

Consulta: ¿La blusa BLU-96 tiene certificado OEKO-TEX vigente?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=1.000000000; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no certificación del artículo.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.821139883 |
| 2 | category:4 | category:4#definition | 0.814861916 |
| 3 | color:observed-vs-commercial | color:observed-vs-commercial#knowledge | 0.794319510 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8211398827350224, "margin_1_2": 0.006277967010973273, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 27. multi_v080_o050 / v2q178 / FP

Consulta: ¿Hasta qué temperatura bajo cero protege exactamente el abrigo AB-99?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=1.000000000; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no aislamiento cuantificado individual; categoría no lo garantiza.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:cold-weather | comparison:cold-weather#comparison | 0.844541023 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.817688368 |
| 3 | category:1 | category:1#definition | 0.811049044 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8445410225068929, "margin_1_2": 0.026852654864528258, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

### 28. lr_t030 / v2q057 / FN

Consulta: If an external label groups shirt and blouse together, should Mati collapse its two categories?

GT: RAG_ANSWERABLE; decisión: INSUFFICIENT_EVIDENCE; score=0.152343506; raw_accepted=False; operational_accepted=False.

Justificación GT congelada: El texto explica agrupación externa y categorías Mati separadas.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.818008006 |
| 2 | category:11 | category:11#definition | 0.815672029 |
| 3 | category:8 | category:8#definition | 0.801284242 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8180080056190491, "margin_1_2": 0.0023359766201941357, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.2727272727272727}

Expected fuentes: comparison:camisa-blusa; supporting_document_hit=True.

Causa probable (inferencia): GATE_FAILURE: comparison:camisa-blusa sí aparece en Top3, pero el score LR no alcanza0.30; coseno/margen bajos y señales lexical nulas en paráfrasis inglesas. No es fallo de retrieval ni label cambiado.

### 29. lr_t030 / v2q058 / FN

Consulta: When shirt and blouse cues overlap, what evidence and review does the comparison call for?

GT: RAG_ANSWERABLE; decisión: INSUFFICIENT_EVIDENCE; score=0.161124697; raw_accepted=False; operational_accepted=False.

Justificación GT congelada: Ante solapamiento requiere ficha verificada y revisión humana.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:camisa-blusa | comparison:camisa-blusa#comparison | 0.801815462 |
| 2 | comparison:lower-garments | comparison:lower-garments#comparison | 0.790342640 |
| 3 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.788297451 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.801815462274745, "margin_1_2": 0.011472822261407112, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.375}

Expected fuentes: comparison:camisa-blusa; supporting_document_hit=True.

Causa probable (inferencia): GATE_FAILURE: comparison:camisa-blusa sí aparece en Top3, pero el score LR no alcanza0.30; coseno/margen bajos y señales lexical nulas en paráfrasis inglesas. No es fallo de retrieval ni label cambiado.

### 30. lr_t030 / v2q178 / FP

Consulta: ¿Hasta qué temperatura bajo cero protege exactamente el abrigo AB-99?

GT: INSUFFICIENT_EVIDENCE; decisión: RAG_ANSWERABLE; score=0.478818899; raw_accepted=True; operational_accepted=True.

Justificación GT congelada: CORPUS_GAP: no aislamiento cuantificado individual; categoría no lo garantiza.

Router: {"route": "candidate_rag", "reason": null, "required_tool": null}

| Rank | Documento | Chunk | Coseno |
| --- | --- | --- | --- |
| 1 | comparison:cold-weather | comparison:cold-weather#comparison | 0.844541023 |
| 2 | comparison:blazer-abrigo | comparison:blazer-abrigo#comparison | 0.817688368 |
| 3 | category:1 | category:1#definition | 0.811049044 |

Hits lexical: []; lexical_top1=0.

Features: {"vector_top1": 0.8445410225068929, "margin_1_2": 0.026852654864528258, "lexical_top1": 0, "vector_lexical_agreement": 0, "exact_taxonomy_name_match": 1, "normalized_keyword_overlap": 0.125}

Expected fuentes: ninguna; negativo; supporting_document_hit=False.

Causa probable (inferencia): CORPUS_GAP para el hecho concreto + GATE_FAILURE: definiciones cercanas no contienen certificado/garantía térmica específica. La falta de esa evidencia exige abstención; no se agrega corpus ni se cambia GT.

## Hallazgos fuera del error binario

CAMISA/BLUSA: recuperación all3 PASS, router falso TOOL por “hay”, positivo end-to-end FAIL en las tres políticas. No se cambia esa regla porque la solución ya estaba congelada.

Las tres strings de injection se tratan como datos. Threshold acepta las3, multi rechaza las3; LR acepta “ignora tus instrucciones” y rechaza “ejecuta DROP TABLE” y “revela el token”. Son false evidence acceptance en el prototipo, nunca ejecución SQL, comandos o revelación de secretos. La ruta pública bloqueada entrega cero chunks. Esta preparación no demuestra seguridad de un LLM aún inexistente.

No hay FN binario de retrieval en el holdout: las cuatro consultas rechazadas tienen su fuente esperada en Top3. No se detecta evidencia que justifique corregir etiquetas ambiguas después del resultado. Los CORPUS_GAP describen límites concretos del corpus general, sin convertir preguntas negativas en ampliación para pasar el test.

Errores adicionales de routing: revisar la matriz3clases del documento04, no sumar esas instancias al conteo30 de FP/FN binarios. El corpus sigue23/23/23. Ningún candidato satisface todas las condiciones, por lo que no se instala gate-model.json ni se habilita el endpoint.

## Evidencia privada y límite de generalización

Fuente: storage/app/ai-rag-gate-v2/holdout-errors.json; SHA-256=99f5dd2c3ea909c0a14b6513987b6ef9ab9fa3af184ee8a71c99f85706185825. Cada registro conserva query/GT/decisión, features, top3 vectoriales/léxicos, predicción y causa inferida. Post_result_tuning_performed=false.

El dataset fue curado por Codex sobre un corpus propuesto pendiente de revisión de dominio Mati. N=36 en holdout, N=3 para algunos subgrupos, y inglés3positivos: no permiten asegurar generalización universal. Un siguiente experimento V3 debe declarar protocolo y prueba nueva; estos errores V2 ya son conocimiento de desarrollo.
