# 05 Â· Atributos por familia y condiciones de aplicabilidad

Estado: `DRAFT_FOR_REVIEW`, versiÃ³n propuesta `1.1.0`; corte 2026-10-08 (`America/La_Paz`). La fuente de verdad es [attribute_applicability.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/attribute_applicability.json): **69 atributos Ã— 12 familias = 828 filas**. Cada fila distingue `required`, `optional` o `not_applicable`, admisiÃ³n de UNKNOWN y `category_scope`. [coverage_matrix.csv](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/coverage_matrix.csv) es su vista tabular de revisiÃ³n.

La tabla siguiente explica la decisiÃ³n de dominio; no duplica las 828 filas. Los atributos globales reutilizables de material, color, patrÃ³n, construcciÃ³n y uso siguen las condiciones de componente y evidencia de [04_atributos_globales.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/04_atributos_globales.md). En todas las familias, pattern y spatial_semantics requieren slot/estado, sin obligar a un valor conocido.

## Familias de prendas

| Familia | Atributos que precisan su identidad | Frontera de anotaciÃ³n |
|---|---|---|
| `garment.family.upper` | Fit, silhouette, upper_length, neckline, sleeve_length, sleeve_shape, sleeve_attachment, sleeve_cuff_type, collar, hem, pleats, structure | Largo se refiere a prenda superior; cuello construido y escote son conceptos separados. No hay rise, leg_shape ni skirt_dress_length. |
| `garment.family.lower` | Fit, silhouette, rise, waist_type, hem, pleats; longitudes y pernera segÃºn categorÃ­a | Las faldas usan skirt_dress_length; prendas bifurcadas usan leg_shape, lower_length e inseam. No asignar mangas. |
| `garment.family.outerwear` | Fit, silhouette, outer_length, neckline, mangas, collar, hem, pleats, lapel, structure, insulation | Lapel y collar pueden coexistir. Insulation exige dato declarado; apariencia acolchada no certifica relleno ni prestaciÃ³n tÃ©rmica. |
| `garment.family.one_piece` | Fit, silhouette, waist_type, neckline, mangas, collar, hem, pleats, structure; longitud segÃºn vestido o prenda bifurcada | Dress usa skirt_dress_length y puede tener slit; jumpsuit/romper usan leg_shape, lower_length e inseam. Rise no se habilita para esta familia en el borrador. |
| `garment.family.multi_piece` | TaxonomÃ­a de conjunto y atributos globales sustentados; descomposiciÃ³n de componentes | No asignar un solo fit, escote, manga, pernera o largo al conjunto si difieren sus piezas. Etiquetar por componente y agregar sÃ³lo afirmaciones justificadas. |

**Upper.** Una camisa sin mangas conserva shirt si su identidad camisera estÃ¡ acreditada. Sleeve_length puede ser KNOWN sleeveless; sleeve_shape, sleeve_attachment y sleeve_cuff_type serÃ¡n NOT_APPLICABLE. Una foto que oculta las mangas exige UNKNOWN en la condiciÃ³n y en esos atributos dependientes. Un sweater_vest pertenece a sweater y sigue la misma lÃ³gica de mangas.

**Lower.** La forma wide leg describe pernera, no silhouette global ni fit. Rise describe la relaciÃ³n de altura de cintura de una prenda inferior con la referencia corporal; la fotografÃ­a debe mostrar la relaciÃ³n necesaria o existir dato de producto. Waist_type identifica construcciÃ³n de cintura, por ejemplo elÃ¡stico, sin demostrar rise. Inseam es una medida en **cm** y requiere medida documentada o protocolo calibrado; no se deduce de pÃ­xeles sin escala.

**Outerwear.** Un blazer puede tener outer_length largo y seguir siendo blazer. Collar, lapel y neckline no son alias. La estructura aparente es ordinal; insulation es informaciÃ³n estructurada no visual. Un abrigo de paÃ±o y una chaqueta acolchada pueden requerir atributos diferentes por componente, sin asignarles automÃ¡ticamente invierno, frÃ­o extremo o waterproof.

**One piece.** La costura de cintura de un vestido no permite tratarlo como upper + lower independientes. Se anotan sus regiones cuando hace falta localizar un detalle, conservando la unidad de producto. Una abertura slit de falda/vestido no se confunde con la separaciÃ³n de dos perneras. Para jumpsuit/romper, una pierna oculta no prueba ausencia o simetrÃ­a de detalles.

**Multi piece.** Un conjunto shirt + trousers necesita etiquetas completas de ambas instancias, relacionadas con la entidad coordinated_set. Si sÃ³lo aparece la pieza superior, se registra cobertura parcial y evidencia comercial del conjunto; no se propagan sus mangas o composiciÃ³n al pantalÃ³n ausente. Un patrÃ³n compartido puede agregarse Ãºnicamente si ambas piezas estÃ¡n sustentadas. El matching visual no demuestra que sean un producto Ãºnico.

## Familias de complementos

| Familia | Especificidad actual | DecisiÃ³n prÃ¡ctica |
|---|---|---|
| `garment.family.footwear` | Heel_height, heel_type, toe_shape, toe_open, shaft_height, sole_type | Toe_shape y toe_open son ejes diferentes. Shaft_height se restringe a boot. Materiales se localizan en upper/lining/sole u otros componentes sustentados. |
| `garment.family.bag` | Carry_method, strap_type, bag_shape, capacity, structure | Transportar a mano o cruzado es contextual. Capacidad se documenta en **L**; silueta garment no sustituye bag_shape. |
| `garment.family.headwear` | Atributos globales y spatial_semantics | No tiene una cabeza especÃ­fica de crown/brim en el borrador. Esas necesidades pueden anotarse como revisiÃ³n de dominio, sin inventar IDs ni usar collar. |
| `garment.family.neckwear` | Atributos globales y spatial_semantics | Scarf y tie son objetos independientes. El cuello cosido a una prenda sigue siendo attribute.collar de esa prenda. |
| `garment.family.jewelry` | Jewelry_form y atributos globales pertinentes | La forma ornamental no determina composiciÃ³n, autenticidad, aleaciÃ³n ni valor. No usar frame_material para joyerÃ­a. |
| `garment.family.eyewear` | Frame_shape, frame_style, lens_shape, lens_tint, frame_material | Montura y lente se anotan como geometrÃ­as distintas; material requiere documentaciÃ³n. |
| `garment.family.accessory` | Case_shape y watch_display para watch; belt_width para belt; strap_type para watch | Restricciones de categorÃ­a evitan atributos de reloj en guantes/cinturÃ³n o ancho de cinturÃ³n en paÃ±uelo de bolsillo. |

VÃ©ase [09_accessories_footwear.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/09_accessories_footwear.md) para fronteras y ejemplos. La distinciÃ³n entre dimensiÃ³n, unidad, componente e identidad es compatible con el enfoque de atributos de producto de [GS1 Web Vocabulary](https://www.gs1.org/gs1-web-vocabulary); el modelo concreto de MORE no se importa de GS1.

## Restricciones de categorÃ­a que vencen al permiso familiar

| Atributos | CategorÃ­as habilitadas en el corte actual |
|---|---|
| `attribute.leg_shape`, `attribute.inseam`, `attribute.lower_length` | Trousers, jeans, shorts, leggings, jumpsuit, romper |
| `attribute.skirt_dress_length`, `attribute.slit` | Skirt, dress |
| `attribute.shaft_height` | Boot |
| `attribute.strap_type` | Handbag, backpack, waist_bag, travel_bag, watch |
| `attribute.case_shape`, `attribute.watch_display` | Watch |
| `attribute.belt_width` | Belt |

Las listas exactas usan IDs `garment.category.*` en el SSOT. Si una familia permite un atributo pero la categorÃ­a queda fuera de `category_scope`, el valor es NOT_APPLICABLE para esa categorÃ­a. Si la categorÃ­a es UNKNOWN, no se resuelve la restricciÃ³n inventando identidad: conservar la incertidumbre y los candidatos. La matriz familiar no acredita por sÃ­ sola mangas, componentes textiles, relleno o medidas.

## Aplicabilidad contextual por componente

Una chaqueta con exterior woven y forro knitted puede tener weave KNOWN para el exterior y NOT_APPLICABLE para el forro; knit_structure se decide al revÃ©s. Si el mÃ©todo del forro es desconocido, ambos dependientes se mantienen UNKNOWN. Una mochila de material no textil con cinta tejida admite fiber_composition para la cinta si estÃ¡ documentada, pero no convierte toda la mochila en textil.

Las reglas explÃ­citas incluyen weave condicionado a woven, knit_structure condicionado a knitted y los tres detalles sleeve_shape, sleeve_attachment y sleeve_cuff_type condicionados a no ser sleeveless. AdemÃ¡s, fiber_composition, textile_structure, weave, knit_structure, fabric_family y opacity requieren contexto de presencia de componente textil; non_textile_material requiere el contexto anÃ¡logo no textil. La familia sÃ³lo establece aplicabilidad potencial. Presencia acreditada permite valorar el atributo; ausencia documentada da NOT_APPLICABLE; contexto desconocido da UNKNOWN. Una ausencia de tejido en una foto no constituye ausencia documentada del componente.

Estos contratos tambiÃ©n aparecen en `value_constraints.component_presence_context` de ontology.json. La guÃ­a futura debe precisar cÃ³mo se identifica el componente y cÃ³mo se acredita su ausencia: por ejemplo, anillo puramente metÃ¡lico documentado frente a una joya cuya construcciÃ³n no estÃ¡ visible. La declaraciÃ³n del contrato no equivale a haber validado anotaciones o instalado un motor en producciÃ³n.

## RevisiÃ³n local y discrepancias

La auditorÃ­a debe comparar definiciÃ³n, fila familiar, category_scope, condiciÃ³n y vocabulario. Un atributo puede ser visualmente identificable y aun asÃ­ UNKNOWN por falta de vistas. Puede ser pertinente y no entrenable visualmente, como una capacidad o la composiciÃ³n. Si una necesidad de Mati no encuentra un atributo exacto â€”por ejemplo medidas de cabeza, propiedades Ã³pticas certificadas o mecanismos de relojâ€” registrar propuesta y evidencia en revisiÃ³n; no sobrecargar un atributo vecino.

Estos lÃ­mites son propuestas versionadas. La aplicabilidad de cada caso y la geometrÃ­a de referencia de las longitudes necesitan ejemplos y contraejemplos aprobados antes de construir un dataset. No se asignan incompatibilidades fashion absolutas a partir de la pertenencia de familia.

## Vocabulario inicial de puÃ±os de manga

La decisiÃ³n humana 10 aprueba incorporar `attribute.sleeve_cuff_type`; no aprueba automÃ¡ticamente cada tÃ©rmino tÃ©cnico. Los cuatro IDs siguientes tienen `NEEDS MATI REVIEW`, traducciÃ³n, ejemplos, fuentes y familias aplicables/no aplicables en attribute_values.json. Upper, outerwear y one_piece admiten el atributo; las otras nueve familias lo tienen no aplicable. En multi_piece se anota cada pieza con su familia, sin agregar un Ãºnico puÃ±o a todo el conjunto.

| ID estable | EspaÃ±ol / inglÃ©s | Criterio observable y evidencia | Ejemplo positivo | Contraejemplo |
|---|---|---|---|---|
| `value.sleeve_cuff_type.button` | PuÃ±o abotonado / Button cuff | PuÃ±o diferenciado con botÃ³n y ojal funcionales o ficha exacta | PuÃ±o simple de camisa con cierre observado | BotÃ³n ornamental, botÃ³n frontal o botÃ³n de abertura encima del puÃ±o |
| `value.sleeve_cuff_type.ribbed` | PuÃ±o acanalado / Ribbed cuff | Banda de puÃ±o con construcciÃ³n/relieve de punto acanalado acreditados | PuÃ±o localizado de sudadera con banda acanalada | Bajo acanalado de prenda, pernera o rayas impresas |
| `value.sleeve_cuff_type.elasticated` | PuÃ±o con elÃ¡stico / Elasticated cuff | ElÃ¡stico incorporado sustentado por detalle, documentaciÃ³n exacta o revisiÃ³n humana | PuÃ±o de chaqueta con elemento elÃ¡stico documentado | RecuperaciÃ³n del punto sin evidencia de elÃ¡stico incorporado; cinturilla |
| `value.sleeve_cuff_type.french` | PuÃ±o francÃ©s / doble / French / double cuff | ConstrucciÃ³n doble doblada con ojales/cierre para gemelos acreditados | PuÃ±o doble de camisa con vÃ­nculo/gemelos | Manga enrollada, tejido French terry, brazalete o puÃ±o simple de botÃ³n |

Las referencias tÃ©cnicas primarias, consultadas el 2026-10-08, son [Charles Tyrwhitt, puÃ±os de botÃ³n y franceses](https://www.charlestyrwhitt.com/us/ttip-formal-shirt-cuff.html), [su guÃ­a de construcciÃ³n de puÃ±os](https://www.charlestyrwhitt.com/us/editorial-style-tips/about-our-cuffs.html), [Nike, sudadera con puÃ±os y bajo diferenciados](https://www.nike.com/t/standard-issue-mens-dri-fit-basketball-crew-neck-sweatshirt-p7VpWc) y [Patagonia, chaqueta con puÃ±os elÃ¡sticos](https://www.patagonia.com.hk/products/mens-lightweight-all-wear-unlined-jacket-20465-blk). Respaldan candidatos tÃ©cnicos y no prueban atributos de un producto fÃ­sico de Mati. Las formas de borde redondo/cortado y el nÃºmero de botones quedan como variantes documentadas, sin crear IDs adicionales en este corte.

El atributo es multilabel por componente de manga: ribbed y elasticated pueden coexistir cuando hay evidencia independiente de ambos. Ribbed no implica elastano ni elÃ¡stico incorporado. French y simple button describen construcciones diferentes; una propuesta de ambos en el mismo puÃ±o requiere revisiÃ³n, por ejemplo para un posible puÃ±o convertible aÃºn fuera del vocabulario. No resolver combinaciones por un score.

| CondiciÃ³n de instancia | Estado del atributo | Evidencia y lÃ­mite |
|---|---|---|
| Ausencia de mangas confirmada | NOT_APPLICABLE | Sleeve_length KNOWN sleeveless con evidencia |
| Manga existente sin puÃ±o diferenciado confirmado | NOT_APPLICABLE | `assertion.context.sleeve_cuff_present=false` y evidencia de terminaciÃ³n simple; no confundir con puÃ±o oculto |
| Manga con puÃ±o diferenciado confirmado | KNOWN si tipo sustentado; UNKNOWN si ambiguo | `sleeve_cuff_present=true` y referencia de componente/fuente |
| PuÃ±o fuera del encuadre, borroso o posible sin evidencia | UNKNOWN | Ausencia de observaciÃ³n no demuestra ausencia fÃ­sica |

Las reglas del SSOT son `sleeve_length!=sleeveless` y `assertion.context.sleeve_cuff_present=true`; condiciÃ³n falsa da NOT_APPLICABLE y desconocida da UNKNOWN, conservando el refinamiento por familia/categorÃ­a. El slot opcional omitido no obliga a introducir contexto de puÃ±o en documentos histÃ³ricos. `future_head=null` y `trainable_visual=no` dejan explÃ­cita la ausencia de autorizaciÃ³n para especialista de puÃ±os; la observabilidad parcial no constituye dataset ni permiso de entrenamiento.

La comprobación de mangas ausentes no exige conocer su longitud para describir un puño visible: un puño acreditado establece presencia de manga aunque sleeve_length siga UNKNOWN. La condición excluye sleeveless confirmado; la falta de etiqueta de longitud no anula evidencia independiente del puño.

La comprobación de mangas ausentes no exige conocer su longitud para describir un puño visible: un puño acreditado establece presencia de manga aunque sleeve_length siga UNKNOWN. La condición excluye sleeveless confirmado; la falta de etiqueta de longitud no anula evidencia independiente del puño.
