# Arranque y parada de MORE — entorno existente

Verificado el 11 de octubre de 2026 en WSL Ubuntu, `/home/soto/Code/more`. Durante la auditoría los cuatro contenedores ya estaban funcionando; no se ejecutó arranque, parada, recreación ni reinicio. Los comandos de control siguientes corresponden a la configuración y los contenedores inspeccionados, pero no se ensayó un arranque en frío para evitar interrumpirlos.

## Puertos y requisitos observados

| Servicio | Dirección real | Estado observado |
| --- | --- | --- |
| Laravel | `http://localhost` (80) | `/up` y `/login`: HTTP 200 |
| Frontend | Build servido por Laravel; 5173 publicado para Vite | `public/hot` ausente; build TypeScript/Vite correcto |
| PostgreSQL | Host 5433, contenedores `pgsql:5432` | PostgreSQL 18.6 + pgvector 0.8.7, healthy |
| Redis | Host 6379, contenedores `redis:6379` | healthy |
| Python IA | `http://mati-ai:8000`, solo red Docker | healthy; inferencia SigLIP CUDA real |

Python no publica 8001 ni 8000 en el host. El contenedor IA actual usa `compose.yaml` y `compose.ai-gpu.yaml`, con GPU NVIDIA. Se conservan `.env`, dependencias instaladas, imágenes Docker, base comercial, volúmenes y pesos locales. Esta guía inicia el entorno existente detenido; no provisiona una máquina vacía ni una base nueva.

## 1. Abrir WSL

En PowerShell:

```powershell
wsl.exe -d Ubuntu
```

Los demás bloques son Bash dentro de Ubuntu:

```bash
cd /home/soto/Code/more
export WWWUSER="$(id -u)"
export WWWGROUP="$(id -g)"
more_compose() {
  docker compose -f compose.yaml -f compose.ai-gpu.yaml --profile ai "$@"
}
more_compose ps -a
```

Docker Desktop debe estar iniciado y tener integración con Ubuntu. Se espera encontrar los contenedores `more-pgsql-1`, `more-redis-1`, `more-mati-ai-1` y `more-laravel.test-1`. Si ya están `running` y los tres servicios con healthcheck están `healthy`, ir directamente a la comprobación. Si faltan contenedores, no improvisar una instalación antes de la defensa: esta guía no autoriza recrear la base o sustituir volúmenes.

## 2. Iniciar únicamente los contenedores existentes detenidos

```bash
more_compose start pgsql redis
more_compose ps pgsql redis
```

Esperar a que ambos indiquen `healthy`; repetir `ps` si aún aparecen iniciando. Después:

```bash
more_compose start mati-ai
more_compose ps mati-ai
```

Esperar `healthy`. Después:

```bash
more_compose start laravel.test
more_compose ps
```

`start` conserva los contenedores y sus volúmenes, y no aplica cambios de Compose. Supervisor arranca Laravel dentro del contenedor; Uvicorn arranca Python dentro de `mati-ai`. No se necesita `artisan serve`, instalar paquetes, migrar o ejecutar seeders. Si la GPU impide iniciar IA, presentar los módulos administrativos y la tienda y declarar IA temporalmente no disponible; no cambiar el baseline para disimularlo.

## 3. Comprobar disponibilidad

```bash
more_compose exec -T redis redis-cli ping
curl -fsS -o /dev/null -w 'Laravel /up: HTTP %{http_code}\n' http://localhost/up
more_compose exec -T laravel.test curl -fsS http://mati-ai:8000/health
```

Resultados esperados: `PONG`, HTTP 200 y JSON de salud de Python. La salud de Python por sí sola no demuestra carga o calidad del modelo. Para una inferencia real sin persistir resultados:

```bash
more_compose exec -T -e LOG_CHANNEL=null laravel.test php DOCUMENTACION/DEFENSA_20261011/verificar_ia_sin_persistencia.php --analyze
```

Este último comando se ejecutó correctamente en la sesión: usa la imagen existente 136, consultas PostgreSQL en transacción READ ONLY y rollback; llama al cliente Laravel–Python directamente, sin `AiController`, `ai_runs`, guardado de vectores ni modificación de imágenes. La salida real puede ser `partial` y abstenerse; no es una clasificación garantizada. Si esa imagen se retira posteriormente, el script fallará de forma visible y deberá adaptarse fuera del alcance de esta revisión.

## 4. Frontend

El build ya quedó actualizado. Para recompilar si se modifican fuentes:

```bash
more_compose exec -T laravel.test npm run build
```

Comando ejecutado y correcto: TypeScript + Vite 8.0.3. No se necesita iniciar Vite para la defensa en el modo actual. Abrir [catálogo](http://localhost/catalogo), [inicio de sesión](http://localhost/login) y [panel](http://localhost/superadmin). Usar las credenciales existentes; no generar usuarios ni compartir contraseñas.

## 5. Detener sin borrar datos

Al terminar la presentación, únicamente si se desea detener el entorno:

```bash
more_compose stop
```

No se ejecutó en esta sesión. No usar `down -v`, limpieza de Docker, reset de almacenamiento ni migraciones para arrancar o detener la presentación.

## Recuperar la corrección de código

No ejecutar automáticamente. HEAD ya permanece en `709bae2`; lo nuevo son cambios locales. Primero conservarlos fuera del repositorio:

```bash
git diff --binary --output=/tmp/more-defensa-20261011.patch
tar -czf /tmp/more-defensa-documentacion-20261011.tar.gz DOCUMENTACION/DEFENSA_20261011
```

Después, si se decide retirar exclusivamente la corrección de esta sesión:

```bash
git restore --source=709bae2 --worktree -- \
  app/Services/Superadmin/Resources/ResourceValidationService.php \
  resources/js/Pages/Superadmin/ProductCreate.tsx \
  tests/Feature/InventoryP0RegressionTest.php
more_compose exec -T laravel.test npm run build
```

La rama de trabajo y el respaldo permanecen en su sitio, los entregables nuevos se conservan y no se restaura `.env`, la base de datos ni las imágenes. Esta restauración devuelve también el bloqueo conocido de edición de prendas agotadas. El checkpoint Git no recupera recursos ignorados ni almacenamiento externo; para ellos hacen falta respaldos independientes existentes.
