# Limitaciones y decisiones abiertas de la revisión

Estado global: `DRAFT_FOR_REVIEW`. Las políticas H01-H12 están aprobadas por el prompt humano del 2026-10-08; los ámbitos, ejemplos y vocabularios pendientes figuran en `governance.json`. La matriz17 distingue cambios aplicados de propuestas y funcionalidades futuras.

Quedaron resueltas las antiguas preguntas sobre POLERA=tshirt, ACCESORIOS sin calzado, separación comercial/ontológica, venta habitual individual, vestidos como una prenda, UNKNOWN ante ambigüedad, evidencia híbrida, atributo de puño y aprobación gradual. Las preguntas iniciales permanecen únicamente en el registro histórico del decision log.

| Pendiente | Evidencia disponible y límite | Criterio para resolver |
|---|---|---|
| Definiciones y ejemplos reales por familia | Vocabularios y ejemplos conceptuales; no guía aprobada ni dataset propio validado | Mati y revisores identificados, muestras positivas/negativas/fronterizas, acuerdos y discrepancias |
| Valores de sleeve_cuff_type | Definiciones bilingües y fuentes técnicas; atributo autorizado, cada valor pendiente | Revisar button/ribbed/elasticated/french con prendas reales y calidad de vista; sin autorización de especialista |
| Estilos, estéticas, códigos y formalidad | 10/4 estilos/estéticas conservados; ampliación23 candidata | Utilidad local, superposiciones, aliases, anclajes y persistencia; sin promoción automática |
| JERSEY ALPACA y casos similares | Dump prenda11 bajo ABRIGOS1; nombre no prueba categoría ni fibra | Evidencia física/documental verificable; UNKNOWN y candidatos; sin corregir categoría comercial |
| Talla ID54 XXXXXXXXL | Snapshot INFANTIL/ALFA/GENERAL; vínculos del dump en review_catalog_audit.json | Verificar maestro y relaciones vivos por lectura autorizada; excluir recomendación hasta verificación; preservar valor |
| Ubicación comercial de calzado | FOOTWEAR independiente; sin productos ACCESORIOS en corte histórico | Decisión comercial separada; sin categoría nueva ni soporte de entrenamiento inferido |
| Inventario de conjuntos | Venta completa/componentes permitida con registros e inventarios adecuados | Modelo compartido/reservado/empaquetado y prevención de doble descuento en otra tarea |
| Fuentes externas e imágenes | Contratos19/20: identidad exacta por variante y permisos | Acceso autorizado y licencia por uso/recurso; encontrar o seleccionar foto no concede derechos |
| Escaneo continuo | Contrato18: selección/fusión/cambio de prenda/privacidad/errores | Validar hardware, latencia y retención futura; ninguna cámara implementada |
| Tendencias y recomendación | Contratos21/22: temporalidad, región, hemisferio, clima y stock | Fuentes con fechas/métricas; disponibilidad por variante de Laravel |
| Responsables y aprobación | Rol Mati identificado, personas técnicas/anotadores pendientes | Evidencia por fase sin firmas inventadas |
| DB viva y ai-rag | Archivos locales inspeccionados; WSL denegado, exportación ai-rag inaccesible | Lectura SELECT-only futura; hashes no prueban conservación live de DB |

No se construyó dataset fino ni se ejecutó doble anotación. No se midió acuerdo, rendimiento, error de identificación, calibración, popularidad o crecimiento. `visually_learnable` expresa observabilidad propuesta; `trainable_visual=conditional` no autoriza entrenar. UNKNOWN y NOT_APPLICABLE no equivalen a null; vista oculta o catálogo vacío no acredita ausencia.

La investigación de estilos y puños no descarga datos/modelos/fotos ni acredita licencias de recursos completos. `research_sources.json`, `catalog_audit.json` y resultados previos se conservan como evidencia histórica, sin reutilizarlos como validación de 1.1.0.

Fuera de alcance: Laravel/React/FastAPI operativo, DB/migraciones, inventario/ventas/precios/SKU, baselines/configuraciones IA, entrenamiento, RAG, escáner, scrapers, descargadores, conectores, perfiles, recomendaciones y motores de outfits/tendencias. No git add/commit/push ni cambio de rama. Implementación y decisiones comerciales futuras requieren otra tarea.
