<?php

namespace App\Services\Superadmin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportPrintService
{
    public function __construct(private readonly ReportQueryService $reports)
    {
    }

    public function payload(string $type, Request $request): array
    {
        $dataset = $this->reports->exportDataset($type, $request);

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
