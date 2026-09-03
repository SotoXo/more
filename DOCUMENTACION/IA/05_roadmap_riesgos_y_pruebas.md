# Roadmap, riesgos, pruebas y MVP

Fecha: 2026-08-03  
Escala relativa: XS, S, M, L, XL. No representa horas; combina alcance, incertidumbre, datos y coordinación.

## 1. Orden recomendado

```text
IA-0 Contratos y líneas base
 → IA-1 Esqueleto FastAPI
 → IA-2 Integración Laravel–FastAPI
 → IA-3 Calidad y color
 → IA-4 Zero-shot (MVP técnico)
 → IA-5 Validación humana y feedback (MVP de aprendizaje)
 → IA-6 Embeddings y similares
 → IA-7 Analítica descriptiva y reglas
 → IA-8 OCR de etiquetas
 → IA-9 Recomendación de contenido/híbrida
 → IA-10 Outfits
 → IA-11 Transfer learning con datos propios
 → IA-12 RAG/asistente, condicionado a valor
```

IA-7 puede ejecutarse en paralelo conceptual con IA-6 después de estabilizar los datos, pero no debe competir por la integración inicial. IA-8 se pospone porque requiere un nuevo tipo de fotografía y dependencias no instaladas. IA-11 no comienza por calendario: comienza cuando el dataset cumple criterios.

## 2. Hoja de ruta por fases

### IA-0 — Arquitectura, taxonomía, contratos y baseline

- **Objetivo:** aprobar límites, taxonomía inicial, contratos, métricas y conjunto de evaluación.
- **Alcance:** decisiones de `06_decisiones_pendientes.md`, guía de imagen, schema OpenAPI conceptual, casos positivos/negativos, benchmark plan CLIP/SigLIP.
- **Archivos/módulos futuros:** documentación, fixtures de contrato y manifest de taxonomía; sin endpoints funcionales.
- **Dependencias:** acceso de lectura a datos representativos y responsables de negocio.
- **Dificultad/tamaño:** media / M, dominada por decisiones y curación.
- **Riesgos:** taxonomía ambigua; dataset no representativo; metas arbitrarias.
- **Criterio de aceptación:** categorías/alias/versiones aprobadas; conjunto de evaluación congelado; contratos y responsabilidades firmados; riesgos con dueño.
- **Pruebas:** revisión de schemas contra casos; validación manual de labels; prueba de que ninguna respuesta IA implica persistencia automática.
- **Entregable:** ADRs, contrato v1, baseline plan y dataset manifest v0.
- **Depende de:** ninguna fase técnica.

### IA-1 — Esqueleto del microservicio

- **Objetivo:** proceso FastAPI reproducible con configuración, health, capabilities, logging y tests, sin modelo productivo.
- **Alcance:** app factory, settings, middleware de request ID, error envelope, autenticación interna, fake pipeline, límites y readiness.
- **Archivos/módulos futuros:** `services/mati-ai/src/mati_ai/{main,api,core,schemas}.py`, tests y README; cualquier cambio de `compose.yaml` requiere aprobación separada.
- **Dependencias:** contrato IA-0; dependencias actuales ya incluyen FastAPI/httpx/Pydantic/pytest.
- **Dificultad/tamaño:** baja-media / S.
- **Riesgos:** configurar secretos incorrectamente; health que oculte readiness; descarga accidental de modelos.
- **Criterio de aceptación:** health/capabilities pasan CPU; auth rechaza token inválido; logs correlacionados; ninguna descarga en request.
- **Pruebas:** unitarias settings/errores, API TestClient, auth, rate/size simulados, lint/type/test.
- **Entregable:** servicio arrancable y OpenAPI v1 con pipeline fake.
- **Depende de:** IA-0.

### IA-2 — Integración Laravel–FastAPI

- **Objetivo:** Laravel llama al fake de forma segura y se degrada al flujo manual.
- **Alcance:** `MatiAiClient`, configuración, DTOs, timeouts, retry idempotente, breaker, endpoint/controlador de prueba protegido, fake en tests.
- **Archivos/módulos futuros:** `app/Services/AI`, config/services, requests/controllers/routes y tests; `.env`/compose solo con aprobación.
- **Dependencias:** IA-1; token/URL aprobados.
- **Dificultad/tamaño:** media / M.
- **Riesgos:** bloquear formulario por caída; reintentos duplicados; exponer FastAPI públicamente.
- **Criterio de aceptación:** respuesta fake visible a usuario autorizado; 401/timeout/503 no impiden continuar; IDs coinciden en ambos logs.
- **Pruebas:** HTTP fake, contrato, timeout, connection refused, retry, breaker open/half-open, autorización, payload máximo.
- **Entregable:** integración simulada extremo a extremo.
- **Depende de:** IA-1.

### IA-3 — Análisis básico de imagen

- **Objetivo:** validar y medir calidad/color de una portada sin red neuronal.
- **Alcance:** MIME real, decodificación, EXIF/orientación, límites, resize, blur/exposición/contraste, fondo/ROI heurístico, SHA/dHash, K-Means LAB, mapeo a colores.
- **Archivos/módulos futuros:** pipelines `image_validation`, `quality`, `foreground`, `color`; schemas y fixtures de imágenes.
- **Dependencias:** IA-2; guía de captura; catálogo de colores aprobado.
- **Dificultad/tamaño:** media-alta / M.
- **Riesgos:** blanco sobre blanco, fondo/editorial, varias prendas, umbrales sesgados.
- **Criterio de aceptación:** no crashes con corpus; errores estructurados; paleta reproducible; selector manual sigue disponible; baseline documentada.
- **Pruebas:** corruptas, MIME falso, bomb/píxeles, EXIF rotado, RGB/RGBA/grayscale, claros/oscuros, fondos, duplicados, CPU, performance.
- **Entregable:** capability `quality/colors/exact_duplicate` con reporte de baseline.
- **Depende de:** IA-2.

### IA-4 — Clasificación zero-shot y MVP técnico

- **Objetivo:** devolver top-3 de categorías del catálogo con modelo/versiones y abstención.
- **Alcance:** benchmark offline CLIP B/32 vs SigLIP base P16 224, prompts, model registry/loader, GPU/CPU, embedding, top-k/margen.
- **Archivos/módulos futuros:** `model_registry`, `zero_shot`, artefact manifest, prompts/taxonomy, tests GPU marcados.
- **Dependencias:** IA-3; checkpoints descargados/fijados; conjunto de evaluación.
- **Dificultad/tamaño:** alta / L por evaluación y operación GPU.
- **Riesgos:** scores no calibrados, OOM, idioma, categorías compuestas, escenas multi-prenda.
- **Criterio de aceptación:** modelo recomendado supera baseline o se justifica por tradeoff; top-3/macro-F1/latencia/VRAM publicados; abstención y CPU degradado funcionan.
- **Pruebas:** regresión por imagen, prompts/taxonomía, determinismo tolerado, GPU/CPU, OOM simulado, concurrencia, cold/warm latency, modelo ausente.
- **Entregable:** MVP técnico `garments/analyze` con SigLIP provisional y CLIP baseline.
- **Depende de:** IA-3.

### IA-5 — Validación humana, persistencia y feedback

- **Objetivo:** completar el ciclo de aprendizaje sin contaminar datos.
- **Alcance:** UI de sugerencias, aceptar/corregir/ignorar, tablas mínimas de modelos/resultados/feedback, auditoría y elegibilidad de muestra.
- **Archivos/módulos futuros:** modelos/migraciones Laravel por aprobar, orquestador IA, pantalla `ProductCreate`, tests y export manifest.
- **Dependencias:** IA-4; política de retención/privacidad; schemas de tablas aprobados.
- **Dificultad/tamaño:** alta / L por tocar el flujo de alta sin romperlo.
- **Riesgos:** autosugestión que se guarda sin revisión; feedback implícito; doble submit; predicción desactualizada.
- **Criterio de aceptación:** dato comercial solo cambia con acción; predicción y validado separados; idempotencia; alta manual funciona sin IA; métricas de aceptación disponibles.
- **Pruebas:** integración completa, autorización, transacción/rollback, doble submit, imagen reemplazada, taxonomía cambiada, servicio caído, accesibilidad UI.
- **Entregable:** **primer MVP de negocio completo** y dataset candidate v0.
- **Depende de:** IA-4.

### IA-6 — Embeddings y prendas similares

- **Objetivo:** indexar catálogo y recuperar similares compatibles.
- **Alcance:** tabla embeddings, invalidación, backfill, búsqueda exacta inicial, evaluación/aprobación pgvector, filtros categoría/color/precio/temporada.
- **Archivos/módulos futuros:** jobs Laravel, `ai_embeddings`, pipeline embedding, endpoint similar, componente de catálogo.
- **Dependencias:** IA-5; modelo estable; decisión pgvector.
- **Dificultad/tamaño:** alta / L.
- **Riesgos:** embeddings stale, filtro posterior reduce K, índice desincronizado, similitud semántica incorrecta.
- **Criterio de aceptación:** 100% de imágenes elegibles con vector activo; Recall@K/Precision@K baseline; cambios de imagen invalidan; Laravel filtra stock.
- **Pruebas:** dimensión/modelo, duplicados, stale/backfill/retry, exact vs índice aproximado, filtros, borrado, carga moderada.
- **Entregable:** carrusel “similares” evaluado.
- **Depende de:** IA-5.

### IA-7 — Analítica descriptiva y reglas comerciales

- **Objetivo:** ampliar decisiones de inventario/ventas con datos existentes antes de predicción.
- **Alcance:** definiciones únicas de venta/reserva válida, rotación, sin movimiento, color/talla, cancelaciones, stock y escenarios de reorden con parámetros explícitos.
- **Archivos/módulos futuros:** servicios/reportes Laravel y consultas versionadas; FastAPI solo para modelado offline posterior.
- **Dependencias:** estados comerciales consolidados; datos perfilados.
- **Dificultad/tamaño:** media-alta / M-L.
- **Riesgos:** cifras contradictorias, censura por stock, series cortas, confundir regla con ML.
- **Criterio de aceptación:** cada KPI tiene fórmula/estado/fuente; coincide con muestras auditadas; baseline temporal/naive documentada.
- **Pruebas:** fixtures por estado, fechas/zona horaria, cancelaciones, devoluciones si aparecen, consultas grandes, reconciliación con reportes actuales.
- **Entregable:** panel descriptivo y reglas explicables, sin predicción no validada.
- **Depende de:** IA-0 y saneamiento de estados; preferible tras IA-5.

### IA-8 — OCR de etiquetas

- **Objetivo:** extraer candidatos de marca/talla/código/composición desde una foto específica de etiqueta.
- **Alcance:** dataset de etiquetas, benchmark Tesseract/EasyOCR, crops/orientación, parsing y validación contra catálogos; procesamiento asíncrono para galería.
- **Archivos/módulos futuros:** pipeline OCR, job, UI de “foto de etiqueta”, dependencias por aprobación.
- **Dependencias:** nueva política de captura; instalación aprobada; IA-5 feedback.
- **Dificultad/tamaño:** alta / L.
- **Riesgos:** texto pequeño/curvo, símbolos, dependencia pesada, foto equivocada, falsos matches de marca.
- **Criterio de aceptación:** CER/WER y precisión por campo superan baseline acordada; ninguna creación automática de marca/talla; cajas/confianza visibles.
- **Pruebas:** español/inglés, rotación, blur, glare, etiqueta parcial, sin texto, regex/checksum, GPU/CPU, privacidad.
- **Entregable:** OCR piloto opcional.
- **Depende de:** IA-5.

### IA-9 — Recomendación basada en contenido e interacciones

- **Objetivo:** superar popularidad con ranking personalizado medible.
- **Alcance:** baseline de popularidad/novedad, perfil por favoritos/compras/reservas, contenido, diversidad; después impresiones/clics y híbrido.
- **Archivos/módulos futuros:** tablas recommendation/items/interactions, servicio Laravel de candidatos, endpoint de ranking, UI y analytics.
- **Dependencias:** IA-6; política de eventos/consentimiento; suficiente tráfico.
- **Dificultad/tamaño:** muy alta / XL por medición online.
- **Riesgos:** cold start, feedback loops, stock agotado, sesgo, no tener impresiones para denominador.
- **Criterio de aceptación:** comparación offline contra popularidad; cobertura/diversidad; experimento online solo con instrumentation completa y guardrails.
- **Pruebas:** usuario nuevo/con historial, producto nuevo, señales vacías, stock cambia, dedupe, privacidad, contract/ranking regression, carga.
- **Entregable:** recomendaciones de contenido explicables y evaluables.
- **Depende de:** IA-6.

### IA-10 — Outfits

- **Objetivo:** generar combinaciones válidas, disponibles y dentro de presupuesto.
- **Alcance:** roles, matriz de compatibilidad, armonía de color, generación acotada, ranking/feedback.
- **Archivos/módulos futuros:** taxonomía de roles, reglas, endpoint outfits, UI.
- **Dependencias:** IA-6/IA-9; aprobación de roles/estilo/ocasión; catálogo suficiente.
- **Dificultad/tamaño:** muy alta / XL.
- **Riesgos:** combinatoria, categorías incompletas, talla entre piezas, subjetividad, no existe calzado.
- **Criterio de aceptación:** cero combinaciones con roles incompatibles/stock inválido al validar; evaluación humana; presupuesto y fallback.
- **Pruebas:** vestido vs top+bottom, conjunto, accesorios, sin calzado, presupuesto imposible, catálogo pequeño, matriz versionada.
- **Entregable:** outfits basados primero en reglas, no generación libre.
- **Depende de:** IA-6; recomendable IA-9.

### IA-11 — Transfer learning con datos propios

- **Objetivo:** superar zero-shot con evidencia reproducible.
- **Alcance:** curación, dataset versionado, split por prenda, entrenamiento de cabeza/backbone, calibración, registry, canary/rollback.
- **Archivos/módulos futuros:** entrenamiento offline, manifests, artefactos/model registry; nunca request web.
- **Dependencias:** IA-5; cobertura de clases y calidad mínimas aprobadas.
- **Dificultad/tamaño:** muy alta / XL.
- **Riesgos:** sobreajuste, leakage, desbalance, olvidar clases, costos/artefactos.
- **Criterio de aceptación:** mejora estadísticamente defendible en test congelado, sin degradación crítica por clase, latencia/VRAM dentro del SLO, rollback probado.
- **Pruebas:** reproducibilidad, seeds, leakage/dedup, evaluación por subgrupo, calibración, shadow/canary, rollback.
- **Entregable:** modelo candidato propio/fine-tuned; no se activa automáticamente.
- **Depende de:** criterio de datos, no una fecha.

### IA-12 — Asistente/RAG futuro

- **Objetivo:** responder consultas autorizadas con fuentes, sin escritura ni SQL libre.
- **Alcance:** documentos versionados, tools de catálogo/reportes de solo lectura, autorización por Laravel, citas y límites.
- **Archivos/módulos futuros:** índice documental, tool gateway Laravel, evaluación de preguntas.
- **Dependencias:** casos de uso reales, políticas consolidadas, seguridad/privacidad.
- **Dificultad/tamaño:** muy alta / XL.
- **Riesgos:** alucinación, fuga, SQL costoso, información desactualizada.
- **Criterio de aceptación:** exactitud/citas en set de preguntas, cero acceso fuera de rol, negativa segura, trazas y costo aprobados.
- **Pruebas:** prompt injection, autorización, datos sensibles, documentos contradictorios, no-answer, freshness, carga.
- **Entregable:** piloto interno; no primer producto IA.
- **Depende de:** valor demostrado y datos gobernados.

## 3. MVP recomendado, definido exactamente

### Alcance funcional

El primer MVP de negocio se completa en IA-5 y analiza **una imagen principal antes de guardar**.

#### Recibe

- JPEG/PNG/WebP estático de hasta 6 MB y dentro del límite de píxeles;
- categorías activas con alias y versión;
- colores activos con familia/HEX y versión;
- features solicitadas y top-k=3;
- hashes/embeddings candidatos si se evalúa duplicado visual;
- request/analysis/idempotency IDs.

#### Procesa

- MIME por contenido, decodificación, frames, dimensiones y orientación;
- copia RGB sin EXIF;
- desenfoque, exposición, contraste, fondo/ROI aproximado;
- SHA-256 y hash perceptual;
- K-Means en LAB y mapeo a catálogo;
- `google/siglip-base-patch16-224` fijado a revisión, con prompts versionados;
- top-3 de categorías y embedding normalizado;
- candidatos de duplicado exacto y, si se valida umbral, visual.

#### Devuelve

- checks `pass|warning|fail` explicados;
- color observado, porcentaje, color/familia candidata y distancia;
- top-3 con score type, rank, margen y abstención;
- duplicados con razón/score;
- modelo/pipeline/taxonomía/prompts, device y tiempos;
- warnings y errores estructurados.

#### No intenta detectar

- talla, marca, composición, instrucciones de lavado o código sin foto de etiqueta;
- material desde apariencia;
- estilo/ocasión sin vocabulario;
- temporada como verdad visual;
- descripción generativa libre;
- múltiples prendas dentro de una escena ambigua;
- disponibilidad, precio correcto, stock o cualquier estado comercial.

#### Intervención del administrador

- La UI muestra sugerencias sin aplicarlas en silencio.
- El administrador acepta/corrige/ignora cada atributo.
- Si la imagen es problemática, puede reemplazarla o continuar manualmente según reglas Laravel.
- Un posible duplicado abre el registro candidato; no bloquea automáticamente.

#### Persistencia

- Laravel guarda resultado bruto/versiones en `ai_analysis_results`.
- El dato confirmado sigue en tablas actuales.
- La acción humana se guarda en `ai_feedback`.
- FastAPI no escribe PostgreSQL.
- Muestras de entrenamiento requieren revisión/elegibilidad; una predicción no se convierte sola en label.

#### Medición

- Dataset de evaluación por prenda y categoría.
- Top-1/top-3, macro-F1, abstención, coincidencia de familia/color, precisión de duplicados.
- Latencia p50/p95, VRAM pico, error/timeout/fallback.
- Aceptación/corrección/ignorado por atributo y versión.
- Comparación SigLIP vs CLIP antes de activar.

#### Limitaciones visibles

“Sugerencia automática; verifica categoría y color. Fotos con varias prendas, fondos complejos, sombras o prendas claras pueden reducir la precisión. La IA no detecta talla, marca ni material desde esta portada.”

### Por qué incluye y excluye funciones

| Función | MVP | Motivo |
|---|---|---|
| Validación/seguridad | Sí | Prerrequisito técnico |
| Calidad/fondo | Sí | Mejora captura y explica fallos |
| Color dominante/secundario | Sí | Catálogo preparado y algoritmo disponible |
| Zero-shot top-3 | Sí | Valor central sin entrenamiento |
| Tags | Solo vocabulario mínimo y si baseline es útil | Evitar ruido |
| Embedding | Sí internamente | Reutilizable para duplicados/similares |
| Temporada | No | Decisión comercial, no señal visual suficiente |
| Material/estilo/ocasión | No | Taxonomía/evidencia insuficientes |
| OCR | No | Foto/dependencia/pipeline distintos |

## 4. Estrategia de pruebas transversal

### Pirámide

1. **Unitarias puras:** schemas, hashes, color, calidad, normalización, filtros, scores y estados.
2. **API FastAPI:** auth, headers, multipart, códigos, errores, idempotencia y OpenAPI.
3. **Contrato:** fixtures compartidos Laravel/Python; validación JSON Schema/OpenAPI; compatibilidad v1.
4. **Integración Laravel–FastAPI:** fake, servicio real CPU y suite GPU marcada.
5. **Modelo/dataset:** evaluación offline congelada y regresión por versión.
6. **Sistema:** timeout, caída, breaker, queue, observabilidad, seguridad y carga moderada.

### Corpus obligatorio de imágenes

- válida aislada/fondo blanco;
- editorial con persona/fondo;
- varias prendas;
- blanca sobre blanco y negra con sombras;
- estampada/multicolor;
- pequeña, enorme, corrupta, extensión falsa, MIME falso;
- JPEG/PNG/WebP, grayscale/RGBA, EXIF rotado;
- duplicado exacto, recompresión/crop de near-duplicate;
- sin prenda y texto/etiqueta.

Las imágenes de evaluación no deben reutilizarse para ajustar prompts/umbrales finales.

### Gates obligatorios por módulo

| Módulo | Gate previo a integrar |
|---|---|
| Health/capabilities | Unit/API/auth/contract, sin secretos |
| Calidad/color | Corpus inválido + CPU + baseline humana + performance |
| Zero-shot | Test congelado top-k/macro-F1, regresión, GPU/CPU, VRAM/latencia |
| Feedback | Integración/transacción/idempotencia/autorización/leakage |
| Similares | Recall/Precision@K, filtros, stale/backfill, exact vs index |
| OCR | CER/WER y por campo, privacidad, no creación automática |
| Recomendaciones | Baseline popularidad, cobertura/diversidad, stock revalidado, instrumentation |
| Outfits | Matriz/roles/presupuesto/talla/stock y evaluación humana |
| Predicción demanda | Backtesting temporal y baseline naive; intervalos |
| RAG | Autorización, inyección, citas/no-answer, datos sensibles |

### Rendimiento y carga

- Medir cold/warm por tamaño de imagen y device.
- Carga moderada con patrón realista, no solo ráfaga sintética.
- Verificar que una solicitud grande no bloquee health.
- Medir cola/semaphore, 429/Retry-After, OOM y recuperación.
- Soak test para fuga/fragmentación de VRAM.
- Batch/backfill separado de tráfico interactivo.

### Seguridad

- token ausente/incorrecto/rotado;
- FastAPI inaccesible desde red pública;
- path traversal, nombres maliciosos, polyglot/archivo corrupto, bomb de descompresión;
- URLs firmadas expiradas/host no permitido cuando existan;
- logs sin token, bytes, PII ni paths sensibles;
- rate limiting y límites de body/píxeles/frames;
- dependencia/model artifact con checksum.

## 5. Matriz de riesgos

Probabilidad/impacto son cualitativos para el estado actual.

| Riesgo | Prob. | Impacto | Mitigación | Fase límite | Responsable |
|---|---|---|---|---|---|
| Dataset muy pequeño/no representativo | Alta | Alto | Zero-shot; capturar/validar; split por prenda; no entrenar aún | IA-0/IA-11 | Datos |
| Fondos/iluminación heterogéneos | Alta | Alto | Guía, quality checks, ROI, fallback manual, dataset estratificado | IA-3 | Datos/FastAPI |
| Varias prendas/modelo en foto | Alta | Alto | Warning/abstención; foto objetivo; segmentación futura | IA-3/IA-4 | Datos/FastAPI |
| Colores claros sobre fondo blanco | Alta | Alto | LAB, bordes, no eliminar blancos, selector manual | IA-3 | FastAPI/React |
| Categorías ambiguas/compuestas | Alta | Alto | Taxonomía+alias, top-3, margen, abstención, guía | IA-0/IA-4 | Datos/Laravel |
| Marca/talla/material inventados | Media | Alto | Excluir del MVP; OCR/foto etiqueta; labels “no determinado” | IA-0 | Producto/FastAPI |
| Predicción guardada sin validación | Media | Crítico | Separar tablas, acción explícita, transacción Laravel | IA-5 | Laravel |
| Errores IA contaminan dataset | Alta | Crítico | Elegibilidad, revisión, dedupe, test congelado, lineage | IA-5/IA-11 | Datos |
| Scores interpretados como probabilidad | Alta | Alto | `score_type`, calibrated nullable, copy/educación | IA-4 | FastAPI/React |
| 8 GB VRAM/OOM/concurrencia | Media | Alto | Un worker/semaphore, FP16 medido, lazy/warmup, 429, modelos no simultáneos | IA-4 | Infra/FastAPI |
| Latencia rompe registro | Media | Alto | Timeout, sync limitado, cache, breaker, manual fallback | IA-2/IA-4 | Laravel/Infra |
| Descarga de modelos en producción | Media | Alto | Artefactos fijados, checksum, precarga/offline cache | IA-1/IA-4 | Infra |
| FastAPI indisponible | Media | Alto | Breaker, retry idempotente, health/readiness, flujo manual | IA-2 | Laravel/Infra |
| Reintento duplica resultados/jobs | Media | Alto | UUID, Idempotency-Key, unique index, payload hash | IA-2/IA-5 | Laravel/FastAPI |
| Exposición pública/robo de token | Baja-Media | Crítico | Red privada, token rotado, TLS/mTLS remoto, logs sanitizados | IA-1/IA-2 | Infra |
| Archivo malicioso/bomb | Media | Crítico | MIME real, decode sandboxed, bytes/píxeles/frames/timeouts | IA-3 | FastAPI/Infra |
| EXIF/personas/privacidad | Media | Alto | Re-encode, minimización, retención, sin biometría, consentimiento | IA-0/IA-3 | Datos/Legal/Infra |
| Embeddings desincronizados | Media | Alto | Hash+modelo, estado stale, job idempotente, invalidación | IA-6 | Laravel/FastAPI |
| Filtros ANN reducen resultados/recall | Media | Medio-Alto | Exact baseline, iterative scans/tuning, postvalidación | IA-6 | Datos/FastAPI |
| Sin datos de vistas/impresiones | Alta | Alto | No inventar señales; instrumentar con consentimiento antes de online metrics | IA-9 | Laravel/Datos |
| Cold start/sesgo de popularidad | Alta | Medio-Alto | Contenido, diversidad, novedades, cobertura/segmentación | IA-9 | Datos |
| Recomendación de producto sin stock | Media | Crítico comercial | Laravel filtra antes/después y al confirmar | IA-9 | Laravel |
| Outfit incompatible/inexistencia de calzado | Alta | Medio | Roles/matriz; solo categorías reales; reglas duras | IA-10 | Datos/Laravel |
| Predicción de demanda con series cortas | Alta | Alto | Descriptivo primero, backtesting, naive, agregación/intermitencia | IA-7 | Datos |
| EOQ sin lead time/costos | Alta | Alto | Escenarios con parámetros; no llamarlo predicción; capturar datos | IA-7 | Negocio/Datos |
| RAG alucina o filtra datos | Alta | Crítico | Tools read-only, auth Laravel, citas, allowlist, no SQL libre | IA-12 | Laravel/Seguridad |
| Migraciones de temporada duplicadas | Alta en instalación limpia | Alto | Corregir antes de entornos reproducibles; prueba migrate:fresh en DB desechable | Antes de implementación | Laravel/DB |
| Documentación histórica incorrecta | Media | Medio | Código/migraciones como fuente; inventario de esquemas automatizado | IA-0 | Arquitectura |

## 6. Cinco riesgos principales

1. Guardar o entrenar con predicciones no confirmadas, contaminando catálogo y dataset.
2. Dataset pequeño/heterogéneo que produzca una falsa sensación de precisión.
3. Caída/latencia/OOM del servicio GPU afectando un flujo comercial que hoy funciona.
4. Exposición o procesamiento inseguro de imágenes y datos personales.
5. Recomendaciones/predicciones construidas sin señales, estados o métricas suficientes.

## 7. Cinco mejoras prioritarias

1. Taxonomía versionada de categoría y política explícita sugerencia→confirmación.
2. Metadata/hash/validación técnica de imágenes y guía de captura.
3. Resultado/modelo/feedback persistidos por separado bajo Laravel.
4. Contrato autenticado, idempotente, observable y con fallback manual.
5. Dataset de evaluación real antes de elegir umbrales o metas.

## 8. Criterio global para avanzar

Una fase no se considera terminada porque el endpoint responde. Debe cumplir simultáneamente contrato, seguridad, regresión, medición, observabilidad, degradación segura y aceptación del dueño de negocio. Si la evidencia no supera una línea base simple, se conserva la línea base y no se promueve complejidad.
