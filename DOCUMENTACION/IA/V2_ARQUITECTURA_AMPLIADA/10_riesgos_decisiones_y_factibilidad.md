# Riesgos, decisiones y factibilidad

**Estado:** registro inicial V2  
**Fecha de evaluación:** 2026-08-03  
**Nota:** las conclusiones de licencia/privacidad son filtros técnicos de diseño, no asesoría jurídica; la aprobación final corresponde a responsables competentes.

## 1. Escala

- **Factibilidad:** `ahora`, `con_datos`, `costoso`, `experimental`, `no_recomendable`, `api_externa`, `licencia`, `hardware`, `legal_privacidad`.
- **Probabilidad/impacto:** baja, media, alta, crítica.
- **Compuerta:** condición que debe cumplirse antes de implementación o activación.

## 2. Cinco riesgos mayores

| # | Riesgo | Probabilidad | Impacto | Por qué es crítico | Mitigación/compuerta | Contingencia |
|---:|---|---|---|---|---|---|
| 1 | resultados convincentes con datos insuficientes | alta | crítica | 11 ventas/7 días pueden producir forecast, asociación o personalización espurios | baselines, `insufficient_history`, backtesting temporal, mínimos de soporte y shadow | permanecer en SQL/reglas y recopilar datos |
| 2 | uso comercial sin derechos/licencia | alta | crítica | imágenes web y varios VTO son no comerciales; código público no equivale a permiso | registry de licencia, rights status, evidencia, revisión legal; nunca auto-publicar | bloquear fuente/modelo/activo y usar contenido propio/proveedor |
| 3 | exposición de fotos/medidas de clientes | media-alta | crítica | VTO y sizing tratan datos personales de alto impacto reputacional | consentimiento, minimización, storage privado, worker aislado, TTL/borrado, no reconocimiento/entrenamiento | desactivar VTO/perfiles; borrar y responder incidente |
| 4 | dependencia de APIs restringidas/inestables | alta | alta | Pinterest es restringido, Google Trends alpha e Instagram limita a cuenta/permisos | adaptadores, capabilities, cache permitido, importación manual y score con cobertura | operar solo con señales internas/proveedor |
| 5 | agotamiento de GPU/latencia en 8 GB | alta | alta | VTO y modelos visuales no caben necesariamente juntos; claims no medidos | workers exclusivos, concurrencia 1, benchmark, OOM breaker, perfiles y descarga | reducir alcance/resolución, GPU mayor o API aprobada |

## 3. Matriz ampliada de riesgos

| ID | Riesgo | P/I | Detección | Prevención | Owner/compuerta |
|---|---|---|---|---|---|
| R01 | taxonomía inconsistente (`POLERA` fuera de lista canónica) | alta/alta | valores no mapeados, drift | vocabulario versionado, `other/unknown`, revisión | catálogo; IA-0 |
| R02 | etiqueta visual derivada de composición no observable | media/alta | conflicto OCR/proveedor | distinguir observable/confirmado | catálogo/modelos; IA-0/4 |
| R03 | modelo zero-shot sesgado por prompts/idioma | alta/media | métricas por clase/prompt | CLIP baseline, prompts evaluados, abstención | ML; IA-4 |
| R04 | fuga train/test por fotos del mismo producto | alta/alta | hashes/IDs cruzados | split por producto/sesión y dedupe | ML; IA-21 |
| R05 | deep learning con dataset pequeño | alta/alta | train alto/test bajo | no entrenar hasta compuerta; transfer learning + baseline | ML/negocio; IA-21 |
| R06 | detector general no reconoce taxonomía fashion | alta/media | recall local bajo | protocolo de captura; anotar dataset antes de detector | visión; fase posterior |
| R07 | licencia YOLO incompatible con producto propietario | media-alta/alta | auditoría licencia | evaluar Apache/permisiva o Enterprise | legal/técnico; antes de dependencia |
| R08 | DeepFashion usado comercialmente contra términos | media/crítica | dataset lineage | solo referencia/investigación; no entrenar comercialmente | legal/ML; bloqueo |
| R09 | código válido asociado a producto incorrecto | media/alta | conflicto marca/modelo/variante | checksum + búsqueda interna + evidencia + humano | catálogo; IA-6 |
| R10 | `cob_prv` mezcla GTIN/proveedor/modelo | alta/alta | colisiones/ambigüedad | identificadores tipados y procedencia | arquitectura datos; antes de IA-6 fuerte |
| R11 | OCR crea entidad o composición errónea | alta/alta | tasa de corrección/conflictos | candidatos, catálogo cerrado y revisión | catálogo; IA-6 |
| R12 | SSRF por URL externa | media/crítica | egress/logs de bloqueo | allowlist, IP/reroute validation, size/time/type | seguridad; IA-9 |
| R13 | parser externo cambia silenciosamente | alta/alta | fixtures, campos nulos/drift | adapter version, canary, fail closed | integración; IA-9/14 |
| R14 | scraping viola términos/robots o causa bloqueo | alta/crítica | revisión/tasa 403/429 | permiso específico, RFC 9309, rate y no evasión | legal/integración; antes de adapter |
| R15 | publicación de imagen sin derechos | media/crítica | rights unknown/missing evidence | estado de derechos bloqueante y doble aprobación | contenido/legal; IA-8/9 |
| R16 | pipeline generativo altera color/diseño | alta/alta | comparación lado a lado/QA | derivados deterministas iniciales; flag fidelidad | catálogo; IA-8 |
| R17 | EXIF filtra ubicación/dispositivo | media/alta | scan de derivados | eliminar EXIF y separar original privado | seguridad; IA-3/8 |
| R18 | Instagram scraping no autorizado | alta/crítica | ausencia de permiso escrito | no crear adapter público; usar API propia | legal; bloqueo |
| R19 | métricas sociales comparadas como volumen absoluto | alta/alta | unit/scale audit | conservar escala y no mezclar unidades | trends; IA-14 |
| R20 | Google Trends/Pinterest no disponibles | alta/media | capability/access status | manual adapter y señal opcional | producto; IA-14 |
| R21 | tendencia social convertida en compra | media/crítica | decision lineage | score evidencia + revisión + lote piloto | compras; IA-15 |
| R22 | stockout interpretado como demanda cero | alta/alta | días stock=0 | availability features/censura/advertencia | datos; IA-10/12 |
| R23 | reserva/venta duplicada en demanda | media/alta | reconciliación | diccionario de hechos y estados | datos; IA-10 |
| R24 | fuga temporal en forecast/features | alta/crítica | data cutoff tests | snapshots as-of y rolling backtest | datos/ML; IA-12 |
| R25 | forecast profundo sobreajustado | alta/alta | peor que naive fuera muestra | baseline obligatorio; no deep ahora | ML; IA-12 |
| R26 | intervalos no calibrados se presentan como certeza | media/alta | coverage por horizonte | cuantiles/cobertura/ancho + UI | ML/producto; IA-12 |
| R27 | EOQ/ROP con costos o lead time inventados | alta/crítica | missing flags | abstener/incomplete; inputs maestros | compras; IA-13 |
| R28 | sugerencia ignora tránsito/mínimos/múltiplos | alta/alta | constraint validation | posición y restricciones explícitas | compras; IA-13 |
| R29 | patrón de asociación por dos cestas | alta/alta | support absoluto/estabilidad | regla curada hasta volumen suficiente | analítica; IA-11/17 |
| R30 | bucle de popularidad del recomendador | media/alta | concentración/cobertura | diversidad, exploración y logging impresión | producto/ML; IA-16 |
| R31 | «ignorado» sin impresión visible | alta/alta | event audit | definición de exposición/posición | datos; IA-10 |
| R32 | explicación afirma causalidad falsa | media/alta | reason-feature audit | reason codes derivados de features | producto; IA-16–18 |
| R33 | KNN confunde similitud con complemento | alta/media | revisión de outfits | matriz de roles + ranking híbrido | recomendaciones; IA-17/18 |
| R34 | perfil personal usa datos no consentidos | media/crítica | lineage/consent | minimización, propósito y opt-out | privacidad; IA-10/16 |
| R35 | VTO académico NC usado en producción | alta/crítica | registry/licencia | bloqueo de Cat/IDM/Stable/OOT | legal/ML; IA-19 |
| R36 | licencia permisiva de VTO no cubre auxiliares | media/crítica | SBOM/model lineage | auditar cada peso/dependencia/dataset | legal/ML; IA-19 |
| R37 | VTO cambia cuerpo/rostro o sexualiza | media/crítica | QA/reporte/filtros | input/output policy, categorías, piloto adulto | seguridad/producto; IA-19 |
| R38 | VTO se interpreta como garantía de talla | alta/alta | copy/usability test | separación UI y advertencia persistente | producto; IA-19 |
| R39 | temporales VTO quedan en disco/cache/backups | media/crítica | deletion audit/scanner | lifecycle, clean startup, TTL en todos los tiers | seguridad; IA-19 |
| R40 | inferir talla desde foto | alta/alta | diseño/claims review | prohibir; tabla+medidas+abstención | sizing/producto; IA-20 |
| R41 | unidades/medidas corporales inconsistentes | media/alta | constraints/outlier | esquema/unidades/método/version | datos; IA-20 |
| R42 | RAG filtra documentos o ejecuta acciones | media/crítica | red-team/tool logs | ACL retrieval, read-only tools, no mutación | seguridad; IA-22 |
| R43 | RAG responde con datos obsoletos/alucina | alta/alta | citation/groundedness eval | corpus versionado, citas y abstención | producto; IA-22 |
| R44 | proliferación de tablas/servicios antes de valor | alta/media | architecture review | crear por fase/necessity test | arquitectura; todas |
| R45 | cambios IA bloquean flujo manual | media/crítica | chaos/feature flag test | circuit breaker, timeout y fallback | arquitectura; IA-2+ |

## 4. Factibilidad por requerimiento

| Requerimiento | Clasificación | Dictamen |
|---|---|---|
| MIME/dimensiones/orientación/EXIF/hash | `ahora` | determinista y de alto valor |
| blur/exposición/contraste/fondo | `ahora` | calibrar umbrales con datos locales |
| color dominante/secundario | `ahora` | advertir contaminación de fondo |
| patrón/silueta/atributos | `ahora` como candidato | taxonomía cerrada y humano; material no inferido como verdad |
| categoría zero-shot | `ahora` | SigLIP candidato, CLIP baseline, top-3/abstención |
| detector/segmentador | `con_datos`, `costoso` | no MVP1; protocolo de captura primero |
| identificación barcode/QR/SKU | `ahora` | OpenCV/checksum/búsqueda interna |
| GTIN externo | `api_externa` | GS1/proveedor, cobertura/límites; identifica, no ficha completa |
| OCR etiqueta | `ahora` como asistencia | Tesseract baseline; no alta automática |
| identificación exacta por foto genérica | `no_recomendable` como promesa | solo candidatos sin referencia autorizada |
| búsqueda similar interna | `ahora` | exacta; índice ANN innecesario con 13 prendas |
| búsqueda web genérica | `api_externa`, `legal_privacidad` | fuente/rights; Custom Search cerrado a nuevos clientes |
| feed proveedor | `con_datos`, `api_externa` | priorizar; hoy proveedor ausente |
| scraping marca/proveedor | `legal_privacidad`, `costoso` | específico y permitido, no masivo |
| scraping Instagram | `no_recomendable`, `legal_privacidad` | términos Meta requieren permiso escrito; no diseñar |
| imágenes de publicación deterministas | `ahora` | original inmutable y perfiles versionados |
| super-resolution/inpainting/publicidad | `experimental`, `legal_privacidad` | fidelidad y rights; no ficha automática |
| Instagram propia Insights | `api_externa` | cuenta profesional/permisos/retención |
| Pinterest Trends | `api_externa` | restringida/Enterprise/partners; fallback manual |
| Google Trends API | `api_externa`, `experimental` | alpha limitada; nunca dependencia crítica |
| analítica descriptiva | `ahora` | definir semántica/calidad primero |
| RFM/clustering | `con_datos` | RFM por reglas antes; clientes insuficientes |
| asociación/FP-Growth | `con_datos` | 11 ventas no bastan |
| anomalías ML | `con_datos` | reglas primero |
| forecast agregado | `con_datos` | hoy solo baseline/abstención; requiere historia/cortes |
| forecast por SKU/talla/color | `no_recomendable` ahora | demanda extremadamente intermitente y corta |
| LSTM/GRU/TFT | `no_recomendable` ahora, `costoso` futuro | no hay escala; comparar siempre con naive |
| reposición | `con_datos` | faltan proveedor/lead time/costos/tránsito |
| EOQ | `con_datos` | no calcular sin supuestos/costos reales |
| surtido | `con_datos`, `experimental` | reglas/evidencia/lote piloto |
| popularidad/novedad | `ahora` | fallback acotado por stock/ventana |
| similar/content-based | `ahora` | requiere atributos/embedding confirmados |
| última compra | `con_datos` | solo cuando exista cliente/compra autorizada |
| cross-sell por reglas | `con_datos` mínimos | necesita roles; asociación después |
| outfits | `con_datos` | reglas primero; faltan estilo/ocasión/rol |
| colaborativo/LTR | `con_datos` | eventos/impresiones/usuarios insuficientes |
| VTO local | `experimental`, `licencia`, `hardware`, `legal_privacidad` | FASHN candidato; 8 GB no confirmado |
| CatVTON/IDM/Stable/OOT comercial | `no_recomendable`, `licencia` | licencias NC bloqueantes salvo permiso adicional |
| talla por tabla/medidas | `con_datos`, `legal_privacidad` | vía responsable y progresiva |
| talla solo por foto | `no_recomendable` | no garantiza medidas/ajuste |
| transfer learning | `con_datos`, `costoso` | dataset propio y mejora objetiva |
| RAG | `con_datos`, `costoso`, `legal_privacidad` | corpus/ACL/citas/tools read-only primero |

## 5. Dependencias externas críticas

| Dependencia | Necesidad | Riesgo | Fallback |
|---|---|---|---|
| acceso/catálogo de proveedor | identidad, reposición, surtido | hoy ausente; formato/cambio/contrato | importación manual |
| modelo/pesos SigLIP/CLIP | visión zero-shot | licencia/revisión/hardware | reglas/manual; CLIP baseline |
| GS1/fuente GTIN | verificar identificador | límites/cobertura/costo | checksum + interno + proveedor |
| Instagram API | métricas propias | cuenta profesional/permisos/cambios | export manual/interno |
| Pinterest Trends | señal de tendencia | acceso restringido | snapshot manual/omitir |
| Google Trends API | señal de búsqueda | alpha/limitada | import manual/omitir |
| OCR/decoder futuro | lectura de etiquetas/códigos | dependencias nativas/calidad | OpenCV/Tesseract/manual |
| VTO licenciado | visualización | peso/auxiliares/8 GB/calidad | no ofrecer o API aprobada |
| almacenamiento privado/cola | VTO/jobs externos | lifecycle/costo/seguridad | no activar funciones asíncronas personales |
| corpus/LLM futuro | RAG | costo, datos, seguridad y licencia | búsqueda/reportes tradicionales |

## 6. Decisiones que requieren aprobación

### D1 — Taxonomía y verdad de producto — prioridad 1

**Aprobar:** categorías canónicas, `POLERA`, atributos observables frente a confirmados, paleta, roles de outfit, `other/unknown`, owners y versionado.  
**Si se difiere:** no se puede evaluar categoría, patrón, outfits ni dataset propio con rigor.

### D2 — Modelo de identificadores y proveedores — prioridad 2

**Aprobar:** si `cob_prv` se migra conceptualmente a identificadores tipados; GTIN/proveedor/modelo; prioridad de fuente; proveedor, lead time, mínimos, múltiples y tránsito.  
**Si se difiere:** barcode básico es posible, pero enriquecimiento/reposición quedan incompletos.

### D3 — Primer MVP, métricas y abstención — prioridad 3

**Aprobar:** MVP1, SigLIP candidato/CLIP baseline, dataset QA, métrica primaria, umbrales y qué ahorro/error se considera éxito.  
**Si se difiere:** se puede construir infraestructura sin saber si entrega valor.

### D4 — Fuentes externas y derechos — prioridad 4

**Aprobar:** primera fuente (preferiblemente proveedor/manual), presupuesto/acceso a APIs, términos, rights statuses, retención y prohibición expresa de Instagram scraping/autopublicación.  
**Si se difiere:** IA-9/14 deben permanecer fuera del alcance.

### D5 — Política de datos personales avanzados — prioridad 5

**Aprobar:** si VTO/talla son objetivos reales; adultos/menores, consentimiento, local vs tercero, plazos de foto/resultado/medidas, borrado, no entrenamiento por defecto y quién accede.  
**Si se difiere:** IA-19/20 personales se bloquean sin afectar MVP1–4.

### Decisiones posteriores

- D6: almacenamiento de embeddings (exacto/pgvector/Faiss) al superar una escala medida.
- D7: motor OCR/decoder adicional después de benchmark OpenCV/Tesseract.
- D8: thresholds de datos para asociación, forecast, colaborativo y transfer learning.
- D9: parámetros de servicio/stock de seguridad y función de costo.
- D10: local vs API para VTO tras benchmark FASHN y evaluación contractual.
- D11: corpus, LLM y tools permitidas para RAG.

## 7. Registro de licencias revisadas

| Activo | Licencia/estado publicado | Uso recomendado |
|---|---|---|
| OpenCV | Apache-2.0 | viable sujeto a versión/packaging |
| ZXing-C++ | Apache-2.0 | candidato barcode futuro |
| Tesseract | Apache-2.0 | OCR baseline |
| SAM2 | Apache-2.0 publicado | segmentación asistida futura; auditar checkpoints |
| PaddleDetection/PaddleOCR | Apache-2.0 publicado | solo si otro stack mejora benchmark |
| Ultralytics YOLO | AGPL-3.0 o Enterprise según proveedor | no default propietario sin decisión/licencia |
| DeepFashion | sitio indica investigación no comercial; imágenes de Internet ajenas | referencia, no dataset comercial |
| CatVTON | CC BY-NC-SA 4.0 código/checkpoints/demo | bloqueado comercial |
| IDM-VTON | CC BY-NC-SA 4.0 código/checkpoints | bloqueado comercial |
| StableVITON | CC BY-NC-SA 4.0 | bloqueado comercial |
| OOTDiffusion | CC BY-NC-SA 4.0 | bloqueado comercial |
| FASHN VTON v1.5 | Apache-2.0 repo | candidato; auditar auxiliares/pesos/dataset |
| Chronos | Apache-2.0 repo | benchmark futuro, no justificado ahora |
| pgvector | PostgreSQL License | opción futura, no instalar ahora |

Licencias y repositorios pueden cambiar; se fija revisión/hash y se vuelve a verificar antes de uso.

## 8. Controles de salida a producción

Checklist por capacidad:

- [ ] problema y usuario claramente definidos;
- [ ] fallback manual probado;
- [ ] datos/labels y lineage aprobados;
- [ ] licencia de código, pesos, dataset y output revisada;
- [ ] contrato/privacidad/retención aprobados;
- [ ] baseline y evaluación fuera de muestra;
- [ ] abstención y manejo de errores;
- [ ] latencia, memoria y costo medidos en hardware real;
- [ ] seguridad de archivos/URLs/tokens;
- [ ] observabilidad sin PII;
- [ ] revisión humana y explicación;
- [ ] feature flag, rollback y kill switch;
- [ ] outcome comercial y revisión post-lanzamiento.

## 9. Criterios de detención

Una idea se detiene o aplaza si:

- no hay permiso/licencia compatible;
- no existe una fuente estable ni fallback aceptable;
- el modelo no supera baseline o su intervalo no es útil;
- el costo/hardware excede el valor medido;
- no puede mitigarse un riesgo de privacidad/seguridad;
- requiere inventar datos ausentes;
- la tasa de abstención/artefactos impide una experiencia honesta;
- agrega mantenimiento sin mejorar una decisión.

«Experimental» no significa que deba llegar a producción.

## 10. Fuentes críticas verificadas

- [Meta Automated Data Collection Terms](https://www.facebook.com/legal/automated_data_collection_terms)
- [Instagram API oficial de Meta](https://www.postman.com/meta/instagram/documentation/6yqw8pt/instagram-api)
- [Pinterest Trends API](https://developers.pinterest.com/docs/analytics-and-reports/trends/)
- [Google Trends API alpha](https://developers.google.com/search/apis/trends)
- [Verified by GS1](https://www.gs1.org/services/verified-by-gs1)
- [Ultralytics licensing](https://www.ultralytics.com/license)
- [DeepFashion](https://mmlab.ie.cuhk.edu.hk/projects/DeepFashion.html)
- [CatVTON](https://github.com/Zheng-Chong/CatVTON)
- [IDM-VTON](https://github.com/yisol/IDM-VTON)
- [StableVITON](https://github.com/rlawjdghek/StableVITON)
- [OOTDiffusion license](https://github.com/levihsu/OOTDiffusion/blob/main/LICENSE)
- [FASHN VTON v1.5 license](https://github.com/fashn-AI/fashn-vton-1.5/blob/main/LICENSE)
