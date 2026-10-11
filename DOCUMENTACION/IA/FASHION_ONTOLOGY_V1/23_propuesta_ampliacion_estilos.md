# Propuesta de ampliación de estilos, estéticas y uso

Fecha de revisión: **2026-10-08**, zona `America/La_Paz`. Estado: **DRAFT_FOR_REVIEW — NEEDS MATI REVIEW**. Origen: decisión humana 7 y requisito de Trend Intelligence del prompt de revisión integral. Se aprueba la separación conceptual; los valores y relaciones de esta propuesta permanecen pendientes.

Este documento amplía la propuesta de vocabulario sin promocionar conceptos a `styles.json`, `usage.json`, labels de entrenamiento ni Product Intelligence. Los nombres `candidate.*` son identificadores documentales de propuestas, no referencias válidas del contrato estable. No existe un objetivo numérico de ampliación. Las definiciones y ejemplos son síntesis originales para MORE; las fuentes externas proporcionan contraste histórico y semántico, no aprobación de Mati.

## 1. Vocabulario actual conservado

Auditoría de los archivos fuente: **10 estilos, 4 estéticas, 7 dress codes y 5 niveles de formalidad**. Todos conservan IDs, definiciones y `NEEDS MATI REVIEW`. Las diez ocasiones, cuatro estaciones y cinco afinidades climáticas de `usage.json` también permanecen intactas. Su presencia no acredita datos anotados, acuerdo humano, rendimiento de un modelo ni relevancia comercial local.

| Dominio | IDs conservados (prefijo indicado) | Tratamiento |
|---|---|---|
| `value.style.*` | `minimalist`, `classic`, `preppy`, `streetwear`, `bohemian`, `sport_inspired`, `romantic`, `utilitarian`, `avant_garde`, `western_inspired` | Afinidades independientes y potencialmente superpuestas |
| `value.aesthetic.*` | `old_money`, `y2k`, `retro`, `techwear` | Referencias contextualizadas; no labels visuales entrenables en esta fase |
| `value.dress_code.*` | `casual`, `smart_casual`, `business_casual`, `business_formal`, `cocktail`, `black_tie`, `white_tie` | Expectativas declaradas por contexto/evento |
| `value.formality.*` | `0`, `1`, `2`, `3`, `4` | Orden contextual; no distancias iguales ni equivalencias automáticas |

No se depreca ningún concepto. Ni `old_money` ni `classic` implican riqueza; `retro` no certifica antigüedad, y `techwear` no acredita impermeabilidad. El dress code y la formalidad de un look no se transfieren automáticamente a cada componente.

## 2. Modificaciones de definición propuestas

Estas aclaraciones justificadas orientan la revisión humana. Se mantienen fuera de los JSON hasta revisar su rubricado, ejemplos y compatibilidad; no sustituyen silenciosamente las definiciones actuales.

| Concepto existente | Aclaración propuesta y razón | Positivo didáctico | Negativo/frontera | Fuente y límite |
|---|---|---|---|---|
| `value.style.minimalist` | La contención ornamental admite detalles que organizan estructura; evitar convertir “liso” en condición suficiente o “algún adorno” en exclusión | Silueta contenida con una costura/patrón usado para ordenar la construcción | Camiseta lisa en un outfit de ornamentación exuberante; observar prenda y conjunto por separado | [FIT Minimalism/Maximalism](https://www.fitnyc.edu/museum/exhibitions/minimalism-maximalism.php), historia del diseño; no prueba vigencia 2026 |
| `value.style.classic` | Pedir un repertorio de referencia y contexto; evitar la promesa universal “atemporal” y el prior blazer → classic | Sastrería de proporciones contenidas dentro de un repertorio acordado por Mati | Blazer con volumen experimental o ornamentación extrema; la categoría sola no decide | Propuesta MORE a partir de la definición existente; pendiente repertorio local |
| `value.style.streetwear` | Especificar referencias observables por contexto; “urbano” solo es demasiado amplio. Mantener influencias históricas sin atribuir identidad social | Combinación con referencias documentadas del repertorio urbano elegido | Una prenda usada en una ciudad o un logotipo aislado | [FIT Hip Hop Style](https://www.fitnyc.edu/museum/exhibitions/hip-hop-style/), evidencia de un repertorio influyente, no equivalencia hip hop = todo streetwear |
| `value.style.utilitarian` | Diferenciar códigos visibles de función comprobada y admitir reinterpretaciones | Bolsillos de utilidad y construcción inspirada en ropa de trabajo en contexto coherente | Bolsillo ornamental aislado; impermeabilidad supuesta por estética | [FIT Beyond Utility](https://www.fitnyc.edu/museum/exhibitions/beyond-utility/index.php), exposición 2026; no certificación de productos Mati |
| `value.aesthetic.retro` / `y2k` | Registrar época de referencia como evidencia descriptiva externa; no crear una estética estable por década ni confundir referencia con fabricación | Look que cita un repertorio documentado de cambio de milenio | Producto fabricado en 2026 o vestido “viejo” sin evidencia de diseño | [FIT Fashion in the Nineties](https://www.fitnyc.edu/museum/exhibitions/fashion-in-the-nineties.php), periodización no rígida |

Para las otras afinidades se conservan las descripciones iniciales; faltan ejemplos reales adjudicados por Mati. Ningún cambio propuesto transforma rasgos culturales en una etiqueta sobre la persona.

## 3. Nuevos candidatos de estilo

Aplicabilidad común: afinidad opcional de prendas, calzado y accesorios si hay rasgos suficientes; outfit y prenda se anotan por separado. Un componente básico puede quedar `UNKNOWN`, aunque contribuya a un outfit identificado. La falta de una afinidad no vuelve `NOT_APPLICABLE` a toda la familia. No hay obligatoriedad de género, edad, origen cultural, fibra ni categoría comercial.

### `candidate.style.maximalist` — Maximalista / Maximalist

**Identidad propuesta:** afinidad con exuberancia deliberada de ornamentación, proporciones o yuxtaposición visual. La combinación de varios rasgos pesa más que un color vivo o estampado aislado. En MORE se propone en el eje de estilo por comparabilidad con `minimalist`; la fuente utiliza también el término estética y no obliga a copiar su clasificación.

**Diferenciación y relaciones:** contrasta con `value.style.minimalist`, sin regla de exclusión mutua por prenda/outfit. Puede coexistir con `romantic`, `bohemian` o `avant_garde`; esos IDs no representan por sí solos intensidad ornamental. No equivale a “caro”, “calidad alta” ni “muchas prendas”. Sinónimos lexicales propuestos: maximalismo / maximalism; no tratar eclecticismo o extravagancia como sinónimos exactos.

**Rasgos y ejemplos didácticos:** positivo: combinación revisada de patrones, adornos y proporciones deliberadamente exuberantes. Negativo: prenda roja lisa de silueta contenida. Frontera: una prenda de líneas limpias con volumen grande; registrar ambos aspectos y adjudicar. Aplicable a `upper`, `lower`, `outerwear`, `one_piece` y accesorios con diseño relevante; una pieza básica de un outfit no hereda la afinidad completa.

**Fuente verificable:** [FIT Minimalism/Maximalism](https://www.fitnyc.edu/museum/exhibitions/minimalism-maximalism.php), exposición 2019. La investigación documenta exuberancia y referencias visuales combinadas. La definición operacional anterior es propuesta MORE.

**Utilidad de identidad separada:** distinguir preferencia por expresión exuberante de preferencia por minimalismo sin forzar toda ornamentación a bohemian/romantic. Es una utilidad potencial del recomendador; faltan ejemplos y demanda de Mati. **Estado: NEEDS MATI REVIEW**; pendiente rúbrica de intensidad, casos fronterizos y soporte real del catálogo.

### `candidate.style.punk_inspired` — Inspiración punk / Punk-inspired

**Identidad propuesta:** afinidad a un repertorio punk documentado de intervención y personalización visible: bricolaje, herrajes expresivos, grafismos y desgaste/rotura deliberada dentro de un contexto de diseño. La observación de deterioro no distingue por sí sola diseño de daño.

**Diferenciación y relaciones:** relacionada con `streetwear` y `avant_garde`, pero no subordinada automáticamente a ninguno. La personalización y los símbolos de ese repertorio aportan una distinción semántica; “ropa negra” y “rock” son demasiado amplios. Punk / punk fashion se consideran posibles términos de consulta contextualizados, no alias aprobados. Una etiqueta sobre la prenda no declara afiliación política, musical o social del usuario.

**Rasgos y ejemplos didácticos:** positivo: chaqueta intervenida con parches, grafismo y herrajes en un repertorio revisado. Negativo: chaqueta lisa oscura sin otra evidencia. Frontera: denim rasgado por uso o prenda de pasarela deconstruida sin referencia punk; conservar evidencia y alternativas. Aplicable en especial a `upper`, `lower`, `outerwear`, `footwear`, `jewelry` y `accessory`; no por pertenecer a esas familias se asigna afinidad.

**Fuente verificable:** [The Met, PUNK: Chaos to Couture](https://www.metmuseum.org/exhibitions/listings/2013/punk), exposición 2013 que distingue materiales, técnicas y símbolos de diseño DIY. La historia documentada no demuestra popularidad actual ni permite reutilizar fotografías sin derechos.

**Utilidad de identidad separada:** evitar que personalización punk se pierda bajo “urbano” o se confunda con desgaste genérico. **Estado: NEEDS MATI REVIEW**; pendiente disponibilidad de productos pertinentes, límites frente a grunge y ejemplos de daño frente a diseño.

### `candidate.style.gothic_inspired` — Inspiración gótica / Gothic-inspired

**Identidad propuesta:** afinidad a repertorios góticos de diseño con referencias documentadas, que pueden combinar dramatismo, referencias históricas, contraste de transparencias/ornamentación e imaginarios oscuros. El negro aislado no determina la afinidad; los subestilos no comparten todos los rasgos.

**Diferenciación y relaciones:** puede relacionarse con `romantic`, `avant_garde` y `retro`; ni romantic ni retro capturan toda la referencia gótica. Gothic / goth son términos de consulta candidatos que pueden tener otros sentidos; no alias irrestrictos. La elección MORE del eje de estilo requiere adjudicación, sin duplicar inmediatamente otro ID de estética “gothic”. No inferir religión, salud mental, identidad o pertenencia subcultural de una persona.

**Rasgos y ejemplos didácticos:** positivo: prenda o combinación cuya construcción dramática y referencias historicistas coincidan con un repertorio gótico documentado. Negativo: vestido negro sencillo de oficina. Frontera: encaje negro romántico o ropa de luto sin evidencia adicional; ambas referencias pueden coexistir o permanecer desconocidas. Aplicable a `one_piece`, `upper`, `lower`, `outerwear`, `footwear` y accesorios relevantes; no se exige material, género ni color universal.

**Fuente verificable:** [FIT Gothic: Dark Glamour](https://www.fitnyc.edu/museum/exhibitions/gothic-dark-glamour.php), exposición 2008–2009 que reúne diseño y distintos repertorios subculturales. No autoriza a importar automáticamente Victorian goth, cyber-goth u otras variantes.

**Utilidad de identidad separada:** conservar una referencia histórica reconocible que no se reduce a color o romanticismo. **Estado: NEEDS MATI REVIEW**; pendiente repertorio anotable, decisión de dominio style/aesthetic y ejemplos reales de Mati. No promocionar ambos dominios por el mismo nombre.

### `candidate.style.grunge_inspired` — Inspiración grunge / Grunge-inspired

**Identidad propuesta:** afinidad a un repertorio grunge documentado de superposición relajada, combinaciones deliberadamente poco coordinadas y apariencia gastada. El efecto de diseño no demuestra prenda usada, fabricación antigua, precio bajo ni condición socioeconómica.

**Diferenciación y relaciones:** relacionada con `streetwear`, `utilitarian`, `retro` y el candidato punk; comparte elementos sin ser alias. El layering es una configuración independiente y no bastaría para asignar grunge. Grunge es término de consulta candidato; “casual”, “oversized” y “noventero” no son sinónimos equivalentes.

**Rasgos y ejemplos didácticos:** positivo: combinación de capas, apariencias gastadas y contrastes de prendas en un repertorio revisado. Negativo: camisa a cuadros sola o pantalón relaxed fit. Frontera: outfit utilitario para frío con varias capas, sin contexto grunge; no forzar clasificación. Aplicable sobre todo a `upper`, `lower`, `outerwear`, `one_piece`, `footwear` y accesorios; exige contexto al anotar piezas básicas.

**Fuente verificable:** [FIT Reinvention & Restlessness: Fashion in the Nineties](https://www.fitnyc.edu/museum/exhibitions/fashion-in-the-nineties.php), exposición 2022 sobre los años noventa. El análisis curatorial distingue este repertorio de minimalismo y deconstrucción. No se traslada la década a una fecha de fabricación.

**Utilidad de identidad separada:** evitar que una misma combinación se convierta en una etiqueta inespecífica “retro/urbano”. **Estado: NEEDS MATI REVIEW**; pendiente comparar anotaciones con punk y con layering funcional. Si la distinción no produce acuerdo ni utilidad local, registrar relación contextual sin introducir ID estable.

## 4. Estéticas: ampliación por especificación y relaciones

No se justifica añadir otra estética genérica solo para aumentar el catálogo. Se propone ampliar las descripciones y relaciones de las cuatro existentes, manteniendo los IDs. Una futura propuesta podrá especificar periodo/repertorio de `retro`, referencias de `y2k`, asociaciones de `old_money` con `classic`/`preppy`/`minimalist`, y de `techwear` con `utilitarian`/`sport_inspired`. Todas son relaciones contextuales pendientes; no jerarquías ni equivalencias obligatorias.

Una estética temporal necesita evidencias fechadas del uso del término además de sus rasgos. Si falta esa evidencia se conserva el nombre observado como texto externo y su incertidumbre. “Nuevo nombre” no crea automáticamente identidad independiente. No se deduce actualidad, crecimiento o aceptación en El Alto a partir de las exposiciones históricas consultadas.

## 5. Códigos y formalidad: una ampliación candidata

`candidate.dress_code.black_tie_optional` — **Etiqueta black tie opcional / Black tie optional**. Identidad propuesta: expectativa declarada de evento que admite alternativas formales a black tie. Relación candidata: variante de `value.dress_code.black_tie`, conservando explícitamente las alternativas admitidas; no es alias exacto. Diferenciador: cambia el conjunto de opciones aceptables del evento y puede influir en una recomendación realizable con inventario.

Positivo didáctico: invitación que declara black tie opcional y confirma alternativas. Negativo: asumir “opcional” porque el inventario no dispone de una prenda requerida. Frontera: evento que dice solo “formal”; solicitar aclaración y mantener `UNKNOWN` en lugar de equiparar códigos. Aplicabilidad: contexto de evento y adecuación revisada de outfit; no inferencia visual de una prenda individual ni obligación de un género determinado.

Posibles términos de consulta: black-tie optional / black tie opcional. Solapamiento con business formal o cocktail no establece equivalencia universal. [Emily Post](https://emilypost.com/advice/attire-guide-dress-codes-from-casual-to-white-tie) distingue black tie optional y creative black tie; esa guía cultural no es una norma universal boliviana. **Estado: NEEDS MATI REVIEW**; pendiente comprobar demanda, expectativas locales y si conviene modelar el código como ID o como regla contextual de opciones. No se añade a `usage.json`.

Festive y creative black tie se proponen inicialmente como modificadores contextuales de la invitación, no estilos nuevos. “Semiformal” necesita adjudicación local antes de convertirse en alias de cocktail, smart casual o un nuevo código. Los cinco ranks de formalidad se conservan: no existe evidencia para añadir escalones ni distancias numéricas iguales. Mati debe revisar anclajes por contexto y su acuerdo entre revisores.

## 6. Alias, variantes y conceptos redundantes

| Término observado/propuesto | Tratamiento recomendado | Destino o relación candidata | Por qué no crear otro estilo automáticamente | Estado |
|---|---|---|---|---|
| Minimalismo / minimalism | Alias lexical candidato | `value.style.minimalist` | Diferencia nominal, no definición independiente | NEEDS MATI REVIEW |
| Boho | Alias condicionado al significado de la fuente | `value.style.bohemian` | Puede abreviar bohemian, pero un uso concreto puede incluir otro repertorio | NEEDS MATI REVIEW; no fuente terminológica nueva verificada |
| Ivy / Ivy-inspired | Variante o relación parcial | `value.style.preppy` | Repertorio histórico con solapamientos, no equivalencia de todo preppy | NEEDS MATI REVIEW; referencia histórica existente [FIT Ivy Style](https://sites.fitnyc.edu/depts/museum/Ivy_Style/) |
| Athleisure | Variante/configuración de uso candidata | `value.style.sport_inspired` + contexto cotidiano | La mezcla de códigos deportivos y uso cotidiano no certifica aptitud técnica | NEEDS MATI REVIEW; [V&A Saul Nash](https://www.vam.ac.uk/articles/fashion-in-motion-saul-nash) documenta reinterpretación de códigos |
| Workwear-inspired / military-inspired | Subestilo/repertorio candidato | `value.style.utilitarian` | No convertir cada arquetipo de ropa de utilidad en una familia de estilos | NEEDS MATI REVIEW; [FIT Beyond Utility](https://www.fitnyc.edu/museum/exhibitions/beyond-utility/index.php) |
| Falda sobre pantalón / skirt over trousers | Configuración de prendas/layering | IDs de prendas y relaciones espaciales existentes | Describe relación de uso, no necesariamente estilo nuevo | Política de separación aprobada; configuración concreta pendiente |
| Un color, estampado o silueta de una temporada | Observación temporal de atributo | Valor existente del atributo pertinente | Cambia frecuencia/configuración, no necesariamente significado ontológico | Evidencia temporal requerida |
| Quiet luxury / lujo silencioso | Nombre externo pendiente de investigación | Comparar `minimalist`, `classic` y `old_money`; no declararlos sinónimos | No confundir contención visual con precio, patrimonio o calidad | RESEARCH INCOMPLETE; sin popularidad ni identidad aprobada |
| Gorpcore, coquette, dark academia u otro nombre viral | Nombre externo pendiente | Comparar repertorios, atributos, estéticas y layering según evidencia | El nombre por sí solo no prueba novedad | RESEARCH INCOMPLETE; no incorporados ni definidos como hechos |
| Vintage | Condición histórica o término comercial que necesita procedencia | Mantener separado de `value.aesthetic.retro` | Apariencia histórica no demuestra antigüedad real | Evidencia documental requerida |

Ningún alias candidato se escribe en `aliases_es` / `aliases_en` durante esta revisión. No hay duplicados comprobados que justifiquen borrar IDs actuales; los riesgos de redundancia se resuelven comparando definiciones y casos de uso, no por similitud nominal.

## 7. Evaluación de novedad y promoción

El documento 21 contiene el contrato futuro de Trend Intelligence. El siguiente procedimiento expresa sus condiciones semánticas; no implementa detección ni añade estados incompatibles al contrato de atributos.

1. Conservar término original, fuente, fecha de publicación si está disponible, fecha de observación, lugar, periodo estudiado y limitaciones. Falta de una fecha no equivale a fecha de consulta.
2. Describir rasgos, prendas/atributos y unidad observada (prenda, outfit, configuración o discurso). Distinguir evidencia primaria de interpretación MORE.
3. Comparar con conceptos existentes y candidatos: significado, rasgos distinguibles, positivos, negativos y fronteras. Documentar también las alternativas alias, variante, combinación, estética, layering y microtendencia.
4. Evaluar contexto cultural, persistencia y utilidad de una identidad aparte. No hay duración, score ni número de fuentes universal que apruebe un concepto; deben justificarse con el caso. Un coseno alto o bajo no resuelve la evaluación.
5. Emitir propuesta con relación sugerida, incertidumbre, compatibilidad y ejemplos. Si falta evidencia, conservar indeterminación. Detectar tendencia y aprobar significado son decisiones diferentes.
6. Mati confirma, corrige, rechaza o aplaza la propuesta; el responsable técnico revisa referencias, versiones y migración. Registrar autor y fecha reales sin rellenar aprobación por defecto.
7. Solo después de una aprobación verificable se propone incorporar el valor al vocabulario versionado, adaptar aplicabilidad/ejemplos y ejecutar integridad. Anotación aprobada, dataset validado, entrenamiento, evaluación y autorización productiva siguen como fases distintas.

Ejemplos de evaluación: “minimalismo” puede reutilizar `minimalist` si la fuente expresa el mismo significado; un look preppy con elementos deportivos puede ser combinación; un color de pasarela es observación de atributo; falda sobre pantalón es configuración. Punk, gothic, grunge y maximalist justifican aquí investigar identidad separada por sus repertorios, pero Mati puede elegir no incorporarlos. La separación de principios aprobada no equivale a aprobación de esos resultados.

## 8. Evidencia temporal y contextualización

No se midió popularidad ni crecimiento. Las fuentes museísticas acreditan usos históricos; no stock, ventas, tendencias actuales ni preferencias de clientes de Mati. La utilidad local de los candidatos queda pendiente de productos reales, revisión del administrador y, en una fase futura autorizada, datos comerciales pertinentes.

Una serie futura de interés de búsqueda deberá conservar consulta/tópico, idioma, geografía, ventana, zona horaria, método de normalización y fecha de extracción. [Google Trends FAQ](https://support.google.com/trends/answer/4365533?hl=en) explica que sus datos son muestras normalizadas, que el mismo valor entre regiones no acredita igual volumen, y que un pico de búsqueda no es una encuesta. Un cero o falta de datos no prueba ausencia de interés; las ventas no se infieren de búsquedas. Este documento no conecta Google Trends ni descarga series.

Antes de traducir observaciones internacionales a outfits, el contrato futuro considera lugar y fecha de uso, hemisferio, estación, clima fiable, ocasión, código declarado, preferencias y compatibilidad, además de inventario/variantes reales de Laravel. Una tendencia de verano europea no se recomienda automáticamente para invierno en La Paz. Se debe poder distinguir representabilidad parcial, outfit completo e inventario insuficiente, sin crear paquetes comerciales ni disponibilidad ficticia.

## 9. Evidencia y pendientes de aceptación

Las URLs, fechas y resúmenes verificables están en `research_sources_revision.json`, registro separado de la investigación histórica. Se consultaron páginas primarias mediante web; no se descargaron fotos, series, datasets ni modelos, ni se importaron taxonomías completas. Un enlace público no concede derechos de imagen. Las definiciones MORE no son citas ni estándares universales.

Para cada candidato queda pendiente identificar al responsable real, aportar productos y contraejemplos revisados, resolver dominio y relaciones, registrar discrepancias entre revisores y decisión de Mati, y validar compatibilidad antes de promocionar. La existencia del documento no aporta esas evidencias ni congela V1. El vocabulario estable continúa con 10 estilos y 4 estéticas, y la propuesta contiene cuatro candidatos de estilo y un candidato de dress code, todos fuera de los conteos del contrato estable.
