# 03 · Jerarquía de prendas

Estado: `DRAFT_FOR_REVIEW`. Corte de documentación: 2026-10-08, zona `America/La_Paz`. Versión propuesta: `1.1.0`. La estructura requiere revisión de Mati; disponer de IDs y validaciones no equivale a congelar el dominio.

La fuente de verdad es [ontology.json](//wsl$/Ubuntu/home/soto/Code/more/services/mati-ai/resources/fashion_ontology/v1/ontology.json): **12 familias, 47 categorías y 22 subcategorías**. Este documento explica sus fronteras; no sustituye sus definiciones, alias ni enlaces `parent_id`.

## Tres niveles con una sola pregunta por nivel

| Nivel | Pregunta | Criterio | Ejemplo |
|---|---|---|---|
| Familia | ¿Qué clase general de objeto es? | Identidad funcional y constructiva del objeto | `garment.family.lower` |
| Categoría | ¿Qué prenda o complemento es? | Identidad reconocible de producto | `garment.category.trousers` |
| Subcategoría | ¿Qué variante estable de esa identidad es? | Construcción o tipología documentable dentro de una categoría | `garment.subcategory.cargo_trousers` |

Cada categoría tiene un padre de familia; cada subcategoría tiene un padre de categoría. La taxonomía no es una jerarquía de capas ni de formalidad. Una subcategoría no genera por sí sola una declaración de fibra, clima, precio, ocasión o público objetivo. Las relaciones adicionales viven fuera de `parent_id` y conservan su tipo y condiciones.

Fashionpedia distingue categorías de ropa, partes y atributos. MORE toma esa separación como referencia conceptual, pero propone su propia cobertura y sus propias fronteras. Las categorías compuestas de DeepFashion2 y las categorías agregadas de ModaNet necesitan descomposición o mappings con pérdida; no constituyen definiciones de MORE. Véanse [Fashionpedia, artículo oficial](https://arxiv.org/html/2004.12276v2), [DeepFashion2, organización de datos](https://github.com/switchablenorms/DeepFashion2#data-organization) y [ModaNet, etiquetas](https://github.com/eBay/modanet#labels).

## Cobertura del borrador

Los IDs de la columna familia llevan el prefijo `garment.family.`. Los ejemplos no son una exportación exhaustiva de categorías o subcategorías.

| Familia | Categorías | Frontera y ejemplos |
|---|---:|---|
| `upper` | 9 | Prendas superiores independientes: camisa, blusa, camiseta, polo, suéter, cárdigan, tank top, top y sudadera. El rol exterior de una camisa no cambia su familia. |
| `lower` | 5 | Pantalón, jeans, shorts, falda y leggings. La falda sigue aquí aunque se superponga a un pantalón. |
| `outerwear` | 5 | Blazer, chaqueta, abrigo, capa y chaleco de identidad exterior o de sastrería. La categoría no garantiza aislamiento. |
| `one_piece` | 3 | Vestido, jumpsuit y romper: continuidad de una prenda entre región superior e inferior. |
| `multi_piece` | 1 | `coordinated_set`: conjunto comercial explícitamente vinculado de piezas separables, con componentes etiquetados. |
| `footwear` | 6 | Sneaker, bota, pump, loafer, sandalia y zapato plano. Tacón y puntera son ejes separados. |
| `bag` | 4 | Bolso, mochila, riñonera y bolso de viaje. Forma y modo de transporte no se confunden con cobertura corporal. |
| `headwear` | 2 | Sombrero y gorra, con beanie propuesto bajo sombrero sujeto a revisión local. |
| `neckwear` | 2 | Bufanda/pañuelo de identidad scarf y corbata. No incluye el cuello unido a una camisa. |
| `jewelry` | 5 | Collar, pulsera, anillo, pendiente y broche; material y autenticidad requieren evidencia propia. |
| `eyewear` | 1 | Gafas; sunglasses es subcategoría propuesta. La receta y la protección UV no se deducen de la categoría. |
| `accessory` | 4 | Cinturón, reloj, guantes y pañuelo de bolsillo. No es un cajón para objetos sin identificar. |

## Identidad y atributos independientes

| Eje | Qué describe | Qué no debe decidir |
|---|---|---|
| Qué es | Familia, categoría y subcategoría | El color, estilo o el lugar en una composición de capas |
| Cómo se ve | Color, patrón, superficie, silueta y detalles observables | Composición porcentual o rendimiento técnico |
| Cómo ajusta | `attribute.fit`, holgura de diseño respecto de una referencia corporal | Talla comercial, identidad o volumen de una pernera |
| Cómo se usa | Ocasión, dress code, formalidad, clima y contexto de uso | Una nueva categoría por cada evento |
| Afinidades de estilo | `attribute.styles` y referencias `attribute.aesthetics` | Identidad del usuario, clase social o tendencia actual |

Por ejemplo, wide leg corresponde a `attribute.leg_shape`; no necesita una categoría wide_leg_trousers. La silueta describe el contorno general y el fit la relación de holgura con un cuerpo o forma de referencia. La documentación del V&A utiliza silueta para el perfil de la vestimenta y muestra que patronaje y estructura pueden producir ese perfil; esta distinción operativa de MORE requiere ejemplos locales de anotación. Véanse [V&A, silueta](https://www.vam.ac.uk/blog/museum-life/letsmakewednesdays-fashion) y [V&A, construcción del Bar Suit](https://www.vam.ac.uk/articles/fashion-unpicked-bar-suit-dior).

## Decisiones de frontera

**Camisa frente a blusa.** `garment.category.shirt` representa identidad camisera; cuerpo construido, cuello y abertura frontal pueden aportar evidencia. `garment.category.blouse` representa identidad blusera y construcción o caída compatibles. Ningún botón, cuello, tejido fluido o género objetivo decide solo la frontera. Una camisa puede tener caída fluida y una blusa puede tener botones. Priorizar identidad de catálogo verificada, detalles constructivos y ejemplos adjudicados por Mati; si esos indicios no resuelven el caso, conservar categoría `UNKNOWN` con candidatos documentados. No convertir toda prenda femenina con botones en blusa.

**Blazer frente a chaqueta y abrigo.** `garment.category.blazer` tiene identidad de chaqueta de sastrería; estructura y solapas son indicios, no requisitos visuales universales. `garment.category.coat` tiene identidad de abrigo pensado para llevar sobre otras prendas; longitud, grosor aparente y ocasión no bastan. `garment.category.jacket` corresponde a identidad de chaqueta sin evidencia suficiente para las categorías más específicas. Un blazer largo sigue siendo blazer cuando la identidad constructiva está acreditada; un abrigo corto puede seguir siendo abrigo. El blazer puede ocupar capa intermedia bajo un coat sin cambiar de categoría.

**Vestido frente a conjunto.** `garment.category.dress` es una unidad continua de torso y parte inferior no bifurcada. Una costura de cintura, un cambio de color o un aspecto de dos piezas no lo convierte en conjunto. `garment.category.coordinated_set` exige piezas separables y evidencia de que el producto constituye ese conjunto. Dos prendas combinadas en una foto de outfit no prueban esa identidad comercial. Jumpsuit y romper también son continuos, con parte inferior bifurcada; la diferencia propuesta es la longitud de sus piernas, con guía de frontera pendiente.

**Chaleco frente a suéter sin mangas.** `garment.category.vest` se reserva a identidad exterior o de sastrería. `garment.subcategory.sweater_vest` depende de `garment.category.sweater`. La ausencia de mangas se anota aparte y no permite importar automáticamente vest de otro dataset a una de esas dos identidades.

**Knit no constituye una categoría genérica.** No existe knit_top en esta propuesta. Knitted es `value.textile_structure.knitted`; jersey, rib e interlock son estructuras de punto. Una camiseta, un polo, un vestido o un suéter pueden ser de punto, y su fibra sigue siendo otro dato. Sweater y cardigan permanecen categorías por identidad de prenda, no por convertir cualquier punto en knitwear. El término jersey también necesita contexto para distinguir prenda regional de estructura textil. Véase [The Weavers' Company, glosario profesional](https://www.weavers.org.uk/textiles/resources/glossary-of-weaving-terms/).

**Jeans frente a pantalón y denim.** `garment.category.jeans` requiere identidad constructiva de jeans; color azul o denim aislados no bastan. Denim puede aparecer en una chaqueta o vestido y se registra como familia textil. La subcategoría denim_jacket pertenece a jacket. La categoría trousers no debe asignarse simultáneamente como un segundo valor paralelo de jeans: ambas son hijas de lower en el modelo actual.

## Alias, residual conocido y casos pendientes

`garment.category.top` es una identidad residual conocida del vocabulario, no un alias de categoría desconocida. `garment.family.accessory` tampoco reemplaza la ausencia de identidad. Un alias ambiguo conserva sus candidatos y la evidencia de desambiguación; no crea equivalencia silenciosa ni un nuevo ID.

Las decisiones humanas 1–3 fijan POLERA ID11 como camiseta/T-shirt (incluido Henley), conservan POLOS Y KNITWEAR ID8 para resolver polo/suéter/cárdigan por producto y delimitan ACCESORIOS ID9 sin calzado. La definición, los ejemplos locales y la adjudicación de cada producto siguen revisables; el alcance comercial aprobado no equivale a dataset o modelo aprobado. ACCESORIOS tampoco es un mapping directo a la familia técnica accessory. El catálogo Laravel es autoridad comercial, mientras esta jerarquía es autoridad propuesta para semántica fashion. La revisión de esos mappings vive en [10_commercial_mapping.md](//wsl$/Ubuntu/home/soto/Code/more/DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/10_commercial_mapping.md).

Para revisar la jerarquía, Mati deberá adjudicar ejemplos y contraejemplos de estas fronteras, confirmar los nombres locales y resolver variantes residuales. Cada ámbito podrá aprobarse gradualmente sólo cuando reúna evidencia, guía y revisión; ni la existencia de subcategorías ni un mapping público eliminan esta revisión.

## Ejemplos de fronteras revisadas por las decisiones humanas

Los mismos casos ilustrativos figuran en `categories[].examples` de ontology.json. Son casos de diseño para futura adjudicación; no se presentan como prendas locales anotadas.

| Frontera | Positivo sustentado | Contraejemplo | Caso fronterizo y estado |
|---|---|---|---|
| Camisa / blusa | Identidad y construcción camisera acreditadas por vistas y ficha fiable | Una blusa con botones conserva blusa; una camisa fluida conserva camisa | Cuello y botones con descripción camisa/blusa imprecisa: categoría UNKNOWN, ambos candidatos, evidencia y revisión |
| Blazer / abrigo | Blazer con identidad sastrera acreditada; abrigo corto con función y construcción exterior acreditadas | Un abrigo con solapas no se convierte en blazer; blazer largo no se convierte en abrigo | Pieza sastrera larga con evidencia ambigua: UNKNOWN con blazer/coat/jacket pertinentes |
| Camiseta / punto | Camiseta de manga corta, larga o Henley documentada conserva tshirt | Camisa de punto no se convierte en polo; suéter bajo POLERA no se relabela automáticamente | Tapeta parcial sin identidad suficiente: UNKNOWN con tshirt/polo pertinentes |
| Vestido / conjunto / outfit | Vestido continuo: una prenda; conjunto con asociación comercial explícita: varias piezas | Aspecto completo, tela compartida o foto de outfit no prueban multipieza comercial | Dos piezas sin asociación comercial comprobada: conservar candidatas si se investiga un conjunto; outfit acreditado conserva instancias individuales |

Un score visual no es probabilidad calibrada sin calibración demostrada. La conclusión reúne construcción, diseño, estructura, detalles, uso y evidencia fiable del producto objetivo. Laravel conserva la categoría comercial ante cualquier discrepancia. El ejemplo histórico JERSEY ALPACA bajo ABRIGOS mantiene su ID y composición UNKNOWN mientras no exista evidencia suficiente.

La venta habitual es de prendas individuales. Los vestidos se venden completos por ser una sola prenda. Un conjunto comercial auténtico puede venderse completo o por componentes sólo con los registros e inventarios necesarios. La selección del modelo compartido, reservado o empaquetado de inventario queda pendiente; esta revisión no lo implementa. El precio de un outfit propuesto no constituye un precio de paquete.
