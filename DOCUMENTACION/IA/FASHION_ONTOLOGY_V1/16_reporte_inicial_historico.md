> Evidencia histórica de la elaboración inicial 1.0.0. Sus resultados no validan la revisión 1.1.0.

# Fashion Ontology V1 — reporte de diseño y validación

Versión de ontología y schema: **1.0.0**. Estado: **DRAFT_FOR_REVIEW**. La validez automática no congela ni aprueba el dominio. El cierre verificable de pruebas, hashes y preservación se registra en `validation_result.json`, `test_result.json`, `preservation_result.json` y los archivos Git de este directorio.

**Cierre:** 13 checks PASS, cero errores; suite 87/87 PASS y comprobaciones finales afectadas 14/14 PASS; 380/380 archivos protegidos idénticos, HEAD y estado Git previo conservados; 52 archivos nuevos de entrega. La DB viva permanece sin verificar. No hay aprobación de congelamiento.

## A. Resumen ejecutivo

Se creó el contrato semántico central propuesto para MORE / Mati Boutique: identidades de prendas, vocabularios ES/EN, atributos con tipos y aplicabilidad, estados, relaciones espaciales/contextuales y puente comercial. La estructura separa qué es, cómo se ve, cómo ajusta, cómo se utiliza y qué estilos admite. La ontología vive en JSON con JSON Schema 2020-12 y referencias resueltas por una CLI independiente; no se oculta en Python ni exige OWL. Los scripts son herramientas de validación/exportación de artefactos de diseño.

## B. Fuentes, licencias y reutilización conceptual

Se investigaron 16 fuentes primarias, con comparación de Fashionpedia, DeepFashion/DeepFashion2, ModaNet, schema.org, Google Merchant, GS1, Shopify, terminología textil profesional, FIT, V&A y Emily Post. El registro contiene 17 mappings conceptuales externos; no se copiaron taxonomías completas, fotos, anotaciones ni definiciones protegidas. Se reutilizan ideas de ejes independientes, atributos por parte, tipos/unidades, procedencia y mappings con pérdidas explícitas.

Fashionpedia declara CC BY 4.0 para ontología/anotaciones, software BSD y derechos fotográficos separados. ModaNet restringe las anotaciones con CC BY-NC 4.0. DeepFashion2 no quedó habilitado comercialmente en la revisión; no se transfiere la licencia de DeepFashion original. schema.org distingue términos del sitio y LICENSE del repositorio; GS1 WebVoc declara Apache 2.0, sin extenderla automáticamente a GPC; Shopify declara MIT para su taxonomía. Google, glosarios y fotos necesitan revisión específica antes de ingestión. Evidencia, links de licencia, atribución y límites están en `02_investigacion_fuentes.md` y `research_sources.json`.

## C. Core taxonomy

Hay **12 familias, 47 categorías y 22 subcategorías**, con IDs estables y labels/aliases ES/EN. Las 81 identidades son distintas de valores de atributos.

| Familia | Categorías ontológicas |
|---|---|
| UPPER | shirt, blouse, tshirt, polo, sweater, cardigan, tank_top, top, sweatshirt |
| LOWER | trousers, jeans, shorts, skirt, leggings |
| OUTERWEAR | blazer, jacket, coat, cape, vest |
| ONE_PIECE | dress, jumpsuit, romper |
| MULTI_PIECE | coordinated_set |
| FOOTWEAR | sneaker, boot, pump, loafer, sandal, flat_shoe |
| BAG | handbag, backpack, waist_bag, travel_bag |
| HEADWEAR | hat, cap |
| NECKWEAR | scarf, tie |
| JEWELRY | necklace, bracelet, ring, earring, brooch |
| EYEWEAR | glasses |
| ACCESSORY | belt, watch, gloves, pocket_square |

Las subcategorías incluyen tailored/chino/cargo trousers, jeans constructivos, overshirt, sweater_vest, cardigan abierto, sudadera con capucha, tipos de jacket/coat, vestidos/faldas constructivos, tote/clutch, variantes de bota, sunglasses, bow_tie y beanie. Wide_leg es leg_shape; cropped es largo; oversized es holgura de diseño. Knit no se vuelve una categoría genérica: estructura y tipo de prenda se conservan separados. Las definiciones y fronteras se detallan en documento03 y JSON, pendientes de revisión Mati.

## D. Atributos globales y específicos

**68 atributos**, con fuente, tipo de etiqueta, observabilidad, dificultad, ámbito semántico y políticas de UNKNOWN/N/A. Los compartidos incluyen color observado/familia/rol/temperatura perceptual, patrón, composición, materiales no textiles, estructura/ligamento/punto/familia de tejido, superficie, acabado, opacidad, fit, silueta, construcción, cierre, estilo/estética, código/formalidad/ocasión, estaciones/clima y segmentos comerciales.

Los específicos cubren tiro/pernera/cintura/entrepierna, largos por grupo, mangas/escote/cuello/bajo, solapa/aislamiento, tacón/punta/caña/suela, porte/correa/forma/capacidad, montura/lente/material, caja/indicación de reloj, forma de joyería y ancho de cinturón. Body zones y layering son estructuras. Target gender y edad provienen del catálogo, nunca de inferencia sobre una persona.

La matriz exportada tiene **816 pares atributo × familia**, más refinamientos por categoría y componente. Required significa slot y estado presentes, no un valor forzado. Un objeto sin componente textil confirmado permite N/A en los ejes textiles con evidencia de ausencia; no se deduce N/A de datos faltantes. Ver documentos04/05, `attribute_applicability.json` y ambas matrices CSV.

## E. Body Zones

**18 zonas** en árbol funcional con agregados y partes. Se usan primary_zone, secondary_zones y coverage_role; covers, worn_at, carried_at y contacts son distintos. Waist es una frontera bajo FULL_BODY; los agregados no duplican descendientes. Bolso en hombro/mano no significa cobertura del torso. Conjunto agrega piezas con roles conservados; ausencia de vista no prueba piel descubierta. Documento06 y `body_zones.json`.

## F. Layering

Cinco roles de uso: BASE, INTERMEDIATE, OUTER, OVERLAY y ACCESSORY. Orden parcial por instancia, zona y contexto; INNER es relativo. Los cinco escenarios incluyen camiseta–camisa–blazer–abrigo, falda sobre pantalón y bufanda sobre abrigo. Las reglas distinguen structural_constraint, styling_preference, soft_rule, trend_exception y hard_incompatibility. No hay incompatibilidad dura de moda afirmada. No se permiten ciclos de orden de instancias dentro del mismo contexto/zona; las posibilidades entre categorías no se convierten en un orden universal.

## G. Style

**10 estilos núcleo + 4 estéticas**: los estilos son minimalist, classic, preppy, streetwear, bohemian, sport_inspired, romantic, utilitarian, avant_garde y western_inspired; las estéticas son old_money, y2k, retro y techwear. Es una convención de MORE, con revisión cultural/local pendiente. Estilo es multi-label y admite scores independientes con evidencia/tipo de confianza; no se exige suma uno ni se inventan scores reales. Documento07.

## H. Formality

Ordinal contextual MORE de cinco grados, 0 muy informal a 4 ceremonial. Orden no implica distancias iguales; no equivale automáticamente a dress code. Se descarta precisión continua sin rúbrica. Los anclajes y ejemplos necesitan Mati; no se mide desempeño ni se fijan thresholds.

## I. Occasions

Diez contextos multi-label: everyday, workplace, ceremony, wedding_guest, celebration, date, travel, exercise, outdoor_leisure y beach_pool. Se separan de siete dress codes y del horario evening. Seasonality separa estación calendárica de clima y colección comercial. PV/OI tienen destinos candidatos; RESORT/CAPSULE/PERMANENTE/CARNAVALERA/MUNDIAL tienen NO_DIRECT_MAPPING calendárico.

## J. Materials / fabrics / textures / patterns

**12 fibras + 8 materiales no textiles**, estructuras tejida/punto/no tejido, ligamentos/puntos y familias de tejido independientes. Composición completa suma 100% por componente con tolerancia declarada; parcial no inventa porcentajes. Satén no demuestra seda ni denim algodón. Ribbed observado no confirma punto rib; pleated/quilted son construcción, sheer opacidad y distressed acabado. Hay **13 patrones**, con check como padre de variantes; plaid no se fusiona automáticamente con tartan. Documento08.

## K. Accessories y footwear

Accesorios se descomponen en bolsos, cabeza/cuello, joyería, gafas y objetos funcionales. Forma de lente y montura son atributos separados; comparten vocabulario inicial pendiente de guía. Relojes tienen caja/indicación/correa/material sin catálogo relojero infinito. Calzado prepara seis categorías y atributos útiles; medidas requieren ficha/escala. No se declara soporte comercial real donde no se observaron productos. Documento09.

## L. Commercial mapping

Se preservan **11 IDs/nombres** del snapshot SELECT-only de 2026-10-05. POLERA=11 tiene candidatos camiseta y alternativas ambiguas pendientes; el usuario confirmó que no tiene mapping definido. POLOS Y KNITWEAR=8 mantiene el bucket comercial con destinos polo/sweater/cardigan/top. ACCESORIOS=9 ofrece candidatos 1:N sin productos observados y sin aprobación de negocio. ABRIGOS conserva el jersey histórico sin relabeling automático. La relación inversa es consulta de candidatos, no equivalencia invertible. Documento10 y `commercial_mapping.json`.

## M. ML label schema

68 registros y tres ejes taxonómicos adicionales. **48 atributos conditional y 20 no** para aprendizaje visual; conditional es hipótesis, no permiso de entrenamiento. UNKNOWN y N/A enmascaran pérdidas; ausencias multi-label necesitan adjudicación/completitud. Medidas, composición, segmentos comerciales y prestaciones no se inventan desde fotos. Métricas futuras por tipo y familia/atributo se documentan sin medirlas. Los 17 mappings públicos explicitan dirección/pérdida y NO_DIRECT_MAPPING; DeepFashion2 style no significa estilo cultural. Documento11.

## N. General vs specialist heads

Los nombres GENERAL, SPECIALIST, EXISTING_COLORIMETRY y contratos espaciales/contextuales designan ámbitos semánticos. No se eligen encoder, número de redes, multi-head físico, especialistas entrenados ni MoE. La ontología guía futuros labels, soporte y métricas; el dataset no dicta qué conceptos deben existir.

## O. Product Intelligence contract

Contrato separado versionado: identity, taxonomy, attributes, colors, materials, styles, spatial_semantics, usage_semantics, evidence, model_metadata y human_review. Valores centrales se referencian desde vistas de sección. KNOWN/UNKNOWN/N/A tienen formas distintas, sin null ambiguo. Evidencia/modelo/confianza/revisión se conservan separados; accepted/corrected/rejected no se confunden. Composición, espacio, capas, color observado e aislamiento tienen payloads específicos. El ejemplo es sintético y explícito; no contiene stock/precio/SKU ni perfil corporal del usuario. Documento12 y schema/fixture.

## P. Human review

SAFE TO FREEZE: separación de capas/ejes, estados, IDs/idiomas y trazabilidad técnica. NEEDS MATI REVIEW: dominio de prendas/atributos, mappings, casos frontera, escalas y afinidades, cuerpo/capas. RESEARCH INCOMPLETE: catálogo vivo no accesible, grupos sin productos, propiedades exactas y permisos comerciales de datasets/fotos. Esta clasificación es recomendación por asunto; V1 completa permanece sin congelar. Documento14.

## Q. Open questions

Hay 12 preguntas concretas para Limber/Mati: alcance de POLERA; contenido de ACCESORIOS; buckets de punto; jersey en ABRIGOS; camisa/blusa y blazer/abrigo; componentes de CONJUNTOS; núcleo de estilos/códigos/formalidad; fuentes textiles/clima; anclas de largo/fit/tiro; eje propio de puños; talla histórica ID54; responsables/desacuerdos de revisión. El decision log registra 28 decisiones y sus límites. No se requiere respuesta para preservar el borrador; sí para congelarlo.

## R. Archivos creados

Los documentos01–15 solicitados, este reporte y registros de investigación/auditoría/preservación/validación. Todos los JSON, schemas, matrices y hashes están bajo `services/mati-ai/resources/fashion_ontology/v1/`. CLI, tests, exportador y README están bajo `services/mati-ai/scripts/fashion_ontology/`. El inventario exacto y tamaños se incluye en `created_files.json`. Los archivos temporales de autoría se eliminaron; los maestros finales son JSON.

## S. Archivos modificados

Ningún archivo preexistente del proyecto fue modificado por esta tarea, según el resultado de comparación de archivos protegidos. Ediciones durante la elaboración afectaron únicamente archivos nuevos de los tres directorios de entrega. Los siete archivos tracked ya modificados al comenzar siguen siendo cambios previos; no se atribuyen a esta ontología.

## T. Tests

Pruebas CPU y offline de consistencia/fixtures: parse estricto, schemas y referencias, IDs/idiomas/aliases, mappings/snapshot, aplicabilidad/labels/matrices, tipos/estados/evidencia/revisiones, composición, zonas y capas. Incluyen casos válidos y mutaciones inválidas. El cierre exacto, cantidad y salida están en `test_result.json` y `test_output.txt`. No se ejecutan entrenamiento, inferencia, benchmarks o tests GPU.

Resultado: **87/87 PASS** en suite completa (382.171 s) antes de vincular los cinco payloads structured directamente a sus defs del schema; después, **14/14 PASS** (57.595 s) para los casos afectados y las dos nuevas comprobaciones directas de JSON Schema. Son ejecuciones distintas: no se afirma que se haya repetido la suite completa de 89 casos sobre el último snapshot. El refuerzo final evita que color/aislamiento reciban formas de otros ejes y elimina una forma de layering duplicada en Python.

## U. Validator result

`validation_result.json` registra ejecución independiente de la CLI read-only, versión, counts, checks y errors. Domain_freeze_approved permanece false aun cuando todos los checks sean PASS. Los hashes se regeneraron después de los cambios finales; schema válido no equivale a dominio validado.

Resultado final independiente: **valid=true, 13 checks PASS, errors=[], read_only=true, domain_freeze_approved=false**. La CLI no cambió ningún artefacto al ejecutarse. Los checks abarcan parse, schema, IDs, referencias/jerarquía, aliases, aplicabilidad/labels, relaciones, zonas/capas, categorías comerciales, temporadas documentales, matrices, Product Intelligence y hashes/summary.

## V. Counts

| Conteo | Resultado |
|---|---:|
| Familias | 12 |
| Categorías ontológicas | 47 |
| Subcategorías | 22 |
| Identidades taxonómicas | 81 |
| Atributos | 68 |
| Estilos núcleo / estéticas | 10 / 4 |
| Fibras / materiales no textiles | 12 / 8 |
| Patrones | 13 |
| Zonas corporales | 18 |
| Conceptos bilingües totales | 449 |
| Relaciones concretas | 97 |
| Tipos de relación | 14 |
| Pares matriz de aplicabilidad | 816 |
| Categorías comerciales preservadas | 11 |

Concepts suma identidad, descriptores de atributos, valores de vocabularios, zonas y roles; excluye IDs de relaciones y predicados. Relations suma 92 relaciones centrales y 5 escenarios de capas. El resumen generado explica el denominador, sin confundir 14 estilos+estéticas con 10 estilos núcleo.

## W. Preservación

Se comparan hashes actuales contra baseline de archivos protegido antes de la elaboración; el detalle final está en `preservation_result.json`. SigLIP, OCR, colorimetría, embeddings, RAG, segmentación, configuración y fuentes históricas quedaron fuera de edición. No hubo escrituras comerciales ni migraciones; no se ejecutó add/commit/push.

Resultado: **380/380 hashes idénticos**, cero archivos protegidos cambiados/desaparecidos, HEAD idéntico, estado previo conservado y git diff --check exit 0. Los artefactos nuevos se inventariaron sin incluir caches/fixtures; los archivos temporales de autoría y fixtures se eliminaron.

El par histórico de preservación gate V2 (2026-10-06) confirma 34 huellas comerciales, 134 vectores visuales, imágenes, AI counts y fingerprints RAG entre esas dos capturas. **No prueba conservación de la DB viva durante esta tarea**. PHP/Docker no proporcionaron un runtime DB accesible; el daemon Docker estaba ausente y no se arrancó. PostgreSQL/embeddings/AI counts actuales quedan UNVERIFIED_NO_LIVE_DB_ACCESS. Classification FULL IMAGE, visual embedding FULL IMAGE, automatic commercial color NONE, gate RAG no aprobado y AUD-07 mitigado permanecen sin cambios operativos.

## X. git diff --stat

La salida final íntegra está en `git_diff_stat.txt`. Git muestra siete archivos tracked con 33 insertions y 2 deletions, todos previos a esta tarea. Los artefactos nuevos están untracked y por eso no aparecen en el stat normal; `created_files.json` aporta su inventario. La comparación de hashes permite distinguir esta entrega de los cambios previos.

```text
 compose.yaml                                  |  1 +
 routes/web.php                                |  1 +
 services/mati-ai/Dockerfile                   |  1 +
 services/mati-ai/src/mati_ai/api/v1/routes.py |  2 ++
 services/mati-ai/src/mati_ai/core/config.py   |  6 ++++++
 services/mati-ai/src/mati_ai/core/http.py     |  8 ++++++--
 services/mati-ai/src/mati_ai/main.py          | 16 ++++++++++++++++
 7 files changed, 33 insertions(+), 2 deletions(-)
```

## Y. git status

La salida final íntegra está en `git_status.txt`, incluido el diagnóstico del acceso Git sobre UNC cuando aparece. Los únicos directorios nuevos de esta entrega son documentación Fashion Ontology, resources/fashion_ontology y scripts/fashion_ontology; el resto del estado previo se conserva. HEAD se compara con baseline. No se hizo staging, commit ni push. Esta fase termina con el reporte, manteniendo las revisiones de dominio abiertas.

Git detecta dos líneas nuevas agregadas al estado resumido: `?? DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/` y `?? services/mati-ai/scripts/fashion_ontology/`. Los resources nuevos están dentro del directorio `services/mati-ai/resources/` que ya era untracked al inicio. No hay líneas previas eliminadas ni cambios nuevos inesperados. Git emite el diagnóstico preexistente `unable to open .git/sequencer/todo: Function not implemented` sobre UNC; los comandos de estado/diff entregaron resultados y HEAD no cambió.
