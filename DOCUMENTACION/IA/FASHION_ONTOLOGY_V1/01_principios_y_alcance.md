# 01. Principios y alcance de Fashion Ontology V1

Fashion Ontology V1 es el contrato semántico propuesto de MORE / Mati Boutique. Define identidades, atributos, vocabularios, aplicabilidad y relaciones para que futuros sistemas compartan el mismo significado. Su versión técnica es `1.1.0` y el estado de los artefactos es `DRAFT_FOR_REVIEW`: una versión numerada permite trazabilidad; no constituye aprobación de dominio ni despliegue.

## Responsabilidades arquitectónicas

| Capa | Responsabilidad | Límite de autoridad |
| --- | --- | --- |
| Modelo visual | Percibe señales de la imagen y propone hipótesis | No redefine conceptos ni confirma composición oculta o realidad comercial |
| Ontología | Estructura identidades, ejes y relaciones | No decide qué comprar ni qué está de moda ahora |
| Fashion Knowledge | Explica construcción, contexto y heurísticas con fuentes | Una preferencia de styling no se convierte en restricción estructural |
| Trend Intelligence | Aporta observaciones temporales, lugar y actualidad | No reescribe automáticamente significados permanentes |
| User Style Profile | Representa preferencias declaradas o aprendidas del usuario | No cambia la definición de slim fit o minimalista |
| Recommendation Engine | Decide candidatos usando producto, usuario y contexto | Su fórmula y políticas no forman parte de esta entrega |
| Laravel | Confirma IDs, clasificación, precio, stock, SKU y disponibilidad del negocio | La ontología conserva trazabilidad, sin sustituir la DB comercial |
| LLM / Mati Assistant | Conversa y orquesta consultas y herramientas | Consume conceptos; no define verdad ontológica ni inventa hechos comerciales |

Product Intelligence es la instancia de lo que sabemos de un producto: combina taxonomía, atributos, evidencia, procedencia, revisión y versiones. La ontología define qué significa blazer; Product Intelligence describe un blazer concreto; Laravel confirma su identidad y situación comercial. Los tres contratos se mantienen separados.

## Separación de ejes

La identidad responde qué es el artículo: familia, categoría y subcategoría. Apariencia describe color, patrón, superficie y contorno. Fit expresa holgura de diseño; talla es una identidad comercial independiente. Uso expresa ocasión, dress code, formalidad y contexto climático. Estilo representa afinidades múltiples, sin convertirlas en una categoría de prenda.

Por ello wide-leg pertenece a forma de pierna, high-rise a tiro y cropped a largo. No se crea una clase compuesta por pantalón + wide-leg + formal + negro + office. Los límites de subtipos como pantalón sastrero se revisan según identidad constructiva y acuerdo de anotación, sin introducir formalidad automática.

Materiales, estructuras textiles y apariencia superficial son capas distintas. Satén no demuestra seda; denim no demuestra una composición porcentual; apariencia metálica no demuestra metal. Un descriptor contextual tampoco prueba rendimiento técnico, antigüedad real, autenticidad, clase social o preferencia de una persona.

## Estados y evidencia

Cada afirmación usa `KNOWN`, `UNKNOWN` o `NOT_APPLICABLE`. `KNOWN` necesita evidencia del valor; `UNKNOWN` conserva falta de información; `NOT_APPLICABLE` necesita una regla de aplicabilidad o contexto confirmado. Un atributo opcional no equivale a una ausencia negativa y un maestro vacío no demuestra que el atributo no aplique. No usar un `null` semántico para representar los tres estados.

La aplicabilidad por familia es una base. Categoría, componente y condiciones pueden refinarla: una prenda sin mangas confirmada no admite unión de manga; la composición puede aplicar a un componente textil de un accesorio; un reloj sin datos de material no autoriza decir que sea no textil. Si el contexto requerido se desconoce, conservar `UNKNOWN`.

Un alias se interpreta con idioma y ámbito. `polera`, `jersey` y `plaid` tienen ambigüedades registradas; no se convierten en equivalencias globales. Los IDs canónicos son estables y separados de las etiquetas ES/EN. La identidad comercial permanece aunque una hipótesis ontológica discrepe.

## Alcance realizado y estado que se conserva

Esta entrega diseña JSON, esquemas, matrices exportables, documentación, validador y tests semánticos. Usa fuentes primarias contrastadas y snapshots locales del catálogo. No entrena, selecciona redes, descarga datasets, implementa transferencia, especialistas, perfil, recomendador, outfits, Trend Intelligence ni LLM. No crea tablas ni migra la DB.

Se preservan SigLIP, OpenCV/colorimetría, EasyOCR, embeddings visuales y pgvector actuales. E5-small y retrieval RAG permanecen según el estado aprobado; el RAG público sigue bloqueado por el gate de abstención no aprobado. Segmentación mantiene su estado experimental, sin modelo promovido. Producción conserva `classification -> FULL IMAGE`, `visual embedding -> FULL IMAGE` y `commercial automatic color -> NONE`. AUD-07 continúa mitigado operacionalmente; esta ontología no reevalúa ese gate.

Los artefactos nuevos viven en `services/mati-ai/resources/fashion_ontology/v1/`; la investigación y decisiones están aquí. Los vocabularios se referencian desde la ontología, evitando copiar listas en cada consumidor. La auditoría comercial documenta acceso limitado: los archivos locales y hashes son verificables; PostgreSQL vivo no se consultó en esta sesión. Las evidencias históricas no se presentan como mediciones actuales.

## Validación y gobierno

El validador comprueba estructura, IDs, referencias, aliases, dominios, matrices y mappings. Sus resultados prueban consistencia técnica; no prueban exactitud visual, acuerdo entre especialistas ni calidad de un dataset. Las clasificaciones `SAFE TO FREEZE`, `NEEDS MATI REVIEW` y `RESEARCH INCOMPLETE` distinguen esos niveles en el documento14. Ningún concepto se congela automáticamente por pasar JSON Schema.

Los cambios incompatibles de significado, aplicabilidad o dominio de etiquetas en una versión ya aprobada requieren versión mayor y transición. La revisión 1.1.0 de este borrador aplica las decisiones humanas a alcances provisionales con preservación de IDs, hashes y transición documentada en el documento24; no publica una versión aprobada. Los IDs retirados se deprecian sin reciclarse. Un cambio opcional compatible puede ser menor; una aclaración sin cambiar significado puede ser patch. Cada derivación futura registra versiones y hashes de sus fuentes. No se ejecutan `git add`, commit o push en esta fase.

## Políticas humanas incorporadas en esta revisión

H01-H12 están registradas con origen, alcance y pendientes en documento15. POLERA ID11 se interpreta como tshirt para Mati, sin convertir el término regional en equivalencia universal ni reclasificar productos históricos. POLOS Y KNITWEAR se resuelve por producto y ACCESORIOS excluye FOOTWEAR. Las definiciones, valores candidatos, ejemplos y fases posteriores conservan revisión por ámbito en governance.json. La validación automática comprueba integridad; no concede aprobación comercial ni de entrenamiento.
