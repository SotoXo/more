# MORE — Trazabilidad de objetivos, requisitos, trabajo y evidencia

Corte: 12/10/2026. Esta matriz permite revisar cobertura y huecos. **Las correspondencias funcionales propuestas no son enlaces oficiales aprobados ni historial de ejecución reconstruido.**

## Identificadores y tipos de vínculo

OE-01–05 son aliases de los cinco objetivos por orden en V4 §3.6.2 (B1693–1697), sin renumerar Word. HU-V4-01–16 son aliases de ID 1–16 de Tabla 8 (B1839), no historias nuevas realizadas. RF-001–087 son la nueva propuesta; RF1–RF10 de Tabla 6 siguen diferenciados. C1–C7/E3/T1/T2 son iteraciones **declaradas** por Tabla 10, cuya ejecución fechada por RF no está acreditada automáticamente.

- “Asociación propuesta” indica afinidad técnica/temática sustentada por contenido; su formalización requiere aprobación.
- “FALTA” indica ausencia de historia, enlace formal, prueba específica o asignación histórica suficiente.
- P-E/P-C/P-T/P-X son identificadores nuevos del plan propuesto del documento 07. No son trabajo pasado.
- Test nombrado significa prueba presente o caso declarado; su resultado solo se considera reportado donde EV-31/32/29 lo respalde explícitamente.
- Estado refleja implementación/evidencia, no porcentaje de objetivo completo.

## Objetivos y evidencia de cumplimiento

| OE de V4 | Artefacto y RF relevantes | HU / iteración declarada | Prueba/evidencia | Vacío para concluir cumplimiento |
| --- | --- | --- | --- | --- |
| OE-01 Caracterizar procesos y limitaciones de inventario | V4 anexos 1/2/4 B3142–3189/B3214–3231; RF-019–042 expresan necesidades derivadas | I0 declarado; no HU de levantamiento como software | Entrevistas con respuestas y ficha de observación en Word (EV-01); no test software aplicable | Falta relacionar fecha, participantes, método y fuente primaria; encuesta anexo 3 es guía/opciones, no 370 respuestas tabuladas. |
| OE-02 Modelo de registro y clasificación visual | RF-058–068; RF-061 parcial; RF-072/073/074 apoyos condicionados | FALTA HU específica; E3 descrita como diseño no operativo | EV-23/25/31/32/34; inferencia real y evaluación N130 documentadas | Definir atributos cubiertos y dataset independiente; actualizar E3 sin inventar una iteración IA ya ejecutada. |
| OE-03 Web de inventario y productos | RF-009–026/028–057; acceso como soporte | HU 3–16; C2–C7 declarados | EV-04–21/31/33; tests selectivos y casos V4 | Matriz de permisos, stock-dinero, transición y recuperación integral sin aceptación completa. |
| OE-04 Análisis ML para recomendaciones | RF-077, apoyo RF-075/081 | FALTA HU propia; ET-29; no C de modelo demostrado | EV-35 diseño y motor de reglas; no prueba de recomendador aprendido | Modelo/target/datos/evaluación y aprobación pendientes DP-02/15. RF-075 no cumple el objetivo por sí solo. |
| OE-05 Recomendación y outfits | RF-078; apoyo RF-010/075/076 | FALTA HU propia; ET-29; no C de motor completo demostrado | EV-28/35 contratos/reglas | Outfit integrado/evaluado con contexto, variantes y juicio humano faltante; DP-02/16. |

## Matriz por requerimiento

En cada fila la relación OE y HU es propuesta cuando se indica; una HU genérica o parcial **no se cuenta como cobertura completa**. Las iteraciones V4 indicadas son correspondencia temática/documental, no prueba de ejecución de ese RF. La columna de evidencia conserva origen con el que verificar y detectar el faltante.

### Acceso

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (soporte de acceso, relación propuesta) | RF-001 | Acceso | Visitante | HU-V4-01 (parcial: autenticación) | C1; asignación por RF no formalizada | RB-001/RB-002 | RegistrationTest | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-03/EV-33 |
| OE-03 (soporte de acceso, relación propuesta) | RF-002 | Acceso | Usuario | HU-V4-01 | C1; asignación por RF no formalizada | RB-002/RB-003 | AuthenticationTest | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-03/EV-33 |
| OE-03 (soporte de acceso, relación propuesta) | RF-003 | Acceso | Usuario autenticado | HU-V4-01 (extensión no explícita) | C1; asignación por RF no formalizada | RB-003 | AuthenticationTest | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-02/EV-03/EV-33 |
| OE-03 (soporte de acceso, relación propuesta) | RF-004 | Acceso | Usuario | FALTA HU específica en V4 | C1; asignación por RF no formalizada | RB-003 | PasswordResetTest | PARCIALMENTE IMPLEMENTADA | EV-02/EV-03/EV-33 |
| OE-03 (soporte de acceso, relación propuesta) | RF-005 | Acceso | Usuario autenticado | FALTA HU específica en V4 | C1; asignación por RF no formalizada | RB-002/RB-003 | ProfileTest; PasswordUpdateTest | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-03/EV-33 |
| OE-03 (soporte de acceso, relación propuesta) | RF-006 | Acceso | Superadministrador | HU-V4-02 | C1; asignación por RF no formalizada | RB-001/RB-002 | V4 CN1.2 / CB1.3; sin revalidación actual completa | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-03/EV-04/EV-05 |
| OE-03 (soporte de acceso, relación propuesta) | RF-007 | Acceso | Superadministrador | HU-V4-02 | C1; asignación por RF no formalizada | RB-002 | AuthenticationTest (actividad, no CRUD completo) | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-03/EV-04 |
| OE-03 (soporte de acceso, relación propuesta) | RF-008 | Acceso | Superadministrador / titular; política pendiente | FALTA HU específica en V4 | P-E1 (propuesta); asignación por RF no formalizada | RB-004 | ProfileTest cubre borrado básico; caso comercial faltante | PARCIALMENTE IMPLEMENTADA | EV-03/EV-04/EV-07 |

### Catálogo

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-009 | Catálogo | Superadministrador | HU-V4-05 | C2; asignación por RF no formalizada | RB-005 | V4 CN2.3; lectura de pantallas EV-31 | IMPLEMENTADA Y COMPROBADA | EV-04/EV-05/EV-07/EV-31 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-010 | Catálogo | Superadministrador | HU-V4-05 (no cubre complementos) | C2; asignación por RF no formalizada | RB-005/RB-006 | Migraciones; sin prueba funcional actual de jerarquía | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-04/EV-07/EV-35 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-011 | Catálogo | Superadministrador | HU-V4-03 (cobertura general insuficiente) | C2; asignación por RF no formalizada | RB-005/RB-007 | MatiAiWorkflowTest; validaciones del servidor | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-05/EV-06/EV-07/EV-25 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-012 | Catálogo | Superadministrador | HU-V4-03 | C2; asignación por RF no formalizada | RB-001/RB-005/RB-007 | V4 CN2.1 / CB2.1; no POST comercial en EV-31 | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-04/EV-05/EV-06/EV-07 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-013 | Catálogo | Superadministrador | HU-V4-04 | C2/C3; asignación por RF no formalizada | RB-008 | InventoryP0RegressionTest: 12 pruebas documentadas en EV-31 | IMPLEMENTADA Y COMPROBADA | EV-05/EV-06/EV-31/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-014 | Catálogo | Superadministrador | HU-V4-04 | C2; asignación por RF no formalizada | RB-009 | Inspección deleteBlockedReason/removeProductVariant | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-04/EV-06/EV-07 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-015 | Catálogo | Superadministrador | HU-V4-06 | C2; asignación por RF no formalizada | RB-001/RB-007/RB-008 | InventoryP0RegressionTest (propiedad/stock) | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-05/EV-06/EV-07/EV-31/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-016 | Catálogo | Superadministrador | HU-V4-06 | C2; asignación por RF no formalizada | RB-001/RB-010 | garmentAssistant.test.mjs; test_identification.py | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-05/EV-06/EV-07/EV-30/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-017 | Catálogo | Superadministrador | HU-V4-07 | C2; asignación por RF no formalizada | RB-011 | V4 CN2.5 no terminado histórico; muestra local EV-31, no todas las altas | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-05/EV-06/EV-07 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-018 | Catálogo | Operador técnico autorizado | FALTA HU específica en V4 | C2 (afinidad, no ejecución de iteración acreditada); asignación por RF no formalizada | RB-011 | CatalogRealImportTest | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-06/EV-33 |

### Inventario

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-019 | Inventario | Vendedor / Superadministrador | HU-V4-08 / HU-V4-14 | C3/C7; asignación por RF no formalizada | RB-012/RB-013 | InventoryP0RegressionTest y lectura EV-31 | IMPLEMENTADA Y COMPROBADA | EV-07/EV-08/EV-14/EV-31 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-020 | Inventario | Superadministrador | HU-V4-08 (actor a corregir) | C3; asignación por RF no formalizada | RB-008/RB-014 | InventoryP0RegressionTest | IMPLEMENTADA Y COMPROBADA | EV-06/EV-07/EV-08/EV-31/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-021 | Inventario | Superadministrador | HU-V4-08 (vendedor no autorizado a ajuste directo) | C3; asignación por RF no formalizada | RB-015/RB-016 | InventoryP0RegressionTest | IMPLEMENTADA Y COMPROBADA | EV-04/EV-08/EV-31/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-022 | Inventario | Superadministrador | HU-V4-11 (cubre efecto, no Kardex completo) | C3; asignación por RF no formalizada | RB-016 | InventoryP0RegressionTest; V4 CB3.2 | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-08/EV-20 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-023 | Inventario | Vendedor / Superadministrador | HU-V4-08 | C3/C7; asignación por RF no formalizada | RB-013 | V4 CB3.5 observado; sin medición de reposición | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-08/EV-14 |

### Daños

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-024 | Daños | Vendedor / Superadministrador | FALTA HU específica en V4 | C6/C7; asignación por RF no formalizada | RB-017 | DamagedProductNameTest; inspección servicio | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-14/EV-15/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-025 | Daños | Superadministrador | FALTA HU específica en V4 | C6; asignación por RF no formalizada | RB-017/RB-018 | V4 CB3.4 observado; escenarios completos faltantes | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-15/EV-08 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-026 | Daños | Superadministrador | FALTA HU específica en V4 | C6; asignación por RF no formalizada | RB-018 | Inspección; no aceptación de todos los cierres | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-15/EV-08 |

### Devoluciones

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-027 | Devoluciones | Personal autorizado por política pendiente | FALTA HU específica en V4 | P-E1 (propuesta); asignación por RF no formalizada | RB-019 | Sin flujo ni prueba equivalente encontrados | PROPUESTA | EV-02/EV-15/EV-35 |

### Ventas

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-028 | Ventas | Vendedor / Superadministrador | HU-V4-09 / HU-V4-11 | C4/C7; asignación por RF no formalizada | RB-020/RB-021/RB-022 | InventoryP0RegressionTest (aritmética); resto sin validación actual completa | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-08/EV-09/EV-31/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-029 | Ventas | Cliente | FALTA HU específica en V4 | C4/C5; asignación por RF no formalizada | RB-012/RB-021/RB-022/RB-023 | CriticalReservationCheckoutP1Test; V4 CN4 general, sin checkout EV-31 | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-10/EV-17/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-032 | Ventas | Superadministrador; no ruta equivalente vendedor | FALTA HU específica en V4 | C4; asignación por RF no formalizada | RB-025 | Inspección cancelOnlineSale | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-02/EV-09/EV-08 |

### Pagos

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-030 | Pagos | Vendedor / Superadministrador | FALTA HU específica en V4 | C4/C7; asignación por RF no formalizada | RB-023/RB-024 | V4 CB4.2 observado; inspección confirmOnlineSale | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-09/EV-14/EV-08 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-031 | Pagos | Vendedor / Superadministrador | FALTA HU específica en V4 | C4/C7; asignación por RF no formalizada | RB-024/RB-025 | Inspección markOnlineSaleIncomplete/reject | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-09/EV-14/EV-08 |

### Reservas

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-033 | Reservas | Cliente / Vendedor / Superadministrador | HU-V4-10 / HU-V4-11 | C4/C7; asignación por RF no formalizada | RB-012/RB-026/RB-027 | InventoryP0RegressionTest (stock); frontend commercialValidation (no rerun) | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-11/EV-12/EV-17/EV-31/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-034 | Reservas | Vendedor / Superadministrador | FALTA HU específica en V4 | C4/C7; asignación por RF no formalizada | RB-023/RB-027 | Inspección VendedorReservationService; cobertura específica faltante | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-12/EV-14 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-035 | Reservas | Vendedor / Superadministrador | FALTA HU específica en V4 | C4/C7; asignación por RF no formalizada | RB-026/RB-027/RB-028 | Inspección verifyPayment; falta matriz multia bono/logística | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-11/EV-12/EV-14 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-036 | Reservas | Vendedor / Superadministrador | FALTA HU específica en V4 | C4/C7; asignación por RF no formalizada | RB-028/RB-029 | Inspección rejectLatestPendingPayment; gap monetario | PARCIALMENTE IMPLEMENTADA | EV-11/EV-12/EV-14 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-037 | Reservas | Vendedor / Superadministrador | HU-V4-10 (sin criterio de cancelación) | C4/C7; asignación por RF no formalizada | RB-029 | CriticalReservationCheckoutP1Test (presente, sin nueva ejecución) | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-11/EV-12/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-038 | Reservas | Superadministrador | FALTA HU específica en V4 | C4; asignación por RF no formalizada | RB-029/RB-030 | CriticalReservationCheckoutP1Test; falta caso monetario | PARCIALMENTE IMPLEMENTADA | EV-11/EV-12/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-039 | Reservas | Superadministrador | FALTA HU específica en V4 | C4; asignación por RF no formalizada | RB-031 | Inspección markReady; guard temporal no acreditado | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-11/EV-20 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-040 | Reservas | Superadministrador | FALTA HU específica en V4 | C4; asignación por RF no formalizada | RB-032/RB-033 | InventoryP0RegressionTest (stock); no aceptación integral nueva | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-11/EV-12/EV-13/EV-31/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-042 | Reservas | Superadministrador | FALTA HU específica en V4 | C4 (refinamiento sin historial acreditado); asignación por RF no formalizada | RB-033 | Inspección settleCompletedReservation | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-11/EV-13 |

### Logística

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-041 | Logística | Vendedor / Superadministrador | FALTA HU específica en V4 | C7; asignación por RF no formalizada | RB-031/RB-032 | V4 CN4.5 observado; inspección VendedorPickupService | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-13/EV-14 |

### Comprobantes

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-043 | Comprobantes | Cliente propietario / Superadministrador | FALTA HU específica en V4 | C4/C5; asignación por RF no formalizada | RB-034 | V4 CB4 general; ReceiptController/ReceiptService | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-18/EV-13 |

### Cuenta

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-044 | Cuenta | Cliente | FALTA HU específica en V4 | C5; asignación por RF no formalizada | RB-034 | Inspección CuentaController; aceptación por todos los roles pendiente | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-18/EV-02 |

### Tienda

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-045 | Tienda | Visitante / Cliente | HU-V4-12 / HU-V4-14 | C5; asignación por RF no formalizada | RB-035 | Lectura real EV-31; no todos los productos | IMPLEMENTADA Y COMPROBADA | EV-16/EV-31 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-046 | Tienda | Visitante / Cliente | HU-V4-13 | C5; asignación por RF no formalizada | RB-035 | EV-31 filtro concreto; V4 CN5.2 observado histórico | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-16/EV-31 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-047 | Tienda | Visitante / Cliente | FALTA HU específica en V4 | C5; asignación por RF no formalizada | RB-036 | purchaseCart.test.mjs | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-17/EV-10/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-048 | Tienda | Visitante / Cliente | FALTA HU específica en V4 | C5; asignación por RF no formalizada | RB-036 | AuthenticationTest; CriticalReservationCheckoutP1Test | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-17/EV-11/EV-33 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-049 | Tienda | Cliente; ruta exige auth | HU-V4-15 | C5; asignación por RF no formalizada | RB-037 | V4 CB5.4 observado; sin nueva aceptación | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-18/EV-07 |

### Administración

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-050 | Administración | Vendedor / Superadministrador | HU-V4-16 (reporte, no todo dashboard) | C6/C7; asignación por RF no formalizada | RB-038 | Lectura EV-31; defecto de gráfico documentado | PARCIALMENTE IMPLEMENTADA | EV-19/EV-14/EV-31 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-051 | Administración | Vendedor / Superadministrador | HU-V4-16 | C6/C7; asignación por RF no formalizada | RB-038 | V4 CN6.2 / CB6.2 observado; lectura EV-31 | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-19/EV-14 |

### Comunicación

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-052 | Comunicación | Cliente / Vendedor / Superadministrador | FALTA HU específica en V4 | C6/C7; asignación por RF no formalizada | RB-039 | Inspección NotificationController/CommercialNotificationService | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-20/EV-02 |

### Trazabilidad

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-053 | Trazabilidad | Sistema / Superadministrador | FALTA HU específica en V4 | C6; asignación por RF no formalizada | RB-040 | V4 CB6.3; MatiAiClientTest (fallback) | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-03/EV-20/EV-22 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-054 | Trazabilidad | Sistema / Superadministrador | FALTA HU específica en V4 | C6; asignación por RF no formalizada | RB-040 | V4 CB6.4; inspección AppServiceProvider | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-20/EV-07 |

### Continuidad

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-055 | Continuidad | Superadministrador | FALTA HU específica en V4 | C6; asignación por RF no formalizada | RB-041 | V4 CB6.5 no terminado; sin restauración comprobada | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-21/EV-02 |
| OE-03 (gestión e inventario; asociación propuesta) | RF-056 | Continuidad | Operador técnico autorizado | FALTA HU específica en V4 | P-T1 (propuesta); asignación por RF no formalizada | RB-041 | No ensayo integral acreditado | PROPUESTA | EV-21/EV-36 |

### Configuración

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-03 (gestión e inventario; asociación propuesta) | RF-057 | Configuración | Superadministrador / operador técnico | FALTA HU específica en V4 | C6; asignación por RF no formalizada | RB-042 | Inspección configuración; sin panel global de parámetros | PARCIALMENTE IMPLEMENTADA | EV-04/EV-08/EV-22/EV-36 |

### IA visual

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-058 | IA visual | Superadministrador | FALTA HU específica en V4 | E3; ejecución posterior sin iteración histórica acreditada; asignación por RF no formalizada | RB-043/RB-044 | MatiAiWorkflowTest y E2E P1 documentado | IMPLEMENTADA Y COMPROBADA | EV-22/EV-23/EV-25/EV-31/EV-32/EV-33 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-059 | IA visual | Superadministrador / servicio IA | FALTA HU específica en V4 | E3 (afinidad documental); asignación por RF no formalizada | RB-011/RB-043 | test_vision.py; pruebas de input P1 documentadas | IMPLEMENTADA Y COMPROBADA | EV-22/EV-23/EV-31/EV-32/EV-33 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-060 | IA visual | Superadministrador | FALTA HU específica en V4 | E3; P-C2 para consolidación; asignación por RF no formalizada | RB-043/RB-044 | N130 documentado + EV-31 inferencia con abstención, sin generalización | IMPLEMENTADA Y COMPROBADA | EV-23/EV-25/EV-31/EV-32/EV-34 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-061 | IA visual | Superadministrador | FALTA HU específica en V4 | P-E2/P-C2 (propuesta); asignación por RF no formalizada | RB-043/RB-044/RB-047 | MatiAiP1Test; falta GT independiente por atributo | PARCIALMENTE IMPLEMENTADA | EV-23/EV-25/EV-28/EV-33 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-062 | IA visual | Superadministrador | FALTA HU específica en V4 | E3; P-E2 para investigación futura; asignación por RF no formalizada | RB-045 | ColorimetryCatalogGuardTest; EV-31 abstención | IMPLEMENTADA Y COMPROBADA | EV-23/EV-25/EV-29/EV-31/EV-33 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-063 | IA visual | Servicio IA / Superadministrador | FALTA HU específica en V4 | E3; P-C2 para aceptación; asignación por RF no formalizada | RB-046 | EV-31 vector 768; auditoría pgvector histórica | IMPLEMENTADA Y COMPROBADA | EV-23/EV-24/EV-31/EV-32/EV-33 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-064 | IA visual | Superadministrador | FALTA HU específica en V4 | P-C2 (consolidación propuesta); asignación por RF no formalizada | RB-046/RB-048 | Auditoría pgvector; no consulta real nueva EV-31 | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-24/EV-34/EV-33 |

### Identificación

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-065 | Identificación | Superadministrador | FALTA HU específica en V4 | P-C2 (propuesta); asignación por RF no formalizada | RB-010/RB-048 | test_identification.py; MatiAiWorkflowTest exact lookup | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-24/EV-30/EV-25/EV-32/EV-33 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-066 | Identificación | Superadministrador | FALTA HU específica en V4 | P-C2 (propuesta); asignación por RF no formalizada | RB-044/RB-048 | E2E label/fallback P1 documentado, no calidad científica | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-30/EV-22/EV-32/EV-33 |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-067 | Identificación | Superadministrador | FALTA HU específica en V4 | P-C2 (propuesta); asignación por RF no formalizada | RB-010/RB-048 | E2E EAN13/QR P1 documentado; fotos variadas pendientes | IMPLEMENTADA Y COMPROBADA | EV-30/EV-22/EV-32/EV-33 |

### Gobierno IA

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-068 | Gobierno IA | Superadministrador | FALTA HU específica en V4 | E3; P-C2 consolidación; asignación por RF no formalizada | RB-043/RB-044/RB-049 | MatiAiWorkflowTest; garmentAssistant.test.mjs; E2E P1 limitado | IMPLEMENTADA Y COMPROBADA | EV-25/EV-32/EV-33 |

### Publicación IA

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-069 | Publicación IA | Superadministrador | FALTA HU específica en V4 | P-C2 (propuesta); asignación por RF no formalizada | RB-011/RB-049 | MatiAiWorkflowTest publication; pipeline tests | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-25/EV-30/EV-33 |

### IA textual

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-070 | IA textual | Servicio IA / operador técnico | FALTA HU específica en V4 | P-E3 (propuesta); asignación por RF no formalizada | RB-046/RB-050 | test_rag_text.py; perfil aprobado, no nueva inferencia | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-26/EV-31/EV-33 |

### RAG

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-071 | RAG | Superadministrador | FALTA HU específica en V4 | P-E3 (propuesta); asignación por RF no formalizada | RB-050 | RagGateV2AccessTest; NO_PROMOTION fechado | EXPERIMENTAL | EV-26/EV-27/EV-33 |

### Fashion Ontology

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-072 | Fashion Ontology | Mati como revisor comercial / responsable técnico por designar | FALTA HU específica en V4 | P-E2 (propuesta); asignación por RF no formalizada | RB-047/RB-049 | Validadores ontológicos presentes; aceptación humana faltante | DISEÑADA | EV-28/EV-35 |

### Detección

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-073 | Detección | Investigador / revisor humano | FALTA HU específica en V4 | P-X1 (experimental propuesta); asignación por RF no formalizada | RB-045/RB-047 | Experimentos DINO/SAM documentados; sin GT de instancia independiente | EXPERIMENTAL | EV-29/EV-28 |

### Segmentación

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-02 (apoyo propuesto; Ontología/RAG/detección requieren vínculo específico) | RF-074 | Segmentación | Investigador / revisor humano | FALTA HU específica en V4 | P-X1 (experimental propuesta); asignación por RF no formalizada | RB-045/RB-046/RB-047 | 84 pruebas experimentales documentadas; gate sin ganador | EXPERIMENTAL | EV-29 |

### Recomendación

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-05 (apoyo limitado por reglas; no cumplimiento integral) | RF-075 | Recomendación | Visitante / Cliente | FALTA HU específica en V4 | C5 (afinidad; sin historial de iteración acreditado); asignación por RF no formalizada | RB-035/RB-038/RB-048 | Inspección ComplementaryProductService; baseline pendiente | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-35/EV-16/EV-07 |
| OE-05 (apoyo limitado por reglas; no cumplimiento integral) | RF-076 | Recomendación | Superadministrador / consumidor técnico | FALTA HU específica en V4 | P-C3 (propuesta); asignación por RF no formalizada | RB-045/RB-048 | Motor y contratos presentes; evaluación humana local faltante | IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE | EV-22/EV-35 |
| OE-04 (capacidad objetivo pendiente) | RF-077 | Recomendación | Cliente / responsable de modelo | FALTA HU específica en V4 | P-E4/P-C3 (propuesta); asignación por RF no formalizada | RB-047/RB-048/RB-050 | No modelo aprendido/productivo ni aceptación acreditados | DISEÑADA | EV-35/EV-28/EV-01 |
| OE-05 (objetivo pendiente) | RF-078 | Recomendación | Cliente | FALTA HU específica en V4 | P-E4/P-C3 (propuesta); asignación por RF no formalizada | RB-047/RB-048/RB-050 | Solo contratos/diseño; evaluación de outfits completa ausente | DISEÑADA | EV-28/EV-35/EV-01 |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-079 | Recomendación | Cliente | FALTA HU específica en V4 | P-X2 (propuesta futura); asignación por RF no formalizada | RB-047/RB-050 | Sin sistema/validación de ajuste encontrado | DISEÑADA | EV-01/EV-35 |

### Probador virtual

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-080 | Probador virtual | Cliente voluntario / investigador | FALTA HU específica en V4 | P-X2 (propuesta futura); asignación por RF no formalizada | RB-047/RB-050 | Diseño/documentación; sin inferencia productiva | DISEÑADA | EV-01/EV-35 |

### Analítica inteligente

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OE-04 (capacidad objetivo pendiente) | RF-081 | Analítica inteligente | Superadministrador / responsable de modelo | FALTA HU específica en V4 | P-E4/P-X3 (propuesta); asignación por RF no formalizada | RB-038/RB-050 | Sin modelo entrenado de demanda/ventas en uso | DISEÑADA | EV-19/EV-35/EV-01 |

### Tendencias

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-082 | Tendencias | Superadministrador / investigador | FALTA HU específica en V4 | P-X3 (propuesta futura); asignación por RF no formalizada | RB-047/RB-050 | Documentación de Trend Intelligence; sin operación acreditada | DISEÑADA | EV-28/EV-35/EV-01 |

### Enriquecimiento

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-083 | Enriquecimiento | Superadministrador | FALTA HU específica en V4 | P-E2/P-C2 (propuesta); asignación por RF no formalizada | RB-011/RB-048/RB-049 | test_enrichment.py; EV-31 fuentes false | PARCIALMENTE IMPLEMENTADA | EV-30/EV-31/EV-32/EV-33 |

### Asistencia textual

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-084 | Asistencia textual | Superadministrador | FALTA HU específica en V4 | P-X4 (propuesta futura); asignación por RF no formalizada | RB-043/RB-049/RB-050 | No generación LLM productiva encontrada | DISEÑADA | EV-28/EV-35 |

### Asistente

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-085 | Asistente | Superadministrador; cliente limitado por política pendiente | FALTA HU específica en V4 | P-X4 (propuesta futura); asignación por RF no formalizada | RB-050 | RagGateV2Test cubre routing, no asistente completo | DISEÑADA | EV-26/EV-27/EV-28/EV-35 |

### Autorización objetivo

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-086 | Autorización objetivo | Administrador propuesto | FALTA HU específica en V4 | FALTA asignación; no ejecución inferida | RB-002 | Sin rol funcional administrativo distinto acreditado | PROPUESTA | EV-02/EV-03/EV-01 |

### Captura inteligente

| OE / cobertura propuesta | RF | Módulo | Actor | HU / faltante | Iteración / vínculo | RB | Prueba documentada o presente | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SIN VÍNCULO DIRECTO APROBADO; revisar alcance | RF-087 | Captura inteligente | Superadministrador / operador por definir | FALTA HU específica en V4 | P-X1 (propuesta futura); asignación por RF no formalizada | RB-043/RB-047/RB-049 | Contratos target-selection presentes; UI E2E continua ausente | DISEÑADA | EV-28/EV-35 |

## Historias originales y cobertura insuficiente

| Historia V4 | RF propuestos relacionados | Evaluación / faltante |
| --- | --- | --- |
| 1 Autenticación de usuario | RF-002; extensión RF-001/003/004/005 | No cubre por sí sola registro/reset/perfil/logout ni estados/roles/archivos. |
| 2 Usuarios y roles (Administrador) | RF-006/007; DP-01 | Actor ambiguo respecto a superadmin actual; permisos específicos faltan. |
| 3 Registro de prendas | RF-011/012 | Agregar aceptación por campos y revisión de atributos; no incluye HU IA explícita. |
| 4 Edición de prendas | RF-013/014 | Separar baja/borrado, historial y preservación de stock. |
| 5 Parámetros | RF-009/010/011 | Maestros cubiertos en intención; complementos/atributos nuevos no descritos en detalle. |
| 6 Variantes | RF-015/016 | Exigir unicidad/formato, propia/ajena, agotada/nueva y autoridad del precio. |
| 7 Imágenes | RF-017/069 | HU solo carga manual general; derechos/token/preparación IA no explicitados. |
| 8 Inventario (Vendedor) | RF-019/021/023 | Consultar sí; ajuste directo corresponde a superadmin. Actualizar por operación comercial se distingue de permiso de editar cantidades. |
| 9 Venta | RF-028; apoyo RF-030/031/041 | Historia directa no cubre checkout online, voucher y logística completa. |
| 10 Reserva | RF-033; extensión RF-034–042 | Faltan anticipo/abonos/rechazo/cancelación/vencimiento/entrega y política monetaria. |
| 11 Stock automático en venta/reserva | RF-028/029/033/040 | Efecto en D/R; no autoriza ajuste libre ni demuestra todas las carreras. |
| 12 Catálogo | RF-045 | Separar visitante de cliente autenticado. |
| 13 Filtros | RF-046 | Detallar coincidencia de variante, disponibilidad y campos efectivamente soportados. |
| 14 Disponibilidad | RF-019/045 | Disponibilidad informativa no es garantía de stock al confirmar. |
| 15 Favoritos | RF-049 | Está en HU/ET-17 pero sin RF independiente en Tabla 6. |
| 16 Reportes | RF-051; extensión RF-050 | No cubre todos los controles/logs/backups ni pronóstico. |

Para funcionalidades sin HU se proponen nuevas historias solo después de aprobar RF/alcance, con actor concreto, necesidad, precondición, alternativas y aceptación del documento 02. Esta entrega registra la ausencia; no asigna historias ficticias como si ya existieran en el Word.

## Casos y suites que pueden vincularse con fundamento

| Prueba/origen | RF/RB principales | Qué conserva el vínculo | Qué no permite afirmar |
| --- | --- | --- | --- |
| V4 CN1.1–1.5 / CB1.1–1.5 | Acceso RF-001–007, RB-002/003 | Casos manuales con fechas declaradas de junio; resultado en tablas 21/22 y 34/35 | Ejecución actual por role/archivo o correo real |
| InventoryP0RegressionTest | RF-013/015/019/020/021 y deltas RF-028/033/040; RB-008/012/014/015/020/032 | Métodos y 12 pruebas/58 aserciones reportadas 11/10 en EV-31 | Aceptación integral de cada venta, pago o recojo |
| CriticalReservationCheckoutP1Test | RF-029/033/037/038; RB-021/029/030 | Guards de actividad/rol, stock y plazos; pagos confirmados bloquean cancelar | Nueva ejecución en esta tarea o política de anticipos al vencer/rechazar |
| CommercialOperationTest / ConcurrencyTest | RF-028/029/033; RB-022 | UUID repetido/validación y test de dos procesos en PostgreSQL testing | Idempotencia de toda modificación ni concurrencia productiva universal |
| tests/frontend/commercialValidation / purchaseCart / commercialOperation | RF-028/029/033/047/048; RB-021/022/026/036 | Validación UI y recuperación de intención presentes | Servidor probado ni navegador/roles completos |
| MatiAiWorkflowTest / MatiAiP1Test / garmentAssistant | RF-058/059/060/062/068/069; RB-043/044/045/049 | Ownership, abstención, guardar valor real, derechos/token y fallback; EV-32 reporta E2E específico | Suite total sin errores P2 o eficacia científica |
| test_identification.py / test_core_api.py | RF-065/066/067; RB-010/048 | Contratos/código/QR/OCR y errores; E2E P1 de inputs concretos | GT científico de OCR ni identidad de cualquier producto |
| RagPersistenceTest / RagFoundationTest / test_rag_text.py | RF-070/071; RB-046/050 | Identidad, dimensiones/corpus y fail-closed | Recuperación habilitada o generación LLM |
| RagGateV2AccessTest / RagGateV2Test | RF-071/085; RB-050 | Acceso, contexto/hash, request sin bypass, routing | Asistente de lenguaje natural completo o herramienta ejecutada |
| Tests de ontología y gobierno | RF-072; RB-047 | Validación estructural/IDs/estados del borrador | Aprobación comercial de ámbito o dataset |
| Scripts/test_policy/test_metrics de segmentación | RF-073/074; RB-045/046/047 | 84 pruebas experimentales reportadas EV-29; gate NONE | Módulo productivo o métrica de máscara humana |
| V4 CN6/CB6 | RF-050–055; RB-038/040/041 | Declaraciones manuales del informe | Indicadores ML, backup restaurado o evaluación real de disponibilidad |

## Brechas de trazabilidad que bloquean conclusiones completas

1. Objetivos de IA sin RF verificables en catálogo original, sin HU ni iteración de ejecución fechada equivalente.
2. RF generales mezclan operaciones independientes; ET más detallados no enlazan sus criterios a casos.
3. C1–C7 son declarados en V4, pero falta registro de cierre/versiones por RF; no convertir afinidad de código en ejecución fechada.
4. Resultados manuales V4, suites de octubre, N130 y gates evalúan poblaciones/tareas distintas. No sumarlos para un porcentaje global de “proyecto completo”.
5. OE-01 tiene respuestas de entrevistas/ficha en Word, pero requiere procedencia e instrumentos ejecutados; encuesta planificada no valida satisfacción/usabilidad.
6. RNF necesitan enlazar procedimientos y mediciones; existencia de middleware/Compose/backup no demuestra porcentajes de seguridad, disponibilidad o portabilidad.

Antes de una modificación del informe, aprobar correspondencias, cerrar decisiones relevantes y registrar faltantes sin ocultarlos. [08](08_INCOHERENCIAS_INFORME.md) identifica ubicación y corrección concreta.
