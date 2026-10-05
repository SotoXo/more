# Resumen para defensa

La implementación actual combina modelos visuales preentrenados, visión clásica y reglas. **No está completa de extremo a extremo**: la inferencia privada funciona, pero los controles de imagen del formulario fallan en Laravel antes de llegar a Python, y el despliegue principal carece de token, servicio y tablas IA.

## A. Cómo funciona todo

React entrega foto/código/texto a rutas Laravel autenticadas. Laravel impone taxonomías activas, permisos y autoridad comercial; invoca FastAPI privado con Bearer. FastAPI normaliza imágenes y mide calidad; SigLIP produce embeddings y categorías/patrones zero-shot; visión clásica calcula foreground/paleta/Lab/ΔE; OpenCV/ZXing leen códigos, EasyOCR intenta texto; reglas ordenan coincidencias y compatibilidad cromática. Laravel registra propuestas/revisión y sólo el guardado humano habitual altera productos. PostgreSQL almacena datos; pgvector es extensión para vectores, no otra base. Preparación de imágenes es orientación/EXIF/resize/WebP determinista, no generación.

## B. Funciona realmente

Clasificación, embeddings y decoder privados funcionan con los límites medidos. Colorimetría produce paleta en84/130 fotos y no separa semánticamente la prenda de la persona. OCR no funciona por pesos ausentes. Matching interno funciona parcialmente. pgvector funciona en instancia aislada pero no está operativo en principal. Búsqueda externa tiene adapters sin fuente activa. Compatibilidad tiene motor privado, sin ruta/UI consumidora. UI por imagen falla 500 en 5 casos. Formulario manual con FastAPI apagado avanzó al Paso2 en control aislado sin guardar. Outfits, recomendación personalizada, RAG, generación y VTO no implementados.

Tabla completa de estados y alcance en01. No se sustituyó una sesión comercial: browser usó usuario sintético/base separada/catálogo copiado y PHP8.5.9 frente al principal8.5.5.

## C. Resultados más importantes

| Medida | Resultado | Alcance |
|---|---|---|
|Top1/Top3|83/130=63.85% /116/130=89.23%|SigLIP real, etiquetas comerciales, SHA deduplicados|
|Macro-F1|0.66492|10clases con soporte, no clase ACCESORIOS|
|Coverage/accuracy cubierta|10% /84.62%(11/13)|Abstención90%; no reemplaza Top1 global|
|PrimaryID accuracy/cobertura|51.43%(18/35) /26.92%(35/130)|Proxy comercial, no color físico/monocolor confirmado|
|ΔE00 esperado|Media 14.497,mediana 9.968,p90 29.634,N84|Foto topcluster alHEX esperado, no instrumento|
|OCR CER/WER|NO EVALUABLE|0 texto útil/3 intentos; no transcripciones reales|
|Barcode|30/36 variantes; originales 6/6 valor y formato|Sintético, decoder real;crop0/6|
|Recall@1/@5/@10|.05314/.16681/.25551|Relevancia misma categoría, no mismo SKU|
|Hit@10/MRR/nDCG@10|.97692/.70305/.40206|Galería 130 excluye self/producto/SHA|
|SQL vs NumPy|130/130top30 iguales,error≤2.389e-7|PG18.3/vector0.8.2aislado|
|AnalyzeHTTPp50/p95|221.96/410.82ms,N130|CUDA+CV caliente privado, no UI|
|11promptsCUDA/CPUp50|30.56/329.73ms,N9/device|Float32, foto33, CPU tras OS cache|
|VRAM pesos/pico|775.23/833.39MiB|PyTorch proceso, no global escritorio|
|E2E seleccionado|2/7 casos normales;fallback1/1|5 image 500, 2 matching 200, UI aislada|

SigLIP revision7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed,pipeline registration-1.2.0,dim768/L2. Dataset134 originales/131SHAúnicos/130evaluados. CLIP no preparado: no comparativa ni ganador. Recomendación **seguir evaluando**, sin cambiar configuración.

## D. Qué significa cada score

SigLIP es coseno imagen/texto L2, no probabilidad. Coseno imagen/imagen describe dirección vectorial y no identidad. Color confidence combina foreground/iluminación/compacidad; ΔE mide diferencia al swatch, no certeza ni gusto. Matching usa prioridad de identificadores y ponderaciones/penalizaciones, no modelo probabilístico. Compatibility0–100 es promedio de reglas cromáticas versionadas entre dos paletas, no calidad de outfit ni recomendación personalizada. Barcode confidence es null; checksum acredita formato/dígitos, no producto. ai_suggestions.confidence_score mezcla semánticas: consultar source/evidence. Fórmulas, ejemplos, rangos/denominadores en02.

## E. Diez mayores incoherencias

1. Regla prohibited_with inexistente provoca500 en los cinco controles por imagen.
2. IA principal enabled con token vacío ysin FastAPI.
3. pgvector/migraciones/tablas IA pendientes enbase principal.
4. Suite Laravel completa aborta porprivate run final;14workflow no ejecutados.
5. Abstención clasificación no limita sugerencias top3 aplicables de Laravel.
6. OCR EasyOCR enabled pero pesos faltan.
7. Foreground de objeto noaisla prenda catalogada; pantalónnegro contamina jersey verde.
8. Build inicial obsoleto; corregido sólo por build normal autorizado, sin editarfuente.
9. Matching soft sólo100/134 yno filtra inactivos.
10. Retry feedback duplica decisión form e invalidaría tasas contadas por filas.

08 describe28hallazgos con prioridad, archivo/línea, reproducción, evidencia, impacto y propuesta; distingue runtime de riesgo estático. No se confirmó P0 matemático, escritura comercial errónea ni explotación crítica. P1 activos AUD-01–07;AUD-08 fueP1 inicial yya no describe el chunk actual.

## F–H. Límites y prioridades

NO EVALUABLE: CLIPcomparativo, OCR CER/WER/F1real, color físico/monocolor confirmado, IoU, identidad retrieval, estética/aceptaciónhumana real y UI principal autenticada. Motivos: pesos/labels/jueces/sesión/tabla ausentes. No sustituir con fixtures ni scores.

Primero validación Laravel ytests; después despliegueprivado/token/migraciones; luego abstención/identidadfoto/guards y OCR. Tras disponibilidad, referenciasfuertes de prenda/color/identidad ycalidad, idempotencia/filtros/linaje. No tunearduranteaudit. Plan09 sólo propuesta, ninguna corrección implementada.

## I–K. Evidencias y preservación

Los11documentos pedidos00–10 viven enDOCUMENTACION/IA/AUDITORIA_FINAL; manifesto evaluation_manifest.json, evidencia ignorada storage/app/ai-audit, scripts sólo services/mati-ai/scripts/audit. _preservacion_y_git.md incluye gitdiffstat/status completos ycomparación antes/después. Branch/HEAD preservados; cambios previos del usuario conservados. Hash comercial read-only idéntico68b7050be96007dba2c27976f1f15ebad0ed34652791899f85c93e88f2788868.

No se editó source/config/productos para corregir comportamiento; **sí se regeneraron assets servidos con el build normal solicitado**, explicitado para no afirmar ausencia absoluta deefecto observable. No se entrenó, no se ajustaron prompts/thresholds, no se favorecieron productos, no commit/push. Métricas deejecuciones reales; mocks/sintéticos/inferencia real, scores/métricas/probabilidades yproxies/groundtruth fuerte claramente separados. Contenedores/procesos propios detenidos alcierre, principales conservados. Auditoría terminada, detenerse sin aplicar plan.
