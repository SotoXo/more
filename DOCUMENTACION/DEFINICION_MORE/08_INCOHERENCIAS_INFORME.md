# MORE — Incoherencias del informe V4 y corrección posterior

Documento revisado: C:/Users/Usuario/Downloads/SotoCazuLimberPII2V4.docx, SHA256 **3faaff715432c78299b2786777882aae4a95cd780c5eea0bcf7dd206a12cc35d**. Corte: 12/10/2026. **El Word permanece sin editar.**

Las referencias Bn identifican el bloque de nivel principal, contado desde cero, en word/document.xml; .Fn identifica fila de tabla con cabecera F0. Se usan título/sección/tabla y bloque para localizar sin depender de paginación o de un render nuevo. Se consultaron párrafos/tablas/ecuaciones y anexos textuales; no se evaluaron ni modificaron representaciones gráficas.

El repositorio acredita comportamiento presente; V4 acredita intención y resultados declarados. Cuando discrepan, se prioriza evidencia específica y fechada para implementación, y aprobación del autor/Mati para cambios de alcance. No se declara falso un resultado histórico solo por ser anterior: se pide procedencia y se evita extrapolarlo.

## Diagnóstico por apartado

### IC-01 — Portada B2; §3.6.1 B1691; §3.6.2 OE-04/05 B1696–1697

**Problema:** Título/OG incluyen aprendizaje y OE-04 exige implementar modelo de recomendación; OE-05 exige integrar outfits. Lo actual no acredita esos dos objetivos completos.

**Evidencia/contraste:** EV-19/35: dashboard descriptivo y complementos por reglas; EV-28: contratos futuros, no modelo completo.

**Corrección propuesta:** Mantener objetivos como pendientes con plan RF-077/078 o aprobar modificación académica mediante DP-02. No declarar cumplimiento porque haya SigLIP o inventario.

**Prioridad:** Bloqueante académico.

### IC-02 — §1.4 NOVEDAD CIENTÍFICA, B627–634

**Problema:** Integrar tecnologías conocidas se presenta como novedad científica y mejora de precisión/registro sin comparación suficiente. Fuentes externas, tendencias, recomendaciones y talla mezclan propuesta y efecto.

**Evidencia/contraste:** EV-23/28/30/31/35: integración visual acotada, fuentes vacías, consumidores futuros y gates negativos.

**Corrección propuesta:** Distinguir aporte ingenieril de hipótesis científica; documentar baseline/experimento/datos/métricas pertinentes y límites. Presentar efectos sin medición como esperados, con aprobación académica del encuadre.

**Prioridad:** Alta.

### IC-03 — Cap. 2 JUSTIFICACIÓN TÉCNICA, B1580–1602

**Problema:** Tecnologías se usan para garantizar rendimiento/seguridad/mantenibilidad; equipo cliente mínimo y VPS se describen sin separar recursos de inferencia.

**Evidencia/contraste:** EV-31/36: entorno local/GPU y build; no capacidad/SLA o servidor final probado.

**Corrección propuesta:** Separar cliente navegador, servidor transaccional y recursos IA opcionales; transformar garantías en requisitos medibles pendientes. No inventar equivalencia entre VPS básico y inferencia GPU.

**Prioridad:** Alta.

### IC-04 — Cap. 2 JUSTIFICACIÓN ECONÓMICA/SOCIAL B1603–1613; RESULTADOS ESPERADOS B2587–2593

**Problema:** Ahorro, incremento de ventas, bajo mantenimiento y viabilidad se formulan como efectos demostrables sin medición operativa de impacto.

**Evidencia/contraste:** EV-01 anexo entrevistas aporta expectativas; EV-31 solo comprobación técnica local.

**Corrección propuesta:** Conservar beneficios como hipótesis/expectativas; exigir línea base, período, costos/datos y evaluación si se reclama impacto. No usar inferencia/modelo o build como evidencia de ventas mejoradas.

**Prioridad:** Alta.

### IC-05 — Cap. 2 JUSTIFICACIÓN LEGAL B1614–1620; seguridad Tabla 18 B2016

**Problema:** El texto garantiza cumplimiento normativo y protección general por usar tecnologías/roles; referencia lineamientos en desarrollo sin verificación integral.

**Evidencia/contraste:** EV-18: vouchers public; EV-03/04: eliminación divergente; EV-20: logs sin política integral de retención.

**Corrección propuesta:** Separar normas/fuentes vigentes de propuestas; documentar políticas y controles efectivamente verificados y pendientes. Revisión aplicable antes de afirmación de cumplimiento, sin dictamen legal en esta entrega.

**Prioridad:** Alta.

### IC-06 — §3.1 PROBLEMÁTICA B1635–1646; §3.3.1 ALCANCES B1659–1668

**Problema:** Problemas de talla/orientación y promesa de prendas/outfits/talla/tendencias amplían alcance mucho más que evidencia implementada.

**Evidencia/contraste:** EV-35/28 y RF-077–082: diseños con datos/evaluación pendientes.

**Corrección propuesta:** Asignar cada problema a RF/estado/evidencia/versión objetivo. No eliminar promesas unilateralmente; DP-02/17/18 debe delimitar versión candidata y futura.

**Prioridad:** Bloqueante de alcance.

### IC-07 — §3.3.2 LÍMITES B1671–1677

**Problema:** Límite humano/bancario es compatible con asistencia, pero expresiones generales dejan ambiguos reembolsos, factura, registro autónomo y uso externo.

**Evidencia/contraste:** EV-09/10/12/25/30: revisión humana/manual; no gateway, factura fiscal o refund completo.

**Corrección propuesta:** Precisar límites de precio/SKU/stock, decisión humana, recibo interno, fuentes/derechos y devolución pendiente. Mantener exclusiones existentes de proveedores/contabilidad/3D.

**Prioridad:** Alta.

### IC-08 — Cap. 3 POBLACIÓN, MÉTODO DE MUESTREO y MUESTRA B1698–1732; anexos 1–4 B3142–3231

**Problema:** N=10.000 estimado, muestreo aleatorio simple y muestra370 son plan, mientras conclusiones dicen caracterización lograda. Entrevistas/ficha están descritas, encuesta contiene preguntas/opciones sin distribución de respuestas.

**Evidencia/contraste:** EV-01: anexos1/2 incluyen respuestas; anexo4 observaciones; anexo3 guía/opciones. No equivalencia con resultados de 370 encuestados.

**Corrección propuesta:** Conservar cálculo/muestra como diseño si no se ejecutó. Vincular fecha/participantes/procedencia de entrevistas y observación; agregar evidencia y análisis de encuesta solo si existe. No inventar aplicación ni confundir cinco perfiles de usabilidad con muestra370.

**Prioridad:** Alta.

### IC-09 — §5.1 introducción AUP B1811; §5.2 Tabla 9 ET-27/28 B1850.F27–28; Tabla10 E3 B1855.F4; §5.4 Construcción B2001

**Problema:** Se afirma falta de servicio de inferencia operativo y asistencia no operativa; es estado desactualizado para partes verificadas en octubre.

**Evidencia/contraste:** EV-31: inferencia real SigLIP CUDA/768 con abstención; EV-32: uploads y fallback documentados; EV-22–25 código.

**Corrección propuesta:** Actualizar por tarea: transporte/inferencia/asistencia operativos acotados; OCR/similares con límites; RAG/segmentación bloqueados y motores futuros diseñados. Agregar evidencias fechadas, sin inventar iteración IA ejecutada.

**Prioridad:** Alta.

### IC-10 — §5.2.1.1 Tabla6 RF1–RF10 B1821

**Problema:** Solo diez RF generales mezclan operaciones comerciales y omiten obligaciones de IA del título/objetivos.

**Evidencia/contraste:** RF-001–087 consolidados; EV-02–30; ET-27/30 sí mencionan inteligencia.

**Corrección propuesta:** Aprobar catálogo descompuesto del documento02 y conservar correspondencia con IDs originales. Definir actor/precondición/aceptación/estado/dependencias por obligación; separar dinero, stock, logística y tareas IA.

**Prioridad:** Bloqueante de definición.

### IC-11 — Tabla6 RF1; Tabla8 HU2/3/4/5/6/7/16; POBLACIÓN B1700; Tabla18 B2016

**Problema:** Administrador de Word y superadmin de backlog/código se usan sin explicar diferencia; crear fila de rol no concede permisos.

**Evidencia/contraste:** EV-02/03: sistema superadmin/vendedor/cliente; middleware por nombre/actividad, sin admin separado funcional.

**Corrección propuesta:** Aplicar DP-01: identificar responsabilidad administrativa actual bajo superadmin o aprobar rol distinto con matriz y futura implementación. No dar privilegios al administrador por suposición.

**Prioridad:** Bloqueante de actores.

### IC-12 — Tabla8 HU8 y HU11 B1839.F8/F11; Tabla6 RF6

**Problema:** HU8 permite al vendedor consultar y actualizar inventario; ajuste directo solo corresponde a superadmin. HU11 habla efecto automático de venta/reserva.

**Evidencia/contraste:** EV-02/04/08/14: vendedor consulta y mueve stock a través de operaciones, no ajuste CRUD.

**Corrección propuesta:** Separar lectura de vendedor, movimientos causados por operación y ajuste manual privilegiado. Precisar D/R por variante; no interpretar 'actualizar' como permiso de cualquier cantidad.

**Prioridad:** Alta.

### IC-13 — Tabla6 RF2/4/6 y Tabla8 HU4/6; Construcción B1996–1997

**Problema:** CRUD/borrado y disponibilidad se describen sin distinguir prenda/variante/ejemplar y sin preservar stock e historial.

**Evidencia/contraste:** EV-06/07/08: agregado variante, edición conserva D/R, borrado condicionado y sin serial físico.

**Corrección propuesta:** Definir entidades y cantidad operativa; documentar baja frente a borrado protegido y alta nueva frente a agotada. Evitar columna ficticia de cantidad física o inventario separado por ejemplar.

**Prioridad:** Alta.

### IC-14 — Tabla9 ET-11/12/13 B1850.F11–13; Construcción B1998; pruebas CN4/CB4

**Problema:** Reserva/pago/cancelación/vencimiento/recojo se agrupan sin política monetaria para abonos confirmados.

**Evidencia/contraste:** EV-11/12: cancelar bloquea pagos confirmados; vencer/rechazar liberan sin ese guard; verify restablece confirmada; EV-13: vendedor necesita pago total previo.

**Corrección propuesta:** Documentar comportamiento real y riesgos; DP-03 define anticipo/reembolso/calendario/estados/regularización y cobro final por actor antes de implementar. No presentar todos los cierres como coherentes/aceptados.

**Prioridad:** Bloqueante comercial.

### IC-15 — Tabla9 ET-07/08/22; Construcción B1997 (devoluciones de stock)

**Problema:** La liberación de reserva, reposición por reparación y etiqueta devolucion_danada pueden confundirse con devolución de venta/reembolso.

**Evidencia/contraste:** EV-15/08: daños y liberaciones presentes; no flujo completo de devolución comercial.

**Corrección propuesta:** Nombrar movimiento exacto de liberación/reparación; dejar devolución de venta RF-027 propuesta y política DP-04. No anunciar reembolso implementado.

**Prioridad:** Alta.

### IC-16 — Tabla6 frente a Tabla8 HU15 y Tabla9 ET-08/12/13/14/16/17/20/21/22/23/24

**Problema:** Backlog describe favoritos, Kardex, pagos, logística, carrito/cuenta, logs/auditoría/backups/daños/avisos/vendedor sin RF independientes suficientes.

**Evidencia/contraste:** EV-02/08–21/33 y filas ET concretas.

**Corrección propuesta:** Completar RF y criterios y enlazar ET/HU/casos; marcar que falta enlace formal en documento06. No considerar RF9/RF10 paraguas como cobertura verificable de cada proceso.

**Prioridad:** Alta.

### IC-17 — Tabla8 HU1–16 B1839; Tabla9 ET-27–30

**Problema:** HU son generales sin criterios/alternativas y no hay historias específicas de asistencia/revisión/embeddings/RAG u objetivos ML/outfits.

**Evidencia/contraste:** EV-25/26/28/35: estados y contratos diferentes; catálogo02 propone obligaciones concretas.

**Corrección propuesta:** Refinar HU existentes y redactar nuevas solo tras aprobar alcance. Señalar ausencia de historia y no fabricar IDs/criterios como historia ejecutada.

**Prioridad:** Alta.

### IC-18 — Tabla10 plan AUP B1855; §5.4 B1995–2002; §5.5 B2004–2007

**Problema:** Declaraciones de completado/implementado y secuencia funcional no vinculan fechas/versiones/aceptación por requisito. T1/T2 siguen abiertas.

**Evidencia/contraste:** EV-31/32/34: auditorías de fechas/alcances distintos; no actas de cada iteración aportadas al vínculo.

**Corrección propuesta:** Conservar plan histórico e indicar evidencia disponible/faltante por C/E/T; separar planificación P- nueva. No reconstruir fechas o cierre a partir del nombre del módulo.

**Prioridad:** Alta metodológica.

### IC-19 — §5.5 B2005; pruebas B2024/B2033 y encabezados Sprint1–6

**Problema:** V4 ya explica grupos históricos C1–C6, pero después dice 'seis sprints reales'; ambigüedad no prueba cambio a Scrum.

**Evidencia/contraste:** EV-01: mismo conjunto de grupos; fuente primaria AUP y Scrum en documento07.

**Corrección propuesta:** Añadir equivalencia/documentar significado histórico. Usar iteración AUP donde haya objetivo/incremento/disciplinas/cierre; preservar IDs/fechas y no sustituir metodología.

**Prioridad:** Media.

### IC-20 — Seguridad software Tabla17 B2011 y Tabla18 B2016

**Problema:** Confidencialidad/disponibilidad/no repudio aparecen como garantías amplias; Eloquent/roles/logs no certifican todo acceso ni inmutabilidad.

**Evidencia/contraste:** EV-02/18/20/21/31: controles limitados, vouchers public, backup sin restore y entorno local debug reportado.

**Corrección propuesta:** Describir controles y evidencia por función; HTTPS previsto como pendiente de producción. Hash de contraseña, no 'cifrado reversible'. No afirmar no repudio jurídico/certificación por logs.

**Prioridad:** Alta.

### IC-21 — §5.5.2 PRUEBAS B2023/B2169; tablas21–46

**Problema:** El porcentaje se mantiene 'intencionalmente' bajo65%; resultados deben provenir de casos y registros, no de una cifra deseada.

**Evidencia/contraste:** EV-01: 30 CN y30 CB, 36/60 terminados=60%, con seguimiento manual declarado.

**Corrección propuesta:** Eliminar justificación por porcentaje deseado; conservar registros históricos como declarados hasta cotejo de evidencia. Recalcular solo con resultados reales, sin elevar ni reducir cifras para parecer coherente.

**Prioridad:** Bloqueante de validez.

### IC-22 — Pruebas inventario CN3.3, Tabla25 B2075.F3 y seguimiento B2078.F3

**Problema:** Esperado 'Disponible baja y reservado sube' para venta o reserva generaliza incorrectamente venta presencial.

**Evidencia/contraste:** EV-08/09/10/11/31/33: presencial D-=q/R sin cambio; reserva y checkout separado D-=q/R+=q; completar R-=q.

**Corrección propuesta:** Separar casos por origen/transición con D/R inicial/final. Conservar resultado antiguo con observación metodológica; no reasignar como prueba correcta de todos los orígenes.

**Prioridad:** Alta.

### IC-23 — Pruebas comerciales CN4.1/4.2/4.3 Tabla27 B2094.F1–3; seguimiento B2101

**Problema:** Pago QR esperado pendiente enlazado a venta directa se mezcla con flujo online; medio de pago no determina por sí solo revisión.

**Evidencia/contraste:** EV-09/10: directa crea pago confirmado; online crea pendiente. Staff revisor registra evidencia manual.

**Corrección propuesta:** Distinguir presencial efectivo/QR/transferencia de voucher online; caso y esperado especifican origen, monto, estado y autor. No llamar gateway a QR.

**Prioridad:** Alta.

### IC-24 — Pruebas CN5.4/CB5.3 B2117.F4/B2245.F3; ET-14; Construcción B1998

**Problema:** Carrito se describe como uno; actualmente compra y reserva usan localStorage y sesión respectivamente.

**Evidencia/contraste:** EV-17/10: dos almacenes, revalidación y UUID; agregar no separa.

**Corrección propuesta:** Documentar ambos flujos y pruebas de login/cantidad/precio/stock/confirmación; ausencia de tabla carrito no demuestra ausencia del carrito UI.

**Prioridad:** Alta.

### IC-25 — Pruebas/software V4 tablas21–46 y reportes de octubre

**Problema:** Resultados manuales de junio y suites/experimentos posteriores no son misma ejecución/población; cobertura '60%' no se puede reutilizar como todos los requisitos.

**Evidencia/contraste:** EV-31: Unit84/239 e inventario12/58; EV-32 conserva Python2failed/SQLiteP2; EV-29 experimental84; EV-33 tests presentes.

**Corrección propuesta:** Añadir registro tipo/fecha/versión/dataset/caso-RF/resultado/defecto. No sumar poblaciones ni declarar suite total verde; prueba IA por tarea independiente de transacción comercial.

**Prioridad:** Alta.

### IC-26 — Métricas funcionalidad B2266–2320; PF tablas49/50 y COCOMO B2426–2443

**Problema:** Puntos de función cuantifican tamaño estimado, pero cociente61,79% respecto a escenario ideal se interpreta como completitud/calidad sin especificación verificable de ideal.

**Evidencia/contraste:** EV-01: PFSA646, ajuste45, PFA710,60. Esos cálculos de tamaño no prueban aceptación de RF actuales/futuros.

**Corrección propuesta:** Conservar estimación y sus supuestos/conteo; separar tamaño de cobertura/aceptación/calidad. Definir escenario ideal y no usar funcionalidades no aprobadas para porcentaje de terminado.

**Prioridad:** Alta.

### IC-27 — Confiabilidad B2322–2344

**Problema:** Se citan Fc0,77, ocho incidencias, λ de7 ejecuciones/mes y luego λ=6/200=0,03; concluye60% y40% probabilidad de fallos sin observaciones compatibles.

**Evidencia/contraste:** EV-01 bloques2331–2335/2343–2344; 36/60 es proporción de casos terminados, no probabilidad operacional.

**Corrección propuesta:** Revisar variables/denominadores/unidades y fórmula con evidencia. Mantener medición de confiabilidad como pendiente si no existe serie real de fallos/tiempo. No convertir pruebas observadas en probabilidad.

**Prioridad:** Bloqueante de métricas.

### IC-28 — Fiabilidad B2354–2367; Tabla55 B2411

**Problema:** 24/710,60 produce96,63% como razón normalizada por tamaño; ajuste64% carece de conexión reproducible en texto y tabla resumen usa62%.

**Evidencia/contraste:** EV-01: texto64 frente a tabla62; tabla general promedio62,30 usa62.

**Corrección propuesta:** Unificar cifras solo desde cálculo/protocolo verificables; separar densidad de defectos de fiabilidad en uso. Revisar expresión original donde extracción no muestre ecuación completa, sin rellenar fórmula inventada.

**Prioridad:** Bloqueante de métricas.

### IC-29 — Usabilidad Tabla52 B2374–2377; mantenibilidad B2380–2392; portabilidad B2405–2412

**Problema:** Cinco perfiles porcentuales, mantenibilidad64 e instalación64/ajuste63/adaptación62 se presentan sin instrumentos/resultados suficientes para reproducir mediciones.

**Evidencia/contraste:** EV-01: tablas/texto; EV-31: muestra UI con límites y defecto de modo claro.

**Corrección propuesta:** Aportar tareas, escala/instrumento, participantes, fechas, observaciones y procedimiento; etiquetas de estimación permanecen referenciales. No deducir porcentajes de arquitectura o Compose.

**Prioridad:** Alta.

### IC-30 — Transición y evaluación de backups: Tabla18 B2016.F6, pruebas CB6.5 B2253.F5/B2256.F5; Tabla10 T2

**Problema:** Generación de backup se usa como continuidad aunque no hay restore integral/recursos externos aceptados; T2 no cerrada.

**Evidencia/contraste:** EV-21/31/36: dump manual y recursos fuera de Git; no ensayo integral.

**Corrección propuesta:** Separar generación/descarga y recuperación demostrada. Planear DP-10 y RPO/RTO acordados, usuarios/capacitación/servidor final; no declarar disponibilidad productiva.

**Prioridad:** Alta.

### IC-31 — §5.4 Construcción B2001; ET-28 B1850.F28; RESULTADOS ESPERADOS B2593

**Problema:** Se agrupan clasificación, búsqueda semántica, recomendaciones y tendencias; notificaciones automáticas figuran futuras aunque internas ya existen.

**Evidencia/contraste:** EV-23/24/26/27/35: visual diferente de textual, matching y recomendación; EV-20: avisos internos.

**Corrección propuesta:** Nombrar tarea/entrada/salida/modelo/estado de cada capacidad y canal de notificación. Aprobación E5 no es RAG; reglas de pares no outfits; fuente vacía no extracción activa.

**Prioridad:** Alta.

### IC-32 — III CONCLUSIONES B2655–2660

**Problema:** Conclusiones alternan propuesta/estructura/planteado/enfoque con verbos de objetivos implementar/integrar; efectos futuros no se distinguen de resultados actuales.

**Evidencia/contraste:** EV-31/32: integración visual actual omitida; EV-35: recomendación/outfits no completos; anexos y pruebas con distintos tipos de evidencia.

**Corrección propuesta:** Una conclusión por OE con resultado real, evidencia, limitación y pendiente; reconocer inferencia implementada y no fingir modelo aprendido/outfit. Impacto sin evaluación permanece esperado.

**Prioridad:** Bloqueante académico.

### IC-33 — Índice B348–386 y secciones Transición/pruebas B2014/B2142/B2173–2252; resultados B2587

**Problema:** Capítulos usan §5.5 pero subtítulos conservan5.4; hay encabezados compuestos/diferentes en índice y cuerpo.

**Evidencia/contraste:** EV-01 lectura de títulos/campos, sin revisión gráfica.

**Corrección propuesta:** Cuando se autorice Word, corregir numeración jerárquica y actualizar campos/índice desde estructura aprobada. Esta entrega no cambia formato ni figuras.

**Prioridad:** Media editorial.

### IC-34 — Guías V2 y PROJECT_OVERVIEW frente a V4/reporte actual

**Problema:** Documentación histórica cita13 prendas, módulos como futuros, estados IA rotos o tablas antiguas; no toda discrepancia es contradicción de ejecución actual.

**Evidencia/contraste:** EV-34/35 son cortes anteriores; EV-31 registra134 prendas/variantes e integración posterior.

**Corrección propuesta:** Etiquetar fecha/versión y fuente más sólida para cada afirmación. Conservar historia sin copiar conteos antiguos como actuales ni adjudicar todas las mejoras a nueva definición.

**Prioridad:** Media de evidencia.

## Criterio de cierre académico propuesto

Antes de editar, aprobar alcance/actores/políticas y catálogo; preparar una tabla por objetivo con estado/evidencia/resultado/limitación. En Word, reemplazar afirmaciones generales por resultados delimitados; conservar fechas y registrar correcciones de interpretación sin alterar resultados históricos por una cifra objetivo.

La aritmética 36/60=60% y 646×1,10=710,60 es consistente como conteo/tamaño. El problema es su interpretación como calidad/probabilidad y las cifras incompatibles de confiabilidad/fiabilidad; no se debe cambiar todo número correcto solo porque el informe tenga otras incoherencias. La muestra370 es cálculo de diseño con sus supuestos, no error numérico demostrado ni ejecución de encuesta.

Para el siguiente cambio de Word se requiere autorización del usuario, se conservará el formato académico y se atenderá la numeración textual aprobada. Esta entrega termina en documentación revisable; no autoriza implementación, promoción de IA ni trabajo gráfico adicional.
