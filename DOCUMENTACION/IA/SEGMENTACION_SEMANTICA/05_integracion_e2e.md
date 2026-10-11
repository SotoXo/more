# Integración y E2E: sin promoción

Cierre: 2026-10-05. **Candidato elegido: NINGUNO. No se implementó el módulo productivo.** Los tres pipelines fallan los criterios cuantitativos de todos los consumidores, definidos en `services/mati-ai/scripts/segmentation/protocol.json` antes de la inferencia. La regla del encargo §46 exige documentar y detenerse si ninguno cumple; los casos de navegador de §63 son condicionales a una integración.

## Decisión y consumidores

| Consumidor | Estrategia final | Evidencia y consecuencia |
|---|---|---|
| Clasificación SigLIP | `full` | FULL reproduce Top1 83/130, Top3 116/130, Macro-F1 0.664920056 y coverage formal 13/130. Base crop sólo produce rankings para 6/130; no pasa el gate ALL130. |
| Embedding visual | `full` | Los 134 vectores canónicos mantienen identidad, valores y perfiles. Los NPZ crop/masked son privados y experimentales; no se reindexó pgvector. |
| Color comercial | `none` | `target_garment_identity_unverified` continúa bloqueando IDs. No se convirtió score de detector/SAM ni selección visual Codex en autorización comercial. |
| Color técnico | Heurística actual de foto completa | La paleta descriptiva conserva su comportamiento existente. No se cambió CIELAB, CIEDE2000, catálogo ni umbrales. |

`storage/app/ai-segmentation/gate-decision.json` registra NONE por candidato y consumidor; `report_gate.py` lee el protocolo y lock por SHA, conserva denominador130 y trata condiciones sin observaciones como no demostradas. La revisión de 60 paneles es cualitativa; no otorga precisión científica de máscaras ni promoción por una foto.

## Alcance implementado

Se añadieron scripts aislados de investigación, preparación explícita de cache, inferencia secuencial, política experimental, métricas, evidencia visual y diagnóstico separado. No se creó `TargetGarmentSegmenter` productivo, endpoint de segmentación, cambio de `/garments/analyze`, selector UI, migración, tabla, fila `ai_model_versions`, revisión humana en DB ni tarea de indexación.

Las propuestas de contrato, selección humana y TTL de [03_arquitectura_seleccionada.md](03_arquitectura_seleccionada.md) quedan como diseño condicional. `policy.py` y sus pruebas modelan condiciones de seguridad en memoria; no equivalen a autorización Laravel, descarga privada autenticada, persistencia real o una UI desplegada. El diagnóstico13 fue una selección visual de Codex N=1, explícitamente separada de AUTO y sin guardar producto/revisión en DB.

## Casos E2E solicitados

| Caso | Estado de navegador de segmentación | Evidencia disponible | HTTP del módulo nuevo |
|---|---|---|---|
| A. Prenda aislada | N/A: sin integración | N130 y muestra congelada; validación experimental de cero/uno/múltiples candidatos | N/A |
| B. Persona con dos prendas | N/A: sin integración | Paneles de muestra y política `ambiguous`; ninguna selección UI real | N/A |
| C. Persona con tres prendas | N/A: sin integración | Imagen13: los tres AUTO son `ambiguous`; Base permite diagnóstico visual de jersey separado | N/A |
| D. Fondo complejo | N/A: sin integración | Contexto auxiliar, caso16 y revisión visual; no es mask ground truth | N/A |
| E. Sin detección válida | N/A: sin integración | `insufficient_evidence` o ambigüedad conservadora; pruebas cero candidatos/máscara inválida | N/A |
| F. Servicio/segmentador no disponible | N/A: sin integración | Pruebas puras de `model unavailable`/timeout y contrato; no fallo provocado al FastAPI principal | N/A |

No se ejecutó un POST nuevo ni se afirma E2E aprobado, navegación asistida, reevaluación tras click, tiempos HTTP o ausencia de500 de un módulo inexistente. La evidencia E2E P1 previa pertenece al checkpoint anterior y no se reutiliza como validación del segmentador. La reevaluación posterior a integrar ganador (§65) tampoco aplica; sí se completó el experimento previo ALL130 con las tres representaciones cuando existe target AUTO.

## Fallback y seguridad

No se añadió dependencia de segmentación a `analyze`; el formulario y fallback previos conservan su código. El diseño futuro mantiene target sin verificar ante cache ausente, error, timeout, OOM, cero candidato o ambigüedad, conserva clasificación full/formulario manual y bloquea color comercial. No se afirma que ese nuevo diseño esté implementado.

Las pruebas experimentales cubren geometría/máscara, fuera de imagen, área mínima/máxima, cero/uno/múltiples candidatos, clase desconocida, identidad request/run/owner/SHA, caducidad, generación de foto, ID inválido/duplicado, respuesta malformada, otra solicitud, modelo no disponible y timeout. Las10 pruebas del lector de consumidores impiden mezclar selección humana con AUTO, aceptaciones inconsistentes y referencias de artefacto ausentes, absolutas, con traversal o escapes por symlink. Los outputs privados contienen PNG/NPZ/JSON; ningún archivo de máscara/peso se añadió a Git.

## Verificación ejecutada y preservación

- `test_policy.py` + `test_metrics.py` + `test_consumer_boundaries.py`: **84 passed** (66+8+10). `policy-metrics-tests.txt` conserva el resultado; son pruebas unitarias y de contrato experimental.
- Repetición final de la suite en venv existente: **84 passed in 1.52s**; Ruff limitado a F/E9 y parseo AST de los11 scripts, aprobados. La imagen productiva no contiene pytest/ruff: los intentos allí no ejecutaron pruebas y no se instalaron herramientas. Se corrigió la instrucción de reproducción al entorno existente.
- `privacy-final.json`:66 archivos de texto inspeccionados, cero coincidencias con secretos configurados; permisos600/700 del árbol privado experimental. No se cambiaron permisos de originales ni cache.
- Tres benchmarks: **390 filas**, cero errores de inferencia y cero OOM. No es una prueba de concurrencia ni de resiliencia del endpoint productivo.
- Guard SELECT-only: `catalog-final.json` contra `catalog-before.json`; **34 tablas comerciales**, hashes de **134 imágenes**, valores/identidades de **134 embeddings** y conteos IA idénticos. Conteos: modelos4/runs147/suggestions11/reviews2/embeddings134. Las11 FK y dimensiones768/normaL2 permanecen válidas.
- `preservation-final.json`: **654 archivos tracked sin cambios**, manifest SHA congelado, branch/HEAD/tag iguales. CMD, imagen Docker, variables permitidas, mounts y puertos del FastAPI principal idénticos; servicio `healthy`, un worker, sin reinicio.
- Todos los contenedores de inferencia del experimento se ejecutaron secuencialmente, offline, sin puertos y sin DB; se liberaron modelos antes de cargar consumidores. No se guardaron productos ni se modificaron ventas, reservas, pagos, inventario o Kardex.

El fallo inicial del lector experimental intentó abrir una máscara inexistente durante un diagnóstico; se preservó el log, se separó ese diagnóstico de AUTO y se añadieron10 pruebas antes de reejecutar consumidores. El protocolo, prompts, umbrales, pesos y datos no cambiaron. `source-finalization.json` conserva fuente ejecutada y hashes antes/después de retirar imports/variables sin uso para lint; no cambió lógica de inferencia. El lector estadístico final también se ajustó a los nombres y campos opcionales reales de los artefactos; no ejecuta inferencias ni altera resultados.

## Git

Referencia preservada: rama `feature/ia-segmentacion-semantica`, HEAD `e422ef4c0a279172ca03818a4b16ffb501d493bd`, tag `ia-p1-stable-v1` (objeto `9441a1fa094b67bc8cfa0134e0bd7eb7902228ab`, commit HEAD). `git diff --stat` y staged diff están vacíos porque los informes/scripts nuevos siguen untracked; el inventario final lista esos archivos por separado. No hubo `git add`, commit, push, cambio de rama, reset, restore ni modificación del tag.

```text
?? DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/
?? services/mati-ai/scripts/segmentation/
```

**AUD-07 permanece MITIGADO OPERACIONALMENTE.** Esta fase no demuestra resolución automática de identidad de prenda ni autoriza IDs de color. Investigación y benchmark cerrados; integración detenida por el gate.
