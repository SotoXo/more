<?php

namespace App\Console\Commands;

use App\Models\Prenda;
use App\Models\User;
use App\Services\CatalogRealImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ImportRealCatalog extends Command
{
    protected $signature = 'catalog:import-real {manifest : Ruta al manifiesto JSON} {--dry-run : Validar sin guardar datos comerciales} {--user= : ID del superadministrador existente responsable} {--report= : Ruta del reporte JSON}';

    protected $description = 'Importa prendas reales desde archivos locales reutilizando el dominio del alta, sin IA.';

    public function handle(CatalogRealImportService $importer): int
    {
        $directory = storage_path('app/catalog-import');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $lock = fopen($directory.'/.import.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            $this->error('Ya hay una importación en curso.');

            return self::FAILURE;
        }
        try {
            return $this->runImport($importer);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function runImport(CatalogRealImportService $importer): int
    {
        $manifest = realpath($this->absolutePath((string) $this->argument('manifest')));
        if (! $manifest || ! is_file($manifest)) {
            throw new \RuntimeException('No existe el manifiesto.');
        }
        $entries = json_decode(ltrim(file_get_contents($manifest), "\xEF\xBB\xBF"), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($entries) || ! array_is_list($entries) || ! $entries) {
            throw new \RuntimeException('El manifiesto debe ser una lista no vacía de prendas.');
        }
        $actors = User::query()->with('rol')->where('est_usu', true)->get()->filter(fn ($user) => $user->isSuperadmin());
        $actor = $this->option('user') ? $actors->firstWhere('id', (int) $this->option('user')) : ($actors->count() === 1 ? $actors->first() : null);
        if (! $actor) {
            throw new \RuntimeException('Indica --user con un superadministrador activo existente.');
        }
        Auth::setUser($actor);
        $dryRun = (bool) $this->option('dry-run');
        $target = $this->option('report')
            ? $this->absolutePath((string) $this->option('report'))
            : storage_path('app/catalog-import/'.($dryRun ? 'dry_run_report.json' : 'import_report.json'));
        $reportRoot = realpath(storage_path('app/catalog-import'));
        $parent = realpath(dirname($target));
        if ($parent !== $reportRoot || ! preg_match('/^(?:[a-z0-9]+_)*report\.json$/i', basename($target)) || realpath($target) === $manifest) {
            throw new \RuntimeException('El reporte debe ser un archivo *_report.json en catalog-import y no puede sobrescribir el manifiesto.');
        }
        $report = [
            'manifest' => $manifest, 'manifest_sha256' => hash_file('sha256', $manifest),
            'started_at' => now()->toIso8601String(), 'dry_run' => $dryRun,
            'initial_count' => Prenda::query()->count(), 'candidates' => count($entries),
            'valid' => [], 'invalid' => [], 'imported' => [], 'skipped_duplicates' => [], 'failed' => [], 'warnings' => [],
        ];
        $preparedRows = [];
        $seen = [];
        foreach ($entries as $index => $entry) {
            $summary = $this->summary(is_array($entry) ? $entry : [], $index);
            try {
                if (! is_array($entry)) {
                    throw new \RuntimeException('El candidato no es un objeto.');
                }
                $prepared = $importer->prepare($entry, $actor);
                $key = $prepared['key'];
                if ($prepared['duplicate'] || isset($seen[$key])) {
                    $report['skipped_duplicates'][] = $summary + [
                        'product_id' => $prepared['duplicate']?->getKey(),
                        'reason' => $prepared['duplicate'] ? 'existing_brand_reference_or_import_key' : 'same_identity_in_manifest',
                    ];

                    continue;
                }
                $seen[$key] = true;
                $preparedRows[] = [$prepared, $summary];
                $report['valid'][] = $summary + ['import_key' => $key];
                foreach ($prepared['warnings'] as $warning) {
                    $report['warnings'][] = ['index' => $index, 'warning' => $warning];
                }
            } catch (\Throwable $exception) {
                $report['invalid'][] = $summary + ['errors' => $exception instanceof ValidationException ? $exception->errors() : [$exception->getMessage()]];
            }
        }

        // The complete preflight must pass before the first product is persisted.
        if (! $dryRun && ! $report['invalid']) {
            foreach ($preparedRows as [$prepared, $summary]) {
                try {
                    $product = $importer->import($prepared);
                    $report['imported'][] = $summary + [
                        'product_id' => $product->getKey(), 'import_key' => $prepared['key'],
                        'stored_images' => $product->imagenes->map(fn ($image) => [
                            'id' => $image->getKey(), 'path' => $image->rut_igp, 'url' => $image->url_igp,
                        ])->all(),
                        'variants' => $product->variantes->map(fn ($variant) => [
                            'id' => $variant->getKey(), 'sku' => $variant->sku_prv,
                            'cdi_inv' => $variant->inventario?->cdi_inv, 'cre_inv' => $variant->inventario?->cre_inv,
                        ])->all(),
                    ];
                    $this->line('Importada #'.$product->getKey().' '.$product->nom_pre);
                } catch (\Throwable $exception) {
                    $report['failed'][] = $summary + ['errors' => $exception instanceof ValidationException ? $exception->errors() : [$exception->getMessage()]];
                    $this->warn('Falló candidato '.($summary['index'] + 1).': '.$exception->getMessage());
                }
            }
        }
        $report['final_count'] = Prenda::query()->count();
        $report['finished_at'] = now()->toIso8601String();
        $report['counts'] = array_combine(
            ['valid', 'invalid', 'imported', 'skipped_duplicates', 'failed', 'warnings'],
            array_map(fn ($key) => count($report[$key]), ['valid', 'invalid', 'imported', 'skipped_duplicates', 'failed', 'warnings'])
        );
        $temporary = tempnam($parent, 'report-');
        if (file_put_contents($temporary, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === false || ! rename($temporary, $target)) {
            throw new \RuntimeException('No se pudo guardar el reporte.');
        }
        chmod($target, 0644);
        $this->table(['valid', 'invalid', 'duplicate', 'imported', 'failed', 'warnings'], [[
            $report['counts']['valid'], $report['counts']['invalid'], $report['counts']['skipped_duplicates'],
            $report['counts']['imported'], $report['counts']['failed'], $report['counts']['warnings'],
        ]]);
        $this->line('Prendas: '.$report['initial_count'].' → '.$report['final_count']);
        $this->line('Reporte: '.$target);

        return $report['invalid'] || $report['failed'] ? self::FAILURE : self::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    private function summary(array $entry, int $index): array
    {
        return [
            'index' => $index, 'source' => $entry['source'] ?? [],
            'name' => $entry['product']['name'] ?? null, 'brand' => $entry['product']['brand'] ?? null,
            'category' => $entry['product']['category'] ?? null, 'model_reference' => $entry['product']['model_reference'] ?? null,
            'price_origin' => $entry['product']['price_origin'] ?? null,
            'images' => $entry['images'] ?? [], 'rights_status' => $entry['source']['rights_status'] ?? null,
        ];
    }
}
