# RAG answerability V2

El protocolo versionado `protocol.json` se congeló antes de extraer datos. Dataset, splits, features, modelos candidatos, métricas, decisiones y hashes viven en `storage/app/ai-rag-gate-v2/` con permisos privados y exclusión Git. Las preguntas y etiquetas del benchmark no se versionan aquí. No se utiliza la evaluación V1 para tuning.

Los comandos de TRAIN/CALIBRATION leen únicamente el archivo separado de desarrollo. Un holdout sólo puede ejecutarse tras sellar código, representantes y modelos, con autorización explícita del coordinador del experimento; el marcador exclusivo de inicio permanece incluso ante fallo. No eliminarlo ni reintentar V2. Los probes de transporte, preservación y la suite crítica independiente no pertenecen al dataset de entrenamiento.

`probe_preservation.php before|after` usa SELECT en transacción de sólo lectura; verifica fingerprints comerciales, vectores visuales, imágenes e índice RAG frente a las evidencias selladas. `probe_http_embedding.php` prueba HTTP real de autenticación, payload, identidad del perfil y determinismo sin almacenar vectores ni secretos. Ambos rechazan sobrescribir evidencia existente.

El resultado aprobado o no aprobado y las instrucciones de reproducción están en DOCUMENTACION/IA/RAG_ABSTENTION_V2/. No hay generación, tools, reindexación, cambio de embeddings, entrenamiento online ni acciones comerciales.
