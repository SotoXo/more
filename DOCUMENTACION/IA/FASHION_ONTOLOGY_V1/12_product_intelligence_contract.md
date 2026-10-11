# Contrato Product Intelligence V1

Contrato de diseño `1.1.0`: `product_intelligence.schema.json`. Ejemplo sintético: `product_intelligence.example.json`; no representa un producto real, anotación humana, inferencia o dataset. El ejemplo declara document_status=design_fixture y procedencia explícita. No se conecta a endpoints ni tablas nuevas.

## Estructura y autoridad

identity identifica entidad product/component/set y puede referenciar producto Laravel mediante identificador opaco. Taxonomy contiene family/category/subcategory con estados explícitos. Attributes centraliza assertions; colors/materials/styles/spatial_semantics/usage_semantics contienen referencias a esas assertions, evitando valores duplicados. Evidence centraliza procedencia; model_metadata identifica versiones; human_review conserva la propuesta y la revisión. Se vincula el hash exacto de ontology.json para fijar el vocabulario interpretado.

Stock, precio y SKU viven en la base comercial; el contrato rechaza propiedades no declaradas en raíz. No almacena body shape del usuario ni intereses personales. Un external_import o salida de modelo no puede confirmar automáticamente el catálogo. Laravel mantiene la autoridad de IDs y realidad comercial.

## Estados

KNOWN exige value y evidence_refs. UNKNOWN exige razón y omite value. NOT_APPLICABLE exige razón y omite value. Null no es una variante válida. Para boolean, false es un valor KNOWN; para multi-label, conjunto completo vacío significa ausencia adjudicada, mientras UNKNOWN significa falta de evidencia. annotation_completeness diferencia complete/partial; etiquetas omitidas en conjuntos incompletos no son negativos de ML.

`UNKNOWN.candidates` es una ampliación opcional: conserva hipótesis con `value`, `evidence_refs` y, cuando procede, `confidence` y `notes`. Cada candidato usa el tipo y dominio del atributo o nivel taxonómico al que pertenece. No constituye un valor efectivo, no convierte UNKNOWN en KNOWN y no se admite en KNOWN/NOT_APPLICABLE. Por ejemplo, una identidad ambigua entre camisa y blusa conserva ambas categorías candidatas y su evidencia; el mapping comercial no decide el ganador. La fixture publicada incorpora una hipótesis sintética de estilo, sin anotarla ni aprobarla.

Cada assertion separa valor, evidence_refs, confidence y context. Los IDs de valores se resuelven contra la ontología; no hay strings de moda libres como sustituto de IDs. Las restricciones por familia/categoría y componentes se verifican semánticamente además de JSON Schema. Un rise conocido para camiseta es inválido; rise para pantalón sin evidencia es UNKNOWN; rise para reloj es NOT_APPLICABLE.

Las dimensiones de composición requieren componente y fibras registradas. Una composición completa suma 100% dentro de tolerancia 0.5; una parcial declara que faltan fibras/porcentajes. No se estima composición desde foto. Weave y knit_structure exigen estructura compatible del componente. Medidas usan unidades de atributo y no se deducen sin escala.

La fila familiar indica aplicabilidad potencial. Los ejes textiles necesitan componente textil: `context.textile_component_present=false`, con evidencia de ausencia, permite NOT_APPLICABLE en un anillo enteramente metálico; sin confirmar presencia/ausencia se usa UNKNOWN. `non_textile_component_present` aplica la misma política a material no textil. El contexto no convierte una ausencia no observada en un negativo. Componente es un ID registrado de la entidad o un nombre de parte permitido (shell/lining/trim/fill).

Los valores structured tienen forma propia: observed_color referencia una observación/version de colorimetría y paleta con campos RGB/HEX/Lab/percentage existentes; no admite un objeto de fibras. Insulation contiene relleno descrito documentalmente y, si existen, mediciones con propiedad/unidad/método/evidencia; no admite stock ni prestaciones inventadas. Layering referencia instancias declaradas en identity/component_refs/related_instance_refs. Se valida fecha ISO, evidencia y premisas de derivación además del tipo estructural.

## Evidencia y confianza

Fuentes: human, vision_model, ocr, catalog, derived_rule, rag, external_import. Cada registro tiene ID, referencia de origen, fecha y autoridad observada/declarada/predicha/human_verified/design_fixture/unverified. Una derived_rule necesita trazabilidad de premisas; RAG aporta explicación recuperada, no autoridad para probar precio, stock, fibra o identidad comercial. Contradicciones se conservan para revisión, no se sobrescriben por confianza aparente.

La ampliación opcional `evidence.source_details` conserva esas enumeraciones. Su `source_kind` es un discriminador textual ampliable que detalla el origen; no es una nueva autoridad ni un label ontológico. Los envelopes futuros referencian `evidence_id` de esta colección y reutilizan `assertion`, evitando una segunda colección de procedencia por atributo.

| Caso conceptual | source_type existente | source_details.source_kind propuesto | Trazabilidad necesaria |
| --- | --- | --- | --- |
| Predicción sobre un fotograma | vision_model | visual_frame | model_version_ref, observation_ref, scan_session_ref, frame_ref y región cuando corresponda |
| Fusión visual temporal | derived_rule | visual_accumulated | supporting_evidence_refs de observaciones independientes, sesión y método de fusión versionado en source_ref |
| OCR de etiqueta física | ocr | label_ocr | Observación/etiqueta y transcripción verificable; OCR puede fallar |
| Código de barras o referencia | ocr o human | barcode_reference | Lectura conservada y evidencia de su vínculo con la prenda; el código solo no demuestra composición |
| Ficha oficial del fabricante | catalog | manufacturer_sheet | URL, proveedor, consulta, identidad externa, variante y alcance de la ficha |
| Declaración de proveedor | catalog | supplier_declaration | Emisor, fecha y relación verificable con referencia/variante |
| Fuente externa no oficial | external_import | unofficial_external | Origen y límites; propuesta pendiente hasta evidencia habilitante o verificación humana documentada |
| Confirmación de Mati | human | administrator_confirmation | Reviewer, fecha y razón/observación de la revisión |
| Dato histórico local | catalog | historical_catalog | Snapshot/registro original, fecha y sus límites; no confirma situación comercial actual |

`source_type` describe cómo se obtuvo el dato y debe respetar `annotation_sources` del atributo. Una fuente no oficial no se renombra `catalog` para eludir esa restricción. Si la declaración está fuera de las fuentes habilitadas, se conserva como propuesta, UNKNOWN y evidencia externa, hasta disponer de respaldo permitido. Un OCR confirmado por Mati conserva la lectura original y añade evidencia human; no altera el origen histórico. `synthetic_contract_fixture` identifica únicamente la fixture de diseño.

Los metadatos opcionales distinguen `source_url`, `provider_ref`, `accessed_at`, `captured_at`, `frame_timestamp_ms`, `quality_assessment_ref`/`quality_notes`, `external_product_ref`, `external_variant_ref`, `match_assessment_ref` y `permission_record_ref`. Calidad de adquisición, autoridad declarada, coincidencia física, permiso y revisión humana son dimensiones separadas: no se resumen en un número de confianza universal. La calidad de un frame contempla visibilidad, desenfoque, iluminación y oclusión; las observaciones repetidas no se cuentan como confirmaciones independientes.

`source_scope` opcional distingue physical/local_catalog/external, independientemente del nombre ampliable source_kind. Para afirmar KNOWN real no visual desde source_type=catalog, el alcance debe estar declarado: physical identifica documentación físicamente vinculada, local_catalog un registro/snapshot local verificable y external una fuente documental externa. `local_catalog` no autoriza copiar una ficha externa ni acredita su verdad técnica por ser histórica. Un source_url o external_product_ref hace tratar la evidencia de catálogo como externa aunque el source_kind tenga un nombre nuevo o declare local_catalog. Si faltan metadatos para distinguir el origen de una declaración técnica, conservar UNKNOWN y la propuesta. Por ejemplo, un snapshot local conserva su fuente/fecha sin identidad externa; una ficha fabricante exige scope external y verificación de producto/variante.

`identity_match_status` contextual usa exact_verified/probable/similar/insufficient/no_results. No cambia los estados de conocimiento ni los source_type. `exact_verified` requiere referencia externa de producto y variante, evaluación de coincidencia, verificación de variante y `same_variant_verified=true`. Ninguna semejanza visual convierte una ficha en evidencia técnica de la prenda física. Para trasladar composición, medidas o propiedades técnicas desde fuentes externas, se exige coincidencia exacta y misma variante verificada, además del alcance del atributo, su fuente habilitada y evidencia suficiente. Una ficha genérica de modelo no prueba detalles que varían por talla, color, temporada o edición.

Tipos confidence: probability [0,1], cosine_similarity [-1,1], rule_score con escala declarada, human_confirmed sin número y unknown sin número. Un score de coseno no se presenta como probabilidad. Para afinidades multi-label, cada elemento puede conservar su propio registro de evidencia/confianza; la ontología no impone softmax ni suma uno. Una predicción futura referencia model_version_id, label_schema_version y preprocess/encoder profile cuando corresponde.

`calibration_ref` sigue opcional en JSON Schema por compatibilidad con fixtures y registros de diseño anteriores; esa forma no autoriza presentar un score como probabilidad calibrada. Las inferencias nuevas que declaren probability deben vincular un procedimiento de calibración real, con versión, datos de evaluación y ámbito aplicable. Sin ese respaldo se conserva el score en su escala documentada (`rule_score` cuando corresponda) o confianza unknown. Una fixture sintética no acredita calibración ni calidad de ningún modelo.

`affinity_scores` opcional conserva concept_id/confidence/evidence_refs por estilo o afinidad de uso presente en value. Puede incluir, en una fixture de validación, dos probabilidades independientes cuya suma supere uno; esto no es una predicción real ni exige exclusividad. El ejemplo publicado mantiene estilos UNKNOWN.

## Revisión humana

accepted conserva predicted_value y decisión; corrected exige human_value, reviewer y fecha; rejected conserva la predicción rechazada y no la trata como efectiva; pending no implica aceptación. Antes de persistencia real se definirá un selector de valor efectivo y reglas de conflicto. Esta fase valida la forma semántica, sin implementar captura de revisiones ni escrituras. Las revisiones incluyen evidence_refs y model_version_ref cuando existe una predicción de modelo.

El selector conceptual futuro mantiene una única assertion central como conclusión efectiva y un historial inmutable de propuestas/revisiones. Una aceptación respalda la propuesta revisada; una corrección utiliza human_value tras validar dominio, aplicabilidad y evidencia; un rechazo excluye la propuesta y conserva UNKNOWN si falta otra conclusión válida. Una revisión pending mantiene la incertidumbre. Si varias revisiones finales compiten, no se usa silenciosamente la última fecha: se requiere adjudicación explícita que referencie las revisiones resueltas. El contrato actual no implementa ese selector ni ejecuta escrituras.

`human_review.target_ref` continúa limitado a taxonomy.* o atributos presentes. Las acciones sobre coincidencias e imágenes pertenecen a los envelopes independientes de identificación/enriquecimiento: no se crean atributos brand, size o image_license para acomodarlas. Marca, talla comercial e identificadores externos son datos de identificación con evidencia; Laravel conserva el catálogo comercial. Mati puede confirmar, corregir, declarar desconocido/no aplicable cuando sea válido, rechazar todo y continuar manualmente. La acción «Ninguna opción corresponde a mi prenda» no selecciona un candidato arbitrario. Su feedback sólo será candidato para curación, nunca dataset validado automáticamente.

## Conflictos y conclusión

Cada conflicto futuro registra target_ref, alternativas con evidence_refs, contexto de producto/componente/variante, revisiones y razón de adjudicación. Mientras la evidencia no resuelva el valor, la assertion central es UNKNOWN con candidates. Las propuestas no se sobrescriben ni se convierten en KNOWN por un ranking de fuentes.

| Conflicto | Tratamiento conceptual |
| --- | --- |
| Color observado frente a color de ficha | Separar observed_color, denominación externa y color comercial; revisar luz, región y variante antes de tratarlo como contradicción del mismo atributo. |
| Dos composiciones documentales distintas | Comparar referencia, variante, temporada, componente y vigencia; conservar ambas declaraciones. Sin resolución, composición UNKNOWN. |
| Misma referencia con distinta talla/color | Crear candidatos externos separados por variante; no mezclar ficha de uno con fotografías o medidas de otro. |
| Etiqueta física frente a tienda externa | Verificar lectura OCR, vínculo físico, alcance de etiqueta y correspondencia de ficha. Una página oficial no prevalece universalmente sobre la evidencia física. |
| Mati rechaza identidad propuesta | Registrar rejected y contexto; descartar el candidato de la conclusión y permitir otra búsqueda o registro manual. |

Confirmar adecuación comercial no demuestra composición, calibración de modelo o licencia de una imagen. La conclusión de identidad ontológica puede discrepar del mapping histórico y conserva la categoría comercial de Laravel para revisión administrativa; no reclasifica el producto ni genera ground truth de entrenamiento.

## Espacio y conjuntos

spatial_semantics referencia asignaciones con primary_zone, secondary_zones y coverage_role. Llevar un bolso no se confunde con cubrir torso. Layering es una configuración de instancias con aristas over/under por zona y contexto; no una posición permanente del tipo de prenda. Conjuntos requieren component_refs y análisis por pieza antes de agregación. El ejemplo sintético usa camiseta corta con cobertura torso/hombros/brazos y deja composición, color observado, estilo y formalidad como UNKNOWN.

Un vestido es una entidad product de una sola prenda aunque dé apariencia de look completo. Un outfit es un resultado contextual que combina productos individuales y referencia sus entidades; no tiene entity_kind=set automáticamente. Un conjunto comercial auténtico tiene component_refs y vínculo comercial explícito. La posibilidad de vender el conjunto o sus piezas depende de registros e inventarios reales de Laravel; el modelo exacto de stock compartido/reservado/empaquetado permanece pendiente y no se implementa.

## Contratos independientes de diseño futuro

Los documentos [18_product_identification.md](18_product_identification.md), [19_external_product_search.md](19_external_product_search.md), [20_product_enrichment.md](20_product_enrichment.md), [21_trend_intelligence.md](21_trend_intelligence.md) y [22_outfit_intelligence.md](22_outfit_intelligence.md) definen envelopes, límites y criterios de aceptación. Sus estados de proceso no se incorporan a las enumeraciones de KNOWN/UNKNOWN/NOT_APPLICABLE. Product Intelligence conserva la evidencia de un producto; búsqueda, permisos de medios, observaciones temporales y disponibilidad comercial mantienen sus propios ámbitos.

## Qué valida esta entrega

La CLI carga ambos schemas y el ejemplo, resuelve IDs y referencias, exige estados coherentes, tipos de label, valores permitidos, evidencia, taxonomía compatible, aplicabilidad, porcentajes, zonas y precedencia de capas cuando hay datos. Los tests de mutación prueban rechazo de ejemplos inválidos. Pasar no prueba corrección de dominio ni aprobación de un producto real. El contrato permanece DRAFT_FOR_REVIEW y requerirá revisión de Mati antes de migraciones, API pública o uso comercial.
