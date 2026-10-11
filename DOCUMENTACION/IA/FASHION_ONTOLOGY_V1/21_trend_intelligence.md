# 21. Trend Intelligence — contrato de diseño futuro

Estado: `DRAFT_FOR_REVIEW`. Origen: decisiones humanas 7 y 12 y aclaración 17 del prompt de revisión de 2026-10-08. Define observaciones temporales, contextualización y propuestas de novedad. No implementa detección activa, scraping, métricas, modelos ni promoción de conceptos.

## Responsabilidad y evidencia temporal

Trend Intelligence observa manifestaciones de moda y relaciona sus rasgos con IDs existentes de Fashion Ontology. Una tendencia no es una preferencia individual ni un nuevo estilo estable por definición. Las observaciones pueden ser internacionales, regionales, nacionales o locales; el contrato no limita las fuentes a Bolivia. El uso comercial posterior contextualiza esas observaciones para el lugar, fecha, clima, ocasión y productos reales de Mati.

| Campo del envelope futuro | Contenido y condición |
| --- | --- |
| trend_observation_ref | Referencia de observación temporal, separada de un canonical_id estable. |
| observed_term / semantic_description | Nombre usado en la fuente y significado contextual; un nombre nuevo no acredita novedad. |
| observation_window / observed_at | Fechas de hechos observados y consulta; fecha de publicación no sustituye ambas. |
| geography / cultural_context | Lugares, alcance, población/contexto y límites de generalización. |
| hemisphere / seasonal_context | Hemisferio y estación pertinentes al lugar/ventana, cuando sean conocidos. |
| characteristic_features | Rasgos visuales, prendas, atributos, layering y alternativas con evidence_refs. |
| related_concept_refs / ontology_version / ontology_content_hash | IDs existentes y significado exacto utilizados en la evaluación. |
| evidence / evidence_refs | Registros con la forma evidence del contrato12, centralizados por observación; los vínculos con productos PI reutilizan sus referencias. |
| source_method / metric_observations | Métodos, muestras/limitaciones y métricas sólo si existen datos verificables. |
| novelty_assessment_ref / proposal_ref | Evaluación y propuesta, sin modificar el vocabulario estable. |
| review_refs / validity_notes | Revisión, vigencia observada y incertidumbre. |

Se reutilizan tipos de evidencia/source_type/authority y confianza del contrato12. Una referencia a fuente no acredita actualidad o crecimiento. Una afirmación sobre popularidad requiere datos, ventana, geografía, método, muestra y comparación documentados; indicadores de búsqueda, cobertura editorial y ventas no se equiparan. La escala de una métrica de tendencia no es afinidad de estilo del producto ni probabilidad calibrada.

Los valores ausentes/ambiguos permanecen explícitos. No extrapolar cobertura de una ciudad a todo un país, publicaciones comerciales a toda una población, ni una observación puntual a persistencia. Una fuente puede aportar evidencia cultural y visual sin medir popularidad. Esta entrega no afirma tendencias vigentes ni crea series inventadas; la investigación de vocabulario queda diferenciada en el documento23.

## Evaluación de novedad y redundancia

La evaluación compara descripción semántica, rasgos visuales, atributos distintivos, relaciones existentes, contexto cultural, evidencia temporal, persistencia y utilidad práctica de crear una identidad independiente. Se conservan alternativas y razones, incluidos datos faltantes y superposiciones. Similitud numérica o aparición de un nombre no deciden la incorporación.

| Resultado de evaluación propuesto | Tratamiento |
| --- | --- |
| Alias/sinónimo | Reutilizar concepto existente y proponer alias con idioma/ámbito; revisar ambigüedad. |
| Variante/subestilo | Relacionar con un padre existente; demostrar diferencias y alcance antes de proponer ID. |
| Combinación de estilos | Referenciar los estilos componentes y sus rasgos sin inventar una clase exclusiva por combinación. |
| Estética relacionada | Mantener eje aesthetic separado de style, dress_code y formality. |
| Configuración de prendas/layering | Registrar prendas, atributos y relaciones contextualizadas; no convertir cada combinación en estilo. |
| Microtendencia temporal | Conservar observación, ventana y lugar; no promover automáticamente al vocabulario permanente. |
| Concepto verdaderamente nuevo | Proponer identidad, diferencias, relaciones y utilidad con evidencia suficiente para revisión. |
| Indeterminado | Conservar candidatos y dependencia de evidencia; no forzar clasificación. |

Por ejemplo, un término observado que sólo renombra una estética ya registrada es una propuesta de alias, mientras una relación falda-sobre-pantalón puede ser configuración de layering sin nuevo estilo. Son ejemplos semánticos de decisión, no afirmaciones sobre popularidad actual ni aprobaciones de Mati.

La propuesta contiene proposal_ref, concepto candidato, descripción ES/EN, tipo de novedad, conceptos comparados, alias propuestos, rasgos diferenciadores, ejemplos positivos/negativos/fronterizos, relaciones, geografía/cultura, evidencia fechada, dudas, utilidad práctica y revisión solicitada. La fuente de cada afirmación y los permisos de sus recursos se conservan separadamente. Un candidato permanece fuera del vocabulario estable y de los labels entrenables.

## Gobierno y versionado

Mati revisa utilidad/comprensión local y criterios comerciales. El responsable técnico revisa IDs, compatibilidad, dominio, relaciones, pruebas y versión; revisores de anotación evalúan ejemplos y desacuerdos. Ninguno de estos responsables o decisiones se inventa a partir de la presencia de la propuesta. La aprobación gradual por ámbito exige los artefactos del registro de gobierno y no aprueba automáticamente dataset/modelos/producción.

Una incorporación futura aceptada tendrá decisión explícita, evidence, responsable, fecha, ámbito y transición de versión/hash. Los conceptos retirados se deprecian sin reciclar ID ni perder enlaces. Cambiar popularidad/vigencia de una observación temporal no redefine un concepto existente. Un cambio de significado/aplicabilidad necesita migración conceptual y revisión de consumidores; una actualización de sinónimo contextual no implica promoción automática de un estilo.

## Detección frente a recomendación realizable

Outfit Intelligence recibe la tendencia con su ámbito y contexto del documento22. Para cada solicitud distingue: tendencia identificada; representable parcialmente con inventario Mati; capaz de producir outfits completos; sin productos suficientes. Este estado depende de consulta comercial reciente, variantes/tallas y piezas requeridas; no es una propiedad permanente de la tendencia.

Antes de recomendar se consideran ubicación de uso, fecha, hemisferio, estación, clima sólo si fiable, ocasión, dress code, preferencias, compatibilidad, layering e inventario. Verano en Europa no implica adecuación al invierno de La Paz. Estación de uso y temporada comercial son ejes diferentes; un nombre de temporada no confirma prestaciones climáticas. Si falta información, se declara el límite y no se inventa clima, stock ni ajuste personal.

## Criterios verificables para la implementación posterior

- Toda afirmación temporal incluye fuentes/fechas/geografía/método; no se afirma crecimiento o persistencia sin comparación verificable.
- Nombres nuevos se comparan con conceptos existentes y se clasifican razonadamente; similitud sola no crea ID.
- Propuestas, aliases y conceptos estables quedan distinguibles; no hay modificación automática de ontology, estilos o labels.
- Estilo/estética/dress code/formalidad y métricas temporales/afinidades de producto siguen separados.
- La detección puede abarcar contextos internacionales; la recomendación distingue hemisferios y disponibilidad real.
- Falta de productos admite cobertura parcial o ninguna, sin marcar variantes inexistentes como disponibles.

Dependencias pendientes: fuentes autorizadas y cobertura real, método/muestras, evaluación temporal, revisión cultural, guía de novedad, responsables y gates de incorporación. No se promueve Trend Intelligence a producción por documentar este contrato.
