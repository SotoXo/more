# Benchmark aislado de selección de prenda

Resultado de esta ejecución: **NONE**, sin promoción ni integración productiva. Los seis informes están en `DOCUMENTACION/IA/SEGMENTACION_SEMANTICA/`. Estos scripts no entrenan, no sirven endpoints, no consultan PostgreSQL y no reindexan `ai_product_embeddings`.

## Identidades congeladas

- HEAD/tag commit: `e422ef4c0a279172ca03818a4b16ffb501d493bd` / `ia-p1-stable-v1`; rama `feature/ia-segmentacion-semantica`.
- Protocolo: `5cdd1eba3ab99d862d039a70a93769f821d7c4ba8c89f8a6508025b6ce6ba0f4`.
- Manifest: `f24d4901949a70769df86f8b85fce7ee85d5ed5e0b90c1bb57e8b77c07c61c5e`;134registros→130SHA únicos/coherentes, regla auditada sin alterar labels.
- Lock preparado: `8f5fd343a93d9c09a2adbfb14a573f6f234005fe2cf933d8f2b8d82426ee8443`; cuatro checkpoints, revisiones y SHA256 de todos sus archivos.
- Imagen Docker: `sha256:4cb86e63d5752679f4d901554e4fd63f663f743f21ffc76061f571d4f08c229f`.
- RTX5060,8151MiB, driver591.86; Python3.11.17, Torch2.13.0+cu130, CUDA13.0, Transformers5.14.1. BF16autocast para detector/máscara; SigLIP F32 original.

`protocol.json` fija vocabulario, thresholds, whitelist, selección, muestraN130, representación de fondo gris127 y gate. `model-lock.json` vive fuera de Git en `storage/app/ai-segmentation/`. Los pesos sólo están en el volumen persistente `more_mati-ai-cache:/cache/segmentation`, UID1000/mode700. SigLIP usa `/cache/huggingface` existente; EasyOCR no se carga en el experimento.

## Scripts

| Archivo | Responsabilidad |
|---|---|
| `prepare_models.py` | Preparación explícita de cuatro checkpoints. Una preparación inicial resuelve revisiones una vez y las congela en lock; con lock existente verifica SHA de pesos y no lo sustituye. |
| `run_candidates.py` | Tres pares secuenciales: detector+segmentador, release/gc/empty_cache,130filas cada uno. Verifica protocolo/lock/archivos, corre offline y rechaza outputs preexistentes. |
| `policy.py` | Contrato y selector experimental conservador; múltiples opciones/clases desconocidas/invalidaciones bloquean AUTO. No autoriza IDs comerciales. |
| `metrics.py` | Clasificación/color/retrieval con denominadores explícitos; faltantes=`no_prediction`/métrica query0. |
| `evaluate_consumers.py` | Reproduce FULL, evalúa exclusivamente AUTOcrop/masked y persiste vectores experimentales; conserva las funciones y thresholds productivos. |
| `evaluate_diagnostic13.py` | Diagnóstico N1 separado, requiere sidecar visual independiente ligado a resultados/mask/SHA/contexto. No es agregado asistido ni humano. |
| `make_evidence.py` | Contact sheets originales/bbox/mask/crop/gris para20IDs previamente fijados. No altera originales. |
| `report_gate.py` | Reporte determinista de criterios cuantitativos congelados y NONE de esta ejecución; no despliega ni promociona automáticamente. |
| Tres `test_*.py` |66pruebas de política,8métricas,10fronteras del lector. |

## Repetición sin sobrescribir el experimento original

Comandos Bash/WSL desde `/home/soto/Code/more`. Son instrucciones de reproducción; esta fase ya se ejecutó. Se necesita el Dockerimage exacto, originales y artefactos privados P1: `storage/app/ai-p1/classification_predictions.json` y `storage/app/ai-audit/p1-color-baseline-comparison.json`. El manifest público por sí solo no contiene toda la evidencia privada.

Crear un directorio nuevo y copiar el lock y protocolo previamente congelados, **sin regenerar revisiones desde main**:

```bash
seg_output="$PWD/storage/app/ai-segmentation/reproduction-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -m700 "$seg_output"
cp storage/app/ai-segmentation/model-lock.json "$seg_output/"
cp storage/app/ai-segmentation/protocol-freeze.json "$seg_output/"
cp storage/app/ai-segmentation/visual-sample-freeze.json "$seg_output/"
chmod 600 "$seg_output"/*.json
seg_image=sha256:4cb86e63d5752679f4d901554e4fd63f663f743f21ffc76061f571d4f08c229f
seg_protocol_sha=5cdd1eba3ab99d862d039a70a93769f821d7c4ba8c89f8a6508025b6ce6ba0f4
seg_lock_sha=8f5fd343a93d9c09a2adbfb14a573f6f234005fe2cf933d8f2b8d82426ee8443
seg_container=(docker run --rm --user 1000:1000 --network none --shm-size 1g
  --mount "type=bind,src=$PWD,dst=/workspace,readonly"
  --mount "type=bind,src=$seg_output,dst=/workspace/storage/app/ai-segmentation"
  --mount type=volume,src=more_mati-ai-cache,dst=/cache,readonly
  -e PYTHONPATH=/workspace/services/mati-ai/src
  -e HF_HOME=/cache/huggingface -e HF_HUB_OFFLINE=1 -e TRANSFORMERS_OFFLINE=1
  -e HF_HUB_DISABLE_TELEMETRY=1 -e MATI_AI_LOCAL_FILES_ONLY=true
  --entrypoint /app/.venv/bin/python)
```

Comprobar el cache existente (sin GPU ni red; no modificar permisos globales). El flag explícito sigue siendo obligatorio aunque este caso sólo verifique pesos ya preparados:

```bash
"${seg_container[@]}" "$seg_image" /workspace/services/mati-ai/scripts/segmentation/prepare_models.py \
  --protocol /workspace/services/mati-ai/scripts/segmentation/protocol.json \
  --expected-protocol-sha256 "$seg_protocol_sha" --workspace-root /workspace \
  --output-dir /workspace/storage/app/ai-segmentation --cache-dir /cache/segmentation --prepare-explicit
```

Si falta un snapshot o cambió su hash, detener la reproducción: no sustituirlo por main. La preparación inicial de este trabajo fue explícita, con red sólo durante downloads, safetensors, `token=False` y `trust_remote_code=False`; su log conserva revisiones, bytes y hashes. Una nueva preparación sin lock puede resolver versiones diferentes y sería otro experimento.

Ejecutar **un** contenedor GPU para los tres pares; el script libera cada par antes del siguiente. No lanzar comandos paralelos de inferencia:

```bash
"${seg_container[@]}" --gpus all "$seg_image" /workspace/services/mati-ai/scripts/segmentation/run_candidates.py \
  --protocol /workspace/services/mati-ai/scripts/segmentation/protocol.json \
  --expected-protocol-sha256 "$seg_protocol_sha" \
  --model-lock /workspace/storage/app/ai-segmentation/model-lock.json \
  --expected-model-lock-sha256 "$seg_lock_sha" --workspace-root /workspace \
  --output-dir /workspace/storage/app/ai-segmentation --execute-explicit

"${seg_container[@]}" --gpus all "$seg_image" /workspace/services/mati-ai/scripts/segmentation/evaluate_consumers.py \
  --root /workspace --output /workspace/storage/app/ai-segmentation
```

El reader compara orden TopK y scores FULL con P1 (tolerancia1e-5). FULLcolor se reconstruye de observacionesP1 congeladas, no se reejecuta como medición física. Los NPZ de cada perfil768 quedan en `$seg_output`; jamás se envían a DB. Maskcolor usa los RGB originales dentro de máscara, no el fondo gris del inputSigLIP; MIN_FOREGROUND_CONFIDENCE=.70 es una entrada de compatibilidad fija y no accuracy de máscara.

## Evidencia, diagnóstico y decisión

`make_evidence.py --root /workspace` puede ejecutarse en el mismo contenedor sin GPU; genera60paneles con la muestra congelada. Las máscaras/crops se mantienen privadas. `--copy-to` es opcional para una carpeta de visualización local, no publicación pública.

Una repetición produce nuevos resultsSHA: no copiar `visual-review.json` ni sidecar13 del experimento original como si validaran esos nuevos resultados. Inspeccionar otra vez las60evidencias y registrar identidad/autoría de la revisión; nunca llamarla maskGT humana. El diagnóstico13 sólo se ejecuta después de una selección visual independiente nueva y compatible con sus hashes/contexto. Sus paths por defecto pertenecen a la carpeta privada montada y sus resultados nunca entran en ALL130.

Para **este experimento original ya revisado**, el reporte no necesita CUDA/modelos/DB:

```bash
python3 services/mati-ai/scripts/segmentation/report_gate.py --root "$PWD"
```

Este comando sólo reemite `gate-decision.json` con una fecha nueva (cambia su hash, no los criterios/resultados). Requiere hashes de protocolo/lock, resultados vinculados a revisión60, baseline y registro84pruebas. Si apareciera un cuantitativo aprobado en otro experimento, falla con `REQUIRES_INDEPENDENT_PROMOTION_REVIEW_NOT_AUTOMATIC_DEPLOYMENT`; no implementa un deploy ni desbloquea colores por sí mismo.

## Pruebas sin inferencia

```bash
PYTHONDONTWRITEBYTECODE=1 PYTHONPATH=services/mati-ai/src:services/mati-ai/scripts/segmentation \
  services/mati-ai/.venv/bin/python -m pytest -q -p no:cacheprovider \
  services/mati-ai/scripts/segmentation/test_policy.py \
  services/mati-ai/scripts/segmentation/test_metrics.py \
  services/mati-ai/scripts/segmentation/test_consumer_boundaries.py
services/mati-ai/.venv/bin/ruff check --no-cache --select F,E9 services/mati-ai/scripts/segmentation
```

Usa la venv de desarrollo **existente**, que contiene pytest/ruff. La imagen productiva congelada no contiene esas herramientas; los intentos finales por Docker devolvieron `No module named pytest/ruff`, sin ejecutar tests ni modificar la imagen. No se instalaron dependencias. El resultado válido proviene de la venv:84passed. No requiere GPU, carga de modelos, cuenta/APIexterna ni DB. No prueba un selector Laravel/UI productivo, dado que no hubo integración.

`source-finalization.json` conserva hashes antes/después de eliminar imports/variables sin uso para lint, con snapshots privados del código ejecutado. La reparación funcional anterior del lector está en `consumer-reader-repair.json`. El SHA del protocolo/gate, modelos, prompts y thresholds permanece congelado; no se reejecutaron inferencias para limpieza estática.

## Lectura correcta

- Rawboxes130/130 no equivale a máscaras válidas ni identidad comercial.
- AUTO0/6/0 es cobertura de esta política conservadora, no accuracy intrínseca de SAM o SigLIP.
- ALL130 mantiene faltantes; N6seleccionado es una cohorte sesgada, con galería6 para retrieval.
- nDCG/Recall/Hit son proxies de misma categoría, no identidad de producto ni gusto del cliente.
- Sin máscaras humanas no se reporta IoU/Dice/mAPaccuracy. IoU de cajas sólo sirve para deduplicación geométrica, no evaluación científica.
- Los scores individuales son no calibrados; tampoco segmentador mide color. La matemática de color vive en CIELAB/CIEDE2000, SigLIP representa/clasifica y pgvector conserva el espacio canónico.
- Artifacts de benchmark privados con retención para reproducibilidad; TTL y selección autenticada son diseño productivo condicional aún no implementado.
