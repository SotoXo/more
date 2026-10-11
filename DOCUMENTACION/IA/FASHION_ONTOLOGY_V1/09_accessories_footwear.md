# 09 · Complementos y calzado

Estado: `DRAFT_FOR_REVIEW`, versión propuesta `1.1.0`; corte 2026-10-08 (`America/La_Paz`). Las siete familias de este documento contienen **24 categorías y 7 subcategorías** del borrador. La identidad está en [ontology.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/ontology.json); formas, cierres y rasgos específicos en [attribute_values.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/attribute_values.json); límites en [attribute_applicability.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/attribute_applicability.json).

Los complementos tienen familias propias. `garment.family.accessory` contiene cinturón, reloj, guantes y pañuelo de bolsillo; no absorbe bag, footwear, jewelry, headwear, neckwear o eyewear. La decisión humana 3 delimita el rótulo comercial ACCESORIOS ID9 a bolsos, mochilas, cinturones, gafas, relojes, joyería, bufandas, pañuelos, gorras, sombreros y guantes. Ese ámbito aprobado no constituye mapping directo a la familia técnica accessory; ejemplos y fronteras locales siguen pendientes de revisión.

## Calzado: identidad, contorno y elevación

El calzado queda explícitamente fuera de ACCESORIOS. Conserva familia ontológica independiente y su categoría comercial futura permanece pendiente; no se crea ni modifica ninguna categoría en Laravel. La falta de ejemplos históricos no acredita capacidad de entrenamiento. `garment.family.footwear` tiene seis categorías propuestas: sneaker, boot, pump, loafer, sandal y flat_shoe. La identidad sneaker no certifica desempeño deportivo; loafer no determina material o adorno; sandal no impone altura de tacón. Flat_shoe es una identidad conocida residual, no una salida UNKNOWN. Las dos subcategorías actuales, ankle_boot y knee_boot, pertenecen a boot.

| Atributo | Qué separa | Evidencia necesaria |
|---|---|---|
| `attribute.heel_type` | Tipo de tacón: plano, bloque, fino o cuña | Vista suficiente de la construcción; no inferir por ocasión |
| `attribute.heel_height` | Altura medida en **mm** | Medida declarada o protocolo calibrado; foto sin escala no aporta exactitud |
| `attribute.toe_shape` | Contorno frontal redondo, puntiagudo o cuadrado | Vista que permita reconocer la geometría |
| `attribute.toe_open` | Abertura frontal que descubre dedos | Booleano independiente del contorno; una puntera oculta es UNKNOWN |
| `attribute.shaft_height` | Altura de caña relativa al cuerpo | Se aplica sólo a boot; perspectiva y referencia importan |
| `attribute.sole_type` | Rasgos visibles de suela, como plataforma o relieve dentado | No confirma adherencia, comodidad ni propiedades técnicas |

Un pump con abertura frontal puede conservar identidad pump y tener toe_open KNOWN true. Una sandalia con tacón puede ser sandal + heel_type block; no se convierte en pump únicamente por elevación. Plataforma y tacón son rasgos diferentes; la altura del tacón no se suma automáticamente a plataforma ni equivale a desnivel efectivo sin protocolo de medida. La escala y las unidades son partes del dato de producto, coherentes conceptualmente con [GS1 Web Vocabulary](https://www.gs1.org/gs1-web-vocabulary).

Una bota puede tener caña de tobillo, pantorrilla, rodilla o sobre rodilla según evidencia. Sólo ankle_boot y knee_boot existen como subcategorías en este corte; no inventar una subcategoría para cada valor de shaft_height. Si la vista no sostiene el nivel preciso, la categoría boot puede estar KNOWN mientras shaft_height sigue UNKNOWN.

El material se localiza por componente: zona exterior/upper, forro, suela, plantilla, cordones o herrajes, según evidencia y alcance. Esos nombres sirven de contexto de revisión; no amplían por sí mismos los componentes de fiber_composition establecidos en ontology.json. Apariencia cuero, tejido elástico o suela dentada no demuestran cuero auténtico, porcentaje de elastano, impermeabilidad o agarre certificado.

## Bolsos: tipología frente a transporte

`garment.family.bag` tiene handbag, backpack, waist_bag y travel_bag. Tote_bag y clutch_bag son subcategorías de handbag. Bag_shape describe contorno; carry_method describe porte; strap_type describe construcción de correa. Una mochila llevada de un asa conserva backpack y puede observarse carried_at hand; una riñonera cruzada conserva waist_bag. La forma rectangular no constituye una nueva identidad de bolso.

| Rasgo | Caso | Decisión |
|---|---|---|
| Carry method multilabel | Bolso con asa y correa para hombro | Registrar métodos sustentados y distinguir posibilidad de diseño de porte observado |
| Strap type multilabel | Correa con ajuste y clips desmontables visibles | Adjustable y detachable pueden coexistir; material de la cadena requiere fuente aparte |
| Capacity continua | Mochila con ficha de volumen | Guardar dato en **L**, procedencia y método; no estimar litros por foto sin escala |
| Structure ordinal | Bolso mantiene o pierde contorno | Anotar soporte aparente y condición de carga, sin atribuir una composición rígida no documentada |
| Spatial semantics | Bolso en hombro/espalda/cintura | Usar `carried_at`, sin declarar que cubre el torso como prenda |

`value.strap_type.none` significa ausencia confirmada de correa; que no aparezca en una foto no confirma none. El contorno slouch depende de carga y disposición; una deformación transitoria no cambia automáticamente categoría o material. Travel_bag no identifica capacidad exacta ni reglas de equipaje de una aerolínea.

## Headwear y neckwear

Hat y cap mantienen identidades propias; beanie es subcategoría de hat pendiente de revisión terminológica local. Crown/brim podrían necesitar detalle especializado en una versión futura: hoy no hay atributos dedicados para esos rasgos. No reutilizar neckline, collar o frame_shape para llenar ese vacío.

Scarf y tie pertenecen a neckwear; bow_tie es subcategoría de tie. Tie y bow_tie conservan sus IDs ontológicos, pero corbatas no se agregan al ámbito comercial aprobado de ACCESORIOS en esta revisión. Un scarf puede llevarse en cabeza, cuello o bolso sin convertirse automáticamente en hat o bag. Se conserva la identidad y se anota ubicación/contexto acreditados. Un pocket_square tiene identidad de pañuelo de bolsillo en accessory; forma cuadrada o el nombre genérico pañuelo no resuelven por sí solos esa frontera. Mati debe adjudicar alias locales y ejemplos.

## Joyería

Necklace, bracelet, ring, earring y brooch son las cinco categorías. `attribute.jewelry_form` es multilabel y describe formas como chain, pendant, beaded, cuff, stud u hoop. Algunas tienen contexto de categoría en sus definiciones: cuff corresponde a bracelet; stud y hoop a earring. La matriz actual habilita jewelry_form por familia; la guía futura deberá concretar esas restricciones semánticas sin afirmar que ya existen reglas mecánicas por cada valor.

Una cadena con colgante puede tener dos formas acreditadas. Dorado y plateado observados son apariencia cromática, no composición precious metal. Un broche con vidrio y metal declarados puede tener materiales por componente; brillo no prueba gemas, aleación o valor. Tampoco se infiere marca/autenticidad por estilo.

El collar independiente necklace contacta o se lleva en cuello. El cuello collar de una prenda es su parte constructiva y pertenece a `attribute.collar`; no se etiqueta como una pieza jewelry adicional.

## Gafas: montura y lente

Glasses es categoría de eyewear; sunglasses es la subcategoría propuesta. El borrador ya incluye **`attribute.frame_shape`, `attribute.frame_style`, `attribute.lens_shape`, `attribute.lens_tint` y `attribute.frame_material`**. Frame_shape representa el contorno exterior de la montura; lens_shape el contorno de la lente; frame_style describe aro completo, parcial o al aire. No se sustituyen entre sí.

Lens_shape usa actualmente el dominio `frame_shape` como vocabulario compartido de seis formas. Esta reutilización no impone igualdad entre contorno de lente y montura. La guía y revisión de Mati deben validar la pertinencia de cada término para lentes —especialmente términos tipológicos como aviator— y decidir si se necesita un dominio propio en una versión posterior. El atributo ya existe; la geometría de referencia y los ejemplos de adjudicación siguen pendientes.

Una montura cat eye puede rodear una lente de contorno distinto. En gafas rimless puede verse la lente sin un aro exterior convencional: no copiar su geometría a frame_shape por comodidad; revisar cómo expresar el alcance de ese atributo o conservar UNKNOWN si la referencia no está definida. Lens_tint puede registrar tinte, gradiente y apariencia espejada con localización y luz. Clear no demuestra graduación; tinted no demuestra UV, polarización, receta o aptitud médica. Frame_material usa material documentado, distinto de apariencia de montura y del material de la lente.

## Reloj, cinturón, guantes y pañuelo de bolsillo

En accessory, `attribute.case_shape` y `attribute.watch_display` se restringen a watch. Display analógico, digital o híbrido es observable con vista adecuada; no acredita movimiento interno, conectividad, resistencia al agua o autenticidad. Strap_type también admite watch; no se atribuye a toda la familia accessory por arrastre.

`attribute.belt_width` se restringe a belt y se mide en **mm** con fuente o escala. Ancho aparente sin referencia no da una medida. Gloves y pocket_square usan atributos globales pertinentes y semántica espacial; no reciben mangas, perneras, caja de reloj o geometría de lente.

## Revisión y fuentes externas

ModaNet agrupa clases de complementos y prendas con fronteras diferentes; un mapping de bag puede conservar familia y perder tipología, mientras outer no tiene equivalencia directa. Google utiliza una clasificación comercial para Shopping; no define por sí sola identidad semántica ni atributos internos de MORE. Véanse [ModaNet, etiquetas](https://github.com/eBay/modanet#labels), [Google, categoría de producto](https://support.google.com/merchants/answer/6324436?hl=en) y [02_investigacion_fuentes.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/02_investigacion_fuentes.md).

La revisión de Mati debe centrarse en identidades residuales, nombres locales, variantes ausentes y criterios de geometría por componente. No se deduce compatibilidad de outfit ni incompatibilidad absoluta a partir de estas familias; zona, largo, holgura, material y ocasión pertenecen a decisiones contextualizadas.
