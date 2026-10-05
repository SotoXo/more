# Auditoría de colorimetría y compatibilidad cromática

Fecha: 2026-10-04. Código productivo sin modificaciones. El documento 00 se creó antes de la ejecución. Evidencia reproducible: `storage/app/ai-audit/python-color-real.json`, `python-synthetic-audit.json`, `classification_predictions.json`; scripts `services/mati-ai/scripts/audit/audit_color_real.py` y `audit_python.py`.

## Resultado y alcance de la evidencia

La función CIEDE2000 supera los 34 pares de referencia publicados, pero el sistema aún necesita validación de segmentación semántica, condiciones fotográficas y correspondencias comerciales. Una distancia matemática correcta no convierte una fotografía de catálogo en medición física del tejido.

Se analizaron 130 fotografías únicas del catálogo por el endpoint real coordinado por la auditoría principal y se reutilizaron sus snapshots completos para las métricas originales. El manifiesto contiene 134 archivos, 131 SHA únicos. El SHA compartido por imágenes 2 y 5 tiene categorías y etiquetas de color incompatibles (BEIGE/CAMEL); se excluyó ese SHA. Los otros dos duplicados exactos se cuentan una sola vez.

**Verdad de referencia disponible:** color comercial de variante y HEX del catálogo. **Verdad ausente:** carta de color medida, instrumento físico, máscara manual de la prenda objetivo, confirmación experta de prenda estrictamente monocolor, transcripción científica del color bajo luz controlada. Una variante con una sola etiqueta comercial no prueba que todos los píxeles de la imagen pertenezcan a una sola prenda/color.

No se afirma exactitud física ni precisión de color calibrada. Se informa coincidencia contra el proxy comercial, cobertura y abstención, con denominadores explícitos.

## Color real: mediciones originales

| Medida | Numerador / denominador | Resultado |
|---|---:|---:|
| Colorimetría completed y paleta no vacía | 84 / 130 | 64,62 % |
| Colorimetría insufficient_evidence | 46 / 130 | 35,38 % |
| Color principal observado presente | 63 / 130 | 48,46 % |
| Color principal con ID comercial aceptado | 35 / 130 | 26,92 % |
| Principal con ID que coincide con etiqueta de variante | 18 / 35 IDs propuestos | 51,43 % de coincidencia proxy |
| Principal que coincide, contando todo el conjunto | 18 / 130 | 13,85 % |
| Tono más poblado con ID comercial aceptado | 48 / 130 | 36,92 % |
| Tono más poblado cuyo ID coincide con etiqueta | 22 / 48 IDs propuestos | 45,83 % de coincidencia proxy |
| Algún tono de paleta tiene ID comercial | 69 / 130 | 53,08 % |
| Algún tono de paleta coincide con etiqueta de variante | 26 / 130 | 20,00 % |

El principal puede ser `null` aun con paleta válida: 21 resultados avisan `NO_SINGLE_DOMINANT_COLOR`. No se rellena ese hueco con el tono más poblado para calcular éxito del principal.

Para 84 paletas no vacías se calculó ΔE00 entre su tono más poblado y el HEX de variante esperado (mínimo entre etiquetas disponibles). Media 14,497; mediana 9,968; p90 29,634. Esta medida es **distancia a un swatch comercial de referencia**, no error físico contra la tela. El campo `delta_e` devuelto por Python mide distancia al candidato de catálogo más cercano, que puede ser otro ID; no sirve como error contra la etiqueta esperada.

Avisos frecuentes: `CATALOG_MATCH_AMBIGUOUS` 53/130; `CATALOG_COLOR_UNMATCHED` 33/130; `BACKGROUND_INTERFERENCE` 46/130; `METALLIC_FINISH_NOT_VERIFIABLE` 10/130. Un resultado `completed` puede incluir avisos e IDs ajenos al proxy.

## Lo que calcula el código

`vision/color.py` analiza copia LANCZOS de hasta 256×256. Convierte RGB flotante 0..1 a CIELAB D65 físico con OpenCV; implementa CIEDE2000 con kL=kC=kH=1. No usa distancia euclidiana RGB como criterio principal.

`vision/segmentation.py:15` estima el modo del borde exterior (~4 %), exige fondo uniforme y conecta los píxeles similares al borde. Evalúa área, centralidad, componentes, contraste y geometría; exige foreground_confidence≥0,70. La máscara aceptada corresponde a un objeto central plausible. **No detecta qué prenda se intenta registrar.** La confianza es una combinación heurística de señales de separación.

La paleta usa histograma Lab [6,8,8], hasta ocho semillas separadas por ΔE00≥12, cinco refinamientos de centroides ponderados y descarte de grupos<1,5 %. Los porcentajes son fracciones del primer plano interior muestreado, no probabilidades ni área física del tejido. Cobertura suma los grupos retenidos, sin renormalizar artificialmente a 100 %.

El principal necesita≥55 % del área muestreada y ventaja≥15 puntos frente al siguiente. Acentos<12 % y ΔE00≥15 contra el primer tono. Propiedades categóricas solo se consideran fiables con iluminación sin avisos y cobertura≥85 %.

Asignar ID comercial exige HEX específico enviado por Laravel, iluminación fiable, ΔE00≤10, margen≥2 (excepto coincidencia≤2), confianza swatch≥0,55, dispersión p90≤14 y ausencia de acabado metálico no verificable. Una sola referencia solo acepta ΔE00≤2. Los fallbacks por nombre no autorizan IDs; `family_hex` no reemplaza el HEX específico.

La implementación no corrige balance de blancos. La ausencia de avisos no certifica iluminación física correcta. El contraste entre negro/blanco intrínseco y clipping de cámara no se puede resolver solo desde una imagen.

## Verificación matemática y fixtures controladas

Los [34 pares oficiales de Sharma, Wu y Dalal](https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/dataNprograms/ciede2000testdata.txt) se ejecutaron contra `delta_e_2000`: 34/34 dentro de ±0,00005, error máximo 0,000049499. También pasaron identidad cero y simetría. El límite corresponde al redondeo de las referencias a cuatro decimales.

Se generaron 12 rectángulos centrales sobre blanco, con colores de referencia exactos, y se procesaron originales más ocho perturbaciones: 108 casos sintéticos. Originales: 11/12 con paleta, 10/12 completed e ID exacto correcto. El blanco muy próximo al fondo se abstuvo; el rojo puro #ff0000 produjo paleta parcial y ningún ID por clipping de canal. Esto confirma conductas conservadoras, no exactitud sobre fotografías reales.

Se verificó que los 12 objetos imagen originales en memoria permanecieran iguales tras extracción. Imagen uniforme completa y fondo de ruido se abstuvieron sin fabricar paleta.

## Robustez fotográfica en memoria

Se conservaron los archivos del catálogo. Para cada una de las 130 fotos se crearon nueve copias/transformaciones temporales en memoria (1.170 evaluaciones): referencia reducida a máximo1024, brillo0,65/1,25, blur Gauss2, JPEG60, rotación10° con borde blanco, dominante RGB cálida/fría y reducción128. No se guardaron variantes de imagen en catálogo.

La mediana/p90 ΔE siguiente compara los tonos más poblados de original y perturbación **solo cuando ambas paletas existen**. Si cambia qué región se selecciona, esa medida también contiene ese cambio; no es comparación de un parche físico fijo.

| Transformación | Completed / parcial / insuficiente (N=130) | Ambas paletas N | ΔE00 mediana / p90 | Coincide algún ID con proxy |
|---|---|---:|---:|---:|
| Referencia1024 | 83 / 0 / 47 | 83 | 0,049 / 0,105 | 25 |
| Blur2 | 84 / 0 / 46 | 84 | 0,185 / 2,215 | 25 |
| JPEG60 | 83 / 0 / 47 | 83 | 0,408 / 1,875 | 25 |
| Rotación10° | 89 / 0 / 41 | 80 | 0,044 / 0,799 | 27 |
| Tamaño128 | 84 / 0 / 46 | 83 | 0,140 / 2,694 | 27 |
| Brillo0,65 | 81 / 0 / 49 | 81 | 7,477 / 21,350 | 16 |
| Brillo1,25 | 67 / 4 / 59 | 70 | 5,329 / 11,092 | 22 |
| Dominante fría | 48 / 35 / 47 | 82 | 3,230 / 8,301 | 15 |
| Dominante cálida | 3 / 80 / 47 | 82 | 3,375 / 7,736 | 0 |

Las copias cálidas inducen sospecha de iluminación y evitan IDs en casi todo el conjunto. Oscurecer la foto cambia significativamente la observación y reduce coincidencias contra los mismos HEX comerciales. Las transformaciones geométricas/compresión suaves muestran cambios menores cuando el foreground sigue disponible. No son métricas de calibración física, ya que la cámara/luz real y el swatch verdadero no se midieron.

## Inspección visual puntual y causas

La imagen13, `productos/11/jersey-alpaca-frueyyc3.jpg`, muestra una persona con jersey verde, pantalón oscuro y bolso oscuro sobre fondo blanco. El sistema acepta la silueta completa y asigna NEGRO CARBON como principal del objeto. El objetivo comercial es el jersey. Es evidencia directa de contaminación por otras prendas/accesorios unidos; el foreground no es máscara semántica del producto.

La imagen86, `productos/84/massimo-5194-594-nmghet0q.jpg`, es una prenda azul oscura aislada; la variante tiene etiqueta AZUL CIELO y el sistema propone AZUL PETROLEO. Aquí el desacuerdo también puede venir de la etiqueta/HEX comercial: sin experto/carta medida no se atribuye el error exclusivamente a extracción.

La imagen111, `productos/109/cos-1208558001-mornyhzg.jpg`, es una prenda verde muy oscura aislada; el sistema propone NEGRO CARBON. Es consistente con ambigüedad entre familia cromática y bajo croma/luminosidad, pero requiere referencia física para cuantificar error.

## Compatibilidad entre dos paletas

`garment-compatibility-1.0.0` es un ranking heurístico0..100. No genera outfits ni preferencias personales. Hue se aproxima con una rueda artística RYB interpolada desde HSV sRGB; luminosidad/croma usan Lab. No debe presentarse como ciencia física de gusto ni porcentaje de certeza.

Cada paleta normaliza las proporciones retenidas; parejas de tonos pesan p_i·p_j y roles no duplican masa. Componentes: temperatura0,10, luminosidad0,20, matiz0,55, contraste0,15. Se excluyen componentes ausentes y se renormalizan sus pesos. La puntuación es100×media ponderada; `unknown` de temperatura no significa incompatibilidad.

Pruebas de 30 perfiles sintéticos: 465 pares incluyendo autocombinaciones, todos acotados0..100 y simétricos a dos decimales; roles cambiados sin alterar paleta mantuvieron score. Foreground<0,70 devolvió null. Sin temperatura, componente null y aviso, sin asignar cero. Son propiedades del motor, no juicios humanos sobre estética.

Primeras30 paletas reales del manifiesto:435 pares distintos;136 completed con score,299 insufficient_evidence con null;435/435 simétricos. No hay verdad de referencia humana de pares. Por ello no se informa precisión de compatibilidad, ranking atractivo ni éxito de outfits.

## Dictamen del dominio

CIEDE2000 y conductas de abstención están verificadas en las condiciones probadas. La correspondencia comercial del color tiene cobertura limitada y discrepancias que requieren revisar etiqueta/foto/swatch. La máscara puede aceptar personas con varias prendas como una sola silueta y atribuir el color de accesorios/pantalones a la prenda objetivo.

Antes de ampliar automatización: definir captura de una prenda aislada o máscara semántica de la prenda objetivo; crear subconjunto experto con color/roles/máscara confirmados; medir muestras bajo luz y cámara controladas; calibrar umbrales sin usar el conjunto final como entrenamiento; separar color observado del ID comercial y mantener revisión humana. No se tocaron los umbrales en esta auditoría.

## Suplemento final: familias y recorte moderado

Familia comercial mapeable130/130. Topcluster coincide47/84=55.95% entre paletas presentes (47/130=36.15% sobre toda la muestra); primary36/63=57.14% (36/130=27.69%). Esperado usa fam_col comercial, no una nueva etiqueta humana. Observado puede incorporar familia del IDaceptado y excepciónCREMA/MARFIL/HUESO conb*>4 hacia beige_cream. Proxy no independiente, no accuracy física. CREMA comercial perteneceBLANCO: taxonomía y regla difieren, no corregidas.

140 transformaciones adicionales en20 fotos reales: primeras20conprimary del baseline, sin selección poracierto. Transformaciones sóloRAM; hashes de originales iguales antes/después. Es diagnóstico condicionado aprimary, no estimador representativo de130. IDnull no cuenta como igualdad. Se muestran supervivencia yacuerdo condicional:

| Transformación | Primary sobre20 | IDigual/comparable (11IDsbase) | Familia primary | Setsfamilia iguales/20 | JaccardIDs media/N | ΔEprimary mediana/N |
|---|---|---|---|---|---|---|
|brightness_090|19/20|6/6|19/19|17/20|0.694/N17|3.333/N19|
|brightness_110|14/20|2/2|14/14|11/20|0.541/N17|2.295/N14|
|warm_cast_5pct|20/20|7/8|15/20|9/20|0.533/N18|2.017/N20|
|cool_cast_5pct|19/20|7/7|19/19|14/20|0.475/N18|2.432/N19|
|crop_10pct_each_border|15/20|8/8|15/15|11/20|0.627/N17|0.038/N15|
|small_128|20/20|10/10|20/20|9/20|0.694/N18|0.132/N20|
|jpeg_60|18/20|10/10|17/18|16/20|0.851/N17|0.385/N18|

Recorte10%por cada borde conserva64%del área:15/20primary,5insuficientes;8/8IDcomparables de11base (8/11retenidos),familia15/15. No interpretar100%condicional como robustezglobal100%. Warmcast5% conserva20/20primary pero familia15/20 eID7/8comparables vs11base. JaccardIDs=intersección/unión, excluye sólo uniónvacía; si desaparecentodosIDsbase cuenta0. ΔEprimary exige dos primary presentes. JPEGfamilia17/18comparables y1insuficiente. Salidas completas audit_supplement.json.

Objeto conectado no implica prenda: foto13JERSEYALPACA/ABRIGOS muestra jersey verde+pantalónnegro+bolsa ydevuelve negro carbon. Evidencia real/visual diagnostic_images.png. No IoU sin máscaras humanas ni color físico sin referencia medida. Primary,palette,temperature,lightness,chroma,contrast,family pueden alimentarcompatibilidad futura, pero no recomendación personalizada.
