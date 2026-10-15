# MORE — Resumen ejecutivo técnico de trabajo

Fecha de corte: 12 de octubre de 2026. **Este es un resumen técnico de la definición; no es un nuevo apartado para insertar automáticamente en el informe universitario.**

## Qué es y qué resuelve

MORE centraliza la gestión de prendas de Mati Boutique: ficha/variantes/imágenes, cantidades disponibles y comprometidas, ventas/reservas/pagos/recojo, catálogo/selección del cliente y control administrativo. Integra asistencia visual privada mediante modelos preentrenados y algoritmos de identificación, con evidencia, abstención y revisión humana. Su aporte de ingeniería está en conectar una gestión comercial consistente con inferencias trazables.

El problema es registro/disponibilidad/operación dispersos y manuales. V4 también promete recomendación aprendida y outfits; esas metas continúan pendientes de implementación/evaluación integral. Un catálogo útil o un dashboard SQL no demuestra recomendación aprendida, mejora de ventas ni ajuste de talla.

## Qué tiene actualmente

- Roles funcionales superadmin/vendedor/cliente; visitante público. Administrador distinto no tiene privilegios implementados propios.
- Catálogo interno con maestros, atributos, variantes, identificadores/precios e imágenes; catálogo público/ficha/filtros, favoritos y cuenta.
- Inventario agregado D/R, entradas, ajustes privilegiados con snapshot, Kardex y alertas; daños con reporte/revisión/descuento/reparación diferenciados.
- Venta presencial; compra online con voucher en revisión; reserva/anticipos/abonos; entrega/conversión y recibos. Revisión manual de pago, sin conciliación/gateway bancario certificado.
- Panel vendedor operativo, dashboards/reportes descriptivos, CSV/impresión, notificaciones internas, logs/auditoría y dump manual DB.
- Transporte Laravel–FastAPI, preprocesamiento y SigLIP, embeddings visuales y clasificación con abstención; código/QR y OCR, matching/similares, revisión y preparación de imágenes con distintos niveles de validación.

El informe del 11/10 documenta una inferencia real SigLIP CUDA y suites selectivas. Esta tarea no volvió a ejecutar pruebas o inferencias. El resultado partial y sus abstenciones son evidencia útil, no categoría/color comercial confirmados.

## Qué está pendiente o bloqueado

La política de anticipos ante rechazo/vencimiento difiere del guard de cancelación; verificar abono puede restablecer confirmada desde lista_recojo. El borrado de perfil y la baja administrativa divergen. Comprobantes almacenados public, definiciones de indicadores y recuperación integral requieren decisión/aceptación. La candidata no está certificada para producción.

Fashion Ontology V1.1.0 sigue DRAFT_FOR_REVIEW. Segmentación/detección experimental no tienen consumidor promovido (NONE). RAG Gate V2 mantiene approved=false y NO_PROMOTION; no sirve chunks ni respuesta generada. Perfil de embedding E5 aprobado no cambia esa decisión.

Complementos por reglas y compatibilidad cromática de pares existen, pero recomendación aprendida, motor completo de outfits, talla, try-on, tendencias, descripciones generativas, consultas naturales y Lens continuo no son funciones completas aceptadas. Fuentes externas están vacías; tener adaptadores no demuestra extracción activa.

## Qué deberá tener la versión objetivo propuesta

Primero, núcleo comercial con políticas, stock/dinero/estados/permisos y reportes reconciliados; privacidad y recuperación comprobadas; aceptación por actores y entorno final. Después, asistencia visual evaluada independientemente por tarea y ámbitos ontológicos aprobados gradualmente.

Si se conservan OE-04/05 y el alcance de V4, deberá implementar/evaluar recomendación basada en aprendizaje y outfits contextualizados frente a baselines y revisión humana. Si no son viables con los datos/tiempo disponibles, corresponde aprobar delimitación académica explícita, sin afirmar objetivos cumplidos. Talla/try-on/tendencias/LLM/RAG/segmentación siguen condicionados a decisiones y gates propios.

## Qué se entregó

Diez documentos completos en DOCUMENTACION/DEFINICION_MORE:

| Documento | Resultado |
| --- | --- |
| [01_DEFINICION_INTEGRAL.md](01_DEFINICION_INTEGRAL.md) | Identidad, actores/permisos, módulos, procesos, stock y versiones/límites |
| [02_REQUERIMIENTOS.md](02_REQUERIMIENTOS.md) | 87 RF y 18 RNF verificables, con prioridad, estado, evidencia y dependencias |
| [03_REGLAS_NEGOCIO.md](03_REGLAS_NEGOCIO.md) | 50 reglas con responsable, condición, resultado y observaciones |
| [04_ESTADO_IMPLEMENTACION.md](04_ESTADO_IMPLEMENTACION.md) | Matrices por función/IA, resultados reportados y 36 referencias de evidencia |
| [05_ARQUITECTURA_FUNCIONAL.md](05_ARQUITECTURA_FUNCIONAL.md) | Componentes, contratos, entradas/modelos/salidas, persistencia y fallos |
| [06_TRAZABILIDAD.md](06_TRAZABILIDAD.md) | OE/RF/módulo/actor/HU/iteración/RB/prueba/estado/evidencia; faltantes explícitos |
| [07_AUP_PLANIFICACION.md](07_AUP_PLANIFICACION.md) | Fases/disciplinas, 30 ET originales, trabajo pendiente y plan futuro separado de historia |
| [08_INCOHERENCIAS_INFORME.md](08_INCOHERENCIAS_INFORME.md) | 34 hallazgos con apartado/bloque V4, evidencia y corrección propuesta |
| [09_DECISIONES_PENDIENTES.md](09_DECISIONES_PENDIENTES.md) | 20 decisiones con alternativas, ventajas/riesgos, recomendación e impacto |
| [10_RESUMEN_EJECUTIVO_TECNICO.md](10_RESUMEN_EJECUTIVO_TECNICO.md) | Esta síntesis técnica de trabajo |

## Prioridades para decidir el siguiente trabajo

Resolver DP-01/02/03/05/09/10: actores y alcance académico, dinero/estados, cuentas, privacidad y recuperación. Formalizar requisitos/historias/evidencia antes de cambiar Word. Continuar con P-E1/P-C1/P-T1 del plan AUP y consolidar visual/ontología según datos. OE-04/05 necesitan decisión de viabilidad y trabajo específico; no activar experimentos rechazados para aparentar avance.

La documentación registra propuestas y limita lo comprobado a su evidencia. No se modificó Word ni se implementaron nuevas funcionalidades; cualquier paso posterior de edición/implementación espera autorización.

## Verificación de la entrega

Se verificaron los diez Markdown en UTF-8, los 87 RF, 18 RNF, 50 RB, 34 IC y 20 DP, referencias internas y enlaces locales: sin faltantes ni enlaces locales rotos. La matriz conserva los huecos de HU/iteración/prueba como tales; no se aprobaron vínculos temáticos propuestos.

El cotejo SHA256 inicial/final de las once áreas inventariadas del repositorio (696 archivos de fuente, pruebas, configuración, recursos IA y documentación previa), dependencias/locks/Compose, .git/HEAD y .git/index no detectó diferencias. La nueva carpeta se excluyó explícitamente de la huella de documentación previa. Los cambios anteriores del usuario se preservaron.

V4 conserva SHA256 3faaff715432c78299b2786777882aae4a95cd780c5eea0bcf7dd206a12cc35d. No se editaron diagramas ni se realizaron operaciones sobre la base activa. La verificación fue documental/de preservación; no ejecutó suites, build, inferencias, migraciones ni seeders.
