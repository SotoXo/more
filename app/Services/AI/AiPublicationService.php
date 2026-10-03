<?php

namespace App\Services\AI;

use App\Models\AiRun;
use App\Models\ImagenPrenda;
use App\Models\Prenda;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiPublicationService
{
    public const RIGHTS = ['owned', 'licensed', 'provider_authorized', 'public_domain'];

    public function stage(AiRun $run, array $result, string $original, array $provenance): array
    {
        $manifest = [];
        $base = 'mati-ai/prepared/'.$run->id;
        $paths = [];
        try {
            $originalPath = $base.'/original';
            if (!Storage::disk('local')->put($originalPath, $original)) throw new \RuntimeException('storage');
            $paths[] = $originalPath;
            foreach (data_get($result, 'data.derivatives', []) as $derivative) {
                $profile = $derivative['profile'] ?? '';
                if (!in_array($profile, ['thumbnail', 'card', 'detail'], true)) continue;
                if (collect($manifest)->contains('profile', $profile)) throw new \RuntimeException('duplicate derivative');
                $bytes = base64_decode($derivative['content_base64'] ?? '', true);
                if ($bytes === false || strlen($bytes) > 12 * 1024 * 1024 || !hash_equals($derivative['sha256'] ?? '', hash('sha256', $bytes))) {
                    throw new \RuntimeException('invalid derivative');
                }
                $info = @getimagesizefromstring($bytes);
                if (!$info || ($info['mime'] ?? '') !== 'image/webp' || $info[0] * $info[1] > 40_000_000) throw new \RuntimeException('invalid format');
                $path = $base.'/'.$profile.'.webp';
                if (!Storage::disk('local')->put($path, $bytes)) throw new \RuntimeException('storage');
                $paths[] = $path;
                unset($derivative['content_base64']);
                $manifest[] = [...$derivative, 'path' => $path];
            }
            if (count($manifest) !== 3) throw new \RuntimeException('missing derivatives');
            $result['data'] = [
                'derivatives' => $manifest, 'original_path' => $originalPath,
                'original_sha256' => hash('sha256', $original), 'provenance' => $provenance,
            ];
            $run->update(['result' => $result, 'expires_at' => now()->addHours(config('mati_ai.prepared_ttl_hours', 24))]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($paths);
            throw $e;
        }
        return [
            'token' => $run->id, 'name' => 'Imagen preparada.webp',
            'url' => route('superadmin.ai.prepared-image', ['token' => $run->id], false),
        ];
    }

    public function approvedRun(string $token, int $actor, bool $lock = false): AiRun
    {
        $run = AiRun::whereKey($token)->where('created_by', $actor)->where('operation', 'prepare-publication')
            ->whereIn('status', ['completed', 'partial'])->when($lock, fn ($query) => $query->lockForUpdate())->first();
        if (!$run || $run->prenda_id || !$run->expires_at || $run->expires_at->isPast()
            || !in_array(data_get($run->result, 'data.provenance.rights_status'), self::RIGHTS, true)
            || !filled(data_get($run->result, 'data.provenance.rights_evidence'))
            || (int) data_get($run->result, 'data.provenance.approved_by') !== $actor) {
            throw ValidationException::withMessages(['ai_image_tokens' => 'La imagen preparada caducó o no está aprobada. Vuelve a prepararla o continúa con una imagen propia.']);
        }
        return $run;
    }

    public function attach(Prenda $product, array $tokens, int $actor, array &$createdPaths): void
    {
        foreach ($tokens as $token) {
            $run = $this->approvedRun($token, $actor, true);
            $manifest = [];
            foreach (data_get($run->result, 'data.derivatives', []) as $derivative) {
                $path = 'productos/'.$product->id_prenda.'/ai-'.$run->id.'-'.$derivative['profile'].'.webp';
                $bytes = Storage::disk('local')->get($derivative['path']);
                if (!$bytes || !hash_equals($derivative['sha256'], hash('sha256', $bytes))) {
                    throw ValidationException::withMessages(['ai_image_tokens' => 'No se encontró la imagen preparada. Vuelve a prepararla.']);
                }
                if (!Storage::disk('public')->put($path, $bytes)) throw new \RuntimeException('Could not publish prepared image');
                $createdPaths[] = $path;
                $manifest[] = [...$derivative, 'path' => $path];
            }
            $detail = collect($manifest)->firstWhere('profile', 'detail');
            $image = ImagenPrenda::create([
                'cod_prendas_igp' => $product->id_prenda, 'rut_igp' => $detail['path'],
                'url_igp' => Storage::disk('public')->url($detail['path']),
                'pri_igp' => !$product->imagenes()->where('pri_igp', true)->exists(),
                'ord_igp' => (int) $product->imagenes()->max('ord_igp') + 1,
                'fue_igp' => 'ia_aprobada', 'fec_igp' => now(),
            ]);
            $result = $run->result;
            $result['data']['published_derivatives'] = $manifest;
            $result['data']['image_id'] = $image->id_imagen_prenda;
            $run->update(['prenda_id' => $product->id_prenda, 'expires_at' => null, 'result' => $result]);
        }
    }
}
