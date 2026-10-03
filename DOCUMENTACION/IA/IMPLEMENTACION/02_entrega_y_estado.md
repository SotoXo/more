# Entrega del módulo de registro inteligente

Fecha de cierre: 29 de septiembre de 2026.

Esta entrega contiene implementación en el repositorio, integración con el formulario real, pruebas escritas y documentación. La instrucción posterior del administrador reservó para él toda validación: no se ejecutaron nuevas pruebas, builds, inferencias, migraciones ni validación de navegador al cerrar estos cambios. Este informe no certifica la operación de la versión final.

Guía operativa: [01_registro_inteligente.md](01_registro_inteligente.md).

## 1. Resumen de lo implementado

Asistente integrado en el alta y edición de prendas: foto, código, QR, etiqueta, coincidencias internas, fuentes externas configuradas, revisión humana y preparación de imágenes. La aplicación de sugerencias modifica el formulario editable; el guardado final permanece en Laravel.

Python 3.11 usa una estructura `src/mati_ai`, FastAPI privado y dependencias administradas con `uv`. Se conservan el catálogo real, incluidas categorías como POLERA, y los atributos actuales de patrones, materiales, texturas y paletas.

## 2. Flujo final

| Entrada/operación | Comportamiento implementado |
|---|---|
| Imagen principal | Laravel envía bytes y taxonomías reales; Python calcula formato/orientación, hashes, calidad, paleta, categoría top-k y patrón. La edición admite la portada local existente. |
| Barcode | Captura dedicada; OpenCV y ZXing-C++ extraen códigos, región y checksum; Laravel consulta primero su catálogo. |
| QR | Se muestra contenido y si contiene URL. El QR no dispara navegación ni descarga. |
| OCR | Foto de etiqueta separada; texto original, normalización, candidatos de marca/talla, modelo/SKU/GTIN y composición porcentual. |
| Matching | Códigos internos exactos antes de ranking por texto, embedding y atributos; los conflictos penalizan el candidato. |
| Scraping | Búsqueda únicamente en adaptadores con dominios y permisos configurados; páginas HTML estáticas con selectores, robots y límites. |
| Imágenes externas | Referencia autenticada por Laravel; derechos unknown solo permiten referencia. Publicación exige derechos y evidencia más aprobación humana. |
| Guardado | Se conservan decisiones y valores finales; imágenes aprobadas se adjuntan durante el flujo comercial habitual. |

## 3. Modelos utilizados

- Principal: `google/siglip-base-patch16-224`, clasificación zero-shot y embeddings.
- Alternativa configurable: `openai/clip-vit-base-patch32`; no se mantiene junto a SigLIP.
- OCR: EasyOCR español/inglés en CPU, encapsulado en `OCRProvider`.
- Color: OpenCV/Lab y cuantización; no se entrena una red.
- Un `ModelManager` conserva un modelo visual por worker, limita concurrencia, emplea `torch.inference_mode()` y admite CPU.
- Revisión de modelo y pipeline acompañan las respuestas. No se realizó entrenamiento ni fine-tuning.

## 4. Dependencias nuevas

| Dependencia | Versión fijada en esta entrega | Motivo |
|---|---:|---|
| beautifulsoup4 | 4.15.0 | HTML permitido mediante selectores |
| easyocr | 1.7.2 | OCR compatible con PyTorch |
| zxing-cpp | 3.1.1 | EAN/UPC, CODE128 y QR |
| sentencepiece | 0.2.2 | Tokenizador de SigLIP |
| protobuf | 7.36.2 | Lectura del tokenizador por Transformers |

Se conserva Python 3.11 y el índice CUDA de PyTorch. `uv.lock` contiene las versiones transitivas. No se agregaron TensorFlow, PaddlePaddle, pyzbar, Playwright ni Selenium al servicio.

## 5. Endpoints FastAPI

| Método | Endpoint |
|---|---|
| GET | `/health` |
| GET | `/api/v1/capabilities` |
| POST | `/api/v1/garments/analyze` |
| POST | `/api/v1/identification/barcode` |
| POST | `/api/v1/identification/label` |
| POST | `/api/v1/products/match` |
| POST | `/api/v1/products/external-search` |
| POST | `/api/v1/products/external-image` |
| POST | `/api/v1/images/prepare-publication` |

Bearer interno en `/api/v1`; `/health` no carga modelos. No hay CORS de React ni documentación pública de FastAPI. El middleware ASGI limita bytes reales, incluidos cuerpos sin Content-Length válido.

## 6. Endpoints Laravel y comandos

Todas las rutas siguientes están dentro de `/superadmin/ai`, con autenticación, rol y límite de solicitudes:

| Método | Sufijo |
|---|---|
| GET | `/capabilities` |
| POST | `/analyze`, `/barcode`, `/label` |
| POST | `/match`, `/external-search` |
| POST | `/prepare-publication`, `/feedback` |
| GET | `/external-reference/{run}/{candidate}/{image}` |
| GET | `/prepared-images/{token}` |

`MatiAiClient` centraliza token, request ID, timeouts, multipart, contratos y errores seguros. `CatalogImageReader` restringe lectura a imágenes locales del catálogo. Las referencias externas se resuelven desde resultados persistidos del usuario, sin aceptar URLs arbitrarias como proxy abierto.

Comandos entregados, sin ejecutar:

- `php artisan mati-ai:cleanup-prepared --limit=500`: elimina temporales vencidos no adjuntados y conserva auditoría.
- `php artisan mati-ai:index-catalog --limit=100 --after-id=0`: analiza portadas locales y almacena embeddings reales, sin editar prendas. `--force` permite volver a registrar una identidad ya indexada.

## 7. React

- `ProductCreate.tsx`: formulario compartido de creación/edición con asistente, modelo de proveedor y adjuntos aprobados.
- `GarmentIntelligentAssistant.tsx`: controles de captura, análisis, sugerencias aplicables, candidatos, fuentes, referencias, feedback e imágenes.
- `garmentAssistant.ts`: compatibilidad de catálogo/tallas, campos editables, conservación de valores manuales, URLs locales y derechos.
- `productEditor.ts`: tipos compartidos del formulario.
- `tests/frontend/garmentAssistant.test.mjs`: 16 casos escritos sin ejecutar. Comando disponible: `node --experimental-strip-types --test tests/frontend/garmentAssistant.test.mjs`.

Los scores se etiquetan como coincidencia/similitud, no como certeza. Los avisos se presentan en español. La selección de una sugerencia no crea categorías, marcas, colores ni tallas. El feedback se serializa y la cola pendiente se conserva por usuario/formulario en sessionStorage durante 24 horas; si el navegador bloquea ese almacenamiento, permanece en memoria.

## 8. Persistencia

Migración nueva: `2026_09_28_000001_create_catalog_ai_tables.php`, entregada sin aplicar.

- `ai_model_versions`: identidad del modelo, revisión y pipeline.
- `ai_runs`: request ID, operación, usuario, estado, resultado, hash, embedding interno y vínculo opcional a prenda.
- `ai_suggestions`: predicción, campo, fuente y evidencia.
- `ai_human_reviews`: aceptación/corrección/rechazo, predicción, valor confirmado, usuario, etapa y fecha; `form_review_id` enlaza de forma única la decisión de formulario con la revisión del guardado.
- `prendas.modelo_pre`: modelo/referencia opcional del proveedor; no reemplaza el SKU del sistema.

La predicción original se conserva. La revisión durante el formulario se distingue del valor guardado. El feedback tardío consulta los valores ya persistidos bajo el mismo bloqueo de ejecución que usa el guardado, para conservar también la etapa final. Los bytes de imágenes se almacenan en disco, no en PostgreSQL. La auditoría se aísla mediante savepoint para no abortar una operación manual por fallo de tablas IA.

## 9. Adaptadores externos

`ExternalProductSource` define búsqueda por GTIN, modelo y texto, ficha e imágenes.

- `ManualCatalogAdapter`: catálogo/feed curado del operador.
- `HtmlCatalogAdapter`: scraping real mediante HTTPX y BeautifulSoup con selectores por fuente.

No había un proveedor real configurado. El ejemplo contiene plantillas desactivadas. Es necesaria información empresarial real para habilitar dominios, permisos y derechos; no se inventó una tienda como fuente activa.

## 10. Seguridad SSRF

HTTPS/443 y allowlist exacta; bloqueo de esquemas alternativos, credenciales en URL, loopback, redes privadas, link-local, metadata, multicast y formatos IPv6 de transición. Se valida cada resolución y redirección y se conecta a la IP pública validada conservando Host/SNI. Sin proxies/cookies/credenciales heredadas.

Se aplican límites de redirección, bytes, Content-Type, decoder raster, timeout, plazo de consulta, pacing, robots y cooldown 429. No se intenta evadir CAPTCHA, login, robots ni anti-bot. Las consultas, tokens, imágenes y texto OCR completo no se escriben en logs técnicos.

## 11. GPU y rendimiento observado

| Dato | Estado |
|---|---|
| Hardware detectado antes de la instrucción de no validar | NVIDIA GeForce RTX 5060, capacidad CUDA 12.0 |
| PyTorch observado | 2.13.0+cu130 |
| Modelo preparado previamente | SigLIP base patch16 224 |
| Revisión descargada | `7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed` |
| Descarga de checkpoint observada | 137.503 s; es preparación, no latencia de inferencia |
| Inferencia completa observada | No disponible; el intento anterior falló antes de añadir dependencias del tokenizador |
| VRAM ocupada por inferencia | No medida |
| Carga, CPU fallback, color, OCR, barcode | Sin mediciones finales completas |

Se añadieron SentencePiece y Protobuf después del fallo anterior. No se volvió a ejecutar inferencia por instrucción del usuario. La capacidad nominal de 8 GB no se presenta como consumo observado. `scripts/benchmark.py` permite al administrador obtener mediciones con sus fotos y pesos ya preparados.

## 12. Pruebas y validación

**Estado de la versión final: pendiente de validación por el administrador.** No se ejecutaron tests, lint, compilación, inferencia ni `git diff --check` al cerrar la implementación.

Resultados históricos anteriores a esa instrucción, que no certifican los cambios finales:

| Área | Registro anterior disponible |
|---|---|
| Python | 79 passed, 1 skipped; versión anterior a los últimos cambios |
| Laravel general | 81 passed, 415 assertions; anterior al cierre de integración IA |
| Integración real SigLIP | Intento fallido previo a incorporar dependencias del tokenizador; no repetido |
| Nuevos tests PHP y frontend | Escritos, sin ejecutar: 7 casos MatiAiClientTest, 14 MatiAiWorkflowTest y 16 helpers frontend |
| Docker/build frontend/migración | Sin ejecución final |

Las pruebas escritas cubren contratos/auth, límites e imágenes, calidad/color, aliases, barcode/QR/checksum, normalización OCR, matching, embeddings, publicación, adaptadores/ranking y SSRF; además del cliente/orquestación Laravel y helpers React. Las pruebas del modelo real son separadas y requieren pesos preparados.

## 13. Archivos creados

- `DOCUMENTACION/IA/IMPLEMENTACION/01_registro_inteligente.md`
- `DOCUMENTACION/IA/IMPLEMENTACION/02_entrega_y_estado.md`
- `app/Console/Commands/CleanupMatiAiPrepared.php`
- `app/Console/Commands/IndexMatiAiCatalog.php`
- `app/Http/Controllers/Superadmin/AiController.php`
- `app/Models/AiHumanReview.php`
- `app/Models/AiRun.php`
- `app/Models/AiSuggestion.php`
- `app/Services/AI/AiPublicationService.php`
- `app/Services/AI/AiReviewService.php`
- `app/Services/AI/CatalogAiService.php`
- `app/Services/AI/CatalogImageReader.php`
- `app/Services/AI/MatiAiClient.php`
- `compose.ai-dev.yaml`
- `compose.ai-gpu.yaml`
- `config/mati_ai.php`
- `database/migrations/2026_09_28_000001_create_catalog_ai_tables.php`
- `resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx`
- `resources/js/lib/garmentAssistant.ts`
- `resources/js/types/productEditor.ts`
- `services/mati-ai/.dockerignore`
- `services/mati-ai/.env.example`
- `services/mati-ai/Dockerfile`
- `services/mati-ai/config/external-sources.example.json`
- `services/mati-ai/scripts/benchmark.py`
- `services/mati-ai/scripts/prepare_models.py`
- `services/mati-ai/src/mati_ai/api/__init__.py`
- `services/mati-ai/src/mati_ai/api/dependencies.py`
- `services/mati-ai/src/mati_ai/api/v1/__init__.py`
- `services/mati-ai/src/mati_ai/api/v1/routes.py`
- `services/mati-ai/src/mati_ai/contracts/__init__.py`
- `services/mati-ai/src/mati_ai/contracts/common.py`
- `services/mati-ai/src/mati_ai/contracts/identification.py`
- `services/mati-ai/src/mati_ai/contracts/vision.py`
- `services/mati-ai/src/mati_ai/core/__init__.py`
- `services/mati-ai/src/mati_ai/core/config.py`
- `services/mati-ai/src/mati_ai/core/errors.py`
- `services/mati-ai/src/mati_ai/core/http.py`
- `services/mati-ai/src/mati_ai/core/model_manager.py`
- `services/mati-ai/src/mati_ai/enrichment/__init__.py`
- `services/mati-ai/src/mati_ai/enrichment/adapters/__init__.py`
- `services/mati-ai/src/mati_ai/enrichment/adapters/html.py`
- `services/mati-ai/src/mati_ai/enrichment/adapters/manual.py`
- `services/mati-ai/src/mati_ai/enrichment/api.py`
- `services/mati-ai/src/mati_ai/enrichment/contracts.py`
- `services/mati-ai/src/mati_ai/enrichment/ports.py`
- `services/mati-ai/src/mati_ai/enrichment/ranking.py`
- `services/mati-ai/src/mati_ai/enrichment/safe_fetch.py`
- `services/mati-ai/src/mati_ai/enrichment/service.py`
- `services/mati-ai/src/mati_ai/identification/__init__.py`
- `services/mati-ai/src/mati_ai/identification/codes.py`
- `services/mati-ai/src/mati_ai/identification/matching.py`
- `services/mati-ai/src/mati_ai/identification/normalization.py`
- `services/mati-ai/src/mati_ai/identification/ocr.py`
- `services/mati-ai/src/mati_ai/main.py`
- `services/mati-ai/src/mati_ai/publication/__init__.py`
- `services/mati-ai/src/mati_ai/publication/pipeline.py`
- `services/mati-ai/src/mati_ai/vision/__init__.py`
- `services/mati-ai/src/mati_ai/vision/attributes.py`
- `services/mati-ai/src/mati_ai/vision/classification.py`
- `services/mati-ai/src/mati_ai/vision/color.py`
- `services/mati-ai/src/mati_ai/vision/embeddings.py`
- `services/mati-ai/src/mati_ai/vision/quality.py`
- `services/mati-ai/src/mati_ai/vision/validation.py`
- `services/mati-ai/tests/conftest.py`
- `services/mati-ai/tests/test_core_api.py`
- `services/mati-ai/tests/test_enrichment.py`
- `services/mati-ai/tests/test_enrichment_api.py`
- `services/mati-ai/tests/test_identification.py`
- `services/mati-ai/tests/test_model_integration.py`
- `services/mati-ai/tests/test_vision.py`
- `tests/Feature/MatiAiWorkflowTest.php`
- `tests/Unit/MatiAiClientTest.php`
- `tests/frontend/garmentAssistant.test.mjs`

## 14. Archivos modificados

- `.env.example`
- `.gitignore`
- `app/Models/Prenda.php`
- `app/Services/Superadmin/Resources/ProductCatalogService.php`
- `app/Services/Superadmin/Resources/ResourceValidationService.php`
- `app/Services/Superadmin/Resources/ResourceWriteService.php`
- `compose.yaml`
- `resources/js/Pages/Superadmin/ProductCreate.tsx`
- `routes/web.php`
- `services/mati-ai/README.md`
- `services/mati-ai/pyproject.toml`
- `services/mati-ai/src/mati_ai/__init__.py`
- `services/mati-ai/uv.lock`

Las listas corresponden al inventario Git al cierre. Los archivos nuevos permanecen sin seguimiento porque no se hizo commit ni staging.

## 15. Limitaciones conocidas

- No se certifica precisión ni funcionamiento de la versión final sin ejecutar la validación del administrador.
- Deben configurarse el token compartido, aplicarse la migración y prepararse pesos/cachés antes de activar todas las funciones.
- No hay fuentes externas reales activas; el scraping necesita configuración por dominio autorizado.
- La similitud interna de fotos antiguas requiere indexación explícita; no se ejecutó una indexación masiva.
- La comparación visual externa requiere embeddings compatibles suministrados por la fuente configurada; el adaptador HTML no calcula embeddings de todas las fotos remotas.
- Las heurísticas de fondo, color, calidad y zero-shot necesitan evaluación con prendas reales. Varias prendas, texto pequeño o etiquetas curvas pueden causar abstención.
- La composición OCR queda como evidencia para revisión; no se crean materiales maestros automáticamente.
- El catálogo de candidatos internos se acota; los códigos exactos se consultan antes en el catálogo completo.
- Un único worker mantiene caché, pacing y modelo en memoria. Escalar a varios workers requerirá coordinar esos recursos.
- El descarte de indexaciones duplicadas se realiza tras inferir con la revisión real; ahorra registros duplicados, no necesariamente inferencia.
- No se implementa scraping de contenido que requiera JavaScript, login o evasión de anti-bot.

## 16. Decisiones técnicas

Se integró el formulario real `ProductCreate`, que el código comparte entre alta y edición. Se añadieron cuatro tablas IA y un campo opcional de modelo, conservando el esquema comercial. Los resultados son evidencia y no fuente de verdad automática.

Se eligió SigLIP principal, CLIP opcional, EasyOCR CPU y ZXing-C++; modelos cacheados y preparados fuera de requests. La visión externa se deja compatible con embeddings versionados, sin descarga masiva de candidatos.

Se eligió HTTPS exclusivamente para las fuentes externas, configuración cerrada por operador y fallo conservador. La UI sirve referencias externas por Laravel y conserva metadatos de descarga sin bytes en la auditoría. La publicación exige una segunda acción humana y conserva el original privado.

Sail mantiene sus servicios existentes; `mati-ai` se activa bajo perfil opcional `ai`, sin puerto público. Los overrides GPU y localhost son separados. El funcionamiento manual queda desacoplado del servicio IA y de su auditoría.

## 17. Siguientes fases

Fuera del alcance de esta entrega: entrenamiento/fine-tuning, calibración sobre dataset etiquetado, tendencias, forecasting, reposición, cross-selling, outfits, recomendaciones, virtual try-on, talla inteligente y RAG. No se inició ninguna de esas implementaciones.

## 18. Git diff --stat

El comando describe archivos ya seguidos por Git. Los archivos nuevos sin seguimiento aparecen en los apartados 13 y 19, no en este diff.

```text
 .env.example                                       |   7 +
 .gitignore                                         |  13 +
 app/Models/Prenda.php                              |   1 +
 .../Superadmin/Resources/ProductCatalogService.php |  31 ++-
 .../Resources/ResourceValidationService.php        |  22 +-
 .../Superadmin/Resources/ResourceWriteService.php  |   1 +
 compose.yaml                                       |  25 ++
 resources/js/Pages/Superadmin/ProductCreate.tsx    |  36 ++-
 routes/web.php                                     |  14 ++
 services/mati-ai/README.md                         |  57 +++++
 services/mati-ai/pyproject.toml                    |  10 +-
 services/mati-ai/src/mati_ai/__init__.py           |   6 +-
 services/mati-ai/uv.lock                           | 261 +++++++++++++++++++++
 13 files changed, 461 insertions(+), 23 deletions(-)
```

## 19. Git status

Captura al cierre, incluyendo esta documentación. No se creó commit ni se hizo push.

```text
On branch mejora/observaciones-defensa
Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git restore <file>..." to discard changes in working directory)
	modified:   .env.example
	modified:   .gitignore
	modified:   app/Models/Prenda.php
	modified:   app/Services/Superadmin/Resources/ProductCatalogService.php
	modified:   app/Services/Superadmin/Resources/ResourceValidationService.php
	modified:   app/Services/Superadmin/Resources/ResourceWriteService.php
	modified:   compose.yaml
	modified:   resources/js/Pages/Superadmin/ProductCreate.tsx
	modified:   routes/web.php
	modified:   services/mati-ai/README.md
	modified:   services/mati-ai/pyproject.toml
	modified:   services/mati-ai/src/mati_ai/__init__.py
	modified:   services/mati-ai/uv.lock

Untracked files:
  (use "git add <file>..." to include in what will be committed)
	DOCUMENTACION/IA/IMPLEMENTACION/
	app/Console/
	app/Http/Controllers/Superadmin/AiController.php
	app/Models/AiHumanReview.php
	app/Models/AiRun.php
	app/Models/AiSuggestion.php
	app/Services/AI/
	compose.ai-dev.yaml
	compose.ai-gpu.yaml
	config/mati_ai.php
	database/migrations/2026_09_28_000001_create_catalog_ai_tables.php
	resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx
	resources/js/lib/garmentAssistant.ts
	resources/js/types/productEditor.ts
	services/mati-ai/.dockerignore
	services/mati-ai/.env.example
	services/mati-ai/Dockerfile
	services/mati-ai/config/
	services/mati-ai/scripts/
	services/mati-ai/src/mati_ai/api/
	services/mati-ai/src/mati_ai/contracts/
	services/mati-ai/src/mati_ai/core/
	services/mati-ai/src/mati_ai/enrichment/
	services/mati-ai/src/mati_ai/identification/
	services/mati-ai/src/mati_ai/main.py
	services/mati-ai/src/mati_ai/publication/
	services/mati-ai/src/mati_ai/vision/
	services/mati-ai/tests/
	tests/Feature/MatiAiWorkflowTest.php
	tests/Unit/MatiAiClientTest.php
	tests/frontend/garmentAssistant.test.mjs

no changes added to commit (use "git add" and/or "git commit -a")
```

## 20. Confirmaciones

- Laravel conserva la autoridad comercial y persiste los valores confirmados mediante su flujo habitual.
- React llama únicamente a Laravel; no llama directamente a FastAPI.
- FastAPI no escribe inventario.
- FastAPI no escribe ventas.
- FastAPI no escribe reservas.
- FastAPI no escribe pagos.
- FastAPI no escribe Kardex.
- El flujo manual está implementado para continuar cuando la IA falla; su funcionamiento final queda para la validación del administrador.
- No se hizo commit.
- No se hizo push.
- No se reescribieron los documentos V2 ni se inició trabajo fuera del módulo solicitado.
