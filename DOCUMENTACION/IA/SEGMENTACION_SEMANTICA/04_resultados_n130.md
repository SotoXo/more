# 04. Resultados de consumidores sobre N130

**Decisión final: NONE. Ninguno de los tres pipelines se integra en producción ni sustituye FULL para clasificación o embeddings.** Todos cumplieron el gate de recursos de este ensayo; los seis perfiles crop/masked fallan el gate cuantitativo de clasificación, color y retrieval fijado antes de la inferencia. No se retiró el guard comercial de color. La revisión visual y el diagnóstico aislado de imagen13 no alteran esa decisión.

Fuentes principales: `consumer-full.json`, `consumer-comparison.json`, los tres `consumers.json` y `gate-decision.json` del árbol privado `storage/app/ai-segmentation`. Los resultados de detección, selección, máscaras, latencia, memoria y reparación del lector se describen en [02_benchmark_candidatos.md](/home/soto/Code/more/DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/02_benchmark_candidatos.md).

## Denominadores y línea base reproducida

Se evaluaron las **130 fotos únicas congeladas**. La etiqueta de categoría es comercial, confirmada en el catálogo, sin anotación experta independiente. Hay diez clases presentes con soportes: ABRIGOS 13, BLAZERS 10, CAMISAS 20, BLUSAS 8, VESTIDOS 18, FALDAS 7, PANTALONES 15, POLOS Y KNITWEAR 20, CONJUNTOS 3 y POLERA 16. La ausencia de máscara ground truth impide afirmar IoU, Dice, mAP o semantic target accuracy.

FULL conserva foto completa, validación sRGB/EXIF/ICC, SigLIP `google/siglip-base-patch16-224` revisión `7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed`, pipeline `registration-1.2.0` y preprocess `srgb-exif-transformers-image-1`. Se reprodujo en CUDA/float32 con los prompts y umbrales productivos sin modificar. En las 130 fotos coincidieron el orden y los IDs Top3 y las métricas agregadas originales. La diferencia máxima registrada de score Top3 fue **1.000000000001e-6**, dentro de la tolerancia declarada de **1e-5**: es reproducción de resultados y métricas, sin afirmar igualdad binaria de todos los floats. Se verificaron los 130 SHA originales.

El color FULL reutiliza la observación productiva preservada, sin reentrenar ni cambiar sus fórmulas. Las diferencias estadísticas de color entre artefactos históricos pueden proceder de denominadores y definición de proxy; las cifras de este informe se toman de `consumer-full.json`.

En ALL130 se cuentan objetivos faltantes como **no_prediction**, con error en Top1/Top3 y falsa ausencia para el recall de su clase. En retrieval, consulta faltante obtiene métricas cero manteniendo N130. Los pipelines Tiny no produjeron AUTO; Base produjo seis AUTO, IDs 16–21, y **124 faltantes**. La evaluación condicionada a esas seis imágenes se presenta aparte, con su propia línea base pareada. No se mezcló selección asistida en ningún agregado.

## Clasificación ALL130

| Perfil | Ranking disponible N | Top1 aciertos/130 (tasa) | Top3 aciertos/130 (tasa) | Macro F1 | Weighted F1 | Cobertura formal/130 (tasa) | No target |
| --- | --- | --- | --- | --- | --- | --- | --- |
| FULL | 130 | 83/130 (0.638461538) | 116/130 (0.892307692) | 0.664920056 | 0.648532672 | 13/130 (0.100000000) | 0 |
| DINO Tiny + SAM2 Tiny / crop | 0 | 0/130 (0.000000000) | 0/130 (0.000000000) | 0.000000000 | 0.000000000 | 0/130 (0.000000000) | 130 |
| DINO Tiny + SAM2 Tiny / masked | 0 | 0/130 (0.000000000) | 0/130 (0.000000000) | 0.000000000 | 0.000000000 | 0/130 (0.000000000) | 130 |
| DINO Base + SAM2 Tiny / crop | 6 | 5/130 (0.038461538) | 5/130 (0.038461538) | 0.048863636 | 0.064772727 | 1/130 (0.007692308) | 124 |
| DINO Base + SAM2 Tiny / masked | 6 | 4/130 (0.030769231) | 5/130 (0.038461538) | 0.041071429 | 0.053983516 | 4/130 (0.030769231) | 124 |
| DINO Tiny + SAM ViT-B / crop | 0 | 0/130 (0.000000000) | 0/130 (0.000000000) | 0.000000000 | 0.000000000 | 0/130 (0.000000000) | 130 |
| DINO Tiny + SAM ViT-B / masked | 0 | 0/130 (0.000000000) | 0/130 (0.000000000) | 0.000000000 | 0.000000000 | 0/130 (0.000000000) | 130 |

Top1 y Top3 describen el ranking exploratorio aun cuando `classification.abstained=true`. La cobertura formal sólo cuenta `abstained=false`; no es la misma métrica que ranking disponible. Macro F1 usa las diez clases presentes y contabiliza los faltantes como falsos negativos de su clase. Los scores son similitudes coseno sin calibración, no probabilidades.

FULL mantiene **83/130** Top1, **116/130** Top3, macro F1 **0.6649200560908808**, cobertura formal **13/130** y abstención **117/130**. Base crop sólo acierta **5/130** y masked **4/130** sobre el denominador obligatorio, aunque el ranking exista para seis fotos.

### Precisión y recall macro; abstención formal ALL130

| Perfil | Macro Precision | Macro Recall | Abstenciones/130 | Tasa de abstención |
| --- | --- | --- | --- | --- |
| FULL | 0.7308658008658009 | 0.6946138583638584 | 117/130 | 0.9 |
| DINO Tiny + SAM2 Tiny / crop | 0.0 | 0.0 | 130/130 | 1.0 |
| DINO Tiny + SAM2 Tiny / masked | 0.0 | 0.0 | 130/130 | 1.0 |
| DINO Base + SAM2 Tiny / crop | 0.2 | 0.028888888888888888 | 129/130 | 0.9923076923076923 |
| DINO Base + SAM2 Tiny / masked | 0.2 | 0.023333333333333334 | 126/130 | 0.9692307692307692 |
| DINO Tiny + SAM ViT-B / crop | 0.0 | 0.0 | 130/130 | 1.0 |
| DINO Tiny + SAM ViT-B / masked | 0.0 | 0.0 | 130/130 | 1.0 |

Los denominadores y clases son los de la tabla ALL130 anterior. La abstención formal incluye tanto falta de target como rankings existentes que no superan la política de clasificación: Base crop registra 129 abstenciones aunque sólo 124 carecen de target; Base masked registra 126. Una tasa de abstención alta no elimina falsos negativos del cálculo macro.

### Confusiones before/after y ausencia de predicción

| Perfil ALL130 | CAMISAS→BLUSAS | KNITWEAR→PANTALONES | BLAZERS→PANTALONES | VESTIDOS→BLUSAS | no_prediction |
| --- | --- | --- | --- | --- | --- |
| FULL | 6 | 5 | 4 | 5 | 0 |
| DINO Tiny + SAM2 Tiny / crop | 0 | 0 | 0 | 0 | 130 |
| DINO Tiny + SAM2 Tiny / masked | 0 | 0 | 0 | 0 | 130 |
| DINO Base + SAM2 Tiny / crop | 0 | 0 | 0 | 0 | 124 |
| DINO Base + SAM2 Tiny / masked | 0 | 0 | 0 | 1 | 124 |
| DINO Tiny + SAM ViT-B / crop | 0 | 0 | 0 | 0 | 130 |
| DINO Tiny + SAM ViT-B / masked | 0 | 0 | 0 | 0 | 130 |

KNITWEAR representa aquí la categoría comercial POLOS Y KNITWEAR. Los ceros de confusión en los pipelines que abstienen no prueban corrección de las clases: desaparece el ranking y pasan a `no_prediction`. La matriz completa y sus etiquetas de columnas, incluida esa columna adicional, permanecen en cada artefacto.

| Perfil | ABRIGOS | BLAZERS | CAMISAS | BLUSAS | VESTIDOS | FALDAS | PANTALONES | KNITWEAR | CONJUNTOS | POLERA |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| FULL | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| DINO Tiny + SAM2 Tiny / crop | 13 | 10 | 20 | 8 | 18 | 7 | 15 | 20 | 3 | 16 |
| DINO Base + SAM2 Tiny / crop | 12 | 10 | 20 | 8 | 14 | 7 | 14 | 20 | 3 | 16 |
| DINO Base + SAM2 Tiny / masked | 12 | 10 | 20 | 8 | 14 | 7 | 14 | 20 | 3 | 16 |

La última fila Tiny crop también describe Tiny masked y los dos perfiles de Tiny + SAM ViT-B: todos tienen 130 faltantes. FULL tiene cero en esta columna porque produce ranking para todas, aunque se abstenga formalmente en 117.

## Color ALL130: proxy comercial con guard intacto

| Perfil | Paleta/130 | Primario/130 | ID prospectivo/130 | ID proxy correcto/130 | Precisión condicional ID | N ΔE00 | Media ΔE00 | IDs aplicables |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| FULL | 84/130 | 63/130 | 35/130 | 18/130 | 0.514285714 | 63 | 11.591495398 | 0 |
| DINO Tiny + SAM2 Tiny / crop | 0/130 | 0/130 | 0/130 | 0/130 | no evaluable | 0 | no evaluable | 0 |
| DINO Tiny + SAM2 Tiny / masked | 0/130 | 0/130 | 0/130 | 0/130 | no evaluable | 0 | no evaluable | 0 |
| DINO Base + SAM2 Tiny / crop | 6/130 | 3/130 | 0/130 | 0/130 | no evaluable | 3 | 9.854675933 | 0 |
| DINO Base + SAM2 Tiny / masked | 6/130 | 3/130 | 0/130 | 0/130 | no evaluable | 3 | 9.854675933 | 0 |
| DINO Tiny + SAM ViT-B / crop | 0/130 | 0/130 | 0/130 | 0/130 | no evaluable | 0 | no evaluable | 0 |
| DINO Tiny + SAM ViT-B / masked | 0/130 | 0/130 | 0/130 | 0/130 | no evaluable | 0 | no evaluable | 0 |

Un **ID prospectivo** es el candidato calculado antes de imponer el guard y sirve sólo para medir contra la etiqueta del catálogo. No se aplica al producto. FULL tiene **35 candidatos prospectivos**, **18 coincidencias de ID proxy**, cobertura exacta ALL130 **18/130 = 0.13846153846153847** y precisión condicional **18/35 = 0.5142857142857142**. Base con máscara no obtiene candidato prospectivo aceptado; su precisión condicional es **no evaluable**, no cero medido sobre intentos inexistentes.

FULL produce paleta en **84/130 = 0.6461538461538462** y primario en **63/130**. Base produce paleta en **6/130** y primario en **3/130**; Tiny carece de salida AUTO y sus métricas observables son cero o no evaluables según denominador. La medida ΔE00 compara el color observado con swatches hex comerciales: no es una medición física de tejido, reflectancia o iluminante controlado. Una etiqueta comercial única tampoco prueba que la fotografía contenga una sola prenda o un solo color.

### Distribución ΔE00 disponible en ALL130

| Perfil | N con distancia | Media ΔE00 | Mediana ΔE00 | p90 ΔE00 |
| --- | --- | --- | --- | --- |
| FULL | 63 | 11.59149539819804 | 9.04542386635896 | 24.714970725261676 |
| DINO Tiny + SAM2 Tiny / crop | 0 | no evaluable | no evaluable | no evaluable |
| DINO Tiny + SAM2 Tiny / masked | 0 | no evaluable | no evaluable | no evaluable |
| DINO Base + SAM2 Tiny / crop | 3 | 9.854675932961351 | 10.172304638005365 | 11.073898507420854 |
| DINO Base + SAM2 Tiny / masked | 3 | 9.854675932961351 | 10.172304638005365 | 11.073898507420854 |
| DINO Tiny + SAM ViT-B / crop | 0 | no evaluable | no evaluable | no evaluable |
| DINO Tiny + SAM ViT-B / masked | 0 | no evaluable | no evaluable | no evaluable |

N cuenta fotos con primario y swatch proxy comparable, no todas las 130 fotos. Los perfiles Tiny tienen N0 y estadísticos no evaluables. FULL N63 y Base N3 no son la misma muestra; medianas y percentiles tampoco constituyen una comparación pareada ni una medición física de tejido.

### Familia de color con vocabulario común

| Perfil | Familia correcta/evaluable | Accuracy familia condicional | Correctas/130 | Tasa familia ALL130 |
| --- | --- | --- | --- | --- |
| FULL | 47/84 | 0.559523810 | 47/130 | 0.361538462 |
| DINO Tiny + SAM2 Tiny / crop | 0/0 | no evaluable | 0/130 | 0.000000000 |
| DINO Tiny + SAM2 Tiny / masked | 0/0 | no evaluable | 0/130 | 0.000000000 |
| DINO Base + SAM2 Tiny / crop | 1/6 | 0.166666667 | 1/130 | 0.007692308 |
| DINO Base + SAM2 Tiny / masked | 1/6 | 0.166666667 | 1/130 | 0.007692308 |
| DINO Tiny + SAM ViT-B / crop | 0/0 | no evaluable | 0/130 | 0.000000000 |
| DINO Tiny + SAM ViT-B / masked | 0/0 | no evaluable | 0/130 | 0.000000000 |

La familia de la etiqueta se obtiene con la misma función productiva de familia y referencia Lab del swatch comercial, para alinear el vocabulario interno; no se inventó una tabla de correspondencias. FULL: **47/84 = 55.9523809524%** condicional y **47/130 = 36.1538461538%** ALL130. Base: **1/6 = 16.6666666667%** condicional y **1/130 = 0.7692307692%** ALL130.

### Qué cambió experimentalmente en la entrada de color

La máscara seleccionada se reduce con nearest neighbor a la escala de muestreo productiva de 256 y se erosiona con núcleo3×3 para usar interior visible. Se reutilizan conversión sRGB→CIELAB D65, clustering, CIEDE2000, referencias, fórmulas y umbrales productivos. Ambos perfiles crop y masked comparten **la misma máscara e idéntica observación semántica de color**: sus tablas iguales no son dos experimentos independientes de color.

La función productiva exige un score de foreground para combinar evidencia. El adaptador experimental suministra el valor fijo **MIN_FOREGROUND_CONFIDENCE = 0.70**, identificado como entrada mínima de compatibilidad. No deriva una confianza semántica del score DINO, SAM ni revisión visual. Esa normalización puede afectar la aceptación prospectiva y limita atribuir una diferencia exclusivamente a selección de píxeles. No se reajustó el umbral ni se cambió el código productivo.

En todas las salidas aplicables a catálogo, los IDs y nombres comerciales permanecen nulos, `catalog_match_accepted=false` y razón `target_garment_identity_unverified`. La identidad sigue `target_garment_identity_verified=false`. Las observaciones técnicas y posibles candidatos prospectivos se conservan como diagnóstico; la máscara experimental no habilita el guard.

La media FULL **11.59149539819804** usa **N63**; la media Base **9.854675932961351** usa **N3**. Su descenso aritmético **−1.7368194652366888** es **no pareado** y no demuestra mejora física. El reporte del gate marca explícitamente `delta_e_comparison_is_unpaired=true`; esa condición numérica aislada no aprueba color.

## Retrieval ALL130: mismo perfil, relevancia por categoría

| Perfil | Consultas disponibles/130 | MRR | nDCG@10 | Recall@1 | Recall@5 | Recall@10 | Hit@1 | Hit@5 | Hit@10 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| FULL | 130/130 | 0.703045273 | 0.402063260 | 0.053140166 | 0.166810219 | 0.255514257 | 0.584615385 | 0.876923077 | 0.976923077 |
| DINO Tiny + SAM2 Tiny / crop | 0/130 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 |
| DINO Tiny + SAM2 Tiny / masked | 0/130 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 |
| DINO Base + SAM2 Tiny / crop | 6/130 | 0.026923077 | 0.013349534 | 0.001357466 | 0.005429864 | 0.005429864 | 0.023076923 | 0.030769231 | 0.030769231 |
| DINO Base + SAM2 Tiny / masked | 6/130 | 0.030769231 | 0.014078676 | 0.001809955 | 0.005429864 | 0.005429864 | 0.030769231 | 0.030769231 | 0.030769231 |
| DINO Tiny + SAM ViT-B / crop | 0/130 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 |
| DINO Tiny + SAM ViT-B / masked | 0/130 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 | 0.000000000 |

Se calcula coseno exacto con los embeddings768 del SigLIP original, normalizados para la comparación. Cada perfil consulta sólo su galería del **mismo preprocess**; no se compara un query masked contra una galería FULL. Se excluyen self, mismo producto y mismo SHA. Relevancia = **misma categoría comercial, distinto producto/SHA**, no identidad exacta de prenda. No existen pares anotados suficientes para afirmar rendimiento de reconocimiento de una prenda exacta.

En ALL130 la galería completa que define cuántos elementos son relevantes permanece congelada; sólo seis embeddings disponibles en Base dan como máximo cinco candidatos disponibles por consulta, y las otras 124 consultas puntúan cero. Recall@k divide por todos los relevantes de la galería completa por consulta; Hit@k indica si aparece al menos uno. Por eso no deben llamarse ambos Recall. nDCG@10 usa relevancia binaria por categoría; MRR usa el primer relevante. Las métricas no tienen ground truth de estilo, outfit o sustitución comercial.

FULL dispone de 130 queries / 130 vectores: MRR **0.7030452734400103**, nDCG@10 **0.40206325975125334**, Recall@10 **0.2555142569848452** y Hit@10 **0.9769230769230769**. Base masked baja a nDCG **0.014078675514955624** y Hit@10 **4/130** cuando se conserva el denominador130.

## Cohorte AUTO seleccionada: comparación pareada N6

Las seis fotos16–21 están elegidas por la política sin GT y constituyen una muestra condicionada al selector, pequeña y sesgada. FULL sobre esas mismas fotos es la línea base pareada; no se compara esta tasa con FULL sobre130 para declarar una mejora.

| Perfil N6 | Top1 | Top3 | Macro F1 N6 | Cobertura formal |
| --- | --- | --- | --- | --- |
| full_matched_cohort | 5/6 (0.833333333) | 5/6 (0.833333333) | 0.666666667 | 0/6 (0.000000000) |
| crop | 5/6 (0.833333333) | 5/6 (0.833333333) | 0.666666667 | 1/6 (0.166666667) |
| masked | 4/6 (0.666666667) | 5/6 (0.833333333) | 0.619047619 | 4/6 (0.666666667) |

Crop conserva **5/6** Top1; masked baja a **4/6**. En imagen 18, FULL y crop predicen VESTIDOS; masked cambia a BLUSAS y deja de abstener formalmente. La cobertura formal sube de **0/6** FULL a **1/6** crop y **4/6** masked, pero mayor confianza/aceptación no equivale a mayor exactitud. Macro F1 N6 considera las tres clases comerciales presentes en esta cohorte y no tiene el mismo universo de clases que macro F1 ALL130.

| Color N6 | Paleta/6 | Primario/6 | ID proxy correcto/6 | Familia correcta/evaluable | Familia condicional | N ΔE00 | Media ΔE00 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| full_matched_cohort | 3/6 | 1/6 | 0/6 | 1/3 | 0.333333333 | 1 | 9.062436874 |
| crop | 6/6 | 3/6 | 0/6 | 1/6 | 0.166666667 | 3 | 9.854675933 |
| masked | 6/6 | 3/6 | 0/6 | 1/6 | 0.166666667 | 3 | 9.854675933 |

La cobertura de paleta aumenta de 3/6 a 6/6 en esta cohorte, mientras la coincidencia exacta de ID proxy sigue 0/6. La media FULL usa sólo la imagen 20; la media con máscara usa 18, 19 y 20. En la **intersección pareada N1**, imagen 20, ΔE00 pasa de **9.06243687419557** a **8.09242618610396**. Este dato aislado no estima desempeño global. Las tres distancias semánticas son imagen 18 **10.172304638005365**, imagen 19 **11.299296974774725** e imagen 20 **8.09242618610396**.

| Retrieval N6 | MRR | nDCG@10 | Recall@10 | Hit@10 |
| --- | --- | --- | --- | --- |
| full_matched_cohort | 0.583333333 | 0.585391246 | 0.666666667 | 0.666666667 |
| crop | 0.583333333 | 0.616716101 | 0.666666667 | 0.666666667 |
| masked | 0.666666667 | 0.650400658 | 0.666666667 | 0.666666667 |

Aquí tanto queries como galería completa del cálculo se restringen a la misma cohorte de seis; quedan hasta cinco candidatos por query. Las categorías representadas una sola vez no tienen relevante en esa galería y sus métricas son cero. Por tanto nDCG **0.6504006584112513** masked N6 no se compara directamente con **0.40206325975125334** FULL N130: cambiaron queries, galería y denominadores de relevancia. Dentro de N6, el cambio es descriptivo de esta cohorte; no aprueba el gate ALL130.

Los dos pipelines Tiny tienen N0 seleccionados. Sus estadísticas condicionadas son nulas/no evaluables; no se presentan como accuracy0% sobre casos inexistentes.

## Diagnóstico asistido de imagen13: N1 fuera de agregados

`image13-assisted-consumers.json` declara `ASSISTED_CODEX_VISUAL_DIAGNOSTIC`, `not_auto=true`, `not_human_oracle=true` y `not_in_any_aggregate=true`. Codex escogió visualmente la máscara candidata2 de DINO Base + SAM2 Tiny correspondiente a la región superior verde; la clase externa `jacket` quedó como metadata y no se usó como prueba de que fuera chaqueta. La selección mantiene el original AUTO **ambiguous**, selected ID nulo y motivo múltiples prendas plausibles.

La categoría comercial **ABRIGOS (ID1)** no se corrigió ni reemplazó por observación visual. No existe nueva anotación humana ni máscara ground truth.

| Perfil diagnóstico | Top1 y score coseno | Margen | Abstención | Top3 |
| --- | --- | --- | --- | --- |
| FULL | PANTALONES 0.059922 | 0.015253 | sí | PANTALONES, POLOS Y KNITWEAR, ABRIGOS |
| crop | POLOS Y KNITWEAR 0.077479 | 0.036016 | sí | POLOS Y KNITWEAR, PANTALONES, ABRIGOS |
| masked | POLOS Y KNITWEAR 0.081462 | 0.018945 | no | POLOS Y KNITWEAR, CAMISAS, POLERA |

Ese desplazamiento concuerda con la región superior observada, pero **no demuestra accuracy contra el catálogo**: masked no incluye ABRIGOS en su Top3. Los scores siguen sin calibrar.

El color observado pasa de negro `#231f1d`, familia black, a verde claro `#bac18c`, familia green. La distancia al swatch comercial pasa de **30.432417947328254** a **27.205561772948233**; el ID comercial aplicable permanece nulo y no se acepta candidato prospectivo nuevo. La confirmación visual es de Codex y conserva pequeños fragmentos/bordes sin validación humana. No se evaluó retrieval en N1 ni se extrapoló este caso a otras fotos.

### Paleta y matching del diagnóstico N1

| Observación | Orden | Hex observado | Share área visible | Familia |
| --- | --- | --- | --- | --- |
| Before FULL | 1 | #231f1d | 0.560576 | black |
| Before FULL | 2 | #bbc28d | 0.215752 | green |
| Before FULL | 3 | #899155 | 0.062695 | beige_cream |
| Before FULL | 4 | #59602e | 0.050089 | green |
| Before FULL | 5 | #f6f6f5 | 0.036145 | white |
| Before FULL | 6 | #4d433e | 0.032686 | gray |
| Before FULL | 7 | #917f76 | 0.021754 | brown |
| Before FULL | 8 | #33371b | 0.020303 | green |
| After máscara semántica | 1 | #bac18c | 0.657998 | green |
| After máscara semántica | 2 | #899255 | 0.180976 | green |
| After máscara semántica | 3 | #59612e | 0.125757 | green |
| After máscara semántica | 4 | #353a14 | 0.035269 | green |

Cada share es fracción de los píxeles interiores aceptados en su propia observación. Las paletas tienen ocho y cuatro entradas respectivamente; el orden es independiente y no empareja clusters entre observaciones. No son porcentajes certificados de composición o superficie total del tejido.

| Observación | Primario hex | Share primario | Nombre catálogo más cercano | ΔE00 al más cercano | ΔE00 al swatch comercial de referencia | Score heurístico primario | Mínimo catálogo | Foreground input |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Before FULL | #231f1d | 0.560576 | NEGRO CARBON | 2.768 | 30.432417947328254 | 0.669 | 0.55 | 0.94 |
| After máscara semántica | #bac18c | 0.657998 | VERDE SALVIA | 7.644 | 27.205561772948233 | 0.481 | 0.55 | 0.7 |

**After: VERDE SALVIA está a ΔE00=7.644 del primario observado; el swatch comercial de referencia está a 27.205561772948233. Son dos referencias distintas.** El score heurístico primario 0.481 queda por debajo del mínimo 0.55; el input foreground 0.70 es la compatibilidad experimental fija descrita antes, no confianza científica de máscara. Ninguno de estos scores es una probabilidad.

| Observación | Aceptación observada antes del guard | Razón observada | ID prospectivo | ID aplicable | Aceptación comercial final | Razón final |
| --- | --- | --- | --- | --- | --- | --- |
| Before FULL | true | exact_reference_within_thresholds | 17 | null | false | target_garment_identity_unverified |
| After máscara semántica | false | cluster_color_evidence_insufficient | null | null | false | target_garment_identity_unverified |

Before tiene un candidato prospectivo 17 al color NEGRO CARBON aunque no coincide con el color comercial de la prenda objetivo. After no acepta candidato prospectivo y registra literalmente cluster_color_evidence_insufficient. Ambas observaciones conservan la aceptación comercial false por identidad no verificada; el ID prospectivo nunca se aplica al producto.

### Evidencia de verificación del target del diagnóstico

| Target | Geometría de máscara válida | Confirmación visual Codex | Verificación humana | Autorización comercial |
| --- | --- | --- | --- | --- |
| imagen13 / candidata2 | true | true | false | false |

Geometría válida comprueba forma y correspondencia con la foto; no demuestra exactitud semántica ni de contorno. La confirmación de Codex se conserva como diagnóstico asistido N1. Human_verified=false y commercial_authorization=false permanecen explícitos; no se añade ground truth ni se cambia el AUTO ambiguo.

## Gate congelado y conservación de producción

| Pareja/perfil | Recursos | Clasificación | Color | Retrieval | Promoción |
| --- | --- | --- | --- | --- | --- |
| DINO Tiny + SAM2 Tiny / crop | pasa | falla | falla | falla | NONE |
| DINO Tiny + SAM2 Tiny / masked | pasa | falla | falla | falla | NONE |
| DINO Base + SAM2 Tiny / crop | pasa | falla | falla | falla | NONE |
| DINO Base + SAM2 Tiny / masked | pasa | falla | falla | falla | NONE |
| DINO Tiny + SAM ViT-B / crop | pasa | falla | falla | falla | NONE |
| DINO Tiny + SAM ViT-B / masked | pasa | falla | falla | falla | NONE |

Criterios fijados: clasificación requiere incremento ALL130 Top1≥0.05 y macroF1≥0.02, caída Top1 seleccionados≤0.02 y caída de cobertura formal≤0.02. Color requiere incremento de correctos exactos proxy ALL130≥0.10, caída de paleta≤0.02, caída de precisión condicional≤0.02, límite de aumento medio ΔE00≤2.0 y guard retenido. Retrieval requiere incremento nDCG@10≥0.05 y caídas Hit@10/Recall@10≤0.02. Falta de denominador no constituye aprobación.

Los perfiles Base tienen deltas Top1 ALL130 **−0.6000000000000000** crop y **−0.6076923076923076** masked; la mejora requerida sería positiva de 0.05. La no regresión de Top1 seleccionado sólo pasa para crop; masked cae **1/6**. La paleta ALL130 Base cae **0.60**, y su ausencia de ID prospectivo impide medir precisión condicional. nDCG@10 cae **−0.3887137255006789** crop y **−0.3879845842362977** masked. Recursos y una comparación ΔE no pareada favorable no compensan estos fallos.

`gate-decision.json` declara `winner=NONE`, `criteria_changed_after_results=false` y `production_integrated=false`. La estrategia permanece FULL para clasificación/embedding, sin asignación comercial automática de color y con color técnico heurístico existente. Los 84 tests de contratos/política/lector experimental y la revisión cualitativa de 60 filas no son un E2E desplegado ni una validación de identidad científica.

El cierre de conservación verifica **654 archivos tracked sin cambio**, **134 fotos originales SHA intactos**, manifiesto intacto, **134 embeddings canónicos idénticos**, **34 huellas comerciales iguales** y conteos IA iguales: **4 versiones metadata / 147 ejecuciones / 11 sugerencias / 2 revisiones / 134 embeddings**. Runtime original healthy/configuración idéntica. No hubo reindexado DB ni escritura comercial durante este benchmark.

## Trazabilidad de artefactos finales

Los resultados proceden de las fuentes ejecutadas conservadas en `executed-source-before-lint`. La limpieza estática posterior sólo retiró imports/variables sin uso y no repitió inferencia ni alteró los artefactos de métricas o decisión. `source-finalization.json` registra hashes antes/después; su detalle se muestra en 02. Las 84 pruebas repetidas del cierre pasan en el venv existente y Ruff F/E9 / AST también; no se confundieron con un E2E productivo.


Las rutas son relativas al árbol privado `storage/app/ai-segmentation`.

| Artefacto | SHA256 |
| --- | --- |
| consumer-full.json | d6f4f07fc57b562f5edc6a558749beba61c3fcd7575fb57f79ccbefcad19e84c |
| color-full.json | 10b98f1f917fbb861fbce568e855fceb08d86f79b64b96f0b2c3edb6edc56243 |
| consumer-comparison.json | a2bc0c577911b62c18e3956b772c1381b930294c11b8e4eec6a042e202eec4af |
| image13-assisted-consumers.json | c269a025ca8273daa61f52ff0c1d9b78ca874a906a3349eaa1e71fd55ce0a7d8 |
| visual-review.json | 4889f02a92ba638cb04b2d69b861655806cce5f6ed655afa00799ca4638d6ce1 |
| source-finalization.json | 00ec80ee9876bfe9e18ae8159a29245c48c6fa263ec4a0afa64cfabf68e47bf8 |
| gate-decision.json | 8e3d6ec3d3a78dcf9c582f729ee27332208cecd64949743fc0cd3114eb54932d |
| preservation-final.json | 00dab384f2eb31557f19e9780096c5dde894aff470c50a253d53364d49ffa72a |
| catalog-final.json | 57c9a69cc1c2ee622e7ab1bfb11b691ee66471a733642be7ea96e73a8595ee94 |
| dino_tiny_sam2_tiny/consumers.json | 437aa2cf3ea48fd6485f5a07643073b64b67809b432954dc67977700ebc2ed79 |
| dino_base_sam2_tiny/consumers.json | 2011db31265de57691e1bf79f283438703f6217af828b629b9c9c78d463dd642 |
| dino_tiny_sam_vit_b/consumers.json | 2c55bbbe09313e5c356638bbea13108bcc069dc4a7ba445ff052cc82852f4e52 |

Los artefactos completos conservan matrices de confusión, detalles por imagen, queries/rankings y candidatos. No hay ajuste posterior de thresholds, selección por GT ni reparación retrospectiva del catálogo. Para una nueva hipótesis o umbral se necesita un nuevo protocolo y una muestra de validación independiente; estos resultados se conservan tal como fueron observados.
