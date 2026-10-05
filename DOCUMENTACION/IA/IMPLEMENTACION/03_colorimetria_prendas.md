# Entrega: colorimetría avanzada de prendas

Fecha: 3 de octubre de 2026. Estado: implementación entregada, validación pendiente del administrador.

Se revisaron completamente los 13 documentos existentes de `IMPLEMENTACION` y `V2_ARQUITECTURA_AMPLIADA`, el directorio de visión, los servicios Laravel IA, el asistente React, las tablas IA y las referencias del catálogo presentes en el repositorio. No se consultó ni se modificó la base de datos en ejecución. Los IDs y colores efectivos se obtienen del catálogo activo en cada solicitud Laravel.

## 1. Arquitectura implementada

```text
Asistente React existente
    → Laravel autenticado: catálogo activo y HEX específico
    → FastAPI /api/v1/garments/analyze existente
        → validación/orientación/sRGB ya existentes
        → máscara conservadora de primer plano
        → paleta CIELAB y comparación CIEDE2000
        → propiedades y evidencia de iluminación
        → data.colors compatible + data.colorimetry adicional
    → Laravel: normalización de referencias y sugerencias auditadas
    → ai_runs.result / ai_suggestions.evidence
    → revisión humana y formulario comercial existente
```

Se conserva el endpoint y `contract_version = "1"`. El pipeline pasa a `registration-1.1.0` para distinguir los resultados nuevos; el algoritmo se identifica con `garment-colorimetry-1.0.0`. `capabilities` describe la capacidad adicional. Calidad, códigos, clasificación y patrón continúan en sus módulos existentes. Una abstención cromática produce una respuesta global parcial y permite conservar los demás resultados.

La fase analiza colores visibles de una prenda aislada. No añade modelos para personas, rostro, piel, etnia, biometría o colorimetría personal. No implementa outfits, recomendaciones, tendencias, VTO, Redis ni pgvector.

## 2. Algoritmo de extracción

Se procesa una copia reducida, de hasta 256 × 256 píxeles, y se convierten sus valores sRGB a CIELAB físico. Los píxeles provienen exclusivamente de una máscara aceptada; si falla la separación no se recurre a la fotografía completa para inventar una paleta de prenda.

La agrupación es determinista: histograma cuantizado en pasos Lab `[6, 8, 8]`, hasta ocho modos iniciales separados al menos por DeltaE00 12 y cinco refinamientos de centroides Lab ponderados por población, con asignación por CIEDE2000. Todos los bins participan; se retienen grupos que representen al menos 1,5 % del primer plano. La dispersión interna de cada grupo se registra mediante su percentil 90 de DeltaE00; una dispersión mayor de 14 impide asociarlo automáticamente al catálogo.

Los porcentajes usan todos los píxeles interiores aceptados como denominador, sin inflar los grupos retenidos para sumar artificialmente 100 %. `coverage` se calcula con las proporciones originales, limitada a 1; una cobertura menor de 85 % deja las propiedades por revisar. Un dominante exige al menos 55 % y una ventaja de al menos 15 puntos porcentuales frente al siguiente tono. Los acentos ocupan menos de 12 % y deben contrastar al menos DeltaE00 15 con el primer tono; los demás colores sin rol principal se conservan como secundarios.

La variedad usa colores de al menos 12 % y separación perceptual/hue/croma, evitando llamar multicolor a un tejido únicamente por sus sombras: DeltaE00 de al menos 15, diferencia circular de matiz de al menos 30° o diferencia de croma de al menos 24 con diferencia de luminosidad ≤ 12. Entre neutrales se exige diferencia de luminosidad de al menos 35. Estas reglas son heurísticas, no reconocimiento de diseño textil.

Los porcentajes expresan área de píxeles de la muestra, no cantidad de tela ni probabilidad. Los tonos descriptivos IA se mantienen separados de los nombres comerciales.

Una paleta válida puede tener `primary_color = null`. La falta de un dominante único no invalida los demás colores ni fuerza un ID principal. La variedad cromática se separa del patrón: rayas y estampados siguen siendo un atributo de su módulo existente.

## 3. Manejo del fondo

`segmentation.py` reúne evidencia de bordes, conectividad, región central, contraste perceptual, componentes y geometría. Busca el modo cromático de una banda exterior de aproximadamente 4 %, evitando promediar fondos incompatibles. Solo elimina regiones cromáticamente parecidas al fondo que estén conectadas con el borde; una zona similar encerrada en la silueta puede permanecer en la paleta.

Umbrales iniciales de separación:

- Soporte exterior de al menos 82 % con DeltaE00 ≤ 7 y percentil 90 ≤ 7.
- Tres lados con soporte de al menos 82 %; todos con al menos 65 %.
- Primer plano central plausible, área entre aproximadamente 8 % y 88 %, componente principal con al menos 88 % del primer plano candidato.
- Ocupación central de al menos 45 %, desplazamiento del centroide ≤ 0,22 y fuga al borde ≤ 5 %.
- Separación mediana entre objeto y fondo de al menos DeltaE00 10.
- Erosión de un píxel para excluir transiciones del borde, conservando al menos 85 % del interior.

`foreground_confidence` combina estas señales y exige al menos 0,70. Es una puntuación heurística sin calibrar. La máscara incierta se vacía; el resultado contiene `BACKGROUND_INTERFERENCE`, paleta vacía, propiedades `unknown` y ningún ID de catálogo sugerido. Se distinguen además objeto pequeño, regiones múltiples y posible recorte.

La máscara identifica un objeto central plausible, no su semántica. Un maniquí, una percha o accesorios unidos pueden confundirse con la prenda. Se recomienda fotografiar una sola prenda aislada con margen y fondo uniforme.

## 4. Espacios de color

- sRGB/RGB: entrada normalizada y muestras para visualización; canales de salida entre 0 y 255.
- CIELAB con referencia D65: agrupación, luminosidad, croma, familias y comparación perceptual. Se usa RGB flotante entre 0 y 1 para evitar confundir Lab físico con la codificación uint8 de OpenCV.
- HSV: saturación aproximada y señales auxiliares de matiz.

RGB no se usa como distancia principal de semejanza. La conversión existente de perfil/orientación actúa sobre una copia. El original no se altera y no se introduce corrección agresiva de balance de blancos.

## 5. Cálculo de DeltaE

`color_distance.py` implementa CIEDE2000 con NumPy y factores `kL = kC = kH = 1`. Calcula diferencias corregidas de luminosidad, croma y matiz, sus pesos y el término de rotación. Contempla croma nulo y el cruce angular de 0°/360°. La misma función sirve para comparar muestras, fondo, grupos y catálogo.

La fórmula se expresó de forma independiente a partir de las [notas de Sharma, Wu y Dalal](https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/). No se incorporó el software MATLAB/Excel de esa página y no se ejecutaron sus datos de prueba en esta entrega.

Un ID comercial exige referencia HEX específica, DeltaE00 ≤ 10, margen respecto al siguiente candidato de al menos 2 salvo coincidencia muy próxima (DeltaE00 ≤ 2), confianza de la muestra ≥ 0,55, fondo aceptado e iluminación suficientemente fiable. Las referencias aproximadas de nombre no autorizan IDs. Si existe una sola referencia comercial, se exige DeltaE00 ≤ 2 porque no hay margen contra un segundo candidato. Los umbrales quedan pendientes de calibración local.

## 6. Propiedades cromáticas

La salida añade temperatura, luminosidad, croma, saturación, neutralidad, contraste interno, variedad y familia dominante. Cada valor conserva métricas y evidencia en el JSON.

| Campo | Valores controlados |
|---|---|
| `temperature` | `warm`, `cool`, `neutral`, `warm_neutral`, `cool_neutral`, `unknown` |
| `lightness`, `chroma`, `saturation`, `neutrality`, `internal_contrast` | `low`, `medium`, `high`, `unknown` |
| `color_variety` | `monochrome`, `bicolor`, `multicolor`, `unknown` |
| `dominant_family` | familia controlada o `null` |

Reglas iniciales de propiedades (bajo: valor menor que el primer umbral; alto: valor mayor o igual al segundo):

| Propiedad | Medida | Umbrales bajo / alto |
|---|---|---|
| Luminosidad | media de L* en primer plano | 35 / 70 |
| Croma | media de `sqrt(a*² + b*²)` | 25 / 50 |
| Saturación | media de S de HSV, escala 0..1 | 0,25 / 0,60 |
| Neutralidad | fracción de píxeles de croma bajo o tonos tierra neutros | 0,35 / 0,70 |
| Contraste interno | máximo entre DeltaE00 ponderado por pares y mitad del rango L* p10–p90 | 8 / 20 |

La temperatura se pondera por los tonos de la paleta. Una combinación equilibrada de tonos cálidos y fríos puede quedar `unknown`. Las métricas numéricas permanecen disponibles aun cuando la interpretación se abstenga.

La exposición se considera sospechosa si al menos 40 % del primer plano tiene todos sus canales ≤ 4 o ≥ 250. Se señala clipping si al menos 35 % tiene algún canal ≥ 254 o todos ≤ 1. Una banda uniforme clara y cercana a neutral (L* ≥ 70, croma ≤ 22, |a*| ≤ 10) sirve únicamente como posible referencia: b* > 9 genera sospecha cálida y b* < −9 fría. No se asume que esa banda fuera físicamente blanca; no se corrige el producto.
Familias: `black`, `white`, `gray`, `beige_cream`, `brown`, `red`, `orange`, `yellow`, `green`, `blue`, `violet`, `pink`, `metallic`, `multicolor`. Se consideran nombres y familias reales como CREMA, CAMEL, MARRÓN, BORGONA, CELESTE, VERDE OLIVA, DORADO y PLATEADO, sin cambiar registros del catálogo.

La temperatura es una descripción del color visible, no temperatura física ni análisis personal. Los nombres descriptivos expresan variantes claras, medias, oscuras, apagadas o intensas cuando hay respaldo suficiente. El nombre descriptivo nunca reemplaza el nombre ni el ID del catálogo.

Se generan avisos ante sobre/subexposición, clipping de canales, posible luz cálida/fría y balance de blancos sospechoso. La ausencia de referencia neutral no permite certificar un balance correcto. La iluminación incierta reduce evidencia y puede dejar propiedades por determinar y asociaciones comerciales vacías. La taxonomía reconoce los nombres/familias comerciales DORADO y PLATEADO como metalizados, pero una muestra RGB no demuestra su acabado. En esta fase se devuelve el aviso `METALLIC_FINISH_NOT_VERIFIABLE` y se abstiene de asignar esos IDs automáticamente; el administrador puede seleccionarlos manualmente.

## 7. Contrato JSON

Se conserva `data.colors` con `dominant_color`, `secondary_colors`, `palette`, `foreground_confidence`, `warnings` y `coverage`. Las muestras mantienen los aliases anteriores `catalog_id`, `catalog_name` y `distance_delta_e`.

El bloque adicional `data.colorimetry` tiene un contrato Pydantic en `contracts/colorimetry.py`. Ejemplo ilustrativo de estructura cuando falta evidencia; no procede de una inferencia ejecutada:

```json
{
  "colorimetry": {
    "version": "garment-colorimetry-1.0.0",
    "status": "insufficient_evidence",
    "foreground_confidence": 0.3,
    "palette": [],
    "primary_color": null,
    "secondary_colors": [],
    "accent_colors": [],
    "temperature": "unknown",
    "lightness": "unknown",
    "chroma": "unknown",
    "saturation": "unknown",
    "neutrality": "unknown",
    "internal_contrast": "unknown",
    "color_variety": "unknown",
    "dominant_family": null,
    "warnings": ["BACKGROUND_INTERFERENCE"],
    "metrics": {},
    "evidence": {},
    "coverage": 0
  }
}
```

Cada muestra de `palette`, `primary_color`, `secondary_colors` o `accent_colors` contiene:

| Campo | Tipo / significado |
|---|---|
| `rgb` | tres enteros de 0 a 255 |
| `hex` | `#RRGGBB` observado |
| `lab` | `[L*, a*, b*]` físico |
| `percentage` | fracción aproximada entre 0 y 1 |
| `catalog_color_id` | ID comercial existente o `null` |
| `catalog_color_name` | nombre canónico del catálogo o `null` |
| `delta_e` | distancia CIEDE2000 al candidato, o `null` si no existe referencia |
| `confidence_score` | puntuación heurística entre 0 y 1 |
| `family` | familia controlada del tono |
| `descriptive_name` | descripción IA separada del nombre comercial |
| `evidence` | población, dispersión, iluminación, referencia y razones de aceptación/abstención |

Una distancia puede conservarse aunque el ID sea `null`: la cercanía por sí sola no autoriza una asociación comercial. `status` cromático es `completed`, `partial` o `insufficient_evidence`. El sobre sigue exigiendo revisión humana. `COLORIMETRY_INSUFFICIENT_EVIDENCE` y `COLORIMETRY_PARTIAL_EVIDENCE` describen abstenciones en el resultado global.

## 8. Cambios Laravel

Laravel envía colores activos ordenados por su ID y usa exclusivamente `hex_col` para la referencia exacta; `hex_dom_col` viaja como `family_hex` y `fam_col` como `family`. Un tono sin HEX específico no convierte el HEX familiar en una referencia exacta. El contexto familiar viaja separado; no se modifica el catálogo. Esto evita comparar CREMA como BLANCO o CELESTE como un azul distinto cuando existe su muestra específica.

El cliente conserva el catálogo original antes de codificar multipart y aplica `ColorimetryCatalogGuard`, tanto para el asistente como para el comando existente de indexación. El guard verifica la sección opcional y normaliza los IDs y nombres contra el catálogo enviado antes de entregar/persistir el resultado. Una sección inválida se elimina y vacía también la paleta heredada con `COLORIMETRY_INVALID_RESPONSE`, conservando calidad y clasificación. Un resultado válido reconstruye los aliases anteriores desde las muestras normalizadas; sin dominante, conserva toda la paleta entre los secundarios heredados. Se descartan IDs desconocidos, ambiguos o sin evidencia suficiente. Las sugerencias comerciales siguen usando `ai_suggestions` y la aplicación explícita en el formulario.

## 9. Cambios React

El asistente existente incorpora una tarjeta comprensible con color principal, paleta, roles, porcentajes aproximados, temperatura, luminosidad, saturación, contraste, variedad y familia. Los nombres descriptivos se muestran separados de la coincidencia del catálogo activo. Los avisos se traducen al español; no se muestra Lab o DeltaE en el formulario.

Si el nuevo bloque falta, se conserva la presentación anterior de `data.colors`. Si existe pero abstiene, no se presenta la vieja paleta como un resultado alternativo fiable. Sin dominante fiable, el lote omite aplicar color; cada coincidencia comercial puede revisarse individualmente. La restricción usa el análisis de la imagen actualmente seleccionada. Los valores manuales y el proceso de feedback siguen su flujo habitual.

## 10. Persistencia

No se crean tablas ni migraciones en esta fase. `ai_runs.result` almacena el sobre completo, incluyendo colorimetría, versiones, métricas y evidencia. `ai_suggestions.evidence` conserva el respaldo de cada sugerencia comercial y `ai_human_reviews` sigue registrando la decisión humana. `ai_model_versions` distingue el pipeline actualizado.

No se guardan bytes de imágenes en PostgreSQL ni se aplican predicciones directamente a prendas, variantes, inventario o transacciones comerciales. La vinculación de ejecuciones al guardado sigue la arquitectura IA existente.

## 11. Archivos creados

- `DOCUMENTACION/IA/IMPLEMENTACION/03_colorimetria_prendas.md`
- `app/Services/AI/ColorimetryCatalogGuard.php`
- `services/mati-ai/src/mati_ai/contracts/colorimetry.py`
- `services/mati-ai/src/mati_ai/vision/color_distance.py`
- `services/mati-ai/src/mati_ai/vision/segmentation.py`

## 12. Archivos modificados

- `app/Services/AI/AiReviewService.php`
- `app/Services/AI/CatalogAiService.php`
- `app/Services/AI/MatiAiClient.php`
- `resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx`
- `resources/js/lib/garmentAssistant.ts`
- `services/mati-ai/README.md`
- `services/mati-ai/src/mati_ai/api/v1/routes.py`
- `services/mati-ai/src/mati_ai/contracts/common.py`
- `services/mati-ai/src/mati_ai/contracts/vision.py`
- `services/mati-ai/src/mati_ai/vision/color.py`

## 13. Limitaciones conocidas

- Entrega sin certificación de ejecución, precisión, latencia o memoria; el administrador realizará la validación.
- Los umbrales son iniciales y no se calibraron contra fotografías etiquetadas de Mati Boutique.
- Primer plano determinista: requiere fondo relativamente uniforme y una prenda central aislada; fondos complejos, objetos unidos, prendas recortadas o similares al fondo pueden producir abstención.
- Los porcentajes corresponden a píxeles muestreados del área visible, excluyendo el borde; no expresan superficie física ni composición de la tela.
- Sombras, pliegues, iluminación mixta, cámara y compresión pueden cambiar la paleta y las propiedades observadas.
- Una imagen de fondo coloreado no demuestra una dominante de iluminación; la evaluación de balance de blancos es una sospecha conservadora, no una medición calibrada.
- Brillo, iridiscencia y metalizados no se pueden confirmar con HEX plano; no se crean equivalencias materiales automáticas.
- Un tono cercano a dos colores comerciales puede quedar sin ID; no se fuerza el primero del catálogo.
- La cantidad de colores no determina rayas o estampado; ese atributo continúa separado.
- Los resultados existentes no se recalculan y no se realiza indexación masiva ni backfill.
- No se conectó a la base en ejecución para consultar su estado; Laravel obtiene el catálogo activo durante el uso.

## 14. Decisiones técnicas

Se evoluciona `extract_colors` y se extraen helpers pequeños para distancia perceptual y máscara, conservando dependencias y arquitectura. No se agrega un modelo de segmentación ni carga adicional en GPU. Se mantiene CIELAB principal, se usa CIEDE2000 y se evita similitud euclidiana RGB. La cuantización determinista permite reproducibilidad sin entrenamiento.

Se prefiere abstener ante fondo inseguro y separar observación de asociación comercial. El HEX de familia es contexto y no sustituto silencioso del tono específico. Una paleta multicolor no necesita un dominante inventado. No se normalizan agresivamente los colores y el original permanece intacto.

Se conserva el contrato HTTP v1, se versiona el algoritmo/pipeline y se reutilizan JSON y auditoría actuales. El administrador continúa siendo quien confirma los datos comerciales.

Por instrucción expresa no se ejecutaron tests, builds, migraciones, benchmarks, lint, inferencias ni validación final. No se hizo staging, commit o push. Solo se realizaron lecturas/ediciones del código y las capturas Git solicitadas.

## 15. git diff --stat

Captura del cierre. Describe 10 archivos ya seguidos por Git; los cinco archivos nuevos sin seguimiento aparecen en el inventario y en git status.

```text
 app/Services/AI/AiReviewService.php                |  27 +-
 app/Services/AI/CatalogAiService.php               |   7 +-
 app/Services/AI/MatiAiClient.php                   |   5 +-
 .../Superadmin/GarmentIntelligentAssistant.tsx     |  80 ++-
 resources/js/lib/garmentAssistant.ts               |  20 +
 services/mati-ai/README.md                         |   7 +
 services/mati-ai/src/mati_ai/api/v1/routes.py      |  30 +-
 services/mati-ai/src/mati_ai/contracts/common.py   |   2 +-
 services/mati-ai/src/mati_ai/contracts/vision.py   |   2 +
 services/mati-ai/src/mati_ai/vision/color.py       | 568 +++++++++++++++++----
 10 files changed, 648 insertions(+), 100 deletions(-)
```

## 16. git status

Rama actual: feature/ia-colorimetria. Diez archivos modificados y cinco nuevos sin seguimiento. Ningún cambio en staging; sin commit ni push.

```text
On branch feature/ia-colorimetria
Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git restore <file>..." to discard changes in working directory)
	modified:   app/Services/AI/AiReviewService.php
	modified:   app/Services/AI/CatalogAiService.php
	modified:   app/Services/AI/MatiAiClient.php
	modified:   resources/js/Components/Superadmin/GarmentIntelligentAssistant.tsx
	modified:   resources/js/lib/garmentAssistant.ts
	modified:   services/mati-ai/README.md
	modified:   services/mati-ai/src/mati_ai/api/v1/routes.py
	modified:   services/mati-ai/src/mati_ai/contracts/common.py
	modified:   services/mati-ai/src/mati_ai/contracts/vision.py
	modified:   services/mati-ai/src/mati_ai/vision/color.py

Untracked files:
  (use "git add <file>..." to include in what will be committed)
	DOCUMENTACION/IA/IMPLEMENTACION/03_colorimetria_prendas.md
	app/Services/AI/ColorimetryCatalogGuard.php
	services/mati-ai/src/mati_ai/contracts/colorimetry.py
	services/mati-ai/src/mati_ai/vision/color_distance.py
	services/mati-ai/src/mati_ai/vision/segmentation.py

no changes added to commit (use "git add" and/or "git commit -a")
```
