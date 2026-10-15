# MORE — Decisiones pendientes de aprobación

Fecha: 12/10/2026. Recomendaciones técnicas para discusión. **Todas las alternativas siguen pendientes de aprobación; ninguna autoriza modificación de Word, código, datos, modelos o alcance por esta entrega.** Responsable comercial: Mati o quien designe; responsable técnico/académico por identificar cuando corresponda.

La prioridad “bloqueante” significa necesaria para cerrar definición o liberar el flujo afectado; no asegura que todo el resto del producto esté aceptado. Una decisión comercial no se sustituye por una recomendación del auditor.

## DP-01 — V4 usa Administrador y Superadministrador sin una política diferenciada; el código tiene tres roles del sistema.

**Problema:** V4 usa Administrador y Superadministrador sin una política diferenciada; el código tiene tres roles del sistema.

**Alternativas:** A) Documentar administración como responsabilidad del superadmin actual. B) Incorporar administrador separado con privilegios definidos.

**Ventajas:** A evita migración y refleja lo existente. B permite separar gestión diaria y seguridad.

**Desventajas:** A concentra privilegios. B exige reglas, migración, UI y pruebas; una fila de rol no basta.

**Recomendación técnica, pendiente de aprobación:** A para describir estado actual; discutir B para versión objetivo sin conceder permisos antes de aprobar.

**Consecuencia sobre requerimientos:** RF-006/007/086; RNF-01/02; RB-02

**Consecuencia sobre alcance:** B amplía autorización; A corrige vocabulario documental.

**Prioridad de decisión:** Bloqueante de definición.

## DP-02 — OG/OE-04/OE-05 exigen aprendizaje y outfits; hoy hay reglas/diseños, no cumplimiento integral.

**Problema:** OG/OE-04/OE-05 exigen aprendizaje y outfits; hoy hay reglas/diseños, no cumplimiento integral.

**Alternativas:** A) Mantener objetivos e implementar/evaluar RF-077/078. B) Aprobar alcance académico centrado en asistencia visual y analítica descriptiva.

**Ventajas:** A conserva intención académica. B reduce riesgo y permite conclusiones sustentadas.

**Desventajas:** A necesita datos, tiempo y evaluación humana. B requiere acuerdo académico y cambiar objetivos/alcance posteriormente.

**Recomendación técnica, pendiente de aprobación:** Estimar y validar viabilidad de A primero; si datos no alcanzan, someter B explícitamente. No declarar objetivos cumplidos.

**Consecuencia sobre requerimientos:** RF-058/060/077/078/081; OE-02/04/05

**Consecuencia sobre alcance:** Decisión académica sustancial; ningún cambio de título/Word aplicado.

**Prioridad de decisión:** Bloqueante académico.

## DP-03 — Cancelación impide pagos confirmados, pero vencimiento/rechazo pueden liberar stock con anticipos previos; verify restaura confirmada.

**Problema:** Cancelación impide pagos confirmados, pero vencimiento/rechazo pueden liberar stock con anticipos previos; verify restaura confirmada.

**Alternativas:** A) Bloquear toda liberación hasta resolver dinero. B) Liberar stock conservando obligación monetaria en un flujo explícito. C) política de anticipo retenido definida por Mati y revisión aplicable.

**Ventajas:** A protege consistencia inmediata. B separa dinero y mercancía. C podría adecuarse a política comercial acordada.

**Desventajas:** A puede inmovilizar stock. B necesita libro/estado monetario y controles. C no se puede presumir desde código ni aplicar unilateralmente.

**Recomendación técnica, pendiente de aprobación:** A como contención propuesta y diseñar B; Mati debe decidir anticipos, plazos, reembolsos, listo/recogido y cobro final de vendedor.

**Consecuencia sobre requerimientos:** RF-035/036/037/038/039/040/041/042; RB-28/29/30/31/33

**Consecuencia sobre alcance:** Afecta reservas, dinero y logística; pendiente autorización de implementación.

**Prioridad de decisión:** Bloqueante comercial.

## DP-04 — No se encontró devolución de venta/reembolso completo; devolucion_danada solo causa de reporte.

**Problema:** No se encontró devolución de venta/reembolso completo; devolucion_danada solo causa de reporte.

**Alternativas:** A) Mantener fuera de versión candidata. B) Diseñar devoluciones con reglas de elegibilidad, stock y dinero.

**Ventajas:** A conserva alcance verificable. B cubre una necesidad operativa frecuente.

**Desventajas:** A obliga gestión externa documentada. B introduce decisiones financieras/comerciales y conciliación.

**Recomendación técnica, pendiente de aprobación:** A para estado actual; recopilar política real antes de estimar B.

**Consecuencia sobre requerimientos:** RF-027; RB-19

**Consecuencia sobre alcance:** B es ampliación explícita, no corrección cosmética.

**Prioridad de decisión:** Alta.

## DP-05 — CRUD usuarios desactiva pero perfil elimina físicamente; relaciones comerciales y último usuario privilegiado requieren criterio.

**Problema:** CRUD usuarios desactiva pero perfil elimina físicamente; relaciones comerciales y último usuario privilegiado requieren criterio.

**Alternativas:** A) Desactivar y conservar historial. B) Anonimización controlada preservando referencias. C) Borrado físico donde no haya obligaciones/vínculos.

**Ventajas:** A facilita auditoría. B reduce exposición de datos. C simple en cuenta sin actividad.

**Desventajas:** A retiene datos. B exige política/implementación. C puede romper relaciones o trazabilidad.

**Recomendación técnica, pendiente de aprobación:** A para operación; diseñar B con política de retención y revisión aplicable antes de uniformar flujos.

**Consecuencia sobre requerimientos:** RF-008/006; RNF-12; RB-04

**Consecuencia sobre alcance:** Unificación de política de cuentas y privacidad.

**Prioridad de decisión:** Bloqueante técnico.

## DP-06 — Stock total D+R no es conteo físico certificado; daño apartado y vendido pendiente de entrega no están representados como tercer stock.

**Problema:** Stock total D+R no es conteo físico certificado; daño apartado y vendido pendiente de entrega no están representados como tercer stock.

**Alternativas:** A) Documentar stock operativo D/R actual. B) Modelo de ubicaciones/condición/propiedad física. C) Serialización individual.

**Ventajas:** A viable y honesto. B permite conciliación física. C rastrea cada ejemplar.

**Desventajas:** A requiere conteos/reconciliación. B/C amplían esquema y flujos; no se justifican solo por tener variantes.

**Recomendación técnica, pendiente de aprobación:** A ahora; acordar inventario físico, daño/reserva y umbrales antes de B; no serializar sin necesidad de Mati.

**Consecuencia sobre requerimientos:** RF-019/021/023/024/025/057; RB-13/42

**Consecuencia sobre alcance:** Umbral actual <=3 permanece descrito; cambios y multisede no aprobados.

**Prioridad de decisión:** Alta.

## DP-07 — Dashboard mezcla unidades/recuentos y definiciones de vendido/cobrado/reservado pueden llevar a doble interpretación.

**Problema:** Dashboard mezcla unidades/recuentos y definiciones de vendido/cobrado/reservado pueden llevar a doble interpretación.

**Alternativas:** A) Definir un diccionario y corregir indicadores. B) Ocultar temporalmente gráficos confusos con aprobación.

**Ventajas:** A permite reportes comparables. B contiene información errónea pronto.

**Desventajas:** A necesita conciliar consultas. B reduce presentación pero no soluciona semántica.

**Recomendación técnica, pendiente de aprobación:** A: acordar período, total neto, venta derivada y pagos confirmados; validar cada reporte antes de medir analítica.

**Consecuencia sobre requerimientos:** RF-050/051/081; RB-38

**Consecuencia sobre alcance:** Corrección técnica posterior, sin nuevas predicciones.

**Prioridad de decisión:** Alta.

## DP-08 — Recuperación de acceso y avisos tienen código; entrega real de correo/SMS no está acreditada.

**Problema:** Recuperación de acceso y avisos tienen código; entrega real de correo/SMS no está acreditada.

**Alternativas:** A) Canal interno y correo de recuperación probado. B) Añadir transaccional email/SMS con proveedor.

**Ventajas:** A limita dependencias. B mejora comunicación externa.

**Desventajas:** A no notifica fuera del sitio. B exige cuentas, costos, privacidad y monitoreo.

**Recomendación técnica, pendiente de aprobación:** Validar primero correo de reset en entorno final; aprobar canales adicionales separadamente.

**Consecuencia sobre requerimientos:** RF-004/052/057; RNF-03/12

**Consecuencia sobre alcance:** No declarar SMTP/SMS productivos antes de verificación.

**Prioridad de decisión:** Alta para acceso; media para avisos.

## DP-09 — Vouchers usan storage public; retención y acceso a datos de cuenta/logs/imágenes no están definidos integralmente.

**Problema:** Vouchers usan storage public; retención y acceso a datos de cuenta/logs/imágenes no están definidos integralmente.

**Alternativas:** A) Acceso privado por propietario/staff. B) Mantener público con alcance explícito aceptado y controles acordados.

**Ventajas:** A reduce exposición fuera de rutas de cuenta. B menor esfuerzo inmediato.

**Desventajas:** A necesita mover/servir archivos y revisar enlaces. B mantiene exposición por URL conocida, no equivalente a autorización.

**Recomendación técnica, pendiente de aprobación:** A recomendado; definir minimización, retención, eliminación y datos permitidos en logs antes de desplegar.

**Consecuencia sobre requerimientos:** RF-043/044/053/054; RNF-01/12/14; RB-23/40

**Consecuencia sobre alcance:** Control técnico y política de privacidad pendientes; no se emite dictamen legal.

**Prioridad de decisión:** Bloqueante de liberación.

## DP-10 — Dump manual DB no demuestra recuperación de fotos/configuración/modelos o disponibilidad bajo incidentes.

**Problema:** Dump manual DB no demuestra recuperación de fotos/configuración/modelos o disponibilidad bajo incidentes.

**Alternativas:** A) Backup integral y ensayo aislado. B) Servicio administrado con restore documentado. C) dump DB manual solamente para candidato local.

**Ventajas:** A permite comprobar recuperación. B reduce operación interna. C sirve como evidencia limitada actual.

**Desventajas:** A exige almacenamiento/procedimiento. B costos/dependencia. C insuficiente para producción integral.

**Recomendación técnica, pendiente de aprobación:** A o B según infraestructura; acordar RPO/RTO y retención con Mati, luego medirlos.

**Consecuencia sobre requerimientos:** RF-055/056; RNF-03/11; RB-41

**Consecuencia sobre alcance:** Requisito de transición; no se ejecutó restore ni fijaron cifras.

**Prioridad de decisión:** Bloqueante de liberación.

## DP-11 — Fuentes externas están vacías; derechos de fotos y condiciones por proveedor no acreditados.

**Problema:** Fuentes externas están vacías; derechos de fotos y condiciones por proveedor no acreditados.

**Alternativas:** A) Solo medios propios/licenciados y referencias manuales. B) Proveedores autorizados por contrato/API.

**Ventajas:** A reduce incertidumbre. B puede enriquecer catálogo verificablemente.

**Desventajas:** A menos automatización externa. B requiere aprobación, límites, correspondencia y vigilancia de cambios.

**Recomendación técnica, pendiente de aprobación:** A primero; aprobar proveedor por proveedor y política de publicación, sin activar ejemplo por defecto.

**Consecuencia sobre requerimientos:** RF-069/082/083; RB-11/49

**Consecuencia sobre alcance:** No scraping indiscriminado; no publicación sin evidencia de derechos.

**Prioridad de decisión:** Alta.

## DP-12 — Ontología V1.1.0 es borrador sin ámbitos/dataset/modelos aprobados automáticamente.

**Problema:** Ontología V1.1.0 es borrador sin ámbitos/dataset/modelos aprobados automáticamente.

**Alternativas:** A) Aprobar ámbitos pequeños con ejemplos y anotación humana. B) intentar ontología completa de una vez.

**Ventajas:** A favorece evaluación y trazabilidad. B ofrece cobertura conceptual amplia.

**Desventajas:** A avance gradual. B costo/ambigüedad altos y falta de evidencia.

**Recomendación técnica, pendiente de aprobación:** A con responsable comercial/técnico identificados y decisiones separadas por fase; preservar IDs y estados.

**Consecuencia sobre requerimientos:** RF-011/061/072/077/078; RB-47

**Consecuencia sobre alcance:** Aprobación de política no promueve dataset o producción.

**Prioridad de decisión:** Bloqueante de nuevos atributos IA.

## DP-13 — RAG Gate V2 no obtuvo promoción aunque embedding E5 tenga perfil aprobado.

**Problema:** RAG Gate V2 no obtuvo promoción aunque embedding E5 tenga perfil aprobado.

**Alternativas:** A) Conservar deshabilitado y priorizar otras capacidades. B) nuevo experimento independiente con protocolo aprobado.

**Ventajas:** A respeta evidencia y protege respuestas. B puede resolver recuperación con métricas defendibles.

**Desventajas:** A no entrega asistente RAG. B costo de dataset, revisión, evaluación y posible nuevo rechazo.

**Recomendación técnica, pendiente de aprobación:** A actual; B solo si necesidad y datos justifican prioridad; no retocar umbrales sobre holdout para aprobar.

**Consecuencia sobre requerimientos:** RF-070/071/085; RB-46/50

**Consecuencia sobre alcance:** Gate sigue false/NO_PROMOTION; ninguna activación solicitada.

**Prioridad de decisión:** Alta de gobierno; prioridad técnica posterior.

## DP-14 — Segmentación tiene NONE y carece de GT humano independiente de target/máscaras.

**Problema:** Segmentación tiene NONE y carece de GT humano independiente de target/máscaras.

**Alternativas:** A) Mantener FULL y color manual. B) anotación experta y nuevo experimento por consumidor.

**Ventajas:** A preserva pipeline comprobado. B puede estudiar mejora real.

**Desventajas:** A limita color automático de prenda. B esfuerzo/licencias/hardware y resultado incierto.

**Recomendación técnica, pendiente de aprobación:** A actual; B preregistrado y separado AUTO/ASSISTED; no promover por ejemplo cualitativo.

**Consecuencia sobre requerimientos:** RF-062/073/074/087; RB-45/46/47

**Consecuencia sobre alcance:** No endpoint/UI nuevo ni reindexación de canónicos.

**Prioridad de decisión:** Alta de gobierno; futuro experimental.

## DP-15 — OE-04 necesita recomendador aprendido; interacciones e histórico no prueban dataset adecuado.

**Problema:** OE-04 necesita recomendador aprendido; interacciones e histórico no prueban dataset adecuado.

**Alternativas:** A) Baseline de contenido/reglas y evaluación humana. B) ranking aprendido supervisado. C) colaborativo cuando exista soporte.

**Ventajas:** A permite medir pronto. B cumple aportación ML si viable. C incorpora preferencias observadas.

**Desventajas:** A solo no cumple OE-04 tal como escrito. B exige targets/negativos y partición. C cold start y soporte escaso.

**Recomendación técnica, pendiente de aprobación:** A como comparación obligatoria; aprobar B si datos permiten evaluación fuera de entrenamiento; no inventar métricas.

**Consecuencia sobre requerimientos:** RF-075/077/081; OE-04; RNF-15/16

**Consecuencia sobre alcance:** El baseline es condición de evaluación, no sustitución unilateral del objetivo.

**Prioridad de decisión:** Bloqueante académico.

## DP-16 — Outfit, conjunto comercial y relación de categorías no son equivalentes.

**Problema:** Outfit, conjunto comercial y relación de categorías no son equivalentes.

**Alternativas:** A) Outfit como recomendación de componentes comprables individualmente. B) bundle comercial con precio/stock propio. C) solo complementos por ahora.

**Ventajas:** A evita duplicar inventario. B permite venta de conjuntos reales. C refleja implementación actual.

**Desventajas:** A requiere selección contextual/validación humana. B amplía stock/precio/promociones. C no cumple OE-05 completo.

**Recomendación técnica, pendiente de aprobación:** A para futura recomendación; B solo si Mati decide política de conjunto/paquete; C como estado actual.

**Consecuencia sobre requerimientos:** RF-010/075/076/078; OE-05

**Consecuencia sobre alcance:** Definir zonas, capas, compatibilidad y evaluación sin reservar productos al recomendar.

**Prioridad de decisión:** Bloqueante de diseño de outfits.

## DP-17 — Tallas y try-on demandan medidas/datos personales/modelos no validados.

**Problema:** Tallas y try-on demandan medidas/datos personales/modelos no validados.

**Alternativas:** A) Dejarlos fuera de versión candidata. B) investigación de talla con medidas. C) añadir síntesis visual consentida después.

**Ventajas:** A reduce riesgo. B responde a incertidumbre concreta. C aporta visualización.

**Desventajas:** A no satisface promesa amplia de alcance. B/C datos, privacidad, licencias, evaluación independiente.

**Recomendación técnica, pendiente de aprobación:** A mientras se aprueba alcance; B antes que C si hay medidas reales; talla filtrada no es recomendación.

**Consecuencia sobre requerimientos:** RF-079/080; RNF-12/15

**Consecuencia sobre alcance:** Posponer/reducir alcance académico requiere aprobación explícita DP-02.

**Prioridad de decisión:** Alta de alcance; futura implementación.

## DP-18 — Tendencias y predicción dependen de definición, tiempo, región y fuentes distintas de dashboard.

**Problema:** Tendencias y predicción dependen de definición, tiempo, región y fuentes distintas de dashboard.

**Alternativas:** A) Analítica descriptiva claramente delimitada. B) experimentos temporales de demanda. C) Trend Intelligence externo contextual.

**Ventajas:** A usa datos actuales. B evalúa utilidad empresarial. C incorpora referencias externas.

**Desventajas:** A no es ML. B histórico insuficiente posible. C difícil transferencia a Mati y derechos.

**Recomendación técnica, pendiente de aprobación:** A actual; B antes de C cuando exista historial y variables/horizonte acordados.

**Consecuencia sobre requerimientos:** RF-050/051/081/082; RNF-15

**Consecuencia sobre alcance:** Ningún forecast ni trend engine se presenta terminado.

**Prioridad de decisión:** Media.

## DP-19 — RAG/router no constituyen LLM de descripciones ni asistente natural/SQL seguro.

**Problema:** RAG/router no constituyen LLM de descripciones ni asistente natural/SQL seguro.

**Alternativas:** A) Plantillas sobre datos confirmados y consultas parametrizadas por intents. B) LLM con herramientas de lectura autorizadas.

**Ventajas:** A acota comportamiento. B flexibilidad de lenguaje.

**Desventajas:** A menor expresividad. B requiere modelo/licencias/seguridad/costo y evaluación de alucinación/permisos.

**Recomendación técnica, pendiente de aprobación:** A como baseline futuro; B solo con contrato de herramientas, evidencia y abstención; jamás autoridad comercial del LLM.

**Consecuencia sobre requerimientos:** RF-084/085; RF-071; RNF-01/12/15

**Consecuencia sobre alcance:** No SQL libre ni agente de escritura autorizado por esta tarea.

**Prioridad de decisión:** Media / futura.

## DP-20 — La captura continua tipo Lens, UI de target y perfil de usuario son diseños, no evidencia de flujo terminado.

**Problema:** La captura continua tipo Lens, UI de target y perfil de usuario son diseños, no evidencia de flujo terminado.

**Alternativas:** A) Subida puntual asistida actual. B) flujo continuo con identidad de sesión/foto/target.

**Ventajas:** A permite validar integración existente. B aporta experiencia nueva.

**Desventajas:** A no escaneo continuo. B captura/privacidad/concurrencia y nuevas pruebas E2E.

**Recomendación técnica, pendiente de aprobación:** A para versión candidata; B solo tras decidir necesidad y dependencias de target.

**Consecuencia sobre requerimientos:** RF-058/065/073/087

**Consecuencia sobre alcance:** No ampliar alcance por contratos target-selection presentes.

**Prioridad de decisión:** Baja / evolución futura.

## Registro de resolución a completar

Al aprobar una decisión registrar alternativa elegida, responsable, fecha real, fundamento, RF/RNF/RB afectados y evidencia de aceptación. La aprobación de una propuesta de alcance no prueba implementación; la aprobación de definición ontológica no aprueba dataset/modelo/producción. No asignar fechas ni responsables ficticios.

El próximo trabajo autorizado deberá partir de una decisión concreta y su impacto, con el orden de [07](07_AUP_PLANIFICACION.md). Este documento no contiene una aceptación automática ni una solicitud de cambio ya aplicada.
