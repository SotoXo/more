# Registro inteligente, identificación y enriquecimiento

Implementación inicial en el catálogo real de Mati Boutique. Python conserva la versión 3.11 y las dependencias se administran con `uv`. No se alteran los documentos de diseño V2.

## Autoridad y flujo

```text
React ProductCreate → Laravel autenticado → HTTP privado → FastAPI
                    ↓                                  ↓
            PostgreSQL y storage                análisis en memoria
                    ↑                                  ↓
            revisión humana ← sugerencias, evidencia y versiones
```

Laravel consulta los catálogos reales de categorías, marcas, colores, tallas y patrones. Los IDs de las sugerencias se contrastan con esos catálogos; no se crean categorías, colores, marcas o tallas. Se conserva `POLERA` si existe. El código actual también tiene materiales, texturas y paletas de variante; la implementación respeta esos atributos.

FastAPI no tiene conexión a PostgreSQL ni autoridad para modificar prendas, variantes, inventario, ventas, reservas, pagos o Kardex. Los únicos cambios comerciales los hace el flujo habitual de Laravel cuando el administrador guarda el formulario.

## Uso en el formulario

El asistente vive dentro del editor real de alta y edición:

1. Foto principal: archivo de la prenda → calidad, paleta, categorías top-3, patrón y posibles coincidencias.
2. Escanear código: captura dedicada, con opción de cámara móvil → barcode o QR → comprobación interna.
3. Etiqueta: captura dedicada → texto original/normalizado, marca, talla, modelo, SKU, GTIN y composición.
4. Código escrito: SKU, GTIN, marca/modelo o texto → búsqueda interna.
5. Búsqueda externa: Laravel vuelve a comprobar el catálogo antes de consultar las fuentes habilitadas.
6. Aplicar categoría/color/marca/talla/patrón/modelo o datos compatibles → campos editables. Los valores ya informados requieren una acción explícita para reemplazarse.
7. Guardar: continúa el formulario habitual; las sugerencias no guardan productos por sí mismas.

Los scores visuales y de ranking se muestran como similitud/coincidencia, nunca como una probabilidad calibrada. Un QR que contiene URL se muestra como dato y no provoca navegación ni descarga automática.

Las imágenes de candidatos se sirven por Laravel mediante descarga segura de FastAPI. Al descargar una referencia se conservan hash, dimensiones, fecha, derechos y procedencia en su ejecución; los bytes no se guardan en PostgreSQL. `unknown` puede utilizarse para referencia; importar/publicar exige `owned`, `licensed`, `provider_authorized` o `public_domain`, evidencia configurada y aprobación humana. `prohibited` queda bloqueado.

## Laravel

Cliente: `app/Services/AI/MatiAiClient.php`. Centraliza URL, token, timeout, multipart, request ID, respuesta versionada y fallback. No se colocan llamadas HTTP en los controladores comerciales existentes.

Servicios separados:

- `CatalogAiService`: taxonomías, candidatos mínimos, códigos y duplicados.
- `AiReviewService`: ejecuciones, sugerencias inmutables y decisiones humanas.
- `AiPublicationService`: originales privados, derivados aprobados y adjuntos al guardado normal.

Rutas bajo `/superadmin/ai`, protegidas por autenticación, rol superadmin y controles de solicitud. React solo conoce estas rutas Laravel.

| Método | Ruta |
|---|---|
| GET | `/superadmin/ai/capabilities` |
| POST | `/superadmin/ai/analyze` |
| POST | `/superadmin/ai/barcode` |
| POST | `/superadmin/ai/label` |
| POST | `/superadmin/ai/match` |
| POST | `/superadmin/ai/external-search` |
| POST | `/superadmin/ai/prepare-publication` |
| POST | `/superadmin/ai/feedback` |
| GET | `/superadmin/ai/external-reference/{run}/{candidate}/{image}` |
| GET | `/superadmin/ai/prepared-images/{token}` |

Al editar, `analyze` admite `existing_image_id` para la portada local existente. Laravel lee únicamente imágenes del almacenamiento del catálogo; FastAPI recibe bytes, no rutas ni URLs del navegador. Las referencias externas se resuelven por índices de una búsqueda persistida del mismo usuario y pasan por FastAPI con `purpose=reference`.

Los límites de subida del formulario son 6 MiB; FastAPI mantiene su límite independiente de 10 MiB para imágenes y el middleware limita el cuerpo recibido, incluso sin Content-Length. Una respuesta externa con bytes no se guarda como JSON binario en PostgreSQL.

Variables Laravel:

| Variable | Valor inicial/propósito |
|---|---|
| `MATI_AI_ENABLED` | interruptor del asistente |
| `MATI_AI_URL` | `http://mati-ai:8000` dentro de Sail |
| `MATI_AI_TOKEN` | secreto compartido; vacío rechaza la integración |
| `MATI_AI_TIMEOUT` | 30 segundos |
| `MATI_AI_CONNECT_TIMEOUT` | 3 segundos |

Un error, timeout, respuesta inválida o modelo ausente muestra un aviso no bloqueante. El guardado manual no depende de FastAPI. Los embeddings se retienen para uso interno y se eliminan de las respuestas a React.

## FastAPI

| Método | Endpoint | Entrada |
|---|---|---|
| GET | `/health` | liveness, sin token ni inferencia |
| GET | `/api/v1/capabilities` | Bearer interno |
| POST | `/api/v1/garments/analyze` | multipart: image, categories, colors, patterns; include_embedding opcional |
| POST | `/api/v1/identification/barcode` | multipart: image |
| POST | `/api/v1/identification/label` | multipart: image, brands, sizes |
| POST | `/api/v1/products/match` | JSON: query, candidates, limit |
| POST | `/api/v1/products/external-search` | JSON: gtin, brand, model_code, query, category, color, source_ids |
| POST | `/api/v1/products/external-image` | JSON: source_id, product_url, original_url, purpose |
| POST | `/api/v1/images/prepare-publication` | multipart: image, approved, rights_status, approval_id |

Todos los endpoints `/api/v1` requieren Bearer Token. No se habilita CORS para React ni documentación pública en el servicio.

Las taxonomías multipart son listas JSON de `{id,name,aliases?,hex?}`. También se admite `categories[]` repetido con objetos JSON. Laravel es quien envía los valores existentes; no se aceptan rutas de archivos suministradas por el navegador.

Sobre común:

```json
{
  "request_id": "uuid",
  "contract_version": "1",
  "status": "completed",
  "data": {},
  "warnings": [],
  "abstentions": [],
  "model": {
    "model_id": "google/siglip-base-patch16-224",
    "model_revision": "revision-del-checkpoint",
    "pipeline_version": "registration-1.0.0"
  },
  "timing_ms": {},
  "human_review_required": true
}
```

Se diferencian `completed`, `partial`, `insufficient_evidence`, `degraded` y `failed`. Los errores tienen código y mensaje seguros; no retornan stack traces, tokens, HTML remoto ni rutas internas.

## Imágenes y visión

Validación de bytes, formato raster JPEG/PNG/WebP, decodificación, límite de tamaño/dimensiones/píxeles, animación y EXIF. Se procesa una copia RGB/sRGB en memoria. El original no se modifica. Se calculan SHA-256 del original y de píxeles normalizados y un hash perceptual.

Calidad: resolución, Laplaciano para desenfoque, exposición y contraste. Son heurísticas que requieren calibración local.

Color: OpenCV en Lab y cuantización determinista. Un fondo uniforme se reduce cuando la heurística encuentra primer plano plausible. Se reportan paleta, porcentajes, cobertura, distancia al color del catálogo y advertencias. No se entrena una red para color.

Clasificación: `google/siglip-base-patch16-224`, zero-shot con categorías reales. `openai/clip-vit-base-patch32` queda como proveedor alternativo configurable. Los aliases de prompts traducen etiquetas; no agregan categorías.

`ModelManager` carga y conserva un solo modelo visual por worker, limita la concurrencia, usa `torch.inference_mode()` y permite CPU cuando CUDA no está disponible o no puede usarse. No existe entrenamiento ni fine-tuning. Los embeddings están normalizados y se comparan por coseno únicamente entre versiones compatibles.

## Preparación de modelos y GPU

Variables Python en `services/mati-ai/.env.example`:

- `MATI_AI_MODEL_PROVIDER=siglip|clip`.
- `MATI_AI_VISION_MODEL`: override opcional del modelo.
- `MATI_AI_MODEL_REVISION`: revisión configurada; fijar commit preparado para releases.
- `MATI_AI_DEVICE=auto|cpu|cuda`.
- `MATI_AI_LOCAL_FILES_ONLY=true`: API sin descargas de pesos.
- `HF_HOME`: cache persistente Hugging Face.
- `MATI_AI_OCR_MODEL_DIRECTORY`: cache de OCR.
- `MATI_AI_OCR_DOWNLOAD_ENABLED=false`: preparar OCR por separado.
- `MATI_AI_EXTERNAL_SOURCES_FILE`: configuración local de fuentes.
- `MATI_AI_MAX_IMAGE_BYTES` y `MATI_AI_MAX_PIXELS`: límites de archivos.

Ejemplo de preparación explícita que el administrador puede ejecutar una sola vez:

```bash
cd services/mati-ai
uv sync --frozen
uv run python scripts/prepare_models.py --vision
uv run python scripts/prepare_models.py --ocr
uv run python -c 'import torch; print(torch.__version__); print(torch.cuda.is_available()); print(torch.cuda.get_device_name(0) if torch.cuda.is_available() else "CPU")'
```

Para arrancar directamente en desarrollo, con el token y las rutas de cache configurados:

```bash
cd services/mati-ai
uv run uvicorn mati_ai.main:app --host 127.0.0.1 --port 8001 --workers 1 --no-access-log
```

Preparar y ejecutar el servicio bajo el mismo usuario y las mismas rutas de cache. La RTX 5060 tiene 8 GB; no se mantienen SigLIP y CLIP simultáneamente. EasyOCR se ejecuta en CPU para evitar competencia por VRAM. No se incluyeron pesos en Git.

El script de benchmark y los tests `model_integration` permiten medir carga, inferencia, VRAM, CPU, color, OCR y barcode. Las mediciones finales quedan a cargo del administrador tras su instrucción de no realizar validaciones adicionales; no se atribuyen latencias no observadas.

## Barcode y OCR

OpenCV es el primer lector de QR/código retail; ZXing-C++ amplía la cobertura de EAN-13, EAN-8, UPC-A, UPC-E, CODE128 y QR. Se conserva simbología, contenido original, valor normalizado, checksum cuando corresponde, región y advertencias. El decoder no inventa una confianza numérica que no proporciona.

Checksum GTIN/EAN/UPC se calcula localmente. Un checksum válido no prueba identidad material. Los códigos de proveedor/QR de texto permanecen candidatos y nunca se tratan automáticamente como fichas de producto.

OCR: `OCRProvider` abstrae el motor. EasyOCR usa PyTorch ya existente y reconocimiento español/inglés en CPU. No se agregó PaddlePaddle ni dependencia de Tesseract del sistema. Se normaliza Unicode/espacios y se extraen candidatos de marca/talla contra catálogos Laravel, modelo/SKU, porcentajes y GTIN. Se conserva texto original para revisión, sin loguearlo.

## Matching

Jerarquía de evidencia: SKU → GTIN válido → modelo/proveedor → marca/modelo → texto → embedding → categoría/color. Los conflictos de identificador penalizan fuertemente y no se compensan con parecido visual. Foto parecida no equivale a identidad.

Laravel busca códigos en el catálogo completo antes del conjunto acotado de candidatos por similitud. El matching no tiene credenciales de base de datos. La similitud de productos antiguos mejora conforme sus imágenes adquieren análisis/embeddings vinculados; no se ejecuta una indexación masiva automática.

## Fuentes externas y scraping

`ExternalProductSource` define `search_by_gtin`, `search_by_model_code`, `search_by_text`, `fetch_product` y `fetch_images`.

- `ManualCatalogAdapter`: catálogo/feed JSON curado por el operador.
- `HtmlCatalogAdapter`: búsqueda y extracción real con httpx + BeautifulSoup, mediante selectores configurados por dominio.

No se encontró un proveedor/API real configurado. `config/external-sources.example.json` contiene plantillas desactivadas, sin tiendas ficticias habilitadas. Una instalación sin fuentes informa `NO_EXTERNAL_SOURCES_CONFIGURED`.

Para habilitar una fuente, copiar el ejemplo a `external-sources.json` (excluido de Git) y completar dominio(s) exactos, URL de búsqueda con `{query}`, selectores, tipo de fuente, autorización de términos, URL/fecha de revisión y derechos/evidencia. Reiniciar el worker tras modificar la configuración.

Ejemplo de fila para un catálogo manual, con marcadores que el operador sustituye:

```json
{
  "title": "Título confirmado por proveedor",
  "brand": "Marca",
  "model_code": "Referencia del proveedor",
  "gtin": "GTIN válido si existe",
  "canonical_url": "https://DOMINIO_AUTORIZADO/producto",
  "images": ["https://DOMINIO_AUTORIZADO/foto.jpg"]
}
```

La URL de cada fila y las imágenes deben pertenecer a dominios de la fuente. Derechos se toman de la configuración del operador, nunca del formulario ni del HTML. Los selectores soportan título, marca, modelo, GTIN, descripción, imágenes, precio, moneda, disponibilidad y canonical. HTML sin título esperado falla como cambio de parser. No se ejecuta JavaScript remoto.

El orden de fuentes prioriza proveedor, marca oficial, API autorizada, catálogo público y scraping permitido. Se respetan robots/rate limits; CAPTCHA, login, bloqueos y respuestas 403/429 detienen la fuente. No hay Instagram scraping, Google Custom Search legacy ni evasión de protecciones.

## Safe fetch / SSRF

Toda descarga del adaptador y de imágenes pasa por `SafeFetcher`:

- Solo HTTPS, puerto 443 y dominios exactos configurados.
- Bloqueo de usuario/contraseña en URL, esquemas alternativos, loopback, privados, link-local, multicast, metadata, IPv6 de transición y respuestas DNS mixtas inseguras.
- Resolución y validación antes de conectar; conexión fijada a IP pública validada con Host/SNI del dominio. No se vuelve a resolver el nombre en la conexión.
- Cada redirección revalida política, DNS y robots; máximo 3 saltos.
- Sin proxies de entorno, cookies, credenciales heredadas ni redirecciones automáticas.
- Streaming con límite de bytes, timeout, plazo total y Content-Type. Se rechaza compresión HTTP inesperada.
- Imágenes se verifican por decoder real y MIME; no SVG ni HTML como raster.
- User-Agent identificado `MatiBoutiqueCatalogBot/1.0`.
- Pacing por fuente, cooldown ante 429 y robots cacheado diez minutos. Una política robots más estricta que la configuración bloquea hasta configurarla adecuadamente.
- Robots inaccesible falla de forma conservadora. El servicio no intenta eludirlo.

Referencias técnicas utilizadas: [HTTPX: conexión a IP conservando SNI](https://www.python-httpx.org/advanced/extensions/#sni_hostname), [OWASP SSRF Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html), [BeautifulSoup](https://www.crummy.com/software/BeautifulSoup/bs4/doc/), [model card SigLIP](https://huggingface.co/google/siglip-base-patch16-224).

## Publicación y storage

La preparación exige aprobación explícita de Laravel y derechos compatibles. Genera `thumbnail` (240), `card` (640) y `detail` (1600), contenido dentro del rectángulo, sin ampliar innecesariamente. Aplica orientación/sRGB, elimina EXIF, codifica WebP y retorna hashes/manifiesto.

No usa generación, inpainting, eliminación generativa de fondo, superresolución ni cambio de color de la prenda.

Los bytes solo viajan por HTTP interno hacia Laravel y se almacenan en disco, nunca en PostgreSQL. El original permanece privado. Una imagen preparada obtiene un token temporal ligado a su administrador; se adjunta al producto durante el guardado normal. Una referencia externa no se convierte automáticamente en imagen publicada.

## Persistencia y feedback

Migración `2026_09_28_000001_create_catalog_ai_tables.php`:

- `ai_model_versions`: modelo, revisión y pipeline.
- `ai_runs`: operación, request ID, usuario, estado, metadatos, resultado, embedding interno y vínculo opcional con prenda.
- `ai_suggestions`: campo, valor, evidencia y score de predicción.
- `ai_human_reviews`: aceptación/corrección/rechazo, valor predicho, valor confirmado, usuario, momento y etapa.
- `prendas.modelo_pre`: referencia de modelo opcional; no reinterpreta el SKU generado por el sistema.

La migración se entrega sin ejecutar. Hasta aplicarla, el alta manual conserva su funcionamiento y no depende de las tablas IA. La auditoría usa una transacción/savepoint separado para que un fallo de su almacenamiento no invalide el guardado comercial.

Comandos de mantenimiento disponibles para ejecución explícita del administrador:

```bash
docker compose exec -T laravel.test php artisan mati-ai:cleanup-prepared --limit=500
docker compose exec -T laravel.test php artisan mati-ai:index-catalog --limit=100 --after-id=0
```

`cleanup-prepared` elimina únicamente directorios temporales vencidos de preparaciones no adjuntadas; conserva los registros de auditoría. No se programó una tarea automática de borrado.

`index-catalog` analiza portadas locales existentes y registra embeddings reales ligados a la prenda, sin aplicar sugerencias ni editar productos. Admite `--force`. El descarte por hash e identidad de embedding ocurre después de recibir el análisis, porque capabilities no garantiza la revisión resuelta del checkpoint. Por ello una repetición puede volver a inferir aunque no inserte un registro duplicado.

La versión de modelo se obtiene a través de sugerencia → ejecución → versión. La predicción original no se sobrescribe. El feedback de aplicación al formulario se distingue del valor finalmente guardado. Si llega después del guardado, Laravel registra la etapa final desde los valores comerciales persistidos, usando el mismo bloqueo de ejecución y una referencia única a la decisión original (`form_review_id`). El navegador serializa el envío de decisiones y conserva pendientes por usuario/formulario en sessionStorage durante 24 horas para reintentar; si ese almacenamiento está bloqueado, la cola permanece en memoria. No se añade una tabla por atributo ni un vector store externo.

## Docker / Sail

`compose.yaml` agrega `mati-ai` bajo perfil opcional `ai`, en la red Sail y sin puerto publicado. Laravel conserva sus dependencias actuales; el servicio IA no es una dependencia obligatoria del arranque comercial.

```bash
docker compose --profile ai build mati-ai
docker compose --profile ai up -d mati-ai
```

Cache persistente `mati-ai-cache:/cache`. Preparar pesos dentro del contenedor con el mismo usuario y cache:

```bash
docker compose --profile ai run --rm mati-ai /app/.venv/bin/python scripts/prepare_models.py --vision
docker compose --profile ai run --rm mati-ai /app/.venv/bin/python scripts/prepare_models.py --ocr
```

Configurar el mismo token por entorno/secret manager en Laravel y FastAPI. No se escribió ningún secreto real en `.env`.

Para GPU, usar el override `compose.ai-gpu.yaml` con NVIDIA Container Toolkit compatible. Para acceso directo desde desarrollo, `compose.ai-dev.yaml` publica únicamente `127.0.0.1:8001`. No usar ese override para exposición pública.

```bash
docker compose -f compose.yaml -f compose.ai-gpu.yaml --profile ai up -d mati-ai
docker compose -f compose.yaml -f compose.ai-dev.yaml --profile ai up -d mati-ai
```

La imagen usa Python 3.11, un usuario sin privilegios, un worker y el lock CUDA existente. La instalación de PyTorch en Docker es pesada por el entorno requerido; no cambia el stack a TensorFlow ni instala modelos adicionales.

## Dependencias nuevas

Se agregaron con `uv add`:

| Dependencia | Motivo |
|---|---|
| `zxing-cpp` | soporte real de CODE128/UPC-E y otros códigos donde OpenCV no alcanza |
| `easyocr` | OCR dedicado compatible con PyTorch, español/inglés y abstracción intercambiable |
| `beautifulsoup4` | extracción HTML por selectores; parser estándar sin añadir lxml |
| `sentencepiece` | tokenizer requerido por el checkpoint SigLIP |
| `protobuf` | lectura del modelo SentencePiece por Transformers |

El lock conserva dependencias transitivas exactas. EasyOCR incorpora auxiliares de imágenes y su recognizer; los pesos se preparan fuera de requests. No se instaló TensorFlow, PaddlePaddle, pyzbar, Playwright ni Selenium para scraping.

## Comandos de validación disponibles para el administrador

La última instrucción del administrador reservó la validación para él. Las pruebas quedan escritas; no se ejecutaron nuevas verificaciones después de esa indicación.

```bash
cd services/mati-ai
uv run pytest -m "not model_integration"
MATI_AI_RUN_MODEL_TESTS=1 uv run pytest -m model_integration
uv run ruff check src tests
uv run mypy src
cd ../..
docker compose exec -T laravel.test php artisan test --compact
node --experimental-strip-types --test tests/frontend/garmentAssistant.test.mjs
npm run build
git diff --check
git diff --stat
git status
```

El comando de pruebas frontend requiere Node con soporte para `--experimental-strip-types`; usa las dependencias del proyecto y no agrega un framework.

Revisar la configuración de tests antes de ejecutarlos: usar la base `testing`, nunca una base comercial para pruebas con migraciones destructivas. Aplicar la migración nueva mediante el procedimiento habitual después de la revisión.

## Límites y siguientes fases

- Categoría/patrón zero-shot y color son sugerencias; requieren evaluación con fotos representativas de Mati. No se promete precisión de catálogo sin benchmark.
- Fotos con varias prendas, fondos complejos, etiquetas curvas, ruido y texto pequeño pueden producir abstención o error.
- OCR obtiene candidatos; la talla/composición no se infiere de la foto principal como verdad.
- Sin pesos preparados hay respuesta parcial. Sin token compartido hay fallback manual. Sin fuente autorizada no hay búsqueda web abierta.
- No hay scraping de páginas renderizadas con JavaScript, login o anti-bot.
- El catálogo de candidatos internos se acota por configuración; una ampliación futura puede requerir indexación/versionado adicional.
- La comparación visual externa requiere embeddings precomputados suministrados por una fuente configurada y compatibles con la revisión del modelo; el adaptador HTML no calcula embeddings de todas las imágenes remotas. Los rankings son reglas versionadas sin calibración estadística. Un parecido visual no verifica un GTIN.
- Se posponen entrenamiento, fine-tuning, tendencias, forecasting, reposición, recomendaciones, outfits, VTO, talla inteligente y RAG. No forman parte de esta implementación.
