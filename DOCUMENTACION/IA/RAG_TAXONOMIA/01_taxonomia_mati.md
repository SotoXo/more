# 01. Taxonomía semántica versionada de Mati

Esta entrega analiza las once categorías comerciales y añade una propuesta semántica revisable. Los IDs y nombres proceden de exportaciones **SELECT-only** de Laravel/PostgreSQL; las definiciones ampliadas, señales y comparaciones son curación de Codex **pendiente de revisión del dominio por Mati**, sin convertirlas en ground truth humano. No se renombró, fusionó ni eliminó ninguna categoría.

Se prepararon **23 documentos / 23 unidades semánticas**, **48 consultas positivas** y **16 negativas**. Los inputs quedaron congelados antes del benchmark textual. Este documento describe la taxonomía y su procedencia; los resultados reales de embeddings y retrieval corresponden a los informes03/05. Una buena recuperación del texto curado no demuestra que sus límites comerciales ya estén confirmados por un experto.

## Autoridad y auditoría de los maestros

Autoridad de IDs, nombres y estados activos: Laravel/PostgreSQL comercial. Autoridad de semántica extendida: la versión explícita del artefacto, con estado de propuesta. Se leen snapshots fechados; no se copian stock, precios, reservas, pagos o inventario al corpus.

| Evidencia | Captura UTC | Alcance |
| --- | --- | --- |
| storage/app/ai-rag/catalog-baseline.json | 2026-10-05T15:14:23Z | Taxonomías expuestas por el catálogo y columnas comerciales; tablas RAG inicialmente ausentes |
| storage/app/ai-rag/catalog-master-baseline.json | 2026-10-05T15:14:55Z | Filas maestras completas de categorías, marcas, colores, tallas y patrones; valores de género observados |
| storage/app/ai-rag/catalog-attributes-baseline.json | 2026-10-05T15:19:25Z | Temporadas, texturas, materiales, columnas de variantes y conteos de relaciones |

Las consultas comerciales las ejecutó el agente de datos en modo sólo lectura. Esta entrega leyó esos exports desde el contenedor Laravel. El snapshot exportable commercial-categories-v1.json deriva programáticamente del maestro, conserva su SHA y permite probar IDs/nombres/grupos sin acceder a una DB writable. Es una foto validada de origen, no un sustituto permanente de la autoridad comercial; una nueva versión debe volver a contrastarse con el catálogo.

En el corte hay 134 prendas y 134 variantes. ACCESORIOS está activo pero no tiene prendas en el conjunto comercial observado. Los conteos por categoría aquí son descriptivos del snapshot completo N134, **no** soportes del benchmark visual N130, que aplica exclusiones de imágenes duplicadas.

### Categorías comerciales y jerarquía conceptual

| ID | Nombre real | Descripción comercial | Talla sistema / grupo real | Rol conceptual propuesto | Prendas N134 |
| --- | --- | --- | --- | --- | ---: |
| 1 | ABRIGOS | Abrigos, capas y piezas de exterior. | ALFA / SUPERIOR | OUTERWEAR | 13 |
| 2 | BLAZERS | Sastreria ligera y blazers de estructura definida. | ALFA / SUPERIOR | OUTERWEAR | 10 |
| 3 | CAMISAS | Camisas y piezas de botonadura. | ALFA / SUPERIOR | UPPER | 20 |
| 4 | BLUSAS | Blusas fluidas y piezas de caida suave. | ALFA / SUPERIOR | UPPER | 8 |
| 5 | VESTIDOS | Vestidos de linea diaria, evento y ocasion. | ALFA / SUPERIOR | ONE_PIECE | 18 |
| 6 | FALDAS | Faldas para look diario, oficina o ocasion. | NUMERICA / INFERIOR | LOWER | 7 |
| 7 | PANTALONES | Pantalones sastreros, rectos y de silueta amplia. | NUMERICA / INFERIOR | LOWER | 16 |
| 8 | POLOS Y KNITWEAR | Punto ligero, knitwear y polos de base. | ALFA / SUPERIOR | UPPER | 22 |
| 9 | ACCESORIOS | Complementos de styling y piezas de apoyo. | UNICA / GENERAL | ACCESSORY | 0 |
| 10 | CONJUNTOS | Sets coordinados y propuestas armadas. | ALFA / SUPERIOR | MULTI_PIECE | 3 |
| 11 | POLERA | NULL | ALFA / NULL | UPPER | 17 |

Todos los cod_categoria_padre_cat del snapshot son NULL. La jerarquía del JSON es metadata nueva, independiente de esos padres comerciales:

    APPAREL
    ├── GARMENT
    │   ├── UPPER: CAMISAS3, BLUSAS4, POLOS Y KNITWEAR8, POLERA11
    │   ├── OUTERWEAR: ABRIGOS1, BLAZERS2
    │   ├── LOWER: FALDAS6, PANTALONES7
    │   ├── ONE_PIECE: VESTIDOS5
    │   └── MULTI_PIECE: CONJUNTOS10
    └── ACCESSORY: ACCESORIOS9

CONJUNTOS se conserva como **set de varias piezas**: la descripción real dice sets coordinados y las referencias históricas incluyen tracksuit sets. No se impone ONE_PIECE. El rol MULTI_PIECE tampoco cambia su perfil comercial ALFA/SUPERIOR. Un modelo que ve dos prendas en una persona no prueba que ambas formen una unidad vendible.

Los roles describen función/construcción de manera útil, sin ser una ontología universal ni un detector de la prenda objetivo. Pueden solaparse funciones de capa exterior y punto. No se convierten en validaciones restrictivas.

### Marcas

La base registra 12 marcas activas. Marca es identidad comercial; no prueba categoría, material, estilo, autenticidad ni calidad visual. No se inventa una guía por marca ni se usa ZARA como sinónimo de una categoría.

| ID | Nombre real |
| --- | --- |
| 1 | ZARA |
| 2 | BERSHKA |
| 3 | STRADIVARIUS |
| 4 | RALPH LAUREN |
| 5 | HUGO BOSS |
| 6 | MASSIMO DUTTI |
| 7 | MANGO |
| 8 | PULL&BEAR |
| 9 | RESERVED |
| 10 | COS |
| 11 | PATITO |
| 12 | PREMIER |

### Colores

Se observaron 56 colores, 55 activos y 15 familias; ROJO VINO ID18 está inactivo. IDs no consecutivos se conservan sin inferir por qué faltan otros números. Las familias agrupan tonos, pero no son equivalencias de IDs. hex_col y hex_dom_col son referencias comerciales, no mediciones garantizadas del tejido bajo una cámara.

NEGRO1 y NEGRO CARBON17 pertenecen a NEGRO y tienen referencias distintas; CREMA3, BLANCO HUESO19 y MARFIL20 se agrupan en BLANCO. AZUL PETROLEO13 tiene ortografía comercial preservada. La paleta observada no autoriza aplicar color si la identidad de píxeles de la prenda objetivo no está verificada. Se conserva el guard comercial activo y estrategia color NONE.

| ID | Nombre real | Familia real | Activo |
| --- | --- | --- | --- |
| 1 | NEGRO | NEGRO | sí |
| 2 | BLANCO | BLANCO | sí |
| 3 | CREMA | BLANCO | sí |
| 4 | BEIGE | BEIGE | sí |
| 5 | CAMEL | CAMEL | sí |
| 6 | GRIS | GRIS | sí |
| 7 | AZUL MARINO | AZUL | sí |
| 8 | CELESTE | AZUL | sí |
| 9 | VERDE OLIVA | VERDE | sí |
| 10 | MARRÓN | MARRON | sí |
| 11 | BORGONA | ROJO | sí |
| 12 | ROSA PALO | ROSA | sí |
| 13 | AZUL PETROLEO | AZUL | sí |
| 14 | AMARILLO | AMARILLO | sí |
| 17 | NEGRO CARBON | NEGRO | sí |
| 18 | ROJO VINO | ROJO | no |
| 19 | BLANCO HUESO | BLANCO | sí |
| 20 | MARFIL | BLANCO | sí |
| 21 | ARENA | BEIGE | sí |
| 22 | TAUPE | BEIGE | sí |
| 23 | CAQUI | BEIGE | sí |
| 24 | GRIS CLARO | GRIS | sí |
| 25 | GRIS OSCURO | GRIS | sí |
| 26 | ROJO | ROJO | sí |
| 27 | ROJO CARMÍN | ROJO | sí |
| 28 | TERRACOTA | ROJO | sí |
| 29 | CORAL | ROJO | sí |
| 30 | NARANJA | NARANJA | sí |
| 31 | NARANJA QUEMADO | NARANJA | sí |
| 32 | DURAZNO | NARANJA | sí |
| 33 | AMARILLO PASTEL | AMARILLO | sí |
| 34 | MOSTAZA | AMARILLO | sí |
| 35 | VERDE MENTA | VERDE | sí |
| 36 | VERDE SALVIA | VERDE | sí |
| 37 | VERDE ESMERALDA | VERDE | sí |
| 38 | VERDE BOTELLA | VERDE | sí |
| 39 | VERDE BOSQUE | VERDE | sí |
| 40 | VERDE LIMA | VERDE | sí |
| 41 | AZUL CIELO | AZUL | sí |
| 42 | AZUL TURQUESA | AZUL | sí |
| 43 | AZUL REY | AZUL | sí |
| 44 | AZUL ELÉCTRICO | AZUL | sí |
| 45 | LAVANDA | MORADO | sí |
| 46 | LILA | MORADO | sí |
| 47 | VIOLETA | MORADO | sí |
| 48 | MORADO | MORADO | sí |
| 49 | CIRUELA | MORADO | sí |
| 50 | ROSA BEBÉ | ROSA | sí |
| 51 | ROSA | ROSA | sí |
| 52 | ROSA FUERTE | ROSA | sí |
| 53 | FUCSIA | ROSA | sí |
| 54 | MAGENTA | ROSA | sí |
| 55 | MARRÓN CLARO | MARRON | sí |
| 56 | CHOCOLATE | MARRON | sí |
| 57 | DORADO | DORADO | sí |
| 58 | PLATEADO | PLATEADO | sí |

### Tallas, grupos y segmento

Hay 54 tallas activas. seg_tal distingue ADULTO, INFANTIL y BEBE; sis_tal y gru_tal son dimensiones separadas. El segmento se usa para sugerir opciones en el formulario y no es un nuevo atributo de categoría o prenda. No se deducen conversiones de centímetros, equivalencias de marca o medidas corporales a partir de nombres.

| Segmento / sistema / grupo | IDs y nombres observados |
| --- | --- |
| ADULTO / UNICA / GENERAL | 1: UNITALLA |
| ADULTO / ALFA / SUPERIOR | 2: XS, 3: S, 4: M, 5: L, 6: XL, 7: XXL, 17: XXXS, 18: XXS, 19: XXXL, 20: XXXXL |
| ADULTO / NUMERICA / INFERIOR | 8: 32, 9: 34, 10: 36, 11: 38, 12: 40, 13: 42, 14: 44, 15: 46, 16: 48 |
| ADULTO / CALZADO_US / CALZADO | 21: 6 US, 22: 6.5 US, 23: 7 US, 24: 7.5 US, 25: 8 US, 26: 8.5 US, 27: 9 US |
| ADULTO / CALZADO_EU / CALZADO | 28: 36 EU, 29: 37 EU, 30: 38 EU, 31: 39 EU, 32: 40 EU |
| INFANTIL / EDAD / GENERAL | 33: 2A, 34: 3A, 35: 4A, 36: 5A, 37: 6A, 38: 8A, 39: 10A, 40: 12A |
| INFANTIL / ALTURA_CM / GENERAL | 41: 92 CM, 42: 98 CM, 43: 104 CM, 44: 110 CM, 45: 116 CM, 46: 122 CM, 47: 128 CM |
| BEBE / MESES / GENERAL | 48: 0-3M, 49: 3-6M, 50: 6-9M, 51: 9-12M, 52: 12-18M, 53: 18-24M |
| INFANTIL / ALFA / GENERAL | 54: XXXXXXXXL |

El nombre XXXXXXXXL ID54 pertenece a INFANTIL/ALFA/GENERAL en el snapshot. Se señala como dato que merece revisión de dominio, sin corregirlo, ni afirmar una talla humana precisa. compatible_size_group refleja campos comerciales existentes y sirve como sugerencia, sin excluir variantes válidas.

**Discrepancia documentada:** [10_categorias_tallas_superadmin.md](/home/soto/Code/more/DOCUMENTACION/10_categorias_tallas_superadmin.md) usa POLERA como ejemplo ALFA/SUPERIOR. El snapshot real actual tiene POLERA11 ALFA/**NULL**. El JSON conserva NULL; no se actualiza el dato comercial por ese ejemplo.

### Temporadas, género y atributos

Temporadas son identidades comerciales con nombres/códigos y fechas propias; no equivalen a clima efectivo, disponibilidad actual o tendencia. Todas las siete filas observadas están activas.

| ID | Temporada | Código | Descripción real |
| --- | --- | --- | --- |
| 1 | PRIMAVERA-VERANO | PV | Piezas ligeras y clima cálido. |
| 2 | OTOÑO-INVIERNO | OI | Abrigo, punto y capas. |
| 3 | RESORT | RES | Viaje, transición y silueta relajada. |
| 4 | CAPSULE | CAP | Edición corta y curada. |
| 5 | PERMANENTE | PER | Base continua de fondo de armario. |
| 6 | CARNAVALERA | C | Carnaval |
| 7 | MUNDIAL | TMP | NULL |

prendas.gen_pre contiene mujer, hombre y unisex, coincidiendo con la validación Laravel. Es un atributo comercial; no se usa como prueba suficiente para separar CAMISAS de BLUSAS, ni para inferir características de una persona.

| Maestro | Valores reales | Uso / límite |
| --- | --- | --- |
| Patrones | 1:LISO, 2:RAYAS, 3:CUADROS, 4:ESTAMPADO, 5:FLORAL, 6:GEOMÉTRICO, 7:CAMUFLAJE, 8:ANIMAL PRINT, 9:DEGRADADO, 10:OTRO | Relación prenda_patrones; LISO no equivale a textura LISA |
| Texturas | 1:LISA, 2:ACANALADA, 3:TEJIDA, 4:WAFFLE, 5:FELPA, 6:ATERCIOPELADA, 7:DENIM, 8:TEXTURIZADA, 9:CALADA, 10:OTRA | Relación prenda_texturas; cero enlaces observados en el corte |
| Materiales | 1:ALGODÓN, 2:POLIÉSTER, 3:ELASTANO, 4:LANA, 5:LINO, 6:VISCOSA, 7:NYLON, 8:ACRÍLICO, 9:CUERO, 10:CUERO SINTÉTICO, 11:SEDA, 12:OTRO | prenda_materiales conserva porcentaje y orden; cero enlaces observados |

Las variantes conservan color principal, talla, SKU/código de barras y tipo de paleta, con 134 relaciones en prenda_variante_colores. Material y textura maestros disponibles no significan que las 134 prendas tengan composición o textura anotadas. No se deduce lana/algodón por el aspecto de punto. modelo_pre es metadata del producto; no se interpreta como categoría o versión de un modelo ML.

Contratos leídos: [Categoria.php](/home/soto/Code/more/app/Models/Categoria.php), [Talla.php](/home/soto/Code/more/app/Models/Talla.php), [Prenda.php](/home/soto/Code/more/app/Models/Prenda.php), [ProductCatalogService.php](/home/soto/Code/more/app/Services/Superadmin/Resources/ProductCatalogService.php) y [ResourceValidationService.php](/home/soto/Code/more/app/Services/Superadmin/Resources/ResourceValidationService.php). No se indexa ese código como contenido conversacional.

## Definiciones operativas propuestas por categoría

### ABRIGOS (ID1)

Categoría Mati para abrigos, capas y piezas de exterior. Se propone priorizar la función de capa exterior sobre otras prendas, sin exigir largo, mangas ni una fibra concreta.

Indicios positivos: función exterior descrita en ficha; capa amplia sobre otra prenda.

Límites: sastrería ligera sola no demuestra abrigo; suéter de punto aislado no demuestra categoría1.

Reglas de distinción: Frente a BLAZERS valorar finalidad exterior y cobertura; longitud o solapa solas no deciden. No garantizar aislamiento térmico por la fotografía.

### BLAZERS (ID2)

Categoría Mati de sastrería ligera y blazers de estructura definida. Se propone reconocer la construcción de chaqueta sastrera; no exige combinación formal, insignia ni un color determinado.

Indicios positivos: sastrería y estructura definidas; ficha describe blazer.

Límites: solapas también existen en abrigos; botones no bastan para separar de camisas.

Reglas de distinción: Frente a ABRIGOS priorizar estructura de sastrería ligera y contexto del producto. Un blazer puede ser largo o de tejido de punto; no excluirlo sólo por esos rasgos.

### CAMISAS (ID3)

Categoría Mati para camisas y piezas de botonadura. Se propone priorizar construcción camisera y apertura de botones, con cuello o puños frecuentes pero no obligatorios.

Indicios positivos: apertura camisera y confección de camisa; ficha identifica camisa.

Límites: género no diferencia camisa de blusa; una blusa también puede tener botones.

Reglas de distinción: Frente a BLUSAS ponderar construcción camisera frente a caída suave; aceptar solapamiento. Tejido de punto no anula una camisa: hay camisas de punto documentadas.

### BLUSAS (ID4)

Categoría Mati para blusas fluidas y piezas de caída suave. Se propone destacar drapeado y confección fluida sin exigir adornos, ausencia de botones ni género del usuario.

Indicios positivos: ficha describe blusa; construcción fluida en el contexto completo.

Límites: blusa puede llevar cuello y botones; una camisa también puede ser fluida.

Reglas de distinción: Frente a CAMISAS distinguir tendencia de construcción, no regla absoluta de botones. Si estructura camisera y fluidez coinciden, etiqueta comercial verificada y revisión humana deciden.

### VESTIDOS (ID5)

Categoría Mati para vestidos de uso diario, evento u ocasión. Se propone una pieza continua que integra zona superior y falda; ni ocasión ni largo identifican por sí solos un vestido.

Indicios positivos: pieza única con zona de falda; ficha identifica vestido.

Límites: dos piezas coordinadas no son vestido por parecer continuas; un mono con perneras no se fuerza a vestido.

Reglas de distinción: Frente a CONJUNTOS comprobar continuidad constructiva frente a piezas separables. Frente a FALDAS comprobar si la zona superior forma parte de la misma pieza.

### FALDAS (ID6)

Categoría Mati para faldas de uso diario, oficina u ocasión. Se propone prenda inferior suspendida desde la cintura, sin dos perneras separadas; uso y largo no fijan la categoría.

Indicios positivos: prenda inferior independiente; ficha identifica falda.

Límites: falda de un vestido no es artículo falda separado; pantalón ancho puede simular falda.

Reglas de distinción: Frente a PANTALONES buscar separación constructiva de perneras; con oclusión conservar duda. Frente a VESTIDOS revisar continuidad con el torso.

### PANTALONES (ID7)

Categoría Mati para pantalones sastreros, rectos y de silueta amplia. Se propone prenda inferior con dos perneras, sin limitarla a un tejido, género o anchura.

Indicios positivos: separación constructiva de piernas; ficha identifica pantalón.

Límites: silueta ancha no prueba falda; jeans es subtipo, no categoría comercial nueva.

Reglas de distinción: Frente a FALDAS verificar perneras, no sólo contorno. Un pantalón dentro de un set no identifica por sí solo todo el artículo CONJUNTOS.

### POLOS Y KNITWEAR (ID8)

Categoría Mati combinada para punto ligero, knitwear y polos de base. Se propone incluir prendas superiores de punto y polos; es una agrupación comercial, no una sola construcción visual homogénea.

Indicios positivos: ficha identifica polo o knitwear; construcción de punto superior documentada.

Límites: todo tejido jersey no equivale a suéter; tener cuello no basta: una camisa también puede ser de punto.

Reglas de distinción: Frente a POLERA considerar término regional y construcción; categoría11 carece de definición comercial. Frente a CAMISAS no usar sólo punto, pues hay camisa de punto. Imagen13 es un jersey físico diagnóstico y no ground truth humano ni prueba de categoría comercial universal.

### ACCESORIOS (ID9)

Categoría Mati para complementos de styling y piezas de apoyo. Se propone separar accesorios de las prendas principales; la lista concreta de productos admitidos requiere revisión comercial.

Indicios positivos: ficha y categoría comercial identifican accesorio.

Límites: un bolso visible junto a una prenda no demuestra que sea el producto objetivo; calzado tiene grupo de talla propio, no inferir cobertura de catálogo9.

Reglas de distinción: Distinguir artículo accesorio de complemento incidental en una foto. No mapear todo objeto detectado a ACCESORIOS ni crear subcategorías comerciales.

### CONJUNTOS (ID10)

Categoría Mati para sets coordinados y propuestas armadas. Se propone conjunto de varias piezas separables comercializadas como unidad; no se impone una construcción de una sola pieza.

Indicios positivos: ficha describe set y sus componentes; identidad comercial conjunta documentada.

Límites: persona con varias prendas no prueba set comercial; una sola pieza continua no prueba conjunto.

Reglas de distinción: Frente a VESTIDOS verificar piezas separables frente a continuidad constructiva. La coordinación visual sin ficha no prueba que las piezas se vendan juntas. Conservar ALFA/SUPERIOR del catálogo aunque el rol conceptual sea MULTI_PIECE.

### POLERA (ID11)

POLERA es categoría comercial activa Mati sin descripción. ASALE documenta sentidos regionales de camiseta y suéter; el uso boliviano orienta a camiseta, pero su frontera con POLOS Y KNITWEAR debe confirmarse con Mati. No se fija equivalencia exclusiva.

Indicios positivos: ID11 y nombre comercial verificados; ejemplos históricos se titulan polera y remiten a t-shirts.

Límites: polera no significa universalmente polo con cuello; manga o material solos no resuelven el uso regional.

Reglas de distinción: Revisar etiqueta comercial y construcción real; no resolver por traducción automática. No completar gru_tal_cat=null con SUPERIOR porque un ejemplo documental lo sugiera. No introducir aliases ingleses definitivos hasta revisión de Mati.


## Comparaciones y ambigüedades que requieren revisión

| Comparación | Diferencia propuesta útil | Frontera que se conserva |
| --- | --- | --- |
| CAMISAS3 / BLUSAS4 | Construcción camisera y botonadura frente a fluidez y caída suave | Ambas pueden tener cuello, botones y tejido fluido o punto; género no resuelve |
| BLAZERS2 / ABRIGOS1 | Sastrería ligera estructurada frente a función de exterior y cobertura | Solapas, largo y botones pueden aparecer en ambos |
| POLERA11 / POLOS Y KNITWEAR8 | Categoría local sin definición frente a agrupación comercial de polos y punto | Polera varía regionalmente; camiseta de tejido jersey no es automáticamente suéter ni polo |
| VESTIDOS5 / CONJUNTOS10 | Una pieza continua con falda frente a varias piezas separables vendidas juntas | Varias prendas en foto no prueban set; no hay categoría de monos para forzar equivalencia |

Las reglas son preferencias explicativas, no condiciones necesarias y suficientes. Falta validación humana de casos frontera y del alcance exacto de ACCESORIOS. Aliases ES/EN describen equivalencias operativas **propuestas**; traducciones amplias como shirt,blouse no se convierten en una categoría comercial única. POLERA conserva sólo su nombre en español como alias operativo y ningún alias inglés definitivo. Se explican explícitamente polera, jersey y el rótulo externo mixto shirt,blouse en alias_ambiguities.

**Caso13 conservado:** [04_resultados_n130.md](/home/soto/Code/more/DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/04_resultados_n130.md) describe la región superior verde observada como jersey. Su etiqueta comercial **ABRIGOS ID1** permaneció intacta. La selección posterior de Codex de candidata2 fue diagnóstico N1 asistido, no AUTO, no oráculo humano y fuera de agregados. Esta taxonomía no reasigna imagen13 a knitwear ni reinterpreta el benchmark. Hay una discrepancia semántica que Mati deberá revisar con la prenda/ficha; mejorar un diccionario no equivale a corregir una etiqueta.

## Fuentes externas primarias y criterio de uso

Las descripciones comerciales son la base principal. La [entrada shirt de Cambridge University Press](https://dictionary.cambridge.org/us/dictionary/learner-english/shirt) respalda rasgos habituales de prenda superior con botonadura; la [entrada blouse](https://dictionary.cambridge.org/us/dictionary/english/blouse) evidencia el solapamiento terminológico. Sus referencias lexicográficas al género no se importan como una regla visual determinista.

Las entradas [blazer](https://dictionary.cambridge.org/us/dictionary/english/blazer), [coat](https://dictionary.cambridge.org/us/dictionary/english/coat) y [knitwear](https://dictionary.cambridge.org/dictionary/english/knitwear) ayudan a separar chaqueta formal, exterior habitual de abrigo y construcción de punto. No se exige una insignia, color distinto al pantalón ni lana como fibra: esos ejemplos o acepciones no constituyen una definición universal para Mati.

La [entrada polera del Diccionario de americanismos de ASALE](https://www.asale.org/damer/polera) registra variación regional: camiseta de manga corta en Bolivia/Chile, otros usos de camiseta o suéter en otros países. Se comprobó la entrada mediante el índice web de la fuente primaria; la apertura directa respondió 404 en esta sesión. Se declara esa limitación de acceso y no se atribuye a ASALE la definición comercial de Mati.

La [ontología oficial de Fashionpedia](https://github.com/KMnP/fashionpedia-api/blob/master/data/demo/category_attributes_descriptions.json) agrupa externamente shirt,blouse y separa partes/categorías del cuerpo; su [artículo original](https://arxiv.org/abs/2004.12276) sustenta la utilidad de relaciones conceptuales y atributos. Es inspiración estructural, sin importar sus IDs, labels o pesos ni asumir correspondencia uno a uno con Mati. Las reglas detalladas de distinción son propuestas derivadas de las descripciones Mati, no una validación científica de Fashionpedia.

Las entradas Cambridge se verificaron en resultados indexados de sus páginas primarias; varias aperturas directas genéricas respondieron 403. El JSON registra URLs, fecha/tipo de acceso y alcance. Se parafrasean rasgos con cautela; no se copia una ontología completa ni se usan blogs como autoridad.

## Corpus curado y evaluación congelada

El corpus incluye unidades pequeñas completas: una categoría, una comparación o una explicación. No parte por tamaño fijo; el máximo observado es 57 palabras contando título y contenido, sin asegurar que sean 128 tokens de un tokenizer. Cada perfil textual debe informar truncaciones reales. **No se recorta ni modifica el corpus después de observar resultados de un modelo.**

| Tipo | Documentos / chunks | IDs |
| --- | ---: | --- |
| taxonomy_category | 11 / 11 | category:1, category:2, category:3, category:4, category:5, category:6, category:7, category:8, category:9, category:10, category:11 |
| comparison | 6 / 6 | comparison:camisa-blusa, comparison:blazer-abrigo, comparison:polera-knitwear, comparison:vestido-conjunto, comparison:lower-garments, comparison:cold-weather |
| color_knowledge | 2 / 2 | color:family-vs-variant, color:observed-vs-commercial |
| technical_explanation | 4 / 4 | technical:text-vs-image, technical:rag-vs-tools, technical:similarity, technical:retrieval-only |

Cada documento tiene ID estable, source, source_version, status y metadata de procedencia/revisión. Cada chunk tiene sección, contenido y hash. status=active habilita este corpus curado de ensayo; **no significa aprobación de experto humano**. El JSON conserva review_status y autor de curación. Se excluyen logs, auditorías crudas, diffs, tests, código, valores dinámicos y políticas de tienda no verificadas.

Input de embedding reproducible: título + LF + sección + LF + contenido UTF8 exacto. Cada modelo añade sólo el prefijo declarado en su perfil. SHA de chunk = SHA256 del contenido UTF8 exacto. SHA de documento = SHA256 de los inputs de sus chunks ordenados, separados por dos LF. El contrato evita vectores con contenido nuevo y hash antiguo. No se mezclan embeddings textuales con SigLIP, incluso si las dimensiones coinciden.

La evaluación tiene 48 positivas y 16 negativas separadas; las positivas incluyen once nombres exactos, once paráfrasis inglesas, seis paráfrasis españolas, ocho comparaciones ES/EN, cuatro typos, dos preguntas de color, cuatro técnicas y dos consultas de rol conceptual. Relevancia se etiqueta con IDs explícitos, sin revisar después del benchmark. Las consultas de categoría simple etiquetan la definición principal; las comparaciones etiquetan ambas definiciones y la unidad comparativa. No es anotación humana exhaustiva de todos los textos parcialmente relacionados.

El caso esencial **q029** pregunta «¿Cuál es la diferencia entre una camisa y una blusa?» y fija category:3#definition, category:4#definition y comparison:camisa-blusa#comparison. La comparación tiene grado 3; las definiciones grado 2. Recall trata cualquier grado positivo como relevante; nDCG sigue la fórmula del protocolo congelado. Faltantes/abstenciones mantienen el denominador positivo N48. El benchmark negativo N16 no diluye esa tasa.

Los ocho negativos sin evidencia incluyen preguntas fuera del dominio y políticas Mati no documentadas. Los ocho dinámicos preguntan stock, precio, pagos, reservas, inventario y disponibilidad. Estos últimos declaran expected_policy=requires_tool, status=insufficient_evidence y chunks vacíos: el corpus no inventa valores y esta fase no añade las Tools comerciales necesarias. Una similitud alta contra un texto de categorías o un texto sobre RAG/Tools no autoriza responderlos.

RAG recupera definiciones estables; disponibilidad y precio pertenecen a la base comercial/Tools Laravel. Compatibilidad de outfits requiere una herramienta futura específica. El contenido recuperado se trata como datos y citas, sin SQL generado, paths arbitrarios o acciones ejecutables. No se implementa un LLM en este artefacto.

## Uso futuro de taxonomía con visión y aprendizaje

Una clase externa mixta shirt,blouse puede aportar un rol UPPER futuro, pero no decidir CAMISAS3 frente a BLUSAS4. La taxonomía podría aportar compatibilidad con un category_hint disponible antes de selección, como feature futura con versión explícita. No se usa RAG en tiempo real como reemplazo del selector ni se emplea final_product_category como feature anterior si sólo aparece después. El contrato y precondiciones de eventos se desarrollan en el informe06.

Un experimento posterior con SigLIP deberá comparar prompts actuales contra prompts derivados de una versión taxonómica revisada. Mantendrá modelo/revisión, imágenes, preprocess y scoring iguales; congelará prompts y gate antes de inferencia y conservará duplicados de una imagen en un solo split. Necesita etiquetas comerciales validadas y revisión de casos como13 antes de atribuir mejora semántica. **Esta fase no modifica prompts ni declara una mejora de clasificación visual.**

Se conserva la estrategia actual FULL para clasificación/embedding visual y NONE para color comercial. Ningún pipeline de segmentación se integra por esta taxonomía. La propuesta no entrena pesos ni convierte clics/curación de Codex en eventos humanos de entrenamiento.

## Versionado, hashes y validación

Artefactos 1.0.0 están congelados antes del benchmark; cualquier cambio real de definición, fuente, chunking, alias o relevancia requiere una nueva versión/identidad o invalidación determinista. La idempotencia de ingestión y el guard contra misma versión con contenido/procedencia distintos son responsabilidad del almacenamiento, documentados en04. Los hashes exactos de esta entrega permiten comprobar que las entradas de evaluación no se acomodaron a resultados.

| Artefacto | SHA256 prebenchmark |
| --- | --- |
| services/mati-ai/resources/taxonomy/commercial-categories-v1.json | 5ba69a10a5065eef1e95ac96d38e9c4e6ac62e607dac63791232563cd0bdd261 |
| services/mati-ai/resources/taxonomy/mati-taxonomy-v1.json | 5ef5ecc95ee9f19bfb4fb2d1370f21610ac1b4b9ed97fdb63adb72e2dc267e84 |
| services/mati-ai/resources/rag/corpus-v1.json | d1fd24f3a18e9c169c6197e6bf12da8a663359010ffc271c33918eb8893a3fe2 |
| services/mati-ai/resources/rag/evaluation-v1.json | c09509b42e33622e9613ba2f64207947d78b62260d355f63da3c951304b6bdb4 |

Se ejecutó services/mati-ai/.venv/bin/python -m pytest -q services/mati-ai/scripts/semantic/test_taxonomy.py: **15 passed in 0.07s**. Evidencia: storage/app/ai-semantic/taxonomy-validation.json. Se verifican unicidad, IDs/nombres/descripción/tallas frente al snapshot derivado de fuente, aliases sin ambigüedad inexplicada, referencias, ausencia de ciclos/self-confusion, enlaces comparativos, campos/versiones, hashes exactos, N48/N16 y caso esencial. Incluye controles negativos que detectan alias duplicado sin explicación y ciclo conceptual.

Estos tests prueban coherencia y preservación de los datos de entrada, **no** precisión semántica humana, calidad visual, calidad general de retrieval ni E2E desplegado. Los resultados textuales reales, licencias del modelo elegido y gates se presentan aparte. Pendientes de dominio: frontera camisa/blusa, alcance POLERA, agrupación heterogénea8, accesorios/calzado, talla54 e imagen13.
