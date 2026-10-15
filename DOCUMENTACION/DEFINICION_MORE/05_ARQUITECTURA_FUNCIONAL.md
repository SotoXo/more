# MORE — Arquitectura funcional y técnica textual

Corte: 12/10/2026. Este documento explica responsabilidades, contratos y límites. No contiene representaciones gráficas. Evidencias EV se localizan en [04](04_ESTADO_IMPLEMENTACION.md); alcance y procesos en [01](01_DEFINICION_INTEGRAL.md).

## Responsabilidades y componentes

La interfaz React/TypeScript integrada mediante Inertia presenta páginas públicas, cuenta, vendedor y superadministración. Solicita operaciones web a Laravel, muestra errores/estados y recoge decisiones humanas. Los campos, roles y precios de UI no son autoridad suficiente para escribir.

Laravel coordina sesión, CSRF, autorización, validación, persistencia comercial y transacciones. Los controladores delegan en servicios de catálogo, inventario, ventas/reservas/pagos, recojo, reportes, daños y trazabilidad. ResourceDefinitionService decide qué recursos son CRUD, secundarios, lectura o not_ready: una ruta genérica no vuelve operativa cualquier pantalla.

PostgreSQL almacena relaciones comerciales y evidencia IA; pgvector permite vectores con identidad/versionado. Redis es servicio auxiliar disponible en Compose; su uso exacto depende de configuración, y no sustituye la fuente de stock/precio. Archivos de producto/voucher, medios preparados privados, backups y pesos tienen ciclos de vida distintos de filas DB.

FastAPI/Python recibe peticiones internas autenticadas de inferencia/identificación/medios. Usa modelos locales y algoritmos deterministas. No tiene autoridad para descontar stock, fijar precio, crear venta ni confirmar pago. Los experimentos se encuentran en scripts/artefactos separados; presencia de código experimental no habilita un endpoint productivo.

La configuración declarada incluye Laravel 13, React/Inertia, PHP compatible según Composer y runtime Sail 8.5, PostgreSQL/pgvector y Python con PyTorch/OpenCV/EasyOCR. EV-31 documenta versiones efectivas locales el 11/10; no se verificó ahora la imagen en ejecución ni el servidor final. Compose con perfil ai no equivale a IA siempre disponible ni a despliegue productivo; el archivo base no publica puerto externo de mati-ai, y el override GPU define recursos específicos (EV-36).

## Entrada, autorización y escritura comercial

Las APIs de carrito y asistencia aparecen en routes/web.php y utilizan el contexto web. No se encontró routes/api.php separado ni un conjunto app/Policies. La autorización usa EnsureSuperadmin, EnsureVendedor, guards de checkout, titularidad y comprobaciones dentro de servicios. Superadmin puede entrar al panel vendedor; vendedor no entra a superadmin. No hay permisos granulares asignables automáticamente por fila de rol.

Para altas comerciales cubiertas, CommercialOperation vincula operation_id UUID con usuario, ruta y hash canónico del payload, incluidos archivos. El claim y la operación comparten transacción; intento equivalente ya confirmado redirige, distinto falla, validación fallida revierte el claim. Su lista PATHS es explícita; no protege por ese mecanismo cada modificación de pago, entrega o daño.

Servicios comerciales releen filas bajo lockForUpdate para precios/actividad/stock pertinentes y usan aritmética según origen. InventarioMovimiento conserva antes/después, tipo, cantidad, actor y referencia. Observers conservan cambios de modelos seleccionados, mientras SystemLogService registra eventos. Esos mecanismos no acreditan todos los interleavings de concurrencia ni inmutabilidad de historial.

Pago, venta/reserva y logística tienen estados propios. Transacción DB no es transacción con banco. Voucher es evidencia aportada y almacenamiento de archivo, no autorización bancaria. Fallo de DB debe evitar operación parcial; compensación de archivos depende de servicio concreto y necesita pruebas por flujo. No asumir que todo archivo y todo aviso participa del rollback DB.

## Persistencia comercial y recursos

Prenda contiene ficha y relaciones de categoría/marca/temporada/atributos. PrendaVariante contiene alternativa, precio/código/estado y relación a inventario único. D/R representan cantidades agregadas; no se almacena una entidad por ejemplar físico.

Venta/DetalleVenta/VentaPago y Reserva/DetalleReserva/ReservaPago son agregados separados. Una reserva convertida se relaciona por número determinista VEN-RES-<nre_res>; no hay FK directa reserva_id en ventas. Recibo derivado obtiene pagos de la reserva; copiar anticipo a venta_pagos sin nuevo ingreso causaría ambigüedad. La atribución y reconciliación histórica requieren revisar datos cuando se autorice.

Carrito de compras reside en localStorage y bolsa de reservas en sesión. Son selecciones preliminares, no inventario reservado. El servidor recibe IDs/cantidades y recalcula realidad comercial. Favoritos se almacenan por usuario/prenda; historial/recibos controlan propietario. Los archivos de voucher se guardan en public en flujos leídos; autorización de ruta de cuenta no garantiza privacidad de una URL de archivo conocida. DP-09 propone resolverlo antes de producción.

Backup ejecuta pg_dump y conserva historial/descarga privada controlada. No se encontró restauración web ni ensayo integral actual. Un dump no incluye por sí solo imágenes, pesos locales o secretos necesarios; recuperación y retención se acuerdan en DP-10.

## Contrato Laravel–Python

MatiAiClient usa rutas internas /api/v1 para capabilities, garments/analyze, garments/embedding, garments/compatibility, identification/barcode, identification/label, products/match, external-search/external-image y images/prepare-publication. RAG textual tiene su servicio/contrato específico, no es una operación generativa añadida a MatiAiClient por suposición.

Se envían Bearer del servicio, X-Request-Id, X-Contract-Version=1 y request_id en payload. Inputs de imagen son multipart; catálogos y listas se serializan de forma controlada. Laravel entrega categorías/colores/tallas/marcas/patrones comerciales vigentes; Python no crea nuevos IDs de catálogo desde etiquetas del modelo.

El envelope incluye request_id, contract_version, status, data, warnings, abstentions, identidad de modelo/pipeline, timing_ms y human_review_required. Estados posibles del contrato Python: completed, partial, insufficient_evidence, degraded y failed; el cliente acepta únicamente los estados/estructuras definidos para éxito utilizable y degrada respuestas inválidas. HTTP 200 no acredita inferencia exitosa ni aceptación comercial.

El cliente compara UUID/versión/estructura y payload requerido por operación. No sigue redirects. Timeout, token ausente, servicio deshabilitado o respuesta inválida producen un código seguro y continuidad manual, sin exponer cuerpos HTML o secretos. Configuración actual de timeout es 30s total y 3s de conexión; no son objetivos de rendimiento aprobados. Límite Laravel de imagen 6144KiB y límites FastAPI de archivo/píxeles deben documentarse por ruta; el máximo anunciado de un servicio no amplía el límite de la entrada Laravel (EV-22/23).

## Ciclo funcional de la asistencia visual

1. Superadmin sube imagen o selecciona imagen existente permitida. Laravel valida archivo/referencia, usuario y contexto del catálogo.
2. Python valida bytes, formato y píxeles, orienta EXIF y prepara copia de análisis. Original y hash no se sustituyen; heurística de calidad aporta advertencias.
3. Algoritmos de código/QR pueden recuperar identificadores; SigLIP compara imagen y prompts para categorías y produce embedding visual. Colorimetría estima paleta del foreground geométrico; no identifica por sí misma la prenda objetivo.
4. Se devuelve resultado estructurado con versiones, timings, candidatos, abstenciones y errores. Laravel vuelve a validar contrato/IDs y aplica guard de color.
5. AiReviewService conserva run/model version/input hash/metadata/result/status/duración/error y sugerencias aplicables. La persistencia necesita esquema disponible; un run no es una ficha confirmada.
6. UI presenta propuestas y permite decisión explícita. Campos abstencionistas permanecen informativos; valores comerciales actuales/manuales no se sobreescriben indiscriminadamente.
7. Feedback se vincula a propietario/run/sugerencia/variante; la decisión y el valor finalmente guardado se distinguen. Guardado comercial sigue ProductCatalogService y sus validaciones/transacción.
8. El registro de auditoría IA tiene tratamiento seguro para no destruir una escritura manual válida por fallo de evidencia asistiva. Los tests de savepoint/workflow son evidencia de diseño/pruebas, no certificación universal actual.

La inferencia del 11/10 documenta google/siglip-base-patch16-224, revisión 7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed, pipeline registration-1.2.0, embedding 768 L2 y resultado partial con abstención de categoría y color. No se repitió aquí. loaded=false en capabilities precede a esa inferencia; no demuestra que nunca se cargó.

## Entradas, modelos y salidas por capacidad

| Capacidad | Entrada / componente | Modelo o mecanismo | Salida y decisión |
| --- | --- | --- | --- |
| Preprocesamiento | Imagen y límites; vision/validation | Validación, orientación y transformación deterministas | Metadatos/imagen de análisis; no identidad ni GT |
| Clasificación | Imagen FULL y categorías activas | SigLIP preentrenado zero-shot y prompts versionados | Top-3, coseno/margen, abstención; aceptación humana |
| Atributos | Imagen y catálogos soportados | Prompts/predicción existente, por atributo | Hipótesis aplicables; no composición/talla certificadas |
| Color | Píxeles foreground geométrico y swatches | Paleta, CIELAB/CIEDE2000 y reglas | Color observado; ID comercial bloqueado si target no verificado |
| Embedding visual | Imagen y modelo/preprocess exactos | SigLIP proyectado/L2 | Vector con identidad; sirve para retrieval compatible |
| Similares | Vector visual y galería canónica | Coseno exacto pgvector | Ranking, no identidad exacta ni preferencia |
| Identificación interna | SKU/GTIN/OCR/model_code y catálogo | Normalización/checksum/matching y candidatos | Exacto sustentado, conflicto o candidatos; humano confirma correspondencia |
| OCR | Foto de etiqueta y pesos locales | EasyOCR CPU y parser/normalización | Texto crudo/regiones/candidatos, partial si falta OCR |
| Código/QR | Foto de simbología | Decodificadores compatibles | Valor/formato/región, sin autoridad para compra/URL |
| Compatibilidad de pares | Dos perfiles de color y patrón opcional | Motor de armonías explícitas | Score heurístico, explicación o insuficiencia; no outfit completo |
| Complementos | Producto/categoría y existencias | ComplementaryProductService, reglas de catálogo/temporada/color | Productos disponibles; baseline no aprendido |
| Embedding textual | Query/passage y perfil aprobado | multilingual-e5-small CPU, 384 L2 | Vector textual con prefijos y hashes; distinto de espacio visual |
| RAG | Query/corpus/profile y gate exactos | Retriever/router/gate V2 | Hoy insufficient_evidence sin chunks/generated_answer |
| Fashion Ontology | Definiciones/IDs/mapping/evidencias | Recurso formal y validadores, borrador V1.1.0 | Contratos propuestos, no modelo aprobado |
| Detección/segmentación | Imagen, candidatos y protocolo experimental | Experimentos DINO/SAM y política separada | Candidatos/máscaras privadas; NONE, sin consumidor integrado |
| Enriquecimiento externo | Identidad/query y fuentes permitidas | Adaptadores manual/HTML y safe fetch | Referencias con origen/derechos; fuentes vacías actualmente |
| Recomendación/outfits/tallas | Productos, contexto, perfiles/medidas y datos por aprobar | Motores futuros diferenciados | Diseños; ningún modelo completo acreditado |
| Try-on | Prenda/persona y consentimiento | Síntesis futura por modelo aprobado | Apariencia sintética; no ajuste real |
| Tendencias / ML de ventas | Observaciones fechadas/historial y variable objetivo | Diseños de análisis/evaluación | Sin predictor productivo acreditado |
| Descripciones / consultas naturales | Datos/evidencias y herramientas permitidas | Plantilla/LLM o intents futuros por aprobar | Borrador/respuesta/abstención; sin SQL libre ni escritura comercial |

## Embeddings, identidad y evidencia

CatalogEmbeddingStore valida forma, dimensión, finitud, revisión inmutable y perfil; prepara normalización L2 de vectores válidos y rechaza vectores nulos/no finitos o identidad incompatible. No basta compartir longitud. Los históricos sin geometría explícita se separan en perfil legacy; no se asume compatibilidad con endpoint posterior.

ai_product_embeddings conserva vector y metadatos; su migración usa NOT NULL, dimensiones 1–16000 y control de vector_dims/norma L2. La búsqueda actual es exacta; no hay implementación acreditada de ANN HNSW/IVFFlat. Cambiar FULL por crop/masked modificaría espacio/preprocesamiento y exigiría evaluación/reindexación autorizada, no reutilizar automáticamente los canónicos.

RagDocument/Chunk/Embedding/ModelVersion separan corpus, fuente, contenido/hash y modelo textual; migración enlaza dimensión/modelo y controla vectores. Perfil aprobado E5 no autoriza retrieval. Gate decision V2 conserva approved=false, NO_PROMOTION y contexto/digests exactos. Un request no puede aprobarlo. El flujo actual comprueba aprobación antes de recuperar: no genera respuestas ni ejecuta herramientas comerciales.

## Publicación de medios y fuentes externas

AiPublicationService prepara original y derivados privados con SHA/dimensiones/contador, run y propietario. Adjuntar al producto requiere token vigente, aprobación y derechos permitidos/evidencia; TTL configurado 24h y un uso. Publicar significa vincular medios autorizados durante guardado, no mostrar un candidato externo.

EnrichmentService solo utiliza fuentes activas con términos aprobados/revisados. SafeFetcher controla URL, DNS/redirecciones, tamaños y política del proveedor. Configuración vacía devuelve NO_EXTERNAL_SOURCES_CONFIGURED; no se presenta como extracción fallida de un proveedor inexistente ni se activa una fuente de ejemplo. Exacto interno puede evitar búsqueda externa. Cambios de proveedores/derechos se aprueban en DP-11.

## Dependencias y tratamiento de incertidumbre

Registro asistido depende de imagen válida, catálogo y contrato; clasificación/embedding no requieren segmentación semántica promovida para funcionar en FULL. Color comercial requiere identidad explícita; el guard mantiene incertidumbre actual. Similares requiere galería compatible; matching conserva uso de código/texto si faltan vectores. OCR unavailable no bloquea registro manual.

Recomendación aprendida requiere datos/targets y comparación con baseline; outfits requieren además producto fiable, contexto, zonas/capas y evaluación humana. Ontología revisada puede ayudar, pero sus contratos no entrenan modelos ni autorizan familias automáticamente. Talla necesita medidas y verdad de ajuste distintas de una foto; try-on necesita otros datos y no valida talla. Trend Intelligence necesita procedencia, fecha y región; dashboard descriptivo no es su sustituto.

Diseños de RAG/LLM deben separar fuentes estáticas y herramientas para precio/stock actual, con autorización por actor y abstención. Hoy el router que indica requires_tool no acredita herramienta ni SQL ejecutado, y el gate público sigue cerrado. Ningún modelo puede resolver falta de dato mediante invención.

## Arquitectura objetivo condicionada

Antes de añadir servicios, cerrar decisiones de dinero/stock/permisos/privacidad, reconciliar indicadores y preparar recuperación. Después consolidar asistencia visual por tarea y ámbitos ontológicos evaluables; desarrollar recomendación/outfits para objetivos mantenidos, con gates y datos independientes. RAG, segmentación y experiencias avanzadas siguen experimentales hasta evidencia y aprobación de consumidor específicas.

No se proponen cambios automáticos de DB, nueva infraestructura, entrenamiento o activación por esta documentación. [07](07_AUP_PLANIFICACION.md) ordena el trabajo futuro y [09](09_DECISIONES_PENDIENTES.md) conserva las decisiones humanas.
