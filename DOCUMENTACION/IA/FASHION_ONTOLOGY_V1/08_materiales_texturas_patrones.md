# 08 · Materiales, estructuras textiles, texturas y patrones

Estado: `DRAFT_FOR_REVIEW`, versión propuesta `1.1.0`; revisión 2026-10-08 (`America/La_Paz`). Los vocabularios son propios de MORE, con conceptos candidatos a revisión: [materials.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/materials.json) contiene **20 conceptos**, [fabrics.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/fabrics.json) **15**, [textures.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/textures.json) **12** y [patterns.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/patterns.json) **13**. No se copian aquí sus listas completas.

## Capas que responden preguntas diferentes

| Capa | Pregunta | Atributo/dominio | Evidencia y límite |
|---|---|---|---|
| Fibra | ¿De qué fibras está hecho este componente textil? | `attribute.fiber_composition`; dominio `fiber` | Etiqueta, ficha o análisis. Una fotografía no confirma porcentajes. |
| Material no textil | ¿Qué material confirmado forma este componente? | `attribute.non_textile_material` | Catálogo o etiqueta; brillo/color no certifican metal, cuero, vidrio ni polímero específico. |
| Método estructural | ¿Cómo se organiza el componente textil? | `attribute.textile_structure` | Woven, knitted o nonwoven; requiere detalle suficiente o documentación. |
| Ligamento | ¿Cuál es la estructura de urdimbre/trama? | `attribute.weave` | Sólo en componente woven: plain, twill o satin candidatos. |
| Estructura de punto | ¿Qué organización de bucles tiene? | `attribute.knit_structure` | Sólo en componente knitted: jersey, rib o interlock candidatos. |
| Familia textil | ¿Qué identidad textil reconocible tiene? | `attribute.fabric_family` | Denim, encaje o terciopelo describen familias, sin resolver automáticamente fibra o método. |
| Apariencia superficial | ¿Qué relieve o pelo es observable? | `attribute.surface_texture` | Acanalado, velloso o granulado se observan sin identificar necesariamente la fabricación. |
| Acabado | ¿Qué tratamiento o efecto de diseño está acreditado? | `attribute.finish` | Procesos requieren documentación; una apariencia distressed puede anotarse como tal sin afirmar desgaste real. |
| Motivo | ¿Qué organización visual de elementos aparece? | `attribute.pattern` | Rayas, floral o cuadros por región; no exige demostrar impresión, tejido o bordado. |
| Opacidad aparente | ¿Qué transmisión visual se aprecia? | `attribute.opacity` | Orden relativo dependiente de luz, fondo, capas y vista; no mide prestación óptica. |

La terminología profesional distingue ligamentos básicos, estructura y nombres de textiles: [The Weavers' Company, glosario](https://www.weavers.org.uk/textiles/resources/glossary-of-weaving-terms/) y [Association for Contract Textiles, glosario](https://contracttextiles.org/learn/contract-textile-glossary/). MORE conserva esa separación y añade estados de evidencia; las fuentes no establecen el contrato KNOWN/UNKNOWN/NOT_APPLICABLE de MORE.

## Composición por componente

`materials.json` separa **12 fibras** y **8 materiales no textiles**. Fiber composition es structured: cada afirmación necesita componente, fibra declarada, proporción cuando se conoce y procedencia. Sus componentes permitidos actuales son `shell`, `lining`, `trim` y `fill`; admite composición parcial. Exterior, forro, relleno y ribete no se mezclan en una lista que aparente describir el producto completo. No promediar exterior y forro; no convertir una composición parcial en 100% ni completar una fibra ausente con una suposición. Correa, suela, herraje u otros alcances requieren conservar su contexto y revisar su correspondencia con el contrato, sin añadir IDs de componente informalmente.

La guía textil de la FTC muestra que nombres genéricos de fibra y porcentajes en peso son información de etiquetado, y contempla componentes diferenciados. Se utiliza como referencia del significado del dato, sin declarar que la normativa estadounidense aplique al comercio boliviano. Véase [FTC, guía de etiquetado textil](https://www.ftc.gov/business-guidance/resources/threading-your-way-through-labeling-requirements-under-textile-wool-acts).

El aspecto lanoso puede sostener surface_texture fuzzy; no demuestra wool, alpaca ni cashmere. Arrugas no demuestran linen. Apariencia brillante no demuestra silk. Cuero aparente no decide leather frente a synthetic_leather. Un acabado dorado no acredita oro, ley, aleación, autenticidad o valor.

`value.non_textile_material.acetate` representa material confirmado de montura; no introduce por sí solo una fibra textil de acetato. Si Mati necesita esa fibra, se revisará como concepto distinto. Polymer no identifica una resina concreta. Los alias de marcas comerciales no deben sustituir automáticamente nombres genéricos de fibra.

## Estructura, familia y prenda

Knitted no significa sweater; jersey de punto no significa necesariamente la prenda regional llamada jersey. Una camiseta de punto conserva categoría tshirt; un vestido de punto conserva dress. Por eso no existe knit_top como categoría. Woven no impone formalidad ni knitted informalidad.

Denim es una familia textil asociada comúnmente a construcción de sarga; no significa algodón 100% ni jeans. La categoría jeans exige identidad de prenda. Satin es un ligamento y no equivale a silk. CottonWorks explica las bases constructivas del denim y del tejido; se ha utilizado para comprensión conceptual, sin incorporar sus diagramas o contenidos sujetos a restricciones de uso. Véanse [CottonWorks, Denim Basics](https://cottonworks.com/learning-hub/denim/denim-basics/) y [CottonWorks, Weaving 101](https://cottonworks.com/wp-content/uploads/2023/03/Weaving-101.pdf).

Los atributos textiles de este capítulo declaran `component_presence_context=textile_component_present`; non_textile_material declara `non_textile_component_present`. Una ausencia documentada del componente pertinente da NOT_APPLICABLE. Si no se sabe si existe, se conserva UNKNOWN. Por ejemplo, un anillo puramente metálico acreditado no necesita fibra desconocida; una joya con piezas ocultas no permite excluir componentes textiles sólo por apariencia. Una camisa sin herrajes ni componente no textil, acreditada como tal, permite excluir non_textile_material; botones fuera de la vista no permiten hacerlo.

En un componente woven confirmado, weave puede ser KNOWN o UNKNOWN; knit_structure es NOT_APPLICABLE. En knitted se invierte esa lógica. Si sólo se observa un componente textil sin poder identificar su método, no declarar ambos NOT_APPLICABLE: la condición permanece UNKNOWN. Nonwoven requiere confirmación; falta de una trama visible en una foto no basta. Opacity describe aquí transmisión del componente textil; no debe reutilizarse como certificación óptica de lentes o como propiedad general de todos los materiales.

Un relieve acanalado puede proceder de diferentes estructuras. `value.surface_texture.ribbed` no implica `value.knit_structure.rib`; pana no equivale a cualquier superficie acanalada. Encaje y terciopelo no fijan su fibra. El pelo visible puede sostener pile/fuzzy, pero brushed exige evidencia del acabado cuando se afirma el proceso.

## Patrones, jerarquía y localización

Patterns contiene un padre check con hijos tartan, gingham y windowpane. Anotar el concepto más específico sustentado para una misma región; conservar el padre para agregación sin contarlo como una segunda etiqueta positiva independiente. Si sólo se distingue cuadrícula, usar check; no adivinar su variante por nombre de producto ambiguo. Tartán no acredita clan o autenticidad.

Liso (`value.pattern.solid`) significa ausencia de motivo visible en la evidencia observada. No significa superficie lisa (`value.surface_texture.smooth`): un suéter liso puede tener textura acanalada. Una camisa puede combinar torso floral y puños solid; la etiqueta multilabel necesita regiones o componentes para evitar que solid se interprete como negación de todo patrón en el producto. Si las vistas son incompletas, registrar esa cobertura; no adjudicar ausencia en paneles ocultos.

Animal print es motivo y no piel animal. Un diseño de rayas puede ser tejido, impreso o construido; pattern no demuestra el proceso. Una imagen con pliegues, sombras o moiré puede aparentar rayas; revisar detalle antes de KNOWN. Distressed describe apariencia de desgaste de diseño, mientras el estado de conservación o uso real requiere otra evidencia.

## Ejemplos de afirmaciones independientes

| Evidencia | Se puede afirmar | Sigue abierto |
|---|---|---|
| Foto detallada de suéter acanalado, sin etiqueta | Sweater si identidad sustentada; surface_texture ribbed; patrón visible | Fibra, porcentajes, técnica exacta de punto, aislamiento |
| Etiqueta documentada de exterior y forro de blazer | Composición separada por cada componente | Trasladar al exterior la composición del forro o deducir formalidad universal |
| Bolso granulado sin ficha de material | Bag shape/carry method visibles; surface_texture pebbled | Leather vs synthetic_leather y material de herrajes |
| Vestido brillante con ligamento satin confirmado | Dress y weave satin del componente pertinente | Silk y composición porcentual |
| Lentes tintadas sin ficha | Lens tint visible bajo condiciones documentadas | Material del lente, receta, protección UV, polarización |

## Revisión y licencia

Mati deberá revisar términos locales, umbrales de detalle visual, componentes permitidos y ejemplos negativos. Los conceptos siguen `NEEDS MATI REVIEW`; distinguir estas capas es un principio de diseño, no aprobación de todos sus valores.

Los glosarios y documentos profesionales se consultaron como fuentes, sin importar listas completas, imágenes ni datasets. Las licencias de código, anotaciones e imágenes están separadas en [research_sources.json](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/research_sources.json) y [02_investigacion_fuentes.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/02_investigacion_fuentes.md). Un concepto documentado no autoriza usar comercialmente las fotografías o anotaciones de la fuente.

## Evidencia híbrida y enriquecimiento — decisión H08

Se permiten observación visual de atributos observables, OCR de etiqueta, fichas técnicas, declaraciones de fabricante/proveedor y fuentes autorizadas, más revisión humana documentada. Una imagen similar no acredita composición del producto Mati. La evidencia técnica externa necesita referencia y variante exactas verificadas; misma marca/modelo con otra talla, color o edición no garantiza la misma ficha. source_scope y source_details del contrato12 separan origen, calidad, coincidencia, permiso y conclusión. Si falta vínculo, mantener UNKNOWN con candidatos y selección manual asistida, sin rellenar porcentajes.

Propiedades declaradas como aislamiento o protección requieren documentación específica; apariencia cálida no demuestra prestación. Adecuación climática se contextualiza con lugar, fecha, estación/hemisferio y clima fiable en contratos21/22. Temporada comercial se mantiene en commercial_season_mapping.json, independiente de fibra, rendimiento y estación de uso. No hay jerarquía universal entre etiqueta física y página oficial: conflicto por componente/variante exige conservar ambos datos y adjudicar.
