# MORE — Definición integral del sistema

Fecha de corte: 12 de octubre de 2026. Especificación de trabajo basada en el repositorio y en **SotoCazuLimberPII2V4.docx**. Los requisitos y planes consolidados son una propuesta revisable; describir una función existente no aprueba una política comercial nueva ni acredita una liberación productiva.

## Identidad, problema y aporte

MORE es un sistema web para Mati Boutique, una tienda multimarca de El Alto, que centraliza fichas de prendas, variantes e imágenes, existencias disponibles/reservadas, ventas, reservas, revisión manual de pagos, recojos y consulta del cliente. Incorpora asistencia visual privada: analiza imágenes, propone categorías/atributos, produce embeddings y mantiene revisión humana y abstención. Laravel conserva la autoridad comercial; Python aporta inferencias.

La problemática de V4 (§3.1, bloques B1635–B1646) es la dispersión del registro en anotaciones y canales no estructurados: datos incompletos, actualización tardía, disponibilidad incierta, seguimiento difícil de ventas/reservas y catálogo poco organizado. Los problemas de talla y orientación de compra también están declarados, pero no deben darse por resueltos por el catálogo o un filtro.

El aporte de ingeniería acreditable es integrar gestión transaccional, semántica de stock, control de permisos y contratos de inferencia con evidencia, revisión humana y continuidad manual. No se acreditó una mejora causal de ventas, ahorro de tiempo, modelo propio entrenado ni novedad científica frente al estado del arte. Esa demostración requiere protocolos y datos, no solamente arquitectura o volumen de código.

## Objetivos funcionales y relación académica

Los cinco objetivos específicos de V4 (§3.6.2, B1693–B1697) se conservan; aquí se denominan OE-01 a OE-05 por orden de aparición, sin alterar Word:

| Objetivo | Resultado que exige | Situación actual |
| --- | --- | --- |
| OE-01: caracterizar procesos, variables y limitaciones operativas | Diagnóstico sustentado en instrumentos, actores y procesos reales | Entrevistas con respuestas y ficha de observación en anexos 1/2/4 de V4; faltan vínculo de fecha/participantes/evidencia primaria e informe de encuesta aplicada. Código no demuestra caracterización de población. |
| OE-02: desarrollar modelo de registro y clasificación visual | Inferencia evaluada por tarea, atributos y revisión | SigLIP preentrenado integrado y evaluado localmente; registro asistido existente. No atribuir entrenamiento propio ni cobertura de todos los atributos. |
| OE-03: construir sistema de inventario y visualización | Operaciones, stock y catálogo coherentes | Base implementada, pruebas selectivas documentadas y controles presentes; aceptación integral pendiente. |
| OE-04: implementar análisis basado en aprendizaje para recomendar | Modelo aprendido y comparación con baseline | No acreditado integralmente; complementos de reglas y diseños no satisfacen ese verbo. |
| OE-05: integrar recomendación de prendas y outfits | Motor contextual integrado y evaluación de combinaciones | Complementos y compatibilidad de pares son apoyos limitados; motor completo diseñado, sin aceptación. |

El objetivo general añade análisis mediante aprendizaje automático. Por eso una versión comercial con dashboard SQL y clasificación no basta para declarar satisfechos OE-04/05. La decisión DP-02 debe elegir entre completar y evaluar esos objetivos o aprobar una delimitación académica diferente. El título tampoco autoriza guardar datos comerciales sin revisión.

## Actores reales y permisos

Visitante es un actor sin sesión, no una fila de rol. Cliente es la cuenta destinada a compra/reserva online. Vendedor opera atención comercial, consulta existencias, revisa comprobantes, entrega y reporta daños. Superadministrador administra maestros, cuentas, stock, controles y asistencia IA, además de operación comercial.

**Administrador independiente no está implementado como rol funcional distinto.** V4 usa ese nombre para tareas de administración; el código reconoce superadmin, vendedor y cliente. Crear una fila denominada administrador no le concede rutas administrativas. DP-01 decide la interpretación documental o una ampliación posterior. La matriz no le asigna permisos imaginarios.

| Operación | Visitante | Cliente | Vendedor | Superadministrador | Administrador distinto |
| --- | --- | --- | --- | --- | --- |
| Catálogo, ficha y filtros públicos | Sí | Sí | Sí, público | Sí, público | Sin rol implementado; acceso público como visitante |
| Carrito de compra / bolsa de reserva, sin confirmar | Sí | Sí | Interfaz pública disponible; no checkout cliente | Igual | No definido |
| Registro público | Sí, crea cliente | Cuenta existente | No alta de staff por registro público | Alta de staff por panel | No definido |
| Perfil, contraseña y notificaciones propias | Requiere sesión | Propias | Propias | Propias | No definido |
| Favoritos y cuenta | Requiere sesión | Propios | Rutas auth generales; no permiso para datos ajenos | Rutas auth generales; cuenta propia | No definido |
| Checkout / confirmación de bolsa pública | No | Sí, activo y rol cliente activo | Rechazado por guard de checkout | Rechazado por guard de checkout | No definido |
| CRUD de prenda, maestros, variantes e imágenes | No | No | No; consulta operativa | Sí, según recurso/validación | No definido |
| Consultar existencias y alertas internas | No | Disponibilidad pública | Sí | Sí | No definido |
| Ajustar stock/ubicación directamente | No | No | No | Sí, con snapshot/bloqueo | No definido |
| Registrar venta/reserva staff | No | Solo flujo público propio | Sí | Sí | No definido |
| Verificar/rechazar comprobantes | No | No | Sí, candidatos permitidos | Sí | No definido |
| Cancelar reserva sin pagos confirmados | No | No ruta pública propia encontrada | Sí | Sí | No definido |
| Vencer reserva / marcar lista | No | No | No ruta específica encontrada | Sí, reglas de servicio | No definido |
| Cancelar venta online en revisión | No | No | Rechazo disponible; cancelación distinta no encontrada | Sí | No definido |
| Entrega comercial | No | Consulta de estado | Sí; reserva debe estar totalmente pagada | Sí; flujo específico puede cobrar saldo de reserva | No definido |
| Reportar daño | No | No | Sí, sin descuento | Sí | No definido |
| Confirmar/reparar/dar baja daño | No | No | No | Sí | No definido |
| Recibo propio / recibo administrativo | No | Propio y estado elegible | Sin ruta administrativa equivalente de recibo | Sí | No definido |
| Reportes internos | No | Historial propio | Operativos del panel | Administrativos autorizados | No definido |
| Usuarios/roles, logs, auditoría, backup | No | No | No | Sí | No definido |
| Asistente visual y ruta de RAG | No | No | No | Sí; gate RAG sigue bloqueado | No definido |

La autorización está en middleware y controles de objeto/servicio. No hay un subsistema acreditado de permisos granulares asignables por rol. Tampoco se verificó ahora la matriz completa mediante sesiones reales. Véase EV-02/03/14 en [04_ESTADO_IMPLEMENTACION.md](04_ESTADO_IMPLEMENTACION.md).

## Módulos y operaciones

| Módulo | Operaciones actuales relevantes | Dependencia / límite |
| --- | --- | --- |
| Acceso y usuarios | Registro cliente, login/logout, reset, perfil, CRUD administrativo y estado | Reset necesita canal de correo; política de eliminación divergente. |
| Catálogo interno | Maestros, atributos, prenda, variante, SKU/precio, imágenes y estados | Borrado condicionado; dato comercial validado por servidor. |
| Inventario | Existencias, entrada inicial, ajuste de stock/ubicación, Kardex, alertas | Pantalla inventario.ajustes no operativa; no compras/proveedores ni reposición automática. |
| Daños | Reporte, revisión, confirmación, reparación, pérdida y resolución | No equivale a devolución comercial. |
| Ventas | Venta directa, checkout, revisión, rechazo/incompleta, cancelación en revisión | Pagos manualmente revisados, sin gateway bancario. |
| Reservas | Anticipo, abonos, revisión, cancelación, vencimiento, recojo y conversión | Política de dinero ante rechazo/vencimiento pendiente. |
| Tienda y cuenta | Catálogo/filtros, dos selecciones de carrito, favoritos, pedidos/reservas/recibos | Lectura pública frente a operación autenticada y titularidad. |
| Operación del vendedor | Consulta y operación comercial autorizada | No edición del catálogo ni ajuste directo. |
| Administración | Dashboard, reportes/CSV/impresión, avisos, logs, auditoría y dumps | Descriptivo; recuperación completa y producción no acreditadas. |
| IA asistiva | Preprocesamiento, SigLIP, embeddings, similares, código/QR, OCR, feedback y publicación controlada | Madurez distinta por tarea; color comercial bloqueado sin identidad. |
| Investigación y evolución | Ontología, RAG, detección/segmentación, recomendación/outfits/tallas/tendencias/try-on/LLM | No promovidos automáticamente; estados individuales en documento 04. |

## Información de prenda y autoridad

Producto/prenda es la ficha: nombre, descripción, categoría, marca, temporada, género, estado, imágenes y atributos. Variante es una alternativa comercial ligada a esa ficha, con talla/color principal y paleta adicional, SKU/código, precio/costo opcional, estado e inventario. Una unidad física es un ejemplar contado en existencias; no se encontró identidad serial por cada unidad.

Superadmin introduce y confirma identificación, precio, costo, cantidades, ubicación, talla y relaciones comerciales. Puede aceptar propuestas de categoría/atributos cuando sean aplicables o editar manualmente el borrador. OCR/códigos aportan evidencia de etiqueta/identidad, sin garantizar correspondencia física. La paleta técnica puede describir píxeles; actualmente no confirma el ID de color comercial. Composición textil, talla adecuada y autenticidad de marca no se certifican desde apariencia.

La edición de una variante existente conserva stock, incluso agotada. Variante nueva requiere stock inicial mínimo uno según código actual. Baja lógica por estado y eliminación física condicionada son operaciones distintas. No se concede eliminación indiscriminada de variantes con historia.

## Inventario: significado e invariantes

D = cdi_inv, cantidad disponible; R = cre_inv, cantidad reservada/comprometida. InventoryStatusService presenta total operativo D+R. El stock reservado incluye reserva y separación de venta online en revisión.

D+R **no es un conteo físico certificado**. Mercancía dañada apartada, bienes ya pagados pendientes de entrega y diferencias de conteo requieren conciliación; no hay una tercera columna de cantidad física ni serialización individual demostradas. La confirmación online consume R al aprobar el pago, antes de la entrega posterior, por lo que el sistema no debe prometer que D+R cuenta toda mercancía aún físicamente en tienda.

| Evento válido de q unidades | Cambio D | Cambio R | Efecto operativo |
| --- | --- | --- | --- |
| Alta de inventario nuevo | +q inicial | 0 inicial | Entrada, no compra a proveedor |
| Venta presencial | -q | 0 | Venta registrada/entregada |
| Reserva | -q | +q | Separación hasta resolución |
| Checkout en revisión | -q | +q | Separación para revisar voucher |
| Aprobar venta online | 0 | -q | Consumo comercial; entrega separada |
| Completar reserva | 0 | -q | Conversión, sin segundo descuento D |
| Liberar reserva/venta separada | +q | -q | Retorno de separación, no devolución vendida |
| Ajustar disponible | Diferencia autorizada | 0 | Snapshot vigente y Kardex |
| Confirmar daño con afectación | -q | 0 | Una vez, con D suficiente |
| Reparar daño antes afectado | +q cuando aplica | 0 | Restitución autorizada |
| Pérdida definitiva ya descontada | 0 | 0 | No doble descuento |
| Carrito / favorito / inferencia | 0 | 0 | No comprometen mercancía |
| Devolución de venta entregada | Sin política implementada | Sin política implementada | DP-04; no inferir deltas |

En cada flujo se validan cantidades enteras positivas, actividad, D>=q o R>=q para su consumo/liberación; se bloquean filas relevantes y se registran referencias y valores antes/después. Las transacciones e intención única protegen altas comerciales cubiertas. No equivalen a una prueba de toda concurrencia o una restricción SQL universal: la migración inventarios declara enteros sin CHECK no negativo. El reservado agregado tampoco prueba por sí solo su atribución correcta a cada operación; conciliar detalles activos con movimientos forma parte de la aceptación pendiente.

## Procesos comerciales detallados

Los nombres siguientes reflejan estados del código; estado comercial, estado de pago y logística son dimensiones independientes. No convertir anticipo pendiente en pago ni venta registrada en entrega automática de todos los orígenes.

### Selección y carrito de compra

Actor: visitante/cliente. Precondición: ficha y variante seleccionadas. Agrega o modifica cantidades en localStorage; puede quitar/vaciar. No cambia existencias. Cantidades inválidas y exceso sobre stock mostrado se rechazan en UI, pero el contenido del navegador sigue siendo no confiable. Resultado: selección preliminar para RF-029. Comprar exige cliente autenticado activo; vendedor/superadmin usan su flujo staff. El carrito local no está sincronizado en DB ni asegura disponibilidad futura.

### Bolsa de reserva

Actor: visitante/cliente. Precondición: variante/producto activos y cantidad válida. Las rutas web api/carrito conservan lista en sesión carrito. Actualizan/eliminan/vacían sin separar existencias. Guest pasa por login para confirmar, preservando intención/bolsa. Confirmación crea reserva y, tras éxito, vacía selección. Excepciones: inactividad posterior, stock cambiado o anticipo inválido; revalidación del servidor impide escritura parcial. No confundir esta bolsa con el carrito de compra local.

### Venta presencial

Actor: vendedor/superadmin por panel habilitado. Precondiciones: variante/producto activos, precio y descuentos válidos, D suficiente, método e importe aceptables. Dentro de transacción registra venta/detalles, pago confirmado y movimiento D-=q. R queda igual. Resultado: registrada y entregada en el flujo directo. Puede existir cliente opcional según estructura de venta; no imponer cuenta online a comprador presencial por suposición. Error de validación/stock revierte operación. La prueba aislada documentada acredita aritmética, no todos los medios ni la recepción bancaria.

### Compra por internet

Actor: cliente activo con rol cliente activo. Precondiciones: carrito válido, método QR/transferencia, monto declarado igual al total validado y voucher permitido. Servidor bloquea/revalida actividad, precio y D; crea venta en_revision y pago pendiente_revision, con D-=q/R+=q. Alternativa ante agotamiento/cambio de precio: rechazo controlado para revisar selección/pago, no venta parcialmente confirmada. UUID de intención impide alta duplicada equivalente. Resultado: pedido pendiente de revisión, sin conciliación bancaria ni pago confirmado.

### Verificación de compra online

Actor: vendedor/superadmin. Precondiciones: venta en_revision y pago candidato propio pendiente. Revisor examina comprobante; confirmación exige monto verificado=total. Servicio marca pago confirmado, venta registrada y consume R, conservando D. Entrega posterior es independiente. Vendedor aprueba el declarado del voucher mediante su ruta; no se debe afirmar que introduce libremente un monto reconciliado. Pago incorrecto/ya resuelto/importe discordante bloquea confirmación. La persona sigue siendo responsable de comprobar que ocurrió el pago.

### Rechazo y cancelación de compra

Rechazo: revisor autorizado sobre pago pendiente y venta en revisión; anota razón, marca incompleta/pago rechazado/logística cancelada y libera D+=q/R-=q. Cancelación: acción superadmin distinta, válida para en_revision; marca cancelada y libera separación. Venta registrada no se anula por esa acción. Repetición o estado incompatible falla antes de un segundo movimiento. Ninguna de las dos realiza un reembolso bancario de venta entregada.

### Crear reserva

Actor: cliente por bolsa, o vendedor/superadmin por entrada staff. Precondiciones: cliente válido, variantes activas, D>=q, anticipo inicial declarado 50–100% positivo y comprobante según validación. Crea cabecera/detalles, pago pendiente y plazo; separa D-=q/R+=q. Estado inicial por_procesar. Declaración no constituye confirmado ni saldo pagado. Stock o precio cambiante se revalida en transacción; inválido revierte. Resultado: reserva pendiente, visible en historial.

### Abono y validación de reserva

Actor: vendedor/superadmin por rutas permitidas; no se encontró ruta pública de abono del cliente. Registra pendiente sin reducir saldo confirmado. Revisor valida último pendiente propio: confirmado positivo; primero al menos 50%; acumulado no supera total. Suma confirmados, recalcula pagado/saldo y estado de pago. Código fija est_res=confirmada, incluso si antes estaba lista_recojo; ese retroceso necesita revisar su coordinación logística. No agregar a venta_pagos el mismo anticipo cuando se convierta la reserva.

### Rechazo de pago de reserva

Actor: revisor autorizado. Precondiciones: último pendiente propio, reserva activa y observación. El servicio rechaza pago, fija reserva rechazada, libera R hacia D y logística cancelada. No es solo retirar un comprobante erróneo manteniendo reserva. Caso con abonos previos confirmados no utiliza la prohibición de cancelación monetaria; por ello se clasifica parcial y se requiere DP-03. No se ha aprobado perder/devolver un anticipo ni se implementó ese arreglo en esta tarea.

### Cancelación y vencimiento de reserva

Cancelar: vendedor/superadmin; reserva por_procesar/confirmada/lista_recojo, sin pagos confirmados. Se bloquea cabecera y pagos; suma confirmados>0 impide cambios. Si válido libera separación y marca cancelada. Vencer: superadmin; activa y fecha límite alcanzada, con validación de reloj del negocio. Libera y marca vencida. El vencimiento visual no escribe y no se acreditó un cron automático equivalente. markExpired no aplica el mismo guard monetario de cancelación. No asumir devolución/retención del anticipo; DP-03 es bloqueante de política.

### Preparación y entrega de reserva

Marcar lista: superadmin, estado confirmada; método markReady no exige fecha alcanzada por sí mismo. Entregar: vendedor exige pago total previo, estado entregable y reservado suficiente; invoca complete sin pago final. Superadmin tiene flujo que admite completar saldo exacto al recojo, con método permitido y comprobante para no efectivo. complete revalida bajo bloqueo; con saldo cero crea venta derivada/detalles, consume R sin restar D y marca completada/recogida con actor/fecha. Saldo, stock o estado incompatibles bloquean resultado.

### Regularización histórica

Actor: superadmin. settleCompletedReservation trata registros ya completados/recogidos con saldo o venta faltante; exige pago final exacto cuando haya saldo y evita consumir stock otra vez. Es correctivo, no flujo normal para entregar sin pagar. La venta se resuelve por número determinista VEN-RES-<nre_res>; no hay FK reserva_id. Recibo derivado consulta pagos de reserva. La política para anomalías previas requiere evidencia y DP-03.

### Entrega de venta y comprobación documental

Vendedor/superadmin entrega venta registrada pagada en estado logístico permitido; deja responsable/fecha sin descontar inventario nuevamente. Cliente consulta historial propio y recibo cuando condición de emisión lo permite; superadmin puede consultar recibo administrativo. ID ajeno se rechaza por titularidad. Voucher subido, pago confirmado y recibo interno son objetos distintos. El recibo no acredita facturación fiscal, conciliación ni reembolso.

### Daño, restitución y devoluciones

Vendedor reporta daño con evidencia sin descuento. Superadmin revisa/confirma, decide si afecta D y registra movimiento; reparación puede devolver lo descontado y pérdida definitiva no resta otra vez. Estado/stock inválido bloquea confirmación. La causa devolucion_danada no implementa devolución comercial. Cambios de artículos vendidos, elegibilidad, plazos y dinero son RF-027 propuesto y DP-04, sin deltas aprobados.

## Versión candidata, versión objetivo y exclusiones

La **versión candidata actual** comprende núcleo transaccional y catálogo, administración/operación vendedor, reportes descriptivos y asistencia visual privada con revisión. No está aceptada como producción integral; aún requiere cerrar políticas, regresiones, privacidad de comprobantes, recuperación y validación por actores.

La **versión objetivo recomendada, pendiente de aprobación**, debe:

1. Consolidar integridad, permisos, estados/pagos/stock y definiciones de reportes; ensayar transición y recuperación.
2. Validar OE-02 por tarea: datos independientes, clases/atributos con soporte, abstención y decisión humana, contrato y fallback comprobados.
3. Desarrollar y evaluar RF-077 respecto a baseline para cumplir OE-04 si se mantiene su redacción.
4. Integrar RF-078 con evaluación humana y disponibilidad actual para cumplir OE-05 si se mantiene el alcance.
5. Incorporar ontología por ámbitos solo tras revisión y con trazabilidad; usar datos/modelos aprobados para cada consumidor.

RAG, segmentación y detección siguen en investigación y no son prerrequisitos automáticos de toda asistencia visual. Tallas, try-on, tendencias, descripciones generativas, consultas naturales, escaneo continuo y administrador independiente requieren decisión de alcance; no se convierten en obligatorios de la candidata por figurar en teoría. El orden y criterios están en [07_AUP_PLANIFICACION.md](07_AUP_PLANIFICACION.md).

Fuera del alcance actual: proveedores/compras detalladas, contabilidad/impuestos avanzados, pasarela/conciliación bancaria compleja, escaneo corporal 3D especializado, multisede/multialmacén no acreditados, SQL o agentes de escritura libres, extracción indiscriminada y automatización de precio/SKU/stock por modelo. Facturación fiscal y devolución con reembolso no están demostradas como módulos actuales. Futuras incorporaciones requieren aprobación específica; no borrar del objetivo académico aquello que continúa prometido sin DP-02.

## Uso de esta definición

[02](02_REQUERIMIENTOS.md) contiene obligaciones y aceptación; [03](03_REGLAS_NEGOCIO.md), reglas actuales y pendientes; [04](04_ESTADO_IMPLEMENTACION.md), estados/evidencias; [05](05_ARQUITECTURA_FUNCIONAL.md), responsabilidades; [06](06_TRAZABILIDAD.md), relaciones y faltantes; [07](07_AUP_PLANIFICACION.md), trabajo AUP; [08](08_INCOHERENCIAS_INFORME.md), correcciones V4; [09](09_DECISIONES_PENDIENTES.md), decisiones; [10](10_RESUMEN_EJECUTIVO_TECNICO.md), síntesis. No se revisaron ni modificaron representaciones gráficas.
