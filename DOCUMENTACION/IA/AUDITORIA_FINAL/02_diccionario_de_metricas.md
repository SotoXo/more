# Diccionario de métricas y scores

Una **métrica** resume resultados frente a una referencia o un procedimiento medido. Un **score** puntúa una entrada según un modelo/regla. Una probabilidad requiere un significado probabilístico y calibración comprobable. Mati no calibra cosenos, color ni ranking; no permite convertir0.8 en «80% de certeza».

## Clasificación: exactitud y Precision son distintas

TP = verdaderos positivos de una clase; FP = otras clases predichas como ella; FN = ejemplos de ella asignados a otra. N es el número de ejemplos evaluados. Todas las razones siguientes van de0 a1, mayor suele ser mejor, pero deben acompañarse de soporte y cobertura.

| Métrica y fórmula | Ejemplo | Uso/interpretación en Mati |
|---|---|---|
|Accuracy/exactitud = aciertos/N|8 fotos bien de10:0.8|Top1 83/130=0.63846; mide aciertos globales contra categoría comercial. No se llama Precision|
|Precision de clase = TP/(TP+FP)|Dice «blusa»10 veces,6 eran blusas:0.6|Pureza de predicciones: BLUSAS7/24=0.29167. No pregunta cuántas blusas totales encontró|
|Recall de clase = TP/(TP+FN)|Había8 blusas, detecta6:0.75|Recuperación de ejemplos reales: BLUSAS7/8=0.875. Alto recall puede coexistir con baja Precision|
|F1 = 2PR/(P+R) = 2TP/(2TP+FP+FN)|P0.6,R0.75 → F1 0.6667|Balance Precision/Recall; BLUSAS0.4375. Si no hay TP se usa0, no descartar errores|
|Macro-Precision/Recall = media simple de P_c/R_c|Dos clases0.9 y0.3:0.6|Todas las clases representadas pesan igual;0.73087/0.69461. No confundir con micro|
|Macro-F1 = (Σ F1_c)/C|Clase grandeF1.9 ypequeña.3:macro.6|0.66492, C10 con soporte. No es F1 calculado usando macroP/macroR|
|Weighted-F1 = Σ(n_c/N)F1_c|90 ejemplosF1.9,10F1.3:0.84|0.64853; refleja distribución comercial y puede ocultar clase pequeña|
|Top-K accuracy = Σ 1[y_i∈topK_i]/N|8/10 etiquetas entre3:0.8|Top3 116/130=0.89231. Tres opciones no son tres aciertos ni multiplican N|
|Coverage = decisiones no abstained/N|Sistema decide2 de10:0.2|13/130=0.10. Abstención90%; no confundir con filas top3 disponibles|
|Accuracy cubierta = aciertos no abstained/decisiones no abstained|Decide2,acierta1:0.5|11/13=0.84615, N13; no reemplaza exactitud83/130. Si decide0 es NO EVALUABLE|
|Abstention rate = abstenciones/N =1−coverage|8/10:0.8|117/130=0.90 por top1<.08 o margen<.005|
|Matriz M_ab = número real a/predicho b|2 camisas→blusas:celda2|Filas reales/columnas predichas; suma130. Diagonal83. No transponer al interpretar|

Accuracy evalúa todas las decisiones; Precision evalúa cuántas predicciones de una clase fueron correctas. En español «precisión» coloquial suele referirse a ambas; aquí usamos **exactitud (Accuracy)** y **Precision de clasificación** para evitar ambigüedad. Ground truth actual es etiqueta comercial, no revisión independiente experta. ACCESORIOS N0 no se incluyó en macro; CONJUNTOS N3 limita fiabilidad. No hay entrenamiento ni holdout externo.

## Coseno, distancias y recuperación

Cosine similarity s(x,y)=(x·y)/(||x||₂||y||₂), rango[−1,1], mayor =direcciones más alineadas. Si vectores L2 unitarios, s=x·y. Ejemplo(1,0) y(0,1) →0; iguales →1; opuestos →−1. Norma0 hace coseno indefinido: se rechaza, no asignar0 como score válido. Norma L2=√Σx_j²; embeddings SigLIP768 observados≈1.

Cosine distance d=1−s, rango[0,2], menor =vecino más cercano. Ejemplo s.8→d.2. pgvector usa operador <=> y devuelve similarity1−distance; no invertir orden ni confundirlo con distancia euclídea. Exact SHA igualdad de bytes ≠pHash cercano ≠coseno alto ≠identidad comercial.

La comparación sólo tiene sentido en el mismo modelo/revisión/pipeline/preprocesador/dimensión/geometría/normalización. Dos vectores de longitud768 no necesariamente comparten espacio. No compara cosenos entre CLIP y SigLIP como si tuvieran la misma escala semántica.

Sea R_q el conjunto de todos los positivos de una query y L_q(K) los primerosK resultados. En esta evaluación, positivo significa **misma categoría comercial, distinto producto/SHA**, no mismo producto ni preferencia humana.

| Métrica y fórmula | Ejemplo | Mati |
|---|---|---|
|Recall@K = media_q count(R_q∩L_q(K))/count(R_q)|Hay10 positivos;top5 contiene2→.2|@1 .05314,@5 .16681,@10 .25551. Q130. Queries sin positivos se excluirían y reportarían, aquí0|
|Hit@K = media_q 1[count(R_q∩L_q(K))>0]|Mismos2 positivos→Hit1|@1 .58462,@5 .87692,@10 .97692. No llamar «Recall» alHit|
|Precision@K = media_q hits/min(K,galería)|2 positivos en 5→.4|@10 .32923, mismo proxy|
|MRR = media_q (1/rank primer relevante)|Primer positivo enpuesto4→.25|.70305 usando galería completa, no MRR@10. Sin positivo→0|
|DCG@K = Σ_i(2^rel_i−1)/log₂(i+1)|Binario:acierto enpuesto1 pesa1;puesto2 .63093|Aquí rel∈{0,1}, y2^rel−1=rel|
|nDCG@K = DCG@K/IDCG@K|Sólo positivo enpuesto2: .63093/1=.63093|@10 .40206. IDCG orden ideal de min(10,positivos). Rango[0,1], mayor mejor|
|Acuerdo topK = queries con orden/set igual/Q|9 de10 consultas coinciden:.9|SQL vs NumPy top30:130/130 orden ysets; errorabs máx2.389e-7. Valida cálculo, no relevancia|
|Candidate/index coverage = candidatos indexados/elegibles|100 de134:.7463|Soft matching100/134=74.63%; persistencia aislada130/130=100% sobre subset evaluado. No cobertura real134/134 en principal|

pgvector es una extensión de PostgreSQL: conserva tablas, relaciones, restricciones y transacciones. Nearest neighbor busca los vectores de mínima distancia; aquí búsqueda exacta con filtros y escaneoO(Nd), ranking posterior. ANN aproxima vecinos con índices HNSW/IVFFlat; no existe aquí yN130/latencia no obliga a agregarlo. No hay RAG implementado.

## Color, máscara, calidad y estabilidad

CIELAB_D65 usa L* para luminosidad[0,100], a* eje verde↔rojo yb* azul↔amarillo; no son probabilidades. C*=√(a*²+b*²) mide croma, no brillo; h=atan2(b*,a*) es matiz. SaturaciónHSV media[0,1] usa otra definición, no sustituye C*. HEX sRGB se convierte aLab; una cámara sin calibración no mide directamente el color físico del tejido.

ΔE2000 = √[(ΔL′/(k_L S_L))²+(ΔC′/(k_C S_C))²+(ΔH′/(k_H S_H))²+R_T(ΔC′/(k_C S_C))(ΔH′/(k_H S_H))]. CIEDE2000 corrige coordenadas de croma/matiz y pondera sensibilidad conS_L,S_C,S_H y términoR_T; aquí k_L=k_C=k_H=1. Rango no negativo, menor diferencia perceptual. IdénticosLab→0. No es distancia RGB euclídea ni ΔE76. [Sharma/Wu/Dalal](https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/ciede2000noteCRNA.pdf) y sus34 pares se comprobaron con error≤0.00005. Eso valida aritmética de esos casos, no iluminación ni máscara.

Mati compara cluster al catálogo para elegir candidato y usa thresholds internosΔE≤10/margen≥2 (excepcióndistancia≤2), no umbrales universales de imperceptibilidad. La métrica de auditoría ΔE al color **esperado** no es necesariamente el delta_e del output, que apunta al candidato más cercano. Contra HEX esperadoN84:media14.497,mediana 9.968,p90 29.634, proxy comercial; no exactitud física.

| Medida y fórmula | Ejemplo | Mati /limitación |
|---|---|---|
|ColorID accuracy cubierta = IDprincipal correcto/primary conID|18 correctos de35:.5143|18/35=51.43%;coverage35/130. No decir51.43% de130|
|Familia accuracy cubierta = familia correcta/salida familia|36 de63:.5714|36/63primary;47/84topcluster. Expected fam_col; salida puede usar catálogo, noindependiente|
|Share paleta = pixels/peso delcluster entre foreground retenido|600 de1000:.6|Porcentaje no esconfidence ni área física de outfit; clusters pequeños descartados reducen coverage, sin renormalizar percentage; compatibilidad sí normaliza la paleta retenida|
|IoU máscara = intersección/union máscara predicha yhumana|40 de50:.8|NO EVALUABLE: máscara humana ausente; foreground_confidence no equivale IoU|
|Laplacian variance = Var(∇²gris)|Textura intensa puede darvaralta|blur_score: mayor suele indicar detalle;threshold<50 alerta. Uniformidad/textura/fondo confunden, no accuracy blur|
|Shadow/highlight fraction = pixels gray<10 /gray>245 entre todos|700 blancos/1000:.7|.55shadow/.65highlight alertas; fondo blanco ≠ foto sobreexpuesta demostrada|
|Contraste = P95(gris)−P5(gris)|200−50=150|<30 alerta técnica; no composición estética|
|Agreement transform = salidas iguales/comparables|8 IDs iguales entre8 presentes|Informar supervivencia15/20 y8/11IDbase, no ocultar abstención como100% robustness|
|Jaccard paleta IDs = count(A∩B)/count(A∪B)|A{1,2},B{2,3}→1/3|Sólo unión vacía NO EVALUABLE; pierde todos IDs base→0. No mideΔE de paleta|
|ΔE estabilidad = ΔE(outputoriginal,outputtransform)|Mismo cluster→0|No usa groundtruth: estabilidad puede conservar un error; distintas de accuracy|

Paleta está disponible84/130;primary63/130,IDprincipal35/130. Una única etiqueta variante no confirma monocolor. Foregroundconfident de persona entera no implica máscara dela prenda. Primary/palette/temperature/lightness/chroma/contrast/family pueden usarse luego en reglas cromáticas, sin demostrar recomendación personalizada.

## Qué significa cada score implementado

| Campo | Fórmula/procedencia | Rango/dirección | Ejemplo y significado |
|---|---|---|---|
|SigLIP similarity_score|Dot producto de features imagen/texto normalizados L2; top3 ymargin|[−1,1],mayor afinidad|.08 no es8%probabilidad. Es parecido conprompt en espacio actual; abstiene<.08 omargin<.005|
|foreground_confidence|Reglas de fondo/bordes/componente/soporte/forma|[0,1],heurístico|.94 puede cubrir persona con otras prendas; no94%de máscara correcta|
|Color confidence_score|foreground × illumination_confidence × max(.35,1−cluster_spread_p90/30)|[0,1],heurístico|.94×.9×.8=.6768. Evidencia compacta/fiable; no probabilidad de color correcto|
|catalog_match_confidence_score|Si aceptado:confidence × max(0,1−ΔE/15),si no0|[0,1],heurístico|confidence.7,ΔE3→.56; no accuracy ni confianza estadística|
|OCR regionconfidence|Valor EasyOCR por región, si motor produce|[0,1],no calibrado aquí|No tuvo inferencias útiles; parser marca calcula coincidencia textual separada|
|Brandcandidate confidence_score|Exact/fuzzy del texto normalizado vs catálogo, no incluye confianza OCR|[0,1]|Texto exactoZARA→1 incluso si OCR región tuviese .01 (control mock); identidad no demostrada|
|Barcode confidence|No estimada|null|Checksum true acredita dígito verificador, no identidad real del producto ni probabilidad decoder|
|Matching Python score|TierSKU1; GTIN válido exacto.98; modelo exacto.94si marca/.86si no; soft min(.79,.35text+.32visual+.45SHAexact+.05brand+.04category+.03color)|[0,1],ranking heurístico|GTIN conflict cap.05,MODEL×.4,BRAND×.5. Evidencia exacta prioritaria, no probabilidad|
|Matching Laravel exacto|Reglas propias SKU/GTIN1,modelo.86,conflicto.05|[0,1],ranking|SKU CAM-00031→1UIaislada; GTINLaravel1 distinto de Python.98, no discrepancia de accuracy|
|External final_score|GTIN.95+.04text; modelo.85conmarca/.78sin+.04text; soft .40text+.25visual+.10brand+.05attributes|[0,1],ranking|GTINconflict0; marca/modeloconflict=min(.2score,.15). Fuentesactivas0, sin accuracy externa|
|pgvector similarity|1−distance_cosine|[−1,1],mayor cercanía|≥.98flag possible_duplicate esheurística; mismo SHA bytes esotraevidencia|
|Compatibility score|100×Σ(w_j c_j)/Σw_j disponibles, c_j interno en[0,1]; pesos temperature.10/lightness.20/hue.55/contrast.15|[0,100],reglas|Componentes faltantesseexcluyen; perfiles insuficientes→null, no0. Sinjueces no estética accuracy|
|UI manual closestcolor|Distancia euclídeaRGB entre promedio canvas yswatch; thresholds24/95|Distancia≥0,menor mejor|NoML niLab/ΔE00; no mezclar conservercolorimetry|

Compatibilidad evalúa pares de paletas observadas, masas normalizadas sin duplicar roles primary/secondary/accent. Cada prenda pesa.5, no estima área física del outfit. Matiz se aproxima enruedaartísticaRYB desde HSV, lightness/chroma enLab. Reglas monocromática/análoga/complementaria/split/triádica/neutros/acento/claroscuro explican señales; no gusto personal, temporada aprendida ni outfits recomendados. Sus465 pares sintéticos y435reales validan simetría/rango, no calidad estética.

ai_suggestions.confidence_score guarda señales diferentes y las limita[−1,1]. El nombre no convierte todas en una única confianza/probabilidad; evidence/source conserva parte del contexto. Es una incoherencia semántica para exportación, aunque UI dice «puntuación» y advierte que similitud no es probabilidad/identidad.

## OCR, barcode y matching evaluable

CER = (S+D+I)/número de caracteres referencia, con distancia Levenshtein mínima: sustituciones,borrados,inserciones. ReferenciaGATO vsPATO:1/4=.25. WER usa tokens/palabras con el mismo cociente; una frase de4palabras conuna sustitución da.25. Rango≥0,pueden superar1 porinserciones; menor mejor. Referencia vacía exige política explícita, no dividir por0. Normalización (acentos,espacios,puntuación) debe congelarse antes de evaluar.

Mati intentó3 etiquetas sintéticas con OCR real yno produjo texto (OCR_UNAVAILABLE); transcripciones fotográficas reales0. CER/WER **NO EVALUABLE**, no0% ni100%. Parser3/3sobretextos suministrados sólo acredita parsing. F1por entidad marca/talla/modelo/composición usaría TP/FP/FN bajo normalización/exactitud acordadas, no se computa contra candidatos generados por el mismo OCR. No hay groundtruth real para ello.

Barcode success = imágenes con valor normalizado esperado recuperado/imágenes evaluadas;6/6 originales,30/36 variantes. Symbology accuracy = formatos correctos/formats evaluables:6/6 originales. Checksumvalidrate = GTIN detectados conchecksumtrue/GTIN detectados:20/20 variantes detectadas; si incluye los4fallidos,20/24detección con checksum válido. QR/CODE128 no tienenchecksumGTINaplicable; null no es false. Crop20%0/6, no generalizar83.33% a fotografías de etiquetas.

MatchingTop1/hit/relevance/identity necesitan juicios independentes por candidato; no usar score alto comogroundtruth. Sólo1SKUexacto y1textoUI son muestras operativas, no porcentaje general. Las métricas retrieval existentes se limitan a misma categoría comercial.

## Performance, estados, aceptación y confiabilidad

p50 = percentil50/mediana: mitad de observaciones hasta ese valor. p95 = valor bajo el cual quedan aproximadamente95%; aquí NumPy interpola entre ordenados. Ejemplo1,2,3,4,100 →p50=3,p95=80.8 porinterpolación. No significa95%de confianza ni que95%deusuarios reales veránese tiempo. Informar min/media/p50/p95/max,N,warmups,device,workload,CPU/GPU,frío/caliente.

Coldload mide carga modelo/procesador; primera inferencia incluye inicialización/kernels. Warm9 del microbenchmark excludes primera; CPU carga tras CUDA yOSpagecache no es frío dedisco. AnalyzeHTTPN130 incluye bytes/validación/calidad/color/barcode/clasificación/patrón, mientras embeddingHTTPN20 usa otra ruta ymismafoto; no comparar ambos comosimilarworkload. P95N9 es exploratorio. Las sumas de percentiles de módulos no sonpercentil total. model_load repetido es telemetría histórica delmanager, no costo cada request.

VRAM torchallocated mide tensores delproceso; reserved incluye memoria delallocator; peak mide máximo desde reset. nvidia-smi global incluye escritorio/otrosprocesos. MiB=2²⁰bytes frenteaMB=10⁶bytes. PesoGPU775.23MiB/pico833.39MiB no es global1902MiB previo. RSSmemoriaCPU refleja residencia proceso;20 inferencias+128KiB no prueban ausencia defugas a largo plazo. Concurrencia0 errores/8requests4 workers prueba ese control, no capacidad máxima.

HTTPsuccess = respuestas2xx/requests;130/130. Envelope completed = respuestascompleted/N;8/130,partial122/130. No son calidad ni accuracy; una abstención puede ser respuesta HTTP correcta. E2Esuccess = casos quecumplen escenario/casos seleccionados;2/7=28.57% UIaislada. Es cobertura diagnóstica de7 casos, no estimación de tráfico real.

Acceptance rate = decisiones humanas únicas accepted/(accepted+corrected+rejected), definiendo etapaform/saved. Correction/rejection rates análogas. Saved acceptance exige comparar confirmado con valor realmente guardado; unclick no prueba guardado ni verdad científica. DatosprincipalesIAausentes: **NO EVALUABLE**. Replay de2POST idénticos produjo2formreviews en aislado; contarfilas inflaría tasas. No se usa el fixture para simular aceptación real ni entrenamiento.

Idempotencia = repetir mismo evento/identidad no produce efectos adicionales indebidos. Vectores canónicos aislados mantienen130trasreplay;feedback form falla esa propiedad. Preparación usa tokens deconsumo yTTL; no prometerbyteidentidad/requestidigual para toda operación porque son eventos distintos.

La confiabilidad depende de la referencia: matemática34paresySQLNumPy prueban cálculo; labels comerciales son proxies; fixtures sintéticos prueban controles; mocks prueban contratos; fotos reales+modelo real prueban comportamiento del baseline actual. Ninguna categoría convierte un score en probabilidad calibrada ni sustituye ungroundtruth ausente.
