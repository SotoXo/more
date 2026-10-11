# 13. Integración futura y orden de fases

Esta entrega deja contratos `DRAFT_FOR_REVIEW` para integraciones posteriores. Ninguna conexión nueva de producción, red de aprendizaje ni motor de decisión se implementa aquí. El recorrido de aprendizaje propuesto es **ontología del ámbito → guía/label schema → dataset validado → baseline → transferencia evaluada → especialistas cuando procedan**. Product Intelligence puede conservar evidencia documental y revisión humana sin que exista un especialista; sus consumidores futuros son User Style Profile, Recommendation Engine, Outfit Engine y Mati Assistant. Trend Intelligence y Fashion Knowledge aportan contexto mediante contratos independientes. Cada promoción necesita aprobación propia: no se exige ni se declara un freeze global para avanzar en un ámbito aprobado.

## Contratos de arquitectura futura A–E

| Ámbito | Contrato documental | Entrada/salida y límite |
| --- | --- | --- |
| A. Product Identification | [18_product_identification.md](18_product_identification.md) | Escaneo continuo de varios frames, objetivo físico, selección/fusión de evidencia, identificación parcial y alternativa manual; no captura cámara. |
| B. External Product Search | [19_external_product_search.md](19_external_product_search.md) | Consultas parciales, fuentes verificables y candidatos separados por producto/variante; exacto/probable/similar/insuficiente/sin resultados; no integra buscadores. |
| C. Product Enrichment | [20_product_enrichment.md](20_product_enrichment.md) | Fotos candidatas/permisos, propuestas de atributos y revisiones con conflicto/valor efectivo separados; no descarga ni escribe catálogo. |
| D. Trend Intelligence | [21_trend_intelligence.md](21_trend_intelligence.md) | Observaciones internacionales/temporales, detección razonada de novedad y propuestas para Mati; no modifica vocabulario estable. |
| E. Outfit Intelligence | [22_outfit_intelligence.md](22_outfit_intelligence.md) | Contexto, compatibilidad/layering y productos/variantes/tallas consultados en Laravel; no crea conjuntos, reserva ni descuenta stock. |

El flujo de registro futuro es evidencia física/manual → identificación parcial → búsqueda → enriquecimiento/revisión → conclusión PI y, sólo mediante un contrato comercial autorizado posterior, registro Laravel. Tipo/color/marca/talla son objetivos iniciales; su ausencia no bloquea la búsqueda ni se rellena por parecido. Todas las propuestas admiten corrección, UNKNOWN, rechazo de todas y «Ninguna opción corresponde a mi prenda», con continuidad manual. Los campos brand/size del envelope de identificación no crean atributos ontológicos ni sustituyen maestros comerciales.

Se minimiza la conservación de frames; la selección de una fotografía distingue correspondencia física de autorización de uso. La ficha de un producto similar no confirma composición, medidas ni propiedades técnicas de Mati. Fuentes externas técnicas requieren coincidencia exacta y misma variante verificada, procedencia habilitada y scope documental del atributo. Los permisos/restricciones y registros de medios pertenecen al contrato C, separados de las assertions PI.

## De conceptos a labels y datos

La revisión humana de la ontología precede a una guía de anotación aprobada. El label schema deriva IDs, tipos, dominios y aplicabilidad de los artefactos; las matrices indican fuente de anotación, dificultad y posible aprendizaje visual. `future_head` describe un ámbito semántico GENERAL o SPECIALIST, no una arquitectura de red. `trainable_visual=conditional` requiere evidencia y evaluación futuras; `no` impide convertir la propiedad en un target visual por conveniencia.

La siguiente fase de dataset deberá preservar artículo objetivo, imagen/instancia/componente, ID comercial, versiones, anotador, evidencia, completitud y estado de valor. Las categorías comerciales no se toman como ground truth fino sin resolver el puente. Una imagen con varias prendas requiere identificar la unidad anotada; un conjunto vendible necesita evidencia de sus componentes. La guía debe contener ejemplos, contraejemplos y adjudicación de desacuerdos.

UNKNOWN se enmascara en el loss y se reporta como cobertura; N/A se valida con aplicabilidad y queda fuera del loss del valor. Para multilabel, una lista incompleta no convierte los labels restantes en negativos. Es necesario distinguir ausencias adjudicadas de campos no anotados. No equiparar valores de afinidad con probabilidades calibradas sin validación.

Los mappings públicos viven con dirección, granularidad, pérdida y procedencia. Una clase pública que agrupa varios conceptos MORE aporta como máximo evidencia al nivel agregado. Una clase compuesta puede descomponerse cuando su guía lo respalda; si falta equivalencia verificada, `NO_DIRECT_MAPPING`. El registro `research_sources.json` y el documento02 distinguen referencias conceptuales de permisos de uso: un mapping no autoriza fotos, anotaciones o entrenamiento comercial.

## Baseline, transferencia y especialización

Antes de entrenamiento se acordarán splits, identidad de artículos/imágenes, fuentes de labels y prevención de fuga entre productos relacionados. El baseline medirá qué información puede aprenderse con los datos propios. La elección de encoder, modelo multimodal, heads múltiples o especialistas queda abierta; no se infiere una red a partir de la lista de familias.

Cada evaluación futura deberá reportar resultados globales y por familia/atributo, soporte, cobertura, desconocidos, N/A y errores selectivos. Los tipos single-label, multilabel, ordinal, continuo y structured tienen métricas distintas en `label_schema.json`. Los gates se decidirán usando soporte, desacuerdo humano, costo de error y escenarios del negocio; no se fijan porcentajes de promoción arbitrarios sin dataset.

Transfer learning solo se evaluará después de confirmar licencias por componente y comprobar que la transformación de labels no inventa detalles. Un especialista futuro puede atender mangas, tiro, calzado o bolsos cuando haya soporte propio suficiente. Composición, aislamiento, capacidad y material de montura pueden exigir catálogo, OCR o revisión humana; su presencia en un ámbito SPECIALIST no implica que deban aprenderse desde una fotografía.

## Product Intelligence y evidencia

El contrato del documento12 describe identity, taxonomy, attributes, colors, materials, styles, spatial semantics, usage semantics, evidence, model metadata y human review. Cada afirmación conserva origen y estado. La ampliación opcional source_details detalla fotograma/acumulación, OCR/código, fabricante/proveedor, fuente no oficial, confirmación e histórico sin sustituir source_type. UNKNOWN.candidates conserva alternativas sin declararlas como valor efectivo. La fusión mantiene conflictos entre ficha, OCR, modelo y humano, sin borrar la procedencia ni reemplazar automáticamente Laravel. La calidad de fuente, correspondencia física, confianza y revisión son dimensiones distintas; no hay una jerarquía universal que haga ganar siempre a una web oficial.

El target garment de una futura segmentación podrá referenciar una instancia/componente y la evidencia espacial que alimenta Product Intelligence. La integración no presupone un modelo promovido. Mientras eso no se apruebe, siguen las rutas FULL IMAGE y el guard de color NONE existentes. El output del modelo visual será una hipótesis; la estructura formal la valida la ontología y la identidad comercial la confirma Laravel.

## Consumidores y dimensiones compartidas

| Consumidor futuro | Datos ontológicos / Product Intelligence | Datos que debe obtener fuera |
| --- | --- | --- |
| User Style Profile | Preferencias de estilo, familias de color, fit, silueta, formalidad, ocasiones y clima | Preferencias del usuario, contexto y revisiones; la ontología no almacena sus scores personales |
| Recommendation Engine | Identidad, atributos, afinidades, evidencia, calidad y cobertura de información | Perfil/contexto y realidad comercial Laravel; fórmula final pendiente |
| Outfit Engine | Body zones, coverage/carry/contact, layering por instancia, color, silueta, estilo, formalidad y ocasión | Configuración de uso, preferencias, Fashion Knowledge/Trend Intelligence contextual y productos/variantes/tallas disponibles consultados en Laravel |
| Trend Intelligence | IDs estables para referir prendas, atributos y estéticas | Observaciones fechadas, ámbito geográfico, procedencia y vigencia; no verdades permanentes |
| Fashion Knowledge | Definiciones e identificadores que una explicación puede citar | Explicaciones y heurísticas con fuentes, contexto y revisión |
| RAG | Documentos explicativos derivados y versionados de la ontología | Retrieval y gate existentes; la estructura JSON continúa como fuente formal |
| Mati Assistant | Ontología, conocimiento, herramientas, perfil y outputs de motores | Hechos comerciales confirmados por Laravel y decisiones de motores autorizados |

El perfil puede decir que a una persona le gusta relaxed fit; no redefine relaxed fit. Recommendation Engine puede preferir una prenda para un contexto; no convierte esa decisión en un axioma. Outfit Engine interpreta falda sobre pantalón como configuración candidata contextual, no invalida toda coincidencia de zonas. Trend Intelligence podrá informar vigencia de esa configuración sin cambiar el significado de overlay.

Las tendencias conservan fechas, lugar, evidencia temporal y contexto cultural, distinguiendo hemisferios. La evaluación de novedad compara alias, variantes, combinaciones, estética, layering, microtendencia y concepto nuevo; similitud numérica y popularidad no bastan. Una observación internacional se contextualiza antes de recomendar y puede tener representación parcial, completa o insuficiente en el inventario real. Los outfits combinan prendas vendidas individualmente; vestidos siguen una prenda y conjuntos comerciales reales necesitan vínculo/componentes explícitos. El modelo de inventario compartido/reservado/empaquetado y los precios de paquetes permanecen decisiones comerciales pendientes.

## RAG y conversación

Una fase posterior podrá generar explicaciones legibles a partir de IDs/definiciones revisados y publicarlas en un corpus versionado. El texto recuperado deberá indicar versión y evidencia y no sustituir la resolución de referencias o el validador formal. No se modifican ahora E5-small, corpus, embeddings, retrieval, router, gate V2, thresholds ni LR.

El LLM consultará herramientas para precio/stock/SKU y podrá explicar lo que los contratos autorizan. Un score de modelo no confirma stock; una explicación recuperada no aprueba un mapping comercial; una tendencia no demuestra preferencia personal. Una respuesta que dependa de información desconocida deberá conservar esa incertidumbre.

## Condiciones de paso entre fases

Por cada ámbito se revisan definición, ejemplos, evidencia, ambigüedades y preguntas del documento14, con responsables y decisión verificable. Después se aprueban separadamente guía/label schema y dataset validado, incluidos derechos por recurso. Más adelante se entrenan y evalúan modelos, se decide si la transferencia aporta valor y se establece autorización propia de producción. Diseñar contratos PI/consumidores no adelanta esas aprobaciones. Integraciones de persistencia, proveedores externos, medios y consumidores necesitan cambios versionados, revisables y autorización específica. Ninguna fase se considera ejecutada por la presencia de contratos; ámbitos sin evidencia pueden permanecer propuestos sin estar listos para entrenamiento.
