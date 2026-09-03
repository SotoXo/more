# Decisiones pendientes de aprobación

Fecha: 2026-08-03  
Regla: ninguna opción marcada “recomendada” se considera autorizada por este documento.

| Decisión | Recomendación | Alternativas | Riesgo | Necesita aprobación |
|---|---|---|---|---|
| Autoridad comercial | Laravel sigue siendo único escritor y validador de catálogo, usuarios, stock, Kardex, ventas, reservas y pagos | Compartir escritura con FastAPI | Compartirla crea transacciones distribuidas y datos inconsistentes. Posponer esta confirmación bloquea todo contrato | Sí |
| Acceso DB inicial de FastAPI | Sin credenciales PostgreSQL; recibe contexto por API | Solo lectura; lectura/escritura; réplica | Más acceso aumenta superficie y acoplamiento. Posponer obliga a diseñar dos caminos | Sí |
| Acceso DB futuro | Vista/esquema IA de solo lectura o réplica únicamente cuando el volumen lo justifique | Mantener siempre API; acceso a DB comercial | Puede filtrar datos o leer estados obsoletos. Puede posponerse hasta IA-6 sin costo | Sí, antes de IA-6 |
| Transporte inicial de imagen | `multipart/form-data` Laravel→FastAPI, 1 imagen ≤6 MB | ID de imagen; URL firmada; volumen compartido | Multipart consume red/memoria pero evita acoplamiento. Posponer impide analizar antes de guardar | Sí |
| Transporte con GPU remota | Object storage + URL firmada corta, host allowlist y TLS | Multipart entre hosts; volumen de red | URL mal validada habilita SSRF/fuga. Puede posponerse mientras ambos servicios estén juntos | Sí, al separar hosts |
| Modelo inicial | `google/siglip-base-patch16-224` fijado a revisión, tras benchmark | CLIP ViT-B/32; otro SigLIP; Torchvision supervisado | SigLIP puede no ganar en dominio y sus scores no están calibrados. Posponer bloquea IA-4, no IA-1/3 | Sí |
| Baseline de modelo | CLIP ViT-B/32 obligatorio como comparación offline | Solo SigLIP; ResNet sin dataset | Sin baseline no se puede justificar elección. Posponer debilita aceptación del MVP | Sí |
| Idioma/prompts | Conjunto versionado con prompts inglés/español y aliases por categoría | Solo español; solo inglés; label directo | Categorías compuestas/idioma degradan ranking. Puede iterarse, pero v1 debe congelarse | Sí |
| Taxonomía de categorías | Conjunto cerrado, versionado, alias y abstención; sanear categorías fuera del seeder | Usar nombres actuales sin curación; taxonomía externa amplia | Duplicados/ambigüedad contaminan labels. Posponer invalida métricas y feedback | Sí, crítica |
| Vocabulario del MVP | Categoría top-3 y color; tags solo si vocabulario pequeño supera baseline | Incluir estilo/material/temporada desde inicio | Atributos débiles reducen confianza y contaminan datos. Posponer tags no afecta valor central | Sí |
| Atributos excluidos | No inferir talla, marca, material, composición, ocasión ni temporada como verdad desde portada | Añadirlos como sugerencias de baja confianza | Riesgo de afirmaciones falsas. Posponer su inclusión es favorable | Sí |
| Algoritmo de color | ROI heurístico + K-Means LAB + ΔE a catálogo; selector manual como fallback | K-Means RGB/HSV; segmentador aprendido | Fondo/sombra puede dominar. Posponer impide IA-3; segmentación puede esperar | Sí |
| Duplicados del MVP | SHA-256/dHash y candidato visual; nunca bloqueo automático | Solo hash; bloqueo por embedding | Falsos positivos pueden impedir altas válidas. Posponer near-duplicate es aceptable | Sí |
| Contrato de confianza | Score type + top-k + margen + abstención; probabilidad calibrada nullable | Mostrar porcentaje directo | “Porcentaje” no calibrado induce confianza falsa. No debe posponerse | Sí |
| Comunicación inicial | HTTP interno síncrono para análisis de una portada | Cola desde el primer análisis; gRPC | Síncrono puede aumentar latencia, mitigado con timeout/fallback. Posponer elección bloquea IA-2 | Sí |
| Operaciones asíncronas | Laravel Queue→FastAPI para OCR/backfill/reindexación | Celery/RQ/Dramatiq; cron directo | Dos colas complican retries/estado. Puede posponerse hasta primer batch | Sí, antes de IA-6/8 |
| Backend de Laravel Queue | Redis si operación lo soporta; `database` para arranque controlado | Celery broker; sync | Configuración incorrecta pierde/repite jobs. Puede decidirse al introducir jobs | Sí |
| Autenticación interna | Bearer de alta entropía, rotación, red privada; TLS/mTLS cuando remoto | IP allowlist sola; HMAC/mTLS desde inicio | Token expuesto permite abuso. Posponer deja endpoint sin protección | Sí, crítica |
| Exposición de FastAPI | Sin acceso público ni llamada desde React | Publicar detrás de proxy | Bypass de autorización/rate limits. No debe posponerse | Sí, crítica |
| Timeouts/reintentos | Connect 0.5–1 s local, presupuesto análisis 8 s/hard 12 s; máximo 1 retry idempotente de transporte | Timeouts mayores; sin retry; retries amplios | Muy corto falla; largo bloquea; retry duplica sin clave. Ajustable tras baseline | Sí |
| Circuit breaker | Estado en cache Laravel y fallback manual | Sin breaker; service mesh | Cascada de timeouts si FastAPI cae. Puede empezar simple, no omitirse en integración | Sí |
| Concurrencia GPU | Un worker GPU y semáforo 1 inicialmente; ajustar por benchmark | Varios workers; sin límite | OOM/fragmentación/latencia. Posponer tuning es correcto; límite inicial sí es necesario | Sí |
| Fallback CPU | Calidad/color siempre CPU; clasificación CPU solo si cumple presupuesto y se reporta | Fallar todo; CPU silencioso | CPU puede saturar/ocultar degradación. Posponer benchmark no impide error explícito | Sí |
| Artefactos de modelos | Precargados, revisión+checksum, sin descarga en request | Descargar de Hub al iniciar/primera petición | Internet/versiones flotantes rompen despliegue. No debe posponerse para producción | Sí |
| Tablas iniciales | `ai_model_versions`, `ai_analysis_results`, `ai_feedback`; jobs solo al ser async | Todas las tablas propuestas desde inicio; JSON en `prendas` | Sobrediseño o mezcla de verdad/predicción. Posponer aprobación bloquea IA-5, no IA-1/4 | Sí |
| Atributos detectados normalizados | Mantener `result_json` al inicio; no crear `ai_detected_attributes` aún | Tabla EAV desde MVP | EAV prematura aumenta complejidad. Posponer es recomendado | Sí |
| pgvector | Aprobar en IA-6; búsqueda exacta NumPy para prototipo y pgvector como persistencia | FAISS; memoria; vector DB externa | Extensión/índice requieren operación/tuning. Posponer no afecta MVP | Sí, antes de IA-6 |
| Tipo de índice pgvector | Exacto primero; HNSW/IVFFlat solo al medir escala/recall | HNSW desde inicio; IVFFlat desde inicio | ANN puede perder recall con filtros y pocos datos. Posponer ANN es recomendado | Sí |
| OCR | Benchmark Tesseract y EasyOCR; PaddleOCR solo si no alcanzan; foto de etiqueta separada | OCR en portada; TrOCR directo | Dependencias/VRAM y baja calidad de entrada. Puede posponerse a IA-8 | Sí |
| Registro de interacciones | No inventar visitas; antes de recomendaciones online registrar impresiones/clics con consentimiento | Usar favoritos/compras solamente; tracking amplio inmediato | Sin impresiones no hay CTR; tracking excesivo afecta privacidad. Puede posponerse hasta IA-9 | Sí |
| Baseline de recomendaciones | Popularidad+novedad+diversidad, luego contenido e híbrido | Collaborative filtering inmediato | Cold start/escasez hacen ML frágil. Posponer híbrido es recomendado | Sí |
| Roles de outfits | Taxonomía `top/bottom/outerwear/one_piece/accessory`; sin calzado mientras no exista | Inferir roles desde categoría al vuelo; incluir calzado externo | Combinaciones inválidas. Puede posponerse hasta IA-10 | Sí |
| Analítica predictiva | Descriptivo/reglas primero; naive/Croston con backtesting después | Modelo ML inmediato | Series cortas producen falsas predicciones. Posponer ML es recomendado | Sí |
| EOQ/reorden | Solo escenarios hasta registrar lead time y costos | Fórmula con supuestos ocultos | Decisiones de compra erróneas. Puede posponerse sin afectar analítica descriptiva | Sí |
| Dataset y split | Solo validación humana explícita; split por prenda; test congelado; dedupe | Aceptación implícita; split por imagen | Leakage/contaminación inflan métricas. No debe posponerse antes de feedback | Sí, crítica |
| Retención/privacidad | Temporal FastAPI en minutos; resultados 12–24 meses propuestos; eventos con política; EXIF eliminado | Retención indefinida; no guardar resultados | Riesgo legal/operativo o falta de auditoría. Aprobar antes de persistir | Sí |
| Uso de imágenes con personas | Permitidas solo para prenda, sin biometría; minimización y consentimiento/política | Prohibirlas; procesarlas sin restricción | Privacidad/sesgo. Debe aprobarse antes del piloto | Sí |
| Métricas de MVP | Línea base primero; top-k/macro-F1/color/latencia/VRAM/aceptación/error | Metas fijas ahora; solo exactitud | Metas arbitrarias o incompletas. No posponer definición, sí los umbrales | Sí |
| Criterio de promoción | Gate de contrato, seguridad, offline, operación y aceptación; canary/rollback futuro | Activar por terminar desarrollo | Modelo peor puede llegar a usuarios. Aprobar antes de IA-4/11 | Sí |
| RAG | Posponer; tools de solo lectura, autorización Laravel y citas si se aprueba | Chat con SQL libre; RAG documental como primer módulo | Alucinación/fuga/consulta costosa. Posponer no afecta roadmap de mayor valor | Sí, antes de IA-12 |
| Duplicidad de migraciones de temporada | Corregir en una tarea separada antes de nuevos entornos/migraciones IA | Dejarla; hacer segunda idempotente | `migrate:fresh` puede fallar. Posponer bloquea reproducibilidad, no este diseño | Sí, fuera de esta tarea |

## Ventaja concreta de cada recomendación

La columna **Riesgo** de la matriz anterior reúne tanto el riesgo de la opción como la consecuencia de posponerla. Esta tabla complementaria hace explícita la ventaja pedida para cada decisión sin alterar las cinco columnas obligatorias.

| Decisión | Ventaja de la recomendación |
|---|---|
| Autoridad comercial | Mantiene una sola fuente de verdad y conserva las invariantes transaccionales ya resueltas por Laravel. |
| Acceso DB inicial de FastAPI | Reduce privilegios, acoplamiento y posibilidades de modificar datos comerciales por error. |
| Acceso DB futuro | Permite escalar lecturas sin entregar control de escritura sobre el negocio. |
| Transporte inicial de imagen | Funciona antes de persistir la prenda y no exige storage compartido ni URLs públicas. |
| Transporte con GPU remota | Evita reenviar archivos grandes entre hosts y permite expiración y auditoría del acceso. |
| Modelo inicial | SigLIP ofrece zero-shot y embeddings con un artefacto viable para la GPU disponible. |
| Baseline de modelo | Convierte la elección de modelo en una comparación reproducible, no en preferencia técnica. |
| Idioma/prompts | Reduce sensibilidad a traducciones y hace reproducible la taxonomía usada por el modelo. |
| Taxonomía de categorías | Da clases evaluables, evita sinónimos duplicados y habilita abstención coherente. |
| Vocabulario del MVP | Concentra la validación en atributos que pueden aportar valor con los datos actuales. |
| Atributos excluidos | Evita presentar inferencias visualmente imposibles como hechos comerciales. |
| Algoritmo de color | LAB aproxima mejor la percepción humana y el catálogo existente aporta el vocabulario final. |
| Duplicados del MVP | Detecta copias exactas y candidatas sin impedir altas legítimas por un falso positivo. |
| Contrato de confianza | Distingue similitud, ranking y probabilidad calibrada para no engañar al administrador. |
| Comunicación inicial | Minimiza infraestructura y permite validar la experiencia interactiva de una imagen. |
| Operaciones asíncronas | Reutiliza el dueño natural del estado del trabajo y evita una segunda plataforma de colas. |
| Backend de Laravel Queue | Permite empezar con infraestructura existente y adoptar Redis cuando la operación lo requiera. |
| Autenticación interna | Limita el uso del servicio a Laravel y permite rotar el secreto sin cambiar contratos. |
| Exposición de FastAPI | Conserva autenticación, autorización y rate limiting en la frontera ya controlada por Laravel. |
| Timeouts/reintentos | Acota la espera del usuario y evita tormentas de reintentos o duplicación de análisis. |
| Circuit breaker | Protege a Laravel de fallos repetidos y conserva el alta manual cuando IA no está disponible. |
| Concurrencia GPU | Evita OOM inicialmente y proporciona una base medible para aumentar paralelismo. |
| Fallback CPU | Mantiene funciones baratas disponibles sin ocultar una degradación costosa de clasificación. |
| Artefactos de modelos | Hace los despliegues reproducibles e independientes de descargas externas durante una petición. |
| Tablas iniciales | Separa predicción, versión y corrección con el mínimo esquema auditable. |
| Atributos detectados normalizados | Mantiene flexibilidad del contrato mientras la forma real de los atributos todavía cambia. |
| pgvector | Aprovecha PostgreSQL y sus filtros comerciales sin operar una base vectorial separada. |
| Tipo de índice pgvector | La búsqueda exacta entrega una referencia de recall y evita tuning prematuro con pocos datos. |
| OCR | Compara una opción CPU madura y otra integrada con PyTorch antes de aceptar más complejidad. |
| Registro de interacciones | Obtiene señales interpretables con consentimiento antes de optimizar recomendaciones online. |
| Baseline de recomendaciones | Produce valor con cold start y crea una referencia contra la cual medir métodos más complejos. |
| Roles de outfits | Impide combinaciones estructuralmente inválidas y respeta la ausencia actual de calzado. |
| Analítica predictiva | Separa hechos y reglas de pronósticos inciertos hasta disponer de series suficientes. |
| EOQ/reorden | Evita convertir supuestos desconocidos de costo y lead time en órdenes de compra. |
| Dataset y split | Reduce fuga y contaminación, y vuelve creíbles las métricas de nuevas versiones. |
| Retención/privacidad | Equilibra auditoría, minimización de datos y eliminación verificable de temporales. |
| Uso de imágenes con personas | Permite analizar la prenda sin introducir reconocimiento biométrico ni usos secundarios. |
| Métricas de MVP | Establece primero evidencia local y evita comprometer metas sin línea base. |
| Criterio de promoción | Impide desplegar un modelo que mejora una métrica pero degrada seguridad u operación. |
| RAG | Reserva el mayor riesgo de autorización y alucinación para cuando exista un caso de valor medido. |
| Duplicidad de migraciones de temporada | Recupera reproducibilidad de nuevos entornos sin mezclar la corrección con esta fase documental. |

## Orden de aprobación recomendado

### Bloque A — necesario para IA-1/IA-2

1. Autoridad exclusiva de Laravel y FastAPI sin DB.
2. Contrato HTTP, multipart, token, red privada y fallback manual.
3. Límites/timeouts/idempotencia/circuit breaker.
4. Corrección separada de migraciones duplicadas para reproducibilidad.

### Bloque B — necesario para IA-3/IA-4

1. Taxonomía/alias y vocabulario excluido.
2. Algoritmo de color y política de duplicados.
3. SigLIP propuesto, CLIP baseline, prompts e interpretación de scores.
4. Artefactos, concurrencia GPU y fallback CPU.
5. Dataset de evaluación, métricas y criterio de promoción.

### Bloque C — necesario para IA-5

1. Tablas mínimas y separación predicción/verdad.
2. UI de acción explícita y elegibilidad de training sample.
3. Retención, EXIF, imágenes con personas y borrado.

### Bloque D — puede esperar

- pgvector/índice;
- OCR/dependencias;
- eventos/recomendaciones;
- roles de outfit;
- datos de demanda/EOQ;
- acceso read-only futuro;
- RAG.

## Recomendación de aprobación para el primer MVP

Aprobar el Bloque A, luego el Bloque B con `SigLIP base P16 224` como candidato provisional condicionado a benchmark, y finalmente el Bloque C. No aprobar aún pgvector, OCR, outfits, entrenamiento propio ni RAG. Esa secuencia conserva opciones abiertas y permite medir valor real antes de ampliar datos e infraestructura.
