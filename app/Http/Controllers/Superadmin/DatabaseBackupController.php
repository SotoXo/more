<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backups,
        private readonly SystemLogService $logs,
    ) {
    }

    public function index(Request $request)
    {
        $history = DatabaseBackup::query()
            ->with('usuario.rol')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Superadmin/Backups', [
            'metrics' => $this->metrics(),
            'backups' => [
                'data' => $history->getCollection()
                    ->map(fn (DatabaseBackup $backup) => $this->serializeBackup($backup))
                    ->values()
                    ->all(),
                'links' => $history->linkCollection()->toArray(),
                'meta' => [
                    'from' => $history->firstItem(),
                    'to' => $history->lastItem(),
                    'total' => $history->total(),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $backup = $this->backups->createManualBackup($request->user());

        if ($backup->estado_bak !== 'exitoso') {
            return redirect()
                ->route('superadmin.backups')
                ->with('error', 'No se pudo generar el backup. Revisa el historial para ver el detalle.');
        }

        return redirect()
            ->route('superadmin.backups')
            ->with('success', 'Backup generado correctamente.');
    }

    public function download(DatabaseBackup $backup): BinaryFileResponse|RedirectResponse
    {
        if (!$this->backups->isDownloadable($backup)) {
            return redirect()
                ->route('superadmin.backups')
                ->with('error', 'El backup no existe o no está disponible para descarga.');
        }

        $this->logs->info(
            'backups',
            'backup_descargado',
            'Backup de base de datos descargado.',
            $backup,
            [
                'backup_id' => $backup->id,
                'filename' => $backup->archivo_bak,
                'tamano_bytes' => $backup->tamano_bak,
                'estado' => $backup->estado_bak,
            ],
        );

        return response()->download($this->backups->absolutePath($backup), $backup->archivo_bak, [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function metrics(): array
    {
        $successful = DatabaseBackup::query()
            ->where('estado_bak', 'exitoso')
            ->orderByDesc('fec_bak')
            ->first();
        $totalSize = (int) DatabaseBackup::query()
            ->where('estado_bak', 'exitoso')
            ->sum('tamano_bak');

        return [
            [
                'label' => 'Total backups',
                'value' => (string) DatabaseBackup::query()->count(),
                'note' => 'Registros históricos generados.',
                'tone' => 'accent',
            ],
            [
                'label' => 'Último exitoso',
                'value' => $successful?->fec_bak?->timezone(config('app.timezone'))->format('d/m H:i') ?? 'Sin backup',
                'note' => $successful?->archivo_bak ?? 'Todavía no hay respaldos exitosos.',
                'tone' => 'success',
            ],
            [
                'label' => 'Fallidos',
                'value' => (string) DatabaseBackup::query()->where('estado_bak', 'fallido')->count(),
                'note' => 'Intentos que requieren revisión.',
                'tone' => 'warning',
            ],
            [
                'label' => 'Tamaño total',
                'value' => $this->humanSize($totalSize),
                'note' => 'Suma aproximada de backups exitosos.',
                'tone' => 'info',
            ],
        ];
    }

    private function serializeBackup(DatabaseBackup $backup): array
    {
        $date = $backup->fec_bak ?: $backup->created_at;

        return [
            'id' => $backup->id,
            'date' => $date?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Sin fecha',
            'file' => $backup->archivo_bak,
            'name' => $backup->nombre_bak,
            'user' => $backup->usuario?->nombreCompleto() ?: ($backup->usuario?->email ?? 'Sistema'),
            'size' => $backup->tamano_bak ? $this->humanSize((int) $backup->tamano_bak) : 'Sin tamaño',
            'sizeBytes' => $backup->tamano_bak,
            'status' => $backup->estado_bak,
            'statusLabel' => $this->statusLabel($backup->estado_bak),
            'tone' => $this->statusTone($backup->estado_bak),
            'message' => $backup->mensaje_bak ?: 'Sin mensaje.',
            'downloadHref' => $backup->estado_bak === 'exitoso'
                ? route('superadmin.backups.download', $backup)
                : null,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'exitoso' => 'Exitoso',
            'fallido' => 'Fallido',
            default => 'Pendiente',
        };
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'exitoso' => 'success',
            'fallido' => 'danger',
            default => 'warning',
        };
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }
}
