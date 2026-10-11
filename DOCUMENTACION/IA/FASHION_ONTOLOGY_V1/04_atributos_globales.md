# 04 · Atributos globales y estados de valor

Estado: `DRAFT_FOR_REVIEW`, versión propuesta `1.1.0`; corte 2026-10-08 (`America/La_Paz`). [ontology.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/ontology.json) contiene **69 atributos**. Global significa reusable entre familias; la existencia de un atributo no obliga a conocerlo ni a aplicarlo a todos los componentes de cada objeto.

La especificación exacta de cada atributo incluye ID, definición, tipo de valor, vocabulario, familias, categorías restringidas, requerimiento, admisión de unknown, fuentes de anotación, dificultad y learnability. [attribute_applicability.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/attribute_applicability.json) tiene **828 filas** de atributo × familia; [label_schema.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/label_schema.json) define su contrato semántico para un futuro esquema de etiquetas. No hay anotación ni entrenamiento implementados en esta fase.

## Estado y valor son decisiones diferentes

| Estado | Significado | Ejemplo | Tratamiento futuro |
|---|---|---|---|
| `KNOWN` | El atributo aplica y su valor está sustentado | Una camisa con mangas claramente largas | Evaluar el valor con su evidencia y procedencia |
| `UNKNOWN` | Puede aplicar, pero falta evidencia suficiente | Composición de fibra sin etiqueta legible | Registrar motivo; no convertir en negativo ni rellenar por plausibilidad |
| `NOT_APPLICABLE` | El concepto no corresponde a este objeto o componente bajo las reglas aplicables | Pernera de una falda; ligamento de calada en componente de punto confirmado | Excluir del objetivo de valor y validar la decisión de aplicabilidad |

Oculto, borroso, fuera del encuadre y contradictorio son motivos de UNKNOWN. No constituyen prueba de ausencia. Un booleano KNOWN false exige ausencia adjudicada del rasgo aplicable; no equivale a UNKNOWN. Un conjunto multilabel incompleto tampoco establece que las etiquetas no mencionadas sean falsas.

`attribute.pattern` y `attribute.spatial_semantics` tienen `requirement=required`; debe existir el slot con su estado. El resto de los atributos es opcional en el borrador actual. Required no significa forzar KNOWN. Un valor UNKNOWN declarado no autoriza inventar patrón ni cobertura corporal. La ausencia de un atributo opcional en una anotación futura deberá distinguirse de la decisión explícita NOT_APPLICABLE.

## Capas semánticas reusables

| Grupo | Atributos principales | Uso correcto |
|---|---|---|
| Apariencia cromática | `attribute.observed_color`, `attribute.color_family`, `attribute.color_role`, `attribute.perceived_temperature` | Distinguir medición o paleta observada, familia descriptiva y función contextual del color. Temperatura percibida no mide clima ni aislamiento. |
| Motivo y superficie | `attribute.pattern`, `attribute.surface_texture`, `attribute.finish`, `attribute.opacity` | Identificar motivos y relieve por región/componente. Separar acabado confirmado de apariencia y documentar condiciones de luz para opacidad. |
| Material y estructura textil | `attribute.fiber_composition`, `attribute.non_textile_material`, `attribute.textile_structure`, `attribute.weave`, `attribute.knit_structure`, `attribute.fabric_family` | Anotar con componente y evidencia; no tratar algodón, denim, satén y acanalado como valores del mismo dominio. |
| Construcción y volumen | `attribute.construction`, `attribute.closure`, `attribute.fit`, `attribute.silhouette`, `attribute.cut_technique`, `attribute.structure` | Separar mecanismo funcional, detalle de fabricación, holgura de diseño y contorno. Construction y closure son reusables; fit y silhouette tienen familias limitadas. |
| Afinidad y uso | `attribute.styles`, `attribute.aesthetics`, `attribute.dress_codes`, `attribute.formality`, `attribute.occasions`, `attribute.calendar_seasons`, `attribute.climate_suitability` | Añadir contexto y procedencia; no deducir ocasión, tendencia o propiedades técnicas por categoría. |
| Público de catálogo | `attribute.target_gender`, `attribute.target_age` | Registrar el público comercial documentado. No inferir identidad, edad o género de la persona fotografiada. |
| Espacio y composición | `attribute.spatial_semantics`, `attribute.layering` | Distinguir cobertura, contacto y transporte; representar capas entre instancias y por zona/contexto. |

Los valores y sus definiciones viven en los vocabularios enlazados por `vocabulary_ref`. `color_semantics.json` tiene 18 conceptos, `styles.json` 14 y `usage.json` 31 en este corte. Los atributos específicos se explican en [05_atributos_por_familia.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/05_atributos_por_familia.md); los dominios textiles en [08_materiales_texturas_patrones.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/08_materiales_texturas_patrones.md).

## Fit, silueta y construcción

`attribute.fit` es single label y describe holgura de diseño frente a un cuerpo o forma de referencia; no es talla. `attribute.silhouette` es multilabel y describe contorno global. `attribute.leg_shape` describe exclusivamente la forma de la pernera. Un pantalón puede tener fit contenido en cintura y cadera con pierna ancha; una foto en percha puede sostener leg_shape y no sostener fit.

`attribute.structure` es ordinal y representa soporte estructural aparente en upper, outerwear, one_piece y bag. No equivale a textile_structure ni a insulation. `attribute.cut_technique` exige evidencia constructiva: caída diagonal aparente no prueba corte al bies. La separación entre perfil, construcción y cuerpo de referencia se apoya conceptualmente en [V&A, Bar Suit](https://www.vam.ac.uk/articles/fashion-unpicked-bar-suit-dior); los criterios de MORE son una propuesta de anotación que necesita adjudicación local.

Closure registra cierres funcionales; un botón ornamental no demuestra abotonado funcional. Pullover describe una manera de ponerse una prenda, no un nuevo mecanismo de cierre. Una prenda puede tener más de un cierre, por eso el dominio es multilabel.

## Style, aesthetic, dress code, ocasión y formalidad

MORE propone `styles` como afinidades de lenguaje de diseño, `aesthetics` como referencias culturales o temporales y `dress_codes` como expectativas de vestimenta de un contexto. Son ejes operativos diferentes; no existe una separación universal de esos términos en todas las publicaciones de moda. Los museos FIT documentan la evolución y reinterpretación del estilo; los códigos de vestimenta de Emily Post dependen del contexto. Véanse [FIT, Hip-Hop Style](https://www.fitnyc.edu/museum/exhibitions/hip-hop-style/), [FIT, Minimalism/Maximalism](https://www.fitnyc.edu/museum/exhibitions/minimalism-maximalism.php) y [Emily Post, dress codes](https://emilypost.com/advice/attire-guide-dress-codes-from-casual-to-white-tie).

Cada afinidad multilabel se valora independientemente. Una pieza puede mostrar rasgos clásicos y minimalistas; no se exige que sus confidencias sumen 1. No se convierte old money en clase social de su comprador, vintage en antigüedad certificada ni utility en aptitud profesional. DeepFashion2 usa su campo `style` para variantes de recuperación de producto; ese campo tiene `NO_DIRECT_MAPPING` a estas afinidades. Véase [DeepFashion2, definición del campo](https://github.com/switchablenorms/DeepFashion2#data-organization).

Formality es un orden operativo de cinco niveles, con rangos 0–4 en `usage.json`; no asume distancias iguales entre niveles ni equivale automáticamente a un dress code. Una ocasión es un contexto de actividad o evento; evening describe un momento y no constituye por sí solo una ocasión. Estación del calendario requiere geografía/hemisferio y no se fusiona con temporada comercial ni con adecuación climática.

## Aplicabilidad por componente y evidencia

La matriz familiar habilita un espacio de atributos; la categoría y el componente refinan la decisión. Una chaqueta puede tener exterior woven, forro knitted y botones metálicos. Se decide weave por el exterior, knit_structure por el forro y material no textil por los botones, sin extender un dato a todo el producto. Un reloj con correa textil puede tener atributos textiles en esa correa aunque el reloj sea accessory. Un anillo metálico sin componente textil no necesita una fibra UNKNOWN: la fibra de ese componente es NOT_APPLICABLE.

El contrato declara presencia contextual de componente textil para fiber_composition, textile_structure, weave, knit_structure, fabric_family y opacity; declara presencia de componente no textil para non_textile_material. Sus `value_constraints.component_presence_context` y las reglas de aplicabilidad distinguen presencia confirmada, ausencia confirmada y presencia desconocida. Sólo la ausencia documentada habilita NOT_APPLICABLE: un anillo de metal documentado sin componentes textiles puede excluir fibra; una vista que no muestra su construcción no puede hacerlo. Si la presencia es desconocida, el atributo sigue UNKNOWN.

Además, weave requiere woven; knit_structure requiere knitted; sleeve_shape, sleeve_attachment y sleeve_cuff_type requieren presencia de mangas; sleeve_cuff_type exige además presencia de un puño diferenciado. Cuando no se conoce la condición, el estado dependiente sigue UNKNOWN, no NOT_APPLICABLE. La guía futura debe precisar selección de componente, evidencia y conflictos. Los contratos están declarados en los artefactos; su aplicación sobre instancias anotadas y su evaluación corresponden a una fase posterior.

## Autoridades preservadas

Observed color conserva el ámbito de la colorimetría existente: puede referenciar su salida y versión y el subconjunto disponible de RGB, hex, Lab y percentage. No introduce otro cálculo de color ni obliga a producir todos esos campos. Una familia descriptiva fashion no reemplaza colores comerciales ni genera remapeos en Laravel. Spatial semantics y layering son semántica conceptual: no autorizan cambiar la segmentación, clasificación FULL IMAGE o embedding FULL IMAGE. Materiales, afinidades y uso requieren procedencia; las explicaciones contextualizadas pertenecen a la capa de conocimiento, mientras las observaciones de tendencia tienen fecha y ámbito externos a la taxonomía estable.

## Medidas, talla comercial y ajuste personal: decisión 9

La ontología conserva silueta, fit de diseño, forma de pierna, tiro, largo de manga y largos de prenda como atributos separados. Entrepierna es una medida real con unidad; su valor no nace de la etiqueta wide_leg. La talla comercial sigue en Laravel y en sus variantes; el ajuste de la prenda sobre una persona pertenece a un modelo futuro independiente. No se añade una segunda talla comercial dentro de la ontología.

| Dato | Evidencia admisible | Caso que permanece desconocido |
|---|---|---|
| Silueta / forma de pierna | Vista suficiente, contorno y referencia de disposición | Contorno deformado por pose, pinzas u oclusión |
| Fit de diseño | Ficha de referencia/variante exactas o holgura observable respecto de referencia corporal | Foto en percha sin referencia; talla mayor por sí sola |
| Tiro | Cintura y referencia corporal verificables o ficha exacta | Cintura fuera de cuadro o prenda sin referencia |
| Largo de manga / prenda | Extremos visibles con referencias anatómicas y perspectiva documentadas | Bordes ocultos o geometría de referencia insuficiente |
| Entrepierna y otras medidas reales | Medición humana con protocolo/unidad o ficha exacta que distinga medida de prenda de medida corporal | Imagen sin escala; tabla genérica sin modelo/variante |
| Talla comercial | Etiqueta legible, identificación exacta de variante y revisión frente a Laravel | Apariencia del cuerpo o de una prenda similar |
| Ajuste personal | Futuro modelo con medidas personales, medidas de prenda, preferencias y evaluación propia | Fit de diseño o talla comercial solos no establecen ajuste individual |

Como respaldo técnico externo consultado el 2026-10-08, [Patagonia, Size & Fit](https://eu.patagonia.com/gb/en/guides/size-fit/mens/) diferencia tablas corporales y tipos de fit; [Charles Tyrwhitt, guías de medidas](https://www.charlestyrwhitt.com/us/sizing-guides) separa cuerpo, camisa y variantes de fit. [Levi’s Secondhand, medición](https://secondhand.levi.com/pages/size-guide) describe entrepierna de una prenda extendida desde la unión de entrepierna al bajo. MORE toma estas distinciones como apoyo del contrato; no importa equivalencias universales entre marcas ni instrucciones de recomendación.

Un registro futuro de medidas debe conservar nombre de dimensión, valor, unidad, método, cuerpo/prenda como sujeto, modelo/variante, fuente, fecha y evidencia; UNKNOWN cuando no existan. Cualquier conversión de unidades debe retener el valor original. Tabla de tallas, medida real y talla disponible no son el mismo dato. Esta documentación define su separación y deja la recomendación personal fuera de alcance.

## Puños de manga: decisión 10

`attribute.sleeve_cuff_type` incorpora un contrato específico; sus cuatro valores iniciales siguen pendientes de aprobación. Se aplica a upper, outerwear y one_piece cuando hay manga y puño diferenciado acreditados. Un bajo de pantalón, brazalete, cierre frontal, dobladillo simple o longitud de manga no es un tipo de puño. Véase la definición y vocabulario técnico en [05_atributos_por_familia.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/05_atributos_por_familia.md). La existencia del atributo no autoriza entrenar un especialista exclusivo.
