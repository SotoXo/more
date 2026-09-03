# Contratos API y diseño de datos

Fecha: 2026-08-03  
Estado: propuesta de contrato; no existen endpoints ni migraciones implementadas.

## 1. Convenciones API

- Base interna: `http(s)://mati-ai:<port>/api/v1`.
- Formato JSON: `application/json; charset=utf-8`; imágenes iniciales: `multipart/form-data`.
- Versión de ruta: `/api/v1`; versión del esquema en `contract_version`.
- IDs de análisis/request: UUID generados por Laravel y propagados.
- Timestamps: UTC ISO-8601 con `Z`.
- Autenticación: `Authorization: Bearer <service-token>` en `/api/v1/*`.
- Cabeceras: `X-Request-ID`, `X-Analysis-ID` cuando corresponda e `Idempotency-Key` en POST.
- FastAPI no autentica usuarios finales; Laravel lo hace y solo envía IDs técnicos mínimos.
- Nombres de modelos/versiones son obligatorios cuando hubo inferencia.
- No se devuelve un ID comercial inventado: candidatos de categoría/color deben provenir del contexto enviado por Laravel.

### 1.1 Sobre scores y confianza

Todo atributo inferido incluye:

```json
{
  "candidate_id": 7,
  "label": "PANTALONES",
  "score": 0.812,
  "score_type": "siglip_logit_sigmoid_uncalibrated",
  "rank": 1,
  "margin_to_next": 0.146,
  "calibrated_probability": null
}
```

`score` no se presenta como probabilidad mientras `calibrated_probability` sea `null`. La calibración es específica de modelo, prompts y taxonomía.

### 1.2 Sobre versiones

Respuesta común:

```json
{
  "contract_version": "1.0",
  "pipeline_version": "garment-analysis/1.0.0",
  "models": [
    {
      "task": "zero_shot_classification",
      "name": "google/siglip-base-patch16-224",
      "version": "<revision-fijada>",
      "prompt_set_version": "garment-prompts/1",
      "taxonomy_version": "categories/1"
    }
  ]
}
```

### 1.3 Error estable

```json
{
  "contract_version": "1.0",
  "error": {
    "code": "IMAGE_DECODE_FAILED",
    "message": "La imagen no pudo decodificarse.",
    "retryable": false,
    "details": {
      "field": "image"
    }
  },
  "request_id": "d0c53359-915d-48e8-94ed-01771de7f918",
  "analysis_id": "b2cb0a70-8314-4388-a803-3a32b6662bb4"
}
```

Códigos previstos: `INVALID_REQUEST`, `UNAUTHORIZED_SERVICE`, `FORBIDDEN_SERVICE`, `IMAGE_TOO_LARGE`, `IMAGE_PIXEL_LIMIT`, `UNSUPPORTED_MEDIA_TYPE`, `IMAGE_DECODE_FAILED`, `ANALYSIS_NOT_CONCLUSIVE`, `TAXONOMY_MISMATCH`, `MODEL_UNAVAILABLE`, `MODEL_BUSY`, `INFERENCE_TIMEOUT`, `IDEMPOTENCY_CONFLICT`, `RATE_LIMITED`, `INTERNAL_ERROR`.

## 2. `GET /health`

### Propósito

Liveness mínima del proceso. No carga modelos ni expone GPU, paths, secretos o versiones detalladas.

| Aspecto | Contrato |
|---|---|
| Método/ruta | `GET /health` (fuera de `/api/v1` por simplicidad operativa) |
| Autenticación | Sin token solo desde loopback/red interna; si se expone más, exigir token |
| Entrada | Sin body |
| Respuesta | Estado del proceso, hora y versión de contrato |
| HTTP | 200 vivo; 503 si el proceso no puede atender |
| Validaciones | Ninguna entrada; limitar/red restringida |
| Timeout | 1 s |
| Idempotencia | Natural, read-only |
| Modelo/confianza | No aplica |

```json
{
  "status": "ok",
  "service": "mati-ai",
  "contract_version": "1.0",
  "time": "2026-08-03T20:00:00Z"
}
```

Health no garantiza que un modelo esté listo; eso pertenece a capabilities/readiness.

## 3. `GET /api/v1/capabilities`

### Propósito

Informar capacidades configuradas, estado de carga, límites y versiones para que Laravel decida qué acciones mostrar.

| Aspecto | Contrato |
|---|---|
| Autenticación | Bearer interno |
| Entrada | Sin body; opcional `?include_runtime=false` |
| HTTP | 200; 401/403; 503 si no se puede construir el estado |
| Validaciones | Query booleano; no exponer secretos |
| Timeout | 2 s |
| Idempotencia | Natural |
| Errores | `UNAUTHORIZED_SERVICE`, `MODEL_UNAVAILABLE`, `INTERNAL_ERROR` |
| Modelo/confianza | Lista versiones/estado, no scores |

```json
{
  "contract_version": "1.0",
  "service_version": "0.1.0",
  "ready": true,
  "device": {
    "preferred": "cuda",
    "active": "cuda",
    "fallback_available": true
  },
  "limits": {
    "max_image_bytes": 6291456,
    "max_image_pixels": 25000000,
    "formats": ["image/jpeg", "image/png", "image/webp"],
    "max_sync_images": 1
  },
  "capabilities": [
    {
      "name": "garment_analysis",
      "status": "ready",
      "pipeline_version": "garment-analysis/1.0.0",
      "features": ["quality", "background", "colors", "category_top_k", "embedding", "exact_duplicate"]
    },
    {
      "name": "ocr",
      "status": "not_installed",
      "pipeline_version": null,
      "features": []
    }
  ],
  "models": [
    {
      "task": "zero_shot_classification",
      "name": "google/siglip-base-patch16-224",
      "version": "<revision-fijada>",
      "loaded": true,
      "dtype": "float16"
    }
  ]
}
```

## 4. `POST /api/v1/garments/analyze`

### Propósito

Analizar una portada y devolver sugerencias no vinculantes antes del guardado comercial.

### Entrada inicial

`multipart/form-data`:

- `image`: archivo requerido.
- `context`: string JSON requerido.

Ejemplo de `context`:

```json
{
  "contract_version": "1.0",
  "analysis_id": "b2cb0a70-8314-4388-a803-3a32b6662bb4",
  "requested_features": ["quality", "background", "colors", "category_top_k", "embedding", "duplicates"],
  "top_k": 3,
  "taxonomy": {
    "version": "categories/1",
    "categories": [
      {"id": 1, "label": "ABRIGOS", "aliases": ["coat", "outerwear"]},
      {"id": 3, "label": "CAMISAS", "aliases": ["shirt", "button-up shirt"]},
      {"id": 7, "label": "PANTALONES", "aliases": ["trousers", "pants"]}
    ]
  },
  "color_catalog": {
    "version": "colors/2026-08-03",
    "colors": [
      {"id": 4, "name": "BEIGE", "family": "BEIGE", "hex": "#D8C3A5"},
      {"id": 6, "name": "GRIS", "family": "GRIS", "hex": "#8A8F98"}
    ]
  },
  "known_images": [
    {"image_id": 6, "sha256": "...", "perceptual_hash": "...", "embedding": [0.012, -0.031, 0.044]}
  ]
}
```

`known_images` se limita a candidatos prefiltrados para el catálogo pequeño; en IA-6 se sustituye por índice persistente. La dimensión real del embedding debe coincidir con el modelo y se valida; el ejemplo está abreviado.

### Respuesta

```json
{
  "contract_version": "1.0",
  "request_id": "d0c53359-915d-48e8-94ed-01771de7f918",
  "analysis_id": "b2cb0a70-8314-4388-a803-3a32b6662bb4",
  "status": "completed_with_warnings",
  "image": {
    "sha256": "7b97...d004",
    "detected_mime": "image/jpeg",
    "width": 1920,
    "height": 2880,
    "bytes": 193904,
    "exif_removed_in_working_copy": true
  },
  "quality": {
    "overall": "warning",
    "checks": [
      {"code": "MIN_DIMENSIONS", "status": "pass", "value": 1920, "threshold": 384},
      {"code": "BACKGROUND_DOMINANCE", "status": "warning", "value": 0.71, "threshold": null}
    ]
  },
  "colors": {
    "method": "lab_kmeans_roi_v1",
    "foreground_confidence": "medium",
    "items": [
      {
        "observed_hex": "#D6CCBB",
        "lab": [82.1, 1.8, 8.9],
        "percentage": 0.68,
        "catalog_candidate": {"id": 4, "name": "BEIGE", "family": "BEIGE", "distance_delta_e": 6.2}
      }
    ]
  },
  "categories": {
    "score_type": "siglip_logit_sigmoid_uncalibrated",
    "items": [
      {"candidate_id": 7, "label": "PANTALONES", "score": 0.812, "rank": 1, "margin_to_next": 0.146},
      {"candidate_id": 6, "label": "FALDAS", "score": 0.666, "rank": 2, "margin_to_next": 0.081},
      {"candidate_id": 10, "label": "CONJUNTOS", "score": 0.585, "rank": 3, "margin_to_next": null}
    ],
    "abstained": false
  },
  "duplicates": [
    {"image_id": 6, "type": "exact", "score": 1.0, "reason": "sha256_equal"}
  ],
  "embedding": {
    "model_version": "<revision-fijada>",
    "dimension": 768,
    "normalized": true,
    "vector": [0.012, -0.031, 0.044]
  },
  "warnings": ["BACKGROUND_MAY_AFFECT_COLOR"],
  "models": [
    {"task": "zero_shot_classification", "name": "google/siglip-base-patch16-224", "version": "<revision-fijada>", "prompt_set_version": "garment-prompts/1", "taxonomy_version": "categories/1"}
  ],
  "timings_ms": {"preprocess": 42, "inference": 118, "postprocess": 24, "total": 184},
  "runtime": {"device": "cuda", "dtype": "float16", "fallback": false}
}
```

Los números son ejemplo de formato, no resultados medidos ni umbrales aprobados.

### Reglas contractuales

| Aspecto | Regla |
|---|---|
| Autenticación | Bearer, red interna, rate limit |
| Validación | 1 imagen; MIME decodificado; JPEG/PNG/WebP estático; ≤6 MB; límite de píxeles; `top_k` 1–5; taxonomía no vacía, IDs únicos; features soportadas |
| HTTP | 200 completado; 207 no se usa: warnings viven en body; 400/413/415/422; 401/403; 409 idempotency/taxonomy; 429 ocupado; 503 modelo; 504 timeout |
| Timeout | Presupuesto 8 s; duro 12 s |
| Idempotencia | Obligatoria; misma clave+hash devuelve misma respuesta; payload distinto →409 |
| Errores posibles | Corrupción, bomb/píxeles, formato, taxonomía, modelo ocupado/no disponible, OOM/timeout |
| Confianza | Score type, top-k, margen y abstención; probabilidad calibrada nullable |

## 5. `POST /api/v1/garments/similar`

### Propósito

Rankear prendas similares usando embedding y restricciones. En la fase sin acceso DB, Laravel envía candidatos prefiltrados; en el futuro FastAPI puede consultar un índice IA de solo lectura.

### Entrada

```json
{
  "contract_version": "1.0",
  "query": {
    "garment_id": 6,
    "embedding": [0.012, -0.031, 0.044],
    "model_version": "siglip-base-p16-224@<revision>",
    "category_id": 7,
    "color_family": "BEIGE",
    "season_id": 5,
    "price": 500.0
  },
  "candidates": [
    {
      "garment_id": 2,
      "embedding": [0.017, -0.028, 0.040],
      "model_version": "siglip-base-p16-224@<revision>",
      "category_id": 7,
      "color_family": "BEIGE",
      "season_id": 5,
      "price": 300.0
    }
  ],
  "filters": {
    "allowed_category_ids": [7],
    "price_min": 200.0,
    "price_max": 700.0,
    "exclude_garment_ids": [6]
  },
  "limit": 8,
  "ranking_version": "similarity/1"
}
```

### Respuesta

```json
{
  "contract_version": "1.0",
  "request_id": "d0c53359-915d-48e8-94ed-01771de7f918",
  "status": "completed",
  "items": [
    {
      "garment_id": 2,
      "rank": 1,
      "score": 0.91,
      "components": {"visual_cosine": 0.94, "color": 1.0, "season": 1.0, "price": 0.62},
      "reasons": ["misma categoría", "color compatible", "alta similitud visual"]
    }
  ],
  "model_version": "siglip-base-p16-224@<revision>",
  "ranking_version": "similarity/1",
  "metrics": {"candidates_received": 1, "candidates_eligible": 1},
  "timings_ms": {"total": 3}
}
```

| Aspecto | Regla |
|---|---|
| Autenticación | Bearer interno |
| Validación | Vectores finitos, normalizados o normalizables; misma dimensión/modelo; IDs únicos; máximo de candidatos; límites de precio coherentes |
| HTTP | 200; 400/422; 401/403; 409 versión incompatible; 413 candidatos excesivos; 429; 503 |
| Timeout | 3 s inicial |
| Idempotencia | Requerida si se registra resultado; recomendable siempre |
| Errores | Embedding ausente/incompatible, lista vacía, categoría inválida, índice no listo |
| Confianza | Score total + componentes; no probabilidad; Laravel revalida stock/estado |

## 6. `POST /api/v1/recommendations/products`

### Propósito

Rankear candidatos suministrados por Laravel usando reglas, contenido y señales reales, sin consultar ni modificar inventario.

### Entrada

```json
{
  "contract_version": "1.0",
  "recommendation_id": "efba0da2-9120-4c45-a7ca-550f954f5016",
  "strategy": "content_popularity/1",
  "user_context": {
    "user_ref": "user:42",
    "favorite_garment_ids": [2, 6],
    "purchased": [{"garment_id": 1, "occurred_at": "2026-07-10T15:00:00Z", "weight": 1.0}],
    "reserved": [{"garment_id": 8, "occurred_at": "2026-07-20T15:00:00Z", "weight": 0.5}],
    "preferred_size_ids": []
  },
  "candidates": [
    {
      "garment_id": 11,
      "category_id": 8,
      "brand_id": 1,
      "color_family": "BEIGE",
      "season_id": 5,
      "price": 450.0,
      "created_at": "2026-07-31T10:00:00Z",
      "popularity": {"valid_sales_units": 3, "favorites": 2},
      "embedding": [0.014, -0.025, 0.041]
    }
  ],
  "constraints": {
    "limit": 12,
    "price_min": null,
    "price_max": 600.0,
    "exclude_seen": false,
    "diversity": true
  }
}
```

Laravel solo incluye candidatos activos/elegibles y vuelve a filtrar disponibilidad después.

### Respuesta

```json
{
  "contract_version": "1.0",
  "request_id": "d0c53359-915d-48e8-94ed-01771de7f918",
  "recommendation_id": "efba0da2-9120-4c45-a7ca-550f954f5016",
  "status": "completed",
  "strategy": "content_popularity/1",
  "items": [
    {
      "garment_id": 11,
      "rank": 1,
      "score": 0.73,
      "components": {"content": 0.81, "popularity": 0.42, "novelty": 0.90},
      "reasons": ["similar a tus favoritos", "novedad"]
    }
  ],
  "metrics": {"candidate_count": 1, "eligible_count": 1, "catalog_coverage": 1.0},
  "model_version": "siglip-base-p16-224@<revision>",
  "ranking_version": "content_popularity/1"
}
```

| Aspecto | Regla |
|---|---|
| Autenticación | Bearer interno; autorización de usuario resuelta en Laravel |
| Validación | Señales conocidas; pesos dentro de rango aprobado; candidatos únicos; no PII; límite de candidatos; embeddings compatibles |
| HTTP | 200; 400/422; 401/403; 409 versión; 413; 429; 503 |
| Timeout | 3 s, máximo inicial 5 s |
| Idempotencia | Obligatoria por `recommendation_id`/cabecera |
| Errores | Sin candidatos, features incompatibles, estrategia no disponible, modelo no listo |
| Confianza | Score de ranking y componentes; no probabilidad de compra |

## 7. `POST /api/v1/recommendations/outfits`

### Propósito

Generar/rankear combinaciones compatibles a partir de candidatos ya filtrados por Laravel.

### Entrada

```json
{
  "contract_version": "1.0",
  "recommendation_id": "0d602085-c1c5-45fc-a333-7a93f2951295",
  "strategy": "outfit_rules/1",
  "anchor_garment_id": 7,
  "occasion": null,
  "season_id": 5,
  "budget": {"currency": "BOB", "max_total": 1200.0},
  "size_constraints": {"allowed_size_ids": [2, 3]},
  "candidates": [
    {"garment_id": 7, "role": "top", "category_id": 3, "color_family": "BEIGE", "price": 250.0, "embedding": [0.01, 0.02]},
    {"garment_id": 6, "role": "bottom", "category_id": 7, "color_family": "BEIGE", "price": 500.0, "embedding": [0.03, 0.04]},
    {"garment_id": 1, "role": "outerwear", "category_id": 1, "color_family": "MARRON", "price": 700.0, "embedding": [0.05, 0.06]}
  ],
  "constraints": {"limit": 5, "max_items": 4, "require_anchor": true}
}
```

### Respuesta

```json
{
  "contract_version": "1.0",
  "recommendation_id": "0d602085-c1c5-45fc-a333-7a93f2951295",
  "status": "completed",
  "strategy": "outfit_rules/1",
  "outfits": [
    {
      "rank": 1,
      "score": 0.78,
      "total_price": 750.0,
      "items": [
        {"garment_id": 7, "role": "top"},
        {"garment_id": 6, "role": "bottom"}
      ],
      "components": {"role_compatibility": 1.0, "color": 0.75, "visual": 0.62, "budget": 1.0},
      "reasons": ["roles complementarios", "dentro del presupuesto"]
    }
  ],
  "ranking_version": "outfit_rules/1"
}
```

| Aspecto | Regla |
|---|---|
| Autenticación | Bearer interno |
| Validación | Roles del vocabulario aprobado; IDs únicos; precios no negativos; dimensiones de embedding; presupuesto/limit; matriz de compatibilidad versionada |
| HTTP | 200; 400/422; 401/403; 409 taxonomía/matriz; 413 combinaciones; 429; 503 |
| Timeout | 5 s síncrono; combinaciones grandes →202/job futuro |
| Idempotencia | Obligatoria por recommendation ID |
| Errores | Falta rol, no hay combinación válida, presupuesto imposible, estrategia no disponible |
| Confianza | Score de ranking/componentes; no afirmar “combina perfectamente” |

Calzado no aparece si el catálogo no tiene ese rol. `occasion` permanece `null` hasta existir taxonomía confirmada.

## 8. `POST /api/v1/feedback`

### Propósito

Validar/normalizar un evento de feedback ya autorizado y registrado oficialmente por Laravel. Inicialmente FastAPI no lo persiste; devuelve elegibilidad y versión de esquema. Laravel conserva la verdad y puede enviarlo asíncronamente para telemetría/dataset.

### Entrada

```json
{
  "contract_version": "1.0",
  "feedback_id": "e456d2c1-60bf-4f2b-843b-b2ec9c50820d",
  "analysis_id": "b2cb0a70-8314-4388-a803-3a32b6662bb4",
  "subject": {"type": "garment_image", "garment_id": 6, "image_id": 8},
  "attribute": "category",
  "action": "corrected",
  "predicted": {"candidate_id": 8, "label": "POLOS Y KNITWEAR", "score": 0.62},
  "validated": {"candidate_id": 7, "label": "PANTALONES"},
  "model_version": "siglip-base-p16-224@<revision>",
  "taxonomy_version": "categories/1",
  "occurred_at": "2026-08-03T20:10:00Z",
  "validation": {"explicit_human_action": true, "source": "admin_form"}
}
```

### Respuesta

```json
{
  "contract_version": "1.0",
  "feedback_id": "e456d2c1-60bf-4f2b-843b-b2ec9c50820d",
  "status": "accepted",
  "normalized": {"attribute": "category", "action": "corrected"},
  "training_eligibility": {
    "eligible": true,
    "reasons": ["explicit_human_validation"],
    "requires_review": true
  }
}
```

| Aspecto | Regla |
|---|---|
| Autenticación | Bearer interno |
| Validación | UUIDs; análisis existente validado por Laravel; atributo/acción cerrados; versión; predicción y validado coherentes; sin PII |
| HTTP | 200/201 lógico; 400/422; 401/403; 409 duplicado con otro payload; 404 solo si FastAPI futuro mantiene índice; 503 |
| Timeout | 2–5 s; preferible asíncrono/no bloqueante |
| Idempotencia | Obligatoria y única por `feedback_id` |
| Errores | Acción inválida, versión desconocida, contradicción, duplicado |
| Confianza | Conserva score original; el feedback no recalcula confianza |

## 9. Política de escritura y lectura

### Primera fase

```text
FastAPI: sin credenciales PostgreSQL
Laravel: lee catálogo/comercial → llama a FastAPI → valida → escribe PostgreSQL
FastAPI: modelos/cache temporal solamente
```

Ventajas: privilegio mínimo, transacciones en un solo dueño, auditoría clara y pruebas simples. El costo de enviar candidatos es aceptable para el catálogo actual.

### Fase futura

FastAPI puede obtener acceso de solo lectura a un esquema/vista IA con embeddings y atributos no sensibles, o a una réplica. Laravel sigue escribiendo y vuelve a filtrar disponibilidad. No se concede lectura a comprobantes, hashes de contraseña, teléfonos, pagos o auditoría personal.

## 10. Tablas existentes reutilizables

| Tabla | Uso IA seguro |
|---|---|
| `prendas` | Verdad confirmada de categoría, marca, temporada, género, descripción/precio/estado |
| `prenda_variantes` | Color/talla/precio/costo/estado por variante |
| `imagenes_prenda` | Identidad y ruta de originales; falta metadata técnica/hash |
| `categorias`, `colores`, `tallas`, `marcas`, `temporadas` | Taxonomías/candidatos reales |
| `favoritos` | Señal explícita positiva |
| `detalle_ventas`, `ventas` | Compra/unidades/fecha/estado; filtrar solo eventos válidos |
| `detalle_reservas`, `reservas` | Intención/operación; ponderar por estado |
| `inventarios` | Elegibilidad actual, siempre revalidada por Laravel |
| `inventario_movimientos` | Rotación y eventos de stock, no perfil personal |
| `system_logs`, `audit_logs` | Trazabilidad existente; no reemplazan resultados IA |
| `jobs`, `failed_jobs` | Cola Laravel para operaciones asíncronas |

No se recomienda introducir columnas JSON IA dentro de `prendas`: mezclarían predicción transitoria con verdad comercial.

## 11. Tablas propuestas

Las siguientes son diseño, no migraciones. Todas pertenecen a Laravel/PostgreSQL. Usar nombres Laravel convencionales o la convención vigente tras aprobación; aquí se prioriza claridad.

### 11.1 `ai_model_versions`

- **Finalidad:** registro reproducible de modelos/pipelines aprobados.
- **Campos:** `id` UUID/bigint, `task`, `model_name`, `model_version`, `artifact_uri` privado, `artifact_sha256`, `framework`, `pipeline_version`, `preprocessor_version`, `prompt_set_version`, `taxonomy_version`, `embedding_dimension`, `dtype`, `status` (`candidate|active|retired|failed`), `metrics_json`, `approved_by`, `approved_at`, timestamps.
- **Relaciones:** referenciada por análisis/embeddings/recomendaciones mediante ID o versión inmutable.
- **Índices:** único `(task, model_version, pipeline_version, taxonomy_version)`; índice `(task,status)`.
- **JSON:** métricas offline y manifest; nunca secretos.
- **Retención:** permanente mientras existan resultados; artefactos según política de modelos.
- **Escribe/lee:** Laravel administra/aprueba; FastAPI informa/lee configuración por despliegue, no escribe DB inicialmente.
- **Primera fase:** sí, o manifest equivalente inmutable. Tabla preferida para auditoría.

### 11.2 `ai_analysis_results`

- **Finalidad:** conservar una ejecución síncrona y su salida original.
- **Campos:** `id`/`analysis_id` UUID, `request_id`, `idempotency_key`, `user_id` nullable, `garment_id` nullable, `image_id` nullable, `image_sha256`, `analysis_type`, `status` (`requested|processing|completed|completed_with_warnings|failed|expired`), `contract_version`, `pipeline_version`, `model_version_id`, `taxonomy_version`, `requested_features_json`, `result_json`, `warnings_json`, `error_code`, `error_message_sanitized`, `device`, `fallback_cpu`, tiempos ms, `started_at`, `completed_at`, timestamps.
- **Relaciones:** usuario/prenda/imagen nullable; modelo; uno-a-muchos feedback.
- **Índices:** únicos `analysis_id`, `idempotency_key`; `(garment_id,created_at)`, `(image_id,model_version_id)`, `(status,created_at)`, `image_sha256`.
- **JSON:** snapshot del contrato; campos frecuentes pueden promoverse después de medir consultas.
- **Retención:** resultado técnico/auditoría 12–24 meses propuestos; errores sanitizados menos tiempo; aprobar legal/negocio.
- **Escribe/lee:** solo Laravel escribe; Laravel/UI autorizada lee; datasets acceden por export controlado.
- **Primera fase:** **sí**.

### 11.3 `ai_feedback`

- **Finalidad:** acción humana por atributo o recomendación.
- **Campos:** `id`/`feedback_id` UUID, `analysis_result_id` nullable, `recommendation_id` nullable, `user_id`, `garment_id` nullable, `image_id` nullable, `attribute`, `action` (`accepted|corrected|rejected|ignored|clicked|converted|dismissed` según dominio), `predicted_value_json`, `validated_value_json`, `reason_code` nullable, `explicit_human_action`, `model_version_id`, `taxonomy_version`, `eligible_for_training`, `review_status` (`pending|approved|rejected`), `reviewed_by`, `reviewed_at`, timestamps.
- **Relaciones:** análisis/recomendación, usuario validador, prenda/imagen, modelo.
- **Índices:** único `feedback_id`; `(analysis_result_id,attribute,user_id)` según política de múltiples revisiones; `(eligible_for_training,review_status)`; `(model_version_id,attribute,created_at)`.
- **JSON:** predicción y valor validado tipados por `attribute`; validar schema en aplicación.
- **Retención:** igual o mayor que dataset lineage; pseudonimizar usuario al exportar.
- **Escribe/lee:** Laravel; FastAPI recibe copia por API/cola sin autoridad.
- **Primera fase:** **sí**.

### 11.4 `ai_analysis_jobs`

- **Finalidad:** estado de trabajos IA de larga duración, además de la tabla técnica `jobs`.
- **Campos:** `id` UUID, `job_type`, `subject_type/id`, `status` (`pending|queued|running|succeeded|failed|cancelled|expired`), `priority`, `payload_json` minimizado, `attempts`, `max_attempts`, `idempotency_key`, `progress`, `result_analysis_id`, `error_code`, `available_at`, `started_at`, `finished_at`, timestamps.
- **Relaciones:** resultado de análisis; subject lógico validado por Laravel.
- **Índices:** único `idempotency_key`; `(status,available_at,priority)`, `(subject_type,subject_id,job_type)`.
- **Retención:** jobs exitosos 90 días propuestos; fallidos 12 meses o según auditoría; payload temporal.
- **Escribe/lee:** Laravel Job/orquestador.
- **Primera fase:** no para análisis síncrono; sí al incorporar OCR/backfill.

### 11.5 `ai_detected_attributes`

- **Finalidad:** normalizar atributos detectados para consultas analíticas.
- **Campos:** `id`, `analysis_result_id`, `attribute_type`, `candidate_ref`, `label_snapshot`, `rank`, `raw_score`, `score_type`, `calibrated_probability`, `value_json`, `model_version_id`, timestamps.
- **Relaciones/índices:** análisis; modelo; único `(analysis_result_id,attribute_type,rank)`; índice `(attribute_type,candidate_ref)`.
- **Retención:** ligada al análisis.
- **Escribe/lee:** Laravel.
- **Primera fase:** **no**; `result_json` evita sobre-normalizar. Crear cuando consultas reales lo requieran.

### 11.6 `ai_embeddings`

- **Finalidad:** vector por imagen/prenda/modelo.
- **Campos:** `id`, `entity_type` (`garment_image|garment`), `entity_id`, `image_sha256`, `model_version_id`, `dimension`, `embedding` (`vector(n)` con pgvector o formato temporal), `normalized`, `status` (`active|stale|failed`), `generated_at`, timestamps.
- **Relaciones:** imagen/prenda lógica; modelo.
- **Índices:** único `(entity_type,entity_id,model_version_id,image_sha256)`; `(model_version_id,status)`; HNSW/IVFFlat coseno solo tras benchmark; índices parciales por modelo activo si conviene.
- **Retención:** mantener activo y versión anterior durante rollback; borrar/stale al cambiar imagen.
- **Escribe/lee:** Laravel persiste respuestas FastAPI; FastAPI podría leer vista futura.
- **Primera fase:** no; necesaria en IA-6. El embedding puede permanecer solo en resultado durante piloto.

### 11.7 `ai_recommendations`

- **Finalidad:** impresión/lista generada para evaluación e idempotencia.
- **Campos:** `id`/`recommendation_id`, `user_id` nullable, `context_type`, `strategy_version`, `model_version_id` nullable, `request_context_json`, `candidate_count`, `status`, `generated_at`, `expires_at`, timestamps.
- **Índices:** `(user_id,generated_at)`, `(strategy_version,generated_at)`, `expires_at`.
- **Retención:** 90–180 días propuestos; anonimizar/agg después.
- **Escribe/lee:** Laravel.
- **Primera fase:** no; IA-8/IA-9.

### 11.8 `ai_recommendation_items`

- **Finalidad:** orden, score, explicación e interacción por ítem/outfit.
- **Campos:** `id`, `recommendation_id`, `garment_id` nullable, `outfit_group_id` nullable, `rank`, `score`, `components_json`, `reasons_json`, `eligible_snapshot_json`, timestamps.
- **Índices:** único `(recommendation_id,rank,outfit_group_id)`; `(garment_id,created_at)`.
- **Retención/escritura:** ligada a recomendación; Laravel.
- **Primera fase:** no.

### 11.9 `ai_interactions`

- **Finalidad:** impresiones/clics/feedback necesarios para evaluar recomendaciones.
- **Campos:** `id` UUID, `user_id` nullable, `anonymous_session_hash` nullable, `recommendation_id` nullable, `recommendation_item_id` nullable, `garment_id`, `event_type` (`impression|click|favorite|add_to_cart|reserve|purchase|dismiss`), `position`, `context`, `occurred_at`, `metadata_json` minimizado, `consent_version` nullable.
- **Índices:** `(user_id,occurred_at)`, `(garment_id,event_type,occurred_at)`, `(recommendation_id,event_type)`, dedupe de evento cliente.
- **Retención:** eventos detallados por período aprobado; luego agregación/anonimización.
- **Escribe/lee:** Laravel captura; pipeline analítico lee export.
- **Primera fase:** no. Obligatoria antes de afirmar eficacia online de recomendaciones.

### 11.10 `ai_training_samples`

- **Finalidad:** manifest de elegibilidad/lineage; no almacenar bytes de imagen en DB.
- **Campos:** `id`, `image_id`, `garment_id`, `feedback_id`, `image_sha256`, `label_snapshot_json`, `quality_snapshot_json`, `dataset_version`, `split`, `status` (`candidate|approved|excluded|exported`), `exclusion_reason`, `approved_by`, `approved_at`, timestamps.
- **Índices:** único `(image_sha256,dataset_version,label_snapshot_hash)`; `(dataset_version,split,status)`; `(feedback_id)`.
- **Retención:** permanente para reproducibilidad; respetar borrado/consentimiento mediante tombstone.
- **Escribe/lee:** curación bajo Laravel/comando offline; entrenamiento lee export inmutable.
- **Primera fase:** no; IA-5 puede iniciar candidatos y IA-10 formalizarla.

## 12. Relaciones lógicas

```text
ai_model_versions
  ├─ ai_analysis_results ── ai_feedback
  ├─ ai_embeddings
  └─ ai_recommendations ── ai_recommendation_items ── ai_interactions

imagenes_prenda/prendas
  ├─ ai_analysis_results
  ├─ ai_embeddings
  └─ ai_training_samples ← ai_feedback

ai_analysis_jobs ── ai_analysis_results
```

Foreign keys a entidades comerciales pueden usar `nullOnDelete` más snapshot/hash si la auditoría necesita conservar la ejecución; la política exacta requiere aprobación de privacidad.

## 13. Almacenamiento de resultados e imágenes

- Original definitivo: Laravel en su disco actual; en futuro object storage.
- Imagen temporal FastAPI: privada, nombre UUID, re-encode sin EXIF, borrada al terminar.
- No guardar thumbnails/máscaras intermedias salvo depuración explícita y sin producción por defecto.
- Resultado crudo: `ai_analysis_results.result_json`, firmado por versión de contrato/modelo.
- Dato confirmado: columnas comerciales existentes o nuevos vocabularios aprobados; nunca dentro de `result_json` como única verdad.
- Embeddings: tabla separada, regenerables, con modelo/checksum.
- Dataset: manifest versionado + imágenes autorizadas por URI controlada; no copiar comprobantes ni PII.

## 14. Tablas mínimas por fase

| Fase | Tablas nuevas realmente necesarias |
|---|---|
| IA-1/IA-2 simulada | Ninguna obligatoria; fake/contratos |
| MVP IA-3/IA-4 | `ai_model_versions`, `ai_analysis_results` |
| Validación IA-5 | `ai_feedback` |
| Async/OCR/backfill | `ai_analysis_jobs` (además de Laravel jobs) |
| Similares IA-6 | `ai_embeddings` y extensión pgvector si se aprueba |
| Recomendaciones IA-8/IA-9 | `ai_recommendations`, `ai_recommendation_items`, `ai_interactions` |
| Entrenamiento IA-11 | `ai_training_samples` o manifest equivalente |
| `ai_detected_attributes` | Solo si consultas reales justifican normalización |

## 15. Conclusión de datos

La opción inicial recomendada es FastAPI sin PostgreSQL y Laravel como único escritor. El núcleo mínimo persistente son versiones, resultados y feedback. Jobs, embeddings, recomendaciones e interacciones se incorporan solo con el módulo que los necesita. Esta secuencia evita una “base de IA” sobrediseñada y conserva una separación verificable entre sugerencia y verdad comercial.
