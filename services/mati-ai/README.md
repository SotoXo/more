# Mati AI — registro inteligente

Servicio privado Python 3.11 / FastAPI. Recibe taxonomías y candidatos desde Laravel y devuelve sugerencias. No tiene credenciales ni acceso a la base comercial.

La guía de instalación, contratos, fuentes, seguridad, límites y uso está en [01_registro_inteligente.md](../../DOCUMENTACION/IA/IMPLEMENTACION/01_registro_inteligente.md).

## Preparación y arranque

```bash
cd services/mati-ai
uv sync --frozen
# Proporcionar MATI_AI_TOKEN mediante el entorno o un archivo local excluido de Git.
# El mismo token se configura en Laravel; un token vacío cierra el acceso.
uv run python scripts/prepare_models.py --vision --ocr
uv run uvicorn mati_ai.main:app --host 127.0.0.1 --port 8001 --workers 1 --no-access-log
```

La preparación descarga los recursos seleccionados y muestra la revisión visual resuelta. Se puede usar solo `--vision` o `--ocr`, y guardar un informe con `--output /ruta/informe.json`. No procesa imágenes. EasyOCR inicializa su lector para preparar sus recursos en CPU; eso también ocupa memoria durante la preparación. Para una entrega reproducible, fijar `MATI_AI_MODEL_REVISION` al commit preparado. Conservar el mismo `HF_HOME` y `MATI_AI_OCR_MODEL_DIRECTORY` durante preparación y servicio.

La API utiliza caché local por defecto: `MATI_AI_LOCAL_FILES_ONLY=true` y `MATI_AI_OCR_DOWNLOAD_ENABLED=false`. Mantener un único worker visual evita duplicar el modelo en VRAM. Sin pesos, el análisis mantiene calidad, color y códigos y devuelve una abstención visual; el formulario permite continuar manualmente. EasyOCR funciona en CPU para no competir con el modelo visual. Los pesos y los informes locales no deben añadirse a Git.

Las dependencias nuevas para identificación son ZXing-C++ y EasyOCR; Beautiful Soup interpreta HTML permitido. SentencePiece y Protobuf son dependencias del tokenizador SigLIP. `uv.lock` fija versiones; no se descarga ni ejecuta código remoto de modelos.

## Contrato y límites

`GET /health` es una comprobación ligera sin cargar modelos. Los endpoints `/api/v1/*` requieren el token del servicio, usan identificadores de solicitud y devuelven errores sin trazas, contenido OCR ni secretos. Laravel controla autenticación de usuario, taxonomías, permisos, persistencia y aprobación humana.

El middleware ASGI cuenta los bytes realmente recibidos, incluidos cuerpos por fragmentos o con un `Content-Length` falso, antes del parser JSON/multipart. El máximo de solicitud es el límite de imagen más 1 MB para campos; la imagen decodificada mantiene sus límites propios de tamaño, píxeles y formato. El servicio no acepta rutas de archivos proporcionadas por clientes HTTP.

Las fuentes externas permanecen desactivadas hasta configurar dominios, permiso de uso, términos revisados y un adaptador. Consultar la guía antes de habilitarlas. Las URLs QR solo son datos y no se visitan automáticamente.

## Medición opcional por el administrador

Después de preparar los pesos, el administrador puede medir imágenes reales de su entorno:

```bash
uv run python scripts/benchmark.py \
  --image /ruta/prenda.jpg \
  --ocr-label /ruta/etiqueta.jpg \
  --barcode-image /ruta/codigo.jpg \
  --device cuda --device cpu --samples 3 \
  --output /ruta/benchmark-mati-ai.json
```

Solo `--image` y `--output` son obligatorios. Este script fuerza caché local y desactiva descargas OCR. Registra carga, inferencia, dispositivo realmente utilizado, VRAM máxima, color y las operaciones opcionales de etiqueta/código. Una caída a CPU queda reflejada en el dispositivo real; no se presenta como medición CUDA. Las operaciones no disponibles producen un informe parcial y salida 1. No guarda texto OCR, valores de códigos ni bytes de imágenes en el informe.

## Comandos de validación para el administrador

```bash
uv run pytest -m "not model_integration"
uv run ruff check src tests
uv run mypy src
# Solo con pesos preparados y aceptación explícita del administrador:
MATI_AI_RUN_MODEL_TESTS=1 uv run pytest -m model_integration
```

Estos comandos no se ejecutaron al cerrar esta entrega, por instrucción del usuario. Los tests escritos no equivalen a una validación final. El test de integración real tiene un marcador separado; las mediciones de producción requieren fotografías y hardware del entorno de destino.
