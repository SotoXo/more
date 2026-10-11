# Fashion Ontology V1 revisada — reporte verificable 1.1.0

Estado global **DRAFT_FOR_REVIEW**, sin freeze ni aprobación de todas las familias. La revisión incorpora H01-H12 del prompt humano recibido el 2026-10-08. Las políticas están aprobadas; ejemplos, valores candidatos, datasets, modelos y producción siguen sus fases independientes. El reporte inicial y sus resultados se conservan como evidencia histórica, no como validación de esta revisión.

## Resultado ejecutivo

Se preservó la ontología existente y se corrigieron alcances provisionales. POLERA ID11 apunta a tshirt, POLOS Y KNITWEAR ID8 mantiene polo/sweater/cardigan por producto y ACCESORIOS ID9 excluye FOOTWEAR. Se conserva identidad comercial histórica aunque difiera de la ontológica; JERSEY ALPACA no demuestra sweater ni fibra alpaca sin evidencia. Vestido sigue una prenda ONE_PIECE; MULTI_PIECE exige asociación comercial explícita y el outfit combina productos individuales.

Se agregó attribute.sleeve_cuff_type con cuatro valores candidatos (button/ribbed/elasticated/french), ejemplos y condiciones. No es hem.cuffed ni jewelry_form.cuff. Manga/puño ausente confirmado permite NOT_APPLICABLE; oculto o ambiguo UNKNOWN. Un puño observado puede demostrar presencia de manga aunque se desconozca su largo. No se autorizó especialista ni entrenamiento.

Product Intelligence conserva estados/enums y centralización de assertions/evidence. Amplía opcionalmente UNKNOWN.candidates, source_details y source_scope; distingue observación por frame/acumulación, OCR/código, fabricante/proveedor/no oficial/humano/histórico, identidad externa/variante, calidad y permisos. Fuente técnica externa requiere producto y variante exactos; visualización o selección humana de foto no acredita composición, medida ni licencia. Los conflictos conservan hipótesis sin una jerarquía universal de fuentes.

Se crearon cinco contratos independientes de diseño para identificación continua, búsqueda externa, enriquecimiento, tendencias y outfits. La búsqueda puede continuar con tipo/color/marca/talla parciales; siempre admite corrección, rechazo de todas, «Ninguna opción corresponde a mi prenda» y registro manual. Los contratos internacionales consideran geografía, fecha, hemisferio/estación, clima fiable, ocasión, preferencias y disponibilidad de variantes reales en Laravel.

## Doce decisiones: estado y evidencia

| Decisión | Resultado aplicado | Artefactos de verificación | Límite vigente |
|---|---|---|---|
| H01 POLERA | Política aprobada, mapping tshirt exclusivo | commercial_mapping.json; doc10 | Sin relabel comercial ni ground truth automático |
| H02 POLOS Y KNITWEAR | Política aprobada, tres candidatos principales por producto | Mapping; doc03/10 | Knit no determina camisa/camiseta/polo |
| H03 ACCESORIOS | Alcance aprobado, sin footwear ni tie | Mapping; doc09/10 | Categoría calzado y ejemplos reales pendientes |
| H04 Discrepancias | Categoría histórica conservada; UNKNOWN y candidatos | review_catalog_audit.json; PI/doc10/12 | DB viva e identidad/composición producto11 sin verificar |
| H05 Fronteras | Evidencia conjunta y ejemplos positivos/negativos/fronterizos | ontology.json; doc03; tests | Sin criterio universal de botones/fluidez/largo/solapas |
| H06 Conjuntos | Vestido individual, set explícito, outfit separado | Núcleo/mapping; docs06/12/22 | Inventario compartido/reservado/empaquetado pendiente |
| H07 Estilos | Cuatro ejes separados; núcleo conservado y propuesta23 | styles/usage; docs07/21/23 | Candidatos y aliases no aprobados |
| H08 Materiales | Política híbrida y procedencia/variante verificable | PI schema; docs08/12/18–20 | Foto/similitud no confirma composición ni licencia |
| H09 Fit/medidas | Ejes separados y evidencia observable/documental | Núcleo; docs04/05/18/20/22 | Sin talla ni centímetros desde apariencia; ajuste personal futuro |
| H10 Puños | Atributo autorizado; cuatro valores candidatos | Núcleo/vocabulario/aplicabilidad/labels/schema | Cada valor requiere Mati; sin entrenamiento |
| H11 Talla anómala | ID54 y valor preservados; vínculos del dump revisados | review_catalog_audit.json; docs10/14/22 | Excluir recomendación hasta verificar; sin corrección comercial |
| H12 Gobierno | Procedimiento aprobado y registro por ámbito | governance.json; docs15/24; tests | Ningún ámbito/dataset/modelo aprobado por esta tarea |

La matriz17 añade conceptos, atributos, relaciones, schemas, tipo de cambio, pruebas y riesgos para cada decisión. Decision log15 registra origen, justificación, alcance, consecuencias, restricciones, pendientes y artefactos; conserva el historial inicial claramente marcado como superado.

## Conteos y compatibilidad

| Conteo | Inicial 1.0.0 | Revisado 1.1.0 | Explicación |
|---|---:|---:|---|
| Familias / categorías / subcategorías | 12 / 47 / 22 | 12 / 47 / 22 | Ninguna eliminación ni cambio de parent |
| Atributos / labels | 68 / 68 | 69 / 69 | Nuevo eje de puños |
| Conceptos bilingües | 449 | 454 | Atributo + cuatro valores; no candidatos de estilo documentales |
| Pares de aplicabilidad | 816 | 828 | Doce filas del nuevo atributo |
| Estilos / estéticas | 10 / 4 | 10 / 4 | Vocabulario conservado |
| Dress codes / niveles | 7 / 5 | 7 / 5 | Propuesta fuera de vocabulario estable |
| Zonas / relaciones | 18 / 97 | 18 / 97 | Sin cambio estructural |
| Categorías comerciales | 11 | 11 | IDs y nombres preservados |

review_compatibility_result.json confirma 449 IDs originales preservados, cero IDs retirados, cero cambios en labels/aliases previos, parent taxonómico intacto y once identidades comerciales iguales. summary/CSV/hashes se regeneraron con el exportador. La transición del borrador está documentada en24: restricciones sobre mappings provisionales no migran registros comerciales ni convierten labels previos en hechos aprobados. Los consumidores necesitan versión/hash nuevos; el pin 1.0.0 no se sustituye silenciosamente.

## Estilos y fuentes investigadas

Se preservan minimalist, classic, preppy, streetwear, bohemian, sport_inspired, romantic, utilitarian, avant_garde y western_inspired; estéticas old_money/y2k/retro/techwear. Propuesta23 contiene maximalist, punk_inspired, gothic_inspired y grunge_inspired, más candidato de dress code black_tie_optional. Todos son propuestas documentales pendientes de Mati. Cada uno distingue identidad, relaciones, atributos, ejemplos, sinónimos, superposiciones, aplicabilidad y utilidad. Las aclaraciones de conceptos existentes tampoco se aplican silenciosamente al JSON.

Alias propuestos (como boho), variantes contextuales (athleisure o Ivy), referencias por década y configuraciones de layering se contrastan antes de crear IDs. Novedad requiere análisis semántico/visual/cultural/temporal/persistencia/utilidad, con alternativas: alias, subestilo, combinación, estética, layering, microtendencia o concepto nuevo. La investigación primaria (FIT, The Met, Emily Post y Google Trends) documenta repertorios y límites; no mide popularidad actual de productos Mati. Once páginas verificadas están en research_sources_revision.json; la investigación original se conserva.

## Contratos futuros y responsabilidades

| Contrato | Definición y límites principales |
|---|---|
| 18 Product Identification | Múltiples frames, calidad/selección/deduplicación/fusión, cambio de prenda, marca/talla con evidencia, privacidad/retención mínima, errores y alternativa sin cámara |
| 19 External Product Search | Consultas parciales, fuentes prioritarias, producto/variante, exact_verified/probable/similar/insufficient/no_results, fallos y restricciones |
| 20 Product Enrichment | Fotos candidatas y selección, permisos por uso/recurso, atributos propuestos/efectivos, conflictos, confirmación/rechazo/manual y feedback a curación |
| 21 Trend Intelligence | Alcance internacional/local, evidencia fechada/geográfica, métricas verificables, novedad/redundancia, revisión y versionado |
| 22 Outfit Intelligence | Contexto/compatibilidad/layering/temporada/clima, preferencias e inventario/tallas reales, cobertura parcial/completa/insuficiente, outfit frente a set |

Laravel conserva IDs/SKU/precios/stock/variantes/tallas/categorías/pedidos/ventas/reservas. Ontología define significado, PI conserva afirmaciones/procedencia y los futuros motores consumen ambos con contexto. Se verificaron manifiestos reales: Laravel ^13, Inertia ^2, React ^18, TypeScript ^5 y servicio Python >=3.11,<3.12; compose declara PostgreSQL/pgvector. Son requisitos declarados, no comprobación del runtime productivo. La CLI independiente se ejecutó con Python 3.12 y jsonschema 4.26.0 aislado, sin alterar dependencias del servicio.

## Gobernanza y pendientes

governance.json registra todas las familias y ámbitos transversales, mapeando DRAFT/PENDING_REVIEW/REVIEWED/APPROVED/BLOCKED_INSUFFICIENT_EVIDENCE sin sustituir review_status ni los tres estados de conocimiento. Mantiene seis fases independientes: definición, guía, dataset, entrenamiento, evaluación y producción. Para aprobar exige responsable, decisión, revisión de definición/ambigüedades/discrepancias, ejemplos positivos/negativos/fronterizos, comprobaciones de integridad, revisor/fecha/evidencia. Referencias estructuralmente válidas todavía requieren auditoría humana de su contenido.

Mati tiene autoridad comercial; responsables técnicos y revisores de anotaciones aún no se nombran. Ninguna firma ni aprobación de ámbito se inventó. Puños, candidatos de estilo, ejemplos propios, calibraciones, permisos externos, cobertura de datasets y modelos siguen pendientes. El significado y relaciones actuales de talla54 requieren lectura viva; en las13 variantes del dump no hay vínculos con esa talla. Inventario de conjuntos y sus componentes requiere decisión posterior. No hay freeze global.

## Validación ejecutada

**Resultado final: 15 checks PASS, cero errores; suite completa 111/111 PASS (379.812 s); cuatro escenarios de fusión PASS.** El validador dejó todos los artefactos idénticos. JSON estricto inicial y parse final: 37 archivos JSON examinados sin errores; hashes, IDs, traducciones, referencias, aplicabilidad, mappings, estados y gobernanza comprobados. git diff --check exit0. Las cifras corresponden a la revisión actual, no a tests históricos.

Comandos portables de los artefactos (usando un intérprete con jsonschema >=4):

```sh
python services/mati-ai/scripts/fashion_ontology/export_fashion_ontology.py
python services/mati-ai/scripts/fashion_ontology/validate_fashion_ontology.py
python -m unittest discover -s services/mati-ai/scripts/fashion_ontology -p test_fashion_ontology.py -v
```

La primera suite de 111 casos tuvo 109 PASS y dos fallos de fixtures nuevos: entrepierna estaba colocada en una camiseta, fuera de aplicabilidad. Se corrigieron los especímenes a pantalón; no se relajó la regla semántica. Los dos casos pasaron en ejecución dirigida. La salida inicial se conserva en review_test_output_initial.txt; la suite final y su comando/runtime/hash están en review_test_result.json y review_test_output.txt.

review_scenarios.json registra cuatro escenarios sintéticos de fusión: dos frames distintos de una sesión válidos; sesiones distintas rechazadas; repetición de un frame rechazada; composición desde fusión visual rechazada. No son métricas de precisión ni implementación del escáner.

No se ejecutaron tests productivos Laravel/FastAPI/React, entrenamiento, inferencia/benchmarks, GPU, conectores externos ni consultas a DB viva: están fuera de esta revisión y no se modificaron sus componentes. WSL devolvió E_ACCESSDENIED; la exportación storage/app/ai-rag fue inaccesible. La dependencia jsonschema se instaló en carpeta aislada después de que el intento en sandbox no resolviera pypi.org; no quedó incorporada a mati-ai.

## Inventario y preservación

**40 archivos preexistentes modificados dentro del alcance, 23 archivos creados y 12 artefactos previos del ámbito preservados.** Los 757/757 archivos protegidos ajenos al alcance mantienen su hash, sin archivos previos desaparecidos. HEAD y rama se conservan; los siete tracked modificados al inicio mantienen exactamente su contenido. Los cambios se limitan a los tres directorios de Fashion Ontology. El stat Git normal no enumera las ediciones de archivos ya untracked; el inventario compara contenido contra la línea base.

review_file_inventory.json contiene la lista exacta de archivos modificados, creados y preservados dentro del ámbito, con hashes inicial/final; review_preservation_result.json verifica los archivos protegidos y estado Git. La línea base no modifica ni borra archivos sin rastrear. El reporte inicial16, auditoría, fuentes, tests/validación y preservación históricos permanecen separados de los resultados nuevos prefijados review_.

No se modificó código operativo Laravel/React/FastAPI, migraciones, inventario, ventas, modelos/pesos/configuración de inferencia, RAG ni pipelines productivos. No se ejecutaron migraciones, git add/commit/push ni cambio de rama. No se creó categoría comercial. Las huellas locales verificadas no prueban conservación de DB viva: permanece UNVERIFIED_NO_LIVE_DB_ACCESS.

El trabajo completo conserva los límites del prompt: únicamente ontología, mappings, schemas, documentación/contratos, gobernanza y sus herramientas/pruebas. Los componentes de escáner, búsqueda, enriquecimiento activo, tendencias, outfits y futuros modelos quedan diseñados para revisión posterior.
