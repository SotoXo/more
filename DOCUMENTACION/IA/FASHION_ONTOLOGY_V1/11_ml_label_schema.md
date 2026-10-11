# 11 · Contrato semántico de etiquetas para ML futuro

Estado: `DRAFT_FOR_REVIEW`, versión propuesta `1.1.0`; corte 2026-10-08 (`America/La_Paz`). Este documento prepara una futura guía de anotación y criterios de evaluación. No selecciona arquitectura, no constituye un dataset y no aprueba entrenar o integrar modelos.

[label_schema.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/label_schema.json) contiene **69 registros de atributos**; [annotation_matrix.csv](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/annotation_matrix.csv) permite revisarlos junto con su learnability y destino semántico. Identidad y valores proceden de [ontology.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/ontology.json) y sus vocabularios; aplicabilidad de [attribute_applicability.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/attribute_applicability.json). La validación de los artefactos no acredita calidad de anotación ni desempeño de un modelo futuro.

## Identidad, valor, estado y evidencia

La unidad semántica futura será una entidad de producto y sus componentes o instancias identificables, enlazados con las vistas que aportan evidencia. Familia y categoría son single label con presencia requerida; subcategoría es single label opcional. UNKNOWN debe conservarse como incertidumbre explícita cuando la evidencia no resuelve la identidad. Una foto de varias prendas no recibe una sola categoría por agregación.

Coordinated_set representa entidad de conjunto acreditada y piezas separables etiquetadas individualmente. Los componentes no heredan indiscriminadamente los atributos de la entidad ni unos de otros. La anotación de un producto con varias vistas puede resolver atributos en vistas diferentes; una única vista oculta no revoca evidencia válida de otra vista, pero un cambio de producto o variante sí exige revisar la asociación.

La futura guía debe precisar estos campos, sin afirmar que ya existe un formato de instancias validado:

| Campo semántico | Propósito |
|---|---|
| Identidad y versión | ID de entidad/instancia, ontology_version y esquema de anotación usado |
| Sujeto | Producto completo, pieza de conjunto, componente o región que recibe la afirmación |
| Atributo y valor canónico | IDs y tipo definidos en SSOT; unidades explícitas para medidas |
| Estado | KNOWN, UNKNOWN o NOT_APPLICABLE, separado del valor |
| Evidencia | Vista, región o fuente documental que sostiene la afirmación; fecha cuando corresponde |
| Procedencia | Autor, revisión/adjudicación y fuente de etiqueta; propuesta automática separada de verdad adjudicada |
| Cobertura y motivo | Oclusión, resolución, vista ausente, contexto insuficiente o conflicto |
| Completitud multilabel | Qué conjunto o región se revisó y qué ausencias se adjudicaron |
| Relación y contexto | Componentes del conjunto y capas por zona/contexto, cuando sean pertinentes |

Confidence de un modelo, certeza de un anotador y evidencia documental son datos diferentes. Un score alto no transforma una composición inferida en declaración de fibra ni una afinidad en hecho de uso.

## Tipos de etiqueta

| Tipo | Registros actuales | Ejemplo | Requisito de guía |
|---|---:|---|---|
| `single_label` | 18 | Leg_shape, neckline, frame_shape | Una elección sustentada en el dominio; alternativas ambiguas conservan UNKNOWN |
| `multi_label` | 29 | Pattern, styles, closure | Conjunto de valores pertinentes; incompletitud no convierte omisiones en negativos |
| `ordinal` | 10 | Rise, sleeve_length, formality | Orden y anclas definidos; no asumir distancias iguales o precisión métrica |
| `boolean` | 3 | Slit, pleats, toe_open | True/false sólo con atributo aplicable y presencia/ausencia adjudicada |
| `continuous` | 4 | Inseam, heel_height, capacity, belt_width | Unidad, método y fuente; no medir con una imagen sin escala |
| `structured` | 5 | Fiber_composition, observed_color, insulation, spatial_semantics, layering | Componentes/campos y evidencia por afirmación; no reducirlos a una clase visual única |

Los conteos corresponden a atributos; family/category/subcategory son etiquetas taxonómicas adicionales. `requirement=required` sólo corresponde a pattern y spatial_semantics entre estos 69; significa slot/estado presente, no KNOWN obligatorio. No se replica la lista de valores: `allowed_values_ref` remite al dominio versionado de cada vocabulario.

## Política de pérdidas y supervisión

Para KNOWN, calcular una futura pérdida de valor sólo sobre datos adjudicados y pertinentes. UNKNOWN se enmascara y se informa como cobertura; nunca se convierte en negativo. NOT_APPLICABLE excluye la pérdida de valor y requiere verificar la decisión de aplicabilidad. Si en el futuro se justifica modelar routing/aplicabilidad, deberá tener objetivo y evaluación propios; no está seleccionado aquí.

En multilabel, los negativos requieren ausencia revisada explícitamente dentro del alcance anotado. Por ejemplo, floral en el torso no prueba que no haya rayas en los puños ocultos. Check y su hijo tartan no constituyen dos positivos independientes para la misma región: utilizar la especificidad adjudicada y una política jerárquica explícita de agregación. Afinidades style no son probabilidades mutuamente excluyentes ni deben sumar 1.

La máscara se decide por atributo, sujeto y evidencia. Una bota puede aportar category boot KNOWN y shaft_height UNKNOWN; un sweater sleeveless aporta sleeve_shape NOT_APPLICABLE; una falda tiene leg_shape NOT_APPLICABLE; una composición sin etiqueta tiene UNKNOWN. La ausencia documentada de componente textil/no textil produce NOT_APPLICABLE para los atributos condicionados; presencia desconocida sigue UNKNOWN. No eliminar la entidad completa porque un atributo no sea conocido.

## Aprendibilidad y ámbitos semánticos futuros

El corte actual marca **48 atributos `trainable_visual=conditional` y 21 `no`**. Conditional significa candidato sujeto a guía, evidencia, acuerdo de anotación y evaluación; ninguno está aprobado para entrenamiento. `visually_learnable` puede ser yes, partial o no y expresa posibilidad de observación, no autorización ni garantía de precisión.

`future_head` distingue ámbitos `GENERAL.*` y `SPECIALIST.*`, además de `EXISTING_COLORIMETRY`, `DERIVED_SPATIAL_CONTRACT` y `CONTEXTUAL_LAYERING_CONTRACT`. El nuevo sleeve_cuff_type usa `future_head=null`: ningún especialista de puños está elegido o autorizado. Son destinos semánticos para planificar anotación/evaluación: no seleccionan encoder compartido, cantidad de redes, multi-head físico, especialistas entrenados o MoE.

| Ámbito propuesto | Contenido | Límite |
|---|---|---|
| General | Apariencia reusable, patrones, construcción/cierre, material y uso como contratos de información | GENERAL no implica trainable; fibra, público objetivo, ocasiones o estética pueden requerir catálogo/contexto y estar en `no` |
| Upper / lower / outerwear / one_piece | Escote/mangas; pernera/cintura; solapa/largo exterior; criterios de una pieza | Respetar familia, category_scope y condiciones; un head semántico no elimina UNKNOWN |
| Footwear / bags / eyewear / jewelry / accesorios específicos | Tacón/puntera, porte/forma, montura/lente, forma ornamental, caja de reloj | Separar objeto, componente y unidad; no propagar rasgos a familias vecinas |
| Colorimetría y contratos espaciales/contextuales | Descripción cromática existente, zonas y orden de capas | No reemplazar el pipeline vigente ni fingir una clase visual global para una relación contextual |

Composición, materiales auténticos, medidas, propiedades declaradas y públicos comerciales permanecen fuera de un objetivo visual directo en este corte. Styles y formality son conditional con aprendibilidad partial: necesitan rúbrica contextual y acuerdo local antes de considerar ejemplos entrenables. Aesthetics, dress_codes, occasions, calendar_seasons y climate_suitability no son objetivos visuales aprobados. Lens_shape y frame_shape tienen destinos separados aunque hoy compartan vocabulario; hace falta definir su geometría de referencia.

## Guía de anotación futura

1. **Fijar el alcance de identidad.** Relacionar fotos con producto/variante; identificar entidades y piezas. Adjudicar camisa–blusa, blazer–coat, sweater_vest–vest y vestido–conjunto con ejemplos y contraejemplos de Mati. No usar top o accessory como desconocido.
2. **Resolver aplicabilidad antes del valor.** Consultar familia, category_scope y condición. En tejidos decidir el componente; en mangas decidir presencia; condición desconocida mantiene UNKNOWN.
3. **Separar fuentes de evidencia.** Registrar observación visual frente a etiqueta/ficha comercial, medida y contexto. Una propuesta de modelo requiere revisión humana; no se convierte en ground truth automáticamente.
4. **Localizar afirmaciones.** Asociar materiales a shell/lining/trim/fill según el contrato actual y revisar extensión cuando haga falta. Localizar patrón y textura; separar lente y montura. Para conjuntos, etiquetar cada pieza.
5. **Registrar estados y completitud.** Distinguir ausencia confirmada, falta de vista y concepto inaplicable. Para multilabel documentar alcance revisado y negativos adjudicados.
6. **Medir acuerdo y adjudicar conflictos.** Comparar anotadores por atributo y familia con los mismos ejemplos; conservar razones del desacuerdo. Revisar definiciones antes de atribuir el desacuerdo a un modelo.
7. **Versionar cambios.** Relacionar cambios de definición/vocabulario con anotaciones afectadas; no reutilizar silenciosamente el mismo significado después de una ruptura semántica.
8. **Diseñar particiones sin fuga.** Separar productos/variantes y fuentes correlacionadas entre entrenamiento y evaluación; definir después el criterio exacto, con un registro de procedencia y derechos.

Este orden es una propuesta editorial para una fase posterior. El dataset, las instrucciones detalladas al anotador, el formato de instancias, las particiones y la herramienta de anotación requieren diseño específico; no están ejecutados por estos documentos.

## Uso de etiquetas públicas

El mapping se revisa por campo y valor, con dirección, pérdida y estado, en la sección `conceptual_mappings` de [research_sources.json](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/research_sources.json): **17 mappings conceptuales** en este corte. El mismo registro contiene procedencia, fuentes y revisión de licencias. Es un registro de diseño y candidatos, no una importación de etiquetas ni un adaptador ejecutado.

DeepFashion2 short/long sleeve dress puede aportar dress y longitud de manga; no aporta automáticamente fibra, estilo cultural o uso. Su campo style tiene NO_DIRECT_MAPPING a styles de MORE. ModaNet pants agrega pantalones, jeans y leggings; no permite elegir una categoría específica sin reanotar. ModaNet outer cruza varias familias/identidades de MORE. Fashionpedia aporta distinción conceptual categoría/parte/atributo, pero sus etiquetas locales no se trasladan a todo el producto sin preservar alcance. Véanse [DeepFashion2](https://github.com/switchablenorms/DeepFashion2#data-organization), [ModaNet](https://github.com/eBay/modanet#labels) y [Fashionpedia](https://arxiv.org/html/2004.12276v2).

Missing source annotation no significa etiqueta MORE negativa. Una equivalencia candidata no convierte el origen en evidencia suficiente de todos los atributos. Ningún mapping semántico autoriza uso comercial del dataset: deben revisarse por separado código, anotaciones e imágenes. Fashionpedia declara licencia de ontología/anotaciones y derechos fotográficos diferentes; ModaNet publica anotaciones bajo licencia no comercial; DeepFashion2 no quedó comercialmente habilitado en la revisión. Véanse [Fashionpedia, licencia](https://fashionpedia.github.io/home/data_license.html), [ModaNet, licencia](https://github.com/eBay/modanet/blob/master/LICENSE) y el registro de investigación para límites de verificación.

## Métricas, cobertura y gates

| Objetivo | Métricas previstas en el contrato | Lectura necesaria |
|---|---|---|
| Single label | Accuracy, macro-F1, recall por clase, coverage, selective error | Reportar soporte por clase y errores en las fronteras adjudicadas |
| Multilabel | Micro/macro-F1, precision, recall, mAP con ranking y ground truth completo, annotation coverage | Separar conjuntos completos de parciales y respetar jerarquía/regiones |
| Ordinal | MAE sobre rango, quadratic weighted kappa, coverage | Rango operacional; no presentar error como cm o distancia psicológica |
| Continuous | MAE, RMSE, correlación con soporte y unidades | Evaluar mediciones de referencia y protocolos por atributo |
| Structured | Validez de campos, integridad de referencias, completitud de componente/evidencia | Medir corrección del contrato además de completitud |
| Boolean | Precision, recall, F1, balanced accuracy, coverage, soporte de positivos/negativos | Evaluar presencia/ausencia adjudicadas sobre casos aplicables; abstención y UNKNOWN requieren denominadores explícitos |

La evaluación futura debe ser **global y por familia/atributo**, con denominadores KNOWN, UNKNOWN, NOT_APPLICABLE y soporte explícitos. Un agregado alto no permite ocultar una familia con escasos ejemplos, baja cobertura o frontera inestable. Selective error debe leerse junto con cobertura; abstenerse en casi todo no acredita una solución útil.

Los gates cualitativos de avance son: definiciones y alias adjudicados por Mati; vocabularios y aplicabilidad consistentes; guía suficiente para acuerdo entre anotadores; procedencia/derechos revisados por componente de dataset; separación de evaluación sin fuga; análisis de errores y cobertura por familia; validación de unidades y evidencias. Cada futura promoción a entrenamiento o integración necesitará umbrales y criterio aprobados sobre datos reales. No se inventan porcentajes o cifras de promoción en esta fase.

Un freeze de dominio, una aprobación de anotación y una promoción de modelo son decisiones diferentes. El borrador mantiene las tres abiertas y preserva las autoridades actuales de catálogo comercial, colorimetría, clasificación FULL IMAGE, embedding FULL IMAGE y RAG.

## Incorporación controlada de puños y mappings comerciales

`attribute.sleeve_cuff_type` es un campo multilabel opcional. Incluye cuatro valores candidatos sujetos a revisión técnica y de Mati. Cada etiqueta futura debe localizar el extremo de la manga y registrar evidencia de puño diferenciado; un dobladillo de pantalón o brazalete es un contraejemplo. Sleeve_length KNOWN sleeveless impone NOT_APPLICABLE. Ausencia confirmada de puño diferenciado (`sleeve_cuff_present=false` con evidencia) también permite NOT_APPLICABLE; puño oculto exige UNKNOWN. Una banda acanalada puede sustentarse visualmente, mientras elástico incorporado y composición requieren sus propias evidencias.

La guía de puños permanece propuesta, `trainable_visual=no` y `future_head=null`; no se autoriza un especialista exclusivo ni entrenamiento. La posible coexistencia ribbed/elasticated se valida por componente y evidencia, sin convertir elasticidad aparente en fibra o elemento elástico confirmado. No se añade una etiqueta de puño a todo un conjunto cuando cada pieza puede tener detalles distintos.

Un mapping comercial aporta búsqueda y contexto, nunca verdad automática de entrenamiento. POLERA→tshirt es interpretación comercial aprobada del ID11; productos históricos con evidencia discrepante conservan su categoría y una identidad ontológica verificable o UNKNOWN/candidatos. Ninguna decisión de mapping aprueba anotaciones, dataset, modelo entrenado, modelo evaluado o producción. Las seis fases mantienen aprobaciones separadas.
