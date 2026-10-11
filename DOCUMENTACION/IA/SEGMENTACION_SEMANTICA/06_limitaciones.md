# Limitaciones de la evaluación

La decisión final es NONE. Ningún pipeline supera el gate cuantitativo preregistrado para un consumidor. FULL sigue siendo la estrategia de clasificación y embedding; AUD-07 conserva bloqueo comercial por identidad de target sin verificar. Las mediciones y diagnósticos siguientes no justifican integrar segmentación.

## Ground truth y revisión visual

No existen máscaras humanas independientes, anotaciones por instancia ni protocolo experto de identidad de target sobre N130. No se presentan IoU, Dice ni mAP de máscaras como precisión científica. Bbox IoU 0,85 sólo deduplica aliases; score DINO/SAM y geometría individual no son tasas de acierto.

El contexto fue elaborado por Codex_visual_inspection_not_human: 89 imágenes de producto aislado, 6 con persona y una prenda visible, 30 con persona/varias prendas, 3 flat lays múltiples, 1 fondo complejo y 1 unknown. Flags auxiliares de fondo complejo no sustituyen estratos ni crean clases semánticas. Contexto no se usó como entrada del selector ni es anotación humana.

Se revisaron 60 paneles de 20 imágenes estratificadas fijadas antes de inferencia. Esto permite describir contaminación aparente, capas y duplicados; no mide precisión píxel a píxel, sensibilidad por instancia ni calibración. Tampoco demuestra que la única candidata válida siempre sea la prenda deseada por el usuario.

## Denominadores y sesgo de selección

El manifest conserva 134 registros/131 SHA únicos. N130 deriva de excluir el SHA con categorías contradictorias y conservar la primera fila de duplicados coherentes según regla original. No se cambiaron originales, etiquetas, orden ni reglas para facilitar el experimento.

| Población | Uso válido | Límite |
|---|---|---|
| AUTO ALL130 | Comparación primaria contra baseline con ausencias/abstenciones incluidas | Catálogo pequeño local, sin validación externa. |
| AUTO selected-only N6 Base | Diagnóstico pareado contra FULL de los mismos IDs | Sesgo hacia imágenes aceptadas; no extrapolar a N130. |
| AUTO selected-only N0 Tiny y Tiny/ViT-B | Falta de cobertura | Accuracy condicional no estimable en vacío. |
| ASSISTED caso13 N1 | Diagnóstico visual de la prenda indicada por usuario | Selección Codex, sin anotador humano independiente; fuera de agregados AUTO. |

Los pipelines seleccionan 0/130, 6/130 y 0/130. Rechazos y ambigüedad siguen en ALL130 como abstención/no_prediction. Quitar los 124 casos ausentes de Base alteraría la evaluación. La política mantiene ambigüedad ante varias clases plausibles y otras candidatas inválidas, incluso frases compuestas/fragmentadas fuera de whitelist.

No se reinterpretaron frases ni se ajustaron aliases, prompts, deduplicación entre clases o thresholds después de resultados para obtener más targets. Hacerlo usando N130 sería otro experimento, con nueva preregistración y validación externa.

No se probó un híbrido semántico con FULL fallback. Sus métricas, cobertura o latencia no pueden presentarse como si hubiese superado el gate. Se conserva FULL existente sin nueva rama semántica.

## Etiquetas comerciales y caso 13

Las etiquetas comerciales están confirmadas por persistencia; no son GT experto independiente de la región física. La taxonomía global tiene 11 categorías, con soporte N130 en 10. Predicciones de categorías sin soporte se conservan como columnas extra; macro F1 mantiene las clases GT congeladas.

Producto11/imagen13, JERSEY ALPACA, permanece ABRIGOS (ID1). La apariencia knitwear e indicación del usuario no autorizan cambiar esa etiqueta para puntuar mejor. POLOS Y KNITWEAR no se redefine retrospectivamente como acierto frente a GT comercial.

Imagen13 sigue ambiguous en AUTO. ASSISTED final elige visualmente image-13-candidate-2 de Base/SAM2, cuya clase externa metadata es jacket. La región física corresponde al jersey superior separado de pantalón/bolso, según revisión Codex. La etiqueta jacket no acredita identidad comercial; no hay máscara humana para certificar bordes.

FULL sugiere PANTALONES; crop/masked sugieren POLOS Y KNITWEAR. Masked alcanza coseno 0,081462 y no se abstiene bajo threshold original. Es una diferencia local de entrada, no mejora ALL130, probabilidad de acierto, identidad SKU ni permiso de color comercial. No se evaluó retrieval sobre N1.

## Color proxy y compatibilidad experimental

HEX/nombres/familias comerciales son proxies. No hay medidas de tejido bajo iluminante controlado, colorímetro, carta calibrada, balance de blancos verificado ni GT físico. Delta E al swatch mide distancia entre representaciones empleadas; no error del color real del producto.

El baseline técnico preguard tiene 84/130 paletas, 63 primarios y 18/130 coincidencias exactas proxy prospectivas. El gate exige mejora exacta proxy ALL130 ≥10 puntos porcentuales frente a esa observación técnica, no frente a cero IDs bloqueados. El bloqueo comercial se mantiene a ambos lados.

El experimento usa RGB original dentro de máscara, escala de muestreo existente y erosión interior, reutilizando funciones privadas CIELAB/CIEDE2000/clustering/paleta/propiedades y thresholds. Iluminación conserva contexto del borde original; el fondo gris de masked crop no entra en colorimetría.

Se fija experimental_mask_foreground_compatibility_score en MIN_FOREGROUND_CONFIDENCE=0,70. Es entrada explícita de compatibilidad, no precisión de máscara, confianza SAM ni probabilidad calibrada. Sustituir la confianza geométrica original por ese valor puede afectar aceptación, primario o score heurístico; no permite atribuir causalmente toda diferencia sólo a separar píxeles con SAM.

Delta E medio es condicional a primario disponible y publica N. FULL usa N63 y Base semántico N3, conjuntos distintos; sus medias no prueban mejora general. Precisión proxy condicional es null sin candidata prospectiva.

En caso13, familia descriptiva pasa de negro a verde y distancia al swatch comercial sigue grande (27,2056). El primario es verde claro, sin ID comercial aplicable y con target_identity_verified=false. El cambio N1 no demuestra coincidencia exacta con VERDE OLIVA ni desbloquea el guard.

## Retrieval y perfiles

Relevancia significa misma categoría comercial, excluyendo propio producto/SHA. No es GT de producto idéntico, outfit compatible, preferencia humana ni éxito de compra.

ALL130 conserva 130 consultas: query sin target/vector aporta cero. Galería derivada incluye sólo vectores disponibles del mismo perfil, pero número de relevantes e ideal nDCG se mantienen frente a galería congelada completa. No se reduce denominador para favorecer el subconjunto fácil.

FULL/CROP/MASKED tienen preprocesamientos distintos. Compartir SigLIP y dimensión768 no hace equivalente crop-query/full-gallery. Esta fase mide NPZ privados, sin insertar derivados en pgvector ni demostrar beneficio de reindexar los 134 embeddings FULL canónicos.

## Recursos y alcance de ejecución

Los tres pipelines completaron N130 sin errores de inferencia u OOM registrados. P95 caliente del pipeline: 1467,24 / 2208,55 / 1534,52 ms; incremental peak reserved: 3090 / 3138 / 3132 MiB. Son medidas locales del runner, RTX5060 con 8150,56 MiB reportados, batch secuencial y configuración congelada; no garantías de capacidad productiva.

Se distingue allocated/reserved/pico del proceso/base previa/memoria libre dispositivo. El allocator puede mantener reservas entre candidatos; incremental reserved no es memoria global de todos los procesos CUDA. La memoria libre reportada no certifica convivencia bajo carga concurrente de OCR/SigLIP/segmentación.

Latencia incluye procesamiento y E/S de artefactos del runner; no es kernel puro ni request HTTP de producción. Warm excluye primera ejecución completada, sin representar tráfico de usuarios. Cold incluye carga local y transferencia CUDA, sin descargar pesos ni instalar dependencias.

No se ejecutaron estrés/concurrencia prolongada, cancelación HTTP, recuperación de servicio ni SLA de selector productivo. Cumplir recursos aislados no satisface mejora cuantitativa por consumidor.

## Licencias y descartes

[01_investigacion_modelos.md](01_investigacion_modelos.md) distingue licencias de código, checkpoints y datasets. Apache/MIT de un repositorio no relicencia pesos externos, fotografías de terceros ni datos de entrenamiento. Descarga pública no equivale a aprobación comercial.

Los candidatos usados conservan revisión/digest local en model-lock.json y se cargaron sin remote code, con safetensors y descargas deshabilitadas en benchmark. Esto prueba integridad/configuración utilizada, no sustituye obligaciones de redistribución ni certifica origen completo de datos.

Los no seleccionados se descartan por permisos incompletos/restricciones, semántica gruesa o runtime no validado documentados. No se inventan OOM, fallos forward ni incompatibilidad Blackwell para modelos que no se ejecutaron. Fashionpedia, framework Detectron2, dataset y checkpoint concreto no se tratan como productos intercambiables.

## Incidencia real y pruebas

La primera lectura de consumidores abortó con INVALID_ARTIFACT_REFERENCE al intentar una referencia None de diagnóstico sin máscara aceptada. [Consumer-reader-repair.json](../../../storage/app/ai-segmentation/consumer-reader-repair.json) y [log fallido](../../../storage/app/ai-segmentation/consumer-comparison-failed-diagnostic.log) conservan evidencia. Se separó ASSISTED del lector AUTO y se reforzaron referencias. No fue OOM ni fallo del modelo; no se alteró gate para ocultarlo.

La suite final registra [84 pruebas pasadas](../../../storage/app/ai-segmentation/policy-metrics-tests.txt): contratos, duplicados, owner/identidad/generación, abstención, geometría/máscara, referencias y denominadores. También se corrigieron matriz con predicción sin soporte GT y rechazo de selección humana presentada como AUTO. Modelos/prompts/thresholds permanecen congelados.

Los contextos/owner sintéticos unitarios no prueban autorización HTTP, endpoints, persistencia, preview privado, purga TTL ni invalidación E2E. Estas piezas son diseño condicionado; NONE impide presentarlas como integración operativa.

## Preservación y condiciones futuras

AUD-07 sigue MITIGADO: color descriptivo/manual con guard de identidad preservado. No se declara resuelto semánticamente. No se agregaron servicios persistentes/tablas/endpoints/UI ni versiones DB de estos candidatos; no se guardaron productos ni se reindexaron canónicos.

El guard SELECT-only [catalog-final.json](../../../storage/app/ai-segmentation/catalog-final.json), 2026-10-05 13:49:31 UTC / 09:49:31 America/La_Paz, confirma 34 huellas comerciales, 134 SHA originales y 134 identidades/valores canónicos iguales; dimensiones768/L2, 11 FK válidas y conteos IA 4/147/11/2/134 idénticos.

[Preservation-final.json](../../../storage/app/ai-segmentation/preservation-final.json), 13:49:36 UTC, registra 654 tracked sin cambios, 134 originales sin errores, manifest fijo, rama/HEAD/tag iguales y runtime principal healthy con imagen/CMD/env/mounts/ports idénticos. El informe raíz actualizará Git documental tras los últimos informes; no se requieren nuevos benchmarks para esa comprobación.

La [arquitectura propuesta](03_arquitectura_seleccionada.md) exigiría nuevo experimento preregistrado, anotación independiente de target/instancias, separación AUTO/ASSISTED, contratos autenticados y E2E si apareciera un consumidor ganador. No se propone entrenar, retocar etiquetas/prompts/thresholds sobre N130 ni habilitar color por una máscara bonita. Los artefactos actuales quedan como evidencia privada reproducible con decisión NONE.
