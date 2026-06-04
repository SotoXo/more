<?php

namespace App\Services\Superadmin;

use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(
        private readonly ReportQueryService $reports,
        private readonly SystemLogService $logs,
    ) {
    }

    public function csvResponse(string $type, Request $request): StreamedResponse
    {
        $dataset = $this->reports->exportDataset($type, $request);

        $this->logs->info(
            'exportaciones',
            'csv_exportado',
            'CSV exportado: ' . $dataset['title'] . '.',
            null,
            [
                'tipo' => $type,
                'filename' => $dataset['filename'],
                'filas' => count($dataset['rows']),
                'filtros' => $this->reports->filtersMetadata($request),
            ],
        );

        return response()->streamDownload(function () use ($dataset) {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_column($dataset['columns'], 'label'), ';');

            foreach ($dataset['rows'] as $row) {
                fputcsv($output, $this->rowValues($dataset['columns'], $row), ';');
            }

            fclose($output);
        }, $dataset['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function rowValues(array $columns, array $row): array
    {
        return collect($columns)
            ->map(fn (array $column) => $this->sanitizeCell($row[$column['key']] ?? ''))
            ->values()
            ->all();
    }

    private function sanitizeCell($value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', (string) $value);

        if (preg_match('/^[=+\-@\t]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }
}
