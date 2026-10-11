# 18. Product Identification — contrato de diseño futuro

Estado: `DRAFT_FOR_REVIEW`. Origen: decisiones humanas 4, 5, 8, 9 y 12; aclaraciones 13 y 16 del prompt de revisión de 2026-10-08. Este documento define el comportamiento futuro del registro asistido. No existe en esta entrega captura de cámara, scanner, servicio, endpoint ni interfaz nuevos.

## Responsabilidad y entradas

Product Identification obtiene una hipótesis sobre una prenda física a partir de múltiples fotogramas de un escaneo continuo, etiquetas, referencias y aportes de Mati. Fashion Ontology valida significado/aplicabilidad; Product Intelligence conserva assertions y evidence; Laravel confirma identidad y datos comerciales. No modifica SKU, categorías comerciales, variantes, tallas, precio ni stock.

El futuro proceso admite cámara continua, imágenes aportadas por el administrador, lectura manual de etiqueta/referencia y registro manual. No exige hardware específico ni presupone calidad suficiente en todos los dispositivos. La alternativa sin cámara sigue el mismo contrato de evidencia y revisión.

| Campo del envelope futuro | Tipo conceptual | Regla |
| --- | --- | --- |
| identification_ref | Referencia opaca | Identifica un proceso, nunca sustituye el ID Laravel. |
| ontology_version / ontology_content_hash | Versión y SHA256 | Fijan los conceptos interpretados; se reutilizan desde Product Intelligence. |
| scan_session_ref | Referencia opcional | Agrupa observaciones de una misma prenda; no implica conservar vídeo. |
| target_entity_ref | Referencia de entidad | Distingue la prenda física observada de sus candidatos externos. |
| observation_refs / evidence_refs | Referencias únicas | Resuelven observaciones y evidence central de Product Intelligence. |
| preliminary_fields | Mapa tipo/color/marca/talla | Reutiliza la forma assertion del contrato12. Marca y talla no son nuevos atributos ontológicos. |
| external_identifiers | Lecturas con evidence_refs | Marca/modelo/referencia/SKU externo/código de barras, sin confundirlos con SKU de Mati. |
| identity_candidates | Hipótesis y evidence_refs | Retienen alternativas ontológicas o externas; UNKNOWN no se fuerza al candidato con mayor score. |
| review_refs / rejection_context | Historial opcional | Permiten corrección, rechazo y adjudicación trazables. |
| quality_assessment_refs / retention_policy_ref | Referencias | Hacen explícitos límites de adquisición y conservación. |

Tipo de prenda, color, marca y talla son objetivos iniciales deseados. Ninguno es una condición obligatoria para iniciar búsqueda: una camisa azul con marca acreditada y talla UNKNOWN puede continuar. La marca exige etiqueta, referencia o fuente vinculada; una semejanza visual con una marca no la acredita. La talla requiere lectura/documentación o declaración manual respaldada y conserva sistema/contexto; no se estima por apariencia. La anomalía histórica `XXXXXXXXL` conserva el valor y queda excluida de recomendación mientras no se verifique, sin normalización comercial.

## Selección y fusión de observaciones

1. Crear sesión y objetivo físico provisional. Detectar presencia de prenda y distinguir escenas vacías, varias prendas o cambios de objetivo.
2. Evaluar desenfoque, movimiento, exposición, iluminación, oclusión y detalle visible por región. Rechazar observaciones inútiles conservando únicamente el motivo necesario.
3. Elegir vistas útiles y regiones complementarias: frente, espalda, mangas, cuello, puños y etiqueta cuando sean visibles. La cobertura es explícita; no se inventan partes ocultas.
4. Reducir fotogramas redundantes con IDs/referencias de observación y un criterio versionado. Muchos frames iguales no equivalen a muchas fuentes independientes.
5. Proponer atributos observables y lecturas OCR/códigos opcionales; preservar transcripción, región, fuente y versión de modelo si interviene uno.
6. Fusionar observaciones de la misma prenda, componente y atributo. `derived_rule` + source_kind=visual_accumulated conserva al menos dos supporting_evidence_refs distintos de la misma scan_session_ref; las hipótesis originales no se borran. Cada visual_frame identifica frame_ref, scan_session_ref y captured_at; acumular frames de distintas prendas/sesiones exige separar objetivos, nunca afirmar una fusión de la misma prenda.
7. Mantener conflictos como UNKNOWN con candidates. La evidencia nueva puede aclarar una vista oculta, pero no elimina discrepancias ni acredita composición o centímetros.
8. Emitir identificación preliminar parcial y continuar con búsqueda externa o revisión/manual según lo disponible.

Un cambio de prenda, pérdida de seguimiento o separación ambigua entre objetivos obliga a pausar la fusión y abrir una nueva sesión/objetivo o pedir selección de objetivo. No combinar la etiqueta de una prenda con la imagen de otra. Una secuencia con varias piezas se analiza por entidad/componente; un vestido no se convierte en conjunto por cubrir varias zonas.

## Incertidumbre, estados de experiencia y recuperación

Los estados de proceso propuestos son listo para capturar, capturando, evidencia insuficiente, identificación parcial, revisión pendiente, listo para búsqueda, manual y cancelado. Son estados del envelope futuro, independientes de KNOWN/UNKNOWN/NOT_APPLICABLE y pendientes de contrato de interfaz. No se declara un estado de producción ni se implementan transiciones.

Sin permiso de cámara, dispositivo disponible o calidad suficiente, se ofrece carga autorizada, referencia/manual o reintento. Un fallo OCR no rellena marca/talla arbitrariamente. Una desconexión conserva el mínimo progreso que el administrador haya decidido guardar; un reintento reutiliza referencias para evitar duplicar evidencia. Mati puede corregir tipo/color/marca/talla, marcar UNKNOWN, rechazar todo, seleccionar otra coincidencia y elegir «Ninguna opción corresponde a mi prenda».

Los scores visuales conservan su escala. Una inferencia nueva probability exige calibración demostrada y ámbito de uso documentado; una similitud no se presenta como probabilidad. Los umbrales y presupuestos de latencia se definirán con datos reales y dispositivos probados, sin cifras hipotéticas en esta fase.

## Privacidad y conservación

El diseño futuro solicita permisos pertinentes y centra el encuadre en la prenda. Evita conservar rostros, entorno y audio innecesarios; la revisión técnica acordará tratamiento local/remoto y eliminación de regiones ajenas. Los frames son transitorios por defecto. Guardar una evidencia seleccionada requiere propósito, acceso restringido y política de retención; no se archiva el vídeo completo por rutina. La duración exacta, responsables y controles de borrado quedan pendientes antes de implementación. Las referencias a observaciones pueden persistir como trazabilidad sin conservar todos sus píxeles.

## Criterios verificables para la implementación posterior

- Vistas complementarias pueden sustentar observaciones distintas; duplicados no inflan certeza y una contradicción mantiene UNKNOWN.
- El cambio de prenda separa sesiones/evidencia; ninguna lectura se transfiere entre objetivos sin validación.
- Marca/talla ausentes permiten búsqueda parcial y modo manual. Cámara denegada o fallida no bloquea el registro.
- Un cuello/puño no visible se conserva UNKNOWN; ausencia de mangas confirmada permite NOT_APPLICABLE en sus detalles. Composición y medidas exactas requieren evidencia habilitada.
- Toda propuesta identifica origen, observación/modelo cuando corresponda y versión ontológica. Ningún feedback se declara dataset validado.
- La revisión de Mati conserva historial y no altera datos comerciales en esta fase.

Dependencias pendientes: autorización de diseño de experiencia, política de privacidad/retención, dispositivos, modelos evaluados, criterios de calidad y fusión, y plan de integración comercial posterior. La existencia de este contrato no aprueba esas dependencias.
