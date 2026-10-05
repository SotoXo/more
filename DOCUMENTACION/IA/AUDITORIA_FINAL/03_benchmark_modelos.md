# Benchmark de modelos y clasificación

## Identidad y semántica

Modelo real local google/siglip-base-patch16-224, revisión7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed, pipeline registration-1.2.0. CUDA RTX5060 y CPU se ejecutaron secuencialmente; dtype real torch.float32, no fp16 impuesto. Imagen sRGB/EXIF y processor del checkpoint224; embeddings imagen/texto se normalizan L2 y su dotproduct es el coseno. Vector runtime768, projected-image-l2-1/srgb-exif-transformers-image-1/l2, norma≈1. trust_remote_code=false ylocal_files_only=true.

[El paper SigLIP](https://arxiv.org/abs/2303.15343) define entrenamiento con pérdida sigmoid por pares. Mati no está usando esa pérdida como probabilidad: esta ruta aplica coseno, sin logits/head/logit_scale/sigmoid/softmax calibrado. Scores observados deltop3 −0.009581 a0.091519 en130 fotos. No suman1, no equivalen a porcentajes ni son comparables automáticamente con CLIP.

Abstención si top1<.08 o margen(top1−top2)<.005. Sin modificar umbrales:117/130abstenciones=90%,coverage13/130=10%,11/13aciertos cubiertos=84.62%. Laravel continúa generando sugerencias desde top3 aunque abstained sea true. Coverage describe la decisión formal upstream, no cantidad de cards aplicables.

## Resultados comerciales y análisis de errores

Dataset134 originales/131SHAúnicos/130evaluados tras deduplicar yexcluir grupoSHAcontradictorio[2,5]. Top1 83/130=63.846%,Top3 116/130=89.231%,macroPrecision.730866,macroRecall.694614,macroF1.664920,weightedF1.648533. Groundtruth comercial existente, no consenso independiente. ACCESORIOS N0 no se incluye enmacro; CONJUNTOS N3 no permite generalizar recall1.

47erroresTop1;14no contienen verdad comercial ni entop3. Principales confusiones: CAMISAS→BLUSAS6/20,POLOS Y KNITWEAR→PANTALONES5/20,VESTIDOS→BLUSAS5/18,BLAZERS→PANTALONES4/10. BLUSAS7/24predicciones correctas=29.17%Precision, pese recall7/8=87.5%. PANTALONES15/30Precision50%,recall15/15. La exactitud no resume todas las clases.

Inspección visual de originales guardada endiagnostic_images.png, sin cambiar fotos:

- Imagen31CAMISAS→PANTALONES: camisa abierta corta en persona conpantalónbeige deárea grande.
- Imagen41BLAZERS→PANTALONES: top/blazernegro corto sobrepantalónblanco largo.
- Imagen13ABRIGOS/JERSEY ALPACA→PANTALONES: jersey verde,pantalónnegro ybolsa enpersonaentera.
- Imagen33CAMISAS→BLUSAS: camisa acuadros en exterior; nomenclatura/corte comercial deblusa/camisa puede solaparse.
- Imagen90VESTIDOS→BLUSAS: minivestidorayado tipocamisola aislado, silueta próxima a top largo.

Observaciones: múltiples prendas/fondo/objetivo comercial no aislado o categorías solapadas. Hipótesis: contexto, área delpantalón, alias único, silhouette influyen. **No se demuestra causalidad** sin ablación/recorte semántico/evaluaciónexperta. No corregir labels ni afirmar que cambiarprompt arreglará cada error.

Taxonomía POLOS Y KNITWEAR mezcla polo/tejido; ABRIGOS contiene jersey; POLERA usa alias t-shirt; CAMISAS/BLUSAS solapan. Desbalance comercial de categorías/marcas/colores se registra en manifest, no análisis demográfico. Patrón confirmado0: NO EVALUABLE. pHash14 pares≤4 inspeccionados son prendas distintas; ningún near-duplicate confirmado dentro de esos candidatos. Exact SHA sí deduplicado.

## Microbenchmark modelo CPU/GPU

Entrada imagen33,11 prompts actuales,10 inferencias/device:primera+9 calientes. No equivale a analyze11 categorías+10patrones. CPU se cargó después deCUDA conOSpagecache caliente; su carga~282ms no es lectura fría dedisco. metadata/dtype/normas enmodel_identity_memory.json.

| Device | Carga ms | Primera inferencia ms | Warm p50 ms | Warm p95 ms | Warm N | dtype |
|---|---|---|---|---|---|---|
|CUDA|2835.388|553.688|30.561|35.114|9|float32|
|CPU|282.095|339.054|329.734|355.486|9|float32|

CUDAallocatedantes0;pesos775.232MiB,reserved856MiB;peak833.39MiB,allocated807.232MiB después deinferir. Globalescritorioantes1902MiB no esconsumo de modelo. AntesCPUallocated32MiB residual/contexto, no modelo CPU en GPU ni demostración defuga. TotalGPU8151MiB nvidia/8546484224bytes torch. Norma CUDA.9999999942/CPU1.0000000075. N9p95 es exploratorio, no prueba capacidad máxima.

El benchmark existente también ejecutó foto real con3 prompts/20samples/device; no se mezcla con11 prompts. FirstHTTP nativo fríoN1 diagnóstico5 categorías: wall4755.286ms,total4683.311,carga2676.392,inferencia1638.011,pico820.64MiB. Docker propioGPUN1foto real:total5031.601,carga3207.971,inferencia1659.494,pico856.83MiB. Workloads/muestras distintas; no sacar ganadorDocker/nativo con ellas.

## Pipeline real caliente

130 fotos diferentes por HTTP nativo FastAPI, modelo ya preparado: wallmin82.51/media245.90/p50221.96/p95410.82/max1081.28ms. 130/130HTTP200;8completed,122partial. Partial incluye abstención/color insuficiente, no error deHTTP. model_load retornado es telemetría de carga histórica delmanager yno se suma130veces ni al tiempo decada request caliente.

EmbeddingHTTP20repeticiones:p5016.212/p9520.892ms,20/20vectoresidénticos. RSS+128KiB,pico814.7MiBestable. Concurrencia8requests4 workers:0 errores/OOM. No fuga prolongada, loadmax ni UIE2E certificados. SQL130 queries aisladop504.330/p954.784ms,EXPLAINexecution1.180ms; no GPU en SQL.

## Comparativa y decisión

| Métrica | SigLIP actual | CLIP opcional |
|---|---|---|
|Modelo preparado|Sí,CUDA y CPU|NO EVALUABLE:pesos locales ausentes|
|Top1/Top3 N130|63.85%/89.23%|NO EVALUABLE|
|Macro-F1/coverage|.66492/10%|NO EVALUABLE|
|Dimensión/norma|768/L2≈1 medido|NO EVALUABLE,no asumir512 sin inferencia|
|Latencia/VRAM|Medidas arriba|NO EVALUABLE|
|Ventaja comercial|Limitaciones descritas|No comparación empírica|

Recomendación: **seguir evaluando**. Mantener este baseline como referencia, comparar ambos modelos preparados contra el mismo manifest/contrato/workload antes de decidir. No declarar ganador poruna sola foto ni cambiar configuración. No se descargóCLIP ni se entrenó/calibró/tuneó nada.

Reproducibilidad: evaluate_catalog_http.py,model_identity_memory.py,audit_scientific_cpu.py yscripts previos bajo scripts/audit; logs JSON bajo storage/app/ai-audit. Orden/transformaciones fijos,sin muestreo aleatorio; no se promete bitexactitud entre hardwareCUDA distinto. SHA/manifests/fecha/hardware/versiones en01. Tablas generadas directamente desde evidencia acontinuación.

## Métricas por clase

|Categoría|N|Precision|Recall|F1|
|---|---|---|---|---|
|ABRIGOS|13|0.8000|0.6154|0.6957|
|BLAZERS|10|0.6250|0.5000|0.5556|
|CAMISAS|20|0.8182|0.4500|0.5806|
|BLUSAS|8|0.2917|0.8750|0.4375|
|VESTIDOS|18|1.0000|0.6111|0.7586|
|FALDAS|7|0.8571|0.8571|0.8571|
|PANTALONES|15|0.5000|1.0000|0.6667|
|POLOS Y KNITWEAR|20|0.6667|0.6000|0.6316|
|CONJUNTOS|3|0.7500|1.0000|0.8571|
|POLERA|16|1.0000|0.4375|0.6087|

## Matriz de confusión

Filas: verdad comercial; columnas: top1. Sólo10clases con soporte, sin prediccionesACCESORIOStop1.

|Real/Pred|ABRIGOS|BLAZERS|CAMISAS|BLUSAS|VESTIDOS|FALDAS|PANTALONES|POLOS Y KNITWEAR|CONJUNTOS|POLERA|
|---|---|---|---|---|---|---|---|---|---|---|
|ABRIGOS|8|2|0|1|0|0|1|1|0|0|
|BLAZERS|1|5|0|0|0|0|4|0|0|0|
|CAMISAS|0|0|9|6|0|0|2|2|1|0|
|BLUSAS|0|1|0|7|0|0|0|0|0|0|
|VESTIDOS|0|0|0|5|11|1|1|0|0|0|
|FALDAS|1|0|0|0|0|6|0|0|0|0|
|PANTALONES|0|0|0|0|0|0|15|0|0|0|
|POLOS Y KNITWEAR|0|0|1|2|0|0|5|12|0|0|
|CONJUNTOS|0|0|0|0|0|0|0|0|3|0|
|POLERA|0|0|1|3|0|0|2|3|0|7|

## Timings internos HTTP, ms

N130por módulo; model_load histórico no incluido. No sumar percentiles.

|Módulo|N|min|media|p50|p95|max|
|---|---|---|---|---|---|---|
|validation|130|1.405|29.424|19.357|73.003|288.299|
|quality|130|1.215|23.347|23.384|45.964|56.697|
|color|130|10.841|35.152|36.367|61.675|99.079|
|barcode|130|8.157|45.227|31.073|85.757|818.161|
|inference|130|54.614|103.903|97.252|149.755|276.394|
|total|130|78.845|237.769|216.285|401.320|1072.191|

## Microbenchmarks CPU por módulo

N20más3warmups,imagen31,375×563/28238bytes. Foreground/color usan rama insuficiente deesta foto, no extracción completa. Matching129 vectores reales; parser usa texto sintético,no OCR. InferenceML excluida.

|Módulo|N|p50ms|p95ms|media|Primera llamada|
|---|---|---|---|---|---|
|image_validation|20|1.402|1.689|1.448|7.992|
|quality|20|1.786|2.045|1.785|10.091|
|foreground|20|10.225|12.773|10.409|39.073|
|color|20|12.884|17.100|13.816|17.648|
|classification_ranking_postprocess|20|0.004|0.013|0.010|0.142|
|color_contract_postprocess|20|0.005|0.006|0.005|0.186|
|barcode|20|8.975|11.199|9.359|12.856|
|label_normalization|20|0.368|0.410|0.368|1.298|
|internal_matching|20|45.255|50.603|45.813|47.407|
|compatibility_insufficient|20|0.042|0.053|0.043|0.193|
|compatibility_completed|20|0.109|0.173|0.119|0.322|
|publication|20|33.702|36.983|33.959|51.884|

## Prompts baseline sin modificar

- POLERA: This is a photo of t-shirt.
- ABRIGOS: This is a photo of coats.
- BLAZERS: This is a photo of blazers.
- CAMISAS: This is a photo of shirt.
- BLUSAS: This is a photo of blouse.
- VESTIDOS: This is a photo of dress.
- FALDAS: This is a photo of skirt.
- PANTALONES: This is a photo of trousers.
- POLOS Y KNITWEAR: This is a photo of polo shirt or knitwear.
- ACCESORIOS: This is a photo of fashion accessories.
- CONJUNTOS: This is a photo of matching clothing sets.
