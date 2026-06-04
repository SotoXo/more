<?php

namespace App\Services\Superadmin;

use App\Models\User;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportPrintService
{
    public function __construct(
        private readonly ReportQueryService $reports,
        private readonly SystemLogService $logs,
    ) {
    }

    public function payload(string $type, Request $request): array
    {
        $dataset = $this->reports->exportDataset($type, $request);

        $this->logs->info(
            'reportes',
            'vista_imprimible_abierta',
            'Vista imprimible abierta: ' . $dataset['title'] . '.',
            null,
            [
                'tipo' => $type,
                'filas' => count($dataset['rows']),
                'filtros' => $this->reports->filtersMetadata($request),
            ],
        );

        return [
            'report' => [
                ...$dataset,
                'storeName' => 'Mati Boutique',
                'generatedAt' => Carbon::now()
                    ->timezone(config('app.timezone'))
                    ->format('d/m/Y H:i'),
                'generatedBy' => $this->userName($request->user()),
                'backHref' => '/superadmin/reportes' . ($request->getQueryString() ? '?' . $request->getQueryString() : ''),
            ],
        ];
    }

    private function userName(?User $user): string
    {
        return $user?->nombreCompleto() ?: ($user?->email ?? 'Sin usuario');
    }
}
