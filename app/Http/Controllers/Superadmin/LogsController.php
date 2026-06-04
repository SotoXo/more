<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderLogs($request);
    }

    public function show(Request $request, SystemLog $log)
    {
        $log->loadMissing('usuario.rol');

        return $this->renderLogs($request, $log);
    }

    private function renderLogs(Request $request, ?SystemLog $selectedLog = null)
    {
        $logs = $this->filteredQuery($request)
            ->with('usuario.rol')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Superadmin/Logs', [
            'metrics' => $this->metrics(),
            'logs' => [
                'data' => $logs->getCollection()
                    ->map(fn (SystemLog $log) => $this->serializeRow($log, $request))
                    ->values()
                    ->all(),
                'links' => $logs->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'from' => $logs->firstItem(),
                    'last_page' => $logs->lastPage(),
                    'path' => $logs->path(),
                    'per_page' => $logs->perPage(),
                    'to' => $logs->lastItem(),
                    'total' => $logs->total(),
                ],
            ],
            'filters' => [
                'fecha_desde' => $request->string('fecha_desde')->toString(),
                'fecha_hasta' => $request->string('fecha_hasta')->toString(),
                'usuario' => $request->string('usuario')->toString(),
                'modulo' => $request->string('modulo')->toString(),
                'accion' => $request->string('accion')->toString(),
                'nivel' => $request->string('nivel')->toString(),
                'buscar' => $request->string('buscar')->toString(),
            ],
            'options' => [
                'users' => $this->userOptions(),
                'modules' => $this->simpleOptions('modulo_slg'),
                'actions' => $this->simpleOptions('accion_slg'),
                'levels' => [
                    ['label' => 'Info', 'value' => 'info'],
                    ['label' => 'Warning', 'value' => 'warning'],
                    ['label' => 'Critical', 'value' => 'critical'],
                ],
            ],
            'selectedLog' => $selectedLog ? $this->serializeDetail($selectedLog) : null,
            'closeDetailHref' => $this->indexHrefWithQuery($request),
        ]);
    }

    private function filteredQuery(Request $request)
    {
        $query = SystemLog::query();

        if ($request->filled('fecha_desde')) {
            $date = $this->dateBoundary((string) $request->input('fecha_desde'), 'start');

            if ($date) {
                $query->where('created_at', '>=', $date);
            }
        }

        if ($request->filled('fecha_hasta')) {
            $date = $this->dateBoundary((string) $request->input('fecha_hasta'), 'end');

            if ($date) {
                $query->where('created_at', '<=', $date);
            }
        }

        if ($request->filled('usuario')) {
            $query->where('cod_usuario_slg', (int) $request->input('usuario'));
        }

        if ($request->filled('modulo')) {
            $query->where('modulo_slg', $request->input('modulo'));
        }

        if ($request->filled('accion')) {
            $query->where('accion_slg', $request->input('accion'));
        }

        if ($request->filled('nivel')) {
            $query->where('nivel_slg', $request->input('nivel'));
        }

        if ($request->filled('buscar')) {
            $search = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $request->input('buscar')) . '%';
            $query->where('descripcion_slg', 'like', $search);
        }

        return $query;
    }

    private function metrics(): array
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();
        $latestUserLog = SystemLog::query()
            ->with('usuario.rol')
            ->whereNotNull('cod_usuario_slg')
            ->orderByDesc('created_at')
            ->first();
        $topModule = SystemLog::query()
            ->select('modulo_slg')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('modulo_slg')
            ->orderByDesc('total')
            ->first();

        return [
            [
                'label' => 'Logs de hoy',
                'value' => (string) SystemLog::query()->whereDate('created_at', $today)->count(),
                'note' => 'Acciones registradas en la jornada.',
                'tone' => 'accent',
            ],
            [
                'label' => 'Acciones criticas',
                'value' => (string) SystemLog::query()->where('nivel_slg', 'critical')->count(),
                'note' => 'Eventos marcados con maxima importancia.',
                'tone' => 'danger',
            ],
            [
                'label' => 'Acciones warning',
                'value' => (string) SystemLog::query()->where('nivel_slg', 'warning')->count(),
                'note' => 'Eventos que requieren seguimiento.',
                'tone' => 'warning',
            ],
            [
                'label' => 'Ultimo usuario activo',
                'value' => $latestUserLog ? Str::limit($this->userName($latestUserLog), 18, '') : 'Sin actividad',
                'note' => $latestUserLog?->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Sin logs de usuario.',
                'tone' => 'info',
            ],
            [
                'label' => 'Modulo con mas actividad',
                'value' => $topModule ? $this->humanize((string) $topModule->modulo_slg) : 'Sin datos',
                'note' => $topModule ? ((string) $topModule->total . ' logs registrados.') : 'Aun no hay actividad.',
                'tone' => 'success',
            ],
        ];
    }

    private function serializeRow(SystemLog $log, Request $request): array
    {
        $date = $log->created_at?->timezone(config('app.timezone'));

        return [
            'id' => $log->id,
            'code' => 'LOG-' . str_pad((string) $log->id, 5, '0', STR_PAD_LEFT),
            'date' => $date?->format('d/m/Y H:i') ?? 'Sin fecha',
            'user' => $this->userName($log),
            'role' => $log->rol_slg ?: 'Sin rol',
            'module' => $log->modulo_slg,
            'moduleLabel' => $this->humanize($log->modulo_slg),
            'action' => $log->accion_slg,
            'actionLabel' => $this->humanize($log->accion_slg),
            'description' => $log->descripcion_slg,
            'entity' => $this->entityLabel($log),
            'level' => $log->nivel_slg,
            'levelLabel' => Str::upper($log->nivel_slg),
            'tone' => $this->levelTone($log->nivel_slg),
            'ip' => $log->ip_slg ?: 'Sin IP',
            'route' => $log->ruta_slg ?: 'Sin ruta',
            'detailHref' => $this->detailHref($log, $request),
        ];
    }

    private function serializeDetail(SystemLog $log): array
    {
        $date = $log->created_at?->timezone(config('app.timezone'));

        return [
            'id' => $log->id,
            'code' => 'LOG-' . str_pad((string) $log->id, 5, '0', STR_PAD_LEFT),
            'date' => $date?->format('d/m/Y H:i:s') ?? 'Sin fecha',
            'user' => $this->userName($log),
            'role' => $log->rol_slg ?: 'Sin rol',
            'module' => $this->humanize($log->modulo_slg),
            'action' => $this->humanize($log->accion_slg),
            'description' => $log->descripcion_slg,
            'entity' => $this->entityLabel($log),
            'level' => Str::upper($log->nivel_slg),
            'tone' => $this->levelTone($log->nivel_slg),
            'ip' => $log->ip_slg ?: 'Sin IP',
            'route' => $log->ruta_slg ?: 'Sin ruta',
            'method' => $log->metodo_http_slg ?: 'Sin metodo',
            'userAgent' => $log->user_agent_slg ?: 'Sin user agent',
            'metadata' => $log->metadata_slg ?: [],
        ];
    }

    private function userOptions(): array
    {
        $userIds = SystemLog::query()
            ->whereNotNull('cod_usuario_slg')
            ->distinct()
            ->pluck('cod_usuario_slg');

        return User::query()
            ->with('rol')
            ->whereIn('id', $userIds)
            ->orderBy('nom_usu')
            ->get()
            ->map(fn (User $user) => [
                'label' => $user->nombreCompleto() . ' - ' . ($user->rol?->nom_rol ?? 'Sin rol'),
                'value' => (string) $user->id,
            ])
            ->values()
            ->all();
    }

    private function simpleOptions(string $column): array
    {
        return SystemLog::query()
            ->select($column)
            ->distinct()
            ->whereNotNull($column)
            ->orderBy($column)
            ->pluck($column)
            ->map(fn (string $value) => [
                'label' => $this->humanize($value),
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    private function dateBoundary(string $value, string $boundary): ?Carbon
    {
        try {
            $date = Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }

        return $boundary === 'end' ? $date->endOfDay() : $date->startOfDay();
    }

    private function userName(SystemLog $log): string
    {
        return $log->usuario?->nombreCompleto()
            ?: ($log->usuario?->email ?? ($log->cod_usuario_slg ? 'Usuario #' . $log->cod_usuario_slg : 'Sistema / invitado'));
    }

    private function entityLabel(SystemLog $log): string
    {
        if (!$log->entidad_tipo_slg || !$log->entidad_id_slg) {
            return 'Sin entidad';
        }

        return $this->humanize($log->entidad_tipo_slg) . ' #' . $log->entidad_id_slg;
    }

    private function humanize(string $value): string
    {
        $text = str_replace('_', ' ', trim($value));

        return Str::ucfirst($text);
    }

    private function levelTone(string $level): string
    {
        return match ($level) {
            'critical' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }

    private function detailHref(SystemLog $log, Request $request): string
    {
        $query = $request->query();
        unset($query['page']);

        return '/superadmin/logs/' . $log->id . ($query ? '?' . http_build_query($query) : '');
    }

    private function indexHrefWithQuery(Request $request): string
    {
        $query = $request->query();

        return '/superadmin/logs' . ($query ? '?' . http_build_query($query) : '');
    }
}
