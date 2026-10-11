# 22. Outfit Intelligence — contrato de diseño futuro

Estado: `DRAFT_FOR_REVIEW`. Origen: decisiones humanas 6, 7, 9 y 12 y aclaración 17 del prompt de revisión de 2026-10-08. Define consumo contextual de ontología, Product Intelligence, tendencias e inventario. No implementa Outfit Engine, perfil, recomendador, reserva, venta ni lógica de stock.

## Entidades y autoridad comercial

La operación habitual de Mati vende prendas individuales. Un outfit es una combinación contextual de productos que pueden venderse por separado; no crea un producto/set ni SKU nuevo. Un vestido es una sola prenda aunque resuelva gran parte del look. Un conjunto comercial auténtico requiere vínculo y componentes explícitos y puede venderse completo o por piezas cuando existan registros e inventarios necesarios.

El precio mostrado de un outfit podrá ser una suma informativa de precios actuales consultados en Laravel; no se presenta como precio de paquete, descuento ni oferta comercial sin regla comercial explícita. El manejo de stock compartido/reservado/empaquetado entre conjunto y componentes permanece pendiente. No se implementa doble decremento, deducción, reserva ni lógica de carrito en esta revisión.

| Entrada del contrato futuro | Contenido y límite |
| --- | --- |
| request_ref / use_context | Ubicación y fecha de uso, hemisferio/estación, ocasión, dress code y clima con fuente cuando exista. |
| user_preferences_ref | Preferencias del cliente mediante contrato futuro de perfil; no se copian medidas/body shape a la ontología. |
| candidate_product_refs / product_intelligence_refs | Productos/variantes Laravel y sus assertions/versiones/evidence. |
| commercial_availability_ref | Resultado de consulta Laravel con instante, variante/talla y disponibilidad; no segunda fuente de stock en IA. |
| trend_observation_refs | Observaciones temporales y geográficas del documento21, sin convertirlas en estilos aprobados. |
| ontology_version / ontology_content_hash | Significados, body zones, atributos y layering utilizados. |
| constraints / unknowns | Requisitos del contexto y datos faltantes expresos; no se simulan medidas/preferencias desconocidas. |

Cada respuesta futura distingue outfit_ref, productos/variantes/tallas seleccionados, motivo/evidence_refs, configuración de layering por instancias, contexto, incertidumbres, disponibilidad consultada y cobertura de la tendencia si se usó. Las referencias comerciales sólo identifican datos obtenidos de Laravel; el resultado no pretende confirmar indefinidamente disponibilidad ni modifica el catálogo.

## Compatibilidad y contexto

La composición evalúa identidad, cobertura/carry/contact de body zones, relaciones over/under por zona/contexto, color, patrón, silueta, fit de diseño, estilo, estética, ocasión, dress code y formalidad. Las preferencias estéticas y soft rules se distinguen de restricciones estructurales. No se prohíben dos prendas de la misma familia o zona por regla global: falda sobre pantalón puede ser configuración contextual.

Los grafos de layering entre instancias no admiten autorrelaciones ni ciclos en la misma zona/contexto. La ontología ya define BASE/INTERMEDIATE/OUTER/OVERLAY/ACCESSORY como roles contextuales; un blazer puede ocupar un rol diferente según lo que lleva encima/debajo. Una posición válida no demuestra holgura, movilidad ni adecuación térmica universal. Se exige evidencia pertinente o se informa incertidumbre.

Estilo, estética, dress code y formalidad se consultan como ejes separados. Una afinidad a estilo no garantiza cumplir un código de vestimenta; el motor no infiere estilo por la categoría comercial ni declara universalmente formal un blazer. Las reglas de color/styling deben tener fuente/contexto y distinguir recomendaciones de prohibiciones físicas.

Se consideran fecha y lugar de uso, estación según hemisferio y clima sólo con información fiable y fecha/fuente apropiadas. La temporada comercial de una ficha no es medición de abrigo/aislamiento ni garantía climática. Una tendencia estival de otra región puede ser inspiración parcial sin recomendación automática en invierno local. Si la fecha/lugar/clima se desconocen, se registra el dato faltante y se evita afirmar adecuación específica.

Fit de diseño y forma de pierna no prueban ajuste sobre una persona. Tallas comerciales disponibles se respetan tal como Laravel las registra. Medidas/tablas oficiales o verificadas pueden apoyar un consumidor futuro independiente de ajuste personal; esta fase no lo calcula. Valores históricos sospechosos como `XXXXXXXXL` permanecen intactos y fuera de recomendación de talla hasta validación humana.

## Inventario y realizabilidad

El motor consulta productos/variantes/tallas realmente existentes y disponibles. La lectura comercial tiene fecha y scope; antes de una operación comercial futura Laravel deberá revalidar condiciones/concurrencia. Una lectura antigua no se declara stock actual. Si falla la consulta o no hay disponibilidad fiable, el resultado expresa disponibilidad desconocida y no afirma que pueda comprarse.

| Cobertura de tendencia | Condición conceptual |
| --- | --- |
| Identificada | Existe observación de tendencia, sin afirmar cobertura comercial. |
| Representación parcial | Hay productos reales para algunos rasgos/piezas y se explican los faltantes. |
| Outfit completo realizable | Las piezas necesarias tienen productos/variantes/tallas disponibles en la consulta y compatibilidad/contexto suficientes. |
| Sin productos suficientes | No hay combinación verificable con el inventario consultado; se declara el límite. |

No se completa un outfit con una variante inexistente ni se etiqueta como disponible un artículo de catálogo externo. Un candidato externo sólo informa identificación/estilo; no forma parte del stock Mati por existir en internet. Si varias piezas reutilizan el mismo componente comercial, la disponibilidad agregada requiere la regla de inventario comercial aún pendiente; no se asume capacidad doble. Un conjunto comercial y sus piezas no se contabilizan como existencias independientes sin un modelo autorizado.

## Revisión y explicación

Mati o el cliente, según el flujo futuro autorizado, pueden comparar alternativas, retirar una pieza, corregir contexto/preferencia o rechazar todo. El resultado conserva por qué se sugirió una combinación, su evidencia y límites, sin transformar una elección en axioma ontológico ni etiqueta de entrenamiento. Una alternativa rechazada no implica que su estilo sea inválido para otros contextos.

El consumidor explica la cobertura parcial de una tendencia y la disponibilidad consultada, separando ambos juicios de popularidad internacional. No genera stock, precios, tallas, medidas ni adecuación personal ausentes. Los rankings y umbrales de recomendación requieren evaluación futura propia, no se deducen de scores visuales o de la existencia de atributos.

## Criterios verificables para la implementación posterior

- Un vestido permanece product individual; un outfit combina referencias existentes y no crea set/producto comercial.
- Productos de outfit pueden comprarse individualmente bajo reglas Laravel; un conjunto auténtico conserva componentes y limitación pendiente de inventario.
- Toda pieza disponible tiene producto/variante/talla y consulta comercial verificables; consulta fallida o antigua no confirma stock actual.
- Ciclos de layering se rechazan por zona/contexto; reglas de estilo no se convierten en incompatibilidades estructurales sin evidencia.
- Tendencia y recomendación usan contexto/hemisferio/estación y distinguen representación parcial/completa/insuficiente.
- Un fit visible no decide talla personal; datos sospechosos no verificados quedan fuera de esa recomendación.
- No hay cambios de precio, stock, SKU, reserva o venta, ni promoción de modelo por este contrato documental.

Dependencias pendientes: perfil/contexto, contrato comercial de consulta y revalidación, inventario de conjuntos/componentes, reglas de recomendación revisadas, medidas suficientes, evaluaciones y autorización independiente de producción.
