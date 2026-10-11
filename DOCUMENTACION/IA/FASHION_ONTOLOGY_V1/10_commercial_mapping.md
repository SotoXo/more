# 10. Puente entre catálogo comercial y Fashion Ontology V1

El puente preserva cada ID y nombre comercial de Laravel. Una categoría de venta puede cubrir varios conceptos ontológicos; esa posibilidad no constituye una reclasificación de sus productos. `commercial_mapping.json` contiene las referencias formales. El análisis histórico se conserva sin cambios en `catalog_audit.json`; la nueva verificación del dump y sus relaciones está en `review_catalog_audit.json` (2026-10-08). Las decisiones humanas aprobadas del prompt corrigen el estado provisional anterior y no reclasifican productos.

## Evidencia y límite de esta inspección

La revisión inicial documentada el 2026-10-07 pudo leer archivos locales y código de contratos. Su historial de acceso se conserva a continuación. **No pudo consultar PostgreSQL en vivo**: en la inspección inicial no había PHP, Docker ni psql disponibles en PATH y WSL no estaba accesible al proceso. El coordinador obtuvo después lectura mediante WSL con escalación, sin PHP disponible; el shim Docker no tenía integración y Docker Windows no encontró el daemon `dockerDesktopLinuxEngine`. No se iniciaron contenedores ni se alteró el entorno. No se invocó un controlador Laravel ni un análisis de imagen. El directorio `storage/app/ai-rag` devolvió acceso denegado en la inspección local; sus snapshots originales se conocen por las referencias de artefactos y la documentación previa, no por una nueva lectura directa.

| Evidencia local | Alcance comprobado | Límite |
| --- | --- | --- |
| `services/mati-ai/resources/taxonomy/commercial-categories-v1.json` | Export SELECT-only fechado 2026-10-05T15:14:55Z: 11 categorías, IDs, nombres, descripciones, estados y 54 tallas | Foto histórica, no consulta actual de DB |
| `storage/app/ai-audit/catalog_taxonomies.json` | 11 categorías, 12 marcas, 55 colores activos, 54 tallas y 10 patrones enumerados | Vista de taxonomías expuestas, no todas las filas comerciales |
| `storage/app/ai-semantic/catalog-final.json` | Huellas de tablas, filas canónicas visuales, conteos e imágenes de una auditoría anterior | Evidencia histórica |
| `storage/app/ai-rag-gate-v2/preservation-before.json` / `preservation-after.json` | Último par local, cierre 2026-10-06T03:14:58Z: 34 huellas comerciales, 134 embeddings visuales, conteos AI y RAG | Se compararon snapshots entre sí; no son mediciones antes/después de esta tarea |
| `storage/app/catalog-import/catalog_real_100.json` | 120 fichas de productos importados, sus descripciones y metadatos | El nombre del archivo es histórico; no tiene 100 ni 134 entradas |
| `DOCUMENTACION/IA/RAG_TAXONOMIA/01_taxonomia_mati.md` | Resumen documentado de maestros y distribución N134 fechado 2026-10-05 | Fuente documental secundaria; originales de `ai-rag` inaccesibles |
| `more_db_actual.sql` | IDs de categorías, marcas y siete temporadas de un dump antiguo | Solo se leyó texto; no se importó ni ejecutó SQL |
| Modelos `Prenda`, `PrendaVariante`, `Color`, `Talla`; seeders | Campos y relaciones vigentes en código | Un seeder describe intención y no prueba el estado actual de la DB |

Los SHA-256 de los archivos leídos están en `catalog_audit.json`. El snapshot de categorías referencia el maestro original con SHA-256 `8079a475b267cbeb99c2e8387d2e011dc92c2bc719f06f97ffbac37853033099`; ese hash es procedencia declarada, no se recalculó sobre el archivo inaccesible.

## Identidades comerciales y correspondencia propuesta

Todos los estados de las once categorías eran activos en el snapshot y todos los padres comerciales eran `NULL`. La jerarquía de la ontología es una capa independiente. Los conteos N134 proceden del resumen previo; los conteos de manifiesto N120 se recalcularon leyendo el archivo de importación.

| ID | Nombre comercial exacto | Talla sistema / grupo | N134 histórico | N120 manifiesto | Conceptos candidatos / criterio |
| ---: | --- | --- | ---: | ---: | --- |
| 1 | ABRIGOS | ALFA / SUPERIOR | 13 | 10 | Abrigo y capa; chaqueta o suéter histórico como candidatos del puente; resolución por producto y criterio Mati |
| 2 | BLAZERS | ALFA / SUPERIOR | 10 | 10 | Blazer; correspondencia nominal, sin derivar formalidad o material |
| 3 | CAMISAS | ALFA / SUPERIOR | 20 | 19 | Camisa; camisa de punto sigue siendo camisa cuando su construcción lo respalda |
| 4 | BLUSAS | ALFA / SUPERIOR | 8 | 8 | Blusa; los límites con camisa necesitan guía humana |
| 5 | VESTIDOS | ALFA / SUPERIOR | 18 | 18 | Vestido; un mono no se convierte en vestido por compartir zonas corporales |
| 6 | FALDAS | NUMERICA / INFERIOR | 7 | 7 | Falda; largo y A-line son atributos, no categorías nuevas |
| 7 | PANTALONES | NUMERICA / INFERIOR | 16 | 14 | Pantalón y sus subtipos constructivos; wide-leg permanece en forma de pierna |
| 8 | POLOS Y KNITWEAR | ALFA / SUPERIOR | 22 | 17 | Polo, suéter y cárdigan, con resolución por producto |
| 9 | ACCESORIOS | UNICA / GENERAL | 0 | 0 | Ámbito comercial aprobado en decisión 3; complementos indicados, calzado excluido; ejemplos locales pendientes |
| 10 | CONJUNTOS | ALFA / SUPERIOR | 3 | 3 | Conjunto auténtico con asociación comercial explícita; venta completa o por componentes con registros/inventario necesarios |
| 11 | POLERA | ALFA / NULL | 17 | 14 | Decisión 1: camiseta/T-shirt, incluyendo Henley, manga corta y larga; atributos independientes |

**No utilizar IDs de tablas históricas del documento `catalogo_real_100_prendas.md` para producir el mapping.** Allí existe una tabla antigua con POLERA=1 y POLOS Y KNITWEAR=9. La exportación 2026-10-05 y el dump corroboran las identidades de la tabla anterior: POLERA=11, POLOS Y KNITWEAR=8, ACCESORIOS=9. Se preserva el historial, sin corregir ese archivo.

## POLERA: interpretación comercial aprobada con alcance local

La decisión humana 1 fija **POLERA ID11 = camiseta/T-shirt**, incluida manga corta, manga larga y tipo Henley. `commercial_mapping.json` mantiene ID y nombre y dirige el ámbito de lookup exclusivamente a `garment.category.tshirt`; retiró sweater y sweatshirt como destinos candidatos de ese puente. Longitud de manga, escote, cuello y cierres siguen siendo atributos independientes. Sweater, cardigan, sweatshirt y coat no se incorporan automáticamente a POLERA.

Se conserva la evidencia del manifiesto N120: 14 entradas, entre ellas `08071695` camiseta de manga corta con estampado, `7231397805` camiseta de manga larga, `hbeu50481923_001` T-shirt documentada en texto y `4813-365-712` camiseta con cuello panadero y botones. Son textos históricos, no adjudicación visual actual de todos sus atributos. Una Henley puede mantenerse tshirt sin crear una subcategoría adicional; no se fuerza un valor de cuello cuando la evidencia no permite elegirlo.

`polera` es alias contextual del catálogo Mati, español, categoría comercial ID11. Los usos regionales fuera de ese ámbito conservan ambigüedad documentada en `ontology.alias_ambiguities` con scope `regional_non_mati`. No se declara sinónimo universal de suéter, polo o cuello alto. `Polo Ralph Lauren` en un título identifica una marca y no prueba identidad polo.

La descripción y el grupo de talla NULL históricos se conservan. La decisión comercial no modifica registros ni convierte el mapping en etiqueta de entrenamiento. Si un producto histórico tiene evidencia fiable discrepante, conserva POLERA comercial y recibe identidad ontológica respaldada, o UNKNOWN con candidatos y revisión administrativa. `review_status=NEEDS MATI REVIEW` remite a ejemplos, definiciones y adjudicaciones aún pendientes; `human_decision` registra que esta interpretación comercial ya está aprobada.

## POLOS Y KNITWEAR: unión de venta, varias construcciones

Los 17 ejemplos del manifiesto documentan tres construcciones distintas: polo, suéter y cárdigan. `6674-404-507` es un polo con cuello y botonadura; `1634-404-401` es un polo de manga corta; `7563323401` es un cárdigan con cierre delantero; `1296275004` es un suéter grueso de cuello alto. El puente es 1:N, con selección por producto.

No convertir `knitwear` en una categoría exclusiva para todo tejido de punto. Una camiseta puede ser de jersey y una camisa puede ser de punto sin dejar de tener construcción de camiseta o camisa. El manifiesto contiene además una CAMISA con título `KNITTED MERINO WOOL SHIRT NAVY`, que demuestra por qué tejido y categoría deben ser ejes independientes. El destino residual top se retiró del puente aprobado, conservando su ID como concepto ontológico. Productos históricos atípicos no se fuerzan a polo/suéter/cárdigan: conservar UNKNOWN y candidatos pertinentes cuando falta evidencia.

La decisión 2 aprueba conservar la unión comercial con resolución individual de polo/suéter/cárdigan; no modifica la categoría global para corregir diferencias entre productos. El motivo histórico por el que Mati unió polos y knitwear no puede deducirse de datos. La explicación respaldada es que su descripción comercial los agrupa explícitamente; una motivación de merchandising necesita confirmación del negocio.

## ACCESORIOS: ámbito aprobado y calzado separado

La decisión humana 3 aprueba para **ACCESORIOS ID9**: bolsos, mochilas, cinturones, gafas, relojes, joyería, bufandas, pañuelos, gorras, sombreros y guantes. El puente conserva sus conceptos existentes de bag, headwear, neckwear/scarf, jewelry, eyewear y accessory en esos ámbitos. Corbata/tie se retiró del mapping comercial, sin borrar su categoría ni bow_tie de la ontología. Las tipologías de bolso y la frontera scarf/pocket_square necesitan ejemplos locales antes de aprobar su guía de anotación.

**Footwear queda fuera de ACCESORIOS.** Mantiene `garment.family.footwear` independiente. Su categoría comercial futura está pendiente; la existencia histórica de tallas US/EU no prueba asociación al ID9. Esta revisión no crea una categoría comercial nueva ni propone trasladar calzado a ACCESORIOS.

El ID9 estaba activo con cero prendas en el corte N134 y en el manifiesto N120. El ámbito aprobado puede conservar vocabulario propuesto, pero esos ceros no acreditan un dataset entrenable. Un bolso incidental en una fotografía de vestido tampoco se convierte en el producto objetivo.

## Maestros y campos: conservar identidades, añadir semántica

**Colores.** La huella comercial histórica cuenta 56 filas; el listado activo leído directamente contiene 55 tonos y 15 familias. El documento anterior informa que ROJO VINO ID18 estaba inactivo. NEGRO1 y NEGRO CARBON17 son identidades distintas. CREMA3 pertenece a familia comercial BLANCO; no reescribir ese dato para ajustarlo a una familia ontológica beige/cream. Un futuro mapping puede expresar diferencias de granularidad. `hex_col`/`hex_dom_col` son referencias comerciales, no observaciones de píxeles. Las capas observed color, commercial color, color family y semantic color conservan evidencia independiente; no se cambia CIELAB/CIEDE2000 ni el guard actual de color automático `NONE`.

**Tallas y segmentos.** El snapshot enumera 54 tallas: ADULTO32, INFANTIL16 y BEBE6, con sistemas UNICA, ALFA, NUMERICA, CALZADO_US, CALZADO_EU, EDAD, ALTURA_CM y MESES. Estos identificadores describen opciones comerciales, no el ajuste visual ni las medidas del usuario. ID54 `XXXXXXXXL` figura en INFANTIL/ALFA/GENERAL: dato sospechoso para revisión Mati, preservado sin decidir si es válido o erróneo. La decisión 11 excluye valores no verificados de recomendaciones futuras y exige autorización humana antes de corrección comercial. `review_catalog_audit.json` verifica 13 variantes y 13 prendas del dump more_db_actual.sql: ninguna variante referencia talla54 mediante cod_tallas_prv, por tanto cero productos vinculados en ese dump únicamente. Las relaciones de DB viva permanecen no verificadas. El segmento vive en `tallas.seg_tal`; no crear un segmento de producto supuesto a partir de ese catálogo de opciones. `prendas.gen_pre` admite mujer/hombre/unisex en código/documentación; los 120 importados se reparten mujer89/hombre31 y no cubren toda la DB.

**Temporadas.** Se observaron/documentaron siete identidades; no reducirlas al seeder de cinco entradas. 1 PRIMAVERA-VERANO/PV y2 OTOÑO-INVIERNO/OI pueden aportar candidatos spring/summer y autumn/winter. 3 RESORT es una colección/contexto, 4 CAPSULE una edición, 5 PERMANENTE continuidad comercial, 6 CARNAVALERA y7 MUNDIAL campañas. Ninguna prueba por sí sola clima, aislamiento o `all_season`; usar `NO_DIRECT_MAPPING` donde corresponda. Todos los 120 productos del manifiesto tienen temporada PERMANENTE, así que ese archivo no respalda seasonality diferenciada.

**Marcas.** Se leyeron 12 IDs: ZARA1, BERSHKA2, STRADIVARIUS3, RALPH LAUREN4, HUGO BOSS5, MASSIMO DUTTI6, MANGO7, PULL&BEAR8, RESERVED9, COS10, PATITO11, PREMIER12. Marca no prueba estilo, autenticidad, composición o calidad.

**Patrones, texturas, materiales.** El snapshot activo enumera LISO, RAYAS, CUADROS, ESTAMPADO, FLORAL, GEOMÉTRICO, CAMUFLAJE, ANIMAL PRINT, DEGRADADO y OTRO. El código/resumen documenta diez texturas y doce materiales. `DENIM` aparece en el maestro comercial de texturas: el puente ontológico debe registrar que su sentido constructivo corresponde a fabric, sin mover ni renombrar la fila comercial. `TEJIDA` mezcla estructura con apariencia; `ESTAMPADO`, `TEXTURIZADA` y `OTRO` son términos amplios y no se fuerzan a labels estrechos. Cero relaciones históricas `prenda_materiales`/`prenda_texturas` significa ausencia de anotación, no `NOT_APPLICABLE`. Composición material requiere evidencia documental/etiqueta/humana; el aspecto visual de punto no prueba lana.

**Campos.** Prenda mantiene ID/nombre/descripción/modelo, categoría, marca, temporada y género. Variante mantiene talla, color, tipo de paleta, SKU y código de barras; sus relaciones de paleta tienen principal y orden. `prenda_materiales` incorpora porcentaje y orden. Precio, costo, stock e inventario permanecen en Laravel y se excluyen de Product Intelligence. `modelo_pre` es referencia comercial de modelo, no versión ML. Se leyeron estos contratos sin crear tablas ni migraciones.

## Semántica del mapping

El registro debe incluir `commercial_category_id`, `commercial_name`, `ontology_targets`, `mapping_type`, `notes` y `version`; añadir evidencia y estado de revisión permite separar candidatos de equivalencias verificadas. La resolución aplica al **artículo objetivo**, no a todos los objetos de la imagen. El mapping solo propone una relación entre conceptos; ninguna puntuación ni automatización comercial se implementa aquí.

Las referencias propuestas siguen IDs formales `garment.category.*`: para POLERA, `garment.category.tshirt`; para POLOS Y KNITWEAR, `garment.category.polo`, `garment.category.sweater` y `garment.category.cardigan`; para CONJUNTOS, `garment.category.coordinated_set`. ACCESORIOS tiene destinos dentro del ámbito humano aprobado en bag, headwear, neckwear/scarf, jewelry, eyewear y accessory; excluye footwear y tie. El estado pendiente corresponde a ejemplos y adjudicaciones, sin reabrir el alcance comercial decidido. `catalog_audit.json.mapping_recommendations` conserva exclusivamente recomendaciones históricas de 1.0.0, superadas por H01-H03 y no consumibles como mapping vigente; `commercial_mapping.json` es el artefacto formal revisado. Las políticas de alcance aprobadas no habilitan resolución automática de productos ni labels de entrenamiento; los candidatos ontológicos y ejemplos siguen su revisión por ámbito.

El puente formal conserva `garment.category.sweater` como candidato bajo ABRIGOS: en el dump antiguo la prenda11 `JERSEY ALPACA` tiene categoría comercial1 y variante11. La decisión 4 conserva esos identificadores y exige UNKNOWN/candidatos hasta contar con evidencia de producto verificable; el nuevo registro de discrepancia está en review_catalog_audit.json. Se registra la discrepancia, sin promover el título a composición alpaca confirmada ni cambiar el ID. Los destinos adicionales de PANTALONES siguen como cobertura propuesta pendiente; el alcance de POLOS Y KNITWEAR y POLERA se ajusta a las decisiones 1–2 sin reescribir la auditoría histórica. Ningún destino acredita pertenencia observada o training truth.

Casos sin evidencia: categoría comercial conocida y concepto ontológico `UNKNOWN`. Casos fuera del alcance de una familia/atributo: `NOT_APPLICABLE`. Incompatibilidad entre fuentes: mantener procedencias y escalar a revisión, sin reemplazar la categoría comercial. Los IDs ontológicos formales se resuelven mediante el validador; una correspondencia técnicamente válida sigue necesitando revisión de dominio.

## Preservación comprobable

El probe existente `services/mati-ai/scripts/gate_v2/probe_preservation.php` abre una transacción READ ONLY, calcula huellas y hace rollback, pero guarda evidencias en las rutas históricas `preservation-before/after.json` y rechaza sobrescribirlas. No se ejecutó ni se modificó. El probe visual existente llama inferencia y quedó fuera del alcance solicitado.

Se conservaron y compararon SHA-256 de los archivos de evidencia y contratos leídos. El par **histórico** de preservación tiene huellas comerciales, vector canónico, conteos AI y RAG iguales. Sus conteos son AI model versions4, runs147, suggestions11, reviews2, embeddings visuales134 y RAG1/23/23/23. Esto permite decir que los archivos fuente usados en esta inspección permanecieron idénticos y que el historial registra preservación anterior. **No permite afirmar que la DB viva actual no cambió durante esta fase**; esa comprobación queda sin verificar por falta de acceso operativo.

## Revisión gradual y pendientes

Las políticas comerciales de POLERA, POLOS Y KNITWEAR, ACCESORIOS, identidad discrepante y conjuntos están aprobadas por las decisiones 1–6; `human_decision` conserva origen, alcance y límites en el mapping. La ontología global mantiene `DRAFT_FOR_REVIEW`. El estado estructural de un mapping y la revisión de sus productos/ejemplos no equivalen a aprobación de familia, guía, dataset o modelo.

NEEDS MATI REVIEW: adjudicación de fronteras camisa/blusa y blazer/abrigo, productos históricos discrepantes, tipologías locales de complementos, unidades/componentes reales de conjuntos, interpretación de talla ID54 y mapping de maestros amplios. Ninguna de estas revisiones permite corregir categorías o tallas comerciales sin autorización.

RESEARCH INCOMPLETE: catálogo live actual, originales inaccesibles de ai-rag, anotaciones de accesorios/calzado y evidencia producto por producto para composición, medidas y seasonality. La talla ID54 tiene relaciones comprobadas sólo en un dump antiguo. La exclusión de recomendaciones es política futura, sin implementación de recomendador.

OPEN QUESTIONS FOR LIMBER/MATI: modelo exacto de inventario compartido, reservado o empaquetado cuando se vendan conjuntos y componentes; categoría comercial futura del calzado; evidencia/adjudicación de XXXXXXXXL; ejemplos suficientes por familia; tratamiento de términos amplios OTRO/TEJIDA/TEXTURIZADA. El alcance de POLERA y la exclusión del calzado de ACCESORIOS ya están decididos y no se presentan como preguntas abiertas.
