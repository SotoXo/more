# 02. Benchmark de candidatos de segmentación semántica

**Fecha de cierre: 2026-10-05. Resultado: ninguno se promueve a producción.** Las tres parejas ejecutaron las 130 imágenes sin OOM ni errores de inferencia registrados y cumplieron los límites de recursos medidos. La selección automática fue 0/130, 6/130 y 0/130 respectivamente. Los efectos en consumidores y el rechazo cuantitativo se detallan en [04_resultados_n130.md](/home/soto/Code/more/DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/04_resultados_n130.md). Una máscara visible o un estado AUTO no verifica científicamente la identidad de una prenda.

## Protocolo, muestra e aislamiento

El protocolo se congeló el **2026-10-05T01:43:34.216494Z**, antes de cualquier inferencia candidata. SHA256: `5cdd1eba3ab99d862d039a70a93769f821d7c4ba8c89f8a6508025b6ce6ba0f4`. La congelación de ejecución, después de preparar pesos, ocurrió a las **13:19:46.060005Z**. Las ejecuciones terminaron a las 13:21:11.236090Z, 13:22:56.293637Z y 13:24:12.617447Z.

La muestra es la lista exacta de 130 IDs y 130 SHA distintos de `protocol.json`. El manifiesto comercial tiene 134 imágenes; se excluyen 2 y 5 por el conflicto de categoría de su SHA compartido, y las copias 11 y 15, conservando 10 y 14. SHA del manifiesto: `f24d4901949a70769df86f8b85fce7ee85d5ed5e0b90c1bb57e8b77c07c61c5e`. Sus categorías son etiquetas comerciales confirmadas, con diez categorías presentes; no son anotaciones independientes de objetivo o máscaras. Las etiquetas comerciales no se pasaron al detector, segmentador ni selector AUTO.

Se usó un runner Docker separado, imagen `sha256:4cb86e63d5752679f4d901554e4fd63f663f743f21ffc76061f571d4f08c229f`, proyecto montado en lectura, sin red, puertos, token ni conexión DB y usuario `1000:1000`. La preparación descargó una vez a `/cache/segmentation`, separado del árbol previo de SigLIP en el mismo volumen persistente. Las inferencias fueron offline, con `local_files_only=True`, `use_safetensors=True` y `trust_remote_code=False`; no se instaló una dependencia productiva ni se cambió FastAPI. Una pareja permaneció residente por turno y las parejas se ejecutaron en secuencia.

Los artefactos se guardaron exclusivamente bajo `storage/app/ai-segmentation`, fuera de `storage/app/public`, con directorios 700 y archivos 600. Los JSON contienen referencias relativas al árbol privado; las máscaras binarias son PNG, sin matrices voluminosas incrustadas ni URLs públicas.

## Modelos realmente cargados

| Modelo oficial | Revisión inmutable | Peso safetensors, bytes | SHA256 peso |
| --- | --- | --- | --- |
| IDEA-Research/grounding-dino-base | 12bdfa3120f3e7ec7b434d90674b3396eccf88eb | 933400872 | 5548f844c928c4b6f411fa8cbcc2bfa8dbbba437cb1d513975519f93c2a9ed21 |
| IDEA-Research/grounding-dino-tiny | a2bb814dd30d776dcf7e30523b00659f4f141c71 | 689359096 | 1a2412ef99bd74bcd3c2a246fa1e48581f8889a1300c9051974741314fc042f3 |
| facebook/sam-vit-base | 70c1a07f894ebb5b307fd9eaaee97b9dfc16068f | 374979480 | 892c410e496344e527255ccdcb2cb7244a609acb5389c7c4fdba1288f861c579 |
| facebook/sam2.1-hiera-tiny | de431c4043854a71d8101e17995dfe596bf101a5 | 155908064 | 48c14467e5cf9e51870511feb72c89688e82dd74523142c0538b663e193ac2a7 |

Los cuatro checkpoints coinciden con las revisiones y hashes investigados. `model-lock.json` fue preparado a las 02:02:34.498944Z y registra licencia declarada Apache-2.0 de cada checkpoint, archivos/configuración y hashes. Las licencias de datos de entrenamiento se documentan aparte en [01_investigacion_modelos.md](/home/soto/Code/more/DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/01_investigacion_modelos.md); la licencia de código/pesos no concede derechos sobre cada imagen de entrenamiento.

DINO Tiny y Base difieren también en mezcla de entrenamiento. Compararlos identifica el efecto de cambiar el detector publicado, sin atribuir toda diferencia a arquitectura o cantidad de parámetros. Cambiar SAM2.1 Tiny por SAM ViT-B manteniendo DINO Tiny permite observar el cambio de segmentador dentro de este protocolo.

## Procesamiento y selección fija

1. Los bytes originales y su SHA se validaron con `mati_ai.vision.validation.validate_image`; se conservó el tratamiento productivo de EXIF, ICC y conversión sRGB. La reducción adicional a dimensión máxima 2048 se aplicó sólo al input de los modelos, después de validar.
2. DINO recibió el texto fijo: `shirt. blouse. t-shirt. polo shirt. sweater. cardigan. blazer. jacket. coat. trousers. jeans. skirt. dress. shorts. jumpsuit.` Los umbrales fueron box 0.25 y text 0.20. El processor de los snapshots DINO usa shortest_edge 800 y longest_edge 1333.
3. Las frases completas quedaron en `raw_label`. Sólo clases exactas de la whitelist y alias ortográficos estrictos pudieron obtener máscara; no se eligió una palabra de una frase que mezclaba clases. No se usó clase comercial, caja más grande ni score máximo para forzar un objetivo.
4. SAM recibió cajas válidas del detector, `multimask_output=False`, binarización de máscara a 0.0 y tamaños fijados: SAM2.1 Tiny 1024×1024; SAM ViT-B longest_edge 1024 con padding 1024×1024. Las máscaras se reescalaron con nearest neighbor a las dimensiones originales ya validadas.
5. El crop corresponde al bbox ajustado de la máscara binaria. El masked crop usa ese bbox y sustituye el exterior por RGB `[127,127,127]`; la foto original no se modifica.
6. La política `target-selection-benchmark-1` valida clases, geometría, máscara, contexto/identidad de la foto y ownership del benchmark. Una sola candidata válida puede quedar bloqueada si existe otra candidata plausible inválida. Varias válidas mantienen ambigüedad. Alias estrictos se deduplican por IoU de bbox ≥0.85; esto no es IoU contra ground truth. El rango fijo de área es 0.005–0.90. Más de 12 detecciones da `ambiguous/MAX_CANDIDATES_EXCEEDED`, sin truncar a una prenda elegible. Contacto con ≥2 bordes o clipping >0.03 impide AUTO.

Versiones: Python **3.11.17**, PyTorch **2.13.0+cu130**, CUDA runtime **13.0**, Transformers **5.14.1**. Parámetros float32 y forwards bajo `bfloat16_autocast`; DINO desactiva kernels personalizados para evitar compilación JIT. Pipeline `grounding-dino-sam-box-single-mask-1`; preprocess de modelos `validated-srgb-exif-icc-max2048-transformers-pinned-1`. Perfiles consumidores: FULL `srgb-exif-transformers-image-1`, crop `target-box-srgb-siglip-1`, masked `target-mask-gray127-srgb-siglip-1`.

## Coberturas distintas: detección, validez y autorización AUTO

| Pareja | Imágenes con raw /130 | Imágenes con ≥1 válida /130 | Exactamente 1 válida /130 | AUTO /130 | Reason NO_VALID_CANDIDATE | Lista válida vacía |
| --- | --- | --- | --- | --- | --- | --- |
| DINO Tiny + SAM2.1 Tiny | 130/130 (100.0000%) | 85/130 (65.3846%) | 61/130 (46.9231%) | 0/130 (0.0000%) | 44 | 45 |
| DINO Base + SAM2.1 Tiny | 130/130 (100.0000%) | 96/130 (73.8462%) | 35/130 (26.9231%) | 6/130 (4.6154%) | 34 | 34 |
| DINO Tiny + SAM ViT-B | 130/130 (100.0000%) | 85/130 (65.3846%) | 61/130 (46.9231%) | 0/130 (0.0000%) | 44 | 45 |

`Raw` cuenta cualquier salida del detector, aunque su frase/clase sea rechazada. `Válida` cuenta candidatas después de validación y deduplicación de la política, y no equivale a objetivo correcto anotado. `Exactamente una válida` no equivale a AUTO: Tiny tiene 61 imágenes con una candidata válida y cero AUTO porque queda evidencia plausible rechazada. `Lista válida vacía` incluye el caso de overflow que no pasó por evaluación de máscaras. Por eso Tiny tiene 45 listas vacías, pero 44 motivos `NO_VALID_CANDIDATE`; la imagen restante es ambigua por overflow.

Los totales de candidatas raw son **539 / 541 / 539** y los de candidatas válidas después de política **114 / 205 / 114**, en orden de las tablas. No son denominadores de accuracy. Las seis AUTO de DINO Base son IDs **16, 17, 18, 19, 20 y 21**: cobertura **6/130 = 4.6153846%**. No se añadieron selecciones asistidas a esa lista.

| Pareja | selected | ambiguous | insufficient | 1 válida + plausible inválida | Múltiples válidas | Overflow |
| --- | --- | --- | --- | --- | --- | --- |
| DINO Tiny + SAM2.1 Tiny | 0 | 86 | 44 | 61 | 24 | 1 |
| DINO Base + SAM2.1 Tiny | 6 | 90 | 34 | 29 | 61 | 0 |
| DINO Tiny + SAM ViT-B | 0 | 86 | 44 | 61 | 24 | 1 |

Cada fila suma 130 en las tres columnas de estado; las columnas de motivos desglosan causas y no se suman otra vez a los estados. Para Tiny, los motivos son 61 `PLAUSIBLE_INVALID_CANDIDATE`, 24 `MULTIPLE_PLAUSIBLE_GARMENTS`, 44 `NO_VALID_CANDIDATE` y un overflow. Base registra 29, 61, 34 y cero respectivamente, además de seis `SINGLE_VALID_GARMENT`.

## Geometría descriptiva de máscaras, sin ground truth

| Pareja | Máscaras no vacías N | Tocan ≥1 borde /N | Tocan ≥2 bordes | Área min | Área p10 | Área mediana | Área p90 | Área max |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| DINO Tiny + SAM2.1 Tiny | 127 | 4/127 (3.1496%) | 0 | 0.003798697 | 0.107944937 | 0.203992895 | 0.468401865 | 0.597471640 |
| DINO Base + SAM2.1 Tiny | 211 | 5/211 (2.3697%) | 0 | 0.001293073 | 0.076930728 | 0.227791667 | 0.491280000 | 0.589076000 |
| DINO Tiny + SAM ViT-B | 127 | 6/127 (4.7244%) | 1 | 0.003642392 | 0.067043296 | 0.202576673 | 0.458499227 | 0.626811802 |

El denominador N aquí es cada candidata con evidencia de máscara binaria no vacía, incluidas candidatas después rechazadas por el rango de área. Por eso el mínimo puede quedar por debajo de 0.005 sin autorizar AUTO. Área = píxeles de máscara / píxeles de la foto original validada; percentiles con interpolación lineal. Estos números no miden precisión de contorno, IoU, Dice, mAP ni exactitud semántica.

Las máscaras se obtuvieron en **86/130**, **97/130** y **86/130** imágenes. Las imágenes con al menos una máscara que toca un borde fueron **4/130**, **2/130** y **6/130**. Esto tiene otro denominador que la tasa por máscara de la tabla. La revisión cualitativa fijada de 20 imágenes por pareja produjo 60 filas, completadas en `visual-review.json`; el anotador es **Codex, no humano**, y no hay máscara ground truth.

## Latencia real y definición de caliente

| Pareja | Carga local cold ms | Pipeline N | Pipeline p50 ms | Pipeline p95 ms | Warm N | Warm p50 ms | Warm p95 ms |
| --- | --- | --- | --- | --- | --- | --- | --- |
| DINO Tiny + SAM2.1 Tiny | 5458.992041 | 130 | 330.808217 | 1544.663642 | 129 | 330.129931 | 1467.240081 |
| DINO Base + SAM2.1 Tiny | 6012.173313 | 130 | 390.085576 | 2207.912818 | 129 | 386.982533 | 2208.554683 |
| DINO Tiny + SAM ViT-B | 5561.309767 | 130 | 360.201618 | 1531.349249 | 129 | 356.983958 | 1534.519176 |

La carga cold comprende `from_pretrained` local y transferencia a CUDA después de verificar integridad del lock e inicializar CUDA. No incluye descarga, verificación SHA de pesos ni arranque completo de Docker. El tiempo total por imagen incluye validación, processors, detector, segmentador cuando aplica, política y escritura de artefactos privados; no incluye consumidores SigLIP/color/retrieval. Las imágenes sin máscara ejecutan menos trabajo y también forman parte del N130 operativo.

El conjunto caliente tiene **N129** en cada pareja: se excluyó ID1, que completó la primera pasada de los modelos. No se hizo una inferencia extra de warmup. Los percentiles describen una pasada y una máquina; no son una garantía de producción ni un benchmark concurrente.

## VRAM y gate de recursos

| Pareja | Libre antes carga MiB | Peak allocated MiB | Peak reserved MiB | Incremental reserved MiB | Libre mínimo observado MiB | OOM | Códigos error |
| --- | --- | --- | --- | --- | --- | --- | --- |
| DINO Tiny + SAM2.1 Tiny | 7019.000 | 2131.848 | 3090.000 | 3090.000 | 3893.000 | 0 | 0 |
| DINO Base + SAM2.1 Tiny | 6873.000 | 2367.956 | 3248.000 | 3138.000 | 3735.000 | 0 | 0 |
| DINO Tiny + SAM ViT-B | 6873.000 | 2365.370 | 3242.000 | 3132.000 | 3741.000 | 0 | 0 |

La GPU real fue **NVIDIA GeForce RTX 5060**, memoria reportada **8150.5625 MiB**. El driver anotado antes de ejecución fue **591.86**. Reserved es el allocator del proceso PyTorch y no toda la memoria global ocupada por el sistema. El incremento se resta de la reserva previa de cada pareja: cero para la primera y 110 MiB para las siguientes. No se presentan tamaños de safetensors como medidas de VRAM.

El runner exigió ≥2048 MiB libres antes de cargar una pareja, ≥1024 MiB antes/después de cada forward y peak incremental reserved ≤6144 MiB. Los tres p95 calientes fueron <5000 ms. `error_counts` está vacío en las tres salidas finales y no se registró OOM: cumplen el gate de recursos **en este ensayo**. Esa aprobación de recursos no aprueba ningún consumidor.

## Fallo observado del lector, reparación y pruebas

Hubo un fallo real del lector: el diagnóstico buscó una frase mixta sin máscara aceptada y recibió `mask_reference=None`, provocando `INVALID_ARTIFACT_REFERENCE`. El log fallido se conserva en `consumer-comparison-failed-diagnostic.log`; esa tentativa no se reportó como resultado válido.

La reparación aisló el diagnóstico asistido de imagen13 fuera del lector AUTO y añadió validación estricta de estado, modo, policy version, IDs coincidentes y referencias privadas no nulas. Se incorporaron **10 pruebas**; la suite final aislada registra **84 passed in 1.49s** frente a 74 en el freeze inicial. SHA del lector antes de reparar: `dcfadf39e4e3eda68bf9c51aa0e9a5b4592940835a86f800c11cf840a0454c90`; SHA del lector realmente ejecutado tras reparar: `df48c9f19137aeaf3b89d912fa97d01111596d1c31ef3674b51dcc0040dc4947`.

La reparación no cambió protocolo, modelos, revisiones, prompts, umbrales, máscaras candidatas, fórmulas de color ni política AUTO. La comparación final proviene de la lectura reparada. El diagnóstico13 se mantiene N1, Codex asistido, fuera de cualquier agregado. Las 84 pruebas cubren scripts experimentales; no convierten en verdes los fallos P2 productivos previamente documentados.

## Trazabilidad tras limpieza estática

A las 13:57:43.807192Z se retiraron únicamente imports y un mapa local sin uso de cinco archivos benchmark. No cambió lógica, parámetros, gate, prompts ni umbrales. No se repitió inferencia. Las fuentes realmente ejecutadas se conservaron bajo el árbol privado `executed-source-before-lint`; `source-finalization.json` registra ambos hashes, comprobados para este informe.

El cierre estático de la sesión repitió la suite en el venv existente: **84 passed in 1.52s**, Ruff limitado a **F/E9** pasa y AST de 11 scripts pasa. El contenedor productivo carecía de pytest/Ruff: el intento allí falló al localizar herramientas y no ejecutó pruebas; no se instalaron. Esto se distingue del log de 84 tests / 1.49s que acompañó los resultados.

| Fuente benchmark | SHA256 ejecutado preservado | SHA256 final estático |
| --- | --- | --- |
| evaluate_consumers.py | df48c9f19137aeaf3b89d912fa97d01111596d1c31ef3674b51dcc0040dc4947 | f897ac433ec620efc44cfb12e69c020e3c000dad626c8355a782574f204f60b0 |
| make_evidence.py | 19a8382cec40c990989d7e710a97313517940365269c13341f31636c102a5674 | b4c7f0ea54edb13a7481266905250a56faf4431dfdd9e75786214c4d01f651f1 |
| metrics.py | a584784bc6d3b6b8ddc6d9822dad96005d30e9c1dcc4c6a85086cb3b01fc4e8d | d23db002ab5e0d3348e93c46073386ddf4509868a615696f5c4c30f90f53a572 |
| policy.py | c64461a6ce4dd98dbbfab73a0a1f69d3c08789e644dd5e4bf6ed41ac3ab8f071 | fd400e39cfb074048e3fb1dbb8707ff298ea8fe924648f19ea7ccf12999117ec |
| test_policy.py | 335f3571508ccccce4b69fb8e85b3cd840acd40d8d463b67bb3113afd5f03004 | d7a42fb2db12130ad43a960b2fc2e4abd3c812871c13da430fa25f7414896ce7 |

## Evidencia verificable

Todas las rutas siguientes son relativas a `storage/app/ai-segmentation` y permanecen privadas.

| Artefacto | SHA256 |
| --- | --- |
| protocol-freeze.json | 45ae9c1cbb900a151b33a078bab4d19733018947e377cb623a62ec14eed5dfdc |
| execution-freeze.json | 6c285d0ff4b9266cceac863b13533d6ec114d7111321f8afc08bd3e5f417ce78 |
| model-lock.json | 8f5fd343a93d9c09a2adbfb14a573f6f234005fe2cf933d8f2b8d82426ee8443 |
| candidate-benchmark.log | dd15f262537251d04a8c7fddba105cf3abed83148b2bf1272335276c99a95632 |
| policy-metrics-tests.txt | 8452ccbefa22913ecd8c800b4f0de9929b7f375ec878e36dbc88c4d355a0ed9a |
| consumer-reader-repair.json | 2d4e3f1564973d5767c301d1f5e54fb4d4addbfe4a372b203bee7efb8bc6f502 |
| visual-review.json | 4889f02a92ba638cb04b2d69b861655806cce5f6ed655afa00799ca4638d6ce1 |
| source-finalization.json | 00ec80ee9876bfe9e18ae8159a29245c48c6fa263ec4a0afa64cfabf68e47bf8 |
| gate-decision.json | 8e3d6ec3d3a78dcf9c582f729ee27332208cecd64949743fc0cd3114eb54932d |
| dino_tiny_sam2_tiny/results.json | f8bc953c2682b1bc3ad471920b7335ac5713983a02790fdce92f06bc928c382a |
| dino_base_sam2_tiny/results.json | bea3bd83c1b52d1d0bfe1b93e80eeb220a64f9769cfee67daccdc77bff1150f7 |
| dino_tiny_sam_vit_b/results.json | 3d13f6851894f2d779ca65d180517c99489269c2d3c0c53b7179a6372412e0d4 |

Los JSON `results.json` conservan todas las 130 filas, estado, razones, SHA de la foto, refs privadas, geometría y modelos. La decisión comercial continúa bloqueada: `commercial_authorization=false`, identidad científica no medida y ningún ID comercial desbloqueado.
