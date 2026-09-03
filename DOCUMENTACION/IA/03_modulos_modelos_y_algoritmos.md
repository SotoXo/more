# Módulos, modelos y algoritmos

Fecha: 2026-08-03  
Regla de lectura: “estimación” indica una conclusión que todavía no fue medida con imágenes de Mati Boutique.

## 1. Estrategia de modelos

El catálogo actual no justifica entrenamiento desde cero. La estrategia recomendada es:

1. algoritmos deterministas para seguridad, calidad, color y duplicados exactos;
2. modelo imagen-texto zero-shot para clasificación y embeddings;
3. validación humana y dataset versionado;
4. búsqueda/recomendación basada en contenido;
5. transfer learning cuando haya suficientes muestras independientes por clase;
6. modelos propios/híbridos solo si superan de forma reproducible las líneas base.

El checkpoint inicial propuesto es `google/siglip-base-patch16-224`, fijado a una revisión concreta durante IA-4. Transformers lo soporta como modelo imagen-texto y su artefacto safetensors publicado ronda 813 MB; cabe holgadamente en 8 GB para inferencia de lote 1, pero el consumo real de VRAM y la latencia deben medirse. La alternativa de referencia será `openai/clip-vit-base-patch32`, cuyo peso PyTorch publicado ronda 605 MB. CLIP se usará como baseline, no como verdad: su propio model card exige evaluación específica para taxonomías fijas y señala limitaciones de idioma, por lo que los prompts internos se probarán en inglés y español y el usuario verá etiquetas en español.

Fuentes técnicas primarias/ oficiales consultadas:

- [Documentación SigLIP en Transformers](https://huggingface.co/docs/transformers/main/model_doc/siglip)
- [Checkpoint SigLIP base patch16 224](https://huggingface.co/google/siglip-base-patch16-224)
- [Checkpoint y model card CLIP ViT-B/32](https://huggingface.co/openai/clip-vit-base-patch32)
- [Modelos oficiales Torchvision](https://docs.pytorch.org/vision/stable/models/)
- [pgvector](https://github.com/pgvector/pgvector)
- [FAISS](https://github.com/facebookresearch/faiss)

## 2. Comparación de modelos visuales

Los consumos son cualitativos o tamaños publicados, no benchmarks de la RTX 5060.

| Opción | Función | Ventajas | Desventajas | Dataset propio | Consumo/dificultad | Adecuación 8 GB | Recomendación |
|---|---|---|---|---|---|---|---|
| SigLIP base P16 224 | Zero-shot, embeddings imagen/texto | Licencia Apache-2.0 del checkpoint; buena interfaz imagen-texto; embeddings reutilizables | Scores sigmoides no son probabilidades calibradas; más pesado que CLIP B/32; requiere prompts/evaluación local | No para comenzar | ~813 MB de pesos publicados; media | Sí, lote pequeño | **Principal provisional del MVP** |
| CLIP ViT-B/32 | Zero-shot y embeddings | Muy conocido, simple, peso menor, baseline sólido | Model card limita despliegue sin prueba de dominio; entrenamiento principalmente inglés; menor detalle por patch 32 | No para comenzar | ~605 MB de pesos; baja-media | Sí | Baseline obligatorio A/B offline |
| ResNet18/50 | Clasificación supervisada | Estable, simple de ajustar, rápida | Etiquetas ImageNet no corresponden al catálogo; sin texto zero-shot; necesita dataset Mati | Sí | ResNet18 publicado ~44.7 MB; baja-media | Sí | Transfer learning futuro/base simple |
| EfficientNet-B0 | Clasificación supervisada eficiente | Buen balance tamaño/cómputo; ~20.5 MB publicados | Necesita etiquetas; no produce compatibilidad texto natural | Sí | Bajo; media | Sí | Candidato futuro cuando haya dataset |
| MobileNetV3 small/large | Clasificador liviano/CPU | Muy bajo consumo; small ~9.8 MB publicados | Menor capacidad esperada para categorías visualmente cercanas | Sí | Muy bajo; baja-media | Sí, incluso CPU | Fallback/edge futuro, no primera elección de calidad |
| ConvNeXt-Tiny | Clasificación supervisada/embeddings | Backbone moderno y fuerte | ~109 MB publicados y más cómputo; sin zero-shot; riesgo de sobreajuste con pocos datos | Sí | Medio-alto; media-alta | Sí, lotes moderados | Candidato futuro si dataset crece |
| Modelo entrenado desde cero | Clasificador propio | Control total | Requiere gran volumen/diversidad, costo de etiquetado y alto riesgo de sobreajuste | Mucho | Muy alto | Técnicamente posible, injustificado | No recomendado |

La exactitud ImageNet publicada por Torchvision no predice calidad en Mati Boutique y no se reutiliza como meta. EfficientNet/MobileNet/ConvNeXt solo deben compararse mediante transfer learning con el mismo split, augmentations y evaluación.

## 3. Registro asistido de prendas mediante imágenes

### Propósito

Reducir trabajo repetitivo y mejorar consistencia sin guardar nada automáticamente.

### Pipeline recomendado

```text
bytes
→ validación/decodificación segura
→ orientación y copia RGB normalizada
→ métricas de calidad/fondo
→ hash exacto y perceptual
→ máscara/ROI aproximada
→ paleta LAB y mapeo a colores del catálogo
→ SigLIP: embedding + top-k de taxonomía
→ reglas de compatibilidad del catálogo
→ respuesta con scores/advertencias
```

### Atributos por confiabilidad

| Atributo | MVP | Método | Observación |
|---|---|---|---|
| Categoría/tipo | Sí, top-3 | SigLIP + prompts + categorías activas | Solo vocabulario aprobado; abstenerse con ambigüedad |
| Color dominante/secundarios | Sí | K-Means/quantización LAB sobre ROI y mapeo ΔE | Confirmación manual obligatoria |
| Calidad/fondo | Sí | Reglas deterministas | Advertencias, no un score opaco único |
| Duplicado exacto/visual | Exacto sí; visual candidato | SHA-256/dHash + embedding coseno | Nunca bloquear solo por embedding |
| Tags | Pocos y controlados | Zero-shot multilabel con vocabulario cerrado | No texto libre en primera versión |
| Patrón | Piloto posterior | Zero-shot: liso, rayas, cuadros, floral, gráfico | Necesita dataset específico y casos multicolor |
| Estilo/ocasión | Posterior | Reglas + zero-shot | Altamente subjetivo; taxonomía inexistente hoy |
| Temporada | No en MVP | Regla comercial + apariencia como señal secundaria | Las fechas y decisión de colección prevalecen |
| Material | No en MVP | OCR de composición o foto macro; visual solo como hipótesis | No afirmar “algodón/lana” desde portada |
| Marca | No desde portada | OCR/logo en foto específica | No inferir por apariencia |
| Talla | No desde portada | OCR de etiqueta y validación contra tallas | Visualmente imposible de forma fiable |
| Descripción preliminar | Futuro | Plantilla controlada desde atributos confirmados | Evitar LLM en MVP; no inventar material |

### Reglas de abstención

- imagen corrupta/no soportada;
- menos de la resolución mínima;
- varias prendas sin objetivo claro;
- score sin separación suficiente entre top-1/top-2;
- categoría fuera de la taxonomía;
- fondo domina la paleta o máscara inestable;
- color cercano a dos catálogos sin diferencia perceptual clara.

La abstención es una salida válida y debe medirse.

## 4. Clasificación visual

### Fase zero-shot

- Generar varios prompts por categoría, por ejemplo “a catalog photo of a blouse”, “una foto de catálogo de una blusa”.
- Mantener alias por categoría: `POLOS Y KNITWEAR` necesita prompts separados para polo, camiseta, suéter/jersey/knitwear; una única etiqueta compuesta degrada interpretabilidad.
- Promediar embeddings de prompts o comparar estrategias; documentar `prompt_set_version`.
- Calcular top-k y margen; no llamar “95% exactitud” a un softmax de candidatos.
- Aplicar reglas: género no debe eliminar categorías; grupo de talla puede ayudar a detectar incompatibilidades, no decidir.

### Transfer learning

Debe iniciarse solo cuando:

- cada categoría prioritaria tenga suficientes prendas distintas, no solo muchas fotos de la misma prenda;
- haya diversidad de fondo, luz, ángulo y persona/maniquí;
- el split sea por `prenda_id`, nunca por imagen, evitando que fotos de una misma prenda caigan en train y test;
- las etiquetas tengan revisión humana y guía de ambigüedad;
- un backbone ajustado supere SigLIP/CLIP en macro-F1, calibración y latencia con intervalo de confianza.

Comenzar congelando backbone y entrenando cabeza; después descongelar bloques finales con learning rate bajo. EfficientNet-B0 o ResNet18 son primeras bases por simplicidad; ConvNeXt-Tiny si el volumen demuestra beneficio. MobileNet si CPU/latencia domina.

### Modelo propio

“Propio” debería significar fine-tune reproducible sobre un backbone, no arquitectura entrenada desde cero. Debe versionar dataset, código, semilla, splits, pesos, métricas y taxonomía.

## 5. Detección de colores

### Pipeline recomendado

1. Aplicar orientación y convertir a sRGB/RGB controlado.
2. Reducir resolución conservando proporción.
3. Estimar fondo con bordes, conectividad y baja variación; si hay alpha, usarla.
4. Construir ROI del producto. En fondo complejo, usar heurística central y reportar baja confiabilidad; una segmentación aprendida se evalúa después.
5. Convertir píxeles de ROI a CIELAB.
6. Excluir píxeles extremos/transparentes según reglas cuidadosas, sin eliminar automáticamente blanco/negro de la prenda.
7. Muestrear píxeles de forma reproducible y ejecutar K-Means con `k` adaptativo pequeño (por ejemplo 2–5, elegido por ganancia/masa mínima), fijando semilla.
8. Fusionar clusters perceptualmente cercanos; calcular porcentaje sobre ROI.
9. Mapear cada centro al `hex_col`/familia activa por ΔE (CIEDE2000 si se implementa; ΔE76 como baseline).
10. Devolver HEX observado, LAB, porcentaje, color candidato y distancia; permitir corrección manual.

### Comparación

| Técnica | Uso | Ventajas | Limitaciones | Recomendación |
|---|---|---|---|---|
| Promedio RGB | Muestra manual actual | Muy simple | Sombras/fondo mezclan; no perceptual | Conservar como fallback del selector |
| Cuantización Pillow/OpenCV | Paleta rápida | Determinista y barata | Menos control perceptual | Baseline CPU |
| K-Means RGB | Clusters de color | Disponible en scikit-learn | Distancias RGB no son perceptuales | Solo baseline |
| K-Means HSV | Separa tono/saturación | Útil para reglas de cromaticidad | Hue circular; inestable con baja saturación | Complemento de reglas |
| K-Means LAB | Paleta perceptual | Mejor para aproximar colores humanos | Depende de iluminación y perfil | **Principal** |

### Casos difíciles

- **Blanco sobre blanco:** preservar bordes/contraste y no descartar todos los píxeles claros; pedir clic manual si la máscara es pequeña.
- **Negro/sombras:** usar luminancia y textura; no convertir sombra en color secundario.
- **Estampado:** devolver varios clusters con porcentaje y tag `multicolor/pattern_candidate`.
- **Modelo con varias prendas:** calidad marca `multiple_garments_suspected`; el color automático queda “baja confianza”.
- **Iluminación desigual:** balance de blancos moderado y comparación con muestra manual; no prometer color exacto de producto.

## 6. Calidad de imagen y fondo

Métricas deterministas propuestas:

- dimensiones, relación de aspecto, bytes y megapíxeles;
- varianza del Laplaciano sobre luminancia como señal de desenfoque;
- percentiles/histograma de luminancia para sub/sobreexposición;
- contraste local/global;
- saturación extrema y clipping por canal;
- porcentaje estimado de fondo, entropía del borde y tamaño del ROI;
- orientación y contenido cerca de bordes;
- hash exacto/perceptual.

No combinar todo en un score universal antes de calibrar. Devolver `checks[]` con `pass|warning|fail`, valor, umbral/version y explicación. Los umbrales salen del dataset real.

## 7. OCR de etiquetas

OCR debe analizar una foto explícita de etiqueta, no ejecutarse por defecto sobre la portada.

| Opción | Ventajas | Desventajas/consumo | Adecuación | Recomendación |
|---|---|---|---|---|
| Tesseract | CPU, maduro, UTF-8 y 100+ idiomas; formatos estructurados | Sensible a calidad, perspectiva y texto pequeño; preprocesamiento manual | Excelente baseline barato | Primer benchmark OCR |
| EasyOCR | API sencilla, PyTorch/GPU, cajas+texto+confianza, 80+ idiomas | Dependencia no instalada; modelos se descargan; memoria GPU adicional | Compatible con stack PyTorch/8 GB | Piloto junto a Tesseract |
| PaddleOCR | Detección/rec. multilingüe y herramientas de producción | Introduce Paddle y dependencias grandes; posible fricción CUDA/RTX | Potente, mayor costo operativo | Evaluar si EasyOCR/Tesseract no alcanzan |
| TrOCR/Transformers | Reconocimiento end-to-end y fine-tuning | Normalmente necesita detector/crops; más pesado y sensible al dominio | Cabe en 8 GB según checkpoint/lote, a medir | Para crops de etiqueta y dataset propio futuro |

Referencias: [Tesseract](https://github.com/tesseract-ocr/tesseract), [EasyOCR](https://github.com/JaidedAI/EasyOCR), [PaddleOCR](https://github.com/PaddlePaddle/PaddleOCR), [TrOCR](https://huggingface.co/docs/transformers/model_doc/trocr).

### Extracción posterior al OCR

- **Marca:** fuzzy matching contra `marcas.nom_mar`, sin crear marca automáticamente.
- **Talla:** regex/normalización y validación por segmento/sistema/grupo.
- **Código:** patrones SKU/EAN definidos; checksum cuando aplique.
- **Composición:** diccionario de fibras y porcentajes que sumen coherentemente.
- **Lavado:** el texto puede capturarse; símbolos requieren clasificador específico, fuera del primer OCR.
- **Texto visible:** conservar bounding boxes/confianza y revisión humana.

Métricas: CER/WER, precisión/recall por campo, tasa de etiqueta detectada y aceptación humana. Privacidad: nunca enviar comprobantes al OCR de etiquetas.

## 8. Prendas visualmente similares

### Embeddings y ranking

- Usar el mismo encoder/versionado que clasificación para generar un vector L2-normalizado por imagen principal.
- Si hay galería, conservar embeddings por imagen y un agregado por prenda; no promediar sin evaluar.
- Similitud coseno como score base.
- Pre-filtrar categorías compatibles y estado activo; post-filtrar stock/precio en Laravel.
- Combinar de forma explicable:

```text
score = w_visual·coseno
      + w_color·compatibilidad_color
      + w_temporada·compatibilidad_temporada
      + w_precio·proximidad_precio
```

Los pesos se calibran con juicios humanos; no se inventan.

### Papel de KNN

KNN se recomienda como estrategia de recuperación, no como clasificador visual autónomo: `NearestNeighbors` de scikit-learn puede ejecutar búsqueda exacta con distancia coseno sobre embeddings L2-normalizados y devolver los K vecinos. No requiere entrenamiento ni dataset para funcionar, aunque sí un conjunto de pares relevantes/no relevantes para evaluar `Recall@K` y ajustar filtros. Su consumo es aproximadamente lineal con la cantidad de vectores en búsqueda exhaustiva, usa poca memoria con el catálogo actual, no necesita GPU y es fácil de ejecutar localmente. La calidad esperada depende del encoder y de la compatibilidad de categorías; por eso es apropiado para el prototipo, mientras pgvector o FAISS quedan como evolución cuando las mediciones de volumen y latencia lo justifiquen.

### Almacenamiento/índice

| Opción | Ventajas | Riesgos | Cuándo |
|---|---|---|---|
| NumPy en memoria, búsqueda exacta | Ya instalado; sencillo; exacto; ideal para decenas/centenas | Recarga, persistencia y múltiples procesos | Prototipo/benchmark inicial |
| PostgreSQL sin pgvector, vectores en JSON/array | Una sola DB | Búsqueda ineficiente y poca ergonomía | Solo almacenamiento transitorio, no ranking a escala |
| PostgreSQL + pgvector | Persistencia, joins/filtros, coseno, HNSW/IVFFlat | Extensión/migración/operación por aprobar; índices aproximados requieren tuning | **Recomendación de IA-6** |
| FAISS | Búsqueda eficiente, CPU/GPU, exacta/aproximada | No está instalado; índice/IDs/backup/sincronización aparte | Catálogo muy grande o servicio vectorial dedicado |
| Híbrido | pgvector como verdad + cache/FAISS | Doble consistencia | Solo escala demostrada |

Con 13 archivos actuales no se necesita índice aproximado. pgvector indica que `vector` admite hasta 2,000 dimensiones y soporta distancia coseno e índices HNSW/IVFFlat; los filtros con índices aproximados requieren evaluación de recall. FAISS usa float32 y ofrece coseno mediante producto interno sobre vectores normalizados.

### Evitar categorías incompatibles

- Matriz aprobada de categorías: misma categoría, categorías hermanas o grupos compatibles.
- “Accesorios” no compite con “Pantalones” salvo modo outfit.
- `CONJUNTOS` necesita tratamiento especial, no compararse ciegamente con partes individuales.
- Si el filtro deja menos de K resultados, ampliar de forma explícita y etiquetar `relaxed_filter=true`.

Métricas: Recall@K, Precision@K, mAP/nDCG, cobertura, tasa de clic y aceptación; dataset de pares positivos/negativos revisados.

## 9. Recomendaciones de productos

### Señales reales hoy

- Disponibles: favoritos, ventas, reservas, categoría, color, talla, marca, temporada, precio, fecha de alta y stock.
- No disponibles: vistas, búsquedas, impresiones, clic de recomendación, carrito persistido en servidor y rechazo explícito.
- Los estados comerciales deben filtrarse: no toda reserva/venta representa preferencia confirmada.

### Evolución

1. **Reglas/popularidad:** novedades, favoritos y unidades vendidas válidas, con disponibilidad y diversidad. No requiere ML.
2. **Contenido:** perfil promedio de embeddings/atributos de ítems favoritos/comprados; similitud coseno y reglas.
3. **Perfil de usuario:** ponderar señales por tipo/recencia; no usar talla como atributo inferido si no es consistente.
4. **Híbrido:** combinar contenido, popularidad, novedad y afinidades categóricas; reservar una fracción para exploración.
5. **Aprendizaje:** learning-to-rank o factorization solo con suficientes eventos e impresiones; evaluación offline y experimento controlado.

### Cold start

- Usuario nuevo: popularidad segmentada por catálogo/temporada, diversidad y novedades.
- Producto nuevo: contenido/embeddings y regla de exposición.
- Catálogo pequeño: reglas transparentes suelen superar complejidad innecesaria.

### Métricas

Offline: Precision@K, Recall@K, nDCG@K, MAP, coverage, diversity, novelty. Online futura: CTR sobre impresiones, favorite/add-to-cart/reservation/purchase rate, hide/skip, latencia y guardrails de stock. No se puede medir CTR sin registrar impresiones.

## 10. Recomendaciones de outfits

### Estado del catálogo

Las categorías sembradas incluyen abrigos, blazers, camisas, blusas, vestidos, faldas, pantalones, polos/knitwear, accesorios y conjuntos. No existe categoría de calzado; por tanto el sistema no debe prometer calzado. `gru_tal_cat` distingue `SUPERIOR`, `INFERIOR` y `GENERAL`, pero no representa rol de outfit de forma suficiente: abrigos y vestidos requieren roles propios.

### Diseño progresivo

1. Aprobar `outfit_role`: `top`, `bottom`, `outerwear`, `one_piece`, `accessory`, `footwear` (solo si aparece en catálogo).
2. Definir matriz de compatibilidad y exclusión: vestido puede reemplazar top+bottom; conjunto no se combina como una pieza simple.
3. Reglas de color basadas en familias/armonías, no HEX exacto únicamente.
4. Filtrar temporada, disponibilidad, tallas compatibles y presupuesto en Laravel.
5. Generar candidatos con beam search acotado, no producto cartesiano completo.
6. Rankear por compatibilidad de roles, color, estilo/ocasión confirmados, contenido visual y diversidad.
7. Capturar feedback por outfit completo y por pieza.

Antes de crear campos de estilo/ocasión, validar que el negocio realmente los usará y definir vocabulario. Los embeddings ayudan a similitud estética, pero no reemplazan reglas de composición.

## 11. Análisis de datos comerciales

### Nivel 1: descriptivo (ya posible)

- unidades/ingreso por prenda, variante, categoría, color, talla, marca y período;
- productos con mayor rotación y sin movimiento desde una fecha;
- stock disponible/reservado y cobertura aproximada;
- ventas/reservas canceladas o vencidas por estado;
- demanda por temporada y ubicación;
- cohortes básicas de clientes con compra/reserva.

Los servicios de reportes existentes ya calculan parte de esto. Primero se deben consolidar consultas y estados, no duplicar cifras en FastAPI.

### Nivel 2: reglas

- bajo stock por umbral configurable;
- días sin movimiento;
- demanda reciente mayor que stock;
- excepción de talla/color agotado dentro de una prenda activa;
- cancelación de reservas por encima de su propia línea base.

### Nivel 3: estadística/ML básico

- promedio móvil y naive estacional como baselines;
- suavizamiento exponencial si la serie tiene densidad suficiente;
- Croston/SBA para demanda intermitente por variante;
- regresión de conteo/árboles solo con features y volumen suficientes;
- intervalos/predicción por horizonte, no un único número sin incertidumbre.

### EOQ y reorden

EOQ requiere demanda, costo por orden y costo de mantener inventario; el punto de reorden requiere lead time y variabilidad. Hoy faltan proveedor, órdenes de compra, lead time y costos operativos. En la primera etapa solo se puede ofrecer apoyo descriptivo/escenarios con parámetros manuales, claramente etiquetado como cálculo, no predicción.

### Evaluación

- backtesting temporal (nunca shuffle aleatorio);
- MAE/WAPE y sesgo, por horizonte y nivel de agregación;
- comparación contra naive y estacional naive;
- cobertura del intervalo;
- costo de decisiones simuladas: quiebre, exceso y rotación.

## 12. Asistente de consulta/RAG

No es primer módulo. Puede agregar valor futuro para explicar reportes, localizar una prenda o responder políticas, pero tiene riesgos mayores que una consulta estructurada:

- respuestas inventadas sobre stock o pagos;
- fuga de datos personales/financieros;
- SQL generado peligroso o costoso;
- reglas/documentos desactualizados;
- dificultad de atribuir respuesta y autorización por fila.

Arquitectura futura recomendada:

- catálogo y reportes mediante tools/API de solo lectura, no texto embebido como verdad de stock;
- documentos versionados y citados para políticas;
- plantillas/consultas permitidas y límites de costo;
- autorización Laravel antes de cada tool;
- respuesta con fuentes, fecha y advertencia;
- sin escritura comercial ni SQL libre.

Solo avanzar si consultas reales demuestran que filtros/reportes convencionales no resuelven el problema.

## 13. Dataset de prendas

### Unidad y campos

La unidad primaria es una imagen vinculada a una prenda, con grupo de split por `prenda_id`.

| Campo | Descripción |
|---|---|
| `sample_id`, `image_id`, `garment_id`, `variant_id` | Identificadores estables |
| `image_sha256`, `perceptual_hash` | Dedupe e integridad |
| `image_role`, `source`, `captured_at` | Portada, galería, etiqueta, demo, etc. |
| `category_id/name/version` | Verdad validada y taxonomía |
| `colors[]` | IDs/HEX/familias y porcentajes si se anotan |
| `season_id`, `brand_id`, `gender` | Datos confirmados existentes |
| `pattern`, `style`, `material`, `occasion`, `tags[]` | Solo vocabularios aprobados; `unknown` permitido |
| `quality_labels`, `background_type`, `garment_count` | Casos difíciles |
| `validated_by`, `validated_at`, `validation_source` | Auditoría pseudonimizada |
| `model_name/version`, `prediction`, `confidence` | Predicción original |
| `human_action`, `human_correction` | Aceptada/corregida/ignorada |
| `dataset_version`, `split` | Reproducibilidad |

### Ciclo de mejora

```text
FastAPI predice
→ administrador acepta/corrige/ignora
→ Laravel guarda predicción y verdad confirmada por separado
→ proceso de curación selecciona muestras elegibles
→ revisión/deduplicación
→ dataset inmutable versionado
→ train/validation/test por prenda
→ evaluación contra modelo activo
→ aprobación
→ despliegue canary/rollback
```

### Evitar contaminación

- Una aceptación por omisión o autosubmit no cuenta como etiqueta humana.
- Requerir acción explícita y registrar quién/cuándo.
- Separar `predicted_value` de `validated_value`; nunca reentrenar sobre la predicción si falta validación.
- Excluir baja calidad, conflictos y casos ambiguos hasta adjudicación.
- Deduplicar exactos y near-duplicates antes del split.
- Mantener todas las imágenes de una prenda en un solo split.
- Congelar test set y no usarlo para elegir prompts/umbrales.
- Muestrear auditorías de aceptaciones y doble anotación en atributos subjetivos.
- Versionar política de inclusión y conservar lineage.

## 14. Recomendación por módulo y fase

| Módulo | Primera técnica | Primera fase razonable | Razón |
|---|---|---|---|
| Registro asistido | Reglas + LAB/K-Means + SigLIP | IA-3/IA-4 | Alto valor y encaja en formulario existente |
| Clasificación | SigLIP, CLIP baseline | IA-4 | Sin dataset propio |
| Color | LAB/K-Means + catálogo + selector manual | IA-3 | Dependencias ya instaladas |
| OCR | Tesseract/EasyOCR benchmark | IA-8 | Requiere fotos de etiqueta; no bloquear MVP |
| Similares | SigLIP + coseno; NumPy→pgvector | IA-6 | Reutiliza embedding |
| Recomendaciones | Reglas→contenido→híbrido | IA-7/IA-9 | Señales actuales limitadas |
| Outfits | Reglas/matriz→ranking | IA-10 | Falta rol/taxonomía y feedback |
| Analítica | SQL descriptivo→baselines temporales | IA-7 | Datos comerciales ya existen |
| RAG | Tools de solo lectura + fuentes | IA-12 | Valor/riesgo aún no demostrado |

## 15. MVP técnico recomendado

El primer MVP usa una portada y la taxonomía activa. Procesa validación segura, calidad, fondo aproximado, paleta LAB/K-Means, hash exacto y `google/siglip-base-patch16-224` para top-3/embedding. Devuelve categorías, color(es), checks de calidad y posibles duplicados con explicación. No detecta marca, talla, material, composición, estilo libre, ocasión, temporada ni texto. El administrador decide cada campo y su acción genera feedback. CLIP ViT-B/32 se evalúa offline como baseline antes de declarar a SigLIP activo.
