# Arquitectura de abstención RAG V2

**Estado final: arquitectura candidata implementada, gate V2 NOT APPROVED, winner NONE / NO_PROMOTION.** La evaluación independiente de 04_holdout_final.md no permite activar entrega de contexto. E5 y retrieval conservan su estado previo; no queda aprobada la fundación RAG completa.

Se conserva E5-small, revisión `614241f622f53c4eeff9890bdc4f31cfecc418b3`, dimensión 384, CPU FP32, normalización L2, cuatro threads y los prefijos y preprocess existentes. No cambia el corpus de 23 documentos, sus 23 chunks ni sus embeddings. El perfil y la evaluación V1 permanecen históricos e inmutables.

Laravel valida la consulta y recupera evidencia mediante SQL parametrizado sobre PostgreSQL/pgvector exacto y búsqueda léxica PostgreSQL. El componente de features calcula señales escalares sólo a partir de query y evidencia recuperada: no usa etiquetas, expected IDs, familia real ni split. El script offline recibe metadata de control y reporte TRAIN/CAL, incluida split; no la usa para calcular features, gates o routing. HOLD se exporta sólo como id/query/split controlado, sin GT. No hay índice ANN ni motor externo. Las consultas se tratan como texto de datos; no se interpolan como identificadores, SQL, comandos o acciones.

El routing conceptual y el gate binario se evalúan por separado. Todos los candidatos comparten las mismas salvaguardas de routing, fijadas antes de medir. El contrato diseñado exige que las consultas comerciales dinámicas con un referente suficiente requieran herramientas futuras y reciban cero chunks. El prototipo no cumple el routing completo en holdout: reconoce4/12 tools y falla el positivo camisa/blusa; por eso queda sin promoción. Las referencias incompletas se abstienen. Las preguntas que explican conceptos de RAG/Tools no ejecutan herramientas.

El gate es un artefacto JSON pequeño e inmutable. Una eventual regresión logística aplica únicamente el orden de features, escalado, coeficientes e intercepto congelados offline; no aprende por request. La inferencia valida dimensiones, tipos, valores finitos, versiones e identidad del contexto. El estado de promoción y sus hashes se registran por separado; no se altera el flag histórico de aprobación V1.

La ruta autenticada existente conserva el acceso de superadmin y los límites de payload. Sólo entrega chunks si el artefacto y la aprobación V2 coinciden con el corpus, el perfil y la implementación evaluados. Un gate ausente, inválido o no aprobado falla cerrado con un motivo controlado y evidencia vacía. Los filtros que cambian el corpus evaluado también fallan cerrado hasta disponer de validación específica.

El resultado expone estado, route, decisión y answerability_score, policy_version, evidencia de las señales y procedencia de los chunks cuando procede. No expone embeddings completos ni genera una respuesta. El score no se presenta como certainty.

SigLIP, prompts y vectores visuales, colorimetría, OCR, compatibilidad y todos los datos comerciales quedan preservados. Classification=FULL, visual embedding=FULL y automatic commercial color=NONE; AUD-07 permanece mitigado. No hay migración, reindexación, escritura de productos ni cambios a ventas, reservas, pagos, inventario o Kardex.

Los detalles de features y las políticas congeladas están en 02_features_y_politicas.md; la comparación en 03_benchmark_modelos.md; los resultados definitivos y límites en 04_holdout_final.md y 05_error_analysis.md. Los artefactos privados, sin secretos, están en `storage/app/ai-rag-gate-v2/`, excluidos de Git.

## Cierre verificado

Se versiona sólo la decisión explícita gate-decision.json approved=false, con contexto y SHA del resultado privado; no se despliega ningún gate-model.json candidato. La ruta actual bloquea todas las consultas válidas, también las dinámicas, antes de inferencia: HTTP200, status=insufficient_evidence, route=required_information, reason=RAG_GATE_NOT_APPROVED, chunks=[]. El contrato futuro requires_tool aún no está habilitado públicamente.

Kernel Laravel real11/11 PASS (usuario y sesión sólo en memoria, sin mocks de evidence/model; no login de navegador):401 sin auth/bearer malo;422 inválido/vacío/largo;200 para las6 consultas válidas, todas bloqueadas; ningún500. Los tests sintéticos de promoción sólo prueban contratos, no autorizan activar el gate.

Checks finales readonly:34 fingerprints comerciales,134 vectores visuales, imágenes y AIcounts idénticos; RAG1/23/23/23 y fingerprints idénticos. Mismo runtime Docker y perfil textual, sin cambios a cache ni configuración. FULL/FULL/NONE, AUD-07 mitigado y P2 de segmentación preservados. No reindexación ni mutaciones comerciales.

Pruebas de desarrollo:90PHP/236assertions y66Python PASS;11goldens PHP↔Python delta0; LR JSON↔sklearn delta2.22e-16; SQL↔NumPy5/5 Top3. No fallos500 en los HTTP medidos. Artefactos privados600/directorio700, sin secretos. Branch/HEAD originales, sin staging/commit/push.
