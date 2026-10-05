# Embeddings persistentes y búsqueda vectorial de Mati Boutique

Fecha: 2026-10-03. Entrega de código sin ejecutar validación final, migraciones, indexación, builds, pruebas, lint, inferencias ni benchmarks. Sin commit ni push. Se preservaron los cambios locales de la fase de colorimetría.

## 1. Estado anterior investigado

`AiReviewService::persist()` extraía `data.embedding` del resultado y lo guardaba como JSON en `ai_runs.embedding`. `AiRun` lo convertía a array; `result` conservaba la respuesta sin ese vector. `ai_model_versions` registraba modelo, revisión y pipeline. El comando anterior generaba análisis completos para imágenes principales y guardaba estos JSON. `CatalogAiService::candidates()` recuperaba el último JSON por prenda y FastAPI calculaba el matching sobre candidatos limitados.

No había tabla vectorial ni operador pgvector. La dimensión no estaba declarada en la tabla comercial. No se debe inferir compatibilidad simplemente por tomar el último run o por tener igual longitud.

Se inspeccionó el esquema de PostgreSQL en una transacción de solo lectura: la base activa todavía no contiene `ai_runs`. Por ello no hay una dimensión observada en registros de esa base para informar. La inspección del modelo preparado se explica abajo. No se ejecutaron migraciones ni consultas de validación del módulo nuevo.

## 2. Extensión y entorno PostgreSQL

La nueva migración ejecuta:

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

La imagen PostgreSQL necesita los archivos de la extensión antes de ejecutar la migración. `database/pgsql/Dockerfile` compila pgvector 0.8.7 con `OPTFLAGS=""` y `with_llvm=no` en una etapa separada, y copia la biblioteca, los SQL/control y la licencia a `postgres:18-alpine`. `compose.yaml` construye `mati-postgres:18-alpine-pgvector-0.8.7`, conservando PostgreSQL 18, Alpine, configuración y volumen `sail-pgsql`.

No se cambió de motor, no se reemplazó PostgreSQL por otra base y no se arrancó ni reconstruyó el contenedor. Para una instalación PostgreSQL externa, el administrador debe instalar pgvector compatible con ese servidor y tener permisos para crear la extensión. No se añaden MongoDB, Pinecone, Qdrant ni Weaviate. Redis ya existía en Compose y no se modificó ni se incorporó al flujo.

Referencia técnica primaria: [pgvector oficial](https://github.com/pgvector/pgvector), que documenta búsqueda exacta, `<=>`, vectores de dimensiones variables e índices por espacio compatible.

## 3. Migraciones

- Prerrequisito existente: `2026_09_28_000001_create_catalog_ai_tables.php`, que crea la auditoría IA y las versiones.
- Nueva: `2026_10_03_000001_create_ai_product_embeddings_table.php`, que crea la extensión y la tabla canónica, las FK, restricciones e índices B-tree.
- PostgreSQL es obligatorio; otro driver produce un error explícito. La migración no crea una alternativa JSON ni SQLite.
- `down()` elimina solamente la tabla del módulo; conserva la extensión compartida.
- No hay HNSW ni IVFFlat.

## 4. Modelo de datos canónico

Tabla `ai_product_embeddings`, modelo Eloquent `AiProductEmbedding`.

| Campo | Propósito |
| --- | --- |
| `id` | Identificador canónico |
| `prenda_id` | FK a `prendas.id_prenda`; eliminación en cascada |
| `image_id`, nullable | FK a `imagenes_prenda.id_imagen_prenda`; corresponde a la imagen de prenda solicitada |
| `ai_model_version_id` | FK a `ai_model_versions`; conserva identidad normalizada y restringe eliminar versiones utilizadas |
| `source_run_id`, nullable | Auditoría de generación; eliminar el run no elimina el vector |
| `embedding` | Vector nativo pgvector, oculto en serialización Eloquent |
| `dimensions` | Longitud real comprobada de los valores |
| `norm` | Norma del vector normalizado persistido; corresponde a `embedding_norm` |
| `input_norm` | Norma real antes de la normalización defensiva en Laravel |
| `content_hash` | SHA-256 de los bytes de la imagen original |
| `embedding_version`, `preprocess_version`, `normalization` | Perfil de generación y normalización L2 |
| `geometry_key` | SHA-256 del perfil anterior para filtros/identidad |
| `metadata` | Procedencia, identidad, imagen/ruta, origen de dimensiones y evidencia |
| `created_at`, `updated_at` | Trazabilidad |

`model_id`, `model_revision` y `pipeline_version` viven canónicamente en `ai_model_versions` y se consultan mediante JOIN. También se conservan como evidencia en `metadata`. No se duplican esas columnas comerciales de identidad en la tabla nueva.

Se usa `embedding vector` sin dimensión fija en el tipo. Esto permite conservar históricos y nuevas versiones de distinta dimensión en la misma tabla. La restricción `vector_dims(embedding) = dimensions` exige coherencia de cada fila. El límite 1–16000 es capacidad del tipo, no una dimensión de modelo asumida. Otra restricción exige norma unitaria y normalización L2. Laravel rechaza valores no finitos, vectores vacíos/cero e identidades incompletas y normaliza con escala numérica estable.

La clave única es `(prenda_id, content_hash, ai_model_version_id, dimensions, geometry_key)`. `ON CONFLICT DO NOTHING` hace idempotente la persistencia. `--force` actualiza esa misma fila. Una imagen idéntica de la misma prenda y versión no crea otro vector por tener otra referencia de imagen. Se preservan asociaciones de prendas distintas incluso si comparten imagen; son necesarias para detectar posibles duplicados comerciales. El SHA corresponde a bytes: recodificaciones distintas no constituyen deduplicación exacta de archivos.

## 5. Dimensión real y modelo preparado

Modelo actualmente configurado/preparado: `google/siglip-base-patch16-224`.

Revisión inmutable local resuelta desde `main`: `7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed`.

La inspección de solo lectura del checkpoint safetensors preparado encontró `vision_model.head.probe` con forma `[1, 1, 768]`, además de las dimensiones concordantes de la proyección/capas del modelo. La implementación instalada de pooling devuelve el último eje de ese tamaño. La dimensión del artefacto preparado es, por tanto, **768**. No se ejecutó una inferencia y no se afirma haber observado un tensor generado en esta entrega.

FastAPI declara `dimension = len(values)` en cada embedding realmente generado y Laravel vuelve a contar los valores antes de persistir o consultar. No hay `vector(768)` ni un 768 fijo usado para decidir compatibilidad. Si cambia el modelo, revisión o tamaño real, se crea otro espacio y nunca se compara con el anterior. `capabilities` distingue información de caché/configuración de una dimensión verificada durante generación; no carga ni descarga pesos para responder.

Para reproducibilidad se puede fijar `MATI_AI_MODEL_REVISION` al SHA preparado. Un alias como `main` sólo es aceptable si FastAPI logra resolverlo a un SHA inmutable local; de lo contrario devuelve `MODEL_REVISION_UNRESOLVED` y no entrega un vector versionado ficticio.

## 6. Versionado y compatibilidad

Pipeline actual: `registration-1.2.0`. Perfil actual: `projected-image-l2-1`; preprocesado: `srgb-exif-transformers-image-1`; normalización: `l2`.

Se comparan únicamente embeddings con la misma identidad completa:

```text
model_id + model_revision + pipeline_version + dimensions
+ embedding_version + preprocess_version + normalization
```

`geometry_key` resume el perfil, mientras que modelo/revisión/pipeline se verifican contra `ai_model_versions`. CLIP y SigLIP permanecen separados aunque compartieran una dimensión. Análisis completo e imagen sola reutilizan el mismo espacio de features proyectadas y normalizadas. Cambiar la geometría/preprocesado requiere cambiar su versión.

El backfill conserva el pipeline realmente registrado en cada run. Si faltan las versiones del perfil antiguo, usa un espacio `legacy-image-*` / `legacy-preprocess-*` derivado del pipeline histórico: no lo presenta como perfil actual. Un run sin revisión inmutable, hash válido o identidad coherente se omite con error registrado. Reindexar imágenes actuales permite poblar el nuevo espacio; el backfill antiguo por sí solo no hace compatibles los históricos con consultas nuevas.

El matching interno deja de elegir arbitrariamente el último JSON: sólo utiliza embeddings canónicos compatibles. La identificación exacta y el matching textual siguen disponibles sin vectores.

## 7. Query vectorial exacta

El operador es distancia coseno `<=>`; la similitud se calcula como `1 - distance`, limitada defensivamente al rango [-1, 1]. Es una puntuación de similitud, no una probabilidad de identidad.

La implementación usa tres pasos relevantes:

```sql
WITH eligible AS MATERIALIZED (
    -- prendas activas e imágenes vigentes; filtros y exclusión de la propia prenda
    SELECT ... FROM ai_product_embeddings e
    JOIN ai_model_versions mv ON mv.id = e.ai_model_version_id
    JOIN prendas p ON p.id_prenda = e.prenda_id
    WHERE ...
), compatible AS MATERIALIZED (
    -- modelo, revisión, pipeline, dimensión y geometry_key iguales a la consulta
    SELECT ... FROM eligible WHERE ...
), scored AS MATERIALIZED (
    SELECT ..., embedding <=> CAST(? AS vector) AS distance
    FROM compatible
)
-- ROW_NUMBER por prenda: escoger su mejor imagen;
-- ordenar por distancia, prenda e embedding y aplicar LIMIT parametrizado.
```

El CTE materializado de compatibilidad impide que el planificador aplique el operador a dimensiones mezcladas. Los valores del vector son validados y se pasan como parámetro, nunca como SQL construido desde texto del navegador. El resultado contiene hasta K prendas distintas, con su mejor imagen, orden estable y filtros opcionales de categoría/marca/temporada/color/talla. El servicio permite un umbral mínimo; el endpoint usa 0, por lo que puede devolver menos de K si ninguna similitud restante lo alcanza.

`total_indexed` cuenta prendas elegibles antes de filtrar el espacio; `total_candidates` cuenta prendas compatibles. Índice vacío y espacio incompatible tienen estados distintos. El default es K=12, máximo 30.

La posible duplicación se marca por SHA-256 idéntico o similitud ≥0.98 (configurable con `MATI_AI_DUPLICATE_SIMILARITY`). Requiere revisión humana y no fusiona ni modifica prendas.

Para HNSW futuro: medir volumen y latencia primero; después crear un índice parcial/de expresión para un único modelo/revisión/pipeline/dimensión/geometría, con cast a `vector(N)` y `vector_cosine_ops`, y adaptar la consulta a ese espacio. La implementación actual permanece exacta y no crea el índice anticipadamente.

## 8. Integración Laravel/FastAPI y seguridad

Flujo implementado:

```text
Foto → POST /superadmin/ai/similar (Laravel autenticado)
     → POST /api/v1/garments/embedding (FastAPI privado con bearer)
     → validación/normalización del vector en Laravel
     → búsqueda coseno exacta en PostgreSQL/pgvector
     → top K prendas → React
```

FastAPI reutiliza modelo/processor/caché, lock de inferencia y fallback a CPU; valida la imagen y aplica el mismo preprocesado sRGB/EXIF. La ruta nueva no ejecuta OCR, clasificación ni extracción de paleta. Devuelve valores y metadatos completos únicamente a Laravel. No recibe credenciales, DSN ni acceso general a PostgreSQL.

Laravel acepta un archivo o `existing_image_id`, resuelve archivos locales permitidos, limita tamaño/formato y excluye la propia prenda al consultar una imagen existente. Añade búsqueda visual también al análisis completo. Los endpoints permanecen en el grupo autenticado de superadmin y con su limitación de solicitudes. React no consulta FastAPI ni PostgreSQL.

El navegador recibe IDs/títulos/enlaces, puntuación/distancia y evidencia de posible duplicación; los valores del vector se eliminan de la respuesta. Las fotos se sirven mediante `GET /superadmin/ai/catalog-image/{image}` con autorización y comprobación de imagen local/prenda activa. No se aceptan URLs de miniaturas arbitrarias. La ruta entrega la imagen local con caché privada; no produce una miniatura reducida nueva.

La auditoría de búsqueda conserva hash, identidad y resultados sin duplicar el vector de consulta en `ai_runs.embedding`. Los análisis conservan el JSON histórico de auditoría y además pueden persistirse canónicamente cuando están vinculados a una imagen guardada. Guardar una prenda sólo adjunta un vector si su SHA real coincide con una imagen que sobrevivió al guardado. Los fallos vectoriales se aíslan con savepoint y no revierten el guardado comercial.

Referencias a imágenes eliminadas/reasignadas o cuya ruta cambió se excluyen; el histórico no se borra. El backfill verifica el SHA actual de una imagen identificada antes de asociarla.

## 9. Comandos para el administrador — no ejecutados

Desde el proyecto en WSL, preparar la extensión y aplicar las migraciones pendientes:

```bash
docker compose build pgsql
docker compose up -d pgsql
./vendor/bin/sail artisan migrate
docker compose up -d --build mati-ai
```

Configurar token compartido y caché del modelo como en la guía existente. Variables Laravel nuevas: `MATI_AI_VECTOR_ENABLED=true` y `MATI_AI_DUPLICATE_SIMILARITY=0.98`. `.env` real no se modificó. Cambiar/recrear el servicio PostgreSQL es una operación del administrador pendiente de ejecución.

Comando solicitado (equivalente con Sail cuando PHP corre en el contenedor):

```bash
php artisan mati-ai:index-catalog --limit=100 --batch-size=25
php artisan mati-ai:index-catalog --limit=100 --batch-size=25 --after-id=123
php artisan mati-ai:index-catalog --limit=100 --batch-size=25 --force
php artisan mati-ai:index-catalog --from-runs --limit=100 --batch-size=25
php artisan mati-ai:index-catalog --from-runs --after-run-id=UUID_EXISTENTE --limit=100 --batch-size=25
```

- Modo imágenes: imágenes principales locales de prendas activas; genera sólo el embedding. No indexa todavía toda la galería.
- `--limit`: hasta 10000 entradas inspeccionadas por ejecución; `--batch-size`: entre 1 y 500.
- `--after-id`: cursor por ID de imagen. `--after-run-id`: resume por `created_at` + UUID; no supone que UUID aleatorios sean cronológicos.
- El cursor se imprime por lote y avanza incluso si falla una entrada. Los fallos se muestran/loguean y producen salida de error. Para reintentar entradas fallidas se repite desde un cursor anterior o desde el inicio; la clave única evita duplicados.
- Idempotencia de almacenamiento: una identidad ya persistida no crea otra fila ni otro run. En modo imágenes se genera el embedding antes de comprobar su identidad real, por lo que repetir indexación todavía consume inferencia. No se adivina la revisión/dimensión para saltarla.
- `--force`: actualiza la fila canónica y mantiene auditoría de la nueva generación; no crea otra fila canónica para la misma clave.
- `--from-runs`: no ejecuta inferencia; exige producto asociado, embedding/hash reales e identidad válida, y conserva el espacio histórico.

## 10. UI

En el asistente de prendas, el botón **Buscar similares** usa la foto principal cargada o su imagen existente. Las tarjetas muestran una sola entrada por prenda, foto autenticada cuando existe, título/enlace local, similitud y aviso de posible duplicado. Los resultados están ligados a la imagen actual; cambiar de foto no muestra resultados de la anterior.

Hay estados claros de carga, catálogo vacío, espacio incompatible, ausencia de embedding y servicio no disponible. Los avisos técnicos se traducen a texto comprensible. Se conservan análisis, colorimetría, códigos y carga manual. No se añade una conexión directa del navegador a la infraestructura IA/base.

## 11. Archivos de esta fase

Nuevos:

- `database/pgsql/Dockerfile`
- `database/migrations/2026_10_03_000001_create_ai_product_embeddings_table.php`
- `app/Models/AiProductEmbedding.php`
- `app/Services/AI/CatalogEmbeddingStore.php`
- `services/mati-ai/src/mati_ai/contracts/embedding.py`
- `DOCUMENTACION/IA/IMPLEMENTACION/04_embeddings_pgvector.md`
- `DOCUMENTACION/IA/IMPLEMENTACION/04_embeddings_pgvector.diff` (diff para revisión)

Modificados:

- `.env.example`, `compose.yaml`, `config/mati_ai.php`
- `app/Console/Commands/IndexMatiAiCatalog.php`
- `app/Http/Controllers/Superadmin/AiController.php`, `routes/web.php`
- `app/Services/AI/AiReviewService.php`, `CatalogAiService.php`, `MatiAiClient.php`
- `resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx`
- `resources/js/lib/garmentAssistant.ts`
- `services/mati-ai/README.md`
- `services/mati-ai/src/mati_ai/api/v1/routes.py`
- `services/mati-ai/src/mati_ai/contracts/common.py`, `contracts/identification.py`
- `services/mati-ai/src/mati_ai/core/model_manager.py`
- `services/mati-ai/src/mati_ai/identification/matching.py`

Los archivos adicionales de colorimetría en el estado Git corresponden a la entrega anterior y se preservaron. Las guías V2 conceptuales no se modificaron.

## 12. Limitaciones y estado de ejecución

Implementación escrita y pendiente de validación del usuario. PostgreSQL activo aún carece de las tablas IA; pgvector no se instaló en el contenedor en ejecución. No se afirma que una consulta, migración, compilación Docker ni inferencia haya pasado en este entorno.

La búsqueda es exacta y su coste crece con las filas compatibles. El comando procesa fotografías principales de forma secuencial dentro de lotes de lectura; no se añaden colas ni Redis. El contrato visual Python limita sus vectores a 2048 componentes; pgvector/Laravel tienen capacidad mayor, que no equivale a habilitar cualquier modelo sin adaptar su contrato.

Cambios de perfil obligan a reindexar para consultar el espacio nuevo. Históricos incompletos se separan o rechazan, no se rellenan con identidades asumidas. Una sobrescritura externa de bytes conservando exactamente la misma ruta no se detecta sólo con SQL; la indexación lee el SHA actual y el backfill lo verifica, pero no hay monitor de archivos ni actualización automática de todo el catálogo. Las cargas comerciales normales usan rutas nuevas.

El embedding describe la foto completa preprocesada, no un recorte automático del foreground de colorimetría; fondo/pose pueden influir. La marca de duplicado es heurística. Recuperación semántica se limita aquí al espacio visual generado por SigLIP: no se añadió un endpoint nuevo de consulta textual. No se implementaron outfits, recomendaciones, tendencias, VTO ni RAG.

## 13. Diff y status

El diff adjunto es una captura para revisión de cambios de código pendientes respecto de HEAD, incluyendo cambios locales anteriores de colorimetría cuando afectan a los mismos archivos. No se usa como prueba de ejecución ni se afirma que represente sólo esta fase. Incluye los archivos fuente nuevos sin hacer `git add`; excluye estos informes y el propio diff.

Los snapshots siguientes también son acumulados respecto de HEAD. `git diff --stat` sólo cuenta archivos rastreados; el status muestra además los nuevos. Ningún archivo se preparó en staging para esta entrega.

Rama: `feature/ia-colorimetria`. Estado acumulado: 19 archivos rastreados modificados y 12 archivos nuevos sin seguimiento (incluye colorimetría anterior e informes). Diff rastreado: 1291 inserciones y 233 eliminaciones. Sin staging, commit ni push.

```text
 .env.example                                       |   4 +
 app/Console/Commands/IndexMatiAiCatalog.php        | 153 ++++--
 app/Http/Controllers/Superadmin/AiController.php   | 114 ++++-
 app/Services/AI/AiReviewService.php                |  58 ++-
 app/Services/AI/CatalogAiService.php               |  29 +-
 app/Services/AI/MatiAiClient.php                   |  12 +-
 compose.yaml                                       |   6 +-
 config/mati_ai.php                                 |   8 +
 .../Superadmin/GarmentIntelligentAssistant.tsx     | 143 +++++-
 resources/js/lib/garmentAssistant.ts               |  36 ++
 routes/web.php                                     |   3 +
 services/mati-ai/README.md                         |  14 +
 services/mati-ai/src/mati_ai/api/v1/routes.py      |  74 ++-
 services/mati-ai/src/mati_ai/contracts/common.py   |   2 +-
 .../src/mati_ai/contracts/identification.py        |   9 +-
 services/mati-ai/src/mati_ai/contracts/vision.py   |   2 +
 services/mati-ai/src/mati_ai/core/model_manager.py | 282 ++++++++--
 .../mati-ai/src/mati_ai/identification/matching.py |   7 +-
 services/mati-ai/src/mati_ai/vision/color.py       | 568 +++++++++++++++++----
 19 files changed, 1291 insertions(+), 233 deletions(-)
```

```text
## feature/ia-colorimetria
 M .env.example
 M app/Console/Commands/IndexMatiAiCatalog.php
 M app/Http/Controllers/Superadmin/AiController.php
 M app/Services/AI/AiReviewService.php
 M app/Services/AI/CatalogAiService.php
 M app/Services/AI/MatiAiClient.php
 M compose.yaml
 M config/mati_ai.php
 M resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx
 M resources/js/lib/garmentAssistant.ts
 M routes/web.php
 M services/mati-ai/README.md
 M services/mati-ai/src/mati_ai/api/v1/routes.py
 M services/mati-ai/src/mati_ai/contracts/common.py
 M services/mati-ai/src/mati_ai/contracts/identification.py
 M services/mati-ai/src/mati_ai/contracts/vision.py
 M services/mati-ai/src/mati_ai/core/model_manager.py
 M services/mati-ai/src/mati_ai/identification/matching.py
 M services/mati-ai/src/mati_ai/vision/color.py
?? DOCUMENTACION/IA/IMPLEMENTACION/03_colorimetria_prendas.md
?? DOCUMENTACION/IA/IMPLEMENTACION/04_embeddings_pgvector.diff
?? DOCUMENTACION/IA/IMPLEMENTACION/04_embeddings_pgvector.md
?? app/Models/AiProductEmbedding.php
?? app/Services/AI/CatalogEmbeddingStore.php
?? app/Services/AI/ColorimetryCatalogGuard.php
?? database/migrations/2026_10_03_000001_create_ai_product_embeddings_table.php
?? database/pgsql/Dockerfile
?? services/mati-ai/src/mati_ai/contracts/colorimetry.py
?? services/mati-ai/src/mati_ai/contracts/embedding.py
?? services/mati-ai/src/mati_ai/vision/color_distance.py
?? services/mati-ai/src/mati_ai/vision/segmentation.py
```
