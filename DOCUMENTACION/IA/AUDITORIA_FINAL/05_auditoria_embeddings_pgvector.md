# Auditoría de embeddings, PostgreSQL y pgvector

Fecha: 2026-10-04 America/La_Paz. Commit inspeccionado fde26bd2d1aebb733733802001f6ce63d5599792 con cambios locales preexistentes. Se distingue el entorno comercial actual del aislamiento preparado para validar código. Las consultas comerciales usaron BEGIN TRANSACTION READ ONLY/ROLLBACK; no se aplicaron migraciones ni se indexó ni se cambió su catálogo.

## Estado real del entorno

| Componente | Entorno comercial actual | Aislamiento de auditoría |
|---|---|---|
| PostgreSQL |18.3 Alpine, more-pgsql-1 |18.3 Debian, more-ai-audit-pg-20261004, base testing, tmpfs |
| Extensión vector |No instalada; tampoco disponible en pg_available_extensions |0.8.2 instalada desde imagen ya disponible |
| Imagen propuesta por repo |PostgreSQL18 Alpine + pgvector0.8.7 |No valida ese build/version específico |
| create_catalog_ai_tables |PENDING |Aplicada sólo en testing |
| create_ai_product_embeddings_table |PENDING |Aplicada sólo en testing |
| ai_runs/suggestions/reviews/model_versions/product_embeddings |No existen |Existen |
| prendas.modelo_pre |No existe |Creada por migración IA |
| FastAPI comercial |No estaba corriendo inicialmente; token Laravel ausente |FastAPI temporal con token de auditoría |
| Búsqueda vectorial |NO FUNCIONA, instalación pendiente |FUNCIONA CON LIMITACIONES: geometría/números correctos, relevancia aún no evaluable |

Config comercial efectiva: enabled=true y vectors.enabled=true, token_present=false, URL http://mati-ai:8000, timeout30, queue.default=database. Activar una bandera no crea tablas, extensión ni servicio. No se imprimieron credenciales. Evidencia: _database_audit.json, migrate:status, pg_extension/pg_available_extensions.

Las pruebas aisladas no demuestran funcionamiento del despliegue actual. La ausencia de tablas es preparación/despliegue pendiente, no fallo matemático del encoder.

## Dataset real y procedencia

134 prendas,134 variantes,134 imágenes principales. Todas las fotos existen y cumplen JPEG/PNG/WebP/6MB/40MP. Hay131 SHA únicos y tres grupos exactos repetidos: [2,5],[10,11],[14,15]. El grupo[2,5] tiene categorías distintasIDs7/11, se excluyó de accuracy limpio; quedan130 imágenes únicas con label coherente. No se corrigió ninguna etiqueta/fichero.

11 categorías activas,10 con ejemplos: ACCESORIOS0, CONJUNTOS3, FALDAS7, BLUSAS8. Antes de dedup: POLOS Y KNITWEAR22, CAMISAS20, VESTIDOS18, POLERA17, PANTALONES16, ABRIGOS13, BLAZERS10. Desbalance afecta agregados; Macro-F1 debe acompañar accuracy. Marcas10 representadas de12; PATITO1 ejemplo. Patrones asignados0: accuracy de patrón NO EVALUABLE.

56 colores,55 activos/1 inactivo; HEX válidos56/56, sin HEX exactos o nombres normalizados duplicados. Esto no demuestra ausencia de cercanía semántica ni fidelidad del HEX a la tela.

Categoría: label comercial confirmado en catálogo, sin anotación experta independiente. Color: label comercial de variante, proxy de color fotografiado/físico, sin referencia instrumental. Retrieval: no hay relevancia de pares anotada independientemente.

Manifiesto evaluation_manifest.json contiene referencias sin copiar fotos, IDs/rutas/SHA/categoría/marca/color/patrón/fuente/eligible_metrics. SHA final f24d4901949a70769df86f8b85fce7ee85d5ed5e0b90c1bb57e8b77c07c61c5e. Primera exportación anterior a aliases/campos explícitos SHA3d012c94367ffefda2dd6382aa902df22271a201bb69e98da8a014abca101a28: mismas fotos/labels, sólo metadata extendida, sin retuning.

Read-only comercial: imágenes sin prenda0; variantes sin prenda0; imagen-variante de otra prenda0. Huérfanos IA actuales NO EVALUABLE porque las tablas no existen. Hash seleccionado catálogo antes/después idéntico:68b7050be96007dba2c27976f1f15ebad0ed34652791899f85c93e88f2788868.

## Conservación y búsqueda del embedding

FastAPI genera vector real. Laravel conserva JSON histórico en ai_runs.embedding y nativo canónico en ai_product_embeddings al vincular imagen comercial guardada con SHA coincidente. Vector nativo oculto de serialización/UI.

Identidad: model_id + model_revision SHA + pipeline_version + dimensiones reales + embedding_version + preprocess_version + normalization. geometry_key resume perfil. Dimensiones de count(values), no nombre de modelo. En130 inferencias reales observadas:768.

Modelo google/siglip-base-patch16-224, revisión7fd15f0689c79d79e38b1c2e2e2370a7bf2761ed, pipeline registration-1.2.0, embedding projected-image-l2-1, preprocesado srgb-exif-transformers-image-1, L2. prepare rechaza vacío/cero/no finito/dimensión incorrecta/revisión mutable/identidad discordante/noL2 y normaliza con escala estable. DB exige vector_dims=dimensions y vector_norm≈1.

pgvector extiende PostgreSQL con tipo/operadores: no reemplaza base relacional, transacciones, claves ni joins. Nearest neighbors encuentra menor distancia a query. Búsqueda actual exacta sobre todos los vectores elegibles compatibles, no ANN; cálculo conceptual O(N·D) más selección/ordenación. CTE MATERIALIZED compatible impide operaciones entre dimensiones distintas. Con130 candidatos y latencias observadas, exact search suficiente; no evidencia que justifique HNSW/IVFFlat.

Cosine=(q·v)/(||q||·||v||); L2 aproxima producto escalar; distancia=1-similitud. Dirección visual del embedding, no identidad, probabilidad, gusto ni personalización. Possible_duplicate por SHA igual o similitud≥.98 es alerta a revisar, sin fusión automática.

## Validación canónica con vectores reales

Se copió sólo catálogo/maestros/atributos/inventario a testing desde SELECT comercial read-only, sin usuarios/ventas/reservas/pagos. Autor remapeado a usuario sintético20 sólo en testing para conservar FK sin copiar cuentas. Fotos referenciadas en filesystem existente, sin cambios.

130 vectores reales de storage/app/ai-audit/embedding_sample.json (SHAc22efe4fe73284a141636483fa7f0b79dd469a11f567f3ae66e73b629a4a408e) persistidos mediante AiReviewService y CatalogEmbeddingStore productivos con conexión explícitamente aislada.

| Métrica | Observado | N / interpretación |
|---|---|---|
| Canónicos creados |130 |130 fotos únicas coherentes |
| Coverage persistencia muestra aislada |100% |130/130, no production coverage |
| Dimensión min/max |768/768 |130 inferencias reales |
| Norma min/max |0.9999999701963109 /1.000000029792895 |Float32 esperado |
| Replay storeRun |misma fila/count130 estable |Idempotencia comprobada |
| Orden TOP30 SQL vs brute PHP64 |100% |130consultas; excluye propia prenda |
| Orden TOP30 SQL vs NumPy |100% (130/130) |Mismos embeddings/exclusiones; maxerror2.389387153689526e-7 |
| Error absoluto coseno máximo vs PHP64 |2.389387148138411e-7 |Float32pgvector vs double |
| search p50/p95 Laravel+DB |4.330321ms /4.783514ms |130consultas calientes; sin inferencia/UI |
| Persistir130 |1137.636541ms |Una corrida, no SLA |
| EXPLAIN planning/execution |0.275ms /1.180ms |1 SELECT productivo q768,K12,129 candidatos |

Raw: pgvector_isolated_audit.json, pgvector_sql_neighbors.json, pgvector_negative_explain_audit.json en storage/app/ai-audit. Validación NumPy ejecutada sin nueva inferencia:130/130 rankings TOP30 idénticos, error máximo absoluto2.389387153689526e-7. Evidencia adicional numpy_pgvector_agreement.json. Tiempos locales aislados, no producción.

Recall@K/MRR/nDCG semánticos NO EVALUABLE sin juicios independientes de relevancia. Acuerdo con fuerza bruta comprueba algoritmo numérico, no calidad comercial del encoder. Recuperar foto idéntica tampoco sustituye etiquetas de similitud.

## Contratos negativos:17 comprobaciones

Script reproducible services/mati-ai/scripts/audit/pgvector_validate.php fija host aislado/base testing/password sólo env.17/17 con resultado esperado; fixtures/cambios sintéticos en transacción rollback; count final130.

- Vacío/cero/no finito/dimensión incorrecta/main/L1/identidad discordante rechazados.
- Dim2 y768 coexisten: query768 separa espacios y no falla.
- Revisión/preprocesado distinto devuelve espacio incompatible vacío.
- SQLSTATE23514 por dimensiones/norma;23503 por prenda inexistente.
- Imagen de otra prenda y model_version_id discordante rechazados.
- Path modificado excluye vector previo.
- Defecto filtros color+talla independientes reproducido.

No son accuracy del modelo ni inferencia real visual: son errores/contratos con fixtures sintéticas.

## Comando real de indexación, sólo aislado

En more-ai-audit-laravel-20261004, con token/servicio efímeros y base testing, comando mati-ai:index-catalog --limit=1 --batch-size=1: processed1,skipped1,indexed0,failed0,cursor after-id1. Luego --force: processed1,updated1,failed0,count130. Llamó embedding real (inferencias adicionales de ruta de indexación), sin crear segunda fila canónica ni modificar catálogo comercial.

No indexación masiva actual. Command sólo imágenes principales activas/locales; no toda galería. Backfill exige hash/revisión reales y separa perfil legacy. Idempotencia de fila no elimina costo de inferencia previa para descubrir identidad.

## Hallazgos y propuesta sin implementar

| ID | Prioridad | Evidencia | Impacto / propuesta |
|---|---|---|---|
|DB-01|P1 despliegue|Dos migrations pendientes/vector ausente/token ausente|Persistencia/pgvector comercial no operativos. Preparar imagen/extensión/migraciones/token/servicio tras auditoría y validar runtime. |
|PG-01|P2 REPRODUCIDO|CatalogEmbeddingStore.php:254–259|EXISTS color/talla separados acepta producto con A/S+B/M para A/M sin esa variante. Unificar si contrato variante específica. UI no expone filtros, impacto otros consumidores/futuro. |
|PG-02|P2 riesgo|CatalogEmbeddingStore.php:202–209|Vigencia ID/propietario/path, no SHA cada query; bytes reescritos mismo path dejan vector viejo. Flujo normal usa paths nuevos; no se alteró fichero real. Objetos inmutables/invalidez por contenido. |
|PG-03|P2 datos|Manifest images2/5|SHA idéntico labelsPANTALONES/POLERA contradictorios. Excluido baseline, revisión humana después sin corregir durante auditoría. |
|PG-04|P3 docs|Doc03_modulos_modelos_y_algoritmos.md:225|Máximo2000 confunde límites de indexación con tipo16000. Documentar después sin reescribir historia. |
|PG-05|P3 validación|Aislada0.8.2 vs repo0.8.7|No afirmar build/version final probado. Verificar imagen propuesta al preparar despliegue. |

Sin añadir ANN/Redis IA/NoSQL, entrenamiento/prompts/thresholds. Redis infraestructura existe; estos servicios IA no lo usan como queue/cache/persistencia. Métricas proceden de ejecuciones descritas separadas de fixtures y del despliegue aún no preparado.

## Suplemento final: imagen objetivo0.8.7 y relevancia

Dockerfile del repo pgvector0.8.7 construido exit0. Contenedor nuevo more-ai-audit-pg087-20261004,DBtesting/tmpfs/sinvolumencomercial:PostgreSQL18.6Alpine por tagflotante18-alpine, distinto delprincipal18.3. Migrate limpio0 con guard host+testing,5tablas IA/extension0.8.7/embeddingvector sinNfijo. Rollbackstep1exit0 elimina ai_product_embeddings yconserva extensión0.8.7 (target087_postrollback.json). No aplicado al comercial. SQL/performance130 queries anteriores siguen siendoPG18.3/0.8.2, no adjudicar a0.8.7.

Relevancia adicional misma categoría comercial, distintoproducto/SHA, galería130:Recall@1 .05314/@5 .16681/@10 .25551;Hit@1 .58462/@5 .87692/@10 .97692;Precision@10 .32923;MRR.70305;nDCG@10 .40206. Queries130, todosconpositivos;NumPy coseno exacto. Recall recupera fracción de todoslospositivoscategoría; Hit exige almenosuno. NO identidad exacta, mismo SKU ni gusto. No contradice NO EVALUABLE de identity retrieval sin juicios pareados. Filas/definiciones enretrieval_category_metrics.json yscript audit_scientific_cpu.py.

pgvector esextensiónPostgreSQL, no reemplaza tablas/FK/transacciones. Nearestneighbor busca vectores de menor distancia; exactscancon filtros, O(Nd) paracosenos(N130,d768), ranking posterior. No ANN/HNSW/IVFFlat; tiempos actuales nojustificanimponerlos. Modelo/revisión/pipeline/preprocess/geometry/dimensión/L2 forman espacio; cosenoalto oduplicateflag≥.98 no confirma producto ni derechos.
