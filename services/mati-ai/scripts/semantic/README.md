# Fundación semántica: reproducción controlada

Los documentos de diseño y resultados viven en `DOCUMENTACION/IA/RAG_TAXONOMIA/`. Taxonomía, corpus, evaluación, protocolo y perfil son JSON revisables en `services/mati-ai/resources/`. Los pesos y vectores de prueba no se versionan. Las evidencias de esta ejecución se guardan en `storage/app/ai-semantic/` privado; las pruebas PostgreSQL aisladas, en `storage/app/ai-rag/`.

## Entradas congeladas

- Protocolo `benchmark-protocol-v1.json`: SHA256 `f60608c413e19989cbdee9cb3391a1ad724b5ab9fd870c1c21e55f3f61068836`.
- Corpus: SHA256 `d1fd24f3a18e9c169c6197e6bf12da8a663359010ffc271c33918eb8893a3fe2`,23 documentos/23 chunks semánticos.
- Evaluación: SHA256 `c09509b42e33622e9613ba2f64207947d78b62260d355f63da3c951304b6bdb4`,48 positivas ES/EN y16 negativas (8 fuera de dominio,8 dinámicas).
- Preparación independiente del corpus: lock SHA256 `e779f2cea4aaebfdbab94997c8311778d039e3c2e3a051f2ed9642e5e741b29b`; `protocol_sha256:null` en ese lock no indica falta de freeze: se verifican los perfiles exactos al ejecutar el benchmark congelado.

Cada embedding documental usa `title + "\n" + section + "\n" + content` y el prefix del perfil. NFC+strip, mean pooling con attention mask, FP32 y L2; se conserva SHA del texto original. No lowercase adicional. E5 utiliza query/passage según modo; el runtime rechaza queries que exceden su límite nativo. El corpus actual no requiere truncación.

## Preparación e inferencia

La preparación es una acción explícita con red; las peticiones normales nunca descargan modelos. Sólo escribe modelos públicos fijados por commit y SHA en `/cache/text-embeddings`, separado de SigLIP/EasyOCR/segmentación. Directorios755/archivos644 sólo para este nuevo caché público; evidencias700/600. Requiere dependencias ya disponibles en el runtime mati-ai, no instala paquetes nuevos.

```bash
python services/mati-ai/scripts/semantic/prepare_text_models.py \
  --cache-dir /cache/text-embeddings --output-lock /output/text-model-lock.json \
  --prepare-explicit
python services/mati-ai/scripts/semantic/benchmark_embeddings.py \
  --protocol /workspace/services/mati-ai/resources/rag/benchmark-protocol-v1.json \
  --protocol-sha256 f60608c413e19989cbdee9cb3391a1ad724b5ab9fd870c1c21e55f3f61068836 \
  --model-lock /output/text-model-lock.json \
  --model-lock-sha256 e779f2cea4aaebfdbab94997c8311778d039e3c2e3a051f2ed9642e5e741b29b \
  --workspace /workspace --output-root /output --execute-explicit
```

Los paths del ejemplo pertenecen al contenedor controlado de esta ejecución. Preparar un nuevo lock cambia su SHA/fecha: comprobar su hash real, no sustituirlo con el histórico. Ejecutar inferencia con caché RO, workspace RO, output privado RW, sin red ni GPU, cuatro CPU/hilos y memoria4GiB. Cada candidato corre en un proceso nuevo secuencial; no comparar modelos simultáneos que compitan por recursos. No sobrescribir resultados accidentalmente (`--overwrite-explicit` requiere una decisión nueva y no cambia el protocolo).

## Backend e índice

El ganador del gate textual es E5-small384/MIT, revisión `614241f622f53c4eeff9890bdc4f31cfecc418b3`. El perfil incluye hashes de todos los assets, benchmark/protocolo, preprocessing y espacioTEXT. Un `approved:true` autoriza el modelo textual, no la recuperación RAG. `retrieval_approved:true` sólo puede aparecer tras un gate RAG aprobado; actualmente permanece falso hasta evidencia independiente.

Compose conserva un worker visual y añade `MATI_AI_TEXT_EMBEDDING_ENABLED` (true por defecto en este checkout). Puede deshabilitarse con esa variable sin tocar SigLIP. FastAPI crea un único childCPU lazy mediante spawn; no cambia los hilos/CUDA del padre. El endpoint interno `/api/v1/rag/embed` requiere el token ya utilizado por Laravel. El token nunca se pasa por CLI ni se imprime en estas instrucciones.

`php artisan mati-ai:rag-index` acepta únicamente el corpus curado fijo y la identidad textual aprobada. Antes de la primera ejecución hay que aplicar sólo `database/migrations/2026_10_05_000001_create_rag_tables.php` y verificar la conexión local esperada; no correr migraciones pendientes ajenas por conveniencia. No indexa productos ni modifica vectores visuales.

Los scripts de esta ejecución `index_rag_verified.php` y `evaluate_rag.php` están anclados a DBlaravel/hostpgsql y inputs congelados. El primero aplica sólo el schema nuevo mediante `--schema --execute-explicit` y luego prueba primer índice+repeat mediante `--index --execute-explicit`; rechaza evidencias preexistentes. No son un mecanismo de migración de producción genérico.

## Evaluación, bloqueo y preservación

```bash
php services/mati-ai/scripts/semantic/evaluate_rag.php --execute-explicit
services/mati-ai/.venv/bin/python services/mati-ai/scripts/semantic/analyze_rag_results.py \
  --workspace /home/soto/Code/more
```

`evaluate_rag.php` habilita el bypass de evaluación sólo en su contextoCLI explícito para medir el prototipo sin promoverlo al endpoint público. La búsqueda pública autenticada no acepta ese bypass como parámetro. `analyze_rag_results.py` computa métricas del corpus congelado, filtros/procedencia y exact top3 SQL contra NumPy. No ajusta floors ni cambia relevancia. El documento05 registra gate, positivos, negativos y camisa/blusa. Las respuestas RAG contienen excerpts y citas, sin vectores ni texto generado.

`probe_text_runtime.php --execute-explicit` comprueba auth401, textoUnicode200, límites422/413, determinismo/L2 y concurrencia acotada; no escribe runs. `probe_visual_preservation.php --before`/`--after` analiza sólo la misma imagen13 mediante FastAPI puro, conserva vectores privados para comparación y no usa el controlador persistente Laravel. El guard comercial histórico verifica fingerprints, originales134, vectores visuales134 y conteosAI antes/después.

Pruebas de esta fase: pytest del contrato humano, taxonomía y métricas, backend RAG/core/color; PHPUnit unit+HTTP RAG; tres tests de persistencia protegidos en contenedor PostgreSQL efímero aislado. Sus assertions sintéticas no son ground truth humano ni métricas de corpus real. No ejecutar git add/commit/push.
