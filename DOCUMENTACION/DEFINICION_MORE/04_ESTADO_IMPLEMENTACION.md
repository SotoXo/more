# MORE — Estado de implementación y registro de evidencias

Corte documental: 12/10/2026. Se contrastó fuente actual con V4 y reportes existentes. **No se ejecutaron pruebas de software, solicitudes comerciales, migraciones, seeders, inferencias, entrenamientos ni consultas a la DB activa en esta tarea.** Las comprobaciones nuevas son estáticas y documentales; resultados fechados se atribuyen a su origen.

## Criterio de estado y de evidencia

| Categoría | Lectura que se aplica |
| --- | --- |
| IMPLEMENTADA Y COMPROBADA | Hay implementación y comprobación específica documentada/inspeccionable. Se declara alcance, fecha y límites; no se deduce aceptación de todo el sistema. |
| IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | Existe código/ruta/contrato, pero falta aceptación o validación suficiente del requisito completo. |
| PARCIALMENTE IMPLEMENTADA | Hay piezas utilizables, pero falta integración/política/validación o existe comportamiento incoherente para completar la función. |
| EXPERIMENTAL | Hay protocolo/scripts/resultados de investigación; sin autorización productiva del consumidor. |
| DISEÑADA | Hay arquitectura/contrato/plan, sin implementación integral acreditada. |
| PROPUESTA | Necesidad propuesta en esta definición, aún sin diseño/implementación aprobados suficientes. |
| FUERA DEL ALCANCE ACTUAL | No se atribuye a la versión actual; exclusión técnica/documental explícita. No implica prohibición permanente de evolución aprobada. |

Tipos de evidencia: **S**, inspección estática de código/configuración/test; **D**, documento de intención; **R**, resultado reportado con fecha y alcance; **A**, artefacto formal de decisión. Un test existente no es un resultado ejecutado; mock/SQLite no equivale a PostgreSQL concurrente. Un resultado histórico válido no prueba disponibilidad de runtime hoy.

## Cobertura y preservación

Se inventariaron rutas y controles de acceso; controladores/modelos/servicios; 47 archivos de migración; páginas/componentes React/Inertia, formularios y utilidades; tests PHP/frontend/Python; FastAPI/contratos; investigación/ontología/RAG/segmentación y reportes. La revisión prioriza comportamiento y restricciones por módulo. Se consultaron texto/tablas/ecuaciones de V4 sin renderizar ni revisar figuras.

Las migraciones describen esquema previsto; no se confirmó aquí su aplicación en DB viva. Los dumps históricos no se trataron como estado comercial actual. Dependencias, .env, imágenes y pesos no se alteraron. No se inspeccionó visualmente ni modificó material gráfico.

Al inicio existían tres cambios de código/test y entregables de defensa en Git. Se conservan; esta tarea solo añade los diez Markdown de DEFINICION_MORE. La verificación final de huellas por áreas, Word, HEAD e índice se registra en [10](10_RESUMEN_EJECUTIVO_TECNICO.md). No se atribuye al trabajo actual la corrección P1 previa.

## Núcleo funcional: situación consolidada

| Función | Estado | Evidencia | Qué está probado / qué falta |
| --- | --- | --- | --- |
| Acceso, perfil y cuentas | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-02/03/33 | Rutas/guards/tests presentes; reset SMTP y matriz completa de roles/propietario no aceptados ahora. |
| Política de baja de cuenta | PARCIALMENTE IMPLEMENTADA | EV-03/04/07 | Administración desactiva; perfil elimina físicamente. Resolver DP-05. |
| Maestros comerciales | IMPLEMENTADA Y COMPROBADA | EV-04/05/31 | Pantallas y catálogos inspeccionados en informe del 11/10; no todos los CRUD/estados aceptados. |
| Alta y variantes | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-05/06/07/33 | Código transaccional y validación; no POST de alta comercial en EV-31. |
| Edición sin reinicio de stock | IMPLEMENTADA Y COMPROBADA | EV-05/06/31/33 | 12 pruebas/58 aserciones de inventario reportadas; editar agotada, propietario y stock vigente. |
| Existencias y aritmética D/R | IMPLEMENTADA Y COMPROBADA | EV-08/31/33 | Pruebas aisladas de venta/reserva/liberar/completar/ajustar; no conciliación de todos los registros activos. |
| Kardex/alertas | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-08/20/31 | Código y pantallas existentes; no conciliación integral por operación/fecha. |
| Módulo separado inventario.ajustes | PARCIALMENTE IMPLEMENTADA | EV-04 | not_ready informativo; edición manual real existe en inventario.stock. |
| Venta directa y checkout completo | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-09/10/17/33 | Delta directo reportado; estados/guard y tests existentes; no aceptación actual completa. |
| Pagos/rechazo/cancelación de venta | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-09/14/33 | Reglas y actores actuales; no gateway ni matriz completa de pagos validada. |
| Reservas y abonos | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-11/12/33 | Separación y guard monetario de cancelación en fuente; pruebas existentes no rerun. |
| Rechazo/vencimiento con anticipo | PARCIALMENTE IMPLEMENTADA | EV-11/12 | No mismo guard que cancelar; decisión DP-03 y escenarios pendientes. |
| Conversión y entrega | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-13/14/31/33 | Consumo R documentado por test aislado; aceptación integral y política por rol pendiente. |
| Daños | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-15/14/33 | Reporte/confirmación/reparación/baja presentes; no devolución de venta ni todos los cierres comprobados. |
| Catálogo/ficha públicos | IMPLEMENTADA Y COMPROBADA | EV-16/31 | Muestra local de fichas y catálogo comprobada el 11/10; no todas las fichas/dispositivos. |
| Filtros combinados | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-16/31 | POLERA comprobado; falta batería combinatoria de talla/color/temporada/stock. |
| Dos carritos/favoritos/historial/recibos | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-17/18/33 | Código y validaciones; privacidad de voucher y aceptación de todos los flujos pendientes. |
| Dashboard | PARCIALMENTE IMPLEMENTADA | EV-19/31 | Renderizado documentado; gráfico mezcla unidades y recuentos. |
| Reportes/exportación/avisos/logs/auditoría | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-19/20/14 | Implementados; no predicciones ni monitoreo/entrega externa certificados. |
| Dump manual | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-21 | Código y descarga autorizada; no recuperación integral comprobada. |
| Recuperación integral | PROPUESTA | EV-21/36 | Ensayo y RPO/RTO pendientes DP-10. |
| Administrador independiente | PROPUESTA | EV-02/03 | No rol funcional diferente acreditado; DP-01. |
| Devolución/reembolso comercial | PROPUESTA | EV-02/15 | No proceso completo encontrado; DP-04. |

## IA: capacidades individualizadas

| RF | Capacidad | Estado | Evidencia | Límite / dependencia inmediata |
| --- | --- | --- | --- | --- |
| RF-058 | Registro asistido por imagen | IMPLEMENTADA Y COMPROBADA | EV-22/EV-23/EV-25/EV-31/EV-32/EV-33 | Analizar no cambia precio/SKU/stock ni guarda prenda; fallo mantiene registro manual. |
| RF-059 | Preprocesamiento y calidad | IMPLEMENTADA Y COMPROBADA | EV-22/EV-23/EV-31/EV-32/EV-33 | Archivo inválido/animado o excesivo se rechaza según contrato; original no se sustituye; heurística de calidad no certifica prenda. |
| RF-060 | Clasificación zero-shot SigLIP | IMPLEMENTADA Y COMPROBADA | EV-23/EV-25/EV-31/EV-32/EV-34 | Bajo score o margen activa abstención; ranking abstencionista no muestra Aplicar; aceptación comercial es humana. |
| RF-061 | Atributos visuales sugeridos | PARCIALMENTE IMPLEMENTADA | EV-23/EV-25/EV-28/EV-33 | Patrón abstencionista se mantiene informativo; no se declara modelo entrenado de manga/material/fit por existir definición ontológica. |
| RF-062 | Color descriptivo y bloqueo comercial | IMPLEMENTADA Y COMPROBADA | EV-23/EV-25/EV-29/EV-31/EV-33 | Geometría foreground no habilita ID; insuficiencia conserva diagnóstico y entrada manual; no se afirma medición física del tejido. |
| RF-063 | Embedding visual compatible | IMPLEMENTADA Y COMPROBADA | EV-23/EV-24/EV-31/EV-32/EV-33 | Igual dimensión con otro perfil no permite mezcla; vector no finito/nulo o identidad incompatible se rechaza y los válidos se preparan en L2. |
| RF-064 | Búsqueda por similitud visual | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-24/EV-34/EV-33 | Se excluye mezcla de perfiles; similar no se presenta como identidad exacta ni outfit; ausencia de vector conduce a información insuficiente. |
| RF-065 | Matching interno por identidad y candidatos | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-24/EV-30/EV-25/EV-32/EV-33 | SKU/GTIN exacto tiene trato distinto de coseno; conflicto de identificador no se compensa con parecido; no inventar producto nuevo. |
| RF-066 | OCR de etiqueta | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-30/EV-22/EV-32/EV-33 | OCR unavailable devuelve partial/fallback; composición o marca no se confirman por texto ambiguo; CER/WER requiere GT posterior. |
| RF-067 | Lectura de código y QR | IMPLEMENTADA Y COMPROBADA | EV-30/EV-22/EV-32/EV-33 | EAN/UPC valida checksum y ceros; QR arbitrario no se convierte en identidad comercial ni autorización de URL. |
| RF-068 | Revisión humana trazable | IMPLEMENTADA Y COMPROBADA | EV-25/EV-32/EV-33 | Otra cuenta no acepta run ajeno; categoría abstencionista no se acepta; precio/stock quedan fuera del auto-aplicar. |
| RF-069 | Preparación autorizada de imágenes | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-25/EV-30/EV-33 | Derechos unknown/prohibited no permiten publicar; token ajeno/caducado/usado falla; vista de referencia no es imagen publicada. |
| RF-070 | Embedding textual E5 | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-26/EV-31/EV-33 | 384 dimensiones E5 no mezcla con SigLIP; changed weights falla; aprobación de embedding no desbloquea gate RAG. |
| RF-071 | Recuperación documental con gate | EXPERIMENTAL | EV-26/EV-27/EV-33 | Flags/prompt del request no habilitan retrieval; precio/stock dinámico requieren herramientas autorizadas, no texto del corpus. |
| RF-072 | Gobernanza de conceptos y contratos | DISEÑADA | EV-28/EV-35 | DRAFT_FOR_REVIEW no se trata como aprobado; política aprobada no aprueba dataset ni modelo; fases de gobierno independientes. |
| RF-073 | Detección de instancia objetivo | EXPERIMENTAL | EV-29/EV-28 | Múltiples targets/cero target se abstienen; bounding box no acredita SKU ni color comercial; no endpoint productivo actual. |
| RF-074 | Segmentación semántica del target | EXPERIMENTAL | EV-29 | NONE se conserva; scores y geometría no son IoU/Dice medidos; perfil crop no mezcla con galería FULL. |
| RF-075 | Complementos por reglas comerciales | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-35/EV-16/EV-07 | Sin relación/stock no se inventan candidatos; no se denomina aprendizaje automático personalizado ni outfit completo. |
| RF-076 | Compatibilidad cromática de pares | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-22/EV-35 | Score 0–100 no es probabilidad de preferencia; par compatible no acredita outfit ni talla ni compra. |
| RF-077 | Modelo de recomendación de productos | DISEÑADA | EV-35/EV-28/EV-01 | Separación temporal/deduplicación; comparar contra RF-075; usuarios nuevos tienen fallback; publicación requiere aceptación. |
| RF-078 | Motor completo de outfits | DISEÑADA | EV-28/EV-35/EV-01 | No confundir conjunto comercial con outfit; compatibilidad de par no valida todos los componentes; no reservar/poner precio automáticamente. |
| RF-079 | Recomendación de talla | DISEÑADA | EV-01/EV-35 | Filtro talla no cuenta como recomendador; apariencia de talla no garantiza ajuste; no exigir biometría para compra. |
| RF-080 | Síntesis de apariencia | DISEÑADA | EV-01/EV-35 | No se demuestra talla/caída real; borrar/conservar foto sigue política aprobada; no servicio actual encontrado. |
| RF-081 | Análisis aprendido de ventas e inventario | DISEÑADA | EV-19/EV-35/EV-01 | No se etiqueta dashboard SQL como predicción; se controlan ventas derivadas, anulaciones y fuga temporal. |
| RF-082 | Trend Intelligence | DISEÑADA | EV-28/EV-35/EV-01 | Frecuencia externa no prueba demanda en El Alto; no scraping indiscriminado; separar alias de concepto nuevo. |
| RF-083 | Extracción externa controlada | PARCIALMENTE IMPLEMENTADA | EV-30/EV-31/EV-32/EV-33 | Hoy fuentes vacías; no se afirma extracción real automática. URL privada/redirección indebida/derecho prohibido se rechaza. |
| RF-084 | Generación de descripciones | DISEÑADA | EV-28/EV-35 | Caso de atributos desconocidos no rellena datos falsos; texto generado conserva fuentes y decisión humana. |
| RF-085 | Consultas naturales con herramientas autorizadas | DISEÑADA | EV-26/EV-27/EV-28/EV-35 | Router que pide tool no ejecuta SQL/LLM; query no puede ampliar permiso ni realizar pago/reserva; semántica y datos actuales separados. |
| RF-087 | Escaneo continuo de producto | DISEÑADA | EV-28/EV-35 | Una subida de foto no cuenta como Lens continuo; eventos de selección no son identidad validada ni anotación humana aprobada. |

No se promueve RAG por tener embedding textual, segmentación por disponer de máscaras privadas, recomendador aprendido por reglas de complementos, ni extracción real por existir adaptadores. RF-073 detección y RF-074 segmentación permanecen experimentales; RF-072 ontología es diseño formal/borrador y no un modelo comercial autorizado.

## Comprobaciones y experimentos ya documentados

| Origen | Fecha y resultado reportado | Interpretación válida | Interpretación inválida |
| --- | --- | --- | --- |
| EV-31, defensa | 11/10/2026: Unit 84 pruebas/239 aserciones; InventoryP0RegressionTest 12/58; build TypeScript/Vite correcto | Suites seleccionadas en entorno aislado; corrección agotada y reglas de inventario verificadas en ese corte | Todos los Feature, roles, comercio o producción aprobados |
| EV-31, verificacion_ia.json | 11/10/2026 12:15:08 -04:00: SigLIP CUDA, embedding 768, norma 0,999999983; partial, categoría abstiene y color insuficiente | Transporte/inferencia real sin persistencia; abstención útil | Categoría/color confirmados, POST UI nuevo probado, calidad universal |
| EV-32, cierre IA P1 | 04/10 local; cierre 05/10 UTC: uploads analyze, EAN13, QR, label y OCR unavailable documentados; FastAPI HTTP200 observados | E2E acotado con evidencia, fallback y continuidad manual | HTTP Laravel numérico capturado directamente, OCR científicamente perfecto o toda suite verde |
| EV-32, Python general | 113 passed, 2 failed, 1 deselected, 1 warning en reporte | Hay errores P2 visibles; no ocultar resultado completo | “Todas las pruebas aprobadas” |
| EV-32, workflow | Error residual SQLite al quitar modelo_pre mientras índice lo referencia | Problema de test/migración aislada pendiente, no demostración de fallo comercial equivalente | Feature suite completa aceptada |
| EV-34, visual N130 | 130 fotos comerciales tras deduplicación/exclusión; clasificación FULL 83/130 Top1 y 116/130 Top3; macro-F1 0,664920056; coverage formal 13/130 | Métricas locales con denominadores y abstención; baseline congelado | Dataset independiente de generalización, identificación de SKU o preferencia de compra |
| EV-29, segmentación | 05/10: NONE; 84 pruebas experimentales reportadas; no GT independiente de máscara/target | Política/scripts evaluados; FULL preservado, no consumidor promovido | IoU/Dice/mAP científicos de máscaras, endpoint o selección UI productiva |
| EV-27, gate V2 | 06/10 UTC: approved=false, NO_PROMOTION, sin ganador ni habilitación pública | Decisión formal vigente; fail-closed | Retrieval/generación LLM aprobados |
| EV-28, ontología | V1.1.0 DRAFT_FOR_REVIEW; gobierno por ámbito/fase | IDs/contratos/borrador revisable; políticas distintas de aprobación de modelo | Ontología completa, etiquetas/dataset/entrenamiento o producción aprobados |
| V4, tablas 21–46 | Junio de 2026 declarado: 60 casos, 36 terminados, 18 observados, 6 no terminados | Resultado manual declarado que necesita instrumentos/evidencias por caso | Resultado de PHPUnit actual o probabilidad operacional 60% |
| Auditoría vendedor EV-34 | Documento registra fallo previo de entorno de test por columna duplicada | No afirmar suite funcional verde desde esa auditoría | Fallo resuelto ahora o aprobación de todos los POST vendedor |

Las cantidades 13 prendas de diseños V2, 134 prendas/variantes del reporte local y N130 de evaluación no se suman ni se presentan como contradicción automática: son fechas, unidades y poblaciones diferentes. Tampoco 126 disponibles y 6 reservadas reportadas el 11/10 describen cantidades actuales verificadas hoy.

## Funciones expresamente fuera del alcance actual

| Capacidad | Estado | Fundamento |
| --- | --- | --- |
| Proveedor/compras detalladas y contabilidad avanzada | FUERA DEL ALCANCE ACTUAL | Límites V4 B1671–1672; no módulo completo identificado. |
| Pasarela/conciliación bancaria y facturación fiscal | FUERA DEL ALCANCE ACTUAL | V4 limita pasarelas complejas; revisión manual y recibo interno no son esos sistemas. |
| Escaneo corporal 3D de alta precisión | FUERA DEL ALCANCE ACTUAL | Límite V4 B1675; no hardware/flujo demostrado. |
| Multitienda/multialmacén y serial por ejemplar | FUERA DEL ALCANCE ACTUAL | Una tienda y cantidades agregadas; ubicaciones TIENDA/DEPOSITO no son sistema multiwarehouse. |
| ANN productivo HNSW/IVFFlat | FUERA DEL ALCANCE ACTUAL | Búsqueda actual exacta; necesidad futura depende de medición. |
| Autonomía comercial del modelo / SQL libre de escritura | FUERA DEL ALCANCE ACTUAL | Autoridad Laravel/humana y límites de gobierno; no acción autorizada. |

Los diseños de talla/try-on/tendencias permanecen identificados en su categoría, pero están fuera de la candidata actual: que tengan categoría DISEÑADA no obliga a liberarlos. DP-02/17/18 decide su relación con la versión objetivo académica.

## Registro de evidencias de origen

Las rutas son relativas a la raíz del repositorio salvo el Word. Las referencias a directorios identifican una familia inspeccionada; una afirmación comercial crítica además nombra el servicio/función en documentos 01/03/05. El registro no pretende que un reporte sustituya sus artefactos primarios.

### EV-01 — Informe académico V4

**Origen:** [V4](C:/Users/Usuario/Downloads/SotoCazuLimberPII2V4.docx)

**Valor y límite de la evidencia:** Fuente de intención académica y resultados declarados; lectura textual de párrafos, tablas y ecuaciones, sin renderizar figuras. SHA256 3faaff715432c78299b2786777882aae4a95cd780c5eea0bcf7dd206a12cc35d.

### EV-02 — Rutas y autorización

**Origen:** [routes/web.php](../../routes/web.php); [routes/auth.php](../../routes/auth.php); [bootstrap/app.php](../../bootstrap/app.php); [app/Http/Middleware/EnsureSuperadmin.php](../../app/Http/Middleware/EnsureSuperadmin.php); [app/Http/Middleware/EnsureVendedor.php](../../app/Http/Middleware/EnsureVendedor.php)

**Valor y límite de la evidencia:** Inspección estática. Las rutas api/carrito y superadmin/ai pertenecen a web.php; no aparece routes/api.php. No se encontró directorio app/Policies. Autorización mediante middleware y comprobaciones de servicio/controlador.

### EV-03 — Acceso e identidad

**Origen:** [app/Models/User.php](../../app/Models/User.php); [app/Models/Role.php](../../app/Models/Role.php); [app/Http/Requests/Auth/LoginRequest.php](../../app/Http/Requests/Auth/LoginRequest.php); [app/Http/Controllers/Auth/RegisteredUserController.php](../../app/Http/Controllers/Auth/RegisteredUserController.php); [app/Http/Controllers/ProfileController.php](../../app/Http/Controllers/ProfileController.php); [app/Support/UserValidation.php](../../app/Support/UserValidation.php)

**Valor y límite de la evidencia:** Roles del sistema superadmin/vendedor/cliente; cuenta y rol activos en login y paneles; registro cliente; hash de contraseña; discrepancia entre baja administrativa y eliminación del perfil.

### EV-04 — Recursos administrativos

**Origen:** [app/Services/Superadmin/Resources/ResourceDefinitionService.php](../../app/Services/Superadmin/Resources/ResourceDefinitionService.php); [app/Services/Superadmin/Resources/ResourceWriteService.php](../../app/Services/Superadmin/Resources/ResourceWriteService.php); [app/Services/Superadmin/Resources/ResourceControllerActions.php](../../app/Services/Superadmin/Resources/ResourceControllerActions.php)

**Valor y límite de la evidencia:** CRUD principal y secundario diferenciados; inventario.ajustes not_ready; borrados protegidos; edición de stock con bloqueo y snapshot.

### EV-05 — Validaciones y galería

**Origen:** [app/Services/Superadmin/Resources/ResourceValidationService.php](../../app/Services/Superadmin/Resources/ResourceValidationService.php); [app/Services/Superadmin/Resources/ProductImageService.php](../../app/Services/Superadmin/Resources/ProductImageService.php); [resources/js/Pages/Superadmin/ProductCreate.tsx](../../resources/js/Pages/Superadmin/ProductCreate.tsx); [resources/js/lib/commercialValidation.ts](../../resources/js/lib/commercialValidation.ts)

**Valor y límite de la evidencia:** Reglas del servidor, pasos del editor y almacenamiento; una variante existente propia acepta disponible cero; nueva exige mínimo uno.

### EV-06 — Prenda y variante

**Origen:** [app/Services/Superadmin/Resources/ProductCatalogService.php](../../app/Services/Superadmin/Resources/ProductCatalogService.php); [app/Models/Prenda.php](../../app/Models/Prenda.php); [app/Models/PrendaVariante.php](../../app/Models/PrendaVariante.php); [app/Models/ImagenPrenda.php](../../app/Models/ImagenPrenda.php)

**Valor y límite de la evidencia:** Alta y edición transaccionales; atributos y paletas; edición de catálogo preserva inventario existente; eliminación de variante ligada a venta/reserva/Kardex bloqueada.

### EV-07 — Esquema por migraciones

**Origen:** [database/migrations/](../../database/migrations/); [app/Models/](../../app/Models/)

**Valor y límite de la evidencia:** 47 archivos de migración y 35 modelos en el árbol leído. Esquema declarado, no comprobación de migraciones aplicadas ni lectura de la base activa. Inventario único por variante; enteros de stock sin CHECK no negativo en su migración.

### EV-08 — Inventario y Kardex

**Origen:** [app/Services/InventoryMovementService.php](../../app/Services/InventoryMovementService.php); [app/Services/Superadmin/InventoryStatusService.php](../../app/Services/Superadmin/InventoryStatusService.php); [app/Services/Superadmin/SaleInventoryService.php](../../app/Services/Superadmin/SaleInventoryService.php); [app/Services/Superadmin/ReservationInventoryService.php](../../app/Services/Superadmin/ReservationInventoryService.php)

**Valor y límite de la evidencia:** Aritmética por origen, filas bloqueadas, valores antes/después y referencias. Total operativo disponible+reservado; umbral bajo <=3 en código.

### EV-09 — Ventas y revisión

**Origen:** [app/Services/Superadmin/SaleService.php](../../app/Services/Superadmin/SaleService.php); [app/Services/Vendedor/VendedorSalesService.php](../../app/Services/Vendedor/VendedorSalesService.php); [app/Services/Vendedor/VendedorPaymentReviewService.php](../../app/Services/Vendedor/VendedorPaymentReviewService.php)

**Valor y límite de la evidencia:** Venta directa registrada; compra en revisión; importe verificado igual al total; rechazo vendedor produce incompleta; cancelación es otra acción.

### EV-10 — Checkout

**Origen:** [app/Services/Superadmin/SaleCheckoutService.php](../../app/Services/Superadmin/SaleCheckoutService.php); [app/Http/Controllers/CompraOnlineController.php](../../app/Http/Controllers/CompraOnlineController.php); [resources/js/Pages/Checkout.tsx](../../resources/js/Pages/Checkout.tsx)

**Valor y límite de la evidencia:** Cliente activo; precio y actividad revalidados bajo bloqueo; comprobante pendiente y separación de stock. QR/transferencia como medios declarados, sin conciliación bancaria.

### EV-11 — Reserva

**Origen:** [app/Services/Superadmin/ReservationService.php](../../app/Services/Superadmin/ReservationService.php); [app/Services/Superadmin/ReservationPickupService.php](../../app/Services/Superadmin/ReservationPickupService.php); [app/Services/Superadmin/PickupLogisticsService.php](../../app/Services/Superadmin/PickupLogisticsService.php)

**Valor y límite de la evidencia:** createReservation, cancel, markExpired, markReady, complete y settleCompletedReservation. No equiparar estado visual de vencimiento con transición persistida.

### EV-12 — Pagos de reserva

**Origen:** [app/Services/Superadmin/ReservationPaymentService.php](../../app/Services/Superadmin/ReservationPaymentService.php); [app/Services/Vendedor/VendedorReservationService.php](../../app/Services/Vendedor/VendedorReservationService.php); [app/Services/Vendedor/VendedorPaymentReviewService.php](../../app/Services/Vendedor/VendedorPaymentReviewService.php)

**Valor y límite de la evidencia:** Pago declarado distinto de confirmado; primer anticipo 50%; cancelación con pagos confirmados impedida; rechazo y vencimiento sin ese mismo guard.

### EV-13 — Conversión y entrega

**Origen:** [app/Services/Superadmin/ReservationToSaleService.php](../../app/Services/Superadmin/ReservationToSaleService.php); [app/Services/Superadmin/SalePickupService.php](../../app/Services/Superadmin/SalePickupService.php); [app/Services/Vendedor/VendedorPickupService.php](../../app/Services/Vendedor/VendedorPickupService.php); [app/Services/Superadmin/ReservationService.php](../../app/Services/Superadmin/ReservationService.php)

**Valor y límite de la evidencia:** Venta derivada VEN-RES-<nre_res>, sin reserva_id en ventas; vendedor entrega reserva pagada, superadmin puede completar saldo mediante flujo específico.

### EV-14 — Panel vendedor

**Origen:** [app/Http/Controllers/Vendedor/](../../app/Http/Controllers/Vendedor/); [app/Services/Vendedor/](../../app/Services/Vendedor/); [resources/js/Pages/Vendedor/](../../resources/js/Pages/Vendedor/)

**Valor y límite de la evidencia:** Rutas operativas y vistas, consulta de catálogo/inventario; no CRUD de prendas ni ajuste directo de stock. Auditoría histórica no prueba por sí sola cada POST actual.

### EV-15 — Daños

**Origen:** [app/Services/Superadmin/DamagedProductService.php](../../app/Services/Superadmin/DamagedProductService.php); [app/Services/Vendedor/VendedorDamagedProductService.php](../../app/Services/Vendedor/VendedorDamagedProductService.php); [database/migrations/2026_06_05_120000_create_prenda_danio_reportes_table.php](../../database/migrations/2026_06_05_120000_create_prenda_danio_reportes_table.php)

**Valor y límite de la evidencia:** Vendedor reporta sin afectar stock; superadmin confirma y puede descontar; reparación puede restituir; baja final evita doble descuento.

### EV-16 — Tienda y filtros

**Origen:** [app/Services/StorefrontCatalogService.php](../../app/Services/StorefrontCatalogService.php); [app/Services/ProductMapper.php](../../app/Services/ProductMapper.php); [app/Http/Controllers/CatalogoController.php](../../app/Http/Controllers/CatalogoController.php); [app/Http/Controllers/PrendaController.php](../../app/Http/Controllers/PrendaController.php); [resources/js/Pages/Shop/](../../resources/js/Pages/Shop/)

**Valor y límite de la evidencia:** Consulta pública, facetas y variantes. Catálogo permite consultas de disponibilidad distintas; acceso no exige registro.

### EV-17 — Dos carritos e intención única

**Origen:** [app/Http/Controllers/CarritoController.php](../../app/Http/Controllers/CarritoController.php); [resources/js/lib/purchaseCart.ts](../../resources/js/lib/purchaseCart.ts); [resources/js/lib/commercialOperation.ts](../../resources/js/lib/commercialOperation.ts); [app/Http/Middleware/CommercialOperation.php](../../app/Http/Middleware/CommercialOperation.php); [database/migrations/2026_09_10_000001_create_commercial_operations_table.php](../../database/migrations/2026_09_10_000001_create_commercial_operations_table.php)

**Valor y límite de la evidencia:** Reserva en sesión carrito; compra en localStorage mati_boutique_purchase_cart. Ninguno separa stock al agregar. commercial.once vincula UUID, actor, ruta y hash.

### EV-18 — Cuenta, favoritos y recibos

**Origen:** [app/Http/Controllers/CuentaController.php](../../app/Http/Controllers/CuentaController.php); [app/Http/Controllers/FavoritoController.php](../../app/Http/Controllers/FavoritoController.php); [app/Http/Controllers/ReceiptController.php](../../app/Http/Controllers/ReceiptController.php); [app/Services/ReceiptService.php](../../app/Services/ReceiptService.php); [resources/js/Pages/Cuenta/](../../resources/js/Pages/Cuenta/)

**Valor y límite de la evidencia:** Consulta por propietario y recibo condicionado. Recibo interno no acredita factura fiscal ni pago bancario. Revisar aislamiento de archivos de comprobantes publicados.

### EV-19 — Indicadores y reportes

**Origen:** [app/Services/Superadmin/DashboardStatsService.php](../../app/Services/Superadmin/DashboardStatsService.php); [app/Services/Superadmin/ReportQueryService.php](../../app/Services/Superadmin/ReportQueryService.php); [app/Services/Superadmin/ReportExportService.php](../../app/Services/Superadmin/ReportExportService.php); [app/Services/Vendedor/VendedorReportService.php](../../app/Services/Vendedor/VendedorReportService.php); [resources/js/Components/Superadmin/Reports/](../../resources/js/Components/Superadmin/Reports/)

**Valor y límite de la evidencia:** Consultas y agregaciones descriptivas, CSV e impresión; ninguna acredita pronóstico entrenado.

### EV-20 — Logs, auditoría y notificación

**Origen:** [app/Services/SystemLogService.php](../../app/Services/SystemLogService.php); [app/Services/AuditLogService.php](../../app/Services/AuditLogService.php); [app/Observers/CriticalAuditObserver.php](../../app/Observers/CriticalAuditObserver.php); [app/Providers/AppServiceProvider.php](../../app/Providers/AppServiceProvider.php); [app/Services/CommercialNotificationService.php](../../app/Services/CommercialNotificationService.php); [app/Http/Controllers/NotificationController.php](../../app/Http/Controllers/NotificationController.php)

**Valor y límite de la evidencia:** Eventos y modelos observados; notificaciones internas. No garantiza inmutabilidad criptográfica, entrega SMTP productiva o no repudio jurídico.

### EV-21 — Respaldo

**Origen:** [app/Services/DatabaseBackupService.php](../../app/Services/DatabaseBackupService.php); [app/Http/Controllers/Superadmin/DatabaseBackupController.php](../../app/Http/Controllers/Superadmin/DatabaseBackupController.php)

**Valor y límite de la evidencia:** pg_dump manual, registro y descarga local protegida; no ruta de restauración ni prueba de recuperación integral actual.

### EV-22 — Transporte y entrada IA

**Origen:** [app/Http/Controllers/Superadmin/AiController.php](../../app/Http/Controllers/Superadmin/AiController.php); [app/Services/AI/MatiAiClient.php](../../app/Services/AI/MatiAiClient.php); [config/mati_ai.php](../../config/mati_ai.php); [services/mati-ai/src/mati_ai/api/v1/routes.py](../../services/mati-ai/src/mati_ai/api/v1/routes.py); [services/mati-ai/src/mati_ai/contracts/common.py](../../services/mati-ai/src/mati_ai/contracts/common.py)

**Valor y límite de la evidencia:** Bearer interno, UUID, contrato v1, timeouts, validación de envelope, fallback manual y límite de imágenes.

### EV-23 — Inferencia visual

**Origen:** [services/mati-ai/src/mati_ai/core/model_manager.py](../../services/mati-ai/src/mati_ai/core/model_manager.py); [services/mati-ai/src/mati_ai/vision/validation.py](../../services/mati-ai/src/mati_ai/vision/validation.py); [services/mati-ai/src/mati_ai/vision/classification.py](../../services/mati-ai/src/mati_ai/vision/classification.py); [services/mati-ai/src/mati_ai/vision/attributes.py](../../services/mati-ai/src/mati_ai/vision/attributes.py); [services/mati-ai/src/mati_ai/vision/color.py](../../services/mati-ai/src/mati_ai/vision/color.py)

**Valor y límite de la evidencia:** SigLIP zero-shot; Top-3, coseno no calibrado y abstención; color geométrico no demuestra identidad de prenda.

### EV-24 — Vectores visuales

**Origen:** [app/Services/AI/CatalogEmbeddingStore.php](../../app/Services/AI/CatalogEmbeddingStore.php); [app/Services/AI/CatalogAiService.php](../../app/Services/AI/CatalogAiService.php); [database/migrations/2026_10_03_000001_create_ai_product_embeddings_table.php](../../database/migrations/2026_10_03_000001_create_ai_product_embeddings_table.php); [services/mati-ai/src/mati_ai/contracts/embedding.py](../../services/mati-ai/src/mati_ai/contracts/embedding.py)

**Valor y límite de la evidencia:** Identidad completa de espacio vectorial y búsqueda coseno exacta pgvector; no índice ANN HNSW/IVFFlat.

### EV-25 — Revisión y publicación IA

**Origen:** [app/Services/AI/AiReviewService.php](../../app/Services/AI/AiReviewService.php); [app/Services/AI/AiPublicationService.php](../../app/Services/AI/AiPublicationService.php); [app/Services/AI/ColorimetryCatalogGuard.php](../../app/Services/AI/ColorimetryCatalogGuard.php); [resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx](../../resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx); [resources/js/lib/garmentAssistant.ts](../../resources/js/lib/garmentAssistant.ts)

**Valor y límite de la evidencia:** Predicción, decisión humana y valor guardado separados; token de imagen ligado a propietario, aprobación, TTL y derechos.

### EV-26 — Embedding textual y recuperación

**Origen:** [services/mati-ai/resources/rag/model-profile.json](../../services/mati-ai/resources/rag/model-profile.json); [services/mati-ai/src/mati_ai/rag/](../../services/mati-ai/src/mati_ai/rag/); [app/Services/AI/RagGateV2Retriever.php](../../app/Services/AI/RagGateV2Retriever.php); [config/rag.php](../../config/rag.php); [config/rag_gate_v2.php](../../config/rag_gate_v2.php); [database/migrations/2026_10_05_000001_create_rag_tables.php](../../database/migrations/2026_10_05_000001_create_rag_tables.php)

**Valor y límite de la evidencia:** Perfil E5 aprobado para embedding; no equivale a RAG autorizado. Corpus/versión/hash separados del catálogo dinámico.

### EV-27 — Gate RAG V2

**Origen:** [services/mati-ai/resources/rag_gate_v2/gate-decision.json](../../services/mati-ai/resources/rag_gate_v2/gate-decision.json); [app/Services/AI/RagGateV2Model.php](../../app/Services/AI/RagGateV2Model.php); [app/Services/AI/RagGateV2Retriever.php](../../app/Services/AI/RagGateV2Retriever.php); [app/Http/Controllers/Superadmin/RagController.php](../../app/Http/Controllers/Superadmin/RagController.php)

**Valor y límite de la evidencia:** 2026-10-06 UTC: approved=false, NO_PROMOTION, public_enablement_performed=false. Respuesta actual sin chunks ni generated_answer.

### EV-28 — Fashion Ontology

**Origen:** [DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/24_gobernanza_por_ambito.md](../../DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/24_gobernanza_por_ambito.md); [DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/13_integracion_futura.md](../../DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/13_integracion_futura.md); [DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/16_reporte_final.md](../../DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/16_reporte_final.md); [services/mati-ai/resources/fashion_ontology/v1/governance.json](../../services/mati-ai/resources/fashion_ontology/v1/governance.json)

**Valor y límite de la evidencia:** V1.1.0 DRAFT_FOR_REVIEW; políticas aprobadas no aprueban ámbitos, anotaciones, dataset, entrenamiento, modelo o producción.

### EV-29 — Segmentación semántica

**Origen:** [DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/05_integracion_e2e.md](../../DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/05_integracion_e2e.md); [DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/06_limitaciones.md](../../DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/06_limitaciones.md); [services/mati-ai/scripts/segmentation/](../../services/mati-ai/scripts/segmentation/)

**Valor y límite de la evidencia:** Cierre 2026-10-05: NONE; investigación aislada sin consumidor promovido, endpoint, UI o cambio de vectores canónicos.

### EV-30 — Identificación, OCR y fuentes

**Origen:** [services/mati-ai/src/mati_ai/identification/](../../services/mati-ai/src/mati_ai/identification/); [services/mati-ai/src/mati_ai/enrichment/](../../services/mati-ai/src/mati_ai/enrichment/); [services/mati-ai/config/external-sources.example.json](../../services/mati-ai/config/external-sources.example.json)

**Valor y límite de la evidencia:** Código/QR/OCR y ranking; búsqueda externa condicionada a adaptadores, política de URL, derechos y configuración.

### EV-31 — Verificación local más reciente

**Origen:** [DOCUMENTACION/DEFENSA_20261011/informe_defensa.md](../../DOCUMENTACION/DEFENSA_20261011/informe_defensa.md); [DOCUMENTACION/DEFENSA_20261011/verificacion_ia.json](../../DOCUMENTACION/DEFENSA_20261011/verificacion_ia.json)

**Valor y límite de la evidencia:** Informe fechado, no ejecución de esta tarea: Unit 84/239 aserciones; InventoryP0 12/58; build correcto; inferencia CUDA 768 con abstención. No suite Feature completa ni checkout real.

### EV-32 — Correcciones IA P1

**Origen:** [DOCUMENTACION/IA/CORRECCIONES_P1/02_validacion_post_p1.md](../../DOCUMENTACION/IA/CORRECCIONES_P1/02_validacion_post_p1.md); [DOCUMENTACION/IA/CORRECCIONES_P1/03_estado_p1.md](../../DOCUMENTACION/IA/CORRECCIONES_P1/03_estado_p1.md)

**Valor y límite de la evidencia:** 2026-10-04 local, cierre 2026-10-05 UTC: E2E documentado de uploads/OCR/códigos/fallback; P2 permanecen. No calidad científica OCR ni toda suite verde.

### EV-33 — Pruebas presentes

**Origen:** [tests/Feature/InventoryP0RegressionTest.php](../../tests/Feature/InventoryP0RegressionTest.php); [tests/Feature/CriticalReservationCheckoutP1Test.php](../../tests/Feature/CriticalReservationCheckoutP1Test.php); [tests/Feature/CommercialOperationTest.php](../../tests/Feature/CommercialOperationTest.php); [tests/Feature/CommercialOperationConcurrencyTest.php](../../tests/Feature/CommercialOperationConcurrencyTest.php); [tests/Feature/MatiAiWorkflowTest.php](../../tests/Feature/MatiAiWorkflowTest.php); [tests/Feature/MatiAiP1Test.php](../../tests/Feature/MatiAiP1Test.php); [tests/Feature/RagGateV2AccessTest.php](../../tests/Feature/RagGateV2AccessTest.php); [tests/Unit/](../../tests/Unit/); [tests/frontend/](../../tests/frontend/); [services/mati-ai/tests/](../../services/mati-ai/tests/)

**Valor y límite de la evidencia:** Existencia y contenido inspeccionados. Un test presente o mock no demuestra ejecución ni comportamiento en PostgreSQL productivo.

### EV-34 — Auditorías históricas

**Origen:** [DOCUMENTACION/IA/AUDITORIA_FINAL/01_resultados_validacion.md](../../DOCUMENTACION/IA/AUDITORIA_FINAL/01_resultados_validacion.md); [DOCUMENTACION/48_auditoria_integral_panel_vendedor.md](../../DOCUMENTACION/48_auditoria_integral_panel_vendedor.md); [DOCUMENTACION/28_auditoria_flujo_comercial.md](../../DOCUMENTACION/28_auditoria_flujo_comercial.md)

**Valor y límite de la evidencia:** Conservar fecha y alcance: bloqueo IA del 04/10 fue parcialmente corregido después; auditoría vendedor registra fallo del entorno de test. N130 no es holdout independiente.

### EV-35 — Diseños de evolución y reglas actuales

**Origen:** [DOCUMENTACION/IA/V2_ARQUITECTURA_AMPLIADA/](../../DOCUMENTACION/IA/V2_ARQUITECTURA_AMPLIADA/); [app/Services/ComplementaryProductService.php](../../app/Services/ComplementaryProductService.php); [services/mati-ai/src/mati_ai/compatibility/engine.py](../../services/mati-ai/src/mati_ai/compatibility/engine.py)

**Valor y límite de la evidencia:** Complementos por reglas y pares cromáticos existen; motores completos de outfits, perfiles, tendencias, asistente, try-on no se demuestran por esos diseños.

### EV-36 — Dependencias y despliegue

**Origen:** [composer.json](../../composer.json); [composer.lock](../../composer.lock); [package.json](../../package.json); [compose.yaml](../../compose.yaml); [compose.ai-gpu.yaml](../../compose.ai-gpu.yaml); [services/mati-ai/pyproject.toml](../../services/mati-ai/pyproject.toml)

**Valor y límite de la evidencia:** Laravel 13 declarado; React/Inertia/TypeScript; PostgreSQL con pgvector; Redis; FastAPI opcional privado y override GPU. Reproducibilidad local no certifica servidor productivo.

## Qué falta para una aceptación defendible

Matriz de permisos/archivos por actor; conciliación de operaciones-stock-dinero y estados; pruebas de reservas con anticipos ante rechazo/vencimiento; definiciones consistentes de indicadores; recuperación aislada integral; pruebas por dispositivo/usuario y entorno final; dataset independiente por tarea IA; aprobación por ámbito/consumidor cuando corresponda.

Los tests ya existentes se vinculan en [06](06_TRAZABILIDAD.md), sin ejecutarlos en este trabajo. Resultados pendientes se conservan como pendientes y no se convierten en porcentajes estimados para cerrar el informe.
