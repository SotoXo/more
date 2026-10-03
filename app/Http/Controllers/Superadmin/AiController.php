<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiRun;
use App\Models\AiSuggestion;
use App\Models\ImagenPrenda;
use App\Services\AI\CatalogImageReader;
use App\Services\AI\AiPublicationService;
use App\Services\AI\AiReviewService;
use App\Services\AI\CatalogAiService;
use App\Services\AI\MatiAiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiController extends Controller
{
    public function __construct(
        private MatiAiClient $client,
        private CatalogAiService $catalog,
        private AiReviewService $reviews,
        private AiPublicationService $publication,
    ) {}

    private function actor(Request $request): int
    {
        abort_unless($request->user()?->isSuperadmin(), 403);
        return (int) $request->user()->getAuthIdentifier();
    }

    public function capabilities(Request $request)
    {
        $this->actor($request);
        return response()->json($this->client->request('capabilities'));
    }

    public function image(Request $request, string $operation)
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'image' => ['nullable', 'required_without:existing_image_id', 'prohibited_with:existing_image_id', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('mati_ai.max_image_kb')],
            'existing_image_id' => ['nullable', 'required_without:image', 'prohibited_with:image', 'integer', 'min:1'],
        ]);
        $bytes = !empty($data['existing_image_id'])
            ? app(CatalogImageReader::class)->read(ImagenPrenda::findOrFail($data['existing_image_id']))
            : $request->file('image')->getContent();
        $taxonomies = $this->catalog->taxonomies();
        $result = $this->client->request($operation, [...$taxonomies, 'include_embedding' => true], $bytes);
        if (!isset($result['error'])) {
            $query = $this->queryFromResult($result);
            $result = $this->withInternalMatches($result, $query);
            if ($operation === 'analyze') $result['data']['duplicates'] = $this->catalog->duplicates(hash('sha256', $bytes));
        }
        return $this->record($operation, $result, $actor, $taxonomies, ['sha256' => hash('sha256', $bytes)]);
    }

    private function queryFromResult(array $result): array
    {
        $codes = data_get($result, 'data.codes', []);
        $code = collect($codes)->first(fn ($item) => !($item['contains_url'] ?? false) && ($item['checksum_valid'] ?? null) !== false);
        $gtin = $code['normalized_value'] ?? data_get($result, 'data.gtin_candidates.0');
        if (is_array($gtin)) $gtin = $gtin['normalized_value'] ?? null;
        return array_filter([
            'gtin' => $gtin, 'sku' => data_get($result, 'data.sku_candidate'),
            'model_code' => data_get($result, 'data.model_code_candidate'),
            'brand' => data_get($result, 'data.brand_candidate.name'),
            'text' => data_get($result, 'data.normalized_text'),
            'category_id' => data_get($result, 'data.classification.top_k.0.category_id'),
            'color_id' => data_get($result, 'data.colors.dominant_color.catalog_id'),
            'embedding' => data_get($result, 'data.embedding'),
        ], fn ($value) => $value !== null);
    }

    private function withInternalMatches(array $result, array $query): array
    {
        $candidates = $this->catalog->candidates($query);
        $matches = $this->catalog->exactMatches($query, $candidates);
        if (!$matches && $candidates && $query) {
            $ranked = $this->client->request('match', ['query' => $query, 'candidates' => $candidates]);
            foreach (data_get($ranked, 'data.matches', []) as $rank) {
                $candidate = collect($candidates)->firstWhere('candidate_id', (string) ($rank['candidate_id'] ?? ''));
                if ($candidate && ($rank['score'] ?? 0) > 0) $matches[] = $this->catalog->displayMatch($candidate, $rank);
            }
            if (isset($ranked['error'])) $result['warnings'][] = 'La búsqueda por similitud no está disponible; la comprobación de códigos exactos sí se realizó.';
        }
        $result['data']['internal_matches'] = $matches;
        $result['data']['internal_checked'] = true;
        $result['data']['external_search_allowed'] = !collect($matches)->contains('exact_identifier', true);
        return $result;
    }

    private function searchInput(Request $request, int $actor): array
    {
        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:100'], 'gtin' => ['nullable', 'string', 'max:30'],
            'brand' => ['nullable', 'string', 'max:150'], 'model_code' => ['nullable', 'string', 'max:150'],
            'query' => ['nullable', 'string', 'max:1000'], 'category_id' => ['nullable', 'integer'],
            'color_id' => ['nullable', 'integer'], 'analysis_run_id' => ['nullable', 'uuid'],
            'source_ids' => ['nullable', 'array', 'max:10'], 'source_ids.*' => ['string', 'max:100'],
        ]);
        $query = array_filter($validated, fn ($v) => $v !== null && $v !== '');
        foreach (['sku', 'gtin', 'model_code', 'query'] as $field) {
            if (isset($query[$field]) && preg_match('~(?:https?://|file://|ftp://|gopher://|data:)~i', $query[$field])) {
                throw ValidationException::withMessages([$field => 'Un QR con URL se muestra como referencia; no se visita ni se busca automáticamente.']);
            }
        }
        $query['text'] = $query['query'] ?? null;
        if (!empty($query['analysis_run_id'])) {
            $run = AiRun::whereKey($query['analysis_run_id'])->where('created_by', $actor)->firstOrFail();
            $query = [...$this->queryFromResult($run->result), ...$query];
            if ($run->embedding) $query['embedding'] = $run->embedding;
        }
        unset($query['analysis_run_id']);
        return $query;
    }

    public function match(Request $request)
    {
        $actor = $this->actor($request);
        $query = $this->searchInput($request, $actor);
        $result = $this->localResult();
        $result = $this->withInternalMatches($result, $query);
        return $this->record('match', $result, $actor, $this->catalog->taxonomies());
    }

    public function externalSearch(Request $request)
    {
        $actor = $this->actor($request);
        $query = $this->searchInput($request, $actor);
        if (!collect(['sku', 'gtin', 'model_code', 'query', 'text', 'brand'])->contains(fn ($key) => filled($query[$key] ?? null))) {
            throw ValidationException::withMessages(['query' => 'Escribe un código, marca, modelo o texto para buscar.']);
        }
        // This check is repeated server-side; a forged browser flag never bypasses the internal catalog.
        $checked = $this->withInternalMatches($this->localResult(), $query);
        if (!$checked['data']['external_search_allowed']) {
            $checked['data']['candidates'] = [];
            $checked['warnings'][] = 'Se encontró una coincidencia interna por identificador. Revisa esa prenda antes de buscar fuera.';
            return $this->record('external-search', $checked, $actor, $this->catalog->taxonomies());
        }
        $taxonomies = $this->catalog->taxonomies();
        $payload = array_filter([
            'gtin' => $query['gtin'] ?? null, 'brand' => $query['brand'] ?? null,
            'model_code' => $query['model_code'] ?? null,
            'query' => $query['query'] ?? $query['text'] ?? $query['sku'] ?? $query['brand'] ?? null,
            'category' => collect($taxonomies['categories'])->firstWhere('id', $query['category_id'] ?? null)['name'] ?? null,
            'color' => collect($taxonomies['colors'])->firstWhere('id', $query['color_id'] ?? null)['name'] ?? null,
            'source_ids' => $query['source_ids'] ?? [],
            'embedding' => data_get($query, 'embedding.values'),
            'model_id' => data_get($query, 'embedding.model_id'),
            'model_revision' => data_get($query, 'embedding.model_revision'),
        ], fn ($value) => $value !== null);
        $result = $this->client->request('external-search', $payload);
        $result['data']['internal_matches'] = $checked['data']['internal_matches'];
        $result['data']['internal_checked'] = true;
        $result['data']['external_search_allowed'] = true;
        return $this->record('external-search', $result, $actor, $this->catalog->taxonomies());
    }

    public function feedback(Request $request)
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'suggestion_id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['accepted', 'corrected', 'rejected'])],
            'confirmed_value' => ['nullable'], 'variant_index' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $suggestion = AiSuggestion::whereHas('run', fn ($q) => $q->where('created_by', $actor))->findOrFail($data['suggestion_id']);
        $value = $data['action'] === 'rejected' ? null : ($data['confirmed_value'] ?? $suggestion->value);
        if (!is_scalar($value) && $value !== null) throw ValidationException::withMessages(['confirmed_value' => 'El valor debe ser un dato de campo.']);
        if (is_string($value) && mb_strlen($value) > 10000) throw ValidationException::withMessages(['confirmed_value' => 'El valor es demasiado largo.']);
        $this->reviews->recordFeedback($suggestion, [...$data, 'confirmed_value' => $value], $actor);
        return response()->json(['saved' => true, 'commercial_data_saved' => false]);
    }

    public function preparePublication(Request $request)
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'approved' => ['required', 'accepted'], 'image' => ['nullable', 'required_without:external_run_id', 'prohibited_with:external_run_id', 'file', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'external_run_id' => ['nullable', 'required_without:image', 'prohibited_with:image', 'uuid'], 'candidate_index' => ['required_with:external_run_id', 'integer', 'min:0'],
            'image_index' => ['required_with:external_run_id', 'integer', 'min:0'], 'rights_status' => ['nullable', Rule::in(AiPublicationService::RIGHTS)],
        ]);
        $provenance = ['rights_status' => 'owned', 'rights_evidence' => 'Carga propia aprobada por administrador', 'approved_by' => $actor, 'approved_at' => now()->toIso8601String()];
        if (!empty($data['external_run_id'])) {
            $sourceRun = AiRun::whereKey($data['external_run_id'])->where('created_by', $actor)->where('operation', 'external-search')->firstOrFail();
            $candidate = data_get($sourceRun->result, 'data.candidates.'.$data['candidate_index']);
            $image = data_get($candidate, 'images.'.$data['image_index']);
            if (!$candidate || !$image || !in_array($image['rights_status'] ?? $candidate['rights_status'] ?? '', AiPublicationService::RIGHTS, true)) {
                throw ValidationException::withMessages(['image' => 'La fuente no autoriza publicar esta imagen.']);
            }
            $download = $this->client->request('external-image', [
                'source_id' => $candidate['source_id'] ?? $candidate['source'],
                'product_url' => $candidate['canonical_url'], 'original_url' => $image['original_url'], 'purpose' => 'publication',
            ]);
            if (isset($download['error'])) return response()->json($download);
            $remote = $download['data'];
            if (!in_array($remote['rights_status'] ?? '', AiPublicationService::RIGHTS, true) || empty($remote['rights_evidence'])) {
                throw ValidationException::withMessages(['image' => 'Falta evidencia vigente de derechos para publicar.']);
            }
            $bytes = $this->externalImageBytes($remote);
            if ($bytes === null) return response()->json($this->client->fallback((string) Str::uuid(), 'INVALID_RESPONSE'));
            unset($remote['image_base64']);
            $provenance = [...$provenance, ...$remote, 'source_run_id' => $sourceRun->id];
        } else {
            $bytes = $request->file('image')->getContent();
        }
        $id = (string) Str::uuid();
        $result = $this->client->request('prepare-publication', ['approved' => true, 'rights_status' => $provenance['rights_status'], 'approval_id' => $id], $bytes, $id);
        if (isset($result['error'])) return response()->json($result);
        try {
            // Binary derivatives are never stored in PostgreSQL, even transiently.
            $metadata = $result;
            $metadata['data']['derivatives'] = array_map(function ($derivative) {
                unset($derivative['content_base64']);
                return $derivative;
            }, $metadata['data']['derivatives'] ?? []);
            $run = $this->reviews->persist('prepare-publication', $metadata, $actor, ['sha256' => hash('sha256', $bytes)]);
            $prepared = $this->publication->stage($run, $result, $bytes, $provenance);
            return response()->json([...$run->result, 'run_id' => $run->id, 'data' => ['prepared_image' => $prepared]]);
        } catch (\Throwable) {
            Log::warning('mati_ai.publication_failed', ['request_id' => $id, 'operation' => 'prepare-publication']);
            return response()->json($this->client->fallback($id, 'PUBLICATION_FAILED'));
        }
    }

    public function externalReference(Request $request, string $run, int $candidate, int $image)
    {
        $sourceRun = AiRun::whereKey($run)->where('created_by', $this->actor($request))
            ->where('operation', 'external-search')->firstOrFail();
        $product = data_get($sourceRun->result, 'data.candidates.'.$candidate);
        $reference = data_get($product, 'images.'.$image);
        abort_unless(is_array($product) && is_array($reference), 404);
        // The caller supplies only persisted indexes, never a URL or a source override.
        $download = $this->client->request('external-image', [
            'source_id' => $product['source_id'] ?? $product['source'] ?? '',
            'product_url' => $product['canonical_url'] ?? '',
            'original_url' => $reference['original_url'] ?? '',
            'purpose' => 'reference',
        ]);
        abort_if(isset($download['error']), 502, 'La referencia no está disponible.');
        $bytes = $this->externalImageBytes($download['data']);
        abort_if($bytes === null, 502, 'La referencia no está disponible.');
        $mime = getimagesizefromstring($bytes)['mime'];
        $remote = $download['data'];
        $metadata = array_intersect_key($remote, array_flip([
            'sha256', 'width', 'height', 'fetched_at', 'rights_status', 'rights_evidence',
            'source_id', 'product_url', 'original_url', 'mime_type',
        ]));
        DB::transaction(function () use ($sourceRun, $candidate, $image, $metadata, $mime) {
            $locked = AiRun::whereKey($sourceRun->id)->lockForUpdate()->firstOrFail();
            $result = $locked->result;
            $result['data']['candidates'][$candidate]['images'][$image] = [
                ...$result['data']['candidates'][$candidate]['images'][$image],
                ...$metadata, 'mime_type' => $mime, 'reference_downloaded_at' => now()->toIso8601String(),
            ];
            $locked->update(['result' => $result]);
        });
        return response($bytes, 200, [
            'Content-Type' => $mime, 'Cache-Control' => 'private, no-store',
            'ETag' => '"'.$remote['sha256'].'"',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Content-Disposition' => 'inline; filename="referencia"',
        ]);
    }

    private function externalImageBytes(array $remote): ?string
    {
        $encoded = $remote['image_base64'] ?? null;
        if (!is_string($encoded) || strlen($encoded) > 16 * 1024 * 1024) return null;
        $bytes = base64_decode($encoded, true);
        if ($bytes === false || strlen($bytes) > 12 * 1024 * 1024
            || !is_string($remote['sha256'] ?? null) || !hash_equals($remote['sha256'], hash('sha256', $bytes))) return null;
        $info = @getimagesizefromstring($bytes);
        if (!$info || !in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)
            || $info[0] * $info[1] > 40_000_000) return null;
        return $bytes;
    }

    public function preparedImage(Request $request, string $token)
    {
        $run = $this->publication->approvedRun($token, $this->actor($request));
        $derivative = collect(data_get($run->result, 'data.derivatives', []))->firstWhere('profile', 'card');
        abort_unless($derivative && Storage::disk('local')->exists($derivative['path']), 404);
        return Storage::disk('local')->response($derivative['path'], null, ['Content-Type' => 'image/webp', 'Cache-Control' => 'private, no-store']);
    }

    private function record(string $operation, array $result, int $actor, array $taxonomies, array $input = [])
    {
        try {
            $run = $this->reviews->persist($operation, $result, $actor, $input);
            $suggestions = $this->reviews->suggestions($run, $taxonomies);
            $result['data'] = $suggestions['data'];
            $result['suggestions'] = $suggestions['suggestions'];
            $result['run_id'] = $run->id;
            foreach ($result['data']['candidates'] ?? [] as $candidateIndex => $candidate) {
                foreach ($candidate['images'] ?? [] as $imageIndex => $image) {
                    $result['data']['candidates'][$candidateIndex]['images'][$imageIndex]['preview_url'] = route('superadmin.ai.external-reference', [
                        'run' => $run->id, 'candidate' => $candidateIndex, 'image' => $imageIndex,
                    ], false);
                }
            }
            unset($result['data']['embedding']);
            return response()->json($result);
        } catch (\Throwable) {
            Log::warning('mati_ai.persistence_failed', ['request_id' => $result['request_id'], 'operation' => $operation]);
            return response()->json($this->client->fallback($result['request_id'], 'PERSISTENCE_UNAVAILABLE'));
        }
    }

    private function localResult(): array
    {
        return ['request_id' => (string) Str::uuid(), 'contract_version' => '1', 'status' => 'completed', 'data' => [], 'warnings' => [],
            'model' => ['model_id' => 'laravel-catalog', 'model_revision' => '1', 'pipeline_version' => '1'], 'human_review_required' => true];
    }
}
