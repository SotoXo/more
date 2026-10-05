# Auditoría de OCR, códigos, matching, fuentes y publicación

Fecha:2026-10-04. Inspección y pruebas posteriores al documento00. No se modificaron fuentes productivas, pruebas históricas, tablas comerciales, productos, variantes ni inventario. Artefactos reproducibles: `storage/app/ai-audit/python-pytest.txt`, `python-ruff.txt`, `python-mypy.txt`, `python-synthetic-audit.json`, `python-updated-fixtures.json`; scripts bajo `services/mati-ai/scripts/audit/`.

## Baseline Python, alcance y fallos

| Ejecución existente | Resultado observado |
|---|---|
| `.venv/bin/python -m pytest -m 'not model_integration' -q` |103 passed,5 failed,1 deselected;9,52s;1 warning de Starlette/httpx |
| `.venv/bin/ruff check src tests` |8 hallazgos; salida1 |
| `.venv/bin/mypy src` |21 errores en7 archivos,47 fuentes revisadas; salida1 |
| Fixture auditada con contrato actual |9/9 checks pasó, con mocks; sin inferencia visual real |
| Auditoría determinista ampliada |92/92 checks pasó; incluye conjuntos numéricos/propiedades, no92 fotografías |

No se declara la suite base verde. Las cinco fallas se explican por contratos/fixtures anteriores al código actual:

1. `FakeModel` del test API no retorna `embedding_metadata`; analyze con include_embedding lo necesita y la fixture genera500. Un mock completo con revisión SHA/metadata del contrato actual devuelve200 y embedding esperado.
2. La fixture de foto uniforme completa ya no constituye foreground fiable: el test de modelo ausente esperaba paleta. Una prenda sintética aislada con borde uniforme conserva paleta y abstención visual correcta.
3. Matching del test usa embeddings legacy con revisión `r` sin SHA/pipeline/versiones. La política actual excluye esa evidencia visual; el orden esperado anterior deja de cumplirse.
4. El test de color uniforme accede a `dominant_color['catalog_id']` aunque ahora puede ser null. La abstención actual es válida bajo contrato.
5. El mock `pinned-sha` no es commit hexadecimal inmutable válido. El gestor resuelve revisión desde caché local y el test espera literal anterior. Con SHA40 válido se verificaron carga única/norma/lock sin pesos.

Los mocks nuevos no sustituyen los tests históricos ni certifican el modelo real. No se arreglaron esas pruebas durante una auditoría sin autorización de cambios productivos.

Ruff: orden de imports en rutas, compatibility engine y dos tests; Callable debe importarse desde collections.abc; dos except/pass en metadatos; catch amplio en enrichment. Mypy: tipado de DNS, acumuladores/métricas color, Literals y colecciones compatibility, source registry, módulo EasyOCR sin stubs y salida opcional de Transformers. Algunos son mantenibilidad/typing, no fallos de ejecución por sí solos.

## Barcode y QR: motor real sobre imágenes generadas

OpenCV intenta QR/códigos retail y ZXing-C++ amplía EAN13/EAN8/UPCA/UPCE/Code128/QR. La confidence del decoder se conserva null, sin fabricar un porcentaje. Checksum GTIN valida longitud8/12/13/14 y alternancia3/1; UPC-E se expande para comprobarlo. EAN13 con cero inicial se normaliza UPC-A. URL en QR permanece dato con `QR_URL_NOT_FETCHED`.

Se generaron imágenes raster con ZXing y se ejecutó el decoder real del producto, sin mock del decoder ni llamadas a URLs QR. Seis formatos, seis variantes cada uno:

| Variante | Lecturas esperadas recuperadas / N |
|---|---:|
| Original limpio |6/6 |
| Rotación90° |6/6 |
| Blur Gauss1 |6/6 |
| Reducción a mitad |6/6 |
| JPEG calidad60 |6/6 |
| Recorte horizontal20 % |0/6 |

Total30/36. Los recortes eliminan partes necesarias del código y fallan de manera explícita (`no_code`); esto no equivale a que el decoder falle en30 % de fotografías reales. No hay capturas físicas etiquetadas de barcode para estimar tasa real del local.

EAN13=4006381333931; EAN8=96385074; UPC-A=036000291452; UPC-E=04210007; CODE128=MATI-ABC-123; QR=https://example.org/product/42. Datos son fixtures de auditoría. Se comprobó checksum inválido para cinco entradas, incluida cifra Unicode; ninguna obtuvo identidad GTIN válida.

Limitación del boundary: ZXing import/read_barcodes en `identification/codes.py:101` queda fuera de catch; un fallo inesperado del decoder puede abortar analyze antes de la rama que preserva resultados cuando falla el modelo visual. No se ha observado ese fallo en los36 casos ejecutados.

## OCR CPU: estado real de preparación y normalización

EasyOCR usa español/inglés, CPU, thumbnail1800, regiones y scores originales, cajas escaladas a resolución fuente y lock2s. Descargas deshabilitadas. Se intentaron tres etiquetas sintéticas de alto contraste con marca, tamaño, referencia y composición. La primera devolvió `OCR_UNAVAILABLE` tras4155,345ms; las siguientes devolvieron unavailable inmediatamente por cooldown de60s. No se encontraron los archivos locales de pesos craft/latin en el entorno inspeccionado y no se descargaron.

**OCR real disponible para medir:0 lecturas exitosas/3 intentos sintéticos. Etiquetas fotográficas reales con transcripción:0. CER, WER y exactitud OCR: no calculables.** La normalización de texto suministrado pasa3/3 ejemplos exactos; ese resultado prueba parsing, no OCR.

Marca: coincidencia exacta por línea/token boundary o SequenceMatcher≥0,82; talla requiere coincidencia exacta≥0,99. Modelo/SKU regex; GTIN checksum; composición porcentajes/materiales conocidos y aviso si total difiere de100 por más de1. Ningún dato de composición se infiere desde apariencia del tejido.

Hallazgo reproducido con mock de región OCR confidence0,01: texto `ZARA` produce brand_candidate confidence1,00, human_review_required=true. `normalize_label` recibe raw_text y catálogos, **no las confidences/regiones OCR**, por lo que ese confidence1 refleja coincidencia textual, no confiabilidad conjunta OCR→marca. El score debe etiquetarse así; un gate combinado puede considerarse después de preparar pesos y calibrar con etiquetas reales.

## Matching interno y protección de identidad

Datos y candidatos vienen de Laravel; Python no consulta BD. Hasta250 candidatos y limit50. SKU exacto1; GTIN válido exacto0,98; modelo0,94 con marca exacta o0,86 sin ella; soft score=min(0,79;0,35text+0,32visual+0,45same_image+0,05brand+0,04category+0,03color). Conflicto GTIN limita0,05; modelo×0,4; marca×0,5. Foto/hash/embedding semejante no prueba identidad física.

Se verificó con embedding completo SHA40 y L2 que identificador correcto vence al parecido, conflicto no se compensa con visual y vectores legacy no comparables avisan `EMBEDDING_MODEL_MISMATCH`. Coseno controla tamaños, ceros y valores no finitos, además evita overflow por escala. Tests existentes de magnitudes extremas1e300/1e-300 pasan.

El tipo de match puede seguir siendo `exact_sku` aunque conflictos bajen score; el consumidor debe considerar score/conflicts juntos. No hay umbral mínimo en rank_matches que elimine candidatos score0. Se requiere revisión humana en todos los sobres.

## Ranking y fuentes externas

Solo manual y HTML son adaptadores implementados. `authorized_api` es tipo/prioridad de fuente, sin adaptador API dedicado. Plantillas de configuración están desactivadas. No se contactaron proveedores/tiendas reales durante estas pruebas.

Ranking externo usa otra fórmula: GTIN exacto0,95+0,04text; modelo(0,85 con marca o0,78)+0,04text; resto0,40text+0,25visual+0,10marca+0,05atributos. GTIN conflict score0; marca/modelo conflict min(score×0,2;0,15). Datos/ranking son evidencia candidata.

**Brecha observada:** ranking externo acepta coseno con model_revision=`main` si modelo/revisión/longitud coinciden; la fixture obtuvo visual_score 1. El matching interno exige SHA inmutable, pipeline y perfiles compatibles. Externos no portan ese mismo contrato completo; no debe asumirse compatibilidad entre espacios solo por string de revisión/configuración.

Búsqueda elige GTIN antes modelo antes texto, sin fallback cuando el GTIN existe pero no encuentra nada. Deadline común18s; HTML≤12s; source priority proveedor/marca oficial/API autorizada/catálogo público/manual/HTML. Manual opera catálogo curado de configuración y HTML selectores cerrados, sin JavaScript, sanitiza texto y bloquea challenge/login/captcha. El cache de configuración por path exige reinicio/invalidation para releer modificaciones.

## SafeFetcher: pruebas negativas y límites

La batería existente de enrichment y la ampliada de auditoría usan HTTPX MockTransport/DNS controlado. Ejecutan parsing/streaming/policy reales del producto, **no conexiones TLS reales a un proveedor**. No constituyen pentest de despliegue/red.

En los escenarios ejecutados se verificó:

- Bloqueo HTTPS no permitido, puerto alternativo, subdominio ajeno, dominio sufijo engañoso, userinfo, fragmento, backslash, caracteres de control, URL larga y esquemas file/data/FTP.
- Bloqueo loopback, privados, metadata/link-local, CGNAT, IPv6 local/mapped/6to4/multicast; mezcla DNS pública+privada rechazada.
- Conexión a IP validada con Host/SNI del dominio comprobados en el request transport; ausencia de segunda resolución del dominio dentro de esa request.
- Redirección a privado/dominio ajeno y límite de saltos; Content-Type SVG/HTML como imagen; compresión inesperada; límite por Content-Length y bytes reales.
- Robots inaccesible, Disallow y crawl-delay más estricto bloquean; 429 inicia cooldown y403 no se evade.
- Fuentes sin términos/dominios habilitados fallan cerradas; unknown derechos sirve referencia pero no publicación y prohibited no referencia.

**Límite de plazo:** socket.getaddrinfo es síncrono y no está interrumpido por timeout HTTP; el deadline se comprueba después. Por tanto el plazo lógico de18s no garantiza límite duro si DNS se bloquea. No se simuló ni provocó una resolución DNS real bloqueante.

## Publicación y derechos

Se verificaron derivados WebP del pipeline real desde JPEG sintético2000×1200: thumbnail240×144, card640×384, detail1600×960; hashes coinciden con bytes decodificados y EXIF vacío. Original hash estable y `original_modified=false`. Cinco casos sin aprobación/derechos/approval_id válidos se bloquearon.

La preparación convierte orientación/perfil/color/size en memoria; no modifica la foto original ni publica por sí misma. `approved`, rights_status y approval_id son declaraciones del caller interno. Python no comprueba expediente/derechos en DB; Laravel mantiene esa autoridad y persistencia. No se realizó publicación comercial ni importación real a producto.

## Dictamen del dominio

Barcode/checksum, parsing, matching de candidatos y políticas SSRF/derechos muestran resultados favorables en fixtures probadas, con límites explícitos. El OCR está funcionalmente degradado por preparación ausente y no dispone de métrica de lectura real. La suite Python/lint/typing no está verde. Se necesita actualizar fixtures al contrato actual en una tarea de corrección, preparar OCR por el procedimiento autorizado y construir conjuntos físicos etiquetados antes de certificar identificación de producción.

Las fórmulas y evidencias conservan revisión humana; los scores de coincidencia y similitud no se presentaron como probabilidades calibradas. No se sustituyeron resultados no ejecutados con estimaciones.

## Suplemento final: denominadores y límites E2E

Decoder real sobre6rasters sintéticos originales: valor exacto6/6 ysimbología6/6(EAN-13,EAN-8,UPC-A,UPC-E,CODE128,QR). Variantes previasGTIN detectados20/24 ychecksumválido20/20detectados;4GTINrecortados no decodificaron. QR/CODE128 checksum GTIN noaplicable: null no es false. CODE128controlinternodecoder esdistinto del campo GTIN. audit_supplement.json incluye salidas. Overall30/36;5variantesbenignas×6=30/30,crop20%0/6.

HTTP privado3fixturesEAN13/QRtexto/QRURL200;URLsentinellocalhost0peticiones,warningQR_URL_NOT_FETCHED (http_barcode_qr.json). UIbarcode/QR/OCRsubieron fixtures desde controles reales,fallaron500porvalidateProhibitedWith antes del decoder. Privadofuncional no certifica integraciónUI. MatchingUI1SKUexacto score 1y1textosoft.282,muestrasoperativas no accuracy general. Probeaislado100/134candidatessoft(74.63%)yinactiveSKUdevuelto;comercio no recibióescrituras.
