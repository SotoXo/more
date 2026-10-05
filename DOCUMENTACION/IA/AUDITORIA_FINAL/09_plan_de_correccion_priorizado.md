# Plan de corrección priorizado — no ejecutado

La auditoría termina sin implementar este plan. Las prioridades proceden de08; primero disponibilidad/contratos, después calidad comercial. No entrenar ni cambiar modelo/prompts/thresholds como atajo para resolver integración.

| Orden | Hallazgos | Corrección propuesta | Criterio de verificación futura |
|---|---|---|---|
|1|AUD-01|Reemplazar regla inexistente por validación soportada deuna sola fuente imagen|Upload/IDválidos llegan a Python;vacío/ambas retornan422;analyze/barcode/label/similar browser no500|
|2|AUD-04/19|Desbloquearhelperrun;alinearfixtures con contrato actualsineliminarinvariantes;tipado/lint|Suite completa estándar Laravel ejecuta14workflow,pytest sin5 fallas,mypy/ruff;modelo real separado|
|3|AUD-02/03/25|Preparar servicio/token privado/imagenPGvector/revisiones/migraciones conplanvolumen yrollback|Misma versión destino comprobada;readinessauth;persistencia real yqueryindex;sin escribirproductos de auditoría|
|4|AUD-05/14/15/16|Abstención/sugerencias ligadas afoto/validaciónschemas/estados claros|Abstenidos distinguidos;fotoA→B/respuestatardía noaplicapropuestavieja;malformed no rompeUI|
|5|AUD-06/17|PrepararpesosOCR/versionesypolíticaconfidencia porentidad|Datasetfotográfico transcritoconCER/WER/F1 ylatenciainformados;confidence OCR separada detextmatching|
|6|AUD-07/18/20/26|Etiquetarobjetivode prenda/máscaras/calidad ycolorswatchadecuado; revisarlabelscontradictorios conhumanos|IoU contra máscaras;accuracy/ΔE conreferenciadecolor/monocolor;baseline congelado separado deexperimento|
|7|AUD-09/11/12/27|Activos/candidatosestables/combinaciónvariante/vigenciacontenido|Coverage+candidates correctos;rojoS+azulMno esrojoMsegúncontrato;reindex/invalidation trasbytescambiados|
|8|AUD-10/22|Idempotenciafeedback/eventos/persistenciaatómica/linajelog|Retry produce una decisión;formvsaved distinguido;parent/revision/pipeline sinpayloadssecretos|
|9|AUD-13/21/23/28|Guard externo completo,DNS acotado,cleanupoperado,límites coherentes|MocksDNSslow/redirect/profilemix pasan;TTL/locks/pathseguros;contratobytes/pixels enambascapas|
|10|AUD-24/25|Documentarestadoactual/readyUI,decidirconsumercompatibilidad segúnnecesidad|No prometeroutfit/recomendación/RAG;motor privado yfeatureconsumida distinguidos|

Para modelo: **seguir evaluando**. Preparar CLIP como experimento independiente, mismos130inputs/taxonomías/versiones/criterios ydatasetnuevoholdout; mediraccuracy/F1/coverage/latencia/VRAM antes de elegir. Mantenerestereportbaseline, sinretocarinputs ni excluirerrores después deverlos. No declarar ganador sin CLIP.

Para color: calibración física no surge deHEX comercial; medirreferencias/iluminación/máscara de objetivo, especialmente fotos depersonaentera,multicolor,blanco/crema,oscuro/croma,metálicos. Para retrieval: juiciosdeidentidad/relevancia comercialpareada, distinta de misma categoría. Para compatibilidad: jueces/preferencias/contextos, sólo si se desarrolla ese producto futuro.

No sugerirANN/RedisIA/NoSQL porcostumbre: exactscan actualN130ySQLp95≈4.8ms, servicios síncronos ysin necesidad medidadeesas capas. Ningún paso se aplica automáticamente tras este documento.
