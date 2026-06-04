<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderAudit($request);
    }

    public function show(Request $request, AuditLog $auditLog)
    {
        $auditLog->loadMissing('usuario.rol');

        return $this->renderAudit($request, $auditLog);
    }

    private function renderAudit(Request $request, ?AuditLog $selectedLog = null)
    {
        $auditLogs = $this->filteredQuery($request)
            ->with('usuario.rol')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Superadmin/Auditoria', [
            'metrics' => $this->metrics(),
            'auditLogs' => [
                'data' => $auditLogs->getCollection()
                    ->map(fn (AuditLog $log) => $this->serializeRow($log, $request))
                    ->values()
                    ->all(),
                'links' => $auditLogs->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $auditLogs->currentPage(),
                    'from' => $auditLogs->firstItem(),
                    'last_page' => $auditLogs->lastPage(),
                    'path' => $auditLogs->path(),
                    'per_page' => $auditLogs->perPage(),
                    'to' => $auditLogs->lastItem(),
                    'total' => $auditLogs->total(),
                ],
            ],
            'filters' => [
                'fecha_desde' => $request->string('fecha_desde')->toString(),
                'fecha_hasta' => $request->string('fecha_hasta')->toString(),
                'usuario' => $request->string('usuario')->toString(),
                'tabla' => $request->string('tabla')->toString(),
                'accion' => $request->string('accion')->toString(),
                'modulo' => $request->string('modulo')->toString(),
                'nivel' => $request->string('nivel')->toString(),
                'buscar' => $request->string('buscar')->toString(),
            ],
            'options' => [
                'users' => $this->userOptions(),
                'tables' => $this->simpleOptions('tabla_aul'),
                'actions' => $this->simpleOptions('accion_aul'),
                'modules' => $this->simpleOptions('modulo_aul'),
                'levels' => [
                    ['label' => 'Info', 'value' => 'info'],
                    ['label' => 'Warning', 'value' => 'warning'],
                    ['label' => 'Critical', 'value' => 'critical'],
                ],
            ],
            'selectedAuditLog' => $selectedLog ? $this->serializeDetail($selectedLog) : null,
            'closeDetailHref' => $this->indexHrefWithQuery($request),
        ]);
    }

    private function filteredQuery(Request $request)
    {
        $query = AuditLog::query();

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
            $query->where('cod_usuario_aul', (int) $request->input('usuario'));
        }

        if ($request->filled('tabla')) {
            $query->where('tabla_aul', $request->input('tabla'));
        }

        if ($request->filled('accion')) {
            $query->where('accion_aul', $request->input('accion'));
        }

        if ($request->filled('modulo')) {
            $query->where('modulo_aul', $request->input('modulo'));
        }

        if ($request->filled('nivel')) {
            $query->where('nivel_aul', $request->input('nivel'));
        }

        if ($request->filled('buscar')) {
            $search = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $request->input('buscar')) . '%';
            $query->where('descripcion_aul', 'like', $search);
        }

        return $query;
    }

    private function metrics(): array
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();
        $topTable = AuditLog::query()
            ->select('tabla_aul')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('tabla_aul')
            ->orderByDesc('total')
            ->first();

        return [
            [
                'label' => 'Auditorias de hoy',
                'value' => (string) AuditLog::query()->whereDate('created_at', $today)->count(),
                'note' => 'Cambios criticos registrados en la jornada.',
                'tone' => 'accent',
            ],
            [
                'label' => 'Cambios criticos',
                'value' => (string) AuditLog::query()->where('nivel_aul', 'critical')->count(),
                'note' => 'Eventos de alta sensibilidad.',
                'tone' => 'danger',
            ],
            [
                'label' => 'Actualizaciones',
                'value' => (string) AuditLog::query()->where('accion_aul', 'updated')->count(),
                'note' => 'Registros con antes y despues.',
                'tone' => 'warning',
            ],
            [
                'label' => 'Tabla mas auditada',
                'value' => $topTable ? $this->humanize((string) $topTable->tabla_aul) : 'Sin datos',
                'note' => $topTable ? ((string) $topTable->total . ' eventos registrados.') : 'Aun no hay auditoria.',
                'tone' => 'info',
            ],
        ];
    }

    private function serializeRow(AuditLog $log, Request $request): array
    {
        $date = $log->created_at?->timezone(config('app.timezone'));

        return [
            'id' => $log->id,
            'code' => 'AUD-' . str_pad((string) $log->id, 5, '0', STR_PAD_LEFT),
            'date' => $date?->format('d/m/Y H:i') ?? 'Sin fecha',
            'user' => $this->userName($log),
            'table' => $log->tabla_aul,
            'tableLabel' => $this->humanize($log->tabla_aul),
            'record' => $log->registro_id_aul ? ('#' . $log->registro_id_aul) : 'Sin registro',
            'action' => $log->accion_aul,
            'actionLabel' => $this->humanize($log->accion_aul),
            'module' => $log->modulo_aul,
            'moduleLabel' => $this->humanize($log->modulo_aul),
            'level' => $log->nivel_aul,
            'levelLabel' => Str::upper($log->nivel_aul),
            'tone' => $this->levelTone($log->nivel_aul),
            'fields' => $log->campos_modificados_aul ?: [],
            'fieldsLabel' => $this->fieldsLabel($log->campos_modificados_aul ?: []),
            'description' => $log->descripcion_aul ?: 'Sin descripcion.',
            'detailHref' => $this->detailHref($log, $request),
        ];
    }

    private function serializeDetail(AuditLog $log): array
    {
        $date = $log->created_at?->timezone(config('app.timezone'));

        return [
            'id' => $log->id,
            'code' => 'AUD-' . str_pad((string) $log->id, 5, '0', STR_PAD_LEFT),
            'date' => $date?->format('d/m/Y H:i:s') ?? 'Sin fecha',
            'user' => $this->userName($log),
            'table' => $this->humanize($log->tabla_aul),
            'record' => $log->registro_id_aul ? ('#' . $log->registro_id_aul) : 'Sin registro',
            'action' => $this->humanize($log->accion_aul),
            'module' => $this->humanize($log->modulo_aul),
            'level' => Str::upper($log->nivel_aul),
            'tone' => $this->levelTone($log->nivel_aul),
            'description' => $log->descripcion_aul ?: 'Sin descripcion.',
            'fields' => $log->campos_modificados_aul ?: [],
            'oldValues' => $log->valores_anteriores_aul ?: [],
            'newValues' => $log->valores_nuevos_aul ?: [],
            'ip' => $log->ip_aul ?: 'Sin IP',
            'route' => $log->ruta_aul ?: 'Sin ruta',
            'userAgent' => $log->user_agent_aul ?: 'Sin user agent',
        ];
    }

    private function userOptions(): array
    {
        $userIds = AuditLog::query()
            ->whereNotNull('cod_usuario_aul')
            ->distinct()
            ->pluck('cod_usuario_aul');

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
        return AuditLog::query()
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

    private function userName(AuditLog $log): string
    {
        return $log->usuario?->nombreCompleto()
            ?: ($log->usuario?->email ?? ($log->cod_usuario_aul ? 'Usuario #' . $log->cod_usuario_aul : 'Sistema'));
    }

    private function humanize(string $value): string
    {
        return Str::ucfirst(str_replace('_', ' ', trim($value)));
    }

    private function fieldsLabel(array $fields): string
    {
        if ($fields === []) {
            return 'Sin campos';
        }

        return implode(', ', array_map(fn (string $field) => $this->humanize($field), $fields));
    }

    private function levelTone(string $level): string
    {
        return match ($level) {
            'critical' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }

    private function detailHref(AuditLog $log, Request $request): string
    {
        $query = $request->query();
        unset($query['page']);

        return '/superadmin/auditoria/' . $log->id . ($query ? '?' . http_build_query($query) : '');
    }

    private function indexHrefWithQuery(Request $request): string
    {
        $query = $request->query();

        return '/superadmin/auditoria' . ($query ? '?' . http_build_query($query) : '');
    }
}
