# Body Zones y Layering V1

La fuente formal es `body_zones.json` y `layering.json` bajo `services/mati-ai/resources/fashion_ontology/v1/`. El árbol espacial y los escenarios de uso son una decisión original MORE, pendiente de validación de dominio; Fashionpedia, DeepFashion2 y ModaNet aportan localización de objetos, pero no equivalencia directa para estas relaciones. Véase la investigación de fuentes.

## Zonas y roles

Hay 18 zonas con IDs estables. FULL_BODY, UPPER_BODY y LOWER_BODY son agregados para consulta, no nuevos targets de cobertura que deban sumarse a sus descendientes. Torso incluye espalda; brazos incluyen muñecas y manos; manos incluyen dedos. Head incluye face y ears. Waist se sitúa bajo FULL_BODY como frontera funcional: duplicarlo bajo superior e inferior crearía doble contabilización. Este árbol es una partición funcional para vestimenta, no una ontología médica ni una medición de anatomía.

Una asignación contiene `primary_zone`, `secondary_zones`, `coverage_role`, y opcionalmente componente/contexto. Los roles son `covers`, `worn_at`, `carried_at` y `contacts`. Las zonas primarias son las más relevantes para el uso descrito; no implican porcentaje de superficie. Pantalón: pelvis primaria y piernas secundarias, cobertura; collar: cuello, worn_at; mochila: espalda/hombros, carried_at; broche: contacto con la prenda sobre torso. Un bolso sobre hombro no cubre necesariamente torso ni mano. Las alternativas de porte se expresan con carry_method y contexto, sin convertirlas en cobertura simultánea.

No se agrega arms a una prenda solo por ser UPPER; depende de mangas observadas. Falda mini y maxi pueden tener la misma familia, pero distinta extensión corporal. No se infiere piel descubierta de zonas sin evidencia. En CONJUNTO, las asignaciones se registran por componente y se agregan conservando roles: no basta FULL_BODY. Las 46 asignaciones por defecto para categorías simples son candidatas orientativas; coordinated_set requiere componentes reales.

## Capas

Roles: BASE, INTERMEDIATE, OUTER, OVERLAY y ACCESSORY. INNER se expresa como relación relativa por debajo de otra instancia; no se duplica como rol absoluto de BASE. Los roles pertenecen a una configuración de uso. Un blazer puede ser OUTER sobre camisa e INTERMEDIATE bajo abrigo sin cambiar su familia.

El orden es un grafo dirigido parcial entre **instancias**, delimitado por zona y wear_context. No se exige un orden total entre artículos en zonas distintas. Son restricciones estructurales: no poner una instancia sobre sí misma ni formar ciclos de orden dentro de la misma zona/contexto. No hay incompatibilidades duras de moda afirmadas en V1. Una incompatibilidad física futura requerirá medidas o evidencia, no solo categorías.

Cinco escenarios explícitos permiten camisa sobre camiseta, blazer sobre camisa, abrigo sobre blazer, falda sobre pantalón y bufanda sobre abrigo. Los tres primeros son soft_rule condicionales; los dos últimos styling_preference contextual. Deben comprobarse holgura, longitud, movilidad, materiales y uso. Ninguno declara compatibilidad universal ni tendencia actual.

`structural_constraint`, `styling_preference`, `soft_rule`, `trend_exception` y `hard_incompatibility` tienen significados distintos. Una trend_exception requiere referencia externa con ventana temporal y procedencia; el artefacto permanente solo admite la relación. La V1 permite skirt-over-pants sin afirmar que esté de moda. LOWER + LOWER no produce un rechazo automático.

## Validación y pendientes

El validador resuelve zonas/roles, padres, bindings, extremos de relaciones, tipos de regla y ciclos estructurales. El contrato Product Intelligence verifica referencias espaciales y, si existen aristas entre instancias, ciclos por zona/contexto. No ejecuta Outfit Engine ni mide compatibilidad. Mati debe revisar extensión de cobertura por largo/manga, cuello frente a adorno, sombrero/beanie y alternativas de porte. Fracciones de cobertura, lateralidad y geometría de colisión quedan fuera de V1 hasta contar con un caso de uso anotable.

## Conjuntos y outfit — decisión H06

La operación habitual vende prendas individuales. Un vestido es ONE_PIECE y no se transforma en MULTI_PIECE por crear apariencia de look completo. Una configuración de layering o combinación recomendada es outfit y no acredita vínculo comercial. Sólo un conjunto multipieza explícitamente vinculado comercialmente agrega componentes como set. Puede ofrecerse completo/componentes cuando existan registros e inventarios necesarios; inventario compartido/reservado/empaquetado y prevención de doble descuento quedan pendientes. Contrato22 conserva estas fronteras sin implementar ventas ni inventario.
