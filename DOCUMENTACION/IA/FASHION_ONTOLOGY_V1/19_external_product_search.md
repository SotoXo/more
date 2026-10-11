# 19. External Product Search — contrato de diseño futuro

Estado: `DRAFT_FOR_REVIEW`. Origen: decisiones humanas 4, 8 y 9 y aclaración 14 del prompt de revisión de 2026-10-08. Diseña consultas y evaluación de coincidencias; no implementa buscador, scraping, descarga ni conectores.

## Entrada, estrategia y fuentes

La entrada referencia la identificación parcial del documento18, sus assertions/evidence y versión ontológica. Tipo/color/marca/talla pueden permanecer UNKNOWN. Se construye una consulta sólo con datos acreditados y candidatos etiquetados como tales; un valor candidato no se presenta como hecho confirmado.

La búsqueda futura combina, según evidencia disponible, marca, referencia/modelo, SKU externo, código de barras, categoría, color, talla/variante, rasgos visuales y descripción. Una referencia o código leído debe verificarse: puede identificar una familia de modelos o variante distinta. Si no hay marca/ref, la búsqueda visual/descriptiva conserva explícitamente su menor poder identificador. No se bloquea por faltar uno de los cuatro campos iniciales.

La estrategia prioriza página oficial del fabricante, catálogo oficial, proveedor/fuente autorizada y finalmente otras fuentes verificables. Esa prioridad organiza consultas; no establece una jerarquía universal de verdad frente a una etiqueta física ni concede permiso de descargar. Cada fuente debe tener alcance de acceso documentado. No se eluden login, restricciones ni límites de los sitios.

| Campo del envelope futuro | Contenido y regla |
| --- | --- |
| search_ref / identification_ref / target_entity_ref | Referencias al proceso y prenda física. |
| query_fields / query_evidence_refs | Datos usados, candidatos declarados y su procedencia. |
| query_strategy_ref / queried_at | Estrategia versionada y fecha de consulta. |
| source_attempts | Fuente/proveedor, URL, resultado del intento, restricciones y razón del fallo. |
| external_product_candidates | Candidatos con producto/modelo y variantes externas independientes. |
| match_assessments | Evaluación argumentada por candidato; evidencia favorable, negativa y desconocida. |
| evidence_refs | Referencias a evidence central del contrato12; no duplica sus registros por campo. |
| unresolved_fields / next_actions | Incertidumbres y opciones de refinar/revisar/manual. |

Un candidato conserva external_product_ref, external_variant_ref cuando se conoce, identificadores leídos/declarados, descripción, URLs y fecha. Se comparan referencia/modelo, color, talla, temporada/edición, composición cuando está documentada y rasgos distintivos. Ausencia de datos no equivale a coincidencia. Fuentes que repiten una ficha no son confirmaciones independientes.

## Niveles de coincidencia

Estas denominaciones se reflejan en `evidence.source_details.identity_match_status`; son contexto de correspondencia, separados de estado KNOWN/UNKNOWN/NOT_APPLICABLE, calidad de fuente y revisión humana.

| Nivel | Criterio conceptual | Consecuencia |
| --- | --- | --- |
| exact_verified | Identidad física/documental suficiente, producto y variante explícitos, sin contradicción material pendiente en ese vínculo | Puede aportar hechos técnicos sólo dentro del alcance verificado de la ficha y fuentes permitidas. |
| probable | Hay coincidencias distintivas, pero falta verificar referencia/variante u otro vínculo | Mantener propuestas y campos técnicos UNKNOWN; solicitar evidencia/revisión. |
| similar | Parecido de apariencia sin identidad demostrada | Referencia visual para comparar, nunca composición o medidas confirmadas de Mati. |
| insufficient | Evidencia incompleta, contradictoria o sin vínculo fiable | No seleccionar arbitrariamente; refinar o continuar manualmente. |
| no_results | Ningún resultado utilizable en los intentos realizados | Es resultado de la consulta, no prueba de inexistencia del producto. |

`exact_verified` requiere external_product_ref, external_variant_ref, match_assessment_ref, variant_verification_ref y same_variant_verified=true en los metadatos PI. Esas referencias deben resolver evidencia verificable en una integración futura. La sola etiqueta del nivel no acredita la coincidencia. No se fija un umbral numérico universal de exactitud: una evaluación considera identificadores, señales distintivas y conflictos por atributo. Similitud de embeddings sólo ordena candidatos.

Coincidir en modelo con distinta talla, color, temporada, composición o referencia no permite unir variantes. Una ficha común a varias variantes exige indicar qué campos comparten realmente; la coincidencia exacta de modelo no prueba medidas de una talla no verificada. SKU externo y SKU comercial Laravel permanecen separados.

## Fallos y revisión

Se distingue sin resultados de error de red, fuente no disponible, restricción de acceso, permiso insuficiente, resultado retirado o formato no interpretable. El registro de intentos conserva fuente, fecha, motivo y datos mínimos; evita repetir automáticamente consultas fallidas en bucle. Las políticas de reintento y límites de proveedor se acordarán antes de implementación.

Mati puede comparar candidatos, seleccionar otro, corregir identificación, rechazar todos, pedir nuevas coincidencias o continuar manualmente. «Ninguna opción corresponde a mi prenda» conserva rechazo/contexto útil y no guarda el primer resultado. Aceptar un parecido por utilidad visual no lo convierte en exact_verified ni autoriza transferir hechos técnicos.

## Criterios verificables para la implementación posterior

- Búsqueda parcial funciona con talla/marca UNKNOWN y declara qué evidencia utilizó.
- Dos variantes se conservan separadas; un resultado similar no rellena composición, medidas, talla ni prestaciones como KNOWN.
- Coincidencia exacta requiere documentación de producto y variante; contradicción relevante mantiene la correspondencia en revisión.
- Sin resultados, acceso restringido o error dan razones distintas y ofrecen registro manual/reintento autorizado.
- La priorización de fuentes no invalida automáticamente una etiqueta física; todos los conflictos conservan procedencia.
- Ningún resultado modifica Laravel, genera ground truth de entrenamiento o descarga contenido sin permiso verificable.

Dependencias pendientes: proveedores autorizados y sus condiciones, estrategia/query versionada, reglas de verificación por producto/variante y política de acceso/reintento. En esta revisión no se consultan productos externos reales ni se afirma precisión de búsqueda.
