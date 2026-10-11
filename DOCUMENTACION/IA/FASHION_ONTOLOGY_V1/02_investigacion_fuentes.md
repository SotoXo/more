# Investigación de fuentes para Fashion Ontology V1

Estado: `DESIGN_CANDIDATE`; revisión documental realizada el **2026-10-07, America/La_Paz** (2026-10-08 UTC). La fecha de consulta no convierte un dataset antiguo en un vocabulario actualizado de tendencias. Las fuentes fueron leídas en web; no se descargaron datasets, pesos, modelos ni listas completas. El registro procesable está en `research_sources.json`.

## Criterio de investigación

Se distingue entre evidencia publicada, interpretación de MORE y autorización de reutilización. Las definiciones y decisiones de MORE son redacción propia. Los nombres externos citados son ejemplos acotados para estudiar interoperabilidad. Un parecido lingüístico no constituye equivalencia ontológica; una licencia de código no licencia fotografías, anotaciones ni pesos. No se asignan códigos externos que no hayan sido verificados.

## Comparación

| Source | Purpose | Useful concepts | Weaknesses | License | What MORE reuses conceptually | What MORE rejects |
|---|---|---|---|---|---|---|
| [Fashionpedia](https://fashionpedia.github.io/home/index.html), [paper](https://arxiv.org/html/2004.12276v2) | Ontología experta y atributos localizados | Categorías, partes y propiedades separadas; relaciones de composición | Benchmark visual; no contrato comercial ni preferencias de usuario | Ontología/anotaciones CC BY 4.0; software BSD de dos cláusulas; imágenes por fuente | Ejes independientes, propiedades vinculadas a partes, mapping explícito | Copiar la lista completa o asumir que cada concepto es aprendible visualmente |
| [DeepFashion2](https://github.com/switchablenorms/DeepFashion2) | Detección, landmarks, segmentación, retrieval | Evidencia por instancia, oclusión y vista; categoría combinada con mangas | Su campo `style` identifica variantes para retrieval | Permiso comercial global no demostrado por README; acceso mediante formulario | Descomponer clases compuestas; registrar visibilidad | Convertir `style` en estilo cultural; importar licencia de otro repositorio |
| [ModaNet](https://github.com/eBay/modanet) | Segmentación de moda en calle | Objetos accesorios y prendas por instancia | Algunas clases agrupan identidades heterogéneas | Anotaciones CC BY-NC 4.0; imágenes proceden de PaperDoll | Evaluación futura a granularidad gruesa | Su clase `outer` como familia equivalente a OUTERWEAR |
| [schema.org Product](https://schema.org/Product) | Publicación de datos de producto | Material, patrón, color, talla y categorías referenciables | Vocabulario amplio; no especifica el sistema de prendas de MORE | Sitio: CC BY-SA 3.0; repositorio actual: Apache 2.0, por recurso | URIs externas y adaptadores de publicación | Inventar un tipo `schema:Apparel` o mezclar ofertas con ontología |
| [Google Merchant Center](https://support.google.com/merchants/answer/6324436?hl=en) | Categoría para Shopping | Identificadores/rutas y árbol externo versionable | Una categoría comercial principal; árbol cambiante | No se verificó licencia autónoma de redistribución de la lista | Mapping de interoperabilidad por versión | Convertir la taxonomía de un canal en dominio interno |
| [GS1 Web Vocabulary](https://www.gs1.org/gs1-web-vocabulary), [repositorio](https://github.com/gs1/WebVoc) | Datos interoperables de producto | Dominio/rango; propiedades especializadas; valores con URI | Publicación actual parcialmente dinámica; GPC es otro recurso | WebVoc declara Apache 2.0; GPC requiere revisión propia | Tipos, aplicabilidad y unidades; futuras referencias GS1 | Inferir códigos GPC o extender Apache a cualquier documento GS1 |
| [Shopify Standard Product Taxonomy](https://github.com/Shopify/product-taxonomy) | Categorías, atributos y valores de retail | Separación de archivos maestros y mappings | Sirve a retail; no es ontología de outfits ni verdad local | Taxonomía publicada bajo MIT | Vocabularios referenciados y versiones de mappings | Copiar su árbol o cambiar categorías de Mati |
| [The Weavers' Company](https://www.weavers.org.uk/textiles/resources/glossary-of-weaving-terms/), [ACT](https://contracttextiles.org/learn/contract-textile-glossary/) | Terminología profesional textil | Diferencia entre fibras, estructuras y familias de tejidos | Glosarios, no esquema de anotación visual; ACT se centra en textiles contract | No se identificó licencia abierta para copiar sus glosarios | Validar conceptos y escribir definiciones propias | Copiar definiciones, traducir listas o asumir composición por brillo |
| [CottonWorks](https://cottonworks.com/learning-hub/denim/denim-basics/) | Formación técnica de textiles | Denim y estructura de ligamento | Fuente centrada en algodón | Términos limitan explotación comercial de contenido | Contrastar terminología factual | Importar textos, diagramas o colecciones de muestras |
| [V&A](https://www.vam.ac.uk/blog/museum-life/letsmakewednesdays-fashion), [Museum at FIT](https://www.fitnyc.edu/museum/exhibitions/hip-hop-style/) | Historia, construcción y contexto cultural | Silueta; estilo como expresión contextual | No publican un label schema universal | No se acreditó licencia general para imágenes/textos citados | Validación terminológica y contexto para revisión humana | Inferir estatus social, edad real o tendencias vigentes desde una prenda |
| [Emily Post Institute](https://emilypost.com/advice/attire-guide-dress-codes-from-casual-to-white-tie) | Dress codes y protocolo contextual | Dress code distinto de ocasión y estilo | Marco cultural estadounidense; no escala universal | No se verificó licencia abierta para copiar la guía | Diferenciar expectativas del evento y afinidad de una prenda | Universalizar normas locales o deducir aceptación del evento sólo por imagen |

## Hallazgos y decisiones propuestas de MORE

### Identidad, atributos y composición

Fashionpedia publica 27 categorías principales, 19 partes y 294 atributos; distingue partes de la prenda y propiedades localizadas. Su paper muestra que las clases de DeepFashion2 pueden representarse combinando categorías y atributos. Esto respalda separar identidad y apariencia; **la jerarquía concreta de MORE sigue siendo una decisión propia**, informada además por catálogo y revisión de Mati. [Proyecto Fashionpedia](https://fashionpedia.github.io/home/index.html), [paper, figura 1 y sección 3.1](https://arxiv.org/html/2004.12276v2).

Propuesta MORE: familias estables para especialización; categorías describen qué objeto es; subcategorías requieren identidad reconocible sin concatenar color, ocasión, formalidad y fit. Una prenda puede conservar identidad al variar largo, patrón o anchura. `wide_leg` se ubica en forma de pierna/silueta; `cropped` en largo; `high_rise` en tiro. `tailored` necesita definición de construcción o tipo de pantalón acordada; no implica formalidad, composición ni ocasión automáticamente.

Conjunto comercial, traje coordinado y varias prendas visibles en una foto no son equivalentes. MORE debe representar un conjunto mediante componentes identificados y evidencia de que se venden/declaran juntos. La categoría de una pieza del conjunto no determina la identidad del conjunto. Los datasets visuales citados no acreditan esa relación comercial.

### Silhouette, fit y cut

El V&A utiliza silueta para el contorno general de una prenda. Su examen del traje Bar diferencia construcción y ajuste. [Silueta](https://www.vam.ac.uk/blog/museum-life/letsmakewednesdays-fashion), [construcción del traje Bar](https://www.vam.ac.uk/articles/fashion-unpicked-bar-suit-dior).

Decisión MORE: `silhouette` representa forma exterior; `fit` representa holgura o relación esperada con el cuerpo; `cut/construction` representa una intervención de patronaje/confección verificable. Una misma prenda puede ser rectangular y holgada, o tener pierna amplia y cintura ajustada. La talla comercial se conserva aparte. Una foto sin cuerpo, medidas o metadatos puede ofrecer señales de fit, pero no prueba el ajuste en un usuario determinado.

No almacenar `oversized` indistintamente en fit y silhouette: si se adopta como fit, su definición debe indicar volumen/holgura de diseño y la silueta debe describir el contorno. `bodycon` requiere tejido y relación corporal; `bias_cut` requiere evidencia de corte, no sólo caída aparente. Los criterios de anotación y casos frontera quedan `NEEDS_MATI_REVIEW`.

### Materiales, estructuras y apariencia

El glosario profesional distingue satén como estructura de tejido de las fibras que pueden producirlo, y señala que `jersey` puede designar tejido de punto o una prenda según contexto. ACT distingue ligamentos básicos y tejidos producidos con ellos. [Weavers' Company](https://www.weavers.org.uk/textiles/resources/glossary-of-weaving-terms/), [ACT](https://contracttextiles.org/learn/contract-textile-glossary/). CottonWorks describe denim en términos de construcción. [Denim Basics](https://cottonworks.com/learning-hub/denim/denim-basics/).

La guía de la FTC documenta composición por nombres genéricos y porcentajes, con posibles declaraciones por partes. Se usa como evidencia del dominio; MORE no adopta automáticamente normativa estadounidense ni la presenta como requisito boliviano. [Guía FTC](https://www.ftc.gov/business-guidance/resources/threading-your-way-through-labeling-requirements-under-textile-wool-acts).

Decisión MORE: separar composición (`fiber_id`, porcentaje, componente, evidencia), método estructural (tejido plano/punto/no tejido), ligamento o punto, familia de tejido, acabado y apariencia superficial. Satén no demuestra seda; denim no demuestra 100% algodón; apariencia tipo cuero no demuestra cuero animal. Metal, vidrio, cuero y polímeros no textiles necesitan una capa de materiales no fibrosos para accesorios.

`ribbed` observado y punto canalé confirmado pueden relacionarse, pero deben conservar fuente y certeza distintas. `quilted` y `pleated` pertenecen a construcción/acabado; `sheer` a opacidad; `distressed` a acabado. El patrón describe motivos/distribución, no brillo, transparencia ni tacto. `tartan` puede ser tipo específico de cuadros; no se tratará como alias universal de todo `checked`. Los límites y equivalencias locales requieren revisión de muestras propias.

### Style, aesthetic, dress_code, formality y occasion

Emily Post describe dress codes en relación con expectativas de un evento o contexto. FIT trata el estilo hip hop como expresión histórica/cultural cambiante. Ninguna de estas fuentes establece una clasificación universal de estilos para visión. [Dress codes](https://emilypost.com/advice/attire-guide-dress-codes-from-casual-to-white-tie), [FIT](https://www.fitnyc.edu/museum/exhibitions/hip-hop-style/).

FIT documenta minimalismo/maximalismo como un continuo de estéticas y el modo Ivy/preppy como vocabulario histórico reinterpretado; su exposición de 2026 examina cómo la ropa utilitaria cambia de función y significado. Estas referencias respaldan estudiar un núcleo estable, sin afirmar tendencias actuales ni equiparar afinidad con función técnica. [Minimalism/Maximalism](https://www.fitnyc.edu/museum/exhibitions/minimalism-maximalism.php), [Ivy Style](https://sites.fitnyc.edu/depts/museum/Ivy_Style/), [Beyond Utility](https://www.fitnyc.edu/museum/exhibitions/beyond-utility/index.php).

La frontera entre las palabras style y aesthetic no es universal en la literatura profesional. Su separación en MORE es una convención operacional de definición, estabilidad temporal y gobernanza; llamar minimalist a un estilo de MORE no contradice que una fuente lo denomine estética. La misma referencia no debe duplicarse en dos ejes para inflar evidencia.

Decisión MORE: estilo es una afinidad cultural/visual multilabel; aesthetic es una referencia temática o histórica que puede ser temporal; dress code es una expectativa de uso contextual; ocasión es actividad/evento; formalidad es un eje ordinal operativo. `smart_casual` y `business_casual` se proponen como dress codes; `formal` requiere indicar si es nivel o código, sin reutilizar el mismo ID para ambos. `old_money` no prueba patrimonio, calidad ni marca. `Y2K` no prueba fecha de fabricación; afinidad vintage tampoco acredita antigüedad real.

La escala ordinal y el núcleo de estilos de MORE son **diseño propuesto**, no estándar extraído de las fuentes. No se fijan distancias numéricas entre niveles ni scores reales. Afinidad cultural, idoneidad para un evento, material y temporada no deben etiquetarse como hechos visuales seguros. La revisión de Mati debe establecer ejemplos positivos/negativos y desacuerdos antes de convertir afinidades en targets de aprendizaje.

### Aplicabilidad, body zones y layering

GS1 organiza propiedades mediante dominios y tipos esperados, e introduce propiedades especializadas de productos; aporta un precedente de interoperabilidad para aplicabilidad. [GS1 WebVoc](https://github.com/gs1/WebVoc). MORE no copia sus listas. Propone condiciones por concepto además de familia: una manga no aplica a todas las piezas UPPER; las mediciones deben declarar unidad y componente.

Los datasets inspeccionados contienen localización visual, pero **no aportan equivalencia directa** a la relación de body zones de MORE ni a reglas de layering. Son diseño propio sujeto a revisión: separar cobertura corporal de apoyo/porte (bolso en mano/hombro), zonas primarias/secundarias y precedencia contextual entre capas. Un conflicto espacial potencial no significa incompatibilidad absoluta de moda; una excepción temporal pertenece a Trend Intelligence.

## Licencias verificadas por componente

| Recurso | Código | Ontología / listas / anotaciones | Imágenes | Resultado para MORE |
|---|---|---|---|---|
| Fashionpedia | BSD de dos cláusulas, software oficial | CC BY 4.0 para ontología y anotaciones | El proyecto no posee su copyright; términos del proveedor y derechos por imagen | Conceptos y mappings propios ahora. Una importación futura exige atribución/versionado; imágenes no tienen autorización global |
| DeepFashion2 | No se encontró licencia global explícita en README raíz inspeccionado; archivos/dependencias requieren revisión propia | Acceso con formulario; términos completos no verificables en esta consulta | No se acreditó permiso comercial | `RESEARCH_INCOMPLETE`: no aprobado para descarga, training comercial o redistribución |
| DeepFashion original | Acuerdo no concede licencia general de código | Acuerdo limita dataset a investigación no comercial | Sujeto al mismo acuerdo y derechos aplicables | Referencia de comparación; no transferir ese acuerdo automáticamente a DeepFashion2 |
| ModaNet | LICENSE incluye una sección para el formato COCO; no prueba permiso amplio de datos/fotos | Anotaciones CC BY-NC 4.0 | Basadas en PaperDoll; revisar licencias de imágenes por separado | No aprobado como training comercial; mapping conceptual no equivale a permiso de uso |
| schema.org | LICENSE del repositorio actual Apache 2.0 | Términos del sitio indican CC BY-SA 3.0 para schema; distinguir publicación y recurso exactos | No hay permiso de imágenes inferible | Referenciar URIs. Revisión de recurso/licencia antes de copiar definiciones o redistribuir schema |
| GS1 WebVoc | Repositorio Apache 2.0 | README declara WebVoc Apache 2.0; verificar aviso del archivo versionado si se importa | No inferible | Referencias conceptuales. GPC, GDSN y documentos GS1 se revisan por separado |
| Shopify Product Taxonomy | MIT según repositorio | Taxonomía MIT expresamente declarada | No licencia imágenes de productos Shopify | No se importó contenido; si se reutiliza después, conservar avisos y release |
| Google Product Taxonomy | No aplica a esta lectura | Licencia autónoma de redistribución no acreditada | No inferible | Enlace de referencia; no copia de lista ni códigos inventados |
| Glosarios, museos, Emily Post | No aplica a esta lectura | Sin licencia abierta confirmada; CottonWorks explicita restricción comercial de contenido | No reutilizadas | Definiciones propias, citas precisas; no importar contenido protegido |

Evidencia de licencia: [Fashionpedia Terms of Use](https://fashionpedia.github.io/home/data_license.html), [Fashionpedia API license](https://github.com/KMnP/fashionpedia-api/blob/master/license.txt), [ModaNet LICENSE](https://github.com/eBay/modanet/blob/master/LICENSE), [DeepFashion Agreement](https://mmlab.ie.cuhk.edu.hk/projects/DeepFashion/DeepFashionAgreement.pdf), [schema.org terms](https://schema.org/docs/terms.html), [schemaorg LICENSE](https://github.com/schemaorg/schemaorg/blob/main/LICENSE), [GS1 LICENSE](https://github.com/gs1/WebVoc/blob/master/LICENSE), [Shopify LICENSE](https://github.com/Shopify/product-taxonomy/blob/main/LICENSE), [CottonWorks terms](https://cottonworks.com/terms-and-conditions/).

CC BY 4.0 permite usos comerciales bajo sus condiciones de atribución, enlace y cambios; no entrega derechos ajenos que el licenciante no posee. CC BY-NC 4.0 restringe el uso comercial. Este inventario acredita lo leído, no sustituye la revisión de licencias de una futura ingestión concreta. [CC BY](https://creativecommons.org/licenses/by/4.0/), [CC BY-NC](https://creativecommons.org/licenses/by-nc/4.0/).

Attribution de investigación: Jia, Shi, Sirotenko, Cui, Cardie, Hariharan, Adam y Belongie, Fashionpedia (ECCV 2020); Ge, Zhang, Wu, Wang, Tang y Luo, DeepFashion2 (CVPR 2019); Zheng, Yang, Kiapour y Piramuthu, ModaNet (ACM Multimedia 2018). MORE no reproduce sus ontologías, fotos ni anotaciones; esta atribución identifica las referencias de investigación. Las citas no implican respaldo de los autores a MORE.

## Compatibilidad conceptual con datasets públicos

Los destinos siguientes son términos semánticos, no nuevos IDs del artefacto MORE. Las equivalencias deben enlazarse posteriormente a IDs versionados y comprobar definiciones, granularidad, instancia y condiciones. El registro JSON identifica dirección, pérdida y evidencia. `CANDIDATE_EQUIVALENCE` no significa conversión aprobada. Un mapping más general sólo permite evaluación al nivel general, nunca inventar la etiqueta más específica.

| Fuente / ejemplo acotado | Destino MORE propuesto | Relación / pérdida | Revisión |
|---|---|---|---|
| DeepFashion2: trousers | Pantalón | Candidato de identidad; no aporta tiro, fit, tejido o subcategoría | Verificar definición de inclusión |
| DeepFashion2: skirt | Falda | Candidato de identidad; largo y silueta pendientes | Verificar guía |
| DeepFashion2: long sleeve dress | Vestido + manga larga | Descomposición categoría/atributo; no estilo ni formalidad | Verificar umbrales de manga |
| DeepFashion2: short sleeve top | UPPER + manga corta | Destino más amplio; no distingue camisa/blusa/camiseta | No emitir categoría fina |
| DeepFashion2: vest | Prenda sin mangas, candidato chaleco | Término ambiguo según guía; no exact match aprobado | `NEEDS_REVIEW` |
| DeepFashion2: style | Identidad de variante para retrieval | Campo operativo; no estilo/aesthetic MORE | `NO_DIRECT_MAPPING` para estilo |
| ModaNet: pants | Pantalón / jeans / leggings | Fuente agrupa candidatos; sólo evidencia de rama inferior | No convertir a jeans sin revisión |
| ModaNet: top | Parte superior | Fuente agrupa varias categorías de MORE | No separar camisa/blusa por conversión |
| ModaNet: outer | Conjunto candidato entre distintas familias | Clase heterogénea; no equivalencia OUTERWEAR | `NO_DIRECT_MAPPING` para familia exacta |
| ModaNet: bag | Bolso | Candidato amplio; porte/capacidad no anotados como conceptos MORE | Detalle necesita anotación propia |
| ModaNet: scarf & tie | Bufanda / corbata | Fuente agrupa identidades; región cuello no resuelve cuál | No emitir categoría fina |
| Fashionpedia: jacket, collar, silhouette | Chaqueta, parte cuello, silueta | Validar identidad, parte y valor por separado; no copiar árbol | `CANDIDATE_EQUIVALENCE` / parte / atributo |
| Fashionpedia: localized attributes | Atributos de instancia/parte | Correspondencia de estructura, sin asumir vocabularios iguales | Revisar guía y versión exactas |
| Los tres datasets | Conjunto vendido junto / body-zone relation / layering | No hay label equivalente verificado en documentación revisada | `NO_DIRECT_MAPPING` |
| Los tres datasets | Afinidad old_money/Y2K, ocasión, material porcentual, formalidad MORE | No equivalencia directa verificada a los ejes propuestos | `NO_DIRECT_MAPPING` |

Evidencia de los ejemplos: [DeepFashion2 Data Organization](https://github.com/switchablenorms/DeepFashion2#data-organization), [ModaNet Labels](https://github.com/eBay/modanet#labels), [Fashionpedia sección 3.1](https://arxiv.org/html/2004.12276v2).

`NO_DIRECT_MAPPING` significa que no se encontró equivalencia respaldada durante esta revisión; no afirma que jamás pueda diseñarse una. Ausencia de anotación no es un negativo; una etiqueta pública no mencionada deja `UNKNOWN`. La granularidad pública no define los labels de MORE.

## Preparación de futura transferencia

Para cualquier uso posterior: fijar release/commit y licencia por componente; verificar autorización del propósito comercial; fijar split y derechos de imágenes; enlazar cada valor a su definición MORE; preservar `source_label`, transformación, pérdida y revisión humana. Las clases compuestas se descomponen cuando hay evidencia; las clases agregadas sólo generan targets más generales; los demás ejes quedan desconocidos y fuera del loss de entrenamiento. Si cambia el mapping, debe cambiar su versión y el hash del label schema derivado.

Los futuros labels de MORE nacen de la ontología y su guía, no de maximizar overlap con un benchmark. Las métricas comparables deben reportar cobertura efectiva y clases descartadas; un resultado de dataset público no acredita calidad en Mati. Ningún modelo, benchmark, extractor de labels ni entrenamiento se ejecutó en esta fase.

## Revisión pendiente antes de congelar

| Clasificación | Asunto | Evidencia para resolver |
|---|---|---|
| `SAFE TO FREEZE` a nivel de principio | Separar identidad/atributo; composición/estructura; ontología/comercio; fuente/mapping | Fuentes contrastadas y contrato del proyecto |
| `NEEDS MATI REVIEW` | Fit/silueta/corte, camisa/blusa, blazer/abrigo, conjunto/pieza | Muestras propias, definición acordada y doble anotación |
| `NEEDS MATI REVIEW` | POLERA, POLOS Y KNITWEAR, ACCESORIOS y aliases regionales | Producto real + maestros comerciales; ningún dataset decide usos locales |
| `NEEDS MATI REVIEW` | Núcleo de estilos, aesthetic, formalidad y ocasiones | Ejemplos, contraejemplos, ámbito cultural y grado de desacuerdo |
| `RESEARCH INCOMPLETE` | DeepFashion2 términos completos; derechos de imágenes; GPC y Google lista | Condiciones de acceso y recurso versionado específico |
| `RESEARCH INCOMPLETE` | Mappings finos de todos los atributos Fashionpedia | Ontología/guía exactas y revisión por concepto, sin descarga de dataset ahora |

OPEN QUESTIONS FOR LIMBER/MATI: ¿Qué producto concreto define POLERA en esta tienda? ¿Qué evidencia hace que una prenda se registre como blusa y no camisa? ¿Cuándo un conjunto puede dividirse comercialmente? ¿Qué códigos de vestimenta usa la clientela de Mati? ¿Qué estilos son distinguibles con acuerdo entre especialistas? ¿Qué campos de composición tienen respaldo de etiqueta/ficha? ¿Qué permisos de datasets públicos se evaluarán sólo cuando exista un plan de entrenamiento comercial?
