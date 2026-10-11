# 20. Product Enrichment — contrato de diseño futuro

Estado: `DRAFT_FOR_REVIEW`. Origen: decisiones humanas 4, 8, 9 y 12; aclaraciones 15 y 16 y modelo de evidencia de la Parte IV del prompt de 2026-10-08. Diseña selección de fotografías, propuestas de atributos y revisión. No implementa extracción, descarga, persistencia comercial ni interfaz.

## Entrada y separación de responsabilidades

Enrichment recibe una identificación parcial y candidatos de búsqueda del documento19. Conserva identidad/variante física y externa, evaluaciones de coincidencia y evidence central del contrato12. Puede operar sin coincidencia aceptada mediante evidencia física o datos manuales, manteniendo UNKNOWN donde no hay respaldo. Product Intelligence almacena conclusiones semánticas y su historial; Laravel mantiene los registros y operación comercial. Las fotos candidatas y sus permisos necesitan un envelope independiente: no se altera el scope de `attribute.finish`, `identity` o `human_review` para incluir gestión de medios.

| Campo del envelope futuro | Contenido y condición |
| --- | --- |
| enrichment_ref / identification_ref / search_ref | Referencias del proceso y origen de candidatos; search_ref puede faltar en flujo manual. |
| target_entity_ref / selected_external_candidate_ref | Prenda física y coincidencia externa elegida cuando exista; sin elección explícita no hay candidato seleccionado. |
| media_candidates | Principal/adicional/vistas/detalles propuestos con sus referencias y permissions. |
| proposed_assertions | target_ref + assertion reutilizada, source_evidence_refs, candidato externo y conflictos. No duplican el valor final de PI. |
| review_records / review_refs | Acciones sobre coincidencias y medios, separadas de las revisiones PI sobre taxonomy/attributes. |
| conflict_records | Alternativas/evidencias, contexto, motivo y adjudicación pendiente o resuelta. |
| product_intelligence_ref | Instancia central con assertions efectivas; su revisión no modifica automáticamente Laravel. |
| permission_record_refs / unresolved_fields | Permisos de medios, restricciones y campos que siguen UNKNOWN. |

## Fotografías y derechos de uso

Cada candidata conserva media_ref, URL de origen, proveedor, fecha de consulta, external_product_ref/external_variant_ref, evaluación de correspondencia física, tipo de vista y evidencia de la procedencia. La fotografía principal es una elección explícita; vistas alternativas no se mezclan entre variantes porque tengan el mismo modelo.

Un registro documental de permiso contiene permission_record_ref, fuente de la autorización/licencia, fecha de verificación, quién la verificó, autorización_status, permitted_uses, limitaciones, vigencia cuando exista y razón si falta permiso. `use_status` expresa si el uso concreto solicitado está permitido, pendiente, restringido o denegado. Los usos se distinguen: acceder al enlace, previsualizar por un mecanismo permitido, descargar, almacenar y mostrar comercialmente no son intercambiables. Un permiso comercial no autoriza entrenamiento y el diseño no inicia entrenamiento. Las denominaciones de permiso pertenecen a este contrato de medios, no a los estados KNOWN/UNKNOWN/NOT_APPLICABLE de los atributos.

Encontrar una imagen o aceptar que representa la prenda no acredita autorización. Si los términos/permisos no se verifican, no se propone almacenamiento ni reutilización como permitido: se conserva URL/procedencia y la limitación, o se ofrecen foto propia autorizada, material proporcionado por proveedor o registro manual. Si la fuente impide acceso/reutilización, se registra la restricción sin mecanismos de evasión ni extracción masiva. No se descarga ninguna imagen externa en esta tarea.

Mati puede comparar, seleccionar principal/adicional, rechazar una foto o todas, corregir el candidato, pedir búsqueda refinada y continuar manualmente. Aprobar una imagen por correspondencia no aprueba todos los atributos de su ficha ni su licencia. Un medio rechazado queda fuera de la selección efectiva, con el mínimo historial útil.

## Extracción y propuestas de atributos

Cada propuesta debe identificar el atributo/nivel taxonómico, valor propuesto, assertion efectiva separada, estado, confianza justificada, source_type/source_kind, evidence_refs, producto/variante externa y componente/región. Un campo ausente de la ficha es UNKNOWN, no NOT_APPLICABLE. Una prenda sin mangas acreditada puede dar NOT_APPLICABLE para sus detalles de manga; un puño oculto da UNKNOWN. Los candidatos no son una anotación KNOWN.

La evidencia visual sirve para aspectos observables. Fibra/composición, medidas exactas, propiedades técnicas y talla no se rellenan por parecido. Una fuente fabricante/proveedor aporta declaraciones documentales, con scope exacto de componente y variante. La transferencia de datos técnicos externos a la prenda física exige exact_verified, same_variant_verified=true y referencias de verificación; probable/similar no bastan. `annotation_sources` del atributo sigue vigente: external_import no se transforma en catalog por conveniencia. Una confirmación humana de un dato técnico debe identificar la evidencia verificada, no certificarlo sólo por aceptar una foto.

El origen conserva `source_scope` physical/local_catalog/external cuando corresponda. Para datos KNOWN reales no visuales de catalog debe declararse ese alcance; nombres nuevos de source_kind no eluden la verificación. Una ficha fabricante con URL o referencia externa se trata como external aunque se importe a un catálogo local. Un snapshot histórico genuinamente local usa local_catalog, fuente/fecha y evidencia de su dato, sin fingir ficha externa ni confirmar situación comercial actual. Metadatos insuficientes mantienen UNKNOWN/propuesta. El gating de correspondencia técnica cubre fichas externas official/catalog y no oficiales/external_import, no depende sólo de una lista cerrada de nombres.

Fit de diseño, silueta, pierna, tiro y largos visuales permanecen separados de talla comercial, entrepierna medida y ajuste personal. Si una talla/medida externa usa otro sistema o corresponde a otra variante, se conserva como declaración externa sin sustituir la talla Laravel. Un nombre histórico ALPACA no prueba la composición; una categoría comercial histórica no fija por sí sola la identidad ontológica.

## Conflictos, valor efectivo y revisión final

El futuro conflict_record contiene conflict_ref, target_ref, alternativas con evidence_refs, contexto físico/externo/componente/variante, review_refs y razón de adjudicación. La conclusión efectiva continúa centralizada en Product Intelligence. Si falta resolución, UNKNOWN.candidates conserva las hipótesis. Fuente de origen, calidad, coincidencia, decisión de Mati y conclusión final se registran por separado.

- Imagen azul y ficha que declara negro: comparar iluminación, región, variante y color observado frente a nombre comercial. No sobrescribir la observación por prioridad de búsqueda.
- Dos materiales documentales: conservar declaraciones por componente y fecha; verificar misma referencia/variante/edición y etiqueta física. La falta de resolución deja composición UNKNOWN.
- Etiqueta física y tienda externa contradictorias: verificar OCR y correspondencia de ambos objetos; ninguna autoridad universal sustituye esa evaluación.
- Distintas variantes del mismo modelo: separar fotografías y declaraciones. Una aceptación parcial no consolida datos de variantes diferentes.
- Mati rechaza identidad o atributo: conservar rejected/contexto, excluir la propuesta del valor efectivo y permitir búsqueda/refinamiento/manual.

Acciones conceptuales: aceptar sugerencia; seleccionar otra coincidencia; corregir categoría/atributo; introducir valor manual con evidencia; marcar UNKNOWN; indicar NOT_APPLICABLE con regla/contexto válido; rechazar todo; buscar otra vez y seguir manualmente. La opción «Ninguna opción corresponde a mi prenda» conserva el rechazo, no elige por defecto y no bloquea el registro.

Las revisiones de taxonomy/attributes usan `human_review` existente: accepted/corrected/rejected/pending; las correcciones finales necesitan reviewer/fecha/human_value. Las acciones sobre medios/coincidencias usan review_records del envelope, con el mismo sentido de decisión y target identificado, sin ampliar silenciosamente `human_review.target_ref`. Una adjudicación explícita referencia las decisiones resueltas si compiten; no se considera que la última revisión cronológica siempre gana. La futura escritura comercial tendrá autorización, validación y concurrencia propias; esta revisión no la implementa.

La aceptación comercial de Mati no certifica composición ni permisos. Su feedback podrá ingresar a una cola de curación futura con evidencia, guía de anotación y revisión; no pasa automáticamente a etiquetas de entrenamiento, dataset validado ni modelo aprobado.

## Criterios verificables para la implementación posterior

- Selección de foto, coincidencia y atributo son decisiones trazables independientes; todo puede rechazarse y el flujo manual sigue disponible.
- Probable/similar y fuentes no habilitadas no producen hechos técnicos KNOWN; declaraciones de variantes diferentes no se fusionan.
- Todo atributo propuesto/aceptado tiene procedencia, estado y scope; un rechazo preserva historia y excluye la propuesta de la conclusión.
- Sin permiso verificado, la reutilización solicitada queda pendiente/restringida y se ofrece alternativa autorizada.
- No hay escritura comercial ni feedback de entrenamiento automático; los campos comerciales permanecen bajo Laravel.

Dependencias pendientes: proveedores y permisos verificables, reglas de extracción por campo, selector/adjudicación efectivo, política de conservación de historial, UX y futuro contrato de escritura. Las licencias y coincidencias de productos reales deben verificarse en la integración futura; aquí no se simulan como aprobadas.
