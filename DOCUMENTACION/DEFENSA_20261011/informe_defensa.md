# MORE — comprobación para la defensa del 11 de octubre de 2026

MORE está funcionando en el entorno inspeccionado. Se corrigió un P1 concreto en la edición de prendas agotadas. No se certifica que todo el proyecto esté terminado ni que todos sus flujos hayan sido probados. La intervención mantuvo código, datos comerciales, almacenamiento y avances de investigación.

## A. Git

- Rama autorizada, comprobada al inicio y al cierre: `mejora/limpieza-defensa-20261011`.
- HEAD: `709bae2e5ad14c60e7ae52efee70f054e6fe21c7`.
- `backup/pre-defensa-20261011`: mismo commit, conservado.
- Mensaje del checkpoint: `checkpoint: respaldo MORE antes de limpieza y defensa`.
- Tres archivos versionados modificados, detallados en D; nuevos entregables solo en esta carpeta.
- Sin commit, push, cambio de rama, reset, limpieza Git ni restauración. `git diff --check` correcto.

## B. Inventario y limpieza

Se preservaron los 832 archivos versionados del inventario inicial: A 507 (funcionamiento), B 168 (documentación/pruebas), C 156 (investigación), E 1 (README genérico de Laravel). No hay archivo versionado propuesto para borrar. Se eliminaron 0 y se apartaron 0; no hizo falta cuarentena. Los ZIP `DOCUMENTACION/IA.zip` y `DOCUMENTACION/IA (2).zip` ya estaban ausentes.

Las cachés regenerables se conservaron porque están ignoradas por Git. Los dos duplicados exactos documentales tienen referencias históricas distintas y también se conservaron. No se limpiaron dependencias, volúmenes, logs de Laravel, dumps, backups, pesos, imágenes, `.env` ni galerías. El total de recursos ignorados no se inventaría como si estuviera cubierto por Git. Relación individual y razones en [inventario_archivos.csv](inventario_archivos.csv); método y áreas en [inventario_limpieza.md](inventario_limpieza.md).

## C. Programa: comprobaciones reales y límites

| Componente | Evidencia de esta sesión | Límite |
| --- | --- | --- |
| Docker/WSL | Cuatro contenedores running; PostgreSQL, Redis y Python healthy | No se ensayó reinicio o arranque en frío |
| Laravel | Laravel 13.1.1, PHP 8.5.5; requisitos Composer correctos; 134 rutas; `/up` y `/login` HTTP 200 | Entorno local con debug habilitado |
| Autenticación/roles | Guest: `/superadmin` y `/vendedor` redirigen a login; sesión superadmin existente usable; pantalla con roles superadmin/vendedor/cliente | No se envió login ni se probó la matriz completa de permisos o sesión vendedor/cliente |
| Frontend | `npm run build`: TypeScript y Vite 8.0.3 correctos, 2.918 módulos | Modo build, `public/hot` ausente; sin instalación de paquetes |
| Administración | Dashboard, prendas, ficha 134, alta y edición, categorías, colores, tallas, ventas, reservas y reportes renderizados en navegador | No se guardó ninguna ficha ni operación comercial |
| Variantes/inventario | Paso comercial y revisión; stock, alertas y Kardex visibles; 134 prendas/variantes, 126 disponibles y 6 reservadas observadas | Totales son una fotografía de esta sesión |
| Tienda | `/catalogo` 124 productos; filtro POLERA 16 resultados; `/producto/134` ficha, imagen y variante visibles | Compra/reserva no se confirmó |
| Recursos visuales | Imágenes locales de las fichas revisadas y de 12 tarjetas muestreadas cargadas; sin desbordamiento de página en pantallas revisadas a anchura normal de escritorio | No inspección exhaustiva de 134 fichas ni todos los tamaños de pantalla |
| Laravel–Python | Inferencia real SigLIP CUDA, embedding 768 y abstenciones; resultado guardado en `verificacion_ia.json` | Cliente directo sin persistencia; no prueba del POST del asistente de UI |

Pruebas ejecutadas con entorno explícito `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=` vacío, cache/sesión array, logging nulo y sin cache PHPUnit: Unit **84 pruebas / 239 aserciones**; `InventoryP0RegressionTest.php` **12 pruebas / 58 aserciones**. Las pruebas de inventario verifican el driver y la base en memoria antes de crear su esquema aislado manual. No se ejecutaron migraciones, seeders ni la suite Feature completa.

Comandos seguros utilizados para las suites seleccionadas, desde WSL:

```bash
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= -e CACHE_STORE=array -e SESSION_DRIVER=array -e LOG_CHANNEL=null laravel.test php vendor/bin/phpunit --testsuite Unit --do-not-cache-result
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= -e CACHE_STORE=array -e SESSION_DRIVER=array -e LOG_CHANNEL=null laravel.test php vendor/bin/phpunit tests/Feature/InventoryP0RegressionTest.php --do-not-cache-result
```

## D. Corrección P1

**Causa:** una variante existente con disponible 0 se cargaba en edición, pero frontend y backend exigían stock mínimo 1. Se bloqueaba llegar a revisión o validar la ficha aunque no se estuviera reponiendo inventario.

| Archivo modificado | Cambio y alcance |
| --- | --- |
| `app/Services/Superadmin/Resources/ResourceValidationService.php` | Mínimo 0 solo para IDs de variantes pertenecientes a la prenda editada; nuevas o ajenas siguen con mínimo 1. Mensaje usa el mínimo real. Consulta adicional de IDs propios. |
| `resources/js/Pages/Superadmin/ProductCreate.tsx` | Variantes existentes muestran stock disponible como lectura y permiten 0; nuevas conservan mínimo 1. |
| `tests/Feature/InventoryP0RegressionTest.php` | Regresiones: editar agotada conserva disponible/reservado; nueva con 0 se rechaza y con 1 se acepta; variante ajena no evade validación; aislamiento SQLite comprobado. |

Antes se reprodujo el rechazo. Después pasaron las 12 pruebas / 58 aserciones y el build. En navegador, la prenda 13 agotada muestra stock 0 en lectura y alcanza Paso 5, Revisión y guardado, sin pulsar Actualizar prenda. No cambió la aritmética de inventario ni se modificaron datos reales. [Evidencia visual](edicion_stock_cero.jpg).

No se identificó P0 en las comprobaciones realizadas. No se ejecutó refactorización general ni se modificó el baseline de reconocimiento.

## E. IA: estado que puede afirmarse

| Estado | Función | Qué puede explicarse |
| --- | --- | --- |
| Operativo y comprobado | Transporte Laravel–Python, análisis visual SigLIP CUDA, embedding visual | Una inferencia real sobre imagen local: 768 valores, norma L2 0,999999983, total aproximado 5,15 s incluida carga inicial |
| Operativo con abstención comprobada | Candidatos de categoría y colorimetría | Estado `partial`; margen 0,00217, abstención de categoría; color `insufficient_evidence`, revisión humana. Similitudes no son probabilidades calibradas |
| Implementado, no ejecutado con casos reales hoy | OCR EasyOCR, códigos/QR, identificación interna, similares, preparación de imágenes, compatibilidad de pares, embedding textual E5 | Código y capacidades presentes. OCR tiene pesos locales pero reader no cargado/quality no evaluada; E5 384 anunciado pero no inferido en esta sesión. No confundir con prueba real |
| Experimental y bloqueado | Segmentación semántica, RAG y RAG Abstention Gate V2 | Sin promoción; recuperación RAG deshabilitada y gate no aprobado, `NO_PROMOTION`. No afirmar respuestas generadas o segmentación productiva |
| Diseñado/documentado o incompleto | Escaneo continuo tipo Lens, identidad externa exacta, enriquecimiento externo automático, Trend Intelligence, Outfit Engine completo, asistente LLM | Existen contratos/diseños, no un módulo completo comprobado. Hay reglas de pares y adaptadores, no un Outfit Engine completo ni enriquecimiento externo activo |

Fashion Ontology V1.1.0 permanece **DRAFT_FOR_REVIEW**. Fuentes externas configuradas: false/lista vacía. Modelo visual usa pesos locales; no se descargó ni entrenó. No se activó gate, recuperación ni fuentes externas.

La evidencia real está en [verificacion_ia.json](verificacion_ia.json). El campo `loaded=false` en capacidades corresponde a la consulta previa a la inferencia; la inferencia posterior sí se ejecutó en CUDA. No presentar color comercial ni categoría de esta imagen como confirmados por el modelo. [Script de verificación](verificar_ia_sin_persistencia.php): consultas en READ ONLY y rollback, sin `ai_runs` ni persistencia de vectores.

## F. Recorrido breve comprobado (8–10 minutos)

Todos los pasos de navegador son de lectura y se apoyan en pantallas abiertas en esta sesión. El inicio de sesión nuevo con credenciales existentes se deja al usuario; no se afirma haber probado el envío de login.

| Orden | Qué mostrar / acción | Resultado esperado | Dependencia / alternativa si falla | No confundir con |
| --- | --- | --- | --- | --- |
| 1 | `/login`: presentar acceso; entrar con la sesión superadmin ya disponible | Formulario y panel accesibles | Laravel + PostgreSQL; si falla, comprobar `/up` y servicios, no crear usuarios | Matriz completa de permisos probada |
| 2 | Panel: recorrer indicadores y accesos | Resumen operativo y navegación | Laravel + PostgreSQL; pasar al listado si un gráfico falla | Totales de gráficos como validación contable |
| 3 | Prendas: abrir ficha 134, después edición 13; Paso 4 → revisión, cancelar sin guardar | Imagen y atributos; agotada puede revisarse con stock 0 | Recursos locales; usar ficha ya comprobada, no sustituir imágenes | Alta o actualización realizada en la base comercial |
| 4 | Categorías, colores y tallas; mostrar variante de una ficha | Taxonomías y configuración comercial | Base local; volver a ficha si un listado falla | Identidad exacta externa o atributos IA confirmados |
| 5 | Existencias, alertas y Kardex | Lectura de disponible, reservado y movimientos | Base local; omitir movimientos de escritura | Reposición, venta o reserva ejecutada hoy |
| 6 | Catálogo: filtrar POLERA y abrir `/producto/134` | 16 resultados del filtro; ficha con imagen, talla, color, precio y disponibilidad | Laravel + imágenes locales; volver al catálogo si una ficha falla | Compra confirmada o recomendaciones Outfit Engine |
| 7 | Terminal: comando de inferencia sin persistencia de la guía | JSON real, embedding, avisos y abstenciones | Python + GPU + pesos locales; si falla, explicar estado y mostrar evidencia fechada como evidencia anterior | IA acertada al 100 %, guardado automático, RAG o LLM productivo |
| 8 | Cerrar con arquitectura Laravel/React → PostgreSQL y Python; mostrar tabla de estado IA | Separación entre gestión comprobada e investigación | Documentación local; no requiere fuente externa | Proyecto completamente terminado |

No confirmar venta, reserva, borrado, movimiento de stock o guardado durante este recorrido para mantener los datos comerciales.

## G. Arranque

Comandos, orden, puertos y parada en [guia_arranque.md](guia_arranque.md). Se verificaron configuración y disponibilidad, no un arranque en frío. El entorno usa el override GPU actual; AI solo está expuesto dentro de Docker. En modo build no es necesario Vite en desarrollo.

## H. Riesgos y documentación pendiente

- Modo claro: reglas incompletas de recoloreado dejan texto oscuro sobre fondos oscuros y algunos textos pálidos sobre claro. P2; se conserva sin cambios y se restauró modo oscuro para la revisión. Repararlo globalmente exige revisar varias excepciones.
- El gráfico «Stock por estado» del dashboard mezcla cantidades de unidades y recuentos de estados; usar las tablas de existencias para explicar números. P2 sin cambio.
- Un componente de imagen conserva su estado de error al cambiar `src`; riesgo menor si primero carga una imagen rota y luego una válida. No ocurrió en las imágenes muestreadas.
- No se probó login POST, sesión vendedor/cliente, guardado comercial, checkout/reserva, captura con cámara, OCR real, similitud real o todos los breakpoints. Código presente no implica prueba de ejecución.
- No se encontró informe académico principal DOC/DOCX/PDF/ODT/TEX/PPTX en el ámbito revisado. No se inventó ni reescribió. `PROJECT_OVERVIEW.md` tiene referencias a tablas antiguas y compra como futura; documentos históricos IA contienen estados después corregidos. Contrastar fechas con las correcciones P1 y con este informe antes de citar.
- Entorno local con debug habilitado; comprobar antes de presentar que la pantalla no muestre un error. Conservar credenciales y sesión existentes. Una falla GPU debe declararse, no reemplazarse por resultado simulado.
- El checkpoint Git no cubre base real, imágenes externas, modelos ignorados ni galerías; todos se preservaron.

## I. Recuperación

HEAD ya es el commit solicitado; no hay commit nuevo que rebobinar. La guía contiene respaldo externo de los tres cambios y de esta carpeta, seguido de `git restore` selectivo desde `709bae2` y recompilación. No se ejecutó restauración. La recuperación conserva rama y respaldo, pero devuelve el P1 de prendas agotadas. Los datos ignorados o externos requieren sus respaldos independientes.
