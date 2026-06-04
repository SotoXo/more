<?php

namespace App\Services\Superadmin\Resources;

use App\Models\ImagenPrenda;
use App\Models\Prenda;
use App\Services\SystemLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageService
{
    public function __construct(
        private readonly AdminFormattingService $formatting,
        private readonly SystemLogService $logs,
    ) {
    }

    public function deleteImageRecord($record): void
    {
        $productId = $record->cod_prendas_igp;
        $wasPrimary = (bool) $record->pri_igp;
        $path = $record->rut_igp;
        $record->delete();
        $this->deleteImageFile($path);

        if ($wasPrimary) {
            $this->ensurePrimaryImage((int) $productId);
        }

        $this->logs->warning(
            'imagenes',
            'imagen_eliminada',
            'Imagen de prenda eliminada.',
            $record,
            $this->imageMetadata($record),
        );
    }

    public function createImageRecord(array $validated, array $payload): ImagenPrenda
    {
        $storedPath = $this->storePublicProductImage(
            $validated['archivo_igp'],
            (int) $payload['cod_prendas_igp'],
            (string) ($payload['fue_igp'] ?? 'imagen'),
        );

        try {
            $image = DB::transaction(function () use ($payload, $storedPath) {
                $image = ImagenPrenda::query()->create([
                    ...$payload,
                    'rut_igp' => $storedPath,
                    'url_igp' => Storage::url($storedPath),
                ]);

                $this->syncPrimaryImage($image);
                $this->ensurePrimaryImage((int) $image->cod_prendas_igp);

                return $image;
            });

            $this->logs->info(
                'imagenes',
                'imagen_agregada',
                'Imagen agregada a prenda.',
                $image,
                $this->imageMetadata($image),
            );

            return $image;
        } catch (\Throwable $exception) {
            $this->deleteImageFile($storedPath);

            throw $exception;
        }
    }

    public function syncProductExistingImages(Prenda $product, array $rows, array &$deleteAfterSuccess): void
    {
        $images = ImagenPrenda::query()
            ->where('cod_prendas_igp', $product->id_prenda)
            ->get()
            ->keyBy('id_imagen_prenda');

        foreach ($rows as $row) {
            $imageId = (int) $row['id'];
            $image = $images->get($imageId);

            if (!$image) {
                continue;
            }

            if (filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOL)) {
                if ($image->rut_igp) {
                    $deleteAfterSuccess[] = $image->rut_igp;
                }

                $image->delete();
                $this->logs->warning(
                    'imagenes',
                    'imagen_eliminada',
                    'Imagen eliminada desde ficha de prenda.',
                    $image,
                    $this->imageMetadata($image),
                );
                continue;
            }

            $original = $image->getOriginal();
            $image->update([
                'pri_igp' => (bool) ($row['pri_igp'] ?? false),
                'ord_igp' => (int) ($row['ord_igp'] ?? 0),
                'fue_igp' => $this->formatting->normalizeNullableText($row['fue_igp'] ?? $image->fue_igp),
            ]);

            $this->syncPrimaryImage($image->fresh());

            if ($this->imageWasChanged($original, $image->fresh())) {
                $this->logs->info(
                    'imagenes',
                    'imagen_actualizada',
                    'Imagen de prenda actualizada.',
                    $image,
                    $this->imageMetadata($image->fresh()),
                );
            }
        }
    }

    public function createProductGalleryImages(Prenda $product, array $rows, array &$createdPaths): void
    {
        foreach ($rows as $row) {
            if (empty($row['archivo'])) {
                continue;
            }

            $storedPath = $this->storePublicProductImage(
                $row['archivo'],
                (int) $product->id_prenda,
                (string) ($row['fue_igp'] ?? $product->nom_pre ?? 'galeria'),
            );

            $createdPaths[] = $storedPath;

            $image = ImagenPrenda::query()->create([
                'cod_prendas_igp' => $product->id_prenda,
                'cod_prenda_variantes_igp' => null,
                'rut_igp' => $storedPath,
                'url_igp' => Storage::url($storedPath),
                'pri_igp' => false,
                'ord_igp' => (int) ($row['ord_igp'] ?? 1),
                'fue_igp' => $this->formatting->normalizeNullableText($row['fue_igp'] ?? 'galeria'),
                'fec_igp' => Carbon::now(),
            ]);

            $this->syncPrimaryImage($image);
            $this->logs->info(
                'imagenes',
                'imagen_agregada',
                'Imagen de galeria agregada a prenda.',
                $image,
                $this->imageMetadata($image),
            );
        }
    }

    public function replaceOrCreatePrimaryProductImage(
        Prenda $product,
        $file,
        ?string $source,
        array &$createdPaths,
        array &$deleteAfterSuccess = [],
    ): void {
        $storedPath = $this->storePublicProductImage(
            $file,
            (int) $product->id_prenda,
            (string) ($source ?? $product->nom_pre ?? 'principal'),
        );

        $createdPaths[] = $storedPath;

        $currentPrimary = ImagenPrenda::query()
            ->where('cod_prendas_igp', $product->id_prenda)
            ->where('pri_igp', true)
            ->orderBy('ord_igp')
            ->orderBy('id_imagen_prenda')
            ->first();

        if ($currentPrimary) {
            $original = $currentPrimary->getOriginal();

            if ($currentPrimary->rut_igp && $currentPrimary->rut_igp !== $storedPath) {
                $deleteAfterSuccess[] = $currentPrimary->rut_igp;
            }

            $currentPrimary->update([
                'cod_prenda_variantes_igp' => null,
                'rut_igp' => $storedPath,
                'url_igp' => Storage::url($storedPath),
                'pri_igp' => true,
                'ord_igp' => 0,
                'fue_igp' => $this->formatting->normalizeNullableText($source ?? 'catalogo'),
            ]);

            $this->syncPrimaryImage($currentPrimary->fresh());
            $this->logs->info(
                'imagenes',
                'imagen_actualizada',
                'Imagen principal de prenda actualizada.',
                $currentPrimary,
                $this->imageMetadata($currentPrimary->fresh()) + [
                    'imagen_reemplazada' => true,
                    'datos_actualizados' => $this->imageWasChanged($original, $currentPrimary->fresh()),
                ],
            );

            return;
        }

        $image = ImagenPrenda::query()->create([
            'cod_prendas_igp' => $product->id_prenda,
            'cod_prenda_variantes_igp' => null,
            'rut_igp' => $storedPath,
            'url_igp' => Storage::url($storedPath),
            'pri_igp' => true,
            'ord_igp' => 0,
            'fue_igp' => $this->formatting->normalizeNullableText($source ?? 'catalogo'),
            'fec_igp' => Carbon::now(),
        ]);

        $this->syncPrimaryImage($image);
        $this->logs->info(
            'imagenes',
            'imagen_agregada',
            'Imagen principal agregada a prenda.',
            $image,
            $this->imageMetadata($image),
        );
    }

    public function updateImageRecord(ImagenPrenda $record, array $validated, array $payload): void
    {
        $newPath = null;
        $oldPath = $record->rut_igp;
        $oldProductId = (int) $record->cod_prendas_igp;

        if (!empty($validated['archivo_igp'])) {
            $newPath = $this->storePublicProductImage(
                $validated['archivo_igp'],
                (int) $payload['cod_prendas_igp'],
                (string) ($payload['fue_igp'] ?? $record->fue_igp ?? 'imagen'),
            );

            $payload['rut_igp'] = $newPath;
            $payload['url_igp'] = Storage::url($newPath);
        } elseif (
            !empty($payload['cod_prendas_igp'])
            && $oldPath
            && (int) $payload['cod_prendas_igp'] !== $oldProductId
        ) {
            $newPath = $this->movePublicProductImage(
                $oldPath,
                (int) $payload['cod_prendas_igp'],
            );

            $payload['rut_igp'] = $newPath;
            $payload['url_igp'] = Storage::url($newPath);
        }

        try {
            $original = $record->getOriginal();

            DB::transaction(function () use ($record, $payload, $oldProductId) {
                $record->update($payload);
                $freshRecord = $record->fresh();
                $this->syncPrimaryImage($freshRecord);
                $this->ensurePrimaryImage($oldProductId);
                $this->ensurePrimaryImage((int) $freshRecord->cod_prendas_igp);
            });
        } catch (\Throwable $exception) {
            if ($newPath && $newPath !== $oldPath) {
                $this->deleteImageFile($newPath);
            }

            throw $exception;
        }

        if ($newPath && $oldPath && $oldPath !== $newPath) {
            $this->deleteImageFile($oldPath);
        }

        $this->logs->info(
            'imagenes',
            'imagen_actualizada',
            'Imagen de prenda actualizada.',
            $record,
            $this->imageMetadata($record->fresh()) + [
                'imagen_reemplazada' => $newPath !== null && $newPath !== $oldPath,
                'datos_actualizados' => $this->imageWasChanged($original, $record->fresh()),
            ],
        );
    }

    private function syncPrimaryImage(?ImagenPrenda $image): void
    {
        if (!$image || !$image->pri_igp) {
            return;
        }

        ImagenPrenda::query()
            ->where('cod_prendas_igp', $image->cod_prendas_igp)
            ->where('id_imagen_prenda', '!=', $image->id_imagen_prenda)
            ->update(['pri_igp' => false]);
    }

    public function ensurePrimaryImage(int $productId): void
    {
        $hasPrimary = ImagenPrenda::query()
            ->where('cod_prendas_igp', $productId)
            ->where('pri_igp', true)
            ->exists();

        if ($hasPrimary) {
            return;
        }

        ImagenPrenda::query()
            ->where('cod_prendas_igp', $productId)
            ->orderBy('ord_igp')
            ->orderBy('id_imagen_prenda')
            ->limit(1)
            ->update(['pri_igp' => true]);
    }

    private function storePublicProductImage($file, int $productId, string $sourceLabel): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $baseName = $baseName !== '' ? $baseName : Str::slug($sourceLabel);
        $baseName = $baseName !== '' ? $baseName : 'imagen';
        $fileName = $baseName . '-' . Str::lower(Str::random(8)) . '.' . Str::lower($extension);

        return $file->storeAs('productos/' . $productId, $fileName, 'public');
    }

    private function movePublicProductImage(string $currentPath, int $productId): string
    {
        if (!Storage::disk('public')->exists($currentPath)) {
            return $currentPath;
        }

        $directory = 'productos/' . $productId;
        $baseName = pathinfo($currentPath, PATHINFO_FILENAME);
        $extension = pathinfo($currentPath, PATHINFO_EXTENSION);
        $cleanBaseName = Str::slug($baseName) ?: 'imagen';
        $targetPath = $directory . '/' . $cleanBaseName . ($extension ? '.' . Str::lower($extension) : '');

        if ($targetPath === $currentPath) {
            return $currentPath;
        }

        if (Storage::disk('public')->exists($targetPath)) {
            $targetPath = $directory . '/' . $cleanBaseName . '-' . Str::lower(Str::random(8)) . ($extension ? '.' . Str::lower($extension) : '');
        }

        Storage::disk('public')->makeDirectory($directory);
        Storage::disk('public')->move($currentPath, $targetPath);

        return $targetPath;
    }

    public function deleteImageFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function imageMetadata(?ImagenPrenda $image): array
    {
        if (!$image) {
            return [];
        }

        return [
            'imagen_id' => $image->id_imagen_prenda,
            'prenda_id' => $image->cod_prendas_igp,
            'variante_id' => $image->cod_prenda_variantes_igp,
            'principal' => (bool) $image->pri_igp,
            'orden' => (int) $image->ord_igp,
            'fuente' => $image->fue_igp,
        ];
    }

    private function imageWasChanged(array $original, ?ImagenPrenda $image): bool
    {
        if (!$image) {
            return false;
        }

        foreach (['cod_prendas_igp', 'cod_prenda_variantes_igp', 'pri_igp', 'ord_igp', 'fue_igp'] as $field) {
            if ((string) ($original[$field] ?? '') !== (string) ($image->{$field} ?? '')) {
                return true;
            }
        }

        return false;
    }
}
