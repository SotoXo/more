# Inventario y decisión de limpieza — MORE

Fecha: 11 de octubre de 2026. Alcance: auditoría conservadora del repositorio antes de la defensa, sin modificaciones de datos comerciales ni del baseline de IA.

## Resultado

| Decisión | Cantidad | Alcance |
| --- | ---: | --- |
| Preservados en el inventario versionado | 832 | Todos los archivos que devuelve `git ls-files` |
| Eliminados | 0 | No se encontró ningún candidato cuya retirada estuviera comprobada y permitida |
| Apartados en cuarentena | 0 | Ningún archivo importante o potencialmente único fue movido |
| ZIP autorizados ausentes | 2 | `DOCUMENTACION/IA.zip` y `DOCUMENTACION/IA (2).zip` |

El archivo [inventario_archivos.csv](inventario_archivos.csv) contiene una fila por archivo versionado, con ruta, clase principal, decisión y motivo. La clasificación permite conservar piezas que sirven a varias finalidades; una clase principal no significa que no tengan otras funciones.

Los **185 archivos registrados como cambios del checkpoint `709bae2` no son el total de archivos del repositorio**. El árbol actual contiene **832 archivos versionados**; su tamaño conjunto observado es 9.523.038 bytes, aproximadamente 9,08 MiB. Ese tamaño no incluye dependencias instaladas, configuración local, bases de datos, imágenes ni otros recursos ignorados.

Esta auditoría creó únicamente los dos archivos de inventario de esta carpeta. Los nuevos entregables de defensa no forman parte de las 832 filas del inventario inicial. No se hizo commit, push, cambio de rama ni restauración.

## Clasificación principal de los archivos versionados

| Clase | Cantidad | Criterio |
| --- | ---: | --- |
| A — Necesario para funcionamiento | 507 | Código vigente, entradas, configuración, rutas, migraciones, recursos consumidos y herramientas de arranque |
| B — Documentación y defensa | 168 | Documentación técnica actual, auditorías, pruebas y scripts de validación pertinentes |
| C — Investigación y continuidad | 156 | Ontología, diseños futuros, protocolos, resultados científicos y herramientas para reproducir investigación |
| D — Innecesario comprobado | 0 | No se propone borrar ningún archivo versionado |
| E — Dudoso | 1 | README raíz de la plantilla Laravel; se conserva sin cambios |
| Total | 832 | Todas las decisiones del CSV son `preservar` |

El README raíz mantiene contenido genérico de Laravel. Su utilidad para explicar MORE es incierta, pero no se considera basura: no se retira ni reemplaza durante esta auditoría.

## Inventario por área

| Área | Archivos versionados | Valor y decisión |
| --- | ---: | --- |
| `app/` | 156 | Backend vigente: controladores, modelos, servicios, permisos y reglas comerciales; conservar |
| `resources/` | 159 | React/Inertia, tipos, estilos y vistas; conservar |
| `database/` | 65 | Migraciones, seeders, factories y configuración PostgreSQL; conservar sin ejecutar |
| `config/` | 14 | Configuración Laravel y capacidades IA/RAG; conservar |
| `bootstrap/`, `routes/`, `lang/`, `public/` | 14 | Arranque, rutas, traducciones y recursos públicos; conservar |
| `services/mati-ai/` | 176 | 55 archivos fuente, 35 recursos, 69 scripts, 9 tests y 8 archivos de configuración/documentación; conservar |
| `tests/` | 34 | Pruebas Laravel/frontend y soporte; conservar |
| `storage/` | 10 | Únicamente archivos `.gitignore` estructurales del inventario Git; no se inspeccionó ni limpió su almacenamiento |
| `DOCUMENTACION/` | 184 | Documentación funcional, auditorías, investigaciones y evidencia; conservar |
| Archivos de raíz | 20 | Manifests, locks, Compose, herramientas y documentación; conservar |
| Total | 832 | Sin retiros |

Las 184 piezas de `DOCUMENTACION/` incluyen 67 archivos en su raíz, 1 auditoría de formularios, 1 catálogo documentado y 115 archivos de IA. Dentro de IA se preservan: 20 de AUDITORIA_FINAL, 3 de CORRECCIONES_P1, 48 de FASHION_ONTOLOGY_V1, 6 de SEGMENTACION_SEMANTICA, 7 de RAG_TAXONOMIA, 7 de RAG_ABSTENTION_V2, 11 de V2_ARQUITECTURA_AMPLIADA, 7 de IMPLEMENTACION y 6 documentos de su raíz.

No se localizó un informe académico principal DOC, DOCX o PDF entre los archivos versionados y los archivos no ignorados observados. Esta constatación no permite concluir que no exista fuera del repositorio o entre recursos protegidos no inspeccionados.

## Referencias y duplicados

La búsqueda de referencias identificó usos explícitos de los corpus, perfiles, taxonomías y reglas RAG en `config/rag.php`, `config/rag_gate_v2.php` y componentes Laravel/Python. No se movió ningún recurso consumido por el programa.

Se calcularon hashes SHA256 de las 184 piezas documentales. Se encontró un único grupo duplicado exacto:

- `DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/git_diff_stat.txt`.
- `DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/review_git_diff_stat.txt`.

Ambos tienen 465 bytes y SHA256 `46ee2d6abf942554afb163e7b8bbf783f6afce449c4c807a6d8d00bf820c09cc`. Se conservan porque informes e inventarios históricos diferentes los referencian para distinguir fases de trabajo. La igualdad de contenido no demuestra ausencia de valor documental.

También se conservan `IMPLEMENTACION/04_embeddings_pgvector.diff` y `IMPLEMENTACION/05_compatibilidad_cromatica.diff`: sus guías enlazan explícitamente esos artefactos y otros informes registran su existencia. Se clasifican como C por su valor histórico y de reproducción.

Los dos ZIP autorizados no existen en el directorio, no aparecen en `git ls-files` y tampoco en las consultas específicas de archivos untracked/ignored. No se eliminó ni examinó ningún otro ZIP.

## Cachés D detectados y preservados

Estos recursos son regenerables, pero **se preservan** por la restricción expresa: «No borrar archivos ignorados por Git».

| Ruta | Evidencia | Decisión |
| --- | --- | --- |
| `.ruff_cache/` | 11 archivos, 3.370 bytes; regla interna `.gitignore` y marcador de caché | Preservar |
| `services/mati-ai/.ruff_cache/` | 4 archivos, 3.438 bytes; excluido por `.gitignore` raíz | Preservar |
| `services/mati-ai/.pytest_cache/` | 5 archivos, 25.943 bytes; excluido por `.gitignore` raíz | Preservar |
| `services/mati-ai/.mypy_cache/` | Caché de verificación de tipos; excluido por `.gitignore` raíz | Preservar |
| `.phpunit.result.cache` | Caché de resultados PHPUnit; excluido por `.gitignore` raíz | Preservar |

No se retiraron `node_modules`, `vendor`, `.venv`, compilados, pesos, variables de entorno, SQL ni backups. Los dumps SQL y `backups/` están ignorados y quedan protegidos; no se leyeron sus datos. Tampoco se exploraron galerías o almacenamiento persistente externo. Los recursos ignorados no están incluidos en las 832 filas y no se les asigna un total exhaustivo.

## Método y límites

Se usaron listados físicos de áreas de documentación y configuración, `git ls-files`, `git check-ignore`, búsquedas de referencias y hashes de documentación. La consulta `git ls-files --others --exclude-standard` no devolvió archivos sin seguimiento no ignorados en el momento de la auditoría inicial. No se encontraron archivos `AGENTS.md` en el repositorio ni en los ancestros consultados.

La lectura de Git en Windows funcionó para el inventario y las reglas de exclusión. `git status` de Windows produjo `unable to open .git/sequencer/todo: Function not implemented`; el primer intento de acceso WSL fue denegado por el sandbox. Posteriormente se autorizó el acceso WSL y se comprobaron correctamente rama, checkpoint y estado de Git, como documenta el informe general de defensa. Las incidencias iniciales no se interpretan como evidencia de archivos sobrantes.

La clasificación por inspección no certifica que cada función esté probada. Las comprobaciones del programa, correcciones, servicios y recorrido de presentación se documentan por separado. No se ejecutaron pruebas, migraciones, seeders, entrenamientos ni llamadas de inferencia como parte de esta auditoría de archivos.
