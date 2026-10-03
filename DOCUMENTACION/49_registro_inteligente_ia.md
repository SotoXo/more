# Registro inteligente de prendas: resumen y funcionamiento

**Proyecto:** MORE / Mati Boutique  
**Documento creado:** 3 de octubre de 2026  
**Alcance:** módulo de registro inteligente, identificación y enriquecimiento de prendas.

Este módulo ayuda al superadministrador a registrar y editar prendas a partir de fotografías, códigos y etiquetas. Analiza la información y presenta sugerencias para revisar. El administrador decide qué aplicar y guarda la ficha mediante el formulario habitual.

La implementación está integrada en el sistema real. La activación y la validación final siguen los pasos indicados más adelante; este documento no afirma que se hayan ejecutado esas operaciones.

## 1. Dónde se utiliza

El asistente aparece dentro del formulario de alta y edición de prendas del panel superadmin. Su integración está en `resources/js/Pages/Superadmin/ProductCreate.tsx`, compartido por ambos formularios.

Permite proporcionar:

| Entrada | Para qué sirve |
|---|---|
| Fotografía principal | Sugerir categoría, color, paleta y patrón; obtener calidad y similitud |
| Fotografía del código | Leer EAN, UPC, CODE128 o QR |
| Fotografía de etiqueta | Extraer texto, marca, talla, modelo, códigos y composición |
| Código escrito | Buscar SKU o código de barras en el catálogo interno |
| Marca y modelo conocidos | Orientar la identificación y la búsqueda externa |
| Foto existente al editar | Analizar una portada local sin volver a subirla |

Las imágenes de prenda, código y etiqueta pueden ser diferentes. No es necesario que todos los datos estén en una sola fotografía.

## 2. Cómo se comunican las partes

```text
Administrador en el formulario React
                |
                v
Laravel: autentica, autoriza y prepara los datos reales
                |
                | HTTP interno con Bearer Token
                v
FastAPI / mati-ai: analiza imágenes, códigos y texto
                |
                | JSON con resultados y advertencias
                v
Laravel: conserva resultados y prepara sugerencias
                |
                v
React: presenta resultados para revisión humana
                |
                | Aplicar, corregir o rechazar
                v
Formulario editable
                |
                | Guardar prenda
                v
Laravel: persiste los valores finales e imágenes aprobadas
```

**Laravel conserva la autoridad comercial.** Consulta PostgreSQL, proporciona los catálogos existentes, controla los permisos y guarda la ficha confirmada.

React llama únicamente a Laravel. FastAPI recibe bytes, taxonomías y candidatos por HTTP; no tiene acceso directo a la base comercial ni escribe inventario, ventas, reservas, pagos o Kardex.

## 3. Qué ocurre al analizar una foto

1. Laravel recibe la fotografía o lee la portada local existente.
2. Envía la imagen y las categorías, colores y patrones activos del catálogo.
3. Python comprueba formato real, decodificación, tamaño, dimensiones y cantidad de píxeles.
4. Corrige orientación EXIF en una copia, normaliza a RGB/sRGB y calcula hashes.
5. Evalúa resolución, desenfoque, exposición y contraste.
6. Extrae una paleta y la compara con los colores proporcionados por Laravel.
7. El modelo visual compara la imagen con textos de las categorías y patrones existentes.
8. Devuelve resultados, advertencias, versión del modelo y tiempos de procesamiento.
9. Laravel añade coincidencias internas y posibles duplicados.
10. El administrador revisa qué sugerencias aplicar.

El original se conserva. Los hashes permiten identificar archivos idénticos; el embedding permite comparar características visuales.

### Categoría y patrón

El modelo principal es **SigLIP**, con el checkpoint `google/siglip-base-patch16-224`. Utiliza clasificación *zero-shot*: compara la foto con descripciones de las categorías enviadas por Laravel, sin entrenar un modelo propio para Mati.

Se muestran las categorías con mayor similitud. Si la evidencia es insuficiente, el sistema puede abstenerse. La taxonomía comercial sigue siendo la existente, incluida una categoría como POLERA si está activa.

CLIP (`openai/clip-vit-base-patch32`) queda disponible como alternativa configurable. Se mantiene un solo modelo visual por worker para controlar memoria y VRAM.

### Colores

OpenCV trabaja en el espacio de color Lab y realiza cuantización. El resultado incluye color dominante, secundarios, paleta y proporciones aproximadas.

Los colores se relacionan con los IDs del catálogo mediante distancia perceptual. El fondo y la iluminación pueden afectar la lectura; por eso se muestran advertencias y la decisión final permanece editable.

**Los scores expresan similitud o coincidencia. No son porcentajes de certeza.**

## 4. Cómo funcionan barcode y QR

La sección de código permite subir una fotografía y, cuando el dispositivo lo admite, usar una captura móvil.

OpenCV y ZXing-C++ leen razonablemente EAN-13, EAN-8, UPC-A, UPC-E, CODE128 y QR. Devuelven la simbología, contenido original, valor normalizado, región detectada y comprobación de checksum cuando corresponde.

Laravel busca primero el código en Mati Boutique. Si existe una coincidencia interna por identificador, se presenta esa ficha antes de consultar fuentes externas.

Un checksum válido indica que los dígitos cumplen la regla del formato; no demuestra por sí solo que la prenda sea auténtica o que corresponda al producto esperado.

Si un QR contiene una URL, se muestra como texto y se marca que contiene una dirección web. **No se visita ni descarga automáticamente.**

## 5. Cómo funciona la lectura de etiquetas

El administrador carga una foto específica de la etiqueta. EasyOCR reconoce español e inglés en CPU y conserva el texto original.

La normalización limpia espacios y Unicode y extrae candidatos de:

- Marca y talla, comparadas con los catálogos que proporciona Laravel.
- Modelo o referencia del proveedor.
- SKU visible y GTIN.
- Composición y porcentajes.
- Texto adicional para revisión.

Por ejemplo, una etiqueta con:

```text
ZARA
ART: 1234/567
M
95% COTTON
5% ELASTANE
```

puede producir candidatos de marca ZARA, talla M, modelo 1234/567 y composición 95 % algodón / 5 % elastano.

El administrador puede aplicar marca, talla y modelo si son compatibles. La composición se presenta como evidencia para revisión; no crea materiales automáticamente. El modelo del proveedor se guarda en `modelo_pre` y no reemplaza el SKU generado por el sistema.

## 6. Cómo encuentra prendas existentes o similares

Laravel prepara candidatos del catálogo; Python puede ordenarlos mediante matching estructurado. La prioridad de evidencia es:

```text
SKU exacto
  → GTIN válido
  → modelo o referencia
  → marca + modelo
  → texto
  → embedding visual
  → categoría y color
```

Los códigos exactos se consultan en el catálogo completo antes de formar el conjunto acotado de candidatos de similitud.

Un conflicto de GTIN, marca o modelo penaliza la coincidencia. Una fotografía parecida no basta para afirmar que se trata de la misma prenda.

Los embeddings son vectores numéricos de características visuales. Se comparan mediante similitud coseno cuando el modelo y su revisión son compatibles. Se conservan para uso interno y no se envían como miles de números al formulario.

Las fotos antiguas pueden incorporarse mediante el comando explícito de indexación descrito en la sección de mantenimiento. No se ejecuta una indexación masiva automáticamente.

## 7. Cómo funciona el enriquecimiento externo

Después de comprobar el catálogo interno, el administrador puede solicitar información externa con GTIN, marca, modelo o texto.

Existen dos adaptadores:

| Adaptador | Funcionamiento |
|---|---|
| `ManualCatalogAdapter` | Consulta un catálogo o feed curado en la configuración del operador |
| `HtmlCatalogAdapter` | Descarga HTML permitido y extrae productos mediante selectores configurados para una fuente |

La prioridad de fuentes favorece proveedor, marca oficial, API autorizada y catálogo público permitido. Los resultados incluyen título, marca, modelo, GTIN, fuente, URL de producto, imágenes, derechos, componentes del score y conflictos.

**La entrega no incluye una tienda real habilitada.** El archivo `services/mati-ai/config/external-sources.example.json` contiene plantillas desactivadas. Para activar una fuente se requiere configurar dominios exactos, búsqueda, selectores, términos revisados y derechos con evidencia real.

No es una búsqueda abierta en cualquier página de Internet. No utiliza scraping de Instagram ni intenta eludir CAPTCHA, login, robots o protecciones anti-bot. El adaptador HTML trabaja con páginas estáticas; no ejecuta JavaScript remoto.

La comparación visual externa solo se utiliza si una fuente suministra embeddings precomputados compatibles. El adaptador HTML no analiza automáticamente todas las fotos remotas.

## 8. Referencias externas y publicación de imágenes

Las tarjetas permiten revisar la fuente y utilizar información compatible. Las fotos externas se sirven a través de Laravel, mediante resultados persistidos del mismo usuario y descarga segura en FastAPI.

| Estado de derechos | Uso permitido por el módulo |
|---|---|
| `owned` | Publicación con aprobación |
| `licensed` | Publicación con evidencia y aprobación |
| `provider_authorized` | Publicación con evidencia y aprobación |
| `public_domain` | Publicación con evidencia y aprobación |
| `unknown` | Referencia, sin publicación |
| `prohibited` | Bloqueado |

Al descargar una referencia se conservan procedencia, hash, dimensiones, fecha y derechos. Los bytes no se almacenan en PostgreSQL.

Cuando una imagen está aprobada, el pipeline:

```text
orienta → convierte a sRGB → elimina EXIF → redimensiona
        → genera thumbnail/card/detail → codifica WebP → calcula hashes
```

Los tamaños máximos de los perfiles son 240, 640 y 1600 píxeles, conservando proporción y evitando ampliaciones innecesarias.

El original permanece privado. La preparación genera un token temporal asociado al administrador, válido durante 24 horas. La imagen se adjunta a la prenda cuando se guarda el formulario.

No se aplican generación de fondo, inpainting, superresolución ni cambios generativos al color de la prenda.

## 9. Aplicación de sugerencias y feedback humano

El administrador puede aplicar un campo individual o los campos compatibles. Las sugerencias de color, talla y código se aplican a la variante seleccionada.

El asistente comprueba que los IDs pertenezcan al catálogo activo y que la talla sea compatible con el perfil de categoría/segmento. Antes de sustituir un valor ya informado, presenta una advertencia para que el reemplazo sea explícito. Después de aplicar, el formulario sigue siendo editable.

Laravel conserva:

| Tabla | Información |
|---|---|
| `ai_model_versions` | Modelo, revisión y versión de pipeline |
| `ai_runs` | Solicitud, operación, usuario, estado, hashes, resultado y embedding interno |
| `ai_suggestions` | Predicción original, campo, score, fuente y evidencia |
| `ai_human_reviews` | Aceptación, corrección o rechazo, valor confirmado, usuario y etapa |

La predicción original no se sobrescribe. Se diferencia la decisión tomada en el formulario del valor finalmente guardado.

Si el feedback llega después del guardado, Laravel obtiene la comparación final desde los valores comerciales persistidos. `form_review_id` enlaza esa revisión con la decisión original para evitar duplicarla.

El frontend serializa el envío de decisiones y conserva pendientes por usuario/formulario en sessionStorage durante 24 horas para poder reintentarlas. Cuando ese almacenamiento no está disponible, la cola permanece en memoria.

## 10. Seguridad y comportamiento ante fallos

Las rutas Laravel requieren autenticación y rol superadmin. La comunicación interna utiliza un Bearer Token configurable; un token vacío no habilita acceso al servicio.

La descarga externa exige HTTPS y dominios permitidos. Se bloquean redes privadas, localhost, direcciones de metadata y esquemas como file, ftp, gopher o data. Se revalidan DNS y redirecciones y se conecta a la IP pública autorizada conservando el nombre TLS del host.

También se limitan bytes, formato, tipo de contenido, redirecciones y tiempos. Se respetan robots, intervalos de consulta y bloqueos por exceso de solicitudes.

Ante un servicio apagado, timeout, respuesta inválida o modelo ausente, se muestra un aviso y permanece disponible el registro manual. Sin pesos visuales, el análisis puede conservar resultados deterministas como calidad y color y abstenerse de clasificar.

Los fallos de auditoría IA se aíslan del guardado comercial mediante transacción/savepoint. Los logs técnicos incluyen identificador de solicitud, operación, estado, duración y código de error; no incluyen tokens, imágenes o texto OCR completo.

## 11. Rutas principales

El formulario consume rutas Laravel bajo `/superadmin/ai`:

| Método | Ruta | Función |
|---|---|---|
| GET | `/capabilities` | Capacidades y configuración disponible |
| POST | `/analyze` | Foto principal o portada existente |
| POST | `/barcode` | Lectura de código |
| POST | `/label` | OCR de etiqueta |
| POST | `/match` | Coincidencias internas |
| POST | `/external-search` | Fuentes externas permitidas |
| POST | `/prepare-publication` | Preparar una imagen aprobada |
| POST | `/feedback` | Registrar la decisión humana |
| GET | `/external-reference/{run}/{candidate}/{image}` | Servir una referencia externa |
| GET | `/prepared-images/{token}` | Vista previa privada de una imagen preparada |

FastAPI ofrece `/health`, `/api/v1/capabilities`, `/api/v1/garments/analyze`, `/api/v1/identification/barcode`, `/api/v1/identification/label`, `/api/v1/products/match`, `/api/v1/products/external-search`, `/api/v1/products/external-image` y `/api/v1/images/prepare-publication`.

Los resultados identifican solicitud, versión de contrato, estado, advertencias, abstenciones, modelo, revisión, pipeline y tiempos. Los estados permiten distinguir resultado completo, parcial, evidencia insuficiente, degradación o fallo.

## 12. Configuración y puesta en marcha

Los siguientes comandos son instrucciones para el administrador; no se ejecutaron al crear este resumen.

### Configuración Laravel

```dotenv
MATI_AI_ENABLED=true
MATI_AI_URL=http://mati-ai:8000
MATI_AI_TOKEN=REEMPLAZAR_POR_UN_SECRETO_COMPARTIDO
MATI_AI_TIMEOUT=30
MATI_AI_CONNECT_TIMEOUT=3
```

El marcador de token debe sustituirse por un secreto real mantenido fuera de Git. Laravel y FastAPI deben recibir el mismo token.

La migración nueva es `database/migrations/2026_09_28_000001_create_catalog_ai_tables.php`. Añade las tablas IA y `prendas.modelo_pre`. Para aplicar esa migración mediante Sail, con Laravel y PostgreSQL ya disponibles:

```bash
docker compose exec -T laravel.test php artisan migrate --path=database/migrations/2026_09_28_000001_create_catalog_ai_tables.php
docker compose exec -T laravel.test php artisan config:clear
```

### Arranque con Docker/Sail

Desde la raíz del proyecto:

```bash
docker compose --profile ai build mati-ai
docker compose --profile ai run --rm mati-ai /app/.venv/bin/python scripts/prepare_models.py --vision --ocr
docker compose --profile ai up -d mati-ai
```

El perfil `ai` activa el servicio opcional. Laravel usa `http://mati-ai:8000` dentro de la red Sail. El volumen `mati-ai-cache` conserva los pesos y recursos OCR para evitar descargas durante las solicitudes.

Para usar GPU, el entorno necesita acceso NVIDIA compatible; el override es:

```bash
docker compose -f compose.yaml -f compose.ai-gpu.yaml --profile ai up -d mati-ai
```

Para exponer el servicio únicamente en localhost durante desarrollo:

```bash
docker compose -f compose.yaml -f compose.ai-dev.yaml --profile ai up -d mati-ai
```

Ese override publica `127.0.0.1:8001`. FastAPI no se expone públicamente en la configuración base.

### Arranque directo del servicio Python

Desde `services/mati-ai`, usando Python 3.11 y `uv`:

```bash
uv sync --frozen
uv run python scripts/prepare_models.py --vision --ocr
uv run uvicorn mati_ai.main:app --host 127.0.0.1 --port 8001 --workers 1 --no-access-log
```

Preparación y servicio deben utilizar el mismo usuario y rutas de caché. La URL Laravel debe ser alcanzable desde donde corre Laravel: en un contenedor Sail, `127.0.0.1` apunta al propio contenedor, no al host.

Variables Python relevantes:

| Variable | Función |
|---|---|
| `MATI_AI_TOKEN` | Secreto compartido con Laravel |
| `MATI_AI_MODEL_PROVIDER` | siglip o clip |
| `MATI_AI_VISION_MODEL` | Modelo visual opcional personalizado |
| `MATI_AI_MODEL_REVISION` | Revisión del checkpoint; fijar commit para reproducibilidad |
| `MATI_AI_DEVICE` | auto, cpu o cuda |
| `MATI_AI_LOCAL_FILES_ONLY` | true para usar pesos preparados |
| `MATI_AI_OCR_DOWNLOAD_ENABLED` | false durante operación normal |
| `HF_HOME` | Caché Hugging Face |
| `MATI_AI_OCR_MODEL_DIRECTORY` | Caché de recursos OCR |
| `MATI_AI_EXTERNAL_SOURCES_FILE` | Archivo de fuentes autorizadas |

EasyOCR trabaja en CPU y el modelo visual puede usar GPU o CPU. No se cargan SigLIP y CLIP simultáneamente como modelos permanentes.

Para fuentes externas, copiar y completar la plantilla en un archivo local `external-sources.json`, configurar su ruta y reiniciar el worker. El archivo local está excluido de Git.

## 13. Mantenimiento

### Incorporar fotos antiguas a la similitud visual

```bash
docker compose exec -T laravel.test php artisan mati-ai:index-catalog --limit=100 --after-id=0
```

Procesa portadas locales y vincula embeddings reales con las prendas sin aplicar sugerencias ni modificar su ficha. Requiere tablas IA, token y modelo disponibles. Admite `--force`.

La comparación por hash/modelo/revisión ocurre después de recibir el análisis, por lo que una repetición puede volver a inferir aunque no inserte un registro duplicado.

### Limpiar preparaciones temporales vencidas

```bash
docker compose exec -T laravel.test php artisan mati-ai:cleanup-prepared --limit=500
```

Elimina únicamente los archivos temporales vencidos de preparaciones no adjuntadas. Conserva los registros de auditoría. No se añadió una limpieza automática programada.

## 14. Ejemplos de uso y límites actuales

**Foto de un pantalón beige:** el administrador analiza la portada, revisa categorías y colores sugeridos, aplica lo compatible y completa la ficha. El ejemplo ilustra el flujo; no garantiza una categoría concreta para cualquier fotografía.

**Código de barras:** se lee y se consulta Mati. Si existe coincidencia interna, se revisa esa prenda. Si no existe, puede iniciarse la búsqueda en fuentes autorizadas.

**Etiqueta:** se extraen marca, modelo, talla y composición. El administrador revisa el texto original antes de aplicar campos.

**Foto + etiqueta + fuente externa:** la visión aporta apariencia, el OCR aporta identificadores y la búsqueda aporta una ficha candidata. El administrador compara las evidencias y decide qué datos e imágenes utilizar.

La implementación requiere validación final por el administrador. La entrega previa dejó esa validación, la aplicación de la migración y la activación pendientes; este resumen no comprueba su estado operativo actual.

Las limitaciones principales son fondos complejos, varias prendas en una foto, iluminación irregular, etiquetas curvas o pequeñas, ausencia de pesos y fuentes externas sin configurar. No hay mediciones finales completas de inferencia/VRAM ni una precisión calibrada para el catálogo.

No se incluyeron tendencias, forecasting, reposición, recomendaciones, outfits, virtual try-on, talla inteligente, RAG ni entrenamiento.

## 15. Dónde consultar más detalle

- [Guía técnica de implementación](IA/IMPLEMENTACION/01_registro_inteligente.md): arquitectura, contratos, configuración, seguridad y operación.
- [Informe de entrega](IA/IMPLEMENTACION/02_entrega_y_estado.md): los 20 puntos solicitados, archivos, decisiones y estado de validación al cierre del módulo.
- [README del servicio Python](../services/mati-ai/README.md): preparación, arranque y comandos disponibles para el administrador.
- `app/Services/AI/`: integración Laravel, catálogo, auditoría y publicación.
- `services/mati-ai/src/mati_ai/`: análisis, identificación, enriquecimiento y preparación de imágenes.
