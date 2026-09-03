# Arquitectura inteligente V2 — Resumen y brechas de V1

**Proyecto:** Mati Boutique  
**Tipo de documento:** investigación y diseño; no constituye implementación  
**Fecha de revisión:** 2026-08-03  
**Alcance verificado:** repositorio, migraciones, modelos, formularios, almacenamiento, reportes, `services/mati-ai`, documentación V1 y PostgreSQL mediante consultas `SELECT`

## 1. Resumen ejecutivo

La V1 estableció correctamente el límite más importante del sistema: Laravel conserva la autoridad comercial y FastAPI solo analiza y sugiere. V2 mantiene ese límite y deja de tratar «la IA» como una capacidad única. La propuesta se divide en dominios independientes que combinan validaciones deterministas, visión clásica, modelos multimodales, OCR, reglas, estadística, recomendación, series temporales y generación de imágenes.

El sistema real aún está en una etapa de datos muy temprana: hay 13 prendas, 13 variantes, 11 ventas registradas distribuidas en siete días con ventas, 8 reservas y 5 favoritos. Por ello, el primer valor no proviene de entrenar redes ni de pronosticar por SKU. Proviene de mejorar la captura de producto, conservar evidencia, registrar decisiones humanas y crear eventos comerciales fiables. Los modelos más complejos solo deben activarse después de superar baselines simples mediante evaluación temporal u online.

La V2 propone cinco líneas de producto:

1. **Operación de catálogo asistida:** calidad, color, categoría, códigos, OCR, duplicados, búsqueda y preparación de imágenes.
2. **Inteligencia comercial explicable:** indicadores, patrones, pronóstico jerárquico, reposición y surtido.
3. **Descubrimiento y recomendación:** similares, última compra, cross-selling, outfits y personalización progresiva.
4. **Señales externas:** proveedores, cuenta propia de Instagram, Pinterest y Google Trends mediante adaptadores que toleran indisponibilidad.
5. **Experiencias avanzadas:** virtual try-on, talla, transfer learning y RAG, todas sujetas a compuertas de datos, licencia, privacidad y hardware.

## 2. Decisiones de V1 que se conservan

Las siguientes decisiones siguen siendo obligatorias:

- Laravel es la única autoridad sobre prendas confirmadas, ventas, reservas, pagos, inventario y Kardex.
- React se comunica únicamente con Laravel; no conoce la red interna ni los contratos de FastAPI.
- FastAPI procesa entradas y devuelve candidatos, predicciones, explicaciones y advertencias; no ejecuta decisiones comerciales.
- Ninguna predicción se convierte en verdad confirmada sin acción humana explícita.
- Predicción, versión de modelo, evidencia y verdad confirmada se almacenan por separado.
- La indisponibilidad de IA no bloquea el alta ni la edición manual.
- `services/mati-ai` sigue siendo el límite físico previsto para el microservicio.
- La primera integración es HTTP privada; los trabajos costosos pasan a colas cuando la fase lo exige.
- Laravel autentica, autoriza, orquesta y persiste; FastAPI no accede directamente a las tablas comerciales en las primeras fases.
- Debe existir fallback manual, timeout estricto, respuesta versionada y trazabilidad por solicitud.

## 3. Evidencia del sistema real

### 3.1 Estado comprobado

La inspección fue de solo lectura. Docker y PostgreSQL estaban disponibles; no se escribieron datos. La base comprobada fue PostgreSQL 18.3, con 40 migraciones aplicadas y 33 tablas públicas.

| Área | Evidencia actual | Estado |
|---|---|---|
| Catálogo | 13 prendas, 13 variantes, 13 imágenes | Existente, volumen mínimo |
| SKU | `sku_prv`, único; 13/13 variantes informadas | Existente |
| Código adicional | `cob_prv`, opcional y único; 0/13 informado | Parcial; semántica GTIN/proveedor no definida |
| Categoría, marca, temporada, género | Asociaciones presentes en prenda | Existente |
| Color y talla | Asociaciones presentes en variante | Existente |
| Inventario | disponible, reservado y ubicación | Existente |
| Kardex | 17 movimientos (`entrada`, `venta`, `venta_en_revision`) | Existente |
| Ventas | 11 ventas, 12 detalles, 18 unidades; 7 días activos | Existente, insuficiente para ML temporal por SKU |
| Cliente identificado en ventas | 1 de 11 ventas | Parcial crítico |
| Reservas | 8 reservas, 9 detalles | Existente |
| Favoritos | 5 eventos, 2 usuarios, 4 prendas | Existente, insuficiente para colaborativo |
| Imágenes | original con extensión de carga, portada/galería | Parcial; sin derivados, hashes ni procedencia completa |
| Duplicados | tres pares con hash exacto repetido en almacenamiento | Brecha operativa confirmada |
| Reportes | ventas, reservas, pagos, inventario, clientes y catálogo | Descriptivos; no predictivos |
| Microservicio | paquete mínimo con `pyproject.toml` e `__init__.py` | Esqueleto, no servicio funcional |

### 3.2 Campos existentes, parciales y ausentes

| Dato | Estado real | Consecuencia para V2 |
|---|---|---|
| SKU interno | Existente | Primera llave de búsqueda interna |
| GTIN/EAN/UPC | Parcial | `cob_prv` puede guardar un código, pero falta tipo, emisor, checksum y procedencia |
| Código de proveedor/modelo | Ausente | Requerido antes de enriquecimiento fiable |
| Proveedor | Ausente | Bloquea reposición completa y trazabilidad de fuente |
| Lead time y variabilidad | Ausente | Reposición solo puede ser indicativa |
| Costos de pedido/mantenimiento | Ausente | EOQ no es calculable con rigor |
| Mínimo, múltiplos y pedidos en tránsito | Ausente | Cantidad sugerida incompleta |
| Temporada | Existente | Utilizable, sujeto a calidad del catálogo |
| Estilo, patrón, material, ocasión y silueta | Ausente | Deben definirse como taxonomías antes de entrenar o recomendar outfits |
| Búsquedas, vistas, impresiones, clics e ignorados | Ausente | Impide medir conversión y recomendación online |
| Recomendaciones expuestas | Ausente | No se puede separar exposición de respuesta |
| Medidas y preferencia de ajuste | Ausente | La talla solo puede usar tabla genérica futura |
| Tablas por marca/prenda y medidas reales | Ausente | No se puede recomendar ajuste responsablemente |
| Cambios, devoluciones y feedback de talla | Ausente | No hay verdad para aprender talla |
| Imagen de cliente, consentimiento y borrado | Ausente | Virtual try-on bloqueado hasta diseñar gobernanza |
| Fuentes externas, derechos y snapshots | Ausente | Enriquecimiento no auditable todavía |
| Tablas IA | Ausente | Deben añadirse solo en la fase que demuestre necesidad |

### 3.3 Calidad y sesgos de datos observados

- El catálogo contiene una categoría `POLERA` fuera de la lista canónica observada en seeds; la taxonomía debe gobernarse antes de usarla como etiqueta.
- Todos los SKU están presentes, pero ningún código adicional está informado.
- Solo una venta está vinculada a un cliente identificable; no es posible inferir preferencias personales representativas.
- La demanda por variante es muy dispersa: cada variante tiene cero, uno o pocos días con venta. Agregar por categoría o tienda es más razonable que pronosticar por SKU.
- No hay eventos de exposición. Un producto sin clic podría no haber sido mostrado; tratarlo como rechazo introduciría sesgo.
- El stockout no está modelado como censura de demanda. Una venta cero con stock cero no significa demanda cero.

## 4. Qué resolvía bien V1

V1 fue sólida en:

- separación de responsabilidades Laravel–FastAPI;
- integración privada, autenticada y tolerante a fallos;
- validación humana y almacenamiento separado de predicción/verdad;
- pipeline inicial de imagen, calidad, color y clasificación;
- uso inicial de SigLIP con CLIP como baseline y abstención por confianza;
- similitud mediante embeddings y búsqueda KNN;
- diseño de contratos versionados y trazabilidad de modelo;
- una única carga grande en GPU y ejecución preferentemente CPU para validaciones simples;
- roadmap incremental con criterios de aceptación, pruebas y riesgos.

Estas decisiones no se sustituyen: se convierten en el núcleo sobre el que se agregan nuevos dominios.

## 5. Brechas principales de V1

V1 no cubría con profundidad suficiente:

1. **Identidad del producto:** GTIN, códigos de proveedor/modelo, QR, OCR de etiquetas y reconciliación entre identificadores.
2. **Enriquecimiento gobernado:** fuentes desacopladas, derechos de imagen, ranking multimodal, scraping permitido y seguridad de URLs.
3. **Ciclo de publicación:** distinción entre original, copia de análisis, candidato externo, aprobado y derivados de publicación.
4. **Señales externas:** limitaciones reales de Instagram, Pinterest y Google Trends, así como fallback manual.
5. **Inteligencia comercial:** reglas de asociación, pronóstico con demanda intermitente, backtesting, reposición y surtido como problemas separados.
6. **Personalización progresiva:** exposición, clic, última compra, cross-selling, outfits, cold start y evaluación online.
7. **Virtual try-on y talla:** separación funcional, licencia, hardware, consentimiento, retención y límites de producto.
8. **Transfer learning:** compuerta objetiva basada en dataset propio y mejora contra zero-shot.
9. **RAG empresarial:** corpus autorizado, herramientas de solo lectura, citas y guardrails.
10. **Gobernanza transversal:** catálogo de fuentes/modelos, políticas de retención, idempotencia, costos y observabilidad por dominio.

## 6. Diferencias estructurales entre V1 y V2

| Tema | V1 | V2 |
|---|---|---|
| Unidad conceptual | Pipeline de visión como centro | Portafolio de dominios independientes |
| Identificación | Metadatos de prenda | SKU + GTIN + proveedor + modelo + QR + OCR, con reconciliación |
| Enriquecimiento | Secundario | Dominio formal con adaptadores, ranking y derechos |
| Tendencias | Fuera del núcleo | Adaptadores externos, evidencia normalizada y score explicable |
| Comercial | Reportes y recomendaciones generales | Analítica, demanda, reposición y surtido separados |
| Recomendación | Similares/KNN inicial | Popularidad, contenido, última compra, cross-sell, outfits, híbrido y LTR futuro |
| Imágenes | Entrada y análisis | Ciclo de vida completo y derivados reproducibles |
| Generación | No prioritaria | VTO aislado, asíncrono y con compuerta legal/hardware |
| Talla | No desarrollada | Reglas y medidas; nunca inferencia solo por foto |
| Datos | Predicción y feedback | Eventos, fuentes, evidencia, snapshots, consentimiento y retención |
| Despliegue | Un servicio | API CPU, workers CPU, un worker GPU visual y worker VTO excluyente |
| Activación | Roadmap técnico | Compuertas de valor, datos, licencia, privacidad y métrica |

## 7. Clasificación crítica de factibilidad

| Capacidad | Clasificación V2 | Motivo y condición |
|---|---|---|
| MIME, dimensiones, EXIF, hash, desenfoque y exposición | Viable ahora | Algoritmos deterministas/visión clásica; no requieren entrenamiento |
| Color dominante y top-3 categoría zero-shot | Viable ahora | Debe abstenerse y ser validado; falta benchmark local |
| Exactitud de categoría fina | Viable con datos | Requiere taxonomía estable y dataset propio representativo |
| Código de barras/QR/SKU | Viable ahora | OpenCV como primer intento; ZXing-C++ como candidato posterior |
| OCR de etiquetas | Viable ahora como asistencia | Necesita fotos dedicadas y revisión; no debe crear entidades |
| Identificación exacta de prenda genérica por foto | No garantizable | Sin código o catálogo autorizado solo puede devolver candidatos |
| Similaridad interna | Viable ahora | Catálogo pequeño permite búsqueda exacta en memoria/SQL auxiliar |
| Búsqueda externa | Dependiente de fuente y derechos | Priorizar proveedor/API; nunca auto-publicar imágenes |
| Scraping específico | Condicionado/alto riesgo | Solo con permiso, términos y robots; prohibido para Instagram sin autorización escrita |
| Instagram propio | Dependiente de cuenta profesional/permisos | Solo insights de la cuenta y medios permitidos |
| Pinterest Trends | Restringido | Orientado a agencias, Enterprise y partners; fallback manual obligatorio |
| Google Trends API | Alpha/restringido | Acceso limitado; no debe ser dependencia de producto |
| Patrones descriptivos | Viable ahora | SQL/reglas, pero resultados frágiles por volumen |
| Forecast por categoría | Experimental inicial | Baselines y backtesting cuando exista historia suficiente |
| Forecast por variante | No recomendable ahora | 11 ventas y siete días activos no sostienen el modelo |
| Reposición completa | Viable con datos adicionales | Faltan proveedor, lead time, costos, mínimos y tránsito |
| Surtido | Asistencia con evidencia | Comenzar con reglas y lote piloto, no orden automática |
| Personalización | Viable gradualmente | Popularidad/contenido primero; eventos e identificación después |
| Colaborativo/deep recommender | No recomendable ahora | Usuarios e interacciones insuficientes |
| Outfits por reglas | Viable con taxonomía | Faltan roles, ocasión, estilo y compatibilidades aprobadas |
| VTO local en RTX 5060 8 GB | Experimental/costoso | Candidato debe probarse aislado; no hay SLO confirmado |
| Talla por fotografía | No recomendable | La foto no aporta medidas fiables ni garantiza ajuste |
| Talla por tabla/medidas | Viable con datos | Requiere tablas por marca/prenda, medidas y consentimiento |
| Transfer learning | Viable futuro | Solo con dataset versionado y mejora objetiva sobre baseline |
| RAG | Viable futuro | Útil solo con corpus autorizado, actualizado y con citas |

## 8. Principios de diseño V2

1. **Asistencia, no autonomía:** toda acción comercial relevante termina en una propuesta revisable.
2. **Baseline antes que complejidad:** SQL, reglas y naive compiten con cualquier modelo.
3. **Abstención explícita:** `insufficient_evidence` es una salida correcta.
4. **Evidencia primero:** cada sugerencia informa origen, fecha, modelo/fórmula, datos faltantes y limitaciones.
5. **Datos por propósito:** una foto para VTO no se reutiliza para entrenamiento sin consentimiento adicional.
6. **Degradación funcional:** cada adaptador externo puede quedar inactivo sin detener catálogo o venta.
7. **No mezclar verdades:** tendencia externa, demanda interna y oportunidad comercial son señales diferentes.
8. **Recursos aislados:** modelos generativos no comparten simultáneamente 8 GB de VRAM con modelos visuales.
9. **Licencia como compuerta:** que código y pesos estén disponibles no significa que puedan usarse comercialmente.
10. **Medición antes de expansión:** no se promueve una fase sin criterios de aceptación y datos de evaluación.

## 9. Resultado recomendado

El primer objetivo V2 no es «tener muchas IAs». Es crear un circuito de alta de prenda más rápido y verificable:

> cargar imágenes dedicadas → validar archivo/calidad → leer código o etiqueta → proponer atributos y duplicados → validar → preparar derivados → Laravel registra la verdad y el feedback.

Ese circuito produce valor inmediato y, al mismo tiempo, genera las etiquetas, identificadores y eventos necesarios para pronóstico, recomendación y entrenamiento futuro.

## 10. Documentos relacionados

- [Arquitectura modular V2](02_arquitectura_modular_v2.md)
- [Visión, identificación y enriquecimiento](03_vision_identificacion_y_enriquecimiento.md)
- [Tendencias y fuentes externas](04_tendencias_y_fuentes_externas.md)
- [Analítica, demanda, reposición y surtido](05_analitica_demanda_reposicion_y_surtido.md)
- [Recomendaciones, outfits y personalización](06_recomendaciones_outfits_y_personalizacion.md)
- [Virtual try-on y tallas](07_virtual_tryon_y_tallas.md)
- [Contratos API y datos V2](08_contratos_api_y_datos_v2.md)
- [Roadmap V2](09_roadmap_v2.md)
- [Riesgos, decisiones y factibilidad](10_riesgos_decisiones_y_factibilidad.md)
- [Glosario técnico](11_glosario_tecnico.md)
