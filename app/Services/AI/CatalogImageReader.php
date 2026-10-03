<?php

namespace App\Services\AI;

use App\Models\ImagenPrenda;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CatalogImageReader
{
    public function read(ImagenPrenda $image): string
    {
        $path = (string) $image->rut_igp;
        $disk = Storage::disk('public');
        $limit = max(1, (int) config('mati_ai.max_image_kb', 6144)) * 1024;
        if (!str_starts_with($path, 'productos/') || str_contains($path, '..') || str_contains($path, '\\')
            || str_contains($path, ':') || !$disk->exists($path) || $disk->size($path) > $limit) {
            throw ValidationException::withMessages(['existing_image_id' => 'Selecciona una imagen local del catálogo de hasta 6 MB.']);
        }
        $bytes = $disk->get($path);
        $info = is_string($bytes) ? @getimagesizefromstring($bytes) : false;
        if (!$info || strlen($bytes) > $limit || !in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)
            || $info[0] * $info[1] > 40_000_000) {
            throw ValidationException::withMessages(['existing_image_id' => 'La imagen del catálogo no tiene un formato compatible.']);
        }
        return $bytes;
    }
}
