# Resultado actual — IMPORTACIÓN V2

**120 prendas nuevas importadas; 14 iniciales + 120 nuevas = 134 finales. Objetivo cumplido.**

La evidencia de cierre V2, pruebas, procedencia y muestras UI se encuentra al final de este documento. El bloque siguiente conserva el historial de la ejecución V1 anterior, que quedó bloqueada con 0 prendas nuevas.

---

# Catálogo real de 100 prendas de Mati Boutique

**Ejecución interrumpida por bloqueo de herramientas: objetivo no cumplido**

Fecha de apertura: 03/10/2026 (America/La_Paz).

Este documento registra el trabajo autorizado para poblar el catálogo real mediante la interfaz del sistema. Solo se contabilizan registros guardados y comprobados en la UI. La meta de al menos 100 prendas reales todavía no se ha alcanzado.

## Estado al cierre de esta ejecución

| Métrica | Resultado comprobado |
| --- | ---: |
| Prendas iniciales | 14 |
| Prendas nuevas creadas en esta tarea | 0 |
| Total final verificado | 14 |
| Objetivo | Al menos 100 prendas reales |

Los 14 registros iniciales, IDs 1–14, fueron confirmados en el listado de prendas de la UI. No se ha creado todavía ninguna prenda nueva. El conteo final de 14 se confirmó también mediante lectura segura. Será necesario continuar la carga para alcanzar las 100 prendas nuevas.

## Entorno e inspección realizada

- Sistema real accesible en `http://localhost`, mediante sesión autenticada de Chrome.
- Listado administrativo: `/superadmin/catalogo/prendas`.
- Formulario normal de alta: `/superadmin/catalogo/prendas/nuevo`.
- Catálogo público: `/catalogo`.
- Se inspeccionaron el listado, los maestros disponibles y el formulario existente.
- El alta usa datos base, imagen principal, tipo de prenda, configuración comercial y revisión. Las prendas con variantes tienen un paso adicional.
- El código SKU interno lo genera automáticamente el sistema. No se reemplazará con referencias externas ni con un GTIN inventado.
- La imagen principal es obligatoria y acepta hasta 6144 KiB por archivo.
- Categoría, marca, temporada, género, color, talla, precio positivo, stock inicial y ubicación son necesarios para el registro operativo.
- Una prenda única necesita una configuración activa. Una prenda con variantes necesita al menos dos combinaciones distintas de color y talla.
- El sistema rechaza nombres duplicados para la misma categoría y marca, así como combinaciones repetidas de color y talla.

La carga de foto genera una vista previa local y prepara el canvas. El muestreo de píxeles y cálculo de color HEX se activa solamente al pulsar la vista previa sobre la tela. Se puede cargar la imagen y continuar sin usar esa herramienta, eligiendo después un color existente manualmente.

## Maestros vigentes inspeccionados en la UI

Las 11 marcas disponibles son:

| N.º | Marca |
| --- | --- |
| 1 | ZARA |
| 2 | BERSHKA |
| 3 | STRADIVARIUS |
| 4 | RALPH LAUREN |
| 5 | HUGO BOSS |
| 6 | MASSIMO DUTTI |
| 7 | MANGO |
| 8 | PULL&BEAR |
| 9 | RESERVED |
| 10 | COS |
| 11 | PATITO |

Las 11 categorías disponibles son:

| N.º | Categoría |
| --- | --- |
| 1 | POLERA |
| 2 | ABRIGOS |
| 3 | BLAZERS |
| 4 | CAMISAS |
| 5 | BLUSAS |
| 6 | VESTIDOS |
| 7 | FALDAS |
| 8 | PANTALONES |
| 9 | POLOS Y KNITWEAR |
| 10 | ACCESORIOS |
| 11 | CONJUNTOS |

La UI muestra 56 colores. ROJO VINO está deshabilitado y no se utilizará como selección activa. Los selectores de talla fueron comprobados; cada alta debe escoger una talla existente compatible con su categoría. Esta inspección no autoriza inventar características ausentes en la fuente comercial.

## Criterios comerciales para las altas pendientes

Los precios serán **precios demo en BOB (bolivianos)**, coherentes dentro del catálogo; no se presentan como precios originales del fabricante, inventario físico auditado ni información obtenida de las tiendas fuente.

| Grupo | Precio demo base (BOB) |
| --- | ---: |
| CAMISAS | 150 |
| POLOS Y KNITWEAR | 150 |
| POLERA | 90 |
| PANTALONES | 300 |
| ABRIGOS | 350 |

Para las demás categorías se fijará un precio demo coherente y se identificará como tal en la tabla de registros. El precio final de cada prenda se documentará después de su alta.

El plan operativo es stock inicial 1 por variante, ubicación TIENDA y comprometido/reservado 0. El servicio existente crea el inventario inicial con cantidad reservada 0. La ejecución por UI todavía está pendiente; estas cantidades no representan un conteo físico comprobado de Mati Boutique.

Se mantendrá separado:

- **SKU:** código interno automático del sistema.
- **Referencia externa:** código de modelo o referencia comercial de la fuente, cuando sea verificable.
- **GTIN:** solo si la fuente lo proporciona expresamente; no se inferirá ni inventará.

## Plan inicial de diversidad

Este reparto de 100 prendas es un plan pendiente y no un resultado registrado. Se podrá ajustar según disponibilidad de fuentes y fotografías adecuadas.

| Categoría | Cantidad prevista |
| --- | ---: |
| PANTALONES | 18 |
| CAMISAS | 12 |
| BLUSAS | 10 |
| POLERA | 12 |
| POLOS Y KNITWEAR | 12 |
| ABRIGOS | 14 |
| BLAZERS | 6 |
| VESTIDOS | 8 |
| FALDAS | 5 |
| CONJUNTOS | 3 |
| **Total previsto** | **100** |

La meta de creación queda pendiente de fuentes verificadas, imágenes permitidas y altas efectivas por el formulario.

## Fuentes y autorización de fotografías

Las fuentes retail consultadas presentan restricciones que impiden dar por autorizada su fotografía comercial. No se publicarán fotografías con modelos. Se requieren imágenes de la prenda y evidencia suficiente de uso permitido antes de registrarlas.

Premier ofrece un flujo público PHOTOGRAPHY > Flatshot Images > DOWNLOAD. Se confirmó el ZIP oficial PR200.zip descargado por esa interfaz (10.867.838 bytes), con 28 archivos originales de fotografía de prenda. No se accedió a recursos Lifestyle con modelos ni se creó una cuenta de proveedor.

- [Biblioteca de imágenes y condiciones de Premier](https://www.premierworkwear.com/en-gb/marketing-suite/image-library/).
- Estado: licencia pública consultada el 03/10/2026. La autorización para Flat Shot Imagery cubre marketing y B2C; se usará solo fotografía de producto sin modelo. Descarga oficial PR200 comprobada en disco. Las fotos se extrajeron sin editar.
- Los candidatos procedentes de una marca ausente requieren una decisión de clasificación correcta antes del alta; no se les atribuirá una marca del maestro sin evidencia.

Todavía no se ha publicado una fotografía externa en Mati Boutique. La licencia de Premier y su packshot oficial fueron verificados. Una URL accesible por sí sola no confirma permiso para publicarla.

## Diferencia entre source y UI compilada

La UI activa usa `public/build/assets/ProductCreate-Br6F6_ny.js`, fechado 26/09/2026. El source `resources/js/Pages/Superadmin/ProductCreate.tsx` es posterior, del 29/09/2026.

El source tiene `modelo_pre` y asistente inteligente, pero el build activo no los incluye. El campo de modelo no apareció en Datos base ni en la revisión de la UI observada. Esta diferencia se deja documentada y no se corregirá ni compilará durante la carga del catálogo.

El formulario activo ofrece **Código adicional (opcional)** dentro de **Agregar costo o código adicional**. Guarda `cob_prv` y permite una referencia alfanumérica de hasta 100 caracteres, letras, números y guion, única. No exige un GTIN numérico ni valida checksum o longitud GTIN. Se podrá usar para una referencia externa verificable, manteniéndola separada del SKU automático.

## Registros nuevos confirmados

La siguiente tabla se completará únicamente después de guardar y comprobar cada alta. No contiene filas ficticias, candidatos pendientes ni datos atribuidos a los 14 registros iniciales sin inspección individual.

| N.º | ID | Nombre | Marca | Categoría | Precio demo BOB | Color | Variantes | URL fuente | Observaciones |
| --- | --- | --- | --- | --- | ---: | --- | --- | --- | --- |

**Registros nuevos confirmados: 0.**

## Verificación y restricciones de trabajo

Cada lote deberá comprobar guardado efectivo, ID, nombre, marca, categoría, precio demo, color, variantes, stock, imagen visible y URL de la fuente. El conteo final se actualizará con los registros visibles en el sistema.

Durante esta tarea:

- La carga comercial se realiza únicamente con el navegador y el formulario normal.
- No se han usado scripts de inserción, SQL de modificación, seeders, migraciones ni importaciones por API.
- No se han ejecutado inferencia IA, OCR, análisis automático, extracción de color, generación de embeddings, entrenamiento ni descargas de modelos.
- No se han corregido ni compilado código o assets.
- No se han hecho commit ni push.
- No se han expuesto credenciales.
- El único archivo nuevo autorizado para documentación es este documento.

## Referencia del estado Git inicial

Rama inicial: `feature/ia-colorimetria`.

HEAD inicial: `fde26bd feat: integrar registro inteligente de prendas con IA`.

El árbol de trabajo ya contenía los siguientes cambios antes de crear este documento. Se conservan como referencia para comparación final; no se atribuyen a la tarea de poblar el catálogo.

```text
 M .env.example
 M app/Console/Commands/IndexMatiAiCatalog.php
 M app/Http/Controllers/Superadmin/AiController.php
 M app/Services/AI/AiReviewService.php
 M app/Services/AI/CatalogAiService.php
 M app/Services/AI/MatiAiClient.php
 M compose.yaml
 M config/mati_ai.php
 M resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx
 M resources/js/lib/garmentAssistant.ts
 M routes/web.php
 M services/mati-ai/README.md
 M services/mati-ai/src/mati_ai/api/v1/routes.py
 M services/mati-ai/src/mati_ai/contracts/common.py
 M services/mati-ai/src/mati_ai/contracts/identification.py
 M services/mati-ai/src/mati_ai/contracts/vision.py
 M services/mati-ai/src/mati_ai/core/model_manager.py
 M services/mati-ai/src/mati_ai/identification/matching.py
 M services/mati-ai/src/mati_ai/vision/color.py
?? DOCUMENTACION/IA/IMPLEMENTACION/03_colorimetria_prendas.md
?? DOCUMENTACION/IA/IMPLEMENTACION/04_embeddings_pgvector.diff
?? DOCUMENTACION/IA/IMPLEMENTACION/04_embeddings_pgvector.md
?? DOCUMENTACION/IA/IMPLEMENTACION/05_compatibilidad_cromatica.diff
?? DOCUMENTACION/IA/IMPLEMENTACION/05_compatibilidad_cromatica.md
?? app/Models/AiProductEmbedding.php
?? app/Services/AI/CatalogEmbeddingStore.php
?? app/Services/AI/ColorimetryCatalogGuard.php
?? app/Services/AI/GarmentCompatibilityService.php
?? database/migrations/2026_10_03_000001_create_ai_product_embeddings_table.php
?? database/pgsql/
?? services/mati-ai/src/mati_ai/compatibility/
?? services/mati-ai/src/mati_ai/contracts/colorimetry.py
?? services/mati-ai/src/mati_ai/contracts/compatibility.py
?? services/mati-ai/src/mati_ai/contracts/embedding.py
?? services/mati-ai/src/mati_ai/vision/color_distance.py
?? services/mati-ai/src/mati_ai/vision/segmentation.py
```

Estos cambios previos se preservan. La comparación final deberá distinguir únicamente este documento y los registros creados por la UI de cualquier trabajo ajeno ya existente.


## PROBLEMAS DETECTADOS DURANTE LA CARGA

### Carga de fotografía bloqueada por la extensión de Chrome

Producto preparado: Premier PR200, Men's Long Sleeve Poplin Shirt.

Fuente individual: https://www.premierworkwear.com/en-gb/catalogue/product/pr200-mens-long-sleeve-poplin-shirt?c=1110

Campo: imagen principal obligatoria, usando el flujo normal del formulario y selector de archivos. Archivo oficial frontal disponible: PR200BOTT_0.jpg (439.169 bytes), extraído sin modificación del ZIP público oficial; color comercial Bottle, equivalente existente VERDE BOTELLA. Talla M aparece en la tabla oficial (15.5). Precio previsto 150 BOB, precio local de catálogo/demo; stock operativo previsto 1, TIENDA, sin comprometido.

Mensaje mostrado por la herramienta de navegador: To enable file upload, go to chrome://extensions in Google Chrome, click Details under the ChatGPT extension, and enable "Allow access to file URLs." fileChooser.setFiles failed.

El usuario informó que habilitó el permiso y autorizó continuar en Chrome. La misma restricción persistió tras un nuevo intento y tras actualizar el formulario. Se intentó comprobar la alternativa del selector nativo de archivos, pero el control de computadora terminó antes de cualquier entrada nativa porque no pudo verificar la URL actual del navegador con suficiente confianza para aplicar sus controles de seguridad. No se ha pulsado Guardar prenda para PR200 y no se cuenta como producto creado ni como intento fallido de guardado del sistema.

El error de descarga "Download 614 failed" no significó ausencia del archivo: el ZIP completo se confirmó posteriormente en Downloads. No se evadieron restricciones de acceso, login ni protecciones del proveedor.

### Modelo ausente en build servido

El campo modelo_pre no está presente en el formulario servido. Se mantiene la alternativa segura por UI de Código adicional (cob_prv), como referencia externa y separado del SKU automático. No se corrigió código ni se compiló el proyecto.

### Marca real añadida

Se creó mediante Otra marca > Crear y seleccionar el maestro PREMIER, inexistente en el catálogo inicial, después de verificar el producto, la licencia pública y el ZIP oficial. No se crearon categorías, colores ni tallas. Esta marca nueva no equivale a una prenda creada: la cantidad nueva de prendas continúa en 0.

## Investigación de candidatos del primer lote (NO registrados)

Fecha de consulta para todas las fichas: 03/10/2026. Fuente: catálogo oficial Premier Workwear. Las siguientes referencias son candidatos de investigación y no suman al objetivo.

| Referencia | Nombre original | URL individual | Evidencia relevante |
| --- | --- | --- | --- |
| PR200 | Men's Long Sleeve Poplin Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr200-mens-long-sleeve-poplin-shirt?c=1110 | Manga larga, cuello formal, bolsillo izquierdo, puños ajustables; Bottle publicado y packshot original descargado; M en tabla oficial. |
| PR202 | Men's Short Sleeve Poplin Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr202-mens-short-sleeve-poplin-shirt?c=1110 | Manga corta, cuello formal y bolsillo; Pink, Purple y otros colores; S/M/L en tabla oficial. |
| PR203 | Men's Long Sleeve Recyclight Poplin Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr203-mens-long-sleeve-recyclight-poplin-shirt?c=1126 | Manga larga, pinzas traseras; White y Black; S/M/L. |
| PR244 | Men's Stretch Fit Cotton Poplin Long Sleeve Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr244-mens-stretch-fit-cotton-poplin-long-sleeve-shirt?c=1115 | Entallada, cuello cutaway y mangas enrollables; Black, Silver, White, Pale Blue; S/M/L. |
| PR258 | Men's Banded Collar Grandad Long Sleeve Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr258-mens-banded-collar-grandad-long-sleeve-shirt?c=1126 | Cuello de banda, puño dos botones; White y Black; S/M/L. |
| PR204 | Men's Long Sleeve Fitted Poplin Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr204-mens-long-sleeve-fitted-poplin-shirt?c=1115 | Entallada, cuello formal; White y Black; S/M/L en tabla. |
| PR207 | Men's Poplin Supreme Long Sleeve Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr207-mens-poplin-supreme-long-sleeve-shirt?c=1110 | Manga larga semientallada; Black y White; S/M/L en tabla. |
| PR209 | Men's Poplin Supreme Short Sleeve Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr209-mens-poplin-supreme-short-sleeve-shirt?c=1115 | Manga corta semientallada; Black y White; S/M/L en tabla. |
| PR234 | Men's Long Sleeve Signature Oxford Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr234-mens-long-sleeve-signature-oxford-shirt?c=1126 | Oxford, cuello abotonado, bolsillo; Black, Light Blue, White; S/M/L en tabla. |
| PR236 | Men's Short Sleeve Signature Oxford Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr236-mens-short-sleeve-signature-oxford-shirt?c=1126 | Oxford manga corta, bolsillo; Black, Light Blue, White; S/M/L en tabla. |
| PR238 | Men's Cotton Rich Oxford Stripes Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr238-mens-cotton-rich-oxford-stripes-shirt?c=1126 | Rayas, cuello abotonado; White/Oxford Blue, Oxford Blue, White/Pink; S/M/L. |
| PR284 | Supreme Oxford Men's Long Sleeve Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr284-supreme-oxford-mens-long-sleeve-shirt | Oxford manga larga, pinzas; Black, White, Navy, Oxford Blue, Steel; S/M/L. |
| PR286 | Supreme Oxford Men's Short Sleeve Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr286-supreme-oxford-mens-short-sleeve-shirt?c=1126 | Oxford manga corta, pinzas; Black, White, Navy, Oxford Blue, Steel; S/M/L. |
| PR220 | Men's Long Sleeve Gingham Cotton Microcheck Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr220-mens-long-sleeve-gingham-cotton-microcheck-shirt?c=1126 | Microcuadros; Navy/White, Light Blue/White, Black/White, Red/White; S/M/L. |
| PR250 | Mulligan Check Men's Long Sleeve Cotton Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr250-mulligan-check--mens-long-sleeve-cotton-shirt?c=1126 | Cuadros y mangas enrollables; Red/Navy, Camel/Navy, Steel/Black, White/Navy; S/M/L. |
| PR221 | Men's Short Sleeve Gingham Cotton Microcheck Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr221-mens-short-sleeve-gingham-cotton-microcheck-shirt?c=1112 | Microcuadros manga corta; Navy/White, Light Blue/White, Black/White, Red/White; S/M/L. |
| PR246 | Men's Stretch Fit Cotton Poplin Short Sleeve Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr246-mens-stretch-fit-cotton-poplin-short-sleeve-shirt?c=1115 | Entallada de manga corta; White, Black, Silver; S/M/L. |
| PR252 | Maxton Check Men's Long Sleeve Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr252-maxton-check---mens-long-sleeve-shirt?c=1117 | Gingham manga larga; Light Blue/White, Navy/White, Silver/White, Steel/Black, Black/Red; S/M/L. |
| PR222 | Men's Jeans Stitch Denim Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr222-mens-jeans-stitch-denim-shirt?c=1113 | Denim, costuras en contraste, bolsillo abotonado; Black Denim e Indigo Denim; S/M/L. |
| PR210 | Men's Long Sleeve Security Shirt | https://www.premierworkwear.com/en-gb/catalogue/product/pr210-mens-long-sleeve-security-shirt?c=18000 | Manga larga, trabillas hombros y bolsillos; Black, White, Light Blue; S/M/L en tabla. |
| PR212 | Men's Short Sleeve Security Shirt | https://premierworkwear.com/en-gb/catalogue/product/pr212-mens-short-sleeve-security-shirt?c=1126 | Manga corta, trabillas hombros y bolsillos; Black, White, Light Blue; S/M/L en tabla. |

Productos descartados por duplicidad: 0 hasta el momento. Productos rechazados por el guardado de Mati: 0; el primer producto está bloqueado antes de cargar portada. Variantes nuevas: 0. Imágenes añadidas al sistema: 0.


## Cierre de esta ejecución: objetivo pendiente

La carga quedó interrumpida por un bloqueo de herramientas. No se alcanzaron las 100 prendas nuevas exigidas.

- Cantidad inicial: **14 prendas**.
- Cantidad nueva añadida: **0 prendas**.
- Cantidad final comprobada: **14 prendas**.
- Verificación final: 03/10/2026, 19:26 America/La_Paz, consulta de solo lectura `App\Models\Prenda::count()` mediante Artisan Tinker. Resultado: 14. No se ejecutaron escrituras por SQL, ORM ni API.
- Categorías utilizadas para prendas nuevas: ninguna; cantidad 0 en todas las categorías.
- Marcas utilizadas para prendas nuevas: ninguna; cantidad 0. Únicamente se creó el maestro real PREMIER por el formulario de marca; no contiene prendas nuevas de esta tarea.
- Colores principales de prendas nuevas: ninguno; cantidad 0.
- Variantes creadas: 0.
- Imágenes añadidas al sistema: 0. Las fotografías oficiales descargadas son material de preparación y no equivalen a imágenes registradas.
- Descartados por duplicidad: 0.
- Productos rechazados al guardar: 0. PR200 quedó bloqueado antes de subir la imagen principal y nunca se pulsó Guardar prenda.

El control nativo devolvió que Computer Use debía terminar esta ejecución porque no podía determinar la URL actual del navegador en Windows con suficiente confianza para aplicar sus controles de seguridad. No se realizó entrada nativa ni se cambiaron permisos por automatización. El usuario había confirmado el permiso de archivos locales de Chrome; aun así, la herramienta de carga continuó indicando `fileChooser.setFiles failed` y pidiendo ese permiso. Este es un bloqueo del entorno de automatización, no una validación de guardado de Mati Boutique.

En Mati Boutique se observó que el formulario servido no muestra el campo de modelo/referencia presente en el código fuente más reciente. No se corrigió código ni se recompilaron assets; se identificó Código adicional como campo alternativo disponible. No se encontraron otros errores de guardado porque no se llegó a enviar el producto.

Estado Git final: se mantienen los cambios previos enumerados en este documento. La única incorporación de archivos de esta tarea es `DOCUMENTACION/DATOS/catalogo_real_100_prendas.md`, representada por `?? DOCUMENTACION/DATOS/` en `git status --short`. No se hizo commit ni push.

Confirmaciones: no se utilizó Mati AI, FastAPI, OCR, análisis automático de fotografías, extracción automática de color, embeddings, entrenamiento ni generación de productos sintéticos. No se modificó código, no se ejecutaron migraciones ni seeders, no se eliminaron prendas existentes y no se modificaron ventas, reservas ni pagos existentes.


# IMPORTACIÓN V2

Fecha de inicio: 03/10/2026, America/La_Paz. Esta fase responde a la nueva autorización expresa para construir y ejecutar un importador Laravel. Se conserva toda la investigación y el resultado de la ejecución anterior; las restricciones anteriores sobre edición de código/carga exclusiva por formulario fueron reemplazadas para esta fase por la nueva solicitud.

## Inspección y estado inicial V2

Cantidad inicial verificada por lectura Laravel y por la UI administrativa: **14 prendas**, IDs 1–14. Marcas existentes: 12, incluyendo PREMIER creada en la ejecución anterior. Categorías existentes: 11. No se crean nuevos maestros automáticamente.

Se guardó una lectura operacional en `storage/app/catalog-import/baseline_v2.json` con huellas SHA-256 de las prendas existentes y sus variantes/imágenes/inventarios, ventas, reservas, pagos, usuarios, roles y Kardex histórico. Se conservará para comprobar que la carga no altera esos registros. El Kardex puede recibir entradas nuevas del stock inicial mediante el servicio normal; los movimientos históricos deben permanecer idénticos.

## Implementación del importador

Comando: `php artisan catalog:import-real <manifest> --dry-run` y, después de validación satisfactoria, el mismo comando sin `--dry-run`.

Archivos de código nuevos: `app/Console/Commands/ImportRealCatalog.php`, `app/Services/CatalogRealImportService.php` y `tests/Feature/CatalogRealImportTest.php`. Cambio mínimo en `app/Services/Superadmin/Resources/ProductCatalogService.php`: parámetro opcional para excluir expresamente `AiReviewService` de la importación; el formulario conserva su comportamiento predeterminado.

El adaptador reutiliza `ResourceValidationService::validateWrite`, `ResourceWriteService::normalizePayload`, `ProductCatalogService::createProductRecord` y `ProductImageService`. La generación de SKU, variantes, inventario, imágenes y entradas iniciales de Kardex sigue la lógica existente. No hay inserciones directas de prendas mediante SQL ni duplicación del dominio comercial.

La validación comprueba taxonomías activas existentes, nombre, precio, referencia, combinaciones de variantes, talla publicada compatible, inventario, ubicación, imagen local válida, extensión y procedencia. Las imágenes requieren al menos 200 píxeles por lado, respetan el límite normal de 6 MB y no pueden escapar del directorio `storage/app/catalog-import/images`. Los derechos `prohibited` se rechazan. Los derechos `unknown` producen advertencia y se conservan como referencia/dataset de desarrollo, sin afirmar licencia comercial.

La importación usa una transacción por prenda y continúa después de un fallo. El dominio limpia archivos al revertir; también se limpia la imagen de una prenda si falla el comprobante de procedencia. La clave estable se basa en marca + referencia real, o marca + URL cuando no hay referencia. Se comprueba contra referencias existentes y contra recibos transaccionales en `SystemLog`; el reporte mantiene la procedencia completa aunque se elimine el JSON operacional después de la importación.

El manifiesto, los candidatos, las fotografías y reportes se guardan en `storage/app/catalog-import/`. La regla existente de `storage/app/.gitignore` ya excluye estas rutas de Git, comprobado con `git check-ignore`; no fue necesario cambiar `.gitignore`.

## Precios e inventario demo

`price_origin = demo_bob`. Los precios no son precios oficiales de Bolivia: se calculan de forma determinista en pasos de 10 BOB dentro de las bandas solicitadas según categoría, con SHA-256 de marca + referencia/URL. El stock previsto es 1 por variante en TIENDA. El dominio fija `cre_inv = 0`. No se inventan SKU externos, GTIN ni barcode; la referencia se conserva en `modelo_pre` cuando la columna existe, y en manifiesto/reporte/procedencia.

## Validación inicial del importador

Primer dry-run operacional: 28 candidatos, 28 válidos, 0 inválidos, 0 duplicados, 0 importados, 28 advertencias por derechos unknown. Conteo real intacto: 14 → 14.

Pruebas: `php artisan test --compact tests/Feature/CatalogRealImportTest.php tests/Feature/InventoryP0RegressionTest.php`: **14 pruebas correctas, 69 aserciones**. Se ejecutan con SQLite en memoria y storage temporal/fake, no sobre el catálogo real. Incluyen fixtures exclusivos de pruebas que no se importan al dataset real.

La carga global de PHPUnit por `--filter` se interrumpió por un error preexistente en `tests/Feature/MatiAiWorkflowTest.php`: un método `run()` intenta sobrescribir un método final de PHPUnit. Se ejecutaron directamente los archivos relevantes; no se corrigió ni se ejecutó el módulo Mati AI.

## Investigación V2 en curso

Las fichas individuales que presentan challenge/Access Denied no se reintentan mediante técnicas de evasión. Cuando un listado oficial público normal aporta datos suficientes, se conserva esa URL como fuente, además de la URL individual observada y la incidencia. No se ejecutan OCR, extracción automática de color, modelos IA, embeddings ni scraping del módulo mati-ai.

El cierre con cifras reales y muestras UI se añadirá después de completar la investigación e importación.

## Cierre IMPORTACIÓN V2

Fecha de cierre: 04/10/2026 00:41 (America/La_Paz).

**Objetivo cumplido: 120 prendas nuevas realmente persistidas.**

| Métrica | Resultado |
| --- | ---: |
| Prendas iniciales | 14 |
| Candidatos investigados y documentados V2 | 129 |
| Candidatos técnicamente válidos en el preflight completo | 129 |
| Candidatos seleccionados para el manifiesto final | 120 |
| Prendas importadas | 120 |
| Total final | 134 |
| Variantes nuevas | 120 |
| Fotografías añadidas al catálogo | 120 |
| Duplicados omitidos durante importación | 0 |
| Fallos de importación real | 0 |
| Candidatos válidos en reserva, no importados | 9 |

### Distribución por marca (prendas nuevas)

| Valor | Cantidad |
| --- | ---: |
| BERSHKA | 12 |
| COS | 12 |
| HUGO BOSS | 12 |
| MANGO | 14 |
| MASSIMO DUTTI | 14 |
| PULL&BEAR | 14 |
| RALPH LAUREN | 9 |
| STRADIVARIUS | 14 |
| ZARA | 19 |

### Distribución por categoría (prendas nuevas)

| Valor | Cantidad |
| --- | ---: |
| ABRIGOS | 10 |
| BLAZERS | 10 |
| BLUSAS | 8 |
| CAMISAS | 19 |
| CONJUNTOS | 3 |
| FALDAS | 7 |
| PANTALONES | 14 |
| POLERA | 14 |
| POLOS Y KNITWEAR | 17 |
| VESTIDOS | 18 |

### Distribución por color principal (variantes nuevas)

| Valor | Cantidad |
| --- | ---: |
| AMARILLO PASTEL | 1 |
| ARENA | 1 |
| AZUL CIELO | 1 |
| AZUL MARINO | 10 |
| AZUL PETROLEO | 2 |
| AZUL REY | 4 |
| BEIGE | 6 |
| BLANCO | 6 |
| BLANCO HUESO | 5 |
| BORGONA | 6 |
| CAMEL | 1 |
| CAQUI | 1 |
| CELESTE | 7 |
| CHOCOLATE | 5 |
| CIRUELA | 2 |
| CREMA | 6 |
| FUCSIA | 1 |
| GRIS | 6 |
| GRIS CLARO | 1 |
| MARRÓN | 6 |
| MARRÓN CLARO | 1 |
| MORADO | 1 |
| NEGRO | 15 |
| ROJO | 3 |
| ROJO CARMÍN | 1 |
| ROSA BEBÉ | 2 |
| ROSA PALO | 2 |
| TAUPE | 6 |
| VERDE BOSQUE | 1 |
| VERDE BOTELLA | 1 |
| VERDE MENTA | 1 |
| VERDE OLIVA | 6 |
| VERDE SALVIA | 2 |

### Imágenes por estado de derechos

| Valor | Cantidad |
| --- | ---: |
| unknown | 120 |
| provider_authorized | 0 |
| public_domain | 0 |
| licensed | 0 |
| prohibited | 0 |

### Dominios de las fichas importadas

| Valor | Cantidad |
| --- | ---: |
| shop.mango.com | 14 |
| www.bershka.com | 12 |
| www.cos.com | 11 |
| www.hugoboss.com | 12 |
| www.massimodutti.com | 14 |
| www.menswearonline.co.uk | 9 |
| www.pullandbear.com | 14 |
| www.stradivarius.com | 14 |
| www.zara.com | 19 |
| www2.hm.com | 1 |

### Verificación del dominio y preservación de datos

Entorno confirmado: Laravel 13.1.1, PHP 8.5.5, APP_ENV local, localhost y timezone America/La_Paz. La importación usó el dominio Laravel real y no dependió del build React antiguo.
Las prendas originales y sus imágenes/variantes/inventarios permanecen idénticos según la huella del estado inicial: sí.
Las huellas de ventas, detalles, reservas, pagos, usuarios, roles y filas originales del Kardex fueron comparadas. Solo las entradas iniciales de las variantes nuevas se agregaron mediante el servicio normal. No se reescribieron movimientos históricos.
Stock disponible de las variantes nuevas: 120. Stock reservado nuevo total: 0. Problemas de almacenamiento: 0. Todas las imágenes finales están bajo productos/{id}/ en el disco public.
La columna modelo_pre no está aplicada en la base actual. No se ejecutaron migraciones: las referencias externas se conservaron en nombres, manifiesto, reporte y comprobantes transaccionales de procedencia. No se usó cob_prv como barcode inventado.

- ventas: 12 registros históricos; sin cambios = sí; total actual = 12.
- detalle_ventas: 13 registros históricos; sin cambios = sí; total actual = 13.
- reservas: 9 registros históricos; sin cambios = sí; total actual = 9.
- detalle_reservas: 10 registros históricos; sin cambios = sí; total actual = 10.
- venta_pagos: 7 registros históricos; sin cambios = sí; total actual = 7.
- reserva_pagos: 13 registros históricos; sin cambios = sí; total actual = 13.
- users: 8 registros históricos; sin cambios = sí; total actual = 8.
- roles: 3 registros históricos; sin cambios = sí; total actual = 3.
- inventario_movimientos: 20 registros históricos; sin cambios = sí; total actual = 140.

### Comandos ejecutados

```sh
php artisan catalog:import-real storage/app/catalog-import/catalog_candidates_v2.json --dry-run --report=storage/app/catalog-import/candidates_validation_report.json
php artisan catalog:import-real storage/app/catalog-import/catalog_real_100.json --dry-run
php artisan catalog:import-real storage/app/catalog-import/catalog_real_100.json
php artisan catalog:import-real storage/app/catalog-import/catalog_real_100.json --report=storage/app/catalog-import/idempotency_report.json
php artisan test --compact tests/Feature/CatalogRealImportTest.php tests/Feature/InventoryP0RegressionTest.php
```

Ejecutados mediante docker compose exec -T laravel.test. La repetición real es idempotente: 0 prendas adicionales, todos los registros del manifiesto omitidos por la identidad persistida, sin nuevos archivos comerciales.

Pruebas finales relevantes: 16 correctas, 88 aserciones. Revisión independiente del importador realizada: el riesgo encontrado de sobrescribir entradas mediante --report fue corregido y cubierto por una prueba; se permite solo un archivo *_report.json en catalog-import distinto del manifiesto activo.

### Muestras aleatorias revisadas en la UI

Selección reproducible: mt_srand(20261004), 10 IDs aleatorios entre las prendas importadas. Se revisó nombre, imagen cargada, marca, categoría, precio, variante, talla y stock en /superadmin/catalogo/prendas/{id}.

| ID | Nombre | Marca | Categoría | Precio BOB demo | Color | Talla | Stock | UI |
| ---: | --- | --- | --- | ---: | --- | --- | ---: | --- |
| 41 | CAMISETA ASIMÉTRICA LENTEJUELAS 5063-303-712 | ZARA | POLERA | 150.00 | CREMA | M | 1 | comprobado |
| 49 | BLUSA RÚSTICA MANGA ABULLONADA 1521-494-902 | BERSHKA | BLUSAS | 150.00 | ROSA BEBÉ | M | 1 | comprobado |
| 62 | BLUSA BAMBI VOLANTES 37095879 | MANGO | BLUSAS | 250.00 | MARRÓN | M | 1 | comprobado |
| 89 | VESTIDO CORTO CUELLO MAO ALAMARES 7380339800 | PULL&BEAR | VESTIDOS | 370.00 | NEGRO | M | 1 | comprobado |
| 94 | CAZADORA EFECTO PIEL CUELLO ALTO 7720323776 | PULL&BEAR | ABRIGOS | 670.00 | MARRÓN | M | 1 | comprobado |
| 105 | CAMISA DE PUNTO MERINO AZUL MARINO 1293216001 | COS | CAMISAS | 130.00 | AZUL MARINO | M | 1 | comprobado |
| 110 | BLAZER COS NEGRO FLUIDO DE CREPE 1279060001 | COS | BLAZERS | 430.00 | NEGRO | S | 1 | comprobado |
| 114 | CONJUNTO BOSS PIQUE VERDE OSCURO CON LOGOS BORDADOS HBEU50569918_398 | HUGO BOSS | CONJUNTOS | 650.00 | VERDE OLIVA | M | 1 | comprobado |
| 120 | PANTALON BOSS MARRON CLARO DE ESPIGA Y CACHEMIRA HBEU50570707_342 | HUGO BOSS | PANTALONES | 440.00 | MARRÓN CLARO | 46 | 1 | comprobado |
| 127 | CAMISA RALPH LAUREN LINO CELESTE MANGA LARGA 710966294509 | RALPH LAUREN | CAMISAS | 230.00 | CELESTE | M | 1 | comprobado |

### Procedencia de todos los productos importados

| ID Mati | Marca | Nombre | Referencia externa | Fuente | Imagen original | Derechos |
| ---: | --- | --- | --- | --- | --- | --- |
| 15 | STRADIVARIUS | VESTIDO MIDI ASIMETRICO CUADROS 06383188 | 06383188 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/808d/313c/3d874b759432/6cce91cb7c01/06383188450-c1/06383188450-c1.jpg?ts=1785739052460&w=746) | unknown |
| 16 | STRADIVARIUS | VESTIDO MIDI TEXTURA HALTER 06360168 | 06360168 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/0ae5/1eb6/70284e09aa59/f22e8cb09364/06360168650-c1/06360168650-c1.jpg?ts=1783604015954&w=746) | unknown |
| 17 | STRADIVARIUS | VESTIDO MIDI ENTALLADO ESCOTE CUADRADO 02382666 | 02382666 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/8014/3a01/f1a246169f8d/dd50588d7cb7/02382666310-c1/02382666310-c1.jpg?ts=1780410965113&w=746) | unknown |
| 18 | STRADIVARIUS | VESTIDO CORTO CUELLO MAO CUADROS 06395286 | 06395286 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/409d/cca6/5ffb4fa1ab62/8a3c3723b910/06395286001-c1/06395286001-c1.jpg?ts=1790178684581&w=746) | unknown |
| 19 | STRADIVARIUS | PANTALON BARREL DE VESTIR 04505140 | 04505140 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/5498/7e77/e03d41909415/d27e24c14a0a/04505140660-c1/04505140660-c1.jpg?ts=1788347115554&w=746) | unknown |
| 20 | STRADIVARIUS | PANTALON FLUIDO RECTO VESTIR CINTURON 04020175 | 04020175 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/8415/8b7c/95eb4dcb8d8e/488277c91b19/04020175456-c1/04020175456-c1.jpg?ts=1786376404714&w=746) | unknown |
| 21 | STRADIVARIUS | TRENCH FLUIDA CORTA VESTIR 05774647 | 05774647 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/3397/7c81/a9194279b202/c6405270d5ba/05774647550-c1/05774647550-c1.jpg?ts=1788858217033&w=746) | unknown |
| 22 | STRADIVARIUS | JERSEY PUNTO ASIMETRICO BAJO RIB 01645702 | 01645702 | [Ficha](https://www.stradivarius.com/es/v/vestidos-viscosa) | [Foto](https://static.e-stradivarius.net/assets/public/d01b/8db2/f691435f8ffd/6b540764c7dc/01645702001-c1/01645702001-c1.jpg?ts=1790668936488&w=746) | unknown |
| 23 | STRADIVARIUS | AMERICANA RECTA BOTONES 05902210 | 05902210 | [Ficha](https://www.stradivarius.com/es/v/vestido-tipo-blazer) | [Foto](https://static.e-stradivarius.net/assets/public/f7ed/2eaa/7c144b6d9722/1c9b4a6ad4b1/05902210400-c1/05902210400-c1.jpg?ts=1788763952324&w=746) | unknown |
| 24 | STRADIVARIUS | AMERICANA REGULAR PATA DE GALLO 01900174 | 01900174 | [Ficha](https://www.stradivarius.com/es/v/vestido-tipo-blazer) | [Foto](https://static.e-stradivarius.net/assets/public/6c82/517b/afdb4f0dad17/c7ad3454279a/01900174200-c1/01900174200-c1.jpg?ts=1784216676732&w=746) | unknown |
| 25 | STRADIVARIUS | TOP CROPPED ENTALLADO ESTAMPADO 06599627 | 06599627 | [Ficha](https://www.stradivarius.com/es/v/tops-estampados-para-mujer) | [Foto](https://static.e-stradivarius.net/assets/public/54e1/3c2d/be9e4af18283/507086c49bea/06599627650-c1/06599627650-c1.jpg?ts=1784284098154&w=746) | unknown |
| 26 | STRADIVARIUS | TOP ESTAMPADO VOLANTES 06606580 | 06606580 | [Ficha](https://www.stradivarius.com/es/v/tops-estampados-para-mujer) | [Foto](https://static.e-stradivarius.net/assets/public/1eff/fde4/c4e74312b2f8/61d77ccb2f75/06606580004-c1/06606580004-c1.jpg?ts=1780901581675&w=746) | unknown |
| 27 | STRADIVARIUS | CAMISETA MANGA CORTA ESTAMPADO 08071695 | 08071695 | [Ficha](https://www.stradivarius.com/es/v/tops-estampados-para-mujer) | [Foto](https://static.e-stradivarius.net/assets/public/bec9/fae5/83be4161b133/dc84a80033d0/08071695101-c1/08071695101-c1.jpg?ts=1784216684322&w=746) | unknown |
| 28 | STRADIVARIUS | CAMISETA PRINT 08045525 | 08045525 | [Ficha](https://www.stradivarius.com/es/v/tops-estampados-para-mujer) | [Foto](https://static.e-stradivarius.net/assets/public/01ea/3d49/751b4dc6a6e7/550902b76e9a/08045525508-c1/08045525508-c1.jpg?ts=1781606263143&w=746) | unknown |
| 29 | ZARA | CAMISA GASA BORDADOS Y VOLANTES 0881-016-251 | 0881/016/251 | [Ficha](https://www.zara.com/mx/es/camisa-gasa-bordados-y-volantes-p00881016.html) | [Foto](https://static.zara.net/assets/public/c273/5ffd/ca024e3e9bb5/e991b433a679/00881016251-p/00881016251-p.jpg?ts=1772186707918&w=375) | unknown |
| 30 | ZARA | VESTIDO COMBINADO RAYAS 1165-050-800 | 1165/050/800 | [Ficha](https://www.zara.com/mx/es/vestido-combinado-rayas-p01165050.html) | [Foto](https://static.zara.net/assets/public/0292/6bb7/817f4bdc91ad/07aefb067e9c/01165050800-p/01165050800-p.jpg?ts=1769783278249&w=375) | unknown |
| 31 | ZARA | CAMISA CUADROS CUELLO BOBO 2181-538-049 | 2181/538/049 | [Ficha](https://www.zara.com/mx/es/camisa-cuadros-cuello-bobo-p02181538.html) | [Foto](https://static.zara.net/assets/public/3b4d/6acc/693e41ffbef2/2736665310e8/02181538049-p/02181538049-p.jpg?ts=1768841089678&w=375) | unknown |
| 32 | ZARA | JERSEY PUNTO DOBLE 1509-130-104 | 1509/130/104 | [Ficha](https://www.zara.com/mx/es/sueter-punto-doble-p01509130.html) | [Foto](https://static.zara.net/assets/public/59d6/335e/2bc84ad9abd7/d507fb1d7181/01509130104-p/01509130104-p.jpg?f=auto&ts=1773160609857&w=560) | unknown |
| 33 | ZARA | CAMISA RAYAS BORDADOS CONTRASTE 7446-405-802 | 7446/405/802 | [Ficha](https://www.zara.com/mx/es/camisa-rayas-bordados-contraste-p07446405.html) | [Foto](https://static.zara.net/assets/public/8143/9c06/1b5f43d5b57e/95ab706acc76/07446405802-p/07446405802-p.jpg?ts=1767095302063&w=375) | unknown |
| 34 | ZARA | VESTIDO CORTO CUADROS VOLANTES CINTURÓN 2200-533-049 | 2200/533/049 | [Ficha](https://www.zara.com/mx/es/vestido-corto-cuadros-olanes-cinturon-p02200533.html) | [Foto](https://static.zara.net/assets/public/09a2/956b/55ec489487a5/0ddbecef4752/02200533049-p/02200533049-p.jpg?ts=1768841107821&w=375) | unknown |
| 35 | ZARA | CAMISETA TÉCNICA ESTRUCTURA 4387-305-611 | 4387/305/611 | [Ficha](https://www.zara.com/mx/es/camiseta-tecnica-estructura-p04387305.html) | [Foto](https://static.zara.net/assets/public/3ae8/ae6b/f2294fcfa697/9d801eff0518/04387305611-p/04387305611-p.jpg?ts=1780494164911&w=375) | unknown |
| 36 | ZARA | CAMISETA RUNNING ALGODÓN 6224-344-682 | 6224/344/682 | [Ficha](https://www.zara.com/mx/es/camiseta-running-algodon-p06224344.html) | [Foto](https://static.zara.net/assets/public/81eb/79b1/f1484fc0bf92/5d69eccd8f2f/06224344682-e1/06224344682-e1.jpg?ts=1781018868446&w=375) | unknown |
| 37 | ZARA | CAMISETA BORDADO SÁNDWICH 6224-687-730 | 6224/687/730 | [Ficha](https://www.zara.com/mx/es/playera-bordado-sandwich-p06224687.html) | [Foto](https://static.zara.net/assets/public/b407/8498/9649450aaf71/9a8968f7c987/06224687730-p/06224687730-p.jpg?ts=1775566961216&w=375) | unknown |
| 38 | ZARA | VESTIDO LARGO ALGODÓN 0858-042-712 | 0858/042/712 | [Ficha](https://www.zara.com/mx/es/vestido-largo-algodon-p00858042.html) | [Foto](https://static.zara.net/assets/public/29cf/bf93/ffc940538e6a/e5d3da28b121/00858042712-p/00858042712-p.jpg?ts=1775638365648&w=375) | unknown |
| 39 | ZARA | BLAZER MANGA CORTA 8164-599-401 | 8164/599/401 | [Ficha](https://www.zara.com/mx/es/blazer-manga-corta-p08164599.html) | [Foto](https://static.zara.net/assets/public/fe35/6460/620144809abe/a4d1ca5e488a/08164599401-p/08164599401-p.jpg?ts=1774618111867&w=375) | unknown |
| 40 | ZARA | JERSEY BÁSICO PUNTO MANGA CORTA 8851-008-250 | 8851/008/250 | [Ficha](https://www.zara.com/mx/es/sueter-basico-punto-manga-corta-p08851008.html) | [Foto](https://static.zara.net/assets/public/1d91/bdc6/c89e498ca2b5/443bfe0dfc99/08851008250-p/08851008250-p.jpg?ts=1772036355985&w=375) | unknown |
| 41 | ZARA | CAMISETA ASIMÉTRICA LENTEJUELAS 5063-303-712 | 5063/303/712 | [Ficha](https://www.zara.com/mx/es/playera-asimetrica-lentejuelas-p05063303.html) | [Foto](https://static.zara.net/assets/public/459c/4640/f7284b9ead68/3809abf7f13e/05063303712-p/05063303712-p.jpg?ts=1774966563928&w=375) | unknown |
| 42 | ZARA | POLO PUNTO LANA 6674-404-507 | 6674/404/507 | [Ficha](https://www.zara.com/mx/en/wool-knit-polo-shirt-p06674404.html) | [Foto](https://static.zara.net/assets/public/ef9f/5694/0d8b4b20a66d/d89a58d82bf8/06674404507-p/06674404507-p.jpg?ts=1769597054219&w=375) | unknown |
| 43 | ZARA | POLO RAYAS TEXTURA 1634-404-401 | 1634/404/401 | [Ficha](https://www.zara.com/mx/en/textured-striped-polo-shirt-p01634404.html) | [Foto](https://static.zara.net/assets/public/3da7/9918/f50245458c87/3ffa6af900da/01634404401-p/01634404401-p.jpg?ts=1777474774056&w=375) | unknown |
| 44 | ZARA | CAMISA ESTRUCTURA TEXTURA 4333-476-070 | 4333/476/070 | [Ficha](https://www.zara.com/mx/es/camisa-estructura-textura-p04333476.html) | [Foto](https://static.zara.net/assets/public/a4cb/4cfb/45ae4dfdba3f/95d6ed35722f/04333476070-p/04333476070-p.jpg?f=auto&ts=1785853915541&w=560) | unknown |
| 45 | ZARA | CAMISETA COMBINADA ENCAJE CUELLO PANADERO 4813-365-712 | 4813/365/712 | [Ficha](https://www.zara.com/mx/es/playera-combinada-encaje-cuello-panadero-p04813365.html) | [Foto](https://static.zara.net/assets/public/8c31/33f5/bebb4870b641/85576ba8c2b0/04813365712-p/04813365712-p.jpg?ts=1778771566091&w=375) | unknown |
| 46 | ZARA | BLAZER CON LANA CUELLO JOYA 3920-057-712 | 3920/057/712 | [Ficha](https://www.zara.com/mx/es/sueter-con-lana-cuello-joya-p03920057.html) | [Foto](https://static.zara.net/assets/public/9cf5/fa0f/926447ff83e8/8ce5ebd3e158/03920057712-p/03920057712-p.jpg?ts=1769176183400&w=375) | unknown |
| 47 | ZARA | BLAZER RELAXED FIT PIEL NAPA 2521-200-700 | 2521/200/700 | [Ficha](https://www.zara.com/mx/en/relaxed-fit-leather-blazer-p02521200.html) | [Foto](https://static.zara.net/assets/public/1e31/efa8/37f647b4a166/587c938336aa/02521200700-p/02521200700-p.jpg?ts=1772013053066&w=375) | unknown |
| 48 | BERSHKA | VESTIDO MIDI TIRANTES 3451-326-106 | 3451/326/106 | [Ficha](https://www.bershka.com/es/vestido-midi-tirantes-c0p232116898.html?colorId=106) | [Foto](https://static.bershka.net/assets/public/77c5/e2e0/dd19460384cf/3077fd989547/03451326106-h1/poster/poster.jpg?ts=1784791638805&w=850) | unknown |
| 49 | BERSHKA | BLUSA RÚSTICA MANGA ABULLONADA 1521-494-902 | 1521/494/902 | [Ficha](https://www.bershka.com/us/es/blusa-r%C3%BAstica-manga-abullonada-c0p209167396.html) | [Foto](https://static.bershka.net/assets/public/e977/df97/168a47df9cee/a9efaca1a4a1/01521494902-a4o/01521494902-a4o.jpg?f=auto&ts=1771928995704&w=850) | unknown |
| 50 | BERSHKA | CAZADORA EFECTO PIEL 0038-741-600 | 0038/741/600 | [Ficha](https://www.bershka.com/es/mujer/ropa/lo-m%C3%A1s-vendido/cazadoras-y-blazers/cazadora-efecto-piel-c1010598227p215063999.html?colorId=600) | [Foto](https://static.bershka.net/assets/public/1e44/4f24/e9ed4b269d94/832bca789b10/00038741600-a4o/00038741600-a4o.jpg?ts=1765377405988&w=850) | unknown |
| 51 | BERSHKA | BLAZER ENTALLADO CUADROS 2461-232-716 | 2461/232/716 | [Ficha](https://www.bershka.com/es/blazer-entallada-cuadros-c0p226931347.html) | [Foto](https://static.bershka.net/assets/public/93eb/c0c2/54304d98a91d/f6de6d31e3e1/02461232716-h1/poster/poster.jpg?ts=1787825539792&w=850) | unknown |
| 52 | BERSHKA | FALDA DENIM MIDI ASIMÉTRICA 3295-534-428 | 3295/534/428 | [Ficha](https://www.bershka.com/es/falda-denim-midi-asim%C3%A9trica-c0p227962165.html) | [Foto](https://static.bershka.net/assets/public/5dd5/f15e/c14b4e77b49b/6bc8a265e1e8/03295534428-h1/poster/poster.jpg?ts=1783591340667&w=850) | unknown |
| 53 | BERSHKA | BLUSA MANGA LARGA CRUZADA PAÑUELO 1711-692-106 | 1711/692/106 | [Ficha](https://www.bershka.com/ad/es/blusa-manga-larga-cruzada-pa%C3%B1uelo-c0p210198699.html) | [Foto](https://static.bershka.net/assets/public/6f5f/feeb/b6174e04b920/cff5bdae6563/01711692106-a4o/01711692106-a4o.jpg?f=auto&ts=1769099270377&w=850) | unknown |
| 54 | BERSHKA | CAZADORA EFECTO PIEL SOLAPAS 1410-768-505 | 1410/768/505 | [Ficha](https://www.bershka.com/ad/es/cazadora-efecto-piel-solapas-c0p207921139.html) | [Foto](https://static.bershka.net/assets/public/5818/e138/ac9649a4b884/d8f2fa854020/01410768505-a4o/01410768505-a4o.jpg?f=auto&ts=1774873927773&w=850) | unknown |
| 55 | BERSHKA | JERSEY CUELLO REDONDO TRENZADO 1484-663-902 | 1484/663/902 | [Ficha](https://www.bershka.com/ad/es/jersey-cuello-redondo-trenzado-c0p208739089.html) | [Foto](https://static.bershka.net/assets/public/5707/0adc/79c348e199f4/e9e15443fa52/01484663902-a4o/01484663902-a4o.jpg?f=auto&ts=1773158081325&w=850) | unknown |
| 56 | BERSHKA | JERSEY MANGA LARGA BARDOT 1432-071-812 | 1432/071/812 | [Ficha](https://www.bershka.com/ad/es/jersey-manga-larga-bardot-c0p220329734.html) | [Foto](https://static.bershka.net/assets/public/2688/6557/68e14a8a9c05/28a91669d366/01432071812-a4o/01432071812-a4o.jpg?f=auto&ts=1768463702787&w=850) | unknown |
| 57 | BERSHKA | CAMISA MANGA CORTA RÚSTICA 0999-721-505 | 0999/721/505 | [Ficha](https://www.bershka.com/ad/es/camisa-manga-corta-r%C3%BAstica-c0p229737074.html) | [Foto](https://static.bershka.net/assets/public/3f5e/c8ed/144644c6a921/a02367dbd909/00999721505-a4o/00999721505-a4o.jpg?f=auto&ts=1773996529948&w=850) | unknown |
| 58 | BERSHKA | CAMISA MANGA CORTA OVERSIZE BORDADO 1575-172-401 | 1575/172/401 | [Ficha](https://www.bershka.com/ad/es/camisa-manga-corta-oversize-bordado-c0p211722912.html) | [Foto](https://static.bershka.net/assets/public/ef83/2166/83fa42b7bdaa/febe63a0bfb1/01575172401-a4o/01575172401-a4o.jpg?f=auto&ts=1771946212771&w=850) | unknown |
| 59 | BERSHKA | CAMISA MANGA CAMPANA ENTALLADA 6278-692-205 | 6278/692/205 | [Ficha](https://www.bershka.com/ad/es/camisa-manga-campana-entallada-c0p209128260.html) | [Foto](https://static.bershka.net/assets/public/b6d7/8b2d/914d48d2a0f7/5bc484a64fb3/06278692205-a4o/06278692205-a4o.jpg?f=auto&ts=1759387001271&w=850) | unknown |
| 60 | MANGO | BLAZER TRAJE RECTA BOLSILLOS SOLAPA 27031298 | 27031298 | [Ficha](https://shop.mango.com/co/es/p/mujer/blazers/oversize/blazer-traje-recta-bolsillos-solapa/27031298/77/00) | [Foto](https://media.mango.com/is/image/punto/27031298-77-900?wid=2048) | unknown |
| 61 | MANGO | JERSEY PUNTO MANGA CORTA 37005868 | 37005868 | [Ficha](https://shop.mango.com/co/es/p/mujer/jerseis-y-cardigans/jerseis/jersey-punto-manga-corta/37005868/50/00) | [Foto](https://media.mango.com/is/image/punto/37005868-50-900?wid=2048) | unknown |
| 62 | MANGO | BLUSA BAMBI VOLANTES 37095879 | 37095879 | [Ficha](https://shop.mango.com/co/es/p/mujer/camisas-y-blusas/blusas/blusa-bambi-volantes/37095879/30/00) | [Foto](https://media.mango.com/is/image/punto/37095879-30-900?wid=2048) | unknown |
| 63 | MANGO | VESTIDO CORTO ENCAJE 37087782 | 37087782 | [Ficha](https://shop.mango.com/co/es/p/mujer/vestidos-y-monos/cortos/vestido-corto-encaje/37087782/99/01) | [Foto](https://media.mango.com/is/image/punto/37087782-99-900?wid=2048) | unknown |
| 64 | MANGO | VESTIDO COMBINADO PLISADO 37006013 | 37006013 | [Ficha](https://shop.mango.com/co/es/p/mujer/vestidos-y-monos/largos/vestido-combinado-plisado/37006013/76/00) | [Foto](https://media.mango.com/is/image/punto/37006013-76-900?wid=2048) | unknown |
| 65 | MANGO | CHAQUETA CUELLO CHIMENEA BOLSILLOS 37055847 | 37055847 | [Ficha](https://shop.mango.com/co/es/p/mujer/chaquetas/bombers/chaqueta-cuello-chimenea-bolsillos/37055847/08/00) | [Foto](https://media.mango.com/is/image/punto/37055847-08-900?wid=2048) | unknown |
| 66 | MANGO | VESTIDO LYOCELL CANALÉ LAZADA 37084079 | 37084079 | [Ficha](https://shop.mango.com/co/es/p/mujer/vestidos-y-monos/cortos/vestido-lyocell-canale-lazada/37084079/32/01) | [Foto](https://media.mango.com/is/image/punto/37084079-32-900?wid=2048) | unknown |
| 67 | MANGO | CHAQUETA DENIM BOLSILLOS 37084109 | 37084109 | [Ficha](https://shop.mango.com/co/es/p/mujer/chaquetas/cropped/chaqueta-denim-bolsillos/37084109/TS/00) | [Foto](https://media.mango.com/is/image/punto/37084109-TS-900?wid=2048) | unknown |
| 68 | MANGO | TRENCH DENIM CINTURÓN 37095868 | 37095868 | [Ficha](https://shop.mango.com/co/es/p/mujer/gabardinas-y-parkas/cropped/trench-denim-cinturon/37095868/TS/00) | [Foto](https://media.mango.com/is/image/punto/37095868-TS-900?wid=2048) | unknown |
| 69 | MANGO | TOP ESTAMPADO DETALLES DRAPEADOS 37025864 | 37025864 | [Ficha](https://shop.mango.com/co/es/p/mujer/tops/top-estampado-detalles-drapeados/37025864/99/00) | [Foto](https://media.mango.com/is/image/punto/37025864-99-900?wid=2048) | unknown |
| 70 | MANGO | PANTALÓN TRAJE TIRO ALTO 37075841 | 37075841 | [Ficha](https://shop.mango.com/co/es/p/mujer/pantalones/straight/pantalon-traje-tiro-alto/37075841/76/00) | [Foto](https://media.mango.com/is/image/punto/37075841-76-900?wid=2048) | unknown |
| 71 | MANGO | CHAQUETA TWEED CUADROS DETALLE FLECOS 37085876 | 37085876 | [Ficha](https://shop.mango.com/co/es/p/mujer/jerseis-y-cardigans/chaquetas/chaqueta-tweed-cuadros-detalle-flecos/37085876/56/00) | [Foto](https://media.mango.com/is/image/punto/37085876-56-900?wid=2048) | unknown |
| 72 | MANGO | TOP CUADROS FRUNCIDO LATERAL 37096730 | 37096730 | [Ficha](https://shop.mango.com/co/es/p/mujer/tops/chalecos/top-cuadros-fruncido-lateral/37096730/43/00) | [Foto](https://media.mango.com/is/image/punto/37096730-43-900?wid=2048) | unknown |
| 73 | MANGO | PANTALÓN SATINADO BAJO ABERTURAS 37036364 | 37036364 | [Ficha](https://shop.mango.com/co/es/p/mujer/pantalones/straight/pantalon-satinado-bajo-aberturas/37036364/05/00) | [Foto](https://media.mango.com/is/image/punto/37036364-05-900?wid=2048) | unknown |
| 74 | MASSIMO DUTTI | PANTALÓN SKINNY FLARE FIT TERCIOPELO 5041-741 | 5041/741 | [Ficha](https://www.massimodutti.com/es/pantalon-skinny-flare-fit-terciopelo-l05041741?pelement=67765661) | [Foto](https://static.massimodutti.net/assets/public/9658/d4ba/bf8f44d99503/3c49f51529c7/05041741800-o1/05041741800-o1.jpg?ts=1787312068191&f=auto) | unknown |
| 75 | MASSIMO DUTTI | PANTALÓN FLARE FIT PANA 5031-731 | 5031/731 | [Ficha](https://www.massimodutti.com/es/pantalon-flare-fit-pana-l05031731?pelement=64340062) | [Foto](https://static.massimodutti.net/assets/public/04d3/2e11/e6254646a3fe/e212686c479f/05031731717-o1/05031731717-o1.jpg?ts=1788265636179&f=auto) | unknown |
| 76 | MASSIMO DUTTI | PANTALÓN RECTO 100 LANA FRÍA 5021-685 | 5021/685 | [Ficha](https://www.massimodutti.com/es/pantalon-recto-100-lana-fria-l05021685?pelement=61819891) | [Foto](https://static.massimodutti.net/assets/public/b0de/8b62/f18246e68739/429cb4a5a3d6/05021685800-o1/05021685800-o1.jpg?ts=1782891059274&f=auto) | unknown |
| 77 | MASSIMO DUTTI | PANTALÓN BARREL FIT DETALLE TABLAS LATERALES 5044-744 | 5044/744 | [Ficha](https://www.massimodutti.com/es/pantalon-barrel-fit-detalle-tablas-laterales-l05044744?pelement=64530027) | [Foto](https://static.massimodutti.net/assets/public/84ca/cdaf/c69444ae98e9/29995827f98c/05044744830-o1/05044744830-o1.jpg?ts=1787916798816&f=auto) | unknown |
| 78 | MASSIMO DUTTI | PANTALÓN WIDE LEG RAYA DIPLOMÁTICA 5039-740 | 5039/740 | [Ficha](https://www.massimodutti.com/es/pantalon-wide-leg-raya-diplomatica-l05039740?pelement=64883153) | [Foto](https://static.massimodutti.net/assets/public/74cc/c5a4/0f5d49e28777/bc9d98b2fcec/05039740830-o1/05039740830-o1.jpg?ts=1790866234574&f=auto) | unknown |
| 79 | MASSIMO DUTTI | JERSEY PUNTO CUELLO REDONDO MEZCLA LANA 5713-594 | 5713/594 | [Ficha](https://www.massimodutti.com/es/jersey-punto-cuello-redondo-mezcla-lana-l05713594?pelement=62540513) | [Foto](https://static.massimodutti.net/assets/public/1f3b/cfe0/11ba40f7be0f/3bb754461d02/05713594414-o1/05713594414-o1.jpg?ts=1786967668944&f=auto) | unknown |
| 80 | MASSIMO DUTTI | JERSEY PUNTO TRENZADO CON LANA 5855-536 | 5855/536 | [Ficha](https://www.massimodutti.com/es/jersey-punto-trenzado-con-lana-l05855536?pelement=63834396) | [Foto](https://static.massimodutti.net/assets/public/f5fe/8440/b5c44d72809f/97bc57eb0003/05855536600-o1/05855536600-o1.jpg?ts=1790000771882&f=auto) | unknown |
| 81 | MASSIMO DUTTI | JERSEY MANGA CORTA DETALLE ROMBOS 5612-528 | 5612/528 | [Ficha](https://www.massimodutti.com/es/jersey-manga-corta-detalle-rombos-l05612528?pelement=61937687) | [Foto](https://static.massimodutti.net/assets/public/b0dd/f585/f52e4c5c9d6b/329b465378c1/05612528211-o1/05612528211-o1.jpg?ts=1783001980544&f=auto) | unknown |
| 82 | MASSIMO DUTTI | JERSEY PUNTO CUELLO CISNE RAYAS 5805-577 | 5805/577 | [Ficha](https://www.massimodutti.com/es/jersey-punto-cuello-cisne-rayas-l05805577?pelement=61734248) | [Foto](https://static.massimodutti.net/assets/public/6609/62e5/00494333b7f1/cac0514cf202/05805577401-o1/05805577401-o1.jpg?ts=1785141842304&f=auto) | unknown |
| 83 | MASSIMO DUTTI | CAMISA FLUIDA RAYAS DETALLE BOLSILLO 5188-588 | 5188/588 | [Ficha](https://www.massimodutti.com/es/camisa-fluida-rayas-detalle-bolsillo-l05188588?pelement=62812112) | [Foto](https://static.massimodutti.net/assets/public/f509/bbb1/7f5440dcbdb6/9dc92b466b8e/05188588250-o1/05188588250-o1.jpg?ts=1788158635361&f=auto) | unknown |
| 84 | MASSIMO DUTTI | BLUSA CUELLO SUBIDO 100 SEDA MORERA 5194-594 | 5194/594 | [Ficha](https://www.massimodutti.com/es/blusa-cuello-subido-100-seda-morera-l05194594?pelement=63985018) | [Foto](https://static.massimodutti.net/assets/public/86ff/c0a1/d4c748348d6b/e4c7ce6982d7/05194594464-o1/05194594464-o1.jpg?ts=1788438392410&f=auto) | unknown |
| 85 | MASSIMO DUTTI | CAMISA POPELÍN CUELLO MAO 5103-504 | 5103/504 | [Ficha](https://www.massimodutti.com/es/camisa-popelin-cuello-mao-l05103504?pelement=67743895) | [Foto](https://static.massimodutti.net/assets/public/b8d7/7ef3/20e147cd8d6e/ead2c6e91d80/05103504250-o1/05103504250-o1.jpg?ts=1777992712310&f=auto) | unknown |
| 86 | MASSIMO DUTTI | FALDA MIDI DETALLE PLIEGUE CUADROS 100 LANA 5691-511 | 5691/511 | [Ficha](https://www.massimodutti.com/es/falda-midi-detalle-pliegue-cuadros-100-lana-l05691511?pelement=63354223) | [Foto](https://static.massimodutti.net/assets/public/0e15/e4e2/1aeb44cb9a0e/f732213f1fe1/05691511210-o1/05691511210-o1.jpg?ts=1787729753616&f=auto) | unknown |
| 87 | MASSIMO DUTTI | FALDA MIDI VUELO DETALLE BOLSILLOS 6660-511 | 6660/511 | [Ficha](https://www.massimodutti.com/es/falda-midi-vuelo-detalle-bolsillos-l06660511?pelement=64530009) | [Foto](https://static.massimodutti.net/assets/public/72c2/fa84/142f4a7c9e6a/a38533f39343/06660511210-o1/06660511210-o1.jpg?ts=1788186052067&f=auto) | unknown |
| 88 | PULL&BEAR | VESTIDO CORTO TIRANTES RAYAS 7380408420 | 7380408420 | [Ficha](https://www.pullandbear.com/es/vestido-corto-tirantes-rayas-l07380408?cS=420&pelement=757014141) | [Foto](https://static.pullandbear.net/assets/public/a1d7/b5c6/e8894842ae3b/28a2a09fad24/07380408420-A6M/07380408420-A6M.jpg?ts=1781690567201&w=1390&f=auto) | unknown |
| 89 | PULL&BEAR | VESTIDO CORTO CUELLO MAO ALAMARES 7380339800 | 7380339800 | [Ficha](https://www.pullandbear.com/es/vestido-corto-cuello-mao-alamares-l07380339?cS=800&pelement=757635686) | [Foto](https://static.pullandbear.net/assets/public/2cec/25f0/b34c4961a4c0/e040cb1b184d/07380339800-A6M/07380339800-A6M.jpg?ts=1789399066062&w=1390&f=auto) | unknown |
| 90 | PULL&BEAR | VESTIDO CORTO TIRANTES LENTEJUELAS 3380317800 | 3380317800 | [Ficha](https://www.pullandbear.com/es/vestido-corto-tirantes-lentejuelas-l03380317?cS=800&pelement=755799413) | [Foto](https://static.pullandbear.net/assets/public/2a1d/7ba6/be094327b3b6/30243c2b341f/07380337800-A6M/07380337800-A6M.jpg?ts=1788875715884&w=1390&f=auto) | unknown |
| 91 | PULL&BEAR | CAMISA CUADROS MANGA LARGA 7460365061 | 7460365061 | [Ficha](https://www.pullandbear.com/es/camisa-cuadros-manga-larga-l07460365?cS=061&pelement=758570358) | [Foto](https://static.pullandbear.net/assets/public/17a6/bff3/14784d13b058/09c0811445a3/07460365061-A6M/07460365061-A6M.jpg?ts=1789571869995&w=1390&f=auto) | unknown |
| 92 | PULL&BEAR | CAMISA ROSA CUELLO BOBO 7460412620 | 7460412620 | [Ficha](https://www.pullandbear.com/es/camisa-rosa-cuello-bobo-l07460412?cS=620&pelement=758784061) | [Foto](https://static.pullandbear.net/assets/public/1f6a/c5a3/f9bf475a9f67/9f1ccf125c65/07460412620-A6M/07460412620-A6M.jpg?ts=1789567778719&w=1390&f=auto) | unknown |
| 93 | PULL&BEAR | CAMISA RAYAS BORDADO 7460300423 | 7460300423 | [Ficha](https://www.pullandbear.com/es/camisa-rayas-bordado-l07460300?cS=423&pelement=748866988) | [Foto](https://static.pullandbear.net/assets/public/d9f9/6fc3/700c4aa6b262/279290bb4d3b/07460300423-a6m/07460300423-a6m.jpg?ts=1786614628060&w=1390&f=auto) | unknown |
| 94 | PULL&BEAR | CAZADORA EFECTO PIEL CUELLO ALTO 7720323776 | 7720323776 | [Ficha](https://www.pullandbear.com/es/cazadora-efecto-piel-cuello-alto-l07720323?cS=776&pelement=748867202) | [Foto](https://static.pullandbear.net/assets/public/7b20/3778/874541d990b1/177aa13a7673/07720323776-A6M/07720323776-A6M.jpg?ts=1788350888209&w=1390&f=auto) | unknown |
| 95 | PULL&BEAR | PARKA CORTA CUELLO ALTO 7720371507 | 7720371507 | [Ficha](https://www.pullandbear.com/es/parka-corta-cuello-alto-l07720371?cS=507&pelement=748856728) | [Foto](https://static.pullandbear.net/assets/public/9c5a/9256/65874e39a378/f2bae24d7d47/07720371507-A6M/07720371507-A6M.jpg?ts=1786461596628&w=1390&f=auto) | unknown |
| 96 | PULL&BEAR | CÁRDIGAN CUELLO ALTO BOTONES 7563323401 | 7563323401 | [Ficha](https://www.pullandbear.com/es/cardigan-cuello-alto-botones-l07563323?cS=401&pelement=750792475) | [Foto](https://static.pullandbear.net/assets/public/e2c8/0427/e409486489eb/f3451e3e15b8/07563323401-A6M/07563323401-A6M.jpg?ts=1787902610297&w=1390&f=auto) | unknown |
| 97 | PULL&BEAR | JERSEY PUNTO ASIMÉTRICO 7540325630 | 7540325630 | [Ficha](https://www.pullandbear.com/es/jersey-punto-asimetrico-l07540325?cS=630&pelement=749205743) | [Foto](https://static.pullandbear.net/assets/public/3aad/ed0d/b269406fa2ff/53b34d63dc34/07540325630-A6M/07540325630-A6M.jpg?ts=1785394271632&w=1390&f=auto) | unknown |
| 98 | PULL&BEAR | PANTALÓN CHINO DICKIES 7671306711 | 7671306711 | [Ficha](https://www.pullandbear.com/es/pantalon-chino-dickies-l07671306?cS=711&pelement=757811010) | [Foto](https://static.pullandbear.net/assets/public/cc8e/9e4b/8dbd46de8c62/ea792165bf96/07671306711-A6M/07671306711-A6M.jpg?ts=1787758311066&w=1390&f=auto) | unknown |
| 99 | PULL&BEAR | VAQUEROS RECTOS CON BRILLOS 3661306800 | 3661306800 | [Ficha](https://www.pullandbear.com/es/jeans-straight-brillos-l03661306?cS=800&pelement=768253337) | [Foto](https://static.pullandbear.net/assets/public/80b7/f6bf/253f44828b9e/72f780e0fded/03661313800-A6M/03661313800-A6M.jpg?ts=1789727744085&w=1390&f=auto) | unknown |
| 100 | PULL&BEAR | CAMISETA PUNTO GRÁFICO 7231397805 | 7231397805 | [Ficha](https://www.pullandbear.com/es/camiseta-punto-grafico-l07231397?cS=805&pelement=753559391) | [Foto](https://static.pullandbear.net/assets/public/c305/2042/ac4c45ec8c5b/b49394afd081/07231397805-A6M/07231397805-A6M.jpg?ts=1787672168452&w=1390&f=auto) | unknown |
| 101 | PULL&BEAR | CAMISETA RAYAS DICKIES 7232362105 | 7232362105 | [Ficha](https://www.pullandbear.com/es/camiseta-rayas-dickies-l07232362?cS=105&pelement=757424334) | [Foto](https://static.pullandbear.net/assets/public/33b6/f191/5e3e4a7092b1/f08404e2b86c/07232362105-A6M/07232362105-A6M.jpg?ts=1789045608731&w=1390&f=auto) | unknown |
| 102 | COS | PANTALON SASTRERO AMPLIO DE LANA BEIGE CLARO 1129676007 | 1129676007 | [Ficha](https://www.cos.com/en-ww/women/womenswear/trousers/wool/product/wide-leg-tailored-wool-trousers-light-beige-1129676007) | [Foto](https://media.cos.com/assets/001/ef/33/ef33e41e1282d28fadb38cf4684f8adf98d8a2d0_xxl-1.jpg?imwidth=2160) | unknown |
| 103 | COS | JERSEY DE LANA Y ALGODON CON BOLSILLO KHAKI 1293229002 | 1293229002 | [Ficha](https://www.cos.com/en-ww/men/product/pocket-detail-wool-crew-neck-jumper-khaki-1293229002) | [Foto](https://media.cos.com/assets/001/69/7c/697c17d3045699b61d73de6853db3e64f0a567fc_xxl-1.jpg?imwidth=2160) | unknown |
| 104 | COS | VESTIDO MAXI MERINO CUELLO ALTO AZUL 1143477002 | 1143477002 | [Ficha](https://www.cos.com/es-es/women/womenswear/dresses/wool/product/lightweight-merino-wool-turtleneck-dress-blue-1143477002) | [Foto](https://media.cos.com/assets/001/01/0f/010f9efdcbdc4965ff4eac1022df187db7d1fc33_xxl-1.jpg?imwidth=2160) | unknown |
| 105 | COS | CAMISA DE PUNTO MERINO AZUL MARINO 1293216001 | 1293216001 | [Ficha](https://www.cos.com/en-nl/men/menswear/shirts/casualshirts/product/knitted-merino-wool-shirt-navy-1293216001) | [Foto](https://media.cos.com/assets/001/63/18/631859545095e27c7141f374dfaa9d7475e05b9d_xxl-1.jpg?imwidth=1260) | unknown |
| 106 | COS | ABRIGO LARGO NEGRO DE LANA DOBLE CARA CON CINTURON 1097894006 | 1097894006 | [Ficha](https://www.cos.com/en-us/product/belted-double-faced-wool-coat-black-1097894006) | [Foto](https://media.cos.com/assets/001/51/34/5134db98f8bbc936ce9ec62172323633d3ba8e87_xxl-1.jpg?imwidth=1260) | unknown |
| 107 | COS | FALDA MAXI DENIM MARFIL CON ABERTURAS 1243254001 | 1243254001 | [Ficha](https://www.cos.com/en-us/women/womenswear/skirts/midlength/product/denim-maxi-skirt-ivory-1243254001) | [Foto](https://media.cos.com/assets/001/2a/69/2a69916ee282516bc8d864f8303f85a7c2e8c767_xxl-1.jpg?imwidth=2160) | unknown |
| 108 | COS | JERSEY GRUESO DE LANA CON CUELLO ALTO BEIGE OSCURO 1296275004 | 1296275004 | [Ficha](https://www.cos.com/en-ww/women/womenswear/knitwear/jumpers/wool/product/chunky-funnel-neck-wool-jumper-dark-beige-1296275004) | [Foto](https://media.cos.com/assets/001/8c/be/8cbef233baef340fbd9291dd585fd8b93f9394d3.jpg?aspectRatio=2%2C3&imwidth=2160) | unknown |
| 109 | COS | JERSEY ATELIER MERINO CUELLO VUELTO VERDE OSCURO 1208558001 | 1208558001 | [Ficha](https://www.cos.com/en-ww/men/menswear/knitwear/jumpers/merino/product/the-merino-wool-roll-neck-jumper-dark-green-1208558001) | [Foto](https://media.cos.com/assets/001/f7/d4/f7d40c5f1d8f4961a7a39b572fd12975794defdd_xxl-1.jpg?imwidth=2160) | unknown |
| 110 | COS | BLAZER COS NEGRO FLUIDO DE CREPE 1279060001 | 1279060001 | [Ficha](https://www2.hm.com/en_gb/productpage.1279060001.html) | [Foto](https://media.cos.com/assets/001/00/61/0061959fe9ba1098e6841ccf03009677a694523a.jpg?aspectRatio=2%2C3&imwidth=2160) | unknown |
| 111 | COS | BLAZER COS PUNTO MILANO DE LINO TOPO CLARO 1273329001 | 1273329001 | [Ficha](https://www.cos.com/es-es/men/menswear/suits/blazers/product/knitted-linen-single-breasted-blazer-light-mole-1273329001) | [Foto](https://cdn.endource.com/image/direct/1b536177459b08618cada1d9fc66f4da05aa7c58/cos-knitted-linen-single-breasted-blazer.jpg?class=800&optimizer=image) | unknown |
| 112 | COS | VESTIDO COS ROJO CRUZADO DE ALGODON 1282374002 | 1282374002 | [Ficha](https://www.cos.com/en-nl/women/womenswear/dresses/jerseydresses/product/wrap-front-t-shirt-dress-red-1282374002) | [Foto](https://media.cos.com/assets/001/92/cd/92cd6f377b529f24c6a1d72aa9b9fa1a75726978_xxl-1.jpg?imwidth=2160) | unknown |
| 113 | COS | BLAZER COS MARRON OSCURO DE MANGA CORTA 1336988001 | 1336988001 | [Ficha](https://www.cos.com/en-ca/women/womenswear/coatsjackets/blazers/product/twill-jersey-short-sleeved-blazer-dark-brown-1336988001) | [Foto](https://d29c1z66frfv6c.cloudfront.net/pub/media/catalog/product/large/9011f2e31e6c1050131dffd9e405961a74b6905d_xxl-1.jpg) | unknown |
| 114 | HUGO BOSS | CONJUNTO BOSS PIQUE VERDE OSCURO CON LOGOS BORDADOS hbeu50569918_398 | hbeu50569918_398 | [Ficha](https://www.hugoboss.com/fi/en/tracksuit-set-in-cotton-blend-pique/hbeu50569918_398.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50569918_398_100?$large$&fit=crop,1&align=0,0) | unknown |
| 115 | HUGO BOSS | CONJUNTO BOSS VERDE CLARO DE ALGODON ELASTICO CON FRANJAS hbeu50533738_357 | hbeu50533738_357 | [Ficha](https://www.hugoboss.com/fi/en/stretch-cotton-regular-fit-tracksuit-set-with-stripe-inserts/hbeu50533738_357.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50533738_357_100?$large$&fit=crop,1&align=0,0) | unknown |
| 116 | HUGO BOSS | CONJUNTO BOSS BEIGE DE ALGODON CON CAPUCHA hbeu50541065_246 | hbeu50541065_246 | [Ficha](https://www.hugoboss.com/uk/casual-cotton-tracksuit-set-with-a-minimalist-logo/hbeu50541065_246.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50541065_246_100?$large$&fit=crop,1&align=0,0) | unknown |
| 117 | HUGO BOSS | CAMISA OXFORD BLANCA BOSS CORTE REGULAR hbeu50519707_100 | hbeu50519707_100 | [Ficha](https://www.hugoboss.com/uk/regular-fit-oxford-shirt/hbeu50519707_100.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50519707_100_100?$large$&fit=crop,1&align=0,0) | unknown |
| 118 | HUGO BOSS | POLERA NEGRA BOSS CON LOGO ESTAMPADO hbeu50481923_001 | hbeu50481923_001 | [Ficha](https://www.hugoboss.com/uk/cotton-jersey-t-shirt-with-large-logo-print/hbeu50481923_001.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50481923_001_100?$large$&fit=crop,1&align=0,0) | unknown |
| 119 | HUGO BOSS | PANTALON BOSS BEIGE CLARO DE LANA CON MICRODISENO hbeu50564687_271 | hbeu50564687_271 | [Ficha](https://www.hugoboss.com/de/tapered-fit-hose-aus-schurwolle-mit-dezentem-muster/hbeu50564687_271.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50564687_271_100?$large$&fit=crop,1&align=0,0) | unknown |
| 120 | HUGO BOSS | PANTALON BOSS MARRON CLARO DE ESPIGA Y CACHEMIRA hbeu50570707_342 | hbeu50570707_342 | [Ficha](https://www.hugoboss.com/de/tapered-fit-hose-aus-schurwolle-und-kaschmir-mit-fischgraet-muster/hbeu50570707_342.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50570707_342_100?$large$&fit=crop,1&align=0,0) | unknown |
| 121 | HUGO BOSS | MINIFALDA BOSS CAMEL CRUZADA CON FLECOS hbeu50568685_222 | hbeu50568685_222 | [Ficha](https://www.hugoboss.com/de/wickel-minirock-mit-fransen/hbeu50568685_222.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50568685_222_100?$large$&fit=crop,1&align=0,0) | unknown |
| 122 | HUGO BOSS | MINIFALDA HUGO BOUCLE NEGRO BLANCO A CUADROS hbeu50571482_966 | hbeu50571482_966 | [Ficha](https://www.hugoboss.com/de/karierter-a-linien-minirock-aus-boucle/hbeu50571482_966.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50571482_966_100?$large$&fit=crop,1&align=0,0) | unknown |
| 123 | HUGO BOSS | MINIFALDA HUGO PLISADA CON MICROCUADROS hbeu50566905_983 | hbeu50566905_983 | [Ficha](https://www.hugoboss.com/de/plissee-minirock-mit-mikro-karo/hbeu50566905_983.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50566905_983_100?$large$&fit=crop,1&align=0,0) | unknown |
| 124 | HUGO BOSS | VESTIDO BOSS BLANCO DE JERSEY Y POPELIN hbeu50565542_100 | hbeu50565542_100 | [Ficha](https://www.hugoboss.com/uk/structured-jersey-dress-with-poplin-skirt/hbeu50565542_100.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50565542_100_100?$large$&fit=crop,1&align=0,0) | unknown |
| 125 | HUGO BOSS | VESTIDO BOSS VERDE CLARO CON FALDA PLISADA hbeu50535658_340 | hbeu50535658_340 | [Ficha](https://www.hugoboss.com/uk/mixed-material-dress-with-plisse-skirt/hbeu50535658_340.html) | [Foto](https://images.hugoboss.com/is/image/boss/hbeu50535658_340_100?$large$&fit=crop,1&align=0,0) | unknown |
| 126 | RALPH LAUREN | CAMISA RALPH LAUREN OXFORD TENIDA AZUL 710805564522 | 710805564522 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-blue-custom-fit-garment-dyed-oxford-shirt/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/09/Ralph-Blue-Sport-Shirt-Front.jpg) | unknown |
| 127 | RALPH LAUREN | CAMISA RALPH LAUREN LINO CELESTE MANGA LARGA 710966294509 | 710966294509 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-blue-custom-fit-long-sleeve-linen-shirt-2/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/04/POLO-RALPH-LAUREN-Blue-Custom-Fit-Long-Sleeve-Linen-Shirt.jpg) | unknown |
| 128 | RALPH LAUREN | CAMISA RALPH LAUREN OXFORD A CUADROS AZULES 710B30241003 | 710B30241003 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-custom-fit-blue-plaid-oxford-shirt/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/09/Ralph-Blue-Check-Shirt-Front.jpg) | unknown |
| 129 | RALPH LAUREN | CAMISA RALPH LAUREN OXFORD RAYAS VERTICALES 710B16700001 | 710B16700001 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-long-sleeved-striped-guaranteed-to-wrinkle-oxford-shirt/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/04/POLO-RALPH-LAUREN-Long-Sleeved-Striped-Guaranteed-To-Wrinkle-Oxford-Shirt.jpg) | unknown |
| 130 | RALPH LAUREN | CAMISA RALPH LAUREN ROSA MANGA CORTA OXFORD 710850782007 | 710850782007 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-light-pink-custom-fit-short-sleeve-knit-oxford-button-down-shirt/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/02/POLO-RALPH-LAUREN-Light-Pink-Custom-Fit-Short-Sleeve-Knit-Oxford-Button-Down-Shirt.jpg) | unknown |
| 131 | RALPH LAUREN | POLERA RALPH LAUREN BORGONA CON PONY 710671438545 | 710671438545 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-burgundy-t-shirt/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/09/Polo-Ralph-Lauren-Burgundy-T-shirt.jpg) | unknown |
| 132 | RALPH LAUREN | POLERA RALPH LAUREN VERDE CON PONY 710671438546 | 710671438546 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-green-jersey-t-shirt/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/09/Polo-Ralph-Lauren-Green-Custom-Slim-Fit-Jersey-T-shirt.jpg) | unknown |
| 133 | RALPH LAUREN | POLERA RALPH LAUREN AZUL JASPEADA CON PONY 710671438541 | 710671438541 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-blue-heather-t-shirt/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/09/Polo-Ralph-Lauren-Blue-Heather-T-shirt.jpg) | unknown |
| 134 | RALPH LAUREN | POLERA RALPH LAUREN BLANCA POLO BEAR 710B14615001 | 710B14615001 | [Ficha](https://www.menswearonline.co.uk/shop/polo-ralph-lauren-classic-fit-white-teddy-bear-tee/) | [Foto](https://www.menswearonline.co.uk/wp-content/uploads/2026/02/Polo-Ralph-Lauren-Classic-Fit-White-Teddy-Bear-Tee.jpg) | unknown |

### Reportes y archivos operacionales

- Manifiesto final: storage/app/catalog-import/catalog_real_100.json.
- Candidatos investigados: storage/app/catalog-import/catalog_candidates_v2.json.
- Reporte completo de validación: storage/app/catalog-import/candidates_validation_report.json.
- Reporte de importación real: storage/app/catalog-import/import_report.json.
- Reporte de repetición idempotente: storage/app/catalog-import/idempotency_report.json.
- Verificación de preservación y conteos: storage/app/catalog-import/verification_report.json.
- Evidencia de las 10 muestras UI: storage/app/catalog-import/ui_verification.json.
- Estado Git y estadística global final: storage/app/catalog-import/git_status_final.txt y git_diff_stat_final.txt.

Se conservaron las fichas previas y candidatos de reserva. No se afirmaron licencias comerciales para imágenes unknown. No se realizó scraping masivo ni evasión de CAPTCHA, challenge, login, paywall o rate limits.

Confirmaciones finales: no se utilizó Mati AI, FastAPI, SigLIP/CLIP, OCR, colorimetría automática, embeddings, pgvector, recomendaciones ni entrenamiento. No se insertaron prendas por SQL directo; se reutilizó el dominio Laravel. No se modificaron productos existentes, ventas, reservas, pagos, usuarios ni roles. No se ejecutaron migraciones, no se hizo rebuild, commit ni push.
