# Decision log — revisión vigente del borrador V1

Estado global DRAFT_FOR_REVIEW. Origen común H01-H12: decisiones humanas del prompt maestro recibido 2026-10-08, hash en review_baseline.json. Son políticas aprobadas; no firmas, revisión de ejemplos ni aprobación de todo el vocabulario. Los artefactos mencionados están en resources/fashion_ontology/v1 o DOCUMENTACION/IA/FASHION_ONTOLOGY_V1 según su tipo.

## H01 — POLERA

- Decisión humana aprobada: La categoría comercial ID11 significa camiseta/T-shirt, incluidas manga corta/larga y Henley.
- Justificación: Resolver la ambigüedad local del borrador inicial.
- Alcance y consecuencias: Mapping exclusivo garment.category.tshirt; cuello y manga en sus ejes.
- Restricciones: Conservar ID/nombre y todos los productos históricos. Mapping no es etiqueta válida de entrenamiento.
- Pendientes: Ejemplos propios y revisión de definiciones.
- Artefactos: commercial_mapping.json; ontology.json; docs03/10/17.

## H02 — POLOS Y KNITWEAR

- Decisión humana aprobada: Conservar ID8; distinguir polo/sweater/cardigan producto por producto.
- Justificación: La técnica textil no determina identidad.
- Alcance y consecuencias: Puente de candidatos principales; camisa/camiseta de punto mantienen su identidad cuando haya evidencia.
- Restricciones: Sin alterar categoría comercial global ni reclasificar por knit.
- Pendientes: Verificar identidad de productos y anotaciones.
- Artefactos: commercial_mapping.json; ontology.json; docs03/10.

## H03 — ACCESORIOS

- Decisión humana aprobada: ID9 incluye bolsos/mochilas/cinturones/gafas/relojes/joyería/bufandas/pañuelos/gorras/sombreros/guantes; excluye calzado.
- Justificación: Separar alcance comercial de familias ontológicas.
- Alcance y consecuencias: FOOTWEAR independiente; se conservan taxones adicionales fuera del puente autorizado.
- Restricciones: No crear categoría comercial calzado; sin inventar ejemplos/stock.
- Pendientes: Ubicación comercial futura de calzado y muestras reales.
- Artefactos: commercial_mapping.json; docs09/10; governance.json.

## H04 — Discrepancia comercial/ontológica

- Decisión humana aprobada: Conservar categoría comercial histórica; identidad ontológica por evidencia o UNKNOWN con candidatos.
- Justificación: JERSEY ALPACA bajo ABRIGOS no demuestra categoría visual ni composición.
- Alcance y consecuencias: Registrar discrepancia y revisión administrativa; política aplicable a casos similares.
- Restricciones: No corrección comercial automática; ALPACA no prueba fibra; mapping no es verdad de entrenamiento.
- Pendientes: Revisión producto11 y otros casos con evidencia física/documental.
- Artefactos: docs10/12/18/20; review_catalog_audit.json; PI schema.

## H05 — Camisa/blusa y blazer/abrigo

- Decisión humana aprobada: Usar evidencia conjunta; abstenerse cuando sea ambigua.
- Justificación: Botones/fluidez/largo/solapas no son criterios universales.
- Alcance y consecuencias: Definiciones con positivos/negativos/fronterizos; candidatos conservados y revisión humana.
- Restricciones: No scores como probabilidades sin calibración documentada; no resolver por género.
- Pendientes: Ejemplos propios y guía adjudicada.
- Artefactos: ontology.json; doc03; PI schema; validator/tests.

## H06 — Conjuntos y outfits

- Decisión humana aprobada: Conjunto comercial auténtico requiere vínculo explícito; puede venderse completo/componentes con registros e inventarios adecuados. Venta habitual individual.
- Justificación: Vestido completo es una prenda; apariencia coordinada no acredita paquete.
- Alcance y consecuencias: ONE_PIECE para vestido; MULTI_PIECE para asociación comercial real; Outfit Engine combina productos individuales.
- Restricciones: Outfit no crea producto/precio de paquete ni automatiza stock; no doble descuento implementado.
- Pendientes: Inventario compartido/reservado/empaquetado, atomicidad y doble uso en futura tarea.
- Artefactos: ontology.json; commercial_mapping.json; docs03/06/10/12/22.

## H07 — Estilos y novedad

- Decisión humana aprobada: Separar estilo/estética/dress code/formalidad; ampliar vocabularios mediante propuesta revisable.
- Justificación: Un nombre popular puede ser alias/variante/combinación/microtendencia.
- Alcance y consecuencias: Novedad por semántica/visual/cultura/tiempo/persistencia/utilidad; reutilizar relaciones cuando corresponda.
- Restricciones: Sin crear conceptos aprobados automáticamente ni popularidad inventada; similitud numérica insuficiente.
- Pendientes: Candidatos23, aliases, escalas y validación cultural por Mati.
- Artefactos: docs07/21/23; research_sources_revision.json.

## H08 — Materiales y registro

- Decisión humana aprobada: Evidencia híbrida visual/OCR/ficha/fabricante/proveedor/autorizada/humana.
- Justificación: Una foto no demuestra composición; imagen similar no identifica misma referencia/variante.
- Alcance y consecuencias: Separar fibra/composición/estructura/textura/patrón/acabado/apariencia/prestación/clima/temporada; procedencia por atributo.
- Restricciones: Hechos técnicos externos solo con identidad exacta y misma variante verificadas; licencia independiente de confirmación humana.
- Pendientes: Fuentes físicas y permisos de cada recurso; integración posterior.
- Artefactos: docs08/12/18/19/20; PI schema; validator/tests.

## H09 — Fit, talla y medidas

- Decisión humana aprobada: Política híbrida: imagen para observable, ficha exacta para medidas/tablas, revisión humana para incertidumbre.
- Justificación: Wide_leg no demuestra centímetros de entrepierna ni talla comercial.
- Alcance y consecuencias: Silueta/fit/pernera/tiro/mangas/largos/entrepierna/medidas reales/talla/ajuste personal separados.
- Restricciones: Talla pertenece a Laravel; modelo de ajuste personal futuro; medidas no inventadas desde apariencia.
- Pendientes: Rúbricas locales y fichas/mediciones; recomendación personal posterior.
- Artefactos: docs04/05/12/18/20/22.

## H10 — Puño de manga

- Decisión humana aprobada: Añadir sleeve_cuff_type exclusivamente para tipos de puño de manga.
- Justificación: Hem.cuffed y brazalete cuff pertenecen a otros dominios.
- Alcance y consecuencias: attribute.sleeve_cuff_type multi_label; candidatos button/ribbed/elasticated/french; manga/puño ausente confirmado N/A, oculto UNKNOWN.
- Restricciones: Atributo autorizado; cada valor pendiente; sin especialista ni entrenamiento de puños.
- Pendientes: Revisión técnica/local de valores y ejemplos reales.
- Artefactos: ontology.json; attribute_values.json; applicability/labels; docs04/05/11; PI schema.

## H11 — Talla anómala

- Decisión humana aprobada: Detectar y preservar XXXXXXXXL ID54 INFANTIL/ALFA/GENERAL; registrar anomalía y verificar vínculos.
- Justificación: Nombre sospechoso no acredita error ni medidas.
- Alcance y consecuencias: Excluir recomendaciones mientras no se verifique; audit del dump separada de DB viva.
- Restricciones: No normalización ni corrección automática; autorización humana antes de modificar dato comercial.
- Pendientes: Verificar maestro y relaciones de DB viva, significado y autorización de corrección si procede.
- Artefactos: review_catalog_audit.json; docs10/14/22.

## H12 — Gobernanza gradual

- Decisión humana aprobada: Aprobar ámbitos gradualmente con responsable/definición/evidencia/ejemplos/integridad/ambigüedades/discrepancias/decisión verificables.
- Justificación: Validez técnica no prueba consenso, dataset, modelo ni producción.
- Alcance y consecuencias: Mati comercial; responsable técnico contratos; revisores anotaciones. Seis fases independientes y registro compatible.
- Restricciones: Sin firmas, nombramientos, freeze global ni aprobación implícita de familias/candidatos/modelos.
- Pendientes: Personas responsables y decisiones de ámbito con evidencia suficiente.
- Artefactos: governance.json; ontology.schema.json; docs01/14/17/24; validator/tests.

## Discrepancias respecto del borrador inicial

D15, D16, D17, D18 y D24 tenían alcances provisionales: ahora H01/H02/H03/H06/H10 los resuelven. Las preguntas comerciales iniciales no siguen abiertas. H07/H12 resuelven separación y gobernanza sin aprobar cada valor. Los mappings previos restringidos se conservan en la copia/hash de línea base; no se trasladan a datos históricos como correcciones automáticas.

## Registro histórico inicial, superado cuando H01-H12 indican resolución

El contenido siguiente explica el estado anterior a esta revisión. NEEDS MATI REVIEW o preguntas aquí no revocan decisiones humanas vigentes de arriba. SAFE TO FREEZE fue una recomendación técnica, nunca autorización de ámbito.

# 15. Decision log de Fashion Ontology V1

Registro de decisiones de diseño MORE, informadas por investigación y catálogo. Estado general `DRAFT_FOR_REVIEW`. Las fuentes externas no dictan la arquitectura ni validan usos de Mati. Este log explica el criterio de los artefactos; no copia sus listas ni sustituye al contrato JSON.

| ID | Decisión propuesta | Justificación / frontera | Estado y revisión |
| --- | --- | --- | --- |
| D01 | Familias separadas de posición de capa | Familia expresa identidad estable; un blazer puede ser capa intermedia bajo abrigo sin dejar de ser OUTERWEAR | NEEDS MATI REVIEW para la jerarquía concreta |
| D02 | Categoría/subcategoría no concatena atributos | Wide-leg, high-rise, cropped y patrón permanecen en sus ejes; un subtipo exige identidad constructiva suficiente | NEEDS MATI REVIEW para subtipos/casos frontera |
| D03 | Camisa/blusa se diferencia por construcción y evidencia conjunta | Botones, fluidez y género no son pruebas únicas; permitir confusión y UNKNOWN | NEEDS MATI REVIEW con ejemplos de tienda |
| D04 | Fit, silueta, largo y técnica de corte son ejes diferentes | Holgura de diseño, contorno, extensión y confección responden preguntas distintas; talla permanece comercial | SAFE TO FREEZE el principio; labels necesitan Mati |
| D05 | Style y aesthetic tienen separación operacional | El núcleo style usa afinidades culturales/visuales; aesthetic identifica referencias temáticas/históricas. La literatura puede usar ambos términos de otro modo | NEEDS MATI REVIEW; no clasificación universal |
| D06 | Smart casual/business casual son dress codes; formalidad es ordinal | Evitar duplicarlos como estilos. `value.formality.0..4` ordena cinco grados propios, sin distancias iguales ni umbrales visuales aprobados | NEEDS MATI REVIEW de ámbitos y anclajes |
| D07 | Afinidad múltiple, puntuación futura independiente | Una prenda puede apoyar varios estilos. No hay scores reales ni equivalencia automática entre score y probabilidad | SAFE TO FREEZE el contrato multilabel; afinidades necesitan Mati |
| D08 | Composición, material no textil, estructura, weave/knit y fabric family son capas distintas | Satén no prueba seda, denim no prueba algodón; montaje por componente conserva evidencia | SAFE TO FREEZE el principio; vocabularios/fichas necesitan revisión |
| D09 | Textura, acabado, opacidad, construcción y patrón no se mezclan | Ribbed observado no confirma rib knit; plisado/acolchado describen construcción, sheer opacidad, distressed acabado | NEEDS MATI REVIEW de muestras y señales |
| D10 | Checked/tartan/plaid no son un alias universal | Tartan es un concepto específico; plaid tiene usos amplios/variantes. Alias requiere idioma/dominio/contexto | NEEDS MATI REVIEW de términos bilingües |
| D11 | Body zones separa cobertura, vestimenta, porte y contacto | Bolso en mano/hombro no cubre el cuerpo como una camisa. Waist es frontera bajo full_body; conjuntos agregan componentes | NEEDS MATI REVIEW del modelo espacial |
| D12 | Layering usa orden parcial dirigido por instancia/zona/contexto | BASE, INTERMEDIATE, OUTER, OVERLAY y ACCESSORY son roles contextuales; INNER expresa debajo relativo. Evitar ciclos del mismo contexto | SAFE TO FREEZE integridad del orden; candidatos necesitan Mati |
| D13 | Compatibilidad lleva tipo de regla y condiciones | No existe veto universal LOWER+LOWER. Falda sobre pantalón puede ser configuración contextual; no se declara tendencia vigente | NEEDS MATI REVIEW de ejemplos; sin hard incompatibilities de moda afirmadas |
| D14 | Comercial ↔ ontología es puente candidato no invertible | Una categoría puede contener varias identidades; varias categorías pueden contener un mismo concepto. Discrepancia se conserva | SAFE TO FREEZE trazabilidad; todos los mappings necesitan Mati |
| D15 | POLERA queda sin equivalencia aprobada | El manifiesto favorece camiseta e incluye Henley/manga larga; sweatshirt/sweater son hipótesis de ambigüedad, no contenido confirmado | NEEDS MATI REVIEW; usuario confirma mapping no definido |
| D16 | POLOS Y KNITWEAR resuelve por producto | Polo, sweater y cardigan tienen soporte textual; punto no identifica una sola prenda y puede existir en camisa/camiseta | NEEDS MATI REVIEW del alcance exacto |
| D17 | ACCESORIOS prepara profundidad sin inventar catálogo | Bag, jewelry, headwear, neckwear, eyewear y accessory tienen conceptos; no hay productos observados. Calzado no se asigna por existencia de tallas | NEEDS MATI REVIEW; usuario confirma mapping no definido |
| D18 | CONJUNTOS representa unidad compuesta con componentes | Vestido es una unidad continua; varias prendas visibles no prueban set vendible. Mantener categoría comercial ALFA/SUPERIOR | NEEDS MATI REVIEW con fichas de unidad comercial |
| D19 | Temporada comercial se conserva aparte de estación/clima | PV/OI aportan candidatos; resort/capsule/permanente/carnavalera/mundial no se fuerzan a estaciones; PERMANENTE no prueba all-season | NEEDS MATI REVIEW de mapping/contexto climático |
| D20 | Semántica de color conserva medición y clasificación comercial independientes | Color family/neutral/accent no cambia CIELAB/CIEDE2000 ni hex comerciales. Metálico visual no prueba metal | SAFE TO FREEZE separación; mappings y roles necesitan Mati |
| D21 | UNKNOWN no es negativo; N/A necesita contexto | Relaciones vacías o partes ocultas no acreditan ausencia. Adjudicar completitud multilabel y enmascarar loss | SAFE TO FREEZE principio/estado; condiciones necesitan revisión |
| D22 | Propiedades no visuales pueden existir sin ser targets de visión | Composición porcentual, aislamiento, capacidad L, material de montura y medida mm necesitan documentos/medición | SAFE TO FREEZE frontera; RESEARCH INCOMPLETE evidencia propia |
| D23 | GENERAL/SPECIALIST designa ámbito semántico | `future_head` no elige encoder, arquitectura multihead ni MoE; puede señalar atributos documentales | SAFE TO FREEZE alcance; redes/entrenamiento fuera de fase |
| D24 | Puños, vuelta de bajo y cuff de joyería no se unifican | `value.hem.cuffed` significa bajo con vuelta; `value.jewelry_form.cuff` brazalete abierto. Un puño de manga necesita eje específico si se adopta | NEEDS MATI REVIEW; no taxonomía completa de puños añadida |
| D25 | Targets comerciales no describen la persona fotografiada | Target age/gender proceden de intención comercial; no se predicen características personales por cuerpo o apariencia | SAFE TO FREEZE límite de autoridad; labels/maestros requieren revisión |
| D26 | Licencia se revisa por componente y propósito | Código, anotaciones, ontología, fotos y pesos tienen derechos distintos; mapping conceptual no autoriza transferencia | RESEARCH INCOMPLETE ingestiones futuras; investigación en documento02 |
| D27 | IDs estables, versionado, hashes y deprecación sin reciclaje | Cambios de significado requieren migración explícita de afirmaciones y label schema; alias no puede cambiar sentido silenciosamente | SAFE TO FREEZE política técnica, con validación |
| D28 | Validación sintáctica no congela dominio | IDs y JSON correctos no prueban acuerdo, anotabilidad o cobertura; conceptos permanecen DRAFT_FOR_REVIEW | SAFE TO FREEZE regla de gobierno |

## Frontera Knowledge / Trend / RAG / LLM

`relation.is_a` es estructura formal. Una explicación de por qué un blazer puede apoyar determinada ocasión necesita Fashion Knowledge y contexto. El predicado style_affinity puede existir en la ontología para representar el eje sin afirmar afinidades universales entre categorías. Los ejemplos de layering tipan una posibilidad, no una recomendación final ni un dato de tendencia.

Trend Intelligence deberá referir configuraciones/conceptos con observaciones fechadas y ámbito. El vocabulario puede admitir old money/Y2K como referencias estéticas sin declarar popularidad actual. RAG puede recuperar explicaciones versionadas; el JSON mantiene autoridad formal. El LLM podrá consultar y explicar, manteniendo UNKNOWN y confirmando realidad comercial mediante Laravel.

## Cambios y evidencias pendientes

La decisión de no congelar es explícita en `ontology.json.freeze_policy`. La auditoría local respalda IDs comerciales históricos y sus hashes; no se obtuvo consulta live. El documento14 enumera preguntas concretas y clasificadores de revisión. Las respuestas futuras deben registrar responsable, fecha, muestras/evidencia, decisión, IDs afectados, versión y resultado del validador. No añadir aprobación humana ficticia ni reconstruir motivaciones de tienda a partir de nombres comerciales.
