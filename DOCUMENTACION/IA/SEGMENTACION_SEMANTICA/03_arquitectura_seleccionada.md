# Arquitectura seleccionada: NONE

La decisión final es NONE: ningún pipeline de segmentación se promueve a producción. Las tres alternativas se evaluaron con el protocolo fijado antes de inferir y ninguna supera el gate cuantitativo de clasificación, color o retrieval. Se conservan clasificación FULL, embeddings FULL y bloqueo de IDs de color comercial mientras la identidad de la prenda objetivo siga sin verificar.

Esta fase deja un benchmark y una propuesta de contrato. No integra segmentación en FastAPI/Laravel/UI, no crea migraciones ni endpoints, no registra estos candidatos en ai_model_versions de la base de datos y no reindexa los 134 embeddings canónicos.

## Decisión por consumidor

| Consumidor | Estrategia al cierre | Motivo |
|---|---|---|
| Clasificación | FULL, imagen original | Ninguna alternativa alcanza mejora ALL130 Top1 ≥5 puntos porcentuales y macro F1 ≥0,02 con los límites de regresión fijados. |
| Embeddings y búsqueda | FULL canónico | Los perfiles derivados no superan mejora ALL130 nDCG@10 ≥0,05; los 134 vectores existentes permanecen fuera del experimento. |
| Color comercial | NONE para aplicación automática de IDs | No hay mejora proxy exacta ALL130 ≥10 puntos porcentuales ni verificación independiente de identidad del target; se conserva el guard de AUD-07. |
| Paleta y color descriptivo | Comportamiento actual, evidencia informativa | La extracción experimental por máscara no reemplaza la función productiva ni autoriza una selección comercial. |
| Segmentación | NONE en producción | Modelos y artefactos locales se conservan como evidencia, no como capacidades activas del servicio. |

| Pipeline experimental | Selección AUTO | Estado AUTO | Resultado |
|---|---:|---|---|
| DINO Tiny + SAM2.1 Hiera Tiny | 0/130 | 86 ambiguous; 44 insufficient_evidence | No promoción |
| DINO Base + SAM2.1 Hiera Tiny | 6/130 | 90 ambiguous; 34 insufficient_evidence; 6 selected | No promoción |
| DINO Tiny + SAM ViT-B | 0/130 | 86 ambiguous; 44 insufficient_evidence | No promoción |

Base cubre sólo 4,62% del conjunto. En ALL130, crop obtiene Top1 5/130 y masked 4/130; nDCG@10 es 0,01335 y 0,01408. FULL conserva Top1 83/130, macro F1 0,66492 y nDCG@10 0,40206. Las ausencias son abstenciones/no_prediction y permanecen en el denominador 130. El detalle corresponde a [02_benchmark_candidatos.md](02_benchmark_candidatos.md) y [04_resultados_n130.md](04_resultados_n130.md).

La revisión visual comprende 20 imágenes estratificadas y 60 paneles entre tres pipelines. El caso 13 ASSISTED_CODEX_VISUAL es un diagnóstico N1 separado de AUTO. Una máscara visualmente útil en ese caso no cambia la decisión.

## Identidades y responsabilidades

La referencia es feature/ia-segmentacion-semantica, commit e422ef4c0a279172ca03818a4b16ffb501d493bd, correspondiente al commit del tag ia-p1-stable-v1. El manifest contiene 134 registros; la regla original deduplica SHA coherentes y excluye el grupo con categorías contradictorias para obtener N130.

| Identidad congelada | Valor |
|---|---|
| Manifest SHA256 | f24d4901949a70769df86f8b85fce7ee85d5ed5e0b90c1bb57e8b77c07c61c5e |
| Protocolo SHA256 | 5cdd1eba3ab99d862d039a70a93769f821d7c4ba8c89f8a6508025b6ce6ba0f4 |
| SigLIP | google/siglip-base-patch16-224 |
| Revisión | 7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed |
| Pipeline/vector | registration-1.2.0 / 768 dimensiones L2 |
| Perfil FULL | projected-image-l2-1 / srgb-exif-transformers-image-1 |
| Clasificación | Categorías/prompts originales; score ≥0,08 y margen ≥0,005 |

[Protocol.json](../../../services/mati-ai/scripts/segmentation/protocol.json), [execution-freeze.json](../../../storage/app/ai-segmentation/execution-freeze.json) y [model-lock.json](../../../storage/app/ai-segmentation/model-lock.json) distinguen configuración previa de resultados posteriores. Los prompts globales de DINO, thresholds 0,25/0,20, whitelist y política no se ajustaron tras observar N130. Las etiquetas comerciales sólo se usan al calcular métricas.

Laravel conserva autoridad sobre autenticación, actor, catálogo, persistencia, revisión humana, permisos y aplicación de atributos. FastAPI realiza inferencia sobre imágenes y devuelve un envelope; no consulta PostgreSQL ni guarda productos. El experimento no convierte una clase externa en categoría comercial. Score DINO/SAM o geometría válida no demuestran SKU objetivo ni son probabilidad calibrada.

## Contrato propuesto, condicionado a evidencia futura

El diseño siguiente no está conectado a requests productivos. [Policy.py](../../../services/mati-ai/scripts/segmentation/policy.py) implementa contrato de benchmark target-garment-selection-1 y política target-selection-benchmark-1. Su existencia y pruebas unitarias no acreditan integración E2E.

| Objeto propuesto | Contenido y validación |
|---|---|
| target_garment | status, selection_mode, candidate_count, eligible_candidate_count, selected_candidate_id, selection_policy_version, selection_validated y warnings. |
| Imagen | SHA256 fuente, dimensiones, EXIF aplicado, espacio de coordenadas y generación vigente. |
| Run | request/run UUID, operación, actor autenticado, fecha/expiry y vínculo inmutable al run fuente. |
| Segmentador | model_id, revisión, digest del checkpoint, pipeline/preprocess y política. |
| Candidato | ID único, clase externa, role, bbox finita, áreas, evidencia de máscara, alertas de bordes/clipping y score no calibrado. |
| Artefactos | IDs opacos; digests separados de máscara binaria, PNG, crop y masked crop; sin path enviado por navegador. |
| Verificación | Método/evidencia de geometría y selección; selección válida no se redefine como accuracy comercial. |

AUTO requiere una candidata válida/elegible con máscara coherente y sin otra candidata plausible inválida. Dos o más candidatas plausibles conservan ambiguous; cero válidas, insufficient_evidence. No se elige por área, centralidad ni máximo score.

Bbox debe estar dentro de la imagen validada. Bbox/mask ratio queda entre 0,005 y 0,90; máscara binaria no vacía con shape correcta. Contacto con dos o más bordes o clipping mayor de 0,03 impide AUTO. Más de 12 candidatas o truncación produce ambiguous. IDs duplicados y contratos malformados se rechazan.

Deduplicación exige misma clase externa canónica, mismo role y bbox IoU ≥0,85. Ese IoU sólo compara cajas para deduplicar aliases. Camisa y blazer superpuestos permanecen diferentes; otra candidata inválida plausible bloquea AUTO incluso si queda una válida.

## Selección humana, owner y cambio de foto

Una futura selección asistida recibiría run_id, candidate_id e identidad/generación. Laravel obtendría owner desde sesión autenticada, cargaría candidatas desde snapshot servidor y comprobaría operación, TTL, política, SHA actual y pertenencia exacta del candidato. El cliente no suministraría owner, path, máscara, geometría ni flag verified autorizado.

El contexto fuente sería inmutable. Current_context debe derivar de bytes/generación vigentes en servidor: repetir desde navegador un SHA anterior no valida una foto nueva. Se rechazan owner distinto, run ajeno, candidato manipulado/inexistente/duplicado, política distinta, generación anterior y expiración.

Los artefactos usarían almacenamiento privado y preview autenticado owner+run con TTL explícito. Se propone 15 minutos, pendiente de implementación; no es una purga automática existente de la auditoría. No se exponen rutas públicas ni matrices completas en formulario.

ai_runs ya dispone de request UUID, created_by, input_hash, input_metadata/result JSON y expires_at; ai_suggestions.evidence permite trazabilidad. Una evolución podría conservar detección fuente y selección como registros vinculados sin sobrescribir el inicial, previa validación del contrato. Esta fase no crea tablas ni nuevos model_version DB.

Una futura UI debe limpiar target, selección, preview, crop y resultados dependientes al cambiar foto; abortar requests previos y descartar respuestas tardías antes de incorporar resultados. Debe validar generación junto a identidad/run en servidor. El flujo actual limpia parte del estado y agrega algunos resultados antes de comprobar identidad; no acredita seguridad del selector propuesto.

## Perfiles y color

| Perfil | Entrada | Preprocess |
|---|---|---|
| FULL | RGB original validado | srgb-exif-transformers-image-1 |
| CROP | Crop de bbox ajustada a máscara binaria | target-box-srgb-siglip-1 |
| MASKED | Crop con RGB 127,127,127 fuera de máscara | target-mask-gray127-srgb-siglip-1 |

Modelo/revisión/dimensión idénticos no vuelven compatibles los perfiles. Un índice futuro derivado necesitaría contrato propio y galería coherente. Digest de máscara individual pertenece a provenance de la fila; no fragmenta la clave geométrica común entre imágenes. Los NPZ experimentales permanecen privados fuera de pgvector.

La matemática productiva CIELAB/CIEDE2000, clustering y thresholds se conserva. El benchmark usa píxeles RGB originales dentro de máscara y contexto original de iluminación; fondo gris sólo para embedding masked. La compatibilidad foreground experimental fija 0,70 y no representa precisión SAM. target_identity_verified permanece falso; IDs comerciales aplicables siguen nulos y AUD-07 sigue MITIGADO.

## Validación y preservación

Las [84 pruebas del benchmark](../../../storage/app/ai-segmentation/policy-metrics-tests.txt) cubren contratos, identidad/owner/generación, abstención, geometría/máscaras y lector/métricas. El lector se corrigió tras una referencia None real en diagnóstico: ASSISTED quedó separado y referencias AUTO se validaron. La matriz conserva predicciones sin soporte GT y el lector rechaza selección humana presentada como AUTO. No se alteraron modelos, prompts, thresholds ni gate.

No hubo E2E de segmentación productiva: ningún consumidor se promovió ni se creó tal flujo. UI/OCR/barcode P1 pertenece a fase anterior y no prueba target semántico, preview privado ni TTL del diseño.

El guard SELECT-only [catalog-final.json](../../../storage/app/ai-segmentation/catalog-final.json), corte 2026-10-05 13:49:31 UTC / 09:49:31 America/La_Paz, confirma 34 huellas comerciales, 134 SHA originales y 134 identidades/valores canónicos iguales; dimensiones 768/L2 y 11 FK válidas. Conteos IA permanecen 4 model_versions / 147 runs / 11 suggestions / 2 reviews / 134 embeddings.

[Preservation-final.json](../../../storage/app/ai-segmentation/preservation-final.json), 13:49:36 UTC, confirma 654 archivos tracked sin cambios, 134 originales sin errores, manifest fijo, rama/HEAD/tag iguales y runtime principal healthy con imagen/CMD/env/mounts/ports idénticos. Ese corte precede la escritura final de informes; el cierre raíz recogerá Git documental definitivo.

Los artefactos completos quedan privados en [storage/app/ai-segmentation](../../../storage/app/ai-segmentation), incluidos [architecture-review.md](../../../storage/app/ai-segmentation/architecture-review.md), resultados, JSONL, máscaras/crops, métricas, revisión visual y diagnóstico13. [06_limitaciones.md](06_limitaciones.md) acota su interpretación.
