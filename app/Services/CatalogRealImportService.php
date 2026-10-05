<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Marca;
use App\Models\Prenda;
use App\Models\SystemLog;
use App\Models\Talla;
use App\Models\Temporada;
use App\Models\User;
use App\Services\Superadmin\Resources\ProductCatalogService;
use App\Services\Superadmin\Resources\ProductImageService;
use App\Services\Superadmin\Resources\ResourceDefinitionService;
use App\Services\Superadmin\Resources\ResourceValidationService;
use App\Services\Superadmin\Resources\ResourceWriteService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CatalogRealImportService
{
    public const RIGHTS = ['provider_authorized', 'public_domain', 'licensed', 'unknown', 'prohibited'];

    public function __construct(
        private readonly ResourceDefinitionService $definitions,
        private readonly ResourceValidationService $validation,
        private readonly ResourceWriteService $writer,
        private readonly ProductCatalogService $products,
        private readonly ProductImageService $images,
        private readonly SystemLogService $logs,
    ) {}

    public function prepare(array $entry, User $actor): array
    {
        $source = $entry['source'] ?? [];
        $product = $entry['product'] ?? [];
        $this->requireUrl($source['url'] ?? null, 'source.url');
        $this->requireRights($source['rights_status'] ?? null, 'source.rights_status');
        $this->check(is_string($source['consulted_at'] ?? null) && strtotime($source['consulted_at']) !== false, 'source.consulted_at', 'Se requiere fecha de consulta válida.');
        $this->check(($product['price_origin'] ?? null) === 'demo_bob', 'product.price_origin', 'El precio debe estar identificado como demo_bob.');
        $this->check(! array_intersect(['ai_run_ids', 'ai_image_tokens', 'sku', 'barcode', 'gtin'], array_keys($entry)), 'entry', 'No se admiten datos IA, SKU ni códigos inventados.');
        $brand = $this->master(Marca::class, 'nom_mar', 'est_mar', $product['brand'] ?? null);
        $category = $this->master(Categoria::class, 'nom_cat', 'est_cat', $product['category'] ?? null);
        $season = $this->master(Temporada::class, 'nom_tem', 'est_tem', $product['season'] ?? null);
        $reference = trim((string) ($product['model_reference'] ?? ''));
        $this->check(mb_strlen($reference) <= 150, 'product.model_reference', 'La referencia excede 150 caracteres.');
        $key = $this->identity($entry);
        $duplicate = $this->findDuplicate($entry, $key, (int) $brand->getKey());
        if ($duplicate) {
            return ['key' => $key, 'duplicate' => $duplicate, 'warnings' => [], 'entry' => $entry];
        }

        $variantRows = $entry['variants'] ?? [];
        $this->check(is_array($variantRows) && count($variantRows) >= 1 && count($variantRows) <= 3, 'variants', 'Se requieren de una a tres variantes.');
        $rows = [];
        foreach ($variantRows as $index => $row) {
            $this->check(is_array($row), "variants.$index", 'Variante inválida.');
            $color = $this->master(Color::class, 'nom_col', 'est_col', $row['color'] ?? null);
            $size = $this->master(Talla::class, 'nom_tal', 'est_tal', $row['size'] ?? null);
            $expectedSystem = $category->tip_tal_cat === 'UNITALLA' ? 'UNICA' : $category->tip_tal_cat;
            $this->check(! $expectedSystem || $size->sis_tal === $expectedSystem, "variants.$index.size", 'La talla no es compatible con el sistema de la categoría.');
            $this->check($size->seg_tal === 'ADULTO', "variants.$index.size", 'Este manifiesto requiere tallas de adulto.');
            $this->check(! $category->gru_tal_cat || in_array($size->gru_tal, [$category->gru_tal_cat, 'GENERAL'], true), "variants.$index.size", 'La talla no es compatible con el grupo de la categoría.');
            $published = $entry['metadata']['source_sizes'] ?? [];
            $this->check(is_array($published) && in_array($this->normalize((string) $row['size']), array_map(fn ($v) => $this->normalize((string) $v), $published), true), "variants.$index.size", 'La talla debe figurar en source_sizes; no se convierten tallas automáticamente.');
            $this->check(! isset($row['cre_inv']) || $row['cre_inv'] === 0, "variants.$index.cre_inv", 'El stock reservado inicial debe ser cero.');
            $this->check(! array_intersect(['sku', 'sku_prv', 'barcode', 'gtin', 'cob_prv', 'id', 'remove'], array_keys($row)), "variants.$index", 'No se admiten SKU, códigos ni edición de variantes existentes.');
            $rows[] = [
                'cod_colores_prv' => $color->getKey(),
                'cod_tallas_prv' => $size->getKey(),
                'pve_prv' => $product['demo_price_bob'] ?? null,
                'stock_inicial' => $row['initial_stock'] ?? null,
                'ubi_inv' => $row['location'] ?? null,
                'est_prv' => true,
            ];
        }

        $imageRows = $entry['images'] ?? [];
        $this->check(is_array($imageRows) && count($imageRows) >= 1, 'images', 'Se requiere una fotografía real.');
        $this->check(count(array_filter($imageRows, fn ($image) => ($image['is_primary'] ?? false) === true)) === 1, 'images', 'Debe existir exactamente una imagen principal.');
        $primary = null;
        $gallery = [];
        $warnings = [];
        foreach ($imageRows as $index => $image) {
            $this->requireUrl($image['source_url'] ?? null, "images.$index.source_url");
            $this->requireRights($image['rights_status'] ?? null, "images.$index.rights_status");
            if ($image['rights_status'] === 'unknown') {
                $warnings[] = "Imagen $index: derechos comerciales desconocidos; uso de referencia/dataset de desarrollo.";
            }
            $file = $this->localImage((string) ($image['local_path'] ?? ''), "images.$index.local_path");
            if (($image['is_primary'] ?? false) === true) {
                $primary = $file;
            } else {
                $gallery[] = ['archivo' => $file, 'ord_igp' => $index + 1, 'pri_igp' => false, 'fue_igp' => 'catalog_import'];
            }
        }
        $request = Request::create('/catalog-import', 'POST', [
            'nom_pre' => $product['name'] ?? null,
            'des_pre' => $product['description'] ?? null,
            'modelo_pre' => $reference ?: null,
            'cod_marcas_pre' => $brand->getKey(),
            'cod_categorias_pre' => $category->getKey(),
            'cod_temporadas_pre' => $season->getKey(),
            'gen_pre' => $product['gender'] ?? null,
            'prb_pre' => $product['demo_price_bob'] ?? null,
            'est_pre' => true,
            'usa_variantes' => count($rows) > 1,
            'variant_rows' => $rows,
            'fue_igp' => 'catalog_import',
            'gallery_images' => $gallery,
        ]);
        $request->files->set('imagen_principal', $primary);
        if ($gallery) {
            $request->files->set('gallery_images', array_map(fn ($row) => ['archivo' => $row['archivo']], $gallery));
        }
        $request->setUserResolver(fn () => $actor);
        $definition = $this->definitions->definition('catalogo', 'prendas');
        $validated = $this->validation->validateWrite($request, $definition);

        return [
            'key' => $key, 'entry' => $entry, 'request' => $request, 'validated' => $validated,
            'payload' => $this->writer->normalizePayload($definition, $validated),
            'warnings' => $warnings, 'duplicate' => null,
        ];
    }

    public function import(array $prepared): Prenda
    {
        $storedPaths = [];
        try {
            return DB::transaction(function () use ($prepared, &$storedPaths) {
                $entry = $prepared['entry'];
                $duplicate = $this->findDuplicate($entry, $prepared['key'], (int) $prepared['payload']['cod_marcas_pre']);
                $this->check(! $duplicate, 'duplicate', 'El producto ya fue importado.');
                $product = $this->products->createProductRecord($prepared['request'], $prepared['payload'], $prepared['validated'], attachAiReview: false);
                $storedPaths = $product->imagenes()->pluck('rut_igp')->all();
                $receipt = $this->logs->info('prendas', 'catalog_real_imported', 'Prenda real importada desde manifiesto de desarrollo.', $product, [
                    'catalog_import_key' => $prepared['key'],
                    'provenance' => [
                        'page' => $entry['source']['url'],
                        'consulted_at' => $entry['source']['consulted_at'],
                        'rights_status' => $entry['source']['rights_status'],
                        'model_reference' => $entry['product']['model_reference'] ?? null,
                        'price_origin' => 'demo_bob',
                        'images' => array_map(fn ($image) => [
                            'page' => $image['source_url'], 'rights_status' => $image['rights_status'],
                        ], $entry['images']),
                    ],
                ]);
                if (! $receipt) {
                    throw new \RuntimeException('No se pudo guardar el comprobante de procedencia; producto revertido.');
                }

                return $product->load(['marca', 'categoria', 'variantes.inventario', 'imagenes']);
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                $this->images->deleteImageFile($path);
            }
            throw $exception;
        }
    }

    public function identity(array $entry): string
    {
        $reference = $this->normalize((string) ($entry['product']['model_reference'] ?? ''));
        $basis = $reference !== '' ? 'model:'.$reference : 'source:'.preg_replace('/#.*$/', '', rtrim((string) ($entry['source']['url'] ?? ''), '/'));

        return hash('sha256', $this->normalize((string) ($entry['product']['brand'] ?? '')).'|'.$basis);
    }

    private function findDuplicate(array $entry, string $key, int $brandId): ?Prenda
    {
        $receipt = SystemLog::query()->where('accion_slg', 'catalog_real_imported')
            ->where('metadata_slg->catalog_import_key', $key)->latest('id')->first();
        if ($receipt && $product = Prenda::query()->find($receipt->entidad_id_slg)) {
            return $product;
        }
        $reference = $this->normalize((string) ($entry['product']['model_reference'] ?? ''));
        if ($reference !== '' && Schema::hasColumn('prendas', 'modelo_pre')) {
            return Prenda::query()->where('cod_marcas_pre', $brandId)
                ->whereRaw('UPPER(TRIM(modelo_pre)) = ?', [$reference])->first();
        }

        return null;
    }

    private function master(string $class, string $nameField, string $activeField, mixed $name): Model
    {
        $this->check(is_string($name) && trim($name) !== '', $nameField, 'Se requiere el nombre del catálogo existente.');
        $model = $class::query()->whereRaw('UPPER(TRIM('.$nameField.')) = ?', [$this->normalize($name)])->where($activeField, true)->first();
        $this->check($model !== null, $nameField, 'No existe un maestro activo: '.$name);

        return $model;
    }

    private function localImage(string $path, string $field): UploadedFile
    {
        $base = realpath(storage_path('app/catalog-import/images'));
        $resolved = realpath(str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path));
        $this->check($base && $resolved && str_starts_with($resolved, $base.DIRECTORY_SEPARATOR) && is_file($resolved), $field, 'La imagen debe existir dentro de catalog-import/images.');
        $this->check(in_array(strtolower(pathinfo($resolved, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true), $field, 'Extensión de imagen no admitida.');
        $dimensions = @getimagesize($resolved);
        $this->check($dimensions && $dimensions[0] >= 200 && $dimensions[1] >= 200, $field, 'La imagen debe ser válida y medir al menos 200 píxeles por lado.');

        return new UploadedFile($resolved, basename($resolved), null, null, true);
    }

    private function requireUrl(mixed $value, string $field): void
    {
        $this->check(is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['https', 'http'], true), $field, 'Se requiere URL pública de procedencia.');
    }

    private function requireRights(mixed $value, string $field): void
    {
        $this->check(in_array($value, self::RIGHTS, true) && $value !== 'prohibited', $field, 'Derechos ausentes, inválidos o prohibidos.');
    }

    private function normalize(string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($value)), 'UTF-8');
    }

    private function check(bool $condition, string $field, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }
}
