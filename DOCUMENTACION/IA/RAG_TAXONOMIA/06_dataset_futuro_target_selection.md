# Dataset futuro para seleccionar la prenda objetivo

**Estado: contrato preparado, captura productiva desconectada y entrenamiento no ejecutado.** Segmentación sigue sin integrar. No se fabricaron etiquetas humanas a partir de máscaras DINO/SAM, feedback de atributos, la revisión Codex ni la imagen13. El manifest `target_selection_dataset_v1` tiene cero eventos, sin split numérico ni afirmación de dataset entrenable.

## Contrato ejecutable y exportable

`mati_ai.learning.target_events` define contratos Pydantic estrictos, sin campos adicionales, arrays inmutables, números finitos y esquemas JSON revisables en `services/mati-ai/resources/target_selection/`. No crea endpoint, tabla, job ni dependencia ML. Laravel deberá obtener snapshot/contexto vigente desde estado autenticado; no recibirlo como autoridad del navegador.

| Bloque | Contenido |
|---|---|
| Identidad de evento | event_id, action_id, group_id/run_id, actor autorizado, created_at con zona, schema/feature/label versions. |
| Snapshot previo | owner, run, image_id opcional, SHA de bytes, generación, dimensiones, captura y caducidad; detector/segmentador con model/revision/checkpointSHA/pipeline/preprocess. |
| Candidatos completos | candidate_id, clase externa, score no calibrado, bbox, área, distancia al centro, bordes, máscara opaca+SHA+píxeles/quality metadata. |
| Semántica previa opcional | broader_role junto a taxonomy_version; category_hint sólo si existe timestamp anterior al snapshot. |
| Referencia visual opcional | Referencia a fila/modelo/revisión/perfil768 L2 del espacio visual y SHA de la misma foto. Nunca vector textual ni embedding expuesto. |
| Etiquetas | Un selected=true y el resto=false, mismos IDs/orden que snapshot. final_product_category separado como dato posterior. |
| Aptitud para training | training_ready=false obligatorio en esta versión; no habilitado por pasar unit tests. |

`build_human_event` verifica que source y current_context sean idénticos en owner/run/foto/generación/hint, que el candidato pertenezca al snapshot y que el momento esté dentro de TTL. Rechaza bbox fuera de imagen, área/máscara inconsistentes, distancia al centro falsa, referencias de otra foto, IDs duplicados, grupos incompletos, digest inválido y eventos manipulados. Los IDs de máscara son opacos, sin paths. La comprobación de categorías y referencias contra PostgreSQL corresponde al futuro caller Laravel; el contrato Python no consulta DB ni demuestra autenticación HTTP.

## Positivo y negativos por grupo

Si el humano escoge B entre A/B/C, las etiquetas son A=0, B=1 y C=0, conservando group_id/run_id y todas las features previas. Un negativo significa “no fue seleccionado en esta decisión”, no “no es ropa” ni una categoría visual incorrecta. Sólo grupos completos, con candidatos/máscaras válidos y exactamente una elección explícita son elegibles bajo esta política. El formulario no debe convertir un clic de aplicar categoría/color en una selección de región física.

`feature_rows` excluye etiquetas, categoría final, actor y timestamps posteriores. Conserva identidad de grupo/SHA para trazabilidad/split, no para usarlos como features predictivas. No consulta RAG como sustituto del ranker. El hint previo no puede reemplazarse con la categoría final ni rellenarse retrospectivamente desde catálogo.

## Replay, revisión y linaje

Un action_id identifica una decisión deliberada; event_id deriva de owner+run+action mediante UUID determinista. Un retry conserva identidad. Una nueva decisión requiere nueva action_id. La persistencia futura debe imponer unique(event_id), devolver el evento original ante replay idéntico y rechazar el mismo ID con contenido distinto; no basta construir el UUID. Debe conservar decisiones anteriores y registrar supersession si el humano cambia de opinión.

Al elegir foto B se invalidan target, preview, crop, máscara y outputs dependientes de A. El servidor valida bytes SHA/generación vigente; el cliente cancela solicitudes previas y descarta respuestas tardías. Lineage debe unir run fuente, snapshot completo, elección, actor y cualquier posterior producto sin sobrescribir la detección original. La expiración debe impedir elección nueva; una evidencia válida ya registrada requiere política separada de retención para reproducibilidad.

## P2 que impiden declarar training usable

| Hallazgo existente | Precondición |
|---|---|
| AUD-10 / FE-03 / LAR-04 | Feedback form no idempotente: no importar replays como decisiones distintas. |
| AUD-14 / FE-02 | Sugerencias de A pueden conservarse o llegar tras cambiar a B: invalidación y captura ligadas a foto vigente. |
| AUD-15 / FE-06 | Validación anidada incompleta: verificar candidato, snapshot y respuesta íntegra. |
| LAR-07 | Persistencia run/suggestions sin transacción conjunta: asegurar snapshot y decisión completos. |
| LAR-03 / LAR-05 / AUD-12 | Deduplicación acotada y vigencia por ruta: deduplicar SHA completo y detectar cambio de bytes. |
| LAR-09 / metadata de lineage | Correlación incompleta entre requests: conservar source run y versiones explícitas. |

Fuentes: `DOCUMENTACION/IA/AUDITORIA_FINAL/_frontend_inspeccion.md`, `_laravel_inspeccion.md`, `08_incoherencias_y_riesgos.md` y documentos `CORRECCIONES_P1`. Estos hallazgos no se cierran por implementar RAG. No se corrigió aquí la UI de segmentación ni se conectó el collector; el bloqueo de entrenamiento permanece explícito.

## Snapshot y split futuros

El manifest estructurado registra fecha, event IDs, versiones de modelos, schemas, política de etiquetas, exclusiones y split. Antes de exportar, excluir pruebas sintéticas, selección Codex, feedback de atributos, eventos repetidos/stale, grupos incompletos y conflictos sin resolución. Pseudonimizar actores y conservar imágenes/máscaras en storage privado con autorización y retención definida; no extraer atributos biométricos.

Agrupar por SHA, duplicados y producto cuando exista, manteniendo candidatos de una imagen en el mismo split. Reservar holdout independiente antes de ajustar features/modelos/umbrales; nunca colocar copias de una foto en train y test. El catálogo N130 no es un conjunto humano nuevo y no debe reciclarse como holdout independiente tras repetidas decisiones experimentales. Los porcentajes se decidirán con volumen real, sin prometer capacidad con cero eventos.

## Alternativas futuras, sin implementación

Una regresión logística ofrece un baseline interpretable sobre features geométricas/semánticas; boosting permite relaciones no lineales. Un ranker aprende competencia dentro del grupo y debe conservar grouping; la [documentación XGBoost](https://xgboost.readthedocs.io/en/stable/tutorials/learning_to_rank.html) usa qid para esos grupos. XGBoost tiene [Apache-2.0](https://github.com/dmlc/xgboost/blob/master/LICENSE); LightGBM tiene [MIT](https://github.com/lightgbm-org/LightGBM/blob/main/LICENSE). No se instalaron aquí. MLP pequeño o fine-tuning visual requieren datos y un experimento posterior; no se implementaron.

Active learning futuro puede dirigir al humano cuando los candidatos compiten o falta evidencia, con selección automática sólo tras validación y calibración independientes. No se fijó un threshold definitivo del futuro ranker. Los clics tienen ruido y sesgo de posición, señalado por la fuente oficial de ranking; no equivalen automáticamente a ground truth limpio.

Flujo futuro: eventos → snapshot versionado → train → validation → holdout → modelo candidato → gate → nueva versión. **Ningún clic actualiza pesos online.** Taxonomía aporta features versionadas; RAG aporta conocimiento estable; SigLIP permanece visual. Estos componentes no se sustituyen entre sí.

## Pruebas realizadas

29 pruebas específicas aprobadas: grupo positivo/negativos, replay determinista, otra foto/generación/run/owner, candidato ajeno, ventana temporal, hint posterior, separación features/labels, máscaras/geometría, IDs duplicados, digest, inmutabilidad, evento manipulado y rechazo del espacio textual como referencia visual. Ruff F/E9 del contrato aprobado. Son pruebas de contrato puro; no prueban persistencia, autenticación, UI, consentimiento ni calidad de etiquetas humanas.
