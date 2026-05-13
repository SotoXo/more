<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\DetalleReserva;
use App\Models\DetalleVenta;
use App\Models\ImagenPrenda;
use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Temporada;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCatalogService
{
    public function __construct(
        private readonly AdminFormattingService $formatting,
        private readonly ResourceDefinitionService $definitions,
        private readonly ResourceQueryService $queries,
        private readonly ProductImageService $images,
    ) {
    }

    public function createProductRecord(Request $request, array $payload, array $validated): Prenda
    {
        $createdPaths = [];
        try {
            return DB::transaction(function () use ($request, $payload, $validated, &$createdPaths) {
                $product = Prenda::query()->create([
                    ...$payload,
                    'cod_usuario_registra_pre' => $request->user()->getAuthIdentifier(),
                ]);

                $this->syncProductVariants($product, $validated['variant_rows'] ?? []);
                $this->images->replaceOrCreatePrimaryProductImage(
                    $product,
                    $validated['imagen_principal'],
                    $validated['fue_igp'] ?? 'catalogo',
                    $createdPaths,
                );
                $this->images->createProductGalleryImages($product, $validated['gallery_images'] ?? [], $createdPaths);
                $this->images->ensurePrimaryImage((int) $product->id_prenda);

                return $product;
            });
        } catch (\Throwable $exception) {
            foreach ($createdPaths as $path) {
                $this->images->deleteImageFile($path);
            }

            throw $exception;
        }
    }

    public function updateProductRecord(Request $request, Prenda $product, array $payload, array $validated): void
    {
        $createdPaths = [];
        $deleteAfterSuccess = [];

        try {
            DB::transaction(function () use ($product, $payload, $validated, &$createdPaths, &$deleteAfterSuccess) {
                $product->update($payload);

                $this->images->syncProductExistingImages($product, $validated['existing_images'] ?? [], $deleteAfterSuccess);
                $this->syncProductVariants($product->fresh(['variantes.inventario', 'imagenes']), $validated['variant_rows'] ?? []);
                $this->images->createProductGalleryImages($product, $validated['gallery_images'] ?? [], $createdPaths);

                if (!empty($validated['imagen_principal'])) {
                    $this->images->replaceOrCreatePrimaryProductImage(
                        $product,
                        $validated['imagen_principal'],
                        $validated['fue_igp'] ?? 'catalogo',
                        $createdPaths,
                        $deleteAfterSuccess,
                    );
                }

                $this->images->ensurePrimaryImage((int) $product->id_prenda);
            });
        } catch (\Throwable $exception) {
            foreach ($createdPaths as $path) {
                $this->images->deleteImageFile($path);
            }

            throw $exception;
        }

        foreach ($deleteAfterSuccess as $path) {
            $this->images->deleteImageFile($path);
        }
    }

    private function syncProductVariants(Prenda $product, array $rows): void
    {
        $product->loadMissing('categoria', 'variantes.inventario', 'variantes.imagenes');

        $existingVariants = $product->variantes->keyBy('id_prenda_variante');
        $baseCode = $this->buildProductBaseCode($product);
        $usedCodes = [];

        foreach ($rows as $row) {
            $remove = filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOL);
            $variantId = filled($row['id'] ?? null) ? (int) $row['id'] : null;

            if ($remove) {
                if ($variantId && $existingVariants->has($variantId)) {
                    $this->removeProductVariant($existingVariants->get($variantId));
                }

                continue;
            }

            $code = null;
            $inventoryPayload = [
                'cdi_inv' => (int) $row['stock_inicial'],
                'cre_inv' => 0,
                'ubi_inv' => $this->formatting->normalizeNullableText($row['ubi_inv'] ?? null),
            ];

            if ($variantId && $existingVariants->has($variantId)) {
                /** @var PrendaVariante $variant */
                $variant = $existingVariants->get($variantId);
                $code = $variant->sku_prv ?: $this->nextVariantInternalCode($baseCode, $usedCodes);

                $variant->update([
                    'cod_colores_prv' => (int) $row['cod_colores_prv'],
                    'cod_tallas_prv' => (int) $row['cod_tallas_prv'],
                    'sku_prv' => $code,
                    'cob_prv' => filled($row['cob_prv'] ?? null)
                        ? mb_strtoupper(trim((string) $row['cob_prv']), 'UTF-8')
                        : null,
                    'pve_prv' => (float) $row['pve_prv'],
                    'cos_prv' => filled($row['cos_prv'] ?? null) ? (float) $row['cos_prv'] : null,
                    'est_prv' => (bool) ($row['est_prv'] ?? true),
                ]);

                if ($variant->inventario) {
                    $variant->inventario->update($inventoryPayload);
                } else {
                    Inventario::query()->create([
                        'cod_prenda_variantes_inv' => $variant->id_prenda_variante,
                        ...$inventoryPayload,
                    ]);
                }
            } else {
                $code = $this->nextVariantInternalCode($baseCode, $usedCodes);

                $variant = PrendaVariante::query()->create([
                    'cod_prendas_prv' => $product->id_prenda,
                    'cod_colores_prv' => (int) $row['cod_colores_prv'],
                    'cod_tallas_prv' => (int) $row['cod_tallas_prv'],
                    'sku_prv' => $code,
                    'cob_prv' => filled($row['cob_prv'] ?? null)
                        ? mb_strtoupper(trim((string) $row['cob_prv']), 'UTF-8')
                        : null,
                    'pve_prv' => (float) $row['pve_prv'],
                    'cos_prv' => filled($row['cos_prv'] ?? null) ? (float) $row['cos_prv'] : null,
                    'est_prv' => (bool) ($row['est_prv'] ?? true),
                    'fec_prv' => Carbon::now(),
                ]);

                Inventario::query()->create([
                    'cod_prenda_variantes_inv' => $variant->id_prenda_variante,
                    ...$inventoryPayload,
                ]);
            }

            $usedCodes[] = $code;
        }
    }

    private function removeProductVariant(PrendaVariante $variant): void
    {
        if (DetalleVenta::query()->where('cod_prenda_variantes_dve', $variant->id_prenda_variante)->exists()) {
            throw ValidationException::withMessages([
                'variant_rows' => 'No puedes quitar una variante que ya tiene ventas asociadas.',
            ]);
        }

        if (DetalleReserva::query()->where('cod_prenda_variantes_dre', $variant->id_prenda_variante)->exists()) {
            throw ValidationException::withMessages([
                'variant_rows' => 'No puedes quitar una variante que ya tiene reservas asociadas.',
            ]);
        }

        $variant->loadMissing('inventario', 'imagenes');

        if ($variant->imagenes->isNotEmpty()) {
            ImagenPrenda::query()
                ->where('cod_prenda_variantes_igp', $variant->id_prenda_variante)
                ->update(['cod_prenda_variantes_igp' => null]);
        }

        $variant->inventario?->delete();
        $variant->delete();
    }

    private function nextVariantInternalCode(string $baseCode, array $usedCodes): string
    {
        if (!in_array($baseCode, $usedCodes, true)) {
            return $baseCode;
        }

        $position = 2;
        do {
            $candidate = $baseCode . '-' . str_pad((string) $position, 2, '0', STR_PAD_LEFT);
            $position++;
        } while (in_array($candidate, $usedCodes, true));

        return $candidate;
    }

    private function buildProductBaseCode(Prenda $product): string
    {
        $source = $product->categoria?->nom_cat ?: $product->nom_pre ?: 'PRENDA';
        $letters = preg_replace('/[^A-Z]/', '', mb_strtoupper(Str::ascii($source), 'UTF-8')) ?: 'PRE';
        $prefix = str_pad(Str::substr($letters, 0, 3), 3, 'X');

        return $prefix . '-' . str_pad((string) $product->id_prenda, 5, '0', STR_PAD_LEFT);
    }

    private function buildVariantInternalCode(string $baseCode, int $variantCount, int $position): string
    {
        return $variantCount > 1
            ? $baseCode . '-' . str_pad((string) $position, 2, '0', STR_PAD_LEFT)
            : $baseCode;
    }

    public function productEditorPayload(array $definition, ?Prenda $record, Request $request, string $mode, array $options): array
    {
        $record?->loadMissing(['categoria', 'marca', 'temporada', 'usuarioRegistra', 'variantes.color', 'variantes.talla', 'variantes.inventario', 'imagenes.variante']);

        $isEdit = $mode === 'edit' && $record;
        $title = $isEdit ? ($record->nom_pre ?: 'Editar prenda') : 'Nueva prenda';

        return [
            'meta' => $this->definitions->meta($definition),
            'breadcrumbs' => $isEdit
                ? $this->definitions->breadcrumbs($definition, $record->nom_pre, 'Editar')
                : $this->definitions->breadcrumbs($definition, 'Nueva prenda'),
            'mode' => $isEdit ? 'edit' : 'create',
            'recordLabel' => $record?->nom_pre,
            'submit' => [
                'action' => $isEdit
                    ? $this->definitions->showHref($definition, $record->id_prenda)
                    : $this->definitions->indexHref($definition),
                'method' => $isEdit ? 'put' : 'post',
            ],
            'stateAction' => $record
                ? [
                    'label' => $record->est_pre ? 'Ocultar prenda' : 'Activar prenda',
                    'value' => $record->est_pre ? '0' : '1',
                    'confirm' => $record->est_pre
                        ? 'La prenda dejará de mostrarse en el catálogo.'
                        : 'La prenda volverá a estar disponible en el catálogo.',
                ]
                : null,
            'editor' => [
                'title' => $title,
                'description' => 'Alta guiada de la prenda, su portada, configuración comercial y existencias.',
                'currentPrimaryUrl' => $record?->imagenes?->firstWhere('pri_igp', true)?->url_igp,
                'currentPrimaryLabel' => $record?->imagenes?->firstWhere('pri_igp', true)?->rut_igp
                    ? basename((string) $record->imagenes->firstWhere('pri_igp', true)?->rut_igp)
                    : null,
            ],
            'options' => [
                'categories' => $options['categories'] ?? [],
                'brands' => $options['brands'] ?? [],
                'seasons' => $options['seasons'] ?? [],
                'genders' => $options['genders'] ?? [],
                'colors' => $options['colors'] ?? [],
                'sizes' => $options['sizes'] ?? [],
                'sizeSegments' => $options['sizeSegments'] ?? [],
                'locations' => $options['locations'] ?? [],
                'colorFamilies' => $this->colorFamilyOptions(),
                'categoryProfiles' => $this->categorySizeProfiles(),
            ],
            'initialData' => $this->productEditorState($record),
        ];
    }

    public function productShowPayload(array $definition, Prenda $record): array
    {
        $record->loadMissing(['categoria', 'marca', 'temporada', 'usuarioRegistra', 'variantes.color', 'variantes.talla', 'variantes.inventario', 'imagenes.variante']);

        $gallery = collect($record->imagenes ?? [])
            ->map(fn ($image) => [
                'id' => $image->id_imagen_prenda,
                'url' => $image->url_igp,
                'label' => $image->rut_igp ? basename((string) $image->rut_igp) : ('Imagen #' . $image->id_imagen_prenda),
                'source' => $image->fue_igp ?: 'catalogo',
                'order' => (int) $image->ord_igp,
                'primary' => (bool) $image->pri_igp,
                'variantLabel' => $image->variante?->sku_prv,
                'editHref' => '/superadmin/catalogo/imagenes/' . $image->id_imagen_prenda . '/editar',
            ])
            ->values()
            ->all();

        $variants = collect($record->variantes ?? [])
            ->map(fn ($variant) => [
                'id' => $variant->id_prenda_variante,
                'label' => $variant->sku_prv ?: ('Variante #' . $variant->id_prenda_variante),
                'color' => $variant->color?->nom_col ?? 'Sin color',
                'colorSwatch' => $variant->color?->hex_dom_col ?: $variant->color?->hex_col,
                'size' => $variant->talla?->nom_tal ?? 'Sin talla',
                'price' => $this->formatting->money((float) $variant->pve_prv),
                'cost' => $variant->cos_prv !== null ? $this->formatting->money((float) $variant->cos_prv) : null,
                'stock' => $this->formatting->available($variant->inventario),
                'reserved' => (int) ($variant->inventario?->cre_inv ?? 0),
                'location' => $variant->inventario?->ubi_inv ?: 'Sin ubicación',
                'active' => (bool) $variant->est_prv,
                'editHref' => '/superadmin/catalogo/variantes/' . $variant->id_prenda_variante . '/editar',
            ])
            ->values()
            ->all();

        return [
            'meta' => $this->definitions->meta($definition),
            'breadcrumbs' => $this->definitions->breadcrumbs($definition, $record->nom_pre),
            'recordLabel' => $record->nom_pre,
            'product' => [
                'name' => $record->nom_pre,
                'description' => $record->des_pre ?: 'Sin descripción registrada.',
                'category' => $record->categoria?->nom_cat ?? 'Sin categoría',
                'brand' => $record->marca?->nom_mar ?? 'Sin marca',
                'season' => $record->temporada?->nom_tem ?? 'Sin temporada',
                'gender' => $record->gen_pre ? mb_strtoupper((string) $record->gen_pre, 'UTF-8') : 'Sin género',
                'status' => $record->est_pre ? 'Visible' : 'Oculta',
                'price' => $this->formatting->money((float) $record->prb_pre),
                'code' => $this->buildProductBaseCode($record),
                'registeredBy' => $record->usuarioRegistra?->nombreCompleto() ?? 'Sin usuario',
                'registeredAt' => $this->formatting->formatDate($record->fec_pre),
            ],
            'metrics' => [
                ['label' => 'Variantes', 'value' => (string) count($variants), 'note' => 'Configuraciones activas o históricas.', 'tone' => 'accent'],
                ['label' => 'Disponible', 'value' => (string) $this->queries->productAvailable($record), 'note' => 'Existencias de la prenda.', 'tone' => 'success'],
                ['label' => 'Galería', 'value' => (string) count($gallery), 'note' => 'Imágenes asociadas.', 'tone' => 'neutral'],
                ['label' => 'Favoritos', 'value' => (string) ($record->favoritos_count ?? $record->favoritos()->count()), 'note' => 'Clientes que la guardaron.', 'tone' => 'warning'],
            ],
            'gallery' => $gallery,
            'variants' => $variants,
            'actions' => [
                ['label' => 'Volver al módulo', 'href' => $this->definitions->indexHref($definition), 'kind' => 'ghost'],
                ['label' => 'Editar prenda', 'href' => $this->definitions->editHref($definition, $record->id_prenda), 'kind' => 'secondary'],
            ],
        ];
    }

    private function productEditorState(?Prenda $record): array
    {
        $record?->loadMissing(['variantes.inventario', 'imagenes.variante']);

        $variantRows = $record
            ? $record->variantes
                ->sortBy('id_prenda_variante')
                ->map(fn ($variant) => [
                    'id' => (string) $variant->id_prenda_variante,
                    'sku_prv' => $variant->sku_prv ?? '',
                    'cod_colores_prv' => (string) $variant->cod_colores_prv,
                    'cod_tallas_prv' => (string) $variant->cod_tallas_prv,
                    'cob_prv' => $variant->cob_prv ?? '',
                    'pve_prv' => $variant->pve_prv !== null ? (string) $variant->pve_prv : '0',
                    'cos_prv' => $variant->cos_prv !== null ? (string) $variant->cos_prv : '',
                    'stock_inicial' => (string) ($variant->inventario?->cdi_inv ?? 1),
                    'ubi_inv' => $variant->inventario?->ubi_inv ?? 'TIENDA',
                    'est_prv' => (bool) $variant->est_prv,
                    'remove' => false,
                ])
                ->values()
                ->all()
            : [[
                'id' => '',
                'sku_prv' => '',
                'cod_colores_prv' => '',
                'cod_tallas_prv' => '',
                'cob_prv' => '',
                'pve_prv' => '',
                'cos_prv' => '',
                'stock_inicial' => '1',
                'ubi_inv' => 'TIENDA',
                'est_prv' => true,
                'remove' => false,
            ]];

        $existingImages = $record
            ? $record->imagenes
                ->sortBy([
                    ['pri_igp', 'desc'],
                    ['ord_igp', 'asc'],
                    ['id_imagen_prenda', 'asc'],
                ])
                ->map(fn ($image) => [
                    'id' => (string) $image->id_imagen_prenda,
                    'url_igp' => $image->url_igp ?? '',
                    'rut_igp' => $image->rut_igp ?? '',
                    'ord_igp' => (string) ($image->ord_igp ?? 0),
                    'pri_igp' => (bool) $image->pri_igp,
                    'fue_igp' => $image->fue_igp ?? 'catalogo',
                    'variant_label' => $image->variante?->sku_prv,
                    'remove' => false,
                ])
                ->values()
                ->all()
            : [];

        return [
            'nom_pre' => $record?->nom_pre ?? '',
            'des_pre' => $record?->des_pre ?? '',
            'cod_categorias_pre' => (string) ($record?->cod_categorias_pre ?? ''),
            'cod_marcas_pre' => (string) ($record?->cod_marcas_pre ?? ''),
            'cod_temporadas_pre' => (string) ($record?->cod_temporadas_pre ?? $this->defaultSeasonId()),
            'gen_pre' => (string) ($record?->gen_pre ?? ''),
            'prb_pre' => $record?->prb_pre !== null ? (string) $record->prb_pre : '',
            'est_pre' => $record ? (bool) $record->est_pre : true,
            'usa_variantes' => count($variantRows) > 1,
            'imagen_principal' => null,
            'fue_igp' => $record?->imagenes?->firstWhere('pri_igp', true)?->fue_igp ?? 'catalogo',
            'existing_images' => $existingImages,
            'gallery_images' => [],
            'variant_rows' => $variantRows,
        ];
    }

    private function defaultSeasonId(): ?int
    {
        return Temporada::query()
            ->whereRaw('UPPER(nom_tem) = ?', ['PERMANENTE'])
            ->value('id_temporada');
    }

    private function categorySizeProfiles(): array
    {
        if (!Schema::hasColumn('categorias', 'tip_tal_cat')) {
            return [];
        }

        $hasSizeGroup = Schema::hasColumn('categorias', 'gru_tal_cat');

        return Categoria::query()
            ->select(array_filter(['id_categoria', 'tip_tal_cat', $hasSizeGroup ? 'gru_tal_cat' : null]))
            ->get()
            ->mapWithKeys(fn ($category) => [
                (string) $category->id_categoria => [
                    'segment' => 'ADULTO',
                    'system' => (string) ($category->tip_tal_cat ?? ''),
                    'group' => $hasSizeGroup ? (string) ($category->gru_tal_cat ?? '') : '',
                ],
            ])
            ->all();
    }

    private function colorFamilyOptions(): array
    {
        if (!Schema::hasColumn('colores', 'fam_col')) {
            return [];
        }

        return Color::query()
            ->whereNotNull('fam_col')
            ->where('fam_col', '!=', '')
            ->select('fam_col')
            ->distinct()
            ->orderBy('fam_col')
            ->pluck('fam_col')
            ->map(fn ($family) => [
                'label' => (string) $family,
                'value' => (string) $family,
            ])
            ->values()
            ->all();
    }
}
