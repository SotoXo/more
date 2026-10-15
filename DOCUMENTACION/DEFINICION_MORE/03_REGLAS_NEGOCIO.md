# MORE — Reglas de negocio y restricciones operativas

Fecha de corte: 12/10/2026. Las reglas describen conducta encontrada y condiciones propuestas. **No se aprueban políticas de anticipos, devoluciones, retención, paquetes o datos personales.** Cuando hay divergencia, se conserva el comportamiento actual y se remite a una decisión.

D=cdi_inv y R=cre_inv. Fuente, actor y condición son parte de cada regla; una formulación normativa no prueba toda la base activa. Estados y EV se definen en [04](04_ESTADO_IMPLEMENTACION.md). Procesos y deltas completos en [01](01_DEFINICION_INTEGRAL.md); criterios por RF en [02](02_REQUERIMIENTOS.md).

## RB-001 — Identificadores comerciales únicos

**Descripción:** Identificadores comerciales únicos. **Módulo:** Acceso/Catálogo. **Actor/responsable:** Servidor y superadministrador.

**Condición:** Alta/edición de email, SKU o código sujeto a unicidad.

**Resultado/regla:** Validar unicidad correspondiente antes de guardar; conservar formato de código.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-05, EV-07.

**Observaciones:** No confundir ID interno con identidad visual; índices concretos y validación no prueban ausencia de duplicados históricos.

## RB-002 — Autoridad por rol real

**Descripción:** Autoridad por rol real. **Módulo:** Acceso. **Actor/responsable:** Middleware, controladores y servicios.

**Condición:** Operación administrativa/comercial o checkout.

**Resultado/regla:** Superadmin controla maestros/stock; vendedor operaciones permitidas; cliente checkout; visitante lectura.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-02, EV-03, EV-14.

**Observaciones:** Crear otro registro de rol no añade política. Administrador independiente requiere DP-01.

## RB-003 — Sesión y acceso válidos

**Descripción:** Sesión y acceso válidos. **Módulo:** Acceso. **Actor/responsable:** Servidor.

**Condición:** Login, entrada a panel y cambio de contraseña.

**Resultado/regla:** Cuenta/rol activos para login/panel; límite de intentos, invalidación logout y comprobación de credencial actual.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-03, EV-02.

**Observaciones:** No afirmar que todas las rutas auth revalidan estado de cuenta en cada petición; checkout contiene validación adicional.

## RB-004 — Baja de usuario con trazabilidad

**Descripción:** Baja de usuario con trazabilidad. **Módulo:** Acceso. **Actor/responsable:** Superadministrador / titular.

**Condición:** Baja administrativa o solicitud de borrar perfil.

**Resultado/regla:** CRUD administrativo desactiva; ProfileController.destroy elimina físicamente tras contraseña.

**Estado actual:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-03, EV-04, EV-07.

**Observaciones:** Dos comportamientos actuales incompatibles como política única; DP-05 debe resolver cuentas con operaciones, último privilegiado y retención.

## RB-005 — Maestros y relaciones válidos

**Descripción:** Maestros y relaciones válidos. **Módulo:** Catálogo. **Actor/responsable:** Superadministrador / servidor.

**Condición:** Asignar categoría, marca, temporada, talla, color o atributo.

**Resultado/regla:** Usar IDs válidos/permitidos y impedir borrado de maestros relacionados.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-04, EV-05, EV-07.

**Observaciones:** No equiparar nombres iguales a equivalencia ontológica ni crear valores de catálogo desde una inferencia.

## RB-006 — Complemento dirigido de categorías

**Descripción:** Complemento dirigido de categorías. **Módulo:** Catálogo/Recomendación. **Actor/responsable:** Superadministrador.

**Condición:** Definir relación comercial complementaria.

**Resultado/regla:** Origen distinto de destino, prioridad >=1 y estado de relación.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-07, EV-35.

**Observaciones:** Jerarquía padre-hijo y complementariedad son relaciones distintas; no equivalen a outfit validado.

## RB-007 — Producto, variante y ejemplar

**Descripción:** Producto, variante y ejemplar. **Módulo:** Catálogo. **Actor/responsable:** Superadministrador / servidor.

**Condición:** Alta o consulta de prenda.

**Resultado/regla:** Prenda describe ficha; variante pertenece a una prenda y talla/color; cantidad representa unidades agregadas.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-06, EV-07.

**Observaciones:** No se encontró serialización de cada unidad física. Variante 0..1 inventario por unicidad; existencia obligatoria se valida en operación.

## RB-008 — Edición no reinicializa inventario

**Descripción:** Edición no reinicializa inventario. **Módulo:** Catálogo/Inventario. **Actor/responsable:** Superadministrador / ProductCatalogService.

**Condición:** Editar variante existente propia o crear nueva.

**Resultado/regla:** Existente conserva D/R; nueva inicializa D>=1,R=0; agotada propia admite D=0 en validación.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-05, EV-06, EV-31, EV-33.

**Observaciones:** Pruebas 12/58 documentadas EV-31; esta regla no autoriza reposición mediante formulario de catálogo.

## RB-009 — Borrado condicionado por historial

**Descripción:** Borrado condicionado por historial. **Módulo:** Catálogo. **Actor/responsable:** Superadministrador / servidor.

**Condición:** Eliminar maestro, prenda o quitar variante.

**Resultado/regla:** Bloquear relaciones protegidas; venta/reserva/Kardex impiden quitar variante desde editor.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-04, EV-06, EV-07.

**Observaciones:** Preferencia por inactivación requiere política; no se certifican todos los caminos concurrentes de borrado.

## RB-010 — Código como evidencia de identidad

**Descripción:** Código como evidencia de identidad. **Módulo:** Identificación. **Actor/responsable:** Servidor / revisor.

**Condición:** Matching con SKU/GTIN/código reconocido.

**Resultado/regla:** Normalizar sin perder ceros, validar checksum donde aplica y distinguir exacto de candidato.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-05, EV-30, EV-24.

**Observaciones:** Código leído debe corresponder al producto real; QR arbitrario no concede permisos ni autoriza visitar URL.

## RB-011 — Imagen válida y derechos explícitos

**Descripción:** Imagen válida y derechos explícitos. **Módulo:** Catálogo/Publicación. **Actor/responsable:** Superadministrador / servidor.

**Condición:** Cargar o publicar un medio.

**Resultado/regla:** Validar archivo/relación; medios externos necesitan derechos permitidos y evidencia antes de adjuntar.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-05, EV-25, EV-30.

**Observaciones:** Referencia privada, original y derivados tienen estados diferentes; no todos los JPG de una web pueden publicarse.

## RB-012 — Disponible y reservado separados

**Descripción:** Disponible y reservado separados. **Módulo:** Inventario. **Actor/responsable:** Servicios transaccionales.

**Condición:** Solicitar q unidades activas.

**Resultado/regla:** q entero positivo y D>=q antes de vender/separar; R no se resta nuevamente de D para medir disponibilidad.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-08, EV-31, EV-33.

**Observaciones:** D=cdi_inv; R=cre_inv. Reservado es agregado de reservas y ventas online en revisión.

## RB-013 — Total operativo no equivale a conteo físico certificado

**Descripción:** Total operativo no equivale a conteo físico certificado. **Módulo:** Inventario. **Actor/responsable:** InventoryStatusService / responsable de inventario.

**Condición:** Mostrar totales o alertas.

**Resultado/regla:** Total operativo D+R; bajo positivo <=3; agotado D=0.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-08, EV-07.

**Observaciones:** D+R excluye daño apartado y no prueba conteo físico ni propiedad contable de mercancía pagada pendiente de entrega; DP-06.

## RB-014 — Entrada inicial de variante

**Descripción:** Entrada inicial de variante. **Módulo:** Inventario. **Actor/responsable:** Superadministrador / servicio.

**Condición:** Crear variante/inventario nuevo.

**Resultado/regla:** D toma cantidad inicial y R=0; registrar movimiento de entrada.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-06, EV-08, EV-31, EV-33.

**Observaciones:** No incluye compras a proveedor ni orden automática de reposición.

## RB-015 — Ajuste autorizado con snapshot

**Descripción:** Ajuste autorizado con snapshot. **Módulo:** Inventario. **Actor/responsable:** Superadministrador / ResourceWriteService.

**Condición:** Editar stock o ubicación.

**Resultado/regla:** Bloquear fila y comparar D/R/ubicación originales; rechazo si cambió; R actual protegido.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-04, EV-08, EV-31, EV-33.

**Observaciones:** inventario.ajustes es pantalla not_ready; sí existe edición operativa de inventario.stock.

## RB-016 — Movimiento trazable y atómico

**Descripción:** Movimiento trazable y atómico. **Módulo:** Inventario. **Actor/responsable:** Servicios / InventoryMovementService.

**Condición:** Entrada, separación, venta, liberación, conversión, daño o ajuste.

**Resultado/regla:** Guardar cantidad, antes/después, referencia, actor y contexto permitido dentro de operación correspondiente.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-08, EV-20, EV-17.

**Observaciones:** No afirmar inmutabilidad; escritura directa SQL puede eludir observer; idempotencia de alta comercial tiene ámbito delimitado.

## RB-017 — Reportar daño no implica descuento

**Descripción:** Reportar daño no implica descuento. **Módulo:** Daños. **Actor/responsable:** Vendedor / Superadministrador.

**Condición:** Crear reporte de daño.

**Resultado/regla:** Reporte vendedor fuerza no afectación; confirmación privilegiada decide afectación con D suficiente.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-15, EV-14.

**Observaciones:** Etiqueta causa devolucion_danada no constituye módulo de devolución o reembolso de venta.

## RB-018 — Daño no se descuenta dos veces

**Descripción:** Daño no se descuenta dos veces. **Módulo:** Daños. **Actor/responsable:** Superadministrador / DamagedProductService.

**Condición:** Confirmar, reparar o dar baja definitiva.

**Resultado/regla:** Descuento al confirmar con afectación; reparación devuelve si aplicó; pérdida final no vuelve a descontar.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-15, EV-08.

**Observaciones:** Caso completo y repetición requieren aceptación; un daño con reserva comprometida no se resuelve inventando política.

## RB-019 — Devolución requiere política propia

**Descripción:** Devolución requiere política propia. **Módulo:** Devoluciones. **Actor/responsable:** Mati / personal autorizado por definir.

**Condición:** Solicitud sobre venta entregada.

**Resultado/regla:** Diseñar elegibilidad, referencia, estado de artículo, restitución y dinero separados.

**Estado actual:** PROPUESTA. **Evidencia:** EV-02, EV-15, EV-35.

**Observaciones:** DP-04 pendiente. Liberar reserva, rechazo de voucher y reparar daño no demuestran devolución comercial.

## RB-020 — Venta directa consume disponible

**Descripción:** Venta directa consume disponible. **Módulo:** Ventas. **Actor/responsable:** Vendedor / Superadministrador.

**Condición:** Venta presencial válida.

**Resultado/regla:** D'=D-q; R'=R; cabecera/detalle/pago y venta registrada/entregada.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-08, EV-09, EV-31, EV-33.

**Observaciones:** Comprobado el delta en prueba aislada documentada; no aceptación actual de todos los medios/descuentos.

## RB-021 — Revalidación comercial bajo bloqueo

**Descripción:** Revalidación comercial bajo bloqueo. **Módulo:** Ventas/Reservas. **Actor/responsable:** Servidor.

**Condición:** Confirmar checkout, venta o reserva.

**Resultado/regla:** Releer producto/variante/stock/precio y actividad en transacción; validar cantidades y descuentos con datos vigentes.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-09, EV-10, EV-11, EV-33.

**Observaciones:** Bloqueo por fila reduce carreras; no garantiza SLA, todos los interleavings ni bloqueo optimista de cada maestro.

## RB-022 — Intención única para altas cubiertas

**Descripción:** Intención única para altas cubiertas. **Módulo:** Comercial. **Actor/responsable:** CommercialOperation / cliente o staff.

**Condición:** POST de venta/reserva en las rutas incluidas en PATHS.

**Resultado/regla:** UUID obligatorio vinculado a usuario/ruta/hash; repetición equivalente redirige al resultado, distinta rechaza.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-17, EV-33.

**Observaciones:** No es idempotencia universal de pagos, entregas, daños o borrados. Prueba multiproceso PostgreSQL existe, no se ejecutó ahora.

## RB-023 — Voucher pendiente no prueba pago

**Descripción:** Voucher pendiente no prueba pago. **Módulo:** Pagos. **Actor/responsable:** Cliente / staff revisor.

**Condición:** Subir comprobante QR/transferencia.

**Resultado/regla:** Guardar declarado y estado pendiente separado de monto confirmado/verificado.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-09, EV-10, EV-12.

**Observaciones:** No hay callback/conciliación bancaria demostrados; comprobantes se almacenan en public, aislamiento de archivo pendiente DP-09.

## RB-024 — Compra online exige totalidad verificada

**Descripción:** Compra online exige totalidad verificada. **Módulo:** Pagos/Ventas. **Actor/responsable:** Vendedor / Superadministrador.

**Condición:** Aprobar venta en_revision con pago candidato.

**Resultado/regla:** Verificado=total redondeado; confirmar pago/venta y consumir R separado sin otro descuento D.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-09, EV-08, EV-14.

**Observaciones:** Panel vendedor utiliza declarado como importe a aprobar; no afirmar que implementa reconciliación bancaria o importe libre.

## RB-025 — Rechazo y cancelación online son distintos

**Descripción:** Rechazo y cancelación online son distintos. **Módulo:** Ventas. **Actor/responsable:** Revisor / Superadministrador.

**Condición:** Pago insuficiente/rechazado o cancelación autorizada en revisión.

**Resultado/regla:** Rechazo genera incompleta; cancelación genera cancelada; ambas liberan separación cuando corresponde.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-09, EV-08, EV-14.

**Observaciones:** Venta registrada no se cancela por este flujo; no reembolso monetario implícito.

## RB-026 — Primer anticipo confirmado mínimo 50%

**Descripción:** Primer anticipo confirmado mínimo 50%. **Módulo:** Reservas/Pagos. **Actor/responsable:** Cliente / staff revisor.

**Condición:** Alta y primera verificación de reserva.

**Resultado/regla:** Declarado inicial positivo 50–100%; primer confirmado >=50%, total acumulado no supera total.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-12, EV-33.

**Observaciones:** Es regla actual del código, no nueva política decidida por auditoría; anticipo declarado no reduce saldo confirmado.

## RB-027 — Saldo según pagos confirmados

**Descripción:** Saldo según pagos confirmados. **Módulo:** Reservas/Pagos. **Actor/responsable:** ReservationPaymentService.

**Condición:** Verificar pago válido de reserva activa.

**Resultado/regla:** Sumar confirmados, limitar a total, calcular saldo y estado anticipo_confirmado/pagado_total.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-12, EV-11.

**Observaciones:** La validación previa usa monto_pagado_res; debe verificarse conciliación ante datos antiguos inconsistentes; abonos no son ingresos duplicados de venta.

## RB-028 — Verificación y rechazo alteran reserva

**Descripción:** Verificación y rechazo alteran reserva. **Módulo:** Reservas. **Actor/responsable:** Revisor autorizado.

**Condición:** Verificar/rechazar último pendiente propio.

**Resultado/regla:** Verify fija confirmada; reject con observación fija rechazada y servicio libera stock.

**Estado actual:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-11, EV-12, EV-14.

**Observaciones:** Verify puede regresar lista_recojo a confirmada; reject puede liberar pese a pagos previos. DP-03 para política coherente, sin cambiar código ahora.

## RB-029 — Cancelación monetariamente restringida

**Descripción:** Cancelación monetariamente restringida. **Módulo:** Reservas. **Actor/responsable:** Vendedor / Superadministrador.

**Condición:** Reserva activa y suma confirmados cero.

**Resultado/regla:** Cancelar y liberar D+=q,R-=q una vez; si confirmado>0, no cambiar.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-12, EV-33.

**Observaciones:** Vencimiento/rechazo no llaman al mismo guard. No prometer mecanismo de resolver/refund inexistente.

## RB-030 — Vencimiento visual distinto de persistido

**Descripción:** Vencimiento visual distinto de persistido. **Módulo:** Reservas. **Actor/responsable:** Superadministrador / servicio.

**Condición:** Fecha límite alcanzada de reserva activa.

**Resultado/regla:** markExpired exige plazo alcanzado, libera stock y marca vencida; vista puede mostrar vencida sin esa escritura.

**Estado actual:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-11, EV-33.

**Observaciones:** No se identificó tarea programada de vencimiento automático en rutas console.php; dinero confirmado requiere DP-03.

## RB-031 — Recojo sujeto a estado y pago

**Descripción:** Recojo sujeto a estado y pago. **Módulo:** Logística. **Actor/responsable:** Vendedor / Superadministrador.

**Condición:** Marcar listo o entregar.

**Resultado/regla:** markReady exige confirmada; entrega requiere estado/pago total y responsable.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-11, EV-13, EV-14.

**Observaciones:** markReady no comprueba fecha en ese método. Vendedor no registra pago final por ruta de entrega; política/calendario DP-03.

## RB-032 — Conversión consume reservado

**Descripción:** Conversión consume reservado. **Módulo:** Reservas/Ventas. **Actor/responsable:** ReservationService / ReservationToSaleService.

**Condición:** Reserva completada con pago total y stock comprometido.

**Resultado/regla:** R'=R-q; D'=D; venta derivada y detalles coherentes, reserva recogida.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-08, EV-13, EV-31, EV-33.

**Observaciones:** No volver a descontar D; pago final exacto es flujo superadmin distinto del vendedor.

## RB-033 — Vínculo reserva–venta actual

**Descripción:** Vínculo reserva–venta actual. **Módulo:** Reservas/Ventas. **Actor/responsable:** Servicio / superadministrador.

**Condición:** Convertir o regularizar reserva.

**Resultado/regla:** Número VEN-RES-<nre_res> resuelve venta; no FK reserva_id ni copia automática de reserva_pagos a venta_pagos.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-13, EV-18, EV-07.

**Observaciones:** Regularización de completada utiliza opción sin otro consumo; mejoras de enlace explícito necesitan diseño posterior DP-03.

## RB-034 — Recibo y consulta por propietario

**Descripción:** Recibo y consulta por propietario. **Módulo:** Cuenta/Comprobantes. **Actor/responsable:** Cliente / Superadministrador.

**Condición:** Consultar operación o emitir recibo.

**Resultado/regla:** Filtrar por cliente actual, chequear titular del recibo y condición de emisión.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-18, EV-02.

**Observaciones:** No existe ruta de recibo superadmin concedida al vendedor solo por ser staff; factura fiscal fuera del alcance actual.

## RB-035 — Catálogo informa, operación revalida

**Descripción:** Catálogo informa, operación revalida. **Módulo:** Tienda. **Actor/responsable:** Visitante / Cliente / servidor.

**Condición:** Consultar disponibilidad o buscar complementos.

**Resultado/regla:** Mostrar actividad/variantes/filtros; usar D en variante coincidente para disponibilidad de operación.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-16, EV-35, EV-10.

**Observaciones:** Catálogo admite vistas de agotados; imagen/card no compromete stock; filtros talla y color deben corresponder a misma variante.

## RB-036 — Carrito no compromete existencias

**Descripción:** Carrito no compromete existencias. **Módulo:** Tienda. **Actor/responsable:** Visitante / Cliente.

**Condición:** Agregar/modificar selección.

**Resultado/regla:** Compra en localStorage; reserva en sesión; ningún alta de item cambia D/R; confirmar usa datos del servidor.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-17, EV-10, EV-11.

**Observaciones:** Dos almacenes actuales no son tabla carrito, reserva persistida ni garantía de sincronización entre dispositivos.

## RB-037 — Favorito propio es preferencia explícita

**Descripción:** Favorito propio es preferencia explícita. **Módulo:** Tienda. **Actor/responsable:** Usuario autenticado.

**Condición:** Alternar favorito de prenda.

**Resultado/regla:** Persistir por usuario/prenda sin duplicación.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-18, EV-07.

**Observaciones:** No demuestra perfil aprendido, afinidad estadística ni consentimiento para toda analítica futura.

## RB-038 — Definiciones coherentes para indicadores

**Descripción:** Definiciones coherentes para indicadores. **Módulo:** Reportes/Analítica. **Actor/responsable:** Responsable de reporte / staff.

**Condición:** Agregar ventas, reservas, pagos o inventario.

**Resultado/regla:** Distinguir cantidad/recuento, vendido/pagado/entregado y período; evitar doble contar reserva convertida.

**Estado actual:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-19, EV-31.

**Observaciones:** Gráfico Stock por estado mezcla unidades/recuentos según EV-31; agregación exacta de cada reporte necesita aceptación DP-07.

## RB-039 — Aviso destinado al usuario

**Descripción:** Aviso destinado al usuario. **Módulo:** Notificaciones. **Actor/responsable:** Sistema / destinatario.

**Condición:** Evento operativo confirmado y lectura de aviso.

**Resultado/regla:** Registrar/leer notificaciones propias; tras commit cuando lo dispone servicio.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-20, EV-02.

**Observaciones:** Notificación database no es correo/SMS verificado; posible fallo de aviso no convierte pago pendiente en confirmado.

## RB-040 — Auditoría no es prueba absoluta de no repudio

**Descripción:** Auditoría no es prueba absoluta de no repudio. **Módulo:** Trazabilidad. **Actor/responsable:** Servicios / observer / superadmin.

**Condición:** Cambio de modelo observado o evento.

**Resultado/regla:** Conservar contexto permitido y responsable; sanitizar datos sensibles.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-20, EV-03, EV-22.

**Observaciones:** Sin sellado/firma o storage inmutable acreditados. Retención, acceso a vouchers y privacidad DP-09.

## RB-041 — Dump no acredita recuperación completa

**Descripción:** Dump no acredita recuperación completa. **Módulo:** Continuidad. **Actor/responsable:** Superadministrador / operador.

**Condición:** Backup y descarga.

**Resultado/regla:** Registrar resultado de pg_dump y restringir descarga; ensayar recuperación integral solo cuando autorizado.

**Estado actual:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-21, EV-36.

**Observaciones:** No incluye automáticamente imágenes/pesos/configuración ni define RPO/RTO; DP-10.

## RB-042 — Parámetro técnico distinto de decisión comercial

**Descripción:** Parámetro técnico distinto de decisión comercial. **Módulo:** Configuración. **Actor/responsable:** Superadmin / operador / Mati.

**Condición:** Cambiar catálogo, umbral, TTL o canal.

**Resultado/regla:** Identificar responsable y mecanismo real (UI/código/configuración); validar impacto antes de aplicar.

**Estado actual:** PARCIALMENTE IMPLEMENTADA. **Evidencia:** EV-04, EV-08, EV-22, EV-36.

**Observaciones:** Umbral <=3 y TTL 24h son configuración/código actuales, no políticas nuevas aprobadas.

## RB-043 — IA asistiva y autoridad comercial humana

**Descripción:** IA asistiva y autoridad comercial humana. **Módulo:** IA. **Actor/responsable:** Servicio IA / superadmin / Laravel.

**Condición:** Inferir imagen o proponer atributos.

**Resultado/regla:** Python propone; persona revisa; Laravel valida y guarda precio/SKU/stock/operación.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-22, EV-23, EV-25, EV-31, EV-32, EV-33.

**Observaciones:** Analizar no crea venta/reserva/prenda. Un atributo puede ser sugerido sin ser cierto; reconocimiento no sustituye evidencia de fabricante.

## RB-044 — Abstención preservada y scores no calibrados

**Descripción:** Abstención preservada y scores no calibrados. **Módulo:** IA. **Actor/responsable:** Modelo / guard / interfaz.

**Condición:** Baja evidencia, estado partial o incertidumbre.

**Resultado/regla:** Mostrar candidatos informativos sin aplicar abstencionistas; error/degraded mantiene flujo manual.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-23, EV-25, EV-31, EV-32, EV-33.

**Observaciones:** Coseno, margen y armonía no son probabilidad de acierto. No seleccionar un top-1 por defecto para ocultar abstención.

## RB-045 — Foreground no confirma identidad ni color real

**Descripción:** Foreground no confirma identidad ni color real. **Módulo:** Color/Segmentación. **Actor/responsable:** ColorimetryCatalogGuard / humano.

**Condición:** Intentar aplicar ID de color.

**Resultado/regla:** Exigir evidencia explícita de target y color comercial completed; actualmente se bloquea asignación automática.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-23, EV-25, EV-29, EV-31, EV-33.

**Observaciones:** Color técnico y swatch son proxies; máscara experimental o foto bonita no autoriza color del producto.

## RB-046 — Espacio vectorial completo e íntegro

**Descripción:** Espacio vectorial completo e íntegro. **Módulo:** Embeddings. **Actor/responsable:** Cliente/almacén vectorial.

**Condición:** Indexar o comparar.

**Resultado/regla:** Coincidir modelo, revisión inmutable, pipeline, dimensión, geometría/preprocess y L2; valores finitos.

**Estado actual:** IMPLEMENTADA Y COMPROBADA. **Evidencia:** EV-24, EV-26, EV-31, EV-33.

**Observaciones:** E5 texto, SigLIP FULL y crop/masked no se mezclan aunque coincidieran dimensiones; búsqueda actual exacta, no ANN.

## RB-047 — Ontología y promoción por evidencia separada

**Descripción:** Ontología y promoción por evidencia separada. **Módulo:** Gobierno IA. **Actor/responsable:** Mati / responsable técnico / revisores por designar.

**Condición:** Aprobar definición, dataset, entrenamiento, evaluación o producción.

**Resultado/regla:** IDs estables y estados KNOWN/UNKNOWN/NOT_APPLICABLE; aprobación explícita independiente por ámbito y fase.

**Estado actual:** DISEÑADA. **Evidencia:** EV-28, EV-29.

**Observaciones:** DRAFT_FOR_REVIEW y NONE permanecen. Políticas humanas aprobadas no son modelos ni etiquetas de producto aprobadas.

## RB-048 — Similitud, identidad y recomendación separadas

**Descripción:** Similitud, identidad y recomendación separadas. **Módulo:** IA/Recomendación. **Actor/responsable:** Servicios / interfaz / revisor.

**Condición:** Clasificar, recuperar, identificar o recomendar.

**Resultado/regla:** Clasificación propone categoría; visual recupera semejanza; código confirma candidato exacto sustentado; recomendación usa contexto.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-23, EV-24, EV-30, EV-35.

**Observaciones:** Complementos de reglas no cumplen por sí solos OE-04 de aprendizaje ni OE-05 de outfits completos.

## RB-049 — Trazabilidad de run, revisión y medio

**Descripción:** Trazabilidad de run, revisión y medio. **Módulo:** IA/Publicación. **Actor/responsable:** Superadministrador / servicios.

**Condición:** Guardar feedback o adjuntar imagen preparada.

**Resultado/regla:** Vincular propietario/run/variante; preservar evidencia, valor guardado, aprobación/derechos/TTL y un solo uso.

**Estado actual:** IMPLEMENTADA, PERO SIN VALIDACIÓN SUFICIENTE. **Evidencia:** EV-25, EV-30, EV-32, EV-33.

**Observaciones:** Fallo de registro IA tiene tratamiento seguro para conservar escritura manual; no retroetiquetar dato anterior como acierto del modelo.

## RB-050 — RAG y capacidades futuras cerradas sin aprobación

**Descripción:** RAG y capacidades futuras cerradas sin aprobación. **Módulo:** IA textual/Evolución. **Actor/responsable:** Gate / operador / responsable de alcance.

**Condición:** Consulta semántica o solicitud de promover consumidor.

**Resultado/regla:** Gate approved=false devuelve insuficiencia sin chunks ni respuesta; datos dinámicos requieren tools autorizadas; no habilitar experimentos por prompt.

**Estado actual:** EXPERIMENTAL. **Evidencia:** EV-26, EV-27, EV-28, EV-29, EV-35.

**Observaciones:** Perfil embedding aprobado no aprueba retrieval. Descripción/SQL/LLM/outfit/talla/try-on requieren DP y validación específica.

## Reglas que requieren decisión prioritaria

RB-004: baja/eliminación de cuenta (DP-05). RB-013/042: inventario físico y umbrales (DP-06). RB-019: devolución/reembolso (DP-04). RB-028–031/033: pagos previos, rechazo/vencimiento, logística y regularización (DP-03). RB-038: semántica de indicadores (DP-07). RB-040/041: privacidad/recuperación (DP-09/10). RB-047/050: ontología y promoción (DP-12/13/14).

No debe escribirse una política ideal como si el repositorio ya la ejecutara. Reforzar estos puntos mediante código o DB queda fuera de esta entrega.
