# MORE — Aplicación de AUP, backlog y planificación

Corte: 12/10/2026. Se conserva **Agile Unified Process**. La secuencia propuesta no reescribe fechas históricas ni acredita iteraciones ejecutadas.

AUP distingue Inicio para alcance/viabilidad, Elaboración para riesgo arquitectónico, Construcción para incrementos funcionales y Transición para validación/entrega. Sus disciplinas se repiten según necesidad. Referencia primaria: [descripción de Scott Ambler conservada en Clemson](https://people.computing.clemson.edu/~johnmc/courses/cpsc372/resources/The%20Agile%20Unified%20Process%20(AUP)%20Home%20Page.htm). La aplicación a MORE que sigue es un análisis del proyecto, no un historial inferido desde la metodología.

## Estado del ciclo y de los productos de trabajo

| Fase | Qué consta en V4/fuente | Qué falta documentar/aceptar | Criterio de salida recomendado para MORE |
| --- | --- | --- | --- |
| Inicio | Problema, OG/OE, límites, RF/RNF generales, HU16, ET30 e I0 declarada completada; entrevistas y observación en anexos | Aprobación de versión objetivo, decisiones de dinero/actores, trazabilidad e instrumentos de campo fechados | Alcance y actores acordados; criterios verificables y riesgos priorizados; no justificar objetivos pendientes por código existente. |
| Elaboración | E1/E2 declaradas completas/refinadas; E3 descrita solo diseño. Contratos de inferencia y prototipos actuales presentes | Actualizar madurez por tarea, riesgos financieros/logísticos y evidencia arquitectónica fechada; ámbitos de ontología sin aprobación integral | Flujos críticos y contratos viables con criterios de incertidumbre, fallback, datos y recursos; promoción separada. |
| Construcción | C1–C7 declaradas implementadas; rutas/servicios/UI/tests y reportes de auditoría | Registro de cierre por RF/versión/aceptación; pagos/vencimiento, privacidad, indicadores y nuevas capacidades objetivo | Incrementos integrados con reglas y regresiones pertinentes, defectos visibles y trazabilidad; no exigir toda IA futura a cada incremento. |
| Transición | T1 en curso; T2 parcial en V4. Muestra local/build/suites selectivas reportados | Aceptación real de actores, servidor final, matriz dispositivos, privacidad, restauración/capacitación y acta de entrega | Versión candidata revisada, controles y recuperación demostrados, riesgos conocidos aceptados por responsables; no declarar producción por health local. |

## Disciplinas aplicadas a MORE

| Disciplina | Evidencia actual del proyecto | Producto de trabajo faltante o a consolidar |
| --- | --- | --- |
| Modelado | Requerimientos/HU/ET y contratos existentes, reglas implícitas en servicios | RF/RNF/RB aprobados, DP resueltas, definición de estados/dinero/stock y ámbitos ontológicos. Esta entrega solo documentación textual. |
| Implementación | Laravel/React/FastAPI y motores auxiliares presentes | Registro por incremento con RF y alcance; distinguir implementación comercial, adaptación de modelo y diseño experimental. |
| Pruebas | Casos V4, tests en repo, resultados selectivos y experimentos fechados | Caso–RF–versión–datos–esperado–obtenido–defecto, más aceptación por actor y evaluación IA por tarea. |
| Despliegue | Compose, guía local y reporte del 11/10 | Configuración final, privacidad, canales, almacenamiento, restore, rollback operativo y aceptación. |
| Gestión de configuración | Locks, migraciones y Git, recursos externos/ignorados preservados | Identidad de release y dependencias, modelos/pesos/corpus/datasets/hashes, inventario de recursos no cubiertos por Git. No modificar checkpoint. |
| Gestión del proyecto | ET30/plan13 filas, prioridades/riesgo de V4 | Responsables reales, decisiones, estimación de pendientes y seguimiento con evidencias; no inventar velocidad, fechas o esfuerzo realizado. |
| Entorno | Windows/WSL, contenedores, herramientas y perfil GPU documentados | Guía reproducible por entorno, límites/recursos y aislamiento de tests/experimentos; no asumir un VPS genérico suficiente para GPU. |

## Iteraciones declaradas y comprobación disponible

Las trece filas del plan V4 (Tabla 10, B1855) se conservan como declaraciones. No se asignan retrospectivamente las auditorías de octubre a fechas de C1–C7.

| Identificador V4 | Objetivo / estado declarado | Evaluación de evidencia actual |
| --- | --- | --- |
| I0 | Inicio: alcance, interesados, requisitos/riesgos — completado | Artefactos presentes; nueva línea consolidada y aprobaciones pendientes. |
| E1 | Arquitectura base — completado | Fuente/entorno presentes; no acta fechada de cierre arquitectónico localizada. |
| E2 | Riesgos transaccionales — completado/refinado | Servicios y correcciones existen; anomalías de política pendientes, no borrar historia. |
| E3 | Arquitectura inteligente — diseñado; IA no operativa | Desactualizado para inferencia/asistencia actuales; gates/consumidores mantienen límites. |
| C1 | Acceso — implementado | Código/tests de acceso; no aceptación global de usuarios/canales. |
| C2 | Catálogo interno — implementado | Código/UI y muestra fechada; no toda escritura aceptada. |
| C3 | Inventario — implementado/refinado | Regresiones selectivas documentadas; reconciliación integral faltante. |
| C4 | Comercio — implementado/refinado | Flujos presentes; anticipos/estado y pruebas por origen requieren cierre. |
| C5 | Cliente — implementado | Catálogo comprobado acotadamente; dos carritos y checkout necesitan aceptación completa. |
| C6 | Administración — implementado | Indicadores/control presentes; gráfico y recuperación pendientes. |
| C7 | Vendedor — implementado/auditado | Fuente y auditoría presentes; auditoría registra fallo de entorno de pruebas, no suite total verde. |
| T1 | Estabilización — en curso | Reportes y checks selectivos no acreditan cierre. |
| T2 | Liberación — parcial/pendiente | No aceptación/despliegue productivo definitivo encontrados. |

“Concluido” se usa solamente para un trabajo/evidencia delimitado (por ejemplo corrección agotada y suite selectiva reportada), no para toda la fase por el nombre de una carpeta. “En progreso” proviene de la declaración de V4; no se afirma que un desarrollador esté trabajando ahora. Previsto y experimental mantienen ese estado hasta prueba y decisión.

## Significado de sprint en el informe

V4 §5.5/B2005 explica que los seis grupos históricos corresponden a C1–C6. Las tablas de pruebas usan “Sprint 1–6” como etiquetas de módulos y fechas de junio; B2024, sin embargo, los llama “seis sprints reales”. No basta esa palabra para demostrar adopción de Scrum.

Un Sprint de Scrum es un evento con duración fija de un mes o menos y eventos/compromisos propios; no equivale automáticamente a una fase o grupo de pruebas AUP. [Scrum Guide oficial](https://scrumguides.org/scrum-guide.html).

Recomendación pendiente para el Word: conservar identificadores y fechas, añadir una equivalencia explícita “grupo histórico Sprint n → evidencia de construcción Cn” y usar “iteración AUP” cuando exista realmente objetivo, incremento, disciplinas y evidencia de cierre. No renombrar toda aparición sin analizar significado. No inventar roles/eventos Scrum ni reemplazar AUP.

## Backlog consolidado a partir de ET-01–ET-30

Tabla 9 de V4 aporta IDs/nombres/prioridad/riesgo/estado, pero no enlaza cada aceptación a evidencia. Los IDs originales se mantienen; la columna “situación” es diagnóstico actual, no edición del Word. Códigos NT/IA/CO/EF y aceptación detallada en documento 02.

| ET V4 | Elemento | RF/RNF propuestos | Situación | Prioridad propuesta | Iteración declarada / trabajo propuesto | Evidencia | Condición de cierre |
| --- | --- | --- | --- | --- | --- | --- | --- |
| ET-01 | Autenticación | RF-001–005 | Implementado en V4; canal reset/matriz pendiente | NT | C1; P-E1/P-T1 | EV-03/33 | RNF-01/02 y aceptación por rol |
| ET-02 | Usuarios, roles, permisos | RF-006–008/086 | Actual tres roles; decisión admin y borrado pendiente | NT | C1; P-E1 | EV-02/03/04 | DP-01/05 |
| ET-03 | Parámetros de catálogo | RF-009–011 | CRUD presente; atributos/ontología con madurez propia | NT/IA | C2; P-E2 | EV-04/05/28 | Integridad/estado y ámbitos |
| ET-04 | Registro de prendas | RF-012–014 | Implementado, validación integral pendiente | NT | C2; P-C1 | EV-05/06/31 | Campos/stock/borrado |
| ET-05 | Variantes, SKU | RF-015–016 | Implementado; propiedad/agotada comprobadas parcialmente | NT | C2/C3; P-C1 | EV-06/31/33 | Unicidad/identidad |
| ET-06 | Galería | RF-017–018/069 | Carga presente; publicación IA y derechos específicos | NT/CO | C2; P-C2 | EV-05/25/33 | Storage, derechos y token |
| ET-07 | Existencias | RF-019–021 | Aritmética/edición aisladas comprobadas | NT | C3; P-C1 | EV-08/31/33 | Conciliación física DP-06 |
| ET-08 | Kardex | RF-022 | Implementado, reconciliación pendiente | NT | C3; P-C1 | EV-08/20 | Deltas/referencias/actor |
| ET-09 | Alertas | RF-023 | Regla descriptiva presente | NT | C3/C7 | EV-08/14 | Umbral DP-06 |
| ET-10 | Ventas directas | RF-028 | Implementado; delta aislado comprobado | NT | C4/C7; P-C1 | EV-09/31/33 | Precio/estado/pago |
| ET-11 | Reservas | RF-033–042 | Implementado y parcial en dinero/vencimiento | NT | C4/C7; P-E1/P-C1 | EV-11/12/13 | DP-03 |
| ET-12 | Pagos, comprobantes | RF-030/031/034–036/043 | Implementado; dinero/privacidad necesitan cierre | NT | C4/C7; P-C1 | EV-09/12/18 | DP-03/09 |
| ET-13 | Recojos | RF-039–042 | Implementado; vendedor/superadmin distintos | NT | C4/C7; P-C1 | EV-11/13/14 | Estado/pago/calendario |
| ET-14 | Carrito, checkout | RF-029/047/048 | Dos almacenes y entrada cliente presentes | NT | C4/C5; P-C1 | EV-10/17 | UUID/revalidación |
| ET-15 | Exploración pública | RF-045/046 | Muestra catálogo/filtro comprobada; combinaciones pendientes | NT | C5; P-T1 | EV-16/31 | Facetas/variante/dispositivos |
| ET-16 | Cuenta del cliente | RF-044/043 | Presente, aceptación por titular y archivos pendiente | NT | C5; P-T1 | EV-18 | Privacidad DP-09 |
| ET-17 | Favoritos | RF-049 | Implementado; aceptación/mensajes pendientes | CO | C5 | EV-18 | No recomendador aprendido |
| ET-18 | Panel de control | RF-050 | Parcial por indicador mezclado | CO | C6/C7; P-C1 | EV-19/31 | DP-07 |
| ET-19 | Reportes operativos | RF-051 | Implementado; semántica/CSV pendiente aceptación | CO | C6/C7; P-C1 | EV-19/14 | Definiciones DP-07 |
| ET-20 | Logs, auditoría | RF-053/054 | Implementado; retención/no repudio no certificados | NT | C6; P-T1 | EV-20 | DP-09 |
| ET-21 | Copias de seguridad | RF-055/056 | Dump implementado; recuperación propuesta | NT | C6; P-T1 | EV-21 | DP-10 |
| ET-22 | Prendas dañadas | RF-024–027 | Daños presentes; devolución propuesta aparte | NT/EF | C6/C7; P-C1 | EV-15 | No doble descuento; DP-04 |
| ET-23 | Notificaciones | RF-052 | Internas presentes, canal externo no acreditado | CO | C6/C7 | EV-20 | DP-08 |
| ET-24 | Panel vendedor | RF operativos por matriz | Implementado, suite integral no acreditada | NT | C7; P-T1 | EV-14/34 | Permisos/POST por operación |
| ET-25 | Adaptación responsiva | RNF-08/09/10 | En validación declarada; muestra escritorio limitada | CO | T1; P-T1 | EV-31 | Matriz real de dispositivos/usuarios |
| ET-26 | Pruebas y estabilización | Todos RF NT y RNF críticos | En curso declarado; evidencia parcial de fechas distintas | NT | T1/T2; P-T1 | EV-31/32/33/34 | No porcentaje global inventado |
| ET-27 | Arquitectura inteligente | RF-058/063/068/070/071 | V4 diseñado; fuente/integración actual superan esa descripción en partes | IA | E3 declarado; P-E2/P-C2 | EV-22–27/31/32 | Distinguir transporte, tarea y promoción |
| ET-28 | Asistencia visual | RF-058–069/083 | SigLIP/embedding/abstención/códigos comprobados acotadamente; OCR/similares y fuentes distintos | IA | P-C2; asociación E3 a formalizar | EV-23–25/30–34 | No equiparar búsqueda visual y semántica |
| ET-29 | Recomendación, analítica | RF-075–078/081 | Reglas actuales; modelos completos diseñados | IA | P-E4/P-C3 | EV-19/28/35 | DP-02/15/16 |
| ET-30 | Talla, probador virtual, tendencias | RF-079/080/082 | Diseñados; no todo diseño es experimento ejecutado | EF | P-X2/P-X3 | EV-28/35 | DP-17/18 |

## Trabajo nuevo que no está suficientemente separado en ET originales

| Elemento propuesto | Trabajo | RF/decisión | Entregable | Estado |
| --- | --- | --- | --- | --- |
| ET-P-01 | Dinero, estados y stock coherentes ante cancelación/rechazo/vencimiento | RF-035–042; DP-03 | Política aprobada y escenarios de aceptación | Previsto; no ejecutado |
| ET-P-02 | Baja/archivos/permisos y privacidad | RF-008/043/086; DP-01/05/09 | Matriz y política, cambios revisables futuros | Previsto |
| ET-P-03 | Recuperación integral y liberación | RF-056; DP-10; RNF críticos | Plan/ensayo/acta con RPO/RTO acordados | Previsto |
| ET-P-04 | Gobierno de ontología/atributos | RF-061/072; DP-12 | Ámbitos, ejemplos/guía/dataset revisados por separado | Diseñado |
| ET-P-05 | Consolidación visual, OCR/matching y evaluación independiente | RF-058–069; DP-11/14 | Contratos/casos/dataset/métricas/limitaciones | Previsto, con evidencias previas acotadas |
| ET-P-06 | ML de recomendación y outfits | RF-077/078; DP-02/15/16 | Baseline, modelo evaluado, integración y juicio humano | Diseñado |
| ET-P-07 | Decisión de RAG y segmentación | RF-071/073/074; DP-13/14 | Nuevo protocolo si aprobado; conservar gates actuales | Experimental; sin promoción |
| ET-P-08 | Talla/try-on/tendencias/LLM/Lens | RF-079–085/087; DP-17–20 | Viabilidad y alcance por capacidad | Futuro; no liberable actual |

## Plan actualizado propuesto: no es historia

Sin fechas ni duraciones comprometidas: dependen de decisiones, responsables y datos reales. Se usan identificadores nuevos para evitar confundirlos con I0/E1/C1 del informe. Una iteración puede volver a modelado/pruebas si la evidencia invalida supuestos.

| Orden / iteración propuesta | Fase y estado | Trabajo / RF | Entrada y dependencia | Entregable / aceptación | Riesgo y esfuerzo relativo |
| --- | --- | --- | --- | --- | --- |
| 1 · P-E1 | Elaboración · previsto | ET-P-01/02; RF-008/027/035–042/086 | DP-01/02/03/04/05/06/07/09 revisadas | Políticas por actor, estados/dinero/stock, alcance aprobado y casos críticos; no implementa aún | Alto; medio/alto según alternativa monetaria |
| 2 · P-C1 | Construcción · previsto | Núcleo comercial/control; ET-01–24 afectados | P-E1; autorización posterior de cambios | Correcciones delimitadas y regresiones de stock/pagos/permisos; indicadores reconciliados | Alto impacto; esfuerzo por decidir tras diseño |
| 3 · P-T1 | Transición · previsto | ET-25/26 y ET-P-03; RNF-01–14 | P-C1; DP-08/10 e infraestructura final | Aceptación por actor/dispositivo; restore y preparación de entrega con evidencias; defectos conocidos explícitos | Alto; medio/alto |
| 4 · P-E2 | Elaboración · previsto | Ontología/atributos y fuentes; RF-061/072/083 | DP-11/12; productos/evidencias vigentes | Ámbitos pequeños revisados, etiquetas/GT y licencias; contrato versionado, sin promover dataset/modelo | Alto por datos; medio/alto |
| 5 · P-C2 | Construcción · previsto | Consolidar RF-058–070; ET-P-05 | P-E2 en atributos nuevos; conserva asistencia FULL ya existente | Input/ownership/fallback, dataset independiente por tarea y métricas; revisión humana/medios/matching | Medio/alto; recursos actuales conocidos pero no SLA |
| 6 · P-E4 | Elaboración · previsto | Viabilidad ML/outfits RF-077/078; OE-04/05 | DP-02/15/16; P-E2, datos e integridad comercial | Target/contexto/partición/baseline/protocolo y revisión humana; si datos insuficientes, no declarar modelo cumplido | Alto; alto |
| 7 · P-C3 | Construcción · previsto | Recomendador/outfits y apoyo RF-075/076 | P-E4 aceptado; soporte real de datos | Incremento evaluado contra baseline e integrado con disponibilidad Laravel, explicaciones y variantes | Alto; alto, sin fecha ficticia |
| 8 · P-E3 | Elaboración · experimental condicional | RAG RF-071; ET-P-07 | DP-13 y protocolo/dataset independiente aprobados | Decisión de gate por contexto; conservar NO_PROMOTION si no hay mejora | Alto; no prioritario sobre consistencia/objetivos |
| 9 · P-X1 | Elaboración · experimental condicional | Detección/segmentación y eventual Lens | DP-14/20, licencias y GT humano target/máscara | AUTO/ASSISTED separados, gates por consumidor; no integración sin ganador | Muy alto; alto |
| 10 · P-X2/3/4 | Elaboración · futuro condicional | Talla/try-on; ML temporal/tendencias; descripciones/asistente | DP-17/18/19, datos/privacidad/modelos/licencias | Viabilidad y experimentos separados; aceptación y transición propias si se aprueban | Muy alto; no consolidar en una sola promesa |

No todas las capacidades inteligentes tienen igual prioridad. Asistencia visual/identificación trazable aprovecha integración y datos actuales, aporta al título y se puede evaluar por tarea. Ontología debe aprobarse gradualmente antes de nuevos atributos. Recomendación/outfits importan por OE-04/05, pero dependen de decisión/datos. Segmentación/RAG requieren nuevos experimentos tras resultados negativos; talla/try-on/tendencias/LLM/Lens tienen mayor dependencia y alcance condicionado.

## Riesgos, evidencia de avance y cierre

| Riesgo | Tratamiento propuesto | Evidencia para revisar avance |
| --- | --- | --- |
| Dinero confirmado y stock liberado incoherentemente | DP-03 y escenarios por origen/abono/estado | Decisión, pruebas y reconciliación por operación |
| Privilegio/archivo de otro usuario | Matriz por ruta/objeto y archivos privados acordados | Casos positivos/negativos y política DP-09 |
| Dashboard descriptivo interpretado como predicción | Diccionario de indicadores y baseline | Consulta/dataset/período, resultado comprobable |
| Etiquetas/modelos insuficientes | Ámbitos y GT independiente, sin fuga | Versiones/hash/anotación/discrepancias/métricas |
| Confundir perfiles/vectores | Identidad completa y versión de corpus/preprocess | Contratos y casos incompatibles |
| Gates negativos ignorados | Decisión por consumidor y aprobación explícita | NONE/NO_PROMOTION o nuevo resultado independiente |
| Liberación sin restore/usuarios | Plan de transición y objetivos acordados | Ensayo aislado, aceptación/capacitación y registro |
| Reconstruir historia por suposición | Conservar fechas, distinguir plan/resultado | Fuente fechada/versionada y acta original donde exista |

Para cada trabajo futuro registrar responsable real, decisión, RF/RB, versión, datos, criterio, resultado, defecto y cierre. No se cambian fechas de junio/octubre ni resultados anteriores por el plan nuevo. La documentación creada ahora es producto de Modelado/Gestión del Proyecto; no es ejecución de P-C/P-T ni aceptación de software.
