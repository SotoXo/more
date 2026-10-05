# Motor de compatibilidad cromática entre prendas de Mati Boutique

Fecha: 2026-10-03. Implementación escrita sin ejecutar tests, validación final, lint, build, inferencias ni llamadas de comprobación a la API. Sin commit ni push. Esta entrega incorpora una señal para ordenar pares de prendas; no construye outfits completos.

## 1. Alcance y entrada existente

El motor recibe dos objetos `GarmentColorimetry` ya producidos por la fase [03_colorimetria_prendas.md](03_colorimetria_prendas.md). No abre fotografías, no vuelve a extraer colores y no carga el modelo visual. Consume paleta, color primario, secundarios, acentos, temperatura, luminosidad, croma, contraste interno y proporciones. La calidad y las advertencias de la extracción acompañan el resultado.

El ámbito es exclusivamente cromático y de prendas. No analiza personas ni rostros; no incorpora VTO, recomendaciones personales, tendencias, Redis ni generación de outfits. Laravel conserva la autoridad sobre IDs y catálogo. Los HEX particulares del catálogo y la paleta extraída permanecen como datos; las familias cromáticas ayudan a interpretar neutros y no reemplazan esos colores.

Cada color de la paleta conserva `rgb`, `hex`, `lab`, `percentage`, `confidence_score`, `family` y la evidencia del contrato anterior. `primary_color`, `secondary_colors` y `accent_colors` señalan funciones de colores presentes en la paleta: no suman otra vez su superficie. El score representa compatibilidad heurística para ranking y nunca un porcentaje de certeza.

## 2. Algoritmo determinista

1. Comprueba la evidencia de cada colorimetría, retiene colores con proporción positiva y conserva las proporciones originales como evidencia. Los pesos utilizados para comparar se normalizan sólo entre los colores retenidos.
2. Construye un resumen por prenda con neutros, colores cromáticos, luminosidad, croma, temperatura, contraste y variedad. Una ausencia de dominante no impide evaluar una paleta suficiente y se explica en el resultado.
3. Deriva el tono sRGB mediante HSV y lo transforma mediante una rueda artística RYB aproximada, definida con nodos y tolerancias versionados. Luminosidad y croma proceden de CIELAB: L* y C* = √(a*² + b*²).
4. Evalúa reglas explícitas entre los colores de A y B. Los pesos de cada par son el producto de sus proporciones normalizadas. Ambas prendas aportan 0.5 al resumen combinado: no se conoce ni se supone el área visible de cada una en un outfit.
5. Busca grupos reales de tres tonos distintos repartidos entre las dos prendas para complementaria dividida y triádica. Dos colores aislados a 120° o 150° no bastan para declarar una armonía de tres tonos.
6. Produce componentes interpretables, tipos de armonía, ajustes suaves, explicaciones y advertencias. La evidencia describe qué colores y relaciones sostienen las reglas.
7. Devuelve `compatibility_score=null` cuando no hay evidencia suficiente de ambas prendas. El estado `partial` describe limitaciones de entrada y se conserva separado del score.

La relación entre tonos es sólo una parte del resultado. Las reglas combinan disposición en la rueda, neutrales, luminosidad, croma, proporciones, roles y contraste. CIEDE2000 sigue siendo parte de la extracción y del matching de color comercial existente; este motor no utiliza únicamente una distancia perceptual para dictaminar una combinación.

## 3. Reglas y versionado

Versión de contrato/motor: `garment-compatibility-1.0.0`. Las reglas y constantes viven en `compatibility/rules.py`; el request declara `rules_version` y la respuesta vuelve a declarar la versión realmente aplicada. La versión de colorimetría permanece `garment-colorimetry-1.0.0` y el envelope HTTP conserva `contract_version="1"` y `registration-1.2.0`.

| Tipo de armonía | Regla conceptual | Explicación que aporta |
| --- | --- | --- |
| `monochromatic` | Tonos cromáticos cercanos en la rueda con variación posible de luz/croma | Continuidad de familia cromática entre las prendas |
| `analogous` | Tonos vecinos dentro de la tolerancia de la regla | Transición cromática cercana |
| `complementary` | Tonos próximos a posiciones opuestas | Contraste de matiz sostenido por proporciones |
| `split_complementary` | Un tono y dos tonos distintos situados a ambos lados de su opuesto | Contraste de tres tonos con oposición repartida |
| `triadic` | Tres tonos distintos aproximadamente equidistantes | Equilibrio de tres regiones de la rueda |
| `neutrals` | Proporción de neutros, incluyendo interpretación de familia y croma Lab | Base neutral con matices y luminosidad registrados |
| `neutral_accent` | Base neutral junto a presencia cromática en la otra prenda | El color cromático funciona como foco frente a una base neutral |
| `light_dark_contrast` | Diferencia relevante de L* entre las prendas | Separación visual por luminosidad |

Una prenda puede participar en varias reglas. Las armonías no son etiquetas excluyentes ni un juicio universal de gusto. Cambiar la rueda, tolerancias, pesos, umbrales, ajustes o significado de componentes requiere una nueva versión de reglas. Para reproducir un ranking futuro se deben conservar ambas colorimetrías de entrada y `rules_version`; comparar scores de versiones distintas requiere revisión y calibración.

### Constantes de reglas v1

La rueda interpola linealmente estos nodos HSV → RYB, expresados en grados:

```text
0→0, 30→60, 60→120, 120→180,
180→210, 240→240, 300→300, 360→360
```

| Parámetro | Valor v1 |
| --- | --- |
| Monocromática | Cercanía máxima de 18° |
| Análoga | Cercanía máxima de 55° |
| Complementaria | Tolerancia de 25° respecto de 180° |
| Complementaria dividida y triádica | Tolerancia de 22° en las posiciones de cada estructura de tres tonos |
| Soporte mínimo de una regla de pares | 0.20 de la masa ponderada de pares |
| Muestra candidata a grupos de tres | Proporción original mínima 0.06; no es el peso renormalizado |
| Grupo de tres tonos | Soporte mínimo 0.18 en cada prenda y 0.45 en el resumen combinado |
| Neutralidad base | C* máximo 12 |
| Neutralidad terrosa | Familia beige_cream/brown, C* máximo 30 y matiz Lab de 35°–100° |
| Prenda considerada base neutral | Masa neutral mínima 0.70 |
| Presencia cromática para neutro + acento | Masa cromática mínima 0.12 |
| Contraste claro/oscuro | Diferencia L* mínima 30 y soporte mínimo 0.35 |
| Evidencia de primer plano | Mínimo 0.70 |
| Evidencia baja de muestras | confidence_score < 0.45; advertencia si cubre al menos 0.25 de la masa retenida |
| Cobertura mínima / completa | 0.50 / 0.85 |
| Croma / saturación intensos | C* mínimo 50 / HSV S mínimo 0.60 |
| Masa necesaria para intensidad conjunta | 0.60 por prenda |
| Ajuste de dos prendas saturadas | 0.08 |
| Ajuste de dos paletas complejas | 0.10 |
| Ajuste de dos patrones intensos | 0.08 |
| Límite acumulado de ajustes | 0.20 |

Los soportes de pares y de grupos de tres son fracciones de los pesos calculados; no son medidas de certeza. Los umbrales de primer plano y cobertura determinan si la entrada es utilizable; no puntúan gustos estéticos.

Los valores numéricos son parámetros elegidos para ranking y no porcentajes de aprobación estética. La fórmula exacta se describe en la sección siguiente.


## 4. Score, componentes y evidencia

`compatibility_score` usa la escala 0–100 y `score_kind="heuristic_ranking_signal"`. Su fin es ordenar combinaciones bajo las mismas reglas. Un valor mayor indica más soporte para las relaciones implementadas; no expresa probabilidad, exactitud del análisis, aceptación comercial ni gusto de una persona.

| Componente | Interpretación |
| --- | --- |
| `temperature` | Relación de temperaturas con contexto de neutros y evidencia disponible |
| `lightness` | Continuidad o contraste de luminosidad según L* |
| `hue` | Soporte de reglas de tonos y de composición neutral, ponderado por proporciones |
| `contrast` | Relación entre contrastes internos y separación de las prendas |

Los componentes son valores 0–100 o `null` cuando faltan datos para evaluarlos. `evidence` conserva el resumen usado, soporte de reglas, proporciones y ajustes aplicados para interpretar el cálculo. `explanations` comunica por qué el par recibió las señales relevantes, incluyendo su diferencia media de L* y la ausencia de dominante cuando corresponde. `warnings` comunica evidencia parcial, intensidad conjunta, multicolor o textura informada cuando corresponde.

Dos prendas muy saturadas o dos estampados intensos pueden recibir un ajuste moderado por competencia visual. La saturación conjunta no anula una armonía explícita ni crea una prohibición. La complejidad multicolor genera contexto y cautela; no produce por sí sola un resultado de “bien” o “mal”.

### Fórmula operacional del score

Para cada prenda, sea p_i la proporción original de una muestra con p_i > 0 y P = Σ p_i. El peso retenido es w_i = p_i/P. Cada par cruzado A_i/B_j pesa w_ij = w_Ai × w_Bj; la suma de pesos cruzados es 1. Las referencias primario/secundario/acento se resuelven por RGB y Lab, con precedencia primario → acento → secundario, y no añaden peso. Los roles son evidencia de interpretación; no se convierten en multiplicadores arbitrarios.

La regla de cada par se aplica en este orden: ambos neutros (0.92), un neutro (0.90), monocromática (0.90), análoga (0.84), complementaria (0.90) o ninguna (0.55). Un grupo de tres tonos válido eleva el valor de los pares cubiertos por sus tonos al máximo entre la regla del par y 0.87. El componente hue es la suma Σ w_ij × valor_del_par. Por ello un tono pequeño no domina por sí solo toda una paleta.

Para las triples se comparan los huecos circulares ordenados de tres tonos: (120°,120°,120°) para triádica y (60°,150°,150°) para complementaria dividida, con error máximo 22°. El hueco más pequeño debe superar 36° para distinguir tres regiones. Se exige que los candidatos originales incluyan colores de ambas prendas; el soporte acumula los colores dentro de 18° de esas regiones, exige al menos 0.18 en cada prenda y media combinada de al menos 0.45. Cuando existen varios grupos del mismo tipo se elige el de mayor soporte, después menor error y finalmente un desempate determinista por tonos.

Para cada par, el valor de luminosidad es:

```text
L_ij = 0.70 + 0.20 × min(1, |L*_Ai − L*_Bj| / 40)
si el par es monocromático: L_ij = max(L_ij, 0.85)
lightness = Σ w_ij × L_ij
```

Temperatura usa 0.88 si una prenda es neutral, 0.82 si ambas comparten orientación cálida o fría y 0.86 para oposición cálido/frío respaldada por complementaria, complementaria dividida, triádica o neutro + acento. Una oposición sin esas estructuras usa 0.70. Si alguna temperatura es `unknown`, el componente queda `null` y se excluye de la media.

El contraste usa la masa de pares sin regla y sin soporte de triple:

```text
adjustment = min(0.20, suma_de_ajustes_activos)
contrast = max(0, 0.85 − 0.12 × unmatched_pair_mass − adjustment)
```

`both_saturated=0.08` se activa cuando ambas prendas son intensas: al menos 0.60 de su peso retenido tiene C* ≥ 50 o HSV S ≥ 0.60, o la colorimetría declara croma/saturación `high`. `both_complex=0.10` exige que ambas sean `multicolor` y tengan `internal_contrast="high"`. `both_intense_patterns=0.08` exige ambos patrones informados dentro de `printed`, `striped` o `checkered` y que cada prenda sea intensa por croma/saturación o declare `internal_contrast="high"`. Por eso también reconoce dos estampados blanco/negro contrastados. El límite 0.20 corresponde sólo a estos ajustes simultáneos; el término de masa sin regla permanece separado.

La media final, expresada primero en 0–1, es:

```text
score = Σ (peso_componente × componente_disponible)
        / Σ pesos_de_componentes_disponibles
compatibility_score = round(100 × clamp(score, 0, 1), 2)
```

Los pesos base son temperatura 0.10, luminosidad 0.20, tono 0.55 y contraste 0.15. Los componentes devueltos se multiplican por 100 y redondean a dos decimales. `active_component_weights` documenta la renormalización cuando falta temperatura. La calidad de entrada parcial no introduce una penalización automática al score.

Una armonía reportada y la contribución de sus pares son conceptos distintos: monocromática/análoga/complementaria se anuncian con soporte cruzado ≥ 0.20; `neutrals` exige masa neutral ≥ 0.70 en ambas prendas; `neutral_accent` exige masa neutral ≥ 0.70 en una y masa cromática ≥ 0.12 en la otra; claro/oscuro exige masa cruzada ≥ 0.35 de pares con ΔL* ≥ 30. Las triples usan sus propios mínimos. Los identificadores se devuelven en un orden estable.

### Evidencia y estado de resultado

Se devuelve `insufficient_evidence` y score/componentes `null` si alguna colorimetría usa una versión no admitida, declara `insufficient_evidence`, carece de paleta con masa positiva, tiene cobertura o suma original de proporciones inferior a 0.50, o su `foreground_confidence` es inferior a 0.70. No se interpreta la abstención como incompatibilidad.

Se devuelve `partial` con score calculado si alguna entrada no es `completed`, cobertura o suma de paleta es inferior a 0.85, cobertura y suma difieren en más de 0.01, una referencia de rol no pertenece a la paleta, los roles se superponen, o al menos 0.25 de la masa retenida tiene `confidence_score < 0.45`. Se añade `COMPATIBILITY_PROVISIONAL_INPUT` y una explicación; no se rebaja el score por ese estado. La evidencia baja de muestras añade `A:LOW_SWATCH_EVIDENCE` o `B:LOW_SWATCH_EVIDENCE`. Sin esas condiciones el estado es `completed`. Temperatura desconocida añade `TEMPERATURE_COMPONENT_UNAVAILABLE` y su componente queda `null`; esto por sí solo no cambia el estado a parcial.

La evidencia incluye `rules`, versiones/estados/cobertura de entrada, propiedades declaradas, `pattern_hints`, `tones_a`/`tones_b`, roles, `role_shares`, proporciones originales y normalizadas, tono RYB, Lab, saturación HSV, masa neutral, `mean_cross_palette_lightness_delta`, soportes de reglas, grupos de tres, pares cruzados, masa sin regla, ajustes, pesos activos y fórmula. Las advertencias previas se propagan con prefijo `A:` o `B:`.

## 5. Casos diferenciados

Los resultados siguientes describen la intención de las reglas; no son mediciones ejecutadas durante esta entrega. El resultado concreto depende de los HEX, proporciones, Lab y evidencia de cada paleta.

| Par | Señales esperables |
| --- | --- |
| Blanco + casi cualquier color | Base neutral, posible acento cromático y contraste claro/oscuro según L* |
| Negro + acento | Base neutral oscura, foco cromático y posible separación luminosa |
| Beige + marrón | Contexto neutral/terroso, continuidad de temperatura y posible variación de luminosidad |
| Azul + naranja | Posible complementariedad bajo la rueda RYB aproximada; revisa matices y proporciones reales |
| Azul + azul | Posible monocromática; diferencia continuidad de tono de igualdad de luminosidad/croma |
| Verde oliva + beige | Base beige neutral y tono oliva de croma contenido; temperatura y proporción contextualizan |
| Dos estampados intensos | Aviso y ajuste suave si los patrones se informaron y cada prenda es intensa o de contraste alto; incluye estampados blanco/negro contrastados sin analizar su geometría |
| Dos prendas muy saturadas | Competencia cromática moderada; conserva complementariedad u otra regla respaldada |
| Paletas multicolor | Evalúa relaciones ponderadas y grupos de tres tonos; evidencia de complejidad y proporciones |

La ausencia de una armonía clásica no equivale a incompatibilidad absoluta. Las reglas no codifican tendencias, códigos personales ni preferencias estéticas como verdades universales.

## 6. API privada FastAPI

`POST /api/v1/garments/compatibility` recibe JSON y usa el mismo bearer de servicio de `/api/v1`. No recibe archivos, imágenes, rutas ni URLs.

| Campo de entrada | Tipo y función |
| --- | --- |
| `garment_a`, `garment_b` | Objetos `GarmentColorimetry` completos del contrato existente |
| `rules_version` | Versión admitida `garment-compatibility-1.0.0`; tiene default explícito |
| `pattern_a`, `pattern_b` | Opcionales: `solid`, `printed`, `striped`, `checkered`, `unknown`; default `unknown` |
| `request_id` | String opcional, patrón `[A-Za-z0-9._:-]{1,100}`; `x-request-id` tiene precedencia |

Los patrones son metadatos aportados por el llamador. El motor no los adivina a partir de la paleta. Se deben traducir IDs o nombres de la taxonomía comercial a este vocabulario sólo cuando su significado esté establecido.

Ejemplo de construcción del cuerpo, sin ejecutar una petición:

```python
payload = {
    "garment_a": saved_result_a["data"]["colorimetry"],
    "garment_b": saved_result_b["data"]["colorimetry"],
    "rules_version": "garment-compatibility-1.0.0",
    "pattern_a": "solid",
    "pattern_b": "printed",
}
```

El identificador enviado en JSON se utiliza cuando falta `x-request-id`; si existe el header, se conserva el ID resuelto por el middleware. Laravel envía ambas formas para mantener correlación entre servicios.

La respuesta mantiene el envelope existente: `request_id`, `contract_version`, `status`, `data`, `warnings`, `abstentions`, `model`, `timing_ms` y `human_review_required`.

| Campo en `data` | Tipo |
| --- | --- |
| `version`, `rules_version` | Versión aplicada |
| `status` | `completed`, `partial` o `insufficient_evidence` |
| `score_kind` | `heuristic_ranking_signal` |
| `compatibility_score` | Número 0–100 o `null` |
| `harmony_types` | Lista de identificadores de armonías respaldadas |
| `components` | `temperature`, `lightness`, `hue`, `contrast`: número 0–100 o `null` |
| `explanations`, `warnings` | Listas de textos/códigos de explicación y advertencia |
| `evidence` | Evidencia del cálculo y ajustes |

El envelope replica las advertencias del resultado. Cuando el score es `null`, añade la abstención `COMPATIBILITY_INSUFFICIENT_EVIDENCE`. Declara `model_id="deterministic_garment_color_compatibility"`, `model_revision=rules_version` y mide `timing_ms.compatibility`; esa identidad describe un cálculo determinista, no inferencia visual.

El request usa contratos Pydantic y rechaza campos superiores no definidos. Admite como máximo ocho muestras en cada lista de paleta, secundarios y acentos; exige L* entre 0 y 100, a*/b* dentro de ±160, suma de proporciones de paleta no mayor que 1 (tolerancia 0.00001) y ausencia de muestras idénticas duplicadas por RGB/Lab. Mantiene los límites y finitud del contrato de colorimetría existente. Una versión de reglas no admitida o datos fuera del esquema producen un error de petición; una paleta válida en estructura pero insuficiente en evidencia produce una respuesta de abstención. Se conservan autenticación, límites de bytes y formato de errores del servicio existente.

`GET /api/v1/capabilities` anuncia `garments.compatibility` y `color_compatibility`: alcance `garment_pairs_only`, entrada `existing_garment_colorimetry`, versión de reglas, escala 0–100, tipo de score y las ocho armonías.

## 7. Integración Laravel y futuro ranking

`MatiAiClient` incorpora la operación `compatibility`, valida la estructura de la respuesta y exige coherencia entre `status` del envelope y `data.status`. `GarmentCompatibilityService::compare()` ofrece la entrada de integración interna:

```php
compare(
    array $colorimetryA,
    array $colorimetryB,
    string $patternA = 'unknown',
    string $patternB = 'unknown',
    ?string $requestId = null,
): array
```

Recibe snapshots de `data.colorimetry` ya generados; devuelve el envelope privado validado. No selecciona automáticamente el “último” resultado de una prenda ni reanaliza imágenes. El llamador debe elegir colorimetrías de las imágenes pertinentes y conservar su procedencia. En esta fase no se añade endpoint público Laravel, pantalla, migración ni persistencia de pares.

El ranking de outfits futuro podrá consumir el score como una señal junto a restricciones comerciales y otras señales. Tendrá que distinguir ausencia de score de un score bajo y considerar estado, advertencias y versiones. Esta entrega no determina pesos de un ranking general ni genera conjuntos de prendas.

## 8. Archivos de esta fase

Nuevos:

- `services/mati-ai/src/mati_ai/contracts/compatibility.py`
- `services/mati-ai/src/mati_ai/compatibility/__init__.py`
- `services/mati-ai/src/mati_ai/compatibility/rules.py`
- `services/mati-ai/src/mati_ai/compatibility/engine.py`
- `app/Services/AI/GarmentCompatibilityService.php`
- `DOCUMENTACION/IA/IMPLEMENTACION/05_compatibilidad_cromatica.md`
- `DOCUMENTACION/IA/IMPLEMENTACION/05_compatibilidad_cromatica.diff`

Modificados:

- `services/mati-ai/src/mati_ai/api/v1/routes.py`
- `app/Services/AI/MatiAiClient.php`
- `services/mati-ai/README.md`

Los cambios de colorimetría y embeddings ya presentes en el árbol de trabajo se conservan. No se añaden dependencias, Redis ni operaciones de despliegue.

## 9. Limitaciones

Los RGB, HEX y Lab de la entrada deben proceder de la misma muestra de colorimetría: el contrato comprueba formato/rangos, pero no recalcula su coherencia física. La rueda RYB es una aproximación artística versionada sobre valores sRGB; no representa pigmentos físicos ni garantiza una percepción idéntica en todas las condiciones. Lab y proporciones dependen de la colorimetría previa, que puede verse afectada por luz, balance de blancos, fondo, segmentación y fotografía.

La entrada no describe silueta, textura, brillo, distribución espacial del estampado ni área de las prendas dentro de un outfit. Los patrones opcionales permiten advertir complejidad, pero no analizar sus motivos o escala. Cada prenda pesa lo mismo en el resumen combinado.

La escala es heurística y no está calibrada con preferencias de usuarios ni evaluaciones comerciales de pares reales. El motor conserva explicaciones para que un ranking futuro pueda revisar su comportamiento. No confundir `confidence_score` de una muestra de color con el score de compatibilidad del par.

Una relación de tres tonos requiere evidencia de los tres: el motor evita afirmar una triada o complementaria dividida a partir de dos colores. Una paleta multicolor puede contener varios subconjuntos armónicos sin que toda su distribución resulte equivalente. Las proporciones y ajustes moderan esas señales.

## 10. Diff y status

Diff propio de esta fase: [05_compatibilidad_cromatica.diff](05_compatibilidad_cromatica.diff). Incluye los ocho archivos de implementación/README y esta guía. Se calculó contra las copias inmediatamente anteriores a esta fase para los tres archivos ya modificados; los archivos nuevos se comparan con vacío. El propio artefacto .diff queda excluido. No se usó staging ni se alteraron cambios anteriores.

### Resumen del diff de implementación/README de esta fase

```text
app/Services/AI/MatiAiClient.php | +47 -0
services/mati-ai/README.md | +8 -0
services/mati-ai/src/mati_ai/api/v1/routes.py | +52 -0
app/Services/AI/GarmentCompatibilityService.php | +34 -0 (nuevo)
services/mati-ai/src/mati_ai/contracts/compatibility.py | +71 -0 (nuevo)
services/mati-ai/src/mati_ai/compatibility/__init__.py | +1 -0 (nuevo)
services/mati-ai/src/mati_ai/compatibility/rules.py | +71 -0 (nuevo)
services/mati-ai/src/mati_ai/compatibility/engine.py | +463 -0 (nuevo)
8 archivos de implementación/README; +747 -0 líneas.
```

A ese inventario se suman esta guía y el artefacto de diff: diez archivos creados o modificados por esta fase. El código y los ejemplos documentados no se ejecutaron.

### git diff --stat acumulado respecto de HEAD

Esta captura incluye colorimetría, embeddings y otros cambios anteriores. Los archivos nuevos sin seguimiento no aparecen en este stat.

```text
.env.example                                       |   4 +
 app/Console/Commands/IndexMatiAiCatalog.php        | 153 ++++--
 app/Http/Controllers/Superadmin/AiController.php   | 114 ++++-
 app/Services/AI/AiReviewService.php                |  58 ++-
 app/Services/AI/CatalogAiService.php               |  29 +-
 app/Services/AI/MatiAiClient.php                   |  59 ++-
 compose.yaml                                       |   6 +-
 config/mati_ai.php                                 |   8 +
 .../Superadmin/GarmentIntelligentAssistant.tsx     | 143 +++++-
 resources/js/lib/garmentAssistant.ts               |  36 ++
 routes/web.php                                     |   3 +
 services/mati-ai/README.md                         |  22 +
 services/mati-ai/src/mati_ai/api/v1/routes.py      | 126 ++++-
 services/mati-ai/src/mati_ai/contracts/common.py   |   2 +-
 .../src/mati_ai/contracts/identification.py        |   9 +-
 services/mati-ai/src/mati_ai/contracts/vision.py   |   2 +
 services/mati-ai/src/mati_ai/core/model_manager.py | 282 ++++++++--
 .../mati-ai/src/mati_ai/identification/matching.py |   7 +-
 services/mati-ai/src/mati_ai/vision/color.py       | 568 +++++++++++++++++----
 19 files changed, 1398 insertions(+), 233 deletions(-)
```

### git status --short

Rama: `feature/ia-colorimetria`. Estado acumulado; los prefijos ` M` describen cambios sin staging y `??` archivos nuevos sin seguimiento.

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

Estado de ejecución: código y documentación escritos; no se ejecutaron tests, lint, builds, inferencias, benchmarks, migraciones, llamadas de comprobación a la API ni validación final. Sin staging, commit ni push. No se afirma que la API, contratos o integración hayan pasado comprobaciones de ejecución. La captura de Git y la creación del diff son documentación de cambios, no una validación del motor.
