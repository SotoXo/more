# Validación frontend y Laravel

Fecha local: 2026-10-04. Se ejecutó después de entregar 00_como_funciona_toda_la_ia.md. No se corrigió código ni tests. Los resultados siguientes son ejecuciones reales de comandos; las pruebas con Http::fake se identifican como mocks y no como inferencia.

## Resultados

| Validación | Resultado observado | Evidencia |
|---|---|---|
| Helpers frontend existentes, todos | 23/23 pass, 0 fail, 146.32686 ms | storage/app/ai-audit/frontend_tests.log |
| Helpers específicos IA | 16/16 dentro de los 23; reglas/URLs/cola | tests/frontend/garmentAssistant.test.mjs |
| Build normal WSL, npm run build | exit 1, tsc completó; Vite transformó 2918 módulos y falló EACCES al limpiar public/build/assets root:root | frontend_build.log |
| Build normal desde contenedor, mismo source | exit 0; TypeScript + Vite 8.0.3, 2918 módulos; build Vite 768 ms | frontend_build_container.log |
| Asset actual por HTTP real | 200; 140221 bytes; SHA256 servido igual al disco | frontend_http_asset.json |
| MatiAiClientTest archivo explícito | 7/7 pass, 13 assertions, 0.48 s; Http::fake | laravel_ai_unit_tests.log |
| Workflow IA + full suite estándar | exit 255 antes de ejecutar; fatal Cannot override final method PHPUnit\\Framework\\TestCase::run() | laravel_ai_tests.log, laravel_full_baseline.log |
| Resto suite, excluye únicamente archivo no cargable | 95/95 pass, 493 assertions, 5.843 s; PHPUnit directo exit 0 | laravel_remaining_suite_direct.log |
| Concurrencia comercial PostgreSQL aislada | pass dentro de 95, dos procesos, sin base comercial | laravel_remaining_suite.log |
| Migraciones IA en aislado | tablas IA y vector reales presentes; migrate normal exit 0 Nothing to migrate después de suites | isolated_ai_schema.log, isolated_migration.log |
| Repetición decisión feedback | 2 invocaciones idénticas → 2 filas stage=form; predicción intacta; transacción rollback | feedback_idempotence_probe.json |

No hay test coverage medido: conteo de pruebas no es cobertura de líneas ni exactitud científica. Las 14 pruebas escritas de MatiAiWorkflowTest NO se ejecutaron por el error de carga, no se contaron como pass ni se parcheó su helper run.

## Source frente a build

Antes de build: public/hot ausente, manifest apuntaba ProductCreate-Br6F6_ny.js (26/09). SHA256 b46b5faa4a834fdacd1a5d854a65216f2c4dc17403bf12cb61caad2f8f790b34. No contenía «Asistente inteligente», «Colores de la prenda», «Prendas similares por fotografía», «Colorimetría de la prenda» ni sessionStorage de reviews. Evidencia JSON/manifest preservados antes del build. FE-01 P1 describe la situación inicial real.

El user exige explícitamente apartado53 «Ejecuta el build normal del proyecto». Ese build fue ejecutado sin source/config edits. El primer intento falló por owner root de assets. El segundo se ejecutó desde more-laravel.test-1 propietario de esos artefactos, sin chown ni parche. Manifest actual apunta ProductCreate-tYfO0olu.js con SHA256 f6eb4220d78745726c7016330fc3dfe2c081d07850125ae523ff675275551ffc y las cinco cadenas presentes. HTTP http://localhost/build/assets/ProductCreate-tYfO0olu.js devuelve exactamente los bytes actuales. FE-01 no describe ya el estado postbuild; la auditoría preserva ambos estados.

public/build está ignorado por Git; regenerarlo no añade diff de source. No se hizo commit, push, reset, restore ni cambio de branch. Este build autorizado actualizó los assets servidos; no se ocultó la discrepancia inicial.

## Entorno observado

- Node WSL v24.14.1; npm WSL 11.11.0.
- Node more-laravel.test-1 v24.14.1; npm 11.13.0; PHP 8.5.5; Xdebug3.5.0; Laravel13.1.1; PHPUnit12.5.14.
- Base principal no se tocó: servidor comercial more-pgsql-1 usa PostgreSQL18.3 Alpine, no extensión vector ni tablas IA aplicadas (auditoría datos separada).
- Base pruebas: more-ai-audit-pg-20261004, PostgreSQL18.3 Debian, pgvector0.8.2 (imagen local cacheada pgvector/pgvector:pg18-trixie), database=testing, sin puertos publicados, tmpfs/sin volumen comercial, password aleatorio no mostrado. No representa todavía validar imagen repo vector0.8.7.
- Se impusieron DB_HOST aislado/DB_DATABASE=testing/DB_URL vacío y se comprobaron config y consulta live current_database/server antes de tests. Host verificado more-ai-audit-pg-20261004, IP172.19.0.5.
- Wrapper artisan --configuration produce warning opción repetida y exit1 pese a95pass. Por ello se repitió PHPUnit directo con XML auditoría, obteniendo exit0; no se cambió phpunit.xml histórico.

## Hallazgo nuevo FE-08

P1, validación de integración Laravel IA. Archivo tests/Feature/MatiAiWorkflowTest.php:94 define private function run(string $operation,...): AiRun, nombre reservado por PHPUnit12 cuyo TestCase::run es final. Carga suite fatal255 incluso al usar --filter. Reproducir php artisan test --filter='MatiAi(Client|Workflow)Test' o suite estándar. Impacto: suite completa estándar y 14 casos workflow son no ejecutables. Propuesta futura: renombrar helper y referencias en un cambio separado. No se implementó.

## FE-03 confirmación runtime

AiReviewService::recordFeedback crea eventos form repetidos para decisión idéntica. Probe sintético en PostgreSQL aislado usa un run/suggestion temporal bajo transacción finalmente rollback: 2 llamadas, 2 form reviews. El unique form_review_id deduplica saved por revisión concreta, no decisión del navegador. Sin idempotency key un timeout después de commit puede repetir la decisión; aceptación/corrección deben agregarse por última decisión válida o deduplicarse, no contar filas como ground truth. Predicción original permaneció intacta.

## Instancia E2E separada

Se levantó more-ai-audit-laravel-20261004 en http://localhost:8088 con misma fuente/build y DB de auditoría. Usuario audit@example.test sintético id20 superadmin, sesión con cookie mati_ai_audit_session distinta. Tokens sólo leídos de archivo privado /tmp y entorno, no logs/documentación. FastAPI real root http://host.docker.internal:8001/health fue accesible del contenedor.

La imagen original principal sha590393… ya no estaba en el cache Docker para clonarla. Primer docker run con esa imagen falló sin crear app; se usó sail-8.5/app:latest disponible. App E2E corre PHP8.5.9/Xdebug3.5.3 frente a tests/principal PHP8.5.5; misma fuente/dependencias montadas. Esta diferencia impide equiparar sin matiz entorno comercial y E2E aislado. Login endpoint aislado HTTP200. Cuenta real no fue modificada/reset ni usada para favorecer métricas.

El agente de datos copia sólo catálogo real por SELECT read-only a aislado, sustituyendo autor por usuario20 en destino y sin copiar usuarios/PII/ventas/pagos. La integración navegador final se añade en 07_auditoria_integracion_e2e.md por auditor principal.

