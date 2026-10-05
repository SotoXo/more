# Incoherencias, riesgos y hallazgos

2026-10-04. Cada hallazgo distingue ejecución, inspección y riesgo. Correcciones propuestas, **no implementadas**. No se confirmó unP0 de cálculo determinista, escritura comercial errónea o vulnerabilidad crítica. Errores predictivos reales están cuantificados; no se guardaron como valores comerciales. P1 identifica módulos clave/contratos rotos; P2 límites recuperables/semánticos; P3 mantenimiento/documentación. No se asigna gravedad crítica sólo por una hipótesis.

## Diez hallazgos prioritarios

### AUD-01 — P1 — Validador Laravel inexistente bloquea cinco operaciones UI

Módulo: integración. Archivo app/Http/Controllers/Superadmin/AiController.php:48–49,75–76,287–288. Reglas prohibited_with invocan validateProhibitedWith inexistente enLaravel13.1.1, sin extensión registrada. Reproducir imagen upload o existing_image_id enanalyze/barcode/label/similar. Evidencia: método false en principal y5HTTP500 deUIaislada, laravel_image_http_blocker.json/validator_prohibited_with_probe.json/browser_e2e_summary.json. Impacto: análisis,color,códigos,OCR,similares no llegan a Python. Propuesta: validar exclusión deinputs con regla realmente soportada y pruebas deambasformas/vacío/ambas; después repetir browser. No corregido.

### AUD-02 — P1 — IA habilitada pero conexión productiva no preparada

Módulo: despliegue. Archivos config/mati_ai.php:4–8,compose.yaml:28–39,.env (presencia, sin valores secretos). Token efectivo vacío, servicio principal FastAPI ausente. Reproducir leer configuración/capabilities a través declient; fallback SERVICE_NOT_CONFIGURED antes deHTTP. Evidencia: principal_laravel_ai_configuration_status.json/_database_audit.json/dockerinventory. Impacto: no inferencia remota desde aplicación principal aunque el modelo funcione aislado. Propuesta: preparar servicio,token privado yreadycheck con configuración revisable en tarea separada. No editar .env durante auditoría.

### AUD-03 — P1 — Persistencia y pgvector comerciales no operativos

Módulo: datos/despliegue. Migraciones2026_09_28_000001_create_catalog_ai_tables.php:10–60 y2026_10_03_000001_create_ai_product_embeddings_table.php:17–49;compose.yaml:50–65. Dos migraciones pendientes,sin5tablas IA,modelo_pre ni extensión vector disponible enDBprincipal. SELECT read-only lo reproduce. Evidencia _database_audit.json. Impacto: feedback/auditoría/indexado/búsqueda comercial no puedenpersistir. Propuesta: revisarbackup/compatibilidadvolumen ydesplegar imagen/migraciones después; imagen0.8.7ya fue probada exclusivamente enDBaislada18.6. No aplicar aproducción aquí.

### AUD-04 — P1 — Suite estándar Laravel aborta antes de ejecutar

Módulo: validación. tests/Feature/MatiAiWorkflowTest.php:94,private function run() sobrescribe PHPUnit12 finalTestCase::run. Reproducir suite estándar o filtroMatiAi. Evidencia laravel_full_baseline.log/laravel_ai_tests.log,fatal255;14workflow no ejecutados. Impacto:CI completo inválido, no certificación integración. Propuesta: renombrar helper/referencias, luego fullsuite sinexclusión. Los95 tests excluyendo únicamenteese archivo pasan, no reparan el baseline.

### AUD-05 — P1 — Abstención de categoría no restringe propuestas aplicables

Módulo: contrato/revisión. AiReviewService.php:54–57 yGarmentIntelligentAssistant.tsx:201–207. Python clasificaciónabstained true conserva top3; Laravel genera sugerencias sin examinarlo. Reproducir una delas117fotosabstenidas a través depersistencia/suggestions; análisis de código confirma la rama. Evidencia classification_predictions.json,classification_metrics.json yfuente. Impacto:coverage 10% formal pero130/130 rankings generan opciones; umbral no implementa misma política en todas lascapas. Propuesta: conservar ranking exploratorio separado de propuesta aplicable,mostrarabstención ydefinir revisión explícita. No afirma que una propuesta se guardó mal en comercio; UIbloqueada porAUD-01 impide reproducir aplicación real completa.

### AUD-06 — P1 — OCR no preparado pese estar enabled

Módulo: identificación. services/mati-ai/src/mati_ai/identification/ocr.py (reader/carga,~25–90),core/config.py:24–27. EasyOCR CPU necesita pesos detector/latin ausentes; downloadsfalse. Reproducir3labels sintéticas con provider real. Evidencia python-synthetic-audit.json/ocr_cpu:3OCR_UNAVAILABLE,0 texto; endpoint label partial. Impacto: OCRno funciona,CER/WER/F1no evaluables. Propuesta: preparar pesos controlados/versionados ydataset transcrito antes de calidad. Parser3/3noacreditaOCR. No descargar/cambiar para ocultarlo.

### AUD-07 — P1 — Foreground de objeto no identifica la prenda catalogada

Módulo: color/visión. vision/segmentation.py (componente/bordes,~70–230),vision/color.py:262–337. Imagen13JERSEYALPACAverde conpantalónnegro/bolsa retorna negro carbon; imagen1tambiénpersona entera. Reproducir extract_colors sobre originales del manifest. Evidencia python-color-real.json/diagnostic_images.png; primaryID18/35aciertosproxy global. Impacto: color comercial sugerido puede corresponder aotra prenda en la foto aun foreground confidence.94. Propuesta: objetivo garment/recorte validado yreferencia de máscaras, antes de nuevos thresholds. No IoU ni causalidad pixelporpixel sin máscara humana. No se corrigió label/foto ni se guardó resultadoequivocado.

### AUD-08 — P1 inicial, resuelto por build autorizado — Assets servidos obsoletos

Módulo: frontend/despliegue. public/build/manifest.json,resources/views/app.blade.php:17. Baseline chunk ProductCreate-Br6F6_ny.js(26/09)no contiene asistente actual. Reproducir comparar entrada/hashes/cadenas. Evidencia frontend_prebuild*.json. Impacto inicial: fuenteimplementada no disponible en browser. Build normal solicitado por usuario pasó dentro de contenedor yactualizóassetsignorados; ProductCreate-tYfO0olu.js HTTP=disco ybrowser. Propuesta: control de coherenciaartefacto/source en despliegue. No fuenteproductivamodificada; no reportar como aún desfasado ni ocultar efectos del build.

### AUD-09 — P2 — Matching no respeta activos ni cubre todos candidatos blandos

Módulo: catálogo. CatalogAiService.php:35–60. Matching softlimit100 sin orden estable, nofiltroest_pre/est_prv. Reproducir catálogoaislado134 ySKUdevariantetemporal inactiva bajo rollback. Evidencia laravel_matching_isolated_probe.json:100/134=74.63%,inactive exact devuelto. Impacto: candidato34fuera de conjunto no rankeado,archivado puede proponerse; elcaminoexactosebuscaantesdelsoftlimit pero también tiene su límite. Propuesta: política explícita activos/archivados,orden estable/selección candidatos informada ycoverage. No medir recall semántico de34no enviados.

### AUD-10 — P2 — Replay de feedback duplica decisiones humanas

Módulo: feedback. AiReviewService.php:162–174;Assistant:225–240; migraciónIA:48–58. Repetir mismo POST con misma sugerencia/acción/valor/variante crea2formreviews. Evidencia feedback_idempotence_probe.json,transacción aislada rollback; predicción original intacta. Impacto: retrytrasrespuestaperdida inflaacceptance/correctionrates. uniqueform_review_iddeduplicasavedporreview,no la decisión form. Propuesta: idempotencykeypor evento ytasasporúltimadecisión válida distinguiendoform/saved. NohistoriarealIAdisponible para calcular aceptación.

## Hallazgos adicionales

### AUD-11 — P2 reproducido — Color y talla combinados en variantes distintas

CatalogEmbeddingStore.php:254–259 usa EXISTS independientes. Producto rojo/S+azul/M satisfacefiltrosrojo/M sinvarianterojo/M. Reproducción sintéticaaislada rollback enpgvector_negative_explain_audit.json,check true. Impacto: contrato variante específica no satisfecho, aunqueUIactual no expone filtros. Propuesta: un EXISTS común si ambos filtros debenreferir ala misma variante; definir contrato antes. No secambióconsulta.

### AUD-12 — P2 riesgo — Vector viejo si cambian bytes en la misma ruta

CatalogEmbeddingStore.php:202–209 validaimageID/propietario/path,no SHA actual por query. Reproducir inspección; cambio depath sí excluye fila probadoaislado, reescriturabytes mismo path no se hizo en foto real. Evidencia fuente/pgvector_negative_explain_audit.json. Impacto: storage externo podría dejarvectorviejo; flujo normal usa nuevo path. Propuesta: objetosinmutables/invalidaciónporcontenido ytestaislado. No presentarlo como vectorcomercialrealstale encontrado (tablasfaltan).

### AUD-13 — P2 reproducido con mocks — Guard externo de embedding incompleto

enrichment/ranking.py:68–81 compara model_id/model_revision/longitud, acepta main yno perfilpipeline/preprocess/L2/geometry completo. Reproducir dos fixtures main con misma longitud; cosine1aceptado. Evidencia python-synthetic-audit.json/external_embedding_version_guard_gap. Impacto: mezcla potencialdeespacios; interno/pgvector son másestrictos. Propuesta: reutilizaridentidadcompleta eSHAinmutable. Fuentesrealesinactivas; no afirmar contaminación actual real externa.

### AUD-14 — P2 inspección — Sugerencias históricas sobreviven cambio de foto

Assistant.tsx:190,201–207,285 reseteaanalysisRun,noremove results;topSuggestions no filtraanalysisImageKey,response tardía se agrega. Reproducir futurofixtureUI A→B antes de respuesta; fuente demuestra selección distinta delalastColorimetry. Evidencia _frontend_inspeccion.md. Impacto: propuestavieja aplicable a nueva imagen. Propuesta: vincularvisibilidad/aplicación/abort aidentidadde imagen. Browserrealno dejó llegar aanalyze porAUD-01: riesgo estático, no observación UI completa.

### AUD-15 — P2 inspección — Guards de respuesta anidada incompletos

Assistant:281–285 valida dataobject/statusstring, no arrays/nested/request_id/contract; MatiAiClient:83–110 sólo envelope yparte delshape para algunasoperaciones. Respuesta suggestions object puede romperflatMap/render; no se inyectó en browser comercial. Evidencia fuente yclientmocks deINVALID_RESPONSE. Impacto: errores deUIantecontrato parcial. Propuesta: validación deesquemaenlímites yharnessmalformed; no afirmar fallo observado depágina actual.

### AUD-16 — P2 — Estados y scores simplificados

Assistant:78,303,450;AiReviewService:43–56. degraded/insufficient_evidence se etiquetan parcial; similarity_score se guarda confidence_score con otros scores sin score_kind de primer nivel. Browseroutage mostróHTTP200completed local conwarningremoteoffline. Impacto: exportaciones/coberturapuedeninterpretarse como confidenciaprobabilística o éxitoremoto. Propuesta: estado explícito/evidencia,score_kind/rawscore/semánticaporfuente; UIactual ya dicepuntuación yadviertecoseno,no acusarprobabilidad falsa presentada. Fuente+muestraUI, no accuracy automática.

### AUD-17 — P2 reproducido — Confianza de marca no incorpora OCR

identification/normalization.py:111–130 devuelve score textual exacto1 al leerZARA; región OCR confidence.01 no es input. Control mock en python-synthetic-audit.json, no OCR útilreal. Impacto: consumidor puede sobreinterpretar confianza entidad. Propuesta: distinguirtextmatch/OCRconfidence/certeza comercial, no multiplicar o calibrar sin referencia. No cambiar fórmula ahora.

### AUD-18 — P2 datos — Duplicado con etiquetas contradictorias y taxonomías cercanas

Manifest[2,5] tiene igual SHA y categoríasPANTALONES/POLERA,coloresBEIGE/CAMEL. Reproducir agruparSELECT/hashesread-only. Evidencia evaluation_manifest.json/classification_metrics.json. Se excluyó grupo completo, no mejorarscores cambiandocatálogo. Colores56/55activos/1inactivoROJO VINOid18, todos HEX presentes; CREMAfam_colBLANCO contraexcepciónfamilybeige_cream encolor.py~150. NombresNEGRO/NEGROCARBON,CELESTE/AZULCIELO,MARRÓN/CAMEL son cercanos perono duplicados exactos automáticamente. Impacto: benchmark/propuestas ambiguas yfamilia inconsistente. Propuesta: revisión humana deidentidad/taxonomía después, preservarbaseline. AliasesPOLERA/POLOS/ABRIGOS yACCESORIOSsin muestras limitaninterpretación.

### AUD-19 — P2 — Baseline Python no está verde y tipado incompleto

TestsFakeModel/fixturescontrato,vision/color.py:212,365,487–495;compatibility/engine.py:141,166,236,437,445;enrichment/safe_fetch.py:92. Reproducir pytest sinmodelintegration,mypy/ruff. Evidencia103pass/5fail/1deselected,mypy21errors/7files,ruff8. Impacto: validaciónautomatizada no acompaña contrato vigente, no prueba que cada error sea un fallo runtime. Propuesta: alinearfixtures/invariantesytipar, manteniendo pruebas con modelo real separadas. Scriptsaudit9mockspass noparchearon original.

### AUD-20 — P2 límite científico — Umbrales no calibrados y referencia incompleta

vision/classification.py:58–60,color.py:262–337,compatibility/rules.py. Métricas130reales:coverage 10%,primaryIDcoverage26.92%/accuracyproxy51.43%; no máscaras/transcripciones/jueces. EvidenciaJSON/reportes03/04/02. Impacto: no defenderprobabilidades, color físico o gusto aprendidos; métricas de casos cubiertospuedenocultarabstención. Propuesta: dataset independiente yreferenciasadecuadas antes decualquier experimento deajuste. Un resultado negativo válido no esrazón paratunearduranteauditoría.

### AUD-21 — P2 riesgo — DNS síncrono fuera de timeout duro

enrichment/safe_fetch.py:67–76,175–187 llama socket.getaddrinfo síncrono; luego limita HTTP por deadline. Inspección del código, noDNSlento real inducido. Impacto: resoluciónDNSpuedebloqueareventloop ypresupuesto de tiempo remoto. Propuesta: resolver acotado/asíncrono yharnesslento sin red externa. Controles SSRF actualespasaron,mantenerallowlist/publicIP/pinning; no afirmarvulnerabilidadexplotada.

### AUD-22 — P3 — Correlación y persistencia parcial de auditoría

MatiAiClient.php:70–75, AiController.php:187,395–418. Log carece revision/pipeline; matching genera nuevoIDsinparent. Run/suggestions no tienen transacción conjunta ypuedenquedarparciales si falla segundo paso. Fuente yHTTPrequestheaders evidencian correlaciónindividual, no falla deDBreal. Impacto: diagnóstico/linaje completo limitado. Propuesta: parent_id/identidadlogsinpayload ypersistenciaatómica/estado explícito. No llamarhuérfano a run sin prenda antes de guardar.

### AUD-23 — P3 operación — TTL no implica limpieza programada

CleanupMatiAiPrepared.php:28–46,routes/console.php. Token expira24h ybloquea acceso; command cleanup no tiene scheduler visible. Inspección, no se borró ningún archivo. Impacto: temporalespuedenquedarocupando storage si nadie ejecuta command. Propuesta: operación/scheduler documentado con lock ypathsUUID verificables. No ejecutar limpieza de otras sesionesenesta auditoría.

### AUD-24 — P3 alcance — Endpoints/configuración sin consumidor operativo

GarmentCompatibilityService.php:19–32,routes/web.php:167–183,Assistant:339–379; external-sources.example.json. Compatibilidad tiene motor/FastAPI/wrapper pero sinroute/UI;capabilities no consumidor React; adaptersmanual/HTML existen pero0 fuentes activas. No es código muerto demostrado sólo por carecer de UI. Impacto: documentos pueden parecerfeatureE2Ecompleta. Propuesta: declarar estado PARCIAL yplanificarconsumidorexterno después, sin agregaroutfitsahora. RedisIA/NoSQL/RAG/generación/VTO ausentes correctamente reportados.

### AUD-25 — P3 — Documentación histórica superada e imágenes flotantes

DOCUMENTACION/IA/03_modulos_modelos_y_algoritmos.md:225(max2000),04_contratos_api_y_datos.md:207,590–651,725(tablas propuestas),docsV2(contrato1.0/outfits),database/pgsql/Dockerfile:2,12(postgres18-alpine). Código usa max16000,ai_product_embeddings/ai_runs/contrato1; esosdocs son diseño histórico,no implementación actual. BuildnuevoPG18.6 vsprincipal18.3;UIisoladaPHP8.5.9 vs8.5.5, registrados. Impacto: reproducibilidad/defensa yvalidaciónentreentornos. Propuesta: documentar estado actual sinreescribirhistoria,fijaridentidadimage/revisión en despliegue. No desplegarpara igualar entorno durante audit.

### AUD-26 — P2 límite — Heurística de calidad altera mensajes sin demostrar defecto

vision/quality.py:funciónassess_quality;imagen1highlightfraction≈.803 por fondo blanco ywarningOVEREXPOSED_OR_WHITE_BACKGROUND. Es evidencia real, no sobreexposición física comprobada. Laplacian<50 también responde auniformidad. Impacto: mensaje puedeinterpretrarse como foto defectuosacuando essimplemente producto aislado. Propuesta: vocabulario dealerta técnica condicional yvalidación con etiquetas de calidad; noajustarthresholdsahora.

### AUD-27 — P2 límite — Duplicados y matching acotados por ventanas

CatalogAiService.php:132 limita SHA checks aprimeras200 imágenes sin orden;catálogoactual134cabe, asíno seobservó omisiónactual. Exactcandidates tambiénlimit100; soft100actual demostrócoverage74.63%. Impacto futuro: duplicados/candidatos fuera deventana no detectados. Propuesta: consultaSHA/indexexistente yselección por lotes/criterios, no introducirANN porvolumen130. No extrapolar omisión real SHA deeste catálogo.

### AUD-28 — P2 límite — Tamaño admitido distinto entre capas

CatalogImageReader.php:11–25(config6MiB/40MP),core/config.py:15–20(default10MiB/24MP). Reproducir inspección ycontrolesHTTPsobrelímites; sin foto real24–40MP, no estimarfrecuencia. Impacto: entrada admitida porunacapa puede serrechazada por otra. Propuesta: contratoúnicodebytes/píxeles yfotosboundary para ambas. Dataset actual válido, no se cambiaron límites.

## Discrepancias documentación/código y complejidad

Documentación inicialcontratos/tablas/outfits ylímites es diseño superado/futuro, nobugpor sí sola. Implementaciones posteriores color/pgvector/compatibilidad describen fuente, no certificaciónruntime. Nueva auditoría01/03/05 agrega ejecución queantesfaltaba; build0.8.7ya probadoaislado, no mantenerconclusión histórica de«no probado» como estado final.

No HNSW/IVFFlat actuales; con130 vectores/4.8msp95 exacto, nofaltanteautomático. No agregarRedisIA/NoSQL/RAG/generación por sugerencias de diseño. Arquitecturaexternaladapters/espaciosversionados tienebeneficios de control pero operación actual no justificófuentesmúltiples; habilitar sólo connecesidad/terms, no borrarcomplejidad enesta auditoría. Helpers/módulos diagnóstico no sonmuertos sóloporqueUI no llama.

Sin secretos logs: se inspeccionóomisiónpayload/OCR/token, no se ejecutó pentest exhaustivo ni secertifica todaslasrutasdesconocidas. Derechosunknownsepermite referencia,no publicación;prohibited bloqueado. No incidentereal deprivacidad encontrado. Loshallazgosstáticos yreferenciascientíficas débilesseidentifican como tales, separadas de runtime.
