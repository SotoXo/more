# Aprobación gradual por ámbito

La decisión H12 aprueba el procedimiento, no las familias. El registro formal `services/mati-ai/resources/fashion_ontology/v1/governance.json` separa las doce políticas humanas aprobadas de los ámbitos aún pendientes. Globalmente se conserva `DRAFT_FOR_REVIEW`. No hay firmas, responsables técnicos nombrados, datasets validados ni modelos autorizados aportados en esta revisión.

| Estado del registro | Lectura y compatibilidad con V1 |
|---|---|
| DRAFT | Elaboración; corresponde al estado global DRAFT_FOR_REVIEW |
| PENDING_REVIEW | Pendiente de revisión; NEEDS MATI REVIEW sigue válido en los conceptos |
| REVIEWED | Se documentó revisión; no equivale a aprobación |
| APPROVED | Decisión explícita por ámbito con responsable, evidencia, ejemplos y fecha; SAFE TO FREEZE no basta |
| BLOCKED_INSUFFICIENT_EVIDENCE | No hay evidencia suficiente; compatible con RESEARCH INCOMPLETE |

Esta enumeración pertenece al registro de gobierno; no sustituye `review_status` ni KNOWN/UNKNOWN/NOT_APPLICABLE. `approved_policy_ids` no es una lista de conceptos aprobados. La propuesta de estilos23 y los cuatro valores de puño permanecen pendientes. La aprobación de POLERA/ACCESORIOS limita el mapping; no valida ejemplos ni etiquetas de productos históricos.

Cada ámbito conserva seis fases independientes: definición ontológica, guía de anotación, dataset validado, modelo entrenado, modelo evaluado y autorización de producción. Cada fase aprobada exige una decisión, fecha, revisor y referencias de evidencia. Las fases anteriores son prerrequisitos, nunca aprobación automática de la siguiente. Un ámbito sin ejemplos puede existir como propuesta; queda bloqueado para dataset/entrenamiento.

Mati valida criterios comerciales, productos reales y opciones propuestas. El responsable técnico, pendiente de identificar, revisa contratos, versión e integridad. Los revisores de anotaciones, también pendientes, revisan casos positivos/negativos/fronterizos, registran desacuerdos y adjudicación. La revisión comercial no prueba composición, licencia de una imagen ni métricas de un modelo.

Para aprobar la definición se requieren responsable identificado, definición revisada, ejemplos y evidencia referenciables, revisión de ambigüedades, registro de discrepancias y decisión explícita. El validador verifica la estructura y estos prerequisitos documentados; no puede acreditar que una persona haya realizado una revisión solo por recibir texto bien formado. La evidencia de revisión debe estar disponible para auditoría humana.

Transición de borrador 1.0.0 a 1.1.0: se preservan todos los IDs existentes, se agrega un atributo con cuatro valores y se amplían opcionalmente los contratos. Los mappings provisionales de POLERA, POLOS Y KNITWEAR y ACCESORIOS se restringen por decisiones humanas. MULTI_PIECE exige vínculo comercial explícito. Afirmaciones antiguas que dependan de destinos retirados del puente o de una mera coordinación visual se someten a revisión; no se migran registros comerciales ni labels automáticamente. Los hashes de 1.0.0 quedan en `review_baseline.json`, y su reporte en `16_reporte_inicial_historico.md`.

La versión 1.1.0 identifica esta revisión del borrador V1; no es una publicación aprobada. Un cambio incompatible de significado en una versión ya aprobada necesita versión mayor, deprecación y transición explícita. Nunca reciclar IDs. Los consumidores deberán validar versión y hash exactos antes de consumir una instancia PI; no aceptar silenciosamente un pin anterior con archivos revisados.
