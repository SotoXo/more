# Estilos, formalidad y ocasiones

El vocabulario está en `styles.json` y `usage.json`; las definiciones propias son propuestas. La separación entre style y aesthetic es una convención operacional de MORE para gobernanza y estabilidad, no un estándar universal del lenguaje profesional. FIT trata minimalismo/preppy e influencias utilitarias como expresiones históricas reinterpretables; Emily Post sitúa dress codes en expectativas de evento. Fuentes: [FIT Minimalism/Maximalism](https://www.fitnyc.edu/museum/exhibitions/minimalism-maximalism.php), [Ivy Style](https://sites.fitnyc.edu/depts/museum/Ivy_Style/), [Emily Post](https://emilypost.com/advice/attire-guide-dress-codes-from-casual-to-white-tie).

La **decisión humana 7** aprueba separar estilo, estética, código de vestimenta y formalidad; no aprueba cada valor del vocabulario. La revisión de 2026-10-08 conserva sus IDs y mantiene `DRAFT_FOR_REVIEW` / `NEEDS MATI REVIEW`. La investigación y las ampliaciones propuestas se registran separadamente en [23_propuesta_ampliacion_estilos.md](23_propuesta_ampliacion_estilos.md) y `research_sources_revision.json`; no se incorporan automáticamente a `styles.json` ni a `usage.json`.

| Eje | Pregunta operacional | Evidencia necesaria | Inferencia que debe evitarse |
|---|---|---|---|
| Estilo | ¿Con qué lenguaje de diseño mantiene afinidad la prenda o combinación? | Rasgos combinados y rubricado contextual humano | Una categoría comercial o un único detalle determina el estilo |
| Estética | ¿Con qué referencia visual/discursiva contextual se relaciona? | Descripción, referencias, lugar y periodo cuando corresponda | Nombre estético demuestra patrimonio, origen cultural o prestaciones |
| Código de vestimenta | ¿Qué expectativas declara el evento u organización? | Instrucción del evento, contexto cultural y revisión | El código se obtiene solo de una fotografía |
| Formalidad | ¿Qué grado ordinal de presentación tiene en ese contexto? | Anclajes humanos y contexto explícito | El rango equivale universalmente a un dress code o a calidad |

Una afinidad de prenda y una afinidad de outfit son conclusiones diferentes. Un producto puede aportar un rasgo al look sin que todos sus componentes hereden la misma etiqueta. Cuando falte evidencia se conserva `UNKNOWN`; que un estilo sea conocido no obliga a conocer su estética, código o formalidad.

## Núcleo multi-label

El núcleo de 10 estilos contiene minimalist, classic, preppy, streetwear, bohemian, sport_inspired, romantic, utilitarian, avant_garde y western_inspired. Cada afinidad necesita ejemplos positivos/negativos y adjudicación de Mati. El núcleo reduce proliferación de microestéticas; no afirma que los estilos sean disjuntos o universales.

Las cuatro referencias estéticas son old_money, y2k, retro y techwear. Old money no atribuye patrimonio; Y2K no acredita año de fabricación; retro no certifica antigüedad; techwear no certifica impermeabilidad o prestaciones. Vintage auténtico requiere procedencia documental y no se ofrece como predicción visual. Las estéticas no se habilitan como labels visuales entrenables en esta fase.

`attribute.styles` es multi-label. En el contrato futuro puede guardar IDs con afinidades independientes y procedencia por score, sin sumar uno. `probability` solo describe probabilidad con validación de calibración; coseno es coseno; rule_score declara escala; human_confirmed no inventa score. El contrato no contiene scores reales de estilo ni priors obligatorios blazer→classic. La ontología define el eje; Fashion Knowledge explica asociaciones; el modelo futuro estima afinidades después de obtener labels humanos.

## Código, formalidad y ocasión

Los siete dress codes son casual, smart_casual, business_casual, business_formal, cocktail, black_tie y white_tie. Son expectativas contextuales, no identidades de prenda ni estilos culturales. Su adecuación requiere evento/organización, cultura y revisión. No se decide dress code a partir de la persona fotografiada.

MORE propone formalidad ordinal de cinco niveles: 0 muy informal, 1 informal, 2 cuidado, 3 formal, 4 ceremonial. Se elige ordinal porque existe orden útil, pero no distancias iguales justificadas. Las categorías discretas pierden orden; un score continuo sugeriría una precisión sin rubricado. Los ranks no son grados de calidad ni equivalencias automáticas con códigos. Un futuro modelo podrá ofrecer distribución/rank e incertidumbre; su validación usará MAE ordinal y weighted kappa. Los anclajes por contexto requieren revisión humana antes de congelar.

Las diez ocasiones son everyday, workplace, ceremony, wedding_guest, celebration, date, travel, exercise, outdoor_leisure y beach_pool. Son contextos de actividad/evento multi-label. Office se normaliza semánticamente hacia workplace cuando procede, sin universalizar ambientes de trabajo. Evening pertenece a contexto horario, no categoría de ocasión; wedding_guest distingue rol y no implica vestir formal. Sport-inspired es estilo; exercise es ocasión; aptitud técnica es dato documentado aparte.

## Estacionalidad

calendar_seasons representa spring/summer/autumn/winter con geografía y hemisferio. climate_suitability separa warm_weather, cool_weather, cold_weather, transitional y all_season. La temporada comercial permanece en Laravel. PV y OI solo tienen mappings candidatos contextualizados; RESORT/CAPSULE/PERMANENTE/CARNAVALERA/MUNDIAL no tienen equivalencia directa. PERMANENTE no demuestra all_season. En La Paz, estación calendárica, altura y clima no deben confundirse.

## Novedad conceptual y tendencias

El núcleo actual se conserva como vocabulario referenciable en borrador; su tamaño no limita propuestas futuras. Ante un nombre nuevo, la revisión deberá distinguir alias, variante/subestilo, combinación, estética relacionada, configuración de prendas/layering, microtendencia temporal, concepto nuevo e indeterminación por evidencia insuficiente. Cada conclusión compara significado, rasgos visuales, atributos, conceptos vecinos, contexto cultural, fechas, persistencia y utilidad de una identidad separada. La similitud numérica solo aporta evidencia auxiliar.

Una tendencia puede consistir en cambios de color, patrón o silueta sin requerir otro estilo. FIT documenta esa diversidad en [Trend-ology](https://www.fitnyc.edu/museum/exhibitions/trend-ology.php), exposición de 2013–2014; esa evidencia histórica no demuestra vigencia actual. El diseño futuro de Trend Intelligence queda en el documento 21 y las propuestas concretas en el documento 23. Una propuesta requiere evidencia y alternativas; Mati valida su utilidad y significado comercial, y el responsable técnico valida compatibilidad y versionado. Rechazar o aplazar una propuesta no elimina las observaciones originales.

La detección internacional se separa de la recomendación contextual. La fecha, lugar y hemisferio de la observación no son necesariamente los del cliente; tampoco una temporada comercial certifica clima. El motor futuro deberá comprobar ocasión, preferencias, clima fiable, compatibilidad, stock y variantes reales en Laravel antes de recomendar. La disponibilidad puede ser parcial, completa o insuficiente sin alterar la identidad de la tendencia.

Todos los vocabularios de afinidad y uso quedan `NEEDS MATI REVIEW`. Esta entrega no contiene mediciones reales de popularidad, umbrales de aceptación aprendidos ni ejecución de recomendaciones. Ninguna propuesta habilita entrenamiento ni uso productivo.
