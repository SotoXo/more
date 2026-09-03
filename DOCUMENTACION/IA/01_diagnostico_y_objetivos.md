# Diagnóstico y objetivos de la fase inteligente

Fecha de análisis: 2026-08-03  
Proyecto: Mati Boutique (`~/Code/more`)  
Alcance: análisis en lectura y diseño; no se implementó ningún módulo inteligente.

## 1. Resumen ejecutivo

Mati Boutique tiene una base comercial suficientemente estructurada para comenzar una fase inteligente asistiva, pero todavía no tiene un sistema de IA. Laravel ya resuelve autenticación, roles, catálogo, variantes, imágenes, inventario, Kardex, ventas, reservas, pagos, favoritos, reportes y auditoría. `services/mati-ai` existe como proyecto Python aislado y su entorno real reconoce una RTX 5060 con aproximadamente 8 GB de VRAM, pero su código contiene únicamente un `main()` de ejemplo: no hay aplicación FastAPI, endpoints, configuración, modelos cargados, pruebas ni integración con Laravel.

La primera inversión útil no es entrenar una red propia ni crear un asistente RAG. Es incorporar un registro asistido de prendas que analice una portada antes del guardado y entregue sugerencias no vinculantes: validez/calidad de imagen, color dominante, top-3 de categorías y posible duplicado. La pantalla actual ya tiene revisión humana, portada obligatoria, galería, variantes y selección manual de color; por ello la IA puede integrarse como ayuda sin desplazar las reglas comerciales existentes.

El catálogo visual disponible en el repositorio es demasiado pequeño y heterogéneo para entrenamiento propio. Se localizaron 13 imágenes de producto actuales, con tres pares exactamente duplicados por SHA-256, tamaños entre 563×563 y 2048×3072, fondos blancos, fotos editoriales con modelo y escenas donde aparecen varias prendas. Un backup del 2026-06-04 contiene solo 6 prendas y 6 imágenes; el diccionario de datos declara haber sido verificado contra PostgreSQL el 2026-06-17, pero la base viva no pudo consultarse porque Docker no estaba disponible durante esta auditoría. Los conteos deben considerarse una fotografía del repositorio, no un censo definitivo de producción.

## 2. Fuentes inspeccionadas y nivel de evidencia

| Fuente | Hallazgo | Nivel de evidencia |
|---|---|---|
| Modelos Eloquent en `app/Models` | Entidades y relaciones comerciales reales | Código actual |
| 40 migraciones y `diccionariodb.md` | Esquema, llaves, índices y campos aplicados | Código + diccionario verificado al 2026-06-17 |
| Servicios `ProductCatalogService`, `ProductImageService`, `ResourceValidationService`, `ResourceWriteService` | Flujo real de alta/edición, validación y almacenamiento de imágenes | Código actual |
| `ProductCreate.tsx` y `ProductColorPicker.tsx` | Formulario por pasos, revisión final y muestreo manual de color | Código actual |
| Servicios de dashboard y reportes | Analítica descriptiva ya disponible | Código actual |
| `storage/app/public/productos` | 13 imágenes locales; resolución y duplicados exactos | Archivos actuales |
| Backup `mati_boutique_backup_20260604_185732.sql` | Snapshot histórico: 6 prendas, 6 variantes, 6 imágenes, 4 favoritos, 6 ventas y 8 reservas | Backup; no equivale a datos vivos |
| `services/mati-ai/pyproject.toml`, `.venv` y ejecución de diagnóstico | Versiones instaladas, CUDA y GPU | Entorno real actual |
| `compose.yaml`, `config/filesystems.php`, `config/queue.php` | PostgreSQL, Redis, storage público y cola Laravel disponibles; sin servicio `mati-ai` | Configuración actual |
| Documentación existente | Antecedentes de catálogo, color, inventario y reportes; no hay diseño IA vigente | Documentación actual |

Limitación de inspección: Docker Desktop no estaba activo y Docker no estaba integrado con WSL; no se ejecutaron consultas a PostgreSQL ni comandos de escritura. Para cualquier fase de implementación se deberá repetir un perfilado de datos de solo lectura contra una copia o réplica.

## 3. Estado actual detectado

### 3.1 Existe y es utilizable

- Laravel 13 con PHP 8.3, React/TypeScript e Inertia.
- PostgreSQL, Redis y tablas estándar de Laravel Queue declarados en `compose.yaml` y configuración.
- Roles `superadmin`, `vendedor` y `cliente`, con middleware de autorización.
- Catálogo normalizado en `prendas`, `prenda_variantes`, `imagenes_prenda`, `categorias`, `marcas`, `colores`, `tallas` y `temporadas`.
- Categorías con jerarquía opcional y perfil de tallaje (`tip_tal_cat`, `gru_tal_cat`).
- Colores con nombre, familia, HEX exacto y HEX dominante. El backend ya aproxima una muestra manual a familias y tonos mediante reglas HSL/distancia RGB.
- Variantes con color, talla, SKU, precio, costo, estado e inventario uno-a-uno.
- Imagen principal obligatoria en creación; galería, orden, fuente y vínculo opcional a variante.
- Validación Laravel de archivo `image` y límite de 6 MB.
- Imágenes guardadas en el disco `public`, bajo `storage/app/public/productos/{id}`, y expuestas por `/storage`.
- Selector React que promedia píxeles alrededor del punto elegido manualmente; es asistencia determinista, no detección automática.
- Favoritos por cliente con unicidad `(usuario, prenda)`.
- Ventas, reservas y sus líneas preservan variante, cantidad y precio; pueden alimentar analítica y perfiles cuando sus estados se filtren correctamente.
- Kardex en `inventario_movimientos`, con cantidades antes/después, tipo, referencia, usuario y metadata.
- Reportes descriptivos de ventas, reservas, pagos, inventario, clientes y catálogo; rankings de productos vendidos/reservados/favoritos y stock por categoría/marca/ubicación.
- Logs del sistema y auditoría con metadata JSON.
- Microservicio Python aislado con Python 3.11.15, `uv` y las dependencias declaradas.
- CUDA funcional desde PyTorch: RTX 5060, 8,546,484,224 bytes de memoria reportada y capacidad CUDA 12.0.

### 3.2 Existe parcialmente

| Capacidad | Estado parcial | Brecha para IA |
|---|---|---|
| Taxonomía de categorías | Hay 10 categorías canónicas en el seeder y jerarquía opcional | No hay alias, rol de outfit ni versión de taxonomía; un backup contiene `POLERA` fuera del conjunto canónico |
| Catálogo de colores | Familia/HEX y heurísticas manuales útiles | No hay distancia perceptual LAB, porcentajes multicolor ni segmentación del fondo |
| Calidad de imágenes | Portada y límite de peso obligatorios | No se guardan ancho/alto, hash, MIME detectado, EXIF, desenfoque, exposición ni estado de validación |
| Duplicados | La validación evita duplicado exacto de nombre+categoría+marca | No detecta archivos visualmente idénticos o prendas casi iguales; hay duplicados exactos en storage |
| Señales del cliente | Favoritos, reservas y compras | No hay eventos de vista, búsqueda, clic de recomendación, impresión, carrito o rechazo |
| Analítica | Reportes operativos y rankings | No hay capa de features, cohortes, demanda pronosticada ni evaluación de recomendaciones |
| Colas | Laravel Queue y Redis están configurables | No hay jobs de IA, estados de análisis ni política de reintentos/idempotencia |
| Microservicio | Entorno y dependencias existen | No hay servidor FastAPI, contratos, seguridad, logging, modelos ni pruebas |

### 3.3 No existe

- Tablas `estilos`, `interacciones_usuarios` o `detecciones_ia`, pese a que `PROJECT_OVERVIEW.md` antiguo las menciona. El código, migraciones y diccionario actual no las contienen.
- Campos canónicos de patrón, estilo, material, ocasión, tags o rol de outfit en `prendas`.
- Dataset versionado, conjunto de evaluación o muestras validadas por humanos.
- Embeddings, índice vectorial, `pgvector` o FAISS.
- OCR instalado o implementado.
- Sistema de recomendación, outfits o RAG.
- Cliente Laravel para FastAPI, token interno, endpoints o circuit breaker.
- Registro de versión de modelo, confianza, predicción original y corrección humana.
- Política de retención para imágenes temporales o resultados inteligentes.

## 4. Capacidades y restricciones reales del entorno inteligente

Versiones verificadas dentro de `.venv`:

| Componente | Versión/capacidad |
|---|---|
| Python | 3.11.15 |
| FastAPI | 0.141.1 |
| Pydantic / Settings | 2.13.4 / 2.14.2 |
| Pillow / OpenCV / NumPy | 12.3.0 / 5.0.0 / 2.4.6 |
| scikit-learn | 1.9.0 |
| PyTorch / Torchvision | 2.13.0+cu130 / 0.28.0+cu130 |
| Transformers | 5.14.1 |
| GPU | NVIDIA GeForce RTX 5060, capacidad 12.0 |
| VRAM reportada | ~7.96 GiB utilizables según PyTorch |

La GPU alcanza para inferencia y ajuste fino moderado de modelos base/pequeños con lotes reducidos y precisión mixta. No justifica cargar simultáneamente varios modelos grandes ni prometer alta concurrencia. La capacidad se deberá medir en el hardware real porque peso de checkpoints, memoria de activaciones, versión de CUDA, formato de precisión y tamaño de lote cambian el consumo.

## 5. Calidad y representatividad de las imágenes actuales

- Se encontraron 13 JPEG de producto, todos válidos estructuralmente según `file`.
- Resoluciones observadas: 563×563, 750×750, 850×1090, 850×1275, 1920×2880 y 2048×3072.
- Tres pares son archivos exactamente iguales: productos 2/4, 8/9 y 12/13.
- Hay fotografía de producto aislado sobre fondo blanco, fotografía editorial con modelo y fondo exterior, y fotografía de un modelo que viste varias prendas.
- En escenas con modelo, el color global de la imagen no representa necesariamente a la prenda objetivo.
- En prendas claras sobre fondo blanco, quitar el fondo por umbral simple puede borrar partes del producto.
- La portada puede ser una imagen remota en datos demo o una ruta local; el diseño no debe depender de una ruta compartida entre servicios.
- No hay miniaturas normalizadas, máscara del producto, anotación de bounding box ni indicador de “una sola prenda”.

Conclusión: el conjunto sirve para construir casos de prueba iniciales, no para entrenar o estimar exactitud general. Antes de fijar metas se requiere un conjunto de evaluación estratificado y validado.

## 6. Necesidades previas a la IA

### Obligatorias antes del primer módulo

1. Aprobar una taxonomía cerrada y versionada para las categorías que el modelo podrá sugerir, con alias en español/inglés y regla para categorías no soportadas.
2. Definir el contrato de sugerencia: una predicción nunca reemplaza el valor confirmado y siempre conserva modelo, versión, confianza y fecha.
3. Definir imágenes aceptadas por contenido real: MIME decodificable, píxeles máximos, dimensiones mínimas, orientación, eliminación de EXIF y retención temporal.
4. Crear una línea base de evaluación con imágenes reales representativas, incluidas imágenes malas, fondos complejos, prendas claras y múltiples prendas.
5. Resolver antes de una instalación limpia la duplicidad de migraciones que agregan `temporadas.fec_ini_tem` y `fec_fin_tem`; no afecta el diseño lógico de IA, pero sí la reproducibilidad del entorno.

### Recomendadas

- Normalizar categorías que no pertenecen al catálogo canónico y evitar duplicados semánticos.
- Agregar alias y familia perceptual al catálogo de colores, conservando el color comercial confirmado.
- Guardar SHA-256, dimensiones y MIME detectado de cada imagen; generar miniatura normalizada.
- Definir una guía de captura: una prenda objetivo, encuadre completo, luz uniforme y fondo contrastante cuando sea posible.
- Centralizar estados comerciales para que analytics filtre ventas/reservas válidas sin strings dispersos.
- Definir una política de consentimiento y minimización si alguna imagen contiene personas.

### Futuras

- Campos canónicos de patrón, estilo, material y ocasión solo después de acordar vocabularios y demostrar uso comercial.
- Eventos de catálogo (vista, búsqueda, clic, impresión y conversión) con consentimiento y retención.
- `pgvector` cuando los embeddings y búsqueda similar tengan aceptación demostrada.
- Almacenamiento de objetos con URL firmada si Laravel y GPU se separan físicamente.
- Registro central de modelos y despliegue canary cuando exista más de una versión productiva.

### Innecesarias por ahora

- Data lake, feature store dedicado, Kubernetes, streaming de eventos o base vectorial independiente.
- Celery además de Laravel Queue para el MVP.
- Modelo generativo grande local para escribir descripciones.
- Entrenamiento desde cero.
- Acceso de FastAPI a tablas comerciales.

## 7. Objetivo general técnico

Construir una plataforma inteligente asistiva, versionada y observable que mejore la calidad y velocidad del registro, búsqueda y recomendación de prendas sin transferir a la IA la autoridad sobre catálogo confirmado, usuarios, inventario, Kardex, ventas, reservas o pagos, y que convierta correcciones humanas en evidencia segura para mejoras posteriores.

## 8. Objetivos específicos

1. Definir contratos versionados Laravel–FastAPI con autenticación interna, idempotencia, errores estables y trazabilidad extremo a extremo.
2. Incorporar análisis de imagen previo al guardado que valide decodificación, dimensiones, calidad, exposición, fondo y presencia de casos no aptos.
3. Sugerir top-3 de categorías y colores mapeados al catálogo real, mostrando confianza y permitiendo aceptar, ignorar o corregir cada atributo.
4. Detectar duplicados exactos y candidatos visuales similares sin bloquear automáticamente el alta.
5. Registrar predicción, modelo, versión, resultado confirmado y acción humana sin mezclar sugerencias con datos comerciales definitivos.
6. Crear un dataset versionado únicamente con muestras revisadas, dividido por prenda y con prevención de fuga entre entrenamiento y evaluación.
7. Evolucionar recomendaciones desde reglas y contenido hacia perfiles híbridos solo cuando existan señales y métricas suficientes.
8. Proporcionar analítica descriptiva y predicción de demanda de forma gradual, separando claramente cálculo, regla y modelo estadístico.

## 9. Objetivos por fase

| Fase | Objetivo medible |
|---|---|
| IA-0 a IA-2 | Contrato estable, comunicación autenticada y fallos de FastAPI sin impedir el alta manual |
| IA-3 | Línea base de calidad/color con dataset de evaluación y tiempos CPU/GPU medidos |
| IA-4 a IA-5 | Top-3 zero-shot visible, validación humana y feedback persistente con versiones completas |
| IA-6 | Búsqueda similar con evaluación de `Recall@K`, restricciones de categoría y cobertura del catálogo |
| IA-7 a IA-9 | Reglas/analítica y recomendaciones de contenido comparadas contra una línea base de popularidad |
| IA-10 a IA-11 | Outfits y ajuste fino solo con taxonomías/muestras suficientes y evaluación offline aprobada |
| IA-12 | RAG limitado a fuentes autorizadas y consultas de solo lectura, si demuestra valor adicional |

## 10. Indicadores de cumplimiento

No se fijan umbrales numéricos definitivos antes de medir. Cada indicador tendrá una línea base en el conjunto de evaluación y, tras un piloto, una meta aprobada por negocio y tecnología.

| Indicador | Cómo se calcula | Línea base necesaria | Uso |
|---|---|---|---|
| Latencia total p50/p95/p99 | Desde Laravel antes de llamar hasta respuesta validada | Por módulo, CPU/GPU y tamaño de imagen | SLO y timeout |
| Tiempo de preproceso/inferencia/postproceso | Métricas FastAPI separadas | Por versión de pipeline | Optimización |
| Tasa de imágenes válidas | Válidas / analizadas, por motivo de rechazo | Piloto representativo | Mejorar captura |
| Top-1 y Top-3 de categoría | Coincidencia con categoría humana validada | Dataset estratificado | Comparar CLIP/SigLIP/futuro fine-tuning |
| Macro-F1 por categoría | F1 sin favorecer categorías abundantes | Soporte mínimo por categoría | Detectar sesgo |
| Coincidencia de color | Familia y tono confirmado frente a sugerido | Casos con iluminación/fondo variados | Calibrar LAB y reglas |
| Error de porcentaje de color | Diferencia frente a anotación humana | Subconjunto multicolor | Estampados |
| Aceptación de sugerencias | Aceptadas sin cambio / mostradas | Por atributo y versión | Valor real del asistente |
| Magnitud de corrección | Distancia taxonómica o cambio de valor | Por atributo | Calidad y ambigüedad |
| Precisión de duplicado | Candidatos correctos / candidatos mostrados | Pares positivos/negativos | Ajustar umbral |
| Recall@K / nDCG@K | Relevantes recuperados y calidad del ranking | Juicios humanos o interacción válida | Similares/recomendaciones |
| Cobertura del catálogo | Ítems elegibles recomendados / ítems activos | Por categoría y talla | Evitar concentración |
| Diversidad y novedad | Distribución de categorías/marcas y antigüedad | Baseline de popularidad | Evitar monotonía |
| Tasa de error | 4xx/5xx/timeouts/fallback por total | Por endpoint/versión | Fiabilidad |
| Disponibilidad | Solicitudes atendibles / ventana | Health sintético | Operación |
| VRAM pico y promedio | Métrica del proceso GPU | Modelo, batch y concurrencia | Capacidad |
| Fallback a CPU | Solicitudes degradadas / total | Por causa | Alertas |
| Drift | Cambio de distribución de categorías/confianza | Ventanas comparables | Reentrenamiento |

## 11. Limitaciones conocidas

- No se conoce aún el volumen real ni la distribución actual de la base productiva.
- El conjunto local no representa prendas femeninas, accesorios, vestidos, blusas y otras categorías de forma suficiente.
- Categoría, estilo, patrón y material pueden ser ambiguos incluso para humanos; la confianza no debe presentarse como probabilidad calibrada hasta medirla.
- Marca, talla, composición y lavado normalmente requieren una fotografía específica de etiqueta; no deben inferirse de la portada.
- Temporada depende de decisión comercial y fechas, no solo de apariencia visual.
- Un fondo o una segunda prenda puede dominar K-Means; se necesita máscara/fallback manual.
- Las recomendaciones sufrirán cold start y sesgo mientras solo existan favoritos, compras y reservas.
- EOQ necesita costos de ordenar/mantener y lead time, datos que no existen en el esquema actual.
- La RTX 5060 de 8 GB no permite asumir múltiples workers GPU; la concurrencia debe limitarse y medirse.
- La primera descarga de checkpoints requiere internet; producción debe usar artefactos fijados y precargados.
- Documentación histórica puede estar desactualizada: `PROJECT_OVERVIEW.md` menciona tablas que no existen; el código y migraciones prevalecen.

## 12. Conclusión diagnóstica

El proyecto está listo para iniciar IA-0/IA-1 y preparar un MVP asistivo, no para entrenar un modelo propio ni automatizar decisiones. El valor inmediato está en aumentar la consistencia del catálogo y capturar feedback. El éxito debe medirse por aceptación/corrección humana y calidad operativa, no por la cantidad de atributos que el sistema intente adivinar.
