# MORE — Catálogo consolidado de requerimientos

Fecha de corte: 12/10/2026. **Línea base propuesta para aprobación**, no modificación de RF1–RF10 del Word ni prueba de aceptación ejecutada. Una obligación describe qué verificar; su criterio de aceptación no significa que ya se cumplió. Las funciones objetivo mantienen sus dependencias y decisiones de alcance.

## Convenciones

- NT: indispensable para núcleo transaccional y liberación confiable.
- IA: indispensable para objetivos principales de asistencia/modelos, aunque esté pendiente; no implica promoción.
- CO: complementario, sin ser condición de cumplimiento de todos los objetivos.
- EF: evolución futura o investigación condicionada.
- Dependencias entre requisitos describen flujos y condiciones; relaciones alternativas no se interpretan como necesidad de implementar todos los futuros modelos.
- Códigos EV remiten al registro de evidencias de [04](04_ESTADO_IMPLEMENTACION.md). Estados usan exactamente las categorías solicitadas; la comprobación siempre tiene un alcance declarado.
- Los RF actuales que conservan riesgos muestran esos riesgos; las obligaciones futuras necesitan aprobarse y evaluarse, sin atribuir autoridad comercial al modelo.

Hay 87 RF y 18 RNF. La prioridad se basa en riesgo comercial y relación con OE, datos, viabilidad y evaluación; no en líneas de código.

## Índice de RF

| RF | Módulo | Nombre | Prioridad |
| --- | --- | --- | --- |
| RF-001 | Acceso | Registro de cliente | NT |
| RF-002 | Acceso | Autenticación por estado y rol | NT |
| RF-003 | Acceso | Cierre de sesión | NT |
| RF-004 | Acceso | Recuperación de contraseña | NT |
| RF-005 | Acceso | Perfil y contraseña propios | NT |
| RF-006 | Acceso | Administración de cuentas | NT |
| RF-007 | Acceso | Gestión de roles existentes | NT |
| RF-008 | Acceso | Unificar política de eliminación de cuenta | NT |
| RF-009 | Catálogo | Parámetros comerciales | NT |
| RF-010 | Catálogo | Jerarquía y complementos de categorías | CO |
| RF-011 | Catálogo | Atributos estructurados de prenda | IA |
| RF-012 | Catálogo | Alta de prenda | NT |
| RF-013 | Catálogo | Edición sin reponer stock implícitamente | NT |
| RF-014 | Catálogo | Baja y borrado condicionado | NT |
| RF-015 | Catálogo | Variantes y precio comercial | NT |
| RF-016 | Catálogo | Identificadores y códigos internos | NT |
| RF-017 | Catálogo | Galería manual | NT |
| RF-018 | Catálogo | Importación controlada de catálogo | CO |
| RF-019 | Inventario | Consulta de existencias | NT |
| RF-020 | Inventario | Entrada e inventario inicial | NT |
| RF-021 | Inventario | Ajuste manual con control de concurrencia | NT |
| RF-022 | Inventario | Consulta y exportación Kardex | NT |
| RF-023 | Inventario | Alertas descriptivas | NT |
| RF-024 | Daños | Reporte de daño | NT |
| RF-025 | Daños | Confirmación y descuento | NT |
| RF-026 | Daños | Reparación y baja definitiva | NT |
| RF-027 | Devoluciones | Devolución comercial y reembolso | EF |
| RF-028 | Ventas | Venta presencial | NT |
| RF-029 | Ventas | Checkout online | NT |
| RF-030 | Pagos | Aprobación de compra online | NT |
| RF-031 | Pagos | Rechazo de compra online | NT |
| RF-032 | Ventas | Cancelación de compra en revisión | NT |
| RF-033 | Reservas | Crear reserva y anticipo declarado | NT |
| RF-034 | Reservas | Registrar comprobantes posteriores | NT |
| RF-035 | Reservas | Verificar anticipo o saldo | NT |
| RF-036 | Reservas | Rechazar pago de reserva | NT |
| RF-037 | Reservas | Cancelar reserva sin pagos confirmados | NT |
| RF-038 | Reservas | Vencimiento persistido | NT |
| RF-039 | Reservas | Lista para recojo | NT |
| RF-040 | Reservas | Completar y convertir a venta | NT |
| RF-041 | Logística | Entrega por vendedor | NT |
| RF-042 | Reservas | Regularización de reserva ya recogida | CO |
| RF-043 | Comprobantes | Recibos del sistema | NT |
| RF-044 | Cuenta | Historial propio | NT |
| RF-045 | Tienda | Catálogo y ficha públicos | NT |
| RF-046 | Tienda | Búsqueda y filtros comerciales | NT |
| RF-047 | Tienda | Carrito de compra local | NT |
| RF-048 | Tienda | Bolsa de reservas en sesión | NT |
| RF-049 | Tienda | Favoritos | CO |
| RF-050 | Administración | Dashboard descriptivo | CO |
| RF-051 | Administración | Reportes, CSV e impresión | CO |
| RF-052 | Comunicación | Notificaciones internas | CO |
| RF-053 | Trazabilidad | Logs operativos y acceso | NT |
| RF-054 | Trazabilidad | Auditoría de cambios críticos | NT |
| RF-055 | Continuidad | Respaldo manual de base | NT |
| RF-056 | Continuidad | Recuperación integral | NT |
| RF-057 | Configuración | Separar parámetros de negocio y configuración técnica | CO |
| RF-058 | IA visual | Registro asistido por imagen | IA |
| RF-059 | IA visual | Preprocesamiento y calidad | IA |
| RF-060 | IA visual | Clasificación zero-shot SigLIP | IA |
| RF-061 | IA visual | Atributos visuales sugeridos | IA |
| RF-062 | IA visual | Color descriptivo y bloqueo comercial | IA |
| RF-063 | IA visual | Embedding visual compatible | IA |
| RF-064 | IA visual | Búsqueda por similitud visual | IA |
| RF-065 | Identificación | Matching interno por identidad y candidatos | IA |
| RF-066 | Identificación | OCR de etiqueta | IA |
| RF-067 | Identificación | Lectura de código y QR | IA |
| RF-068 | Gobierno IA | Revisión humana trazable | IA |
| RF-069 | Publicación IA | Preparación autorizada de imágenes | CO |
| RF-070 | IA textual | Embedding textual E5 | IA |
| RF-071 | RAG | Recuperación documental con gate | IA |
| RF-072 | Fashion Ontology | Gobernanza de conceptos y contratos | IA |
| RF-073 | Detección | Detección de instancia objetivo | EF |
| RF-074 | Segmentación | Segmentación semántica del target | EF |
| RF-075 | Recomendación | Complementos por reglas comerciales | CO |
| RF-076 | Recomendación | Compatibilidad cromática de pares | CO |
| RF-077 | Recomendación | Modelo de recomendación de productos | IA |
| RF-078 | Recomendación | Motor completo de outfits | IA |
| RF-079 | Recomendación | Recomendación de talla | EF |
| RF-080 | Probador virtual | Síntesis de apariencia | EF |
| RF-081 | Analítica inteligente | Análisis aprendido de ventas e inventario | EF |
| RF-082 | Tendencias | Trend Intelligence | EF |
| RF-083 | Enriquecimiento | Extracción externa controlada | CO |
| RF-084 | Asistencia textual | Generación de descripciones | EF |
| RF-085 | Asistente | Consultas naturales con herramientas autorizadas | EF |
| RF-086 | Autorización objetivo | Administrador distinto de superadmin | EF |
| RF-087 | Captura inteligente | Escaneo continuo de producto | EF |

## Especificaciones verificables

### RF-001 — Registro de cliente

**Módulo:** Acceso. **Actor:** Visitante. **Prioridad:** NT.

**Obligación:** Crear una cuenta desde el formulario público, con correo único y rol cliente asignado por el servidor.

**Precondiciones:** Rol cliente disponible; datos válidos.

**Resultado esperado:** Cliente activo registrado.

**Aceptación:** Correo duplicado se rechaza; el payload no concede rol vendedor/superadmin.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-33. **Validación disponible/límite:** RegistrationTest.

**Dependencias:** —. **Reglas relacionadas:** RB-001, RB-002.

### RF-002 — Autenticación por estado y rol

**Módulo:** Acceso. **Actor:** Usuario. **Prioridad:** NT.

**Obligación:** Autenticar credenciales y comprobar actividad de cuenta y rol antes de redirigir al panel correspondiente.

**Precondiciones:** Cuenta existente, credenciales correctas y cuenta/rol activos.

**Resultado esperado:** Sesión regenerada y acceso conforme al rol.

**Aceptación:** Credencial inválida, usuario inactivo y rol inexistente/inactivo se rechazan; login aplica límite de intentos.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-33. **Validación disponible/límite:** AuthenticationTest.

**Dependencias:** RF-001. **Reglas relacionadas:** RB-002, RB-003.

### RF-003 — Cierre de sesión

**Módulo:** Acceso. **Actor:** Usuario autenticado. **Prioridad:** NT.

**Obligación:** Cerrar sesión e invalidar sesión y token CSRF.

**Precondiciones:** Sesión válida.

**Resultado esperado:** Sesión cerrada.

**Aceptación:** Tras logout no se accede a rutas protegidas con la sesión anterior.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-02, EV-03, EV-33. **Validación disponible/límite:** AuthenticationTest.

**Dependencias:** RF-002. **Reglas relacionadas:** RB-003.

### RF-004 — Recuperación de contraseña

**Módulo:** Acceso. **Actor:** Usuario. **Prioridad:** NT.

**Obligación:** Solicitar enlace y restablecer contraseña mediante token y validaciones del servidor.

**Precondiciones:** Correo de cuenta; canal de correo configurado para entrega.

**Resultado esperado:** Contraseña sustituida sin revelar la anterior.

**Aceptación:** Token válido permite reset; token inválido no; entrega del correo requiere prueba del canal final.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-02, EV-03, EV-33. **Validación disponible/límite:** PasswordResetTest.

**Dependencias:** RF-001; decisión DP-08. **Reglas relacionadas:** RB-003.

### RF-005 — Perfil y contraseña propios

**Módulo:** Acceso. **Actor:** Usuario autenticado. **Prioridad:** NT.

**Obligación:** Actualizar datos permitidos del perfil y cambiar contraseña comprobando contraseña actual.

**Precondiciones:** Propietario de cuenta y datos válidos.

**Resultado esperado:** Perfil/credencial actualizados.

**Aceptación:** Intentar cambiar contraseña con credencial actual incorrecta falla; no se admite cambio de rol desde perfil.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-33. **Validación disponible/límite:** ProfileTest; PasswordUpdateTest.

**Dependencias:** RF-002. **Reglas relacionadas:** RB-002, RB-003.

### RF-006 — Administración de cuentas

**Módulo:** Acceso. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Crear o editar usuarios, asignar rol válido y dar de baja mediante estado desde el panel autorizado.

**Precondiciones:** Rol activo; email único y datos válidos.

**Resultado esperado:** Cuenta con estado y rol registrados.

**Aceptación:** Baja administrativa conserva usuario; no se elimina desde el CRUD administrativo.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-04, EV-05. **Validación disponible/límite:** V4 CN1.2 / CB1.3; sin revalidación actual completa.

**Dependencias:** RF-002; RF-007. **Reglas relacionadas:** RB-001, RB-002.

### RF-007 — Gestión de roles existentes

**Módulo:** Acceso. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Consultar, crear y editar registros de rol conservando nombres y actividad de los tres roles del sistema.

**Precondiciones:** Acceso administrativo; rol nuevo sin nombre duplicado.

**Resultado esperado:** Registro de rol coherente.

**Aceptación:** Los roles del sistema no se borran ni se renombran; crear otro nombre no concede privilegios de panel.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-04. **Validación disponible/límite:** AuthenticationTest (actividad, no CRUD completo).

**Dependencias:** RF-002. **Reglas relacionadas:** RB-002.

### RF-008 — Unificar política de eliminación de cuenta

**Módulo:** Acceso. **Actor:** Superadministrador / titular; política pendiente. **Prioridad:** NT.

**Obligación:** Resolver la diferencia entre baja administrativa y eliminación física del perfil antes de aceptar borrado de cuentas ligadas a operaciones.

**Precondiciones:** DP-05 aprobada; inventario de relaciones e historial.

**Resultado esperado:** Política única verificable y sin pérdida de trazabilidad.

**Aceptación:** Se prueban cuenta con venta/reserva y última cuenta privilegiada; alternativa elegida queda documentada.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-03, EV-04, EV-07. **Validación disponible/límite:** ProfileTest cubre borrado básico; caso comercial faltante.

**Dependencias:** DP-05; RF-006. **Reglas relacionadas:** RB-004.

### RF-009 — Parámetros comerciales

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Administrar categorías, marcas, tallas, colores y temporadas con validación y protección de registros utilizados.

**Precondiciones:** Datos válidos y permisos.

**Resultado esperado:** Maestros activos consultables por formularios y catálogo.

**Aceptación:** Borrar parámetro en uso se rechaza; valores activos se ofrecen en selección.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-04, EV-05, EV-07, EV-31. **Validación disponible/límite:** V4 CN2.3; lectura de pantallas EV-31.

**Dependencias:** RF-002. **Reglas relacionadas:** RB-005.

### RF-010 — Jerarquía y complementos de categorías

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** CO.

**Obligación:** Registrar padre de categoría y relaciones complementarias dirigidas con prioridad.

**Precondiciones:** Categorías existentes; origen y destino diferentes.

**Resultado esperado:** Relaciones comerciales registradas.

**Aceptación:** Se rechaza auto-complemento; las prioridades y estado condicionan los candidatos de RF-075.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-04, EV-07, EV-35. **Validación disponible/límite:** Migraciones; sin prueba funcional actual de jerarquía.

**Dependencias:** RF-009. **Reglas relacionadas:** RB-005, RB-006.

### RF-011 — Atributos estructurados de prenda

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Asociar patrones, texturas y composición declarada a la prenda, y colores adicionales a la variante.

**Precondiciones:** Catálogos de atributos activos; porcentajes válidos.

**Resultado esperado:** Atributos con IDs comerciales, sin afirmar composición inferida.

**Aceptación:** Material/porcentaje inválido se rechaza; una hipótesis visual no se registra como composición certificada sin revisión.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-05, EV-06, EV-07, EV-25. **Validación disponible/límite:** MatiAiWorkflowTest; validaciones del servidor.

**Dependencias:** RF-009; RF-012. **Reglas relacionadas:** RB-005, RB-007.

### RF-012 — Alta de prenda

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Registrar ficha con nombre, descripción, categoría, marca, temporada, género y estado, variantes e imágenes permitidas.

**Precondiciones:** Maestros válidos; al menos configuración comercial requerida por formulario.

**Resultado esperado:** Prenda con responsable de registro y relaciones.

**Aceptación:** Fallo de validación no deja alta comercial parcial; precio/stock se toman de entradas comerciales validadas.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-04, EV-05, EV-06, EV-07. **Validación disponible/límite:** V4 CN2.1 / CB2.1; no POST comercial en EV-31.

**Dependencias:** RF-009; RF-015. **Reglas relacionadas:** RB-001, RB-005, RB-007.

### RF-013 — Edición sin reponer stock implícitamente

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Editar datos de prenda/variante conservando disponible y reservado de variantes existentes.

**Precondiciones:** Prenda existente; variantes propias; validación.

**Resultado esperado:** Ficha actualizada sin reinicializar existencias.

**Aceptación:** Editar variante agotada con D=0 y R>0 no altera D/R; stock enviado desactualizado no sobreescribe inventario existente.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-05, EV-06, EV-31, EV-33. **Validación disponible/límite:** InventoryP0RegressionTest: 12 pruebas documentadas en EV-31.

**Dependencias:** RF-012. **Reglas relacionadas:** RB-008.

### RF-014 — Baja y borrado condicionado

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Desactivar prenda/variante y permitir borrado únicamente donde las relaciones y reglas actuales lo autorizan.

**Precondiciones:** Registro existente; análisis de vínculos.

**Resultado esperado:** Estado inactivo o borrado permitido con historial protegido.

**Aceptación:** Variante con venta/reserva/Kardex no se retira desde editor; prenda con dependencias no se elimina desde CRUD.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-04, EV-06, EV-07. **Validación disponible/límite:** Inspección deleteBlockedReason/removeProductVariant.

**Dependencias:** RF-012; DP-05. **Reglas relacionadas:** RB-009.

### RF-015 — Variantes y precio comercial

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Crear variantes por prenda con talla, color, precio, costo opcional, SKU/código y estado.

**Precondiciones:** Prenda y valores permitidos; precio válido; cantidad inicial de nueva >=1.

**Resultado esperado:** Variante ligada a una prenda con inventario agregado.

**Aceptación:** ID de variante ajena no permite editarla ni eludir stock mínimo; variante propia agotada puede conservarse.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-05, EV-06, EV-07, EV-31, EV-33. **Validación disponible/límite:** InventoryP0RegressionTest (propiedad/stock).

**Dependencias:** RF-012; RF-009. **Reglas relacionadas:** RB-001, RB-007, RB-008.

### RF-016 — Identificadores y códigos internos

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Mantener SKU único y códigos comerciales conforme a validaciones, preservando ceros iniciales en GTIN cuando aplique.

**Precondiciones:** Variante y formato válido; código no conflictivo.

**Resultado esperado:** Identificador utilizable para consulta y matching.

**Aceptación:** Duplicado de SKU falla; código GTIN no se convierte a número que pierda ceros; IA no inventa identidad.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-05, EV-06, EV-07, EV-30, EV-33. **Validación disponible/límite:** garmentAssistant.test.mjs; test_identification.py.

**Dependencias:** RF-015. **Reglas relacionadas:** RB-001, RB-010.

### RF-017 — Galería manual

**Módulo:** Catálogo. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Cargar, ordenar, asociar y cambiar imagen principal validando archivo y relación prenda/variante.

**Precondiciones:** Archivo permitido y espacio de almacenamiento; registro propio.

**Resultado esperado:** Galería con referencia y principal coherentes.

**Aceptación:** Archivo inválido falla; variante de otra prenda no recibe imagen; edición gestiona archivos creados/eliminados según éxito.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-05, EV-06, EV-07. **Validación disponible/límite:** V4 CN2.5 no terminado histórico; muestra local EV-31, no todas las altas.

**Dependencias:** RF-012. **Reglas relacionadas:** RB-011.

### RF-018 — Importación controlada de catálogo

**Módulo:** Catálogo. **Actor:** Operador técnico autorizado. **Prioridad:** CO.

**Obligación:** Prevalidar manifiesto local autorizado y ejecutar importación idempotente mediante servicios del dominio.

**Precondiciones:** Manifiesto, imágenes, derechos y tallas confirmadas; ejecución posterior autorizada.

**Resultado esperado:** Altas trazables o informe de rechazo.

**Aceptación:** Dry-run no escribe; mismo manifiesto no duplica; ruta fuera de ámbito y derechos prohibidos se rechazan.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-06, EV-33. **Validación disponible/límite:** CatalogRealImportTest.

**Dependencias:** RF-012; RF-017. **Reglas relacionadas:** RB-011.

### RF-019 — Consulta de existencias

**Módulo:** Inventario. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Mostrar D disponible, R reservado, ubicación y total operativo D+R por variante.

**Precondiciones:** Variante con registro de inventario.

**Resultado esperado:** Existencias legibles para operación y consulta.

**Aceptación:** Con D=0/R=2 se muestra reservado, sin vender esas dos unidades como disponibles.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-07, EV-08, EV-14, EV-31. **Validación disponible/límite:** InventoryP0RegressionTest y lectura EV-31.

**Dependencias:** RF-015. **Reglas relacionadas:** RB-012, RB-013.

### RF-020 — Entrada e inventario inicial

**Módulo:** Inventario. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Crear inventario inicial de variante nueva con D=cantidad y R=0 y registrar entrada.

**Precondiciones:** Variante nueva propia; cantidad válida.

**Resultado esperado:** Registro único de inventario y movimiento.

**Aceptación:** Nueva variante inicializa reservado cero; variante existente conserva R y D al editar catálogo.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-06, EV-07, EV-08, EV-31, EV-33. **Validación disponible/límite:** InventoryP0RegressionTest.

**Dependencias:** RF-015. **Reglas relacionadas:** RB-008, RB-014.

### RF-021 — Ajuste manual con control de concurrencia

**Módulo:** Inventario. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Editar disponible y ubicación desde stock usando snapshot original y bloqueo; conservar reservado actual.

**Precondiciones:** Inventario existente y snapshot vigente.

**Resultado esperado:** Ajuste de stock/ubicación auditado o rechazo por conflicto.

**Aceptación:** Un snapshot desactualizado no escribe; vendedor no tiene ruta de ajuste; R no se toma del formulario.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-04, EV-08, EV-31, EV-33. **Validación disponible/límite:** InventoryP0RegressionTest.

**Dependencias:** RF-019. **Reglas relacionadas:** RB-015, RB-016.

### RF-022 — Consulta y exportación Kardex

**Módulo:** Inventario. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Registrar y consultar movimientos con tipo, cantidad, antes/después, actor y referencia de operación.

**Precondiciones:** Movimiento originado por servicio; filtros válidos.

**Resultado esperado:** Historial y CSV de movimientos.

**Aceptación:** Venta/reserva/liberación/conversión muestran deltas correctos y referencia; ubicación puede mover cero unidades.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-08, EV-20. **Validación disponible/límite:** InventoryP0RegressionTest; V4 CB3.2.

**Dependencias:** RF-020; RF-021; RF-028; RF-033. **Reglas relacionadas:** RB-016.

### RF-023 — Alertas descriptivas

**Módulo:** Inventario. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Clasificar disponibilidad: agotado D=0, bajo D<=3 positivo y disponible superior, distinguiendo R.

**Precondiciones:** Registro de inventario.

**Resultado esperado:** Lista de alertas basada en regla existente.

**Aceptación:** D=1 genera bajo; D=4 disponible; umbral actual se informa como código, sin predicción de demanda.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-08, EV-14. **Validación disponible/límite:** V4 CB3.5 observado; sin medición de reposición.

**Dependencias:** RF-019; DP-06. **Reglas relacionadas:** RB-013.

### RF-024 — Reporte de daño

**Módulo:** Daños. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Registrar causa, severidad, prenda/variante, cantidad y evidencias; vendedor informa sin descontar existencias.

**Precondiciones:** Datos válidos y referencia operativa.

**Resultado esperado:** Reporte rastreable.

**Aceptación:** Reporte de vendedor fuerza afecta_inventario=false y no cambia D/R antes de revisión.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-14, EV-15, EV-33. **Validación disponible/límite:** DamagedProductNameTest; inspección servicio.

**Dependencias:** RF-019. **Reglas relacionadas:** RB-017.

### RF-025 — Confirmación y descuento

**Módulo:** Daños. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Confirmar daño y descontar disponible una sola vez cuando superadmin elija afectación de inventario.

**Precondiciones:** Reporte revisable, variante/inventario y D suficiente.

**Resultado esperado:** Reporte confirmado y movimiento cuando corresponde.

**Aceptación:** Confirmación sin afectación no descuenta; con afectación registra deltas y bloquea doble descuento.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-15, EV-08. **Validación disponible/límite:** V4 CB3.4 observado; escenarios completos faltantes.

**Dependencias:** RF-024. **Reglas relacionadas:** RB-017, RB-018.

### RF-026 — Reparación y baja definitiva

**Módulo:** Daños. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Cerrar daño como reparación o pérdida conservando evidencia de si ya se descontó stock.

**Precondiciones:** Reporte en estado permitido y decisión documentada.

**Resultado esperado:** Reparación restituye cuando aplica o pérdida queda cerrada.

**Aceptación:** Baja definitiva de daño ya descontado no resta otra vez; reparación solo devuelve cantidad afectada.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-15, EV-08. **Validación disponible/límite:** Inspección; no aceptación de todos los cierres.

**Dependencias:** RF-025. **Reglas relacionadas:** RB-018.

### RF-027 — Devolución comercial y reembolso

**Módulo:** Devoluciones. **Actor:** Personal autorizado por política pendiente. **Prioridad:** EF.

**Obligación:** Diseñar devolución de una venta entregada, clasificación de artículo, restitución elegible y tratamiento monetario.

**Precondiciones:** DP-04 aprobada; venta comprobable y política vigente.

**Resultado esperado:** Devolución enlazada y resolución comercial/monetaria.

**Aceptación:** Se diferencia devolución vendida de liberación de reserva y reparación; stock y dinero se resuelven con autorización.

**Estado:** PROPUESTA. **Evidencia:** EV-02, EV-15, EV-35. **Validación disponible/límite:** Sin flujo ni prueba equivalente encontrados.

**Dependencias:** DP-04; RF-028; RF-043. **Reglas relacionadas:** RB-019.

### RF-028 — Venta presencial

**Módulo:** Ventas. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Registrar cabecera, detalle y pago confirmado con precio actual y descuento válido; descontar D y conservar R.

**Precondiciones:** Prenda/variante activas, D>=q, cantidad positiva y método válido.

**Resultado esperado:** Venta registrada y entregada con pago.

**Aceptación:** D disminuye q una vez y R queda igual; precio/actividad se revalidan dentro de transacción.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-08, EV-09, EV-31, EV-33. **Validación disponible/límite:** InventoryP0RegressionTest (aritmética); resto sin validación actual completa.

**Dependencias:** RF-015; RF-019; RF-016. **Reglas relacionadas:** RB-020, RB-021, RB-022.

### RF-029 — Checkout online

**Módulo:** Ventas. **Actor:** Cliente. **Prioridad:** NT.

**Obligación:** Recibir carrito de compra de cliente activo, validar comprobante e importe total, revalidar stock/precio y separar existencias.

**Precondiciones:** Cliente activo con rol cliente activo, items válidos; QR/transferencia y comprobante.

**Resultado esperado:** Venta en_revision y pago pendiente_revision; D-=q/R+=q.

**Aceptación:** Staff y cuenta inactiva son rechazados; falta de stock tras agregar no produce venta parcial; valores del navegador no fijan precio.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-10, EV-17, EV-33. **Validación disponible/límite:** CriticalReservationCheckoutP1Test; V4 CN4 general, sin checkout EV-31.

**Dependencias:** RF-047; RF-002; RF-019. **Reglas relacionadas:** RB-012, RB-021, RB-022, RB-023.

### RF-030 — Aprobación de compra online

**Módulo:** Pagos. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Confirmar pago pendiente vinculado a venta en revisión por importe verificado igual al total.

**Precondiciones:** Comprobante candidato actual; venta en_revision; total concordante.

**Resultado esperado:** Pago confirmado, venta registrada; R-=q sin otro descuento D.

**Aceptación:** Importe menor/mayor se rechaza para confirmar; comprobante de otra operación no se utiliza; repetición no duplica consumo.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-09, EV-14, EV-08. **Validación disponible/límite:** V4 CB4.2 observado; inspección confirmOnlineSale.

**Dependencias:** RF-029. **Reglas relacionadas:** RB-023, RB-024.

### RF-031 — Rechazo de compra online

**Módulo:** Pagos. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Rechazar comprobante desde revisión, registrar razón y marcar venta incompleta liberando stock separado.

**Precondiciones:** Pago pendiente y venta en_revision.

**Resultado esperado:** Pago rechazado, venta incompleta/logística cancelada; D+=q/R-=q.

**Aceptación:** La operación no se presenta como devolución de venta entregada ni como cancelada; liberación ocurre una vez.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-09, EV-14, EV-08. **Validación disponible/límite:** Inspección markOnlineSaleIncomplete/reject.

**Dependencias:** RF-029. **Reglas relacionadas:** RB-024, RB-025.

### RF-032 — Cancelación de compra en revisión

**Módulo:** Ventas. **Actor:** Superadministrador; no ruta equivalente vendedor. **Prioridad:** NT.

**Obligación:** Cancelar una venta online todavía en revisión mediante acción administrativa permitida y liberar separación.

**Precondiciones:** Venta en_revision; no registrada ni previamente cancelada.

**Resultado esperado:** Venta cancelada y stock liberado.

**Aceptación:** Venta registrada o incompleta no se cancela por esta acción; sin reembolso bancario implícito.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-02, EV-09, EV-08. **Validación disponible/límite:** Inspección cancelOnlineSale.

**Dependencias:** RF-029. **Reglas relacionadas:** RB-025.

### RF-033 — Crear reserva y anticipo declarado

**Módulo:** Reservas. **Actor:** Cliente / Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Crear reserva con cliente válido, detalles, plazo y primer pago declarado entre 50% y 100%, separando D hacia R.

**Precondiciones:** Cliente y productos activos; D>=q; anticipo válido y comprobante según flujo.

**Resultado esperado:** Reserva por_procesar con pago pendiente, D-=q/R+=q.

**Aceptación:** Anticipo bajo 50% o sobre total falla; pendiente no cuenta como confirmado; duplicados de variante se controlan.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-12, EV-17, EV-31, EV-33. **Validación disponible/límite:** InventoryP0RegressionTest (stock); frontend commercialValidation (no rerun).

**Dependencias:** RF-048 o entrada staff; RF-019. **Reglas relacionadas:** RB-012, RB-026, RB-027.

### RF-034 — Registrar comprobantes posteriores

**Módulo:** Reservas. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Añadir un pago pendiente vinculado a reserva activa sin contarlo como dinero confirmado.

**Precondiciones:** Reserva activa y saldo; comprobante/datos válidos.

**Resultado esperado:** Pago pendiente con monto declarado.

**Aceptación:** Monto pendiente no reduce saldo confirmado; no se concede al cliente una ruta de abono inexistente.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-12, EV-14. **Validación disponible/límite:** Inspección VendedorReservationService; cobertura específica faltante.

**Dependencias:** RF-033. **Reglas relacionadas:** RB-023, RB-027.

### RF-035 — Verificar anticipo o saldo

**Módulo:** Reservas. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Verificar último pago pendiente, exigir primer confirmado >=50% y evitar suma superior al total.

**Precondiciones:** Reserva activa y pago propio pendiente; importe >0.

**Resultado esperado:** Pagado acumulado y saldo recalculados; confirmada y estado de pago correspondiente.

**Aceptación:** Primer abono insuficiente falla; pendiente ajeno falla; se registra responsable y fecha. Restauración a confirmada desde lista_recojo se documenta como riesgo.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-12, EV-14. **Validación disponible/límite:** Inspección verifyPayment; falta matriz multia bono/logística.

**Dependencias:** RF-033; RF-034; DP-03. **Reglas relacionadas:** RB-026, RB-027, RB-028.

### RF-036 — Rechazar pago de reserva

**Módulo:** Reservas. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Rechazar último pago pendiente con observación y liberar stock de reserva activa según comportamiento actual.

**Precondiciones:** Reserva activa y pago pendiente propio; observación no vacía.

**Resultado esperado:** Pago rechazado, reserva rechazada y logística cancelada.

**Aceptación:** No se describe como anulación neutra de un abono: cancela la reserva. Caso con pagos previos exige DP-03 antes de liberación objetivo.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-11, EV-12, EV-14. **Validación disponible/límite:** Inspección rejectLatestPendingPayment; gap monetario.

**Dependencias:** RF-033; DP-03. **Reglas relacionadas:** RB-028, RB-029.

### RF-037 — Cancelar reserva sin pagos confirmados

**Módulo:** Reservas. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Cancelar reserva activa solo cuando suma de pagos confirmados es cero, liberando R hacia D.

**Precondiciones:** Estado por_procesar/confirmada/lista_recojo y sin confirmado.

**Resultado esperado:** Reserva cancelada y existencias liberadas.

**Aceptación:** Pago confirmado impide cambios parciales; segundo cancel no libera dos veces; pendiente no bloquea como confirmado.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-12, EV-33. **Validación disponible/límite:** CriticalReservationCheckoutP1Test (presente, sin nueva ejecución).

**Dependencias:** RF-033. **Reglas relacionadas:** RB-029.

### RF-038 — Vencimiento persistido

**Módulo:** Reservas. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Marcar reserva vencida únicamente al alcanzar plazo de recojo, liberar separación y registrar estado.

**Precondiciones:** Reserva activa y fecha límite <=ahora.

**Resultado esperado:** Reserva/logística vencidas y stock liberado.

**Aceptación:** Plazo futuro no permite vencer; vencimiento visual no escribe. Tratamiento de anticipos confirmados queda pendiente de DP-03.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-11, EV-12, EV-33. **Validación disponible/límite:** CriticalReservationCheckoutP1Test; falta caso monetario.

**Dependencias:** RF-033; DP-03. **Reglas relacionadas:** RB-029, RB-030.

### RF-039 — Lista para recojo

**Módulo:** Reservas. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Marcar reserva confirmada lista para recojo, actualizando estado/logística y aviso.

**Precondiciones:** Reserva confirmada.

**Resultado esperado:** Reserva lista_recojo y notificación operativa.

**Aceptación:** Estado incompatible falla. markReady actual no exige por sí mismo fecha alcanzada; política objetivo necesita DP-03.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-20. **Validación disponible/límite:** Inspección markReady; guard temporal no acreditado.

**Dependencias:** RF-035; DP-03. **Reglas relacionadas:** RB-031.

### RF-040 — Completar y convertir a venta

**Módulo:** Reservas. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Con pago total confirmado, completar reserva y crear venta derivada/detalles una vez consumiendo R.

**Precondiciones:** Confirmada/lista_recojo; saldo cero o pago final exacto válido; stock reservado suficiente.

**Resultado esperado:** Reserva completada/recogida y venta VEN-RES-<nre_res>.

**Aceptación:** R disminuye q y D no se vuelve a descontar; pago final no efectivo exige comprobante; no se duplican venta ni pagos en otra tabla.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-12, EV-13, EV-31, EV-33. **Validación disponible/límite:** InventoryP0RegressionTest (stock); no aceptación integral nueva.

**Dependencias:** RF-033; RF-035. **Reglas relacionadas:** RB-032, RB-033.

### RF-041 — Entrega por vendedor

**Módulo:** Logística. **Actor:** Vendedor / Superadministrador. **Prioridad:** NT.

**Obligación:** Entregar venta registrada pagada o reserva activa totalmente pagada mediante rutas del panel vendedor.

**Precondiciones:** Resumen confirmado pagado al 100%, estado entregable y existencia comprometida de reserva.

**Resultado esperado:** Logística entregada/recogida con responsable y fecha.

**Aceptación:** Reserva con saldo impide entrega; esta ruta no cobra automáticamente pago final; entrega de venta no descuenta D otra vez.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-13, EV-14. **Validación disponible/límite:** V4 CN4.5 observado; inspección VendedorPickupService.

**Dependencias:** RF-030 o RF-035; RF-040. **Reglas relacionadas:** RB-031, RB-032.

### RF-042 — Regularización de reserva ya recogida

**Módulo:** Reservas. **Actor:** Superadministrador. **Prioridad:** CO.

**Obligación:** Resolver reserva histórica recogida/completada con saldo, exigir pago final exacto y vincular venta sin consumir inventario nuevamente.

**Precondiciones:** Estado completada/recogida; anomalía y saldo; política DP-03.

**Resultado esperado:** Reserva regularizada y vínculo comercial auditable.

**Aceptación:** No se usa este correctivo como flujo normal; una venta existente no se recrea; pago y stock no se duplican.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-13. **Validación disponible/límite:** Inspección settleCompletedReservation.

**Dependencias:** RF-040; DP-03. **Reglas relacionadas:** RB-033.

### RF-043 — Recibos del sistema

**Módulo:** Comprobantes. **Actor:** Cliente propietario / Superadministrador. **Prioridad:** NT.

**Obligación:** Emitir consulta imprimible de reserva/venta en estado permitido con pagos y origen correctos.

**Precondiciones:** Operación propia o acceso superadmin; condición de emisión satisfecha.

**Resultado esperado:** Recibo interno legible.

**Aceptación:** Cliente ajeno recibe rechazo; venta derivada utiliza pagos de reserva; no se declara factura fiscal.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-18, EV-13. **Validación disponible/límite:** V4 CB4 general; ReceiptController/ReceiptService.

**Dependencias:** RF-028 o RF-033. **Reglas relacionadas:** RB-034.

### RF-044 — Historial propio

**Módulo:** Cuenta. **Actor:** Cliente. **Prioridad:** NT.

**Obligación:** Consultar pedidos, reservas, estados y referencias del usuario autenticado.

**Precondiciones:** Cuenta autenticada.

**Resultado esperado:** Historial limitado al propietario.

**Aceptación:** Cambiar ID ajeno no expone recibo; pedidos/reservas se filtran por cliente actual.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-18, EV-02. **Validación disponible/límite:** Inspección CuentaController; aceptación por todos los roles pendiente.

**Dependencias:** RF-002; RF-029 o RF-033. **Reglas relacionadas:** RB-034.

### RF-045 — Catálogo y ficha públicos

**Módulo:** Tienda. **Actor:** Visitante / Cliente. **Prioridad:** NT.

**Obligación:** Mostrar prendas activas, ficha, imágenes, variantes y disponibilidad antes de seleccionar producto.

**Precondiciones:** Producto publicado/activo; fuentes comerciales.

**Resultado esperado:** Catálogo y ficha consultables sin sesión.

**Aceptación:** Fichas y listados muestreados cargan; condición de disponibilidad se diferencia de stock garantizado al checkout.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-16, EV-31. **Validación disponible/límite:** Lectura real EV-31; no todos los productos.

**Dependencias:** RF-012; RF-017; RF-019. **Reglas relacionadas:** RB-035.

### RF-046 — Búsqueda y filtros comerciales

**Módulo:** Tienda. **Actor:** Visitante / Cliente. **Prioridad:** NT.

**Obligación:** Buscar y filtrar por campos soportados de categoría, marca, talla, color, temporada y disponibilidad, respetando variante coincidente.

**Precondiciones:** Filtros válidos y catálogo.

**Resultado esperado:** Listado paginado con facetas y orden.

**Aceptación:** Caso POLERA documentado devuelve lista filtrada; talla/color deben coincidir en misma variante disponible; batería combinatoria pendiente.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-16, EV-31. **Validación disponible/límite:** EV-31 filtro concreto; V4 CN5.2 observado histórico.

**Dependencias:** RF-045. **Reglas relacionadas:** RB-035.

### RF-047 — Carrito de compra local

**Módulo:** Tienda. **Actor:** Visitante / Cliente. **Prioridad:** NT.

**Obligación:** Agregar, actualizar o quitar variantes en carrito local del navegador sin reservar existencias.

**Precondiciones:** Variante seleccionada y cantidad válida.

**Resultado esperado:** Lista local usada para checkout.

**Aceptación:** Cantidad decimal/negativa/superior al stock mostrado falla en UI; modificar localStorage no cambia autoridad de RF-029.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-17, EV-10, EV-33. **Validación disponible/límite:** purchaseCart.test.mjs.

**Dependencias:** RF-045; RF-029. **Reglas relacionadas:** RB-036.

### RF-048 — Bolsa de reservas en sesión

**Módulo:** Tienda. **Actor:** Visitante / Cliente. **Prioridad:** NT.

**Obligación:** Mantener variantes y cantidades en sesión web, y vaciarla tras reserva confirmada por servidor.

**Precondiciones:** Producto/variante activos; cantidad válida.

**Resultado esperado:** Bolsa de reserva conservada hasta flujo autenticado.

**Aceptación:** Agregar no cambia D/R; confirmar revalida actividad y stock; guest conserva bolsa e intención tras login.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-17, EV-11, EV-33. **Validación disponible/límite:** AuthenticationTest; CriticalReservationCheckoutP1Test.

**Dependencias:** RF-045; RF-033; RF-002. **Reglas relacionadas:** RB-036.

### RF-049 — Favoritos

**Módulo:** Tienda. **Actor:** Cliente; ruta exige auth. **Prioridad:** CO.

**Obligación:** Alternar y consultar favoritos del usuario autenticado asociados a prendas.

**Precondiciones:** Sesión y prenda válida.

**Resultado esperado:** Favorito propio guardado o retirado.

**Aceptación:** Repetición alterna estado sin duplicado por usuario/prenda; no crea una recomendación aprendida.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-18, EV-07. **Validación disponible/límite:** V4 CB5.4 observado; sin nueva aceptación.

**Dependencias:** RF-002; RF-045. **Reglas relacionadas:** RB-037.

### RF-050 — Dashboard descriptivo

**Módulo:** Administración. **Actor:** Vendedor / Superadministrador. **Prioridad:** CO.

**Obligación:** Mostrar agregaciones operativas de inventario, ventas, reservas y accesos según panel.

**Precondiciones:** Datos comerciales disponibles y permisos de panel.

**Resultado esperado:** Resumen descriptivo con unidades y período.

**Aceptación:** No se presenta gráfico que mezcla cantidades/recuentos como validación contable; no se denomina pronóstico.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-19, EV-14, EV-31. **Validación disponible/límite:** Lectura EV-31; defecto de gráfico documentado.

**Dependencias:** RF-019; RF-028; RF-033; DP-07. **Reglas relacionadas:** RB-038.

### RF-051 — Reportes, CSV e impresión

**Módulo:** Administración. **Actor:** Vendedor / Superadministrador. **Prioridad:** CO.

**Obligación:** Consultar reportes autorizados con filtros y emitir CSV o vista imprimible usando la misma base operativa.

**Precondiciones:** Tipo/filtros válidos; rol habilitado.

**Resultado esperado:** Reporte con período, definiciones y exportación.

**Aceptación:** Ventas, reservas, pagos e inventario no duplican importes derivados; CSV e impresión se contrastarán con listado.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-19, EV-14. **Validación disponible/límite:** V4 CN6.2 / CB6.2 observado; lectura EV-31.

**Dependencias:** RF-028; RF-033; RF-019. **Reglas relacionadas:** RB-038.

### RF-052 — Notificaciones internas

**Módulo:** Comunicación. **Actor:** Cliente / Vendedor / Superadministrador. **Prioridad:** CO.

**Obligación:** Crear y consultar notificaciones operativas, contarlas y marcar propias como leídas.

**Precondiciones:** Evento comercial y destinatario; sesión para lectura.

**Resultado esperado:** Aviso interno asociado a operación.

**Aceptación:** Solo destinatario marca su aviso; envío de email/SMS no se deduce de notificación database.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-20, EV-02. **Validación disponible/límite:** Inspección NotificationController/CommercialNotificationService.

**Dependencias:** RF-028; RF-033; DP-08. **Reglas relacionadas:** RB-039.

### RF-053 — Logs operativos y acceso

**Módulo:** Trazabilidad. **Actor:** Sistema / Superadministrador. **Prioridad:** NT.

**Obligación:** Registrar eventos/errores y consultar bitácora técnica sin exponer contraseñas o tokens.

**Precondiciones:** Evento trazable y política de sanitización.

**Resultado esperado:** Evento con módulo, actor y contexto permitido.

**Aceptación:** Intento de acceso fallido tiene evento; secretos no se incorporan al contexto público; retención pendiente DP-09.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-20, EV-22. **Validación disponible/límite:** V4 CB6.3; MatiAiClientTest (fallback).

**Dependencias:** RF-002; DP-09. **Reglas relacionadas:** RB-040.

### RF-054 — Auditoría de cambios críticos

**Módulo:** Trazabilidad. **Actor:** Sistema / Superadministrador. **Prioridad:** NT.

**Obligación:** Conservar evidencia de creación/cambio/borrado de modelos observados y consulta de detalle autorizado.

**Precondiciones:** Acción mediante modelos observados.

**Resultado esperado:** Registro de cambio vinculado a actor y objeto.

**Aceptación:** Se distingue observer Eloquent de SQL directo; no se afirma protección frente a manipulación privilegiada.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-20, EV-07. **Validación disponible/límite:** V4 CB6.4; inspección AppServiceProvider.

**Dependencias:** RF-006; RF-012; RF-028; RF-033. **Reglas relacionadas:** RB-040.

### RF-055 — Respaldo manual de base

**Módulo:** Continuidad. **Actor:** Superadministrador. **Prioridad:** NT.

**Obligación:** Generar dump PostgreSQL, registrar éxito/fallo y descargarlo desde ruta administrativa controlada.

**Precondiciones:** pg_dump y credenciales/configuración disponibles; almacenamiento permitido.

**Resultado esperado:** Dump e historial o fallo registrado.

**Aceptación:** Archivo fuera de ruta protegida no se descarga; dump no se presenta como respaldo automático de fotos/pesos.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-21, EV-02. **Validación disponible/límite:** V4 CB6.5 no terminado; sin restauración comprobada.

**Dependencias:** RF-002; DP-10. **Reglas relacionadas:** RB-041.

### RF-056 — Recuperación integral

**Módulo:** Continuidad. **Actor:** Operador técnico autorizado. **Prioridad:** NT.

**Obligación:** Definir y validar restauración de DB, imágenes y configuración necesaria en entorno aislado, con objetivos acordados.

**Precondiciones:** DP-10 aprobada; copias independientes; procedimiento revisado.

**Resultado esperado:** Servicio y recursos recuperables con evidencia.

**Aceptación:** Ensayo verifica relaciones, archivos y operación; RPO/RTO se acuerdan y miden, no se inventan.

**Estado:** PROPUESTA. **Evidencia:** EV-21, EV-36. **Validación disponible/límite:** No ensayo integral acreditado.

**Dependencias:** RF-055; DP-10. **Reglas relacionadas:** RB-041.

### RF-057 — Separar parámetros de negocio y configuración técnica

**Módulo:** Configuración. **Actor:** Superadministrador / operador técnico. **Prioridad:** CO.

**Obligación:** Documentar qué se configura por maestros y qué requiere despliegue, sin declarar una pantalla global inexistente.

**Precondiciones:** Catálogos y configuración versionada.

**Resultado esperado:** Parámetros explícitos y responsables.

**Aceptación:** Umbral de stock, TTL IA y canales no se prometen editables desde panel si están en código/configuración.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-04, EV-08, EV-22, EV-36. **Validación disponible/límite:** Inspección configuración; sin panel global de parámetros.

**Dependencias:** RF-009; DP-06; DP-08. **Reglas relacionadas:** RB-042.

### RF-058 — Registro asistido por imagen

**Módulo:** IA visual. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Analizar fotografía subida o imagen guardada propia y devolver hipótesis para un borrador de prenda sin alta comercial automática.

**Precondiciones:** Sesión autorizada, imagen válida, catálogos activos; servicio disponible o fallback.

**Resultado esperado:** Run y sugerencias revisables o estado degradado.

**Aceptación:** Analizar no cambia precio/SKU/stock ni guarda prenda; fallo mantiene registro manual.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-22, EV-23, EV-25, EV-31, EV-32, EV-33. **Validación disponible/límite:** MatiAiWorkflowTest y E2E P1 documentado.

**Dependencias:** RF-012; RF-059; RF-068. **Reglas relacionadas:** RB-043, RB-044.

### RF-059 — Preprocesamiento y calidad

**Módulo:** IA visual. **Actor:** Superadministrador / servicio IA. **Prioridad:** IA.

**Obligación:** Validar formato/tamaño/píxeles, orientar EXIF y transformar entrada manteniendo hash/original y diagnóstico de calidad.

**Precondiciones:** Imagen admitida dentro de límites.

**Resultado esperado:** Entrada normalizada, metadatos y advertencias.

**Aceptación:** Archivo inválido/animado o excesivo se rechaza según contrato; original no se sustituye; heurística de calidad no certifica prenda.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-22, EV-23, EV-31, EV-32, EV-33. **Validación disponible/límite:** test_vision.py; pruebas de input P1 documentadas.

**Dependencias:** RF-017. **Reglas relacionadas:** RB-011, RB-043.

### RF-060 — Clasificación zero-shot SigLIP

**Módulo:** IA visual. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Comparar imagen con prompts de categorías comerciales y entregar Top-3, margen y abstención.

**Precondiciones:** Modelo y revisión compatibles, taxonomía proporcionada por Laravel.

**Resultado esperado:** Candidatos de categoría con coseno no calibrado.

**Aceptación:** Bajo score o margen activa abstención; ranking abstencionista no muestra Aplicar; aceptación comercial es humana.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-23, EV-25, EV-31, EV-32, EV-34. **Validación disponible/límite:** N130 documentado + EV-31 inferencia con abstención, sin generalización.

**Dependencias:** RF-059; RF-068. **Reglas relacionadas:** RB-043, RB-044.

### RF-061 — Atributos visuales sugeridos

**Módulo:** IA visual. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Sugerir patrones u otros atributos efectivamente soportados conservando abstención y origen; no deducir toda la ontología.

**Precondiciones:** Entrada válida, catálogo y evidencia aplicable.

**Resultado esperado:** Hipótesis de atributos revisables.

**Aceptación:** Patrón abstencionista se mantiene informativo; no se declara modelo entrenado de manga/material/fit por existir definición ontológica.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-23, EV-25, EV-28, EV-33. **Validación disponible/límite:** MatiAiP1Test; falta GT independiente por atributo.

**Dependencias:** RF-058; RF-068; DP-12. **Reglas relacionadas:** RB-043, RB-044, RB-047.

### RF-062 — Color descriptivo y bloqueo comercial

**Módulo:** IA visual. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Extraer paleta observada y propiedades cromáticas manteniendo bloqueada asignación de color comercial sin identidad de target.

**Precondiciones:** Imagen válida y catálogo de colores vigente.

**Resultado esperado:** Paleta técnica o abstención; color comercial no autorizado cuando target sin verificar.

**Aceptación:** Geometría foreground no habilita ID; insuficiencia conserva diagnóstico y entrada manual; no se afirma medición física del tejido.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-23, EV-25, EV-29, EV-31, EV-33. **Validación disponible/límite:** ColorimetryCatalogGuardTest; EV-31 abstención.

**Dependencias:** RF-059; RF-068. **Reglas relacionadas:** RB-045.

### RF-063 — Embedding visual compatible

**Módulo:** IA visual. **Actor:** Servicio IA / Superadministrador. **Prioridad:** IA.

**Obligación:** Obtener y conservar vector visual finito L2 con modelo, revisión, pipeline, dimensiones y preprocesamiento exactos.

**Precondiciones:** Pesos locales compatibles y entrada válida.

**Resultado esperado:** Vector trazable y apto para espacio visual compatible.

**Aceptación:** Igual dimensión con otro perfil no permite mezcla; vector no finito/nulo o identidad incompatible se rechaza y los válidos se preparan en L2.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-23, EV-24, EV-31, EV-32, EV-33. **Validación disponible/límite:** EV-31 vector 768; auditoría pgvector histórica.

**Dependencias:** RF-059. **Reglas relacionadas:** RB-046.

### RF-064 — Búsqueda por similitud visual

**Módulo:** IA visual. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Consultar imágenes internas por distancia coseno exacta de embeddings de mismo espacio y entregar candidatos con procedencia.

**Precondiciones:** Vector de consulta y galería compatibles; índices canónicos vigentes.

**Resultado esperado:** Ranking de similares con identificadores internos.

**Aceptación:** Se excluye mezcla de perfiles; similar no se presenta como identidad exacta ni outfit; ausencia de vector conduce a información insuficiente.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-24, EV-34, EV-33. **Validación disponible/límite:** Auditoría pgvector; no consulta real nueva EV-31.

**Dependencias:** RF-063; RF-016. **Reglas relacionadas:** RB-046, RB-048.

### RF-065 — Matching interno por identidad y candidatos

**Módulo:** Identificación. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Buscar primero identificadores exactos de catálogo y luego candidatos limitados por señales suaves, conservando conflicto y evidencia.

**Precondiciones:** Código/texto/imagen y catálogo válido; identificadores normalizados.

**Resultado esperado:** Coincidencia exacta sustentada o candidatos.

**Aceptación:** SKU/GTIN exacto tiene trato distinto de coseno; conflicto de identificador no se compensa con parecido; no inventar producto nuevo.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-24, EV-30, EV-25, EV-32, EV-33. **Validación disponible/límite:** test_identification.py; MatiAiWorkflowTest exact lookup.

**Dependencias:** RF-016; RF-067 o RF-066 o RF-063. **Reglas relacionadas:** RB-010, RB-048.

### RF-066 — OCR de etiqueta

**Módulo:** Identificación. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Leer texto/regiones con EasyOCR y normalizar datos permitidos conservando texto original y advertencias.

**Precondiciones:** Etiqueta legible, OCR habilitado y pesos locales; alternativa manual disponible.

**Resultado esperado:** Texto reconocido y propuestas no certificadas.

**Aceptación:** OCR unavailable devuelve partial/fallback; composición o marca no se confirman por texto ambiguo; CER/WER requiere GT posterior.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-30, EV-22, EV-32, EV-33. **Validación disponible/límite:** E2E label/fallback P1 documentado, no calidad científica.

**Dependencias:** RF-059; RF-068. **Reglas relacionadas:** RB-044, RB-048.

### RF-067 — Lectura de código y QR

**Módulo:** Identificación. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Decodificar simbologías soportadas y devolver valor/formato/región para consulta de identidad.

**Precondiciones:** Imagen de código dentro de límites.

**Resultado esperado:** Códigos leídos o ausencia explícita.

**Aceptación:** EAN/UPC valida checksum y ceros; QR arbitrario no se convierte en identidad comercial ni autorización de URL.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-30, EV-22, EV-32, EV-33. **Validación disponible/límite:** E2E EAN13/QR P1 documentado; fotos variadas pendientes.

**Dependencias:** RF-059; RF-065. **Reglas relacionadas:** RB-010, RB-048.

### RF-068 — Revisión humana trazable

**Módulo:** Gobierno IA. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Registrar aceptación/descarte/edición de sugerencias ligadas a run, propietario y variante y conservar valor realmente guardado.

**Precondiciones:** Run propio y sugerencia vigente/aplicable; prenda propia al guardar.

**Resultado esperado:** Decisión separada de inferencia y dato comercial.

**Aceptación:** Otra cuenta no acepta run ajeno; categoría abstencionista no se acepta; precio/stock quedan fuera del auto-aplicar.

**Estado:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-25, EV-32, EV-33. **Validación disponible/límite:** MatiAiWorkflowTest; garmentAssistant.test.mjs; E2E P1 limitado.

**Dependencias:** RF-058; RF-012. **Reglas relacionadas:** RB-043, RB-044, RB-049.

### RF-069 — Preparación autorizada de imágenes

**Módulo:** Publicación IA. **Actor:** Superadministrador. **Prioridad:** CO.

**Obligación:** Preparar original y derivados privados y asociarlos a producto solo con aprobación, derechos, propietario y token vigente de un uso.

**Precondiciones:** Fuente permitida, derechos/evidencia explícitos, imagen válida.

**Resultado esperado:** Medios preparados privados y adjuntos tras guardar autorizado.

**Aceptación:** Derechos unknown/prohibited no permiten publicar; token ajeno/caducado/usado falla; vista de referencia no es imagen publicada.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-25, EV-30, EV-33. **Validación disponible/límite:** MatiAiWorkflowTest publication; pipeline tests.

**Dependencias:** RF-017; RF-068; DP-11. **Reglas relacionadas:** RB-011, RB-049.

### RF-070 — Embedding textual E5

**Módulo:** IA textual. **Actor:** Servicio IA / operador técnico. **Prioridad:** IA.

**Obligación:** Codificar query/passage mediante perfil E5 CPU aprobado, con prefijos y hashes de pesos/identidad.

**Precondiciones:** Perfil aprobado de embedding y pesos cacheados íntegros; corpus permitido.

**Resultado esperado:** Vector textual L2 de espacio separado del visual.

**Aceptación:** 384 dimensiones E5 no mezcla con SigLIP; changed weights falla; aprobación de embedding no desbloquea gate RAG.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-26, EV-31, EV-33. **Validación disponible/límite:** test_rag_text.py; perfil aprobado, no nueva inferencia.

**Dependencias:** DP-13. **Reglas relacionadas:** RB-046, RB-050.

### RF-071 — Recuperación documental con gate

**Módulo:** RAG. **Actor:** Superadministrador. **Prioridad:** IA.

**Obligación:** Mantener recuperación autenticada cerrada mientras manifiesto/promoción no estén aprobados; futuras respuestas citan fuentes.

**Precondiciones:** Aprobación de gate y contexto exacto para servir chunks; hoy no satisfecha.

**Resultado esperado:** Actualmente insufficient_evidence, sin chunks ni respuesta generada.

**Aceptación:** Flags/prompt del request no habilitan retrieval; precio/stock dinámico requieren herramientas autorizadas, no texto del corpus.

**Estado:** EXPERIMENTAL. **Evidencia:** EV-26, EV-27, EV-33. **Validación disponible/límite:** RagGateV2AccessTest; NO_PROMOTION fechado.

**Dependencias:** RF-070; DP-13; DP-19 para herramientas futuras. **Reglas relacionadas:** RB-050.

### RF-072 — Gobernanza de conceptos y contratos

**Módulo:** Fashion Ontology. **Actor:** Mati como revisor comercial / responsable técnico por designar. **Prioridad:** IA.

**Obligación:** Consolidar conceptos/IDs/estados de conocimiento, mapping y Product Intelligence por ámbitos antes de integrarlos al catálogo.

**Precondiciones:** Definiciones, ejemplos, fuentes y decisiones por ámbito.

**Resultado esperado:** Ámbitos revisados y versionados sin migración comercial automática.

**Aceptación:** DRAFT_FOR_REVIEW no se trata como aprobado; política aprobada no aprueba dataset ni modelo; fases de gobierno independientes.

**Estado:** DISEÑADA. **Evidencia:** EV-28, EV-35. **Validación disponible/límite:** Validadores ontológicos presentes; aceptación humana faltante.

**Dependencias:** DP-12; RF-011. **Reglas relacionadas:** RB-047, RB-049.

### RF-073 — Detección de instancia objetivo

**Módulo:** Detección. **Actor:** Investigador / revisor humano. **Prioridad:** EF.

**Obligación:** Investigar detección de prendas y seleccionar target sin convertir confianza del detector en identidad comercial.

**Precondiciones:** Dataset anotado por instancia, protocolo y licencias aprobados.

**Resultado esperado:** Candidatas espaciales con ambigüedad explícita.

**Aceptación:** Múltiples targets/cero target se abstienen; bounding box no acredita SKU ni color comercial; no endpoint productivo actual.

**Estado:** EXPERIMENTAL. **Evidencia:** EV-29, EV-28. **Validación disponible/límite:** Experimentos DINO/SAM documentados; sin GT de instancia independiente.

**Dependencias:** RF-072; DP-14. **Reglas relacionadas:** RB-045, RB-047.

### RF-074 — Segmentación semántica del target

**Módulo:** Segmentación. **Actor:** Investigador / revisor humano. **Prioridad:** EF.

**Obligación:** Evaluar máscaras de instancia y consumo de crop/masked solo bajo nuevo protocolo y gate por consumidor.

**Precondiciones:** Target anotado/seleccionado con evidencia; GT de máscara independiente; licencias.

**Resultado esperado:** Máscaras y evaluación experimental; ninguna integración habilitada hoy.

**Aceptación:** NONE se conserva; scores y geometría no son IoU/Dice medidos; perfil crop no mezcla con galería FULL.

**Estado:** EXPERIMENTAL. **Evidencia:** EV-29. **Validación disponible/límite:** 84 pruebas experimentales documentadas; gate sin ganador.

**Dependencias:** RF-073; DP-14; RF-063. **Reglas relacionadas:** RB-045, RB-046, RB-047.

### RF-075 — Complementos por reglas comerciales

**Módulo:** Recomendación. **Actor:** Visitante / Cliente. **Prioridad:** CO.

**Obligación:** Ordenar prendas complementarias a producto usando relación de categorías, temporada, familia de color y disponible.

**Precondiciones:** Producto fuente activo, relaciones complementarias y variantes disponibles.

**Resultado esperado:** Candidatos explicables de complemento.

**Aceptación:** Sin relación/stock no se inventan candidatos; no se denomina aprendizaje automático personalizado ni outfit completo.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-35, EV-16, EV-07. **Validación disponible/límite:** Inspección ComplementaryProductService; baseline pendiente.

**Dependencias:** RF-010; RF-045; RF-019. **Reglas relacionadas:** RB-035, RB-038, RB-048.

### RF-076 — Compatibilidad cromática de pares

**Módulo:** Recomendación. **Actor:** Superadministrador / consumidor técnico. **Prioridad:** CO.

**Obligación:** Evaluar armonías de dos perfiles cromáticos con reglas explícitas y score heurístico.

**Precondiciones:** Perfiles válidos y compatibles; color suficiente.

**Resultado esperado:** Compatibilidad/explanación o insufficient_evidence.

**Aceptación:** Score 0–100 no es probabilidad de preferencia; par compatible no acredita outfit ni talla ni compra.

**Estado:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-22, EV-35. **Validación disponible/límite:** Motor y contratos presentes; evaluación humana local faltante.

**Dependencias:** RF-062. **Reglas relacionadas:** RB-045, RB-048.

### RF-077 — Modelo de recomendación de productos

**Módulo:** Recomendación. **Actor:** Cliente / responsable de modelo. **Prioridad:** IA.

**Obligación:** Construir y evaluar ranking basado en datos y preferencias/contexto frente a baseline de reglas, con disponibilidad real Laravel.

**Precondiciones:** DP-02/DP-15 aprobadas, datos suficientes y protocolo sin fuga.

**Resultado esperado:** Ranking evaluado con evidencia y explicación.

**Aceptación:** Separación temporal/deduplicación; comparar contra RF-075; usuarios nuevos tienen fallback; publicación requiere aceptación.

**Estado:** DISEÑADA. **Evidencia:** EV-35, EV-28, EV-01. **Validación disponible/límite:** No modelo aprendido/productivo ni aceptación acreditados.

**Dependencias:** RF-075; RF-072; RF-019; DP-15. **Reglas relacionadas:** RB-047, RB-048, RB-050.

### RF-078 — Motor completo de outfits

**Módulo:** Recomendación. **Actor:** Cliente. **Prioridad:** IA.

**Obligación:** Generar combinaciones contextualizadas de productos/variantes disponibles considerando zonas/capas, estilo y preferencias.

**Precondiciones:** DP-02/DP-16 aprobadas; ontología y producto fiables; evaluación humana.

**Resultado esperado:** Outfits con componentes individuales, evidencia y alternativas.

**Aceptación:** No confundir conjunto comercial con outfit; compatibilidad de par no valida todos los componentes; no reservar/poner precio automáticamente.

**Estado:** DISEÑADA. **Evidencia:** EV-28, EV-35, EV-01. **Validación disponible/límite:** Solo contratos/diseño; evaluación de outfits completa ausente.

**Dependencias:** RF-077; RF-072; RF-076; DP-16. **Reglas relacionadas:** RB-047, RB-048, RB-050.

### RF-079 — Recomendación de talla

**Módulo:** Recomendación. **Actor:** Cliente. **Prioridad:** EF.

**Obligación:** Proponer talla bajo protocolo con medidas de prenda/marca/corte y datos voluntarios, conservando incertidumbre.

**Precondiciones:** DP-17 aprobada; tablas dimensionales, privacidad y GT de ajuste.

**Resultado esperado:** Sugerencia de talla explicada o abstención.

**Aceptación:** Filtro talla no cuenta como recomendador; apariencia de talla no garantiza ajuste; no exigir biometría para compra.

**Estado:** DISEÑADA. **Evidencia:** EV-01, EV-35. **Validación disponible/límite:** Sin sistema/validación de ajuste encontrado.

**Dependencias:** RF-015; DP-17. **Reglas relacionadas:** RB-047, RB-050.

### RF-080 — Síntesis de apariencia

**Módulo:** Probador virtual. **Actor:** Cliente voluntario / investigador. **Prioridad:** EF.

**Obligación:** Explorar representación visual de prenda sobre persona solo bajo alcance, consentimiento y licencias aprobados.

**Precondiciones:** DP-17 aprobada; modelo/dataset/hardware y política de imágenes personales.

**Resultado esperado:** Imagen sintética identificada como tal, si experimento autorizado.

**Aceptación:** No se demuestra talla/caída real; borrar/conservar foto sigue política aprobada; no servicio actual encontrado.

**Estado:** DISEÑADA. **Evidencia:** EV-01, EV-35. **Validación disponible/límite:** Diseño/documentación; sin inferencia productiva.

**Dependencias:** RF-072; DP-17. **Reglas relacionadas:** RB-047, RB-050.

### RF-081 — Análisis aprendido de ventas e inventario

**Módulo:** Analítica inteligente. **Actor:** Superadministrador / responsable de modelo. **Prioridad:** EF.

**Obligación:** Evaluar modelos de ranking/demanda/rotación solo con variable objetivo, horizonte y datos internos suficientes.

**Precondiciones:** DP-15/DP-18 aprobadas, historial limpio y definición de venta efectiva.

**Resultado esperado:** Análisis validado contra baseline temporal, con limitaciones.

**Aceptación:** No se etiqueta dashboard SQL como predicción; se controlan ventas derivadas, anulaciones y fuga temporal.

**Estado:** DISEÑADA. **Evidencia:** EV-19, EV-35, EV-01. **Validación disponible/límite:** Sin modelo entrenado de demanda/ventas en uso.

**Dependencias:** RF-051; RF-077; DP-18. **Reglas relacionadas:** RB-038, RB-050.

### RF-082 — Trend Intelligence

**Módulo:** Tendencias. **Actor:** Superadministrador / investigador. **Prioridad:** EF.

**Obligación:** Investigar señales externas fechadas por ámbito geográfico y contexto antes de usarlas en recomendaciones.

**Precondiciones:** Fuentes/derechos aprobados y definiciones de tendencia/novedad.

**Resultado esperado:** Observaciones trazables y evaluación contextual.

**Aceptación:** Frecuencia externa no prueba demanda en El Alto; no scraping indiscriminado; separar alias de concepto nuevo.

**Estado:** DISEÑADA. **Evidencia:** EV-28, EV-35, EV-01. **Validación disponible/límite:** Documentación de Trend Intelligence; sin operación acreditada.

**Dependencias:** RF-083; DP-11; DP-18. **Reglas relacionadas:** RB-047, RB-050.

### RF-083 — Extracción externa controlada

**Módulo:** Enriquecimiento. **Actor:** Superadministrador. **Prioridad:** CO.

**Obligación:** Consultar proveedores/adaptadores autorizados, devolver candidatos y derechos sin sobrescribir catálogo confirmado.

**Precondiciones:** Fuentes explícitamente configuradas y autorizadas; URL segura; coincidencia sustentada.

**Resultado esperado:** Referencias externas o no_sources explícito.

**Aceptación:** Hoy fuentes vacías; no se afirma extracción real automática. URL privada/redirección indebida/derecho prohibido se rechaza.

**Estado:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-30, EV-31, EV-32, EV-33. **Validación disponible/límite:** test_enrichment.py; EV-31 fuentes false.

**Dependencias:** RF-065; RF-068; DP-11. **Reglas relacionadas:** RB-011, RB-048, RB-049.

### RF-084 — Generación de descripciones

**Módulo:** Asistencia textual. **Actor:** Superadministrador. **Prioridad:** EF.

**Obligación:** Proponer texto usando solo atributos/evidencias confirmadas, marcando inferidos y revisión antes de publicar.

**Precondiciones:** DP-19 aprobada; fuentes y modelo autorizados; revisión.

**Resultado esperado:** Borrador trazable, sin composición/precio/stock inventados.

**Aceptación:** Caso de atributos desconocidos no rellena datos falsos; texto generado conserva fuentes y decisión humana.

**Estado:** DISEÑADA. **Evidencia:** EV-28, EV-35. **Validación disponible/límite:** No generación LLM productiva encontrada.

**Dependencias:** RF-068; RF-072; DP-19. **Reglas relacionadas:** RB-043, RB-049, RB-050.

### RF-085 — Consultas naturales con herramientas autorizadas

**Módulo:** Asistente. **Actor:** Superadministrador; cliente limitado por política pendiente. **Prioridad:** EF.

**Obligación:** Diseñar consulta de datos actuales mediante intents/herramientas permitidas, aclarando ambigüedad y controlando resultados por rol.

**Precondiciones:** DP-19 aprobada; tools de solo lectura y permisos específicos.

**Resultado esperado:** Respuesta o aclaración/abstención con evidencia.

**Aceptación:** Router que pide tool no ejecuta SQL/LLM; query no puede ampliar permiso ni realizar pago/reserva; semántica y datos actuales separados.

**Estado:** DISEÑADA. **Evidencia:** EV-26, EV-27, EV-28, EV-35. **Validación disponible/límite:** RagGateV2Test cubre routing, no asistente completo.

**Dependencias:** RF-051; RF-071; DP-19. **Reglas relacionadas:** RB-050.

### RF-086 — Administrador distinto de superadmin

**Módulo:** Autorización objetivo. **Actor:** Administrador propuesto. **Prioridad:** EF.

**Obligación:** Definir, si se aprueba, un rol administrador con conjunto explícito de privilegios separado de superadministrador.

**Precondiciones:** DP-01 aprobada; matriz de privilegios y compatibilidad de usuarios actuales.

**Resultado esperado:** Rol diferenciado con reglas y pruebas propias, solo después de implementación.

**Aceptación:** Crear fila ADMINISTRADOR no concede permisos; cada ruta/objeto tendrá pruebas positivas y negativas antes de conceder funciones.

**Estado:** PROPUESTA. **Evidencia:** EV-02, EV-03, EV-01. **Validación disponible/límite:** Sin rol funcional administrativo distinto acreditado.

**Dependencias:** DP-01; RF-007. **Reglas relacionadas:** RB-002.

### RF-087 — Escaneo continuo de producto

**Módulo:** Captura inteligente. **Actor:** Superadministrador / operador por definir. **Prioridad:** EF.

**Obligación:** Diseñar flujo continuo cámara/fotogramas con target, acumulación de evidencia e invalidación por nueva imagen.

**Precondiciones:** DP-20 aprobada; privacidad, contrato y evaluación independientes.

**Resultado esperado:** Captura revisable y matching contextual, sin compromiso comercial automático.

**Aceptación:** Una subida de foto no cuenta como Lens continuo; eventos de selección no son identidad validada ni anotación humana aprobada.

**Estado:** DISEÑADA. **Evidencia:** EV-28, EV-35. **Validación disponible/límite:** Contratos target-selection presentes; UI E2E continua ausente.

**Dependencias:** RF-065; RF-073; DP-20. **Reglas relacionadas:** RB-043, RB-047, RB-049.

## Requerimientos no funcionales

Los valores temporales, disponibilidad, usuarios concurrentes, tamaños de dataset y umbrales de aceptación futuros no se fijan sin acuerdo/medición. Las cifras históricas o límites de configuración se citan con origen; no se convierten en compromisos productivos.

### RNF-01 — Seguridad de acceso y archivos

**Prioridad:** NT. **Requisito:** Autorizar rutas y archivos privados por rol/propietario; validar CSRF y credenciales; no exponer tokens.

**Criterio verificable:** Matriz positiva/negativa por ruta y descarga; casos de intento ajeno deben impedir acceso. Aislamiento de vouchers pendiente DP-09.

**Estado y límite:** Controles presentes; no evaluación completa ni HTTPS productivo confirmado. **Evidencia:** EV-03, EV-02, EV-18, EV-22.

### RNF-02 — Separación de privilegios

**Prioridad:** NT. **Requisito:** Aplicar matriz real y comprobar actividad de cuenta/rol en los puntos exigidos; nuevos roles no reciben autoridad por nombre.

**Criterio verificable:** Login inactivo, staff en checkout y cliente en panel se rechazan; administrador separado necesita aprobación y casos propios.

**Estado y límite:** Pruebas existentes; matriz completa y sesiones de todos los roles no verificadas ahora. **Evidencia:** EV-02, EV-03, EV-10, EV-33.

### RNF-03 — Disponibilidad y continuidad

**Prioridad:** NT. **Requisito:** Definir horario operativo, interrupciones aceptables, recuperación y dependencia de IA sin interrumpir registro manual.

**Criterio verificable:** Ventana, porcentaje objetivo y forma de medir quedan PENDIENTES DE APROBACIÓN/MEDICIÓN. Fallo IA tiene fallback manual; no SLA numérico deducido de health.

**Estado y límite:** Disponibilidad local documentada en fecha específica; no serie temporal productiva. **Evidencia:** EV-21, EV-22, EV-31, EV-36.

### RNF-04 — Rendimiento

**Prioridad:** NT/IA. **Requisito:** Medir tiempos por flujo, carga y condición fría/caliente, separando DB, transporte y modelo.

**Criterio verificable:** Informe reproducible de percentiles/error/carga y hardware. Objetivos numéricos PENDIENTES. Timeouts cliente 30s/3s son configuración, no promesa de respuesta.

**Estado y límite:** 5154,034ms de inferencia local del 11/10 incluye carga; no latencia universal ni checkout. **Evidencia:** EV-22, EV-29, EV-31.

### RNF-05 — Confiabilidad transaccional

**Prioridad:** NT. **Requisito:** Evitar ventas/reservas parciales, consumo doble y operaciones repetidas en entradas cubiertas.

**Criterio verificable:** Validación fallida revierte cabecera/detalles/stock/intención; repetir UUID equivalente no duplica. PostgreSQL multiproceso necesita evidencia vinculada a versión candidata.

**Estado y límite:** Transacciones/test presente; no toda concurrencia comprobada; 60% de Word no es probabilidad de fallo. **Evidencia:** EV-08, EV-17, EV-33.

### RNF-06 — Mantenibilidad

**Prioridad:** CO. **Requisito:** Separar responsabilidades, versionar contratos/dependencias y conservar regresiones por comportamiento crítico.

**Criterio verificable:** Cambio de inventario/contrato tiene impacto y regresión identificados; locks y configuración permiten reproducir dependencias. Esfuerzo/porcentaje global PENDIENTE.

**Estado y límite:** Servicios y tests existen; no demuestra 64% de mantenibilidad. **Evidencia:** EV-04, EV-22, EV-33, EV-36.

### RNF-07 — Portabilidad

**Prioridad:** CO. **Requisito:** Reproducir entorno soportado y declarar componentes GPU opcionales y recursos fuera de Git.

**Criterio verificable:** Lista de versiones/configuración y arranque en entorno limpio con evidencia antes de liberación. Hardware objetivo pendiente; no ejecutar prueba nueva en esta tarea.

**Estado y límite:** Compose y guía local presentes; no arranque frío/servidor final certificado. **Evidencia:** EV-31, EV-36.

### RNF-08 — Usabilidad y respuesta de interfaz

**Prioridad:** NT/IA. **Requisito:** Hacer comprensibles estado, saldo, errores, carga y abstención con lenguaje consistente para actores.

**Criterio verificable:** Evaluación por tareas con usuarios reales, instrumentos, muestras y resultados; aceptación/umbrales PENDIENTES. No presentar análisis 'partial' como fracaso de toda gestión.

**Estado y límite:** Muestra de UI documentada; modo claro tiene observaciones; porcentajes V4 sin instrumento aportado. **Evidencia:** EV-05, EV-14, EV-25, EV-31.

### RNF-09 — Compatibilidad y responsividad

**Prioridad:** CO. **Requisito:** Operar en navegadores/dispositivos acordados y conservar flujos y validaciones de cantidad.

**Criterio verificable:** Matriz de navegador/versión/ancho/tarea con resultados. Conjunto de dispositivos y cobertura PENDIENTES DE APROBACIÓN; no deducir Firefox/Edge de Chrome.

**Estado y límite:** Build TypeScript/Vite reportado y pantallas de escritorio muestreadas; no cobertura universal. **Evidencia:** EV-05, EV-16, EV-17, EV-31.

### RNF-10 — Accesibilidad

**Prioridad:** CO. **Requisito:** Permitir tareas esenciales por teclado, etiquetas, foco, lectura de errores y contraste; distinguir acceso web de accesibilidad.

**Criterio verificable:** Checklist y evaluación asistiva sobre flujos acordados; estándar/nivel y umbrales PENDIENTES DE APROBACIÓN. aria-label presentes no acreditan conformidad global.

**Estado y límite:** Elementos accesibles visibles en código; auditoría formal faltante. **Evidencia:** EV-05, EV-17, EV-25, EV-31.

### RNF-11 — Respaldo y recuperación

**Prioridad:** NT. **Requisito:** Respaldar conjunto definido de DB/medios/configuración y comprobar restore separado de generación de dump.

**Criterio verificable:** Archivo verificable y ensayo aislado con reconciliación DB/medios. RPO, RTO, periodicidad y retención PENDIENTES DE ACUERDO/ENSAYO; DP-10.

**Estado y límite:** Backup DB manual implementado; recuperación integral propuesta. **Evidencia:** EV-21, EV-36.

### RNF-12 — Privacidad y minimización

**Prioridad:** NT/IA. **Requisito:** Acotar información de cuenta, vouchers, logs, OCR y posibles fotos personales a finalidad y acceso aprobados.

**Criterio verificable:** Inventario de datos, responsables, retención y borrado/anonimización; canal de comprobantes privado propuesto. Plazos PENDIENTES; no usar borrador normativo como ley vigente.

**Estado y límite:** Sanitización parcial; almacenamiento public de vouchers y baja física requieren decisión. No dictamen legal. **Evidencia:** EV-03, EV-18, EV-20, EV-25, EV-30.

### RNF-13 — Integridad de datos

**Prioridad:** NT. **Requisito:** Mantener referencias, unicidad, precios/saldos válidos y D/R no negativos en operaciones; identidad vectorial consistente.

**Criterio verificable:** Escenarios q>D, variante ajena, snapshot viejo, pago sobre total y vector inválido se rechazan. CHECK de stock no negativo no está en migración inventarios; considerar refuerzo posteriormente.

**Estado y límite:** Invariantes en servicios y tests; no lectura de DB viva ni garantía SQL universal. **Evidencia:** EV-05, EV-07, EV-08, EV-12, EV-24, EV-33.

### RNF-14 — Observabilidad

**Prioridad:** NT/IA. **Requisito:** Correlacionar operación/run/error con identificador, versión, duración y estado, sin secretos ni datos personales innecesarios.

**Criterio verificable:** Timeout/error conserva código seguro y UUID; logs/auditoría permitidos se consultan por actor habilitado. Retención, alertas y monitoreo operativo PENDIENTES.

**Estado y límite:** Eventos y metadata presentes; no monitor de incidentes ni inmutabilidad acreditados. **Evidencia:** EV-20, EV-22, EV-25, EV-31.

### RNF-15 — Calidad de IA por tarea

**Prioridad:** IA. **Requisito:** Evaluar clasificación, retrieval, identidad, OCR, recomendación y máscara con protocolo, GT y métricas pertinentes.

**Criterio verificable:** Dataset congelado y separado por propósito; soporte por clase, abstención/cobertura/error; umbrales de promoción PENDIENTES salvo gates históricos existentes. No confundir coseno/score SAM con probabilidad.

**Estado y límite:** N130 local no holdout independiente; OCR sin CER/WER acreditado; NONE/NO_PROMOTION preservados. **Evidencia:** EV-23, EV-28, EV-29, EV-32, EV-34.

### RNF-16 — Trazabilidad y control de IA

**Prioridad:** IA. **Requisito:** Conservar modelo/revisión/hash/pipeline/entrada/run, predicción y decisión humana separados del valor comercial.

**Criterio verificable:** Dato aplicado puede rastrearse a evidencia y actor; perfiles incompatibles rechazados; experimentos no activados por request. Ontología por ámbitos conserva aprobaciones independientes.

**Estado y límite:** Persistencia y guards presentes; autorización productiva amplia no obtenida. **Evidencia:** EV-24, EV-25, EV-26, EV-27, EV-28.

### RNF-17 — Aislamiento de recursos IA

**Prioridad:** IA. **Requisito:** Acotar payload, carga y ejecución, con pesos locales y sin descargas durante request.

**Criterio verificable:** Límites efectivos y endpoint coinciden con contrato; timeout conserva recursos hasta job real según implementación textual. Concurrencia/modelos coexistentes requieren medición y objetivos posteriores.

**Estado y límite:** Laravel imagen 6144KiB, FastAPI archivo 10MiB/24M píxeles por config; distintos límites se documentan, no SLA. **Evidencia:** EV-22, EV-23, EV-26, EV-29, EV-36.

### RNF-18 — Procedencia de imágenes y fuentes

**Prioridad:** IA/CO. **Requisito:** Exigir evidencia de origen/derechos y política de acceso al extraer/publicar medios externos.

**Criterio verificable:** Sin fuente configurada no se simula resultado externo; derechos desconocidos no permiten adjuntar imagen; URL se valida contra política y redirecciones.

**Estado y límite:** Adaptadores/controles presentes, proveedores productivos no configurados. **Evidencia:** EV-25, EV-30, EV-31.

## Correspondencia con catálogo de V4

La siguiente descomposición propuesta conserva RF originales por su nombre; no es renumeración silenciosa.

| V4 (Tabla 6, B1821) | Descomposición principal | Vacío relevante |
| --- | --- | --- |
| RF1 Usuarios y roles | RF-001–008; RF-086 solo propuesto | No describe permisos por operación, recuperación/retención ni admin distinto. |
| RF2 Prendas | RF-012–014 | Baja versus eliminación condicionada y preservación de stock necesitan criterio. |
| RF3 Parámetros | RF-009–011 | Atributos estructurados y complementos posteriores requieren alcance explícito. |
| RF4 Variantes | RF-015–016 | Identidad, SKU, precio y ejemplar físico deben diferenciarse. |
| RF5 Imágenes | RF-017–018; RF-069 en publicación asistida | Derechos, ownership y aprobación de medios preparados no constan como RF separados. |
| RF6 Inventario | RF-019–026 | No define D/R, ajuste concurrente, Kardex, daños o devoluciones. |
| RF7 Catálogo | RF-045 | Diferenciar público/autenticado y vistas de agotados. |
| RF8 Búsqueda/filtros | RF-046 | Filtro talla/color sobre misma variante y revalidación posterior. |
| RF9 Ventas/reservas | RF-028–044; RF-047–048 | Pago pendiente, rechazo, cancelación, vencimiento, logística y conversión mezclados. |
| RF10 Reportes | RF-050–051 | Logs/auditoría/backup/notificaciones y analítica ML no son reportes genéricos. |

Los RF-049 y RF-052–057 tienen implementación/diseño y respaldo en HU o ET de V4, pero carecen de RF explícito suficientemente separado en Tabla 6. Los RF-058–085 y RF-087 no están definidos como catálogo de obligaciones verificables en esa tabla aunque título, objetivos y ET-27–30 los mencionen. Esas relaciones deben aprobarse y completar HU/aceptación, como señala [06](06_TRAZABILIDAD.md).

RNF1 de V4 trata acceso desde navegadores; corresponde principalmente a compatibilidad/portabilidad, no demuestra accesibilidad asistiva. RNF2–8 necesitan completar seguridad medible, integridad, disponibilidad, rendimiento, usabilidad y mantenibilidad con procedimiento/evidencia. RNF-10/11/12/14/15/16 aquí explicitan accesibilidad, recuperación, privacidad, observabilidad y calidad/trazabilidad IA que no quedan satisfechas por frases generales.
