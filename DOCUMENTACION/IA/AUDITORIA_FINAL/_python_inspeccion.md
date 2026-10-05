# Inspección Python previa a la ejecución

Fecha de auditoría: 2026-10-04. Estado: inspección completa de las fuentes productivas Python de `services/mati-ai/src/mati_ai`, ambos scripts, contratos, configuración, Dockerfile, README y guías de implementación IA. **No se han ejecutado tests, lint, inferencias, benchmarks, descargas ni escrituras comerciales en esta fase.** Este archivo es auxiliar para el documento 00 y las fases posteriores.

## Servicio, modelo y contratos efectivos

- FastAPI `create_app()` (`main.py:15`) crea Settings, un ModelManager y un EasyOCRProvider por proceso. Documentación OpenAPI/Swagger/Redoc deshabilitada. `GET /health` es público y solo liveness; no afirma preparación de pesos ni conectividad Laravel.
- Router principal `/api/v1` y router `/api/v1/products` requieren `Bearer ` exacto y comparación HMAC de token. Token vacío cierra acceso (`api/dependencies.py:8`). No hay DB, credenciales PostgreSQL ni escrituras comerciales Python.
- Middleware cuenta cuerpo ASGI completo antes de parser. Solicitud máximo `max_image_bytes + 1,000,000`; por defecto imagen 10 MiB, 24 millones píxeles, dimensión 12,000. Control de Content-Length y cuerpos chunked (`core/http.py:62-97`). Buffer es acotado pero toda solicitud se materializa en memoria.
- Sobre `contract_version=1`, `pipeline_version=registration-1.2.0`, request_id, status, data, warnings, abstentions, identidad y tiempos; `human_review_required=true` siempre (`contracts/common.py`). Errores seguidos son JSON seguros. Request IDs HTTP admiten caracteres `[A-Za-z0-9._:-]` de 1..100.
- Rutas presentes: capabilities; garments/analyze; garments/compatibility; garments/embedding; identification/barcode; identification/label; products/match; products/external-search; products/external-image; images/prepare-publication. No hay endpoint outfits, recomendación personal, tendencias, VTO ni entrenamiento.
- Taxonomías son del cliente Laravel: objetos id/name/aliases/hex/family/family_hex, máximo 150 entradas/lista e IDs únicos. Categorías obligatorias en analyze; colores/patrones opcionales. `family_hex` no es referencia exacta de matching.

Modelo efectivo por Settings: SigLIP `google/siglip-base-patch16-224`; alternativa configurable CLIP `openai/clip-vit-base-patch32`. `MATI_AI_VISION_MODEL` permite override; provider no garantiza por sí mismo el checkpoint usado. Revisión configurada por defecto main, pero al cargar exige resolver commit hexadecimal inmutable de 40 o 64 caracteres. No usa trust_remote_code. API local_files_only=true por defecto. EasyOCR descarga deshabilitada por defecto.

`ModelManager` cachea un checkpoint por worker con lock común de carga/inferencia (timeout 2 s), reintento tras fallo de carga 60 s y selección CUDA si disponible salvo device=cpu. device=cuda puede terminar CPU; registra dispositivo real. Fallos de mover modelo o ejecutar en CUDA intentan CPU. No hay cola de trabajos ni Redis. Workers múltiples duplicarían caches/VRAM. `torch.inference_mode()` y `.eval()` evitan entrenamiento.

Embeddings imagen/texto se normalizan L2, se verifica finitud, norma >0, tensor 2D y dimensión 1..2048. Coseno inferido es `image_normalized @ text_normalized.T` sin sigmoid/softmax; no se usan logits ni temperatura del checkpoint. Identidad embedding: modelo, revisión, dimensión real, pipeline, `projected-image-l2-1`, `srgb-exif-transformers-image-1`, normalization=l2, normalized=true, norm y dimension_source=generated_vector. Capabilities intenta leer AutoConfig local sin pesos y distingue dimensión estimada por configuración de la verificada al generar (`core/model_manager.py:75`). Endpoint embedding llama get_image_features; admite Tensor Transformers 4 o pooler_output/tuple Transformers 5.

## Flujo analyze, clasificación y calidad

`api/v1/routes.py:154-231`: validar → calidad → colorimetría → barcode → inferencia conjunta de categorías y patrones → embedding opcional → envelope. Inferencia ve la **imagen completa normalizada**, no máscara/crop foreground. Fallo visual se convierte en abstención parcial conservando otros resultados; fallo previo de validación/barcode no tiene ese mismo catch.

Validación (`vision/validation.py`): solo JPEG/PNG/WebP real por decoder, estática, mínimo lado 16; verify, load, orientación EXIF, perfil ICC→sRGB o sin perfil conversión RGB; transparencia sin ICC sobre blanco; metadatos despejados. SHA256 bytes originales, pixel_sha256 normalizado y dHash grayscale 9×8. No modifica archivo original.

Calidad (`vision/quality.py`): thumbnail 1024, varianza Laplaciano; blur<50; fracción gris<10 mayor .55; gris>245 mayor .65; contraste p95−p5<30; lado mínimo<300. Son avisos heurísticos, no filtro que impida inferencia.

Prompts categoría `This is a photo of {alias}.` con primer alias del catálogo o traducción inglesa de nombres conocidos. Patrón `This is a photo of clothing with a {alias} pattern.`. Categoría devuelve top3; abstiene con top_score<0.08 o margen top1−top2<0.005. Patrón devuelve mejor entrada o null con mismos umbrales; sin patrón catálogo devuelve null. Scores coseno no calibrados; no probabilidad ni exactitud porcentual. No reconoce fibra, marca, talla ni composición desde foto general.

## Foreground y colorimetría efectivos

Version `garment-colorimetry-1.0.0`. Copia hasta 256×256 LANCZOS, sRGB float /255 → OpenCV CIELAB físico D65; CIEDE2000 vectorizado con kL=kC=kH=1 (`vision/color_distance.py`).

Foreground (`vision/segmentation.py`) **heurística de silueta, no detector semántico de prenda/persona**:

1. Banda ~4%; modo histograma Lab [6,8,8]; vecinos ΔE00≤6 para mediana fondo.
2. Fondo uniforme exige soporte borde ΔE≤7 ≥.82, p90≤7, todos lados ≥.65 y al menos tres lados ≥.82.
3. Umbral conexión al borde clamp(p90+3,6,11); connectedComponents 8; fondo similar unido al borde. Foreground inverso y closing3×3.
4. Componente ≥4% imagen que intersecta centro 28..72%; candidato≥8%; principal elegido por área.
5. Rechazo si otro componente≥3.5%, share principal<.88, fuga borde>.05, área>.88, ocupación centro<.45, offset centroide>.22 o mediana separación ΔE<10.
6. Confianza=min(.94, .30·soporte + .20·max(0,1−p90/10) + .20·min(1,ΔE_median/22) + .15·share_principal + .10·min(1,centro/.8) + .05·min(1,edge_support/.3)).
7. Erosión3×3 excluye boundary; debe retener≥85% área y confianza≥.70. Rechazo devuelve máscara vacía, paleta vacía y propiedades unknown. No fallback a toda foto.

Color (`vision/color.py`): histogram [6,8,8], hasta 8 seeds separados ΔE≥12, cinco refinamientos de centros Lab ponderados con asignación ΔE00. Retiene grupos≥.015 del total de píxeles foreground; proporciones no renormalizadas; coverage suma retenida.

Iluminación: shadow_clipped todos RGB≤4 en ≥.40; highlight todos≥250 en ≥.40; channel_clipped algún canal≥254 o todos≤1 en ≥.35. Borde posible neutral L≥70,C≤22,|a|≤10; b>9 cálida, b<−9 fría. No referencia certificada, no balance corregido. Reliable equivale a ausencia de estos avisos; ausencia aviso no demuestra color físico verdadero.

Confianza swatch = foreground·(.90 si luz fiable/.45 si no)·max(.35,1−spread_p90/30). Match ID catálogo requiere iluminación fiable, HEX específico, acabado no metálico, ΔE≤10, margen≥2 salvo ΔE≤2, confianza≥.55 y spread≤14. Una sola referencia solo acepta ΔE≤2. Nombres sin HEX usan fallback descriptivo y jamás asignan ID. Color metálico abstiene ID: RGB no prueba acabado.

Propiedades fiables solo si iluminación fiable y coverage≥.85; conserva métricas aun cuando categorical unknown:
- lightness media L*: low<35, high≥70.
- chroma media hypot(a,b): low<25, high≥50.
- HSV S media: low<.25, high≥.60.
- neutralidad fracción C≤12 o (C≤30 y hue Lab 35..100): low<.35, high≥.70.
- contraste = max(ΣiΣj p_i p_j ΔE00(i,j), (L_p90−L_p10)/2): low<8, high≥20.
- Variedad grupos≥.12 distintos: ΔE≥15 y hue≥30° o ΔC≥24 con ΔL≤12; neutrales necesitan ΔL≥35. Monochrome ≤1 grupo, bicolor2, multicolor≥3. Dominant_family=multicolor solo variedad multicolor.
- Temperatura por swatch: C<5 neutral; C<30 b>5,a>−8 warm_neutral, b<−5 cool_neutral; C≥30 hue≤125 o≥345 warm, resto cool. Suma proporciones por clase; warm y cool ambas≥.25 y diferencia<.20 da unknown.
- Principal exige share≥.55 y ventaja≥.15; acento share<.12 y ΔE contra top≥15, restantes secundarios. Ausencia principal no invalida paleta.

Limitaciones científicas visibles antes de pruebas: fondo/color/iluminación umbrales heurísticos pendientes calibración local; objetos/maniquí/percha unidos pueden contaminar; thumbnails/antialiasing sombras/compresión alteran observación; clipping puede ser negro/blanco/color saturado intrínseco; RYB futuro usa otra geometría. No seguridad semántica de que foreground sea prenda.

## Identificación y ranking interno

Barcode (`identification/codes.py`): OpenCV QR multi + barcode detectAndDecodeWithType con excepciones controladas; ZXing-C++ EAN13/EAN8/UPCA/UPCE/Code128/QR. Este último import/read no está dentro de catch, por lo que fallo del runtime decoder puede fallar analyze completo. Deduplica por simbología+valor; EAN13 empieza 0 → UPCA; UPCE expansión 8→12 para checksum. GTIN 8/12/13/14 usa checksum alternado 3/1. Confidence=null; bounding boxes; URLs dato y aviso QR_URL_NOT_FETCHED.

OCR (`identification/ocr.py`): EasyOCR es/en CPU gpu=false, cache, lock2s, thumbnail1800 y cajas escaladas a resolución original. Lee texto/regiones/confianza; normalización **no utiliza la confianza OCR** para descartar tokens. Marca con match línea/boundary exacto o SequenceMatcher≥.82; talla exacta≥.99. SKU/modelo regex, GTIN checksum, composición regex porcentaje-material; avisa total distancia de100>1. OCR ausente/BUSY vuelve partial con datos vacíos manuales.

Matching (`identification/matching.py`) solo candidatos dados (≤250; limit≤50), sin consulta DB. SKU exacto1; GTIN válido exacto .98 (padding14); modelo .94 con marca exacta o .86; restantes min(.79,.35 texto + .32 max(0,coseno) + .45 imagen hash exacto + .05 marca + .04 categoría + .03 color). GTIN inválido no identidad. GTIN_CONFLICT cap .05; modelo conflicto ×.4; marca conflicto ×.5. No threshold que elimine score0: retorna rank hasta limit. Nombre `match_type=exact_sku` puede persistir aunque score sea penalizado por conflictos; consumidor debe mirar conflictos.

Embedding interno exige ambos SHA revisión, pipeline no vacío, modelo/revisión/longitud/pipeline/embedding_version/preprocess/normalization iguales. Función cosine_similarity estable escala por max abs para prevenir overflow y clamp[-1,1]. Similaridad foto no identidad.

## Enrichment, safe fetch y publicación

Fuentes ejemplo ambas enabled=false; no API real configurada en archivo de ejemplo. Servicio configura≤10 fuentes desde path operador de JSON≤5MB. Solo adapters manual/html. enabled, terms_approved, terms_url, reviewed_at requeridos; no secreto en payload. Cache lru2 por path; cambios archivo no aplican automáticamente. Search source priority supplier→official_brand→authorized_api→public_catalog→manual→html, plazo común18s; HTML subplazo12s; elige GTIN antes modelo antes texto, sin fallback texto cuando GTIN sin resultados. Query marca puede fabricar texto marca/categoría/color. Source warnings dan partial. Search vacío sin warnings puede ser completed: no certificado hallazgo.

Manual carga filas, checked URLs y hasta10 imágenes; derechos config sobrescriben fila/HTML. HTML selectores cerrados, quote query, max_pages productos≤5; bloquea challenges título/login/captcha; no JS; título ausente parser_changed; quita script/style/noscript y texto no HTML para resultados. `authorized_api` es tipo/prioridad configurable, **no adaptador API dedicado implementado**.

Ranking externo distinto del interno: exactGTIN .95+.04 texto; modelo exacto (.85 marca exacta o .78)+.04 texto; resto .40 texto+.25 visual+.10 marca+.05 atributos (category/color .5 cada). GTIN conflicto score0; marca/modelo conflicto min(score×.2,.15). Visual externo valida solo model_id/revision/len, no pipeline/preprocess/normalización ni revisión inmutable: protección inferior al embedding interno. Prohibidos se excluyen; unknown permanece revisión. Dedup source+canonical. Candidates revisión humana.

SafeFetcher (`enrichment/safe_fetch.py`):
- HTTPS443 dominio exacto allowlist, sin usuario/pass/fragment/backslash/control chars, URL≤2048; localhost/local/internal prohibido.
- DNS getaddrinfo valida **todas** IP públicas; no privadas/multicast/mapped/6to4/Teredo. Conecta IP elegida preservando Host y SNI; no resolución segunda del hostname. trust_env=false, cliente nuevo, sin cookies/auth heredadas.
- Redirecciones hasta3 con policy/DNS/robots revalidado. Robots obligatorio, fetch text/plain≤256k, cache600s, User-Agent MatiBoutiqueCatalogBot/1.0; robots inaccesible falla; pacing stricter bloquea hasta ajustar config.
- Deadline y timeout request≤15s, streaming16KiB y contador bytes, tipo contenido allowlist, Content-Encoding solo identity. 429 cooldown60..3600s, sin reintento evasivo.
- Posible límite a examinar en pruebas: getaddrinfo DNS es bloqueo fuera de timeout HTTP; deadlines se verifican después de resolver, no interrumpen DNS. Límite de plazo de search puede superarse por DNS bloqueado.

External image exige source/product/image canonical index propio y derechos no prohibited; publication requiere rights publishable+evidence; reference admite unknown. Fetch≤10MB JPEG/PNG/WebP; decoder formato/mime idéntico, estático≤20Mpx, SHA metadata y base64. No inferencia ni validación semántica producto. `request_id` campos JSON external/match no actualizan context; header sí (unlike compatibility).

Publication (`publication/pipeline.py`): approved=true, rights_status owned/licensed/provider_authorized/public_domain y approval_id no vacío, luego validación común. Tres WebP quality90/method4, contener sin ampliar:240/640/1600; elimina EXIF/metadata, hash/bytes/base64/manifiesto. No DB/storage Python ni generación. Derechos son declaraciones internas; Python no comprueba expediente contra DB.

## Documentación contrastada y pruebas a realizar después de 00

Guías implementadas son consistentes con arquitectura básica y reconocen umbrales no calibrados/no outfits. Guía01 contiene ejemplo histórico pipeline1.0.0 y tabla rutas antigua sin embedding/compatibility; guía03 describe la fase histórica1.1.0; README y código actual1.2.0, por lo que leer como historia de fases. Documentos originales/V2 describen intención amplia y no prueban features presentes.

Propuesta posterior: pytest sin model_integration; ruff check src tests; mypy src; fixtures sintéticas foreground/color/ΔE published pairs/barcode/OCR/publication/SSRF; tests compatibility simetría/invarianza roles/paletas; fallos controlados/model metadata. Separar tests deterministas/mocks de inferencias reales y fotografías reales. El único test model_integration escrito usa imagen beige uniforme, dos prompts y norma/dimensión; no mide exactitud ni categorías/cambio luz. No hay test dedicado compatibility en tests actuales (verificar colección posterior). CPU y GPU reales los coordina root, sin concurrencia de modelos.

## Compatibilidad cromática efectiva (adición previa/posterior de notas, sin cambiar código)

`garment-compatibility-1.0.0` evalúa JSON de dos paletas existentes; no imágenes, detector, entrenamiento ni outfits. Contrato máximo8swatches/lista; paleta suma≤1; Lab L∈[0,100], |a/b|≤160; sin duplicado(rgb,lab), aunque no valida coherencia física RGB↔Lab. Entrada unusable si versión no admitida, status insufficient, paleta vacía/suma≤0, min(coverage,suma)<.50 o foreground<.70; score=null.

Cada prenda normaliza pesos dentro de su paleta retenida; pareja tono_i/tono_j pesa p_i·p_j. La proporción física entre prendas NO se mide; se usa peso conceptual igual(.5,.5) para soporte triples. Roles principal/secundario/acento no añaden masa. Hue artístico RYB se aproxima linealmente desde HSV sRGB con nodos HSV→RYB [0→0,30→60,60→120,120→180,180→210,240→240,300→300,360→360]; Lab solo luminosidad/croma/neutros.

Neutro C≤12 o family beige_cream/brown con C≤30 y hueLab35..100. Pares: monocromático≤18° score.90, análogo≤55° .84, complementario |angle−180|≤25° .90, ambosneutros.92, neutro+cromático.90, noarmónico.55. Armonías de pares declaradas si masa≥.20. Neutrales requieren cada prenda≥.70 de masa; neutralacento exige una≥.70 y otra cromática≥.12. Claro/oscuro ΔL≥30 debe cubrir masa≥.35.

Triples: tonos no neutrales share original≥.06, ambas prendas presentes, regiones mín separadas>36°; gap triada(120,120,120) o complementaria dividida(60,150,150) tolerancia22°, soporte cada prenda≥.18 y promedio≥.45. Triple score.87 puede elevar score del par, sin duplicar p_i p_j.

Lightness por par=.70+.20·min(1,ΔL/40); monocromático mínimo.85. Temperature=.88 si cualquiera neutral, .82 misma orientación cálida/fría, .86 opuestas con armonía estructurada, .70 opuestas sin estructura; unknown→null y se excluye peso (no score0). Intensas si masa con C≥50 o S≥.60≥.60, o perfiles chroma/saturation high. Contraste=max(0,.85−.12·masa_noarmónica−min(.20,ajustes)): ambas intensas−.08, ambas multicolor y contrast high−.10, ambas printed/striped/checkered con intensidad−.08. Score final100·media ponderada componentes disponibles [temperatura.10,luminosidad.20,hue.55,contraste.15], clamp0..100 y2decimales.

Status partial si entrada parcial/cobertura<.85, evidencia swatchbaja≥.25 de masa, coverage mismatch>.01 o roles inválidos/superpuestos. Unknowntemperature puede mantener status completed con aviso; por tanto completed significa cálculo terminó, no evidencia plena en todos componentes ni validación estética. Puntuación heurística sin calibration ground truth humano de pares.
