# Arquitectura inteligente de Mati Boutique

Fecha: 2026-08-03  
Estado: diseño propuesto; requiere aprobación antes de implementar.

## 1. Principios arquitectónicos

1. **Laravel conserva la autoridad comercial.** Autentica, autoriza, valida opciones vigentes, decide qué se muestra, recibe la confirmación humana y persiste el resultado definitivo.
2. **FastAPI calcula; no decide.** Decodifica imágenes, ejecuta algoritmos/modelos y devuelve sugerencias con evidencia, confianza, versión y advertencias.
3. **El cliente web nunca llama a FastAPI.** React/Inertia se comunica solo con Laravel.
4. **Una caída de IA no bloquea el negocio.** El administrador debe poder continuar manualmente; la IA se presenta como capacidad degradable.
5. **Predicción y verdad se almacenan por separado.** Nunca se sobrescribe silenciosamente el dato confirmado con la salida de un modelo.
6. **Contratos antes que modelos.** El esquema de entrada/salida, seguridad, trazabilidad e idempotencia debe permanecer estable aunque cambie el checkpoint.
7. **Síncrono primero, asíncrono cuando el costo lo exija.** Se evita introducir dos sistemas de colas en el MVP.
8. **Medir antes de escalar.** Con el catálogo actual, índices o infraestructura complejos aportarían más carga operativa que valor.

## 2. Diagrama general

```text
Administrador
  → React/Inertia: formulario de prenda
  → Laravel: autenticación, autorización y prevalidación
  → MatiAiClient: contrato, token, request_id, timeout
  → FastAPI /api/v1/garments/analyze
  → validación segura de bytes y preprocesamiento
  → pipeline de calidad + color + modelo visual
  → resultado estructurado y versionado
  → Laravel: valida IDs contra catálogos vigentes
  → React: presenta sugerencias y advertencias
  → administrador acepta, corrige o ignora
  → Laravel: transacción comercial y feedback
  → PostgreSQL: dato confirmado + trazabilidad IA
```

Para una operación asíncrona:

```text
Laravel
  → crea ai_analysis_job (pending)
  → Laravel Queue (Redis o database)
  → Job llama a FastAPI con idempotency_key
  → FastAPI procesa y responde
  → Job persiste resultado/estado en PostgreSQL
  → notificación o polling desde React/Inertia
```

## 3. Componentes recomendados

| Componente | Responsabilidad | No debe hacer |
|---|---|---|
| React/Inertia | Captura de imagen, progreso, presentación de sugerencias, aceptación/corrección | Decidir confianza, llamar a FastAPI, escribir datos IA directamente |
| Controlador/acción Laravel | Autorizar, validar formulario, preparar contexto, traducir errores | Ejecutar PyTorch o confiar ciegamente en IDs devueltos |
| `MatiAiClient` Laravel | HTTP interno, token, request/idempotency IDs, timeouts, retries controlados, validación del contrato | Guardar inventario/venta o ocultar errores de contrato |
| Servicio de orquestación IA Laravel | Crear análisis, mapear taxonomía, aplicar decisión humana y guardar feedback | Mezclar predicción con campos confirmados sin acción humana |
| Laravel Queue | Operaciones largas/reintentables y notificaciones | Entrenar dentro del proceso web |
| FastAPI | API interna, validación Pydantic, pipelines, límites de concurrencia, observabilidad | Autorizar usuarios finales, acceder a estados comerciales o persistir ventas |
| Preprocesador | MIME real, decodificación, EXIF/orientación, límites, RGB, miniatura temporal | Conservar metadata sensible sin motivo |
| Registro/cargador de modelos | Checkpoint fijado, checksum, device, lazy load/warm-up, versión | Descargar versiones flotantes durante una solicitud |
| Pipeline visual | Calidad, color, embeddings, clasificación y advertencias | Convertir scores en verdad comercial |
| PostgreSQL | Datos comerciales y, bajo Laravel, resultados/feedback IA | Ser escrito directamente por FastAPI en la primera etapa |
| Redis | Cola/cache opcional y locks | Fuente de verdad de análisis o catálogo |

## 4. Límites de responsabilidad

### Laravel es dueño de

- sesión, usuario, rol y permisos;
- taxonomías y sus IDs vigentes;
- imágenes originales del catálogo y su ciclo de vida;
- formularios y validación definitiva;
- prendas, variantes, inventario, Kardex, ventas, reservas y pagos;
- disponibilidad, talla, precio, presupuesto y filtros comerciales;
- registro de análisis, decisión humana y feedback;
- idempotencia comercial y transacciones;
- notificaciones al usuario.

### FastAPI es dueño de

- validación técnica profunda de la imagen recibida;
- normalización visual y cálculo de métricas de calidad;
- extracción de paleta y mapeo sugerido contra colores proporcionados;
- carga/ejecución de modelos y embeddings;
- ranking matemático de candidatos suministrados o de un índice IA futuro;
- versionado del pipeline y formato de scores;
- telemetría de preproceso, inferencia, postproceso, GPU/CPU;
- errores técnicos estables.

### Responsabilidad compartida, con decisión final en Laravel

- Laravel envía la taxonomía/versiones; FastAPI puntúa; Laravel verifica que los IDs aún existan.
- FastAPI marca `warning`; Laravel decide la experiencia y si permite continuar manualmente.
- FastAPI calcula candidatos; Laravel vuelve a filtrar por estado, stock, permisos y precio.
- FastAPI recibe feedback normalizado; Laravel conserva el registro oficial.

## 5. Flujo del registro asistido

1. React selecciona una portada y muestra preview local.
2. Laravel valida autorización, tamaño de request, existencia de catálogos y forma básica del archivo.
3. Laravel calcula/genera `request_id`, `analysis_id` e `idempotency_key` y envía `multipart/form-data` a FastAPI con la imagen y contexto JSON.
4. FastAPI valida cabecera, MIME detectado por decodificación, píxeles máximos, dimensiones, frames y tiempo de proceso.
5. El preprocesador aplica orientación EXIF en memoria, convierte a RGB, elimina metadata, crea una copia normalizada y calcula hash.
6. El pipeline ejecuta solo las capacidades solicitadas y disponibles.
7. FastAPI devuelve sugerencias con `pipeline_version`, `model_version`, scores, advertencias y tiempos.
8. Laravel valida el esquema y descarta cualquier ID de categoría/color que no pertenezca a la taxonomía enviada.
9. React muestra top-k, paleta, calidad y posibles duplicados; no rellena campos silenciosamente.
10. El administrador acepta/corrige/ignora por atributo.
11. Laravel guarda el dato comercial normal y, en tablas IA separadas, la predicción y decisión humana.

Si FastAPI falla en los pasos 3–7, Laravel muestra “Análisis no disponible; continúa manualmente”, conserva la imagen/formulario y registra el fallo sin crear datos comerciales incompletos.

## 6. Transporte de imágenes

| Opción | Ventajas | Riesgos/costo | Uso recomendado |
|---|---|---|---|
| `multipart/form-data` Laravel→FastAPI | Funciona antes de persistir; no comparte filesystem; simple de asegurar | Duplica transferencia y memoria si no hay streaming | **Inicial** |
| ID de imagen | Payload pequeño y estable | FastAPI necesitaría API de descarga o acceso a storage | Análisis de imágenes ya guardadas |
| URL firmada | Desacopla servidores y permite streaming desde object storage | Requiere S3/compatible, expiración y SSRF controlado | Producción con GPU separada |
| Ruta de volumen compartido | Rápida en un único host | Acopla rutas/contenedores; riesgo de traversal y no escala a host remoto | Solo optimización local controlada, no contrato público |

Recomendación inicial: multipart con una sola imagen, máximo HTTP 6 MB para alinear el formulario actual y máximo de píxeles decodificados independiente. FastAPI no debe aceptar una URL arbitraria. En el futuro, Laravel emitirá una URL firmada de corta duración para un bucket permitido.

## 7. Sincronía y asincronía

### HTTP síncrono

Adecuado cuando la interacción requiere respuesta inmediata y el trabajo cabe en el timeout:

- health/capabilities;
- decodificación y validación;
- dimensiones, orientación, desenfoque, exposición y fondo;
- extracción de color en una imagen reducida;
- una clasificación zero-shot con un único modelo precargado;
- embedding y búsqueda sobre un catálogo pequeño;
- ranking de una lista limitada de candidatos.

Condiciones: modelo caliente, lote 1, una imagen, concurrencia limitada y fallback manual.

### Trabajo asíncrono

Debe usarse para:

- OCR de varias fotos/etiquetas;
- embeddings masivos y reindexación;
- análisis/recomendaciones de todo el catálogo;
- backfill después de cambiar de modelo;
- procesamiento por lotes;
- entrenamiento, evaluación de modelos y exportación de datasets;
- outfits combinatorios amplios;
- trabajos que superen el presupuesto de una interacción web.

### Tecnología de cola

Recomendación progresiva:

1. Usar Laravel Queue, que ya está soportada, con Redis si está operativamente disponible o `database` al inicio.
2. El Job de Laravel llama a FastAPI. Laravel conserva reintentos, estado, autorización y persistencia.
3. No introducir Celery en las primeras fases: crearía otro worker, otro modelo de retry y otra fuente de estado.
4. Evaluar Celery/RQ/Dramatiq solo si FastAPI debe orquestar pipelines Python autónomos, con múltiples consumidores GPU o tareas que no nacen en Laravel.
5. Para entrenamiento, preferir comandos/jobs offline versionados, no la cola de solicitudes de usuario.

## 8. Política HTTP y resiliencia

### Timeouts

- `connect`: 0.5–1 s en la misma red; hasta 2 s entre hosts.
- `read` de análisis rápido: presupuesto recomendado 8 s y límite duro inicial 12 s.
- health: 1 s; capabilities: 2 s; similares/recomendaciones: 3–5 s según contrato.
- el timeout de Laravel debe ser levemente mayor que el timeout interno del pipeline para recibir un error estructurado.
- ninguna solicitud síncrona debe entrenar, reindexar ni descargar un modelo.

Los valores son presupuestos iniciales; se ajustan con p95/p99 medidos, no como promesa previa.

### Reintentos

- `GET /health` y `GET /capabilities`: un retry corto con jitter.
- POST idempotente con `Idempotency-Key`: máximo un retry solo ante error de conexión, 502, 503 o 504 y si el presupuesto total lo permite.
- No reintentar 400, 401, 403, 413, 415, 422 ni errores permanentes de modelo.
- Jobs asíncronos: backoff exponencial con jitter, máximo definido y paso a `failed`/dead letter lógica.

### Circuit breaker equivalente

Laravel mantiene por endpoint un contador en cache:

```text
closed → fallos consecutivos/ratio en ventana supera umbral → open
open → no llama; ofrece flujo manual durante cooldown
half-open → permite pocas pruebas
éxito → closed; fallo → open
```

En el MVP basta una implementación explícita en cache y una bandera `ai_available`; no hace falta una plataforma de service mesh. El breaker nunca debe bloquear el guardado manual.

### Idempotencia

- Laravel genera UUID por operación lógica y lo envía en `Idempotency-Key`.
- FastAPI puede cachear temporalmente `key + hash_payload + response` y rechaza reutilizar la misma clave con otro payload.
- Laravel impone índice único en el registro oficial de análisis/feedback.
- Un retry devuelve el mismo `analysis_id` y no duplica resultados.

## 9. Errores y degradación

| Clase | Ejemplo | Conducta Laravel |
|---|---|---|
| Entrada inválida | MIME falso, imagen corrupta, demasiados píxeles | Mostrar motivo; no reintentar; permitir reemplazar archivo |
| No analizable | varias prendas, fondo dominante, confianza insuficiente | Mostrar advertencia y campos manuales |
| Servicio ocupado | semáforo GPU lleno | Respuesta 429/503 con `Retry-After`; ofrecer reintentar o continuar |
| Modelo no disponible | checkpoint ausente/error CUDA | Fallback CPU solo si cabe en SLO; si no, degradar capacidad |
| Timeout | inferencia supera presupuesto | Cancelar trabajo útil cuando sea posible y no aplicar respuesta tardía |
| Contrato incompatible | versión de respuesta desconocida | Rechazar, alertar y mantener flujo manual |
| FastAPI caído | conexión rechazada | Breaker + mensaje no bloqueante |

No debe existir fallback “silencioso” que cambie de modelo o precisión sin reportarlo en `degraded`, `device` y `model_version`.

## 10. Gestión de modelos y GPU

### Registro y versionado

Cada modelo desplegado tendrá:

- nombre lógico y tarea;
- ID exacto del artefacto/checkpoint y revisión/commit;
- SHA-256 del archivo o manifiesto;
- versión del preprocesador, prompts y taxonomía;
- dimensiones de embedding;
- device/dtype admitidos;
- fecha, estado (`candidate`, `active`, `retired`, `failed`);
- métricas offline y decisión de aprobación.

`model_version` no debe ser solo “CLIP”. Ejemplo: `siglip-base-p16-224@revision+prompts-v2+taxonomy-v3`.

### Descarga y arranque

- Descargar/verificar artefactos durante preparación de imagen/despliegue, nunca dentro de una petición productiva.
- Usar una ruta de cache explícita y de solo lectura en runtime.
- `GET /health` no debe cargar modelos.
- Carga perezosa protegida por lock en desarrollo; en producción, warm-up controlado al arrancar y readiness falsa hasta terminar.
- Si un modelo opcional no carga, `/capabilities` lo marca no disponible sin tumbar health general.

### Uso de 8 GB de VRAM

- Un worker de proceso con acceso GPU y semáforo inicial de una inferencia pesada concurrente.
- `torch.inference_mode()` y autocast FP16/BF16 solo tras validar estabilidad/calidad.
- No mantener CLIP, SigLIP, OCR pesado y un segmentador grande residentes simultáneamente.
- Modelos opcionales con política LRU/descarga explícita o procesos separados, midiendo fragmentación.
- Reducir imagen a la resolución esperada por el modelo; calidad/color se calculan sobre copias controladas.
- Limitar batch; los backfills usan lotes pequeños ajustados por benchmark.
- Antes/después de cada despliegue medir VRAM pico, latencia, temperatura/errores CUDA y estabilidad prolongada.

### Fallback a CPU

- Calidad, hashes, color y validaciones siempre deben funcionar en CPU.
- Clasificación base puede caer a CPU solo si el modelo está cargado y el timeout lo permite; en caso contrario devuelve capacidad degradada.
- OCR y backfill no deben saltar automáticamente a CPU si eso bloquea workers durante minutos; pasan a cola/retry.

## 11. Concurrencia y límites

- Límite HTTP inicial: 6 MB por imagen para coincidir con Laravel; revisar overhead multipart en proxy.
- Límite independiente de resolución: propuesto 25 megapíxeles como punto de partida sujeto a pruebas; rechazar antes de expandir buffers enormes.
- Formatos iniciales: JPEG, PNG y WebP estáticos decodificables. Rechazar SVG, PDF, GIF animado y archivos con múltiples frames.
- Dimensión mínima sugerida para análisis: 384 px en el lado corto; no bloquear guardado manual si el negocio acepta menor resolución.
- Una imagen por análisis síncrono; galería/OCR pasan a batch asíncrono.
- Semáforos separados para CPU y GPU, cola de espera corta y `Retry-After`.
- Uvicorn: un worker GPU. Escalar procesos CPU por separado o replicar servicio solo tras medir.

## 12. Cache

Clave recomendada:

```text
sha256(image_bytes)
+ pipeline_version
+ model_version
+ taxonomy_version
+ requested_features
```

- Cachear respuestas deterministas por un período corto en Redis o memoria controlada.
- No cachear sin incluir taxonomía/prompts/versiones.
- El resultado oficial permanece en PostgreSQL bajo Laravel.
- Invalidar embeddings cuando cambia imagen, preprocesador o modelo.
- No usar cache como registro de auditoría.

## 13. Seguridad

### Autenticación de servicio

- `Authorization: Bearer <token interno aleatorio de alta entropía>` en todas las rutas `/api/v1/*` excepto liveness mínima.
- Token distinto por entorno, en secret manager/.env durante implementación, con rotación y soporte temporal de token actual/anterior.
- Comparación constante; no registrar token ni cabecera Authorization.
- Restricción de red: FastAPI sin puerto público; solo red Docker/host Laravel o firewall allowlist.
- En hosts separados: TLS obligatorio; mTLS es evolución recomendable si la operación lo soporta.
- `X-Request-ID`, `X-Analysis-ID`, `Idempotency-Key` y, opcionalmente, timestamp/HMAC para defensa adicional contra replay entre hosts.

### Archivos

- No confiar en extensión ni `Content-Type` enviado.
- Leer una cabecera limitada, identificar formato, decodificar con librería y verificar dimensiones/frames.
- Nombres aleatorios/UUID; nunca usar el nombre original como ruta.
- Normalizar ruta y prohibir traversal/symlinks si existe volumen compartido.
- Aplicar orientación y re-encode temporal para eliminar EXIF/GPS/ICC innecesario.
- Directorio temporal privado, permisos mínimos, cuota, borrado en `finally` y recolector de huérfanos.
- No enviar comprobantes de pago ni imágenes de daños al pipeline de prendas.
- Proteger contra decompression bombs con límites de píxeles/memoria/tiempo.
- Rate limit por identidad de servicio y endpoint; 429 con retry explícito.
- CORS no habilitado para orígenes públicos.

### Privacidad

- Minimizar imágenes con personas; no realizar reconocimiento facial, edad, etnia ni otros atributos personales.
- Logs contienen IDs/hashes, nunca bytes, paths firmados completos ni datos personales.
- Definir retención: temporal de entrada en FastAPI en minutos; original bajo política Laravel; resultados según auditoría/dataset.
- Dataset de entrenamiento usa copias autorizadas y pseudonimiza `validated_by`.
- Borrado de una prenda debe propagar invalidación de embedding/muestra según política aprobada.

## 14. Observabilidad

### Campos comunes

`timestamp`, `level`, `service`, `environment`, `request_id`, `analysis_id`, `endpoint`, `status_code`, `duration_ms`, `error_code`, `contract_version`.

### Laravel registra

- usuario/rol interno autorizado (ID, no datos personales en logs técnicos);
- ruta/acción origen y prenda/imagen si ya existen;
- timeout, retry, breaker y estado del job;
- versión de contrato enviada/recibida;
- sugerencias mostradas, aceptadas/corregidas/ignoradas en tablas IA;
- resultado de validación final y enlace a auditoría, no los tensores.

### FastAPI registra/mide

- `model_name`, `model_version`, `pipeline_version`, `taxonomy_version`;
- tamaño de bytes, dimensiones y formato detectado;
- `preprocess_ms`, `inference_ms`, `postprocess_ms`, `total_ms`;
- dispositivo, dtype, VRAM asignada/reservada/pico, fallback CPU;
- longitud de cola/semaphore y cache hit;
- confianza/scores agregados sin imágenes ni prompts sensibles;
- excepciones sanitizadas y código estable.

### Métricas y alertas

- tasa 2xx/4xx/5xx, timeout y rechazo por motivo;
- p50/p95/p99 por endpoint/modelo/device;
- disponibilidad y readiness;
- errores CUDA/OOM, VRAM y fallback;
- porcentaje de análisis no concluyentes;
- aceptación/corrección por atributo y versión;
- drift de categorías, colores y confianza;
- backlog/edad de jobs y dead letters.

Logs estructurados JSON a stdout en FastAPI; Laravel usa sus canales existentes. La correlación se realiza con los mismos IDs, sin acoplar formatos internos.

## 15. Despliegue por entorno

### 15.1 Desarrollo local

```text
React/Vite opcional → Laravel local/Sail → http://127.0.0.1:<puerto> → FastAPI en WSL
                                                 └→ RTX 5060 o CPU mediante configuración
```

- Token de desarrollo; no exponer FastAPI a la LAN.
- Modelos en cache local fijada.
- Se permite lazy load y dataset pequeño.
- Tests usan un fake HTTP; GPU solo en suite marcada.
- No se debe requerir `npm run dev` para pruebas backend/contrato.

### 15.2 Docker local

Estado actual: `compose.yaml` contiene `laravel.test`, `pgsql` y `redis`, no `mati-ai`. El cambio se diseñará en IA-1 pero requiere aprobación separada.

```text
red sail: laravel.test → mati-ai (red interna, sin publicar) → GPU NVIDIA runtime
                      ↘ pgsql / redis
```

- Volumen de modelos de solo lectura y volumen temporal separado.
- Healthcheck sin información sensible.
- GPU reservada solo al contenedor IA.
- Laravel no comparte socket Docker con FastAPI.

### 15.3 Producción en un solo servidor

```text
Reverse proxy → Laravel público
                 → FastAPI privado en loopback/red interna
                 → PostgreSQL/Redis
                 → GPU local
```

- Un worker GPU, límites de recursos, artefactos precargados.
- Proxy limita cuerpo/tiempo y FastAPI no tiene DNS/puerto público.
- El almacenamiento original sigue bajo Laravel; multipart interno en MVP.
- Supervisión independiente para reiniciar FastAPI sin reiniciar Laravel.

### 15.4 Producción con GPU en otro servidor

```text
Servidor aplicación                         Servidor GPU
React → Laravel → objeto/URL firmada ─TLS→ FastAPI privado
        PostgreSQL/Redis                    cache de modelos + GPU
```

- VPN/VPC/allowlist, TLS y preferiblemente mTLS.
- URL firmada solo a bucket/domain permitido, corta y de una lectura; no aceptar URLs de usuario.
- Timeouts mayores de conexión, mismos límites de cómputo y breaker.
- FastAPI no accede a PostgreSQL comercial al inicio.
- Para índices grandes futuros: réplica/read-only o esquema IA dedicado, aprobado y con privilegios mínimos.

## 16. Evolución de acceso a datos

### Inicial

FastAPI no posee credenciales PostgreSQL. Laravel envía taxonomías/candidatos/contexto mínimo por API y escribe todos los resultados. Es la opción más segura, comprobable y coherente con el volumen actual.

### Futura

Si el catálogo vectorial supera el costo razonable de transportar candidatos, se puede habilitar a FastAPI lectura exclusiva sobre un esquema/vista IA (`ai_embeddings`, IDs y atributos no sensibles) o una réplica. Nunca sobre pagos, usuarios, inventario mutable ni estados comerciales. Laravel sigue revalidando disponibilidad y persiste toda decisión.

## 17. Decisión arquitectónica central

La arquitectura recomendada es una integración HTTP interna síncrona para el MVP, con multipart, token de servicio, un worker GPU y fallback manual. Laravel Queue se agrega para backfills/OCR/reindexación. FastAPI permanece stateless respecto al negocio y sin base de datos. Esta forma permite validar valor con poco acoplamiento y evolucionar a object storage, pgvector o servidor GPU separado sin romper la interfaz de usuario ni transferir autoridad comercial.
