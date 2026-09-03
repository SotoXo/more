# Glosario técnico V2

**Fecha:** 2026-08-03  
**Propósito:** lenguaje común para negocio, desarrollo y evaluación

## A

### Abstención

Decisión explícita de no predecir o recomendar cuando la evidencia/confianza no alcanza. Es una salida correcta (`insufficient_evidence`), no un error técnico. Requiere un fallback manual.

### Algoritmo determinista

Proceso que, con la misma entrada y configuración, produce la misma salida sin aprender de datos. Ejemplos: validar checksum EAN, redimensionar, calcular SHA-256 o aplicar una regla de punto de reorden.

### Apriori

Algoritmo para descubrir conjuntos frecuentes y reglas de asociación en cestas. Requiere soporte suficiente; con pocas ventas produce reglas frágiles.

### ARIMA/SARIMA

Familia estadística para series temporales que modela autocorrelación, diferenciación y errores móviles; SARIMA incorpora estacionalidad. Necesita historia y diagnóstico adecuados. No es una red neuronal.

## B

### Backtesting temporal

Evaluación que simula decisiones históricas: en cada fecha de corte se usa únicamente información disponible hasta entonces y se compara el pronóstico con el futuro observado. Evita splits aleatorios inválidos para series.

### Baseline

Método simple contra el que debe competir un modelo: predicción naive, popularidad, regla o SQL. Un sistema complejo no se activa si no mejora un baseline relevante.

### Beam search

Búsqueda combinatoria que conserva las mejores `B` soluciones parciales en cada paso. Puede construir outfits por roles sin enumerar todas las combinaciones. No es por sí misma IA entrenada.

### Boosting

Familia de ML que combina muchos árboles débiles de manera secuencial (por ejemplo XGBoost/LightGBM). Suele funcionar bien en datos tabulares, pero requiere suficientes filas, control de fuga, tuning y evaluación.

## C

### Calibración

Correspondencia entre confianza predicha y frecuencia real. Si 100 casos tienen confianza 0.8, aproximadamente 80 deberían ser correctos. Una clasificación puede tener buen top-1 y mala calibración.

### CLIP

Modelo multimodal que aprende un espacio común para imágenes y texto. Permite comparar una imagen con prompts y generar embeddings. Sus variantes visuales ViT no son CNN clásicas. Puede usarse zero-shot sin entrenar en Mati, pero debe evaluarse en el dominio/idioma.

### CNN (Convolutional Neural Network)

Red neuronal que usa convoluciones para aprender patrones espaciales como bordes, texturas y formas. ResNet, EfficientNet y MobileNet son CNN. Reutilizar una CNN preentrenada mediante transfer learning requiere entrenamiento propio parcial; usarla solo con pesos existentes para features no.

### Cold start

Problema de recomendar a un usuario sin historia o un producto sin interacciones. Se resuelve inicialmente con contexto, popularidad acotada, atributos, embeddings, novedades y preferencias explícitas.

### Confianza

Señal cuantitativa de certeza del pipeline. No siempre es probabilidad calibrada. Debe indicar cómo se obtuvo y no usarse como verdad.

### ConvNeXt

Arquitectura convolucional moderna que adopta decisiones de diseño contemporáneas y compite con transformers visuales. Sigue siendo una red basada en convoluciones, no un Vision Transformer.

### Croston/SBA/TSB

Métodos para demanda intermitente. Croston separa tamaño de demanda no cero e intervalo; SBA corrige parte de su sesgo; TSB actualiza la probabilidad de ocurrencia y puede reaccionar mejor a obsolescencia. No convierten una serie de siete días en una predicción confiable.

### Cross-selling

Recomendación de un producto complementario al contexto o compra, no de un sustituto similar. Combina roles, reglas, stock, precio y señales de cesta futuras.

## D

### DBSCAN

Clustering basado en densidad capaz de marcar ruido y descubrir grupos no esféricos. Depende mucho de escala y parámetros; no requiere elegir `k`, pero sí datos suficientes.

### Deep Learning

Subcampo de ML con redes neuronales de múltiples capas y alta capacidad. CNN, Vision Transformers, LSTM y modelos de difusión son deep learning. No es automáticamente mejor para datos pequeños.

### Detector de objetos

Modelo que localiza objetos mediante cajas y clases. YOLO, RT-DETR y Faster/Mask R-CNN son ejemplos. Un detector general no conoce automáticamente las categorías finas de Mati Boutique.

### Difusión (modelo de)

Modelo generativo que aprende a revertir progresivamente ruido para producir una imagen condicionada. Muchos VTO modernos usan difusión. Puede sintetizar detalles o artefactos; no preserva necesariamente el producto con exactitud.

## E

### Embedding

Vector numérico que representa contenido/semántica. Imágenes o textos parecidos suelen quedar cercanos según coseno o distancia. Vectores de versiones/modelos distintos no se mezclan sin validación.

### Ensemble

Combinación de varios modelos o señales. Puede mejorar robustez, pero eleva costo/operación. V2 prefiere señales justificadas antes de cargar varios modelos grandes.

### EOQ

Cantidad económica de pedido. Fórmula clásica que balancea costo de ordenar y mantener inventario bajo supuestos fuertes. Requiere demanda/costos; no es calculable responsablemente si esos datos faltan.

### ETS/Holt-Winters

Modelos de suavizamiento exponencial para nivel, tendencia y estacionalidad. Holt maneja nivel+tendencia; Holt-Winters añade estacionalidad. Necesitan varios ciclos para estimar patrones estacionales.

## F

### Feature

Variable usada por una regla o modelo: precio, color, días con stock, embedding, recencia. Debe existir en la fecha de corte para evitar fuga.

### Fine-tuning

Continuación del entrenamiento de un modelo preentrenado con datos propios. Es una forma de transfer learning. Requiere dataset, GPU/proceso de entrenamiento, test bloqueado, model registry y rollback.

### FP-Growth

Algoritmo para conjuntos frecuentes que evita generar tantos candidatos como Apriori. Su eficiencia no corrige falta de cestas; soporte/estabilidad siguen siendo obligatorios.

## G

### GTIN/EAN/UPC

Estándares/formatos de identificación de artículos comerciales. El código contiene normalmente una llave, no toda la ficha. Un checksum válido prueba estructura, no la identidad material del candidato. La información se recupera de catálogos internos, proveedor o GS1 autorizado.

## H

### HNSW/IVFFlat

Índices aproximados de vecinos en espacios vectoriales. Aceleran búsqueda grande a costa de memoria/configuración/recall. Con 13 prendas es mejor la búsqueda exacta.

## I

### IA (Inteligencia Artificial)

Término amplio para sistemas que realizan tareas asociadas a percepción, predicción, recomendación o generación. Incluye reglas, búsqueda, ML y deep learning según el uso; Mati Boutique no tiene una «IA única».

### Idempotencia

Propiedad que permite reintentar una solicitud sin duplicar job, artefacto o evento. Se implementa con una clave y constraints/lifecycle claros.

### Isolation Forest

Modelo no supervisado que aísla observaciones atípicas mediante árboles aleatorios. Señala anomalías, no fraude/causa. Requiere suficientes datos normales representativos.

## K

### K-Means

Algoritmo de clustering que asigna puntos a `k` centroides. No es red neuronal. V2 puede usarlo para agrupar colores o, con datos suficientes, explorar clientes. Requiere escala, elección de `k` y validación.

### KNN (K-Nearest Neighbors)

Método que recupera los `k` elementos más cercanos según una distancia. No es red neuronal y puede no requerir entrenamiento paramétrico. En V2 ayuda a buscar prendas similares; no decide por sí solo complementos/outfits.

## L

### Learning-to-rank (LTR)

ML que aprende a ordenar candidatos a partir de exposiciones y outcomes. Requiere logging correcto de impresiones, sesgo de posición, split temporal y baseline. No es viable con los eventos actuales.

### LLM

Modelo de lenguaje de gran escala que genera/comprende texto. En RAG debe estar acotado por recuperación, permisos, citas y herramientas seguras; no es fuente de verdad comercial.

### LSTM/GRU

Redes neuronales recurrentes para secuencias. Pueden modelar series temporales, pero demandan suficientes datos y no se recomiendan hoy para Mati Boutique.

## M

### Machine Learning (ML)

Métodos que aprenden patrones desde datos para predecir, clasificar, agrupar o rankear. Regresión, árboles, K-Means y redes neuronales pertenecen al campo, aunque no todos son deep learning.

### Market Basket Analysis

Análisis de productos presentes en una misma cesta. Produce soporte, confianza y lift. No demuestra causalidad ni compatibilidad estética.

### MASE

Error absoluto escalado respecto a un pronóstico naive. Permite comparar series de escalas distintas si el denominador es válido. Debe acompañarse de sesgo/cobertura.

### Multimodal

Modelo/sistema que relaciona más de una modalidad, por ejemplo imagen y texto. CLIP/SigLIP son multimodales; comparar una foto con la frase «una blusa floral» es una operación multimodal.

## N

### Naive forecast

Baseline que usa último valor, valor estacional previo u otra regla mínima. Es obligatorio: un modelo sofisticado debe demostrar mejora real sobre él.

### Newsvendor

Modelo de inventario de un período que equilibra costo de faltante y sobrante bajo incertidumbre. Puede servir para moda estacional/lotes, pero requiere distribución y costos confiables.

## O

### OCR

Reconocimiento óptico de caracteres. Convierte regiones de imagen en texto. Tesseract, EasyOCR, PaddleOCR y TrOCR son opciones distintas. OCR no valida semántica: su salida se normaliza, contrasta y revisa.

### Outfit

Conjunto de prendas con roles complementarios y restricciones de color, estilo, ocasión, temporada, presupuesto, talla y stock. No es una lista de productos similares.

## P

### Predicción frente a verdad

La predicción es salida versionada de un algoritmo. La verdad confirmada es la decisión/dato aprobado por una persona o evento validado. Nunca se sobrescribe la predicción con la corrección; se relacionan para auditoría.

### Precision@K / Recall@K

Métricas de ranking: precision mide cuántos de los `K` resultados son relevantes; recall, cuántos relevantes posibles se recuperan. Deben definirse labels y contexto.

### Prompt zero-shot

Texto usado para describir una clase a un modelo multimodal, por ejemplo «una fotografía de un abrigo». Cambiar idioma/plantilla cambia resultados; se versiona y evalúa.

### Punto de reorden (ROP)

Umbral de posición de inventario: demanda esperada durante lead time más stock de seguridad. Sin lead time/variabilidad no es una recomendación completa.

## R

### RAG (Retrieval-Augmented Generation)

Arquitectura que recupera fragmentos de un corpus autorizado y los da a un modelo generativo para responder. Su valor proviene de evidencia/citas y control de acceso. No entrena necesariamente el LLM ni garantiza verdad; debe abstenerse si no hay respaldo.

### Random Forest

Ensemble de árboles entrenados sobre muestras/features aleatorias. Útil en datos tabulares y no lineales; no extrapola tendencias como un modelo temporal por defecto.

### RFM

Segmentación por recencia, frecuencia y valor monetario. Puede empezar como reglas/percentiles sin entrenamiento. Requiere clientes identificados y semántica de compra válida.

## S

### Segmentación

Asignación de una etiqueta/máscara a píxeles. Puede separar prenda/fondo o instancias. SAM2 es promptable/general; Mask R-CNN puede producir máscaras por instancia. Segmentar no clasifica automáticamente el tipo exacto.

### SigLIP

Modelo multimodal imagen-texto entrenado con una pérdida sigmoide. Sus variantes ViT son Vision Transformers, no CNN. V2 lo propone como candidato zero-shot inicial sujeto a benchmark, licencia/revisión y hardware.

### Similitud coseno

Compara el ángulo entre vectores. En embeddings normalizados, mayor coseno suele indicar mayor similitud semántica. No demuestra identidad ni complementariedad.

### Stock de seguridad

Inventario adicional para cubrir variabilidad de demanda/lead time a un nivel de servicio. Depende de supuestos y datos; no debe fijarse como constante universal.

### SSRF

Server-Side Request Forgery: una URL hostil hace que el servidor solicite recursos internos o sensibles. Se mitiga con allowlist, validación de IP/DNS/redirecciones, límites y egress.

## T

### Taxonomía

Vocabulario controlado y relaciones para categoría, color, patrón, estilo, rol, etc. Se versiona porque es parte del contrato y de las etiquetas del modelo.

### Temporal Fusion Transformer (TFT)

Red profunda para pronóstico multi-horizonte con covariables, selección de variables y atención. Requiere mucha más historia/variedad que la disponible; es candidato futuro, no recomendación actual.

### Time series / serie temporal

Observaciones ordenadas por tiempo. El orden, frecuencia, estacionalidad, interrupciones y fecha de corte importan; no debe evaluarse con split aleatorio convencional.

### Transfer Learning

Reutilización de un modelo preentrenado en una tarea/dominio propio, congelando o ajustando capas. Si se ajustan pesos, requiere entrenamiento propio. En V2 entra después de zero-shot, feedback y dataset versionado; solo se activa si mejora métricas.

### Transformer

Arquitectura basada en atención. Puede procesar texto, imágenes o series. Que un modelo use transformer no garantiza mejor rendimiento con pocos datos.

### TrOCR

Modelo transformer de reconocimiento de texto a partir de recortes. Necesita detección/regiones adecuadas y no reemplaza por sí solo un pipeline de layout/normalización.

## V

### Vision Transformer (ViT)

Modelo visual que divide la imagen en patches/tokens y usa atención. No es CNN clásica. CLIP/SigLIP pueden usar un encoder ViT.

### Virtual try-on (VTO)

Generación de una imagen sintética aproximando una prenda sobre la foto de una persona. No garantiza talla ni fidelidad y requiere consentimiento, advertencias, retención breve, licencia comercial y recursos aislados.

### VRAM

Memoria de la GPU. Pesos, activaciones, atención, VAE y pre/postprocesamiento consumen VRAM. «Modelo de 2 GB» no significa que use solo 2 GB en inferencia.

## W

### WAPE

Suma de errores absolutos dividida por demanda total. Es interpretable a nivel agregado, pero falla si el denominador es cero y puede ocultar series pequeñas.

## Z

### Zero-shot

Uso de un modelo preentrenado para una tarea/clase sin entrenarlo con ejemplos propios de esa clase en Mati. Requiere prompts, taxonomía y evaluación local. No significa cero configuración ni exactitud garantizada.

## Técnicas que requieren o no entrenamiento propio

| Técnica V2 | ¿Entrenamiento propio inicial? | Comentario |
|---|---:|---|
| MIME/hash/checksum/resize/reglas | No | determinista |
| blur/exposición/histograma/color K-Means | No | umbrales/paleta sí se calibran |
| SigLIP/CLIP zero-shot/embedding | No | benchmark/prompts obligatorios |
| KNN/coseno exacto | No | necesita embeddings/labels para evaluar |
| Tesseract/decoder barcode | No | preprocesamiento/diccionario y benchmark |
| detector preentrenado general | No para inferencia | utilidad fashion puede ser insuficiente |
| detector/segmentador fashion propio | Sí | cajas/máscaras anotadas |
| ResNet/EfficientNet/ConvNeXt fine-tuned | Sí | transfer learning con dataset propio |
| SQL/RFM/reglas de outfit/reposición | No | parámetros de negocio aprobados |
| Apriori/FP-Growth | No entrenamiento paramétrico | descubre patrones desde cestas; requiere volumen |
| K-Means de clientes | Ajusta centroides | aprendizaje no supervisado desde datos propios |
| Random Forest/boosting/LTR | Sí | labels/features/split temporal |
| naive/ETS/ARIMA/Croston | Ajuste sobre serie | no deep learning; necesitan historia/evaluación |
| LSTM/GRU/TFT | Sí | gran volumen y costo; no ahora |
| VTO con pesos existentes | No entrenamiento para inferir | licencia, QA y hardware siguen obligatorios |
| VTO fine-tuned | Sí | alto costo, dataset/licencia/privacidad |
| RAG básico | No necesariamente | se indexa corpus; LLM puede quedar preentrenado |
| RAG/LLM fine-tuned | Sí | no es requisito para primera versión |
